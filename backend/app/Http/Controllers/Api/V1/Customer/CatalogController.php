<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractPlanResource;
use App\Http\Resources\ServiceResource;
use App\Models\ContractPlan;
use App\Models\Service;
use App\Services\AvailabilityService;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function services(): AnonymousResourceCollection
    {
        return ServiceResource::collection(Service::active()->with('activePrices')->get());
    }

    public function service(int $id): ServiceResource
    {
        return new ServiceResource(Service::active()->with('activePrices')->findOrFail($id));
    }

    public function plans(): AnonymousResourceCollection
    {
        return ContractPlanResource::collection(ContractPlan::active()->get())->additional([
            'meta' => [
                'terms_version' => Settings::get('contracts.terms_version'),
                'payment_schedule' => Settings::get('contracts.payment_schedule'),
                'min_start_lead_days' => Settings::get('contracts.min_start_lead_days'),
            ],
        ]);
    }

    public function availability(Request $request, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return response()->json(['data' => [
            'date' => $data['date'],
            'slots' => $availability->slotsFor(CarbonImmutable::parse($data['date'])),
        ]]);
    }
}
