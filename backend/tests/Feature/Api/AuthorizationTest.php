<?php

namespace Tests\Feature\Api;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\ServicePrice;

/**
 * SRS §12: "الصلاحيات تمنع الوصول غير المصرح به".
 * مورد لا يخصك = 404 (لا نكشف وجوده)، ودور خاطئ = 403.
 */
class AuthorizationTest extends ApiTestCase
{
    public function test_customer_cannot_access_another_customers_data(): void
    {
        [$owner, $ownerCustomer, $ownerAddress] = $this->customer();
        [$intruder] = $this->customer();

        $booking = Booking::factory()->for($ownerCustomer)->status(BookingStatus::Completed)->create();
        $contract = Contract::factory()->for($ownerCustomer)->create();
        $complaint = Complaint::create(['customer_id' => $ownerCustomer->id, 'booking_id' => $booking->id, 'type' => 'other', 'description' => 'x']);

        $this->actingAsUser($intruder);
        foreach ([
            ['get', "/api/v1/bookings/{$booking->id}"],
            ['post', "/api/v1/bookings/{$booking->id}/cancel"],
            ['post', "/api/v1/bookings/{$booking->id}/rating", ['service_score' => 1]],
            ['get', "/api/v1/contracts/{$contract->id}"],
            ['post', "/api/v1/contracts/{$contract->id}/cancel"],
            ['get', "/api/v1/complaints/{$complaint->id}"],
            ['post', "/api/v1/complaints/{$complaint->id}/messages", ['body' => 'x']],
            ['post', '/api/v1/complaints', ['booking_id' => $booking->id, 'type' => 'other', 'description' => 'x']],
            ['put', "/api/v1/addresses/{$ownerAddress->id}", ['label' => 'x', 'city' => 'x', 'district' => 'x']],
            ['delete', "/api/v1/addresses/{$ownerAddress->id}"],
        ] as $req) {
            $this->json($req[0], $req[1], $req[2] ?? [])->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND');
        }

        // لا يمكن الحجز على عنوان عميل آخر
        $this->postJson('/api/v1/bookings', [
            'service_price_id' => ServicePrice::firstOrFail()->id, 'address_id' => $ownerAddress->id,
            'scheduled_date' => $this->day(1), 'scheduled_time' => '10:00',
        ])->assertStatus(404);

        // قوائمه لا تحتوي بيانات غيره
        $this->getJson('/api/v1/bookings')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/contracts')->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);
    }

    public function test_worker_sees_only_assigned_bookings_and_contracts(): void
    {
        [, $customer] = $this->customer();
        $worker = $this->worker();
        $booking = Booking::factory()->for($customer)->status(BookingStatus::Confirmed)->create();
        $contract = Contract::factory()->for($customer)->create();

        $this->actingAsWorker($worker);
        $this->getJson("/api/v1/worker/bookings/{$booking->id}")->assertStatus(404);
        $this->postJson("/api/v1/worker/bookings/{$booking->id}/status", ['status' => 'on_the_way'])->assertStatus(404);
        $this->getJson("/api/v1/worker/contracts/{$contract->id}")->assertStatus(404);
        $this->getJson('/api/v1/worker/bookings')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_roles_cannot_use_each_others_endpoints(): void
    {
        [$customerUser] = $this->customer();
        $worker = $this->worker();

        $this->actingAsUser($customerUser)->getJson('/api/v1/worker/bookings')
            ->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');

        $this->actingAsWorker($worker)->getJson('/api/v1/bookings')
            ->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');
        $this->postJson('/api/v1/contracts', [])->assertStatus(403);
    }

    public function test_customer_cannot_advance_status_or_forge_prices(): void
    {
        [$user, , $address] = $this->customer();
        $this->worker();
        $price = ServicePrice::where('label_ar', 'شقة حتى 3 غرف')->firstOrFail();

        // المبلغ من الخادم فقط — أي total/status مرسل يُتجاهل
        $this->actingAsUser($user)->postJson('/api/v1/bookings', [
            'service_price_id' => $price->id, 'address_id' => $address->id,
            'scheduled_date' => $this->day(1), 'scheduled_time' => '10:00',
            'total' => 1, 'status' => 'completed',
        ])->assertCreated()
            ->assertJsonPath('data.total', '250.00')
            ->assertJsonPath('data.status.value', 'pending');
    }
}
