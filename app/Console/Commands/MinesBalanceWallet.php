<?php

namespace App\Console\Commands;

use App\Models\Cryptocurrency;
use App\Models\Settings;
use App\Models\WalletsCrypto;
use Illuminate\Console\Command;

class MinesBalanceWallet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mines:balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send id_user mines for admin';

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
        $kavenegar = new \Kavenegar\KavenegarApi(env('KavehnegarKey'));
        $ids = json_decode(Settings::where('name', 'mines_wallets')->first()['value']);
        $wallets = WalletsCrypto::whereNotIn('id_user',$ids)->where(function ($query) {
                $query->where('value_num','<',0)
                ->orWhere('value_available_num','<',0);
        })->get();
        foreach ($wallets as $wallet){
            $crypto = Cryptocurrency::find($wallet->id_crypto);
            try{
                $kavenegar->VerifyLookup('09100588871',$wallet->id_user,$crypto->symbol,null,'MinesBalance');
                array_push($ids,$wallet->id_user);
            }catch (\Exception $e){}
        }

        Settings::where('name', 'mines_wallets')->update(['value'=>json_encode($ids)]);
    }
}
