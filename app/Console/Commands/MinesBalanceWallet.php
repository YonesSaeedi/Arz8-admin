<?php

namespace App\Console\Commands;

use App\Models\Cryptocurrency;
use App\Models\Settings;
use App\Models\Wallet;
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

        $wallets = Wallet::whereNotIn('id_user', $ids)
            ->where('type', Wallet::TYPE_ASSET)
            ->where(function ($query) {
                $query->where('balance', '<', 0)
                    ->orWhere('available_balance', '<', 0);
            })->get();

        foreach ($wallets as $wallet) {
            $crypto = Cryptocurrency::find($wallet->id_crypto);
            try {
                $kavenegar->VerifyLookup('09100588871', $wallet->id_user, $crypto->symbol, null, 'MinesBalance');
                array_push($ids, $wallet->id_user);
            } catch (\Exception $e) {
                \Log::error('Mines balance notification failed: ' . $e->getMessage());
            }
        }

        Settings::where('name', 'mines_wallets')->update(['value' => json_encode($ids)]);
    }
}
