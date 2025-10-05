<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', 'AuthController@login');
Route::post('/login/2fa-google', 'AuthController@login2faGoogle');
Route::post('/login/2fa-sms', 'AuthController@login2faSms');
Route::post('/refresh-token', 'AuthController@refresh');
Route::post('/get-general-info', 'GeneralController@info');
Route::get('/settings/banner/image/{img}', 'Settings\SettingsController@imageViewBanner');
Route::get('/settings/stories/image/{img}', 'Settings\SettingsController@imageViewStories');
Route::get('/test', 'Dashboard\PricingContoller@test');
Route::get('/invoice/salesEndUser', 'GeneralController@invoice');
Route::get('/tbl1-download-csv', [\App\Http\Controllers\Reports\Shaparak\Table1Controller::class, 'downloadCsv']);
Route::get('/tbl2-download-csv', [\App\Http\Controllers\Reports\Shaparak\Table2Controller::class, 'downloadCsv']);
Route::get('/tbl3-download-csv', [\App\Http\Controllers\Reports\Shaparak\Table3Controller::class, 'downloadCsv']);
Route::get('/tbl4-download-csv', [\App\Http\Controllers\Reports\Shaparak\Table4Controller::class, 'downloadCsv']);


// File view
Route::get('image/{hash}/image.jpg', function(\Illuminate\Http\Request $request) {
    $controller = app('App\Http\Controllers\Controller');
    return $controller->imageView($request);
});
Route::get('image2/{hash}/image.jpg', function(\Illuminate\Http\Request $request) {
    $controller = app('App\Http\Controllers\Controller');
    return $controller->imageView2($request);
});

Route::group(['prefix' => 'v2','middleware' => ['XssSanitizer','throttle:60,1','verifySignature']], function() {
    Route::post('/login', 'AuthController@login');
    Route::post('/login/2fa-google', 'AuthController@login2faGoogle');
    Route::post('/login/2fa-sms', 'AuthController@login2faSms');
    Route::post('/refresh-token', 'AuthController@refresh');
    Route::post('/get-general-info', 'GeneralController@info');
    Route::get('/settings/banner/image/{img}', 'Settings\SettingsController@imageViewBanner');
    Route::get('/settings/stories/image/{img}', 'Settings\SettingsController@imageViewStories');
    Route::get('/test', 'Dashboard\PricingContoller@test');
    Route::get('/invoice/salesEndUser', 'GeneralController@invoice');

    // File view
    Route::get('image/{hash}/image.jpg', function(\Illuminate\Http\Request $request) {
        $controller = app('App\Http\Controllers\Controller');
        return $controller->imageView($request);
    });

    Route:: group(['middleware' => ['jwt.auth','auth:api','Access']], function () {
        // File view
        Route::get('image/{hash}', function(\Illuminate\Http\Request $request) {
            $controller = app('App\Http\Controllers\Controller');
            return $controller->imageView($request);
        });
        Route::get('image2/{hash}', function(\Illuminate\Http\Request $request) {
            $controller = app('App\Http\Controllers\Controller');
            return $controller->imageView2($request);
        });

        // Dashboard
        Route:: group(['namespace' => 'Dashboard'], function () {
            Route::post('/dashboard', 'DashboardContoller@getData');
            Route::post('/dashboard/price', 'PricingContoller@getPrice');
        });

        // Users
        Route:: group(['namespace' => 'Users'], function () {
            Route:: group(['namespace' => 'Kyc'], function () {
                Route::post('/users/edit/kyc/basic/{id}', 'KycBasicController@Basic');

                Route::post('/users/edit/kyc/advanced/{id}', 'KycAdvancedController@Advanced');
                Route::put('/users/edit/kyc/advanced/{id}/status', 'KycAdvancedController@Status');
            });
            // user
            Route::post('/users/list', 'UsersController@listUsers');
            Route::post('/users/list/statistic', 'UsersController@statistic');
            Route::post('/users/add-new', 'UsersController@addUser');
            Route::post('/users/info/{id}', 'EditController@getUserInfo');
            Route::post('/users/edit/level0/{id}', 'EditController@level0Edit');
            Route::post('/users/edit/level1/{id}', 'EditController@level1Edit');
            Route::post('/users/edit/level1/{id}/inquiry', 'EditController@inquiryMobileNationalCode');

            Route::post('/users/edit/level2/{id}', 'EditController@level2Edit');
            Route::get('/users/edit/level2/{id}/file', 'EditController@level2File');
            Route::put('/users/edit/level2/{id}/status', 'EditController@level2Status');

            Route::post('/users/edit/level3/{id}', 'EditController@level3Edit');
            Route::get('/users/edit/level3/{id}/file', 'EditController@level3File');
            Route::put('/users/edit/level3/{id}/status', 'EditController@level3Status');

            Route::post('/users/edit/level4/{id}', 'EditController@level4Edit');
            Route::get('/users/edit/level4/{id}/file', 'EditController@level4File');
            Route::put('/users/edit/level4/{id}/status', 'EditController@level4Status');

            Route::post('/users/edit/settings/{id}', 'EditController@settingsEdit');
            Route::put('/users/block/{id}', 'UsersController@block');
            Route::post('/users/note/{id}', 'UsersController@note');
            Route::delete('/users/remove/{id}', 'UsersController@remove');

            // Portfolio
            Route::post('/users/portfolio/{id}/', 'PortfolioController@portfolio');
            Route::post('/users/portfolio/{id}/statistic', 'PortfolioController@statistic');

            // Wallets
            Route::post('/users/wallets/{id}/list', 'WalletsController@getlistWallet');
            Route::post('/users/wallets/{symbol}/crypto-single', 'WalletsController@getSingleCryptoWallet');
            Route::post('/users/wallets/{id_wallet}/crypto-transaction', 'WalletsController@transactionCryptoWallet');
            Route::post('/users/wallets/{id_wallet}/crypto-balance-fixation', 'WalletsController@fixationCryptoWallet');
            Route::post('/users/wallets/{id_wallet}/internal-single', 'WalletsController@getSingleInternalWallet');
            Route::post('/users/wallets/{id_wallet}/internal-transaction', 'WalletsController@transactioneInternalWallet');


            // Card Bank
            Route::post('/card-bank/list', 'CardBankController@listCard');
            Route::post('/card-bank/info/{id}', 'CardBankController@singleCard');
            Route::post('/card-bank/edit/{id}', 'CardBankController@editCard');
            Route::post('/card-bank/status/{id}', 'CardBankController@statusCard');
            Route::post('/card-bank/inquiry/{id}/card', 'CardBankController@inquiryCard');
            Route::post('/card-bank/inquiry/{id}/iban', 'CardBankController@inquiryIban');

            // Deposit Id
            Route::post('/card-bank/deposit-id', 'CardBankController@listDepositId');

            // login
            Route::post('/login-history/list', 'LoginHistoryController@listLogin');
            Route::post('/login-history/info/{id}', 'LoginHistoryController@singleLogin');

            // Call
            Route::post('/call-history/list', 'CallHistoryController@listCall');
            Route::post('/call-history/add', 'CallHistoryController@addCall');
            Route::delete('/call-history/remove/{id}', 'CallHistoryController@removeCall');

            // Wallets Deposit Crypto
            Route::post('/wallets-crypto/list', 'WalletsCryptoDepositController@getlist');
            Route::delete('/wallets-crypto/{id}', 'WalletsCryptoDepositController@removeWallet');
        });

        // Orders
        Route:: group(['namespace' => 'Orders'], function () {
            Route::post('/orders/list', 'OrdersController@listOrders');
            Route::post('/orders/list/statistic', 'OrdersController@statistic');
            Route::post('/orders/info/{id}', 'OrdersController@singleOrder');
            Route::post('/orders/PMV/check', 'OrdersController@checkPMV');
        });

        // Trades
        Route:: group(['namespace' => 'Trades'], function () {
            Route::post('/trades/list', 'TradesController@listTrades');
            Route::post('/trades/list/statistic', 'TradesController@statistic');
            Route::post('/trades/info/{id}', 'TradesController@singleTrade');
            Route::post('/trades/status/{id}', 'TradesController@statusTrade');
        });

        // Tickets
        Route:: group(['namespace' => 'Tickets'], function () {
            Route::post('/tickets/list', 'TicketsController@listTickets');
            Route::post('/tickets/list/statistic', 'TicketsController@statistic');
            Route::post('/tickets/info/{id}', 'TicketsController@singleTicket');
            Route::post('/tickets/remove/{id}', 'TicketsController@removeTicket');
            Route::post('/tickets/close/{id}', 'TicketsController@closeTicket');
            Route::post('/tickets/{id}/new', 'TicketsController@ticketSingleInsert');

            Route::post('/tickets/pattern', 'TicketsController@newPattern');
            Route::post('/tickets/pattern/remove', 'TicketsController@removePattern');
        });

        // Gift
        Route:: group(['namespace' => 'Gift'], function () {
            Route::post('/gift/list', 'GiftController@listGift');
            Route::post('/gift/add-new', 'GiftController@addGift');
            Route::post('/gift/edit', 'GiftController@addGift');
            Route::delete('/gift/remove/{id}', 'GiftController@removeGift');
            Route::post('/gift/statistic', 'GiftController@statistic');
            Route::post('/gift/info/{id}', 'GiftController@singleGift');
            Route::post('/gift/users/list', 'GiftController@listUsers');
            Route::delete('/gift/users/remove/{id}', 'GiftController@removeUsers');

            Route::post('/gift/wheel/list', 'WheelController@listWheel');
            Route::post('/gift/wheel/statistic', 'WheelController@statisticWheel');
            Route::delete('/gift/wheel/remove/{id}', 'WheelController@removeWheel');

            Route::post('/gift/card/list', 'GiftCardController@listGift');
            Route::post('/gift/card/add-new', 'GiftCardController@addGift');
            Route::post('/gift/card/info/{id}', 'GiftCardController@singleGift');
            Route::delete('/gift/card/remove/{id}', 'GiftCardController@removeGift');
            Route::post('/gift/card/statistic', 'GiftCardController@statistic');
        });

        // Transaction
        Route:: group(['namespace' => 'Transaction'], function () {
            // Internal
            Route::post('/internal/list', 'InternalController@listInternal');
            Route::post('/internal/info/{id}', 'InternalController@singleInternal');
            Route::post('/internal/confirm/group', 'InternalController@confirmGroupInternal');
            Route::post('/internal/export', 'InternalController@exportXls');
            Route::post('/internal/confirm/{id}', 'InternalController@confirmInternal');
            Route::post('/internal/reject/{id}', 'InternalController@rejectInternal');
            Route::post('/internal/verify/{id}', 'InternalController@verifyInternal');
            Route::post('/internal/inquiry-receipt/{id}', 'InternalController@inquiryReceipt');

            // Withdraw
            Route::post('/withdraw/list', 'GatewayWithdrawController@listWithdraw');
            Route::post('/withdraw/info/{id}', 'GatewayWithdrawController@singleWithdraw');
            Route::post('/withdraw/add/balance', 'GatewayWithdrawController@balance');
            Route::post('/withdraw/add/ibanInquiry', 'GatewayWithdrawController@ibanInquiry');
            Route::post('/withdraw/add', 'GatewayWithdrawController@withdraw');

            // crypto
            Route::post('/crypto/list', 'CryptoController@listCrypto');
            Route::post('/crypto/info/{id}', 'CryptoController@singleCrypto');
            Route::post('/crypto/confirm/{id}', 'CryptoController@confirmCrypto');
            Route::post('/crypto/reject/{id}', 'CryptoController@rejectCrypto');
            Route::post('/crypto/remove/{id}', 'CryptoController@removeCrypto');
        });

        // Settings
        Route:: group(['namespace' => 'Settings','prefix' => 'setting'], function () {
            // crypto
            Route::post('/crypto/auto-trade', 'CryptoAutoTradeController@listTrade');
            Route::post('/crypto/little', 'CryptoLittleController@listLittle');
            Route::delete('/crypto/little/{id}', 'CryptoLittleController@removeLittle');
            Route::post('/crypto/wallets', 'CryptoWalletsController@listWallets');
            Route::post('/crypto/balance', 'CryptoController@listCrypto');
            Route::post('/crypto/balance-total', 'CryptoController@totalBalance');
            Route::post('/crypto/list', 'CryptoController@listCrypto');
            Route::post('/crypto/info/{id}', 'CryptoController@singleCrypto');
            Route::post('/crypto/new', 'CryptoController@newCrypto');
            Route::post('/crypto/edit/{id}', 'CryptoController@editCrypto');
            Route::post('/crypto/balance/{id}', 'CryptoController@balanceUsers');
            Route::post('/crypto/withdraw/{id}', 'CryptoController@withdraw');
            Route::post('/crypto/trade/{id}', 'CryptoController@trade');
            Route::post('/crypto/all-balance/{id}', 'CryptoController@changeAllBalance');
            Route::post('/crypto/fit-balance/{id}', 'CryptoController@fitBalance');
            Route::post('/crypto/fit-balance-gruop', 'CryptoController@fitBalanceGroup');

            // network
            Route::post('/network/list', 'NetworkController@listNetwork');
            Route::post('/network/new', 'NetworkController@newNetwork');
            Route::post('/network/edit/{id}', 'NetworkController@editNetwork');
            Route::delete('/network/remove/{id}', 'NetworkController@removeNetwork');

            // markets
            Route::post('/markets/list', 'MarketsController@listMarkets');
            Route::post('/markets/new', 'MarketsController@newMarket');
            Route::post('/markets/info/{id}', 'MarketsController@singleMarket');
            Route::post('/markets/edit/{id}', 'MarketsController@editMarket');
            Route::delete('/markets/remove/{id}', 'MarketsController@removeMarket');

            // settings
            Route::post('/settings', 'SettingsController@getSettings');
            Route::post('/settings/edit', 'SettingsController@editSettings');
            Route::post('/settings/gateway', 'SettingsController@gateway');
            Route::post('/settings/auto-deposit', 'SettingsController@autoDeposit');
            Route::post('/settings/banner', 'SettingsController@banner');
            Route::post('/settings/stories', 'SettingsController@stories');

            // notification
            Route::post('/notification/list', 'NotificationController@listNotification');
            Route::post('/notification/send', 'NotificationController@sendNotif');
            Route::post('/notification/info/{id}', 'NotificationController@singledNotif');
            Route::post('/notification/edit/{id}', 'NotificationController@editNotif');
            Route::post('/notification/remove/{id}', 'NotificationController@removeNotif');

        });

        // audit
        Route:: group(['namespace' => 'Audit'], function () {
            // wage
            Route::post('/audit/wage', 'WageController@listWageCrypto');
            Route::post('/audit/wage/statistic', 'WageController@statistic');
            Route::post('/audit/wage/table-calculate', 'WageController@tableCalculate');
            Route::post('/audit/wage/{id}', 'WageController@singleWageCrypto');

            // orders
            Route::post('/audit/orders', 'OrdersController@listOrders');
            Route::post('/audit/orders/calculation', 'OrdersController@calculationTable');

            // daily
            Route::post('/audit/daily', 'DailyController@listAudit');
            Route::post('/audit/daily/add-new', 'DailyController@addOrder');

            //cust
            Route::post('/audit/cust', 'CustController@listCust');
            Route::post('/audit/cust/add-new', 'CustController@addCust');
            Route::delete('/audit/cust/remove/{id}', 'CustController@removeCust');
            Route::get('/audit/cust/file/{id}', 'CustController@fileCust');

            // factor
            Route::post('/audit/factor', 'FactorController@listFactor');
            Route::post('/audit/factor/table-calculate', 'FactorController@tableCalculate');
            Route::post('/audit/factor/{id}', 'FactorController@getFactor');
            Route::post('/audit/export-gov', 'FactorController@govExport');

        });

        // referral
        Route:: group(['namespace' => 'Referral'], function () {
            Route::post('/referral/users', 'ReferralController@listUsers');
            Route::post('/referral/transaction', 'ReferralController@listTransaction');
            Route::post('/referral/users/statistic', 'ReferralController@statistic');
        });

        // Reports
        Route:: group(['namespace' => 'Reports','prefix' => 'reports'], function () {
            Route::post('/users/list', 'UsersController@listUsers');
            Route::post('/users/chart', 'UsersController@chartData');

            Route::post('/orders/chart', 'OrdersController@chartData');
            Route::post('/trades/chart', 'TradesController@chartData');
            Route::post('/referral/chart', 'ReferralController@chartData');
            Route::post('/payment-gateway', 'PaymentGatewayController@listInternal');
            Route::post('/payment-gateway/add-tr', 'PaymentGatewayController@addTran');

            Route::post('/shaparak/tbl1-download-csv', 'Shaparak\Table1Controller@downloadCsv');
            Route::post('/shaparak/tbl2-download-csv', 'Shaparak\Table2Controller@downloadCsv');
            Route::post('/shaparak/tbl3-download-csv', 'Shaparak\Table3Controller@downloadCsv');
            Route::post('/shaparak/tbl4-download-csv', 'Shaparak\Table4Controller@downloadCsv');
        });

        // Admins
        Route:: group(['namespace' => 'Admins'], function () {
            // profile
            Route::post('/profile/edit', 'ProfileController@edit');
            Route::post('/profile/2fa', 'ProfileController@twoFa');
            Route::post('/profile/2fa/info', 'ProfileController@twoFaInfo');

            // admins
            Route::post('/admins/list', 'AdminsController@listUsers');
            Route::post('/admins/add-new', 'AdminsController@addAdmin');
            Route::post('/admins/admin/{id}', 'AdminsController@singleAdmin');
            Route::delete('/admins/admin/hesab/{id}', 'AdminsController@removeHesab');
            Route::post('/admins/admin/edit/{id}', 'AdminsController@editAdmin');
            Route::post('/admins/logs/list', 'AdminsController@listLog');
            Route::post('/admins/logs/info/{id}', 'AdminsController@singleLog');
        });
    });
});

// Telegram Bot
Route::group(['namespace' => 'Telegram','prefix' => 'telegram'], function($router) {
    // Admin
    $router->group(['namespace' => 'Admin','prefix' => 'admin'], function($router) {
        $router->get('send-top-coins', 'AdminController@sendTopCoins');
    });
});



