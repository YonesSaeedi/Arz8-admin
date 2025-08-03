<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Cryptocurrency;
use App\Models\GiftWheel;
use App\Models\WalletsCrypto;
use Illuminate\Http\Request;
use Morilog\Jalali;
use App\Models\User;
use App\Models\Orders;
use App\Models\Trades;
use App\Models\WalletsInternal;
use Illuminate\Support\Facades\Cache;
use Spatie\Async\Pool;

class DashboardContoller extends Controller
{
    public function getData(Request $request)
    {
        if(\Auth::user()->id == 22)
            abort(404);

        if (!$request->fast && Cache::has('a_dashboard_report')) {
            return response()->json(Cache::get('a_dashboard_report'));
        }

        // ایجاد یک آبجکت برای ذخیره نتایج
        $result = new \stdClass();
        $result->statistics = new \stdClass(); // اطمینان حاصل کردن از وجود statistics
        $result->statistics->users = 0; // مقداردهی اولیه به `users`

        // آمار کاربران و سفارش‌ها
        $statistics = new \stdClass();
        $statistics->users = User::count(); // محاسبه تعداد کاربران
        $statistics->orders = Orders::where('status', 'success')->where('id_user', '!=', 1)->count();
        $statistics->trades = Trades::where('status', 'success')->count();
        $statistics->balance_toman = round(WalletsInternal::sum('value_num'));
        $statistics->balance_theter = WalletsCrypto::where('id_crypto', 5)->sum('value_num');
        $result->statistics = $statistics;


        // chart via
        $two_months_ago = Jalali\Jalalian::now()->subDays(60);
        $one_months_ago = Jalali\Jalalian::now()->subDays(30);
        $date_start = Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d 00:00:00', $two_months_ago->format('Y-m-d 00:00:00'));
        $date_stop = Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d 00:00:00', $one_months_ago->format('Y-m-d 00:00:00'));
        $chart_user_via = new \stdClass();
        $chart_user_via->users_last_days = User::where('created_at', '>=', $date_stop)->count();
        $chart_user_via->users_ago_days = User::where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->count();
        $chart_user_via->data = User::select('info->register_via as via')
            ->whereNotNull('info->register_via')
            ->selectRaw('round((count(*) * 100) / ' .  $statistics->users  . ') as percent')
            ->groupBy('via')->get();
        $result->chart_user_via =$chart_user_via;



        $cryptos = Cryptocurrency::query();
        $cryptos->leftJoin('users_wallets_crypto', 'users_wallets_crypto.id_crypto', 'cryptocurrency.id');
        $cryptos->where('value_num', '>', 0);
        $cryptos->selectRaw('SUM(ROUND(users_wallets_crypto.value_num * JSON_EXTRACT(cryptocurrency.data, "$.price_usdt"), 4)) as balance_usdt');
        $cryptos = $cryptos->first();
        $result->statistics->all_balance_usdt = $cryptos->balance_usdt ?? 0;
        $result->statistics->time = time() * 1000;


        $minChart = $this->minChart();
        $result->chart_min_order = $minChart->chart_min_order;
        $result->chart_min_trade = $minChart->chart_min_trade;


        $result->chart_type = $this->getDataChartType();

        $result->report_table = $this->reportTable();



        if (isset($result->report_table))
            Cache::forever('a_dashboard_report', $result);

        return response()->json((object)$result);
    }



    function minChart(){
        $result = (object)[];

        $six_months_ago = Jalali\Jalalian::now()->startOfMonth()->subMonths(6);
        $months = [];
        for ($i = 1; $i <= 6; $i++) {
            $month = $six_months_ago->addMonths($i);


            $start_of_month = $month->startOfMonth()->toCarbon()->format('Y-m-d 00:00:00');
            $end_of_month = $month->endOfMonth()->toCarbon()->format('Y-m-d 23:59:59');
            $months[] = [
                'start' => $start_of_month,
                'end' => $end_of_month,
                'label' => $month->format('F Y'), // ماه و سال شمسی
            ];

            if($i == 6)
                $last_label = $month->format('F Y');
        }

        $orderData = [];
        $tradeData = [];
        $labels = [];

        foreach ($months as $month) {
            // کلید کش برای این ماه خاص
            $cacheKey = 'chart_data_' . $month['label'];

            // بررسی کش برای داده‌ها
            $cachedData = Cache::get($cacheKey);

            if ($cachedData && $month['label'] != $last_label) {
                // اگر داده‌ها در کش هستند، استفاده از کش
                $orders = $cachedData['orders'];
                $trades = $cachedData['trades'];
            } else {
                // اگر داده‌ها در کش نیستند، کوئری اجرا و ذخیره در کش
                $orders = Orders::whereBetween('created_at', [$month['start'], $month['end']])
                    ->where('status', 'success')
                    ->where('id_user', '!=', 1)
                    ->count();

                $trades = Trades::whereBetween('created_at', [$month['start'], $month['end']])
                    ->where('status', 'success')
                    ->count();

                // ذخیره داده‌ها در کش برای 1 روز (مدت زمان انقضاء کش)
                Cache::put($cacheKey, ['orders' => $orders, 'trades' => $trades], now()->addMonths(6));
            }

            // افزودن داده‌ها به آرایه‌ها
            $orderData[] = $orders;
            $tradeData[] = $trades;
            $labels[] = $month['label'];
        }


        $result->chart_min_order = (object)[
            'orders' => array_sum($orderData),
            'lable' => $labels,
            'data' => $orderData,
        ];

        $result->chart_min_trade = (object)[
            'trades' => array_sum($tradeData),
            'lable' => $labels,
            'data' => $tradeData,
        ];

        return $result;
    }

    function getDataChartType() {
        $chart_type = (object)[];

        // محاسبه سفارش‌ها و معاملات برای امروز (مقایسه شده در یک کوئری)
        $chart_type->orders_tomans_today = Orders::whereBetween('created_at', [now()->startOfDay(),  now()->endOfDay()])
            ->where('status', 'success')
            ->where('id_user', '!=', 1)
            ->sum('amount');

        $chart_type->trades_tomans_today = 0;
        $trades_today = Trades::whereBetween('created_at', [now()->startOfDay(),  now()->endOfDay()])
            ->where('status', 'success')
            ->leftJoin('cryptocurrency', 'cryptocurrency.id', '=', 'trades.id_baseAsset')
            ->selectRaw('SUM(trades.amount_base * JSON_UNQUOTE(JSON_EXTRACT(cryptocurrency.data, "$.price_usdt"))) as total_price')
            ->first();

        $chart_type->trades_tomans_today = $trades_today->total_price ?? 0;

        // 3. لیبل ماه‌ها و روزها
        $chart_type->lable = ['months' => [], 'days' => []];
        $chart_type->orders = ['months' => ['buy' => [], 'sell' => []], 'days' => ['buy' => [], 'sell' => []]];
        $chart_type->trades = ['months' => ['buy' => [], 'sell' => []], 'days' => ['buy' => [], 'sell' => []]];

        // 4. دریافت لیبل ماه‌ها برای 12 ماه گذشته و ماه جاری
        $six_months_ago = Jalali\Jalalian::now()->subMonths(12);
        for ($i = 1; $i <= 12; $i++) {
            $month = $six_months_ago->addMonths($i);
            $chart_type->lable['months'][] = $month->format('F');


            $start_of_month = $month->startOfMonth()->toCarbon()->format('Y-m-d 00:00:00');
            $end_of_month = $month->endOfMonth()->toCarbon()->format('Y-m-d 23:59:59');
            $months[] = [
                'start' => $start_of_month,
                'end' => $end_of_month,
                'label' => $month->format('F'),
            ];

            if($i == 12)
                $last_label = $month->format('F');
        }

        foreach ($months as $month) {
            // کلید کش برای این ماه خاص
            $cacheKey = 'chart_type_data_' . $month['label'];

            // بررسی کش برای داده‌ها
            $cachedData = Cache::get($cacheKey);

            if ($cachedData && $month['label'] != $last_label) {
                // اگر داده‌ها در کش هستند، استفاده از کش
                $orders = $cachedData['orders'];
                $trades = $cachedData['trades'];
            } else {
                // اگر داده‌ها در کش نیستند، کوئری اجرا و ذخیره در کش
                $orders = Orders::whereBetween('created_at', [$month['start'], $month['end']])
                    ->where('status', 'success')
                    ->where('id_user', '!=', 1)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type');
                $trades = Trades::whereBetween('created_at', [$month['start'], $month['end']])
                    ->where('status', 'success')
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type');

                // ذخیره داده‌ها در کش برای 1 روز (مدت زمان انقضاء کش)
                Cache::put($cacheKey, ['orders' => $orders, 'trades' => $trades], now()->addMonths(6));
            }


            // افزودن داده‌ها به آرایه‌ها
            array_push($chart_type->orders['months']['buy'], $orders['buy']);
            array_push($chart_type->orders['months']['sell'], $orders['sell'] * -1);
            array_push($chart_type->trades['months']['buy'], $trades['buy']??0);
            array_push($chart_type->trades['months']['sell'], $trades['sell']??0 * -1);
        }


        // تعیین تاریخ 14 روز گذشته
        $one_month_ago = Jalali\Jalalian::now()->subDays(14);
// ایجاد یک آرایه برای ذخیره داده‌های روزانه
        $days_data = Orders::whereBetween('created_at', [$one_month_ago->addDays()->startOfDay()->toCarbon(), now()->endOfDay()])
            ->where('status', 'success')
            ->where('id_user', '!=', 1)
            ->selectRaw('DAY(created_at) as day, COUNT(*) as count, type')
            ->groupBy('day', 'type')
            ->get();

        $trades_data = Trades::whereBetween('created_at', [$one_month_ago->addDays()->startOfDay()->toCarbon(), now()->endOfDay()])
            ->where('status', 'success')
            ->selectRaw('DAY(created_at) as day, COUNT(*) as count, type')
            ->groupBy('day', 'type')
            ->get();

// مقداردهی به آرایه‌ها برای ذخیره نتایج
        $chart_type->orders['days']['buy'] = [];
        $chart_type->orders['days']['sell'] = [];
        $chart_type->trades['days']['buy'] = [];
        $chart_type->trades['days']['sell'] = [];

        for ($i = 1; $i <= 14; $i++) {
            $day = $one_month_ago->addDays($i);
            $chart_type->lable['days'][] = $day->format('d');

            // پیدا کردن داده‌های روز مورد نظر برای سفارشات
            $orders_buy = $days_data->where('type', 'buy')->slice($i-1, 1)->sum('count');
            $orders_sell = $days_data->where('type', 'sell')->slice($i-1, 1)->sum('count');

            $chart_type->orders['days']['buy'][] = $orders_buy;
            $chart_type->orders['days']['sell'][] = $orders_sell * -1;

            // پیدا کردن داده‌های روز مورد نظر برای معاملات
            $trades_day = $trades_data->where('day', $i);
            $trades_buy = $trades_day->where('type', 'buy')->sum('count');
            $trades_sell = $trades_day->where('type', 'sell')->sum('count');

            $chart_type->trades['days']['buy'][] = $trades_buy;
            $chart_type->trades['days']['sell'][] = $trades_sell * -1;
        }

        return $chart_type;
    }



    function reportTable(){
        $report_table = (object)[];
        $report_table->today = self::GetDetailGeneral(date('Y-m-d 00:00:00'),date('Y-m-d 00:00:00',strtotime('+1 day')));
        $report_table->yesterday = self::GetDetailGeneral(date('Y-m-d 00:00:00',strtotime('-1 day')),date('Y-m-d 00:00:00'));

        $Saturday = Jalali\Jalalian::forge('last saturday')->format('Y/m/d H:i:s');
        $SaturdayStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', $Saturday);
        $Saturday = Jalali\Jalalian::forge('saturday')->addDays(7)->format('Y/m/d H:i:s');
        $SaturdayStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', $Saturday);
        $report_table->week = self::GetDetailGeneral($SaturdayStart,$SaturdayStop);

        $MonthStart = Jalali\Jalalian::forge('now')->format('Y/m/01 00:00:00');
        $MonthStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', $MonthStart);
        $MonthStop = Jalali\Jalalian::forge('now')->addMonths(1)->format('Y/m/01 00:00:00');
        $MonthStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', $MonthStop);
        $report_table->month = self::GetDetailGeneral($MonthStart,$MonthStop);

        $YearStart = Jalali\Jalalian::forge('now')->format('Y/01/01 00:00:00');
        $YearStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', $YearStart);
        $YearStop = Jalali\Jalalian::forge('now')->addYears(1)->format('Y/01/01 00:00:00');
        $YearStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', $YearStop);
        $report_table->year = self::GetDetailGeneral($YearStart,$YearStop);
        return $report_table;
    }

    public function GetDetailGeneral($DateStart,$dateStop){
        $result = array();
        $order = Orders::whereBetween('created_at', [$DateStart, $dateStop])
            ->where('status', 'success')
            ->where('id_user', '!=', 1)
            ->selectRaw('
                COUNT(*) as CountOrders,
                COALESCE(ROUND(SUM(amount)), 0) as AmountOrders,
                COALESCE(ROUND(MAX(amount)), 0) as MaxAmount,
                COUNT(DISTINCT id_user) as UniqueUsers,

                COUNT(IF(type = "sell", 1, NULL)) as CountOrdersSell,
                COALESCE(ROUND(SUM(IF(type = "sell", amount, 0))), 0) as AmountOrdersSell,
                COALESCE(ROUND(MAX(IF(type = "sell", amount, NULL))), 0) as AmountMaxSell,


                COUNT(IF(type != "sell", 1, NULL)) as CountOrdersBuy,
                COALESCE(ROUND(SUM(IF(type != "sell", amount, 0))), 0) as AmountOrdersBuy,
                COALESCE(MAX(IF(type != "sell", amount, NULL)), 0) as AmountMaxBuy,

                COUNT(IF(id_crypto IS NOT NULL, 1, NULL)) as CountOrdersCrypto,
                COALESCE(ROUND(SUM(IF(id_crypto IS NOT NULL, amount, 0))), 0) as AmountOrdersCrypto,

                COUNT(IF(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) = "PerfectMoney" AND id_crypto IS NULL, 1, NULL)) as CountOrdersPerfoctmoney,
                COALESCE(ROUND(SUM(IF(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) = "PerfectMoney" AND id_crypto IS NULL, amount, 0))), 0) as AmountOrdersPerfoctmoney,

                COUNT(IF(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) = "PMvoucher" AND id_crypto IS NULL, 1, NULL)) as CountOrdersPMvoucher,
                COALESCE(ROUND(SUM(IF(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) = "PMvoucher" AND id_crypto IS NULL, amount, 0))), 0) as AmountOrdersPMvoucher,

                COUNT(IF(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) = "PSVouchers" AND id_crypto IS NULL, 1, NULL)) as CountOrdersPSVouchers,
                COALESCE(ROUND(SUM(IF(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) = "PSVouchers" AND id_crypto IS NULL, amount, 0))), 0) as AmountOrdersPSVouchers,

                COUNT(IF(id_crypto IS NULL, 1, NULL)) as CountOrdersDigital,
                COALESCE(ROUND(SUM(IF(id_crypto IS NULL, amount, 0))), 0) as AmountOrdersDigital
            ')
            ->first()
            ->toArray();

        $result = array_merge($result, $order);



        $result['CountUser'] = User::where('created_at', '>=',$DateStart)->where('created_at', '<=',$dateStop)->count();
        $result['CountTrades'] = Trades::where('created_at', '>=',$DateStart)->where('created_at', '<=',$dateStop)->where('status','success')->count();

        $trades = Trades::query();
        $trades->where('status', 'success')->where('created_at', '>=',$DateStart)->where('created_at', '<=',$dateStop);
        $trades->leftJoin('cryptocurrency','cryptocurrency.id','trades.id_baseAsset');
        $trades->selectRaw('SUM(JSON_UNQUOTE(JSON_EXTRACT(cryptocurrency.data, "$.price_usdt")) * amount_base ) as sum_trades_usdt');
        $result['AmountTrades'] = $trades->first()->sum_trades_usdt??0;
        $result['CountWheel'] = GiftWheel::where('created_at', '>=',$DateStart)->where('created_at', '<=',$dateStop)->count();
        return (object)$result;
    }

}
