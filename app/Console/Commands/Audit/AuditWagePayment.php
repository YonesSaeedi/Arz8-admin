<?php

namespace App\Console\Commands\Audit;

use App\Models\Audit\CostAudit;
use App\Models\TransactionInternal;
use Illuminate\Console\Command;

class AuditWagePayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:wagePayment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit Cust wage payment ex:paystar';

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
        $dateStart = date('Y-m-d 00:00:00',strtotime( ' -1 day'));
        $dateStop = date('Y-m-d 00:00:00');

        // Wage Binance
        $query = TransactionInternal::query();
        $query->where('status','success')->where('type','deposit');
        $query->whereNull('id_order')->whereNull('id_trade');
        $query->whereIn('payment_gateway',['paystar']);
        $query->where('updated_at','>=', $dateStart)->where('updated_at','<', $dateStop);

        $sumWageToday = 0;
        $transactions = $query->get();
        foreach ($transactions as $transaction){
            $wage = $this->getWage($transaction->amount);
            $sumWageToday += $wage;
        }


        $cust = new CostAudit();
        $cust->asset = 'TOMAN';
        $cust->type =  'decrement';
        $cust->amount = $sumWageToday;
        $cust->description = 'کارمزد درگاه پرداخت برای واریزی ها';
        $cust->created_at = $dateStart;
        $cust->updated_at = $dateStart;
        $cust->save();

    }

    function getWage($amount){
        $wage = round($amount * 0.01);
        if($wage > 5000)
            $wage = 5000;
        elseif ($wage < 500)
            $wage = 500;
        return $wage;
    }
}
