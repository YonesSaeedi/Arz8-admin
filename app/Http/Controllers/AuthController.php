<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use PragmaRX\Google2FAQRCode\Google2FA;

class AuthController extends Controller
{
    public function login(Request $request)
    {

        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_secret = env('recaptcha_SECRET');
        $recaptcha_response = $request->recaptcha;

        // Make and decode POST request:
        $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
        $recaptcha = json_decode($recaptcha);

        // Take action based on the score returned:
        if ($recaptcha->success == false || $recaptcha->score < 0.5) {
            return response()->json(array('status'=> false, 'msg'=>'google recaptcha error'), 400);
        }

        $validator = \Validator::make($request->all(), [
            'email'    => 'required|email|max:255',
            'password' => 'min:6|required',
        ]);

        if ($validator->fails()) {
            $result = array('status' => false, 'msg' => $validator->errors()->first());
            return response()->json($result);
        }

        try {
            auth()->shouldUse('api');
            if (! $token = JWTAuth::attempt($request->only('email', 'password'))) {
                return response()->json(array('status'=>false, 'msg'=> __('Wrong Email or Password')), 400);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(array('status'=>false, 'msg'=>'token expired'), 400);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(array('status'=>false, 'msg'=>'token invalid'), 400);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(array('status'=>false, 'msg'=>'token absent:'.$e->getMessage()), 400);
        }

        // Check blocked
        $access = json_decode( Auth::user()->access);
        if(isset($access) && isset($access->is_block) && $access->is_block == true)
            return response()->json(array('status'=>false, 'msg'=>'دسترسی شما به پنل ممکن نیست!'), 400);

        //Check 2fa
        $statusTwofa = self::statusTwofa();
        if($statusTwofa->status == true)
            return response()->json($statusTwofa, 200);


        return response()->json(
            array('status'=>true, 'msg'=>'success','jwtToken'=> $token , 'user'=> Auth::user()),
            200);
    }

    public function refresh()
    {
        $token =  JWTAuth::getToken();
        if (! $token) {
            return response()->json(['status' => false,'msg'=>'Token not provided'],401);
        }
        try {
            $token = JWTAuth::refresh($token);
        } catch (\Exception $e) {
            return response()->json(['status' => false,'msg'=>'The token is invalid'],500);
        }
        return response()->json(array('accessToken'=>$token),200);
    }

    public function statusTwofa(){
        $functions = new \App\Functions;
        if(isset(Auth::user()->google2fa))
            $sendTo = $functions->hideMobile(Auth::user()->mobile);
        else
            $sendTo = $functions->hideMobile(Auth::user()->mobile);
        //send Email or Sms
        $request = new Request();
        $createToken2fa = \Crypt::encryptString(Auth::user()->email.Auth::user()->name);
        $request->replace(['token2fa' => $createToken2fa,'id_user'=>Auth::user()->id]);
        self::sendCodeTwofa($request);
        // Data
        $data = (object)array();
        $data->twofa = true;
        $data->sendTo = $sendTo;
        $data->type = isset(Auth::user()->google2fa) ?'google':'sms';
        $data->token2fa = $createToken2fa;
        $data->id_user = Auth::user()->id;
        //
        return (object)array('status'=>true, 'msg'=> __('TwoFa') , 'data'=> $data );
    }


    function sendCodeTwofa(Request $request){
        $user = AdminUser::find($request->id_user);
        $decryptToken2fa = \Crypt::decryptString($request->token2fa);
        if(!isset($user) || !isset($request->token2fa) || ($user->email.$user->name != $decryptToken2fa)){
            $result = array('status' => false, 'msg' => __('Failed.'));
            return response()->json($result);
        }

        $data = json_decode($user->data)??(object)array();
        if(isset($data->twofa_code) && isset($data->timeStr) && ($data->timeStr+600 > time())) {
            $str_random = strtoupper($data->twofa_code);
            $data->timeStr = $data->timeStr;
        }else{
            $str_random = rand(1000,9999);
            $data->timeStr = time();
        }
        $data->twofa_code = $str_random;

        if(!isset($data->timeSend) || ($data->timeSend+120 < time())){
            if(isset($user->google2fa))
                $result = array('status' => true, 'msg' => __('successfully.'));
            else{
                try{
                    \Kavenegar::VerifyLookup($user->mobile,$str_random,null,null,'VerifyMobile');
                    $result = array('status' => true, 'msg' => __('Sms sent successfully.'));
                } catch (\Exception $ex){
                    dd($ex);
                }
            }
            $data->timeSend = time();
        }else
            $result = array('status'=> false , 'msg'=> __('Failed to retry code. Try for another :seconds seconds',['seconds'=>($data->timeSend - time()  +120 )]) );

        $user->data = json_encode($data);
        $user->save();
        return response()->json($result);
    }


    public function login2faSms(Request $request){
        $user = AdminUser::find($request->id_user);
        $decryptToken2fa = \Crypt::decryptString($request->token2fa);
        if(!isset($user) || !isset($request->token2fa) || ($user->email.$user->name != $decryptToken2fa)){
            $result = array('status' => false, 'msg' => __('Failed.'));
            return response()->json($result);
        }

        $data = json_decode($user->data);
        if(isset($data->twofa_code) && isset($data->timeStr) && ($data->timeStr+600 > time())) {
            if(isset($request->code) && $request->code ==$data->twofa_code ){
                $token = Auth::guard('api')->tokenById($user->id);
                $data->twofa_code = null;
                $user->data = json_encode($data);
                $user->save();
                $result = array('status'=>true, 'msg'=> __('done successfully') ,'jwtToken'=> $token,'user'=> $this->getUser($user));
            }else
                $result = array('status'=> false , 'msg'=> __('The code is incorrect.') );

        }else{
            self::sendCodeTwofa($request);
            $result = array('status'=> false , 'msg'=> __('کد جدیدی برای شما ارسال شد و لطفا کد را درج کنید.') );
        }
        return response()->json($result);
    }

    public function login2faGoogle(Request $request){
        $user = AdminUser::find($request->id_user);
        $decryptToken2fa = \Crypt::decryptString($request->token2fa);
        if(!isset($user) || !isset($request->token2fa) || ($user->email.$user->name != $decryptToken2fa)){
            $result = array('status' => false, 'msg' => __('Failed.'));
            return response()->json($result);
        }
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey(\Crypt::decryptString($user->google2fa), $request->code);

        if($valid==1) {
            $token = Auth::guard('api')->tokenById($user->id);
            $result = array('status'=>true, 'msg'=> __('done successfully') ,'jwtToken'=> $token,'user'=> $this->getUser($user));
        }else
            $result = array('status'=> false , 'msg'=> __('The code is incorrect.') );

        return response()->json($result);
    }


    function getUser($user){
        return $user;
    }

}
