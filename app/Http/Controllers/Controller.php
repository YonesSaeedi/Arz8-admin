<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    function convertDate($date,$format){
        $Locale = app()->getLocale();
        if($Locale == 'fa')
            return \Morilog\Jalali\CalendarUtils::strftime($format, strtotime($date));
        else{
            return date(str_replace('/','-',$format), strtotime($date));
        }
    }

    function imageView(\Illuminate\Http\Request $request){
        $storagePath = \Crypt::decryptString($request->hash);
        //$storagePath = base_path('').env('PATH_PANEL').'/storage/'.$storagePath;
        //$storagePath = str_replace(env('PATH_ADMIN_PANEL'),'',$storagePath);
        $storagePath = storage_path().'/'.$storagePath;
        $storagePath = str_replace(env('PATH_ADMIN_PANEL'),env('PATH_PANEL'),$storagePath);
        //dd($storagePath);
        //return \Image::make($storagePath)->response('jpg');
        return response(\File::get($storagePath), 200)->header('Content-Type', 'image/jpg');
    }
    function imageView2(\Illuminate\Http\Request $request){
        $storagePath = \Crypt::decryptString($request->hash);
        //$storagePath = base_path('').env('PATH_PANEL').'/storage/'.$storagePath;
        //$storagePath = str_replace(env('PATH_ADMIN_PANEL'),'',$storagePath);
        $storagePath = storage_path().'/'.$storagePath;
        $storagePath = str_replace(env('PATH_ADMIN_PANEL'),'saeedi',$storagePath);
        //dd($storagePath);
        //return \Image::make($storagePath)->response('jpg');
        return response(\File::get($storagePath), 200)->header('Content-Type', 'image/jpg');
    }

    function storagePath(){
        //$storagePath = base_path('').env('PATH_PANEL').'/storage/';
        //$storagePath = str_replace(env('PATH_ADMIN_PANEL'),'',$storagePath);
        $storagePath = storage_path().'/';
        $storagePath = str_replace(env('PATH_ADMIN_PANEL'),env('PATH_PANEL'),$storagePath);
        return $storagePath;
    }

    function cutFloatNumber($amount,$percent){
        $percent = str_pad(1,  $percent +1, '0', STR_PAD_RIGHT);
        $amount = floor($amount * $percent) / $percent;
        return $amount;
    }


    function logSave($key,$data,$msg,$ip = null){
        $log = new \App\Models\AdminLog;
        $log->id_admin = \Auth::user()->id;
        $log->key = $key;
        $log->data = \Crypt::encryptString(json_encode($data));
        $log->text = $msg;
        $log->ip = $ip;
        $log->save();
    }

    function sendNotification($id_user,$key,$data){
        $job = (new \App\Jobs\NotificationCenter($id_user,$key,$data))->delay(\Carbon\Carbon::now()->addSeconds(5));
        dispatch($job);
    }

    function cacheClear($key){
        switch ($key){
            case 'market':
                Cache::forget('general_markets');
                break;
            case 'crypto':
                Cache::forget('ad_general_crypto');
                Cache::forget('general_cryptocurrency');
                Cache::forget('general_new_coin');
                Cache::forget('dashboard_currencys');
                Cache::forget('dashboard_markets');
                Cache::forget('order_info_cryptocurrency');
                Cache::forget('order_info2_cryptocurrency');
                Cache::forget('landing_calculator');
                Cache::forget('la_general_cryptocurrency');
                break;
            case 'settings':
                Cache::forget('general_popup');
                Cache::forget('general_alert');
                Cache::forget('general_banner');
                Cache::forget('general_stories');
                Cache::forget('general_application');
                break;
        }
    }

    function createWallet($id_crypto,$id_user){
        $wallet = new \App\Models\WalletsCrypto();
        $wallet->id_user = $id_user;
        $wallet->id_crypto = $id_crypto;
        $wallet->value = Crypt::encryptString(0);
        $wallet->value_available = Crypt::encryptString(0);
        $wallet->save();
        return $wallet;
    }

    function createWalletInternal($id_internal,$id_user){
        $wallet = new \App\Models\WalletsInternal();
        $wallet->id_user = $id_user;
        $wallet->id_internal = $id_internal;
        $wallet->value = Crypt::encryptString(0);
        $wallet->value_available = Crypt::encryptString(0);
        $wallet->save();
        return $wallet;
    }

    function OTP($nameOtp,$codeReceive=null){
        $first = false;
        $data = json_decode(\Auth::user()->data) ?? (object)array();
        if (!isset($codeReceive)) {
            if (isset($data->{$nameOtp}) && isset($data->{$nameOtp}->randstring) && $data->{$nameOtp}->time + 60 >= time()) {
                $randstring = $data->{$nameOtp}->randstring;
            } else {
                $first = true;
                $data->{$nameOtp} = (object)array();
                $randstring = rand(100000, 999999);
                $data->{$nameOtp}->randstring = $randstring;
                $data->{$nameOtp}->time = time();
                $data->{$nameOtp}->type = isset(\Auth::user()->google2fa)?'google':'sms';
                \App\Models\AdminUser::where('id', \Auth::user()->id)->update(['data' => json_encode($data)]);
            }

            if($data->{$nameOtp}->time + 100 <= time()){
                return array('status' => false, 'msg' => __('successfully.'), 'otp' => true, 'msgOtp'=> __('Enter the verification code'));
            }

            if(\Auth::user()->sms2fa == 1){
                //send SMS
                try{
                    if ($first){
                        $api = new \Kavenegar\KavenegarApi(env('KavehnegarKey'));
                        $api->VerifyLookup(\Auth::user()->mobile,$randstring,null,null,'VerifyMobile');
                    }
                    return array('status' => false, 'msg' => __('successfully.'), 'otp' => true, 'msgOtp'=>__('SMS sent to your mobile'));
                } catch (\Exception $ex){
                    return array('status' => false, 'msg' => 'send sms error');
                }
            }else{
                return array('status' => false, 'msg' => __('successfully.'), 'otp' => true, 'msgOtp'=> __('Enter the code you see in the google authenticator app'));
            }
        } else {
            if (isset($data->{$nameOtp}) && isset($data->{$nameOtp}->randstring) && $data->{$nameOtp}->time + 300 >= time()
                && $data->{$nameOtp}->randstring == $codeReceive && ($data->{$nameOtp}->type=='sms'||$data->{$nameOtp}->type == 'email')) {
                return array('status' => true, 'msg' => 'ok');
            }elseif (isset($data->{$nameOtp}) && isset($codeReceive) && $data->{$nameOtp}->type == 'google'){
                $google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());
                $valid = $google2fa->verifyKey(Crypt::decryptString(\Auth::user()->google2fa), $codeReceive);
                if($valid==1){
                    return array('status' => true, 'msg' => 'ok');
                }else
                    return array('status' => false, 'msg' => __('The code entered is incorrect.'), 'otp' => true);
            }
            else
                return array('status' => false, 'msg' => __('The code entered is incorrect.'), 'otp' => true);
        }
    }

    function toLatin($string) {
        $persian_num = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $latin_num = range(0, 9);
        $string = str_replace($persian_num, $latin_num, $string);
        return $string;
    }

    function PercentageCalculation($result, $buy,$sell){
        if($buy >= 100 || $buy <= -100)
            $result['buy'] = $result['buy'] + ($buy);
        else
            $result['buy'] = round($result['buy'] + ($result['buy']/100 * $buy));

        if($sell >= 100 || $sell <= -100)
            $result['sell'] = $result['sell'] + ($sell);
        else
            $result['sell'] = round($result['sell'] + ($result['sell']/100 * $sell));

        return $result;
    }

    function applyFilter($query, $key, $value, $operator = '=') {
        if (isset($value)) {
            $query->where($key, $operator, $value);
        }
    }
}
