<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminRole;
use App\Filament\Widgets\NeedsActionBookings;
use App\Filament\Widgets\OperationsOverview;
use Livewire\Livewire;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\ContractChangeRequest;
use App\Models\ContractPlan;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Team;
use App\Models\Worker;
use Database\Seeders\DemoSeeder;

/**
 * كل صفحات اللوحة تُفتح بدون أخطاء على بيانات واقعية (DemoSeeder)،
 * وكل دور يرى فقط ما تسمح به صلاحياته.
 */
class AdminPagesTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    public function test_every_page_renders_for_super_admin(): void
    {
        $this->actingAsAdmin(AdminRole::SuperAdmin);

        $urls = [
            '/admin', '/admin/bookings', '/admin/contracts', '/admin/change-requests', '/admin/complaints',
            '/admin/teams', '/admin/teams/create', '/admin/workers', '/admin/workers/create', '/admin/customers',
            '/admin/services', '/admin/services/create', '/admin/contract-plans', '/admin/contract-plans/create',
            '/admin/payments', '/admin/ratings', '/admin/settings', '/admin/admin-users', '/admin/admin-users/create',
            '/admin/audit-logs', '/admin/profile',
            '/admin/bookings/'.Booking::firstOrFail()->id,
            '/admin/contracts/'.Contract::where('status', 'active')->firstOrFail()->id,
            '/admin/change-requests/'.ContractChangeRequest::firstOrFail()->id,
            '/admin/complaints/'.Complaint::firstOrFail()->id,
            '/admin/customers/'.Customer::firstOrFail()->id,
            '/admin/teams/'.Team::firstOrFail()->id.'/edit',
            '/admin/workers/'.Worker::firstOrFail()->id.'/edit',
            '/admin/services/'.Service::firstOrFail()->id.'/edit',
            '/admin/contract-plans/'.ContractPlan::firstOrFail()->id.'/edit',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_dashboard_shows_the_days_numbers(): void
    {
        $this->actingAsAdmin();

        // الـ Widgets تُحمّل بعد فتح الصفحة (lazy) — تُختبر مباشرة
        Livewire::test(OperationsOverview::class)
            ->assertSee('بانتظار المراجعة')
            ->assertSee('عقود سارية')
            ->assertSee('طلبات استبدال / إنهاء');

        Livewire::test(NeedsActionBookings::class)
            ->assertCanSeeTableRecords(Booking::whereIn('status', ['pending', 'confirmed'])->get())
            ->assertCanNotSeeTableRecords(Booking::where('status', 'completed')->get());
    }

    public function test_operations_cannot_reach_settings_or_admin_users(): void
    {
        $this->actingAsAdmin(AdminRole::Operations);

        $this->get('/admin/settings')->assertForbidden();
        $this->get('/admin/admin-users')->assertForbidden();
        $this->get('/admin/teams/create')->assertOk();
        $this->get('/admin/audit-logs')->assertOk();
    }

    public function test_support_is_limited_to_viewing_and_complaints(): void
    {
        $this->actingAsAdmin(AdminRole::Support);

        foreach (['/admin/bookings', '/admin/contracts', '/admin/complaints', '/admin/customers', '/admin/ratings'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/admin/teams/create', '/admin/workers/create', '/admin/services/create', '/admin/settings', '/admin/admin-users', '/admin/audit-logs'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get('/admin/services/'.Service::firstOrFail()->id.'/edit')->assertForbidden();
    }
}
