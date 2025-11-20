<?php

namespace App\Models\PaymentGateway;

use Auth;
use App\Models\PaymentGateway\PaymentGateway;
use Illuminate\Support\Facades\Http;

class Paystar
{
    public $application_id = "r40ej";
    public $access_password = "n9HKaw4hZxNhj3mBzl0MguUOeodg7IR6uOPrz3u70jiR5ArakL";
    public $account_number = "0120137445003";

    function withdraw($amount,$Iban,$id_last,$name,$family){
        $apikey_withdraw = self::getApiKeyWithdraw();
        $paystar = PaymentGateway::where('name','paystar')->first();
        $dataPaystar = json_decode($paystar->data);
        $sing = hash_hmac('sha512',($dataPaystar->id_wallet.'#'.$dataPaystar->wallet_password) ,$dataPaystar->singkey_withdraw);
        $fee_withdraw = round($amount * 0.002);
        if($fee_withdraw > 5500)
            $fee_withdraw = 5500;
        elseif ($fee_withdraw < 500)
            $fee_withdraw = 500;

        $amount = $amount - $fee_withdraw;
        $params = array(
            'wallet_hashid' => $dataPaystar->id_wallet,
            'withdraw_type' => 7,
            'password' => $dataPaystar->wallet_password,
            'sign' => $sing,
            'transfers' => [
                [
                'amount'=> $amount*10,
                'destination_number'=> 'IR'.$Iban,
                'destination_firstname'=> $name,
                'destination_lastname'=> $family,
                'track_id'=> ($amount<100000000) ? (string)$id_last : null,
                ]
            ],
        );

        $response = Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization' => 'Bearer '. $apikey_withdraw
        ])->post('https://core.paystar.ir/api/wallet/create-settlement', $params);
        $response = (object)$response->json();
        if ($response->status == 1) {
            $result = array('status' => true,'msg' => 'واریز به صورت پایا انجام شد','response'=>$response);
        }else{
            if($response->message == "دسترسی نامعتبر")
                self::getApiKeyWithdraw(true);

            $result = array('status' => false,
                'msg' => isset(array_values($response->data)[0][0])? array_values($response->data)[0][0] : $response->status.' '.$response->message,'response'=>$response);
        }
        return (object)$result;
    }

    function withdrawGroup($transfers = []){
        $apikey_withdraw = self::getApiKeyWithdraw();
        $paystar = PaymentGateway::where('name','paystar')->first();
        $dataPaystar = json_decode($paystar->data);
        $sing = hash_hmac('sha512',($dataPaystar->id_wallet.'#'.$dataPaystar->wallet_password) ,$dataPaystar->singkey_withdraw);

        $params = array(
            'wallet_hashid' => $dataPaystar->id_wallet,
            'withdraw_type' => 8,
            'password' => $dataPaystar->wallet_password,
            'sign' => $sing,
            'transfers' => $transfers,
        );

        $response = Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization' => 'Bearer '. $apikey_withdraw
        ])->post('https://core.paystar.ir/api/wallet/create-settlement', $params);
        $response = (object)$response->json();
        if ($response->status == 1) {
            $result = array('status' => true,'response'=>$response);
        }else{
            \Log::channel('ErrorApi')->info("paystar timing withdraw: ". json_encode($transfers));
            \Log::channel('ErrorApi')->info("paystar timing withdraw: ". json_encode($response));
            $result = array('status' => false,'response'=>$response);
        }
        return (object)$result;
    }

    private function getApiKeyWithdraw($new_token = false){
        $paystar = PaymentGateway::where('name','paystar')->first();
        $dataPaystar = json_decode($paystar->data);
        if($dataPaystar->apikay_withdraw_expire > date('Y-m-d H:i:s',strtotime('+ 15 minute')) && $new_token == false){
            return $dataPaystar->apikay_withdraw;
        }else{
            $sing = hash_hmac('sha512',($dataPaystar->id_wallet.'#'.$dataPaystar->wallet_password) ,$dataPaystar->singkey_withdraw);
            $params = array(
                'wallet_hashid' => $dataPaystar->id_wallet,
                'password' => $dataPaystar->wallet_password,
                'refresh_token' => $dataPaystar->refresh_token_withdraw,
                'sign' => $sing,
            );
            $response = Http::withHeaders([
                'Content-Type'=>'application/json',
                'Authorization' => 'Bearer '. $dataPaystar->apikay_withdraw
            ])->post('https://core.paystar.ir/api/wallet/refresh-api-key', $params);
            $response = (object)$response->json();

            if($response->status == 1) {
                $dataPaystar->apikay_withdraw_expire = $response->data['api_key_expire_date'];
                $dataPaystar->apikay_withdraw = $response->data['api_key'];
                $paystar->data = json_encode($dataPaystar);
                $paystar->save();
            }
            return $dataPaystar->apikay_withdraw;
        }
    }


    function withdrawGroupOpenBanking($transfers = []){
        $apikey_withdraw = self::getApiKeyApplication();

        $params = array(
            'application_id' => $this->application_id,
            'access_password' => $this->access_password,
            'transfers' => $transfers,
        );

        $response = Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization' => 'Bearer '. $apikey_withdraw
        ])->post('https://core.paystar.ir/api/bank-transfer/v2/settlement', $params);
        $response = (object)$response->json();
        if ($response->status == "ok") {
            $result = array('status' => true,'response'=>$response);
        }else{
            $result = array('status' => false,'response'=>$response);
        }
        return (object)$result;
    }


    function withdrawOpenBanking($amount,$Iban,$id_last,$name,$family){
        $fee_withdraw = round($amount * 0.002);
        if($fee_withdraw > 5500)
            $fee_withdraw = 5500;
        elseif ($fee_withdraw < 500)
            $fee_withdraw = 500;

        $amount = $amount - $fee_withdraw;

        $apikey_withdraw = self::getApiKeyApplication();
        $params = array(
            'application_id' => $this->application_id,
            'access_password' => $this->access_password,
            'transfers' => [
                [
                    'deposit' => $this->account_number,
                    'amount'=> $amount*10,
                    'destination_account'=> 'IR'.$Iban,
                    'destination_firstname'=> $name,
                    'destination_lastname'=> $family,
                    'track_id'=> (string)$id_last,
                ]
            ],
        );

        $response = Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization' => 'Bearer '. $apikey_withdraw
        ])->post('https://core.paystar.ir/api/bank-transfer/v2/settlement', $params);
        $response = (object)$response->json();
        if ($response->status == "ok") {
            $result = array('status' => true,'msg' => 'واریز به صورت پایا انجام شد','response'=>$response);
        }else{
            if($response->message == "دسترسی نامعتبر")
                self::getApiKeyApplication(true);
            $result = array('status' => false,
                'msg' => isset(array_values($response->data)[0][0])? array_values($response->data)[0][0] : $response->status.' '.$response->message,'response'=>$response);
        }
        return (object)$result;
    }


    public function getApiKeyApplication($new_token = false){

        $paystar = PaymentGateway::where('name','paystar')->first();
        $dataPaystar = json_decode($paystar->data);
        if($dataPaystar->apikay_application_expire > date('Y-m-d H:i:s',strtotime('+ 15 minute')) && $new_token == false){
            return $dataPaystar->apikay_application;
        }else{
            $params = array(
                'application_id' =>  $this->application_id,
                'access_password' =>  $this->access_password,
                'refresh_token' => "Y538X6JU1HvFsK5dIJS92A6InjaI3e01QpleGAtLGBewmexWWBqqXZGH0D72YSJ8PO0ZioggRmVKPC1VUYpwb6eVT4Xgg54GgsKJ",
            );
            $response = Http::withHeaders([
                'Content-Type'=>'application/json',
            ])->post('https://core.paystar.ir/api/application/refresh-api-key', $params);
            $response = (object)$response->json();

            if($response->status == 1) {
                $dataPaystar->apikay_application_expire = $response->data['api_key_expire_date'];
                $dataPaystar->apikay_application = $response->data['api_key'];
                $paystar->data = json_encode($dataPaystar);
                $paystar->save();
            }
            return $dataPaystar->apikay_application;
        }
    }
}
