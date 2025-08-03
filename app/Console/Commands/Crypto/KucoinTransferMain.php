<?php

namespace App\Console\Commands\Crypto;

use App\Models\Cryptocurrency;
use App\Models\CryptoLittle;
use Illuminate\Console\Command;
use App\Http\Controllers\Exchange\ExchangeApi;

class KucoinTransferMain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kucoin:transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kucoin Transfer Balance From Main To Trade';

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
        $ExchangeApi = new ExchangeApi;
        $tickers = $ExchangeApi->kucoin->symbol->getList();

        $balances = $ExchangeApi->kucoin->apiAccount->getList();
        foreach ($balances as $balance){
            if($balance["currency"] !== "USDT" && $balance["type"] === "main" && $balance["balance"] > 0){
                $ExchangeApi->kucoin->apiAccount->innerTransferV2( uniqid(),$balance["currency"],'main','trade',$balance["balance"]);
            }
        }

        $balances = $ExchangeApi->kucoin->apiAccount->getList();
        foreach ($balances as $balance){
            if($balance["currency"] !== "USDT" && $balance["type"] === "trade" && $balance["balance"] > 0){

                $indexArrayFind = (string)array_search($balance["currency"].'-USDT', array_column($tickers, 'symbol'));
                $ticker = $tickers[$indexArrayFind];
                //$priceInc = $ticker['priceIncrement'];
                $sizeInc = $ticker['baseIncrement'];

                //$priceDecimalPlaces = strlen(substr(strrchr($priceInc, "."), 1));
                $sizeDecimalPlaces = strlen(substr(strrchr($sizeInc, "."), 1));

                //$priceForOrder = roundNumberDown($canBuyFor, $priceDecimalPlaces);
                $sizeForOrder = $this->roundNumberDown($balance["balance"], $sizeDecimalPlaces);

                //$priceForOrder = strval($priceForOrder);
                $sizeForOrder = strval($sizeForOrder);


                try{
                    $trade = $ExchangeApi->kucoin->apiTrade->create([
                        'clientOid' => uniqid(),
                        'size'      => $sizeForOrder,
                        'symbol'    => $balance["currency"].'-USDT',
                        'type'      => 'market',
                        'side'      => 'sell',
                        'remark'    => 'auto transaction',
                    ]);

                    $crypto = Cryptocurrency::where('symbol',$balance["currency"])->first();
                    if(isset($crypto->id))
                        $little = CryptoLittle::where('id_crypto',$crypto->id)->decrement('amount_coin',$balance["balance"]);

                }catch (\Exception $e){
                    \Log::channel('ErrorApi')->info("kucoin:transfer id_crypto:".$crypto->id .' || '.$e->getMessage().$e->getFile().$e->getLine());
                    //dd($e);
                }

            }
        }

    }

    function roundNumberDown($number, $significance = 2)
    {
        $multiplier = 1;
        for ($i = 0; $i < $significance; $i++)
            $multiplier *= 10;

        $number = intval($number * $multiplier) / $multiplier;
        return round($number, $significance);
    }
}
