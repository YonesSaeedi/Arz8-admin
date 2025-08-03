<?php

namespace App\Http\Middleware;

use Closure;
use HTMLPurifier;
use HTMLPurifier_Config;

class XssSanitizer
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next)
    {
        if (!in_array(strtolower($request->method()), ['put', 'post'])) {
            return $next($request);
        }

        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);

        $input = $request->all();
        array_walk_recursive($input, function (&$input, $key) use ($purifier) {
            if (!is_numeric($input) && !is_bool($input) && !is_null(null))
                $input = $purifier->purify($input);
        });

        $request->merge($input);

        return $next($request);

    }
}

