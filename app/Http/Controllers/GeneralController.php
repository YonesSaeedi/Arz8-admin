<?php namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cryptocurrency;
use App\Models\Internalcurrency;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TransactionCrypto;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\UserCardBank;
use Illuminate\Http\Request;
use Morilog\Jalali;
use Illuminate\Support\Facades\Cache;
use DB;

class GeneralController extends Controller
{
    function info(Request $request){
        // Cache::put('user_id_'.$user->id,$user->name);
        //Cache::forget('user_id_' . 1);
        //Cache::flush();

        $result = (object)array();
        // count market
        $result->market = (object)array();

        $result->crypto = Cache::remember('ad_general_crypto', now()->addDay(), function () {
            return Cryptocurrency::select('color','symbol','icon'
                   ,DB::raw("
                        JSON_OBJECT(
                            'fa',
                            CASE
                                WHEN JSON_EXTRACT(locale, '$.fa.name') IS NOT NULL
                                THEN JSON_UNQUOTE(JSON_EXTRACT(locale, '$.fa.name'))
                                ELSE name
                            END,
                            'en', name
                        ) as name_locale
                    ")
                    ,DB::raw("
                        CASE
                            WHEN JSON_CONTAINS(settings, JSON_OBJECT('font', true))
                            THEN true
                            ELSE false
                        END as has_font
                    ")
            )->get();
        });

        $result->pending = self::getPending();

        $locales = Cache::remember('ad_general_locales', now()->addDays(100), function () {
            return  \App\Models\Settings::where('name','locale')->first()->value;
        });
        $result->locales = json_decode($locales);

        $result->gatewayslist = Cache::remember('ad_general_gatewayslist', now()->addDays(7), function () {
            return  \App\Models\PaymentGateway\PaymentGateway::select('name','withdraw')->get();
        });
        $result->bajeAccount = Cache::remember('ad_general_baje_account', now()->addDays(7), function () {
            $baje = \App\Models\PaymentGateway\PaymentGateway::select('data')->where('name','baje')->first()->data;
            return  json_decode($baje)->account;
        });

        $result->digital_icon = ['PMV'=>'perfectmoney.svg','PM'=>'perfectmoney.svg','PSV'=>'psvoucher.svg','PY'=>'payeer.svg','UUSD'=>'uusd.png'];

        return response()->json($result);
    }

    function getPending(){
        $result = (object)array();
        $result->count_sum = 0;
        $count = TransactionCrypto::where('status','pending')->count();
        $single = TransactionCrypto::where('status','pending')->first();
        $crypto = $single ? Cryptocurrency::find($single->id_crypto) : null;
        $result->count_sum += $count;
        $result->transaction_crypto = array(
            'count' => $count,
            'time'  => $single ? Jalali\Jalalian::forge($single->created_at)->ago() : null,
            'msg'   => $single ? ($single->type=='deposit'?'واریز':'برداشت').' '.($single->amount.$crypto->symbol) : null,
            'sort'  => $single ? strtotime($single->created_at) : null,
        );


        $count = TransactionInternal::where('status','pending')->count();
        $single = TransactionInternal::where('status','pending')->first();
        $result->count_sum += $count;
        $result->transaction_internal = array(
            'count' => $count,
            'time'  => $single ? Jalali\Jalalian::forge($single->created_at)->ago() : null,
            'msg'   => $single ? ($single->type=='deposit'?'واریز':'برداشت').' '.(number_format($single->amount).' '.__('Toman')) : null,
            'sort'  => $single ? strtotime($single->created_at) : null,
        );


        $count = UserCardBank::where('status','pending')->count();
        $single = UserCardBank::where('status','pending')->first();
        $result->count_sum += $count;
        $result->cardbank = array(
            'count' => $count,
            'time'  => $single ? Jalali\Jalalian::forge($single->created_at)->ago() : null,
            'msg'   => $single ? $single->bank_name.' '.$single->card_number : null,
            'sort'  => $single ? strtotime($single->created_at) : null,
        );

        $query = User::query();
        $query->whereRaw('JSON_CONTAINS(identification_img, ?)', [json_encode(array('status' => 'pending'))])
                        ->orWhereRaw('JSON_CONTAINS(auth_img, ?)', [json_encode(array('status' => 'pending'))])
                        ->orWhereRaw('JSON_CONTAINS(address, ?)', [json_encode(array('status' => 'pending'))]);
        $count = $query->count();
        $single = $query->first();
        $result->count_sum += $count;
        $result->users = array(
            'count' => $count,
            'time'  => $single ? Jalali\Jalalian::forge($single->updated_at)->ago() : null,
            'msg'   => $single ? $single->name.' '.$single->family : null,
            'sort'  => $single ? strtotime($single->created_at) : null,
            'avatar'  => $single ? json_decode($single->info ?? '{}')->account_profile_img??null : null,
        );

        $count = Ticket::where('status','awaiting answer')->count();
        $single = Ticket::where('status','awaiting answer')->first();
        $singleMsg = $single ? TicketMessage::where('id_ticket',$single->id)->orderBy('id','desc')->first() :null;
        $result->count_sum += $count;
        $result->tickets = array(
            'count' => $count,
            'time'  => $single ? Jalali\Jalalian::forge($singleMsg->created_at)->ago() : null,
            'msg'   => $single ? $single->title: null,
            'sort'  => $single ? strtotime($single->updated_at) : null,
        );
        return $result;
    }

    function invoice(Request $request){
        try{
            $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStart);
        }catch(\Exception $e){
            return response()->json(['status'=>false,'msg'=>'فرمت تاریخ شروع درست نیست!'],400);
        }

        try{
            $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateStop);
        }catch(\Exception $e){
            return response()->json(['status'=>false,'msg'=>'فرمت تاریخ پایان درست نیست!'],400);
        }

        if($dateStart < date('Y-m-d', strtotime('- 7 day')) )
            return response()->json(['status'=>false,'msg'=>'تاریخ شروع حداکثر 7 روز قبل باید باشد!'],400);


        $result = (object)[];
        $result->status = true;
        $result->msg = "success";

        $query = TransactionInternal::query();
        $query->where('created_at','>=', $dateStart);
        $query->where('created_at','>', $dateStart);
        $query->where(['status'=>'success','type'=>'deposit','payment_gateway'=>'zibal']);
        $query->where('description','!=', 'Deposit with ID');
        $query->select('id','created_at','amount');
        $transactions = $query->get();
        foreach ($transactions as $item){
            $item->amount = $item->amount * 10;
            $item->created = \Morilog\Jalali\CalendarUtils::strftime('Y/m/d - H:i', strtotime($item->created_at));
            unset($item->created_at);
        }

        $result->transactions = $transactions;
        return $result;
    }
}
