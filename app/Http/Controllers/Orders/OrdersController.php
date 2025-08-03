<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Cryptocurrency;
use App\Models\Orders;
use App\Models\ReferralTransaction;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali;
use DB;

class OrdersController extends Controller
{
    function listOrders(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = Orders::query();

        $query->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'family', 'email', 'mobile', 'name_display', 'level_account');
            },
            'crypto' => function ($query) {
                $query->select('id','symbol');
            }
        ]);

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();

        $query->select('id','amount','amount_coin','created_at','type','status','id_crypto','id_user',
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(orders.data, '$.symbol')) as symbol"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(orders.data, '$.name')) as nameCoin")
        );
        $orders = $query->paginate($limit)->through(function ($order) {
            return [
                'id' => $order->id,
                'id_user' => $order->id_user, /////
                'amount' => $order->amount,
                'amount_coin' => $order->amount_coin,
                'type' => $order->type,
                'status' => $order->status,
                'date' =>  $this->convertDate($order->created_at, 'Y/m/d - H:i'),
                'user' => $order->user,
                'crypto' => isset($order->crypto)?$order->crypto:['symbol'=>$order->symbol,'name'=>$order->nameCoin],
                'symbol' => $order->symbol,
                'nameCoin' => $order->nameCoin,
            ];
        });

        $result->list =  $orders->items();;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query,$request){
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('orders.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('orders.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        $this->applyFilter($query, 'id_user', $request->id_user);
        $this->applyFilter($query, 'type', $request->type);
        $this->applyFilter($query, 'status', $request->status);
        $this->applyFilter($query, 'amount', $request->amountStart,'>=');
        $this->applyFilter($query, 'amount', $request->amountStop,'<=');
        $this->applyFilter($query, 'via', $request->via);

        if (isset($request->id)){
            $ids = explode(',',$request->id);
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

        switch ($request->sortBy){
            case 'date': $sortBy = 'orders.created_at'; break;
            case 'id': $sortBy = 'orders.created_at'; break;
            default: $sortBy = $request->sortBy;
        }
        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function statistic(){
        $statistic = (object)array();
        $statistic->total_orders = Orders::where('status','success')->count();
        $statistic->buy_orders = Orders::where('status','success')->where('type','buy')->count();
        $statistic->sell_orders = Orders::where('status','success')->where('type','sell')->count();
        $statistic->amount_orders = round(Orders::where('status','success')->sum('amount'));
        return response()->json($statistic);
    }

    function singleOrder(Request $request){
        $order = Orders::find($request->id);
        $order->date = $this->convertDate($order->created_at, 'd F Y - H:i');
        $order->description = __($order->description);
        $coin = Cryptocurrency::select('symbol')->where('id',$order->id_crypto)->first();
        if(!isset($coin)){
            $data = json_decode($order->data);
            $order->symbol = $data->symbol;
            $order->name = $data->name;
            $order->dataParam = isset($data->data)?json_decode(Crypt::decryptString( $data->data)):null;
            unset($data->data);
            $order->data = json_encode($data);
        }else
            $order->symbol = $coin->symbol;

        $order->user = User::select('id','name','family','email','mobile','name_display')->find($order->id_user);
        $order->admin = AdminUser::select('id','name')->find($order->id_admin);

        $referral = UserReferral::where('id_user_referral',$order->user->id)->first();
        if(isset($referral)){
            $tr_referral = ReferralTransaction::where(['id_referral'=>$referral->id,'id_order'=>$order->id])->first();
            $order->referral_amount = $tr_referral->amount??0;
            $order->referral = $referral;
        }
        return response()->json(array('status'=>true ,'msg'=>'', 'order'=> $order));
    }


    public function checkPMV(Request $request){
        $ev_number = $request->ev_number;
        $setting = json_decode(Crypt::decryptString( \App\Models\Settings::where('name','perfectmoney')->first()->value) );
        $AccountID = $setting->account_id;
        $PassPhrase =  $setting->password;

        $baseUrl = "https://perfectmoney.com/acct/evcsv.asp?";
        $params = "&AccountID=" . $AccountID. "&PassPhrase=" . $PassPhrase . "&ev_number=" . $ev_number;

        try {
            $response = Http::withHeaders(['cache-control' => 'no-cache'])->get($baseUrl . $params);
            $result = $response->throw()->body();
            $lines = explode("\n", trim($result));
            if ($lines[0] != 'Created,e-Voucher number,Activation code,Currency,Batch,Payer Account,Payee Account,Activated,Amount') {
                return $lines[0];
            } else {
                $ar = array();
                $n = count($lines);
                if ($n != 2) return 'payment not found';

                $item = explode(",", $lines[1], 10);
                if (count($item) != 9) return 'invalid API output';
                $item_named['Created'] = $item[0];
                $item_named['Activation code'] = $item[2];
                $item_named['e-Voucher number'] = $item[1];
                $item_named['Currency'] = $item[3];
                $item_named['Batch'] = $item[4];
                $item_named['Payer Account'] = $item[5];
                $item_named['Payee Account'] = $item[6];
                $item_named['Activated'] = $item[7];
                $item_named['Amount'] = $item[8];
            }
            return array('status' => true, 'msg' => '', 'data' => $item_named);
        }catch (\Exception $e){
            return array('status'=>false, 'msg'=> $e->getMessage());
        }

    }
}
