<?php


namespace App\Http\Controllers\Users;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserCall;
use Illuminate\Http\Request;
use Morilog\Jalali;

class CallHistoryController extends Controller
{
    function listCall(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;
        $result = (object)array();
        $query = UserCall::query();

        $query->leftJoin('users','users_call_history.id_user','users.id');
        $query->leftJoin('admins','users_call_history.id_admin','admins.id');
        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $query->select('users_call_history.*','users.name','users.name_display','users.family','users.level_account','users.mobile as email','admins.name as admin_name','admins.email as admin_email');
        $cardbanks = $query->paginate($limit)->items();
        foreach ($cardbanks as $card) {
            $card->date = $this->convertDate($card->created_at, 'd F Y - H:i');
        }
        $result->lists = $cardbanks;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'date': $sortBy = 'users_call_history.created_at'; break;
            case 'id': $sortBy = 'users_call_history.id'; break;
            case 'users_call_history': $sortBy = 'users_call_history.id'; break;
            default: $sortBy = $request->sortBy;
        }


        if (!empty($search)) {
            $fields = ['users_call_history.id','text', 'users.name', 'users.mobile', 'users.email', 'family','admins.name','admins.email'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('users_call_history.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_call_history.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        if (isset($request->id_user)) {
            $query->where('users_call_history.id_user', $request->id_user);
        }

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function addCall(Request $request){
        $validator = \Validator::make($request->all(), [
            'mobile'    => 'required|numeric|digits:11',
            'text'    => 'required',
            'date'    => 'required',
        ]);
        if ($validator->fails()) {
            $result = array('status' => false, 'msg' => $validator->errors()->first());
            return response()->json($result);
        }
        $user = User::where('mobile',$request->mobile)->first();
        if(!isset($user))
            return array('status'=> false , 'msg'=>  'کاربری با این موبایل پیدا نشد.' );

        $created_at = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->date);

        $call = new UserCall();
        $call->id_user  = $user->id;
        $call->text = $request->text;
        $call->id_admin = \Auth::user()->id;
        $call->created_at = $created_at;
        if($call->save())
            $result = array('status'=> true , 'msg'=> __('Successfully registered.') );
        else
            $result = array('status'=> false , 'msg'=> __('Registration failed.') );
        return response()->json($result);
    }
    function removeCall(Request $request){
        try {
            $gift = UserCall::find($request->id);
            $gift->delete();
            return array('status' => true, 'msg' => 'با موفقیت حذف شد.');
        }catch (\Exception $e){
            return array('status' => false, 'msg' => 'حذف امکان پذیر نیست!');
        }
    }

}
