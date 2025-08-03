<?php

namespace App\Console\Commands\FiatWallet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\UserCardBank;
use Illuminate\Console\Command;
use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Cache;


class PaystarDeposit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paystar:deposit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check tr paystar deposit';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $application_id = "r40ej";
    public $access_password = "n9HKaw4hZxNhj3mBzl0MguUOeodg7IR6uOPrz3u70jiR5ArakL";
    public $account_number = "0120137445003";
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
        $TrExistSuspend = TransactionInternal::where(['status'=>'suspend','description'=>'Deposit via Card to Card Transfer'])->where('created_at', '>', date('Y-m-d H:i:s',strtotime("-30 minute")))->count();
        if ($TrExistSuspend > 0):
            $apikey_application = self::getApiKeyApplication();
            $params = array(
                'application_id' => $this->application_id,
                'access_password' => $this->access_password,
                'account_number' => $this->account_number,
                'from_date' => date('Y-m-d',strtotime('- 24 hour')),
                'to_date' => null,
            );
            $response = Http::withHeaders([
                'Content-Type'=>'application/json',
                'Authorization' => 'Bearer '. $apikey_application
            ])->get('https://core.paystar.ir/api/open-banking/service/invoice', $params);
            $response = (object)$response->json();
            if ($response->status == "ok") {
                $ctr = new Controller();

                $transactions = $response->data['transactions'];
                $cardPattern = '/\b\d{16}\b/';

                // فیلتر کردن و افزودن شماره کارت به آبجکت‌های واجد شرایط
                $filteredTransactions = array_map(function ($transaction) use ($cardPattern) {
                    if (isset($transaction['additional']['additionalInfo2']) && preg_match($cardPattern, $transaction['additional']['additionalInfo2'], $matches)) {
                        // افزودن شماره کارت به آبجکت
                        $transaction['extracted_card_number'] = $matches[0];
                        return $transaction;
                    }
                    return null;  // اگر شماره کارت پیدا نشد، حذف شود
                }, $transactions);

                // حذف موارد null (آن‌هایی که شماره کارت ندارند)
                $filteredTransactions = array_filter($filteredTransactions);

                foreach ($filteredTransactions as $transaction){
                    $cardbank = UserCardBank::where('status','confirm')->where('card_number',$transaction['extracted_card_number'])->first();
                    if(isset($cardbank)){
                        $user = User::where('id',$cardbank->id_user)->first();
                        if($transaction['debit_amount']>0) {
                            $user->access = 'blocked';
                            $user->save();
                            continue;
                        }

                        $amount = round($transaction['credit_amount']/ 10);

                        $wallet = \App\Models\WalletsInternal::where('id_internal',$user->id_internal)->where('id_user',$user->id)->first();
                        if(!isset($wallet)){
                            $wallet = $ctr->createWalletInternal($user->id_internal,$user->id);
                        }
                        $balance = round( Crypt::decryptString($wallet->value));
                        $balance_available = round(Crypt::decryptString($wallet->value_available));

                        $TraInternalExist = TransactionInternal::
                            where(['status'=>'success','type'=>'deposit','id_user'=>$user->id,'description'=>'Deposit via Card to Card Transfer'])->
                            whereJsonContains('data->paystar->transaction_trace',$transaction['transaction_trace'])->first();
                        //if($transaction['transaction_date_time'] == '03-11-18 20:27:04')
                        //    dd($TraInternalExist,$transaction);
                        if(!isset($TraInternalExist)){
                            $TraInternal = TransactionInternal::where('created_at', '>', date('Y-m-d H:i:s',strtotime("-30 minute")))->where(['status'=>'suspend','type'=>'deposit','id_cardbank'=>$cardbank->id,'amount'=>$amount,'id_user'=>$user->id,'description'=>'Deposit via Card to Card Transfer'])->first();

                            $finalAmount = $this->calculateAmountAfterFee($amount);
                            if(isset($TraInternal)){
                                $data = json_decode($TraInternal->data);
                                $data->paystar = $transaction;
                            }else{
                                $TraInternal = new TransactionInternal();
                                $TraInternal->type = 'deposit';
                                $TraInternal->description = 'Deposit via Card to Card Transfer';
                                $TraInternal->id_user = $user->id;
                                $TraInternal->id_cardbank = $cardbank->id;
                                $TraInternal->id_internalcurrency = $user->id_internal;
                                $data = (object)array('fee_usdt'=> $this->getFeeUsdt(),'paystar'=>$transaction);
                            }
                            $TraInternal->status = 'success';
                            $TraInternal->amount = $finalAmount;
                            $TraInternal->stock = $balance + $finalAmount;
                            $TraInternal->data = json_encode($data);
                            $TraInternal->payment_gateway = 'paystar';
                            $TraInternal->save();

                            $wallet->value = Crypt::encryptString($balance + $finalAmount);
                            $wallet->value_available = Crypt::encryptString($balance_available + $finalAmount);
                            $wallet->value_num = $balance + $finalAmount;
                            $wallet->value_available_num = $balance_available + $finalAmount;
                            $wallet->save();

                            $ctr->sendNotification($user->id,'confirmInternalCard',
                                ['amount'=> number_format($amount),'sms'=>[number_format($amount)]]);
                        }


                    }
                }


                $this->sendTelegram($transactions);



            }
        endif;
    }

    private function getApiKeyApplication($new_token = false){
        $paystar = PaymentGateway::where('name','paystar')->first();
        $dataPaystar = json_decode($paystar->data);
        if($dataPaystar->apikay_application_expire > date('Y-m-d H:i:s',strtotime('+ 15 minute')) && $new_token == false){
            return $dataPaystar->apikay_application;
        }else{
            $params = array(
                'application_id' => $this->application_id,
                'access_password' => $this->access_password,
                'refresh_token' => "Y538X6JU1HvFsK5dIJS92A6InjaI3e01QpleGAtLGBewmexWWBqqXZGH0D72YSJ8PO0ZioggRmVKPC1VUYpwb6eVT4Xgg54GgsKJ",
            );
            $response = Http::withHeaders([
                'Content-Type'=>'application/json',
            ])->post('https://core.paystar.ir/api/application/refresh-api-key', $params);
            $response = (object)$response->json();

            if($response->status == 1) {
                $dataPaystar->apikay_application_expire = $response->data['api_key_expire_date'];
                $dataPaystar->apikay_application = $response->data['api_key'];
                $paystar->data = json_encode($dataPaystar);
                $paystar->save();
            }
            return $dataPaystar->apikay_application;
        }
    }

    function calculateAmountAfterFee($amount) {
        // درصد کارمزد (0.1%)
        $feePercentage = 0.001;

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

    function sendTelegram($transactions){
        try {
            $telegram = new Api('5519467988:AAHnqeh0X1NBKmhC5wXzGTTJvz7cfMnkvGc');
            $chat_id = '1018262135';

            $response = $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'داده های واریز پی استار گرفته شد و اطلاعات جدیدی اگر وجود داشته باشد در پیام های بعدی ارسال میشود.',
            ]);
            $response->getMessageId();

            foreach (array_reverse($transactions) as $transaction) {
                $transactionTrace = $transaction['transaction_trace'];
                if (Cache::has("paystar_transaction_sent_{$transactionTrace}")) {
                    continue; // اگر قبلاً ارسال شده است، از حلقه عبور کن
                }
                $response = $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => '```'.json_encode($transaction, JSON_PRETTY_PRINT).'```',
                    'parse_mode' => 'Markdown',
                ]);
                $messageId = $response->getMessageId();
                Cache::put("paystar_transaction_sent_{$transactionTrace}", true, now()->addDays(2));
            }
        } catch (\Exception $e) {
            \Log::channel('ErrorApi')->info("paystar:deposit sendTelegram:".$e->getMessage().$e->getFile().$e->getLine());

        }
    }

    function getFeeUsdt() {
        $ExchangeApi = new ExchangeApi();
        return $ExchangeApi->priceUsdtInToman(\App\Models\Cryptocurrency::find(5));
    }
}
