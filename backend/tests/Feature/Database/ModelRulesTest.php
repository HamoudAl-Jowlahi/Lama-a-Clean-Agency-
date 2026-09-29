<?php

namespace Tests\Feature\Database;

use App\Enums\ActorType;
use App\Enums\AssignmentStatus;
use App\Enums\BookingStatus;
use App\Enums\ComplaintMessageKind;
use App\Enums\UserRole;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\User;
use App\Models\Worker;
use App\Support\Settings;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ModelRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_must_belong_to_exactly_one_of_booking_or_contract(): void
    {
        $booking = Booking::factory()->create();
        $contract = Contract::factory()->create();

        $this->expectException(LogicException::class);
        Payment::create(['booking_id' => $booking->id, 'contract_id' => $contract->id, 'amount' => 10, 'currency' => 'SAR', 'due_date' => today()]);
    }

    public function test_rating_and_complaint_without_subject_are_rejected(): void
    {
        $customer = Customer::factory()->create();

        foreach ([
            fn () => Rating::create(['customer_id' => $customer->id, 'service_score' => 5]),
            fn () => Complaint::create(['customer_id' => $customer->id, 'type' => 'other', 'description' => 'x']),
        ] as $create) {
            try {
                $create();
                $this->fail('Expected LogicException');
            } catch (LogicException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_booking_can_have_only_one_rating(): void
    {
        $booking = Booking::factory()->status(BookingStatus::Completed)->create();
        Rating::create(['booking_id' => $booking->id, 'customer_id' => $booking->customer_id, 'service_score' => 5]);

        $this->expectException(QueryException::class);
        Rating::create(['booking_id' => $booking->id, 'customer_id' => $booking->customer_id, 'service_score' => 4]);
    }

    public function test_document_numbers_are_generated_from_id(): void
    {
        $booking = Booking::factory()->create();
        $contract = Contract::factory()->create();

        $this->assertSame(sprintf('BK-%d-%06d', now()->year, $booking->id), $booking->fresh()->booking_number);
        $this->assertSame(sprintf('CT-%d-%06d', now()->year, $contract->id), $contract->fresh()->contract_number);
    }

    public function test_role_and_status_cannot_be_mass_assigned(): void
    {
        // في وضع strict (خارج الإنتاج) المحاولة ترمي خطأ بدل التجاهل الصامت
        try {
            new User(['name' => 'x', 'phone' => '0500000000', 'password' => 'secret123', 'role' => 'worker', 'status' => 'suspended']);
            $this->fail('Expected MassAssignmentException');
        } catch (MassAssignmentException $e) {
            $this->assertStringContainsString('role, status', $e->getMessage());
        }

        // وفي الإنتاج تُتجاهل الحقول فلا تتغير الصلاحية
        Model::preventSilentlyDiscardingAttributes(false);
        $user = new User(['name' => 'x', 'phone' => '0500000000', 'password' => 'secret123', 'role' => 'worker', 'status' => 'suspended']);
        Model::preventSilentlyDiscardingAttributes(true);

        $this->assertNull($user->role);
        $this->assertSame('active', $user->getAttributes()['status']);
    }

    public function test_worker_national_id_is_encrypted_at_rest_and_hidden(): void
    {
        $worker = Worker::factory()->create(['national_id' => '1234567890']);

        $raw = DB::table('workers')->where('id', $worker->id)->value('national_id');
        $this->assertNotSame('1234567890', $raw);
        $this->assertSame('1234567890', $worker->fresh()->national_id);
        $this->assertArrayNotHasKey('national_id', $worker->fresh()->toArray());
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'plain-password']);

        $this->assertNotSame('plain-password', DB::table('users')->where('id', $user->id)->value('password'));
    }

    public function test_settings_override_config_defaults(): void
    {
        $this->assertSame(2, Settings::get('contracts.replacement_sla_days'));

        Settings::set('contracts.replacement_sla_days', 5, AdminUser::factory()->create());

        $this->assertSame(5, Settings::get('contracts.replacement_sla_days'));
    }

    public function test_unknown_setting_key_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Settings::set('not.a.real.key', 1);
    }

    public function test_internal_notes_are_hidden_from_customer_scope(): void
    {
        $booking = Booking::factory()->create();
        $complaint = Complaint::create(['customer_id' => $booking->customer_id, 'booking_id' => $booking->id, 'type' => 'quality', 'description' => 'x']);
        $complaint->messages()->create(['sender_type' => ActorType::Customer, 'kind' => ComplaintMessageKind::Message, 'body' => 'a']);
        $complaint->messages()->create(['sender_type' => ActorType::Admin, 'kind' => ComplaintMessageKind::InternalNote, 'body' => 'secret']);

        $visible = $complaint->messages()->visibleToCustomer()->pluck('body')->all();

        $this->assertSame(['a'], $visible);
    }

    public function test_scopes_isolate_customers_and_workers(): void
    {
        [$mine, $other] = Booking::factory()->count(2)->create();
        $worker = Worker::factory()->create();
        $admin = AdminUser::factory()->create();
        $mine->assignments()->create(['worker_id' => $worker->id, 'assigned_by' => $admin->id, 'status' => AssignmentStatus::Accepted]);
        $other->assignments()->create(['worker_id' => $worker->id, 'assigned_by' => $admin->id, 'status' => AssignmentStatus::Rejected]);

        $customer = Customer::findOrFail($mine->customer_id);

        $this->assertSame([$mine->id], Booking::forCustomer($customer)->pluck('id')->all());
        // الإسناد المرفوض لا يمنح العاملة حق رؤية الطلب
        $this->assertSame([$mine->id], Booking::assignedTo($worker)->pluck('id')->all());
    }

    public function test_user_role_helpers(): void
    {
        $user = User::factory()->worker()->create();

        $this->assertSame(UserRole::Worker, $user->role);
        $this->assertTrue($user->isWorker());
        $this->assertFalse($user->isCustomer());
    }
}
