<?php

namespace App\Http\Middleware;

use App\Enums\WorkerType;
use App\Exceptions\BusinessRuleException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * worker.type:cleaner | worker.type:housekeeper (CR-3)
 * فرق الزيارات لا ترى العقود، والخادمات لا ترى الزيارات.
 * يُستخدم بعد role:worker.
 */
class EnsureWorkerType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        if ($request->user()->worker?->type !== WorkerType::from($type)) {
            throw BusinessRuleException::make('FORBIDDEN', status: 403);
        }

        return $next($request);
    }
}
