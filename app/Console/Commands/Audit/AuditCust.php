<?php

namespace App\Console\Commands\Audit;

use App\Models\Audit\CostAudit;
use App\Models\CryptoLittle;
use App\Models\Orders;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt as Crypt;

class AuditCust extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cust';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit Cust BNB Charge';
    public $dateStart;
    public $dateStop;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->dateStart = date('Y-m-d 00:00:00',strtotime( ' -1 day'));
        $this->dateStop = date('Y-m-d 00:00:00',strtotime( ' -0 day'));
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        self::wageBinance();
        self::wageCoinex();
        self::wageKucoin();

    }

    public function wageBinance(){
        // Wage Binance orders
        $query = Orders::query();
        $query->where('orders.created_at','>=', $this->dateStart)->where('orders.created_at','<', $this->dateStop);
        $query->where('status','success')->whereNotNull('id_crypto');
        $query->whereJsonContains('data->exchange','binance');
        $wages = $query->selectRaw('(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.fills"))) AS wage')
            ->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.fills"))) IS NOT NULL')->get();

        $order_wage_bnb = 0;
        foreach ($wages as $wage){
            $fills = json_decode($wage->wage);
            foreach ($fills as $fill){
                if($fill->commissionAsset == 'BNB')
                    $order_wage_bnb += $fill->commission;
            }
        }

        // Wage Binance Trades
        $query = \App\Models\Trades::query();
        $query->where('updated_at','>=', $this->dateStart)->where('updated_at','<', $this->dateStop);
        $query->where('status','success');
        $trades = $query->selectRaw('SUM(JSON_UNQUOTE(JSON_EXTRACT(data, "$.history.commission"))) AS wage')
            ->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.history"))) IS NOT NULL
                         AND (JSON_UNQUOTE(JSON_EXTRACT(data, "$.history.commissionAsset"))) = "BNB"')->first();
        $trade_wage_bnb = round(sprintf('%.6f', $trades->wage),8);

        $cust_bnb = CostAudit::where('created_at','>=', $this->dateStart)->where('created_at','<', $this->dateStop)
            ->where(['asset'=>'BNB','type'=>'increase'])->selectRaw('sum(amount) as sum,round(sum(amount*fee) / sum(amount)) as fee')->first();

        $balance_bnb = round($cust_bnb->sum - ($order_wage_bnb+$trade_wage_bnb),8);

        $cust = new CostAudit();
        $cust->fee = $cust_bnb->fee;
        $cust->asset = 'BNB';
        $cust->type =  'increase';
        $cust->amount = $balance_bnb;
        $cust->description = 'شارژ BNB اتوماتیک';
        $cust->created_at = $this->dateStop;
        $cust->updated_at = $this->dateStop;
        $cust->save();
    }

    public function wageKucoin(){
        // Wage Kucoin orders
        $query = Orders::query();
        $query->where('orders.created_at','>=', $this->dateStart)->where('orders.created_at','<', $this->dateStop);
        $query->where('status','success')->whereNotNull('id_crypto');
        $query->whereJsonContains('data->exchange','kucoin');
        $orders = $query->selectRaw('ROUND(SUM(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.detail.fee"))*1),8) as fee')
            ->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.detail.fee"))) IS NOT NULL')
            ->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.detail.feeCurrency"))) = "USDT"')->first();

        $price_usdt_toman = Crypt::decryptString(\App\Models\Settings::where('name', 'feeUsdtApiPrice')->first()->value);
        $amount = round($orders->fee *$price_usdt_toman);

        $cust = new CostAudit();
        $cust->fee = null;
        $cust->asset = 'TOMAN';
        $cust->type =  'decrement';
        $cust->amount = $amount;
        $cust->description = 'کارمزد سفارشات کوکوین';
        $cust->created_at = $this->dateStop;
        $cust->updated_at = $this->dateStop;
        $cust->save();
    }

    public function wageCoinex(){
        // Wage coinex by CET
        $query = Orders::query();
        $query->where('orders.created_at','>=', $this->dateStart)->where('orders.created_at','<', $this->dateStop);
        $query->where('status','success')->whereNotNull('id_crypto');
        $query->whereJsonContains('data->exchange','coinex');
        $query->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.response.data.discount_fee"))) > 0');
        $order_wage_cet = $query->selectRaw('ROUND(SUM(JSON_UNQUOTE(JSON_EXTRACT(orders.data, "$.response.data.discount_fee"))*1),8) as discount_fee')->first();

        $cust_cet = CostAudit::where('created_at','>=', $this->dateStart)->where('created_at','<', $this->dateStop)
            ->where(['asset'=>'CET','type'=>'increase'])->selectRaw('sum(amount) as sum,round(sum(amount*fee) / sum(amount)) as fee')->first();
        $balance_cet = round($cust_cet->sum - $order_wage_cet->discount_fee,8);

        $cust = new CostAudit();
        $cust->fee = $cust_cet->fee;
        $cust->asset = 'CET';
        $cust->type =  'increase';
        $cust->amount = $balance_cet;
        $cust->description = 'شارژ CET اتوماتیک';
        $cust->created_at = $this->dateStop;
        $cust->updated_at = $this->dateStop;
        $cust->save();



        // Wage Coinex By coins and not CET
        $query = Orders::query();
        $query->where('orders.created_at','>=', $this->dateStart)->where('orders.created_at','<', $this->dateStop);
        $query->select('id_crypto');
        $query->where('status','success')->whereNotNull('id_crypto');
        $query->whereJsonContains('data->exchange','coinex');
        $query->where(function ($query) {
            $query->whereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.response.data.base_fee"))) > 0');
            $query->orWhereRaw('(JSON_UNQUOTE(JSON_EXTRACT(data, "$.response.data.quote_fee"))) > 0');
        });

        $query->groupBy('id_crypto');
        $query->selectRaw('SUM(JSON_UNQUOTE(JSON_EXTRACT(data, "$.response.data.base_fee"))*1) as base_fee');
        $query->selectRaw('SUM(JSON_UNQUOTE(JSON_EXTRACT(data, "$.response.data.quote_fee"))*1) as quote_fee');
        $query->selectRaw('AVG(JSON_UNQUOTE(JSON_EXTRACT(data, "$.price"))*1) as avg_fee');
        $query->selectRaw('AVG(JSON_UNQUOTE(JSON_EXTRACT(data, "$.fee_usdt.buy"))*1) as avg_fee_usdt_buy');
        $query->selectRaw('AVG(JSON_UNQUOTE(JSON_EXTRACT(data, "$.fee_usdt.sell"))*1) as avg_fee_usdt_sell');
        $orders = $query->get();
        $wage_toman = 0;
        foreach ($orders as $order){
            // Base Fee
            $amount_coin = $order->base_fee;
            $sum_wage_usdt = $order->base_fee * $order->avg_fee;
            $sum_wage_toman = round($sum_wage_usdt * $order->avg_fee_usdt_buy);
            $little_exist = \App\Models\CryptoLittle::where('id_crypto',$order->id_crypto)->first();
            if(isset($little_exist)){
                \App\Models\CryptoLittle::where('id_crypto',$order->id_crypto)->update([
                    'amount_coin' => \DB::raw("amount_coin - {$amount_coin}"),
                    'amount_toman' => \DB::raw("amount_toman - {$sum_wage_toman}"),
                ]);
            }else{
                $CryptoLittle = new \App\Models\CryptoLittle();
                $CryptoLittle->amount_coin = $amount_coin;
                $CryptoLittle->amount_toman = $sum_wage_toman;
                $CryptoLittle->id_crypto = $order->id_crypto;
                $CryptoLittle->save();
            }
            $wage_toman += $sum_wage_toman;

            // Quote fee
            $wage_toman += round($order->quote_fee * $order->avg_fee_usdt_buy);
        }
        $cust = new CostAudit();
        $cust->amount = $wage_toman;
        $cust->description = 'جمع کارمرزد ارز های کوینکس بدون استفاده از CET';
        $cust->created_at =  $this->dateStart;
        $cust->updated_at = $this->dateStart;
        $cust->save();
    }
}
