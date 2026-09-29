<?php

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\Contract;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\ContractService;
use Carbon\CarbonImmutable;

/** العاملة لا تُسند لعملين متعارضين (زيارة/عقد) في نفس الفترة. */
class AvailabilityTest extends ApiTestCase
{
    private function expectCode(string $code, callable $fn): void
    {
        try {
            $fn();
            $this->fail("Expected {$code}");
        } catch (BusinessRuleException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    public function test_worker_on_contract_cannot_take_visits_in_that_period(): void
    {
        [, $customer] = $this->customer();
        $worker = $this->worker();
        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(1), 'end_date' => $this->day(30)]);
        $contracts = app(ContractService::class);
        $contracts->confirm($contract, $this->admin);
        $contracts->assign($contract, $worker, $this->admin);

        $inside = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create(['scheduled_date' => $this->day(5)]);
        $after = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create(['scheduled_date' => $this->day(31)]);

        $this->expectCode('WORKER_BUSY_CONTRACT', fn () => app(BookingService::class)->assign($inside, $worker, $this->admin));
        app(BookingService::class)->assign($after, $worker, $this->admin); // بعد انتهاء العقد: مسموح
        $this->assertSame(BookingStatus::Assigned, $after->fresh()->status);

        // ولا تُحتسب في الطاقة الاستيعابية لأيام العقد
        $slots = app(AvailabilityService::class)->slotsFor(CarbonImmutable::parse($this->day(5)));
        $this->assertFalse(collect($slots)->contains('available', true));
    }

    public function test_worker_with_visit_in_period_cannot_take_contract(): void
    {
        [, $customer] = $this->customer();
        $worker = $this->worker();
        $visit = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create(['scheduled_date' => $this->day(3)]);
        app(BookingService::class)->assign($visit, $worker, $this->admin);

        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(1), 'end_date' => $this->day(30)]);
        app(ContractService::class)->confirm($contract, $this->admin);

        $this->expectCode('WORKER_BUSY_BOOKING', fn () => app(ContractService::class)->assign($contract, $worker, $this->admin));
    }

    public function test_overlapping_visits_for_same_worker(): void
    {
        [, $customer] = $this->customer();
        $worker = $this->worker();
        $service = app(BookingService::class);
        $make = fn (string $time) => Booking::factory()->for($customer)->status(BookingStatus::Confirmed)
            ->create(['scheduled_date' => $this->day(2), 'scheduled_time' => $time]);

        $service->assign($make('10:00'), $worker, $this->admin);
        $this->expectCode('WORKER_BUSY_BOOKING', fn () => $service->assign($make('11:00'), $worker, $this->admin));
        $service->assign($make('12:00'), $worker, $this->admin); // بعد فترة كاملة (120 دقيقة)
        $this->assertSame(2, $worker->bookingAssignments()->count());
    }

    public function test_inactive_worker_cannot_be_assigned(): void
    {
        [, $customer] = $this->customer();
        $worker = $this->worker();
        $worker->update(['status' => 'on_leave']);
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create();

        $this->expectCode('WORKER_INACTIVE', fn () => app(BookingService::class)->assign($booking, $worker, $this->admin));
    }

    public function test_state_machine_blocks_illegal_admin_shortcuts(): void
    {
        [, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create(); // pending

        // لا إسناد قبل التأكيد
        $this->expectCode('INVALID_TRANSITION', fn () => app(BookingService::class)->assign($booking, $this->worker(), $this->admin));
        // ولا إكمال لطلب لم يبدأ
        $this->expectCode('INVALID_TRANSITION', fn () => app(BookingService::class)->completeByAdmin($booking, $this->admin));
        $this->assertSame(0, $booking->assignments()->count()); // لم يُنشأ إسناد (الـ transaction تراجعت)
    }
}
