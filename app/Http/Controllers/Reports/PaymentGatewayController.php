<?php
namespace App\Http\Controllers\Reports;

use App\Models\AdminHesab;
use App\Models\AutomaticDeposit;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\PaymentGateway\Zibal;
use App\Models\User;
use App\Models\Internalcurrency;
use App\Models\WalletsInternal;
use App\Models\PaymentGateway\PaymentGateway;
use App\Models\TransactionInternal;
use App\Models\UserCardBank;
use App\Models\Orders;
use Illuminate\Http\Request;
use Morilog\Jalali;
use DB;
use Crypt;

class PaymentGatewayController extends Controller
{
    function listInternal(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = TransactionInternal::query();


        $query->leftJoin('users','users_transaction_internal.id_user','users.id');
        $query->leftJoin('internalcurrency','users_transaction_internal.id_internalcurrency','internalcurrency.id');
        $query->leftJoin('users_cardbank','users_transaction_internal.id_cardbank','users_cardbank.id');

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $query->select('users_transaction_internal.*','iban','card_number','internalcurrency.symbol','internalcurrency.name as nameCurrency','users.name','users.family','users.email','users.name_display');
        $transactions = $query->paginate($limit)->items();
        foreach ($transactions as $transaction) {
            $transaction->date = $this->convertDate($transaction->created_at, 'Y/m/d - H:i');
        }
        $result->lists = $transactions;
        $result->total = $totalCount;

        $result->gatewayslist = PaymentGateway::select('name')->get();
        $result->tableCalculate = $this->tableCalculate($request);
        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'date': $sortBy = 'users_transaction_internal.created_at'; break;
            case 'id': $sortBy = 'users_transaction_internal.created_at'; break;
            default: $sortBy = $request->sortBy;
        }


        if (!empty($search)) {
            $fields = ['users_transaction_internal.id', 'description', 'users_transaction_internal.data',
                'trace_number', 'users.name', 'mobile', 'email', 'family','name_display','bank_name','card_number','iban'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->type))
            $query->where('type', $request->type);
        if (isset($request->status)) {
            $query->where('users_transaction_internal.status', $request->status);
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('users_transaction_internal.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_transaction_internal.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }
        if (isset($request->amountStart))
            $query->where('users_transaction_internal.amount','>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('users_transaction_internal.amount','<=', $request->amountStop);
        if (isset($request->via))
            $query->whereRaw('JSON_CONTAINS(users_transaction_internal.data, ?)', [json_encode(array('via' => $request->via))]);
        if (isset($request->id)){
            $ids = explode(',',$request->id);
            $query->whereIn('users_transaction_internal.id', $ids);
        }
        if (isset($request->gateway))
            $query->where('payment_gateway', $request->gateway);

        switch ($request->other){
            case 'trGateway':
                $query->whereNotNull('payment_gateway');
                break;
            case 'trOrders':
                $query->whereNotNull('id_order');
                break;
            case 'trReceipt':
                $query->where('description', 'like', '%Receipt%');
                break;
        }

        if (isset($request->id_user))
            $query->where('users_transaction_internal.id_user', $request->id_user);

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function tableCalculate($request){
        $result = (object)array();
        $query = TransactionInternal::query();
        $query->leftJoin('users','users_transaction_internal.id_user','users.id');
        $query->leftJoin('internalcurrency','users_transaction_internal.id_internalcurrency','internalcurrency.id');
        $query->leftJoin('users_cardbank','users_transaction_internal.id_cardbank','users_cardbank.id');
        $query = self::filters($query,$request);
        $result->sum_deposit = $query->where('type','deposit')->sum('amount');

        $query = TransactionInternal::query();
        $query->leftJoin('users','users_transaction_internal.id_user','users.id');
        $query->leftJoin('internalcurrency','users_transaction_internal.id_internalcurrency','internalcurrency.id');
        $query->leftJoin('users_cardbank','users_transaction_internal.id_cardbank','users_cardbank.id');
        $query = self::filters($query,$request);
        $result->sum_withdraw = $query->where('type','withdraw')->sum('amount');
        $result->residual = $result->sum_deposit - $result->sum_withdraw;
        return $result;
    }


    function addTran(Request $request){
        $validator = \Validator::make($request->all(), [
            'type'    => 'required',
            'amount'    => 'required|numeric',
            'dis' => 'required',
        ]);
        if ($validator->fails()) {
            $result = array('status' => false, 'msg' => $validator->errors()->first());
            return response()->json($result);
        }

        $trInternal = new TransactionInternal();
        $trInternal->id_user = 1;
        $trInternal->type = $request->type;
        $trInternal->amount = $request->amount;
        $trInternal->payment = $request->amount;
        $trInternal->status = 'success';
        $trInternal->description = $request->dis;
        $trInternal->id_admin = \Auth::user()->id;
        $trInternal->payment_gateway = $request->payment_gateway;
        $trInternal->save();



        $result = array('status'=> true , 'msg'=> __('Successfully registered.') );
        return response()->json($result);
    }
}
