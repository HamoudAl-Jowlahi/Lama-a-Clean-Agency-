<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * المهمة اليومية عبر HTTP — للاستضافة المجانية التي لا تشغّل cron (Render Free وغيره).
 * تستدعيها خدمة جدولة خارجية (cron-job.org) يومياً بالرمز السري في الترويسة:
 *   POST /api/v1/internal/cron/daily   X-Cron-Token: <CRON_TOKEN>
 * آمنة للتكرار (runDaily لا يكرر التفعيل أو الاستحقاقات).
 */
class CronController extends Controller
{
    public function daily(Request $request, ContractService $contracts): JsonResponse
    {
        $token = (string) config('agency.cron_token');

        // بدون رمز مضبوط في الخادم: المسار معطّل تماماً
        abort_if($token === '' || ! hash_equals($token, (string) $request->header('X-Cron-Token')), 404);

        return response()->json(['data' => $contracts->runDaily()]);
    }
}
