<?php

namespace App\Http\Controllers\Reports\Shaparak;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use Illuminate\Http\Request;
use Morilog\Jalali\CalendarUtils;
use Illuminate\Support\Facades\Response;

class Table3Controller extends Controller
{
    const HEADERS = [
        'F1','F2','F3','F4','F5','F6','F7','F8','F9','F10','F11',
    ];

    public function downloadCsv(Request $request)
    {
        if (isset($request->dateStart)) {
            try{
                $dateStart = CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
            }catch(\Exception $e){}
        }


        $callback = function () use ($dateStart,$dateStop){
            $file = fopen('php://output', 'w');
            fputcsv($file, self::HEADERS);

            // Join میکنیم که اطلاعات کاربر و کریپتو یکجا بیاد
            Orders::query()
                ->select(
                    'orders.*',
                    'users.national_code',
                    'cryptocurrency.symbol as crypto_symbol'
                )
                ->join('users', 'users.id', '=', 'orders.id_user')
                ->leftjoin('cryptocurrency', 'cryptocurrency.id', '=', 'orders.id_crypto')
                ->where('orders.status', 'success')
                ->where('orders.id_user', '!=', 1)
                //->whereNotNull('orders.id_crypto')
                ->whereBetween('orders.created_at', [
                    isset($dateStart)? $dateStart : now()->subDay()->startOfDay(),
                    isset($dateStop)? $dateStop : now()->startOfDay()
                ])
                ->orderBy('orders.id')
                ->chunk(1000, function ($orders) use ($file) {
                    foreach ($orders as $order) {
                        $isBuy = $order->type === 'buy';

                        fputcsv($file, [
                            'azar',                                        // F1
                            $isBuy ? '01' : '02',                           // F2
                            $isBuy ? 'IR' : $order->crypto_symbol??'USDT',          // F3
                            !$isBuy ? 'IR' : $order->crypto_symbol??'USDT',         // F4
                            $order->created_at->format('His'),              // F5
                            $order->created_at->format('Ymd'),              // F6
                            '10',                                           // F7
                            $order->national_code,                         // F8
                            $isBuy ? $order->amount * 10 : $order->amount_coin,  // F9
                            !$isBuy ? $order->amount * 10 : $order->amount_coin,  // F10
                            0,                                               // F11
                        ]);
                    }
                });

            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=customer_orders_'.CalendarUtils::strftime('Y-m-d', now()->subDay()).'.csv',
        ]);
    }
}
