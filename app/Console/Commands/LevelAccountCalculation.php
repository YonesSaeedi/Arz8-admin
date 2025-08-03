<?php

namespace App\Console\Commands;

use App\Models\Orders;
use App\Models\Trades;
use App\Models\TransactionCrypto;
use App\Models\User;
use App\Models\Settings;
use Illuminate\Console\Command;

class LevelAccountCalculation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'level:account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Level Account Calculation';

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
        $levels_account = json_decode(\Crypt::decryptString(Settings::where('name','levels_account')->first()->value));
        $dateStart = date('Y-m-d 00:00:00',strtotime( ' -30 day'));;
        $dateStop =  date('Y-m-d 00:00:00');
        $id_users_orders = Orders::where('created_at','>=', $dateStart)->where('status','success')->where('created_at','<=', $dateStop)->groupBy('id_user')->pluck('id_user')->toArray();
        $id_users_trades = Trades::where('created_at','>=', $dateStart)->where('status','success')->where('created_at','<=', $dateStop)->groupBy('id_user')->pluck('id_user')->toArray();
        $id_users = array_merge($id_users_orders,$id_users_trades);

        $users = User::whereIn('id',$id_users)->where('id','!=',1)->where('id','!=',43)->get();
        foreach ($users as $user){
            $sum_amount_orders = Orders::where('id_user',$user->id)->where('created_at','>=', $dateStart)->where('created_at','<=', $dateStop)->where('status','success')->sum('amount');

            $id_trade = Trades::where('id_user',$user->id)->where('created_at','>=', $dateStart)->where('created_at','<=', $dateStop)->where('status','success')->pluck('id')->toArray();
            $sum_amount_trade = TransactionCrypto::whereIn('id_trade',$id_trade)->where('id_user',$user->id)->where('type','deposit')->sum('amount_toman');

            $sum = $sum_amount_orders+$sum_amount_trade;
            foreach ($levels_account as $item){
                if($sum >= $item->amount_start && $sum < $item->amount_stop)
                    $user->level_account = $item->number;
            }
            $user->save();
        }
    }

}
