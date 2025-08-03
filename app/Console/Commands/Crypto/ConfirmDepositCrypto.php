<?php

namespace App\Console\Commands\Crypto;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Exchange\BinanceApi;
use App\Models\Cryptocurrency;
use App\Models\TransactionCrypto;
use App\Models\User;
use App\Models\WalletsCrypto;
use Crypt;
use DB;
use Illuminate\Console\Command;

class ConfirmDepositCrypto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'confirm:depositCrypto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'confirm deposit crypto after 30 min';

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
        $binance = new BinanceApi();
        $controller = new Controller();

        $Transactions = TransactionCrypto::where('created_at','<',date('Y-m-d H:i:s',strtotime('- 30 minute')))
                            ->where(['status'=>'pending','type'=>'deposit'])->whereNotNull('txid')->whereNull('payment')->get();
        foreach ($Transactions as $transaction){
            $user = User::find($transaction->id_user);
            $crypto = Cryptocurrency::find($transaction->id_crypto);
            $depositHistory = $binance->api[0]->depositHistory($crypto->symbol, array('startTime' => strtotime('-3 day', time()) . '000', 'endTime' => time() . '000'));
            $indexArrayFind = (string)array_search($transaction->txid, array_column($depositHistory, 'txId'));
            $inquiry_binance = $depositHistory[$indexArrayFind]??null;
            if(isset($inquiry_binance) && $inquiry_binance['status']==1){
                $txid_duplicate = TransactionCrypto::whereNotNull('txid')->where('id', '!=', $transaction->id)->where('txid', $transaction->txid)->count();
                $deposit_success = TransactionCrypto::where(['id_user'=>$transaction->id_user,'type'=>'deposit','status'=>'success'])->whereNotNull('txid')->count();
                $tr = TransactionCrypto::where(['id'=>$transaction->id,'status'=>'pending','type'=>'deposit'])->whereNotNull('txid')->whereNull('payment')->get();
                if($txid_duplicate == 0 && isset($tr) && $user->level >= 3 && $deposit_success > 2){

                    DB::beginTransaction();
                    try {
                        $transaction->status = 'success';
                        $transaction->payment = $transaction->amount;
                        $transaction->save();

                        //get Balance
                        $wallet = WalletsCrypto::where('id_crypto',$crypto->id)->where('id_user',$transaction->id_user)->first();
                        if(!$wallet)
                            $wallet = $controller->createWallet($crypto->id,$transaction->id_user);
                        $balance = Crypt::decryptString($wallet->value);
                        $balance_available = Crypt::decryptString($wallet->value_available);
                        $balance = $controller->cutFloatNumber($balance,$crypto->percent);
                        $balance_available = $controller->cutFloatNumber($balance_available,$crypto->percent);

                        $wallet = WalletsCrypto::where('id_crypto',$crypto->id)->where('id_user', $transaction->id_user)->first();
                        $b = $controller->cutFloatNumber($balance + $transaction->amount,$crypto->percent);
                        $ba = $controller->cutFloatNumber($balance_available + $transaction->amount,$crypto->percent);
                        $wallet->value = Crypt::encryptString($b);
                        $wallet->value_available = Crypt::encryptString($ba);
                        $wallet->value_num = $b;
                        $wallet->value_available_num = $ba;
                        $wallet->save();
                        DB::commit();

                        // send Notif
                        $controller->sendNotification($transaction->id_user,'confirmDepositCrypto',
                            ['amount'=>$transaction->amount,'symbol'=>$crypto->symbol,'sms'=>[$transaction->amount,$crypto->symbol]]);

                    } catch (\Exception $e) {
                        DB::rollback();
                    }
                }
            }
        }
    }

}
