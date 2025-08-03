<?php

namespace App\Console\Commands;

use App\Models\Orders;
use App\Models\TransactionCrypto;
use App\Models\TransactionInternal;
use App\Models\UserCardBank;
use App\Models\User;
use App\Models\AutomaticDeposit;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test';

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
        $query = Orders::query();
        $query->where('orders.created_at','>','2022-01-01 00:00:00')->where('orders.status','success')->whereNotNull('orders.id_crypto');
        $query->leftJoin('users_transaction_crypto','orders.id','users_transaction_crypto.id_order');
        $query->whereRaw('orders.id_crypto != users_transaction_crypto.id_crypto');
        $query->select('orders.id');
        $c = $query->get();
        dd($c);

        /*
        foreach ($orders as $order){
            $tr = TransactionCrypto::where()
        }
        */
    }

}
