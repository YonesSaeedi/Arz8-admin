<?php

namespace App\Models\PaymentGateway;
use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Auth;
use App\Models\Settings;

class Vandar
{

    function getToken(){
        $vandar = PaymentGateway::where('name','vandar')->first();
        $dataVandar = (object)json_decode($vandar->data);
        if(isset($dataVandar->created_at) && isset($dataVandar->expires_in) && ($dataVandar->created_at + ($dataVandar->expires_in-2000)) > time()){
            return $dataVandar->access_token;
        }else{
            $params = array(
                'mobile' => $dataVandar->mobile,
                'password' => $dataVandar->password,
            );
            $response = Http::withHeaders(['Content-Type'=>'application/json'])->post('https://api.vandar.io/v3/login', $params);
            try{
                $response = (object)$response->json();
            } catch(\Exception $e){
                return (object) array('status' => false,'msg' => ($e->getMessage()));
            }

            $response->created_at = time()-100;
            if(isset($response->expires_in) && isset($response->access_token)){
                $vandar->value = json_encode($response);
                $vandar->save();
            }
            return $response->access_token;
        }
    }

    function withdraw($amount,$Iban,$id_last){
        $functions = new functions;
        $token = self::getToken();
        $authorization = "Authorization: Bearer ".$token;

        $vandar = PaymentGateway::where('name','vandar')->first();
        $dataVandar = json_decode($vandar->data);

        $business = $dataVandar->name_business;
        $VandarModel = $dataVandar->model;

        $params = array(
            'amount' => $amount*10,
            'iban' => $Iban,
            'track_id' => $id_last+1,
            'payment_number' =>$id_last+1,
            //'notify_url' => $id_last.$amount,
            'is_instant' => $VandarModel=='true' ? true : null,
        );
        $responce = $functions->Curl('https://api.vandar.io/v3/business/'.$business.'/settlement/store',$params,$authorization);
        if(isset($responce->status ) && $responce->status == 1){
            $result = array('status' => true,'message' => 'واریز به صورت شبا انجام شد','responce'=>$responce);
        }else{
            $result = array('status' => false,'message' => reset($responce->errors)[0],'responce'=>$responce);
        }
        return (object)$result;

    }
}
