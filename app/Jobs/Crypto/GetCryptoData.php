<?php

namespace App\Jobs\Crypto;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class GetCryptoData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $id_crypto;
    public function __construct($id_crypto)
    {
        $this->id_crypto = $id_crypto;
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
