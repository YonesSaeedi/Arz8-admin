<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Http;
class ApiProxy
{
    public static function proxyRequest($method, $realUrl, $headers = [], $data = [])
    {
        $proxyBase = 'https://dev.arz8.com/index.php';
        // فقط پارامتر url داخل کوئری است:
        $proxyUrl = $proxyBase . '?url=' . urlencode($realUrl);

        $request = Http::withHeaders($headers);

        if (strtolower($method) === 'get') {
            // اگر داده query داری، باید دستی به proxyUrl اضافه کنی
            if (!empty($data)) {
                $query = http_build_query($data);
                $proxyUrl .= '&' . $query;
            }
            $response = $request->get($proxyUrl);
        } else {
            // متدهای POST, PUT, DELETE و ...
            $jsonBody = is_array($data) ? json_encode($data) : $data;

            $response = $request->send(strtoupper($method), $proxyUrl, [
                'headers' => $headers,
                'body' => $jsonBody,
            ]);
        }

        return $response;
    }

}
