<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Orders;
use App\Models\ReferralTransaction;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali;

class ReferralController extends Controller
{

    function chartData(Request $request){
        $result = (object)array();
        $result->pie_percent_caller = UserReferral::select('percent_caller as name')->selectRaw('count(id) as value')->groupBy('percent_caller')->get();
        $result->pie_percent_referral = UserReferral::select('percent_referral as name')->selectRaw('count(id) as value')->groupBy('percent_referral')->get();


        $result->line_time = (object)array();
        $result->line_time->lable = [];
        $result->line_time->data = ['amount'=>[],'count'=>[]];

        $result->bar_user = (object)array();
        $result->bar_user->lable = [];
        $result->bar_user->data = [];

        $six_months_ago = Jalali\Jalalian::now()->subMonths(6);
        $six_months_ago = $six_months_ago->subDays($six_months_ago->getDay() - 1);
        for ($i = 1; $i <= 6; $i++) {
            $month = $six_months_ago->addMonths($i);
            $date_start = Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d 00:00:00', $month->format('Y-m-d 00:00:00'));
            $date_stop = Jalali\CalendarUtils::createCarbonFromFormat('Y-m-d 00:00:00', $month->addMonths(1)->format('Y-m-d 00:00:00'));
            $m = $month->format('F Y');
            array_push($result->line_time->lable,$m );
            array_push($result->bar_user->lable,$m );

            $c = ReferralTransaction::where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->count();
            $a = ReferralTransaction::where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->sum('amount');
            array_push($result->line_time->data['count'], $c);
            array_push($result->line_time->data['amount'], round($a));

            // Bar
            $c = UserReferral::where('created_at', '>=', $date_start)->where('created_at', '<', $date_stop)->count();
            array_push($result->bar_user->data, $c);
        }

        return $result;
    }
}
