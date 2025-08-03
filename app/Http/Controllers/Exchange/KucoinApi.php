<?php

namespace App\Http\Controllers\Exchange;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Support\Facades\Crypt as Crypt;
use KuCoin\SDK\Auth;
use KuCoin\SDK\KuCoinApi as KuCoin;
use KuCoin\SDK\PrivateApi\Account;
use KuCoin\SDK\PrivateApi\Deposit;
use KuCoin\SDK\PrivateApi\Order;
use KuCoin\SDK\PrivateApi\Withdrawal;
use KuCoin\SDK\PublicApi\Currency;
use KuCoin\SDK\PublicApi\Symbol;

class KucoinApi extends Controller {
    public $baseURL = "https://api.kucoin.com";
    public $header = ['Content-Type'=>'application/json'];
    private $authKucoin;
    public $apiAccount;
    public $apiDeposit;
    public $apiWithdrawal;
    public $apiTrade;
    public $currency;

    public $api=[];
    public $apiTrade_list = [];
    public $apiAccount_list = [];

    function __construct(){
        $kucoin = json_decode(Crypt::decryptString(Settings::where('name', 'kucoin')->first()['value']));
        KuCoin::setBaseUri($this->baseURL);
        KuCoin::setDebugMode(true);
        $this->authKucoin = new Auth($kucoin->key, $kucoin->secret, $kucoin->passphrase, Auth::API_KEY_VERSION_V2);
        $this->apiAccount = new Account($this->authKucoin);
        $this->apiDeposit = new Deposit($this->authKucoin);
        $this->apiWithdrawal = new Withdrawal($this->authKucoin);
        $this->apiTrade = new Order($this->authKucoin);
        $this->currency = new Currency();
        $this->symbol = new Symbol();

        $binance = json_decode(Crypt::decryptString(Settings::where('name', 'kucoin_new')->first()['value']));
        foreach ($binance as $key=>$item){
            $this->api[$key] = new Auth($item->key, $item->secret, $item->passphrase, Auth::API_KEY_VERSION_V2);
            $this->apiTrade_list[$key] = new Order($this->api[$key]);
            $this->apiAccount_list[$key] = new Account($this->api[$key]);
        }
    }
}
