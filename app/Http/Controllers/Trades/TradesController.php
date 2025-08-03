<?php

namespace App\Http\Controllers\Trades;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Cryptocurrency;
use App\Models\ReferralTransaction;
use App\Models\Trades;
use App\Models\Markets;
use App\Models\UserReferral;
use App\Models\TradesTrade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali;

class TradesController extends Controller
{
    function listTrades(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = Trades::query();

        $query->leftJoin('users','trades.id_user','users.id');
        $query->leftJoin('cryptocurrency as baseAsset','trades.id_baseAsset','baseAsset.id');
        $query->leftJoin('cryptocurrency as quoteAsset','trades.id_quoteAsset','quoteAsset.id');
        $query->leftJoin('markets','trades.id_market','markets.id');
        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $query->select('trades.*','markets.name as marketName','markets.data as marketData','baseAsset.symbol as baseSymbol','quoteAsset.symbol as quoteSymbol','users.name','users.family','users.email','users.level_account');
        $trades = $query->paginate($limit)->items();
        foreach ($trades as $trade) {
            $data = json_decode($trade->marketData);
            $trade->marketName = $data->name->{app()->getLocale()} ?? $trade->marketName;
            $trade->date = $this->convertDate($trade->created_at, 'd F Y - H:i');
            if($trade->status == 'partial'){
                $data = json_decode($trade->data);
                $trade->first_amount_base = ($data->check_status->origQty*1)??0;
            }
        }
        $result->list = $trades;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'date': $sortBy = 'trades.created_at'; break;
            case 'id': $sortBy = 'trades.id'; break;
            case 'amountBase': $sortBy = 'amount_base'; break;
            case 'amountQuote': $sortBy = 'amount_quote'; break;
            default: $sortBy = $request->sortBy;
        }


        if (!empty($search)) {
            $fields = ['trades.id', 'markets.name','markets.data','markets.symbol', 'model','amount_base','amount_quote','fee','binance_orderId',
                        'baseAsset.symbol', 'baseAsset.name', 'quoteAsset.symbol', 'quoteAsset.name', 'users.name', 'mobile', 'email', 'family'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->type))
            $query->where('type', $request->type);
        if (isset($request->status)) {
            $query->where('trades.status', $request->status);
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('trades.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('trades.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }
        if (isset($request->amountStart))
            $query->where('amount_base','>=', $request->amountStart)->orWhere('amount_quote','>=',$request->amountStart);
        if (isset($request->amountStop))
            $query->where('amount_base','<=', $request->amountStop)->orWhere('amount_quote','<=',$request->amountStop);
        if (isset($request->via))
            $query->where('trades.via', $request->via);
        if (isset($request->id)){
            $ids = explode(',',$request->id);
            $query->whereIn('trades.id', $ids);
        }
        if (isset($request->model)){
            $query->where('model', $request->model);
        }

        if (isset($request->id_user)){
            $query->where('trades.id_user', $request->id_user);
        }

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }

    function statistic(){
        $statistic = (object)array();
        $statistic->total_trades = Trades::count();
        $statistic->buy_trades = Trades::where('status','success')->where('type','buy')->count();
        $statistic->sell_trades = Trades::where('status','success')->where('type','sell')->count();
        $statistic->success_trades = Trades::where('status','success')->count();
        return response()->json($statistic);
    }


    function singleTrade(Request $request){
        $trade = Trades::find($request->id);
        $market = Markets::find($trade->id_market);


        $trade->date = $this->convertDate($trade->created_at, 'd F Y - H:i');
        $trade->update = $this->convertDate($trade->updated_at, 'd F Y - H:i');

        $trade->description = __($trade->description);
        $dataMarket = json_decode($market->data);
        $trade->marketName = $dataMarket->name->{app()->getLocale()} ?? $trade->marketName;
        $trade->marketSymbol = $market->symbol;

        $trade->baseSymbol = Cryptocurrency::where('id',$trade->id_baseAsset)->first()->symbol;
        $trade->quoteSymbol = Cryptocurrency::where('id',$trade->id_quoteAsset)->first()->symbol;

        $fills = TradesTrade::where('id_trade',$trade->id)->orderBy('id','desc')->get();
        foreach ($fills as $fill){
            $fill->dateTime = $this->convertDate($fill->created_at,'d F Y H:i');

            unset($fill->created_at,$fill->updated_at, $fill->id_order);
        }
        $trade->fills = $fills;

        $trade->user = User::select('id','name','family','email','mobile')->find($trade->id_user);
        $trade->admin = AdminUser::select('id','name')->find($trade->id_admin);
        if($trade->status == 'partial'){
            $data = json_decode($trade->data);
            $trade->first_amount_base = ($data->check_status->origQty*1)??0;
        }

        $referral = UserReferral::where('id_user_referral',$trade->user->id)->first();
        if(isset($referral)){
            $tr_referral = ReferralTransaction::where(['id_referral'=>$referral->id,'id_trade'=>$trade->id])->first();
            $trade->referral_amount = $tr_referral->amount??0;
            $trade->referral = $referral;
        }
        return response()->json(array('status'=>true ,'msg'=>'', 'trade'=> $trade));
    }

    function statusTrade(Request $request){
        $trade = Trades::where(['status'=>'open','id'=>$request->id])->first();
        $response = Http::post(env('APP_URL_FRONT').'api/v2/trade-status-admin', ['id_trade'=>$trade->id]);
        return $response->throw()->json();
    }

}
