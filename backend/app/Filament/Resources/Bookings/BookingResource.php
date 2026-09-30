<?php

namespace App\Filament\Resources\Bookings;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Support\Format;
use App\Filament\Support\GuardedByAbility;
use App\Models\Booking;
use App\Models\Team;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** الزيارات: المراجعة، الإسناد لفريق، المتابعة. العملاء ينشئونها من التطبيق. */
class BookingResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'bookings';

    protected static ?string $model = Booking::class;

    protected static ?string $modelLabel = 'زيارة';

    protected static ?string $pluralModelLabel = 'الزيارات';

    protected static ?string $recordTitleAttribute = 'booking_number';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_OPERATIONS;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false; // الزيارات تُنشأ من تطبيق العميل
    }

    public static function canEdit($record): bool
    {
        return false; // التغيير عبر الإجراءات فقط (State Machine)
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Booking::where('status', BookingStatus::Pending)->count();

        return $count ?: null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer.user', 'items', 'activeAssignment.team', 'payment']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('booking_number')->label('رقم الطلب')->searchable()->weight('bold'),
                TextColumn::make('customer.user.name')->label('العميل')->searchable()
                    ->description(fn (Booking $r) => $r->address_snapshot['district'] ?? null),
                TextColumn::make('service')->label('الخدمة')
                    ->state(fn (Booking $r) => $r->items->pluck('service_name_snapshot')->implode('، ')),
                TextColumn::make('scheduled_date')->label('الموعد')->date('Y-m-d')->sortable()
                    ->description(fn (Booking $r) => substr((string) $r->scheduled_time, 0, 5)),
                TextColumn::make('activeAssignment.team.name')->label('الفريق')->placeholder('—'),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('total')->label('المبلغ')->formatStateUsing(fn ($state, Booking $r) => Format::money($state, $r->currency)),
                TextColumn::make('payment.status')->label('الدفع')->badge()->placeholder('نقداً عند الإتمام'),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(BookingStatus::class)->multiple(),
                SelectFilter::make('team')->label('الفريق')
                    ->options(fn () => Team::orderBy('name')->pluck('name', 'id'))
                    ->query(fn (Builder $q, array $data) => $q->when($data['value'], fn ($q, $team) => $q->whereHas(
                        'assignments', fn ($a) => $a->where('team_id', $team)->whereIn('status', ['pending', 'accepted'])))),
                Filter::make('today')->label('مواعيد اليوم')
                    ->query(fn (Builder $q) => $q->whereDate('scheduled_date', now(config('agency.timezone'))->toDateString())),
                Filter::make('needs_action')->label('يحتاج إجراء')
                    ->query(fn (Builder $q) => $q->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed])),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                ActionGroup::make(BookingActions::all()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('الطلب')->columnSpan(2)->columns(2)->schema([
                    TextEntry::make('booking_number')->label('رقم الطلب')->weight('bold'),
                    TextEntry::make('status')->label('الحالة')->badge(),
                    TextEntry::make('scheduled_date')->label('التاريخ')->date('Y-m-d'),
                    TextEntry::make('scheduled_time')->label('الوقت')->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),
                    TextEntry::make('customer.user.name')->label('العميل'),
                    TextEntry::make('customer.user.phone')->label('الجوال'),
                    TextEntry::make('address_snapshot')->label('العنوان')->columnSpanFull()
                        ->formatStateUsing(fn (Booking $record) => Format::address($record->address_snapshot)),
                    TextEntry::make('customer_notes')->label('ملاحظات العميل')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('cancel_reason')->label('سبب الإلغاء/الرفض')->placeholder('—')->columnSpanFull()
                        ->visible(fn (Booking $record) => $record->cancel_reason !== null),
                ]),
                Section::make('المبلغ والفريق')->columnSpan(1)->schema([
                    TextEntry::make('total')->label('الإجمالي')->weight('bold')
                        ->formatStateUsing(fn ($state, Booking $record) => Format::money($state, $record->currency)),
                    TextEntry::make('payment.status')->label('الدفع (نقداً)')->badge()->placeholder('يُستحق عند الإتمام'),
                    TextEntry::make('activeAssignment.team.name')->label('الفريق')->placeholder('لم يُسند بعد'),
                    TextEntry::make('activeAssignment.status')->label('رد القائد')->badge()->placeholder('—'),
                ]),
            ]),
            Section::make('الخدمات')->columnSpanFull()->schema([
                RepeatableEntry::make('items')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('service_name_snapshot')->label('الخدمة'),
                    TextEntry::make('price_label_snapshot')->label('الخيار'),
                    TextEntry::make('quantity')->label('الكمية'),
                    TextEntry::make('line_total')->label('المجموع')->formatStateUsing(fn ($state) => Format::money($state)),
                ]),
            ]),
            Section::make('السجل الزمني')->columnSpanFull()->collapsible()->schema([
                RepeatableEntry::make('statusLogs')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('to_status')->label('الحالة')->badge()
                        ->formatStateUsing(fn ($state) => Format::enumLabel(BookingStatus::class, $state))
                        ->color(fn ($state) => Format::enumColor(BookingStatus::class, $state)),
                    TextEntry::make('actor_type')->label('بواسطة'),
                    TextEntry::make('note')->label('ملاحظة')->placeholder('—'),
                    TextEntry::make('created_at')->label('الوقت')->dateTime('Y-m-d H:i'),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
    }
}
