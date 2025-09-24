<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\Cryptocurrency;
use App\Models\CryptoLittle;
use App\Models\CryptoNetwork;
use App\Models\Settings;
use App\Models\TransactionCrypto;
use App\Models\TransactionInternal;
use App\Models\WalletsCrypto;
use App\Models\WalletsInternal;
use Crypt;
use DB;
use Illuminate\Http\Request;
use Mdanter\Ecc\Math\DebugDecorator;

class CryptoController extends ExchangeApi
{
    public $path_logo;
    public $settings;
    public function __construct()
    {
        //$path = base_path('').env('PATH_PANEL').'/'.env('PUBLIC_USER').'/images/currency';
        //$this->path_logo = str_replace(env('PATH_ADMIN_PANEL'),'',$path);
        $path = public_path().'/images/currency';
        $this->path_logo = str_replace(env('PATH_ADMIN_PANEL'),env('PATH_PANEL'),$path);
        $this->path_logo = str_replace('sorg.ir-v3/public','app/api',$this->path_logo);
        $this->settings = array('font'=>false,'wage_buy'=> '0','wage_sell'=>'0' ,'hidden'=>false,
                                'price_tether_satatus'=>true,'fee_buy'=>30000,'fee_sell'=>30000, 'percent_buy'=>0,'percent_sell'=>0,
                                'stock_api'=> true, 'stock'=>0,'exchange_account'=>0);
        parent::__construct();
    }

    function listCrypto(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $cryptos = Cryptocurrency::query();

        // Filters
        $cryptos = self::filters($cryptos,$request);
        $usersCount = $cryptos->count();

        $cryptos->leftJoin('users_wallets_crypto','users_wallets_crypto.id_crypto','cryptocurrency.id')->groupBy('cryptocurrency.id');
        $cryptos->leftJoin('cryptocurrency_wage_trade','cryptocurrency_wage_trade.id_crypto','cryptocurrency.id')->groupBy('cryptocurrency.id');
        $cryptos->leftJoin('cryptocurrency_little','cryptocurrency_little.id_crypto','cryptocurrency.id')->groupBy('cryptocurrency.id');
        $cryptos->select(
            'cryptocurrency.id','cryptocurrency.symbol','cryptocurrency.icon','cryptocurrency.name','cryptocurrency.sort','cryptocurrency.percent',
            'cryptocurrency.buy_status','cryptocurrency.sell_status','cryptocurrency.withdraw_auto',
            DB::raw("ROUND(SUM(users_wallets_crypto.value_num),percent) as balance_users")
        );
        $cryptos->selectRaw('@price_usdt := JSON_EXTRACT(data, "$.price_usdt")*1 as price_usdt');
        $cryptos->selectRaw('SUM(users_wallets_crypto.value_num) * (JSON_EXTRACT(data, "$.price_toman_buy")) as balance_users_toman_buy');
        $cryptos->selectRaw('SUM(users_wallets_crypto.value_num) * (JSON_EXTRACT(data, "$.price_toman_sell")) as balance_users_toman_sell');


        $cryptos->selectRaw("
            ROUND(
                SUM(users_wallets_crypto.value_num) -
                (
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.balance.sum_all')) * 1, 0) +
                    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings, '$.coolwallet')) * 1, 0) +
                    COALESCE(cryptocurrency_wage_trade.amount_coin, 0) +
                    COALESCE(cryptocurrency_little.amount_coin * -1, 0)
                ),
                percent
            ) as balance_other_wallet
        ");

        $cryptos->selectRaw("
            ROUND(
                (
                    SUM(users_wallets_crypto.value_num) -
                    (
                        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.balance.sum_all')) * 1, 0) +
                        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings, '$.coolwallet')) * 1, 0) +
                        COALESCE(cryptocurrency_wage_trade.amount_coin, 0) +
                        COALESCE(cryptocurrency_little.amount_coin * -1, 0)
                    )
                ) * COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.price_usdt')) * 1, 0),
                percent
            ) as balance_other_wallet_usdt
        ");





        // Balance
        $cryptos->selectRaw("
            @balance := JSON_UNQUOTE(
                JSON_EXTRACT(
                    data,
                    CONCAT('$.balance.', exchange)
                )
            ) as balance,
            ROUND(@balance * @price_usdt,2) as balance_usdt
        ");

        $cryptos = $cryptos->paginate($limit)->items();

        //foreach ($cryptos as $crypto) {

        //}
        $result->lists = $cryptos;
        $result->total = $usersCount;

        $result->networks = CryptoNetwork::get();
        $exchange_account = [];
        $binance_a = json_decode(Crypt::decryptString(Settings::where('name', 'binance')->first()['value']));
        foreach ($binance_a as $key=>$item){
            array_push($exchange_account,$item->name);
        }

        $kucoin_a = json_decode(Crypt::decryptString(Settings::where('name', 'kucoin_new')->first()['value']));
        foreach ($kucoin_a as $key=>$item){
            array_push($exchange_account,$item->name);
        }

        $coinex_a = json_decode(Crypt::decryptString(Settings::where('name', 'coinex_new')->first()['value']));
        foreach ($coinex_a as $key=>$item){
            array_push($exchange_account,$item->name);
        }

        $result->exchange_account = $exchange_account;

        if(isset($request->balanceTotal)){

        }
        return response()->json($result);
    }


    function filters($cryptos,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'balanceWalletOther': $sortBy = 'balance_other_wallet_usdt'; break;
            case 'balanceUsers': $sortBy = 'balance_users'; break;
            case 'balanceUsersToman': $sortBy = 'balance_users_toman_buy'; break;
            case 'id': $sortBy = 'cryptocurrency.id'; break;
            case 'logo': $sortBy = 'cryptocurrency.id'; break;
            case 'buyStatus': $sortBy = 'buy_status'; break;
            case 'sellStatus': $sortBy = 'sell_status'; break;
            case 'withdrawAuto': $sortBy = 'withdraw_auto'; break;
            case 'balance': $sortBy = 'balance_usdt'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['cryptocurrency.id', 'name', 'symbol', 'data', 'locale'];
            $cryptos = $cryptos->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->hide)) {
            $cryptos->whereRaw('JSON_CONTAINS(settings, ?)', [json_encode(array('hidden' => $request->hide=="true"?true:false))]);
        }
        if (isset($request->exchange)) {
            $cryptos->where('exchange',$request->exchange);
        }
        if (isset($request->exchangeAccount)) {
            if (str_contains($request->exchangeAccount,'بایننس')){
                $binance_a = json_decode(Crypt::decryptString(Settings::where('name', 'binance')->first()['value']));
                foreach ($binance_a as $key=>$item){
                    if($item->name == $request->exchangeAccount){
                        $cryptos->where('exchange','binance')->whereRaw('JSON_CONTAINS(settings, ?)', [json_encode(array('exchange_account' => $key))]);
                    }
                }
            }elseif (str_contains($request->exchangeAccount,'کوکوین')){
                $kucoin_a = json_decode(Crypt::decryptString(Settings::where('name', 'kucoin_new')->first()['value']));
                foreach ($kucoin_a as $key=>$item){
                    if($item->name == $request->exchangeAccount){
                        $cryptos->where('exchange','kucoin')->whereRaw('JSON_CONTAINS(settings, ?)', [json_encode(array('exchange_account' => $key))]);
                    }
                }
            }
            elseif (str_contains($request->exchangeAccount,'کوینکس')){
                $coinex_a = json_decode(Crypt::decryptString(Settings::where('name', 'coinex_new')->first()['value']));
                foreach ($coinex_a as $key=>$item){
                    if($item->name == $request->exchangeAccount){
                        $cryptos->where('exchange','coinex')->whereRaw('JSON_CONTAINS(settings, ?)', [json_encode(array('exchange_account' => $key))]);
                    }
                }
            }

        }
        if (isset($request->statusBuy)) {
            $cryptos->where('buy_status',$request->statusBuy == "true"?1:0);
        }
        if (isset($request->statusSell)) {
            $cryptos->where('sell_status',$request->statusSell == "true"?1:0);
        }
        if (isset($request->withdraw)) {
            $cryptos->where('withdraw',$request->withdraw == "true"?1:0);
        }
        if (isset($request->deposit)) {
            $cryptos->where('deposit',$request->deposit == "true"?1:0);
        }
        if (isset($request->network)) {
            $network = CryptoNetwork::where('symbol',$request->network)->first();
            $cryptos->whereRaw('JSON_CONTAINS(network, ?)', [json_encode(array('id_network' => $network->id))]);
        }
        switch (isset($request->other)){
            case 'hasCoolWallet':
                $cryptos->whereRaw('(JSON_EXTRACT(settings, "$.coolwallet") IS NOT NULL AND JSON_EXTRACT(settings, "$.coolwallet") + 0) > 0');
                break;
        }
        if(isset($sortBy))
            $cryptos->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $cryptos;
    }

    function totalBalance(Request $request)
    {
        $result = (object)array();

        if ($request->filled('exchange')) {
            $exchange = $request->exchange;
            $cryptos = Cryptocurrency::selectRaw("
                ROUND(SUM(
                    JSON_UNQUOTE(JSON_EXTRACT(data, '$.balance.$exchange')) *
                    JSON_UNQUOTE(JSON_EXTRACT(data, '$.price_usdt'))
                ), 2) AS total_usdt
            ")->first();
            $result = $cryptos;
        }

        $indexExchangeAccount = null;
        $exchange = null;
        if ($request->filled('exchangeAccount')) {

            if (str_contains($request->exchangeAccount,'بایننس')){
                $binance_a = json_decode(Crypt::decryptString(Settings::where('name', 'binance')->first()['value']));
                foreach ($binance_a as $key=>$item){
                    if($item->name == $request->exchangeAccount){
                        $indexExchangeAccount = $key;
                        $exchange = 'binance';
                    }
                }
            }elseif (str_contains($request->exchangeAccount,'کوکوین')){
                $kucoin_a = json_decode(Crypt::decryptString(Settings::where('name', 'kucoin_new')->first()['value']));
                foreach ($kucoin_a as $key=>$item){
                    if($item->name == $request->exchangeAccount){
                        $indexExchangeAccount = $key;
                        $exchange = 'kucoin';
                    }
                }
            }
            elseif (str_contains($request->exchangeAccount,'کوینکس')){
                $coinex_a = json_decode(Crypt::decryptString(Settings::where('name', 'coinex_new')->first()['value']));
                foreach ($coinex_a as $key=>$item){
                    if($item->name == $request->exchangeAccount){
                        $indexExchangeAccount = $key;
                        $exchange = 'coinex';
                    }
                }
            }

            $cryptos = Cryptocurrency::selectRaw("
                -- مجموع موجودی اکانت × قیمت دلاری
                 ROUND(
                    SUM(
                        (JSON_UNQUOTE(JSON_EXTRACT(data, '$.balance.{$exchange}_account[{$indexExchangeAccount}]'))) *
                        (JSON_UNQUOTE(JSON_EXTRACT(data, '$.price_usdt')))
                    ), 2
                ) AS total_usdt
            ")->first();

            $result = $cryptos?? 0;
        }

        if (!$request->filled('exchange') && !$request->filled('exchangeAccount')){
            $cryptos = Cryptocurrency::selectRaw("
                -- مجموع balance.sum_all × قیمت دلار
                ROUND(
                    SUM(
                        (JSON_UNQUOTE(JSON_EXTRACT(data, '$.balance.sum_all'))) *
                        (JSON_UNQUOTE(JSON_EXTRACT(data, '$.price_usdt')))
                    ), 2
                ) AS total_usdt
            ")->first();
            $result = $cryptos ?? 0;
        }


        $result->total_toman = round($result->total_usdt * self::feeUsdt()['sell']);

        return response()->json($result);
    }


    function newCrypto(Request $request){
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'nameFa' => 'required',
            'symbol' => 'required',
            'exchange' => 'required',
            'percent' => 'required|numeric',
            //'network' =>  'required',
            'icon' => 'required|mimes:svg,png|max:20500',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $name = strtolower(str_replace(' ','-',$request->name));
        $symbol = strtoupper($request->symbol);
        $cryptoExist = Cryptocurrency::where('name',$name)->orWhere('symbol',$symbol)
                                            ->orWhereRaw('JSON_CONTAINS(locale,?)', [json_encode(array('fa' =>array('name'=>$request->nameFa)))])->first();
        if(isset($cryptoExist))
            return array('status' => false, 'msg' => 'نام یا نام فارسی یا نماد ارز تکراری است و از قبل وجود دارد.');


        /*
        if($request->exchange == 'binance') {
            try {
                $price = $this->binance->api[0]->price($symbol . 'USDT');
            } catch (\Exception $e) {
                return array('status' => false, 'msg' => 'همچین ارزی در باینسس وجود ندارد.');
            }
        }else{
            $price = $this->coinex->price($symbol . 'USDT');
            if(isset($price['status']) && $price['status']==false)
                return array('status' => false, 'msg' => 'همچین ارزی در کوینکس وجود ندارد.');
        }*/

        $crypto = new Cryptocurrency();
        $crypto->name = $name;
        $crypto->symbol = $symbol;
        $crypto->percent = $request->percent;
        $crypto->icon = $name.'.'.$request->icon->extension();
        $locale = array('fa' =>array('name'=>$request->nameFa));
        $crypto->locale = json_encode($locale);
        $crypto->exchange = $request->exchange;
        $crypto->color = '#fdb90';

        // check exist network for coin
        if(isset($request->network) && $request->network!="null" ):
            $network = CryptoNetwork::where('symbol', $request->network)->first();
            if($request->exchange=='binance') {
                $coins = $this->binance->api[0]->coins();
                $indexArrayFind = array_search($symbol, array_column($coins, 'coin'));
                $coin = $coins[$indexArrayFind];
                $indexArrayFind = array_search($network->symbol, array_column($coin['networkList'], 'network'));
                if (!is_numeric($indexArrayFind))
                    return array('status' => false, 'msg' => 'همچین شبکه ای برای این ارز در باینسس وجود ندارد.');
            }
            $crypto->network = json_encode(array(array('status'=>1, 'id_network'=>$network->id,'is_default'=>true)));
        else:
            $crypto->network = json_encode(array());
        endif;


        if(in_array(strtolower($crypto->symbol),self::listClassCryptoFont()))
            $this->settings['font'] = true;
        $crypto->settings = json_encode($this->settings);

        $request->file('icon')->move($this->path_logo,$name.'.'.$request->icon->extension());
        $crypto->save();


        $little = CryptoLittle::where('id_crypto',$crypto->id)->first();
        if(!isset($little)){
            $little = new CryptoLittle();
            $little->id_crypto = $crypto->id;
            $little->amount_coin = 0;
            $little->amount_toman = 0;
            $little->save();
        }

        $this->cacheClear('crypto');
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');
    }

    function singleCrypto(Request $request){
        $crypto = Cryptocurrency::find($request->id);
        $data = json_decode($crypto->data??'{}');
        $locales = Settings::where('name','locale')->first()->value;
        $locales = json_decode($locales);
        $networks = CryptoNetwork::get();

        $settings = json_decode($crypto->settings??'{}');
        foreach ($this->settings as $key => $value){
            $settings->{$key} = isset($settings->{$key})? $settings->{$key} : $value;
        }
        $crypto->settings = json_encode($settings);
        $crypto->save();

        if(in_array(strtolower($crypto->symbol),self::listClassCryptoFont()))
            $crypto->hasFont = true;

        $crypto->network = json_decode($crypto->network);

        $statistic = (object)[];
        $statistic->userBalance = WalletsCrypto::where('id_crypto',$crypto->id)->where('value_num','>',0)->count();
        $statistic->allBalance = WalletsCrypto::where('id_crypto',$crypto->id)->sum('value_num');
        $statistic->allBalanceAvailable = WalletsCrypto::where('id_crypto',$crypto->id)->sum('value_available_num');
        $statistic->littleBalance = CryptoLittle::where('id_crypto',$crypto->id)->sum('amount_coin');
        $statistic->wageTrades = DB::table('cryptocurrency_wage_trade')->where('id_crypto',$crypto->id)->first()->amount_coin??0;


        $statistic->balance = self::balanceExchange($crypto->symbol);
        //dd($statistic->balance->sum_balance);

        $statistic->otherBalance = $statistic->allBalanceAvailable -
                                        ($statistic->balance->sum_balance + ($settings->coolwallet??0) + $statistic->wageTrades + ($statistic->littleBalance * -1));


        // list account exchange
        $exchange = ['binance'=>[],'coinex'=>[],'kucoin'=>[]];
        $binance = json_decode(Crypt::decryptString(Settings::where('name', 'binance')->first()['value']));
        foreach ($binance as $key=>$item){
            array_push($exchange['binance'],['key'=>$key,'name'=>$item->name]);
        }

        $kucoin = json_decode(Crypt::decryptString(Settings::where('name', 'kucoin_new')->first()['value']));
        foreach ($kucoin as $key=>$item){
            array_push($exchange['kucoin'],['key'=>$key,'name'=>$item->name]);
        }

        $coinex = json_decode(Crypt::decryptString(Settings::where('name', 'coinex_new')->first()['value']));
        foreach ($coinex as $key=>$item){
            array_push($exchange['coinex'],['key'=>$key,'name'=>$item->name]);
        }

        $result = array('status' => true, 'msg' => '', 'crypto'=> $crypto, 'locales'=>$locales, 'networks'=>$networks,
            'statistic'=> $statistic,'exchange'=>$exchange);
        return response()->json($result);
    }

    function balanceExchange($symbol){
        $result = (object)array();
        $result->balance = ['binance'=>[],'kucoin'=>[],'coinex'=>[],'exonyx'=>0];
        $result->sum_balance = 0;


        foreach ($this->binance->api as $key=>$item){
            try{
                $result->balance['binance'][$key] = (float)$this->binance->api[$key]->balances()[$symbol]['available'];
                $result->sum_balance += $result->balance['binance'][$key];
                //$result->balance['binance']['sum'] += $result->balance['binance'][$key];
            }catch (\Exception $e){
                $result->balance['binance'][$key] = 0;
            }
        }
        try{
            $balance = $this->kucoin->apiAccount->getList(['currency'=>$symbol]);
            //dd($balance);
            $result->balance['kucoin']['wallets']['m'] = (float)$balance[0]['balance'];
            $result->balance['kucoin']['wallets']['t'] = isset($balance[1])? (float)$balance[1]['balance']:0;
            $result->balance['kucoin']['wallets']['s'] = $result->balance['kucoin']['wallets']['m']+$result->balance['kucoin']['wallets']['t'];
            //$result->balance['kucoin']['sum'] += $result->balance['kucoin']['wallets']['m']+$result->balance['kucoin']['wallets']['t'];
            $result->sum_balance += ($result->balance['kucoin']['wallets']['m']+$result->balance['kucoin']['wallets']['t']);
        }catch (\Exception $e){
            $result->balance['kucoin']['wallets']['m'] = 0;
            $result->balance['kucoin']['wallets']['t'] = 0;
            $result->balance['kucoin']['wallets']['s'] = 0;
        }


        foreach ($this->kucoin->apiAccount_list as $key=>$item){
            try{
                $balance = $this->kucoin->apiAccount_list[$key]->getList(['currency'=>$symbol]);
                $result->balance['kucoin'][$key]['m'] = $balance[0]['type']=='main' ? (float)$balance[0]['balance'] : 0;
                $result->balance['kucoin'][$key]['t'] = (isset($balance[1]) && $balance[1]['type']=='trade') ? (float)$balance[1]['balance']: (float)$balance[0]['balance'];
                $result->balance['kucoin'][$key]['s'] = $result->balance['kucoin'][$key]['m']+$result->balance['kucoin'][$key]['t'];
                //$result->balance['kucoin']['sum'] += ($result->balance['kucoin'][$key]['m']+$result->balance['kucoin'][$key]['t']);
                $result->sum_balance += $result->balance['kucoin'][$key]['s'];
            }catch (\Exception $e){
                $result->balance['kucoin'][$key]['m'] = 0;
                $result->balance['kucoin'][$key]['t'] = 0;
                $result->balance['kucoin'][$key]['s'] = 0;
            }
        }

        foreach ($this->coinex->api as $key=>$item) {
            try{
                $balance = $this->coinex->api[$key]->balance();
                if(isset($balance->data[$symbol])){
                    $result->balance['coinex'][$key] = (float)$balance->data[$symbol]['available'];;
                    $result->sum_balance += $result->balance['coinex'][$key];
                }else{
                    $result->balance['coinex'][$key] = 0;
                }
            }catch (\Exception $e){

            }
        }


        try {
            if ($symbol == 'USDT' || $symbol == 'TRX'):
                $response = \Http::withHeaders([
                    'accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'apiKey' => 'BEr4WdSiFIQoFxbcEJ86P22G7mfhcBQoQTq7KbS2zx1dfjRiNzs8PLytIeOtISCnsRQwOH2BbhJffluIM1WXxnZVtC4nLfFIB2ziOwKlSRsX8lSruobAAiEEmtkBrxFrzxewM9OllpQEncpDQRwva9Sji6vYsCycVF07jsSkAMPcA4cN5nCXhpmpBNpH79y',
                ])->timeout(10)->get('https://api.exonyx.com/api/v1/reseller' . '/wallet/index');
                $response = (object)$response->throw()->json();
                $indexArrayFind = (string)array_search($symbol, array_column($response->data, 'currency'));
                $result->balance['exonyx'] = (float)$response->data[$indexArrayFind]['balance'];
                $result->sum_balance += $result->balance['exonyx'];
            endif;
        }catch (\Exception $e){
        }

        return $result;
    }


    function listClassCryptoFont(){
        $storagePath = storage_path('app/cryptofont.css');
        $css = file_get_contents($storagePath);
        $rules = [];
        $css = str_replace("\r", "", $css); // get rid of new lines
        $css = str_replace("\n", "", $css); // get rid of new lines
        $first = explode('}', $css);
        if($first)
        {
            foreach($first as $v)
            {
                $second = explode('{', $v);
                if(isset($second[0]) && $second[0] !== '')
                {
                    $rules[] = str_replace(['.cf-',':before'],['',''],trim($second[0]));
                }
            }
        }
        return $rules;
    }


    function editCrypto(Request $request){
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'color' => 'required',
            'percent' => 'required|numeric',
            'symbol' => 'required',
            'file' => 'nullable|mimes:svg,png|max:20500',
            'settings' => 'required',
            //'networkDefault' => 'required|numeric',
            'locale' => 'required',
            'exchange' => 'required',
        ]);
        if ($validator->fails()) {
            $result = array('status' => false, 'msg' => $validator->errors()->first());
            return response()->json($result);
        }
        $name = strtolower(str_replace(' ','-',$request->name));
        $symbol = strtoupper($request->symbol);
        $cryptoExist = Cryptocurrency::where('name',$name)->orWhere('symbol',$symbol)
            ->orWhereRaw('JSON_CONTAINS(locale,?)', [json_encode(array('fa' =>array('name'=>$request->nameFa)))])->first();


        if(isset($cryptoExist) && $cryptoExist->id != $request->id)
            return array('status' => false, 'msg' => 'نام یا نام فارسی یا نماد ارز تکراری است و از قبل وجود دارد.');

        /*
        if($request->exchange == 'binance') {
            if($symbol != 'USDT'){
                try{
                    $price = $this->binance->api->price($symbol.'USDT');
                }catch (\Exception $e){
                    try{
                        $price = $this->binance->api->price($symbol.'BUSD');
                    }catch (\Exception $e) {
                        return array('status' => false, 'msg' => 'همچین ارزی در باینسس وجود ندارد.');
                    }
                }
            }
        }else{
            $price = $this->coinex->price($symbol . 'USDT');
            if(isset($price['status']) && $price['status']==false)
                return array('status' => false, 'msg' => 'همچین ارزی در کوینکس وجود ندارد.');
        }*/


        $crypto = Cryptocurrency::find($request->id);
        // check exist network for coin
        if(isset($request->networkDefault) && $request->networkDefault!="null"):
            $network = CryptoNetwork::where('id',$request->networkDefault)->first();

            // netwrok
            $networks_crypto = (array(array('status'=>1, 'id_network'=>$network->id,'is_default'=>true)));
            foreach (json_decode($request->network) as $key => $net){
                //$network = CryptoNetwork::where('id',$net->id_network)->first();
                //$indexArrayFind = array_search($network->symbol, array_column($coin['networkList'], 'network'));
                //if(!is_numeric($indexArrayFind))
                //    return array('status' => false, 'msg' => 'شبکه '. ($key+2) .' که انتخاب کرده اید در باینسس برای این ارز وجود ندارد.');
                array_push($networks_crypto,$net);
            }
            $crypto->network = json_encode($networks_crypto);
            $crypto->save();


            // Address Wallet
            if ($request->deposit == 1)
                try{
                    \Artisan::call("wallet:getAddress --id=".$crypto->id);
                }catch(\Exception $e){
                    return array('status' => false, 'msg' => 'احتمالا گرفتن کیف پول برای شبکه های انتخابی ارز غیر فعل است و واریز را غیر فعال نمایید مجدد تلاش کنید.');
                }



            // withdraw_min & withdraw_fee
            $job = (new \App\Jobs\Crypto\GetCryptoData($crypto->id))->delay(\Carbon\Carbon::now()->addSeconds(1));
            dispatch($job);


        endif;

        $crypto->settings = $request->settings;
        $crypto->locale = $request->localeCrypto;

        if($request->file){
            $crypto->icon = $name.'.'.$request->file->extension().'?v='.time();
            $request->file('file')->move($this->path_logo,$name.'.'.$request->file->extension());
        }

        $crypto->name = $name;
        $crypto->symbol = $request->symbol;
        $crypto->deposit = $request->deposit;
        $crypto->withdraw = $request->withdraw;
        $crypto->withdraw_auto = $request->withdraw_auto;
        $crypto->percent = $request->percent;
        $crypto->sell_status = $request->sell_status;
        $crypto->buy_status = $request->buy_status;
        $crypto->color = $request->color;
        $crypto->sort = $request->sort;
        $crypto->exchange = $request->exchange;
        $crypto->save();
        $this->cacheClear('crypto');
        return array('status' => true, 'msg' => 'تغییرات با موفقیت ثبت شد.');
    }

    function balanceUsers(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;
        $offset = (($request->page - 1) ?? 0) *$limit;

        $result = (object)array();
        $query = WalletsCrypto::query();
        $query->where('id_crypto',$request->id);
        $query->where('value_num','>',0);

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'users.name'; break;
            case 'userLevel': $sortBy = 'users.level'; break;
            case 'logo': $sortBy = 'cryptocurrency.id'; break;
            case 'balanceUsdt': $sortBy = 'balance_usdt'; break;
            case 'balanceUsersToman': $sortBy = 'balance_toman_buy'; break;
            case 'id': $sortBy = 'users_wallets_crypto.id'; break;
            default: $sortBy = $request->sortBy;
        }

        $search = $request->search;
        if (!empty($search)) {
            $fields = ['users.id', 'users.name', 'symbol', 'cryptocurrency.data', 'locale'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');


        $query->leftJoin('users','users_wallets_crypto.id_user','users.id');
        $query->leftJoin('cryptocurrency','users_wallets_crypto.id_crypto','cryptocurrency.id');
        $totalCount = $query->count();

        $query->select('cryptocurrency.symbol','users.name','users.id as id_user','users.family','users.email','users.level');
        $query->selectRaw(DB::raw("ROUND(users_wallets_crypto.value_num,percent) as balance"));
        $query->selectRaw('ROUND(users_wallets_crypto.value_num * JSON_EXTRACT(cryptocurrency.data, "$.price_usdt") ,4) as balance_usdt');
        $query->selectRaw('ROUND(users_wallets_crypto.value_num * JSON_EXTRACT(cryptocurrency.data, "$.price_toman_buy") ,percent) as balance_toman_buy');
        $query->selectRaw('ROUND(users_wallets_crypto.value_num * JSON_EXTRACT(cryptocurrency.data, "$.price_toman_sell") ,percent) as balance_toman_sell');

        $query->limit($limit)->offset($offset);
        $wallets = $query->get();


        $result->lists = $wallets;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function withdraw(Request $request){
        $validator = \Validator::make($request->all(), [
            'network' => 'required',
            'amount' => 'required',
            'addressWallet' => 'required',
            //'addressTagWallet' => 'required',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        if(\Auth::user()->role =='admin'):
            $cryptoNetwork = CryptoNetwork::where('id',$request->network)->first();
            $crypto = Cryptocurrency::where('id',$request->id)->
                                whereRaw('JSON_CONTAINS(network, ?)', [json_encode(array('id_network' => $cryptoNetwork->id))])->first();
            $settings = json_decode($crypto->settings??'{}');
            $index_account = $settings->exchange_account??0;
            if(isset($crypto) && isset($cryptoNetwork)){
                $amount = self::cutFloatNumber($request->amount,$crypto->percent);

                // Validate Address
                $validateAddress = preg_match('/' . $cryptoNetwork->addressRegex . '/', $request->addressWallet);
                if (!$validateAddress)
                    return array('status' => false, 'msg' =>'آدرس ولت اشتباه درج شده است!');

                // Validate Address Tag
                if ($cryptoNetwork->tag == 1 && isset($request->addressTagWallet) && $request->addressTagWallet != '') {
                    $validateAddressTag = preg_match('/' . $cryptoNetwork->memoRegex . '/', $request->addressTagWallet);
                    if (!$validateAddressTag)
                        return array('status' => false, 'msg' => 'تگ یا ممو آدرس ولت اشتباه درج شده است!');
                }

                // send Otp for withdraw
                $otp = self::OTP('WithdrawCrypto',$request->codeOtp);
                if($otp['status'] == false && $otp['msg'] == '2fa disabled'){
                    return array('status' => false, 'msg' => __('To withdraw, you need to enable one of the two-step login methods in the profile settings.'));
                }else if ($otp['status'] != true)
                    return response()->json($otp);

                // withdraw
                try {
                    $response = $this->binance->api[$index_account]->withdraw($crypto->symbol, $request->addressWallet, $amount,
                        $request->addressTagWallet, "",false, $cryptoNetwork->symbol);
                    if (isset($response) && isset($response['id'])) {
                        self::logSave('withdraw',$request->all(), 'برداشت ارز '.$crypto->symbol.' به کیف پول دیگر',$request->ip());
                        return array('status' => true, 'msg' =>'ارز با موفقیت انتقال یافت!');
                    }else{
                        return array('status' => false, 'msg' => json_encode($response));
                    }
                } catch (\Exception $e) {
                    return array('status' => false, 'msg' => $e->getMessage());
                }


            }else
                return array('status' => false, 'msg' =>'شبکه یا ارز موجود نیست!');
        endif;
    }


    function trade(Request $request){
        $validator = \Validator::make($request->all(), [
            'amount' => 'required',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        if(\Auth::user()->role =='admin'):
            $crypto = Cryptocurrency::where('id',$request->id)->first();
            if(isset($crypto)){
                $amount = self::cutFloatNumber($request->amount,$crypto->percent);

                // send Otp for withdraw
                $otp = self::OTP('TradeCrypto',$request->codeOtp);
                if($otp['status'] == false && $otp['msg'] == '2fa disabled'){
                    return array('status' => false, 'msg' => __('To withdraw, you need to enable one of the two-step login methods in the profile settings.'));
                }else if ($otp['status'] != true)
                    return response()->json($otp);

                // Trade
                $result = self::tradeExchange($crypto,$amount,$request->reverse);
                self::logSave('trade',$request->all(), (!$request->reverse?'ترید ارز '.$crypto->symbol.' به تتر':$crypto->symbol.'ترید تتر به '),$request->ip());
                return  $result;


            }else
                return array('status' => false, 'msg' =>'شبکه یا ارز موجود نیست!');
        endif;
    }

    private function tradeExchange($crypto,$amount,$reverse){
        $settings = json_decode($crypto->settings??'{}');
        $index_account = $settings->exchange_account??0;
        if($crypto->exchange =='binance'):
            $symbolMarket = $crypto->symbol.'USDT';
            try {
                if(!$reverse)
                    $result = $this->binance->api[$index_account]->marketSell($symbolMarket, $amount);
                else
                    $result = $this->binance->api[$index_account]->marketBuy($symbolMarket, $amount);
                if (isset($result['symbol']) && $result['symbol'] == $symbolMarket)
                    return array('status' => true, 'msg' =>'ارز با موفقیت تبدیل شد!');
                else
                    return array('status' => false, 'msg' => json_encode($result));
            } catch (\Exception $e) {
                return array('status' => false, 'msg' => $e->getMessage());
            }
        elseif($crypto->exchange =='kucoin'):
            $arr = [
                'clientOid' => uniqid(),
                'symbol'    => $crypto->symbol.'-USDT',
                'type'      => 'market',
                'side'      => strtolower($reverse?'buy':'sell'),
                'size'      => strval($amount),
                'remark'    => '',
            ];
            try{
                $trade = $this->kucoin->apiTrade_list[$index_account]->create($arr);
                return array('status' => true, 'msg' =>'ارز با موفقیت تبدیل شد!');
            }catch (\Exception $e){
                return array('status' => false, 'msg' => $e->getMessage());
            }
        elseif($crypto->exchange =='coinex'):
            try{
                $result = $this->coinex->api[$index_account]->marketTrade($reverse?'BUY':'SELL',($crypto->symbol . 'USDT'),$amount,$crypto->symbol);
                if($result->status == true)
                    return array('status' => true, 'msg' =>'ارز با موفقیت تبدیل شد!');
                else
                    return array('status' => false, 'msg' => json_encode($result));
            }catch (\Exception $e){
                return array('status' => false, 'msg' => $e->getMessage());
            }
        endif;

    }


    function changeAllBalance(Request $request){
        $validator = \Validator::make($request->all(), [
            'fee' => 'required',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        if(\Auth::user()->role =='admin'):
            $crypto = Cryptocurrency::where('id',$request->id)->first();
            if(isset($crypto)){
                $feeUsdt = $request->fee;
                $otp = self::OTP('TradeCrypto',$request->codeOtp);
                if($otp['status'] == false && $otp['msg'] == '2fa disabled'){
                    return array('status' => false, 'msg' => __('To withdraw, you need to enable one of the two-step login methods in the profile settings.'));
                }else if($otp['status'] != true)
                    return response()->json($otp);

                $price = $feeUsdt * $this->feeUsdt()['sell'];
                $wallets = WalletsCrypto::where('id_crypto',$request->id)->where('value_num','>',0)->get();
                foreach ($wallets as $wallet){
                    DB::beginTransaction();
                    try{
                        $balance = Crypt::decryptString($wallet->value);
                        $balance_available = Crypt::decryptString($wallet->value_available);

                        // Internal pluse
                        $amount = $price*$balance_available;
                        $walletInternal = WalletsInternal::where('id_user',$wallet->id_user)->first();
                        if(!isset($walletInternal)){
                            $walletInternal = new WalletsInternal();
                            $walletInternal->id_user = $wallet->id_user;
                            $walletInternal->id_internal = 1;
                            $walletInternal->value = Crypt::encryptString(0);
                            $walletInternal->value_available = Crypt::encryptString(0);
                            $walletInternal->save();
                        }
                        $balanceInternal = Crypt::decryptString($walletInternal->value);
                        $balance_availableInternal = Crypt::decryptString($walletInternal->value_available);
                        $walletInternal->value = Crypt::encryptString($balanceInternal + $amount);
                        $walletInternal->value_available = Crypt::encryptString($balance_availableInternal + $amount);
                        $walletInternal->value_num = $balanceInternal + $amount;
                        $walletInternal->value_available_num = $balance_availableInternal + $amount;
                        $walletInternal->Save();

                        $transaction = new TransactionInternal();
                        $transaction->id_internalcurrency = 1;
                        $transaction->id_user = $walletInternal->id_user;
                        $transaction->type = 'deposit';
                        $transaction->amount = $amount;
                        $transaction->payment = $amount;
                        $transaction->status = 'success';
                        $transaction->description = 'فروش و تبدیل ارز '.$crypto->symbol;
                        $transaction->id_admin = \Auth::user()->id;
                        $transaction->stock = $walletInternal->value_num;
                        $transaction->save();

                        // Crypto zero
                        $wallet->value = Crypt::encryptString('0');
                        $wallet->value_available = Crypt::encryptString('0');
                        $wallet->value_num = 0;
                        $wallet->value_available_num = 0;
                        $wallet->save();

                        $transaction = new TransactionCrypto;
                        $transaction->id_crypto = $crypto->id;
                        $transaction->id_user = $wallet->id_user;
                        $transaction->type = 'withdraw';
                        $transaction->amount = $balance_available;
                        $transaction->payment = $balance_available;
                        $transaction->status = 'success';
                        $transaction->description = 'فروش و تبدیل به تومان';
                        $transaction->amount_toman = $amount;
                        $transaction->id_admin = \Auth::user()->id;
                        $transaction->stock = $wallet->value_num;
                        $transaction->save();

                        DB::commit();

                    }catch (\Exception $e){
                        DB::rollback();
                        dd($e);
                    }
                }
                return array('status' => true, 'msg' =>'موجودی همه کاربران با موفقیت به تومان تبدیل شد!');

            }else
                return array('status' => false, 'msg' =>'شبکه یا ارز موجود نیست!');
        endif;
    }


    function fitBalance(Request $request) {
        if(\Auth::user()->role =='admin'):
            $crypto = Cryptocurrency::where('id',$request->id)->first();
            if(isset($crypto)){
                $allBalanceAvailable = WalletsCrypto::where('id_crypto',$crypto->id)->sum('value_available_num');
                $balance = self::balanceExchange($crypto->symbol);
                $settings = json_decode($crypto->settings??'{}');
                $wageTrades = DB::table('cryptocurrency_wage_trade')->where('id_crypto',$crypto->id)->first()->amount_coin??0;
                $littleBalance = CryptoLittle::where('id_crypto',$crypto->id)->sum('amount_coin');

                $otherBalance = $allBalanceAvailable -
                    ($balance->sum_balance + ($settings->coolwallet??0) + $wageTrades + ($littleBalance * -1));

                $little = new CryptoLittleController();
                $minTrade = $little->minTrade($crypto);
                if(abs($otherBalance) < $minTrade)
                    return array('status' => false, 'msg' =>'موجودی سایر از حداقل مجاز معامله کمتر است!');

                $trade_auto = new \App\Models\CryptoAutoTrade();
                $trade_auto->from  = 'admin_balance_set';
                $trade_auto->id_crypto  = $crypto->id;
                $type = $otherBalance < 0?'SELL':'BUY';
                $trade = $little->tradeExchange($crypto,$otherBalance,$type);
                if($trade->status == true){
                    $trade_auto->amount_usdt = $trade->amount_usdt;
                    $trade_auto->status = 'success';
                    $result = array('status' => true, 'msg' =>'بالانس موجودی با موفقیت انجام شد!'.$trade->amount_coin);
                }else
                    $result = array('status' => false, 'msg' => 'متاسفانه انجام نشد و نتیجه در ترید های اتوماتیک ثبت شده است.'.$trade->msg);

                $trade_auto->amount_coin = $trade->amount_coin;
                $trade_auto->side = strtolower($type);
                $trade_auto->data = json_encode($trade);
                $trade_auto->save();

                return $result;

            }else
                return array('status' => false, 'msg' =>'شبکه یا ارز موجود نیست!');
        endif;
    }

    function fitBalanceGroup(Request $request) {
        if(\Auth::user()->role =='admin'):
            $cryptos = Cryptocurrency::whereIn('symbol',$request->symbols)->whereNotIn('symbol',['USDT','CET','BNB'])->get();
            $count = count($cryptos);
            $success = 0;
            $failer = 0;
            $minLimit = 0;
            $little = new CryptoLittleController();
            foreach ($cryptos as $crypto){
                $allBalanceAvailable = WalletsCrypto::where('id_crypto',$crypto->id)->sum('value_available_num');
                $balance = self::balanceExchange($crypto->symbol);
                $settings = json_decode($crypto->settings??'{}');
                $wageTrades = DB::table('cryptocurrency_wage_trade')->where('id_crypto',$crypto->id)->first()->amount_coin??0;
                $littleBalance = CryptoLittle::where('id_crypto',$crypto->id)->sum('amount_coin');

                $otherBalance = $allBalanceAvailable -
                    ($balance->sum_balance + ($settings->coolwallet??0) + $wageTrades + ($littleBalance * -1));


                $minTrade = $little->minTrade($crypto);
                if(abs($otherBalance) < $minTrade){
                    $minLimit++;
                    continue;
                }


                $trade_auto = new \App\Models\CryptoAutoTrade();
                $trade_auto->from  = 'admin_balance_set';
                $trade_auto->id_crypto  = $crypto->id;
                $type = $otherBalance < 0?'SELL':'BUY';
                $trade = $little->tradeExchange($crypto,$otherBalance,$type);
                if($trade->status == true){
                    $trade_auto->amount_usdt = $trade->amount_usdt;
                    $trade_auto->status = 'success';
                    //$result = array('status' => true, 'msg' =>'بالانس موجودی با موفقیت انجام شد!'.$trade->amount_coin);
                    $success++;
                }else
                    $failer++;
                    //$result = array('status' => false, 'msg' => 'متاسفانه انجام نشد و نتیجه در ترید های اتوماتیک ثبت شده است.'.$trade->msg);

                $trade_auto->amount_coin = $trade->amount_coin;
                $trade_auto->side = strtolower($type);
                $trade_auto->data = json_encode($trade);
                $trade_auto->save();
            }

        return  array('status' => true, 'msg' => 'موفق:'.$success .' | ناموفق:' .$failer.' | حداقل مجاز:'.$minLimit);
        endif;
    }
}
