<?php

namespace Tests\Feature\Api;

use App\Enums\AssignmentEndReason;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ContractChangeRequest;
use App\Models\ContractPlan;
use App\Services\ContractChangeService;
use App\Services\ContractService;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * CR-2: استئجار عاملة بعقد + استبدالها أو إنهاء العقد من التطبيق.
 * الوقت يبدأ الأحد 4 أكتوبر 2026، 9 صباحاً.
 */
class ContractFlowTest extends ApiTestCase
{
    private function at(string $datetime): void
    {
        $this->travelTo(Carbon::parse($datetime, config('agency.timezone')));
    }

    private function fullTimePlan(): ContractPlan
    {
        return ContractPlan::where('name_ar', 'دوام كامل')->firstOrFail(); // 1800 شهرياً
    }

    public function test_contract_request_validation(): void
    {
        [$user, , $address] = $this->customer();
        $plan = $this->fullTimePlan();
        $base = [
            'plan_id' => $plan->id, 'address_id' => $address->id, 'months' => 1,
            'start_date' => $this->day(1), 'accept_terms' => true, 'terms_version' => '2026-10',
        ];

        $this->actingAsUser($user)->getJson('/api/v1/contract-plans')
            ->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('meta.terms_version', '2026-10');

        $this->postJson('/api/v1/contracts', ['start_date' => $this->day(0)] + $base)
            ->assertStatus(422)->assertJsonPath('code', 'CONTRACT_START_TOO_SOON');
        // الباقة تسمح بـ 12 شهراً كحد أقصى
        $this->postJson('/api/v1/contracts', ['months' => 13] + $base)
            ->assertStatus(422)->assertJsonPath('code', 'CONTRACT_MONTHS_OUT_OF_RANGE')
            ->assertJsonPath('message', 'مدة العقد يجب أن تكون بين 1 و 12 أشهر.');
        $this->postJson('/api/v1/contracts', ['terms_version' => '2025-01'] + $base)
            ->assertStatus(422)->assertJsonPath('code', 'TERMS_OUTDATED');
        $this->postJson('/api/v1/contracts', ['accept_terms' => false] + $base)
            ->assertStatus(422)->assertJsonPath('errors.accept_terms.0', 'يجب الموافقة على شروط العقد.');

        $this->postJson('/api/v1/contracts/quote', ['plan_id' => $plan->id, 'start_date' => $this->day(1), 'months' => 3])
            ->assertOk()
            ->assertJsonPath('data.start_date', '2026-10-05')
            ->assertJsonPath('data.end_date', '2027-01-04')
            ->assertJsonPath('data.total', '5400.00');
    }

    public function test_contract_with_worker_replacement_and_monthly_cash_due(): void
    {
        [$user, , $address] = $this->customer();
        $fatima = $this->worker();
        $aisha = $this->worker();
        $contracts = app(ContractService::class);

        // 1) العميل يطلب عقد دوام كامل لشهر يبدأ غداً
        $id = $this->actingAsUser($user)->postJson('/api/v1/contracts', [
            'plan_id' => $this->fullTimePlan()->id, 'address_id' => $address->id, 'months' => 1,
            'start_date' => $this->day(1), 'accept_terms' => true, 'terms_version' => '2026-10',
        ])->assertCreated()
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.end_date', '2026-11-04')
            ->assertJsonPath('data.total_amount', '1800.00')
            ->json('data.id');
        $contract = Contract::findOrFail($id);

        // 2) الإدارة تؤكد وتسند فاطمة — العقد لم يبدأ بعد
        $contracts->confirm($contract, $this->admin);
        $contracts->assign($contract, $fatima, $this->admin);
        $this->assertSame(ContractStatus::Assigned, $contract->fresh()->status);

        // 3) المهمة اليومية تفعّل العقد في تاريخ البدء
        $this->at('2026-10-05 00:10');
        $this->artisan('lamaa:contracts-daily')->expectsOutputToContain('activated=1')->assertSuccessful();

        $this->at('2026-10-10 09:00');
        $this->getJson("/api/v1/contracts/{$id}")->assertOk()
            ->assertJsonPath('data.status.value', 'active')
            ->assertJsonPath('data.status.label', 'ساري')
            ->assertJsonPath('data.progress', ['day' => 6, 'total_days' => 31])
            ->assertJsonPath('data.current_worker.id', $fatima->id);

        // فاطمة ترى بيانات العميل لأنها العاملة الحالية
        $this->actingAsWorker($fatima)->getJson("/api/v1/worker/contracts/{$id}")->assertOk()
            ->assertJsonPath('data.is_current', true)
            ->assertJsonPath('data.customer.phone', $user->phone);

        // 4) العميل يطلب "إخراج" العاملة واستبدالها
        $this->actingAsUser($user)->postJson("/api/v1/contracts/{$id}/change-requests", [
            'type' => 'replace_worker', 'reason_type' => 'quality', 'details' => 'المطبخ لا يُنظف جيداً',
        ])->assertCreated()->assertJsonPath('data.status.value', 'open')->assertJsonPath('data.type.label', 'استبدال العاملة');

        $this->postJson("/api/v1/contracts/{$id}/change-requests", ['type' => 'replace_worker', 'reason_type' => 'quality'])
            ->assertStatus(409)->assertJsonPath('code', 'REQUEST_ALREADY_OPEN');

        // 5) الإدارة تعتمد: فاطمة تنتهي اليوم، عائشة تبدأ بعد يومين (11 أكتوبر بلا عاملة)
        $request = ContractChangeRequest::firstOrFail();
        app(ContractChangeService::class)->approveReplacement($request, $this->admin, $aisha, CarbonImmutable::parse('2026-10-12'), 'تم تعيين بديلة');

        $this->getJson("/api/v1/contracts/{$id}")->assertOk()
            ->assertJsonPath('data.status.value', 'active') // العقد لم يتوقف
            ->assertJsonPath('data.current_worker.id', $aisha->id)
            ->assertJsonPath('data.workers_history.0.to', '2026-10-10')
            ->assertJsonPath('data.workers_history.0.end_reason.value', 'replaced')
            ->assertJsonPath('data.workers_history.1.from', '2026-10-12')
            ->assertJsonPath('data.change_requests.0.status.value', 'approved');

        // فاطمة لم تعد ترى عنوان العميل ولا هاتفه
        $this->actingAsWorker($fatima)->getJson("/api/v1/worker/contracts/{$id}")->assertOk()
            ->assertJsonPath('data.is_current', false)
            ->assertJsonPath('data.address', null)
            ->assertJsonPath('data.customer.phone', null);

        // 6) بعد نهاية العقد: يكتمل تلقائياً ويُنشأ استحقاق نقدي بخصم يوم الانتظار
        $this->at('2026-11-05 00:10');
        $this->artisan('lamaa:contracts-daily')->expectsOutputToContain('completed=1 payments=1')->assertSuccessful();

        $contract->refresh();
        $this->assertSame(ContractStatus::Completed, $contract->status);
        $this->assertSame(AssignmentEndReason::ContractCompleted, $contract->assignments()->where('worker_id', $aisha->id)->firstOrFail()->end_reason);
        // 31 يوماً، منها 30 بعاملة: 1800 × 30/31
        $this->assertSame('1741.94', $contract->payments()->firstOrFail()->amount);

        // المهمة اليومية آمنة للتكرار
        $this->artisan('lamaa:contracts-daily')->expectsOutputToContain('payments=0')->assertSuccessful();

        // 7) تقييم كل عاملة عملت في العقد
        $this->actingAsUser($user)->postJson("/api/v1/contracts/{$id}/rating", ['worker_id' => $aisha->id, 'service_score' => 5, 'worker_score' => 5])
            ->assertCreated();
        $this->postJson("/api/v1/contracts/{$id}/rating", ['worker_id' => $aisha->id, 'service_score' => 4])
            ->assertStatus(409)->assertJsonPath('code', 'ALREADY_RATED');
        $this->postJson("/api/v1/contracts/{$id}/rating", ['worker_id' => 99999, 'service_score' => 4])
            ->assertStatus(404);
    }

    public function test_early_termination_on_customer_request(): void
    {
        [$user, $customer] = $this->customer();
        $worker = $this->worker();
        $contracts = app(ContractService::class);

        $contract = Contract::factory()->for($customer)->create([
            'plan_id' => $this->fullTimePlan()->id,
            'start_date' => '2026-10-05', 'end_date' => '2027-01-04', 'months' => 3,
            'monthly_price' => 1800, 'total_amount' => 5400,
        ]);
        $contracts->confirm($contract, $this->admin);
        $contracts->assign($contract, $worker, $this->admin);
        $this->at('2026-10-05 00:10');
        $contracts->runDaily();

        $this->at('2026-10-20 09:00');
        $this->actingAsUser($user)->postJson("/api/v1/contracts/{$contract->id}/change-requests", [
            'type' => 'terminate', 'reason_type' => 'no_longer_needed', 'requested_date' => '2027-02-01',
        ])->assertStatus(422)->assertJsonPath('code', 'TERMINATION_DATE_INVALID');

        $this->postJson("/api/v1/contracts/{$contract->id}/change-requests", [
            'type' => 'terminate', 'reason_type' => 'no_longer_needed', 'requested_date' => '2026-10-25',
        ])->assertCreated();

        app(ContractChangeService::class)->approveTermination(ContractChangeRequest::firstOrFail(), $this->admin);

        $contract->refresh();
        $this->assertSame(ContractStatus::Terminated, $contract->status);
        $this->assertSame('2026-10-25', $contract->assignments()->first()->ended_on->toDateString());

        // الاستحقاق الأخير يُنشأ بعد آخر يوم عمل: 21 يوماً من 31 = 1800 × 21/31
        $this->at('2026-10-26 00:10');
        $contracts->runDaily();
        $this->assertSame(['1219.35'], $contract->payments()->pluck('amount')->all());

        $this->getJson("/api/v1/contracts/{$contract->id}")->assertJsonPath('data.terminated_at', '2026-10-25');
    }

    public function test_replacement_limit_setting_is_enforced(): void
    {
        Settings::set('contracts.max_replacements', 1, $this->admin);
        [$user, $customer] = $this->customer();
        [$w1, $w2] = [$this->worker(), $this->worker()];

        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(1), 'end_date' => $this->day(40)]);
        $service = app(ContractService::class);
        $service->confirm($contract, $this->admin);
        $service->assign($contract, $w1, $this->admin);
        $this->at('2026-10-05 09:00');
        $service->runDaily();

        $this->actingAsUser($user)->postJson("/api/v1/contracts/{$contract->id}/change-requests", ['type' => 'replace_worker', 'reason_type' => 'absence'])->assertCreated();
        app(ContractChangeService::class)->approveReplacement(ContractChangeRequest::firstOrFail(), $this->admin, $w2, CarbonImmutable::parse('2026-10-06'));

        $this->postJson("/api/v1/contracts/{$contract->id}/change-requests", ['type' => 'replace_worker', 'reason_type' => 'absence'])
            ->assertStatus(409)->assertJsonPath('code', 'REPLACEMENT_LIMIT_REACHED');
    }

    public function test_customer_can_cancel_before_start_only(): void
    {
        [$user, $customer] = $this->customer();
        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(3), 'end_date' => $this->day(33)]);

        $this->actingAsUser($user)->postJson("/api/v1/contracts/{$contract->id}/cancel", ['reason' => 'سافرنا'])
            ->assertOk()->assertJsonPath('data.status.value', 'cancelled');

        $active = Contract::factory()->for($customer)->status(ContractStatus::Active)->create();
        $this->postJson("/api/v1/contracts/{$active->id}/cancel")
            ->assertStatus(409)->assertJsonPath('code', 'CANCEL_NOT_ALLOWED');
    }
}
