<?php

namespace App\Console\Commands\FiatWallet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\UserCardBank;
use Illuminate\Console\Command;
use App\Models\PaymentGateway\PaymentGateway;
use App\Services\Wallets\WalletsService;
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

    private WalletsService $walletsService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(WalletsService $walletsService)
    {
        parent::__construct();
        $this->walletsService = $walletsService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $TrExistSuspend = TransactionInternal::where(['status'=>'suspend','description'=>'Deposit via Card to Card Transfer'])
            ->where('created_at', '>', date('Y-m-d H:i:s',strtotime("-30 minute")))
            ->count();

        if ($TrExistSuspend > 0) {
            $apikey_application = $this->getApiKeyApplication();
            $params = array(
                'application_id' => "r40ej",
                'access_password' => "n9HKaw4hZxNhj3mBzl0MguUOeodg7IR6uOPrz3u70jiR5ArakL",
                'account_number' => "0120137445003",
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

                // فیلتر کردن تراکنش‌های دارای شماره کارت
                foreach ($transactions as $transaction) {
                    if (isset($transaction['additional']['additionalInfo2']) &&
                        preg_match($cardPattern, $transaction['additional']['additionalInfo2'], $matches)) {

                        $cardNumber = $matches[0];
                        $cardbank = UserCardBank::where('status','confirm')->where('card_number', $cardNumber)->first();

                        if ($cardbank) {
                            $user = User::where('id', $cardbank->id_user)->first();

                            if ($transaction['debit_amount'] > 0) {
                                $user->access = 'blocked';
                                $user->save();
                                continue;
                            }

                            $amount = round($transaction['credit_amount'] / 10);

                            // بررسی وجود تراکنش تکراری
                            $TraInternalExist = TransactionInternal::where([
                                'status' => 'success',
                                'type' => 'deposit',
                                'id_user' => $user->id,
                            ])->whereJsonContains('data->paystar->transaction_trace', $transaction['transaction_trace'])->first();

                            if (!$TraInternalExist) {
                                $finalAmount = $this->calculateAmountAfterFee($amount);

                                // واریز به کیف پول تومانی
                                $walletData = $this->walletsService->getWalletFiat($user->id, true);
                                $wallet = $walletData->wallet;

                                $success = $wallet->deposit($finalAmount);

                                if ($success) {
                                    $TraInternal = new TransactionInternal();
                                    $TraInternal->type = 'deposit';
                                    $TraInternal->description = 'Deposit via Card to Card Transfer';
                                    $TraInternal->id_user = $user->id;
                                    $TraInternal->id_cardbank = $cardbank->id;
                                    $TraInternal->id_internalcurrency = $user->id_internal;
                                    $TraInternal->status = 'success';
                                    $TraInternal->amount = $finalAmount;
                                    $TraInternal->stock = (float) $wallet->balance;
                                    $TraInternal->payment_gateway = 'paystar';
                                    $data = (object)array('fee_usdt'=> $this->getFeeUsdt(),'paystar'=>$transaction);
                                    $TraInternal->data = json_encode($data);
                                    $TraInternal->save();

                                    $ctr->sendNotification($user->id,'confirmInternalCard',
                                        ['amount'=> number_format($amount),'sms'=>[number_format($amount)]]);

                                    $this->info("Deposit successful for user {$user->id}: {$finalAmount} Toman");
                                } else {
                                    \Log::error('Failed to deposit to wallet for user: ' . $user->id);
                                }
                            }
                        }
                    }
                }

                $this->sendTelegram($transactions);
            }
        }
    }

    private function getApiKeyApplication($new_token = false)
    {
        $paystar = PaymentGateway::where('name','paystar')->first();
        $dataPaystar = json_decode($paystar->data);

        if($dataPaystar->apikay_application_expire > date('Y-m-d H:i:s',strtotime('+ 15 minute')) && $new_token == false){
            return $dataPaystar->apikay_application;
        }else{
            $params = array(
                'application_id' => "r40ej",
                'access_password' => "n9HKaw4hZxNhj3mBzl0MguUOeodg7IR6uOPrz3u70jiR5ArakL",
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

    function calculateAmountAfterFee($amount)
    {
        $feePercentage = 0.001;
        $minFee = 1000;

        $calculatedFee = $amount * $feePercentage;
        $finalFee = max($calculatedFee, $minFee);
        $finalAmount = $amount - $finalFee;

        return $finalAmount;
    }

    function sendTelegram($transactions)
    {
        try {
            $telegram = new Api('5519467988:AAHnqeh0X1NBKmhC5wXzGTTJvz7cfMnkvGc');
            $chat_id = '1018262135';

            $response = $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => 'داده های واریز پی استار گرفته شد و اطلاعات جدیدی اگر وجود داشته باشد در پیام های بعدی ارسال میشود.',
            ]);

            foreach (array_reverse($transactions) as $transaction) {
                $transactionTrace = $transaction['transaction_trace'];
                if (Cache::has("paystar_transaction_sent_{$transactionTrace}")) {
                    continue;
                }

                $response = $telegram->sendMessage([
                    'chat_id' => $chat_id,
                    'text' => '```'.json_encode($transaction, JSON_PRETTY_PRINT).'```',
                    'parse_mode' => 'Markdown',
                ]);

                Cache::put("paystar_transaction_sent_{$transactionTrace}", true, now()->addDays(2));
            }
        } catch (\Exception $e) {
            \Log::channel('ErrorApi')->info("paystar:deposit sendTelegram:".$e->getMessage());
        }
    }

    function getFeeUsdt()
    {
        $ExchangeApi = new ExchangeApi();
        return $ExchangeApi->priceUsdtInToman(\App\Models\Cryptocurrency::find(5));
    }
}
