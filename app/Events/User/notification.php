<?php

namespace App\Events\User;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Notifications;

class notification implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $user;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($id_user)
    {
        $this->user = User::find($id_user);
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
        $notifications = Notifications::where('id_user',$this->user->id)->where('seen','unseen')->orderBy('created_at','desc');
        $notifications = $notifications->limit(50)->get(['id','title','message','keyword','created_at']);
        foreach ($notifications as $notification){
            $notification->message = json_decode($notification->message);
            $notification->time = $notification->created_at->timestamp * 1000;
            unset($notification->created_at);
        }
        return $notifications->toArray();
    }

}
