<?php

namespace Tests\Feature\Database;

use App\Enums\AssignmentEndReason;
use App\Enums\BookingStatus;
use App\Enums\ChangeRequestStatus;
use App\Enums\ContractStatus;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Contract;
use App\Models\ContractPlan;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_seeders_create_catalog_and_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Service::count());
        $this->assertSame(3, ContractPlan::count());
        $this->assertTrue(AdminUser::where('email', 'admin@lamaa.test')->first()->isSuperAdmin());
        // DemoSeeder لا يعمل تلقائياً خارج local
        $this->assertSame(0, Booking::count());
    }

    public function test_catalog_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Service::count());
        $this->assertSame(3, ContractPlan::count());
        $this->assertSame(1, AdminUser::count());
    }

    public function test_demo_seeder_builds_consistent_scenarios(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $onTheWay = Booking::with(['statusLogs', 'activeAssignment', 'items'])
            ->where('status', BookingStatus::OnTheWay)->firstOrFail();
        $this->assertMatchesRegularExpression('/^BK-\d{4}-\d{6}$/', $onTheWay->booking_number);
        $this->assertSame(
            ['pending', 'confirmed', 'assigned', 'on_the_way'],
            $onTheWay->statusLogs->pluck('to_status')->all(),
        );
        $this->assertNotNull($onTheWay->activeAssignment);
        $this->assertCount(1, $onTheWay->items);

        // عقد سارة: فاطمة استُبدلت بعائشة عبر طلب معتمد
        $sara = Contract::with(['assignments.worker.user', 'currentAssignment.worker.user', 'changeRequests'])
            ->whereHas('customer.user', fn ($q) => $q->where('name', 'سارة أحمد'))
            ->where('status', ContractStatus::Active)->firstOrFail();

        $this->assertSame(['فاطمة', 'عائشة'], $sara->assignments->map(fn ($a) => $a->worker->user->name)->all());
        $this->assertSame(AssignmentEndReason::Replaced, $sara->assignments->first()->end_reason);
        $this->assertSame('عائشة', $sara->currentAssignment->worker->user->name);

        $request = $sara->changeRequests->sole();
        $this->assertSame(ChangeRequestStatus::Approved, $request->status);
        $this->assertSame($sara->currentAssignment->id, $request->resulting_assignment_id);
        $this->assertMatchesRegularExpression('/^RQ-\d{4}-\d{6}$/', $request->request_number);

        // الإتمام يولّد استحقاقاً نقدياً
        $completed = Booking::with('payment')->where('status', BookingStatus::Completed)->firstOrFail();
        $this->assertEquals($completed->total, $completed->payment->amount);
        $this->assertSame('due', $completed->payment->status->value);
    }
}
