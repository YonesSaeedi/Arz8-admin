<?php

namespace App\Models\PaymentGateway;

use Auth;
use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Support\Facades\Http;

class Zibal{
    function zibalGatewayWithdraw($amount,$Iban,$id_last){
        $Zibal = PaymentGateway::where('name','zibal')->first();
        $dataZibal = json_decode($Zibal->data);
        $withdraw_token = $dataZibal->withdraw_token;
        $id_wallet = $dataZibal->withdraw_id_wallet;
        $model = $dataZibal->withdraw_model;
        $params = array(
            'amount' => $amount*10, //Rial
            'id' => $id_wallet,
            'bankAccount' => 'IR'.$Iban,
            'checkoutDelay' => ($model!='') ? $model: null,
            'uniqueCode' => $id_last,
        );
        $authorization = 'Bearer '. $withdraw_token;
        $response = Http::withHeaders(['Content-Type'=>'application/json','Authorization' => $authorization])
            ->post('https://api.zibal.ir/v1/wallet/checkout/plus', $params);
        try{
            $response = (object)$response->json();
        } catch(\Exception $e){
            return (object) array('status' => false,'msg' => ($e->getMessage()));
        }

        if ($response->result == 1) {
            $result = array('status' => true,'msg' => 'واریز به صورت پایا انجام شد','response'=>$response);
        }else{
            $result = array('status' => false,'msg' => ($response->message),'response'=>$response);
        }
        return (object)$result;
    }


    function Verify($trackId){
        $zibal = PaymentGateway::where('name','zibal')->first();
        $parameters = array(
            "merchant" => $zibal->token,
            "trackId" => $trackId,
        );
        $response = Http::withHeaders(['Content-Type'=>'application/json'])->post('https://gateway.zibal.ir/v1/verify', $parameters);
        $result = (object)$response->throw()->json();
        return $result;
    }

}
