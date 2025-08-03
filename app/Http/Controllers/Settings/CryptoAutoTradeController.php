<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\CryptoAutoTrade;
use DB;
use Illuminate\Http\Request;
use Morilog\Jalali;

class CryptoAutoTradeController extends ExchangeApi
{

    function listTrade(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $trades = CryptoAutoTrade::query();
        $trades->leftJoin('cryptocurrency','cryptocurrency.id','cryptocurrency_auto_trade.id_crypto');
        // Filters
        $trades = self::filters($trades,$request);
        $allCount = $trades->count();

        $trades->select('cryptocurrency_auto_trade.*','symbol','icon');
        $trades->paginate($limit);
        $trades = $trades->paginate($limit)->items();
        foreach ($trades as $trade) {
            $trade->date = $this->convertDate($trade->created_at, 'd F Y - H:i');
        }

        $result->lists = $trades;
        $result->total = $allCount;
        return response()->json($result);
    }

    function filters($filters,$request){
        $search = $request->search;
        switch ($request->sortBy){
            case 'id': $sortBy = 'cryptocurrency_auto_trade.id'; break;
            case 'amount': $sortBy = 'amount_coin'; break;
            case 'from': $sortBy = 'from'; break;
            case 'date': $sortBy = 'created_at'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['cryptocurrency_auto_trade.id', 'cryptocurrency.name', 'cryptocurrency.symbol', 'cryptocurrency_auto_trade.data', 'cryptocurrency.locale'];
            $filters = $filters->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->from)) {
            $filters->where('from',$request->from);
        }
        if (isset($request->status)) {
            $filters->where('status', $request->status);
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $filters->where('orders.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $filters->where('orders.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }
        if(isset($sortBy))
            $filters->orderByRaw('abs('.$sortBy.') '.($request->sortDesc?'desc':'asc'));
        return $filters;
    }

}
