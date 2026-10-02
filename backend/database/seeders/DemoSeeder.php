<?php

namespace Database\Seeders;

use App\Enums\ActorType;
use App\Enums\AdminRole;
use App\Enums\AssignmentEndReason;
use App\Enums\AssignmentStatus;
use App\Enums\BookingStatus;
use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRequestType;
use App\Enums\ComplaintMessageKind;
use App\Enums\ComplaintStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentStatus;
use App\Enums\WorkerStatus;
use App\Enums\WorkerType;
use App\Models\Address;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\ContractPlan;
use App\Models\Customer;
use App\Models\ServicePrice;
use App\Models\Team;
use App\Models\User;
use App\Models\Worker;
use App\Services\TeamService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * بيانات تجريبية للتطوير فقط (نفس سيناريوهات التصاميم في design/).
 * لا يعمل خارج local/testing.
 *
 * حسابات التطبيق التجريبية — كلمة المرور لكلها من UserFactory:
 *   العملاء       050000000X  (1 سارة · 2 نورة · 3 خالد · 4 ريم · 5 منى · 6 عبدالله)
 *   الخادمات      051000000X  (1 فاطمة · 2 مريم · 3 عائشة · 4 خديجة · 5 زينب/إجازة · 6 حليمة/غير نشطة)
 *   فرق الزيارات  052000000X  (1 هدى قائدة فريق أ · 2 سلمى · 3 رحاب · 4 منيرة قائدة فريق ب · 5 جميلة · 6 أمل)
 */
class DemoSeeder extends Seeder
{
    /** @var array<string, int> عداد أرقام الجوال لكل بادئة */
    private array $phoneCounters = [];
    private AdminUser $ops;

    private AdminUser $support;

    /** @var array<string, Worker> */
    private array $workers = [];

    /** @var array<string, Team> */
    private array $teams = [];

    /** @var array<string, Customer> */
    private array $customers = [];

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->error('DemoSeeder runs in local/testing only.');

            return;
        }

        $this->ops = $this->admin('مدير العمليات', 'ops@lamaa.test', AdminRole::Operations);
        $this->support = $this->admin('خدمة العملاء', 'support@lamaa.test', AdminRole::Support);

        // الخادمات — للعقود فقط
        foreach (['فاطمة', 'مريم', 'عائشة', 'خديجة'] as $name) {
            $this->workers[$name] = $this->worker($name);
        }
        $this->workers['زينب'] = $this->worker('زينب', WorkerStatus::OnLeave);
        $this->workers['حليمة'] = $this->worker('حليمة', WorkerStatus::Inactive);

        // فرق الزيارات (CR-3) — القائد أول اسم
        $teams = app(TeamService::class);
        foreach (['فريق أ' => ['هدى', 'سلمى', 'رحاب'], 'فريق ب' => ['منيرة', 'جميلة', 'أمل']] as $teamName => $names) {
            $members = array_map(fn ($n) => $this->workers[$n] = $this->worker($n, type: WorkerType::Cleaner), $names);
            $this->teams[$teamName] = $teams->create($teamName, array_map(fn ($w) => $w->id, $members), $members[0]->id, $this->ops);
        }

        foreach ([
            'سارة أحمد' => 'حي النرجس', 'نورة العتيبي' => 'حي الربيع', 'خالد الحربي' => 'حي الصحافة',
            'ريم القحطاني' => 'حي العارض', 'منى الشهري' => 'حي العليا', 'عبدالله السبيعي' => 'حي الملقا',
        ] as $name => $district) {
            $this->customers[$name] = $this->customer($name, $district);
        }

        $this->seedBookings();
        $this->seedContracts();
    }

    // ------------------------------------------------------------------ visits

    private function seedBookings(): void
    {
        $full = ServicePrice::where('label_ar', 'شقة حتى 3 غرف')->firstOrFail();
        $villa = ServicePrice::where('label_ar', 'فيلا / أكثر من 3 غرف')->firstOrFail();
        $windows = ServicePrice::where('label_ar', 'حتى 10 نوافذ')->firstOrFail();
        $today = today();

        $this->booking('نورة العتيبي', $villa, $today->copy()->addDay(), '08:00', BookingStatus::Pending);
        $this->booking('ريم القحطاني', $windows, $today->copy()->addDay(), '16:00', BookingStatus::Confirmed);
        $this->booking('سارة أحمد', $full, $today, '10:00', BookingStatus::OnTheWay, 'فريق أ');
        $this->booking('خالد الحربي', $full, $today, '14:00', BookingStatus::Assigned, 'فريق ب');

        $done = $this->booking('عبدالله السبيعي', $full, $today->copy()->subDay(), '10:00', BookingStatus::Completed, 'فريق ب');
        $done->rating()->create([
            'customer_id' => $done->customer_id,
            'team_id' => $this->teams['فريق ب']->id,
            'service_score' => 2,
            'worker_score' => 3,
            'comment' => 'التنظيف غير مكتمل في المطبخ',
        ]);
        $this->complaint($done, 'quality', 'التنظيف لم يكن مكتملاً، المطبخ بقي كما هو تقريباً.', ComplaintStatus::Open);

        $this->booking('ريم القحطاني', $windows, $today->copy()->subDays(2), '18:00', BookingStatus::Cancelled);
    }

    private function booking(string $customer, ServicePrice $price, Carbon $date, string $time, BookingStatus $target, ?string $teamName = null): Booking
    {
        $team = $teamName ? $this->teams[$teamName] : null;
        $c = $this->customers[$customer];
        $address = Address::findOrFail($c->default_address_id);
        $price->loadMissing('service');

        $booking = Booking::create([
            'customer_id' => $c->id,
            'address_id' => $address->id,
            'address_snapshot' => $address->toSnapshot(),
            'status' => BookingStatus::Pending,
            'scheduled_date' => $date->toDateString(),
            'scheduled_time' => $time,
            'subtotal' => $price->amount,
            'total' => $price->amount,
            'currency' => $price->currency,
            'created_at' => $date->copy()->subDay()->setTime(20, 12),
        ]);

        $booking->items()->create([
            'service_id' => $price->service_id,
            'service_price_id' => $price->id,
            'service_name_snapshot' => $price->service->name_ar,
            'price_label_snapshot' => $price->label_ar,
            'quantity' => 1,
            'unit_price' => $price->amount,
            'line_total' => $price->amount,
        ]);

        $flow = [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Assigned, BookingStatus::OnTheWay, BookingStatus::InProgress, BookingStatus::Completed];
        $path = $target === BookingStatus::Cancelled ? [BookingStatus::Pending, BookingStatus::Cancelled] : array_slice($flow, 0, array_search($target, $flow, true) + 1);

        $from = null;
        foreach ($path as $status) {
            $actor = match ($status) {
                BookingStatus::Pending, BookingStatus::Cancelled => [ActorType::Customer, $c->user_id],
                BookingStatus::Confirmed, BookingStatus::Assigned => [ActorType::Admin, $this->ops->id],
                default => [ActorType::Worker, Worker::whereKey($team->leader_id)->value('user_id')],
            };
            $booking->statusLogs()->create(['from_status' => $from?->value, 'to_status' => $status->value, 'actor_type' => $actor[0], 'actor_id' => $actor[1]]);

            if ($status === BookingStatus::Assigned) {
                $answered = $target !== BookingStatus::Assigned;
                $booking->assignments()->create([
                    'team_id' => $team->id,
                    'assigned_by' => $this->ops->id,
                    'status' => $answered ? AssignmentStatus::Accepted : AssignmentStatus::Pending,
                    'responded_by' => $answered ? $team->leader_id : null,
                    'responded_at' => $answered ? now() : null,
                ]);
            }
            $from = $status;
        }

        $booking->update([
            'status' => $target,
            'confirmed_at' => in_array(BookingStatus::Confirmed, $path, true) ? now() : null,
            'completed_at' => $target === BookingStatus::Completed ? now() : null,
            'cancelled_at' => $target === BookingStatus::Cancelled ? now() : null,
            'cancelled_by_type' => $target === BookingStatus::Cancelled ? ActorType::Customer : null,
            'cancel_reason' => $target === BookingStatus::Cancelled ? 'تغيّر الموعد' : null,
        ]);

        if ($target === BookingStatus::Completed) {
            $booking->payment()->create(['amount' => $booking->total, 'currency' => $booking->currency, 'due_date' => $date->toDateString()]);
        }

        return $booking;
    }

    // --------------------------------------------------------------- contracts

    private function seedContracts(): void
    {
        $fullTime = ContractPlan::where('name_ar', 'دوام كامل')->firstOrFail();
        $halfDay = ContractPlan::where('name_ar', 'نصف يوم')->firstOrFail();
        $partTime = ContractPlan::where('name_ar', 'دوام جزئي')->firstOrFail();
        $today = today();

        $this->contract('نورة العتيبي', $fullTime, $today->copy()->addDays(5), 3, ContractStatus::Pending);
        $this->contract('ريم القحطاني', $halfDay, $today->copy()->addDays(2), 1, ContractStatus::Confirmed);

        // سارة: عقد ساري، استُبدلت فاطمة بعائشة بعد طلب معتمد
        $sara = $this->contract('سارة أحمد', $fullTime, $today->copy()->subDays(12), 1, ContractStatus::Active, 'فاطمة');
        $first = $sara->assignments()->firstOrFail();
        $request = $sara->changeRequests()->create([
            'customer_id' => $sara->customer_id,
            'type' => ChangeRequestType::ReplaceWorker,
            'reason_type' => 'quality',
            'details' => 'المطبخ لا يُنظف بشكل جيد رغم التنبيه أكثر من مرة.',
            'status' => ChangeRequestStatus::Approved,
            'admin_response' => 'تم تعيين عاملة بديلة.',
            'handled_by' => $this->ops->id,
            'handled_at' => now()->subDays(10),
        ]);
        $first->update(['ended_on' => $today->copy()->subDays(10), 'end_reason' => AssignmentEndReason::Replaced]);
        $second = $sara->assignments()->create([
            'worker_id' => $this->workers['عائشة']->id,
            'assigned_by' => $this->ops->id,
            'started_on' => $today->copy()->subDays(8),
        ]);
        $request->update(['resulting_assignment_id' => $second->id]);
        $this->complaint($sara, 'late', 'تأخرت العاملة ساعة كاملة عن الموعد بدون إبلاغ.', ComplaintStatus::UnderReview);

        // منى: عقد ساري + طلب استبدال جديد + طلب إنهاء قيد المراجعة
        $mona = $this->contract('منى الشهري', $partTime, $today->copy()->subDays(40), 3, ContractStatus::Active, 'مريم');
        $mona->changeRequests()->create(['customer_id' => $mona->customer_id, 'type' => ChangeRequestType::ReplaceWorker, 'reason_type' => 'frequent_delay', 'details' => 'تتأخر العاملة يومياً قرابة ساعة.']);
        $mona->changeRequests()->create(['customer_id' => $mona->customer_id, 'type' => ChangeRequestType::Terminate, 'reason_type' => 'no_longer_needed', 'requested_date' => $today->copy()->addDays(10), 'status' => ChangeRequestStatus::UnderReview]);
        $mona->payments()->create(['period_start' => $mona->start_date, 'period_end' => $mona->start_date->copy()->addMonth()->subDay(), 'amount' => $mona->monthly_price, 'currency' => $mona->currency, 'due_date' => $mona->start_date->copy()->addMonth()->subDay(), 'status' => PaymentStatus::Collected, 'collected_at' => now()->subDays(9), 'collected_by' => $this->ops->id]);

        // عبدالله: عقد منتهٍ ومحصّل ومقيّم
        $done = $this->contract('عبدالله السبيعي', $fullTime, $today->copy()->subMonths(2), 1, ContractStatus::Completed, 'خديجة');
        $done->assignments()->update(['ended_on' => $done->end_date, 'end_reason' => AssignmentEndReason::ContractCompleted]);
        $done->payments()->create(['period_start' => $done->start_date, 'period_end' => $done->end_date, 'amount' => $done->total_amount, 'currency' => $done->currency, 'due_date' => $done->end_date, 'status' => PaymentStatus::Collected, 'collected_at' => $done->end_date, 'collected_by' => $this->ops->id]);
        $done->ratings()->create(['customer_id' => $done->customer_id, 'worker_id' => $this->workers['خديجة']->id, 'service_score' => 5, 'worker_score' => 5, 'comment' => 'عمل ممتاز والتزام بالموعد.']);
    }

    private function contract(string $customer, ContractPlan $plan, Carbon $start, int $months, ContractStatus $target, ?string $worker = null): Contract
    {
        $c = $this->customers[$customer];
        $address = Address::findOrFail($c->default_address_id);
        $end = $start->copy()->addMonthsNoOverflow($months)->subDay();

        $contract = Contract::create([
            'customer_id' => $c->id,
            'plan_id' => $plan->id,
            'address_id' => $address->id,
            'address_snapshot' => $address->toSnapshot(),
            'plan_snapshot' => $plan->toSnapshot(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'months' => $months,
            'monthly_price' => $plan->monthly_price,
            'total_amount' => $plan->monthly_price * $months,
            'currency' => $plan->currency,
            'status' => $target,
            'terms_version' => config('agency.contracts.terms_version'),
            'terms_accepted_at' => $start->copy()->subDays(3),
            'confirmed_at' => $target === ContractStatus::Pending ? null : $start->copy()->subDays(2),
            'activated_at' => in_array($target, [ContractStatus::Active, ContractStatus::Completed], true) ? $start : null,
            'ended_at' => $target === ContractStatus::Completed ? $end : null,
        ]);

        $path = match ($target) {
            ContractStatus::Pending => [ContractStatus::Pending],
            ContractStatus::Confirmed => [ContractStatus::Pending, ContractStatus::Confirmed],
            ContractStatus::Active => [ContractStatus::Pending, ContractStatus::Confirmed, ContractStatus::Assigned, ContractStatus::Active],
            default => [ContractStatus::Pending, ContractStatus::Confirmed, ContractStatus::Assigned, ContractStatus::Active, $target],
        };
        $from = null;
        foreach ($path as $status) {
            $actor = match ($status) {
                ContractStatus::Pending => [ActorType::Customer, $c->user_id],
                ContractStatus::Active, ContractStatus::Completed => [ActorType::System, null],
                default => [ActorType::Admin, $this->ops->id],
            };
            $contract->statusLogs()->create(['from_status' => $from?->value, 'to_status' => $status->value, 'actor_type' => $actor[0], 'actor_id' => $actor[1]]);
            $from = $status;
        }

        if ($worker) {
            $contract->assignments()->create([
                'worker_id' => $this->workers[$worker]->id,
                'assigned_by' => $this->ops->id,
                'started_on' => $start->toDateString(),
            ]);
        }

        return $contract;
    }

    // ---------------------------------------------------------------- helpers

    private function complaint(Booking|Contract $subject, string $type, string $description, ComplaintStatus $status): Complaint
    {
        $complaint = Complaint::create([
            'customer_id' => $subject->customer_id,
            'booking_id' => $subject instanceof Booking ? $subject->id : null,
            'contract_id' => $subject instanceof Contract ? $subject->id : null,
            'type' => $type,
            'description' => $description,
            'status' => $status,
        ]);

        $customerUserId = Customer::whereKey($subject->customer_id)->value('user_id');
        $complaint->messages()->create(['sender_type' => ActorType::Customer, 'sender_id' => $customerUserId, 'kind' => ComplaintMessageKind::Message, 'body' => $description]);

        if ($status === ComplaintStatus::UnderReview) {
            $complaint->messages()->create(['sender_type' => ActorType::Admin, 'sender_id' => $this->ops->id, 'kind' => ComplaintMessageKind::InternalNote, 'body' => 'تم التواصل مع العاملة، أفادت بضيق الوقت.']);
            $complaint->messages()->create(['sender_type' => ActorType::Admin, 'sender_id' => $this->support->id, 'kind' => ComplaintMessageKind::StatusChange, 'meta' => ['from' => 'open', 'to' => 'under_review']]);
            $complaint->messages()->create(['sender_type' => ActorType::Admin, 'sender_id' => $this->support->id, 'kind' => ComplaintMessageKind::Message, 'body' => 'نعتذر عن ذلك. نراجع الأمر وسنعود إليك خلال 24 ساعة.']);
        }

        return $complaint;
    }

    private function admin(string $name, string $email, AdminRole $role): AdminUser
    {
        $admin = AdminUser::firstOrNew(['email' => $email]);
        $admin->fill(['name' => $name, 'password' => 'password']);
        $admin->role = $role;
        $admin->save();

        return $admin;
    }

    private function worker(string $name, WorkerStatus $status = WorkerStatus::Active, WorkerType $type = WorkerType::Housekeeper): Worker
    {
        $phone = $this->nextPhone($type === WorkerType::Cleaner ? '052' : '051');

        return Worker::factory()
            ->for(User::factory()->worker()->state(['name' => $name, 'phone' => $phone]))
            ->create(['status' => $status, 'type' => $type]);
    }

    private function customer(string $name, string $district): Customer
    {
        $user = User::factory()->customer()->state(['name' => $name, 'phone' => $this->nextPhone('050')]);
        $customer = Customer::factory()->for($user)->create();
        $address = Address::factory()->for($customer)->create(['label' => 'المنزل', 'district' => $district]);
        $customer->update(['default_address_id' => $address->id]);

        return $customer;
    }

    /** 0500000001، 0500000002 ... حسب البادئة. */
    private function nextPhone(string $prefix): string
    {
        $this->phoneCounters[$prefix] = ($this->phoneCounters[$prefix] ?? 0) + 1;

        return $prefix.str_pad((string) $this->phoneCounters[$prefix], 7, '0', STR_PAD_LEFT);
    }
}
