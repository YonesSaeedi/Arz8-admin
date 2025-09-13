<?php

namespace App\Models\PaymentGateway;

use Auth;
use App\Models\PaymentGateway\PaymentGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class Baje
{
    function withdraw($amount,$Iban,$id_last,$description = 'تسویه کاربر',$model = null,$wageStatus = true,$baje_account = null){
        $Zibal = PaymentGateway::where('name','baje')->first();
        $dataZibal = json_decode($Zibal->data);

        if(!isset($model)){
            $model = $dataZibal->withdraw_model;
            if ($amount > 100000000)
                $model = 0;
        }

        if($wageStatus){
            $wage = $amount * 0.0003;
            if($wage< 1000)
                $wage = 1000;

            $amount = $amount - $wage;
        }
        if($baje_account == null)
            $accountId = $dataZibal->accountId;
        else{
            $bajeAccounts = (array)$dataZibal->account;
            $names = array_column($bajeAccounts, 'accountName'); // فقط ستون اسم‌ها
            $index = array_search($baje_account, $names); // پیدا کردن ایندکس اسم مورد نظر

            if ($index !== false) {
                $accountId = $bajeAccounts[$index]['accountId'];
            } else {
                $accountId = $dataZibal->accountId;
            }
        }

        $params = array(
            'amount' => round($amount*10), //Rial
            'iban' => 'IR'.$Iban,
            'accountId' => $accountId,
            'delay' => $model,
            'uniqueCode' => $id_last,
            'description' => $description,
        );
        $authorization = 'Bearer '. $Zibal->token;
        $response = Http::withHeaders(['Content-Type'=>'application/json','Authorization' => $authorization])
            ->post('https://api.zibal.ir/ebank/v1/account/checkout/create/', $params);
        try{
            $response = (object)$response->json();
        } catch(\Exception $e){
            return (object) array('status' => false,'msg' => ($e->getMessage()));
        }

        if (isset($response->result) && $response->result == 1) {
            $result = array('status' => true,'msg' => 'واریز به صورت پایا انجام شد','response'=>$response);
        }else{
            $result = array('status' => false,'msg' => ($response->message),'response'=>$response);
        }
        return (object)$result;
    }


    function listDeposit($accountId){
        $Zibal = PaymentGateway::where('name','baje')->first();
        $fromDate = Carbon::now()->subHours(48)->toIso8601String();
        $params = array(
            'accountId' => $accountId,
            'paymentId' => null,
            'page' => 1,
            'size' => 100,
            'status' => 0,
            'fromDate' => $fromDate,
        );
        $authorization = 'Bearer '. $Zibal->token;
        $response = Http::withHeaders(['Content-Type'=>'application/json','Authorization' => $authorization])
            ->get('https://api.zibal.ir/ebank/v1/account/identified-payment/list/', $params);

        try {
            return $response->throw()->json()['data'];
        }
        catch(\Exception $e){
            return  [];
        }

    }
}
