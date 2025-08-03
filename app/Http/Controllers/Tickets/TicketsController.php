<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali;
use Auth;

class TicketsController extends Controller
{
    function listTickets(Request $request)
    {
        $limit = isset($request->perPage) && $request->perPage <= 100 ? $request->perPage : 10;

        $result = (object)array();
        $query = Ticket::query();

        $query->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'family', 'email', 'mobile', 'name_display', 'level_account');
            }
        ]);

        // Filters
        $query = self::filters($query,$request);
        $totalCount = $query->count();


        $query->select('id','id_user','updated_at','title','status');
        $tickets = $query->paginate($limit)->items();
        foreach ($tickets as $ticket) {
            $ticket->date = $this->convertDate($ticket->updated_at, 'd F Y - H:i');
        }
        $result->list = $tickets;
        $result->total = $totalCount;

        return response()->json($result);
    }

    function filters($query,$request){
        if (isset($request->dateStart)) {
            try{
                $dateStart = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStart);
                $query->where('ticket.created_at','>=', $dateStart);
            }catch(\Exception $e){}
        }
        if (isset($request->dateStop)) {
            try{
                $dateStop = Jalali\CalendarUtils::createCarbonFromFormat('Y/m/d H:i', $request->dateStop);
                $query->where('ticket.created_at','<=', $dateStop);
            }catch(\Exception $e){}
        }
        if (isset($request->via))
            $query->where('ticket.via', $request->via);
        if (isset($request->id)){
            $ids = explode(',',$request->id);
            $query->whereIn('ticket.id', $ids);
        }

        if (isset($request->id_user)){
            $query->where('ticket.id_user', $request->id_user);
        }
        if (isset($request->status)) {
            $query->where('ticket.status', $request->status);
        }

        $search = $request->search;
        if (!empty($search)) {
            $fields = ['ticket.id', 'title'];
            $query->where(function ($query) use ($search, $fields) {
                $searchTerm = '%' . $search . '%';
                $query->whereRaw(
                    '(' . implode(' OR ', array_map(function ($field) use ($searchTerm) {
                        return "$field LIKE ?";
                    }, $fields)) . ')',
                    array_fill(0, count($fields), $searchTerm)
                );
                $query->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('family', 'like', '%' . $search . '%');
                });
            });
        }

        switch ($request->sortBy){
            case 'date': $sortBy = 'ticket.updated_at'; break;
            case 'id': $sortBy = 'ticket.id'; break;
            default: $sortBy = $request->sortBy;
        }
        if(isset($sortBy))
            $query->orderBy($sortBy,$request->sortDesc?'desc':'asc');
        return $query;
    }

    function statistic(){
        $statistic = (object)array();
        $statistic->total_tickets = Ticket::count();
        $statistic->pending_tickets = Ticket::where('status','awaiting answer')->count();
        $statistic->total_messages = TicketMessage::count();
        $statistic->open_tickets = Ticket::where('status','!=','closed')->count();
        return response()->json($statistic);
    }


    function singleTicket(Request $request){
        $ticket = Ticket::find($request->id);
        $ticket->seen_admin = 1;
        $ticket->timestamps = false;
        $ticket->save();

        $ticket->created = $this->convertDate($ticket->created_at, 'Y/m/d - H:i');
        $ticket->updated = $this->convertDate($ticket->updated_at, 'Y/m/d - H:i');

        $ticket_message = TicketMessage::where('id_ticket',$ticket->id)->orderBy('created_at')->get();
        foreach($ticket_message as $message){
            $message->time = $this->convertDate($message->created_at,'d F Y H:i');
            if($message->file_link){
                $message->file = Crypt::encryptString($message->file_link);
            }

            $admin = AdminUser::find($message->id_admin);
            if($admin)
                $message->admin = $admin->name;

            unset($message->file_link,$message->id_admin,$message->id_ticket,$message->created_at,$message->updated_at);
        }

        $pattern = (array)json_decode(Auth::user()->ticket_pattern);
        $setting = \App\Models\Settings::where('name','ticketPattern')->first();
        $ticketPattern = (array)json_decode($setting->value);
        $pattern = array_merge($pattern,$ticketPattern);

        $user = User::select('id','name','family','email','mobile','name_display','info')->where('id',$ticket->id_user)->first();
        $info = json_decode($user->info ?? '{}');
        $user->avatar = $info->account_profile_img ?? null;
        unset($user->info);

        return response()->json(array('status'=>true ,'msg'=>'', 'ticket'=> $ticket, 'ticket_message'=> $ticket_message,'user'=>$user,'pattern'=>$pattern));
    }


    function ticketSingleInsert(Request $request){
        $validator = \Validator::make($request->all(), [
            'message' => 'required',
            'file' => 'nullable|mimes:jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails())
            return array('status' => false, 'msg' => $validator->errors()->first());

        $ticket = Ticket::where('id',$request->id)->first();
        $user = User::find($ticket->id_user);
        if($ticket):
            DB::beginTransaction();
            try {
                $ticket_message = new TicketMessage;
                $ticket_message->id_ticket = $request->id;
                $ticket_message->message = $request->message;
                $ticket_message->id_admin = Auth::user()->id;
                $ticket_message->author = 'admin';
                $ticket_message->save();

                if (isset($request->file)) {
                    $functions = new \App\Functions;
                    $file = $request->file('file');
                    $directory = 'uploads/Users/' .$user->created_at->year . '/' . $user->created_at->month . '/' . $user->id.'/Ticket/';
                    $SaveFile = $functions->saveFileImage($file, $directory);
                    if($SaveFile != false){
                        $ticket_message->file_link = $SaveFile;
                    }
                }
                $ticket_message->save();

                $ticket->seen_user = 0;
                $ticket->status = 'answered';
                $ticket->save();


                // send Notif
                $this->sendNotification($user->id,'ticket', array('sms'=>[$ticket->id],'title'=>$ticket->title));

                DB::commit();
                return array('status' => true, 'msg' => __('done successfully'));

            } catch (\Exception $e) {
                DB::rollback();
                $result = array('status' => 'false', 'msg' =>  $e->getMessage());
            }
        else:
            $result = array('status' => 'false', 'msg' => 'Error');
        endif;

        return $result;
    }

    function removeTicket(Request $request){
        try {
            $ticket = Ticket::find($request->id);
            TicketMessage::where('id_ticket',$ticket->id)->delete();
            $ticket->delete();

            $user = User::find($ticket->id_user);
            self::logSave('ticket.remove', ['id' => $ticket->id], 'حذف تیکت کاربر ' . $user->email,$request->ip());
            return array('status' => true, 'msg' => __('done successfully'));
        }catch (\Exception $e){
            return array('status' => 'false', 'msg' => 'Error');
        }
    }

    function closeTicket(Request $request){
        try {
            $ticket = Ticket::find($request->id);
            $ticket->status = 'closed';
            $ticket->save();
            return array('status' => true, 'msg' => __('done successfully'));
        }catch (\Exception $e){
            return array('status' => 'false', 'msg' => 'Error');
        }
    }

    function newPattern(Request $request){
        $admin = AdminUser::find(\Auth::user()->id);
        if (isset($request->global) && $admin->role=='admin' && $request->global==true){
            $setting = \App\Models\Settings::where('name','ticketPattern')->first();
            $ticketPattern = (array)json_decode($setting->value);
            $newPattern = ['title'=>$request->title,'msg'=>$request->msg,'general'=>true];
            array_push($ticketPattern,$newPattern);

            $setting->value = json_encode($ticketPattern);
            $setting->save();
            return array('status' => true, 'msg' => __('done successfully'));
        }else{
            $ticketPattern = (array)json_decode($admin->ticket_pattern);
            $newPattern = ['title'=>$request->title,'msg'=>$request->msg];
            array_push($ticketPattern,$newPattern);

            $admin->ticket_pattern = json_encode($ticketPattern);
            $admin->save();
            return array('status' => true, 'msg' => __('done successfully'));
        }
    }

    function removePattern(Request $request){
        $admin = AdminUser::find(\Auth::user()->id);
        if(isset($request->general) && $admin->role=='admin'){
            $setting = \App\Models\Settings::where('name','ticketPattern')->first();
            $ticketPattern = (array)json_decode($setting->value);
            foreach ($ticketPattern as $key=>$pattern){
                if($pattern->title == $request->title && $pattern->msg == $request->msg) {
                    unset($ticketPattern[$key]);
                    continue;
                }
            }
            $setting->value = json_encode($ticketPattern);
            $setting->save();
        }else{
            $ticketPattern = (array)json_decode($admin->ticket_pattern);
            foreach ($ticketPattern as $key=>$pattern){
                if($pattern->title == $request->title && $pattern->msg == $request->msg) {
                    unset($ticketPattern[$key]);
                    continue;
                }
            }
            $admin->ticket_pattern = json_encode($ticketPattern);
            $admin->save();
        }
        return array('status' => true, 'msg' => __('done successfully'));
    }
}
