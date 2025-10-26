<?php
namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Settings\CryptoLittleController;
use App\Models\Audit\TradeWage;
use App\Models\CryptoAutoTrade;
use App\Models\Cryptocurrency;
use Illuminate\Http\Request;
use Morilog\Jalali;
use DB;

class WageController extends CryptoLittleController
{
    function listWageCrypto(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $cryptos = Cryptocurrency::query();
        $cryptos->leftJoin('cryptocurrency_wage_trade','cryptocurrency.id','cryptocurrency_wage_trade.id_crypto');


        // Filters
        $cryptos = self::filters($cryptos,$request);
        $count = $cryptos->count();

        /*
        $cryptos->leftJoin('trades', function ($join) {
            $join->on('trades.wage_asset', '=', 'cryptocurrency.symbol');
            $join->where('trades.updated_at','>',DB::raw("JSON_UNQUOTE(JSON_EXTRACT(cryptocurrency.data, '$.wage_calculation.last_time'))"));
        });
        $cryptos->groupBy('cryptocurrency.id');
        */



        $cryptos->select('cryptocurrency.id','cryptocurrency.symbol','cryptocurrency.icon','cryptocurrency.name','cryptocurrency.data','cryptocurrency.locale');
        $cryptos->selectRaw("ROUND(cryptocurrency_wage_trade.amount_coin,percent) as wage_amount");

        $cryptos->selectRaw('JSON_EXTRACT(cryptocurrency.data, "$.price_usdt")*1 as price_usdt');
        $cryptos->selectRaw('JSON_EXTRACT(cryptocurrency.data, "$.price_toman_sell")*1 as price_toman_sell');
        $cryptos->selectRaw('ROUND((JSON_EXTRACT(cryptocurrency.data, "$.price_usdt")*1) * (amount_coin)  ,percent) as wage_usdt');
        $cryptos->selectRaw('ROUND((JSON_EXTRACT(cryptocurrency.data, "$.price_toman_sell")*1) * (amount_coin)) as wage_toman');

        $cryptos = $cryptos->paginate($limit)->items();

        //foreach ($cryptos as $crypto) {

        //}
        $result->lists = $cryptos;
        $result->total = $count;

        return response()->json($result);
    }
    function filters($cryptos,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'wage': $sortBy = 'wage_usdt'; break;
            case 'wageAmount': $sortBy = 'wage_amount'; break;
            case 'id': $sortBy = 'cryptocurrency.id'; break;
            case 'logo': $sortBy = 'cryptocurrency.id'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['cryptocurrency.id', 'name', 'symbol', 'cryptocurrency.data', 'locale'];
            $cryptos = $cryptos->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if(isset($sortBy))
            $cryptos->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $cryptos;
    }

    function singleWageCrypto(Request $request){
        $crypto = Cryptocurrency::find($request->id);
        $data = json_decode($crypto->data);
        $wage = TradeWage::where('id_crypto',$crypto->id)->first()->amount_coin??0;
        $wage_usdt = $wage * $data->price_usdt;
        $wage_toman = $wage * $data->price_toman_sell;
        $trades = CryptoAutoTrade::where('id_crypto',$crypto->id)->where('from','wage_trade')->orderBy('id','desc')->get();
        foreach ($trades as $trade){
            $trade->date = $this->convertDate($trade->created_at, 'd F Y - H:i:s');
            $trade->amount_coin = $this->cutFloatNumber($trade->amount_coin,$crypto->percent);
        }
        $result = array('status' => true, 'msg' => '', 'crypto'=> $crypto, 'trades'=> $trades,
            'wage'=>[round($wage,$crypto->percent),round($wage_usdt,$crypto->percent),round($wage_toman)]);
        return response()->json($result);
    }

    function trade(){
        $TradeWages = TradeWage::where('amount_coin','!=','0')->where('symbol','!=','USDT')->select('cryptocurrency_wage_trade.*','symbol','name','cryptocurrency.exchange')->
            leftJoin('cryptocurrency','cryptocurrency.id','cryptocurrency_wage_trade.id_crypto')->get();
        foreach ($TradeWages as $TradeWage){
            $success = 0;
            $min_Trade = $this->minTrade($TradeWage);
            if(abs($TradeWage->amount_coin) >=$min_Trade){
                $trade_auto = new CryptoAutoTrade();
                $trade_auto->id_crypto  = $TradeWage->id_crypto;
                if($TradeWage->exchange == 'binance'){
                    $amount_coin = $this->getAmountLOTSIZE($TradeWage->symbol,abs($TradeWage->amount_coin));
                    try{
                        $result = $this->binance->api[0]->marketSell($TradeWage->symbol.'USDT', $amount_coin);
                        if (isset($result['symbol'])){
                            $success = 1;
                            $trade_auto->amount_usdt = $result['cummulativeQuoteQty'];
                            $amount_coin = $result['origQty'];
                        }
                    }catch (\Exception $e){
                        $result = ['msg'=>$e->getMessage()];
                    }
                }else if($TradeWage->exchange == 'coinex'){
                    /*$amount_coin = abs($TradeWage->amount_coin);
                    $result = $this->coinex->marketTrade( 'SELL',$TradeWage->symbol . 'USDT',$amount_coin);
                    if($result->status == true)
                        $success = 1;*/
                }

                if($success == 1){
                    $TradeWage->amount_coin = $TradeWage->amount_coin-$amount_coin;
                    $TradeWage->save();
                    $trade_auto->status = 'success';
                }
                $trade_auto->amount_coin = $amount_coin??0;
                $trade_auto->side = 'sell';
                $trade_auto->from = 'wage_trade';
                $trade_auto->data = json_encode($result??'{}');
                $trade_auto->save();

            }
        }

        $TradeWageUSDT = TradeWage::where('id_crypto',5)->first();
        if(isset($TradeWageUSDT) && $TradeWageUSDT->amount_coin > 10){
            $trade_auto = new CryptoAutoTrade();
            $trade_auto->id_crypto = 5;
            $trade_auto->amount_coin = $TradeWageUSDT->amount_coin;
            $trade_auto->amount_usdt = $TradeWageUSDT->amount_coin;
            $trade_auto->side = 'sell';
            $trade_auto->status = 'success';
            $trade_auto->from = 'wage_trade';
            $trade_auto->save();

            $TradeWageUSDT->amount_coin = 0;
            $TradeWageUSDT->save();
        }
    }


    function statistic(){
        $statistic = (object)array();
        // Total Wage
        $query = Cryptocurrency::query();
        $query->leftJoin('cryptocurrency_wage_trade','cryptocurrency.id','cryptocurrency_wage_trade.id_crypto');
        $query->groupBy('cryptocurrency.id');
        $query->selectRaw('ROUND((JSON_EXTRACT(cryptocurrency.data, "$.price_usdt")*1) * (amount_coin)  ,percent) as wage_usdt');
        $totals = $query->get();
        $total_wage = 0;
        foreach ($totals as $wage){
            $total_wage += $wage->wage_usdt;
        }
        $wage_old = CryptoAutoTrade::where('from','wage_trade')->sum('amount_usdt');


        $statistic->total_wage = round($total_wage+$wage_old,4);

        // Trade to usdt
        $statistic->total_trade_wage = round($wage_old,4);

        // Total Wage for trade
        $statistic->total_wage_for_trade = round($total_wage,2);


        $statistic->total_trades = CryptoAutoTrade::where('from','wage_trade')->count();
        return response()->json($statistic);
    }

    function tableCalculate(Request $request){
        $statistic = (object)array();
        $queryTotalWage = CryptoAutoTrade::where('from','wage_trade');
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStart);
                $queryTotalWage->where('created_at','>=', $dateStart);
            }catch(\Exception $e){
                dd($e->getMessage());
            }
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStop);
                $queryTotalWage->where('created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }
        if (isset($request->amountStart))
            $queryTotalWage->where('amount_usdt','>=', $request->amountStart);
        if (isset($request->amountStop))
            $queryTotalWage->where('amount_usdt','<=', $request->amountStop);

        $statistic->total_wage = round($queryTotalWage->sum('amount_usdt'),4);

        return response()->json($statistic);
    }

}
