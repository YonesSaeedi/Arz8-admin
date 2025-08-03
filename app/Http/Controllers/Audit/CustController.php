<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Models\Audit\DailyAudit;
use App\Models\Orders;
use App\Models\TransactionCrypto;
use Illuminate\Http\Request;
use Morilog\Jalali;
use DB;
use App\Models\Audit\CostAudit;

class

CustController extends Controller
{
    function listCust(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = CostAudit::query();

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $custs = $query->paginate($limit)->items();
        foreach ($custs as $cust) {
            $cust->date = $this->convertDate($cust->created_at, 'd F Y');
        }
        $result->list = $custs;
        $result->total = $totalCount;

        // sum benefit
        $query = CostAudit::query();
        $query->where('asset','TOMAN');
        $query = self::filters($query,$request);
        $result->sum_cust = round($query->sum('amount')??0);

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'date': $sortBy = 'created_at'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['id', 'amount', 'description', 'file', 'asset', 'fee'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStart);
                $query->where('created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStop);
                $query->where('created_at','<', $dateStop);
            }catch(\Exception $e){}
        }
        if (isset($request->amountStart))
            $query->where('amount','>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('amount','<=', $request->amountStop);

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }

    function addCust(Request $request){
        $validator = \Validator::make($request->all(), [
            'type'    => 'required',
            'description'    => 'required',
            'amount'    => 'required|numeric',
            'fee'    => 'nullable|numeric',
            'date'    => 'required',
            'file' => 'nullable|mimes:jpg,jpeg,png,pdf|max:20500',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $date = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', date('Y/m/d H:i:s',strtotime($request->date)));

        $cust = new CostAudit();
        if($request->type !== 'decrement') {
            $cust->fee = $request->fee;
            $cust->asset = $request->type;
        }
        $cust->type =  ($request->type !== 'decrement' ? 'increase':'decrement');
        $cust->amount =  $request->amount;
        $cust->description = $request->description;
        $cust->tax = $request->tax;
        $cust->created_at = $date;
        $cust->updated_at = $date;
        $cust->save();

        if($request->hasFile('file')) {
            $functions = new \App\Functions;
            $directory = 'uploads/Custs/' . $cust->created_at->year . '/' . $cust->created_at->month;
            $file = $request->file('file');
            $SaveFile = $functions->saveFile($file, $directory);
            if($SaveFile['status'] == true){
                $cust->file = $SaveFile['url'];
            }
            $cust->save();
        }

        if($date < date('Y-m-d 00:00:00')){
            $daily = DailyAudit::where('date',$date)->first();
            $daily->cust = $daily->cust + $request->amount;
            $daily->save();
        }
        self::logSave('audit.addCust',$cust,'درج هزینه',$request->ip());
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');
    }

    function removeCust(Request $request){
        $cust = CostAudit::find($request->id);
        if(isset($cust->file)){
            try {
                \Storage::disk('uploads')->delete($cust->file);
            }catch (\Exception $e){}
        }
        self::logSave('audit.removeCust',$cust,'حذف هزینه',$request->ip());
        $cust->delete();
        return array('status' => true, 'msg' => 'با موفقیت حذف شد.');
    }
    function fileCust(Request $request){
        $cust = CostAudit::find($request->id);
        $nameFull = basename(basename(storage_path($cust->file)));

        return \Storage::disk('uploads')->download(
            str_replace('uploads/','',$cust->file),
            $nameFull,
            ['name'=>$nameFull]);
    }
}
