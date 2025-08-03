<?php
namespace App\Http\Controllers\Referral;


use App\Http\Controllers\Controller;

use App\Models\ReferralTransaction;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use Morilog\Jalali;

class referralController extends Controller
{
    function listUsers(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = UserReferral::query();


        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();

        $query->leftJoin('users as user_caller','user_caller.id','users_referral.id_user_caller');
        $query->leftJoin('users as user_referral','user_referral.id','users_referral.id_user_referral');
        $query->leftJoin('users_referral_transaction','users_referral_transaction.id_referral','users_referral.id');
        $query->groupBy('users_referral.id');

        $query->select('users_referral.*','user_caller.name as user_caller_name','user_caller.family as user_caller_family', 'user_caller.email as user_caller_email'
                            ,'user_referral.name as user_referral_name','user_referral.family as user_referral_family','user_referral.email as user_referral_email');
        $query->selectRaw('SUM(users_referral_transaction.amount_toman) as total_amount');
        $query->selectRaw('COUNT(users_referral_transaction.id) as count_transaction');


        $users_referral = $query->paginate($limit)->items();
        foreach ($users_referral as $referral) {
            $referral->date = $this->convertDate($referral->created_at, 'd F Y - H:i');
            $referral->total_amount = (int)$referral->total_amount;
        }
        $result->list = $users_referral;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'userCaller': $sortBy = 'user_caller.name'; break;
            case 'userReferral': $sortBy = 'user_referral.name'; break;
            case 'percentAll': $sortBy = 'percent_all'; break;
            case 'percentCaller': $sortBy = 'percent_caller'; break;
            case 'percentReferral': $sortBy = 'percent_referral'; break;
            case 'date': $sortBy = 'users_referral.created_at'; break;
            case 'id': $sortBy = 'users_referral.id'; break;
            case 'countReferral': $sortBy = 'count_transaction'; break;
            case 'amountReferral': $sortBy = 'total_amount'; break;
            default: $sortBy = $request->sortBy;
        }


        if (!empty($search)) {
            $fields = ['users_referral.id', 'user_caller.name','user_referral.name','user_caller.family','user_referral.family',
                'user_caller.email','user_referral.email','user_caller.mobile','user_referral.mobile'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('users_referral.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_referral.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        if (isset($request->amountStart))
            $query->where('total_amount','>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('total_amount','<=', $request->amountStop);

        if (isset($request->countStart))
            $query->where('count_transaction','>=', $request->countStart);
        if (isset($request->countStop))
            $query->where('count_transaction','<=', $request->countStop);

        if (isset($request->id_user))
            $query->where('users_referral.id_user_caller', $request->id_user);

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }




    function listTransaction(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = ReferralTransaction::query();


        // Filters
        $query = self::filtersTransaction($query,$request);
        $totalCount = $query->count();

        $query->leftJoin('users_referral','users_referral.id','users_referral_transaction.id_referral');
        $query->leftJoin('users as user_caller','user_caller.id','users_referral.id_user_caller');
        $query->leftJoin('users as user_referral','user_referral.id','users_referral.id_user_referral');
        $query->leftJoin('users as user','user.id','users_referral_transaction.id_user');

        $query->select('users_referral_transaction.*','id_user_caller','id_user_referral','user_caller.name as user_caller_name','user_caller.family as user_caller_family', 'user_caller.email as user_caller_email'
            ,'user_referral.name as user_referral_name','user_referral.family as user_referral_family','user_referral.email as user_referral_email',
        'user.name','user.family','user.id as id_user','user.email');


        $users_referral = $query->paginate($limit)->items();
        foreach ($users_referral as $referral) {
            $referral->date = $this->convertDate($referral->created_at, 'd F Y - H:i');
        }
        $result->list = $users_referral;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filtersTransaction($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'userCaller': $sortBy = 'user_caller.name'; break;
            case 'userReferral': $sortBy = 'user_referral.name'; break;
            case 'date': $sortBy = 'users_referral_transaction.created_at'; break;
            case 'id': $sortBy = 'users_referral_transaction.id'; break;
            case 'amountToman': $sortBy = 'amount_toman'; break;
            default: $sortBy = $request->sortBy;
        }


        if (isset($request->id_user)){
            //$ids = UserReferral::where('id_user_caller',$request->id_user)->pluck('id')->toArray();
            //$query->whereIn('users_referral_transaction.id_referral', $ids);
            $query->where('users_referral_transaction.id_user', $request->id_user);
        }

        if (!empty($search)) {
            $fields = ['users_referral_transaction.id','users_referral_transaction.symbol','users_referral_transaction.amount',
                'user_caller.name','user_referral.name','user_caller.family','user_referral.family',
                'user_caller.email','user_referral.email','user_caller.mobile','user_referral.mobile'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('users_referral_transaction.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_referral_transaction.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        if (isset($request->amountStart))
            $query->where('amount','>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('amount','<=', $request->amountStop);

        if (isset($request->for)){
            if($request->for == 'orders')
                $query->whereNotNull('id_order');
            else
                $query->whereNotNull('id_trade');
        }


        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function statistic(Request $request){
        $statistic = (object)array();
        if (!isset($request->id_user)){
            $statistic->users = UserReferral::count();
            $statistic->transaction = ReferralTransaction::count();
            $statistic->amount = ReferralTransaction::sum('amount');
            $statistic->avg = round($statistic->amount / $statistic->transaction);
        }else{
            $statistic->users = UserReferral::where('id_user_caller',$request->id_user)->count();
            $statistic->transaction = ReferralTransaction::where(['id_user'=>$request->id_user])->count();
            $statistic->amount = ReferralTransaction::where(['id_user'=>$request->id_user])->sum('amount');
            $statistic->avg = $statistic->transaction>0 ? round($statistic->amount / $statistic->transaction):0;
        }


        return response()->json($statistic);
    }
}
