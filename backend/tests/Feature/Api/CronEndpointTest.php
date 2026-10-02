<?php

namespace Tests\Feature\Api;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Support\Carbon;

class CronEndpointTest extends ApiTestCase
{
    public function test_disabled_without_configured_token(): void
    {
        config(['agency.cron_token' => null]);

        $this->postJson('/api/v1/internal/cron/daily', [], ['X-Cron-Token' => ''])->assertNotFound();
    }

    public function test_rejects_wrong_token(): void
    {
        config(['agency.cron_token' => 'secret-123']);

        $this->postJson('/api/v1/internal/cron/daily', [], ['X-Cron-Token' => 'nope'])->assertNotFound();
    }

    public function test_runs_daily_job_with_valid_token(): void
    {
        config(['agency.cron_token' => 'secret-123']);
        [, $customer] = $this->customer();
        $contract = Contract::factory()->for($customer)->create(['start_date' => '2026-10-06', 'end_date' => '2026-11-05']);
        $service = app(ContractService::class);
        $service->confirm($contract, $this->admin);
        $service->assign($contract, $this->worker(), $this->admin);

        $this->travelTo(Carbon::parse('2026-10-06 09:00', config('agency.timezone')));
        $this->postJson('/api/v1/internal/cron/daily', [], ['X-Cron-Token' => 'secret-123'])
            ->assertOk()
            ->assertJsonPath('data.activated', 1);

        $this->assertSame(ContractStatus::Active, $contract->fresh()->status);
    }
}
