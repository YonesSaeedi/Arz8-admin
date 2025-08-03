<?php

namespace App\Models\PaymentGateway;

use Auth;
use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class Jibimo
{
    function withdraw($TraInternal ,$iban){
        $amount = $TraInternal->amount;
        $fee_withdraw = round($amount * 0.002);
        if($fee_withdraw > 5000)
            $fee_withdraw = 5000;
        elseif ($fee_withdraw < 1000)
            $fee_withdraw = 1000;
        $amount = $amount - $fee_withdraw;

        $user = User::find($TraInternal->id_user);
        $transfers = [];
        $transfer = [
            'uuid'=> $TraInternal->id,
            'row'=> $TraInternal->id,
            'name'=> $user->name,
            'family'=> $user->family,
            'amount'=> $amount*10,
            'iban'=> 'IR'.$iban,
            'mobile'=> $user->mobile,
        ];
        array_push($transfers,$transfer);

        $apikey_withdraw = self::getApiKeyWithdraw();
        $jibimo = PaymentGateway::where('name','jibimo')->first();
        $dataJibimo = json_decode($jibimo->data);
        $params = array(
            'data' => $transfers,
        );
        $response = Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization' => 'Bearer '. $apikey_withdraw
        ])->post('https://api.jibimo.com/v2/batch-pay/'. $dataJibimo->batch_id .'/items/create', $params);
        $response = (object)$response->json();
        if (!isset($response->errors) && count($response->warnings) == 0 && count($response->items) == 1 ) {
            $result = array('status' => true,'msg' => 'واریز به صورت پایا انجام شد','response'=>$response);
        }else{
            \Log::channel('ErrorApi')->info("jibimo timing withdraw: ". json_encode($response));
            $result = array('status' => false,'msg'=> $response->errors['invalid'],'response'=>$response);
        }
        return (object)$result;
    }

    private function getApiKeyWithdraw(){
        $jibimo = PaymentGateway::where('name','jibimo')->first();
        $dataJibimo = json_decode($jibimo->data);
        if(isset($dataJibimo->apikay_withdraw_expire) && $dataJibimo->apikay_withdraw_expire > date('Y-m-d H:i:s',strtotime('+ 3 minute'))){
            return $dataJibimo->apikay_withdraw->access_token;
        }else{
            $params = array(
                'username' => $dataJibimo->username,
                'password' => $dataJibimo->password,
                'secret_key' => $dataJibimo->secret_key,
                'scopes' => ["iban-inquiry", "card-inquiry"],
            );
            $response = Http::withHeaders([
                'Content-Type'=>'application/json',
                'Accept'=>'application/json',
            ])->post('https://api.jibimo.com/v2/auth/token', $params);
            $response = (object)$response->json();
            if(isset($response->access_token)) {
                $dataJibimo->apikay_withdraw_expire = date('Y-m-d H:i:s',strtotime('+ 30 minute'));
                $dataJibimo->apikay_withdraw = $response;
                $dataJibimo->data = json_encode($dataJibimo);
                $jibimo->save();
            }
            return $dataJibimo->apikay_withdraw->access_token;
        }
    }
}
