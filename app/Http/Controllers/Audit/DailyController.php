<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use App\Models\TransactionCrypto;
use Illuminate\Http\Request;
use Morilog\Jalali;
use DB;
use App\Models\Audit\DailyAudit;

class DailyController extends Controller
{
    function listAudit(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = DailyAudit::query();

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();



        $dailys = $query->paginate($limit)->items();
        foreach ($dailys as $daily) {
            $date = $daily->date;
            $daily->date = $this->convertDate($date, 'd F Y');
            $daily->dateTime = $this->convertDate($date, 'Y/m/d 00:00');
        }
        $result->list = $dailys;
        $result->total = $totalCount;

        // sum benefit
        $query = DailyAudit::query();
        $query = self::filters($query,$request);
        $result->sum_benefit = round($query->sum('benefit')??0);
        $result->sum_cust = round($query->sum('cust')??0);
        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['audit_daily.id', 'amount_sell', 'avg_price_sell', 'amount_buy', 'avg_price_buy', 'benefit', 'date', 'balance'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStart);
                $query->where('date','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStop);
                $query->where('date','<', $dateStop);
            }catch(\Exception $e){}
        }
        if (isset($request->amountStart))
            $query->where('benefit','>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('benefit','<=', $request->amountStop);

        if($request->isCrypto == true)
            $query->where('is_crypto',1);
        else
            $query->where('is_crypto',0);

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }

    function addOrder(Request $request){
        $validator = \Validator::make($request->all(), [
            'type'    => 'required',
            //'txid'    => 'required',
            'amount'    => 'required|numeric',
            'fee'    => 'required|numeric',
            'date'    => 'required',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        DB::beginTransaction();
        try {

        $date = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', date('Y/m/d H:i:s',strtotime($request->date)));

        if ($request->isCrypto) {
            $transaction_crypto = new TransactionCrypto();
            $transaction_crypto->id_user = 1;
            $transaction_crypto->id_crypto = 5;
            $transaction_crypto->amount = $request->amount;
            $transaction_crypto->payment = $request->payment;
            $transaction_crypto->stock = 0;
            $transaction_crypto->status = 'success';
            $transaction_crypto->description = 'ثبت تراکنش حسابداری';
            $transaction_crypto->txid = $request->txid;
            $transaction_crypto->created_at = $date;
            $transaction_crypto->updated_at = $date;
            $transaction_crypto->save();
        }

        $order = new Orders();
        $order->type = ($request->type == 'deposit')?'sell':'buy';
        $order->amount = $request->amount * $request->fee;
        $order->amount_coin = $request->amount;
        $order->wage = 0;
        $order->fee = $request->fee;
        $order->description = 'ثبت سفارش حسابداری';
        $order->status = 'success';
        $order->id_crypto = $request->isCrypto ? 5 : null;
        $order->id_user = 1;
        $order->created_at = $date;
        $order->updated_at = $date;
        if(!$request->isCrypto){
            $data = (object)['name'=>'PerfectMoney','symbol'=>'PM'];
            $order->data = json_encode($data);
        }
        $order->save();

        DB::commit();
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');

        } catch (\Exception $e) {
            DB::rollback();
            $result = array('status' => false, 'msg' => __('Operation failed!') . $e->getMessage().$e->getLine());
            return response()->json($result);
        }
    }
}
