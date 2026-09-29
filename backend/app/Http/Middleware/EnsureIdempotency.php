<?php

namespace App\Http\Middleware;

use App\Exceptions\BusinessRuleException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency-Key: إذا أعاد التطبيق إرسال نفس طلب الإنشاء (انقطاع شبكة، ضغط مزدوج)
 * يرجع نفس الرد بدل إنشاء زيارة/عقد مكرر. المفتاح خاص بكل مستخدم ومسار، ويُحفظ 24 ساعة.
 */
class EnsureIdempotency
{
    private const TTL_SECONDS = 86400;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        if (! $key || ! $request->user()) {
            return $next($request);
        }

        $cacheKey = 'idem:'.$request->user()->id.':'.$request->route()->getName().':'.hash('sha256', $key);

        if ($cached = Cache::get($cacheKey)) {
            return response($cached['body'], $cached['status'], ['Content-Type' => 'application/json', 'Idempotent-Replayed' => 'true']);
        }

        $lock = Cache::lock($cacheKey.':lock', 30);
        if (! $lock->get()) {
            throw BusinessRuleException::make('IDEMPOTENCY_IN_PROGRESS');
        }

        try {
            $response = $next($request);

            if ($response->getStatusCode() < 500) {
                Cache::put($cacheKey, ['status' => $response->getStatusCode(), 'body' => $response->getContent()], self::TTL_SECONDS);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }
}
