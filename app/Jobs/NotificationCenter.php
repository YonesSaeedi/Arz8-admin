<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Notifications;
use App\Models\User;
use App\Functions;

class NotificationCenter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $id_user;
    public $nameNotif;
    public $data;
    public function __construct($id_user,$nameNotif,$data)
    {
        $this->id_user = $id_user;
        $this->nameNotif = $nameNotif;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //self::notifCenter($this->id_user,$this->nameNotif,$this->data);
    }

}
