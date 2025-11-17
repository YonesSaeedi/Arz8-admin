<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali;
use App\Http\Controllers\Users\UsersController as UserContrl;

class UsersController extends Controller
{
    public function listUsers(Request $request)
    {
        // parse dates
        $registeryDateStart = null; $registeryDateStop = null;
        if ($request->registeryDateStart) {
            try {
                $registeryDateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->registeryDateStart);
            } catch (\Exception $e) {}
        }
        if ($request->registeryDateStop) {
            try {
                $registeryDateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->registeryDateStop);
            } catch (\Exception $e) {}
        }

        $limit = ($request->perPage && $request->perPage <= 100) ? (int)$request->perPage : 10;
        $page = max(1, (int)($request->page ?? 1));

        $usersQuery = User::query();

        // apply base filters (search, status, etc.)
        $usersQuery = $this->filters($usersQuery, $request);

        // apply joins / aggregates depending on sortFilter and date range
        $usersQuery = $this->applySortFilter($usersQuery, $request, $registeryDateStart, $registeryDateStop);

        // === CORRECT TOTAL: count distinct users.id ===
        // clone query to avoid modifying original
        $countQuery = clone $usersQuery;

        // remove any select/groupBy that interfere and compute distinct count
        // use toBase() so Laravel returns a Query\Builder (not Eloquent) and value() works
        $total = (int) $countQuery->getQuery()
            ->clone() // ensure clone of underlying query (some Laravel versions)
            ->selectRaw('count(distinct users.id) as aggregate')
            ->getConnection()
            ->table(DB::raw("({$countQuery->toSql()}) as sub"))
            ->mergeBindings($countQuery->getQuery()) // bring bindings
            ->selectRaw('count(distinct id) as aggregate')
            ->value('aggregate');

        // Simpler and more portable alternative (if DB::raw subquery approach causes issues):
        // $total = (int) $countQuery->toBase()->selectRaw('count(distinct users.id) as aggregate')->value('aggregate');

        // === PAGINATION (manual, avoid paginate() internal count) ===
        $offset = ($page - 1) * $limit;

        // When there is a groupBy in the query, ->get() returns grouped rows; use get() after applying limit/offset
        $rows = $usersQuery->skip($offset)->take($limit)->get();

        // post-process each user (avatar, access, lastOrder, amount)
        $userController = new UserContrl();
        foreach ($rows as $user) {
            $info = json_decode($user->info ?? '{}');
            $user->avatar = $info->account_profile_img ?? null;
            $user->access = $userController->accessStatus($user);

            $order = Orders::where('id_user', $user->id)->orderBy('created_at', 'desc')->first();
            $user->lastOrder = $order ? $this->convertDate($order->created_at, 'd F Y H:i') : null;
            $user->amount = (int) ($user->amount ?? 0);
        }

        return response()->json([
            'users' => $rows,
            'total' => $total,
            'perPage' => $limit,
            'page' => $page,
        ]);
    }


    private function applySortFilter($q, $request, $start, $stop)
    {
        // join مشترک
        $q->leftJoin('users_wallets_internal', 'users_wallets_internal.id_user', 'users.id');

        // اطلاعات پایه
        $q->select(
            'users.id','users.email','users.name','users.family',
            'users.mobile','users.level','name_display','identification_img',
            'access','auth_img','address',
            'users_wallets_internal.value_num as balanceInternal'
        );

        // فیلترهای مختلف
        switch ($request->sortFilter) {

            case 'amountOrders':
                $q->leftJoin('orders','orders.id_user','users.id')
                    ->where('orders.status','success')
                    ->selectRaw('round(sum(amount)) as amount')
                    ->groupBy('users.id');
                break;

            case 'amountOrdersBuy':
                $q->leftJoin('orders','orders.id_user','users.id')
                    ->where('orders.status','success')
                    ->where('orders.type','buy')
                    ->selectRaw('round(sum(amount)) as amount')
                    ->groupBy('users.id');
                break;

            case 'amountOrdersSell':
                $q->leftJoin('orders','orders.id_user','users.id')
                    ->where('orders.status','success')
                    ->where('orders.type','sell')
                    ->selectRaw('round(sum(amount)) as amount')
                    ->groupBy('users.id');
                break;

            case 'countOrders':
                $q->leftJoin('orders','orders.id_user','users.id')
                    ->where('orders.status','success')
                    ->selectRaw('count(orders.id) as amount')
                    ->groupBy('users.id');
                break;

            case 'countTrades':
                $q->leftJoin('trades','trades.id_user','users.id')
                    ->where('trades.status','success')
                    ->selectRaw('count(trades.id) as amount')
                    ->groupBy('users.id');
                break;

            case 'countReferral':
                $q->leftJoin('users_referral','users_referral.id_user_caller','users.id')
                    ->selectRaw('count(users_referral.id) as amount')
                    ->groupBy('users.id');
                break;

            case 'countReferralTr':
                $q->leftJoin('users_referral_transaction','users_referral_transaction.id_user','users.id')
                    ->selectRaw('count(users_referral_transaction.id) as amount')
                    ->groupBy('users.id');
                break;

            case 'countReferralTrAmount':
                $q->leftJoin('users_referral_transaction','users_referral_transaction.id_user','users.id')
                    ->selectRaw('round(sum(amount)) as amount')
                    ->groupBy('users.id');
                break;

            case 'countWheel':
                $q->leftJoin('gift_wheel','gift_wheel.id_user','users.id')
                    ->selectRaw('count(gift_wheel.id) as amount')
                    ->groupBy('users.id');
                break;

            case 'countWheelAmount':
                $q->leftJoin('gift_wheel','gift_wheel.id_user','users.id')
                    ->selectRaw('round(sum(amount)) as amount')
                    ->groupBy('users.id');
                break;
        }

        // ************* فیلتر تاریخ برای هر گروه
        if (str_contains($request->sortFilter, 'Orders')) {
            if ($start) $q->where('orders.created_at', '>=', $start);
            if ($stop)  $q->where('orders.created_at', '<=', $stop);

        } elseif (str_contains($request->sortFilter, 'Trades')) {
            if ($start) $q->where('trades.created_at', '>=', $start);
            if ($stop)  $q->where('trades.created_at', '<=', $stop);

        } elseif (str_contains($request->sortFilter, 'ReferralTr')) {
            if ($start) $q->where('users_referral_transaction.created_at', '>=', $start);
            if ($stop)  $q->where('users_referral_transaction.created_at', '<=', $stop);

        } elseif (str_contains($request->sortFilter, 'Referral')) {
            if ($start) $q->where('users_referral.created_at', '>=', $start);
            if ($stop)  $q->where('users_referral.created_at', '<=', $stop);
        }

        return $q;
    }

    private function filters($query, $request)
    {
        $search = $request->search;

        // فیلدهای مرتب‌سازی
        $sortByMap = [
            'nameFamily' => 'name',
            'balanceInternalWallet' => 'balanceInternal',
            'registeryDate' => 'users.created_at',
            'id' => 'users.id'
        ];

        $sortBy = $sortByMap[$request->sortBy] ?? $request->sortBy;

        // ************* جستجوی عمومی
        if (!empty($search)) {
            $fields = [
                'users.id', 'email', 'name_display', 'name',
                'family', 'mobile', 'national_code', 'phone',
                'address', 'info'
            ];

            $query->where(function ($q) use ($fields, $search) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%$search%");
                }
            });
        }

        // فیلتر level
        if ($request->level)
            $query->where('level', $request->level);

        // ************* فیلتر وضعیت KYC
        if ($request->status) {
            switch ($request->status) {
                case 'pending':
                    $query->whereRaw('JSON_CONTAINS(identification_img, ?)', [json_encode(['status' => 'pending'])])
                        ->orWhereRaw('JSON_CONTAINS(auth_img, ?)', [json_encode(['status' => 'pending'])])
                        ->orWhereRaw('JSON_CONTAINS(address, ?)', [json_encode(['status' => 'pending'])]);
                    break;

                case 'pendingIdentification':
                    $query->whereRaw('JSON_CONTAINS(identification_img, ?)', [json_encode(['status'=>'pending'])]);
                    break;

                case 'pendingAuthImg':
                    $query->whereRaw('JSON_CONTAINS(auth_img, ?)', [json_encode(['status'=>'pending'])]);
                    break;

                case 'pendingLocation':
                    $query->whereRaw('JSON_CONTAINS(address, ?)', [json_encode(['status'=>'pending'])]);
                    break;

                default:
                    $query->where('access', $request->status);
            }
        }

        // ************* فیلترهای مالی
        if ($request->balanceStart)
            $query->where('users_wallets_internal.value_num', '>=', $request->balanceStart);

        if ($request->balanceStop)
            $query->where('users_wallets_internal.value_num', '<=', $request->balanceStop);

        // ************* فیلترهای جانبی
        switch ($request->otherFilter) {
            case 'emailVerified': $query->whereNotNull('email_verified_at'); break;
            case 'emailNotVerified': $query->whereNull('email_verified_at'); break;

            case 'referral':
                $query->rightJoin('users_referral', 'users_referral.id_user_referral', 'users.id');
                break;

            case 'referralNot':
                $ids = UserReferral::pluck('id_user_referral')->toArray();
                $query->whereNotIn('users.id', $ids);
                break;

            case 'notifActive': $query->whereNotNull('firebase_token'); break;
            case 'notifNotActive': $query->whereNull('firebase_token'); break;

            case '2faActive':
                $query->whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(['status'=>true])]);
                break;

            case '2faNotActive':
                $query->whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(['status'=>false])])
                    ->orWhereNull('twofa');
                break;

            case 'registerWebsite':
                $query->whereRaw('JSON_CONTAINS(info, ?)', [json_encode(['register_via'=>'website'])]);
                break;

            case 'registerAndroid':
                $query->whereRaw('JSON_CONTAINS(info, ?)', [json_encode(['register_via'=>'android'])]);
                break;

            case 'registerIos':
                $query->whereRaw('JSON_CONTAINS(info, ?)', [json_encode(['register_via'=>'ios'])]);
                break;
        }

        // ************* سورت
        if ($sortBy)
            $query->orderBy($sortBy, $request->sortDesc ? 'desc' : 'asc');

        return $query;
    }


    function chartData(Request $request){
        $result = (object)array();
        $all_users = User::count();
        $result->chart_user_via = User::select('info->register_via as via')->selectRaw('count(users.id) as sum')
            ->whereNotNull('info->register_via')->groupBy('via')->get();

        $result->pie_user_level = User::selectRaw('CONVERT(level, CHAR(50))  as name,count(users.id) as value')->groupBy('level')->get();
        $result->pie_user_province = User::whereNotNull('info->birthplace')->select('info->birthplace->province as name')
                ->selectRaw('count(users.id) as value')->groupBy('info->birthplace->province')->orderBy('value','desc')->get();
        $result->pie_user_city = User::whereNotNull('info->birthplace')->select('info->birthplace->city as name')
            ->selectRaw('count(users.id) as value')->groupBy('info->birthplace->city')->orderBy('value','desc')->limit('15')->get();


        // status twofa
        $result->pie_twofa = (object)array();
        $twofa_active = User::whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('status' => true))])->count();
        $result->pie_twofa->status = [['name'=>'ورود دو مرحله ای فعال','value'=>$twofa_active],['name'=>'بدون ورود دو مرحله ای','value'=>$all_users-$twofa_active]];
        $twofa_sms = User::whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('type' => 'sms','status'=>true))])->count();
        $twofa_google = User::whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('type' => 'google','status'=>true))])->count();
        $twofa_email = User::whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('type' => 'email','status'=>true))])->count();
        $twofa_telegram = User::whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('type' => 'telegram','status'=>true))])->count();
        $result->pie_twofa->active_via = [['name'=>'Sms','value'=>$twofa_sms],['name'=>'Google','value'=>$twofa_google],
                                          ['name'=>'Email','value'=>$twofa_email],['name'=>'Telegram','value'=>$twofa_telegram]];

        $result->chart_user_via_line = (object)array();
        $result->chart_user_via_line->lable = [];
        $result->chart_user_via_line->data = ['all'=>[],'android'=>[], 'ios'=>[], 'website'=>[]];
        $six_months_ago = Jalali\Jalalian::now()->subMonths(12);
        $six_months_ago = $six_months_ago->subDays($six_months_ago->getDay() - 1);
        for ($i = 1; $i <= 12; $i++) {
            $month = $six_months_ago->addMonths($i);
            $date_start = Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d 00:00:00', $month->format('Y-m-d 00:00:00'));
            $date_stop = Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d 00:00:00', $month->addMonths(1)->format('Y-m-d 00:00:00'));
            array_push($result->chart_user_via_line->lable, $month->format('F Y'));

            $users = User::where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->count();
            array_push($result->chart_user_via_line->data['all'], $users);

            $users = User::where('created_at', '>=', $date_start)->whereJsonContains('info->register_via','android')->where('created_at', '<', $date_stop)->count();
            array_push($result->chart_user_via_line->data['android'], $users);


            $users = User::where('created_at', '>=', $date_start)->where('info->register_via','ios')->where('created_at', '<', $date_stop)->count();
            array_push($result->chart_user_via_line->data['ios'], $users);

            $users = User::where('created_at', '>=', $date_start)->where('info->register_via','website')->where('created_at', '<', $date_stop)->count();
            array_push($result->chart_user_via_line->data['website'], $users);

        }

        return $result;
    }
}
