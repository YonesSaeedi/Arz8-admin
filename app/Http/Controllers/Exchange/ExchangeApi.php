<?php


namespace App\Http\Controllers\Exchange;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt as Crypt;
use Carbon\Carbon;

class ExchangeApi extends Controller
{
    public $binance;
    public $coinex;
    public $stableCoin = [];
    public $busdCoin = [];

    public function __construct()
    {
        $this->binance = new BinanceApi();
        $this->coinex = new CoinexApi();
        $this->kucoin = new KucoinApi();

        $this->fastPriceBinance = (array)Cache::get('pricesBinance');
        $this->fastPriceCoinex = (array)Cache::get('pricesCoinex');
        $this->fastPriceKucoin = (array)Cache::get('pricesKucoin');
    }

    function fastPriceBinance(){
        return (array)Cache::get('pricesBinance');
    }
    function fastPriceCoinex(){
        return (array)Cache::get('pricesCoinex');
    }
    function fastPriceKucoin(){
        return (array)Cache::get('pricesKucoin');
    }

    function getFee($crypto){
        if($crypto->exchange == 'binance'){
            $priceAll = $this->fastPriceBinance();
            $fee = $priceAll[$crypto->symbol . 'USDT'] ?? '0';
        }
        else if($crypto->exchange == 'coinex'){
            $priceAll = $this->fastPriceCoinex();
            $fee = $priceAll[$crypto->symbol . 'USDT'] ?? '0';
        }
        else if($crypto->exchange == 'kucoin'){
            $priceAll = $this->fastPriceKucoin();
            $fee = $priceAll[$crypto->symbol] ?? '0';
        }
        return $fee;
    }

    function getFeeAll($fast = false){
        $pricess = [];
        $cryptocurrency = \App\Models\Cryptocurrency::get();
        if($fast == false){
            $pricesBinance = $this->binance->api[0]->prices();
            $pricesCoinex = $this->coinex->api[0]->allPrice();
            $pricesKucoin = $this->kucoin->currency->getPrices();
        }else{
            $pricesBinance = $this->fastPriceBinance();
            $pricesCoinex = $this->fastPriceCoinex();
            $pricesKucoin = $this->fastPriceKucoin();
        }
        foreach ($cryptocurrency as $crypto){
            if ($crypto->symbol != 'USDT') {
                if($crypto->exchange == 'binance'):
                    $price = $pricesBinance[$crypto->symbol . 'USDT']??1;
                elseif($crypto->exchange == 'coinex'):
                    $price = $pricesCoinex[$crypto->symbol . 'USDT']??1;
                elseif($crypto->exchange == 'kucoin'):
                    $price = $pricesKucoin[$crypto->symbol]??1;
                endif;
            }


            if($crypto->symbol =='USDT')
                $price = 1;

            $pricess[$crypto->symbol] = [];
            $pricess[$crypto->symbol]['price'] = $price;
        }
        return $pricess;
    }

    public function priceToman($crypto,$fast = false){
        $result = self::priceUsdtInToman($crypto);
        if($fast == false){
            if ($crypto->name != 'tether') {
                if($crypto->exchange == 'binance'):
                    $price = $this->binance->api[0]->price($crypto->symbol."USDT");
                elseif($crypto->exchange == 'coinex'):
                    $price = $this->coinex->api[0]->price($crypto->symbol."USDT");
                elseif($crypto->exchange == 'kucoin'):
                    $price = $this->kucoin->currency->getPrices(null,$crypto->symbol)[$crypto->symbol];
                endif;
            }
            $percent = 8;
        }else{
            if($crypto->exchange == 'binance'){
                $priceAll = $this->fastPriceBinance();
                $price = $priceAll[$crypto->symbol . 'USDT'] ?? 0;
            }
            else if($crypto->exchange == 'coinex'){
                $priceAll = $this->fastPriceCoinex();
                $price = $priceAll[$crypto->symbol . 'USDT'] ?? 0;
            }
            else if($crypto->exchange == 'kucoin'){
                $priceAll = $this->fastPriceKucoin();
                $price = $priceAll[$crypto->symbol] ?? 0;
            }


            $p = $result['buy'] * (float)($price??1);
            if($p > 1000)
                $percent = 0;
            elseif ($p > 100)
                $percent = 2;
            else
                $percent = 8;
        }

        $result['buy'] = round($result['buy'] * (float)($price??1), $percent);
        $result['sell'] = round($result['sell'] * (float)($price??1), $percent);
        return (object)$result;
    }

    public function priceTomanAll($fast = false){
        $pricess = [];
        $cryptocurrency = \App\Models\Cryptocurrency::get();

        if($fast == false){
            $pricesBinance = $this->binance->api[0]->prices();
            $pricesCoinex = $this->coinex->api[0]->allPrice();
            $pricesKucoin = $this->kucoin->currency->getPrices();
        }else{
            $pricesBinance = $this->fastPriceBinance();
            $pricesCoinex = $this->fastPriceCoinex();
            $pricesKucoin = $this->fastPriceKucoin();
        }
        foreach ($cryptocurrency as $crypto){
            $result = self::priceUsdtInToman($crypto);
            if ($crypto->symbol != 'USDT') {
                if($crypto->exchange == 'binance'):
                    $price = $pricesBinance[$crypto->symbol . 'USDT']??1;
                elseif($crypto->exchange == 'coinex'):
                    $price = $pricesCoinex[$crypto->symbol . 'USDT']??1;
                elseif($crypto->exchange == 'kucoin'):
                    $price = $pricesKucoin[$crypto->symbol]??1;
                endif;
            }

            if($crypto->symbol =='USDT')
                $price = 1;

            $p = $result['buy'] * (float)($price??1);
            if($p > 1000)
                $percent = 0;
            elseif ($p > 100)
                $percent = 2;
            else
                $percent = 8;

            $pricess[$crypto->symbol] = [];
            $pricess[$crypto->symbol]['buy'] = round($result['buy'] * (float)($price??1), $percent);
            $pricess[$crypto->symbol]['sell'] = round($result['sell'] * (float)($price??1), $percent);
        }
        return $pricess;
    }

    public function priceUsdtInToman($Crypto = null){
        $result = Cache::remember('priceUsdtInTomanNew', Carbon::now()->addMinutes(1), function () use($Crypto) {
            $feeUsdtApiStatus = Settings::where('name','feeUsdtApi')->first()->value;
            if($feeUsdtApiStatus !== 'false'){
                $price_usdt_toman = Crypt::decryptString(Settings::where('name', 'feeUsdtApiPrice')->first()->value);
            }else{
                $price_usdt_toman = Crypt::decryptString(Settings::where('name', 'feeUsdt')->first()->value);
            }
            $result = array('buy'=>$price_usdt_toman,'sell'=>$price_usdt_toman);
            $percent_buy = Crypt::decryptString(Settings::where('name', 'percentUsdtBuy')->first()->value);
            $percent_sell = Crypt::decryptString(Settings::where('name', 'percentUsdtSell')->first()->value);
            $result = $this->percentageCalculation($result,$percent_buy,$percent_sell);
            return  $result;
        });
        return  $result;
    }
}
