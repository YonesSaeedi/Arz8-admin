<?php


namespace App\Http\Controllers\Users;
use App\Http\Controllers\Controller;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use Morilog\Jalali;

class LoginHistoryController extends Controller
{
    function listLogin(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = UserLogin::query();

        $query->leftJoin('users','users_login_history.id_user','users.id');
        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $query->select('users_login_history.*','users.name','users.name_display','users.family','users.email');
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
            case 'date': $sortBy = 'users_login_history.created_at'; break;
            case 'id': $sortBy = 'users_login_history.id'; break;
            case 'users_login_history': $sortBy = 'users_login_history.id'; break;
            default: $sortBy = $request->sortBy;
        }


        if (!empty($search)) {
            $fields = ['users_login_history.id', 'via', 'agent', 'os', 'device','browser','ip','users_login_history.twofa', 'name', 'mobile', 'email', 'family'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->twofa)) {
            if($request->twofa == 'nothing')
                $query->whereNull('users_login_history.twofa');
            else
                $query->where('users_login_history.twofa', $request->twofa);
        }
        if (isset($request->via)) {
            $query->where('users_login_history.via', $request->via);
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('users_login_history.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_login_history.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        if (isset($request->id_user)) {
            $query->where('users_login_history.id_user', $request->id_user);
        }

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function singleCard(Request $request){
        $card = UserCardBank::find($request->id);
        $card->date = $this->convertDate($card->created_at, 'd F Y - H:i');
        return response()->json(array('status'=>true ,'msg'=>'', 'card'=> $card));
    }

    function editCard(Request $request){
        $validator = \Validator::make($request->all(), [
            'bankName'    => 'required|max:50',
            'cardNumber'    => 'required|numeric|digits:16',
            'iban' => 'required|max:33',
            //'AccountNumber' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $result = array('status' => false, 'msg' => $validator->errors()->first());
            return response()->json($result);
        }

        $CardBank = UserCardBank::find($request->id);
        $CardBank->bank_name = $request->bankName;
        $CardBank->card_number = $request->cardNumber;
        $CardBank->account_number = $request->accountNumber;
        $CardBank->iban = $request->iban;
        $CardBank->name_family = $request->nameFamily;

        $data = json_decode($CardBank->data)??(object)[];
        $data->changeAdmin = $data->changeAdmin??[];
        $request->request->add(['admin' =>  \Auth::user()->name]);
        array_push($data->changeAdmin ,$request->all());
        $CardBank->data = json_encode($data);

        if($CardBank->save())
            $result = array('status'=> true , 'msg'=> __('Successfully registered.') );
        else
            $result = array('status'=> false , 'msg'=> __('Registration failed.') );

        self::logSave('cardbank.edit',$request->all(), 'وایرایش کارت بانکی #'.$CardBank->id,$request->ip());
        return response()->json($result);
    }

    function statusCard(Request $request){
        $card = UserCardBank::find($request->id);
        $card->id_admin = \Auth::user()->id;

        if($request->type == 'confirm' && $card->status != 'confirm'){
            $keyword = 'cardConfirm';
            $card->status = 'confirm';

            $data = json_decode($card->data)??(object)[];
            $data->statusAdmin = $data->statusAdmin??[];
            $request->request->add(['admin' =>  \Auth::user()->name]);
            array_push($data->statusAdmin ,$request->all());
            $card->data = json_encode($data);
            $card->save();
            $result = array('status'=> true , 'msg'=> 'با موفقیت تایید شد.' );

        }else{
            $keyword = 'cardReject';
            $card->status = 'reject';

            $data = json_decode($card->data)??(object)[];
            $data->statusAdmin = $data->statusAdmin??[];
            $request->request->add(['admin' =>  \Auth::user()->name]);
            array_push($data->statusAdmin ,$request->all());
            $data->reject_reason = $request->reason;
            $card->data = json_encode($data);
            $card->save();
            $result = array('status'=> true , 'msg'=> 'با موفقیت رد شد.' );
        }

        // send Notif
        $this->sendNotification($card->id_user,$keyword,
            array('sms'=>null,'cardnumber'=>$card->card_number,'iban'=>$card->iban));

        self::logSave('cardbank.status',$request->all(), ($request->type == 'confirm'?'تایید':'ریجکت').' کارت بانکی #'.$card->id,$request->ip());
        return $result;
    }


    function inquiryCard(Request $request){
        $card = UserCardBank::find($request->id);
        if($request->company == 'finnotech'):
            $function = new \App\Functions;
            $finnotech = $function->GetTokenFinotech();
            if($finnotech['status']){
                $response = \Http::withHeaders(["Authorization"=> "Bearer ".$finnotech['token']])
                    ->get('https://apibeta.finnotech.ir/mpg/v2/clients/'.$finnotech['client_id'].'/cards/'.$card->card_number.'?trackId='.time());
                $result = $response->json();
            }else
                $result = $finnotech;
        else:
            $token = json_decode(Crypt::decryptString(Settings::where('name', 'cardinfo')->first()->value));
            $response = \Http::withHeaders(["Authorization"=> "Bearer ".$token->token])
                ->get('https://cardinfo.ir/inquiry/apiv1?api=card_sheba&card='.$card->card_number);
            $result = $response->json();
        endif;

        self::logSave('cardbank.inquiry',$request->all(), 'استعلام شمار کارت #'.$card->id,$request->ip());
        return $result;
    }
    function inquiryIban(Request $request){
        $card = UserCardBank::find($request->id);
        $iban = str_replace(['-',' '],'',$card->iban);
        if($request->company == 'finnotech'):
            $function = new \App\Functions;
            $finnotech = $function->GetTokenFinotech();
            if($finnotech['status']){
                $response = \Http::withHeaders(["Authorization"=> "Bearer ".$finnotech['token']])
                    ->get('https://apibeta.finnotech.ir/oak/v2/clients/'.$finnotech['client_id'].'/ibanInquiry?trackId='.time().'&iban='.$iban);
                $result = $response->json();
            }else
                $result = $finnotech;
        else:
            $token = json_decode(Crypt::decryptString(Settings::where('name', 'cardinfo')->first()->value));
            $response = \Http::withHeaders(["Authorization"=> "Bearer ".$token->token])
                ->get('https://cardinfo.ir/inquiry/apiv1?api=sheba_info&sheba=IR'.$iban);
            $result = $response->json();
        endif;
        self::logSave('cardbank.inquiry',$request->all(), 'استعلام شبا #'.$card->id,$request->ip());
        return $result;
    }
}
