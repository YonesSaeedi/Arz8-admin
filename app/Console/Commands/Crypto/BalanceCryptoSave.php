<?php

namespace App\Console\Commands\Crypto;
use App\Http\Controllers\Exchange\BinanceApi;
use App\Http\Controllers\Exchange\CoinexApi;
use App\Http\Controllers\Exchange\KucoinApi;
use App\Models\Cryptocurrency;
use Crypt;
use DB;
use Illuminate\Console\Command;

class BalanceCryptoSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:cryptoSave';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'save balance coins in database';

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
        $binance = new BinanceApi();
        $coinex = new CoinexApi();
        $kucoin = new KucoinApi();

        $cryptos = Cryptocurrency::all();

        $binance_balance = [];
        foreach ($binance->api as $key => $item){
            $binance_balance[$key] = $binance->api[$key]->balances();
        }

        $kucoin_balance = [];
        $kucoin_balance_wallets = $kucoin->apiAccount->getList();
        foreach ($kucoin->apiAccount_list as $key => $item){
            $kucoin_balance[$key] = $kucoin->apiAccount_list[$key]->getList();
        }

        $coinex_balance = [];
        foreach ($coinex->api as $key => $item){
            $coinex_balance[$key] = $coinex->api[$key]->balance()->data;
        }

        $response = \Http::withHeaders([
            'accept'=>'application/json',
            'Content-Type'=>'application/json',
            'apiKey'=>'BEr4WdSiFIQoFxbcEJ86P22G7mfhcBQoQTq7KbS2zx1dfjRiNzs8PLytIeOtISCnsRQwOH2BbhJffluIM1WXxnZVtC4nLfFIB2ziOwKlSRsX8lSruobAAiEEmtkBrxFrzxewM9OllpQEncpDQRwva9Sji6vYsCycVF07jsSkAMPcA4cN5nCXhpmpBNpH79y',
        ])->get('https://api.exonyx.com/api/v1/reseller'.'/wallet/index');
        $response = (object)$response->json();
        $balanceExonyx = $response->data ?? [];

        foreach ($cryptos as $crypto){
            // binance
            $binance_b = 0;
            $binance_account_b = [];
            foreach ($binance->api as $key => $item){
                $b = isset($binance_balance[$key][$crypto->symbol])? $binance_balance[$key][$crypto->symbol]['available'] + $binance_balance[$key][$crypto->symbol]['onOrder']: '0';
                $binance_account_b[$key] = $b;
                $binance_b += $b;
            }

            // coinex
            $coinex_b = 0;
            $coinex_account_b = [];
            foreach ($coinex->api as $key => $item){
                $b = isset($coinex_balance[$key][$crypto->symbol])?$coinex_balance[$key][$crypto->symbol]['available']:'0';
                $coinex_account_b[$key] = $b;
                $coinex_b += $b;
            }

            // kucoin
            $kucoin_wallet_b = 0;
            $indexArrayFind = (string)array_search($crypto->symbol, array_column($kucoin_balance_wallets, 'currency'));
            if($indexArrayFind != ""){
                $balances = array_filter($kucoin_balance_wallets,function ($lists) use($crypto){
                    return in_array($crypto->symbol, $lists);
                });
                foreach ($balances as $balance){
                    $kucoin_wallet_b += $balance["balance"];
                }
            }

            $kucoin_b = 0;
            $kucoin_account_b = [];
            foreach ($kucoin->apiAccount_list as $key => $item){
                $indexArrayFind = (string)array_search($crypto->symbol, array_column($kucoin_balance[$key], 'currency'));
                if($indexArrayFind != ""){
                    $balances = array_filter($kucoin_balance[$key],function ($lists) use($crypto){
                        return in_array($crypto->symbol, $lists);
                    });

                    $kucoin_account_b[$key] = 0;
                    foreach ($balances as $balance){
                        $kucoin_b += $balance["balance"];
                        $kucoin_account_b[$key] += $balance["balance"];
                    }
                }else
                    $kucoin_account_b[$key] = 0;
            }

            // Exonyx
            $indexArrayFind = (string)array_search($crypto->symbol, array_column($balanceExonyx, 'currency'));
            $balacne_exonyx = 0;
            if($indexArrayFind != ""){
                $balacne_exonyx = $response->data[$indexArrayFind]['balance'];
            }


            $data = json_decode($crypto->data);
            $data->balance = [
                'binance'=> $binance_b,
                'binance_account'=> $binance_account_b,
                'coinex'=> $coinex_b,
                'coinex_account'=> $coinex_account_b,
                'kucoin_wallet'=> $kucoin_wallet_b,
                'kucoin'=> $kucoin_b,
                'kucoin_account'=> $kucoin_account_b,
                'exonyx' => $balacne_exonyx,
                'sum_all' => $binance_b+$coinex_b+$kucoin_wallet_b+$kucoin_b+$balacne_exonyx,
            ];
            $crypto->data = json_encode($data);
            $crypto->save();
        }

    }

}
