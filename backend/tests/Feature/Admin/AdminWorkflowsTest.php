<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminRole;
use App\Enums\ChangeRequestStatus;
use App\Enums\ComplaintStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserStatus;
use App\Enums\WorkerType;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ChangeRequests\Pages\ListChangeRequests;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Teams\Pages\CreateTeam;
use App\Filament\Resources\Workers\Pages\CreateWorker;
use App\Filament\Resources\Workers\Pages\EditWorker;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\ContractChangeRequest;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Worker;
use App\Services\ContractService;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

class AdminWorkflowsTest extends AdminTestCase
{
    public function test_contract_confirm_and_assign_housekeeper(): void
    {
        $this->actingAsAdmin();
        [, $customer] = $this->customer();
        $housekeeper = $this->worker();
        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(2), 'end_date' => $this->day(31)]);

        Livewire::test(ListContracts::class)
            ->callTableAction('confirm', $contract)
            ->callTableAction('assign', $contract, data: ['worker_id' => $housekeeper->id])
            ->assertHasNoFormErrors();

        $contract->refresh();
        $this->assertSame(ContractStatus::Assigned, $contract->status);
        $this->assertSame($housekeeper->id, $contract->currentAssignment()->first()->worker_id);
    }

    public function test_approve_replacement_request_from_the_list(): void
    {
        $admin = $this->actingAsAdmin();
        [$user, $customer] = $this->customer();
        [$first, $second] = [$this->worker(), $this->worker()];
        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(1), 'end_date' => $this->day(30)]);
        $service = app(ContractService::class);
        $service->confirm($contract, $admin);
        $service->assign($contract, $first, $admin);
        $this->travelTo(Carbon::parse('2026-10-06 09:00', config('agency.timezone')));
        $service->runDaily();

        $this->actingAsUser($user)->postJson("/api/v1/contracts/{$contract->id}/change-requests", [
            'type' => 'replace_worker', 'reason_type' => 'quality',
        ])->assertCreated();
        $request = ContractChangeRequest::firstOrFail();

        $this->actingAs($admin, 'admin');
        Livewire::test(ListChangeRequests::class)
            ->assertTableActionHidden('approveTermination', $request) // نوع الطلب استبدال
            ->callTableAction('approveReplacement', $request, data: [
                'worker_id' => $second->id, 'start_on' => '2026-10-07', 'response' => 'تم تعيين بديلة',
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('تم اعتماد الاستبدال');

        $this->assertSame(ChangeRequestStatus::Approved, $request->fresh()->status);
        $this->assertSame($second->id, $contract->currentAssignment()->first()->worker_id);
        $this->assertSame(ContractStatus::Active, $contract->fresh()->status);
    }

    public function test_collect_cash_payment(): void
    {
        $this->actingAsAdmin();
        [, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create(['status' => 'completed']);
        $payment = Payment::create(['booking_id' => $booking->id, 'amount' => 250, 'currency' => 'SAR', 'due_date' => $this->day(0)]);

        Livewire::test(ListPayments::class)
            ->assertCanSeeTableRecords([$payment])
            ->callTableAction('collect', $payment, data: ['notes' => 'استلمه قائد الفريق'])
            ->assertNotified('تم تسجيل التحصيل');

        $this->assertSame(PaymentStatus::Collected, $payment->fresh()->status);
        $this->assertTrue(AuditLog::where('action', 'payment.collected')->exists());
    }

    public function test_complaint_reply_internal_note_and_status(): void
    {
        $this->actingAsAdmin(AdminRole::Support);
        [$user, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create();
        $complaint = Complaint::create(['customer_id' => $customer->id, 'booking_id' => $booking->id, 'type' => 'late', 'description' => 'تأخروا']);

        Livewire::test(ViewComplaint::class, ['record' => $complaint->id])
            ->callAction('reply', data: ['body' => 'ملاحظة للفريق فقط', 'internal' => true])
            ->callAction('reply', data: ['body' => 'نعتذر، سنتابع', 'internal' => false])
            ->callAction('status', data: ['status' => 'resolved'])
            ->assertNotified('تم تغيير الحالة');

        $this->assertSame(ComplaintStatus::Resolved, $complaint->fresh()->status);

        // العميل يرى الرد وتغيير الحالة، ولا يرى الملاحظة الداخلية
        $bodies = collect($this->actingAsUser($user)->getJson("/api/v1/complaints/{$complaint->id}")->json('data.messages'))->pluck('body');
        $this->assertContains('نعتذر، سنتابع', $bodies);
        $this->assertNotContains('ملاحظة للفريق فقط', $bodies);
    }

    public function test_create_team_through_the_form_and_validation(): void
    {
        $this->actingAsAdmin();
        $cleaners = Worker::factory()->cleaner()->count(3)->create();
        $housekeeper = $this->worker();

        // القائد ليس من الأعضاء → النموذج نفسه يرفضه (خيارات القائد = الأعضاء المختارون فقط)،
        // وTeamService يرفضه أيضاً لو وصل (TeamRulesTest)
        Livewire::test(CreateTeam::class)
            ->fillForm(['name' => 'فريق ج', 'members' => [$cleaners[0]->id, $cleaners[1]->id], 'leader_id' => $cleaners[2]->id, 'is_active' => true])
            ->call('create')
            ->assertHasFormErrors(['leader_id']);
        $this->assertDatabaseMissing('teams', ['name' => 'فريق ج']);

        // الخادمة لا تظهر في خيارات الأعضاء أصلاً
        $this->assertArrayNotHasKey($housekeeper->id, \App\Filament\Resources\Teams\TeamResource::memberOptions(null));

        Livewire::test(CreateTeam::class)
            ->fillForm(['name' => 'فريق ج', 'members' => $cleaners->modelKeys(), 'leader_id' => $cleaners[0]->id, 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('teams', ['name' => 'فريق ج', 'leader_id' => $cleaners[0]->id]);
    }

    public function test_create_and_edit_worker_keeps_encrypted_id(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateWorker::class)
            ->fillForm([
                'name' => 'هدى', 'phone' => '0551112233', 'password' => 'secret-pass',
                'type' => WorkerType::Cleaner->value, 'status' => 'active', 'national_id' => '1234567890',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('phone', '0551112233')->firstOrFail();
        $worker = $user->worker()->firstOrFail();
        $this->assertTrue($user->isWorker());
        $this->assertSame(WorkerType::Cleaner, $worker->type);

        // تعديل بدون إدخال رقم الهوية/كلمة المرور لا يمسحهما
        Livewire::test(EditWorker::class, ['record' => $worker->id])
            ->fillForm(['name' => 'هدى أحمد', 'national_id' => null, 'password' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $worker->refresh();
        $this->assertSame('1234567890', $worker->national_id);
        $this->assertSame('هدى أحمد', $worker->user()->first()->name);
        $this->postJson('/api/v1/auth/login', ['phone' => '0551112233', 'password' => 'secret-pass'])->assertOk();
    }

    public function test_worker_type_cannot_change_while_in_a_team(): void
    {
        $this->actingAsAdmin();
        $team = $this->team();
        $member = $this->member($team);

        Livewire::test(EditWorker::class, ['record' => $member->id])
            ->fillForm(['type' => WorkerType::Housekeeper->value])
            ->call('save')
            ->assertNotified('الموظف عضو في فريق — أخرجه من الفريق أولاً.');

        $this->assertSame(WorkerType::Cleaner, $member->fresh()->type);
    }

    public function test_suspending_a_customer_revokes_app_access(): void
    {
        $this->actingAsAdmin();
        [$user, $customer] = $this->customer();
        $token = $user->createToken('app', ['customer'])->plainTextToken;

        Livewire::test(ListCustomers::class)->callTableAction('toggleAccount', $customer);

        $this->assertSame(UserStatus::Suspended, $user->fresh()->status);
        $this->assertSame(0, $user->tokens()->count());
        $this->withToken($token)->getJson('/api/v1/bookings')->assertStatus(401);
    }

    public function test_settings_page_updates_business_rules_with_audit(): void
    {
        $this->actingAsAdmin(AdminRole::SuperAdmin);

        Livewire::test(ManageSettings::class)
            ->fillForm(['contracts__max_replacements' => 2, 'booking__worker_can_reject_assignment' => false])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('تم حفظ الإعدادات');

        $this->assertSame(2, Settings::get('contracts.max_replacements'));
        $this->assertFalse(Settings::get('booking.worker_can_reject_assignment'));
        $log = AuditLog::where('action', 'settings.updated')->firstOrFail();
        $this->assertSame(2, $log->new_values['contracts.max_replacements']);
    }

    public function test_price_changes_are_audited(): void
    {
        $this->actingAsAdmin();
        $service = Service::with('prices')->firstOrFail();
        $price = $service->prices->first();

        Livewire::test(EditService::class, ['record' => $service->id])
            ->fillForm(['name_ar' => $service->name_ar.' ✓'])
            ->call('save')
            ->assertHasNoFormErrors();
        $price->update(['amount' => 275]); // يحدث بنفس الطريقة من الـ Repeater

        $this->assertTrue(AuditLog::where('action', 'service.updated')->exists());
        $log = AuditLog::where('action', 'service_price.updated')->firstOrFail();
        $this->assertEquals(275, $log->new_values['amount']);
    }
}
