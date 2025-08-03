<?php

namespace App\Http\Controllers\Users;

use App\Models\Cryptocurrency;
use App\Models\Internalcurrency;
use App\Models\TransactionCrypto;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\WalletsCrypto;
use App\Models\WalletsInternal;
use Illuminate\Http\Request;
use Crypt;
use DB;

class WalletsController extends UsersController
{
    function getlistWallet(Request $request){

        if($request->page == 1 && $request->id==1)
            self::createWallets($request->id);

        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = Cryptocurrency::query();
        $query->leftJoin('users_wallets_crypto', function ($join)use ($request) {
            $join->on('users_wallets_crypto.id_crypto', '=', 'cryptocurrency.id');
            $join->where('users_wallets_crypto.id_user', '=', $request->id);
        });

        // Filters
        $query = self::filters($query,$request);
        $count = $query->count();


        $query->select('users_wallets_crypto.*','cryptocurrency.symbol','cryptocurrency.percent','cryptocurrency.name');
        $query->selectRaw('ROUND(users_wallets_crypto.value_num * JSON_EXTRACT(cryptocurrency.data, "$.price_usdt") ,4) as balance_usdt');
        $query->selectRaw('ROUND(users_wallets_crypto.value_num * JSON_EXTRACT(cryptocurrency.data, "$.price_toman_buy") ,percent) as balance_toman_buy');
        $query->selectRaw('ROUND(users_wallets_crypto.value_num * JSON_EXTRACT(cryptocurrency.data, "$.price_toman_sell") ,percent) as balance_toman_sell');

        $wallets = $query->paginate($limit)->items();
        foreach ($wallets as $wallet) {
            if( isset($wallet->value)){
                $wallet->balance = (float)\Crypt::decryptString($wallet->value);
                $wallet->balance_available = (float)\Crypt::decryptString($wallet->value_available);
            }else{
                $wallet->balance = 0;
                $wallet->balance_available = 0;
            }


            unset($wallet->value,$wallet->value_available);
        }

        // toman add first table
        $user = User::find($request->id);
        $internal = Internalcurrency::find($user->id_internal);
        $internalWallet = WalletsInternal::where('id_user',$user->id)->where('id_internal',$user->id_internal)->first();
        if(!isset($internalWallet))
            $internalWallet = self::createInternalWallet($user);
        $internalWallet->balance = (int)\Crypt::decryptString($internalWallet->value);
        $internalWallet->balance_available = (int)\Crypt::decryptString($internalWallet->value_available);
        $internalWallet->name = $internal->name;
        $internalWallet->symbol = $internal->symbol;
        unset($internalWallet->value,$internalWallet->value_available);
        array_unshift($wallets,$internalWallet->toArray());



        $result->lists = $wallets;
        $result->total = $count;

        return response()->json($result);
    }

    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'coin': $sortBy = 'users_wallets_crypto.id'; break;
            case 'name': $sortBy = 'cryptocurrency.name'; break;
            case 'symbol': $sortBy = 'cryptocurrency.symbol'; break;
            case 'balance': $sortBy = 'users_wallets_crypto.value_num'; break;
            case 'balanceToman': $sortBy = 'balance_toman_buy'; break;
            case 'balanceUsdt': $sortBy = 'balance_usdt'; break;
            case 'valueAvailable': $sortBy = 'value_available_num'; break;

            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['name', 'symbol', 'data', 'locale'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }

    function createWallets($id_user){
        $cryptos = Cryptocurrency::all();
        foreach ($cryptos as $crypto){
            $wallet = WalletsCrypto::where('id_crypto',$crypto->id)->where('id_user',$id_user)->first();
            if(!isset($wallet)){
                $wallet = new WalletsCrypto;
                $wallet->id_user = $id_user;
                $wallet->id_crypto = $crypto->id;
                $wallet->value = \Crypt::encryptString(0);
                $wallet->value_available = \Crypt::encryptString(0);
                $wallet->save();
            }
        }
    }
    function createInternalWallet($user){
        $wallet = new WalletsInternal;
        $wallet->id_user = $user->id;
        $wallet->id_internal = $user->id_internal;
        $wallet->value = \Crypt::encryptString(0);
        $wallet->value_available = \Crypt::encryptString(0);
        $wallet->save();
        return $wallet;
    }

    function getSingleCryptoWallet(Request $request){
        $crypto = Cryptocurrency::where('symbol',$request->symbol)->first();
        $user = User::find($request->id_user);

        $wallet = WalletsCrypto::where(['id_crypto'=>$crypto->id,'id_user'=>$user->id])->first();
        if(!isset($wallet))
            $wallet = $this->createWallet($crypto->id,$user->id);

        $wallet->balance = (float)\Crypt::decryptString($wallet->value);
        $wallet->balance_available = (float)\Crypt::decryptString($wallet->value_available);
        unset($wallet->value,$wallet->value_available);
        $crypto = Cryptocurrency::find($wallet->id_crypto);
        $wallet->symbol = $crypto->symbol;
        $wallet->percent = $crypto->percent;

        return array('status' => true, 'msg' => '', 'wallet' => $wallet);
    }
    function transactionCryptoWallet(Request $request){
        $wallet = WalletsCrypto::find($request->id_wallet);
        $crypto = Cryptocurrency::find($wallet->id_crypto);
        $crypto_price_toman = json_decode($crypto->data??'{}')->price_toman_buy;

        DB::beginTransaction();
        try {
            $transaction = new TransactionCrypto;
            $transaction->id_crypto = $crypto->id;
            $transaction->id_user = $wallet->id_user;
            $transaction->type = $request->type;
            $transaction->amount = $request->amount;
            $transaction->payment = $request->amount;
            $transaction->status = 'success';
            $transaction->description = $request->description;
            $transaction->amount_toman = $crypto_price_toman * $wallet->value_num;
            $transaction->id_admin = \Auth::user()->id;

            $balance = Crypt::decryptString($wallet->value);
            $balance_available = Crypt::decryptString($wallet->value_available);
            if($request->type == 'deposit'){
                $wallet->value = Crypt::encryptString($balance + $request->amount);
                $wallet->value_available = Crypt::encryptString($balance_available + $request->amount);
                $wallet->value_num = $balance + $request->amount;
                $wallet->value_available_num = $balance_available + $request->amount;
            }else{
                $wallet->value = Crypt::encryptString($balance - $request->amount);
                $wallet->value_available = Crypt::encryptString($balance_available - $request->amount);
                $wallet->value_num = $balance - $request->amount;
                $wallet->value_available_num = $balance_available - $request->amount;
            }
            $wallet->save();

            $transaction->stock = $wallet->value_num;
            $transaction->save();
            DB::commit();

            self::logSave('users.changeBalanceWallet',$request->all(), 'تغییر موجودی رمزارز کاربر #'.$wallet->id_user,$request->ip());
            return array('status' => true, 'msg' => 'تراکنش با موفیقت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
        }
    }

    function fixationCryptoWallet(Request $request){
        $validator = \Validator::make($request->all(), [
            'fixationBalanceType' => 'required',
            'fixationTransaction' => 'required',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $wallet = WalletsCrypto::find($request->id_wallet);
        $crypto = Cryptocurrency::find($wallet->id_crypto);

        DB::beginTransaction();
        try {
            $balance = Crypt::decryptString($wallet->value);
            $balance_available = Crypt::decryptString($wallet->value_available);
            if($request->fixationBalanceType == 'balance'){
                $wallet->value_available = Crypt::encryptString($balance);
                $wallet->value_available_num = $balance;
            }else{
                $wallet->value = Crypt::encryptString($balance_available);
                $wallet->value_num = $balance_available;
            }
            $wallet->save();

            if($request->fixationTransaction != false):
                $amount = ($request->fixationBalanceType == 'balance') ? $balance : $balance_available;
                $description = 'یکسان شدن '. (($request->fixationBalanceType == 'balance') ? 'موجودی در دسترس با موجودی' : 'موجودی با موجودی در دسترس');

                $transaction = new TransactionCrypto;
                $transaction->id_crypto = $crypto->id;
                $transaction->id_user = $wallet->id_user;
                $transaction->type = 'deposit';
                $transaction->amount = $amount;
                $transaction->payment = $amount;
                $transaction->status = 'success';
                $transaction->description = $description;
                $transaction->amount_toman = 0;
                $transaction->id_admin = \Auth::user()->id;
                $transaction->stock = $wallet->value_num;
                $transaction->save();
            endif;
            DB::commit();

            self::logSave('users.changeBalanceWallet',$request->all(), 'یکسان کردن موجودی رمزارز کاربر #'.$wallet->id_user,$request->ip());
            return array('status' => true, 'msg' => 'یکسان سازی موجود با موفیقت انجام شد.');

        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
        }
    }





    function getSingleInternalWallet(Request $request){
        $wallet = WalletsInternal::find($request->id_wallet);
        $wallet->balance = (float)\Crypt::decryptString($wallet->value);
        $wallet->balance_available = (float)\Crypt::decryptString($wallet->value_available);
        unset($wallet->value,$wallet->value_available);
        $internal = Internalcurrency::find($wallet->id_internal);
        $wallet->symbol = $internal->symbol;
        $wallet->percent = $internal->percent;
        return array('status' => true, 'msg' => '', 'wallet' => $wallet);
    }

    function transactioneInternalWallet(Request $request){
        $wallet = WalletsInternal::find($request->id_wallet);
        $internal = Internalcurrency::find($wallet->id_internal);

        DB::beginTransaction();
        try {
            $transaction = new TransactionInternal();
            $transaction->id_internalcurrency = $internal->id;
            $transaction->id_user = $wallet->id_user;
            $transaction->type = $request->type;
            $transaction->amount = $request->amount;
            $transaction->payment = $request->amount;
            $transaction->status = 'success';
            $transaction->description = $request->description;
            $transaction->id_admin = \Auth::user()->id;

            $balance = Crypt::decryptString($wallet->value);
            $balance_available = Crypt::decryptString($wallet->value_available);
            if($request->type == 'deposit'){
                $wallet->value = Crypt::encryptString($balance + $request->amount);
                $wallet->value_available = Crypt::encryptString($balance_available + $request->amount);
                $wallet->value_num = $balance + $request->amount;
                $wallet->value_available_num = $balance_available + $request->amount;
            }else{
                $wallet->value = Crypt::encryptString($balance - $request->amount);
                $wallet->value_available = Crypt::encryptString($balance_available - $request->amount);
                $wallet->value_num = $balance - $request->amount;
                $wallet->value_available_num = $balance_available - $request->amount;
            }
            $wallet->save();

            $transaction->stock = $wallet->value_num;
            $transaction->save();
            DB::commit();

            self::logSave('users.changeBalanceWallet',$request->all(), 'تغییر موجودی تومان کاربر #'.$wallet->id_user,$request->ip());
            return array('status' => true, 'msg' => 'تراکنش با موفیقت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
        }
    }
}
