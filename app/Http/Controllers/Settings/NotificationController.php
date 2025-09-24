<?php
namespace App\Http\Controllers\Settings;
use App\Http\Controllers\Controller;
use App\Models\Notifications;
use App\Models\User;
use Illuminate\Http\Request;
use Morilog\Jalali;

class NotificationController extends  Controller
{
    function listNotification(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = Notifications::query();
        if(!$request->public) {
            $query->join('users', 'notifications.id_user', 'users.id');
            $query->select('notifications.*', 'users.name', 'users.family', 'users.email', 'name_display');
        }
        // Filters
        $query = self::filters($query,$request);
        $usersCount = $query->count();

        $notifications = $query->paginate($limit)->items();
        foreach ($notifications as $notification) {
            $notification->date = $this->convertDate($notification->created_at, 'd F Y - H:i');
            $notification->message = json_decode($notification->message);
        }
        $result->lists = $notifications;
        $result->total = $usersCount;

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'name': $sortBy = $request->public ? 'notifications.id':'users.family'; break;
            case 'id': $sortBy = 'notifications.id'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            if(!$request->public)
                $fields = ['notifications.id', 'users.name', 'users.family', 'users.email','users.mobile','title','message'];
            else
                $fields = ['notifications.id','title','message'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if($request->public)
            $query->whereNull('id_user');

        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('notifications.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('notifications.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        if (isset($request->id_user)) {
            $query->where('notifications.id_user', $request->id_user);
        }

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function sendNotif(Request $request){
        $validator = \Validator::make($request->all(), [
            'title'    => 'required',
            'message'    => 'required',
        ]);
        if ($validator->fails()) {
            $result = array('status' => false, 'msg' => $validator->errors()->first());
            return response()->json($result);
        }

        $usersId = [];
        $idUsers = !$request->allUsers ? explode(",",$request->idUsers): [];
        foreach ($idUsers as $id_user){
            if(isset($id_user) && $id_user != ''){
                $user = User::find($id_user);
                if(!isset($user))
                    return array('status' => false, 'msg' => 'کاربری با شناسه '.$id_user.' وجود ندارد!');
                else
                    array_push($usersId,(int)$id_user);
            }
        }

        if(count($usersId) > 0){
            foreach ($usersId as $id) {
                $notifications = new Notifications;
                $notifications->id_user = $id;
                $notifications->title = $request->title;
                $notifications->message = json_encode($request->message);
                $notifications->keyword = 'message';
                $notifications->data = json_encode($request->all());
                $notifications->save();
            }
        }else{
            $notifications = new Notifications;
            $notifications->id_user = null;
            $notifications->title = $request->title;
            $notifications->message = json_encode($request->message);
            $notifications->keyword = 'message';
            $notifications->seen = 'seen';
            $notifications->data = json_encode($request->all());
            $notifications->save();
        }

        if(count($usersId) <= 0 && $request->sendEmail==false && $request->sendSms==false && $request->sendNotif==true){
            $func = new \App\Functions();
            $func->sendMsgFirebase(env('APP_NAME'),$request->message['fa'],$request->urlImageNotif,$request->soundNotif);
        }else{
            if($request->sendEmail!=false || $request->sendSms!=false || $request->sendNotif!=false || $request->sendTelegram!=false):
                $limit = $request->numUsers;
                $offsetTime = $request->numSeconds;
                $query = User::query();
                if(count($usersId) > 0)
                    $query->whereIn('id',$usersId);

                if($request->sendEmail==false && $request->sendSms==false && $request->sendNotif==false && $request->sendTelegram!=false)
                    $query->whereNotNull('telegram_id');
                if($request->sendEmail!=false && $request->sendSms==false && $request->sendNotif==false && $request->sendTelegram==false)
                    $query->whereNotNull('email');
                if($request->sendEmail==false && $request->sendSms!=false && $request->sendNotif==false && $request->sendTelegram==false)
                    $query->whereNotNull('mobile');


                $count = $query->count();
                for($i=0;$i <= ceil($count/$limit);$i++){
                    $users = $query->limit($limit)->offset($i*$limit)->get('id')->pluck('id')->toArray();
                    $time = $offsetTime * $i;
                    $job = (new \App\Jobs\Admin\NotificationTiming($users,$notifications->id))->delay(\Carbon\Carbon::now()->addSeconds($time));
                    dispatch($job);
                }
            endif;
        }

        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');

    }

    function singledNotif(Request $request){
        $notification = Notifications::find($request->id);
        $notification->data = json_decode($notification->data);
        $notification->message = json_decode($notification->message);
        if($notification->id_user){
            $notification->user = User::select('id','email','name','family','mobile')->find($notification->id_user);
        }
        $result = array('status' => true, 'msg' => '', 'notification'=> $notification,);
        return response()->json($result);
    }

    function editNotif(Request $request){
        $validator = \Validator::make($request->all(), [
            'title' => 'required',
            'message' => 'required'
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $notification = Notifications::find($request->id);
        $notification->seen = $request->seen;
        if($notification->keyword == 'message'){
            $notification->message = json_encode($request->message);
        }
        $notification->data = json_encode($request->data);
        $notification->save();
        $result = array('status' => true, 'msg' => 'با موفقیت تغییر کرد.');
        return response()->json($result);
    }


    function removeNotif(Request $request){
        $notification = Notifications::find($request->id);
        //$data = json_decode($notification->data);
        //if ((!isset($data->numUsers) && !isset($data->numSeconds)) || isset($notification->id_user)){
            $notification->delete();
            $result = array('status' => true, 'msg' => 'با موفقیت حذف شد.');
        //}else{
            //$count = count($data->idUsers) > 0 ? count($data->idUsers) : User::count();
        //    $result = array('status' => false, 'msg' => 'حذف در حال حاضر امکان پذیر نیست.');
       // }
        return response()->json($result);
    }
}
