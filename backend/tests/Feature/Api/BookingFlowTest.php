<?php

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Enums\ComplaintStatus;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\ServicePrice;
use App\Services\BookingService;
use App\Services\CollectionService;
use App\Services\ComplaintService;

/**
 * معايير القبول في SRS §12 للزيارات، من طلب العميل حتى التقييم والشكوى.
 */
class BookingFlowTest extends ApiTestCase
{
    public function test_full_visit_lifecycle(): void
    {
        [$user, $customer, $address] = $this->customer();
        $worker = $this->worker();
        $price = ServicePrice::where('label_ar', 'شقة حتى 3 غرف')->firstOrFail();

        // 1) العميل يرى الخدمات والأوقات ويحسب السعر
        $this->actingAsUser($user)->getJson('/api/v1/services')->assertOk()->assertJsonCount(4, 'data');
        $this->getJson('/api/v1/availability?date='.$this->day(1))
            ->assertOk()->assertJsonPath('data.slots.1', ['time' => '10:00', 'available' => true]);
        $this->postJson('/api/v1/bookings/quote', ['service_price_id' => $price->id])
            ->assertOk()->assertJsonPath('data.total', '250.00');

        // 2) العميل ينشئ طلباً كاملاً (SRS: يستطيع العميل إنشاء طلب كامل)
        $bookingId = $this->postJson('/api/v1/bookings', [
            'service_price_id' => $price->id,
            'address_id' => $address->id,
            'scheduled_date' => $this->day(1),
            'scheduled_time' => '10:00',
            'customer_notes' => 'يوجد حيوان أليف',
        ])->assertCreated()
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.status.label', 'قيد المراجعة')
            ->assertJsonPath('data.payment_method', 'cash')
            ->json('data.id');

        $booking = Booking::findOrFail($bookingId);
        $this->assertMatchesRegularExpression('/^BK-2026-\d{6}$/', $booking->booking_number);

        // 3) الإدارة تراجع وتسند (SRS: تستطيع الإدارة رؤية الطلب وإسناده)
        $service = app(BookingService::class);
        $service->confirm($booking, $this->admin);
        $service->assign($booking, $worker, $this->admin);

        // 4) العاملة ترى الطلب المسند وتحدّث حالته (SRS)
        $this->actingAsWorker($worker)->getJson('/api/v1/worker/bookings?scope=new')
            ->assertOk()->assertJsonPath('data.0.id', $bookingId)
            ->assertJsonPath('data.0.customer.phone', null); // الهاتف مخفي قبل التوجه

        $this->postJson("/api/v1/worker/bookings/{$bookingId}/accept")->assertOk()
            ->assertJsonPath('data.assignment_status.value', 'accepted')
            ->assertJsonPath('data.next_statuses.0.value', 'on_the_way');

        $this->postJson("/api/v1/worker/bookings/{$bookingId}/status", ['status' => 'on_the_way'])->assertOk()
            ->assertJsonPath('data.customer.phone', $user->phone); // يظهر أثناء التنفيذ

        // لا يمكن القفز فوق الخطوات
        $this->postJson("/api/v1/worker/bookings/{$bookingId}/status", ['status' => 'completed'])
            ->assertStatus(409)->assertJsonPath('code', 'INVALID_TRANSITION');

        $this->postJson("/api/v1/worker/bookings/{$bookingId}/status", ['status' => 'in_progress'])->assertOk();
        $this->postJson("/api/v1/worker/bookings/{$bookingId}/status", ['status' => 'completed'])->assertOk()
            ->assertJsonPath('data.status.value', 'completed')
            ->assertJsonPath('data.customer.phone', null); // يُخفى بعد الإتمام

        // 5) العميل يتابع الحالة بسجل زمني كامل + استحقاق نقدي (SRS + CR-1)
        $this->actingAsUser($user)->getJson("/api/v1/bookings/{$bookingId}")->assertOk()
            ->assertJsonPath('data.status.value', 'completed')
            ->assertJsonPath('data.worker.name', explode(' ', $worker->user->name)[0])
            ->assertJsonPath('data.payment.status.value', 'due')
            ->assertJsonPath('data.payment.amount', '250.00')
            ->assertJsonPath('data.can_rate', true)
            ->assertJsonCount(6, 'data.timeline');

        // الإدارة تسجل التحصيل النقدي
        app(CollectionService::class)->collect($booking->payment()->firstOrFail(), $this->admin);
        $this->getJson("/api/v1/bookings/{$bookingId}")->assertJsonPath('data.payment.status.value', 'collected');

        // 6) التقييم بعد الإكمال، مرة واحدة (SRS)
        $this->postJson("/api/v1/bookings/{$bookingId}/rating", ['service_score' => 5, 'worker_score' => 4, 'comment' => 'ممتاز'])
            ->assertCreated();
        $this->postJson("/api/v1/bookings/{$bookingId}/rating", ['service_score' => 3])
            ->assertStatus(409)->assertJsonPath('code', 'ALREADY_RATED');

        // 7) شكوى مرتبطة بالطلب، والإدارة تعالجها وتغير حالتها (SRS)
        $complaintId = $this->postJson('/api/v1/complaints', [
            'booking_id' => $bookingId, 'type' => 'quality', 'description' => 'المطبخ لم يُنظف جيداً',
        ])->assertCreated()->assertJsonPath('data.status.value', 'open')->json('data.id');

        $complaint = Complaint::findOrFail($complaintId);
        $complaints = app(ComplaintService::class);
        $complaints->reply($complaint, $this->admin, 'ملاحظة داخلية سرية', internal: true);
        $complaints->changeStatus($complaint, $this->admin, ComplaintStatus::UnderReview);
        $complaints->reply($complaint, $this->admin, 'نعتذر، سنرسل عاملة لإكمال التنظيف.');
        $complaints->changeStatus($complaint, $this->admin, ComplaintStatus::Resolved);

        $messages = $this->getJson("/api/v1/complaints/{$complaintId}")->assertOk()
            ->assertJsonPath('data.status.value', 'resolved')
            ->json('data.messages');
        $this->assertCount(4, $messages); // الشكوى + تغييرا حالة + رد — بدون الملاحظة الداخلية
        $this->assertNotContains('ملاحظة داخلية سرية', array_column($messages, 'body'));
    }

    public function test_rating_before_completion_is_rejected(): void
    {
        [$user, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create();

        $this->actingAsUser($user)->postJson("/api/v1/bookings/{$booking->id}/rating", ['service_score' => 5])
            ->assertStatus(409)->assertJsonPath('code', 'RATING_NOT_ALLOWED_YET');
    }

    public function test_idempotency_key_prevents_duplicate_bookings(): void
    {
        [$user, , $address] = $this->customer();
        $this->worker();
        $payload = [
            'service_price_id' => ServicePrice::firstOrFail()->id,
            'address_id' => $address->id,
            'scheduled_date' => $this->day(2),
            'scheduled_time' => '12:00',
        ];

        $first = $this->actingAsUser($user)->withHeader('Idempotency-Key', 'abc-123')->postJson('/api/v1/bookings', $payload)->assertCreated();
        $second = $this->withHeader('Idempotency-Key', 'abc-123')->postJson('/api/v1/bookings', $payload)->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Booking::count());
    }

    public function test_customer_cancellation_policy(): void
    {
        [$user, , $address] = $this->customer();
        $this->worker();
        $priceId = ServicePrice::firstOrFail()->id;

        // اليوم 9:00 → موعد الغد 10:00 قابل للإلغاء
        $id = $this->actingAsUser($user)->postJson('/api/v1/bookings', [
            'service_price_id' => $priceId, 'address_id' => $address->id,
            'scheduled_date' => $this->day(1), 'scheduled_time' => '10:00',
        ])->json('data.id');

        // قبل الموعد بأقل من 6 ساعات: مرفوض
        $this->travel(20)->hours();
        $this->postJson("/api/v1/bookings/{$id}/cancel")->assertStatus(409)->assertJsonPath('code', 'CANCEL_TOO_LATE');

        $this->travel(-20)->hours();
        $this->postJson("/api/v1/bookings/{$id}/cancel", ['reason' => 'تغيّر الموعد'])->assertOk()
            ->assertJsonPath('data.status.value', 'cancelled')
            ->assertJsonPath('data.cancel_reason', 'تغيّر الموعد');
    }

    public function test_slot_validation(): void
    {
        [$user, , $address] = $this->customer();
        $priceId = ServicePrice::firstOrFail()->id;
        $base = ['service_price_id' => $priceId, 'address_id' => $address->id, 'scheduled_date' => $this->day(1)];

        // لا توجد عاملات نشطة → لا طاقة
        $this->actingAsUser($user)->postJson('/api/v1/bookings', $base + ['scheduled_time' => '10:00'])
            ->assertStatus(409)->assertJsonPath('code', 'SLOT_UNAVAILABLE');

        $this->worker();
        // خارج أوقات العمل / ليس بداية فترة
        $this->postJson('/api/v1/bookings', $base + ['scheduled_time' => '22:00'])
            ->assertStatus(422)->assertJsonPath('code', 'SLOT_INVALID');
        // قبل مهلة 12 ساعة
        $this->postJson('/api/v1/bookings', ['scheduled_date' => $this->day(0), 'scheduled_time' => '14:00'] + $base)
            ->assertStatus(409)->assertJsonPath('code', 'SLOT_UNAVAILABLE');

        // عاملة واحدة = حجز واحد لكل فترة
        $this->postJson('/api/v1/bookings', $base + ['scheduled_time' => '10:00'])->assertCreated();
        $this->postJson('/api/v1/bookings', $base + ['scheduled_time' => '10:00'])
            ->assertStatus(409)->assertJsonPath('code', 'SLOT_UNAVAILABLE');
    }

    public function test_worker_rejection_returns_booking_to_confirmed(): void
    {
        [, $customer] = $this->customer();
        $worker = $this->worker();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create();
        app(BookingService::class)->assign($booking, $worker, $this->admin);

        $this->actingAsWorker($worker)->postJson("/api/v1/worker/bookings/{$booking->id}/reject", ['reason' => 'تعارض في الموعد'])
            ->assertOk()->assertJsonPath('data.status.value', 'confirmed');

        // بعد الرفض لم يعد الطلب مسنداً لها
        $this->getJson("/api/v1/worker/bookings/{$booking->id}")->assertStatus(404);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertSame('rejected', $booking->assignments()->first()->status->value);
    }
}
