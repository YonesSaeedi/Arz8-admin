<?php
namespace App\Http\Controllers\Settings;
use App\Http\Controllers\Exchange\BinanceApi;
use App\Models\Cryptocurrency;
use App\Models\CryptoMarket;
use App\Models\Markets;
use App\Models\Trades;
use Illuminate\Http\Request;


class MarketsController extends  BinanceApi
{
    function listMarkets(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = Markets::query();

        // Filters
        $query = self::filters($query,$request);
        $usersCount = $query->count();

        $query->leftJoin('cryptocurrency as baseAsset','baseAsset.id','markets.id_baseAsset');
        $query->leftJoin('cryptocurrency as quoteAsset','quoteAsset.id','markets.id_quoteAsset');
        $query->leftJoin('trades','trades.id_market','markets.id')->groupBy('markets.id');
        $query->select('markets.*','baseAsset.symbol as baseSymbol','quoteAsset.symbol as quoteSymbol');
        $query->selectRaw('count(trades.id) as count_trades');
        $query->selectRaw('round(sum(trades.amount_base),baseAsset.percent) as amount_trades');
        $markets = $query->paginate($limit)->items();

        foreach ($markets as $market) {
            $market->data = json_decode($market->data);
        }
        $result->lists = $markets;
        $result->total = $usersCount;

        $result->cryptocurrency = Cryptocurrency::all();

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'name': $sortBy = 'markets.name'; break;
            case 'symbol': $sortBy = 'markets.symbol'; break;
            case 'id': $sortBy = 'markets.id'; break;
            case 'baseAsset': $sortBy = 'quoteAsset.symbol'; break;
            case 'quoteAsset': $sortBy = 'quoteAsset.symbol'; break;
            case 'countTrade': $sortBy = 'count_trades'; break;
            case 'status': $sortBy = 'markets.status'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['markets.id', 'markets.name', 'markets.symbol', 'markets.data'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->status)) {
            $query->where('markets.status',$request->status);
        }
        if (isset($request->status_auto)) {
            $query->where('markets.status_auto',$request->status_auto);
        }

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function newMarket(Request $request){
        $validator = \Validator::make($request->all(), [
            'baseAsset'    => 'required',
            'quoteAsset'    => 'required',
            'chart' => 'required',
        ]);
        if ($validator->fails()) {
            $result = array('status' => false, 'msg' => $validator->errors()->first());
            return response()->json($result);
        }

        $baseAsset = Cryptocurrency::where('symbol',$request->baseAsset)->first();
        if(!isset($baseAsset))
            return array('status' => false, 'msg' => '"ارز base" در بین رمز ارز های تعریف شده سیستم یافت نشد!');
        $baseAsset_nameFa = json_decode($baseAsset->locale)->fa->name;

        $quoteAsset = Cryptocurrency::where('symbol',$request->quoteAsset)->first();
        if(!isset($quoteAsset))
            return array('status' => false, 'msg' => '"ارز quote" در بین رمز ارز های تعریف شده سیستم یافت نشد!');
        $quoteAsset_nameFa = json_decode($quoteAsset->locale)->fa->name;

        $marketSymbol =  $baseAsset->symbol.'-'.$quoteAsset->symbol;
        $marketExist = Markets::where('symbol',$marketSymbol)->first();
        if(isset($marketExist))
            return array('status' => false, 'msg' => 'این مارکت از قبل تعریف شده است.');


        try{
            $marketGlobalSymbol =  str_replace('-','',$marketSymbol);
            $price = $this->api[0]->price($marketGlobalSymbol);
        }catch (\Exception $e){
            //return array('status' => false, 'msg' => 'همچین مارکتی در بایننس وجود ندارد.','symbol'=>$marketGlobalSymbol);
        }

        $market = new Markets();
        $market->symbol = $marketSymbol;
        $market->name = $baseAsset->name.' - '.$quoteAsset->name;
        $market->id_baseAsset = $baseAsset->id;
        $market->id_quoteAsset = $quoteAsset->id;
        $market->status = 'inactive';
        $market->chart = $request->chart;
        $market->data = json_encode(array("name"=>["fa"=>$baseAsset_nameFa." - ".$quoteAsset_nameFa]));
        $market->save();

        $this->cacheClear('market');
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');

    }

    function singleMarket(Request $request){
        $market = Markets::find($request->id);
        $market->data = json_decode($market->data);
        $baseAsset = Cryptocurrency::where('id',$market->id_baseAsset)->first();
        $quoteAsset = Cryptocurrency::where('id',$market->id_quoteAsset)->first();

        $market->baseAsset = $baseAsset;
        $market->quoteAsset = $quoteAsset;


        $statistic = (object)[];
        $statistic->allTradeSuccess = Trades::where(['id_market'=>$market->id,'status'=>'success'])->count();
        $statistic->allTradeOpen = Trades::where(['id_market'=>$market->id,'status'=>'open'])->count();
        $statistic->amountTradeBase = Trades::where(['id_market'=>$market->id,'status'=>'success'])->sum('amount_base');
        $statistic->amountTradeQuote = Trades::where(['id_market'=>$market->id,'status'=>'success'])->sum('amount_quote');
        $statistic->allTradeBuy = Trades::where(['id_market'=>$market->id,'status'=>'success','type'=>'buy'])->count();
        $statistic->allTradeSell = Trades::where(['id_market'=>$market->id,'status'=>'success','type'=>'sell'])->count();
        $statistic->allTradeCancel = Trades::where(['id_market'=>$market->id,'status'=>'canceled'])->count();
        $statistic->wageTradeBuy = Trades::where(['id_market'=>$market->id,'status'=>'success','type'=>'buy'])->sum('wage_amount');
        $statistic->wageTradeSell = Trades::where(['id_market'=>$market->id,'status'=>'success','type'=>'sell'])->sum('wage_amount');
        $statistic->avgTradeWagePercent = Trades::where(['id_market'=>$market->id,'status'=>'success'])->avg('wage');
        $statistic->maxTradeAmount = Trades::where(['id_market'=>$market->id,'status'=>'success'])->max('amount_base');


        $result = array('status' => true, 'msg' => '', 'market'=> $market, 'statistic'=> $statistic);
        return response()->json($result);
    }

    function editMarket(Request $request){
        $validator = \Validator::make($request->all(), [
            'chart' => 'required',
            'status' => 'required'
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $market = Markets::find($request->id);
        $market->chart = $request->chart;
        $market->status = $request->status;
        $market->save();
        $this->cacheClear('market');
        $result = array('status' => true, 'msg' => 'با موفقیت تغییر کرد.');
        return response()->json($result);
    }


    function removeMarket(Request $request){
        $market = Markets::find($request->id);
        try{
            $market->delete();
            $this->cacheClear('market');
            return array('status'=>true ,'msg'=>'با موفقیت حذف شد.');
        }catch (\Exception $e){
            return array('status'=>false ,'msg'=>'حذف به دلیل داده های ثبت شده به این بازار امکان پذیر نیست.');
        }
    }
}
