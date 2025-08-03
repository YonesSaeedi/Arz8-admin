<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Audit\CostAudit;
use App\Models\Cryptocurrency;
use App\Models\Orders;
use App\Models\Trades;
use App\Models\ReferralTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Morilog\Jalali;
use DB;

class OrdersController extends Controller
{
    function listOrders(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = Orders::query();
        $query->where('status', 'success');

        $query->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'family', 'email', 'mobile', 'name_display', 'level_account');
            },
            'crypto' => function ($query) {
                $query->select('id','symbol');
            }
        ]);

        // Filters
        $query = self::filters($query, $request);
        $totalCount = $query->count();


        $query->select('id','id_crypto','id_user','orders.created_at','amount','amount_coin','type',
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(orders.data, '$.symbol')) as symbol"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(orders.data, '$.name')) as nameCoin")
        );

        $query->selectRaw('ROUND(IF(((id_crypto is not null) AND id_crypto != "5"),
                              JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
                              amount_coin),4) as amount_usdt');
        $query->selectRaw('IF(
                              ((id_crypto is not null) AND  id_crypto != "5"),
                              ROUND(amount / (IF(((id_crypto is not null) AND  id_crypto != "5"),JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,amount_coin))),
                              fee) as fee_usdt');

        if ($request->sortBy == 'amount_usdt' || $request->sortBy == 'fee_usdt')
            $query->orderByRaw($request->sortBy . ' ' . ($request->sortDesc ? 'desc' : 'asc'));


        $orders = $query->paginate($limit)->through(function ($order) {
            return [
                'id' => $order->id,
                'amount' => $order->amount,
                'amount_coin' => $order->amount_coin,
                'amount_usdt' => $order->amount_usdt,
                'fee_usdt' => $order->fee_usdt,
                'type' => $order->type,
                'date' =>  $this->convertDate($order->created_at, 'Y/m/d - H:i'),
                'user' => $order->user,
                'crypto' => isset($order->crypto)?$order->crypto:['symbol'=>$order->symbol,'name'=>$order->nameCoin],
                'symbol' => $order->symbol,
                'nameCoin' => $order->nameCoin,
            ];
        });

        $result->list =  $orders->items();
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query, $request)
    {
        if (isset($request->dateStart)) {
            try {
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('orders.created_at', '>=', $dateStart);
                //$query->where('orders.created_at','>=', '2022-07-13 14:15:00');
            } catch (\Exception $e) {
            }
        }
        if (isset($request->dateStop)) {
            try {
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('orders.created_at', '<', $dateStop);
            } catch (\Exception $e) {
            }
        }
        if ($request->isCrypto == true)
            $query->whereNotNull('id_crypto');
        else
            $query->whereNull('id_crypto');

        if (isset($request->type))
            $query->where('type', $request->type);
        if (isset($request->status)) {
            $query->where('status', $request->status);
        }
        if (isset($request->amountStart))
            $query->where('orders.amount', '>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('orders.amount', '<=', $request->amountStop);
        if (isset($request->via))
            $query->where('orders.via', $request->via);
        if (isset($request->id)) {
            $ids = explode(',', $request->id);
            $query->whereIn('orders.id', $ids);
        }

        $search = $request->search;
        if (!empty($search)) {
            $fields = ['orders.id', 'description', 'orders.data'];
            $query->where(function ($query) use ($search, $fields) {
                $searchTerm = '%' . $search . '%';
                $query->whereRaw(
                    '(' . implode(' OR ', array_map(function ($field) use ($searchTerm) {
                        return "$field LIKE ?";
                    }, $fields)) . ')',
                    array_fill(0, count($fields), $searchTerm)
                );
                $query->orWhereHas('crypto', function ($q) use ($search) {
                    $q->where('symbol', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('locale', 'like', '%' . $search . '%');
                });
                $query->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('family', 'like', '%' . $search . '%');
                });
            });
        }

        switch ($request->sortBy) {
            case 'date':
                $sortBy = 'orders.created_at';
                break;
            case 'id':
                $sortBy = 'orders.id';
                break;
            default:
                $sortBy = $request->sortBy;
        }
        if (isset($sortBy))
            if ($sortBy != 'amount_usdt' && $sortBy != 'fee_usdt')
                $query->orderBy($sortBy, $request->sortDesc ? 'desc' : 'asc');
        return $query;
    }

    function calculationTable(Request $request)
    {
        $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
        $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
        $cust = (object)[];
        $result = (object)array();


        // sell
        $query = Orders::query();
        $query->whereBetween('created_at', [$dateStart, $dateStop])->where('type', 'sell')->where('status', 'success');
        $query = self::filtersCalculationTable($query, $request);
        // Filters
        $result->sell = $query->selectRaw('
                ROUND(SUM(IF(((id_crypto is not null) AND id_crypto != "5"),
                  JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
                  amount_coin)),8) as sum_amount_usdt,

                ROUND(sum(amount) /
                  (ROUND(SUM(IF(((id_crypto is not null) AND id_crypto != "5"),
                  JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
                  amount_coin)),8))
                )  as avg_fee_usdt
        ')->first();


        // buy
        $query = Orders::query();
        $query->whereBetween('created_at', [$dateStart, $dateStop])->where('type', 'buy')->where('status', 'success');
        // Filters
        $query = self::filtersCalculationTable($query, $request);
        $result->buy = $query->selectRaw('
                ROUND(SUM(IF(((id_crypto is not null) AND id_crypto != "5"),
                  JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
                  amount_coin)),8) as sum_amount_usdt,

                ROUND(sum(amount) /
                  (ROUND(SUM(IF(((id_crypto is not null) AND id_crypto != "5"),
                  JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
                  amount_coin)),8))
                )  as avg_fee_usdt
        ')->first();

        if ($request->isCrypto != false):
            // Costs
                // Referral
                $cust->referral = (object)[];
                $cust->referral->orders = ReferralTransaction::whereBetween('created_at', [$dateStart, $dateStop])->whereNotNull('id_order')->sum('amount');
                $cust->referral->trades = ReferralTransaction::whereBetween('created_at', [$dateStart, $dateStop])->whereNotNull('id_trade')->sum('amount');

                //Cost
                $cust->cust_toman = CostAudit::whereBetween('created_at', [$dateStart, $dateStop])->where('asset', 'TOMAN')->sum('amount');

                // Wage Binance Orders
                $orders = Orders::query()
                    ->whereBetween('created_at', [$dateStart, $dateStop])
                    ->whereNotNull('id_crypto')
                    ->whereJsonContains('data->exchange', 'binance')
                    ->where("status", "success")
                    ->selectRaw("JSON_EXTRACT(data, '$.response.fills') as fills")
                    ->get();

                $totalCommission = $orders->flatMap(function ($order) {
                    $fills = json_decode($order->fills, true);
                    return $fills ? collect($fills) : collect();
                })->where('commissionAsset', 'BNB')
                    ->sum(fn($item) => (float) $item['commission']);

                $cust->order_wage_bnb = round($totalCommission, 8);


            // Wage Binance Trades
                $query = Trades::query();
                $query->whereBetween('created_at', [$dateStart, $dateStop]);
                $query->where('status', 'success');
                $trades = $query->selectRaw('SUM(JSON_UNQUOTE(JSON_EXTRACT(data, "$.history.commission"))) AS wage')
                    ->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.history"))) IS NOT NULL
                                         AND (JSON_UNQUOTE(JSON_EXTRACT(data, "$.history.commissionAsset"))) = "BNB"')->first();
                $cust->trade_wage_bnb = round(sprintf('%.6f', $trades->wage), 8);

                $cust_bnb = CostAudit::whereBetween('created_at', [$dateStart, $dateStop])
                    ->where(['asset' => 'BNB', 'type' => 'increase'])->selectRaw('sum(amount) as sum,round(sum(amount*fee) / sum(amount)) as fee')->first();
                $cust->order_wage_toman = round($cust->order_wage_bnb * $cust_bnb->fee);
                $cust->trade_wage_toman = round($cust->trade_wage_bnb * $cust_bnb->fee);
                $cust->balance_bnb = round($cust_bnb->sum - ($cust->order_wage_bnb + $cust->trade_wage_bnb), 8);
                $cust->bnb = $cust_bnb;

            // Wage Coinex CET
                $query = Orders::query();
                $query->whereBetween('created_at', [$dateStart, $dateStop]);
                $query->where('status', 'success')->whereNotNull('id_crypto');
                $query->whereJsonContains('data->exchange', 'coinex');
                $query->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.response.data.discount_fee"))) > 0');
                $order_wage_cet = $query->selectRaw('ROUND(SUM(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.data.discount_fee"))*1),8) as discount_fee')->first();

                $cust->order_wage_cet = $order_wage_cet->discount_fee;
                $cust_cet = CostAudit::whereBetween('created_at', [$dateStart, $dateStop])
                    ->where(['asset' => 'CET', 'type' => 'increase'])->selectRaw('sum(amount) as sum,round(sum(amount*fee) / sum(amount)) as fee')->first();
                $cust->balance_cet = round($cust_cet->sum - $cust->order_wage_cet, 8);
                $cust->cet = $cust_cet;
                $cust->order_wage_toman += round($cust->order_wage_cet * $cust_cet->fee);

        else:
            // Wage PerfectMoney and PSVocher
            $query = Orders::query();
            $query->whereBetween('created_at', [$dateStart, $dateStop]);
            $query->where(['status' => 'success', 'type' => 'buy']);
            $query->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name"))) != "PSVouchers"');
            $query->whereNull('orders.id_crypto');
            $sum = $query->sum('amount');
            $cust->wage_buy_digital = round($sum / 200);
        endif;

        $result->cust = $cust;
        return $result;
    }

    function filtersCalculationTable($query, $request)
    {
        if ($request->isCrypto == true)
            $query->whereNotNull('orders.id_crypto');
        else
            $query->whereNull('orders.id_crypto');
        return $query;
    }

}
