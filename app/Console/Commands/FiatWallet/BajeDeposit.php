<?php

namespace App\Console\Commands\FiatWallet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\PaymentGateway\Baje;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\UserCardBank;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;


class BajeDeposit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baje:deposit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check tr baje zibal deposit';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $baje = new Baje();
        $listDeposit = $baje->listDeposit();


        if (count($listDeposit) > 0) {
            $ctr = new Controller();


            foreach ($listDeposit as &$transaction) {
                $sourceIban = $transaction['sourceIban'];

                // حذف فرمت‌های اضافی از iban
                $cleanIban = str_replace([' ', '-', 'IR'], '', strtoupper($sourceIban));

                // جستجوی کارت بانکی در دیتابیس
                $cardbank = UserCardBank::where('status','confirm')->whereRaw("REPLACE(REPLACE(REPLACE(iban, ' ', ''), '-', ''), 'IR', '') = ?", [$cleanIban])->first();

                if ($cardbank) {
                    $amount = round($transaction['amount'] / 10);
                    $user = User::where('id', $cardbank->id_user)->first();

                    $wallet = \App\Models\WalletsInternal::where('id_internal', $user->id_internal)->where('id_user', $user->id)->first();
                    if (!isset($wallet)) {
                        $wallet = $ctr->createWalletInternal($user->id_internal, $user->id);
                    }
                    $balance = round(Crypt::decryptString($wallet->value));
                    $balance_available = round(Crypt::decryptString($wallet->value_available));

                    $TraInternalExist = TransactionInternal::where(['status' => 'success', 'type' => 'deposit', 'id_user' => $user->id,])->whereJsonContains('data->baje->id', $transaction['id'])->first();
                    if (!isset($TraInternalExist)) {
                        $finalAmount = $this->calculateAmountAfterFee($amount);

                        $TraInternal = new TransactionInternal();
                        $TraInternal->type = 'deposit';
                        $TraInternal->description = 'Deposit with ID';
                        $TraInternal->id_user = $user->id;
                        $TraInternal->id_cardbank = $cardbank->id;
                        $TraInternal->id_internalcurrency = $user->id_internal;
                        $TraInternal->status = 'success';
                        $TraInternal->amount = $finalAmount;
                        $TraInternal->stock = $balance + $finalAmount;
                        $TraInternal->payment_gateway = 'baje';
                        $data = (object)array('fee_usdt'=> $this->getFeeUsdt(),'baje' => $transaction);
                        $TraInternal->data = json_encode($data);
                        $TraInternal->save();

                        $wallet->value = Crypt::encryptString($balance + $finalAmount);
                        $wallet->value_available = Crypt::encryptString($balance_available + $finalAmount);
                        $wallet->value_num = $balance + $finalAmount;
                        $wallet->value_available_num = $balance_available + $finalAmount;
                        $wallet->save();

                        $ctr->sendNotification($user->id, 'depositId',
                            ['amount' => number_format($amount), 'sms' => [number_format($amount)]]);

                        $this->changeStatusTransaction($transaction['id']);
                    }

                }else{
                    $this->changeStatusTransaction($transaction['id'],3);
                }
            }

        }

    }

    function calculateAmountAfterFee($amount)
    {
        // درصد کارمزد (0.1%)
        $feePercentage = 0.0005;

        // حداقل کارمزد
        $minFee = 1000;

        // محاسبه کارمزد
        $calculatedFee = $amount * $feePercentage;

        // اگر کارمزد محاسبه‌شده کمتر از حداقل بود، حداقل اعمال شود
        $finalFee = max($calculatedFee, $minFee);

        // محاسبه مبلغ نهایی پس از کسر کارمزد
        $finalAmount = $amount - $finalFee;

        return $finalAmount;
    }

    private function changeStatusTransaction($id,$status = 2){

        $Zibal = PaymentGateway::where('name','baje')->first();
        $dataZibal = json_decode($Zibal->data);

        $params = array(
            'accountId' => $dataZibal->accountId,
            'transaction_id' => $id,
            'status' => $status
        );
        $authorization = 'Bearer '. $Zibal->token;
        $response = Http::withHeaders(['Content-Type'=>'application/json','Authorization' => $authorization])
            ->post('https://api.zibal.ir/ebank/v1/account/identified-payment/change-status/', $params);

        return $response->json();
    }

    function getFeeUsdt() {
        $ExchangeApi = new ExchangeApi();
        return $ExchangeApi->priceUsdtInToman(\App\Models\Cryptocurrency::find(5));
    }
}
