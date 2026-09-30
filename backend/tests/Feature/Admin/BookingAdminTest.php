<?php

namespace Tests\Feature\Admin;

use App\Enums\AdminRole;
use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Notifications\Notification;
use Livewire\Livewire;

class BookingAdminTest extends AdminTestCase
{
    public function test_pages_render_for_operations(): void
    {
        $this->actingAsAdmin();
        [, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create();

        $this->get('/admin')->assertOk();
        $this->get('/admin/bookings')->assertOk()->assertSee($booking->fresh()->booking_number);
        $this->get("/admin/bookings/{$booking->id}")->assertOk()->assertSee('السجل الزمني');
    }

    public function test_confirm_then_assign_to_team_from_the_table(): void
    {
        $this->actingAsAdmin();
        [, $customer] = $this->customer();
        $team = $this->team();
        $booking = Booking::factory()->for($customer)->create();

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$booking])
            ->assertTableActionVisible('confirm', $booking)
            ->assertTableActionHidden('assign', $booking) // لا إسناد قبل التأكيد
            ->callTableAction('confirm', $booking)
            ->assertNotified('تم تأكيد الطلب');

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        Livewire::test(ListBookings::class)
            ->callTableAction('assign', $booking, data: ['team_id' => $team->id])
            ->assertHasNoFormErrors()
            ->assertNotified('تم إسناد الطلب للفريق');

        $this->assertSame(BookingStatus::Assigned, $booking->fresh()->status);
        $this->assertSame($team->id, $booking->assignments()->first()->team_id);
    }

    public function test_business_rule_errors_are_shown_in_arabic(): void
    {
        $admin = $this->actingAsAdmin();
        [, $customer] = $this->customer();
        $team = $this->team();
        $make = fn (string $time) => Booking::factory()->for($customer)->status(BookingStatus::Confirmed)
            ->create(['scheduled_date' => $this->day(2), 'scheduled_time' => $time]);
        app(BookingService::class)->assign($make('10:00'), $team, $admin);
        $second = $make('11:00');

        Livewire::test(ListBookings::class)
            ->callTableAction('assign', $second, data: ['team_id' => $team->id])
            ->assertNotified(Notification::make()->danger()->title('الفريق لديه زيارة في نفس الفترة.'));

        $this->assertSame(BookingStatus::Confirmed, $second->fresh()->status);
    }

    public function test_reject_requires_a_reason(): void
    {
        $this->actingAsAdmin();
        [, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create();

        Livewire::test(ViewBooking::class, ['record' => $booking->id])
            ->callAction('reject', data: ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);

        Livewire::test(ViewBooking::class, ['record' => $booking->id])
            ->callAction('reject', data: ['reason' => 'خارج منطقة الخدمة']);

        $this->assertSame(BookingStatus::Rejected, $booking->fresh()->status);
        $this->assertSame('خارج منطقة الخدمة', $booking->fresh()->cancel_reason);
    }

    public function test_support_can_view_but_not_act(): void
    {
        $this->actingAsAdmin(AdminRole::Support);
        [, $customer] = $this->customer();
        $booking = Booking::factory()->for($customer)->create();

        $this->get('/admin/bookings')->assertOk();
        Livewire::test(ListBookings::class)
            ->assertTableActionHidden('confirm', $booking)
            ->assertTableActionHidden('cancel', $booking);
    }

    public function test_guests_and_inactive_admins_are_kept_out(): void
    {
        $this->get('/admin/bookings')->assertRedirect('/admin/login');

        $admin = $this->actingAsAdmin();
        $admin->forceFill(['is_active' => false])->save(); // is_active غير قابل للإسناد الجماعي
        $this->get('/admin/bookings')->assertForbidden();
    }
}
