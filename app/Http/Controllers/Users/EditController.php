<?php

namespace App\Http\Controllers\Users;

use App\Models\Cryptocurrency;
use App\Models\Settings;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\Wallets\WalletsService;
use Illuminate\Http\Request;

class EditController extends UsersController
{
    private WalletsService $walletsService;
    public function __construct(WalletsService $walletsService)
    {
        $this->walletsService = $walletsService;
    }

    function getUserInfo(Request $request){
        $user = User::find($request->id);

        $user->access = self::accessStatus($user);
        $user->identification_img = json_decode($user->identification_img);
        if(isset($user->identification_img) && $user->identification_img->file){
            $user->identification_img->file_hash = \Crypt::encryptString(end($user->identification_img->file)->url);
            foreach ($user->identification_img->file as $file)
                $file->url = \Crypt::encryptString($file->url);
        }
        $user->auth_img = json_decode($user->auth_img);
        if(isset($user->auth_img) && $user->auth_img->file){
            $user->auth_img->file_hash = \Crypt::encryptString(end($user->auth_img->file)->url);
            foreach ($user->auth_img->file as $file)
                $file->url = \Crypt::encryptString($file->url);
        }

        $user->kyc_advanced = json_decode($user->kyc_advanced);
        if(isset($user->kyc_advanced) && $user->kyc_advanced->file){
            $user->kyc_advanced->file1_hash = \Crypt::encryptString(end($user->kyc_advanced->file)->file1);
            $user->kyc_advanced->file2_hash = \Crypt::encryptString(end($user->kyc_advanced->file)->file2);
            foreach ($user->kyc_advanced->file as $file) {
                $file->file1 = \Crypt::encryptString($file->file1);
                $file->file2 = \Crypt::encryptString($file->file2);
            }
        }


        $user->address = json_decode($user->address);

        $user->info = json_decode($user->info??'{"birthplace":null}');
        $user->settings = json_decode($user->settings);
        $user->twofa = json_decode($user->twofa);
        $user->data = json_decode($user->data);
        $user->date_birth = $user->date_birth ? $this->convertDate($user->date_birth, 'Y/m/d') : null;
        $user->date_register = $this->convertDate($user->created_at, 'd F Y - H:i');

        $referral = UserReferral::where('id_user_referral',$user->id)->first();
        if(isset($referral))
            $user->referral = User::find($referral->id_user_caller);
        else
            $user->referral = null;

        $data = (object)array();
        $data->locale = json_decode(Settings::where('name','locale')->first()->value);
        $data->levels = json_decode(\Crypt::decryptString(Settings::where('name','levels')->first()->value));
        $data->levels_account = json_decode(\Crypt::decryptString(Settings::where('name','levels_account')->first()->value));

        $walletData = $this->walletsService->getWalletFiat($user->id);
        $balance = (float) $walletData->balance;
        $balance_available = (float) $walletData->balance_available;
        $data->internal_balance = array('balance'=>$balance??0, 'balance_available'=>$balance_available??0);

        $cryptoWallets = \App\Models\Wallet::where('id_user', $user->id)
            ->where('type', \App\Models\Wallet::TYPE_ASSET)
            ->get();
        $sum_usdt = 0;
        foreach ($cryptoWallets as $wallet){
            $crypto = Cryptocurrency::find($wallet->id_crypto);
            $data_crypto = json_decode($crypto->data??'{}');
            $price = $data_crypto->price_usdt??1;
            $balance_crypto =  \Crypt::decryptString($wallet->value);
            $sum_usdt += ($balance_crypto * $price);
        }
        $data->balance_crypto_usdt = $sum_usdt;

        // 30 days ago
        $dateStart = date('Y-m-d 00:00:00',strtotime( ' -30 day'));
        $dateStop =  date('Y-m-d 00:00:00');
        $amount_30days_orders = round(\App\Models\Orders::where('id_user',$request->id)->where('created_at','>=', $dateStart)->where('created_at','<=', $dateStop)->where('status','success')->sum('amount'));

        $id_trade = \App\Models\Trades::where('id_user',$request->id)->where('created_at','>=', $dateStart)->where('created_at','<=', $dateStop)->where('status','success')->pluck('id')->toArray();
        $amount_30days_trades  = round(\App\Models\TransactionCrypto::whereIn('id_trade',$id_trade)->where('id_user',$request->id)->where('type','deposit')->sum('amount_toman'));

        $data->sum_30days_all = ($amount_30days_orders+$amount_30days_trades);
        return response()->json(array('status'=>true ,'msg'=>'','user'=>$user,'data'=>$data));
    }


    function level0Edit(Request $request){
        $validator = \Validator::make($request->all(), [
            'name_display'    => 'required|max:50',
            'email'    => 'required|email|max:255',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $user = User::find($request->id);

        $emailExist = User::where('email',$request->email)->where('id','!=',$user->id)->first();
        if($emailExist)
            return array('status' => false, 'msg' => 'ایمیل برای کاربر دیگری ثبت شده است.');

        $user->email = $request->email;
        $user->name_display = $request->name_display;
        if ($request->emailVerified == true)
            $user->email_verified_at = date('Y-m-d H:i:s');
        else
            $user->email_verified_at = null;
        $user->save();

        self::logSave('users.edit',$request->all(),'ویرایش سطح یک کاربر '.$user->email,$request->ip());
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');
    }


    function level1Edit(Request $request){
        $validator = \Validator::make($request->all(), [
            'mobile' => 'nullable|numeric|digits:11',
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


        $user->name = $request->name;
        $user->family = $request->family;
        $user->date_birth = $request->dateBirth;
        $user->mobile = $request->mobile;
        $user->national_code =  $request->nationalCode;
        $user->father =  $request->father;
        $user->save();

        self::logSave('users.edit',$request->all(),'ویرایش سطح یک کاربر '.$user->email,$request->ip());
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');
    }


    function level2Edit(Request $request){
        $validator = \Validator::make($request->all(), [
            'file' => 'nullable|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $user = User::find($request->id);
        $identification = json_decode($user->identification_img)??(object)array();
        if(isset($request->file)){
            $functions = new \App\Functions;
            $directory = 'uploads/Users/'.$user->created_at->year.'/'.$user->created_at->month.'/'. $user->id;
            $file = $request->file('file');
            $SaveFile = $functions->saveFileImage($file, $directory);
            if($SaveFile != false){
                $identification->file = $identification->file ?? array();
                array_push($identification->file, array('url' => $SaveFile,'date'=>date('Y-m-d H:i:s'),'upload_admin'=>\Auth::user()->email) );
            }
        }
        //$identification->iranian = (string)$request->iranian;
        $user->identification_img = json_encode($identification);
        $user->save();

        self::logSave('users.edit',$request->all(),'ویرایش سطح دو کاربر '.$user->email,$request->ip());
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');
    }
    function level2File(Request $request){
        $user = User::find($request->id);
        $identification = json_decode($user->identification_img);
        if( isset($identification)&&isset($identification->file))
            $identification->file = isset($identification)&&isset($identification->file) ? end($identification->file)->url : null;

        $storagePath = $identification->file;
        $storagePath = self::storagePath().$storagePath;
        return \Intervention\Image\ImageManagerStatic::make($storagePath)->response();
    }
    function level2Status(Request $request){
        $user = User::find($request->id);
        $identification_img = json_decode($user->identification_img);
        $settings = json_decode($user->settings);

        if($request->status == 'confirm'){
            $identification_img->status = 'confirm';
            $identification_img->time_confirm = time();
            if ($user->level < 2)
                $user->level = 2;
            $result = array('status'=>true ,'msg'=>'با موفقیت تایید شد.');
            // send Notif
            $this->sendNotification($user->id,'confirmLevelUser',['level'=>2,'sms'=>[2]]);

            $job = (new \App\Jobs\Award\RegisterLevel2($user->id))->delay(\Carbon\Carbon::now()->addSeconds(0));
            //dispatch($job);

        }else if ($request->status == 'reject'){
            $identification_img->status = 'reject';
            $identification_img->{'reason_'. ($settings->localization??'fa')} = $request->reason;
            $result = array('status'=>true ,'msg'=>'با موفقیت رد شد.');
            // send Notif
            $this->sendNotification($user->id,'rejectLevelUser', ['level'=>2,'sms'=>[2]]);
        }

        end($identification_img->file)->id_admin = \Auth::user()->id;
        end($identification_img->file)->admin = \Auth::user()->name;
        end($identification_img->file)->admin_email = \Auth::user()->email;
        end($identification_img->file)->status = $request->status;
        end($identification_img->file)->reason = $request->reason;
        end($identification_img->file)->date_admin = date('Y-m-d H:i:s');

        $user->identification_img = json_encode($identification_img);
        $user->save();

        self::logSave('users.edit',$request->all(),($request->status == 'confirm'?'تایید':'رد کردن').'اطلاعات سطح دو کاربر '.$user->email,$request->ip());
        return  response()->json($result);
    }
    function inquiryMobileNationalCode(Request $request){
        $user = User::find($request->id);
        $function = new \App\Functions;
        $finnotech = $function->GetTokenFinotech();
        if($finnotech['status']){
            $response = \Http::withHeaders(["Authorization"=> "Bearer ".$finnotech['token']])
                ->get('https://apibeta.finnotech.ir/facility/v2/clients/'.$finnotech['client_id'].'/shahkar/verify?trackId='.time()
                    .'&mobile='.$user->mobile.'&nationalCode='.$user->national_code);
            $result = $response->json();
            if($result['status']=='DONE'){
                if($result['result']['isValid'] == true)
                    $result = array('status'=>true,'msg'=> 'کد ملی و موبایل با یکدیگر مطابقت دارد.');
                else
                    $result = array('status'=>false,'msg'=> 'کد ملی و موبایل با یکدیگر مطابقت ندارد.');
            }else
                $result = array('status'=>false,'msg'=> ($result['error']['message']));
        }else
            $result = array('status'=>false,'msg'=> ($finnotech['error']['message']));

        self::logSave('users.edit',$request->all(),'استعلام کد ملی و موبایل کاربر '.$user->email,$request->ip());
        return $result;
    }


    function level3Edit(Request $request){
        $validator = \Validator::make($request->all(), [
            'file' => 'required|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $user = User::find($request->id);

        $auth_img = json_decode($user->auth_img)??(object)array();
        if(isset($request->file)){
            $functions = new \App\Functions;
            $directory = 'uploads/Users/'.$user->created_at->year.'/'.$user->created_at->month.'/'. $user->id;
            $file = $request->file('file');
            $SaveFile = $functions->saveFileImage($file, $directory);
            if($SaveFile != false){
                $auth_img->file = $auth_img->file ?? array();
                array_push($auth_img->file, array('url' => $SaveFile,'date'=>date('Y-m-d H:i:s'),'upload_admin'=>\Auth::user()->email) );
            }

        }
        $user->auth_img = json_encode($auth_img);
        $user->save();

        self::logSave('users.edit',$request->all(),'ویرایش سطح سه کاربر '.$user->email,$request->ip());
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');
    }
    function level3File(Request $request){
        $user = User::find($request->id);
        $auth_img = json_decode($user->auth_img);
        if( isset($auth_img)&&isset($auth_img->file))
            $auth_img->file = isset($auth_img)&&isset($auth_img->file) ? end($auth_img->file)->url : null;

        $storagePath = $auth_img->file;
        $storagePath = self::storagePath().$storagePath;
        return \Intervention\Image\ImageManagerStatic::make($storagePath)->response();
    }
    function level3Status(Request $request){
        $user = User::find($request->id);
        $auth_img = json_decode($user->auth_img);
        $settings = json_decode($user->settings);

        if($request->status == 'confirm'){
            $auth_img->status = 'confirm';
            $auth_img->time_confirm = time();
            if ($user->level < 3)
                $user->level = 3;
            $result = array('status'=>true ,'msg'=>'با موفقیت تایید شد.');
            // send Notif
            $this->sendNotification($user->id,'confirmLevelUser',['level'=>3,'sms'=>[3]]);
        }else if ($request->status == 'reject'){
            $auth_img->status = 'reject';
            $auth_img->{'reason_'. ($settings->localization??'fa')} = $request->reason;
            $result = array('status'=>true ,'msg'=>'با موفقیت رد شد.');
            // send Notif
            $this->sendNotification($user->id,'rejectLevelUser',['level'=>3,'sms'=>[3]]);
        }

        end($auth_img->file)->id_admin = \Auth::user()->id;
        end($auth_img->file)->admin = \Auth::user()->name;
        end($auth_img->file)->admin_email = \Auth::user()->email;
        end($auth_img->file)->status = $request->status;
        end($auth_img->file)->reason = $request->reason;
        end($auth_img->file)->date_admin = date('Y-m-d H:i:s');

        $user->auth_img = json_encode($auth_img);
        $user->save();

        self::logSave('users.edit',$request->all(),($request->status == 'confirm'?'تایید':'رد کردن').'اطلاعات سطح سه کاربر '.$user->email,$request->ip());
        return  response()->json($result);
    }


    function level4Edit(Request $request){
        $validator = \Validator::make($request->all(), [
            'address' => 'nullable|min:3',
            'phone' => 'nullable|numeric',
            'file' => 'nullable|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $user = User::find($request->id);

        $address = json_decode($user->address)??(object)array();
        if(!isset($address->status))
            $address->status = 'confirm';
        $address->address = $request->address;
        $address->zipcode = $request->zipcode;

        /*
        $address->file = [];
        if(isset($request->file)){
            $functions = new \App\Functions;
            $directory = 'uploads/Users/'.$user->created_at->year.'/'.$user->created_at->month.'/'. $user->id;
            $file = $request->file('file');
            $SaveFile = $functions->saveFileImage($file, $directory);
            if($SaveFile != false){
                //$address->file = $auth_img->file ?? array();
                array_push($address->file, array('url' => $SaveFile,'date'=>date('Y-m-d H:i:s'),'upload_admin'=>\Auth::user()->email) );
            }
        }
        end($address->file)->address = $request->address;
        */
        $user->address = json_encode($address);
        $user->phone = $request->phone;
        $user->save();

        self::logSave('users.edit',$request->all(),'ویرایش سطح چهار کاربر '.$user->email,$request->ip());
        return array('status' => true, 'msg' => 'با موفقیت ثبت شد.');
    }
    function level4File(Request $request){
        $user = User::find($request->id);
        $address = json_decode($user->address);
        if( isset($address)&&isset($address->file))
            $address->file = isset($address)&&isset($address->file) ? end($address->file)->url : null;

        $storagePath = $address->file;
        $storagePath = self::storagePath().$storagePath;
        return \Intervention\Image\ImageManagerStatic::make($storagePath)->response();
    }
    function level4Status(Request $request){
        $user = User::find($request->id);
        $address = json_decode($user->address);
        $settings = json_decode($user->settings);

        if($request->status == 'confirm'){
            $address->status = 'confirm';
            if ($user->level < 4)
                $user->level = 4;
            $result = array('status'=>true ,'msg'=>'با موفقیت تایید شد.');
            // send Notif
            $this->sendNotification($user->id,'confirmLevelUser',['level'=>4,'sms'=>[4]]);
        }else if ($request->status == 'reject'){
            $address->status = 'reject';
            $address->{'reason_'. ($settings->localization??'fa')} = $request->reason;
            $result = array('status'=>true ,'msg'=>'با موفقیت رد شد.');
            // send Notif
            $this->sendNotification($user->id,'rejectLevelUser',['level'=>4,'sms'=>[4]]);
        }

        end($address->file)->id_admin = \Auth::user()->id;
        end($address->file)->admin = \Auth::user()->name;
        end($address->file)->admin_email = \Auth::user()->email;
        end($address->file)->status = $request->status;
        end($address->file)->reason = $request->reason;
        end($address->file)->date_admin = date('Y-m-d H:i:s');

        $user->address = json_encode($address);
        $user->save();

        self::logSave('users.edit',$request->all(),($request->status == 'confirm'?'تایید':'رد کردن').'اطلاعات سطح چهار کاربر '.$user->email,$request->ip());
        return  response()->json($result);
    }


    function settingsEdit(Request $request){
        $user = User::find($request->id);
        $user->level = $request->level;
        $settings = json_decode($user->settings??'{}');
        $twofa = json_decode($user->twofa??'{}');
        $notifications = $request->notifications;
        foreach ($notifications as $key=>$value){
            //dd($key,$value,$request->notifications[$key]);
            $notifications[$key] = ($value=="1") ? true :false;
        }
        $settings->notifications = $notifications;

        $settings->localization = $request->localization ? $request->localization :'fa';
        $settings->access_digital_money = $request->accessDigitalMoney ? true : false;
        $settings->withdrawal_limit = $request->withdrawalLimit ? true : false;
        $settings->withdrawal_crypto = (!isset($request->withdrawalCrypto) || $request->withdrawalCrypto) ? true : false;
        $settings->deposit_receipt = $request->depositReceipt ? true : false;
        $user->settings = json_encode($settings);

        if($request->twofa == false)
            $twofa->status = false;
        else{
            $twofa->type = $request->twofa;
            $twofa->status = true;
        }
        $user->twofa = json_encode($twofa);

        if(isset($request->password))
            $user->password = \bcrypt($request->password);

        // referral
        /*
        if($request->referralId){
            $userReferral = User::find($request->referralId);
            if(!isset($userReferral))
                return array('status'=>false ,'msg'=>'شناسه کاربر معرف یافت نشد!');

            $referral = UserReferral::where('id_user_referral',$user->id)->first();
            if(!isset($referral))
                $referral = new UserReferral;
            $referral->id_user_caller = $userReferral->id;
            $referral->id_user_referral = $user->id;
            $referral->save();
        }else{
            $referral = UserReferral::where('id_user_referral',$user->id)->delete();
        }
        */

        $user->save();

        self::logSave('users.edit',$request->all(),'ویرایش تنظیمات کاربر '.$user->email,$request->ip());
        return array('status'=>true ,'msg'=>'با موفقیت تغییر کرد.');

    }
}
