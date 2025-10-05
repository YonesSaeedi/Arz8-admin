<?php

namespace App\Http\Controllers\Users\Kyc;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class KycAdvancedController extends Controller
{
    function Advanced(Request $request){
        $validator = \Validator::make($request->all(), [
            'file1' => 'required|mimes:jpg,jpeg,png',
            'file2' => 'required|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $user = User::find($request->id);
        $kyc_advanced = json_decode($user->kyc_advanced)??(object)array();
        if(isset($request->file1) && isset($request->file2)){
            $functions = new \App\Functions;
            $directory = 'uploads/Users/'.$user->created_at->year.'/'.$user->created_at->month.'/'. $user->id;

            $file1 = $request->file('file1');
            $SaveFile1 = $functions->saveFileImage($file1, $directory);

            $file2 = $request->file('file1');
            $SaveFile2 = $functions->saveFileImage($file2, $directory);

            if($SaveFile1 != false && $SaveFile2 != false){
                $kyc_advanced->file = $kyc_advanced->file ?? array();
                array_push($kyc_advanced->file, array('file1' => $SaveFile1,'file2' => $SaveFile2,'date'=>date('Y-m-d H:i:s'),'upload_admin'=>\Auth::user()->email) );
            }
        }


        $user->kyc_advanced = json_encode($kyc_advanced);
        $user->save();

        self::logSave('users.edit',$request->all(),'ویرایش سطح پیشرفته کاربر '.$user->email,$request->ip());
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');
    }


    function Status(Request $request){
        $user = User::find($request->id);
        $kyc_advanced = json_decode($user->kyc_advanced);

        if($request->status == 'confirm'){
            $kyc_advanced->status = 'confirm';
            $kyc_advanced->time_confirm = time();
            $user->level_kyc = 'advanced';
            $result = array('status'=>true ,'msg'=>'با موفقیت تایید شد.');

            // send Notif
            $this->sendNotification($user->id,'confirmLevelUser',['level'=>'پیشرفته','sms'=>['پیشرفته']]);

        }else if ($request->status == 'reject'){
            $kyc_advanced->status = 'reject';
            $kyc_advanced->reason_reject = $request->reason;
            $result = array('status'=>true ,'msg'=>'با موفقیت رد شد.');

            // send Notif
            $this->sendNotification($user->id,'rejectLevelUser', ['level'=>'پیشرفته','sms'=>['پیشرفته']]);
        }

        end($kyc_advanced->file)->id_admin = \Auth::user()->id;
        end($kyc_advanced->file)->admin = \Auth::user()->name;
        end($kyc_advanced->file)->admin_email = \Auth::user()->email;
        end($kyc_advanced->file)->status = $request->status;
        end($kyc_advanced->file)->reason = $request->reason;
        end($kyc_advanced->file)->date_admin = date('Y-m-d H:i:s');

        $user->kyc_advanced = json_encode($kyc_advanced);
        $user->save();

        self::logSave('users.edit',$request->all(),($request->status == 'confirm'?'تایید':'رد کردن').'اطلاعات سطح پیشرفته کاربر '.$user->email,$request->ip());
        return  response()->json($result);
    }
}
