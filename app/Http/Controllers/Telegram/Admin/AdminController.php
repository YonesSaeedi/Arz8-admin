<?php

namespace App\Http\Controllers\Telegram\Admin;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

class AdminController
{
    private $key = '5519467988:AAHnqeh0X1NBKmhC5wXzGTTJvz7cfMnkvGc';
    private $chanel = /*'@test_arz8';//*/'@arz8com';
    private $telegram;
    function __construct(){
        $this->telegram = new Api($this->key);
    }

    function sendTopCoins(){
        $time = time();
        $params = array(
            "access_key" => "28d316feb2ab400084983b7174775773",
            "url" => "https://arz8.com/price-cryptocurrencies",
            "format" => "png",
            "quality" => "".rand(90,100),
            "delay" => "".rand(2,10),
            "crop" => "300,0,810,785",
            "element" => ".main-table",
            "js" => "document.querySelector('#coin-table-holder').getElementsByTagName('tr')[12].remove();document.querySelector('#coin-table-holder').getElementsByTagName('tr')[13].remove();document.querySelector('#coin-table-holder').getElementsByTagName('tr')[14].remove();",
        );
        $topCoins = Http::withHeaders(['Content-Type'=>'application/json'])->post('https://app.arz8.com/api/v1/landing/top-coins');
        try{
            $topCoins = (object)$topCoins->json();
        } catch(\Exception $e){
            return (object) array('status' => false,'msg' => ($e->getMessage()));
        }
        //dd($topCoins->loss[0]['priceChangePercent']);

        // profit
        $profit = $params;
        $profit["js"] = "document.querySelector('.fillterbtn').getElementsByTagName('div')[1].click();".$profit["js"];
        $image_data = file_get_contents("https://api.apiflash.com/v1/urltoimage?" . http_build_query($profit));
        file_put_contents(storage_path()."/screenshot-profit".$time.".png", $image_data);

        $inputFile = InputFile::create(storage_path().'/screenshot-profit'.$time.'.png', 'screenshot-profit'.$time.'.png');
        $response =  $this->telegram->sendPhoto([
            'chat_id' =>  $this->chanel,
            'photo' => $inputFile,
            'caption' => '✅ لیست پُر سودترین ارز های صرافی ارزهشت در 24 ساعت گذشته
1. '.str_replace('USDT','',$topCoins->profit[0]['symbol']).'                   %'.$topCoins->profit[0]['priceChangePercent'].'
2. '.str_replace('USDT','',$topCoins->profit[1]['symbol']).'                   %'.$topCoins->profit[1]['priceChangePercent'].'
3. '.str_replace('USDT','',$topCoins->profit[2]['symbol']).'                   %'.$topCoins->profit[2]['priceChangePercent'].'

Arz8.com'
        ]);


        // loss
        $loss = $params;
        $loss["js"] = "document.querySelector('.fillterbtn').getElementsByTagName('div')[2].click();".$loss["js"];
        $image_data = file_get_contents("https://api.apiflash.com/v1/urltoimage?" . http_build_query($loss));
        file_put_contents(storage_path()."/screenshot-loss".$time.".png", $image_data);

        $inputFile = InputFile::create(storage_path().'/screenshot-loss'.$time.'.png', 'screenshot-loss'.$time.'.png');
        $response =  $this->telegram->sendPhoto([
            'chat_id' => $this->chanel,
            'photo' => $inputFile,
            'caption' => '❎ لیست پُر ضررترین ارز های صرافی ارزهشت در 24 ساعت گذشته
1. '.str_replace('USDT','',$topCoins->loss[0]['symbol']).'                   %'.$topCoins->loss[0]['priceChangePercent'].'
2. '.str_replace('USDT','',$topCoins->loss[1]['symbol']).'                   %'.$topCoins->loss[1]['priceChangePercent'].'
3. '.str_replace('USDT','',$topCoins->loss[2]['symbol']).'                   %'.$topCoins->loss[2]['priceChangePercent'].'

Arz8.com'
        ]);

        $messageId = $response->getMessageId();


        unlink(storage_path().'/screenshot-loss'.$time.'.png');
        unlink(storage_path().'/screenshot-profit'.$time.'.png');
        return $messageId;

    }


    function coin360(){
        $time = time();
        $params = array(
            "access_key" => "fee4864be29c44fdb87a6a226516adb5",
            "url" => "https://coin360.com/widget/map?utm_source=embed_map&v=" . $time, // پارامتر ضد کش
            "format" => "png",
            "quality" => "".rand(90,100),
            "delay" => "3",
            "fresh" => "true", // از داکیومنت apiflash
            "js" => "document.querySelector('section:first-child').remove();document.querySelectorAll('aside').forEach(el => el.remove());",
        );

        $image_data = file_get_contents("https://api.apiflash.com/v1/urltoimage?" . http_build_query($params));
        $fileName = storage_path()."/coin360_".$time.".png";
        file_put_contents($fileName, $image_data);

        $inputFile = InputFile::create($fileName, 'coin360_'.$time.'.png');
        sleep(2);

        $response =  $this->telegram->sendPhoto([
            'chat_id' => $this->chanel,
            'photo' => $inputFile,
            'caption' => "📈 هم اکنون وضعیت بازار رمز ارز\n".$this->chanel."\nArz8.com"
        ]);

        sleep(1);
        unlink($fileName);
        return $response ?? 'o';
    }


    function test(){
        $this->telegram->sendMessage([
            'chat_id' => 128610659,
            'text' => __('Verification code:').PHP_EOL. "`". 1234 ."`",
            'parse_mode' => 'MarkdownV2',
        ]);
    }
}
