<?php

namespace App\Console\Commands\Crypto;

use App\Models\Cryptocurrency;
use App\Models\CryptoNetwork;
use App\Models\CryptoWallets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt as Crypt;

class GetAddressWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:getAddress {--id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'getAddressWallet';

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
        $id = $this->option('id');
        $ExchangeApi = new \App\Http\Controllers\Exchange\ExchangeApi();
        if(!isset($id))
            $cryptos = Cryptocurrency::where('deposit',1)->whereJsonContains('settings->hidden',true)->get();
        else
            $cryptos = Cryptocurrency::where('deposit',1)->whereJsonContains('settings->hidden',true)->where('id',$id)->get();

        try {
            foreach ($cryptos as $crypto){

                try{
                    $getAddresses = $ExchangeApi->kucoin->apiDeposit->createAddress($crypto->symbol);
                }catch (\Exception $e){}

                $getAddresses = $ExchangeApi->kucoin->apiDeposit->getAddresses($crypto->symbol);
                if(count($getAddresses) == 0){
                    //$crypto->deposit = 0;
                    //$crypto->withdraw = 0;
                    //$crypto->save();
                    continue;
                }

                foreach ($getAddresses as $address){
                    $network = CryptoNetwork::where('name',$address['chain'])->orWhere('symbol',$address['chain'])->first();
                    if(isset($network)){
                        if(($network->id == 1 || $network->id == 7) && ($crypto->symbol !== 'TRX' || $crypto->symbol !== 'USDT'))
                            continue;
                        $wallet = CryptoWallets::where(['id_crypto'=>$crypto->id,'address'=>$address['address'],'address_tag'=>($address['memo']!=""?$address['memo']:null)])->first();
                        if(!isset($wallet))
                            $wallet = new CryptoWallets();

                        $wallet->address = $address['address'];
                        $wallet->address_tag = $address['memo']!=""?$address['memo']: null;
                        $wallet->address_hash = Crypt::encryptString($address['address']);
                        $wallet->address_tag_hash = $address['memo']!="" ? Crypt::encryptString($address['memo']) : null;
                        $wallet->id_crypto = $crypto->id;
                        $wallet->id_network = $network->id;
                        $wallet->active = 1;
                        $wallet->for_txid = false;

                        $wallet->save();
                    }
                }

                $wallets = CryptoWallets::where(['id_crypto'=>$crypto->id,'active'=>1])->orderBy('id')->groupBy('id_network')->get();
                foreach ($wallets as $wallet){
                    $wallet->for_txid = true;
                    $wallet->save();
                }
            }
        } catch (\Exception $e) {
            //dd($e);
        }
    }

}
