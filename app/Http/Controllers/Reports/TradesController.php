<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Trades;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali;

class TradesController extends Controller
{

    function chartData(Request $request){
        $result = (object)array();
        $result->pie_via = Trades::select('via')->selectRaw('count(id) as sum')->where('status','success')->groupBy('via')->get();
        $result->pie_model = Trades::select('model as name')->selectRaw('count(id) as value')->where('status','success')->groupBy('model')->get();


        $result->line_via = (object)array();
        $result->line_via->lable = [];
        $result->line_via->data = ['all'=>[],'android'=>[], 'ios'=>[], 'website'=>[]];

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

            $orders_c = Trades::where('status','success')->where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->count();
            array_push($result->line_via->data['all'], $orders_c);

            $orders_c = Trades::where('status','success')->where('created_at', '>=', $date_start)->where('via','android')->where('created_at', '<', $date_stop)->count();
            array_push($result->line_via->data['android'], $orders_c);


            $orders_c = Trades::where('status','success')->where('created_at', '>=', $date_start)->where('via','ios')->where('created_at', '<', $date_stop)->count();
            array_push($result->line_via->data['ios'], $orders_c);

            $orders_c = Trades::where('status','success')->where('created_at', '>=', $date_start)->where('via','website')->where('created_at', '<', $date_stop)->count();
            array_push($result->line_via->data['website'], $orders_c);


            // Bar Type
            array_push($result->bar_type->lable, $m);
            $orders_buy = Trades::where('status','success')->where('id_user','!=',1)->where('type','buy')->where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->count();
            array_push($result->bar_type->data['buy'], round($orders_buy));
            $orders_sell = Trades::where('status','success')->where('id_user','!=',1)->where('type','sell')->where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->count();
            array_push($result->bar_type->data['sell'], round($orders_sell));
        }

        return $result;
    }
}
