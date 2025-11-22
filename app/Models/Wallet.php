<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class Wallet extends Model
{
    protected $table='users_wallets';

    use HasFactory;

    protected $fillable = [
        'id_user',
        'type',
        'id_crypto',
        'currency_code',
        'balance',
        'blocked_balance',
        'balance_hash',
        'blocked_balance_hash',
        'wallet_hash'
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'blocked_balance' => 'decimal:8',
    ];

    const TYPE_ASSET = 'crypto';
    const TYPE_CURRENCY = 'currency';
    const CURRENCY_TOMAN = 'IRT';


    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wallet) {
            // بررسی وجود ولت تومانی تکراری
            if ($wallet->is_toman) {
                $existingTomanWallet = self::where('id_user', $wallet->id_user)
                    ->where('type', self::TYPE_CURRENCY)
                    ->where('currency_code', self::CURRENCY_TOMAN)
                    ->exists();

                if ($existingTomanWallet) {
                    throw new \Exception('کاربر نمی‌تواند بیش از یک کیف پول تومانی داشته باشد');
                }
            }

            // بررسی وجود ولت دارایی تکراری
            if ($wallet->id_crypto) {
                $existingAssetWallet = self::where('id_user', $wallet->id_user)
                    ->where('type', self::TYPE_ASSET)
                    ->where('id_crypto', $wallet->id_crypto)
                    ->exists();

                if ($existingAssetWallet) {
                    throw new \Exception('کاربر نمی‌تواند بیش از یک کیف پول برای این رمزارز داشته باشد');
                }
            }
        });
    }

    // کلید مخفی برای هش (در env تعریف شود)
    private function getHashSecret(): string
    {
        return env('WALLET_HASH_SECRET', 'default-secret');
    }

    /**
     * محاسبه هش برای یک مقدار
     */
    private function calculateHash(float $value, string $type): string
    {
        $data = "{$this->id_user}|{$this->getWalletIdentifier()}|{$type}|{$value}|{$this->getHashSecret()}";
        return hash_hmac('sha256', $data, $this->getHashSecret());
    }

    /**
     * شناسه یکتا برای کیف پول
     */
    private function getWalletIdentifier(): string
    {
        if ($this->id_crypto) {
            return "asset_{$this->id_crypto}";
        }
        return "currency_{$this->currency_code}";
    }

    /**
     * بررسی صحت هش
     */
    private function verifyHash(float $value, string $hash, string $type): bool
    {
        $data = "{$this->id_user}|{$this->getWalletIdentifier()}|{$type}|{$value}|{$this->getHashSecret()}";
        return hash_equals(hash_hmac('sha256', $data, $this->getHashSecret()), $hash);
    }

    /**
     * بروزرسانی هش‌ها
     */
    private function updateHashes(): void
    {
        $this->balance_hash = $this->calculateHash($this->balance, 'balance');
        $this->blocked_balance_hash = $this->calculateHash($this->blocked_balance, 'blocked_balance');

        // هش کل کیف پول
        $walletData = "{$this->balance}|{$this->blocked_balance}|{$this->balance_hash}|{$this->blocked_balance_hash}";
        $this->wallet_hash = Hash::make($walletData . $this->getHashSecret());
    }

    /**
     * بررسی یکپارچگی کیف پول
     */
    public function verifyIntegrity(): bool
    {
        // بررسی هش مقادیر
        $balanceValid = $this->verifyHash($this->balance, $this->balance_hash, 'balance');
        $blockedBalanceValid = $this->verifyHash($this->blocked_balance, $this->blocked_balance_hash, 'blocked_balance');

        // بررسی هش کل
        $walletData = "{$this->balance}|{$this->blocked_balance}|{$this->balance_hash}|{$this->blocked_balance_hash}";
        $walletHashValid = Hash::check($walletData . $this->getHashSecret(), $this->wallet_hash);

        return $balanceValid && $blockedBalanceValid && $walletHashValid;
    }

    /**
     * آیا کیف پول هک شده
     */
    public function getIsCompromisedAttribute(): bool
    {
        return !$this->verifyIntegrity();
    }

    /**
     * Override save برای محاسبه هش
     */
    public function save(array $options = [])
    {
        $this->updateHashes();
        return parent::save($options);
    }

    /**
     * Override fill برای بررسی یکپارچگی
     */
    public function fill(array $attributes)
    {
        $result = parent::fill($attributes);

        // اگر مقادیر تغییر کردند، هش‌ها باید بروز شوند
        if ($this->isDirty(['balance', 'blocked_balance'])) {
            $this->updateHashes();
        }

        return $result;
    }

    // ارتباطات
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Cryptocurrency::class);
    }

    // اسکوپ‌ها
    public function scopeToman($query)
    {
        return $query->where('type', self::TYPE_CURRENCY)
            ->where('currency_code', self::CURRENCY_TOMAN);
    }

    public function scopeAssetWallets($query)
    {
        return $query->where('type', self::TYPE_ASSET);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('balance', '>', 0)
                ->orWhere('blocked_balance', '>', 0);
        });
    }

    // ابزارهای کمکی
    public function getIsTomanAttribute(): bool
    {
        return $this->type === self::TYPE_CURRENCY && $this->currency_code === self::CURRENCY_TOMAN;
    }

    public function getIsAssetAttribute(): bool
    {
        return $this->type === self::TYPE_ASSET;
    }

    public function getAvailableBalanceAttribute(): string
    {
        return $this->balance - $this->blocked_balance;
    }

    // متدهای مدیریت موجودی
    public function deposit(float $amount): bool
    {
        if ($this->is_compromised) {
            throw new \Exception('Wallet integrity compromised!');
        }

        $this->balance += $amount;
        return $this->save();
    }

    public function withdraw(float $amount): bool
    {
        if ($this->is_compromised) {
            throw new \Exception('Wallet integrity compromised!');
        }

        if ($this->available_balance >= $amount) {
            $this->balance -= $amount;
            return $this->save();
        }
        return false;
    }

    public function blockBalance(float $amount): bool
    {
        if ($this->is_compromised) {
            throw new \Exception('Wallet integrity compromised!');
        }

        if ($this->available_balance >= $amount) {
            $this->blocked_balance += $amount;
            return $this->save();
        }
        return false;
    }

    public function unblockBalance(float $amount): bool
    {
        if ($this->is_compromised) {
            throw new \Exception('Wallet integrity compromised!');
        }

        if ($this->blocked_balance >= $amount) {
            $this->blocked_balance -= $amount;
            return $this->save();
        }
        return false;
    }

    // متدهای ایجاد ایمن
    public static function createTomanWallet($userId): Wallet
    {
        $existingWallet = self::where('id_user', $userId)
            ->where('type', self::TYPE_CURRENCY)
            ->where('currency_code', self::CURRENCY_TOMAN)
            ->first();

        if ($existingWallet) {
            return $existingWallet;
        }

        return self::create([
            'id_user' => $userId,
            'type' => self::TYPE_CURRENCY,
            'currency_code' => self::CURRENCY_TOMAN,
            'balance' => 0,
            'blocked_balance' => 0
        ]);
    }

    public static function createAssetWallet($userId, $assetId,$symbol): Wallet
    {
        $existingWallet = self::where('id_user', $userId)
            ->where('type', self::TYPE_ASSET)
            ->where('id_crypto', $assetId)
            ->where('currency_code', $symbol)
            ->first();

        if ($existingWallet) {
            return $existingWallet;
        }

        return self::create([
            'id_user' => $userId,
            'type' => self::TYPE_ASSET,
            'id_crypto' => $assetId,
            'currency_code' => $symbol,
            'balance' => 0,
            'blocked_balance' => 0
        ]);
    }


    public function canWithdraw(float $amount): bool
    {
        return $this->available_balance >= $amount && !$this->is_compromised;
    }

    public function canBlock(float $amount): bool
    {
        return $this->available_balance >= $amount && !$this->is_compromised;
    }
}
