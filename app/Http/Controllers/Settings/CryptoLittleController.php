<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\CryptoAutoTrade;
use App\Models\Cryptocurrency;
use App\Models\CryptoLittle;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CryptoLittleController extends ExchangeApi
{
    function exchangeInfoBinance(){
        return Cache::get('exchangeInfoBinance');
    }
    function exchangeInfoCoinex(){
        return Cache::get('exchangeInfoCoinex');
    }
    function exchangeInfoKucoin(){
        return Cache::get('exchangeInfoKucoin');
    }
    function pricesBinance(){
        return Cache::get('pricesBinance');
    }

    function listLittle(Request $request){
        //if (\Auth::user()->id == 2)
        //   dd(self::trade());

        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $cryptos = Cryptocurrency::query();

        // Filters
        $cryptos = self::filters($cryptos,$request);
        $usersCount = $cryptos->count();

        $cryptos->leftJoin('cryptocurrency_little','cryptocurrency_little.id_crypto','cryptocurrency.id');
        $cryptos->select('cryptocurrency.id','symbol','icon','amount_coin','amount_toman');
        $cryptos->selectRaw('JSON_EXTRACT(data, "$.price_usdt")*1 as price_usdt');
        $cryptos->selectRaw('round((JSON_EXTRACT(data, "$.price_usdt")*1) * amount_coin,4) as balance_usdt');
        $cryptos = $cryptos->paginate($limit)->items();
        $result->lists = $cryptos;
        $result->total = $usersCount;


        // Sum

        $result->sum = $this->sum($request);
        $result->fee = $this->feeUsdt();


        return response()->json($result);
    }

    function sum($request){
        $cryptos = Cryptocurrency::query();
        $cryptos = self::filters($cryptos,$request,true);
        $cryptos->leftJoin('cryptocurrency_little','cryptocurrency_little.id_crypto','cryptocurrency.id');
        $cryptos->selectRaw('round(sum((JSON_EXTRACT(data, "$.price_usdt")*1) * amount_coin),5) as sum_balance_usdt');
        $cryptos->selectRaw('sum(amount_toman) as sum_balance_toman');
        $result = $cryptos->first();

        $cryptos = Cryptocurrency::query();
        $cryptos = self::filters($cryptos,$request,true);
        $cryptos->leftJoin('cryptocurrency_little','cryptocurrency_little.id_crypto','cryptocurrency.id');
        $cryptos->where('amount_toman','<=',0);
        $cryptos->selectRaw('sum(amount_toman) as sum_balance_toman');
        $result->mines_balance_toman = $cryptos->first()->sum_balance_toman;

        $cryptos = Cryptocurrency::query();
        $cryptos = self::filters($cryptos,$request,true);
        $cryptos->where('amount_coin','<=',0);
        $cryptos->leftJoin('cryptocurrency_little','cryptocurrency_little.id_crypto','cryptocurrency.id');
        $cryptos->selectRaw('round(sum((JSON_EXTRACT(data, "$.price_usdt")*1) * amount_coin),5) as sum_balance_usdt');
        $result->mines_balance_usdt = $cryptos->first()->sum_balance_usdt;

        $cryptos = Cryptocurrency::query();
        $cryptos = self::filters($cryptos,$request,true);
        $cryptos->leftJoin('cryptocurrency_little','cryptocurrency_little.id_crypto','cryptocurrency.id');
        $cryptos->where('amount_toman','>',0);
        $cryptos->selectRaw('sum(amount_toman) as sum_balance_toman');
        $result->plus_balance_toman = $cryptos->first()->sum_balance_toman;

        $cryptos = Cryptocurrency::query();
        $cryptos = self::filters($cryptos,$request,true);
        $cryptos->where('amount_coin','>',0);
        $cryptos->leftJoin('cryptocurrency_little','cryptocurrency_little.id_crypto','cryptocurrency.id');
        $cryptos->selectRaw('round(sum((JSON_EXTRACT(data, "$.price_usdt")*1) * amount_coin),5) as sum_balance_usdt');
        $result->plus_balance_usdt = $cryptos->first()->sum_balance_usdt;

        return $result;
    }


    private function filters($cryptos,$request,$sum = false){
        $search = $request->search;
        if(!$sum)
            switch ($request->sortBy){
                case 'balance': $sortBy = 'balance_usdt'; break;
                case 'equivalent': $sortBy = 'balance_usdt'; break;
                case 'balanceToman': $sortBy = 'amount_toman'; break;
                case 'priceUsdt': $sortBy = 'price_usdt'; break;
                case 'id': $sortBy = 'cryptocurrency.id'; break;
                case 'logo': $sortBy = 'cryptocurrency.id'; break;
                default: $sortBy = $request->sortBy;
            }

        if (!empty($search)) {
            $fields = ['cryptocurrency.id', 'name', 'symbol', 'data', 'locale'];
            $cryptos = $cryptos->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->exchange)) {
            $cryptos->where('exchange',$request->exchange);
        }
        if (isset($request->statusBuy)) {
            $cryptos->where('buy_status',$request->statusBuy == "true"?1:0);
        }
        if (isset($request->statusSell)) {
            $cryptos->where('sell_status',$request->statusSell == "true"?1:0);
        }
        if (isset($request->withdraw)) {
            $cryptos->where('withdraw',$request->withdraw == "true"?1:0);
        }
        if (isset($request->deposit)) {
            $cryptos->where('deposit',$request->deposit == "true"?1:0);
        }
        if(isset($sortBy) && !$sum)
            $cryptos->orderByRaw('abs('.$sortBy.') '.($request->sortDesc?'desc':'asc'));
        return $cryptos;
    }

    function trade(){
        $littles = CryptoLittle::where('amount_coin','!=','0')->whereNotIn('symbol',['USDT'])
                    ->select('cryptocurrency_little.*','symbol','name','percent','cryptocurrency.exchange','cryptocurrency.settings')
                    ->where('sell_status',1)->where('buy_status',1)/*->where('symbol','PARAM')*/
                    ->whereRaw('abs((JSON_EXTRACT(cryptocurrency.data, "$.price_usdt")*1) * cryptocurrency_little.amount_coin) > 1')
                    ->leftJoin('cryptocurrency','cryptocurrency.id','cryptocurrency_little.id_crypto')->get();

        foreach ($littles as $little){
            try {
                $min_Trade = $this->minTrade($little);
                if($this->cutFloatNumber(abs($little->amount_coin),$little->percent) > $min_Trade){
                    //if($little->symbol == 'DASH')
                    //    dd($little->symbol,$min_Trade,$this->cutFloatNumber(abs($little->amount_coin),$little->percent));

                    //dd($little);
                    //echo $little->symbol.' | '.$min_Trade.' | '.abs($little->amount_coin).'<br>';
                    $trade_auto = new CryptoAutoTrade();
                    $trade_auto->id_crypto  = $little->id_crypto;
                    $type = $little->amount_coin > 0?'SELL':'BUY';

                    //dd($little,$little->amount_coin,$type);
                    $trade = self::tradeExchange($little,$little->amount_coin,$type);
                    if($trade->status == true){
                        $trade_auto->amount_usdt = $trade->amount_usdt;

                        $fee_toman = $this->priceToman($little);
                        if($type == 'BUY'){
                            $little->amount_coin = $little->amount_coin+$trade->amount_coin;
                            $little->amount_toman = $little->amount_toman +($fee_toman->sell*abs($trade->amount_coin));
                        }
                        else{
                            $little->amount_coin = $little->amount_coin-$trade->amount_coin;
                            $little->amount_toman = $little->amount_toman -($fee_toman->buy*abs($trade->amount_coin));
                        }
                        $little->save();

                        $trade_auto->status = 'success';
                    }
                    $trade_auto->amount_coin = $trade->amount_coin;
                    $trade_auto->side = strtolower($type);
                    $trade_auto->data = json_encode($trade);
                    $trade_auto->save();

                }
            }catch (\Exception $e){
                \Log::channel('ErrorApi')->info("trade little id_crypto:".$little->id_crypto .' || '.$e->getMessage().$e->getFile().$e->getLine());
                //dd($e);
            }

        }
    }


    public function minTrade($crypto)
    {
        if ($crypto->exchange == 'binance') {
            $filters = $this->exchangeInfoBinance()['symbols'][$crypto->symbol.'USDT']['filters'] ?? [];
            $min = array_column($filters, 'minNotional')[0] ?? 1;
            $fee = $this->pricesBinance()[$crypto->symbol.'USDT'] ?? 1;
            $min_amount = ($min / $fee) * 1.05;
        } elseif ($crypto->exchange == 'coinex') {
            $min_amount = $this->exchangeInfoCoinex()[$crypto->symbol.'USDT']['min_amount'] ?? 1;
            $min_amount *= 1.05;
        } elseif ($crypto->exchange == 'kucoin') {
            $exchangeInfoKucoin = $this->exchangeInfoKucoin();
            $index = array_search($crypto->symbol.'-USDT', array_column($exchangeInfoKucoin, 'symbol'));
            $min_amount = $exchangeInfoKucoin[$index]['baseMinSize'] ?? 1;
            $min_amount *= 1.15;
        }
        return $min_amount;
    }


    function getAmountLOTSIZE($symbol,$amount_coin){
        if($symbol != 'USDT'):
            $exchangeInfo = $this->exchangeInfoBinance();
            if(!in_array($symbol,$this->stableCoin))
                if(!in_array($symbol,$this->busdCoin))
                    $LOT_SIZE = $exchangeInfo['symbols'][$symbol . 'USDT']['filters'][1]['minQty'];
                else
                    $LOT_SIZE = $exchangeInfo['symbols'][$symbol . 'BUSD']['filters'][1]['minQty'];
            else
                $LOT_SIZE = $exchangeInfo['symbols']['USDT'.$symbol]['filters'][1]['minQty'];
            if($LOT_SIZE < 1){
                $LOT_SIZE = (explode('1', $LOT_SIZE));
                $LOT_SIZE = strlen(substr(strrchr($LOT_SIZE[0], "."), 1))+1;
            }else
                $LOT_SIZE = 0;

            $amount_coin = $this->cutFloatNumber($amount_coin,$LOT_SIZE);
        endif;
        return $amount_coin;
    }

    function tradeExchange($crypto,$amount,$type){
        $res = (object)[];
        $settings = json_decode($crypto->settings??'{}');
        $index_account = $settings->exchange_account??0;
        if($crypto->exchange == 'binance'){
            $amount_coin = $this->getAmountLOTSIZE($crypto->symbol,abs($amount));
            try{
                if($type =='BUY')
                    $result = $this->binance->api[$index_account]->marketBuy($crypto->symbol.'USDT', $amount_coin);
                else
                    $result = $this->binance->api[$index_account]->marketSell($crypto->symbol.'USDT', $amount_coin);

                if (isset($result['symbol'])){
                    $res->status = true;
                    $res->amount_usdt = $result['cummulativeQuoteQty'];
                    $res->amount_coin = $result['origQty'];
                }
            }catch (\Exception $e){
                $res->status = false;
                $res->msg = $e->getMessage();
                $res->amount_coin = $amount_coin;
            }
        }else if($crypto->exchange == 'coinex'){
            try{
                $amount_coin = abs($amount);
                $result = $this->coinex->api[$index_account]->marketTrade($type,($crypto->symbol . 'USDT'),$amount_coin,$crypto->symbol);
                if($result->status == true){
                    $res->amount_coin = $result->data['filled_amount'];
                    $res->amount_usdt = $result->data['filled_value'];
                    $res->status = true;
                }else{
                    $res->status = false;
                    $res->msg = $result->message;
                    $res->amount_coin = $amount_coin;
                }
            }catch (\Exception $e){
                $res->status = false;
                $res->msg = $e->getMessage();
                $res->amount_coin = $amount_coin;
            }
        }else if($crypto->exchange == 'kucoin'){
            $amount_coin = $this->cutFloatNumber(abs($amount),$crypto->percent);
            //if($little->symbol == 'BCH')
            //    $amount_coin = 0.0013;
            //dd($little->symbol,$min_Trade,$amount_coin);

            $arr = [
                'clientOid' => uniqid(),
                'symbol'    => $crypto->symbol.'-USDT',
                'type'      => 'market',
                'side'      => strtolower($type),
                'size'      => strval($amount_coin),
                'remark'    => '',
            ];
            try{
                $trade = $this->kucoin->apiTrade_list[$index_account]->create($arr);
                $result = $this->kucoin->apiTrade_list[$index_account]->getDetail($trade['orderId']);

                $res->amount_coin = $result['dealSize'];
                $res->amount_usdt = $result['dealFunds'];
                $res->status = true;
            }catch (\Exception $e){
                $res->status = false;
                $res->msg = $e->getMessage();
                $res->amount_coin = $amount_coin;
            }
        }
        $res->responce = $result??[];
        return $res;
    }

    function removeLittle(Request $request){
        $little = CryptoLittle::where('id_crypto',$request->id)->first();
        if(isset($little)){
            try {
                $little->amount_coin = 0;
                $little->amount_toman = 0;
                $little->save();
                return array('status' => true, 'msg' => 'با موفقیت حذف شد.');
            }catch (\Exception $e){
                return array('status' => false, 'msg' => 'حذف امکان پذیر نیست!');
            }
        }else
            return array('status' => false, 'msg' => 'این ارز دارای موجودی اندک نمیباشد!');
    }
}
