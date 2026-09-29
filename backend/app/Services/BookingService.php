<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\AssignmentStatus;
use App\Enums\BookingStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\BookingAssignment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\ServicePrice;
use App\Models\Team;
use App\Models\Worker;
use App\StateMachines\BookingStateMachine;
use App\Support\AuditLogger;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * كل عمليات الزيارة (العميل، العاملة، الإدارة). الـ Controllers ولوحة الإدارة
 * يستدعون هذه الدوال فقط — لا منطق أعمال في الواجهات.
 */
class BookingService
{
    /** ترتيب مسار الزيارة (لسياسة الإلغاء "حتى حالة معينة"). */
    private const FLOW = ['pending', 'confirmed', 'assigned', 'on_the_way', 'in_progress', 'completed'];

    public function __construct(
        private BookingStateMachine $machine,
        private AvailabilityService $availability,
        private PricingService $pricing,
    ) {}

    // ============================================================ customer

    /**
     * @param  array{service_price_id: int, quantity?: int, address_id: int, scheduled_date: string, scheduled_time: string, customer_notes?: ?string}  $data
     */
    public function create(Customer $customer, array $data): Booking
    {
        $price = ServicePrice::active()->with('service')->findOrFail($data['service_price_id']);
        if (! $price->service->is_active) {
            throw BusinessRuleException::make('SERVICE_UNAVAILABLE', status: 422);
        }

        $address = $customer->addresses()->findOrFail($data['address_id']);
        $date = CarbonImmutable::parse($data['scheduled_date']);
        $time = substr($data['scheduled_time'], 0, 5);
        $this->availability->ensureSlotAvailable($date, $time);

        $quote = $this->pricing->quoteBooking($price, (int) ($data['quantity'] ?? 1));

        return DB::transaction(function () use ($customer, $data, $price, $address, $date, $time, $quote) {
            $booking = Booking::create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'address_snapshot' => $address->toSnapshot(),
                'status' => BookingStatus::Pending,
                'scheduled_date' => $date->toDateString(),
                'scheduled_time' => $time,
                'subtotal' => $quote['subtotal'],
                'tax' => $quote['tax'],
                'total' => $quote['total'],
                'currency' => $quote['currency'],
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $booking->items()->create([
                'service_id' => $price->service_id,
                'service_price_id' => $price->id,
                'service_name_snapshot' => $price->service->name_ar,
                'price_label_snapshot' => $price->label_ar,
                'quantity' => $quote['quantity'],
                'unit_price' => $quote['unit_price'],
                'line_total' => $quote['subtotal'],
            ]);

            $booking->statusLogs()->create([
                'to_status' => BookingStatus::Pending->value,
                'actor_type' => ActorType::Customer,
                'actor_id' => $customer->user_id,
            ]);

            return $booking;
        });
    }

    public function cancelByCustomer(Booking $booking, Customer $customer, ?string $reason = null): Booking
    {
        $until = array_search(Settings::get('booking.customer_cancel_until_status'), self::FLOW, true);
        $current = array_search($booking->status->value, self::FLOW, true);
        if ($current === false || $current > $until) {
            throw BusinessRuleException::make('CANCEL_NOT_ALLOWED');
        }

        $startsAt = CarbonImmutable::parse(
            $booking->scheduled_date->toDateString().' '.$booking->scheduled_time,
            Settings::get('timezone'),
        );
        $hours = (int) Settings::get('booking.customer_cancel_hours_before');
        if ($this->availability->now()->addHours($hours)->gt($startsAt)) {
            throw BusinessRuleException::make('CANCEL_TOO_LATE', ['hours' => $hours]);
        }

        return $this->cancel($booking, ActorType::Customer, $customer->user_id, $reason);
    }

    // =============================================================== admin

    public function confirm(Booking $booking, AdminUser $admin): Booking
    {
        $this->machine->transition($booking, BookingStatus::Confirmed, ActorType::Admin, $admin->id, attributes: ['confirmed_at' => now()]);
        AuditLogger::log($admin, 'booking.confirmed', $booking);

        return $booking;
    }

    public function reject(Booking $booking, AdminUser $admin, string $reason): Booking
    {
        $this->machine->transition($booking, BookingStatus::Rejected, ActorType::Admin, $admin->id, $reason, ['cancel_reason' => $reason]);
        AuditLogger::log($admin, 'booking.rejected', $booking, new: ['reason' => $reason]);

        return $booking;
    }

    /** CR-3: الزيارة تُسند لفريق كامل، وقائده يقبل أو يرفض. */
    public function assign(Booking $booking, Team $team, AdminUser $admin): BookingAssignment
    {
        return DB::transaction(function () use ($booking, $team, $admin) {
            // قفل صف الفريق يمنع إسنادين متزامنين لنفس الوقت
            $team = Team::lockForUpdate()->findOrFail($team->id);
            $this->availability->ensureTeamCanTakeBooking($team, $booking);

            $this->machine->transition($booking, BookingStatus::Assigned, ActorType::Admin, $admin->id);

            $autoAccept = ! Settings::get('booking.worker_can_reject_assignment');
            $assignment = $booking->assignments()->create([
                'team_id' => $team->id,
                'assigned_by' => $admin->id,
                'status' => $autoAccept ? AssignmentStatus::Accepted : AssignmentStatus::Pending,
                'responded_at' => $autoAccept ? now() : null,
            ]);

            AuditLogger::log($admin, 'booking.assigned', $booking, new: ['team_id' => $team->id]);

            return $assignment;
        });
    }

    public function withdrawAssignment(Booking $booking, AdminUser $admin, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $admin, $reason) {
            $this->machine->transition($booking, BookingStatus::Confirmed, ActorType::Admin, $admin->id, $reason);
            $this->activeAssignment($booking)?->update(['status' => AssignmentStatus::Withdrawn]);
            AuditLogger::log($admin, 'booking.assignment_withdrawn', $booking, new: ['reason' => $reason]);

            return $booking;
        });
    }

    public function cancelByAdmin(Booking $booking, AdminUser $admin, string $reason): Booking
    {
        $this->cancel($booking, ActorType::Admin, $admin->id, $reason);
        AuditLogger::log($admin, 'booking.cancelled', $booking, new: ['reason' => $reason]);

        return $booking;
    }

    public function completeByAdmin(Booking $booking, AdminUser $admin): Booking
    {
        $this->complete($booking, ActorType::Admin, $admin->id);
        AuditLogger::log($admin, 'booking.completed', $booking);

        return $booking;
    }

    // ================================================== team leader (CR-3)

    public function accept(Booking $booking, Worker $leader): BookingAssignment
    {
        $assignment = $this->leaderAssignment($booking, $leader);
        if ($assignment->status !== AssignmentStatus::Pending) {
            throw BusinessRuleException::make('ASSIGNMENT_ALREADY_ANSWERED');
        }

        $assignment->update(['status' => AssignmentStatus::Accepted, 'responded_at' => now(), 'responded_by' => $leader->id]);

        return $assignment;
    }

    public function rejectAssignment(Booking $booking, Worker $leader, string $reason): Booking
    {
        if (! Settings::get('booking.worker_can_reject_assignment')) {
            throw BusinessRuleException::make('ASSIGNMENT_REJECT_NOT_ALLOWED', status: 403);
        }

        $assignment = $this->leaderAssignment($booking, $leader);
        if ($assignment->status !== AssignmentStatus::Pending) {
            throw BusinessRuleException::make('ASSIGNMENT_ALREADY_ANSWERED');
        }

        return DB::transaction(function () use ($booking, $leader, $assignment, $reason) {
            $assignment->update([
                'status' => AssignmentStatus::Rejected,
                'responded_at' => now(),
                'responded_by' => $leader->id,
                'rejection_reason' => $reason,
            ]);
            $this->machine->transition($booking, BookingStatus::Confirmed, ActorType::Worker, $leader->user_id, $reason);

            return $booking;
        });
    }

    /** قائد الفريق يتقدم خطوة: on_the_way ← in_progress ← completed (بعد القبول الصريح فقط). */
    public function advance(Booking $booking, Worker $leader, BookingStatus $to): Booking
    {
        $assignment = $this->leaderAssignment($booking, $leader);

        if ($assignment->status !== AssignmentStatus::Accepted) {
            throw BusinessRuleException::make('ASSIGNMENT_NOT_ACCEPTED');
        }

        if ($to === BookingStatus::Completed) {
            return $this->complete($booking, ActorType::Worker, $leader->user_id);
        }

        return $this->machine->transition($booking, $to, ActorType::Worker, $leader->user_id);
    }

    // ============================================================= private

    private function cancel(Booking $booking, ActorType $actor, int $actorId, ?string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $actorId, $reason) {
            $this->machine->transition($booking, BookingStatus::Cancelled, $actor, $actorId, $reason, [
                'cancel_reason' => $reason,
                'cancelled_by_type' => $actor,
                'cancelled_at' => now(),
            ]);
            $this->activeAssignment($booking)?->update(['status' => AssignmentStatus::Withdrawn]);

            return $booking;
        });
    }

    private function complete(Booking $booking, ActorType $actor, int $actorId): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $actorId) {
            $this->machine->transition($booking, BookingStatus::Completed, $actor, $actorId, attributes: ['completed_at' => now()]);

            // CR-1: الدفع نقداً عند الإتمام → استحقاق تسجل الإدارة تحصيله
            Payment::firstOrCreate(['booking_id' => $booking->id], [
                'amount' => $booking->total,
                'currency' => $booking->currency,
                'due_date' => $this->availability->now()->toDateString(),
            ]);

            return $booking;
        });
    }

    private function activeAssignment(Booking $booking): ?BookingAssignment
    {
        return $booking->assignments()
            ->whereIn('status', [AssignmentStatus::Pending, AssignmentStatus::Accepted])
            ->latest('id')->first();
    }

    /**
     * الإسناد الفعّال بشرط أن يكون $worker قائد الفريق المسند.
     * ليس في الفريق → 404 · عضو غير قائد → 403 TEAM_LEADER_ONLY.
     */
    private function leaderAssignment(Booking $booking, Worker $worker): BookingAssignment
    {
        $assignment = $this->activeAssignment($booking)?->load('team');

        if (! $assignment || ! $assignment->team->members()->whereKey($worker->id)->exists()) {
            throw BusinessRuleException::make('NOT_FOUND', status: 404);
        }
        if (! $assignment->team->isLeader($worker)) {
            throw BusinessRuleException::make('TEAM_LEADER_ONLY', status: 403);
        }

        return $assignment;
    }
}
