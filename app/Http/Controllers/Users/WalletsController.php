<?php

namespace App\Http\Controllers\Users;

use App\Models\Cryptocurrency;
use App\Models\TransactionCrypto;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallets\WalletsService;
use Illuminate\Http\Request;
use Crypt;
use DB;

class WalletsController extends UsersController
{
    private WalletsService $walletsService;

    public function __construct(WalletsService $walletsService)
    {
        $this->walletsService = $walletsService;
    }
    function getlistWallet(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();

        // دریافت همه رمزارزها و join با کیف پول کاربر
        $query = Cryptocurrency::query();
        $query->leftJoin('users_wallets', function($join) use ($request) {
            $join->on('users_wallets.id_crypto', '=', 'cryptocurrency.id')
                ->where('users_wallets.id_user', $request->id)
                ->where('users_wallets.type', Wallet::TYPE_ASSET);
        });

        // فیلترها
        $query = self::filters($query, $request);
        $count = $query->count();

        // انتخاب فیلدها
        $query->select([
            'users_wallets.*',
            'cryptocurrency.id as crypto_id',
            'cryptocurrency.symbol',
            'cryptocurrency.percent',
            'cryptocurrency.name',
            'cryptocurrency.icon'
        ]);

        // محاسبه موجودی به دلار و تومان
        $query->selectRaw('ROUND(COALESCE(users_wallets.balance, 0) * JSON_EXTRACT(cryptocurrency.data, "$.price_usdt"), 4) as balance_usdt');
        $query->selectRaw('ROUND(COALESCE(users_wallets.balance, 0) * JSON_EXTRACT(cryptocurrency.data, "$.price_toman_buy"), cryptocurrency.percent) as balance_toman_buy');
        $query->selectRaw('ROUND(COALESCE(users_wallets.balance, 0) * JSON_EXTRACT(cryptocurrency.data, "$.price_toman_sell"), cryptocurrency.percent) as balance_toman_sell');

        $wallets = $query->paginate($limit)->items();

        // تبدیل داده‌ها به فرمت مناسب
        foreach ($wallets as $wallet) {
            $wallet->balance = (float) ($wallet->balance ?? 0);
            $wallet->balance_available = (float) ($wallet->available_balance ?? 0);
            $wallet->blocked_balance = (float) ($wallet->blocked_balance ?? 0);

            // اگر ولت وجود نداره، اطلاعات پایه رو ست کن
            if (!$wallet->id) {
                $wallet->id = null;
                $wallet->id_user = $request->id;
                $wallet->type = Wallet::TYPE_ASSET;
                $wallet->id_crypto = $wallet->crypto_id;
            }
        }

        // اضافه کردن کیف پول تومانی به ابتدای لیست
        $user = User::find($request->id);
        $walletData = $this->walletsService->getWalletFiat($user->id);
        $tomanWallet = $walletData->wallet;
        $internal = $walletData->internal;

        $tomanWalletArray = [
            'id' => $tomanWallet->id,
            'id_user' => $tomanWallet->id_user,
            'type' => $tomanWallet->type,
            'currency_code' => $tomanWallet->currency_code,
            'balance' => (int) $tomanWallet->balance,
            'balance_available' => (int) $tomanWallet->available_balance,
            'blocked_balance' => (int) $tomanWallet->blocked_balance,
            'name' => $internal->name,
            'symbol' => $internal->symbol,
            'icon' => null, // یا آیکون مناسب برای تومان
            'balance_usdt' => 0,
            'balance_toman_buy' => (int) $tomanWallet->balance,
            'balance_toman_sell' => (int) $tomanWallet->balance,
        ];

        array_unshift($wallets, $tomanWalletArray);

        $result->lists = $wallets;
        $result->total = $count + 1; // +1 برای کیف پول تومانی

        return response()->json($result);
    }

    function filters($query, $request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'coin': $sortBy = 'cryptocurrency.id'; break;
            case 'name': $sortBy = 'cryptocurrency.name'; break;
            case 'symbol': $sortBy = 'cryptocurrency.symbol'; break;
            case 'balance': $sortBy = 'users_wallets.balance'; break;
            case 'balanceToman': $sortBy = 'balance_toman_buy'; break;
            case 'balanceUsdt': $sortBy = 'balance_usdt'; break;
            case 'valueAvailable': $sortBy = 'users_wallets.available_balance'; break;
            default: $sortBy = 'cryptocurrency.sort'; // سورت پیش فرض
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('cryptocurrency.name', 'like', '%' . $search . '%')
                    ->orWhere('cryptocurrency.symbol', 'like', '%' . $search . '%')
                    ->orWhere('cryptocurrency.data', 'like', '%' . $search . '%');
            });
        }

        if(isset($sortBy)) {
            // برای فیلدهایی که ممکنه null باشن از COALESCE استفاده کن
            if (in_array($sortBy, ['users_wallets.balance', 'users_wallets.available_balance'])) {
                $query->orderByRaw("COALESCE($sortBy, 0) " . ($request->sortDesc ? 'DESC' : 'ASC'))
                    ->orderBy('cryptocurrency.id', 'asc');
            } else {
                $query->orderBy($sortBy, $request->sortDesc ? 'desc' : 'asc')
                    ->orderBy('cryptocurrency.id', 'asc');
            }
        } else {
            $query->orderBy('cryptocurrency.id', 'asc');
        }

        return $query;
    }


    function getSingleCryptoWallet(Request $request){
        $crypto = Cryptocurrency::where('symbol', $request->symbol)->first();
        $user = User::find($request->id_user);

        $walletData = $this->walletsService->getWalletCrypto($user->id, $crypto->id);
        $wallet = $walletData->wallet;

        $wallet->balance = (float) $wallet->balance;
        $wallet->balance_available = (float) $wallet->available_balance;
        $wallet->symbol = $crypto->symbol;
        $wallet->percent = $crypto->percent;

        return array('status' => true, 'msg' => '', 'wallet' => $wallet);
    }

    function transactionCryptoWallet(Request $request){
        $crypto = Cryptocurrency::where('symbol', $request->symbol)->first();
        $walletData = $this->walletsService->getWalletCrypto($request->id_user, $crypto->id, true);
        $wallet = $walletData->wallet;
        $crypto = $walletData->crypto;
        $crypto_price_toman = json_decode($crypto->data ?? '{}')->price_toman_buy;

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
            $transaction->amount_toman = $crypto_price_toman * $wallet->balance;
            $transaction->id_admin = \Auth::user()->id;

            if($request->type == 'deposit'){
                $success = $wallet->deposit($request->amount);
            } else {
                $success = $wallet->withdraw($request->amount);
            }

            if (!$success) {
                throw new \Exception('خطا در انجام عملیات کیف پول');
            }

            $transaction->stock = (float) $wallet->balance;
            $transaction->save();
            DB::commit();

            self::logSave('users.changeBalanceWallet', $request->all(), 'تغییر موجودی رمزارز کاربر #'.$wallet->id_user, $request->ip());
            return array('status' => true, 'msg' => 'تراکنش با موفیقت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Transaction crypto wallet failed: ' . $e->getMessage());
            return array('status' => false, 'msg' => $e->getMessage());
        }
    }

    function fixationCryptoWallet(Request $request){
        $validator = \Validator::make($request->all(), [
            'fixationBalanceType' => 'required',
            'fixationTransaction' => 'required',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $walletData = $this->walletsService->getWalletCrypto($request->id_user, $request->id_crypto, true);
        $wallet = $walletData->wallet;
        $crypto = $walletData->crypto;

        DB::beginTransaction();
        try {
            if($request->fixationBalanceType == 'balance'){
                // یکسان کردن موجودی در دسترس با موجودی اصلی
                $wallet->blocked_balance = 0;
            } else {
                // یکسان کردن موجودی اصلی با موجودی در دسترس
                $wallet->blocked_balance = $wallet->balance - $wallet->available_balance;
            }

            $wallet->save();

            if($request->fixationTransaction != false):
                $amount = ($request->fixationBalanceType == 'balance') ? $wallet->balance : $wallet->available_balance;
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
                $transaction->stock = (float) $wallet->balance;
                $transaction->save();
            endif;

            DB::commit();

            self::logSave('users.changeBalanceWallet', $request->all(), 'یکسان کردن موجودی رمزارز کاربر #'.$wallet->id_user, $request->ip());
            return array('status' => true, 'msg' => 'یکسان سازی موجود با موفیقت انجام شد.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Fixation crypto wallet failed: ' . $e->getMessage());
            return array('status' => false, 'msg' => $e->getMessage());
        }
    }


    function getSingleInternalWallet(Request $request){
        $walletData = $this->walletsService->getWalletFiat($request->id_user);
        $wallet = $walletData->wallet;
        $internal = $walletData->internal;

        $wallet->balance = (float) $wallet->balance;
        $wallet->balance_available = (float) $wallet->available_balance;
        $wallet->symbol = $internal->symbol;
        $wallet->percent = $internal->percent;

        return array('status' => true, 'msg' => '', 'wallet' => $wallet);
    }

    function transactioneInternalWallet(Request $request){
        $walletData = $this->walletsService->getWalletFiat($request->id_user, true);
        $wallet = $walletData->wallet;
        $internal = $walletData->internal;

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

            if($request->type == 'deposit'){
                $success = $wallet->deposit($request->amount);
            } else {
                $success = $wallet->withdraw($request->amount);
            }

            if (!$success) {
                throw new \Exception('خطا در انجام عملیات کیف پول تومانی');
            }

            $transaction->stock = (float) $wallet->balance;
            $transaction->save();
            DB::commit();

            self::logSave('users.changeBalanceWallet', $request->all(), 'تغییر موجودی تومان کاربر #'.$wallet->id_user, $request->ip());
            return array('status' => true, 'msg' => 'تراکنش با موفیقت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Transaction internal wallet failed: ' . $e->getMessage());
            return array('status' => false, 'msg' => $e->getMessage());
        }
    }
}
