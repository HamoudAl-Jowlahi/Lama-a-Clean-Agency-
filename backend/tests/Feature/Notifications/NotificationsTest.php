<?php

namespace Tests\Feature\Notifications;

use App\Contracts\PushSender;
use App\Enums\ComplaintStatus;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\ContractChangeRequest;
use App\Models\DeviceToken;
use App\Models\ServicePrice;
use App\Notifications\AppNotification;
use App\Services\BookingService;
use App\Services\ComplaintService;
use App\Services\ContractChangeService;
use App\Services\ContractService;
use App\Support\Settings;
use Carbon\CarbonImmutable;
use Filament\Notifications\DatabaseNotification as PanelNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Api\ApiTestCase;

/** Phase 6: من يستلم ماذا — ولا يُشعَر من قام بالفعل نفسه. */
class NotificationsTest extends ApiTestCase
{
    /** مرسل Push وهمي يلتقط الرسائل. */
    private function fakePush(array $invalid = []): object
    {
        $fake = new class($invalid) implements PushSender
        {
            public array $sent = [];

            public function __construct(private array $invalid) {}

            public function send(array $tokens, string $title, string $body, array $data = []): array
            {
                $this->sent[] = compact('tokens', 'title', 'body', 'data');

                return array_values(array_intersect($tokens, $this->invalid));
            }
        };
        $this->app->instance(PushSender::class, $fake);

        return $fake;
    }

    private function sentEvent(object $notifiable, string $event, string $audience = 'customer'): void
    {
        Notification::assertSentTo($notifiable, AppNotification::class,
            fn (AppNotification $n) => $n->event === $event && $n->audience === $audience);
    }

    private function notSentEvent(object $notifiable, string $event): void
    {
        Notification::assertNotSentTo($notifiable, AppNotification::class, fn (AppNotification $n) => $n->event === $event);
    }

    public function test_visit_lifecycle_notifies_the_right_people(): void
    {
        Notification::fake();
        [$user, , $address] = $this->customer();
        $team = $this->team();
        $member = $this->member($team);
        $ops = AdminUser::factory()->create(); // operations
        $support = AdminUser::factory()->support()->create();

        $id = $this->actingAsUser($user)->postJson('/api/v1/bookings', [
            'service_price_id' => ServicePrice::firstOrFail()->id, 'address_id' => $address->id,
            'scheduled_date' => $this->day(1), 'scheduled_time' => '10:00',
        ])->assertCreated()->json('data.id');
        $booking = Booking::findOrFail($id);

        // طلب جديد → الإدارة التشغيلية فقط (لا العميل، ولا خدمة العملاء)
        Notification::assertSentTo($ops, PanelNotification::class);
        Notification::assertNotSentTo($support, PanelNotification::class);
        $this->notSentEvent($user, 'booking.created');

        $service = app(BookingService::class);
        $service->confirm($booking, $this->admin);
        $this->sentEvent($user, 'booking.confirmed');

        $service->assign($booking, $team, $this->admin);
        $this->sentEvent($user, 'booking.assigned');
        $this->sentEvent($team->leader->user, 'booking.assigned', 'staff');
        $this->sentEvent($member->user, 'booking.assigned', 'staff');

        $service->accept($booking, $team->leader);
        $service->advance($booking, $team->leader, \App\Enums\BookingStatus::OnTheWay);
        $this->sentEvent($user, 'booking.on_the_way');
        $this->notSentEvent($member->user, 'booking.on_the_way'); // الفريق نفسه من حدّث الحالة

        $service->advance($booking, $team->leader, \App\Enums\BookingStatus::InProgress);
        $service->advance($booking, $team->leader, \App\Enums\BookingStatus::Completed);
        Notification::assertSentTo($user, AppNotification::class, function (AppNotification $n) {
            return $n->event === 'booking.completed' && str_contains($n->body(), '250.00');
        });
    }

    public function test_notifications_are_stored_and_listed_in_the_app(): void
    {
        [$user, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create();
        app(BookingService::class)->confirm($booking, $this->admin);

        $item = $this->actingAsUser($user)->getJson('/api/v1/notifications')->assertOk()
            ->assertJsonPath('meta.unread', 1)
            ->json('data.0');

        $this->assertSame('booking.confirmed', $item['event']);
        $this->assertSame('تم تأكيد طلبك', $item['title']);
        $this->assertSame(['type' => 'booking', 'id' => $booking->id, 'number' => $booking->fresh()->booking_number], $item['subject']);

        $this->postJson("/api/v1/notifications/{$item['id']}/read")->assertOk();
        $this->getJson('/api/v1/notifications')->assertJsonPath('meta.unread', 0);
    }

    public function test_push_goes_to_devices_and_respects_preferences(): void
    {
        $push = $this->fakePush(invalid: ['dead-token']);
        [$user, $customer] = $this->customer();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'good-token', 'platform' => 'android']);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'dead-token', 'platform' => 'ios']);

        $booking = Booking::factory()->for($customer)->create();
        app(BookingService::class)->confirm($booking, $this->admin);

        $this->assertCount(1, $push->sent);
        $this->assertSame('تم تأكيد طلبك', $push->sent[0]['title']);
        $this->assertSame(['event' => 'booking.confirmed', 'subject_type' => 'booking', 'subject_id' => (string) $booking->id, 'subject_number' => $booking->fresh()->booking_number], $push->sent[0]['data']);
        // الجهاز الذي لم يعد مسجلاً يُحذف تلقائياً
        $this->assertSame(['good-token'], DeviceToken::pluck('token')->all());

        // العميل أطفأ إشعارات الطلبات من التطبيق → لا Push، لكن يبقى في القائمة
        $this->actingAsUser($user)->patchJson('/api/v1/auth/me', ['notification_preferences' => ['orders' => false]])
            ->assertOk()
            ->assertJsonPath('data.notification_preferences', ['orders' => false, 'complaints' => true]);

        app(BookingService::class)->cancelByAdmin($booking, $this->admin, 'تعذر الوصول');
        $this->assertCount(1, $push->sent);
        $this->assertSame(2, $user->notifications()->count());
    }

    public function test_admin_matrix_can_turn_an_event_off(): void
    {
        Notification::fake();
        $matrix = config('agency.notifications');
        $matrix['booking.confirmed']['customer'] = false;
        Settings::set('notifications', $matrix, $this->admin);

        [$user, $customer] = $this->customer();
        app(BookingService::class)->confirm(Booking::factory()->for($customer)->create(), $this->admin);

        $this->notSentEvent($user, 'booking.confirmed');
    }

    public function test_worker_replacement_notifies_customer_new_and_old_housekeeper(): void
    {
        Notification::fake();
        [$user, $customer] = $this->customer();
        [$old, $new] = [$this->worker(), $this->worker()];
        $contract = Contract::factory()->for($customer)->create(['start_date' => $this->day(1), 'end_date' => $this->day(30)]);
        $contracts = app(ContractService::class);
        $contracts->confirm($contract, $this->admin);
        $contracts->assign($contract, $old, $this->admin);
        $this->sentEvent($old->user, 'contract.assigned', 'staff');

        $this->travelTo(Carbon::parse('2026-10-05 09:00', config('agency.timezone')));
        $contracts->runDaily();
        $this->sentEvent($user, 'contract.active');

        $this->actingAsUser($user)->postJson("/api/v1/contracts/{$contract->id}/change-requests", ['type' => 'replace_worker', 'reason_type' => 'quality'])->assertCreated();
        Notification::assertSentTo($this->admin, PanelNotification::class);

        app(ContractChangeService::class)->approveReplacement(ContractChangeRequest::firstOrFail(), $this->admin, $new, CarbonImmutable::parse('2026-10-07'));

        Notification::assertSentTo($user, AppNotification::class, fn (AppNotification $n) => $n->event === 'contract.worker_changed'
            && str_contains($n->body(), strtok($new->user->name, ' ')) && str_contains($n->body(), '2026-10-07'));
        $this->sentEvent($new->user, 'contract.worker_changed', 'staff');
        $this->sentEvent($old->user, 'contract.worker_released', 'staff');
        $this->notSentEvent($user, 'change_request.approved'); // لا إشعار مكرر
    }

    public function test_complaint_internal_notes_do_not_notify_the_customer(): void
    {
        Notification::fake();
        [$user, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create();
        $complaint = Complaint::create(['customer_id' => $customer->id, 'booking_id' => $booking->id, 'type' => 'late', 'description' => 'x']);
        $complaints = app(ComplaintService::class);

        $complaints->reply($complaint, $this->admin, 'ملاحظة داخلية', internal: true);
        $this->notSentEvent($user, 'complaint.replied');

        $complaints->reply($complaint, $this->admin, 'نعتذر');
        $complaints->changeStatus($complaint, $this->admin, ComplaintStatus::Resolved);
        $this->sentEvent($user, 'complaint.replied');
        $this->sentEvent($user, 'complaint.status_changed');
    }

    public function test_messages_are_written_in_the_recipients_language(): void
    {
        [$user, $customer] = $this->customer();
        $user->update(['locale' => 'en']);
        [$arUser, $arCustomer] = $this->customer();

        app(BookingService::class)->confirm(Booking::factory()->for($customer)->create(), $this->admin);
        app(BookingService::class)->confirm(Booking::factory()->for($arCustomer)->create(), $this->admin);

        $this->assertSame('Your booking is confirmed', $user->notifications()->first()->data['title']);
        $this->assertSame('تم تأكيد طلبك', $arUser->notifications()->first()->data['title']);
    }

    public function test_contract_ending_reminder_is_sent_once(): void
    {
        Notification::fake();
        [$user, $customer] = $this->customer();
        $contract = Contract::factory()->for($customer)->status(\App\Enums\ContractStatus::Active)
            ->create(['start_date' => $this->day(-20), 'end_date' => $this->day(3)]); // ينتهي بعد 3 أيام (الافتراضي)

        $this->assertSame(1, app(ContractService::class)->runDaily()['reminders']);
        $this->assertSame(0, app(ContractService::class)->runDaily()['reminders']); // تشغيل ثانٍ في نفس اليوم

        Notification::assertSentToTimes($user, AppNotification::class, 1);
        $this->sentEvent($user, 'contract.ending_soon');
        Notification::assertSentTo($this->admin, PanelNotification::class);
    }
}
