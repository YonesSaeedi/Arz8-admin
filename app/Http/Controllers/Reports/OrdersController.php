<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali;

class OrdersController extends Controller
{

    function chartData(Request $request){
        $result = (object)array();
        $result->pie_via = Orders::select('via')->selectRaw('count(id) as sum')->where('status','success')->where('id_user','!=',1)->groupBy('via')->get();
        $result->pie_via_amount = Orders::select('via as name')->selectRaw('round(sum(amount)) as value')->where('status','success')->where('id_user','!=',1)->groupBy('via')->get();


        $result->line_via = (object)array();
        $result->line_via_amount = (object)array();
        $result->line_via->lable = [];
        $result->line_via_amount->lable = [];
        $result->line_via->data = ['all'=>[],'android'=>[], 'ios'=>[], 'website'=>[]];
        $result->line_via_amount->data = ['all'=>[],'android'=>[], 'ios'=>[], 'website'=>[]];

        $result->bar_type = (object)array();
        $result->bar_type->lable = [];
        $result->bar_type->data = ['buy'=>[],'sell'=>[]];

        $six_months_ago = Jalali\Jalalian::now()->subMonths(12);
        $six_months_ago = $six_months_ago->subDays($six_months_ago->getDay() - 1);
        for ($i = 1; $i <= 12; $i++) {
            $month = $six_months_ago->addMonths($i);
            $date_start = Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d 00:00:00', $month->format('Y-m-d 00:00:00'));
            $date_stop = Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d 00:00:00', $month->addMonths(1)->format('Y-m-d 00:00:00'));
            $m = $month->format('F Y');
            array_push($result->line_via->lable,$m );
            array_push($result->line_via_amount->lable, $m);

            $orders_c = Orders::where('status','success')->where('id_user','!=',1)->where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->count();
            $orders_a = Orders::where('status','success')->where('id_user','!=',1)->where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->sum('amount');
            array_push($result->line_via->data['all'], $orders_c);
            array_push($result->line_via_amount->data['all'], round($orders_a));

            $orders_c = Orders::where('status','success')->where('id_user','!=',1)->where('created_at', '>=', $date_start)->where('via','android')->where('created_at', '<', $date_stop)->count();
            $orders_a = Orders::where('status','success')->where('id_user','!=',1)->where('created_at', '>=', $date_start)->where('via','android')->where('created_at', '<', $date_stop)->sum('amount');
            array_push($result->line_via->data['android'], $orders_c);
            array_push($result->line_via_amount->data['android'], round($orders_a));


            $orders_c = Orders::where('status','success')->where('id_user','!=',1)->where('created_at', '>=', $date_start)->where('via','ios')->where('created_at', '<', $date_stop)->count();
            $orders_a = Orders::where('status','success')->where('id_user','!=',1)->where('created_at', '>=', $date_start)->where('via','ios')->where('created_at', '<', $date_stop)->sum('amount');
            array_push($result->line_via->data['ios'], $orders_c);
            array_push($result->line_via_amount->data['ios'], round($orders_a));

            $orders_c = Orders::where('status','success')->where('id_user','!=',1)->where('created_at', '>=', $date_start)->where('via','website')->where('created_at', '<', $date_stop)->count();
            $orders_a = Orders::where('status','success')->where('id_user','!=',1)->where('created_at', '>=', $date_start)->where('via','website')->where('created_at', '<', $date_stop)->sum('amount');
            array_push($result->line_via->data['website'], $orders_c);
            array_push($result->line_via_amount->data['website'], round($orders_a));


            // Bar Type
            array_push($result->bar_type->lable, $m);
            $orders_buy = Orders::where('status','success')->where('id_user','!=',1)->where('type','buy')->where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->sum('amount');
            array_push($result->bar_type->data['buy'], round($orders_buy));
            $orders_sell = Orders::where('status','success')->where('id_user','!=',1)->where('type','sell')->where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->sum('amount');
            array_push($result->bar_type->data['sell'], round($orders_sell));
        }

        return $result;
    }
}
