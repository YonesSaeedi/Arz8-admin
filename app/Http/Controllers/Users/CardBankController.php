<?php


namespace App\Http\Controllers\Users;
use App\Http\Controllers\Controller;
use App\Models\UserCardBank;
use App\Models\Settings;
use App\Models\UserDepositId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt as Crypt;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali;

class CardBankController extends Controller
{
    function listCard(Request $request) {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = UserCardBank::query();

        // Join users table to access user fields like 'name', 'email', etc.
        $query->leftJoin('users', 'users_cardbank.id_user', '=', 'users.id');

        // Eager Load relations (users and user_deposit_id)
        $query->with([
            /*'user' => function ($query) {
                $query->select( 'id','name', 'family', 'email', 'mobile');  // Only select necessary fields from users table
            },*/
            'userDepositIds' => function ($query) {
                $query->select('deposit_id', 'payment_gateway', 'id_cardbank');  // Only select necessary fields from user_deposit_id table
            }
        ]);

        // Apply filters
        $query = self::filters($query, $request);
        $totalCount = $query->count();

        // Select necessary columns for the result (users_cardbank fields only)
        $query->select('users_cardbank.*','users.name','users.family','users.email','users.mobile');  // Select only users_cardbank fields

        // Paginate the results
        $cardbanks = $query->paginate($limit);

        // Iterate over the results and add custom fields
        foreach ($cardbanks as $card) {
            // Convert date
            $card->date = $this->convertDate($card->created_at, 'd F Y - H:i');

            // Get the depositId (already eager loaded, so no need for additional query)
            $card->depositId = $card->userDepositIds->first(); // If userDepositIds is a collection, use first() to get the latest
        }

        $result->lists = $cardbanks->items();
        $result->total = $totalCount;

        return response()->json($result);
    }



    function filters($query, $request) {
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'name'; break;
            case 'date': $sortBy = 'users_cardbank.created_at'; break;
            case 'id': $sortBy = 'users_cardbank.id'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            // Fields to search (from both users_cardbank and users)
            $fields = [
                'users_cardbank.id',
                'users_cardbank.data',
                'name_family',
                'bank_name',
                'card_number',
                'account_number',
                'iban',
                'users.name',  // Add 'users.name' for searching in users table
                'users.mobile',
                'users.email',
                'users.family'
            ];

            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', '%' . $search . '%');
                }
            });
        }

        if (isset($request->status)) {
            $query->where('status', $request->status);
        }
        if (isset($request->bank)) {
            $query->where('bank_name', $request->bank);
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('users_cardbank.created_at', '>=', $dateStart);
            } catch (\Exception $e) {}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_cardbank.created_at', '<=', $dateStop);
            } catch (\Exception $e) {}
        }

        if (isset($request->id_user)) {
            $query->where('users_cardbank.id_user', $request->id_user);
        }

        if (isset($sortBy)) {
            $query->orderBy($sortBy, $request->sortDesc ? 'desc' : 'asc');
        }

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
                $response = Http::withHeaders(["Authorization"=> "Bearer ".$finnotech['token']])
                    ->get('https://apibeta.finnotech.ir/mpg/v2/clients/'.$finnotech['client_id'].'/cards/'.$card->card_number.'?trackId='.time());
                $result = $response->json();
            }else
                $result = $finnotech;
        elseif($request->company == 'cardinfo'):
            $token = json_decode(Crypt::decryptString(Settings::where('name', 'cardinfo')->first()->value));
            $response = Http::withHeaders(["Authorization"=> "Bearer ".$token->token])
                ->get('https://cardinfo.ir/inquiry/apiv1?api=card_sheba&card='.$card->card_number);
            $result = $response->json()??[];
        elseif($request->company == 'zibal'):
            $zibal = \App\Models\PaymentGateway\PaymentGateway::where('name','zibal')->first();
            $dataZibal = json_decode($zibal->data);
            $withdraw_token = $dataZibal->withdraw_token;
            $response = Http::withHeaders(["Authorization"=> "Bearer ".$withdraw_token])
                ->post('https://api.zibal.ir/v1/facility/cardToIban/',['cardNumber'=>$card->card_number]);
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
        elseif($request->company == 'cardinfo'):
            $token = json_decode(Crypt::decryptString(Settings::where('name', 'cardinfo')->first()->value));
            $response = \Http::withHeaders(["Authorization"=> "Bearer ".$token->token])
                ->get('https://cardinfo.ir/inquiry/apiv1?api=sheba_info&sheba=IR'.$iban);
            $result = $response->json()??[];;
        elseif($request->company == 'zibal'):
            $zibal = \App\Models\PaymentGateway\PaymentGateway::where('name','zibal')->first();
            $dataZibal = json_decode($zibal->data);
            $withdraw_token = $dataZibal->withdraw_token;
            $response = Http::withHeaders(["Authorization"=> "Bearer ".$withdraw_token])
                ->post('https://api.zibal.ir/v1/facility/ibanInquiry/',['IBAN'=>'IR'.$iban]);
            $result = $response->json();
        endif;
        self::logSave('cardbank.inquiry',$request->all(), 'استعلام شبا #'.$card->id,$request->ip());
        return $result;
    }


    function listDepositId(Request $request){
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;
        $offset = (($request->page - 1) ?? 0) *$limit;

        $result = (object)array();
        $query = UserDepositId::query();

        $query->leftJoin('users_cardbank','users_deposit_id.id_cardbank','users_cardbank.id');
        $query->leftJoin('users','users_deposit_id.id_user','users.id');

        // Filters
        $query = self::filtersDepositId($query,$request);
        $totalCount = $query->count();


        $query->select('users_deposit_id.*','bank_name','iban','users.name','users.family','users.email','users.mobile');

        $query->limit($limit)->offset($offset);
        $cardbanks = $query->get();
        foreach ($cardbanks as $card) {
            $card->date = $this->convertDate($card->created_at, 'd F Y - H:i');
            $card->depositId = UserDepositId::select('deposit_id','payment_gateway')->where('id_cardbank',$card->id)->latest()->first();
        }
        $result->lists = $cardbanks;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filtersDepositId($query,$request){
        $search = $request->search;

        switch ($request->sortBy){
            case 'nameFamily': $sortBy = 'users.name'; break;
            case 'date': $sortBy = 'users_deposit_id.created_at'; break;
            case 'id': $sortBy = 'users_deposit_id.id'; break;
            default: $sortBy = $request->sortBy;
        }

        if (!empty($search)) {
            $fields = ['deposit_id','destination_owner_name','users_cardbank.id', 'users_cardbank.data', 'name_family', 'bank_name', 'card_number', 'account_number','iban', 'users.name', 'mobile', 'email', 'family'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }

        if (isset($request->bank)) {
            $query->where('bank_name', $request->bank);
        }
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('users_deposit_id.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('users_deposit_id.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }

        if (isset($request->id_user)) {
            $query->where('users_deposit_id.id_user', $request->id_user);
        }

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }
}
