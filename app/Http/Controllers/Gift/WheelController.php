<?php
namespace App\Http\Controllers\Gift;
use App\Http\Controllers\Controller;

use App\Models\GiftWheel;
use App\Models\User;
use Illuminate\Http\Request;
use Morilog\Jalali;

class WheelController extends Controller
{
    function listWheel(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = GiftWheel::query();
        $query->leftJoin('users','users.id','gift_wheel.id_user');
        $query->select('gift_wheel.*','users.name','users.family', 'users.email', 'users.level','users.level_account');

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $wheels = $query->paginate($limit)->items();
        foreach ($wheels as $wheel) {
            $item = json_decode($wheel->item);
            $wheel->gift = $item->value;
            if(isset($item->between))
                $wheel->possibility = $item->weight/10;
            else
                $wheel->possibility = $item->weight;
            $wheel->level_gift = $item->level??1;
            $wheel->date = $this->convertDate($wheel->created_at, 'd F Y - H:i');

            $count_previous = GiftWheel::where('id_user',$wheel->id_user)->where('id','<',$wheel->id)->count();
            $wheel->count = $count_previous + 1 ;
        }
        $result->list = $wheels;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;
        switch ($request->sortBy){
            default: $sortBy = $request->sortBy;
        }
        if (!empty($search)) {
            $fields = ['gift_wheel.id', 'item','amount','users.name','users.family','users.family','users.email'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('gift_wheel.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('gift_wheel.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }


        if (isset($request->amountStart))
            $query->where('amount','>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('amount','<=', $request->amountStop);

        if(isset($request->id_user))
            $query->where('id_user',$request->id_user);

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }



    function removeWheel(Request $request){
        try {
            $gift = GiftWheel::find($request->id);
            $gift->delete();
            return array('status' => true, 'msg' => 'با موفقیت حذف شد.');
        }catch (\Exception $e){
            return array('status' => false, 'msg' => 'حذف امکان پذیر نیست!');
        }
    }

    function statisticWheel(Request $request){
        $statistic = (object)array();
        $where = '';

        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $where .= 'gift_wheel.created_at >= "'.$dateStart.'"';
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $where .= (($where!=='')? ' and ':'').'gift_wheel.created_at <= "'.$dateStop .'"';
            }catch(\Exception $e){}
        }

        if(!isset($request->id_user)):
            if($where == ''){
                $statistic->count = GiftWheel::count();
                $statistic->amount = GiftWheel::sum('amount');
                $statistic->users_count = GiftWheel::distinct('id_user')->count('id_user');
                $statistic->empty_count = GiftWheel::where('item','like', '%' . 'پوچ' . '%')->count();
            }else{
                $statistic->count = GiftWheel::whereRaw($where)->count();
                $statistic->amount = GiftWheel::whereRaw($where)->sum('amount');
                $statistic->users_count = GiftWheel::whereRaw($where)->distinct('id_user')->count('id_user');
                $statistic->empty_count = GiftWheel::whereRaw($where)->where('item','like', '%' . 'پوچ' . '%')->count();
            }
        else:
            $statistic->count = GiftWheel::where('id_user',$request->id_user)->count();
            $statistic->amount = GiftWheel::where('id_user',$request->id_user)->sum('amount');
            $statistic->users_count = GiftWheel::where('id_user',$request->id_user)->distinct('id_user')->count('id_user');
            $statistic->empty_count = GiftWheel::where('id_user',$request->id_user)->where('item','like', '%' . 'پوچ' . '%')->count();
        endif;
        return response()->json($statistic);
    }

}
