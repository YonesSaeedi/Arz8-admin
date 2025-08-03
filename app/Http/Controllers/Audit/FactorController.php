<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\CostAudit;
use App\Models\TransactionInternal;
use App\Models\User;
use Illuminate\Http\Request;
use Morilog\Jalali;

class FactorController extends Controller {
    function listFactor(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = TransactionInternal::query();
        $query->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'family', 'email', 'mobile', 'name_display', 'level_account');
            }
        ]);

        $query->whereNotIn('id_user',[1,43,638]);
        $query->whereNull(['id_trade','id_order']);
        $query->select('id','type','amount','stock','users_transaction_internal.created_at','description','status','id_user');

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $transactions = $query->paginate($limit)->through(function ($transaction) {
            return [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'wage' => $this->calculateFee($transaction->amount,$transaction->type),
                'stock' => $transaction->stock,
                'type' => $transaction->type,
                'status' => $transaction->status,
                'card_bank' => $transaction->cardBank,
                'date' =>  $this->convertDate($transaction->created_at, 'Y/m/d - H:i'),
                'description' => __($transaction->description),
                'user' => $transaction->user,
                'order' => $transaction->order,
            ];
        });

        $result->lists = $transactions->items();
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query,$request){
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStart);
                $query->where('created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStop);
                $query->where('created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }
        if($request->typeDeposit != 'receipt')
            $query->whereNotNull('payment_gateway');

        $this->applyFilter($query, 'status', 'success');
        $this->applyFilter($query, 'type', $request->type);
        $this->applyFilter($query, 'amount', $request->amountStart,'>=');
        $this->applyFilter($query, 'amount', $request->amountStop,'<=');


        switch ($request->typeDeposit){
            case 'dateway':
                $query->where(function ($query) {
                    $query->where('type','deposit')
                        ->where('description', 'not like', '%Receipt%')
                        ->where('description', 'not like', '%Deposit with ID%')
                        ->where('description', 'not like', '%Deposit via Card to Card%');
                });
                break;
            case 'receipt':
                $query->where(function ($query) {
                    $query->where('description', 'like', '%Receipt%')->orWhere(['payment_gateway'=>'manual']);
                });
                break;
            case 'depositId':
                $query->where(function ($query) {
                    $query->where('description', 'like', '%Deposit with ID%')->orWhere(['payment_gateway'=>'baje']);
                });
                break;
            case 'cardTocard':
                $query->where(function ($query) {
                    $query->where('description', 'like', '%Deposit via Card to Card%')->orWhere(['payment_gateway'=>'paystar']);
                });
                break;
        }


        $search = $request->search;
        if (!empty($search)) {
            $fields = ['id', 'description', 'data', 'trace_number'];
            $query->where(function ($query) use ($search, $fields) {
                $searchTerm = '%' . $search . '%';
                $query->whereRaw(
                    '(' . implode(' OR ', array_map(function ($field) use ($searchTerm) {
                        return "$field LIKE ?";
                    }, $fields)) . ')',
                    array_fill(0, count($fields), $searchTerm)
                );
                $query->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('family', 'like', '%' . $search . '%');
                });
            });
        }


        switch ($request->sortBy){
            case 'date': $sortBy = 'created_at'; break;
            default: $sortBy = $request->sortBy;
        }
        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }

    function getFactor(Request $request) {
        $internal = TransactionInternal::find($request->id);
        $internal->date = $this->convertDate($internal->created_at, 'd F Y - H:i');
        $internal->user = User::select('id','name','family','email','mobile','name_display','level','info','address','national_code','phone')->find($internal->id_user);
        $count = TransactionInternal::where('id','<',$internal->id)->where('created_at','>=','2023-03-21')->whereNotNull('payment_gateway')->whereNull(['id_trade','id_order'])->count();
        $internal->id_factor = $count+1;
        $internal->wage = $this->calculateFee($internal->amount,$internal->type);

        $data = json_decode($internal->data);
        if(!isset($data->fee_usd) && $internal->created_at > '2025-01-20'){
            $data->fee_usdt = ['buy'=>90000];
            $internal->data = json_encode($data);
        }
        return response()->json(array('status'=>true ,'msg'=>'', 'factor'=> $internal));
    }

    function tableCalculate(Request $request){
        $statistic = (object)array();
        $query = TransactionInternal::whereNull(['id_trade','id_order']);
        $query = self::filters($query, $request);

// محاسبه مجموع کل مبالغ
        $total = $query->sum('amount');
        $statistic->total = round($total);

// کپی کوئری برای خرید و فروش
        $buyQuery = clone $query;
        $sellQuery = clone $query;

// محاسبه مجموع مبالغ خرید و فروش
        $statistic->total_buy = round($buyQuery->where('type', 'deposit')->sum('amount'));
        $statistic->total_sell = round($sellQuery->where('type', '!=', 'deposit')->sum('amount'));

// تابع محاسبه کارمزد بر اساس مبلغ و نوع عملیات
        function calculateWage($amount, $type) {
            return $amount * 0.005;
            if ($type === 'buy') {
                if ($amount < 5000000) {
                    return $amount * 0.004; // 0.4% برای خرید زیر ۵ میلیون
                } elseif ($amount >= 5000000 && $amount <= 10000000) {
                    return $amount * 0.003; // 0.3% برای خرید بین ۵ تا ۱۰ میلیون
                } else {
                    return $amount * 0.0025; // 0.25% برای خرید بالای ۱۰ میلیون
                }
            } elseif ($type === 'sell') {
                return $amount * 0.001; // 0.1% برای فروش (ثابت برای همه مبالغ)
            }
            return 0;
        }

// محاسبه کارمزد کل
        $totalWage = 0;

// محاسبه کارمزد خرید
        $buyTransactions = $buyQuery->where('type', 'deposit')->get();
        foreach ($buyTransactions as $transaction) {
            $totalWage += calculateWage($transaction->amount, 'buy');
        }

// محاسبه کارمزد فروش
        $sellTransactions = $sellQuery->where('type', '!=', 'deposit')->get();
        foreach ($sellTransactions as $transaction) {
            $totalWage += calculateWage($transaction->amount, 'sell');
        }

        $statistic->total_wage = round($totalWage);

        return response()->json($statistic);
    }


    public function govExport(Request $request)
    {
        // دریافت داده‌ها
        $query = TransactionInternal::query();
        $query->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'family', 'mobile', 'national_code');
            }
        ]);
        $query->whereNotNull('payment_gateway');
        $query->whereNull(['id_trade','id_order']);
        $query->select('id','type','amount','stock','users_transaction_internal.created_at','description','status','id_user');

        // Filters
        $query = self::filters($query,$request);
        $transactions = $query->get();

        // تولید محتوای XML برای فایل Excel
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlContent .= '<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xmlContent .= '<ss:Worksheet ss:Name="Sheet1">';
        $xmlContent .= '<ss:Table>';

        // داده‌های تراکنش
        foreach ($transactions as $transaction) {
            $xmlContent .= '<ss:Row>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . ($transaction->user ? $transaction->user->name . ' ' . $transaction->user->family : '') . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="Number">' . ($transaction->user ? $transaction->user->national_code: '') . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="Number">' . ($transaction->user ? $transaction->user->mobile: '') . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . ($this->convertDate($transaction->created_at, 'Y/m/d')) . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . ($this->convertDate($transaction->created_at, 'H:i')) . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . ($transaction->type=='deposit' ? 'خرید'  : 'فروش') . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="Number">' . ($transaction->amount * 10) . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="Number">' . ($this->calculateFee($transaction->amount,$transaction->type) * 10 ) . '</ss:Data></ss:Cell>';
            $xmlContent .= '</ss:Row>';
        }

        $xmlContent .= '</ss:Table>';
        $xmlContent .= '</ss:Worksheet>';
        $xmlContent .= '</ss:Workbook>';

        // ارسال فایل برای دانلود
        return \Response::make($xmlContent, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="transactions.xls"',
        ]);
    }

    function calculateFee($amount, $type) {
        return $amount * 0.005;

        // تعریف نرخ‌های کارمزد
        $buyFeeRate1 = 0.004;  // 0.4% برای خرید زیر ۵ میلیون
        $buyFeeRate2 = 0.003;  // 0.3% برای خرید بین ۵ تا ۱۰ میلیون
        $buyFeeRate3 = 0.0025; // 0.25% برای خرید بالای ۱۰ میلیون

        $sellFeeRate = 0.001; // 0.1% برای فروش (ثابت برای همه مبالغ)

        // محاسبه کارمزد بر اساس نوع عملیات و مبلغ
        if ($type === 'buy'|| $type === 'deposit') {
            if ($amount < 5000000) {
                return $amount * $buyFeeRate1;
            } elseif ($amount >= 5000000 && $amount <= 10000000) {
                return $amount * $buyFeeRate2;
            } else {
                return $amount * $buyFeeRate3;
            }
        } elseif ($type === 'sell' || $type === 'withdraw') {
            return $amount * $sellFeeRate;
        } else {
            throw new Exception("نوع عملیات نامعتبر است. فقط 'buy' یا 'sell' مجاز است.");
        }
    }
}
