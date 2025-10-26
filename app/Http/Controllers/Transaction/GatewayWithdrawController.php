<?php
namespace App\Http\Controllers\Transaction;

use App\Models\AutomaticDeposit;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\PaymentGateway\PaymentGateway;
use App\Models\UserCardBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali;
use DB;
use Crypt;

class GatewayWithdrawController extends Controller
{
    function listWithdraw(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;
        $offset = (($request->page - 1) ?? 0) *$limit;

        $result = (object)array();
        $query = AutomaticDeposit::query();

        $query->leftJoin('users','payment_gateway_auto_deposit.id_user','users.id');
        $query->leftJoin('users_cardbank','payment_gateway_auto_deposit.id_cardbank','users_cardbank.id');
        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $query->select('payment_gateway_auto_deposit.*','users.name','users.family','users.email');
        $query->limit($limit)->offset($offset);
        $transactions = $query->get();
        foreach ($transactions as $transaction) {
            $transaction->date = $this->convertDate($transaction->created_at, 'Y/m/d - H:i');
            $transaction->iban = 'IR-'.str_replace(['IR','-'],'',$transaction->iban);
        }
        $result->lists = $transactions;
        $result->total = $totalCount;

        $result->gatewayslist = PaymentGateway::select('name')->where('withdraw',1)->get();
        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'gateway': $sortBy = 'gateway_withdraw'; break;
            case 'date': $sortBy = 'payment_gateway_auto_deposit.created_at'; break;
            case 'id': $sortBy = 'payment_gateway_auto_deposit.id'; break;
            case 'idTransaction': $sortBy = 'id_internal_transaction'; break;
            default: $sortBy = $request->sortBy;
        }


        if (!empty($search)) {
            $fields = ['payment_gateway_auto_deposit.id', 'iban', 'gateway_withdraw','payment_gateway_auto_deposit.data', 'users.name', 'mobile', 'email', 'family'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('payment_gateway_auto_deposit.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('payment_gateway_auto_deposit.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }
        if (isset($request->amountStart))
            $query->where('payment_gateway_auto_deposit.amount','>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('payment_gateway_auto_deposit.amount','<=', $request->amountStop);
        if (isset($request->id)){
            $ids = explode(',',$request->id);
            $query->whereIn('payment_gateway_auto_deposit.id', $ids);
        }
        if (isset($request->gateway))
            $query->where('gateway_withdraw', $request->gateway);

        if (isset($request->bank))
            $query->where('bank_name', $request->bank);

        if (isset($request->id_user))
            $query->where('payment_gateway_auto_deposit.id_user', $request->id_user);

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function singleWithdraw(Request $request){
        $withdraw = AutomaticDeposit::find($request->id);
        $withdraw->date = $this->convertDate($withdraw->created_at, 'd F Y - H:i');
        $withdraw->cardbank = UserCardBank::find($withdraw->id_cardbank);
        if(isset($withdraw->id_admin))
            $withdraw->admin = AdminUser::select('id','name')->find($withdraw->id_admin);
        else
            $withdraw->admin = null;

        return response()->json(array('status'=>true ,'msg'=>'', 'withdraw'=> $withdraw));
    }


    function balance()
    {
        $Zibal = PaymentGateway::where('name','baje')->first();
        $authorization = 'Bearer '. $Zibal->token;
        $response = Http::withHeaders(['Content-Type'=>'application/json','Authorization' => $authorization])
            ->post('https://api.zibal.ir/v1/wallet/balance');
        try{
            $response = (object)$response->throw()->json();
        } catch(\Exception $e){
            return (object) array('status' => false,'msg' => ($e->getMessage()));
        }
    }

    function ibanInquiry(Request $request)
    {
        $ibanValidate = self::ibanValidate($request->iban);
        if ($ibanValidate != true) {
            $result = array('status' => false, 'msg' => 'شماره شبا اشتباه است!');
            return response()->json((object)$result);
        }

        $response = Http::withHeaders(['Content-Type'=>'application/json','Authorization' => 'Bearer b28f7e380121481e9cb4ad4c6318823e'])
            ->post('https://api.zibal.ir/v1/facility/ibanInquiry',['IBAN'=>$request->iban]);
        try{
            $response = (object)$response->throw()->json();
            return $response->data;
        } catch(\Exception $e){
            return (object) array('status' => false,'msg' => ($e->getMessage()));
        }
    }
    function ibanValidate($iban)
    {
        $iban = str_replace([' ', '-'], '', $iban);
        $iban = substr($iban, 4) . substr($iban, 0, 4);
        $iban = str_replace('IR', '1827', $iban);

        if (bcmod($iban, 97) == 1)
            return true;
        else
            return false;
    }


    function withdraw(Request $request)
    {
        //return response()->json(array('status' => false, 'msg' => 'شماره شبا اشتباه است!'), 400);
        $validator = \Validator::make($request->all(), [
            'amount'    => 'required|numeric',
            'iban'    => 'required',
            'description'    => 'required',
            'uniqueCode'    => 'required',
        ]);
        if ($validator->fails()) {
            $result = array('status' => false, 'msg' => $validator->errors()->first());
            return response()->json($result,400);
        }

        $ibanValidate = self::ibanValidate($request->iban);
        if ($ibanValidate != true) {
            $result = array('status' => false, 'msg' => 'شماره شبا اشتباه است!');
            return response()->json((object)$result,400);
        }

        $iban = str_replace('IR','',$request->iban);
        $amount = $request->amount;
        $uniqueCode = $request->uniqueCode;

        $gatewayWithdraw = $request->payment;
        if($gatewayWithdraw == 'zibal'){
            $zibal = new \App\Models\PaymentGateway\Zibal();
            $result = $zibal->zibalGatewayWithdraw($amount,$iban,$uniqueCode);
        }elseif ($gatewayWithdraw == 'paystar'){
            $paystar = new \App\Models\PaymentGateway\Paystar();
            $result = $paystar->withdrawOpenBanking($amount,$iban,$uniqueCode,$request->name,$request->family);
        }elseif ($gatewayWithdraw == 'baje'){
            $baje = new \App\Models\PaymentGateway\Baje();
            $result = $baje->withdraw($amount,$iban,$uniqueCode,$request->description,$request->model,false,$request->bajeAccount);
        }
        if($result->status){
            $money = new AutomaticDeposit;
            $money->iban = $request->iban;
            $money->id_cardbank = 1;
            $money->id_user = 1;
            $money->amount =  $amount;
            $money->gateway_withdraw = 'baje';
            $money->data = json_encode($result->response);
            $money->save();
        }
        return response()->json($result,$result->status ? 200 : 400);

    }

}
