<?php


namespace App;
use Auth;

class Functions extends Http\Controllers\Controller
{
    function saveFileImage($file,$path){
        $extension = $file->getClientOriginalExtension();
        $just_name = str_replace(" ", "-", pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $just_name_hash = str_replace("/", "", app('hash')->make($just_name  .time()));
        $name = $just_name_hash.'.'.$extension;

        $ImageManager = new \Intervention\Image\ImageManager(array('driver' => 'gd'));//imagick
        $Image= $ImageManager->make($file);

        //$Image = \Intervention\Image\ImageManagerStatic::make($file); //gd

        if ($Image->width() > 3500) {
            $Image->resize(3500, null, function ($constraint) {
                $constraint->aspectRatio();
            });
        }
        if ($Image->height() > 3000) {
            $Image->resize(null, 3000, function ($constraint) {
                $constraint->aspectRatio();
            });
        }


        $storagePath = self::storagePath();
        $directory = $storagePath.$path ;
        $file_path = $directory . '/'. $name;
        if (!file_exists($file_path)) {
            \Illuminate\Support\Facades\File::makeDirectory($directory, $mode = 0777, true, true);
            if ($Image->save($directory . '/'. $name)) {
                return str_replace( $storagePath,'',$file_path);
            }
        }
        return false;
    }

    function saveFile($file,$path,$name = null){
        $extension = $file->getClientOriginalExtension();
        if(!isset($name))
            $just_name = str_replace(" ", "-", pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        else
            $just_name = $name;
        $name = $just_name.'.'.$extension;

        $directory = $path ;
        $file_path = $directory . '/'. $name;
        if (file_exists(storage_path($file_path))){
            $name = $just_name.'-'.time().'.'.$extension;
            $file_path = $directory . '/'. $name;
        }

        \Illuminate\Support\Facades\File::makeDirectory(storage_path($directory), $mode = 0777, true, true);
        if ($file->move(storage_path($directory), $name)) {
            return array('status'=>true ,'url'=>$file_path ,'name'=>$name);
        }
        return array('status'=>false);
    }

    function hideEmail($email)
    {
        $em   = explode("@",$email);
        $name = implode('@', array_slice($em, 0, count($em)-1));
        $len  = floor(strlen($name)/2);

        return substr($name,0, $len) . str_repeat('*', $len) . "@" . end($em);
    }
    function hideMobile($mobile)
    {
        $number = $mobile;
        $middle_string ="";
        $length = strlen($number);
        $part_size = ceil( $length / 3 ) ;
        $middle_part_size = $length - ( $part_size * 2 );
        for( $i=0; $i < $middle_part_size ; $i ++ ){
            $middle_string .= "*";
        }
        $star = substr($number, 0, $part_size ) . $middle_string  . substr($number,  - $part_size );
        return $star;
    }


    function GetTokenFinotech(){
        $finnotech_setting = \App\Models\Settings::where('name','finnotech')->first();
        $finnotech = json_decode($finnotech_setting->value);
        if(isset($finnotech->token) && $finnotech->token->time > (time()-77600) ) {// -9 days
            return array('status'=>true, 'token'=>$finnotech->token->value, 'client_id'=>$finnotech->client_id);
        }else{
            $url = 'https://apibeta.finnotech.ir/dev/v2/oauth2/token';
            $param = array(
                "grant_type"=> "client_credentials",
                "nid"=> $finnotech->codemeli,
                "scopes"=> "card:information:get,oak:iban-inquiry:get,facility:shahkar:get"
            );
            $response = \Http::withHeaders(['Authorization' => "Basic ".$finnotech->key])->post($url,$param);
            $result = (object) $response->json();

            if($response->status() == 200 && $result->status =='DONE'){
                $finnotech->token = (object)array();
                $finnotech->token->time = time();
                $finnotech->token->value = $result->result['value'];
                $finnotech_setting->value = json_encode($finnotech);
                $finnotech_setting->save();
                return array('status'=>true, 'token'=>$result->result['value'], 'client_id'=>$finnotech->client_id);
            }else{
                \Log::channel('ErrorApi')->info("finnotech Get Token Error:". json_encode($result));
                return array('status'=>false, 'token'=> null);
            }
        }
    }

    function sendMsgFirebase($title,$msg,$image = null,$sound = null){
        $notification = [
            'click_action' => 'https://app.arz8.com/tools/notifications',
            'title' => $title,
            'body' => $msg,
            'icon' =>  env('MAIL_SRC_URL')."logo.png",
            'image' => $image,
            'sound' => $sound,
        ];
        try {
            $factory = (new \Kreait\Firebase\Factory)->withServiceAccount(env('FIREBASE_CREDENTIALS'));
            $messaging = $factory->createMessaging();
            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('topic', 'all')
                ->withNotification($notification);
            $report = $messaging->send($message);
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            return ['error' => 'Error sending notification: ' . $e->getMessage()];
        }

    }
}
