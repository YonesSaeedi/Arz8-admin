<?php

namespace App\Console\Commands\Marketing;

use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\Cryptocurrency;
use App\Models\Notifications;
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
use Telegram\Bot\Api;

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
        DB::beginTransaction();
        try {
            // تعریف جوایز برای تمام رده‌ها
            $rewards = [
                // رده 1-5
                ['id_crypto' => 18, 'amount' => 1000000, 'title' => 'جایزه نفر اول'],
                ['id_crypto' => 18, 'amount' => 500000, 'title' => 'جایزه نفر دوم'],
                ['id_crypto' => 18, 'amount' => 300000, 'title' => 'جایزه نفر سوم'],
                ['id_crypto' => 18, 'amount' => 100000, 'title' => 'جایزه نفر چهارم'],
                ['id_crypto' => 18, 'amount' => 100000, 'title' => 'جایزه نفر پنجم'],

                // رده 6-20 (15 نفر)
                ...array_fill(0, 15, ['id_crypto' => 18, 'amount' => 25000, 'title' => 'جایزه رده 6-20']),

                // رده 21-50 (30 نفر)
                ...array_fill(0, 30, ['id_crypto' => 18, 'amount' => 10000, 'title' => 'جایزه رده 21-50']),

                // رده 51-100 (50 نفر)
                ...array_fill(0, 50, ['id_crypto' => 18, 'amount' => 5000, 'title' => 'جایزه رده 51-100']),
            ];

            $yesterday = Carbon::now()->subDay();
            $yesterdayStart = $yesterday->copy()->startOfDay();
            $yesterdayEnd = $yesterday->copy()->endOfDay();

            // گرفتن تاریخ آخرین برد هر کاربر
            $winnerDates = Ml::select('date', 'id_user_1', 'id_user_2', 'id_user_3', 'id_user_4', 'id_user_5')->get();
            $userLastWinMap = [];

            foreach ($winnerDates as $row) {
                $winDate = Carbon::parse($row->date)->addDay()->startOfDay();
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

            // گرفتن 100 نفر اول با مجموع خرید بالاتر از صفر
            $top100 = $rankedUsers->filter(fn($item) => $item->total_amount > 0)->take(100);

            // پرداخت جوایز به 100 نفر برتر
            foreach ($top100 as $key => $entry) {
                if (!isset($rewards[$key])) break;

                $userId = $entry->id_user;
                $reward = (object)$rewards[$key];

                // تولید عنوان مناسب بر اساس رتبه
                $rank = $key + 1;
                if ($rank <= 5) {
                    $reward->title = "جایزه نفر {$rank}";
                } elseif ($rank <= 20) {
                    $reward->title = "جایزه رده 6-20 (رتبه {$rank})";
                } elseif ($rank <= 50) {
                    $reward->title = "جایزه رده 21-50 (رتبه {$rank})";
                } else {
                    $reward->title = "جایزه رده 51-100 (رتبه {$rank})";
                }



                $this->transactionCryptoWallet($userId, $reward, $reward->title);

            }

            // ذخیره نتایج در جدول لیگ (فقط 5 نفر اول برای نمایش در تاریخچه)
            $MarketingLeague = new Ml();
            $MarketingLeague->date = Carbon::yesterday()->toDateString();
            $MarketingLeague->id_user_1 = $top100[0]->id_user ?? null;
            $MarketingLeague->id_user_2 = $top100[1]->id_user ?? null;
            $MarketingLeague->id_user_3 = $top100[2]->id_user ?? null;
            $MarketingLeague->id_user_4 = $top100[3]->id_user ?? null;
            $MarketingLeague->id_user_5 = $top100[4]->id_user ?? null;

            // ذخیره اطلاعات کامل 100 نفر برتر در فیلد datadata
            $winnersData = $top100->map(function ($user, $index) use ($rewards) {
                $rank = $index + 1;
                $prize = isset($rewards[$index]) ? $this->formatPrize($rewards[$index]['amount']) : 'بدون جایزه';
                return [
                    'rank' => $rank,
                    'user_id' => $user->id_user,
                    'amount' => $user->total_amount,
                    'prize' => $prize
                ];
            })->toArray();

            $MarketingLeague->data = json_encode(['1 میلیون شیبا', '500 هزار شیبا', '300 هزار شیبا', '100 هزار شیبا', '100 هزار شیبا']);
            $MarketingLeague->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            \Log::channel('ErrorApi')->info("marketing:league ". $e->getMessage().':'.$e->getLine());
        }

        $this->nofitcation($MarketingLeague);
    }

// تابع کمکی برای فرمت کردن جایزه
    private function formatPrize($amount)
    {
        if ($amount >= 1000000) {
            return '1,000,000 SHIB';
        } elseif ($amount >= 100000) {
            return number_format($amount) . ' SHIB';
        } else {
            return number_format($amount) . ' SHIB';
        }
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
            dd($e->getMessage());
            DB::rollback();
            \Log::channel('ErrorApi')->info("marketing:league 2". $e->getMessage().':'.$e->getLine());
            return false;
        }
    }


    function nofitcation($MarketingLeague)
    {
        try {
            // دریافت اطلاعات کاربران از دیتابیس
            $user1 = $MarketingLeague->id_user_1 ? \App\Models\User::find($MarketingLeague->id_user_1) : null;
            $user2 = $MarketingLeague->id_user_2 ? \App\Models\User::find($MarketingLeague->id_user_2) : null;
            $user3 = $MarketingLeague->id_user_3 ? \App\Models\User::find($MarketingLeague->id_user_3) : null;
            $user4 = $MarketingLeague->id_user_4 ? \App\Models\User::find($MarketingLeague->id_user_4) : null;
            $user5 = $MarketingLeague->id_user_5 ? \App\Models\User::find($MarketingLeague->id_user_5) : null;

            // ایجاد متن پیام
            $msg = "🏆 برندگان مسابقه روز گذشته مشخص شدن!\n";
            $msg .= "چندین میلیون شیبا بین ۱۰۰ نفر تقسیم شد 🎁\n\n";

            $msg .= "🥇 " . ($user1 ? $user1->name.' '.$user1->family : 'نامشخص') . "\n";
            $msg .= "🥈 " . ($user2 ? $user2->name.' '.$user2->family : 'نامشخص') . "\n";
            $msg .= "🥉 " . ($user3 ? $user3->name.' '.$user3->family : 'نامشخص') . "\n";
            $msg .= "🎖 " . ($user4 ? $user4->name.' '.$user4->family : 'نامشخص') . "\n";
            $msg .= "🎖 " . ($user5 ? $user5->name .' '.$user5->family: 'نامشخص') . "\n\n";
            $msg .= "✨ " . "و این بار تا رتبه ۱۰۰ جایزه گرفتند! 😍" . "\n\n";

            $msg .= "جوایز واریز شد ✅\n";
            $msg .= "مسابقه امروز فعال است، از الان شروع کن 💎";

            $func = new \App\Functions();
            $func->sendMsgFirebase(env('APP_NAME'), $msg);



            $notifications = new Notifications;
            $notifications->id_user = null;
            $notifications->title = 'لیگ شیبا';
            $notifications->message = json_encode(['fa'=>$msg,'en'=>$msg]);
            $notifications->keyword = 'message';
            $notifications->seen = 'seen';
            $notifications->save();



            $this->telegram = new Api("5519467988:AAHnqeh0X1NBKmhC5wXzGTTJvz7cfMnkvGc");
            $response =  $this->telegram->sendMessage([
                'chat_id' => '@arz8com',
                'text' => $msg
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::channel('ErrorApi')->info("marketing:league nofitcation". $e->getMessage().':'.$e->getLine());
            return false;
        }
    }

}
