<?php
namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;

use App\Models\AdminHesab;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Morilog\Jalali;
use App\Models\AdminUser;
use Auth;
use PragmaRX\Google2FAQRCode\Google2FA;

class AdminsController extends Controller
{
    public $access;
    public function __construct()
    {
        $this->access = array('is_block'=>false,'logout'=>false, 'block_reason'=>'',
            'section_access'=> (object)[
                'users'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                    'wallets'=>false,
                    'orders'=>false,
                    'trades'=>false,
                    'tr-internal'=>false,
                    'tr-crypto'=>false,
                    'cardbank'=>false,
                    'referral'=>false,
                    'gift'=>false,
                    'notification'=>false,
                    'tickets'=>false,
                    'login-history'=>false,
                    'gift-wheel'=>false,
                    'call-history'=>false,
                    'wallets-users'=>false,
                    'deposit-id'=>false,
                    'gift-card'=>false,
                ],
                'orders'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'trades'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'tickets'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'tr-internal'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'tr-crypto'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'cardbank'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'referral'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'tr-referral'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'gift'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'gift-wheel'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'payment-gateway'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting-crypto'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting-cryptos-balance'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting-cryptos-little'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting-cryptos-wallets'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting-cryptos-auto-trade'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting-networks'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting-markets'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting-notification'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'setting'=> (object)[
                    'list' => false,
                    'general'=> false,
                    'payment-gateway'=> false,
                    'auto-deposit'=> false,
                    'proxy'=> false,
                    'levels'=> false,
                    'levels_account'=> false,
                    'receipt'=> false,
                    'pop-up'=> false,
                    'alert'=> false,
                    'binance'=> false,
                    'tether'=> false,
                    'finnotech'=> false,
                    'application'=> false,
                    'banner'=> false,
                    'perfectmoney'=> false,
                    'pmvoucher'=> false,
                    'payeer'=> false,
                    'psvouchers'=> false,
                    'utopia'=> false,
                ],
                'admins'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'audit'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'audit-digital'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'audit-wage'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'audit-factor'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'login-history'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'reports-users'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'reports-orders'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'reports-trades'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'reports-referral'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'reports-shaparak'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'reports-payment-gateway'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'call-history'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'wallets-users'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'deposit-id'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
                'gift-card'=> (object)[
                    'list'=> false,
                    'single'=> false,
                    'edit'=> false,
                ],
            ]
        );
    }

    function listUsers(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;
        $offset = (($request->page - 1) ?? 0) *$limit;

        $result = (object)array();
        $users = AdminUser::query();

        // Filters
        $users = self::filters($users,$request);
        $usersCount = $users->count();

        $users->select('admins.*');
        $users->limit($limit)->offset($offset);
        $users = $users->get();
        foreach ($users as $user){
            $access = self::setAccess($user);
            $user->registeryDate = $this->convertDate($user->created_at, 'd F Y');
            $user->access = $access;
            $user->access_count = 9;
        }
        $result->users = $users;
        $result->total = $usersCount;
        //$result->statistic = self::statistic();
        return response()->json($result);
    }

    private function setAccess($user){
        $access = json_decode($user->access??'{}');
        foreach ($this->access as $key => $value){
            $access->{$key} = isset($access->{$key})? $access->{$key} : $value;
            if(is_object($value)) {
                foreach ($value as $key2 => $value2) {
                    $access->{$key}->{$key2} = isset($access->{$key}->{$key2}) ? $access->{$key}->{$key2} : $value2;
                    if(is_object($value2)) {
                        foreach ($value2 as $key3 => $value3) {
                            $access->{$key}->{$key2}->{$key3} = isset($access->{$key}->{$key2}->{$key3}) ? $access->{$key}->{$key2}->{$key3} : $value3;
                        }
                    }
                }
            }
        }
        $user->access = json_encode($access);
        $user->save();
        return $access;
    }

    function filters($users,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'registeryDate': $sortBy = 'created_at'; break;
            case 'id': $sortBy = 'id'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['id', 'email', 'name', 'mobile'];
            $users = $users->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if(isset($sortBy))
            $users->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $users;
    }

    function addAdmin(Request $request){
        $emailExist = AdminUser::where('email',$request->email)->first();
        if(isset($emailExist)){
            return response()->json(array('status'=>false,'msg'=>'ایمیل تکراری است و قبلا ثبت شده است.'), 400);
        }

        $user = new AdminUser();
        $user->email = $request->email;
        $user->name = $request->name;
        $user->mobile = $request->mobile;
        $user->password = \Hash::make($request->password);
        $user->role = $request->role;
        $user->save();
        return response()->json(array('status'=>true ,'msg'=>'با موفقیت ثبت شد.'));
    }

    function singleAdmin(Request $request){
        $admin = AdminUser::where('id',$request->id)->first();
        $admin->hesab = AdminHesab::where('id_admin',$request->id)->get();
        $admin->google2fa = isset($admin->google2fa) ? true : false;
        unset($admin->data,$admin->created_at,$admin->updated_at);
        return $admin;
    }

    function editAdmin(Request $request){
        $admin = AdminUser::find($request->id);
        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->mobile = $request->mobile;
        $admin->role = $request->role;
        if(isset($request->password))
            $admin->password = \Hash::make($request->password);

        if($request->twofa == 'sms'){
            $admin->google2fa = null;
            $admin->sms2fa = 1;
        }

        // access
        $access = $request->access;
        foreach ($access['section_access'] as $key=>$value){
            foreach ($value as $k=>$a)
                $access['section_access'][$key][$k] = (boolean)$a;
        }
        if(json_decode($admin->access,true)['section_access'] != $access['section_access'] || isset($request->password)
               || $request->role != $admin->role)
            $access['logout'] = true;
        $admin->access = json_encode($access);
        $admin->save();

        foreach ($request->hesab as $hesab){
            $hesab = (object)$hesab;
            if(isset($hesab->id))
                $AdminHesab = AdminHesab::find($hesab->id);
            else
                $AdminHesab = new AdminHesab;
            $AdminHesab->stock = str_replace(',','',$hesab->stock);
            $AdminHesab->description = $hesab->description;
            $AdminHesab->name = $hesab->name;
            $AdminHesab->id_admin = $admin->id;
            $AdminHesab->save();
        }
        return response()->json(array('status'=>true ,'msg'=>'با موفقیت ثبت شد.'));
    }


    function listLog(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;
        $offset = (($request->page - 1) ?? 0) *$limit;

        $result = (object)array();
        $logs = AdminLog::query();

        // Filters
        $logs = self::filtersLog($logs,$request);
        $count = $logs->count();

        $logs->leftJoin('admins','admins.id','admin_log.id_admin');
        $logs->select('admin_log.*','admins.name');
        $logs->limit($limit)->offset($offset);
        $logs = $logs->get();
        foreach ($logs as $log){
            $log->registeryDate = $this->convertDate($log->created_at, 'd F Y - H:i:s');
        }
        $result->users = $logs;
        $result->total = $count;
        $result->admins = AdminUser::select('id','name','email')->get();
        return response()->json($result);
    }

    function filtersLog($logs,$request){
        $search = $request->search;
        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'registeryDate': $sortBy = 'admin_log.created_at'; break;
            case 'id': $sortBy = 'admin_log.id'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['admin_log.id', 'text','admin_log.data'];
            $logs = $logs->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->registeryDateStart)) {
            try{
                $registeryDateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->registeryDateStart);
                $logs->where('admin_log.created_at','>=', $registeryDateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->registeryDateStop)) {
            try{
                $registeryDateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->registeryDateStop);
                $logs->where('admin_log.created_at','<=', $registeryDateStop);
            }catch(\Exception $e){}
        }
        if (isset($request->admin))
            $logs->where('id_admin',$request->admin);

        if (isset($request->key))
            $logs->where('key',$request->key);

        if(isset($sortBy))
            $logs->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $logs;
    }

    function singleLog(Request $request){
        $log = AdminLog::find($request->id);
        $log->data = \Crypt::decryptString($log->data);
        return response()->json(array('status'=>true ,'msg'=>'', 'log'=> $log));
    }

    function removeHesab(Request $request){
        try{
            AdminHesab::where('id',$request->id)->delete();
            return response()->json(array('status'=>true ,'msg'=>'حذف با موفقیت انجام شد!'));
        }catch (\Exception $e){
            return response()->json(array('status'=>false ,'msg'=>'حذف امکان پذیر نیست!'));
        }
    }
}
