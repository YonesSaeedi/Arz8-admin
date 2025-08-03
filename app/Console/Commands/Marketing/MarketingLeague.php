<?php

namespace App\Console\Commands\Marketing;

use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\Cryptocurrency;
use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt as Crypt;
use Illuminate\Support\Facades\DB;
use App\Models\Audit\CostAudit;
use App\Models\TransactionCrypto;
use App\Models\WalletsCrypto;
use App\Http\Controllers\Controller;
use App\Models\Marketing\MarketingLeague as Ml;

class MarketingLeague extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketing:league';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marketing League';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $birthplace;
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
        $reward = [
            ['id_crypto'=>5,'amount'=>20],
            ['id_crypto'=>5,'amount'=>10],
            ['id_crypto'=>5,'amount'=>5]
        ];
        $titles = ['جایزه نفر اول', 'جایزه نفر دوم', 'جایزه نفر سوم'];

        $start = Carbon::yesterday()->startOfDay();
        $end = Carbon::yesterday()->endOfDay();

        $top3 = DB::table('orders')
            ->select('id_user', DB::raw('SUM(amount) as total_amount'))
            ->where('id_user', '!=', 1)
            ->where('status', 'success')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('id_user')
            ->orderByDesc('total_amount')
            ->limit(3)
            ->get();

// پرداخت جوای      ز
        foreach ($top3 as $key => $entry) {
            if (!isset($reward[$key])) break;
            $userId = $entry->id_user;
            $this->transactionCryptoWallet($userId, (object)$reward[$key], $titles[$key]);
        }

        // ذخیره در جدول لیگ
        $MarketingLeague = new Ml();
        $MarketingLeague->date = Carbon::yesterday()->toDateString();
        $MarketingLeague->id_user_1 = $top3[0]->id_user ?? null;
        $MarketingLeague->id_user_2 = $top3[1]->id_user ?? null;
        $MarketingLeague->id_user_3 = $top3[2]->id_user ?? null;
        $MarketingLeague->data = json_encode(['20 تتر','10 تتر','5 تتر']);
        $MarketingLeague->save();
        //d($top3,$MarketingLeague->save());

    }

    private function transactionCryptoWallet($id_user,$reward,$description){
        $crypto = Cryptocurrency::find($reward->id_crypto);
        $amount = $reward->amount;

        $walletCrypto = new ExchangeApi();
        $amountToman = $amount * $walletCrypto->priceToman($crypto)->sell;

        $wallet = WalletsCrypto::where('id_user',$id_user)->where('id_crypto',$crypto->id)->first();
        if(!isset($wallet)){
            $controller = new Controller();
            $wallet = $controller->createWallet($crypto->id,$id_user);
        }



        DB::beginTransaction();
        try {
            $transaction = new TransactionCrypto;
            $transaction->id_crypto = $crypto->id;
            $transaction->id_user = $wallet->id_user;
            $transaction->type = 'deposit';
            $transaction->amount = $amount;
            $transaction->payment = $amount;
            $transaction->status = 'success';
            $transaction->description = $description;
            $transaction->amount_toman = $amountToman;

            $balance = Crypt::decryptString($wallet->value);
            $balance_available = Crypt::decryptString($wallet->value_available);
            $wallet->value = Crypt::encryptString($balance + $amount);
            $wallet->value_available = Crypt::encryptString($balance_available + $amount);
            $wallet->value_num = $balance + $amount;
            $wallet->value_available_num = $balance_available + $amount;
            $wallet->save();

            $transaction->stock = $wallet->value_num;
            $transaction->save();



            // ثبت در حسابداری
            $description = 'جوایز لیگ ارزهشت';
            $cust = CostAudit::where('description', $description)->where('created_at','>',date('Y-m-d 00:00:00'))
                ->where('created_at','<=',date('Y-m-d 00:00:00',strtotime( ' +1 day')))->first();
            if(isset($cust->amount)){
                $cust->amount = $cust->amount+$amountToman;
                $cust->save();
            }else{
                $cust = new CostAudit();
                $cust->amount = $amountToman;
                $cust->description = $description;
                $cust->save();
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            \Log::channel('ErrorApi')->info("RegisterLevel2: tr error". $e->getMessage().':'.$e->getLine());
            return false;
        }
    }

}
