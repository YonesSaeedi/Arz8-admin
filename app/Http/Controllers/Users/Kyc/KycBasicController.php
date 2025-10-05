<?php

namespace App\Http\Controllers\Users\Kyc;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class KycBasicController extends Controller
{
    function basic(Request $request){
        $validator = \Validator::make($request->all(), [
            'mobile' => 'nullable|numeric|digits:11',
            'email' => 'nullable|email',
            'name' => 'nullable|min:3|max:50',
            'family' => 'nullable|min:3|max:50',
            'father' => 'nullable|min:3|max:50',
            'nationalCode' => 'nullable|numeric',
            'dateBirth' => 'nullable',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $user = User::find($request->id);
        $nationalCodeExist = User::where('national_code',$request->nationalCode)->where('id','!=',$user->id)->first();
        if($nationalCodeExist)
            return array('status' => false, 'msg' => 'کد ملی برای کاربر دیگری ثبت شده است.');


        if(isset($request->dateBirth) && $request->dateBirth!='null' && app()->getLocale() == 'fa'){
            $request->dateBirth = \Morilog\Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d', $request->dateBirth);
        }else
            $request->dateBirth = null;

        $userExist= User::where("mobile", $request->mobile)->where('id','!=',$user->id)->first();
        if (isset($userExist) && isset($request->mobile))
            return array('status' => false, 'msg' => 'موبایل برای کاربر دیگری ثبت شده است.');

        $userExist= User::where("email", $request->email)->where('id','!=',$user->id)->first();
        if (isset($userExist) && isset($request->email))
            return array('status' => false, 'msg' => 'ایمیل برای کاربر دیگری ثبت شده است.');



        $user->name = $request->name;
        $user->family = $request->family;
        $user->date_birth = $request->dateBirth;
        $user->mobile = $request->mobile;
        $user->national_code =  $request->nationalCode;
        $user->father =  $request->father;
        $user->email =  $request->email;
        $user->save();

        self::logSave('users.edit',$request->all(),'ویرایش سطح پایه کاربر '.$user->email,$request->ip());
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');

    }
}
