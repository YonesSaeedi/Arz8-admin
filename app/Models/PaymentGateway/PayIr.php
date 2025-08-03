<?php

namespace App\Models\PaymentGateway;
use App\functions;
use App\PaymentGateway;
use Auth;

class PayIr
{
    function GotoPayment($amount,$desc,$callback){
        $functions = new functions;
        $payIr = PaymentGateway::where('name_as','pay.ir')->first();
        $params = array(
            'api' => $payIr->token,
            'amount' => $amount*10,
            'name' => Auth::user()->id,
            //'mobile' => Auth::user()->mobile,
            'factorNumber' => time(),
            'description' => $desc,
            'redirect' => $callback,
        );
        $authorization = null;
        $result = $functions->Curl('https://pay.ir/pg/send',$params,$authorization);
        if($result->status) {
            $go = "https://pay.ir/pg/$result->token";
            header("Location: $go");
            echo '<script>
                    window.location.replace("'. $go.'");
            </script>';
        } else {
            dd($result);
        }
    }


    function Verify($request){
        $functions = new functions;
        $payIr = PaymentGateway::where('name_as','pay.ir')->first();
        $params = array(
            'api' => $payIr->token,
            'token' => $request->token,
        );
        $result = $functions->Curl('https://pay.ir/pg/verify',$params,null);
        return $result;
    }
}
