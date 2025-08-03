<?php

namespace App\Console\Commands\FiatWallet;

use App\Models\AutomaticDeposit;
use App\Models\TransactionInternal;
use App\Models\User;
use App\Models\UserCardBank;
use Illuminate\Console\Command;

class PaystarWithdrawTiming extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paystar:withdrawTiming';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Withdraw Timing ex: paystar';

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

        if($this->statusWithdraw()):
            $transfers = [];
            $TraInternals = TransactionInternal::where('type','withdraw')->where('status','pending')->whereNull('payment')
                ->whereNotNull('id_cardbank')->where('amount','<=',10000000)->limit(100)->get();
            foreach ($TraInternals as $traInternal){
                $card = UserCardBank::find($traInternal->id_cardbank);
                $iban = str_replace(['-','IR',' '],'',$card->iban);

                if (isset($card->iban) && isset($iban) && $iban!=''):
                    $user = User::find($traInternal->id_user);
                    $amount = $this->getAmount($traInternal->amount);
                    $transfer = [
                        'deposit' => '0120137445003',
                        'amount'=> $amount*10,
                        'destination_account'=> 'IR'.$iban,
                        'destination_firstname'=> $user->name,
                        'destination_lastname'=> $user->family,
                        'track_id'=> (string)$traInternal->id,
                    ];
                    array_push($transfers,$transfer);
                endif;
            }


            if(count($transfers) > 0):
                $paystar = new \App\Models\PaymentGateway\Paystar();
                $result = $paystar->withdrawGroupOpenBanking($transfers);
                if($result->status == true){
                    foreach ($TraInternals as $traInternal) {
                        $card = UserCardBank::find($traInternal->id_cardbank);
                        $user = User::find($traInternal->id_user);

                        $money = new AutomaticDeposit;
                        $money->iban = $card->iban;
                        $money->id_cardbank = $card->id;
                        $money->id_user = $user->id;
                        $money->amount = $traInternal->amount;
                        $money->id_internal_transaction = $traInternal->id;
                        $money->gateway_withdraw = 'paystar';
                        $money->data = json_encode($result->response);
                        $money->save();

                        $traInternal->status = 'success';
                        $traInternal->payment = $traInternal->amount;
                        $traInternal->payment_gateway = 'paystar';
                        $traInternal->save();

                        // Send Notif
                        $job = (new \App\Jobs\NotificationCenter($traInternal->id_user,'confirmInternalWithdraw',
                            ['amount'=> number_format($traInternal->amount),'sms'=>[number_format($traInternal->amount)]]))->delay(\Carbon\Carbon::now()->addSeconds(1));
                        dispatch($job);
                    }
                }
            endif;
        endif;
    }

    function getAmount($amount){
        $fee_withdraw = round($amount * 0.0004);
        if($fee_withdraw > 7500)
            $fee_withdraw = 7500;
        elseif ($fee_withdraw < 800)
            $fee_withdraw = 800;

        $amount = $amount - $fee_withdraw;
        return $amount;
    }

    function statusWithdraw(){
        $Deposit = \App\Models\Settings::where('name','AutomaticDeposit')->first()->value;
        $Deposit = json_decode($Deposit);
        $gatewayWithdraw = $Deposit->GatewayWithdraw;
        $numWeeks = date('l');
        switch($numWeeks) {
            case "Saturday":
                $numWeek = 0;break;
            case "Sunday":
                $numWeek = 1;break;
            case "Monday":
                $numWeek = 2;break;
            case "Tuesday":
                $numWeek = 3;break;
            case "Wednesday":
                $numWeek = 4;break;
            case "Thursday":
                $numWeek = 5;break;
            case "Friday":
                $numWeek = 6;break;
        }
        if($gatewayWithdraw == "paystar" && $Deposit->status == 'true' && (date('H:i') >= $Deposit->{$numWeek.'0'} && date('H:i') <= $Deposit->{$numWeek.'1'}  ))
            return true;
        else
            return false;
    }
}
