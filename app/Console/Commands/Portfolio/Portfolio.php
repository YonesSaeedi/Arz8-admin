<?php

namespace App\Console\Commands\Portfolio;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Exchange\ExchangeApi;
use App\Models\Cryptocurrency;
use App\Models\Portfolio as Portfolios;
use App\Models\TransactionCrypto;
use App\Models\User;
use App\Models\Wallet;
use File;
use Illuminate\Console\Command;
use App\Services\Wallets\WalletsService;

class Portfolio extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portfolio:saveData';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'save data priceing and user balance';

    private WalletsService $walletsService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(WalletsService $walletsService)
    {
        parent::__construct();
        $this->walletsService = $walletsService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ct = new Controller();
        $date = date('Y-m-d 00:00');

        // Prices
        $ex = new ExchangeApi();
        $pricesToman = $ex->priceTomanAll(true);
        $pricesPerUsdt = $ex->getFeeAll(true);
        $pricess = [];
        $cryptos = Cryptocurrency::all();
        foreach ($cryptos as $crypto){
            $pricess[$crypto->id] = ['toman'=>$pricesToman[$crypto->symbol],'usdt'=>$pricesPerUsdt[$crypto->symbol]['price']];
        }

        // Wallets
        $users = User::select('id','created_at')->whereNotNull('settings->portfolio')->where('settings->portfolio',true)->get();
        foreach ($users as $user){
            $dateStart = date('Y-m-d H:i:s',strtotime( ' -1 day'));
            $dateStop = date('Y-m-d H:i:s');
            $portfolio = Portfolios::where(['id_user'=>$user->id])->orderBy('id','desc')->first();
            $portfolio_wallet = json_decode($portfolio->wallets??'[]');

            // sale zero balance
            $ids = TransactionCrypto::select('id_crypto')->where(['id_user'=>$user->id,'status'=>'success'])
                ->where('created_at','>=', $dateStart)->where('created_at','<', $dateStop)->groupBy('id_crypto')->get()->pluck('id_crypto')->toArray();
            foreach ($ids as $id){
                $this->walletsService->getWalletCrypto($user->id, $id);
            }

            $wallets = Wallet::select('id_crypto','balance')
                ->where('id_user', $user->id)
                ->where('type', Wallet::TYPE_ASSET)
                ->where(function($query) use ($ids) {
                    $query->where('balance', '>', 0)
                        ->orWhereIn('id_crypto', [3]);
                })
                ->get()
                ->toArray();

            if(isset($wallets) && count($wallets) > 0){
                $wallet_ar = [];
                $sum_toman = 0;
                $sum_usdt = 0;
                $sum_b = 0;
                $sum_bb = 0;
                $sum_c = 0;
                $sum_cc = 0;

                foreach ($wallets as $wallet){
                    $price_usdt = $pricess[$wallet['id_crypto']]['usdt'];
                    $price_toman = $pricess[$wallet['id_crypto']]['toman']['sell'];

                    $wallet['amount_toman'] = round($wallet['balance'] * $price_toman);
                    $wallet['amount_usdt'] = round($wallet['balance'] * $price_usdt, 4);
                    $sum_toman += $wallet['amount_toman'];
                    $sum_usdt += $wallet['amount_usdt'];

                    $indexArrayFind = (string)array_search($wallet['id_crypto'], array_column($portfolio_wallet, 'id_crypto'));
                    $a = $portfolio_wallet[$indexArrayFind]->amount_usdt ?? 0;
                    $aa = round($portfolio_wallet[$indexArrayFind]->amount_toman ?? 0);

                    $b = TransactionCrypto::where(['id_user'=>$user->id,'type'=>'deposit','id_crypto'=>$wallet['id_crypto'],'status'=>'success'])
                        ->where('created_at','>=', $dateStart)->where('created_at','<', $dateStop)->sum('amount');
                    $bb = round($b * $price_toman);
                    $b = $b * $price_usdt;
                    $sum_b += $b;
                    $sum_bb += $bb;

                    $c = TransactionCrypto::where(['id_user'=>$user->id,'type'=>'withdraw','id_crypto'=>$wallet['id_crypto'],'status'=>'success'])
                        ->where('created_at','>=', $dateStart)->where('created_at','<', $dateStop)->sum('amount');
                    $cc = round($c * $price_toman);
                    $c = $c * $price_usdt;
                    $sum_c += $c;
                    $sum_cc += $cc;

                    $e = $wallet['amount_usdt'] - $a + $c - $b;
                    $ee = round($wallet['amount_toman'] - $aa + $cc - $bb);

                    $wallet['change_usdt'] = $e;
                    $wallet['change_toman'] = $ee;
                    array_push($wallet_ar, $wallet);
                }

                $change_usdt = $sum_usdt - ($portfolio->sum_usdt ?? 0) + $sum_c - $sum_b;
                $change_toman = $sum_toman - ($portfolio->sum_toman ?? 0) + $sum_cc - $sum_bb;

                $portfolio = new Portfolios();
                $portfolio->date = $date;
                $portfolio->id_user = $user->id;
                $portfolio->sum_toman = $sum_toman;
                $portfolio->sum_usdt = $sum_usdt;
                $portfolio->change_usdt = $change_usdt;
                $portfolio->change_toman = $change_toman;
                $portfolio->wallets = json_encode($wallet_ar);

                $portfolio->save();
            }
        }
    }
}
