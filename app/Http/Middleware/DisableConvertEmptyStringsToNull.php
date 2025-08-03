<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull as Middleware;

class DisableConvertEmptyStringsToNull extends Middleware
{
    /**
     * The URIs that should be excluded from this middleware.
     *
     * @var array
     */
    protected $except = [
        'api/v2/*/list',
        'api/v2/admins/admin/*',
        'api/v2/setting/crypto/trade/*',
        'api/v2/setting/crypto/balance/*',
        'api/v2/setting/crypto/balance',
        'api/v2/setting/crypto/little',
        'api/v2/setting/crypto/wallets',
        'api/v2/setting/crypto/auto-trade',
        'api/v2/audit/daily/add-new',
        'api/v2/setting/notification/list',
        'api/v2/setting/crypto/all-balance/*',
        'api/v2/audit/cust/add-new',
    ];

    /**
     * Transform the given value.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        // اگر مسیر در لیست استثناء‌ها باشد، مقادیر تغییری نمی‌کنند
        if ($this->inExceptArray(request()->path())) {
            return $value;
        }

        // تبدیل رشته‌های خالی به null
        return is_string($value) && $value === '' ? null : $value;
    }

    /**
     * Determine if the request has a URI that should pass through.
     *
     * @param  string  $path
     * @return bool
     */
    protected function inExceptArray($path)
    {
        foreach ($this->except as $except) {
            if (\Illuminate\Support\Str::is($except, $path)) {
                return true;
            }
        }

        return false;
    }
}
