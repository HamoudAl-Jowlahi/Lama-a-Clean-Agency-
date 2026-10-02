<?php

namespace Tests\Feature\Api;

use App\Models\ContractPlan;
use App\Models\ServicePrice;

/**
 * من يستخدم التطبيق بالإنجليزية يرى الخدمات والباقات وطلباته وعقوده بالإنجليزية،
 * مع الرجوع للعربية عندما لا يوجد نص إنجليزي.
 */
class EnglishContentTest extends ApiTestCase
{
    public function test_catalog_is_returned_in_english(): void
    {
        $this->withHeader('Accept-Language', 'en');

        $service = collect($this->getJson('/api/v1/services')->assertOk()->json('data'))->firstWhere('name', 'Full home cleaning');
        $this->assertSame('Complete cleaning of rooms, kitchen and bathrooms, including floors and surfaces.', $service['description']);
        $this->assertSame('Apartment up to 3 rooms', $service['prices'][0]['label']);

        $plan = collect($this->getJson('/api/v1/contract-plans')->json('data'))->firstWhere('name', 'Full-time');
        $this->assertSame('6 days a week, 8 hours a day', $plan['description']);
    }

    public function test_booking_and_contract_names_follow_the_language(): void
    {
        [$user, , $address] = $this->customer();
        $this->team(); // الأوقات المتاحة = عدد الفرق المفعّلة
        $price = ServicePrice::where('label_ar', 'شقة حتى 3 غرف')->firstOrFail();

        $id = $this->actingAsUser($user)->postJson('/api/v1/bookings', [
            'service_price_id' => $price->id,
            'address_id' => $address->id,
            'scheduled_date' => $this->day(1),
            'scheduled_time' => '10:00',
        ])->assertCreated()->json('data.id');

        $this->withHeader('Accept-Language', 'en')->getJson("/api/v1/bookings/{$id}")
            ->assertJsonPath('data.items.0.service', 'Full home cleaning')
            ->assertJsonPath('data.items.0.option', 'Apartment up to 3 rooms');
        $this->withHeader('Accept-Language', 'ar')->getJson("/api/v1/bookings/{$id}")
            ->assertJsonPath('data.items.0.service', 'تنظيف شامل للمنزل');

        $plan = ContractPlan::where('name_en', 'Full-time')->firstOrFail();
        $contractId = $this->postJson('/api/v1/contracts', [
            'plan_id' => $plan->id, 'address_id' => $address->id, 'months' => 1,
            'start_date' => $this->day(2), 'accept_terms' => true, 'terms_version' => '2026-10',
        ])->assertCreated()->json('data.id');

        $this->withHeader('Accept-Language', 'en')->getJson("/api/v1/contracts/{$contractId}")
            ->assertJsonPath('data.plan.name', 'Full-time');
    }

    public function test_falls_back_to_arabic_when_english_is_missing(): void
    {
        ContractPlan::query()->update(['description_en' => null]);

        $plan = collect($this->withHeader('Accept-Language', 'en')->getJson('/api/v1/contract-plans')->json('data'))
            ->firstWhere('name', 'Full-time');
        $this->assertSame('6 أيام أسبوعياً، 8 ساعات يومياً', $plan['description']);
    }
}
