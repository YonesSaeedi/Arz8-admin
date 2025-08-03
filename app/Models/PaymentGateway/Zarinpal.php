<?php

namespace App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use SoapClient;
use App\functions;
use App\Models\PaymentGateway\PaymentGateway;
use Auth;

class Zarinpal
{
    function GotoPayment($amount,$desc,$callback){
        $zarinpal = PaymentGateway::where('name','zarinpal')->first();
        $parameters = array(
            'merchant_id' => $zarinpal->token,
            'amount' => $amount * 10,
            'description' => $desc,
            'callback_url' => $callback,
        );
        $response = Http::withHeaders(['Content-Type'=>'application/json'])->post('https://api.zarinpal.com/pg/v4/payment/request.json', $parameters);
        $result = (object)$response->throw()->json();
        if($result->Status == 100 && isset($result->Authority))
            return array('status'=>true, 'msg'=>'','url'=>'https://www.zarinpal.com/pg/StartPay/'.$result->Authority.'/ZarinGate');
        else
            return array('status'=>false, 'msg'=> json_encode($result) , 'url'=>null);
    }


    function Verify($request,$amount){
        $zarinpal = PaymentGateway::where('name_as','zarinpal')->first();

        $MerchantID = $zarinpal->token;
        $Authority = $request->Authority;

        $client = new SoapClient('https://www.zarinpal.com/pg/services/WebGate/wsdl', ['encoding' => 'UTF-8']);

        $result = $client->PaymentVerificationWithExtra(
            [
                'MerchantID' => $MerchantID,
                'Authority' => $Authority,
                'Amount' => $amount,
            ]
        );

        return $result;

    }
}
