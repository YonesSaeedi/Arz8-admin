<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Kavenegar;

class OTPService
{
    const OTP_LENGTH = 6;
    const OTP_EXPIRY_MINUTES = 5;
    const MAX_ATTEMPTS = 3;
    const RESEND_COOLDOWN = 60;

    const TYPE_TRANSFER = 'transfer';
    const TYPE_LOGIN = 'login';
    const TYPE_WITHDRAW = 'withdraw';

    const METHOD_SMS = 'sms';
    const METHOD_EMAIL = 'email';
    const METHOD_TELEGRAM = 'telegram';
    const METHOD_GOOGLE = 'google';

    /**
     * تولید و ارسال OTP برای تراکنش
     */
    public function generateAndSendForTransaction(User $user, int $transactionId, string $type, array $data = []): array
    {
        $lock = Cache::lock("otp_generate_{$user->id}_{$type}", 5);

        if (!$lock->get()) {
            throw new \Exception('درخواست قبلی در حال پردازش است');
        }

        try {
            // بررسی وضعیت دو مرحله‌ای کاربر
            $twofa = json_decode($user->twofa ?? '{}');
            if (!isset($twofa->type) || ($twofa->status ?? false) != true) {
                throw new \Exception('برای انجام این عملیات، نیاز به فعال‌سازی احراز هویت دو مرحله‌ای دارید');
            }

            // بررسی کول‌داون
            if ($this->isInCooldown($user->id, $type)) {
                throw new \Exception('لطفاً قبل از درخواست مجدد کمی صبر کنید');
            }

            // پاک کردن OTP قبلی برای این تراکنش
            $this->clearOTP($user->id, $type, $transactionId);

            // تولید OTP (به جز برای گوگل آتنتیکیتور)
            $otp = $twofa->type === self::METHOD_GOOGLE ? null : $this->generateOTP();

            // ذخیره در کش با کلید ترکیبی user_id + transaction_id
            $this->storeOTP($user->id, $type, $transactionId, $otp, $twofa->type, $data);

            // ارسال کد بر اساس نوع روش
            $result = $this->sendOTP($user, $otp, $twofa->type, $type, $data);

            // تنظیم کول‌داون
            $this->setCooldown($user->id, $type);

            return $result;

        } finally {
            $lock->release();
        }
    }

    /**
     * بررسی صحت OTP برای تراکنش
     */
    public function verifyForTransaction(User $user, int $transactionId, string $type, string $code): bool
    {
        $lock = Cache::lock("otp_verify_{$user->id}_{$type}", 5);

        if (!$lock->get()) {
            throw new \Exception('درخواست تأیید در حال پردازش است');
        }

        try {
            $cacheKey = $this->getCacheKey($user->id, $type, $transactionId);
            $otpData = Cache::get($cacheKey);

            // بررسی وجود OTP
            if (!$otpData) {
                throw new \Exception('کد تأیید منقضی شده است');
            }

            // بررسی تعداد تلاش‌ها
            if ($otpData['attempts'] >= self::MAX_ATTEMPTS) {
                $this->clearOTP($user->id, $type, $transactionId);
                throw new \Exception('تعداد تلاش‌ها بیش از حد مجاز است');
            }

            // بررسی صحت کد بر اساس نوع روش
            $isValid = $this->verifyCode($user, $otpData, $code);

            if (!$isValid) {
                // افزایش تعداد تلاش‌ها
                $otpData['attempts']++;
                Cache::put($cacheKey, $otpData, now()->addMinutes(self::OTP_EXPIRY_MINUTES));

                $remainingAttempts = self::MAX_ATTEMPTS - $otpData['attempts'];
                throw new \Exception("کد تأیید نادرست است. {$remainingAttempts} تلاش باقی مانده");
            }

            // OTP معتبر - پاک کردن از کش
            $this->clearOTP($user->id, $type, $transactionId);

            // ذخیره لاگ موفق
            $this->logVerification($user->id, $transactionId, $type, true);

            return true;

        } finally {
            $lock->release();
        }
    }

    /**
     * ارسال OTP بر اساس نوع روش
     */
    private function sendOTP(User $user, ?string $otp, string $method, string $type, array $data = []): array
    {
        switch ($method) {
            case self::METHOD_EMAIL:
                return $this->sendEmailOTP($user, $otp, $type);

            case self::METHOD_SMS:
                return $this->sendSMSOTP($user, $otp, $type, $data);

            case self::METHOD_TELEGRAM:
                return $this->sendTelegramOTP($user, $otp, $type);

            case self::METHOD_GOOGLE:
                return $this->prepareGoogleOTP($user, $type);

            default:
                throw new \Exception('روش احراز هویت نامعتبر است');
        }
    }

    /**
     * ارسال OTP از طریق ایمیل
     */
    private function sendEmailOTP(User $user, string $otp, string $type): array
    {
        try {
            Mail::send('email.email-verification', ['code' => $otp], function ($message) use ($user) {
                $message->to($user->email)->subject(__('Email Verification'));
            });

            return [
                'status' => true,
                'message' => __('successfully.'),
                'message_otp' => __('The code has been sent to your email'),
                'method' => self::METHOD_EMAIL
            ];
        } catch (\Exception $e) {
            Log::error('Email OTP sending error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
                'type' => $type
            ]);
            throw new \Exception('خطا در ارسال ایمیل');
        }
    }

    /**
     * ارسال OTP از طریق SMS
     */
    private function sendSMSOTP(User $user, string $otp, string $type, array $data = []): array
    {
        try {
            $template = $this->getTemplate($type);
            $tokens = $this->prepareTokens($otp, $type, $data);

            \Kavenegar::VerifyLookup(
                $user->mobile,
                $tokens['token1'] ?? $otp,
                $tokens['token2'] ?? null,
                $tokens['token3'] ?? null,
                $template,
                $tokens['token4'] ?? null
            );

            return [
                'status' => true,
                'message' => __('successfully.'),
                'message_otp' => __('SMS sent to your mobile'),
                'method' => self::METHOD_SMS
            ];
        } catch (\Exception $e) {
            Log::error('SMS sending error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'mobile' => $user->mobile,
                'type' => $type
            ]);
            throw new \Exception('خطا در ارسال پیامک');
        }
    }

    /**
     * ارسال OTP از طریق تلگرام
     */
    private function sendTelegramOTP(User $user, string $otp, string $type): array
    {
        try {
            if (empty($user->telegram_id)) {
                throw new \Exception('شناسه تلگرام کاربر تنظیم نشده است');
            }

            $telegram = new \App\Http\Controllers\Telegram\TelegramController;
            $telegram->telegram->sendMessage([
                'chat_id' => $user->telegram_id,
                'text' => __('Verification code:') . PHP_EOL . "`{$otp}`",
                'parse_mode' => 'MarkdownV2',
            ]);

            return [
                'status' => true,
                'message' => __('successfully.'),
                'message_otp' => __('Insert the code that has been sent by the telegram robot'),
                'method' => self::METHOD_TELEGRAM
            ];
        } catch (\Exception $e) {
            Log::error('Telegram OTP sending error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'telegram_id' => $user->telegram_id,
                'type' => $type
            ]);
            throw new \Exception('خطا در ارسال پیام تلگرام');
        }
    }

    /**
     * آماده‌سازی برای گوگل آتنتیکیتور
     */
    private function prepareGoogleOTP(User $user, string $type): array
    {
        return [
            'status' => true,
            'message' => __('successfully.'),
            'message_otp' => __('Enter the code you see in the google authenticator app'),
            'method' => self::METHOD_GOOGLE
        ];
    }

    /**
     * بررسی صحت کد بر اساس نوع روش
     */
    private function verifyCode(User $user, array $otpData, string $code): bool
    {
        $twofa = json_decode($user->twofa ?? '{}');

        switch ($otpData['method']) {
            case self::METHOD_GOOGLE:
                $google2fa = new \PragmaRX\Google2FAQRCode\Google2FA();
                return $google2fa->verifyKey(Crypt::decryptString($twofa->googleSecret), $code);

            case self::METHOD_EMAIL:
            case self::METHOD_SMS:
            case self::METHOD_TELEGRAM:
                return $otpData['code'] === $code;

            default:
                return false;
        }
    }

    /**
     * تولید کد ۶ رقمی
     */
    private function generateOTP(): string
    {
        return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * ذخیره OTP در کش
     */
    private function storeOTP(int $userId, string $type, int $transactionId, ?string $otp, string $method, array $data = []): void
    {
        $cacheKey = $this->getCacheKey($userId, $type, $transactionId);

        $otpData = [
            'code' => $otp,
            'method' => $method,
            'transaction_id' => $transactionId,
            'user_id' => $userId,
            'attempts' => 0,
            'created_at' => now(),
            'data' => $data
        ];

        Cache::put($cacheKey, $otpData, now()->addMinutes(self::OTP_EXPIRY_MINUTES));
    }

    /**
     * پاک کردن OTP
     */
    private function clearOTP(int $userId, string $type, int $transactionId): void
    {
        $cacheKey = $this->getCacheKey($userId, $type, $transactionId);
        Cache::forget($cacheKey);
    }

    /**
     * تولید کلید کش برای OTP
     */
    private function getCacheKey(int $userId, string $type, int $transactionId): string
    {
        return "otp_{$userId}_{$type}_{$transactionId}";
    }

    /**
     * بررسی کول‌داون
     */
    private function isInCooldown(int $userId, string $type): bool
    {
        return Cache::has("otp_cooldown_{$userId}_{$type}");
    }

    /**
     * تنظیم کول‌داون
     */
    private function setCooldown(int $userId, string $type): void
    {
        Cache::put("otp_cooldown_{$userId}_{$type}", true, self::RESEND_COOLDOWN);
    }

    /**
     * انتخاب تمپلیت
     */
    private function getTemplate(string $type): string
    {
        $templates = [
            self::TYPE_TRANSFER => 'VerifyMobile',
            self::TYPE_LOGIN => 'VerifyMobile',
            self::TYPE_WITHDRAW => 'VerifyMobile',
        ];

        return $templates[$type] ?? 'VerifyMobile';
    }

    /**
     * آماده‌سازی توکن‌ها
     */
    private function prepareTokens(string $otp, string $type, array $data): array
    {
        $tokens = ['token1' => $otp];

        switch ($type) {
            case self::TYPE_TRANSFER:
                $tokens['token2'] = $data['amount'] ?? '';
                $tokens['token3'] = $data['asset_name'] ?? '';
                break;

            case self::TYPE_WITHDRAW:
                $tokens['token2'] = $data['amount'] ?? '';
                $tokens['token3'] = number_format($data['amount'] / 10) . ' تومان';
                break;
        }

        return $tokens;
    }

    /**
     * لاگ کردن تأیید
     */
    private function logVerification(int $userId, int $transactionId, string $type, bool $success): void
    {
        Log::info('OTP verification', [
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'type' => $type,
            'success' => $success,
            'ip' => request()->ip()
        ]);
    }

    /**
     * بررسی وضعیت OTP
     */
    public function getOTPStatus(int $userId, string $type, int $transactionId): array
    {
        $cacheKey = $this->getCacheKey($userId, $type, $transactionId);
        $otpData = Cache::get($cacheKey);

        if (!$otpData) {
            return ['exists' => false];
        }

        return [
            'exists' => true,
            'method' => $otpData['method'],
            'attempts' => $otpData['attempts'],
            'expires_at' => now()->diffInSeconds($otpData['created_at']->addMinutes(self::OTP_EXPIRY_MINUTES)),
            'can_resend' => !$this->isInCooldown($userId, $type)
        ];
    }

    /**
     * بررسی وضعیت کول‌داون کاربر
     */
    public function getUserCooldownStatus(int $userId, string $type): array
    {
        $isInCooldown = $this->isInCooldown($userId, $type);

        if ($isInCooldown) {
            $remaining = self::RESEND_COOLDOWN;
            return [
                'in_cooldown' => true,
                'remaining_seconds' => max(0, $remaining)
            ];
        }

        return [
            'in_cooldown' => false,
            'remaining_seconds' => 0
        ];
    }

    /**
     * بررسی فعال بودن احراز هویت دو مرحله‌ای برای کاربر
     */
    public function is2FAEnabled(User $user): bool
    {
        $twofa = json_decode($user->twofa ?? '{}');
        return isset($twofa->type) && ($twofa->status ?? false) == true;
    }

    /**
     * دریافت روش فعال احراز هویت کاربر
     */
    public function getUser2FAMethod(User $user): ?string
    {
        $twofa = json_decode($user->twofa ?? '{}');
        return ($twofa->status ?? false) ? ($twofa->type ?? null) : null;
    }
}
