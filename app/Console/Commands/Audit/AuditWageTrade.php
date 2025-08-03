<?php

namespace App\Console\Commands\Audit;
use App\Models\Audit\TradeWage;
use App\Models\Cryptocurrency;
use App\Models\Trades;
use Illuminate\Console\Command;


class AuditWageTrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:wageTrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit wage trade';

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

        $cryptos = Cryptocurrency::all();
        foreach ($cryptos as $crypto){
            $wage = Trades::where('status','success')->where('wage_asset',$crypto->symbol)
                ->where('updated_at','>=', $dateStart)
                ->where('updated_at','<', $dateStop)->sum('wage_amount');

            $cryptoWageTrade = TradeWage::where('id_crypto',$crypto->id)->first();
            if(!isset($cryptoWageTrade)){
                $cryptoWageTrade = new TradeWage();
                $cryptoWageTrade->id_crypto = $crypto->id;
                $cryptoWageTrade->amount_coin = $wage;
                $cryptoWageTrade->save();
            }else{
                $cryptoWageTrade->amount_coin += $wage;
                $cryptoWageTrade->save();
            }
        }

    }
}
