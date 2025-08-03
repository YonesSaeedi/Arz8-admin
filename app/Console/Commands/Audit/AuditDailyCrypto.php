<?php

namespace App\Console\Commands\Audit;

use App\Models\Audit\DailyAudit;
use App\Models\Orders;
use Illuminate\Console\Command;

class AuditDailyCrypto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:dailyCrypto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit Daily Crypto';

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
        $query->where('orders.created_at','>=', $dateStart)
              ->where('orders.created_at','<', $dateStop)
              ->where('status','success')
              ->whereNotNull('orders.id_crypto');

        // Filters
        $sell = $query->where('type','sell')->selectRaw('
        ROUND(SUM(IF(((id_crypto is not null) AND id_crypto != "5"),
          JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
          amount_coin)),4) as sum_amount_usdt,

        ROUND(sum(amount) /
          (ROUND(SUM(IF(((id_crypto is not null) AND id_crypto != "5"),
          JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
          amount_coin)),4))
        )  as avg_fee_usdt
        ')->first();


        // buy
        $query = Orders::query();
        $query->where('orders.created_at','>=', $dateStart)
               ->where('orders.created_at','<', $dateStop)
               ->where('status','success')
               ->whereNotNull('orders.id_crypto');
        // Filters
        $buy = $query->where('type','buy')->selectRaw('
        ROUND(SUM(IF(((id_crypto is not null) AND id_crypto != "5"),
          JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
          amount_coin)),4) as sum_amount_usdt,

        ROUND(sum(amount) /
          (ROUND(SUM(IF(((id_crypto is not null) AND id_crypto != "5"),
          JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.cummulativeQuoteQty"))*1,
          amount_coin)),4))
        )  as avg_fee_usdt
        ')->first();




        // Cust
            $cust = 0;
            //Referral
            $cust += \App\Models\ReferralTransaction::where('created_at','>=', $dateStart)->where('created_at','<', $dateStop)->sum('amount');

            // Cust Manual toman
            $cust += \App\Models\Audit\CostAudit::where('created_at','>=', $dateStart)->where('created_at','<', $dateStop)->where('asset','TOMAN')->sum('amount');

            // Wage Orders bnb
            $query = Orders::query();
            $query->where('status','success')->where('created_at','>=', $dateStart)->where('created_at','<', $dateStop);
            $orders = $query->selectRaw('(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.fills"))) AS wage')
                ->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.fills"))) IS NOT NULL')->get();
            $order_wage_bnb = 0;
            foreach ($orders as $order){
                $fills = json_decode($order->wage);
                foreach ($fills as $fill){
                    if($fill->commissionAsset == 'BNB')
                        $order_wage_bnb += $fill->commission;
                }
            }
            $order_wage_bnb = round($order_wage_bnb,8);

            // Wage Trade bnb
            $query = \App\Models\Trades::query();
            $query->where('status','success');
            $query->where('updated_at','>=', $dateStart)->where('updated_at','<', $dateStop);
            $trades = $query->selectRaw('SUM(JSON_UNQUOTE(JSON_EXTRACT(data, "$.history.commission"))) AS wage')
                ->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.history"))) IS NOT NULL
                                 AND (JSON_UNQUOTE(JSON_EXTRACT(data, "$.history.commissionAsset"))) = "BNB"')->first();
            $trade_wage_bnb = round(sprintf('%.6f', $trades->wage),8);

            $cust_bnb = \App\Models\Audit\CostAudit::where('created_at','>=', $dateStart)->where('created_at','<', $dateStop)
                ->where(['asset'=>'BNB','type'=>'increase'])->selectRaw('sum(amount) as sum,round(sum(amount*fee) / sum(amount)) as fee')->first();
            $cust += round($order_wage_bnb * $cust_bnb->fee);
            $cust += round($trade_wage_bnb * $cust_bnb->fee);


            // Wage Trade cet
            $query = Orders::query();
            $query->where('status','success');
            $query->where('orders.updated_at','>=', $dateStart)->where('orders.updated_at','<', $dateStop);
            $query->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.response.data.fee_asset"))) IS NOT NULL
                             AND (JSON_UNQUOTE(JSON_EXTRACT(data, "$.response.data.fee_asset"))) = "CET"');
            $order_wage_cet = $query->selectRaw('ROUND(SUM(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.data.asset_fee"))*1),8) as asset_fee')->first();
            $cust_cet = \App\Models\Audit\CostAudit::where('created_at','>=', $dateStart)->where('created_at','<', $dateStop)
                ->where(['asset'=>'CET','type'=>'increase'])->selectRaw('sum(amount) as sum,round(sum(amount*fee) / sum(amount)) as fee')->first();
            $cust += round($order_wage_cet->asset_fee * $cust_cet->fee);


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
        $daily->is_crypto = 1;
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
        $order->id_crypto = 5;
        $order->id_user = 1;
        $order->created_at = $dateStop;
        $order->updated_at = $dateStop;
        $order->save();

    }
}
