<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * role:customer | role:worker — الدور يُقرأ من قاعدة البيانات، لا من التطبيق.
 * يمنع أيضاً الحسابات الموقوفة حتى لو كان لديها token صالح.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::from($role)) {
            throw BusinessRuleException::make('FORBIDDEN', status: 403);
        }

        if (! $user->isActive()) {
            throw BusinessRuleException::make('ACCOUNT_SUSPENDED', status: 403);
        }

        // ملف الدور يُحمّل مرة واحدة لكل الطلب ($request->user()->customer / ->worker)
        $user->loadMissing($role === UserRole::Customer->value ? 'customer' : 'worker');

        return $next($request);
    }
}
