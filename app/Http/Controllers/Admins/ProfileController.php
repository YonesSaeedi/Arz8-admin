<?php
namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Morilog\Jalali;
use App\Models\AdminUser;
use Auth;
use PragmaRX\Google2FAQRCode\Google2FA;

class ProfileController extends Controller
{
    function edit(Request $request){
        $validator = \Validator::make($request->all(), [
            'name'    => 'required|max:50',
            'mobile'    => 'required',
            'password' => 'nullable|min:10|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'nullable|min:10',
        ]);
        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $user = AdminUser::find(Auth::user()->id);
        $user->name = $request->name;
        $user->mobile = $request->mobile;
        if(isset($request->password)){
            $user->password = bcrypt($request->password);
        }
        $user->save();
        return array('status'=>true ,'msg'=>'با موفقیت ثبت شد.');
    }

    function twoFaInfo(){
        $result = array();
        $google2fa = new Google2FA();

        //
        $user = AdminUser::find(Auth::user()->id);
        $data = json_decode($user->data)??(object)array();
        if(isset($data->google2fa_secret) && isset($data->timeStr) && ($data->timeStr+300 > time())) {
            $secret = $data->google2fa_secret;
            $data->timeStr = $data->timeStr;
        }else{
            $secret = $google2fa->generateSecretKey();
            $data->timeStr = time();
        }
        $data->google2fa_secret = $secret;
        $user->data = json_encode($data);
        $user->save();
        //

        $inlineUrl = $google2fa->getQRCodeInline(
            env('APP_NAME').' - Admin',
            'mobile:'.Auth::user()->mobile,
            $secret,500
        );

        $result['inlineUrl'] = $inlineUrl;
        $result['secret'] = $secret;
        $result['status'] = isset($user->google2fa) ? true : false;
        return response()->json((object)$result);
    }

    function twoFa(Request $request){
        $user = AdminUser::find(Auth::user()->id);
        $google2fa = new Google2FA();
        $data = json_decode($user->data)??(object)array();
        if(isset($user->google2fa)){
            $valid = $google2fa->verifyKey(\Crypt::decryptString($user->google2fa), $request->code);
            if($valid==1){
                $user->google2fa = null;
                $data->google2fa_secret = null;
                $result = array('status' => true, 'msg' =>  __('done successfully'));
            }else
                $result = array('status' => false, 'msg' => __('The code is incorrect.'));
        }else{
            if(isset($data->google2fa_secret) && isset($data->timeStr) && ($data->timeStr+600 > time())) {
                $secret = $data->google2fa_secret;
                $valid = $google2fa->verifyKey($secret, $request->code);
                if($valid==1){
                    $user->google2fa = \Crypt::encryptString($secret);
                    $result = array('status' => true, 'msg' =>  __('done successfully'));
                }else
                    $result = array('status' => false, 'msg' => __('The code is incorrect.'));
            }else
                $result = array('status'=> false , 'msg'=>  __('Try again from the beginning.') );
        }
        $user->data = json_encode($data);
        $user->save();
        return response()->json((object)$result);
    }
}
