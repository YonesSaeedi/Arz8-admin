<?php

namespace App\Models\PaymentGateway;
use App\functions;
use App\PaymentGateway;
use Auth;
use App\Orders;
use App\UserFinance;

class Payping
{
    function GotoPayment($amount,$desc,$callback,$id_order = null,$id_finance = null){
        $function = new \App\functions;
        $Payping = PaymentGateway::where('name_as','payping')->first();
        //$UserCardBank = \App\CardBank::select('card_number')->where('confirm','1')->where('id_user',Auth::user()->id)->get();

        $params = array(
            'amount' => round( $amount),
            'returnUrl' => $callback,
            'payerIdentity' => Auth::user()->mobile,
            'payerName' => Auth::user()->name .' '. Auth::user()->family,
            'description' =>  $desc,
        );
        $authorization = "Authorization: Bearer ".$Payping->token;
        $response = $function->Curl('https://api.payping.io/v2/pay',$params,$authorization);
        if(isset($response->code)){
            if (isset($id_order))
                $order = Orders::find($id_order);
            if(isset($id_finance))
                $order = UserFinance::find($id_finance);

            $data = (array) json_decode($order->data);
            $data['PaypingSend'] =  $response;
            $order->data = json_encode($data);
            $order->save();

            $startGateWayUrl = 'https://api.payping.io/v2/pay/gotoipg/'.$response->code;
            header('location: '.$startGateWayUrl);
            echo '<script>
                    window.location.replace("'. $startGateWayUrl.'");
            </script>';
            //exit();
        }else{
            print_r($response);
        }

    }


    function Verify($request,$id_order = null,$id_finance = null){
        $functions = new \App\functions;
        $Payping = PaymentGateway::where('name_as','payping')->first();
        if (isset($id_order))
            $order = Orders::find($id_order);
        if(isset($id_finance))
            $order = UserFinance::find($id_finance);

        $authorization = "Authorization: Bearer " . $Payping->token;
        $params = array(
            'amount' => round($order->amount),
            'refid' => $request->refid,
        );
        $response = $functions->Curl('https://api.payping.io/v2/pay/verify',$params,$authorization);
        $data = (object)json_decode($order->data);
        $data->PaypingVerify =  $response;
        $order->data = json_encode($data);
        $order->save();
        return $response;
    }


    function withdraw($amount,$Iban,$id_order=null,$id_finance = null){
        $PayPingToken = Settings::where('name','PayPingToken')->first()->value;
        $Data = array(
          "amount" => $amount,
          "description" => isset($id_order) ? 'سفارش شماره:' . $id_order : 'برداشت شماره:' . $id_finance,
          "shaba" => 'IR' . $Iban,
        );
        $authorization = "Authorization: Bearer " . $PayPingToken;
        $responce = $this->Curl('https://api.payping.ir/v1/withdraw/refund',$Data,$authorization,'POST');
        if ($responce->statusCodeHttp == 200 && isset($responce->code) )
          $result = array('status' => true,'message' => 'واریز به صورت شبا انجام شد','responce'=>$responce);
        else
          $result = array('status' => false,'message' => json_encode($responce));

        return (object)$result;
    }

}
