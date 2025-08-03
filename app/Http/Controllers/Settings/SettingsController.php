<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Morilog\Jalali;

class SettingsController extends Controller
{

    private $hide_param;
    public function __construct()
    {
        $path = public_path().'/images/';
        $this->path = str_replace(env('PATH_ADMIN_PANEL'),env('PATH_PANEL'),$path);
        $this->path = str_replace('sorg.ir-v3/public','app/api',$this->path);

        $this->hide_param = [
            'utopia'=>['api_key'],'psvouchers'=>['apikey','seckey'],
            'perfectmoney'=>['account_id','password','payer_account'],
            'cardinfo' => ['token'],
            'finnotech' => ['password','client_id'],
            'kucoin' => ['key','passphrase','secret'],
            'coinex' => ['ACCESS_ID','SECRET_KEY'],
            'binance' => ['apikey','seckey'],
            'kucoin_new' => ['key','passphrase','secret'],
            'coinex_new' => ['ACCESS_ID','SECRET_KEY'],
        ];
    }

    function getSettings(){
        $result = (object)array();
        $settings = Settings::whereNotIn('name',['birthplace','mines_wallets','ticketPattern','last10MinPrice'])->get();
        $returnSettings = [];
        foreach($settings as $setting){
            if($setting->hash == 1){
                try{
                    $setting->value =  \Crypt::decryptString($setting->value);
                }catch (\Exception $e){}
            }

            if($setting->json == 1){
                $setting->value = json_decode($setting->value);
            }
            $returnSettings[$setting->name] = $setting->value;

            if(isset($this->hide_param[$setting->name])){
                foreach ($returnSettings[$setting->name] as $key => $item){
                    if(is_scalar($item) && in_array($key,$this->hide_param[$setting->name]))
                        $returnSettings[$setting->name]->{$key} =  $this->hideMiddle($item);
                    else if(!is_scalar($item)){
                        foreach ($item as $key2 => $item2){
                            if(in_array($key2,$this->hide_param[$setting->name]))
                                $returnSettings[$setting->name][$key]->{$key2} =  $this->hideMiddle($item2);
                        }
                    }
                }
            }


        }

        $result->settings = $returnSettings;
        $paymentGateway =  PaymentGateway::all();
        $result->paymentGateway = [];
        foreach ($paymentGateway as $gateway){
            $gateway->data = json_decode($gateway->data);
            $result->paymentGateway[$gateway->name] =  $gateway;
        }
        return (array)$result;
    }


    function editSettings(Request $request){
        foreach($request->all() as $key => $value){
            $setting = Settings::where('name',$key)->first();
            if(isset($setting)){
                if(isset($this->hide_param[$key])){
                    $value_before = $setting->value;
                    if($setting->hash == 1)
                        $value_before = \Crypt::decryptString($value_before);
                    if($setting->json == 1)
                        $value_before = json_decode($value_before);

                    foreach ($this->hide_param[$key] as $item){
                        if(isset($value[$item]) && substr_count($value[$item], "*")>3)
                            $value[$item] = $value_before->{$item};
                        elseif (isset($value[0][$item]))
                            foreach ($value as $k => $i){
                                if(isset($value[$k][$item]) && substr_count($value[$k][$item], "*")>3)
                                    $value[$k][$item] = $value_before[$k]->{$item};
                            }

                    }
                }

                if($setting->json == 1)
                    $value = json_encode($value);
                if($setting->hash == 1)
                    $value = \Crypt::encryptString($value);
                $setting->value = $value;
                $setting->save();
            }else{
                $setting = new Settings;
                $setting->name = $key;
                $setting->value = $value;
                $setting->save();
            }
        }
        self::logSave('settings',$request->all(), 'تغییر در تنظیمات',$request->ip());

        $this->cacheClear('settings');
        return array('status' => true, 'msg' => 'تغییرات با موفقیت ثبت شد.');
    }

    function gateway(Request $request){
        PaymentGateway::query()->update(['select'=>0]);
        foreach($request->gateways as $key => $value){
            $gateway = PaymentGateway::where('name',$key)->first();
            $gateway->token = $value['token'];
            $gateway->status = $value['status'];
            $gateway->withdraw = $value['withdraw'];
            if($request->defaultGateway == $key)
                $gateway->select = 1;
            $gateway->save();
        }
        Cache::forget('ad_general_gatewayslist');
        self::logSave('settings',$request->all(), 'تغییر در تنظیمات درگاه پرداخت',$request->ip());
        return array('status' => true, 'msg' => 'تغییرات با موفقیت ثبت شد.');
    }

    function autoDeposit(Request $request){
        $gateway = PaymentGateway::where('name',$request->formData['AutomaticDeposit']['GatewayWithdraw'])->first();
        $gateway->data = json_encode($request->gateways[$gateway->name]['data']);
        $gateway->save();

        $setting = Settings::where('name','AutomaticDeposit')->first();
        $setting->value = json_encode($request->formData['AutomaticDeposit']);
        $setting->save();
        self::logSave('settings',$request->all(), 'تغییر در تنظیمات واریز اتوماتیک',$request->ip());
        return array('status' => true, 'msg' => 'تغییرات با موفقیت ثبت شد.');
    }

    function banner(Request $request){
        $banner = json_decode($request->banner);
        $array_file_name = [];
        foreach ($banner as $key1=>$ban){
            $ban->status = ($ban->status ==="true" || $ban->status === true) ? true :false;
            foreach ($ban->banner as $key2=>$img){
                $locale = [];
                foreach ($img->locale as $lang)
                    array_push($locale,$lang->value);
                $img->locale = $locale;
                if(!isset($img->imgUrl)):
                    $file = 'imgUrl'.$key2.$key1;
                    $name = rand(0,100).$request->file($file)->getClientOriginalName();
                    if($request->hasFile($file)){
                        $request->file($file)->move($this->path.'banner',$name);
                    }
                    $img->imgUrl = $name;
                endif;
                array_push($array_file_name,$img->imgUrl);
            }
        }

        //remove other file
        if ($handle = opendir($this->path.'banner')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if(!in_array($entry,$array_file_name))
                        \File::delete($this->path.'banner/'.$entry);
                }
            }
            closedir($handle);
        }

        $setting = Settings::where('name','banner')->first();
        $setting->value = json_encode($banner);
        $setting->save();
        self::logSave('settings',$request->all(), 'تغییر در تنظیمات بننر',$request->ip());
        $this->cacheClear('settings');
        return array('status' => true, 'msg' => 'تغییرات با موفقیت ثبت شد.');
    }

    function imageViewBanner(Request $request){
        $img = str_replace(' ','%20',$request->img);
        $image = file_get_contents(env('APP_URL_FRONT').'api/images/banner/'.$img);
        header('Content-type: image/jpeg;');
        header("Content-Length: " . strlen($image));
        echo $image;
    }

    function hideMiddle($text) {
        $maskedText = "";
        if (strlen($text) > 2) {
            $middleLength = floor(strlen($text) / 1.5);
            $startPos = round((strlen($text) - $middleLength) / 2);
            $maskedText = substr($text, 0, $startPos) . str_repeat("*", $middleLength) . substr($text, $startPos + $middleLength);
        }
        return $maskedText;
    }


    function stories(Request $request){
        $stories = json_decode($request->stories);
        $array_file_name = [];
        $request->status = ($request->status ==="true" || $request->status === true) ? true :false;
        foreach ($stories->list as $key=>$item){
            if(!isset($item->imgUrl)):
                $file = 'imgUrl'.$key;
                $name = rand(0,100).$request->file($file)->getClientOriginalName();
                $name = str_replace(' ','_',$name);
                if($request->hasFile($file)){
                    $request->file($file)->move($this->path.'stories',$name);
                }
                $item->imgUrl = $name;
            endif;

            $item->started_at = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $item->started_at);
            $item->expired_at = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $item->expired_at);

            if(!isset($item->id))
                $item->id = (int)preg_replace('/\D/', '', uniqid());

            array_push($array_file_name,$item->imgUrl);
        }


        //remove other file
        if ($handle = opendir($this->path.'stories')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if(!in_array($entry,$array_file_name))
                        \File::delete($this->path.'stories/'.$entry);
                }
            }
            closedir($handle);
        }

        $setting = Settings::where('name','stories')->first();
        $setting->value = json_encode($stories);
        $setting->save();
        self::logSave('settings',$request->all(), 'تغییر در تنظیمات استوری',$request->ip());
        $this->cacheClear('settings');
        return array('status' => true, 'msg' => 'تغییرات با موفقیت ثبت شد.');
    }

    function imageViewStories(Request $request){
        $img = str_replace(' ','%20',$request->img);
        $image = file_get_contents(env('APP_URL_FRONT').'api/images/stories/'.$img);
        header('Content-type: image/jpeg;');
        header("Content-Length: " . strlen($image));
        echo $image;
    }
}
