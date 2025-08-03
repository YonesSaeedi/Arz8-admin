<?php
namespace App\Http\Controllers\Settings;
use App\Http\Controllers\Exchange\BinanceApi;
use App\Models\Cryptocurrency;
use App\Models\CryptoNetwork;
use Illuminate\Http\Request;


class NetworkController extends  BinanceApi
{
    function listNetwork(){
        $networks = CryptoNetwork::orderBy('id','desc')->get();
        foreach ($networks as $network){
            $network->locale = json_decode($network->locale);
            $network->coins = Cryptocurrency::whereRaw('JSON_CONTAINS(network, ?)', [json_encode(array('id_network' => $network->id))])->count();
        }
        return $networks;
    }

    function editNetwork(Request $request){
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'nameFa' => 'required',
            'exchange' => 'required',
            //'symbol' => 'required',
            'tag' => 'required|numeric',
            'status' => 'required|numeric',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $network = CryptoNetwork::find($request->id);
        if($request->exchange == 'manual' && !isset($network->address_wallet) && (!isset($request->address_wallet) || $request->address_wallet ==''))
            return array('status' => false, 'msg' => 'نحوه استعلام در حالت دستی باید آدرس کیف پول درج شود!');

        //$network = CryptoNetwork::find($request->id);
        $network->name = $request->name;
        $network->symbol = $request->symbol;
        $network->tag = $request->tag;
        $network->status = $request->status;
        $network->exchange = $request->exchange;
        $network->address_wallet = $request->address_wallet;
        $network->address_tag_wallet = $request->address_tag_wallet;
        $locale = array('fa' =>array('name'=>$request->nameFa));
        $network->locale = json_encode($locale);
        $network->save();
        $this->cacheClear('crypto');
        return array('status' => true, 'msg' => 'تغییرات با موفقیت ثبت شد.');
    }

    function removeNetwork(Request $request){
        $network = CryptoNetwork::find($request->id);
        $coins = Cryptocurrency::whereRaw('JSON_CONTAINS(network, ?)', [json_encode(array('id_network' => $network->id))])->count();
        if($coins>0)
            return array('status' => false, 'msg' => 'ارز هایی هستند که به این شبکه متصل هستند!');
        try{
            $network->delete();
            $this->cacheClear('crypto');
            return array('status' => true, 'msg' => 'با موفقیت حذف شد!');

        }catch (\Exception $e){
            return array('status' => false, 'msg' => 'به دلیل اتصال داده ها به این شبکه حذف امکان پذیر نیست!');
        }
    }

    function newNetwork(Request $request){
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'nameFa' => 'required',
            'symbol' => 'required',
            'symbolCoin' => 'required',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $symbol = strtoupper($request->symbol);
        $symbolCoin = strtoupper($request->symbolCoin);

        $networkExist = CryptoNetwork::where('symbol',$symbol)->first();
        if(isset($networkExist))
            return array('status' => false, 'msg' => 'نماد این شبکه قبلا ثبت شده است.');

        $coins = $this->api[0]->coins();
        $indexArrayFind = array_search($symbolCoin, array_column($coins, 'coin'));
        $coin = $coins[$indexArrayFind];
        $indexArrayFind = array_search($symbol, array_column($coin['networkList'], 'network'));
        //if(!is_numeric($indexArrayFind))
        //    return array('status' => false, 'msg' => 'شبکه در باینسس وجود ندارد.');

        $addressRegex = $coin['networkList'][$indexArrayFind]['addressRegex'];
        $memoRegex = $coin['networkList'][$indexArrayFind]['memoRegex'];

        $network = new CryptoNetwork;
        $network->name = $request->name;
        $network->symbol = $symbol;
        $network->tag = $memoRegex!='' ? 1 : 0;
        $network->addressRegex = $addressRegex!='' ? $addressRegex : null;
        $network->memoRegex = $memoRegex!='' ? $memoRegex : null;
        $locale = array('fa' =>array('name'=>$request->nameFa));
        $network->locale = json_encode($locale);
        $network->save();
        $this->cacheClear('crypto');
        return array('status' => true, 'msg' => 'ثبت با موفقیت ثبت شد.');
    }
}
