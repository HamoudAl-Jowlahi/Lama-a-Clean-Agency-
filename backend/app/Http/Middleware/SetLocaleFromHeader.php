<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accept-Language: ar | en. تُضبط اللغة في كل طلب صراحةً (العربية افتراضية)
 * حتى لا تنتقل لغة طلب سابق إلى طلب لاحق في نفس العملية.
 */
class SetLocaleFromHeader
{
    private const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $default = config('app.locale');
        $locale = $request->headers->has('Accept-Language')
            ? ($request->getPreferredLanguage(self::SUPPORTED) ?? $default)
            : $default;

        app()->setLocale($locale);

        return $next($request);
    }
}
