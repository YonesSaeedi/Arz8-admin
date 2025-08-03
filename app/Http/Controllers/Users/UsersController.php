<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\GiftUser;
use App\Models\Orders;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\Trades;
use App\Models\TransactionCrypto;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\UserCardBank;
use App\Models\UserReferral;
use App\Models\WalletsCrypto;
use App\Models\WalletsInternal;
use Illuminate\Http\Request;
use Morilog\Jalali;
use DB;

class UsersController extends Controller
{
    function listUsers(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $users = User::query();

        // Filters
        $users = self::filters($users,$request);
        $usersCount = $users->count();

        $users->leftJoin('users_wallets_internal','users_wallets_internal.id_user','users.id')->groupBy('users.id');
        $users->select('users.id','name_display','email','name','family','mobile','level','level_account','identification_img','access','auth_img','address','users.created_at',
                        'users_wallets_internal.value_num as balanceInternal');
        $users = $users->paginate($limit)->items();

        foreach ($users as $user) {
            $info = json_decode($user->info ?? '{}');
            $user->avatar = $info->account_profile_img ?? null;
            $user->registeryDate = $this->convertDate($user->created_at, 'd F Y H:i');

            $user->access = self::accessStatus($user);

            unset($user->identification_img,$user->auth_img,$user->address,$user->users,$user->created_at);
        }
        $result->users = $users;
        $result->total = $usersCount;

        return response()->json($result);
    }


    function filters($users,$request){
        if (isset($request->registeryDateStart)) {
            try{
                $registeryDateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->registeryDateStart);
                $users->where('created_at','>=', $registeryDateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->registeryDateStop)) {
            try{
                $registeryDateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->registeryDateStop);
                $users->where('created_at','<=', $registeryDateStop);
            }catch(\Exception $e){}
        }

        if (isset($request->ids))
            $users->whereIn('users.id', $request->ids);
        if (isset($request->level))
            $users->where('level', $request->level);
        if (isset($request->levelAccount))
            $users->where('level_account', $request->levelAccount);
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
            case '2faTelegramActive':
                $users->whereRaw('JSON_CONTAINS(twofa, ?)', [json_encode(array('type' => 'telegram','status'=>true))]);
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
            case 'portfolio':
                $users->whereRaw('JSON_CONTAINS(settings, ?)', [json_encode(array('portfolio' => true))]);
                break;
        }


        $search = $request->search;
        if (!empty($search)) {
            $fields = ['users.id', 'email', 'name_display', 'name', 'family', 'mobile', 'national_code', 'phone', 'address', 'info'];
            $users->where(function ($query) use ($search, $fields) {
                $searchTerm = '%' . $search . '%';
                $query->whereRaw(
                    '(' . implode(' OR ', array_map(function ($field) use ($searchTerm) {
                        return "$field LIKE ?";
                    }, $fields)) . ')',
                    array_fill(0, count($fields), $searchTerm)
                );
            });
        }

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'balanceInternalWallet': $sortBy = 'balanceInternal'; break;
            case 'registeryDate': $sortBy = 'users.created_at'; break;
            case 'id': $sortBy = 'users.id'; break;
            default: $sortBy = $request->sortBy;
        }
        $sortDirection = $request->sortDesc ? 'DESC' : 'ASC';
        if ($request->sortBy === 'balanceInternalWallet') {
            // مرتب‌سازی خاص برای balanceInternal که nullها برن آخر
            $users->orderByRaw("balanceInternal IS NULL, balanceInternal $sortDirection");
        } else {
            $users->orderBy($sortBy, $sortDirection);
        }
        return $users;
    }

    function statistic() {
        $statistic = (object) [];

        // تعداد کل کاربران
        $statistic->total_users = User::count();

        // تعداد کاربران با ایمیل تایید شده
        $statistic->email_users = User::whereNotNull('email_verified_at')->count();

        // یکتا کردن دو لیست فقط با یک کوئری پیچیده‌تر ولی سریع‌تر:
        $statistic->balance_users = DB::table(function ($query) {
            $query->select('id_user')
                ->from('users_wallets_crypto')
                ->where('value_num', '>', 0)
                ->union(
                    DB::table('users_wallets_internal')
                        ->select('id_user')
                        ->where('value_num', '>', 0)
                );
        }, 'sub')->distinct()->count('id_user');

        // مجموع موجودی داخلی
        $statistic->balance_all_amount = WalletsInternal::where('value_num', '>', 0)->sum('value_num');

        // کاربران فعال در سفارش یا معامله
        $statistic->active_users = DB::table(function ($query) {
            $query->select('id_user')
                ->from('orders')
                ->union(
                    DB::table('trades')
                        ->select('id_user')
                );
        }, 'sub')->distinct()->count('id_user');

        return response()->json($statistic);
    }


    function addUser(Request $request){
        $emailExist = User::where('email',$request->email)->first();
        if(isset($emailExist)){
            return response()->json(array('status'=>false,'msg'=>'ایمیل تکراری است و قبلا ثبت شده است.'), 400);
        }

        $user = new User();
        $user->email = $request->email;
        $user->name = $request->name;
        $user->family = $request->family;
        $user->name_display = $request->name.' '.$request->family;
        $user->password = \Hash::make($request->password);
        $user->save();

        $data = $request->all();
        unset($data['password']);
        self::logSave('users.addAndRemeve',$data,'افزودن کاربر '.$request->email,$request->ip());
        return response()->json(array('status'=>true ,'msg'=>'با موفقیت ثبت شد.'));
    }

    function accessStatus($user){
        $identification = json_decode($user->identification_img) ?? (object)array();
        if (isset($identification->status) && $identification->status == 'pending') {
            return 'pending';
        }

        $auth_img = json_decode($user->auth_img) ?? (object)array();
        if (isset($auth_img->status) && $auth_img->status == 'pending') {
            return 'pending';
        }

        $address = json_decode($user->address) ?? (object)array();
        if (isset($address->status) && $address->status == 'pending') {
            return 'pending';
        }

        return $user->access;
    }


    function block(Request $request){
        $user = User::find($request->id);
        if($request->access == 'blocked')
            $result = array('status'=>true ,'msg'=>'با موفقیت بلاک شد.');
        elseif ($request->access == 'accessible')
            $result = array('status'=>true ,'msg'=>'با موفقیت آنبلاک شد.');
        $user->access = $request->access;

        // reason
        // $info->block_reason
        $user->save();
        self::logSave('users.edit',$request->all(),($request->access == 'blocked'?'بلاک':'آنبلاک').' کاربر '.$user->email,$request->ip());
        return response()->json($result);
    }

    function remove(Request $request){
        if (\Auth::user()->role == 'admin'):
            $user = User::find($request->id);
            try{
                $tickets = Ticket::where('id_user',$user->id)->get();
                foreach ($tickets as $ticket){
                    $ticket_message = TicketMessage::where('id_ticket',$ticket->id)->whereNotNull('file_link')->get();
                    foreach($ticket_message as $message){
                        if (file_exists(($message->file_link)))
                            unlink($message->file_link);
                    }
                    TicketMessage::where('id_ticket',$ticket->id)->delete();
                }
                Ticket::where('id_user',$user->id)->delete();
                WalletsCrypto::where('id_user',$user->id)->delete();
                WalletsInternal::where('id_user',$user->id)->delete();
                TransactionCrypto::where('id_user',$user->id)->delete();
                TransactionInternal::where('id_user',$user->id)->delete();
                Orders::where('id_user',$user->id)->delete();
                Trades::where('id_user',$user->id)->delete();
                GiftUser::where('id_user',$user->id)->delete();
                UserCardBank::where('id_user',$user->id)->delete();
                sleep(1);

                $user->delete();
                $directory = 'uploads/Users/'.$user->created_at->year.'/'.$user->created_at->month.'/'. $user->id;
                \File::deleteDirectory($directory);

                self::logSave('users.addAndRemeve',$user->toArray(),'حذف کاربر '.$user->email,$request->ip());
                return array('status'=>true ,'msg'=>'با موفقیت حذف شد.');
            }catch (\Exception $e){
                //dd($e);
                return array('status'=>false ,'msg'=>'حذف به دلیل داده های ثبت شده به این کاربر امکان پذیر نیست. مجدد تلاش کنید.');
            }
        else:
            return array('status'=>false ,'msg'=>'حذف فقط توسط ادمین کل امکان پذیر است.');
        endif;
    }

    function note(Request $request){
        $user = User::find($request->id);
        $info = json_decode($user->info);
        $info->note = $request->note;
        $user->info = json_encode($info);
        $user->save();
        self::logSave('users.edit',$request->all(),'تغییر در یادداشت کاربر '.$user->email,$request->ip());
        return array('status'=>true ,'msg'=>'با موفقیت تغییر یافت.');
    }
}
