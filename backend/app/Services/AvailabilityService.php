<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\BookingStatus;
use App\Enums\ContractStatus;
use App\Enums\WorkerStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\ContractAssignment;
use App\Models\Worker;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * مصدر واحد لتوفر العاملات — يستخدمه إسناد الزيارات والعقود والاستبدال.
 * الأوقات (scheduled_date/time) بتوقيت الوكالة (agency.timezone).
 */
class AvailabilityService
{
    /** زيارات غير منتهية تحجز وقتاً في الجدول (لحساب الطاقة الاستيعابية). */
    private const SCHEDULED_STATUSES = [
        BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Assigned,
        BookingStatus::OnTheWay, BookingStatus::InProgress,
    ];

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now(Settings::get('timezone'));
    }

    // ------------------------------------------------------------ visits

    public function ensureWorkerCanTakeBooking(Worker $worker, Booking $booking): void
    {
        $this->ensureWorkerActive($worker);
        $date = CarbonImmutable::parse($booking->scheduled_date);

        if ($this->hasContractOn($worker, $date, $date)) {
            throw BusinessRuleException::make('WORKER_BUSY_CONTRACT');
        }

        $slot = (int) Settings::get('slot_minutes');
        $time = $this->minutes($booking->scheduled_time);

        $clash = Booking::query()
            ->whereKeyNot($booking->id)
            ->whereDate('scheduled_date', $date->toDateString())
            ->whereIn('status', BookingStatus::occupyingWorker())
            ->whereHas('assignments', fn ($q) => $q->where('worker_id', $worker->id)
                ->whereIn('status', [AssignmentStatus::Pending, AssignmentStatus::Accepted]))
            ->pluck('scheduled_time')
            ->contains(fn ($t) => abs($this->minutes($t) - $time) < $slot);

        if ($clash) {
            throw BusinessRuleException::make('WORKER_BUSY_BOOKING');
        }
    }

    // --------------------------------------------------------- contracts

    public function ensureWorkerCanTakeContract(Worker $worker, CarbonInterface $from, CarbonInterface $to, ?int $exceptContractId = null): void
    {
        $this->ensureWorkerActive($worker);

        if ($this->hasContractOn($worker, $from, $to, $exceptContractId)) {
            throw BusinessRuleException::make('WORKER_BUSY_CONTRACT');
        }

        $hasVisits = Booking::query()
            ->whereBetween('scheduled_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', BookingStatus::occupyingWorker())
            ->whereHas('assignments', fn ($q) => $q->where('worker_id', $worker->id)
                ->whereIn('status', [AssignmentStatus::Pending, AssignmentStatus::Accepted]))
            ->exists();

        if ($hasVisits) {
            throw BusinessRuleException::make('WORKER_BUSY_BOOKING');
        }
    }

    /** هل لدى العاملة إسناد عقد (قائم أو قادم) يتقاطع مع الفترة؟ */
    public function hasContractOn(Worker $worker, CarbonInterface $from, CarbonInterface $to, ?int $exceptContractId = null): bool
    {
        return ContractAssignment::query()
            ->where('worker_id', $worker->id)
            ->when($exceptContractId, fn ($q) => $q->where('contract_id', '!=', $exceptContractId))
            ->whereDate('started_on', '<=', $to->toDateString())
            ->where(fn ($q) => $q->whereNull('ended_on')->orWhereDate('ended_on', '>=', $from->toDateString()))
            ->whereHas('contract', fn ($q) => $q
                ->whereIn('status', ContractStatus::occupyingWorker())
                ->whereDate('end_date', '>=', $from->toDateString()))
            ->exists();
    }

    // ------------------------------------------------------------- slots

    /**
     * الأوقات المتاحة لحجز زيارة في يوم معين.
     *
     * @return list<array{time: string, available: bool}>
     */
    public function slotsFor(CarbonInterface $date): array
    {
        $tz = Settings::get('timezone');
        $day = CarbonImmutable::parse($date->toDateString(), $tz);
        $hours = Settings::get('working_hours');
        $step = (int) Settings::get('slot_minutes');
        $earliest = $this->now()->addHours((int) Settings::get('min_booking_lead_hours'));

        $freeWorkers = Worker::query()
            ->where('status', WorkerStatus::Active)
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->get()
            ->reject(fn (Worker $w) => $this->hasContractOn($w, $day, $day))
            ->count();

        $taken = Booking::query()
            ->whereDate('scheduled_date', $day->toDateString())
            ->whereIn('status', self::SCHEDULED_STATUSES)
            ->pluck('scheduled_time')
            ->map(fn ($t) => substr((string) $t, 0, 5))
            ->countBy();

        $slots = [];
        for ($t = $day->setTimeFromTimeString($hours['start']); $t->lt($day->setTimeFromTimeString($hours['end'])); $t = $t->addMinutes($step)) {
            $time = $t->format('H:i');
            $slots[] = [
                'time' => $time,
                'available' => $t->gte($earliest) && ($taken[$time] ?? 0) < $freeWorkers,
            ];
        }

        return $slots;
    }

    public function ensureSlotAvailable(CarbonInterface $date, string $time): void
    {
        $slot = collect($this->slotsFor($date))->firstWhere('time', substr($time, 0, 5));

        if (! $slot) {
            throw BusinessRuleException::make('SLOT_INVALID', status: 422);
        }
        if (! $slot['available']) {
            throw BusinessRuleException::make('SLOT_UNAVAILABLE');
        }
    }

    // ----------------------------------------------------------- helpers

    private function ensureWorkerActive(Worker $worker): void
    {
        $worker->loadMissing('user');

        if ($worker->status !== WorkerStatus::Active || ! $worker->user->isActive()) {
            throw BusinessRuleException::make('WORKER_INACTIVE');
        }
    }

    private function minutes(mixed $time): int
    {
        [$h, $m] = array_map('intval', explode(':', substr((string) $time, 0, 5)));

        return $h * 60 + $m;
    }
}
