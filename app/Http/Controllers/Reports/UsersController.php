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
    function listUsers(Request $request)
    {
        if (isset($request->registeryDateStart)) {
            try{
                $registeryDateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->registeryDateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->registeryDateStop)) {
            try{
                $registeryDateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->registeryDateStop);
            }catch(\Exception $e){}
        }

        $userController = new UserContrl();

        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $users = User::query();

        // Filters
        $users = self::filters($users,$request);
        $usersCount = $users->count();
        $users->leftJoin('users_wallets_internal','users_wallets_internal.id_user','users.id')->groupBy('users.id');
        $users->select('users.id','users.email','users.name','users.family','users.mobile','users.level','name_display','identification_img','access','auth_img','address','users_wallets_internal.value_num as balanceInternal');

        switch ($request->sortFilter){
            case 'amountOrders':
                $users->leftJoin('orders','orders.id_user','users.id')->groupBy('users.id');
                $users->where('orders.status','success');
                $users->selectRaw('round(sum(amount)) as amount');
                break;
            case 'amountOrdersBuy':
                $users->leftJoin('orders','orders.id_user','users.id')->groupBy('users.id');
                $users->where('orders.status','success')->where('orders.type','buy');
                $users->selectRaw('round(sum(amount)) as amount');
                break;
            case 'amountOrdersSell':
                $users->leftJoin('orders','orders.id_user','users.id')->groupBy('users.id');
                $users->where('orders.status','success')->where('orders.type','sell');
                $users->selectRaw('round(sum(amount)) as amount');
                break;
            case 'countOrders':
                $users->leftJoin('orders','orders.id_user','users.id')->groupBy('users.id');
                $users->where('orders.status','success');
                $users->selectRaw('count(orders.id) as amount');
                break;
            case 'countTrades':
                $users->leftJoin('trades','trades.id_user','users.id')->groupBy('users.id');
                $users->where('trades.status','success');
                $users->selectRaw('count(trades.id) as amount');
                break;
            case 'countReferral':
                $users->leftJoin('users_referral','users_referral.id_user_caller','users.id')->groupBy('users.id');
                $users->selectRaw('count(users_referral.id) as amount');
                break;
            case 'countReferralTr':
                $users->leftJoin('users_referral_transaction','users_referral_transaction.id_user','users.id')->groupBy('users.id');
                $users->selectRaw('count(users_referral_transaction.id) as amount');
                break;
            case 'countReferralTrAmount':
                $users->leftJoin('users_referral_transaction','users_referral_transaction.id_user','users.id')->groupBy('users.id');
                $users->selectRaw('round(sum(amount)) as amount');
                break;
            case 'countWheel':
                $users->leftJoin('gift_wheel','gift_wheel.id_user','users.id')->groupBy('users.id');
                $users->selectRaw('count(gift_wheel.id) as amount');
                break;
            case 'countWheelAmount':
                $users->leftJoin('gift_wheel','gift_wheel.id_user','users.id')->groupBy('users.id');
                $users->selectRaw('round(sum(amount)) as amount');
                break;
            default:
                break;
        }

        if(strpos($request->sortFilter,"Orders") !== false){
            if (isset($registeryDateStart))
                $users->where('orders.created_at','>=',$registeryDateStart);
            if (isset($registeryDateStop))
                $users->where('orders.created_at','<=',$registeryDateStop);

        }else if(strpos($request->sortFilter,"Trades") !== false){
            if (isset($registeryDateStart))
                $users->where('trades.created_at','>=',$registeryDateStart);
            if (isset($registeryDateStop))
                $users->where('trades.created_at','<=',$registeryDateStop);

        }else if(strpos($request->sortFilter,"ReferralTr") !== false){
            if (isset($registeryDateStart))
                $users->where('users_referral_transaction.created_at','>=',$registeryDateStart);
            if (isset($registeryDateStop))
                $users->where('users_referral_transaction.created_at','<=',$registeryDateStop);

        }else if(strpos($request->sortFilter,"Referral") !== false){
            if (isset($registeryDateStart))
                $users->where('users_referral.created_at','>=',$registeryDateStart);
            if (isset($registeryDateStop))
                $users->where('users_referral.created_at','<=',$registeryDateStop);
        }


        $users = $users->paginate($limit)->items();
        foreach ($users as $user) {
            $info = json_decode($user->info ?? '{}');
            $user->avatar = $info->account_profile_img ?? null;
            $user->access = $userController->accessStatus($user);
            $order = Orders::where('id_user',$user->id)->orderBy('created_at','desc')->first();
            if(isset($order))
                $user->lastOrder = $this->convertDate($order->created_at, 'd F Y H:i');
            else
                $user->lastOrder = null;

            $user->amount = (int)$user->amount;
        }
        $result->users = $users;
        $result->total = $usersCount;
        return response()->json($result);
    }
    function filters($users,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'balanceInternalWallet': $sortBy = 'balanceInternal'; break;
            case 'registeryDate': $sortBy = 'users.created_at'; break;
            case 'id': $sortBy = 'users.id'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['users.id', 'email', 'name_display', 'name', 'family', 'mobile', 'national_code', 'phone', 'address', 'info'];
            $users = $users->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->level))
            $users->where('level', $request->level);
        if (isset($request->status)) {
            if ($request->status == 'pending')
                $users->whereRaw('JSON_CONTAINS(identification_img, ?)', [json_encode(array('status' => 'pending'))])
                      ->orWhereRaw('JSON_CONTAINS(auth_img, ?)', [json_encode(array('status' => 'pending'))])
                      ->orWhereRaw('JSON_CONTAINS(address, ?)', [json_encode(array('status' => 'pending'))]);
            elseif ($request->status == 'pendingIdentification')
                $users->whereRaw('JSON_CONTAINS(identification_img, ?)', [json_encode(array('status' => 'pending'))]);
            elseif ($request->status == 'pendingAuthImg')
                $users->whereRaw('JSON_CONTAINS(auth_img, ?)', [json_encode(array('status' => 'pending'))]);
            elseif ($request->status == 'pendingLocation')
                $users->whereRaw('JSON_CONTAINS(address, ?)', [json_encode(array('status' => 'pending'))]);
            else
                $users->where('access', $request->status);
        }

        if (isset($request->balanceStart))
            $users->where('users_wallets_internal.value_num','>=', $request->balanceStart);
        if (isset($request->balanceStop))
            $users->where('users_wallets_internal.value_num','<=', $request->balanceStop);

        switch ($request->otherFilter){
            case 'emailVerified':
                $users->whereNotNull('email_verified_at');
                break;
            case 'emailNotVerified':
                $users->whereNull('email_verified_at');
                break;
            case 'referral':
                $users->rightJoin('users_referral','users_referral.id_user_referral','users.id');
                break;
            case 'referralNot':
                $arrayId = UserReferral::pluck('id_user_referral')->toArray();
                $users->whereNotIn('users.id',$arrayId);
                break;
            case 'notifActive':
                $users->whereNotNull('firebase_token');
                break;
            case 'notifNotActive':
                $users->whereNull('firebase_token');
                break;
            case '2faActive':
                $users->whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('status' => true))]);
                break;
            case '2faNotActive':
                $users->whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('status' => false))])->orWhereNull('twofa');
                break;
            case '2faSmsActive':
                $users->whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('type' => 'sms','status'=>true))]);
                break;
            case '2faEmailActive':
                $users->whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('type' => 'email','status'=>true))]);
                break;
            case '2faGoogleActive':
                $users->whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('type' => 'google','status'=>true))]);
                break;
            case 'registerWebsite':
                $users->whereRaw('JSON_CONTAINS(info, ?)', [json_encode(array('register_via' => 'website'))]);
                break;
            case 'registerAndroid':
                $users->whereRaw('JSON_CONTAINS(info, ?)', [json_encode(array('register_via' => 'android'))]);
                break;
            case 'registerIos':
                $users->whereRaw('JSON_CONTAINS(info, ?)', [json_encode(array('register_via' => 'ios'))]);
                break;
            case 'digitalCurrencyAccess':
                $users->whereRaw('JSON_CONTAINS(settings, ?)', [json_encode(array('access_digital_money' => true))]);
                break;
        }

        if(isset($sortBy))
            $users->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $users;
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
