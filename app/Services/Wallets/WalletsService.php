<?php

namespace App\Services\Wallets;

use App\Models\Cryptocurrency;
use App\Models\Wallet;
use App\Models\Internalcurrency;
use DB;

class WalletsService
{
    public function getBalanceCrypto($id_crypto, $justList = false)
    {
        $crypto = Cryptocurrency::find($id_crypto);
        if (!auth()->check()) {
            return (object)['balance' => 0, 'balance_available' => 0];
        }

        $wallet = Wallet::where('id_crypto', $crypto->id)
            ->where('id_user', auth()->id())
            ->where('type', Wallet::TYPE_ASSET)
            ->first();

        if (!$wallet && $justList) {
            return (object)['balance' => 0, 'balance_available' => 0];
        } else if (!$wallet) {
            $wallet = $this->createWalletCrypto($crypto->id);
        }

        $balance = (float) $wallet->balance;
        $balance_available = (float) $wallet->available_balance;

        return (object)[
            'balance' => $balance,
            'balance_available' => $balance_available
        ];
    }
    function createWalletCrypto($id_crypto, $id_user = null)
    {
        $crypto = Cryptocurrency::find($id_crypto);
        $userId = $id_user ?? auth()->id();

        return Wallet::createAssetWallet($userId, $id_crypto, $crypto->symbol);
    }

    function createWalletFiat($id_user, $id_internal = 1)
    {
        return Wallet::createTomanWallet($id_user);
    }

    function getBalanceFiat($id_user, $id_internal = 1)
    {
        $wallet = Wallet::where('id_user', $id_user)
            ->where('type', Wallet::TYPE_CURRENCY)
            ->where('currency_code', Wallet::CURRENCY_TOMAN)
            ->first();

        if (!$wallet) {
            $wallet = $this->createWalletFiat($id_user);
        }

        $internal = \App\Models\Internalcurrency::find($id_internal);

        return (object)[
            'balance' => (float) $wallet->balance,
            'balance_available' => (float) $wallet->available_balance,
            'internal' => $internal,
            'wallet' => $wallet
        ];
    }


    public function getWalletCrypto(int $userId, int $cryptoId, bool $lockForUpdate = false)
    {
        $query = Wallet::where('id_crypto', $cryptoId)
            ->where('id_user', $userId)
            ->where('type', Wallet::TYPE_ASSET);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $wallet = $query->first();

        if (!$wallet) {
            if ($lockForUpdate && DB::transactionLevel() > 0) {
                // سعی مجدد با قفل
                $wallet = Wallet::where('id_crypto', $cryptoId)
                    ->where('id_user', $userId)
                    ->where('type', Wallet::TYPE_ASSET)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    $wallet = $this->createWalletCrypto($cryptoId, $userId);
                }
            } else {
                $wallet = $this->createWalletCrypto($cryptoId, $userId);
            }
        }

        $crypto = Cryptocurrency::find($cryptoId);

        return (object)[
            'wallet' => $wallet,
            'balance' => (float) $wallet->balance,
            'balance_available' => (float) $wallet->available_balance,
            'crypto' => $crypto
        ];
    }

    /**
     * دریافت ولت تومان با قفل
     */
    public function getWalletFiat(int $userId, bool $lockForUpdate = false, int $internalId = 1)
    {
        $query = Wallet::where('id_user', $userId)
            ->where('type', Wallet::TYPE_CURRENCY)
            ->where('currency_code', Wallet::CURRENCY_TOMAN);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $wallet = $query->first();

        if (!$wallet) {
            if ($lockForUpdate && DB::transactionLevel() > 0) {
                // سعی مجدد با قفل
                $wallet = Wallet::where('id_user', $userId)
                    ->where('type', Wallet::TYPE_CURRENCY)
                    ->where('currency_code', Wallet::CURRENCY_TOMAN)
                    ->lockForUpdate()
                    ->first();

                if (!$wallet) {
                    $wallet = $this->createWalletFiat($userId);
                }
            } else {
                $wallet = $this->createWalletFiat($userId);
            }
        }

        $internal = Internalcurrency::find($internalId);

        return (object)[
            'wallet' => $wallet,
            'balance' => (float) $wallet->balance,
            'balance_available' => (float) $wallet->available_balance,
            'internal' => $internal
        ];
    }

    /**
     * بررسی موجودی معدن (Mines)
     */
    public function checkMinesBalance($id_user): array
    {
        $wallets = Wallet::where('id_user', $id_user)
            ->where('type', Wallet::TYPE_ASSET)
            ->get();

        foreach ($wallets as $wallet) {
            $crypto = Cryptocurrency::find($wallet->id_crypto);
            $balance = (float) $wallet->balance;
            $balance_available = (float) $wallet->available_balance;

            if ($balance < -5000 || $balance_available < -5000) {
                return [
                    'status' => false,
                    'msg' => 'برداشت برای شما امکان پذیر نیست و لطفا با پشتیبانی در تماس باشید.'
                ];
            }
        }

        return [
            'status' => true,
            'msg' => 'Balance check passed'
        ];
    }


    /**
     * واریز به ولت تومانی
     */
    public function depositToTomanWallet(int $userId, float $amount): bool
    {
        $wallet = $this->getWalletFiat($userId, true);
        return $wallet->wallet->deposit($amount);
    }

    /**
     * برداشت از ولت تومانی
     */
    public function withdrawFromTomanWallet(int $userId, float $amount): bool
    {
        $wallet = $this->getWalletFiat($userId, true);
        return $wallet->wallet->withdraw($amount);
    }

    /**
     * واریز به ولت رمزارز
     */
    public function depositToCryptoWallet(int $userId, int $cryptoId, float $amount): bool
    {
        $wallet = $this->getWalletCrypto($userId, $cryptoId, true);
        return $wallet->wallet->deposit($amount);
    }

    /**
     * برداشت از ولت رمزارز
     */
    public function withdrawFromCryptoWallet(int $userId, int $cryptoId, float $amount): bool
    {
        $wallet = $this->getWalletCrypto($userId, $cryptoId, true);
        return $wallet->wallet->withdraw($amount);
    }

    /**
     * بلوک کردن موجودی در ولت تومانی
     */
    public function blockTomanBalance(int $userId, float $amount): bool
    {
        $wallet = $this->getWalletFiat($userId, true);
        return $wallet->wallet->blockBalance($amount);
    }

    /**
     * آزاد کردن موجودی بلوک شده در ولت تومانی
     */
    public function unblockTomanBalance(int $userId, float $amount): bool
    {
        $wallet = $this->getWalletFiat($userId, true);
        return $wallet->wallet->unblockBalance($amount);
    }

    /**
     * بلوک کردن موجودی در ولت رمزارز
     */
    public function blockCryptoBalance(int $userId, int $cryptoId, float $amount): bool
    {
        $wallet = $this->getWalletCrypto($userId, $cryptoId, true);
        return $wallet->wallet->blockBalance($amount);
    }

    /**
     * آزاد کردن موجودی بلوک شده در ولت رمزارز
     */
    public function unblockCryptoBalance(int $userId, int $cryptoId, float $amount): bool
    {
        $wallet = $this->getWalletCrypto($userId, $cryptoId, true);
        return $wallet->wallet->unblockBalance($amount);
    }
}
