<?php

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\Contract;
use App\Services\BookingService;
use App\Services\ContractService;

/**
 * التوفر: الخادمة لا تُسند لعقدين متداخلين، والفريق غير المتاح لا يُسند.
 * (قواعد الفرق نفسها في TeamRulesTest)
 */
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

    public function test_housekeeper_cannot_take_overlapping_contracts(): void
    {
        [, $customer] = $this->customer();
        $housekeeper = $this->worker();
        $contracts = app(ContractService::class);

        $first = Contract::factory()->for($customer)->create(['start_date' => $this->day(1), 'end_date' => $this->day(30)]);
        $contracts->confirm($first, $this->admin);
        $contracts->assign($first, $housekeeper, $this->admin);

        $overlapping = Contract::factory()->for($customer)->create(['start_date' => $this->day(20), 'end_date' => $this->day(50)]);
        $after = Contract::factory()->for($customer)->create(['start_date' => $this->day(31), 'end_date' => $this->day(60)]);
        $contracts->confirm($overlapping, $this->admin);
        $contracts->confirm($after, $this->admin);

        $this->expectCode('WORKER_BUSY_CONTRACT', fn () => $contracts->assign($overlapping, $housekeeper, $this->admin));
        $contracts->assign($after, $housekeeper, $this->admin); // يبدأ بعد انتهاء الأول: مسموح
        $this->assertSame(2, $housekeeper->contractAssignments()->count());
    }

    public function test_inactive_staff_or_team_cannot_be_assigned(): void
    {
        [, $customer] = $this->customer();

        $housekeeper = $this->worker();
        $housekeeper->update(['status' => 'on_leave']);
        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(2), 'end_date' => $this->day(30)]);
        app(ContractService::class)->confirm($contract, $this->admin);
        $this->expectCode('WORKER_INACTIVE', fn () => app(ContractService::class)->assign($contract, $housekeeper, $this->admin));

        $team = $this->team();
        $team->update(['is_active' => false]);
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create();
        $this->expectCode('TEAM_UNAVAILABLE', fn () => app(BookingService::class)->assign($booking, $team, $this->admin));
    }

    public function test_state_machine_blocks_illegal_admin_shortcuts(): void
    {
        [, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create(); // pending

        // لا إسناد قبل التأكيد
        $this->expectCode('INVALID_TRANSITION', fn () => app(BookingService::class)->assign($booking, $this->team(), $this->admin));
        // ولا إكمال لطلب لم يبدأ
        $this->expectCode('INVALID_TRANSITION', fn () => app(BookingService::class)->completeByAdmin($booking, $this->admin));
        $this->assertSame(0, $booking->assignments()->count()); // لم يُنشأ إسناد (الـ transaction تراجعت)
    }
}
