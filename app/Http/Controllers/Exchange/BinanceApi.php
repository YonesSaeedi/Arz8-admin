<?php
namespace App\Http\Controllers\Exchange;
use App\Http\Controllers\BinanceController;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Support\Facades\Crypt as Crypt;

class BinanceApi extends Controller
{
    public $api=[];
    public function __construct()
    {
        $binance = json_decode(Crypt::decryptString(Settings::where('name', 'binance')->first()['value']));
        foreach ($binance as $key=>$item){
            $this->api[$key] = new BinanceController($item->apikey, $item->seckey);
        }
    }

    function getProxyConf(){
        $proxy = json_decode(Crypt::decryptString(Settings::where('name','proxy')->first()->value));
        $proxys = array();
        if($proxy->status == 'true'){
            $list_proxy = $proxy->list_proxy;
            foreach ($list_proxy as $proxy){
                if(isset($proxy->ip)&& $proxy->status == 'true')
                    array_push($proxys,$proxy);
            }
            $proxyConf = $proxys[rand(0,count($proxys)-1)]??null;
            if(isset($proxyConf)):
                $proxyConf = array(
                    'proto' => 'http',
                    'address' => $proxyConf->ip,
                    'port' => $proxyConf->port,
                    'user' => $proxyConf->username,
                    'pass' => $proxyConf->password
                );
                return array('status'=>true ,'proxy'=>$proxyConf);
            else:
                return array('status'=>false);
            endif;
        }
        else
            return array('status'=>false);
    }


}
