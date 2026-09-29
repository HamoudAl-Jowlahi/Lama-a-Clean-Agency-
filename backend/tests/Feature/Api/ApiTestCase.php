<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Address;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\User;
use App\Models\Worker;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * أساس اختبارات الـ API: الوقت مثبت على الأحد 4 أكتوبر 2026 الساعة 9 صباحاً بتوقيت الوكالة.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // بيئة الاختبار ترسل Accept-Language: en افتراضياً؛ التطبيق يرسل لغة الجهاز (العربية)
        $this->withHeader('Accept-Language', 'ar');
        $this->travelTo(Carbon::parse('2026-10-04 09:00', config('agency.timezone')));
        $this->seed(CatalogSeeder::class);
        $this->admin = AdminUser::factory()->create();
    }

    /** @return array{0: User, 1: Customer, 2: Address} */
    protected function customer(): array
    {
        $customer = Customer::factory()->withAddress()->create();
        $customer->load('user', 'defaultAddress');

        return [$customer->user, $customer, $customer->defaultAddress];
    }

    protected function worker(): Worker
    {
        return Worker::factory()->create()->load('user');
    }

    protected function actingAsUser(User $user): static
    {
        Sanctum::actingAs($user, [$user->role->value]);

        return $this;
    }

    protected function actingAsWorker(Worker $worker): static
    {
        return $this->actingAsUser($worker->user);
    }

    protected function assertRole(User $user, UserRole $role): void
    {
        $this->assertSame($role, $user->fresh()->role);
    }

    /** يوم في المستقبل بتنسيق Y-m-d. */
    protected function day(int $offset): string
    {
        return Carbon::now(config('agency.timezone'))->addDays($offset)->toDateString();
    }
}
