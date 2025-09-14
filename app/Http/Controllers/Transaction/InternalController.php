<?php
namespace App\Http\Controllers\Transaction;

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
use App\Models\Cryptocurrency;
use Illuminate\Http\Request;
use Morilog\Jalali;
use DB;
use Crypt;

class InternalController extends Controller
{
    function listInternal(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = TransactionInternal::query();

        $query->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'family', 'email', 'mobile', 'name_display', 'level_account');
            },
            'cardBank' => function ($query) {
                $query->select('id', 'bank_name', 'card_number', 'iban');
            },
            'order' => function ($query) {
                 $query->select('id', 'data', 'id_crypto');
            },
            'order.crypto' => function ($query) {
                $query->select('id', 'symbol');
            }
        ]);

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();

        $query->select('id','type','amount','stock','users_transaction_internal.created_at','description','status','id_user','id_order','id_cardbank','users_transaction_internal.data');
        $transactions = $query->paginate($limit)->through(function ($transaction) {
            if (isset($transaction->order)) {
                if (isset($transaction->order->id_crypto)) {
                    $transaction->order->symbol = $transaction->order->crypto->symbol;
                } else {
                    $data = json_decode($transaction->order->data, true);
                    $transaction->order->symbol = $data['symbol'] ?? null;
                }
            }
            if($transaction->description == 'Convert small inventories'){
                $data = json_decode($transaction->data);
                $transaction->order = ['symbol'=>$data->symbol??''];
            }

            return [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
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
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        $this->applyFilter($query, 'id_user', $request->id_user);
        $this->applyFilter($query, 'id_order', $request->id_order);
        $this->applyFilter($query, 'type', $request->type);
        $this->applyFilter($query, 'status', $request->status);
        $this->applyFilter($query, 'amount', $request->amountStart,'>=');
        $this->applyFilter($query, 'amount', $request->amountStop,'<=');
        $this->applyFilter($query, 'payment_gateway', $request->gateway);

        if (isset($request->via))
            $query->whereRaw('JSON_CONTAINS(data, ?)', [json_encode(array('via' => $request->via))]);
        if (isset($request->id)){
            $ids = explode(',',$request->id);
            $query->whereIn('id', $ids);
        }
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
            case 'trDepositID':
                $query->where('description', 'like', '%Deposit with ID%');
                break;
            case 'trDepositCard':
                $query->where('description', 'like', '%Deposit via Card to Card%');
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
                $query->orWhereHas('cardBank', function ($q) use ($search) {
                    $q->where('iban', 'like', '%' . $search . '%')
                        ->orWhere('card_number', 'like', '%' . $search . '%')
                        ->orWhere('bank_name', 'like', '%' . $search . '%');
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
            case 'nameFamily': $sortBy = 'name'; break;
            case 'date': $sortBy = 'created_at'; break;
            case 'id': $sortBy = 'created_at'; break;
            default: $sortBy = $request->sortBy;
        }
        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function singleInternal(Request $request){
        $internal = TransactionInternal::find($request->id);
        $internal->date = $this->convertDate($internal->created_at, 'd F Y - H:i');
        $internal->description = __($internal->description);
        $internal->nameCurrency = Internalcurrency::find($internal->id_internalcurrency)->name;
        $internal->user = User::select('id','name','family','email','mobile','name_display','level')->find($internal->id_user);
        $internal->cardbank = UserCardBank::find($internal->id_cardbank);
        $internal->admin = AdminUser::select('id','name')->find($internal->id_admin);

        $data = json_decode($internal->data);
        if(isset($data->receipt_payment)){
            if(isset($data->receipt_payment->file_link))
                $data->receipt_payment->photo = \Crypt::encryptString($data->receipt_payment->file_link);
            $internal->receipt = $data->receipt_payment;
        }
        //return response()->json(array('status'=>true ,'msg'=>'', 'internal'=> $internal));


        $admin_hesab = AdminHesab::select('id','name','stock')->where('id_admin',\Auth::user()->id)->get();
        return response()->json(array('status'=>true ,'msg'=>'', 'internal'=> $internal,'admin_hesab'=>$admin_hesab));
    }

    function confirmInternal(Request $request){
        $TraInternal = TransactionInternal::where('id',$request->id)->where('status','pending')->whereNull('payment')->first();
        $user = User::find($TraInternal->id_user);
        $data = json_decode($TraInternal->data);
        if(isset($data->receipt_payment) && $TraInternal->type == 'deposit') {
           $result = self::confirmReceipt($TraInternal,$user,$request->amount);
        }else{
            $result = self::confirmWithdraw($TraInternal,$user,$request->ip(),$request->viaWithdraw,$request->bajeAccount);
        }
        return  response()->json($result);
    }

    function confirmReceipt($TraInternal,$user,$amount = null){
        DB::beginTransaction();
        try {
            $TraInternal->amount = $amount ?? $TraInternal->amount;
            $TraInternal->status = 'success';
            $TraInternal->payment = $TraInternal->amount;
            $TraInternal->id_admin = \Auth::user()->id;
            $TraInternal->save();

            $internal = Internalcurrency::find($TraInternal->id_internalcurrency);
            $wallet = WalletsInternal::where('id_internal',$internal->id)->where('id_user',$user->id)->first();
            if(!isset($wallet))
                $wallet = $this->createWalletInternal($internal->id,$user->id);
            $balance = round( Crypt::decryptString($wallet->value),$internal->percent);
            $balance_available = round( Crypt::decryptString($wallet->value_available),$internal->percent);

            $balance += $TraInternal->amount;
            $balance_available += $TraInternal->amount;
            $wallet->value = Crypt::encryptString(round($balance,$internal->percent));
            $wallet->value_available = Crypt::encryptString(round($balance_available,$internal->percent));
            $wallet->value_num = $balance;
            $wallet->value_available_num = $balance_available;
            $wallet->save();
            DB::commit();
            // send Notif
            $this->sendNotification($user->id,'confirmInternalReceipt',
                ['amount'=> number_format($TraInternal->amount),'sms'=>[number_format($TraInternal->amount)]]);

            self::logSave('trInternal',['id_tr'=>$TraInternal->id,'id_user'=>$user->id,'amount'=>$TraInternal->amount],
                'تایید فیش واریز تومان #'.$TraInternal->id);
            return array('status'=>true ,'msg'=>'فیش با موفقیت تایید و کیف پول کاربر به مبلغ '.number_format($TraInternal->amount).' شارژ شد.');

        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
        }
    }

    function confirmWithdraw($TraInternal,$user,$ip = null,$viaWithdraw = 'manual',$bajeAccount = null){
        DB::beginTransaction();
        try {

            if ($viaWithdraw != 'manual'){
                if($bajeAccount != null){
                    $data = json_decode($TraInternal->data);
                    $data->baje_account = $bajeAccount;
                    $TraInternal->data = json_encode($data);
                    $TraInternal->save();
                }
                $result = self::automaticDeposit($TraInternal->id,$viaWithdraw,$user);
            }else{
                $TraInternal->status = 'success';
                $TraInternal->payment = $TraInternal->amount;
                $TraInternal->payment_gateway = $viaWithdraw;
                $TraInternal->id_admin = \Auth::user()->id;
                $TraInternal->save();
                $result =  array('status'=>true ,'msg'=>'برداشت به مبلغ '.number_format($TraInternal->amount).' با موفقیت تایید شد.');
            }
            DB::commit();

            if ($result['status'] == true){
                // send Notif
                $this->sendNotification($user->id,'confirmInternalWithdraw',
                    ['amount'=> number_format($TraInternal->amount),'sms'=>[number_format($TraInternal->amount)]]);

                self::logSave('trInternal',['id_tr'=>$TraInternal->id,'id_user'=>$user->id,'amount'=>$TraInternal->amount],
                    'تایید برداشت تومان #'.$TraInternal->id,$ip);
            }

            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().$e->getFile().':'.$e->getLine());
        }
    }

    private function automaticDeposit($id_TraInternal,$gatewayWithdraw,$user){
        DB::beginTransaction();
        try {
            $TraInternal = TransactionInternal::where('id',$id_TraInternal)->where('status','pending')->whereNull('payment')->first();
            $AutomaticDeposit = AutomaticDeposit::where('id_internal_transaction',$id_TraInternal)->first();
            if(!isset($TraInternal) || isset($AutomaticDeposit)){
                return array('status' => false, 'msg' => 'این تراکنش قبلا تایید شده است.');
            }
            $card = UserCardBank::find($TraInternal->id_cardbank);
            $iban = str_replace(['-','IR',' '],'',$card->iban);

            $id_last = $TraInternal->id;
            if($gatewayWithdraw == 'zibal'){
                $zibal = new \App\Models\PaymentGateway\Zibal();
                $result = $zibal->zibalGatewayWithdraw($TraInternal->amount,$iban,$id_last);
            }elseif ($gatewayWithdraw == 'paystar'){
                $paystar = new \App\Models\PaymentGateway\Paystar();
                $result = $paystar->withdrawOpenBanking($TraInternal->amount,$iban,$id_last,$user->name,$user->family);
            }elseif ($gatewayWithdraw == 'jibimo'){
                $jibimo = new \App\Models\PaymentGateway\Jibimo();
                $result = $jibimo->withdraw($TraInternal,$iban);
            }elseif ($gatewayWithdraw == 'baje'){
                $baje = new \App\Models\PaymentGateway\Baje();
                $data = json_decode($TraInternal->data);
                $result = $baje->withdraw($TraInternal->amount,$iban,$id_last,'تسویه کاربر',null,true,$data->baje_account);
            }

            if ($result->status == true) {
                $money = new AutomaticDeposit;
                $money->iban = $card->iban;
                $money->id_cardbank = $card->id;
                $money->id_user = $user->id;
                $money->amount = $TraInternal->amount;
                $money->id_internal_transaction = $TraInternal->id;
                $money->gateway_withdraw = $gatewayWithdraw;
                $money->data = json_encode($result->response);
                $money->save();

                $TraInternal->status = 'success';
                $TraInternal->payment = $TraInternal->amount;
                $TraInternal->payment_gateway = $gatewayWithdraw;
                $TraInternal->id_admin = \Auth::user()->id;
                $TraInternal->save();
                $result = array('status' => true,'msg' => 'واریز اتوماتیک به مبلغ '.number_format($TraInternal->amount).' با موفقیت انجام شد','response'=>$result);

                // Send Notif
                $job = (new \App\Jobs\NotificationCenter($TraInternal->id_user,'confirmInternalWithdraw',
                    ['amount'=> number_format($TraInternal->amount),'sms'=>[number_format($TraInternal->amount)]]))->delay(\Carbon\Carbon::now()->addSeconds(1));
                dispatch($job);

                // Save Log
                self::logSave('automaticDeposit',
                    ['id_tr'=>$TraInternal->id,'id_user'=>$user->id,'amount'=>$TraInternal->amount,'gatewayWithdraw'=>$gatewayWithdraw,'id_deposit'=>$money->id],
                    'واریز اتوماتیک تومان #'.$TraInternal->id);
            } else{
                $result = array('status' => false,'msg' => $result->msg);
            }

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().$e->getFile().':'.$e->getLine());
        }
    }

    function rejectInternal(Request $request){
        $TraInternal = TransactionInternal::where('id',$request->id)->whereIn('status',['pending','success'])
            ->where('created_at','>',date('Y-m-d H:i:s',strtotime('- 720 hour')))->first();
        $user = User::find($TraInternal->id_user);
        $data = json_decode($TraInternal->data);
        DB::beginTransaction();
        try {
            $TraInternal->status = 'reject';
            $TraInternal->payment = $TraInternal->amount;
            $TraInternal->id_admin = \Auth::user()->id;
            $data->reason_reject = $request->reason;
            $TraInternal->data = json_encode($data);
            $TraInternal->save();
            if($TraInternal->type == 'deposit') {
                DB::commit();
                // send Notif
                $this->sendNotification($user->id,'rejectInternalReceipt',
                    ['amount'=> number_format($TraInternal->amount),'sms'=>[number_format($TraInternal->amount)]]);

                self::logSave('trInternal',['id_tr'=>$TraInternal->id,'id_user'=>$user->id,'amount'=>$TraInternal->amount],
                    'ریجکت فیش واریز تومان #'.$TraInternal->id,$request->ip());
                return array('status'=>true ,'msg'=>'تراکنش ثبت فیش با موفقیت رد شد.');
            }else{
                //return amount
                $internal = Internalcurrency::find($TraInternal->id_internalcurrency);
                $wallet = WalletsInternal::where('id_internal',$internal->id)->where('id_user',$user->id)->first();
                if(!isset($wallet)){
                    $wallet = new WalletsInternal();
                    $wallet->id_user = $user->id;
                    $wallet->id_internal = 1;
                    $wallet->value = Crypt::encryptString(0);
                    $wallet->value_available = Crypt::encryptString(0);
                    $wallet->save();
                }
                $balance = round( Crypt::decryptString($wallet->value),$internal->percent);
                $balance_available = round( Crypt::decryptString($wallet->value_available),$internal->percent);

                $balance += $TraInternal->amount;
                $balance_available += $TraInternal->amount;
                $wallet->value = Crypt::encryptString(round($balance,$internal->percent));
                $wallet->value_available = Crypt::encryptString(round($balance_available,$internal->percent));
                $wallet->value_num = round($balance,$internal->percent);
                $wallet->value_available_num = round($balance_available,$internal->percent);
                $wallet->save();
                DB::commit();

                // send Notif
                $this->sendNotification($user->id,'rejectInternalWithdraw',
                    ['amount'=> number_format($TraInternal->amount),'sms'=>[number_format($TraInternal->amount)]]);
                self::logSave('trInternal',['id_tr'=>$TraInternal->id,'id_user'=>$user->id,'amount'=>$TraInternal->amount],
                    'ریجکت برداشت تومان #'.$TraInternal->id,$request->ip());
                return array('status'=>true ,'msg'=>'تراکنش با موفقیت رد شد و مبلغ به کیف پول کاربر برگردانده شد.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().$e->getFile().':'.$e->getLine());
        }
    }

    function verifyInternal(Request $request){
        $TraInternal = TransactionInternal::where('id',$request->id)->where('status','suspend')->whereNull('payment')->first();
        $user = User::find($TraInternal->id_user);
        $data = json_decode($TraInternal->data);
        DB::beginTransaction();
        try {
            $zibal = new Zibal();
            $resultVerify = $zibal->Verify($data->before_payment->trackId);
            if (isset($resultVerify->result ) && $resultVerify->result  == 100) {
                $TraInternal->status = 'success';
                $TraInternal->payment = $TraInternal->amount;
                $TraInternal->trace_number = $resultVerify->refNumber;
                $TraInternal->description = 'Deposit from the payment gateway';

                $data->resultVerify = $resultVerify;
                $TraInternal->data = json_encode($data);
                $TraInternal->save();

                $internal = \App\Models\Internalcurrency::find($user->id_internal);
                $wallet = \App\Models\WalletsInternal::where('id_internal',$internal->id)->where('id_user',$user->id)->first();
                $balance = round( \Illuminate\Support\Facades\Crypt::decryptString($wallet->value),$internal->percent);
                $balance_available = round(Crypt::decryptString($wallet->value_available),$internal->percent);

                $balance += $TraInternal->amount;
                $balance_available += $TraInternal->amount;
                $wallet->value = Crypt::encryptString(round($balance,$internal->percent));
                $wallet->value_available = Crypt::encryptString(round($balance_available,$internal->percent));
                $wallet->value_num = round($balance,$internal->percent);
                $wallet->value_available_num = round($balance_available,$internal->percent);
                $wallet->save();
                DB::commit();

                $result = array('status' => true, 'msg' =>'تراکنش با موفقیت وریفای شد و به کیف پول کاربر اضافه شد.');
            }else{
                $result = array('status' => false, 'msg' => 'خطا در تراکنش '.json_encode($resultVerify));
            }
            return $result;
        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
        }
    }

    function inquiryReceipt(Request $request){
        $TraInternal = TransactionInternal::where('id',$request->id)->first();
        return $TraInternal;
    }

    function confirmGroupInternal(Request $request){
        $TraInternals = TransactionInternal::whereIn('id',$request->ids)->where('type','withdraw')->whereIn('status',['pending'])/*->whereIn('id_user',[43,638])*/->get();
        try{
            foreach ($TraInternals as $internal){
                $user = User::find($internal->id_user);
                //$data = json_decode($internal->data);
                //if(isset($data->receipt_payment) && $internal->type == 'deposit') {
                    //self::confirmReceipt($internal,$user);
                //}else{
                    self::confirmWithdraw($internal,$user,$request->ip(),$request->viaWithdraw,$request->bajeAccount);
                //}
            }
            return array('status' => true, 'msg' =>'تمامی تراکنش ها به صورت دستی تایید شدند.');
        }catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
        }
    }




    public function exportXls(Request $request)
    {
        // دریافت داده‌ها
        $transactions = TransactionInternal::with(['user', 'cardBank'])->whereIn('id', $request->ids)->whereIn('status', ['pending'])->get();

        // تولید محتوای XML برای فایل Excel
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlContent .= '<ss:Workbook xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xmlContent .= '<ss:Worksheet ss:Name="Sheet1">';
        $xmlContent .= '<ss:Table>';

        // داده‌های تراکنش
        foreach ($transactions as $transaction) {
            $xmlContent .= '<ss:Row>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="Number">' . ($transaction->amount*10) . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . ($transaction->cardBank ? preg_replace('/\D/', '', $transaction->cardBank->iban) : '') . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . ($transaction->user ? $transaction->user->name . ' ' . $transaction->user->family : '') . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . 'تسویه کاربر' . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . $transaction->id . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . 'SPAC' . '</ss:Data></ss:Cell>';
            $xmlContent .= '<ss:Cell><ss:Data ss:Type="String">' . 'paya' . '</ss:Data></ss:Cell>';
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
}
