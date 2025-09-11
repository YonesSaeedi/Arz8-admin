<?php

namespace App\Http\Controllers\Telegram\Admin;
use App\Models\Settings;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Morilog\Jalali;

class AdminPriceController
{
    private $key = '5519467988:AAHnqeh0X1NBKmhC5wXzGTTJvz7cfMnkvGc';
    private $chanel = /*'@test_arz8';//*/'@usd_iran';
    private $telegram;
    function __construct(){
        $this->telegram = new Api($this->key);
    }

    function priceUsdt(){
        $setting = Settings::where('name','last10MinPrice')->first();

        $response = Http::get('https://apiv2.nobitex.ir/v3/orderbook/USDTIRT');
        $result = (object)$response->throw()->json();
        if (isset($result->status) && $result->status == 'ok')
            $price_usdt_toman = round($result->lastTradePrice / 10);

       /* $response = Http::get('https://azapi.ok-ex.io/oapi/v1/market/ticker?symbol=USDT-IRT');
        $result = (object)$response->json();
        if (isset($result->code) && $result->code == 100)
            $price_usdt_toman = $result->ticker['last'];*/

        $color = ($setting->value > $price_usdt_toman)?'🔴':'🟢';
        $color2 = ($setting->value > $price_usdt_toman)?'🟥':'🟩';
        $change = $setting->value - $price_usdt_toman;
        $setting->value = $price_usdt_toman;
        $setting->save();

        $date = Jalali\CalendarUtils::strftime('Y/m/d', date('Y/m/d'));
        $time = date('H:i:s');

        if($time > '08:00:00' && $time < '24:00:00'){
            $response =  $this->telegram->sendMessage([
                'chat_id' => $this->chanel,
                'text' => '⏰ ساعت  '.$time.'📆 تاریخ  '.$date.PHP_EOL.PHP_EOL.$color.' نرخ لحظه ایی تتر : '.PHP_EOL.'🇮🇷 '.number_format($price_usdt_toman).' Toman'.PHP_EOL.PHP_EOL.
                    'تغییر:'.PHP_EOL.$color2.(($change<0?'+':'-').abs($change)).' Tom'.PHP_EOL.PHP_EOL.'🔅@Usd_iran'.PHP_EOL.PHP_EOL.'⭐️ اسپانسر : صرافی Arz8.com'.PHP_EOL.'🔅@Arz8com'
            ]);
            $messageId = $response->getMessageId();
            return $messageId;
        }
    }

    function priceDifference(){
        return false;
        $response = Http::post('https://api.nobitex.ir/market/stats', ['srcCurrency'=>'usdt','dstCurrency'=>'rls']);
        $result = (object)$response->throw()->json();
        if (isset($result->status) && $result->status == 'ok')
            $price_usdt_toman1 = round($result->stats['usdt-rls']['latest'] / 10);

        $response = Http::get('https://api.tetherland.com/currencies');
        $result = ($response->throw()->body());
        $result = json_decode($result);
        if (isset($result->status) && $result->status == 200)
            $price_usdt_toman2 = $result->data->currencies->USDT->price;

        $difference = abs($price_usdt_toman1 - $price_usdt_toman2);
        if ($difference > 300){
            $date = Jalali\CalendarUtils::strftime('Y/m/d', date('Y/m/d'));
            $time = date('H:i:s');
            $text = '⏰ ساعت  '.$time.PHP_EOL.'📆 تاریخ  '.$date.PHP_EOL.PHP_EOL.'⚠️ تفاوت قیمت نوبیتکس و تترلند: '.PHP_EOL.number_format($difference).' Toman'
                .PHP_EOL.PHP_EOL.'قیمت نوبیتکس: '.number_format($price_usdt_toman1).PHP_EOL.'قیمت تترلد: '.number_format($price_usdt_toman2).PHP_EOL;
            $response =  $this->telegram->sendMessage([
                'chat_id' => '@yones_saeedi12345',
                'text' =>$text
            ]);
            $messageId = $response->getMessageId();
            return $messageId;
        }
    }

}
