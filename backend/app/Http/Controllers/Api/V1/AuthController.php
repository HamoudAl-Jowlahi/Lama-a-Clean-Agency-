<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /** تسجيل عميل جديد. حسابات العاملات تُنشأ من لوحة الإدارة فقط. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = new User($request->safe()->only(['name', 'phone', 'email', 'password']));
            $user->role = UserRole::Customer; // الدور يحدده الخادم
            $user->save();
            $user->customer()->create();

            return $user;
        });

        return $this->tokenResponse($user, $request->input('device_name'), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->input('phone'))->first();

        // نفس الرسالة لرقم غير موجود أو كلمة مرور خاطئة (لا نكشف وجود الحساب)
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw BusinessRuleException::make('INVALID_CREDENTIALS', status: 401);
        }
        if (! $user->isActive()) {
            throw BusinessRuleException::make('ACCOUNT_SUSPENDED', status: 403);
        }

        return $this->tokenResponse($user, $request->input('device_name'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('api.logged_out')]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load(['customer', 'worker.teams']));
    }

    public function updateProfile(Request $request): UserResource
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => ['sometimes', 'required', Rule::in(['ar', 'en'])],
        ]);
        $user->update($data);

        return new UserResource($user->load(['customer', 'worker.teams']));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password:sanctum'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'different:current_password'],
        ]);

        $user = $request->user();
        $user->update(['password' => $request->input('password')]);
        // إنهاء الجلسات على الأجهزة الأخرى
        $user->tokens()->whereKeyNot($user->currentAccessToken()->id)->delete();

        return response()->json(['message' => __('api.password_changed')]);
    }

    private function tokenResponse(User $user, ?string $device, int $status = 200): JsonResponse
    {
        $minutes = config('sanctum.expiration');
        $token = $user->createToken($device ?: 'mobile', [$user->role->value], $minutes ? now()->addMinutes($minutes) : null);

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at?->toAtomString(),
                'user' => new UserResource($user->load(['customer', 'worker.teams'])),
            ],
        ], $status);
    }
}
