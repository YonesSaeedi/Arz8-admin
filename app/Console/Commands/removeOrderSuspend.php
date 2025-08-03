<?php

namespace App\Console\Commands;

use App\Models\TransactionInternal;
use Illuminate\Console\Command;
use App\Models\Orders;

class removeOrderSuspend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:removeSuspend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove order suspend';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Orders::where('status','suspend')->where('created_at','<',date('Y-m-d H:i:s',strtotime('- 60 minute')))->delete();
        TransactionInternal::where('status','suspend')->where('created_at','<',date('Y-m-d H:i:s',strtotime('- 2 day')))->delete();
    }
}
