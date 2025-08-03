<?php
namespace App\Http\Controllers\Gift;
use App\Http\Controllers\Controller;

use App\Models\Gift;
use App\Models\GiftUser;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use Morilog\Jalali;

class GiftController extends Controller
{
    function listGift(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = Gift::query();


        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $gifts = $query->paginate($limit)->items();
        foreach ($gifts as $gift) {
            $gift->users = json_decode($gift->users);
            $gift->description = json_decode($gift->description);

            if ($gift->expired > date('Y-m-d H:i:s')){
                $dtCurrent = \DateTime::createFromFormat('U', time());
                $dtCreate = \DateTime::createFromFormat('U', strtotime($gift->expired));
                $diff = $dtCurrent->diff($dtCreate);
                $interval = $diff->format("%y سال %m ماه %d روز %h ساعت %i دقیقه");
                $gift->interval = preg_replace('/(^0| 0) (سال|ماه|روز|ساعت|دقیقه|ثانیه)/', '', $interval);
            }
            $gift->started = $this->convertDate($gift->started, 'd F Y - H:i');
            $gift->expired = $this->convertDate($gift->expired, 'd F Y - H:i');
        }
        $result->list = $gifts;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;
        switch ($request->sortBy){
            case 'discount': $sortBy = 'buy_discount'; break;
            default: $sortBy = $request->sortBy;
        }
        if (!empty($search)) {
            $fields = ['id', 'name','count','period','buy_discount','sell_discount','description','users'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function addGift(Request $request){
        $validator = \Validator::make($request->all(), [
            'name'    => 'required|max:50',
            'count'    => 'required|numeric',
            'period'    => 'required|numeric',
            'buyDiscount'    => 'required|numeric|min:1|max:100',
            'sellDiscount'    => 'required|numeric|min:1|max:100',
            'description'    => 'required',
            'started'    => 'required',
            'expired'    => 'required',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        if(isset($request->id)){
            $giftExist =  Gift::where('name',$request->name)->where('id','!=',$request->id)->first();
            if(isset($giftExist))
                return array('status' => false, 'msg' => 'کد تخففیی قبلا با این اسم درج شده است.');
            $gift = Gift::find($request->id);
        }else{
            $giftExist =  Gift::where('name',$request->name)->first();
            if(isset($giftExist))
                return array('status' => false, 'msg' => 'کد تخففیی قبلا با این اسم درج شده است.');
            $gift = new Gift();
        }


        $users = [];
        $idUsers = explode(",",$request->idUsers);
        foreach ($idUsers as $id_user){
            if(isset($id_user) && $id_user != ''){
                $user = User::find($id_user);
                if(!isset($user))
                    return array('status' => false, 'msg' => 'کاربری با شناسه '.$id_user.' وجود ندارد!');
                else
                    array_push($users,(int)$id_user);
            }
        }

        $gift->name = $request->name;
        $gift->count = $request->count;
        $gift->period = $request->period;
        $gift->buy_discount = $request->buyDiscount;
        $gift->sell_discount = $request->sellDiscount;
        $gift->description = json_encode($request->description);
        $gift->started = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->started);
        $gift->expired = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->expired);
        $gift->users = count($users)>0 ? json_encode($users) :null;
        $gift->save();

        return array('status' => true, 'msg' => 'با موفقیت '.(isset($request->id)?'ویرایش':'درج').' شد.');

    }

    function removeGift(Request $request){
        try {
            $gift = Gift::find($request->id);
            GiftUser::where('id_gift',$gift->id)->delete();
            $gift->delete();
            return array('status' => true, 'msg' => 'با موفقیت حذف شد.');
        }catch (\Exception $e){
            return array('status' => false, 'msg' => 'حذف امکان پذیر نیست!');
        }
    }


    function singleGift(Request $request){
        $gift = Gift::find($request->id);
        $gift->started = $this->convertDate($gift->started, 'Y/m/d H:i');
        $gift->expired = $this->convertDate($gift->expired, 'Y/m/d H:i');
        $gift->description = json_decode($gift->description);
        $gift->users = isset( $gift->users) ? implode(",",json_decode($gift->users)) : null;
        return $gift;
    }


    function listUsers(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;
        $offset = (($request->page - 1) ?? 0) *$limit;

        $result = (object)array();
        $query = GiftUser::query();

        $query->leftJoin('users','users.id','gift_account_users.id_user');
        $query->leftJoin('gift_account','gift_account.id','gift_account_users.id_gift');

        // Filters
        $query = self::filtersListUsers($query,$request);
        $totalCount = $query->count();

        $query->select('gift_account_users.*','gift_account.name as gift','users.name','users.family', 'users.email');


        $query->limit($limit)->offset($offset);
        $users = $query->get();
        foreach ($users as $user) {
            $user->created = $this->convertDate($user->created_at, 'd F Y - H:i');
            $user->expired = $this->convertDate($user->expired_at, 'd F Y - H:i');
            $user->expired_ago = Jalali\Jalalian::forge($user->expired_at)->ago('datetime');
            if ($user->expired_at > date('Y-m-d H:i:s')){
                $user->status = 'active';
                $dtCurrent = \DateTime::createFromFormat('U', time());
                $dtCreate = \DateTime::createFromFormat('U', strtotime($user->expired_at));
                $diff = $dtCurrent->diff($dtCreate);
                $interval = $diff->format("%y سال %m ماه %d روز %h ساعت %i دقیقه");
                $user->interval = preg_replace('/(^0| 0) (سال|ماه|روز|ساعت|دقیقه|ثانیه)/', '', $interval);
            }else
                $user->status = 'expired';

        }
        $result->list = $users;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filtersListUsers($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'users.name'; break;
            case 'giftName': $sortBy = 'gift_account.name'; break;
            case 'created': $sortBy = 'gift_account_users.created_at'; break;
            case 'expired': $sortBy = 'gift_account_users.expired_at'; break;
            case 'id': $sortBy = 'gift_account_users.id'; break;
            case 'discount': $sortBy = 'gift_account_users.buy_discount'; break;
            default: $sortBy = $request->sortBy;
        }


        if (!empty($search)) {
            $fields = ['gift_account_users.id','gift_account.name','gift_account.description',
                'users.name','users.family','users.family','users.email'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('gift_account_users.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('gift_account_users.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }


        if (isset($request->amountStart))
            $query->where('amount','>=', $request->amountStart);
        if (isset($request->amountStop))
            $query->where('amount','<=', $request->amountStop);

        if (isset($request->status)){
            if($request->status == 'active')
               $query->where('gift_account_users.expired_at','>=',date('Y-m-d H:i:s'));
            else
                $query->where('gift_account_users.expired_at','<',date('Y-m-d H:i:s'));
        }

        if(isset($request->id_user))
            $query->where('id_user',$request->id_user);

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }

    function removeUsers(Request $request){
        try {
            $giftUser = GiftUser::find($request->id);
            $giftUser->delete();
            return array('status' => true, 'msg' => 'با موفقیت حذف شد.');
        }catch (\Exception $e){
            return array('status' => false, 'msg' => 'حذف امکان پذیر نیست!');
        }
    }

    function statistic(){
        $statistic = (object)array();
        $statistic->all_gift = Gift::where('expired','>',date('Y-m-d H:i:s'))->count();
        $statistic->gift_users_active = GiftUser::where('expired_at','>',date('Y-m-d H:i:s'))->count();
        $statistic->gift_users_all = GiftUser::count();
        $statistic->gift_users_id = Gift::whereNotNull('users')->count();
        return response()->json($statistic);
    }
}
