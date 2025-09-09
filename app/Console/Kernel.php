<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Audit
        $schedule->command('audit:cust')->dailyAt('00:01');
        $schedule->command('audit:dailyCrypto')->dailyAt('00:03');
        $schedule->command('audit:dailyDigital')->dailyAt('00:03');
        $schedule->command('audit:wageTrade')->dailyAt('00:03');

        // Crypto
        $schedule->command('withdraw:crypto')->everyFiveMinutes();
        $schedule->command('disable:crypto')->everyFiveMinutes();
        $schedule->command('kucoin:transfer')->dailyAt('00:00');
        $schedule->command('confirm:depositCrypto')->everyFiveMinutes();
        $schedule->command('balance:cryptoSave')->everyFiveMinutes();


        $schedule->command('users:findCity')->dailyAt('06:00');
        $schedule->command('order:removeSuspend')->dailyAt('06:00');
        $schedule->command('level:account')->dailyAt('00:00');
        $schedule->command('portfolio:saveData')->dailyAt('00:00');
        $schedule->command('mines:balance')->everyFiveMinutes();
        $schedule->command('paystar:deposit')->everyTenMinutes();
        $schedule->command('paystar:withdrawTiming')->everyFiveMinutes();
        $schedule->command('baje:deposit')->everyThirtyMinutes();

        $BotAdminTelegram = 'App\Http\Controllers\Telegram\Admin\AdminController';
        $BotAdminPriceTelegram = 'App\Http\Controllers\Telegram\Admin\AdminPriceController';
        $schedule->call(function () use($BotAdminTelegram){
            //$sendTopCoins = app($BotAdminTelegram)->sendTopCoins();
        })->dailyAt('20:00');
        $schedule->call(function () use($BotAdminTelegram){
            $coin360 = app($BotAdminTelegram)->coin360();
        })->dailyAt('12:00');
        $schedule->call(function () use($BotAdminPriceTelegram){
            $priceUsdt = app($BotAdminPriceTelegram)->priceUsdt();
            $priceDifference = app($BotAdminPriceTelegram)->priceDifference();
        })->everyTwoMinutes();

        $schedule->call(function (){
            $little = new \App\Http\Controllers\Settings\CryptoLittleController();
            $little->trade();

            $wageTrade = new \App\Http\Controllers\Audit\WageController();
            $wageTrade->trade();
        })->everyFiveMinutes();

        $schedule->command('wallet:getAddress')->dailyAt('19:30');

        // Marketing
        $schedule->command('marketing:league')->dailyAt('00:01');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
