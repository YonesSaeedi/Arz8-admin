<?php

namespace App\Console\Commands\Crypto;

use App\Http\Controllers\Controller;
use App\Models\Cryptocurrency;
use App\Models\CryptoNetwork;
use App\Models\TransactionCrypto;
use Illuminate\Console\Command;

class WithdrawCrypto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'withdraw:crypto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Withdraw crypto';

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
        $ExchangeApi = new \App\Http\Controllers\Exchange\ExchangeApi();
        $controller = new \App\Http\Controllers\Controller();
        $TraCryptos = TransactionCrypto::where('type','withdraw')->where('status','pending')->whereNull('payment')
            ->whereNotNull('destination')->whereNotIn('id_crypto',[5,3])->limit(10)->get();
        foreach ($TraCryptos as $TraCrypto){
            try{
                $crypto = Cryptocurrency::find($TraCrypto->id_crypto);
                if($crypto->withdraw_auto){
                    $network = CryptoNetwork::find($TraCrypto->id_network);

                    // Trade usdt to coin
                    $trade = $ExchangeApi->kucoin->apiTrade->create([
                        'clientOid' => uniqid(),
                        'size'      => $TraCrypto->amount,
                        'symbol'    => $crypto->symbol.'-USDT',
                        'type'      => 'market',
                        'side'      => 'buy',
                        'remark'    => '',
                    ]);

                    // transfer to main
                    $innerTransferV2 = $ExchangeApi->kucoin->apiAccount->innerTransferV2( uniqid(),$crypto->symbol,'trade','main',$TraCrypto->amount);

                    // withdraw
                    $chain = $ExchangeApi->kucoin->currency->getV2Detail($crypto->symbol)['chains'];
                    $chainId = $chain[array_search(strtolower($network->symbol), array_column($chain, 'chain'))];
                    $withdraw = $ExchangeApi->kucoin->apiWithdrawal->apply([
                        'currency'  => $crypto->symbol,
                        'amount'    => $controller->cutFloatNumber($TraCrypto->amount-$TraCrypto->withdraw_fee,$crypto->percent),
                        'address'      => $TraCrypto->destination,
                        'memo'      => $TraCrypto->destination_tag,
                        'remark'    => 'transactionWithdraw#' . $TraCrypto->id,
                        'chain'      => $chainId['chain'],
                    ]);

                    $data = json_decode($TraCrypto->data);
                    $data->withdrawAuto = true;
                    $data->trade = $trade;
                    $data->innerTransfer = $innerTransferV2;
                    $data->withdraw = $withdraw;
                    $data->exchange = 'kucoin';

                    $TraCrypto->status = 'success';
                    $TraCrypto->payment = $TraCrypto->amount;
                    $TraCrypto->data = json_encode($data);
                    $TraCrypto->save();

                    \App\Models\CryptoLittle::where('id_crypto',$crypto->id)->update([
                        'amount_coin' => \DB::raw("amount_coin + {$TraCrypto->amount}"),
                        'amount_toman' => \DB::raw("amount_toman + {$TraCrypto->amount}"),
                    ]);

                    // send Notif
                    $controller = new Controller();
                    $controller->sendNotification($TraCrypto->id_user,'confirmWithdrawCrypto',
                        ['amount'=>$TraCrypto->amount,'symbol'=>$crypto->symbol,'sms'=>[$TraCrypto->amount,$crypto->symbol]]);
                }
            }catch (\Exception $e){
                //dd($e);
                \Log::channel('ErrorApi')->info("Withdraw crypto trId:".$TraCrypto->id .' || '.$e->getMessage().$e->getFile().$e->getLine());
            }
        }

    }
}
