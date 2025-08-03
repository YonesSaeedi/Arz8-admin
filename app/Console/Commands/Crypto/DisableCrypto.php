<?php

namespace App\Console\Commands\Crypto;

use App\Models\Cryptocurrency;
use Illuminate\Console\Command;

class DisableCrypto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disable:crypto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable crypto';

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
        $coinexInfo = $ExchangeApi->coinex->api[0]->marketInfo();
        $binanceInfo = $ExchangeApi->binance->api[0]->exchangeInfo();
        $cryptos = Cryptocurrency::where('symbol','!=','USDT')->get();
        foreach ($cryptos as $crypto){
            if($crypto->exchange == 'coinex' && !isset($coinexInfo[$crypto->symbol."USDT"]) ){
                $crypto->sell_status = 0;
                $crypto->buy_status = 0;
                $crypto->save();
            }
            elseif ($crypto->exchange == 'binance' && isset($binanceInfo["symbols"][$crypto->symbol."USDT"]) && $binanceInfo["symbols"][$crypto->symbol."USDT"]["status"] !== "TRADING"){
                $crypto->sell_status = 0;
                $crypto->buy_status = 0;
                $crypto->save();
            }
        }
    }
}
