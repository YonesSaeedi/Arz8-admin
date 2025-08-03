<?php

namespace App\Events\User;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\UserCardBank;

class cardbankStatus implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $cardBank;
    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(UserCardBank $cardBank)
    {
        $this->cardBank = $cardBank;
        $this->user = User::find($cardBank->id_user);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->user->id);
    }

    public function broadcastWith()
    {
        $list = self::CreditCardList();
        return [
            'list' => $list
        ];
    }

    function CreditCardList(){
        $CardBanks = UserCardBank::select('bank_name','card_number','iban','status','data')->where('id_user',$this->user->id)->get();
        foreach ($CardBanks as $card){
            if($card->status == 'reject'){
                $data = json_decode($card->data);
                $card->reason = $data->reject_reason;
            }
            unset($card->data);
        }
        $result = array('status'=> true , 'msg'=> __('Success'), 'data'=> $CardBanks );
        return $result;
    }

}
