<?php

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\Contract;
use App\Models\ServicePrice;
use App\Models\Team;
use App\Models\Worker;
use App\Services\BookingService;
use App\Services\ContractService;
use App\Services\TeamService;

/**
 * CR-3: الزيارات لفرق ثابتة يقودها قائد، والخادمات للعقود فقط.
 */
class TeamRulesTest extends ApiTestCase
{
    private function assignedBooking(Team $team): Booking
    {
        [, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create();
        app(BookingService::class)->assign($booking, $team, $this->admin);

        return $booking;
    }

    public function test_team_member_sees_the_visit_but_only_the_leader_acts(): void
    {
        $team = $this->team();
        $member = $this->member($team);
        $booking = $this->assignedBooking($team);

        // العضو يرى التفاصيل، بدون هاتف العميل أو المبلغ أو أزرار
        $this->actingAsWorker($member)->getJson("/api/v1/worker/bookings/{$booking->id}")->assertOk()
            ->assertJsonPath('data.team.name', $team->name)
            ->assertJsonPath('data.is_leader', false)
            ->assertJsonPath('data.amount_to_collect', null)
            ->assertJsonPath('data.next_statuses', []);

        foreach (['accept' => [], 'reject' => ['reason' => 'x'], 'status' => ['status' => 'on_the_way']] as $action => $body) {
            $this->postJson("/api/v1/worker/bookings/{$booking->id}/{$action}", $body)
                ->assertStatus(403)->assertJsonPath('code', 'TEAM_LEADER_ONLY');
        }

        // القائد يقبل ويبدأ ويرى هاتف العميل أثناء التنفيذ
        $this->actingAsWorker($team->leader)->postJson("/api/v1/worker/bookings/{$booking->id}/accept")->assertOk()
            ->assertJsonPath('data.is_leader', true);
        $this->postJson("/api/v1/worker/bookings/{$booking->id}/status", ['status' => 'on_the_way'])->assertOk()
            ->assertJsonPath('data.customer.phone', fn ($phone) => $phone !== null)
            ->assertJsonPath('data.amount_to_collect', '250.00');

        // والعضو لا يرى الهاتف حتى أثناء التنفيذ
        $this->actingAsWorker($member)->getJson("/api/v1/worker/bookings/{$booking->id}")
            ->assertJsonPath('data.customer.phone', null);
    }

    public function test_other_teams_cannot_see_the_visit(): void
    {
        $booking = $this->assignedBooking($this->team());
        $otherLeader = $this->team()->leader;

        $this->actingAsWorker($otherLeader)->getJson("/api/v1/worker/bookings/{$booking->id}")->assertStatus(404);
        $this->postJson("/api/v1/worker/bookings/{$booking->id}/accept")->assertStatus(404);
    }

    public function test_staff_types_are_separated(): void
    {
        $housekeeper = $this->worker();
        $cleaner = $this->team()->leader;

        // الخادمة لا ترى الزيارات، وعضو الفريق لا يرى العقود
        $this->actingAsWorker($housekeeper)->getJson('/api/v1/worker/bookings')->assertStatus(403);
        $this->actingAsWorker($cleaner)->getJson('/api/v1/worker/contracts')->assertStatus(403);

        // وملف المستخدم يخبر التطبيق بالواجهة المناسبة
        $this->actingAsWorker($cleaner)->getJson('/api/v1/auth/me')->assertOk()
            ->assertJsonPath('data.worker.type.value', 'cleaner')
            ->assertJsonPath('data.worker.team.is_leader', true);
        $this->actingAsWorker($housekeeper)->getJson('/api/v1/auth/me')->assertOk()
            ->assertJsonPath('data.worker.type.value', 'housekeeper')
            ->assertJsonPath('data.worker.team', null);

        // عضو فريق الزيارات لا يُسند لعقد
        [, $customer] = $this->customer();
        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(2), 'end_date' => $this->day(30)]);
        app(ContractService::class)->confirm($contract, $this->admin);
        try {
            app(ContractService::class)->assign($contract, $cleaner, $this->admin);
            $this->fail('Expected WORKER_TYPE_MISMATCH');
        } catch (BusinessRuleException $e) {
            $this->assertSame('WORKER_TYPE_MISMATCH', $e->errorCode);
        }
    }

    public function test_team_service_validates_membership(): void
    {
        $service = app(TeamService::class);
        $cleaners = Worker::factory()->cleaner()->count(2)->create();
        $housekeeper = $this->worker();

        $expect = function (string $code, callable $fn) {
            try {
                $fn();
                $this->fail("Expected {$code}");
            } catch (BusinessRuleException $e) {
                $this->assertSame($code, $e->errorCode);
            }
        };

        // القائد يجب أن يكون عضواً
        $expect('TEAM_LEADER_NOT_MEMBER', fn () => $service->create('فريق س', [$cleaners[0]->id], $cleaners[1]->id, $this->admin));
        // الخادمة لا تدخل فريق زيارات
        $expect('WORKER_TYPE_MISMATCH', fn () => $service->create('فريق س', [$cleaners[0]->id, $housekeeper->id], $cleaners[0]->id, $this->admin));

        $team = $service->create('فريق س', $cleaners->modelKeys(), $cleaners[0]->id, $this->admin);
        $this->assertSame($cleaners[0]->id, $team->leader_id);
        $this->assertCount(2, $team->members);

        // العضو في فريق واحد فقط
        $expect('WORKER_IN_OTHER_TEAM', fn () => $service->create('فريق ص', [$cleaners[1]->id], $cleaners[1]->id, $this->admin));
    }

    public function test_capacity_equals_available_teams(): void
    {
        [$user, , $address] = $this->customer();
        $this->team();
        $inactive = $this->team();
        $inactive->update(['is_active' => false]);

        $payload = ['service_price_id' => ServicePrice::firstOrFail()->id, 'address_id' => $address->id, 'scheduled_date' => $this->day(1), 'scheduled_time' => '10:00'];

        // فريق مفعّل واحد فقط → زيارة واحدة في نفس الفترة
        $this->actingAsUser($user)->postJson('/api/v1/bookings', $payload)->assertCreated();
        $this->postJson('/api/v1/bookings', $payload)->assertStatus(409)->assertJsonPath('code', 'SLOT_UNAVAILABLE');
    }

    public function test_same_team_cannot_take_overlapping_visits(): void
    {
        $team = $this->team();
        [, $customer] = $this->customer();
        $make = fn (string $time) => Booking::factory()->for($customer)->status(BookingStatus::Confirmed)
            ->create(['scheduled_date' => $this->day(2), 'scheduled_time' => $time]);
        $service = app(BookingService::class);

        $service->assign($make('10:00'), $team, $this->admin);
        try {
            $service->assign($make('11:00'), $team, $this->admin);
            $this->fail('Expected TEAM_BUSY');
        } catch (BusinessRuleException $e) {
            $this->assertSame('TEAM_BUSY', $e->errorCode);
        }
        $service->assign($make('12:00'), $team, $this->admin); // بعد فترة كاملة
        $this->assertSame(2, $team->bookingAssignments()->count());
    }
}
