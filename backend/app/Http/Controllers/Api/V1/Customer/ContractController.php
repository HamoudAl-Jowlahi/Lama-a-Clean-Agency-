<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\RatingRequest;
use App\Http\Requests\StoreChangeRequestRequest;
use App\Http\Requests\StoreContractRequest;
use App\Http\Resources\ChangeRequestResource;
use App\Http\Resources\ContractResource;
use App\Http\Resources\RatingResource;
use App\Models\Contract;
use App\Models\ContractPlan;
use App\Services\ContractChangeService;
use App\Services\ContractService;
use App\Services\PricingService;
use App\Services\RatingService;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** عقود العميل وطلبات الاستبدال/الإنهاء عليها. */
class ContractController extends Controller
{
    private const WITH = ['currentAssignment.worker.user', 'assignments.worker.user', 'payments', 'changeRequests', 'statusLogs'];

    public function __construct(private ContractService $contracts) {}

    public function quote(Request $request, PricingService $pricing): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'months' => ['required', 'integer', 'between:1,24'],
        ]);
        $plan = ContractPlan::active()->findOrFail($data['plan_id']);
        $start = CarbonImmutable::parse($data['start_date']);

        $this->contracts->validateTerms($plan, $start, (int) $data['months'], Settings::get('contracts.terms_version'));

        return response()->json(['data' => $pricing->quoteContract($plan, $start, (int) $data['months']) + [
            'payment_schedule' => Settings::get('contracts.payment_schedule'),
            'terms_version' => Settings::get('contracts.terms_version'),
        ]]);
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        $contract = $this->contracts->create($request->user()->customer, $request->validated());

        return (new ContractResource($contract->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $page = Contract::forCustomer($request->user()->customer)
            ->with('currentAssignment.worker.user')
            ->latest('id')
            ->paginate(20);

        return ContractResource::collection($page);
    }

    public function show(Request $request, int $id): ContractResource
    {
        return new ContractResource($this->find($request, $id)->load(self::WITH));
    }

    public function cancel(Request $request, int $id): ContractResource
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $contract = $this->contracts->cancelByCustomer($this->find($request, $id), $request->user()->customer, $data['reason'] ?? null);

        return new ContractResource($contract->load(self::WITH));
    }

    public function rate(RatingRequest $request, int $id, RatingService $ratings): JsonResponse
    {
        $rating = $ratings->rateContract($this->find($request, $id), $request->user()->customer, $request->validated());

        return (new RatingResource($rating))->response()->setStatusCode(201);
    }

    // -------------------------------------------- replacement / termination

    public function changeRequests(Request $request, int $id): AnonymousResourceCollection
    {
        return ChangeRequestResource::collection(
            $this->find($request, $id)->changeRequests()->withCount('attachments')->latest('id')->get()
        );
    }

    public function storeChangeRequest(StoreChangeRequestRequest $request, int $id, ContractChangeService $changes): JsonResponse
    {
        $changeRequest = $changes->submit(
            $this->find($request, $id),
            $request->user()->customer,
            $request->safe()->except('attachments'),
            $request->attachments(),
        );

        return (new ChangeRequestResource($changeRequest->loadCount('attachments')))->response()->setStatusCode(201);
    }

    private function find(Request $request, int $id): Contract
    {
        return Contract::forCustomer($request->user()->customer)->findOrFail($id);
    }
}
