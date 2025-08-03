<?php

namespace App\Console\Commands\Audit;

use App\Models\Audit\DailyAudit;
use App\Models\Orders;
use Illuminate\Console\Command;

class AuditDailyDigital extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:dailyDigital';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit Daily Digital';

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
        $dateStop = date('Y-m-d 00:00:00',strtotime( ' -0 day'));
        $query = Orders::query();
       // $query->where('orders.created_at','>=', '2022-07-13 14:00:00');
        $query->where('status','success')
              ->where('orders.created_at','>=', $dateStart)
              ->where('orders.created_at','<', $dateStop);
        $query->whereNull('orders.id_crypto');
        $query->leftJoin('cryptocurrency','orders.id_crypto','cryptocurrency.id');

        // Filters
        $sell = $query->where('type','sell')->selectRaw('
        ROUND(SUM(IF(((id_crypto is not null) AND cryptocurrency.symbol != "USDT"),
          JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
          amount_coin)),4) as sum_amount_usdt,

        ROUND(sum(amount) /
          (ROUND(SUM(IF(((id_crypto is not null) AND cryptocurrency.symbol != "USDT"),
          JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
          amount_coin)),4))
        )  as avg_fee_usdt
        ')->first();


        // buy
        $query = Orders::query();
        //$query->where('orders.created_at','>=', '2022-07-13 14:00:00');
        $query->where('status','success')
            ->where('orders.created_at','>=', $dateStart)
            ->where('orders.created_at','<', $dateStop);
        $query->whereNull('orders.id_crypto');
        $query->leftJoin('users','orders.id_user','users.id');
        $query->leftJoin('cryptocurrency','orders.id_crypto','cryptocurrency.id');
        // Filters
        $buy = $query->where('type','buy')->selectRaw('
        ROUND(SUM(IF(((id_crypto is not null) AND cryptocurrency.symbol != "USDT"),
          JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
          amount_coin)),4) as sum_amount_usdt,

        ROUND(sum(amount) /
          (ROUND(SUM(IF(((id_crypto is not null) AND cryptocurrency.symbol != "USDT"),
          JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
          amount_coin)),4))
        )  as avg_fee_usdt
        ')->first();

        // Cust
            $cust = 0;
            // PSVouchers
            $query = Orders::query();
            $query->where('updated_at','>=', $dateStart)->where('updated_at','<', $dateStop);
            $query->where(['status'=>'success','type'=>'buy']);
            $query->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name"))) = "PSVouchers"');
            $query->whereNull('orders.id_crypto');
            $sum = $query->sum('amount');
            $cust += round($sum * 0.005);

            // Utopia
            $query = Orders::query();
            $query->where('updated_at','>=', $dateStart)->where('updated_at','<', $dateStop);
            $query->where(['status'=>'success','type'=>'buy']);
            $query->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.name"))) = "Utopia"');
            $query->whereNull('orders.id_crypto');
            $sum = $query->sum('amount');
            $cust += round($sum * 0.0015);



        $daily = new DailyAudit;
        $daily->amount_sell = $sell->sum_amount_usdt??0;
        $daily->avg_price_sell = $sell->avg_fee_usdt??0;
        $daily->amount_buy = $buy->sum_amount_usdt??0;
        $daily->avg_price_buy = $buy->avg_fee_usdt??0;
        $daily->date = $dateStart;
        $daily->balance = ($daily->amount_sell - $daily->amount_buy);
        $a = $daily->amount_buy * $daily->avg_price_buy;
        $b = $daily->amount_buy * $daily->avg_price_sell;
        $daily->benefit = $a - $b;
        $daily->is_crypto = 0;
        $daily->cust = $cust;
        $daily->save();


        $order = new Orders();
        $order->type = 'sell';
        $order->amount = $daily->balance * $daily->avg_price_sell;
        $order->amount_coin = $daily->balance;
        $order->wage = 0;
        $order->fee = $daily->avg_price_sell;
        $order->description = 'ثبت سفارش حسابداری';
        $order->status = 'success';
        $order->id_crypto = null;
        $order->id_user = 1;
        $order->created_at = $dateStop;
        $order->updated_at = $dateStop;

        $data = (object)['name'=>'PerfectMoney','symbol'=>'PM'];
        $order->data = json_encode($data);
        $order->save();

    }
}
