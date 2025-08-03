<?php
namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\AdminUser;
use App\Models\Cryptocurrency;
use App\Models\CryptoNetwork;
use App\Models\Orders;
use App\Models\Trades;
use App\Models\TransactionCrypto;
use App\Models\User;
use App\Models\WalletsCrypto;
use Crypt;
use DB;
use Illuminate\Http\Request;
use Morilog\Jalali;

class CryptoController extends ExchangeApi
{
    function listCrypto(Request $request)
    {

        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = TransactionCrypto::query();

        $query->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'family', 'email', 'mobile', 'name_display', 'level_account');
            },
            'crypto' => function ($query) {
                $query->select('id','symbol');
            },
            'order' => function ($query) {
                $query->select('id', 'id_crypto');
            },
            'trade' => function ($query) {
                $query->select('id');
            },

        ]);

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();

        $query->select('id','id_crypto','type','amount', 'stock','description','status','created_at','id_user','id_order','id_trade');
        $query->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(users_transaction_crypto.data, "$.primary")) as primary_wallet');

        $transactions = $query->paginate($limit)->through(function ($transaction) {
            return [
                'id' => $transaction->id,
                'amount' => $transaction->amount,
                'stock' => $transaction->stock,
                'type' => $transaction->type,
                'status' => $transaction->status,
                'date' =>  $this->convertDate($transaction->created_at, 'Y/m/d - H:i'),
                'description' => __($transaction->description) . ($transaction->primary_wallet == 'true' ? '(اختصاصی)':''),
                'user' => $transaction->user,
                'crypto' => $transaction->crypto,
                'order' => $transaction->order,
                'trade' => $transaction->trade,
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
                $query->where('users_transaction_crypto.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_transaction_crypto.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        $this->applyFilter($query, 'id_user', $request->id_user);
        $this->applyFilter($query, 'type', $request->type);
        $this->applyFilter($query, 'status', $request->status);
        $this->applyFilter($query, 'amount', $request->amountStart,'>=');
        $this->applyFilter($query, 'amount', $request->amountStop,'<=');

        if (isset($request->via)){
            $query->whereRaw('JSON_CONTAINS(users_transaction_crypto.data, ?)', [json_encode(array('via' => $request->via))])
                ->orWhere('orders.via',$request->via)->orWhere('trades.via',$request->via);
        }
        if (isset($request->id)){
            $ids = explode(',',$request->id);
            $query->whereIn('users_transaction_crypto.id', $ids);
        }
        if (isset($request->idOrders)){
            $ids = explode(',',$request->idOrders);
            $query->whereIn('id_order', $ids);
        }
        if (isset($request->idTrades)){
            $ids = explode(',',$request->idTrades);
            $query->whereIn('id_trade', $ids);
        }
        switch ($request->other){
            case 'trOrders':
                $query->whereNotNull('id_order');
                break;
            case 'trTrades':
                $query->whereNotNull('id_trade');
                break;
            case 'trNoOrdersAndTrades':
                $query->whereNull('id_order')->whereNull('id_trade')->where('users_transaction_crypto.description','!=','for wheel of luck')->where('users_transaction_crypto.description','!=','Convert small inventories');
                break;
        }

        $search = $request->search;
        if (!empty($search)) {
            $fields = ['id', 'description','destination', 'data'];
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
            case 'nameFamily': $sortBy = 'name'; break;
            case 'date': $sortBy = 'users_transaction_crypto.created_at'; break;
            case 'id': $sortBy = 'users_transaction_crypto.created_at'; break;
            case 'coin': $sortBy = 'cryptocurrency.name'; break;
            default: $sortBy = $request->sortBy;
        }
        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function singleCrypto(Request $request){
        $transaction = TransactionCrypto::find($request->id);
        if(!isset($transaction))
            abort(404);
        $data = json_decode($transaction->data);
        $transaction->date = $this->convertDate($transaction->created_at, 'd F Y - H:i');
        $transaction->description = __($transaction->description).(isset($data->primary)?'(اختصاصی)':'');
        $transaction->symbol = Cryptocurrency::find($transaction->id_crypto)->symbol;
        $transaction->network = CryptoNetwork::find($transaction->id_network);
        $transaction->user = User::select('id','name','family','email','mobile','level')->find($transaction->id_user);
        $transaction->admin = AdminUser::select('id','name')->find($transaction->id_admin);
        $transaction->deposit_success = TransactionCrypto::where(['id_user'=>$transaction->id_user,'type'=>'deposit','status'=>'success'])->whereNotNull('txid')->count();

        $transaction->txid_duplicate = TransactionCrypto::select('id')->whereNotNull('txid')->where('id', '!=', $transaction->id)->where('txid', $transaction->txid)->get();


        $data = json_decode($transaction->data);
        if(isset($data->file_link))
            $transaction->photo = \Crypt::encryptString($data->file_link);

        if($transaction->id_order){
            $order =  Orders::find($transaction->id_order);
            $transaction->via = $order->via;
            $transaction->ip = $order->ip;
        }
        else if($transaction->id_trade){
            $trade = Trades::find($transaction->id_trade);
            $transaction->via = $trade->via??'--';
            $transaction->ip = $trade->ip??'--';
        }
        else{
            $transaction->via = $data->via??'website';
            $transaction->ip = $data->ip??'--';
        }

        // If transaction between users
        if (isset($data->receivingUser)){
            $receivingUser = User::find($data->receivingUser);
            $transaction->receivingUser = (object)[];
            $transaction->receivingUser->name = isset($receivingUser->name) ?($receivingUser->name .' '.$receivingUser->family):$receivingUser->name_display;
            $transaction->receivingUser->receivingUserFindBy = $data->receivingUserFindBy;
        }
        if(isset($data->senderUser)){
            $senderUser = User::find($data->senderUser);
            $transaction->senderUser = (object)[];
            $transaction->senderUser->name = isset($senderUser->name) ?($senderUser->name .' '.$senderUser->family):$senderUser->name_display;
            $transaction->senderUser->receivingUserFindBy = $data->receivingUserFindBy;
        }

        if ($transaction->type =='deposit' && isset($transaction->txid) && $transaction->status =='pending'){
            $depositHistory = $this->binance->api[0]->depositHistory($transaction->symbol, array('startTime' => strtotime('-3 day', time()) . '000', 'endTime' => time() . '000'));
            $indexArrayFind = (string)array_search($transaction->txid, array_column($depositHistory, 'txId'));
            $inquiry_binance = $depositHistory[$indexArrayFind]??null;
        }

        return response()->json(array('status'=>true ,'msg'=>'', 'transaction'=> $transaction,'inquiry_binance'=>$inquiry_binance??null));
    }

    function confirmCrypto(Request $request){
        $transaction = TransactionCrypto::where('id',$request->id)->whereIn('status',['pending','reject'])->whereNull('payment')->first();
        $user = User::find($transaction->id_user);
        if($transaction->type == 'deposit') {
           $result = self::confirmDeposit($transaction,$user);
        }else{
            $result = self::confirmWithdraw($transaction,$user,$request);
        }
        return  response()->json($result);
    }

    function confirmDeposit($transaction,$user){
        DB::beginTransaction();
        try {
            $crypto = Cryptocurrency::find($transaction->id_crypto);

            $transaction->status = 'success';
            $transaction->payment = $transaction->amount;
            $transaction->id_admin = \Auth::user()->id;
            $transaction->save();

            //get Balance
            $wallet = WalletsCrypto::where('id_crypto',$crypto->id)->where('id_user',$user->id)->first();
            if(!$wallet)
                $wallet = self::createWallet($crypto->id,$user->id);
            $balance = Crypt::decryptString($wallet->value);
            $balance_available = Crypt::decryptString($wallet->value_available);
            $balance = self::cutFloatNumber($balance,$crypto->percent);
            $balance_available = self::cutFloatNumber($balance_available,$crypto->percent);

            $wallet = WalletsCrypto::where('id_crypto',$crypto->id)->where('id_user', $user->id)->first();
            $b = self::cutFloatNumber($balance + $transaction->amount,$crypto->percent);
            $ba = self::cutFloatNumber($balance_available + $transaction->amount,$crypto->percent);
            $wallet->value = Crypt::encryptString($b);
            $wallet->value_available = Crypt::encryptString($ba);
            $wallet->value_num = $b;
            $wallet->value_available_num = $ba;
            $wallet->save();
            DB::commit();

            // send Notif
            $this->sendNotification($user->id,'confirmDepositCrypto',
                ['amount'=>$transaction->amount,'symbol'=>$crypto->symbol,'sms'=>[$transaction->amount,$crypto->symbol]]);
            self::logSave('trCrypto',['id_tr'=>$transaction->id,'id_user'=>$user->id,'amount'=>$transaction->amount],'تایید واریز رمزارز #'.$transaction->id);
            return array('status'=>true ,'msg'=>'واریز با موفقیت تایید و کیف پول کاربر به مقدار '.$transaction->amount.$crypto->symbol.' شارژ شد.');

        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
        }
    }

    function confirmWithdraw($TransactionCrypto,$user,$request){
        DB::beginTransaction();
        try {
            $crypto = Cryptocurrency::find($TransactionCrypto->id_crypto);
            $network = CryptoNetwork::find($TransactionCrypto->id_network);

            $data = json_decode($TransactionCrypto->data);

            if ($request->autoApi == 'binance') {
                try {
                    $response = $this->binance->api[0]->withdraw($crypto->symbol, $TransactionCrypto->destination, $TransactionCrypto->amount, $TransactionCrypto->destination_tag, false, false, $network->symbol);
                    if (isset($response) && isset($response['id'])) {
                        $data->withdrawViaAdminAuto = true;
                        $data->withdrawViaAdminAutoName = \Auth::user()->name;
                        $data->withdraw = ($response);
                        $data->exchange = 'binance';
                        $result = array('status' => true, 'msg' => 'برداشت به مبلغ ' . $TransactionCrypto->amount . ' با موفقیت به صورت اتوماتیک واریز و تایید شد.');
                    }
                } catch (\Exception $e) {
                    return array('status' => false, 'msg' => $e->getMessage() . $e->getFile() . ':' . $e->getLine());
                }
            }else if ($request->autoApi == 'kucoin') {
                $response = $this->kucoin->apiWithdrawal->apply([
                    'currency' => $crypto->symbol,
                    'address' => $TransactionCrypto->destination,
                    'amount' => $this->cutFloatNumber($TransactionCrypto->amount - $TransactionCrypto->withdraw_fee, $crypto->percent),
                    'chain' => strtolower($network->symbol),
                    'memo' => $TransactionCrypto->destination_tag
                ]);
                if (isset($response) && isset($response['withdrawalId'])) {
                    $data->withdrawViaAdminAuto = true;
                    $data->withdrawViaAdminAutoName = \Auth::user()->name;
                    $data->withdraw = ($response);
                    $data->exchange = 'kucoin';
                    $result = array('status' => true, 'msg' => 'برداشت به مبلغ ' . $TransactionCrypto->amount . ' با موفقیت به صورت اتوماتیک واریز و تایید شد.');
                }

            }else if ($request->autoApi == 'exonyx'){
                $response = \Http::withHeaders([
                    'accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'apiKey' => 'BEr4WdSiFIQoFxbcEJ86P22G7mfhcBQoQTq7KbS2zx1dfjRiNzs8PLytIeOtISCnsRQwOH2BbhJffluIM1WXxnZVtC4nLfFIB2ziOwKlSRsX8lSruobAAiEEmtkBrxFrzxewM9OllpQEncpDQRwva9Sji6vYsCycVF07jsSkAMPcA4cN5nCXhpmpBNpH79y',
                ])->post('https://api.exonyx.org/api/v1/reseller' . '/withdraw/create', [
                    "currency" => $crypto->symbol,
                    "network" => $network->symbol == 'TRX' ? 'TRC-20' : 'BEP20',
                    "amount" => $TransactionCrypto->amount - $TransactionCrypto->withdraw_fee,
                    "to_address" => $TransactionCrypto->destination,
                    "unique_param" => 'Arz8'.$TransactionCrypto->id
                ]);
                try {
                    $result = (object)$response->throw()->json();
                    $data->withdrawViaAdminAuto = true;
                    $data->withdrawViaAdminAutoName = \Auth::user()->name;
                    $data->withdraw = ($response);
                    $data->exchange = 'exonyx';
                    $result = array('status' => true, 'msg' => 'برداشت به مبلغ ' . $TransactionCrypto->amount . ' با موفقیت به صورت اتوماتیک واریز و تایید شد.');
                } catch (\Exception $e) {
                    return array('status' => false, 'msg' => $e->getMessage());
                }

            }else {
                $data->exchange = 'manual';
                $result = array('status' => true, 'msg' => 'برداشت به مبلغ ' . $TransactionCrypto->amount . ' با موفقیت به صورت دستی تایید شد.');
            }

            $TransactionCrypto->status = 'success';
            $TransactionCrypto->payment = $TransactionCrypto->amount;
            $TransactionCrypto->id_admin = \Auth::user()->id;
            $TransactionCrypto->data = json_encode($data);
            $TransactionCrypto->save();

            DB::commit();
            // send Notif
            $this->sendNotification($user->id,'confirmWithdrawCrypto',
                ['amount'=>$TransactionCrypto->amount,'symbol'=>$crypto->symbol,'sms'=>[$TransactionCrypto->amount,$crypto->symbol]]);

            self::logSave('trCrypto',['id_tr'=>$TransactionCrypto->id,'id_user'=>$user->id,'amount'=>$TransactionCrypto->amount],
                'تایید برداشت رمزارز #'.$TransactionCrypto->id,$request->ip());

            return $result;

        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().$e->getFile().':'.$e->getLine());
        }
    }


    function rejectCrypto(Request $request){

        $transaction = TransactionCrypto::where('id',$request->id)->where('status','pending')->whereNull('payment')->first();
        $user = User::find($transaction->id_user);
        $data = json_decode($transaction->data);
        DB::beginTransaction();
        try {
            $crypto = Cryptocurrency::find($transaction->id_crypto);
            $transaction->status = 'reject';
            //$transaction->payment = $transaction->amount;
            $transaction->id_admin = \Auth::user()->id;
            $data->reason = $request->reason;
            $transaction->data = json_encode($data);
            $transaction->save();
            if($transaction->type == 'deposit') {
                DB::commit();
                // send Notif
                $this->sendNotification($user->id,'rejectCryptoDeposit',
                    ['amount'=>$transaction->amount,'symbol'=>$crypto->symbol,'sms'=>[$transaction->amount,$crypto->symbol]]);

                self::logSave('trCrypto',['id_tr'=>$transaction->id,'id_user'=>$user->id,'amount'=>$transaction->amount],'ریجکت واریز رمزارز #'.$transaction->id);

                return array('status'=>true ,'msg'=>'تراکنش با موفقیت رد شد.');
            }else{
                //get Balance
                $wallet = WalletsCrypto::where('id_crypto',$crypto->id)->where('id_user',$user->id)->first();
                if (!isset($wallet))
                    $wallet = $this->createWallet($crypto->id,$user->id);
                $balance = Crypt::decryptString($wallet->value);
                $balance_available = Crypt::decryptString($wallet->value_available);
                $balance = self::cutFloatNumber($balance,$crypto->percent);
                $balance_available = self::cutFloatNumber($balance_available,$crypto->percent);

                $wallet = WalletsCrypto::where('id_crypto',$crypto->id)->where('id_user', $user->id)->first();
                $b = self::cutFloatNumber($balance + $transaction->amount,$crypto->percent);
                $ba = self::cutFloatNumber($balance_available + $transaction->amount,$crypto->percent);
                $wallet->value = Crypt::encryptString($b);
                $wallet->value_available = Crypt::encryptString($ba);
                $wallet->value_num = $b;
                $wallet->value_available_num = $ba;
                $wallet->save();
                DB::commit();

                // send Notif
                $this->sendNotification($user->id,'rejectWithdrawDeposit',
                    ['amount'=>$transaction->amount,'symbol'=>$crypto->symbol,'sms'=>[$transaction->amount,$crypto->symbol]]);
                self::logSave('trCrypto',['id_tr'=>$transaction->id,'id_user'=>$user->id,'amount'=>$transaction->amount],
                    'ریجکت برداشت رمزارز #'.$transaction->id,$request->ip());
                return array('status'=>true ,'msg'=>'تراکنش با موفقیت رد شد و مبلغ به کیف پول کاربر برگردانده شد.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
        }
    }


    function removeCrypto(Request $request){
        if(\Auth::user()->role =='admin'):
            DB::beginTransaction();
            try {
                $transaction = TransactionCrypto::where('id',$request->id)->delete();
                DB::commit();
                return array('status'=>true ,'msg'=>'تراکنش با موفقیت حذف شد.');
            } catch (\Exception $e) {
                DB::rollback();
                return array('status' => false, 'msg' => $e->getMessage().':'.$e->getLine());
            }
        endif;
    }
}
