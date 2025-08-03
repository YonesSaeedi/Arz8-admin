<?php

namespace App\Http\Middleware;

use Closure;

class VerifyRequestSignature
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */

    protected $except = [
        'api/v2/tickets/*/new',
        'api/v2/setting/notification/send',
        'api/v2/setting/crypto/new',
        'api/v2/setting/settings/banner',
        'api/v2/setting/settings/stories',
        'api/v2/card-bank/edit/*',
        'api/v2/users/edit/settings/*',
    ];

    public function handle($request, Closure $next)
    {
        return $next($request);

        if ($this->inExceptArray(request()->path())) {
            return $next($request);
        }

        $secretKey = env('ApiKeySignature');

        // دریافت هدرها
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        // بررسی زمان (مثلاً حداکثر 1 دقیقه قبل یا بعد)
        if (abs(time() - $timestamp) > 60) {
            return response()->json(['error' => 'Invalid timestamp'], 403);
        }

        $data = $request->all();
        if ($request->hasFile('file') || $request->hasFile('icon'))
            unset($data['file'],$data['icon']); // حذف فایل از داده‌ها



        if (isset($request['search']) && $request['search'] === null)
            $data['search'] = '';

        // ایجاد مجدد داده قابل امضا
        $dataToSign = $request->method() . ' ' . $request->path() . ' ' . $timestamp . ' ' . str_replace('\/', '/', json_encode($data, JSON_UNESCAPED_UNICODE));
        //if (\Auth::user()->id == 2)
        //    dd($data,json_encode($data, JSON_UNESCAPED_UNICODE));

        // تولید امضا سمت سرور
        $expectedSignature = hash_hmac('sha256', $dataToSign, $secretKey);
        //dd($expectedSignature);

        // مقایسه امضاها
        if ($signature !== $expectedSignature) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }

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

