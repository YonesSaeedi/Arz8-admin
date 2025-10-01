<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\Cryptocurrency;
use App\Models\Settings;
use App\Models\WalletsCrypto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt as Crypt;
use Spatie\Async\Pool;

class PricingContoller extends ExchangeApi
{
    function getPrice()
    {
        $result = new \stdClass();

        $functions = [
            //'payeer' => 'payeer',
            'psvouchers' => 'psvouchers',
            'utopia' => 'utopia',
            'theter' => 'theter',
            'usdt' => 'usdt',
        ];

        foreach ($functions as $key => $function) {
            // فراخوانی تابع دینامیک و ذخیره نتایج
            $result->$key = $this->$function();
        }

        // بازگشت به نتیجه به صورت JSON
        return response()->json($result);

    }


    function theter()
    {
        $result = (object)array();
        $crypto = Cryptocurrency::where('symbol', 'USDT')->first();
        $price = self::priceToman($crypto);
        $result->buy = $price->buy;
        $result->sell = $price->sell;

        $result->balance = [
            'binance' => ['sum' => 0],
            'kucoin' => ['sum' => 0],
            'coinex' => ['sum' => 0],
            'exonyx' => 0
        ];
        $result->sum_balance = 0;


        // Binance
        try {
            foreach ($this->binance->api as $key => $item) {
                $result->balance['binance'][$key] = (float)$this->binance->api[$key]->balances()['USDT']['available'];
                $result->balance['binance']['sum'] += $result->balance['binance'][$key];
                $result->sum_balance += $result->balance['binance'][$key];
            }
        } catch (\Exception $e) {
            // در صورت خطا ادامه دهید
        }


        // Kucoin (اولین API)
        try {
            $balance = $this->kucoin->apiAccount->getList(['currency' => 'USDT']);
            $result->balance['kucoin']['wallets']['m'] = (float)$balance[0]['balance'];
            $result->balance['kucoin']['wallets']['t'] = (float)$balance[1]['balance'];
            $result->balance['kucoin']['sum'] += $result->balance['kucoin']['wallets']['m'] + $result->balance['kucoin']['wallets']['t'];
            $result->sum_balance += (float)$balance[0]['balance'] + (float)$balance[1]['balance'];
        } catch (\Exception $e) {
            $result->balance['kucoin']['wallets']['m'] = 0;
            $result->balance['kucoin']['wallets']['t'] = 0;
        }


        // Kucoin (API List)
        try {
            foreach ($this->kucoin->apiAccount_list as $key => $item) {
                $balance = $this->kucoin->apiAccount_list[$key]->getList(['currency' => 'USDT']);
                $result->balance['kucoin'][$key]['m'] = (float)$balance[0]['balance'];
                $result->balance['kucoin'][$key]['t'] = (float)$balance[1]['balance'];
                $result->balance['kucoin']['sum'] += ($result->balance['kucoin'][$key]['m'] + $result->balance['kucoin'][$key]['t']);
                $result->sum_balance += (float)$balance[0]['balance'] + (float)$balance[1]['balance'];
            }
        } catch (\Exception $e) {
            // در صورت خطا ادامه دهید
        }


        // Coinex
        try {
            foreach ($this->coinex->api as $key => $item) {
                $balance = $this->coinex->api[$key]->balance();
                $result->balance['coinex'][$key] = (float)$balance->data['USDT']['available'];
                $result->balance['coinex']['sum'] += $result->balance['coinex'][$key];
                $result->sum_balance += $result->balance['coinex'][$key];
            }
        } catch (\Exception $e) {
            // در صورت خطا ادامه دهید
        }


        // Exonyx
        try {
            $response = \Http::withHeaders([
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'apiKey' => 'BEr4WdSiFIQoFxbcEJ86P22G7mfhcBQoQTq7KbS2zx1dfjRiNzs8PLytIeOtISCnsRQwOH2BbhJffluIM1WXxnZVtC4nLfFIB2ziOwKlSRsX8lSruobAAiEEmtkBrxFrzxewM9OllpQEncpDQRwva9Sji6vYsCycVF07jsSkAMPcA4cN5nCXhpmpBNpH79y'
            ])->timeout(10)->get('https://api.exonyx.com/api/v1/reseller' . '/wallet/index');
            $response = (object)$response->throw()->json();
            $indexArrayFind = (string)array_search('USDT', array_column($response->data, 'currency'));
            $result->balance['exonyx'] = (float)$response->data[$indexArrayFind]['balance'];
            $result->sum_balance += $result->balance['exonyx'];
        } catch (\Exception $e) {
            //dd($e);
        }


        $balanceUsdt_users = WalletsCrypto::where('id_crypto', 5)->sum('value_num');
        $result->sum_balance_net = $result->sum_balance - $balanceUsdt_users;

        return $result;
    }


    function usdt()
    {
        return self::priceUsdtInToman();
    }


    function psvouchers()
    {
        $result = (object)array();
        $setting = json_decode(Crypt::decryptString(Settings::where('name', 'psvouchers')->first()->value));
        $result->buy = $setting->price->buy;
        $result->sell = $setting->price->sell;
        $result->balance = $setting->price->balance ?? 0;
        return $result;
    }

    function utopia()
    {
        $result = (object)array();
        $setting = json_decode(Crypt::decryptString(Settings::where('name', 'utopia')->first()->value));
        $result->buy = $setting->price->buy;
        $result->sell = $setting->price->sell;
        $result->balance = $setting->price->balance ?? 0;
        return $result;
    }


    function test()
    {
        $BotAdminTelegram = 'App\Http\Controllers\Telegram\Admin\AdminPriceController';
        $coin360 = app($BotAdminTelegram)->priceUsdt();
        dd(111,$coin360);


        $t = Cache::get('general_cryptocurrency');
        dd($t);


        $crypto = Cryptocurrency::where('symbol','BTC')->first();
        $ct = app('App\Http\Controllers\Settings\CryptoLittleController');
        dd($ct->minTrade($crypto));




       try {
            $response = \Http::withHeaders([
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'apiKey' => 'BEr4WdSiFIQoFxbcEJ86P22G7mfhcBQoQTq7KbS2zx1dfjRiNzs8PLytIeOtISCnsRQwOH2BbhJffluIM1WXxnZVtC4nLfFIB2ziOwKlSRsX8lSruobAAiEEmtkBrxFrzxewM9OllpQEncpDQRwva9Sji6vYsCycVF07jsSkAMPcA4cN5nCXhpmpBNpH79y'
            ])->get('http://159.69.133.179/api/v1/reseller' . '/wallet/index');
            $response = (object)$response->throw()->json();
            dd($response);

        } catch (\Exception $e) {
            dd($e);
        }


        //$list = json_decode(\Storage::get('twofa_users.json'), true);

       /* $list = \App\Models\User::where('twofa->status', true)
            ->where('twofa->type', 'sms')
            ->select('id')->get()->pluck('id')->toArray();
        dd($list);*/

        $list = json_decode(\Storage::get('twofa_users.json'), true);
        \App\Models\User::whereIn('id', $list)
            ->where('twofa->type','!=', 'email')
            ->update([
                'twofa' => null
            ]);
        dd(true);



        $ExchangeApi = new \App\Http\Controllers\Exchange\ExchangeApi();
        $coinexInfo = $ExchangeApi->coinex->api[0]->marketInfo();
        return ($coinexInfo);

        //$BotAdminTelegram = 'App\Http\Controllers\Telegram\Admin\AdminController';
        //return app($BotAdminTelegram)->coin360();



        $pool = Pool::create();

        $start_time = microtime(true); // زمان شروع کل فرآیند

        $pool->add(function () {
            $start = microtime(true);
            sleep(2); // شبیه‌سازی تأخیر
            echo "Task 1 executed\n";
            $end = microtime(true);
            return "Task 1 execution time: " . ($end - $start) . " seconds\n";
        });

        $pool->add(function () {
            $start = microtime(true);
            sleep(2); // شبیه‌سازی تأخیر
            echo "Task 2 executed\n";
            $end = microtime(true);
            return "Task 2 execution time: " . ($end - $start) . " seconds\n";
        });

        $pool->add(function () {
            $start = microtime(true);
            sleep(2); // شبیه‌سازی تأخیر
            echo "Task 3 executed\n";
            $end = microtime(true);
            return "Task 3 execution time: " . ($end - $start) . " seconds\n";
        });

        $results = $pool->wait(); // منتظر اتمام تمام تسک‌ها

        $end_time = microtime(true);
        echo "Total execution time: " . ($end_time - $start_time) . " seconds\n";

        foreach ($results as $result) {
            echo $result; // نمایش زمان هر درخواست
        }

        echo 'Version PHP: ' . phpversion();
    }


}
