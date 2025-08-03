<?php

namespace App\Http\Controllers\Exchange;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Support\Facades\Crypt as Crypt;
use Illuminate\Support\Facades\Http;

class CoinexApi extends Controller {
    public $baseURL = "https://api.coinex.com/v1";
    public $baseURLV2 = "https://api.coinex.com";
    public $header = ['Content-Type'=>'application/json'];
    private $ACCESS_ID;
    private $SECRET_KEY;

    public $api = [];

    public function __construct($access_id = null, $secret_key = null, $initializeApis = true) {
        // If access_id and secret_key are provided, set them
        if ($access_id && $secret_key) {
            $this->ACCESS_ID = $access_id;
            $this->SECRET_KEY = $secret_key;
        }

        // Only initialize API instances when $initializeApis is true
        if ($initializeApis) {
            $this->createApiInstances();
        }
    }

    // Method to create multiple API objects
    private function createApiInstances() {
        $coinex = json_decode(Crypt::decryptString(Settings::where('name', 'coinex_new')->first()['value']));
        foreach ($coinex as $item) {
            // Create API instance without initializing again (to avoid infinite loop)
            $this->api[] = new self($item->ACCESS_ID, $item->SECRET_KEY, false);
        }
    }

    private function buildHeaders($method, $path, $params = [], $body = '')
    {
        // زمان فعلی به میلی‌ثانیه
        $timestamp = round(microtime(true) * 1000);

        // ساخت رشته امضا
        $queryString = http_build_query($params);
        $stringToSign = $method . $path;
        if (!empty($queryString)) {
            $stringToSign .= '?' . $queryString;
        }
        $stringToSign .= $body . $timestamp;

        // تولید امضا با HMAC-SHA256
        $signature = hash_hmac('sha256', $stringToSign, $this->SECRET_KEY);

        // ساخت هدرها
        $headers = [
            'Content-Type' => 'application/json',
            'X-COINEX-KEY' => $this->ACCESS_ID,
            'X-COINEX-SIGN' => $signature,
            'X-COINEX-TIMESTAMP' => $timestamp,
        ];

        return $headers;
    }

    private function sendRequest($method, $endpoint, $params = [], $body = [])
    {
        // تبدیل بدنه به JSON در صورت نیاز
        $bodyJson = !empty($body) ? json_encode($body) : (!empty($params) ? json_encode($params) : "");

        // ساخت هدرها
        $headers = $this->buildHeaders($method, $endpoint, [], $bodyJson);

        // ارسال درخواست با توجه به متد
        $url = $this->baseURLV2 . $endpoint;
        return match (strtoupper($method)) {
            'GET' => Http::withHeaders($headers)->get($url, $params),
            'POST' => Http::withHeaders($headers)->post($url, $body),
            default => throw new \Exception("Invalid HTTP method"),
        };
    }

    function allPrice(){
        $response = Http::withHeaders($this->header)->get($this->baseURL."/market/ticker/all");
        $result = (object)$response->json();
        if($result->code == 0)
            return $result->data['ticker'];
        else
            return ['status'=> false ,'message'=> $result->message];
    }

    function price($market){
        $response = Http::withHeaders($this->header)->get($this->baseURL."/market/ticker?market=".$market);
        $result = (object)$response->json();
        if($result->code == 0){
            return $result->data['ticker']['last'];
        }
        else
            return ['status'=> false ,'message'=> $result->message];
    }

    function marketTrade($type,$marketSymbol,$amount,$amountBySymbol) {
        $endpoint = '/v2/spot/order';
        $method = 'POST';
        $body = [
            'market' => $marketSymbol,
            'market_type' => 'SPOT',
            'side' => strtolower($type),
            'type' => 'market',
            'ccy' => $amountBySymbol,
            'amount' => $amount,
        ];
        $response = $this->sendRequest($method, $endpoint, [], $body);
        $result = (object) $response->json();
        if($result->code == 0){
            $result->status = true;
            return $result;
        }else
            return (object)['status'=> false ,'message'=> $result->message,'result'=> $result];
    }

    function marketInfo() {
        $response = Http::withHeaders($this->header)->get($this->baseURLV2."/v2/spot/market");
        $result = (object)$response->json();
        if($result->code == 0){

            $restructured = [];
            foreach ($result->data as $item) {
                $symbol = $item['market'];
                $restructured[$symbol] = $item;
            }
            return $restructured;
        }
        else
            return ['status'=> false ,'message'=> $result->message];
    }

    function balance() {
        $method = 'GET';
        $endpoint = '/v2/assets/spot/balance';
        $params = []; // اگر نیاز به پارامتر خاصی است
        $response = $this->sendRequest($method, $endpoint, $params);
        $result = (object) $response->json();

        if($result->code == 0){
            $result->status = true;
            $transformedResponse = [];
            foreach ($result->data as $item) {
                $key = $item['ccy'];
                unset($item['ccy']); // حذف کلید اصلی ccy برای جلوگیری از تکرار
                $transformedResponse[$key] = $item; // اضافه کردن ورودی با کلید جدید
            }
            $result->data = $transformedResponse;
            return $result;
        }else
            return ['status'=> false ,'message'=> $result->message];

    }

    function createAuthorization($params) {
        $text = http_build_query($params). "&secret_key=" . $this->SECRET_KEY;
        $text = str_replace('%3A',':',$text);
        return strtoupper(md5($text));
    }

    function depositAddress($symbol,$network = null){
        $params = [
            'access_id'=> $this->ACCESS_ID,
            'smart_contract_name'=> $network,
            'tonce'=> round(microtime(true)*1000)

        ];
        $headers = $this->header;
        $headers['authorization'] = self::createAuthorization($params);
        $response = Http::withHeaders($headers)->get($this->baseURL."/balance/deposit/address/".$symbol."?". http_build_query($params));
        $result = (object)$response->json();
        if($result->code == 0) {
            $address = explode(':',$result->data['coin_address']);
            $result->data['coin_address'] = $address[0];
            $result->data['coin_address_tag'] = isset($address[1])?$address[1]:null;
            return $result->data;
        }else
            return ['status'=> false ,'message'=> $result->message];
    }

    function depositHistory($limit = 100,$page = 1,$status = null,$symbol = null,$txid = null){
        $params = [
            'access_id'=> $this->ACCESS_ID,
            'coin_type'=> $symbol,
            'limit'=> $limit,
            'page'=> $page,
            'status'=> $status,
            'tonce'=> round(microtime(true)*1000),
            'tx_id'=> $txid,
        ];
        $headers = $this->header;
        $headers['authorization'] = self::createAuthorization($params);
        $response = Http::withHeaders($headers)->get($this->baseURL."/balance/coin/deposit?". http_build_query($params));
        $result = (object)$response->json();
        if($result->code == 0) {
            return $result->data;
        }else
            return ['status'=> false ,'message'=> $result->message];
    }

    function withdraw($symbol,$amount,$address,$network = null,$tag_address = null) {
        $coin_address = isset($tag_address) ? $address.":".$tag_address : $address;
        $params = [
            'access_id'=> $this->ACCESS_ID,
            'actual_amount'=> $amount,
            'coin_address'=> $coin_address,
            'coin_type'=> $symbol,
            'smart_contract_name'=> $network,
            'tonce'=> round(microtime(true)*1000),
            'transfer_method'=> 'onchain',
        ];
        $headers = $this->header;
        $headers['authorization'] = self::createAuthorization($params);
        $response = Http::withHeaders($headers)->post($this->baseURL."/balance/coin/withdraw",$params);
        $result = (object)$response->json();
        if($result->code == 0){
            $result->status = true;
            return $result;
        }else
            return ['status'=> false ,'message'=> $result->message];
    }


}
