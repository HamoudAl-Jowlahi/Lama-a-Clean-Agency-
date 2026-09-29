<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\RatingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\RatingResource;
use App\Models\Booking;
use App\Models\ServicePrice;
use App\Services\BookingService;
use App\Services\PricingService;
use App\Services\RatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** زيارات العميل. كل استعلام عبر forCustomer() → طلب غير مملوك = 404. */
class BookingController extends Controller
{
    private const WITH = ['items', 'payment', 'rating', 'statusLogs', 'activeAssignment.worker.user'];

    public function __construct(private BookingService $bookings) {}

    public function quote(Request $request, PricingService $pricing): JsonResponse
    {
        $data = $request->validate([
            'service_price_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'between:1,50'],
        ]);
        $price = ServicePrice::active()->findOrFail($data['service_price_id']);

        return response()->json(['data' => $pricing->quoteBooking($price, (int) ($data['quantity'] ?? 1))]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookings->create($request->user()->customer, $request->validated());

        return (new BookingResource($booking->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', 'string', 'max:20']]);

        $page = Booking::forCustomer($request->user()->customer)
            ->with(['items', 'activeAssignment.worker.user'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(20);

        return BookingResource::collection($page);
    }

    public function show(Request $request, int $id): BookingResource
    {
        return new BookingResource($this->find($request, $id)->load(self::WITH));
    }

    public function cancel(Request $request, int $id): BookingResource
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $booking = $this->bookings->cancelByCustomer($this->find($request, $id), $request->user()->customer, $data['reason'] ?? null);

        return new BookingResource($booking->load(self::WITH));
    }

    public function rate(RatingRequest $request, int $id, RatingService $ratings): JsonResponse
    {
        $rating = $ratings->rateBooking($this->find($request, $id), $request->user()->customer, $request->validated());

        return (new RatingResource($rating))->response()->setStatusCode(201);
    }

    private function find(Request $request, int $id): Booking
    {
        return Booking::forCustomer($request->user()->customer)->findOrFail($id);
    }
}
