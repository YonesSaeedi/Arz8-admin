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
            ['id_crypto' => 18, 'amount' => 1000000],
            ['id_crypto' => 18, 'amount' => 500000],
            ['id_crypto' => 18, 'amount' => 300000],
            ['id_crypto' => 18, 'amount' => 100000],
            ['id_crypto' => 18, 'amount' => 100000],
        ];
        $titles = ['جایزه نفر اول', 'جایزه نفر دوم', 'جایزه نفر سوم', 'جایزه نفر چهارم', 'جایزه نفر پنجم'];

        //$startOfLeague = Carbon::createFromFormat('Y-m-d', '2025-10-19')->startOfDay();
        $yesterdayStart = Carbon::now()->startOfDay();
        $yesterdayEnd = Carbon::now()->endOfDay();

        // گرفتن تاریخ آخرین برد هر کاربر
        $winnerDates = Ml::select('date', 'id_user_1', 'id_user_2', 'id_user_3', 'id_user_4', 'id_user_5')->get();
        $userLastWinMap = [];

        foreach ($winnerDates as $row) {
            $winDate = Carbon::parse($row->date)->addDay()->startOfDay(); // شروع محاسبه از روز بعد برد
            foreach (['id_user_1', 'id_user_2', 'id_user_3', 'id_user_4', 'id_user_5'] as $field) {
                $uid = $row->{$field};
                if ($uid) {
                    if (!isset($userLastWinMap[$uid]) || $winDate->gt($userLastWinMap[$uid])) {
                        $userLastWinMap[$uid] = $winDate;
                    }
                }
            }
        }

        // آرایه‌ای برای ذخیره مجموع خرید هر کاربر
        $usersTotal = [];

        // بارگذاری تدریجی سفارش‌ها با chunk
        DB::table('orders')
            ->where('status', 'success')
            ->where('created_at', '<=', $yesterdayEnd)
            ->where('created_at', '>=', $yesterdayStart)
            ->where('id_user', '!=', 1)
            ->select('id_user', 'amount', 'created_at')
            ->orderBy('id_user')
            ->chunk(1000, function ($ordersChunk) use (&$usersTotal, $userLastWinMap, $yesterdayEnd, $yesterdayStart) {
                foreach ($ordersChunk as $order) {
                    $uid = $order->id_user;
                    // تاریخ شروع محاسبه برای هر کاربر: روز بعد آخرین برد یا شروع لیگ
                    $userStart = $userLastWinMap[$uid] ?? $yesterdayStart;

                    $createdAt = Carbon::parse($order->created_at);
                    if ($createdAt->gte($userStart) && $createdAt->lte($yesterdayEnd)) {
                        if (!isset($usersTotal[$uid])) {
                            $usersTotal[$uid] = 0;
                        }
                        $usersTotal[$uid] += $order->amount;
                    }
                }
            });

        // تبدیل به collection و رتبه‌بندی
        $rankedUsers = collect($usersTotal)
            ->map(function ($total, $id_user) {
                return (object)[
                    'id_user' => $id_user,
                    'total_amount' => round($total)
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        // گرفتن سه نفر اول با مجموع خرید بالاتر از صفر
        $top3 = $rankedUsers->filter(fn($item) => $item->total_amount > 0)->take(5);

        // پرداخت جوایز
        foreach ($top3 as $key => $entry) {
            if (!isset($reward[$key])) break;
            $userId = 638;//$entry->id_user;
            $this->transactionCryptoWallet($userId, (object)$reward[$key], $titles[$key]);
        }

        // ذخیره نتایج در جدول لیگ
        $MarketingLeague = new Ml();
        $MarketingLeague->date = Carbon::yesterday()->toDateString();
        $MarketingLeague->id_user_1 = $top3[0]->id_user ?? null;
        $MarketingLeague->id_user_2 = $top3[1]->id_user ?? null;
        $MarketingLeague->id_user_3 = $top3[2]->id_user ?? null;
        $MarketingLeague->id_user_4 = $top3[3]->id_user ?? null;
        $MarketingLeague->id_user_5 = $top3[4]->id_user ?? null;
        $MarketingLeague->data = json_encode(['1 میلیون شیبا', '500 هزار شیبا', '300 هزار شیبا', '100 هزار شیبا', '100 هزار شیبا']);
        $MarketingLeague->save();
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
