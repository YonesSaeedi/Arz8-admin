<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Cryptocurrency;
use App\Models\TransactionCrypto;
use App\Models\Orders;
use App\Models\Trades;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    function statistic(Request $request){
        $statistic = (object)array();
        $statistic->orders = Orders::where(['id_user'=>$request->id,'status'=>'success'])->count();
        $statistic->orders_buy_count = Orders::where(['id_user'=>$request->id,'status'=>'success','type'=>'buy'])->count();
        $statistic->orders_sell_count = Orders::where(['id_user'=>$request->id,'status'=>'success','type'=>'sell'])->count();
        $statistic->orders_buy_amount = Orders::where(['id_user'=>$request->id,'status'=>'success','type'=>'buy'])->sum('amount');
        $statistic->orders_sell_amount = Orders::where(['id_user'=>$request->id,'status'=>'success','type'=>'sell'])->sum('amount');
        $statistic->orders_max_amount = Orders::where(['id_user'=>$request->id,'status'=>'success'])->max('amount');

        $statistic->trades = Trades::where('id_user',$request->id)->count();
        $statistic->trades_amount = 0;
        $trades = Trades::query();
        $trades->where(['id_user'=>$request->id,'status'=>'success']);
        $trades->leftJoin('cryptocurrency','cryptocurrency.id','trades.id_baseAsset');
        $trades->selectRaw('SUM(JSON_UNQUOTE(JSON_EXTRACT(cryptocurrency.data, "$.price_usdt")) * amount_base ) as sum_trades_usdt');
        $statistic->trades_amount = $trades->first()->sum_trades_usdt;

        // 30 days ago
        $dateStart = date('Y-m-d 00:00:00',strtotime( ' -30 day'));
        $dateStop =  date('Y-m-d 00:00:00');
        $statistic->amount_30days_orders = round(Orders::where('id_user',$request->id)->where('created_at','>=', $dateStart)->where('created_at','<=', $dateStop)->where('status','success')->sum('amount'));

        $id_trade = Trades::where('id_user',$request->id)->where('created_at','>=', $dateStart)->where('created_at','<=', $dateStop)->where('status','success')->pluck('id')->toArray();
        $statistic->amount_30days_trades  = round(TransactionCrypto::whereIn('id_trade',$id_trade)->where('id_user',$request->id)->where('type','deposit')->sum('amount_toman'));

        return response()->json($statistic);
    }
}
