<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\BookingActions;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Support\Admin;
use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** زيارات تحتاج إجراءً الآن: مراجعة أو إسناد لفريق. */
class NeedsActionBookings extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'زيارات تحتاج إجراء';

    public static function canView(): bool
    {
        return Admin::can('bookings.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Booking::query()
                ->with(['customer.user', 'items'])
                ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])
                ->orderBy('scheduled_date')->orderBy('scheduled_time'))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('لا زيارات تنتظر إجراءً')
            ->columns([
                TextColumn::make('booking_number')->label('رقم الطلب')->weight('bold')
                    ->url(fn (Booking $r) => BookingResource::getUrl('view', ['record' => $r])),
                TextColumn::make('customer.user.name')->label('العميل'),
                TextColumn::make('service')->label('الخدمة')
                    ->state(fn (Booking $r) => $r->items->pluck('service_name_snapshot')->implode('، ')),
                TextColumn::make('scheduled_date')->label('الموعد')->date('Y-m-d')
                    ->description(fn (Booking $r) => substr((string) $r->scheduled_time, 0, 5)),
                TextColumn::make('status')->label('الحالة')->badge(),
            ])
            ->recordActions([BookingActions::confirm(), BookingActions::assign()]);
    }
}
