<?php

namespace App\Http\Controllers\Users;

use App\Models\TransactionCrypto;
use App\Models\UsersWalletsCryptoDeposit;
use Illuminate\Http\Request;
use Crypt;
use DB;
use Morilog\Jalali;

class WalletsCryptoDepositController extends UsersController
{
    function getlist(Request $request){

        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = UsersWalletsCryptoDeposit::query();
        $query->leftJoin('cryptocurrency_wallets','users_wallets_crypto_deposit.id_wallet','cryptocurrency_wallets.id');
        $query->leftJoin('cryptocurrency','users_wallets_crypto_deposit.id_crypto','cryptocurrency.id');
        $query->leftJoin('cryptocurrency_network','users_wallets_crypto_deposit.id_network','cryptocurrency_network.id');
        $query->leftJoin('users','users_wallets_crypto_deposit.id_user','users.id');

        // Filters
        $query = self::filters($query,$request);
        $count = $query->count();

        $query->select('users_wallets_crypto_deposit.*','cryptocurrency_wallets.address','cryptocurrency_wallets.address_tag'
            ,'cryptocurrency.symbol','cryptocurrency_network.symbol as net_symbol','cryptocurrency_network.name as net_name'
            ,'users.name','users.family','users.mobile','users.email');

        $wallets = $query->paginate($limit)->items();
        foreach ($wallets as $wallet) {
            if ($wallet->expired_at > date('Y-m-d H:i:s')){
                $dtCurrent = \DateTime::createFromFormat('U', time());
                $dtCreate = \DateTime::createFromFormat('U', strtotime($wallet->expired_at));
                $diff = $dtCurrent->diff($dtCreate);
                $interval = $diff->format("%y سال %m ماه %d روز %h ساعت %i دقیقه");
                $wallet->interval = preg_replace('/(^0| 0) (سال|ماه|روز|ساعت|دقیقه|ثانیه)/', '', $interval);
            }

            $wallet->created = $this->convertDate($wallet->created_at, 'd F Y H:i');
            $wallet->updated = $this->convertDate($wallet->updated_at, 'd F Y - H:i');
            $wallet->expired = $this->convertDate($wallet->expired_at, 'd F Y - H:i');

            $transactionCrypto = TransactionCrypto::query();
            $transactionCrypto->where(['id_user'=>$wallet->id_user,'status'=>'success','type'=>'deposit','destination'=>$wallet->address,'id_crypto'=>$wallet->id_crypto])
                ->whereNotNull('destination')->where('created_at','>=', $wallet->created_at)->where('created_at','<=', $wallet->expired_at);
            if(isset($wallet->address_tag))
                $transactionCrypto->where('destination_tag',$wallet->address_tag);

            $wallet->amount = $transactionCrypto->sum('amount');
            $wallet->count = $transactionCrypto->count();

            unset($wallet->created_at,$wallet->updated_at,$wallet->expired_at);
        }

        $result->list = $wallets;
        $result->total = $count;

        return response()->json($result);
    }

    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'coin': $sortBy = 'cryptocurrency.id'; break;
            case 'nameFamily': $sortBy = 'users.family'; break;
            case 'network': $sortBy = 'cryptocurrency_network.symbol'; break;
            case 'created': $sortBy = 'users_wallets_crypto_deposit.created_at'; break;
            case 'expired': $sortBy = 'users_wallets_crypto_deposit.expired_at'; break;
            case 'address': $sortBy = 'cryptocurrency_wallets.address'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['cryptocurrency.name', 'cryptocurrency.symbol', 'cryptocurrency.locale','cryptocurrency_network.name','cryptocurrency_network.symbol',
                'users.name','family','mobile','email','cryptocurrency_wallets.address','cryptocurrency_wallets.address_tag'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('users_wallets_crypto_deposit.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_wallets_crypto_deposit.expired_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        if (isset($request->status)) {
            if($request->status == 'active'){
                $query->where('users_wallets_crypto_deposit.expired_at','>',  date('Y-m-d H:i:s'));
            }else{
                $query->where('users_wallets_crypto_deposit.expired_at','<=', date('Y-m-d H:i:s'));
            }
        }

        if(isset($request->id_user))
            $query->where('id_user',$request->id_user);

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }

    function removeWallet(Request $request){
        try {
            $wallet = UsersWalletsCryptoDeposit::find($request->id);
            $wallet->delete();
            return array('status' => true, 'msg' => 'با موفقیت حذف شد.');
        }catch (\Exception $e){
            return array('status' => false, 'msg' => 'حذف امکان پذیر نیست!');
        }
    }
}
