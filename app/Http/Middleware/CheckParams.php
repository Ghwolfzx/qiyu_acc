<?php

namespace App\Http\Middleware;

use Closure;
use Log;

class CheckParams
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $params = $request->all();
        Log::info(LARAVEL_START . ' [start time] ===' . LARAVEL_START);
        Log::info(LARAVEL_START . ' [url] ==='. url()->full());
        Log::info(LARAVEL_START . ' [ip] ==='. $request->getClientIp());
        $sign = $params['sign'] ?? '';
        unset($params['sign']);
        $md5 = encrypt_md5($params);

        if (env('APP_ENV') !== 'local' && $md5 !== $sign) {
            Log::info('status === 签名验证失败');
            Log::info(LARAVEL_START . ' [end time] ===' . microtime(true));
            return response(['success' => 'false', 'msg' => '签名验证失败', 'result' => ['errorcode' => 2]]);
        }
        $response = $next($request);

        Log::info(LARAVEL_START . ' [response result] ===' . json_encode($response));
        Log::info(LARAVEL_START . ' [end time] ===' . microtime(true));
        return $response;
    }
}
