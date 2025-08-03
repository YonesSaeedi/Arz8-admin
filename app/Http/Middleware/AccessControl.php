<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

class AccessControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public $access;
    function __construct() {
        $this->access = [
            'users/info/*' => ['users','single'],
            'users/add-new' => ['users','list'],
            'users/edit/level1/*' => ['users','edit'],
            'users/edit/level2/*' => ['users','edit'],
            'users/edit/level3/*' => ['users','edit'],
            'users/edit/level4/*' => ['users','edit'],
            'users/edit/settings/*' => ['users','edit'],
            'users/block/*' => ['users','edit'],
            'users/remove/*' => ['users','edit'],
            'users/wallets/*/crypto-transaction' => ['users','wallets'],
            'users/wallets/*/internal-transaction' => ['users','wallets'],

            'orders/list' => ['orders','list'],
            'orders/info/*' => ['orders','single'],

            'trades/list' => ['trades','list'],
            'trades/info/*' => ['trades','single'],

            'internal/list' => ['tr-internal','list'],
            'internal/info/*' => ['tr-internal','single'],
            'internal/confirm/*' => ['tr-internal','edit'],
            'internal/reject/*' => ['tr-internal','edit'],

            'crypto/list' => ['tr-crypto','list'],
            'crypto/new' => ['tr-crypto','list'],
            'crypto/edit/*' => ['tr-crypto','edit'],
            'crypto/info/*' => ['tr-crypto','single'],

            'card-bank/list' => ['cardbank','list'],
            'card-bank/info/*' => ['cardbank','single'],
            'card-bank/edit/*' => ['cardbank','edit'],
            'card-bank/status/*' => ['cardbank','edit'],
            'card-bank/inquiry/*/card' => ['cardbank','edit'],
            'card-bank/inquiry/*/iban' => ['cardbank','edit'],

            'gift/list' => ['gift','list'],
            'gift/add-new' => ['gift','list'],
            'gift/statistic' => ['gift','list'],
            'gift/edit' => ['gift','edit'],
            'gift/remove/*' => ['gift','edit'],
            'gift/users/remove/*' => ['gift','edit'],
            'gift/info/*' => ['gift','single'],

            'withdraw/list' => ['payment-gateway','list'],
            'withdraw/info/*' => ['payment-gateway','single'],

            'setting/crypto/balance' => ['setting-cryptos-balance','list'],
            'setting/crypto/little' => ['setting-cryptos-little','list'],
            'setting/crypto/wallets' => ['setting-cryptos-wallets','list'],
            'setting/crypto/auto-trade' => ['setting-cryptos-auto-trade','list'],
            'setting/crypto/list' => ['setting-crypto','list'],
            'setting/crypto/info/*' => ['setting-crypto','single'],
            'setting/crypto/edit/*' => ['setting-crypto','edit'],

            'setting/network/list' => ['setting-networks','list'],
            'setting/network/edit/*' => ['setting-networks','edit'],
            'setting/network/remove/*' => ['setting-networks','edit'],

            'setting/markets/list' => ['setting-markets','list'],
            'setting/markets/edit/*' => ['setting-markets','edit'],
            'setting/markets/remove/*' => ['setting-markets','edit'],
            'setting/markets/info/*' => ['setting-markets','single'],

            'settings' => ['setting','list'],

            'admins/list' => ['admins','list'],
            'admins/admin/*' => ['admins','single'],
            'admins/admin/edit/*' => ['admins','edit'],


            'reports/users/list' => ['reports-users','list'],
            'reports/users/chart' => ['reports-users','list'],
            'reports/orders/chart' => ['reports-orders','list'],
            'reports/trades/chart' => ['reports-trades','list'],
            'reports/referral/chart' => ['reports-referral','list'],
        ];
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $access = json_decode($user->access);
        if($access->is_block == true || $access->logout == true){
            $access->logout = false;
            $user->access = json_encode($access);
            $user->save();
            auth()->invalidate(true);
            return response()->json(array('status'=> false, 'msg'=>'لطفا مجدد لاگین نمایید.'), 401);
        }

        if($user->role != 'admin' ){
            $uri = str_replace('api/','',$request->path());
            $uri = (preg_replace('/[0-9]+/', '*', $uri));

            /*
            $arr = explode('/',$uri);
            foreach($arr as $a){
                if(is_numeric($a))
                    $uri = str_replace('/'.$a,'/*',$uri);
            }
            */

            $status_access = true;
            if(isset($this->access[$uri])){
                $status_access = $access->section_access;
                for($i=0; $i<count($this->access[$uri]); $i++)
                    $status_access = $status_access->{$this->access[$uri][$i]};
            }

            if(!$status_access)
                return response()->json(array('status'=> false, 'msg'=>'شما اجازه دسترسی به این بخش را ندارید!'), 408);
        }

        return $next($request);
    }
}
