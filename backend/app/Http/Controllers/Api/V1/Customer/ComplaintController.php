<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComplaintMessageRequest;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** شكاوى العميل. الملاحظات الداخلية للإدارة لا تُحمّل أبداً هنا. */
class ComplaintController extends Controller
{
    public function __construct(private ComplaintService $complaints) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $page = Complaint::forCustomer($request->user()->customer)
            ->with(['booking', 'contract'])
            ->latest('id')
            ->paginate(20);

        return ComplaintResource::collection($page);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $complaint = $this->complaints->create(
            $request->user()->customer,
            $request->safe()->except('attachments'),
            $request->attachments(),
        );

        return (new ComplaintResource($this->loadForCustomer($complaint)))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id): ComplaintResource
    {
        return new ComplaintResource($this->loadForCustomer($this->find($request, $id)));
    }

    public function message(ComplaintMessageRequest $request, int $id): JsonResponse
    {
        $complaint = $this->find($request, $id);
        $this->complaints->addCustomerMessage($complaint, $request->user()->customer, $request->input('body'), $request->attachments());

        return (new ComplaintResource($this->loadForCustomer($complaint)))->response()->setStatusCode(201);
    }

    private function find(Request $request, int $id): Complaint
    {
        return Complaint::forCustomer($request->user()->customer)->findOrFail($id);
    }

    private function loadForCustomer(Complaint $complaint): Complaint
    {
        return $complaint->load([
            'booking', 'contract',
            'messages' => fn ($q) => $q->visibleToCustomer()->with('attachments'),
        ]);
    }
}
