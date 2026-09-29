<?php

namespace App\Http\Controllers\Api\V1\Worker;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\RatingResource;
use App\Http\Resources\WorkerBookingResource;
use App\Http\Resources\WorkerContractResource;
use App\Models\Booking;
use App\Models\Contract;
use App\Models\Rating;
use App\Models\Worker;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * واجهة العاملة: فقط ما هو مسند إليها. طلب غير مسند = 404.
 */
class WorkerController extends Controller
{
    private const BOOKING_WITH = ['items', 'customer.user', 'activeAssignment.team'];

    public function __construct(private BookingService $bookings) {}

    // ------------------------------------------------------------- visits

    /** scope: new (بانتظار قبولها) | today | upcoming | done */
    public function bookings(Request $request, AvailabilityService $availability): AnonymousResourceCollection
    {
        $data = $request->validate(['scope' => ['nullable', Rule::in(['new', 'today', 'upcoming', 'done'])]]);
        $worker = $this->worker($request);
        $today = $availability->now()->toDateString();

        $page = Booking::assignedTo($worker)
            ->with(self::BOOKING_WITH)
            ->when($data['scope'] ?? null, fn ($q, $scope) => match ($scope) {
                'new' => $q->whereHas('assignments', fn ($a) => $a->where('status', 'pending')),
                'today' => $q->whereDate('scheduled_date', $today),
                'upcoming' => $q->whereDate('scheduled_date', '>', $today)->whereNotIn('status', ['completed', 'cancelled']),
                'done' => $q->where('status', BookingStatus::Completed),
            })
            ->orderBy('scheduled_date')->orderBy('scheduled_time')
            ->paginate(20);

        return WorkerBookingResource::collection($page);
    }

    public function booking(Request $request, int $id): WorkerBookingResource
    {
        return new WorkerBookingResource($this->findBooking($request, $id)->load(self::BOOKING_WITH));
    }

    public function accept(Request $request, int $id): WorkerBookingResource
    {
        $booking = $this->findBooking($request, $id);
        $this->bookings->accept($booking, $this->worker($request));

        return new WorkerBookingResource($booking->load(self::BOOKING_WITH));
    }

    public function reject(Request $request, int $id): WorkerBookingResource
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $booking = $this->findBooking($request, $id);
        $this->bookings->rejectAssignment($booking, $this->worker($request), $data['reason']);

        return new WorkerBookingResource($booking->load(self::BOOKING_WITH));
    }

    public function updateStatus(Request $request, int $id): WorkerBookingResource
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([BookingStatus::OnTheWay->value, BookingStatus::InProgress->value, BookingStatus::Completed->value])],
        ]);
        $booking = $this->findBooking($request, $id);
        $this->bookings->advance($booking, $this->worker($request), BookingStatus::from($data['status']));

        return new WorkerBookingResource($booking->load(self::BOOKING_WITH));
    }

    // ---------------------------------------------------------- contracts

    /** العقود التي عملت/تعمل فيها — بيانات العميل تظهر للعقد الحالي فقط. */
    public function contracts(Request $request): JsonResponse
    {
        $worker = $this->worker($request);
        $contracts = Contract::whereHas('assignments', fn ($q) => $q->where('worker_id', $worker->id))
            ->with(['customer.user', 'assignments'])
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $contracts->map(fn (Contract $c) => (new WorkerContractResource($c))->forWorker($worker)->resolve($request)),
        ]);
    }

    public function contract(Request $request, int $id): WorkerContractResource
    {
        $worker = $this->worker($request);
        $contract = Contract::whereHas('assignments', fn ($q) => $q->where('worker_id', $worker->id))
            ->with(['customer.user', 'assignments'])
            ->findOrFail($id);

        return (new WorkerContractResource($contract))->forWorker($worker);
    }

    // ------------------------------------------------------------ ratings

    public function ratings(Request $request): AnonymousResourceCollection
    {
        $worker = $this->worker($request);
        // فريق الزيارات: تقييمات فريقه · الخادمة: تقييماتها في العقود
        $visible = $worker->isCleaner()
            ? Rating::whereIn('team_id', $worker->teams()->pluck('teams.id'))->visible()
            : $worker->ratings()->visible();

        return RatingResource::collection((clone $visible)->latest('id')->paginate(20))->additional([
            'meta' => [
                'average' => round((float) (clone $visible)->avg('worker_score'), 1),
                'count' => (clone $visible)->whereNotNull('worker_score')->count(),
            ],
        ]);
    }

    // ------------------------------------------------------------ helpers

    private function worker(Request $request): Worker
    {
        return $request->user()->worker;
    }

    private function findBooking(Request $request, int $id): Booking
    {
        return Booking::assignedTo($this->worker($request))->findOrFail($id);
    }
}
