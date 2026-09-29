<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureWorkerType;
use App\Http\Middleware\SetLocaleFromHeader;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'worker.type' => EnsureWorkerType::class,
        ]);
        $middleware->api(prepend: [SetLocaleFromHeader::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // كل أخطاء الـ API بشكل واحد: { message, code, errors? }
        $json = fn (string $code, int $status, ?string $message = null, array $extra = []) => response()->json(
            ['message' => $message ?? __('api.errors.'.$code), 'code' => $code] + $extra,
            $status,
        );
        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (BusinessRuleException $e, Request $request) use ($json) {
            return $json($e->errorCode, $e->status, $e->userMessage());
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($json, $isApi) {
            if ($isApi($request)) {
                return $json('VALIDATION_FAILED', 422, $e->validator->errors()->first(), ['errors' => $e->errors()]);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($json, $isApi) {
            if ($isApi($request)) {
                return $json('UNAUTHENTICATED', 401);
            }
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($json, $isApi) {
            if ($isApi($request)) {
                return $json('FORBIDDEN', 403);
            }
        });

        // "غير موجود" و"لا يخصك" بنفس الرد — لا نكشف وجود مورد لغير مالكه
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($json, $isApi) {
            if ($isApi($request)) {
                return $json('NOT_FOUND', 404);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($json, $isApi) {
            if ($isApi($request)) {
                return $json('TOO_MANY_REQUESTS', 429);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($json, $isApi) {
            if ($isApi($request)) {
                return $json('HTTP_ERROR', $e->getStatusCode(), $e->getMessage() ?: null);
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($json, $isApi) {
            if ($isApi($request) && ! config('app.debug')) {
                return $json('SERVER_ERROR', 500);
            }
        });
    })->create();
