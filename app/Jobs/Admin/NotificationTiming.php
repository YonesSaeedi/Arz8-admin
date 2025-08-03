<?php

namespace App\Jobs\Admin;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class NotificationTiming implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $usersIds;
    public $id_notification;
    public function __construct($usersIds,$id_notification)
    {
        $this->usersIds = $usersIds;
        $this->id_notification = $id_notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

    }

}
