<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/** الإشعارات ورموز الأجهزة — مشتركة للعميل والعاملة. */
class AccountController extends Controller
{
    public function notifications(Request $request): AnonymousResourceCollection
    {
        $page = $request->user()->notifications()->paginate(20);

        return NotificationResource::collection($page)->additional([
            'meta' => ['unread' => $request->user()->unreadNotifications()->count()],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['message' => __('api.marked_read')]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => __('api.marked_read')]);
    }

    /** تسجيل رمز FCM للجهاز (يُنقل للمستخدم الحالي إن كان مسجلاً لغيره). */
    public function storeDeviceToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::in(['android', 'ios'])],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform'], 'last_used_at' => now()],
        );

        return response()->json(['message' => __('api.marked_read')], 201);
    }

    public function destroyDeviceToken(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string', 'max:255']]);
        $request->user()->deviceTokens()->where('token', $request->input('token'))->delete();

        return response()->json(['message' => __('api.deleted')]);
    }
}
