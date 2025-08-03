<?php
namespace App\Http\Controllers\Gift;
use App\Http\Controllers\Controller;

use App\Models\Cryptocurrency;
use App\Models\GiftCard;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Morilog\Jalali;

class GiftCardController extends Controller
{
    function listGift(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = GiftCard::query();
        $query->leftJoin('cryptocurrency','gift_card.id_crypto','cryptocurrency.id');
        $query->leftJoin('users','gift_card.id_user_issuer','users.id');
        $query->select('symbol','gift_card.id','amount','card_namber','started','expired','activated','count','gift_card.created_at','gift_card.data','id_user_consumer',
            'users.id as id_issuer','users.name as name_issuer','users.family as family_issuer','users.mobile as mobile_issuer','level_account');

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();

        $gifts = $query->paginate($limit)->items();
        foreach ($gifts as $gift) {
            if(isset($gift->id_user_consumer)){
                $gift->consumer = User::select('name','family','email','mobile','level_account',)->find($gift->id_user_consumer);
            }
            if (isset($gift->expired) && $gift->expired > date('Y-m-d H:i:s')){
                $dtCurrent = \DateTime::createFromFormat('U', time());
                $dtCreate = \DateTime::createFromFormat('U', strtotime($gift->expired));
                $diff = $dtCurrent->diff($dtCreate);
                $interval = $diff->format("%y سال %m ماه %d روز %h ساعت %i دقیقه");
                $gift->interval = preg_replace('/(^0| 0) (سال|ماه|روز|ساعت|دقیقه|ثانیه)/', '', $interval);
            }
            $gift->created = $this->convertDate($gift->created_at, 'Y/m/d - H:i');
            $gift->started = isset($gift->started) ? $this->convertDate($gift->started, 'Y/m/d - H:i') : null;
            $gift->expired = isset($gift->expired) ? $this->convertDate($gift->expired, 'Y/m/d - H:i') : null;
            $gift->activated = isset($gift->activated) ? $this->convertDate($gift->activated, 'Y/m/d - H:i') : null;
        }
        $result->list = $gifts;
        $result->total = $totalCount;

        return response()->json($result);
    }


    function filters($query,$request){
        $search = $request->search;
        switch ($request->sortBy){
            case 'cardNamber': $sortBy = 'card_namber'; break;
            case 'issuer': $sortBy = 'id_user_issuer'; break;
            case 'consumer': $sortBy = 'id_user_consumer'; break;
            case 'created': $sortBy = 'gift_card.created_at'; break;
            case 'activated': $sortBy = 'gift_card.activated'; break;
            case 'expired': $sortBy = 'gift_card.expired'; break;
            case 'count': $sortBy = 'count'; break;
            case 'id': $sortBy = 'gift_card.id'; break;
            default: $sortBy = $request->sortBy;
        }
        if (!empty($search)) {
            $fields = ['card_namber','users.name','users.family','cryptocurrency.symbol','cryptocurrency.name','cryptocurrency.locale'];
            $query = $query->where(function ($query) use ($search, $fields) {
                foreach ($fields as $field)
                    $query->orWhere($field, 'like', '%' . $search . '%');
            });
        }
        if (isset($request->status)){
            if($request->status == 'active')
                $query->whereNull('activated')->orWhere('count','>',0);
            else
                $query->whereNotNull('activated')->orWhere('count',0);
        }
        if(isset($request->id_user)){
            $id_user = (int)$request->id_user;
            $query->where(function ($query) use($id_user){
                $query->where('id_user_issuer',$id_user);
                $query->orWhere('id_user_consumer',$id_user);
                $query->orWhereRaw("JSON_CONTAINS(gift_card.data, ?)",[json_encode(array('id_user_used' => [$id_user]))]);
            });
        }

        if (isset($request->for)){
            if($request->for == 'admins')
                $query->whereNull('id_user_issuer');
            else
                $query->whereNotNull('id_user_issuer');
        }

        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }


    function addGift(Request $request){
        $validator = \Validator::make($request->all(), [
            'name'    => 'required|max:15',
            'count'    => 'required|numeric',
            'amount'    => 'required',
            'symbol'    => 'required',
            'started'    => 'required',
            'expired'    => 'required',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        if(isset($request->id)){
            $giftExist = GiftCard::where('card_namber',$request->name)->where('id','!=',$request->id)->first();
            if(isset($giftExist))
                return array('status' => false, 'msg' => 'کد تخففیی قبلا با این اسم درج شده است.');
            $gift = GiftCard::find($request->id);
        }else{
            $giftExist =  GiftCard::where('card_namber',$request->name)->first();
            if(isset($giftExist))
                return array('status' => false, 'msg' => 'کد تخففیی قبلا با این اسم درج شده است.');
            $gift = new GiftCard();
            $gift->data = json_encode(['id_user_used'=>[]]);
        }

        $crypto = Cryptocurrency::where('symbol',$request->symbol)->first();
        if(!isset($crypto))
            return array('status' => false, 'msg' => 'سیمبل درج شده در لیست ارز ها نیست');




        $gift->amount = $request->amount;
        $gift->amount_hash = Crypt::encryptString($request->amount);
        $gift->card_namber = $request->name;
        $gift->card_number_hash =  Crypt::encryptString($request->name);
        $gift->id_crypto = $crypto->id;

        $gift->count = $request->count;
        $gift->started = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->started);
        $gift->expired = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->expired);
        $gift->save();

        return array('status' => true, 'msg' => 'با موفقیت '.(isset($request->id)?'ویرایش':'درج').' شد.');

    }

    function singleGift(Request $request){
        $gift = GiftCard::find($request->id);
        $gift->started = $this->convertDate($gift->started, 'Y/m/d H:i');
        $gift->expired = $this->convertDate($gift->expired, 'Y/m/d H:i');
        $gift->symbol = Cryptocurrency::find($gift->id_crypto)->symbol;
        return $gift;
    }

    function removeGift(Request $request){
        try {
            $gift = GiftCard::find($request->id);
            $gift->delete();
            return array('status' => true, 'msg' => 'با موفقیت حذف شد.');
        }catch (\Exception $e){
            return array('status' => false, 'msg' => 'حذف امکان پذیر نیست!');
        }
    }

    function statistic(){
        $statistic = (object)array();
        $statistic->all = GiftCard::count();
        $statistic->active = GiftCard::whereNull('activated')->count();
        $statistic->anount_usdt = GiftCard::selectRaw('ROUND(SUM(JSON_UNQUOTE(JSON_EXTRACT(data, "$.amount_usdt")))) as sum')->first()->sum;
        $statistic->anount_toman = GiftCard::selectRaw('ROUND(SUM(JSON_UNQUOTE(JSON_EXTRACT(data, "$.amount_toman")))) AS sum')->first()->sum;
        return response()->json($statistic);
    }
}
