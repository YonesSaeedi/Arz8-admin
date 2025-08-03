<?php

namespace App\Models\PaymentGateway;
use App\Models\PaymentGateway\PaymentGateway;
use Auth;
use Illuminate\Support\Facades\Http;

class IdPay
{
    function GotoPayment($amount,$desc,$callback){
        $idpay = PaymentGateway::where('name','idpay')->first();
        $params = array(
            'order_id' => time(),
            'amount' => $amount*10,
            'name' => Auth::user()->id,
            //'phone' => Auth::user()->mobile,
            //'mail' => Auth::user()->email,
            'desc' => $desc,
            'callback' => $callback,
        );
        $authorization = 'X-API-KEY: '.$idpay->token;
        $response = Http::withHeaders($authorization)->post('https://api.idpay.ir/v1.1/payment', $params);
        $result = $response->throw()->json();
        if(isset($result->link))
           return array('status'=>true, 'msg'=>'','url'=>$result->link);
        else
            return array('status'=>false, 'msg'=>json_encode($result), 'url'=>null);
    }


    function Verify($request){
        $functions = new functions;
        $idpay = PaymentGateway::where('name','idpay')->first();
        $params = array(
            'id' => $request->id,
            'order_id' => $request->order_id,
        );
        $authorization = 'X-API-KEY: ' . $idpay->token;
        return $functions->Curl('https://api.idpay.ir/v1.1/payment/verify', $params, $authorization);
    }
}
