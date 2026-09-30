<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Contracts\ContractResource;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Support\Admin;
use App\Filament\Support\Format;
use App\Filament\Support\GuardedByAbility;
use App\Models\AdminUser;
use App\Models\Payment;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\CollectionService;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * التحصيل النقدي (CR-1): الاستحقاقات تُنشأ تلقائياً عند إتمام الزيارة أو نهاية فترة العقد،
 * والإدارة تسجّل التحصيل أو الإعفاء.
 */
class PaymentResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'payments';

    protected static ?string $model = Payment::class;

    protected static ?string $modelLabel = 'استحقاق';

    protected static ?string $pluralModelLabel = 'التحصيل النقدي';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CATALOG;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Payment::due()->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['booking.customer.user', 'contract.customer.user', 'collectedBy']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'desc')
            ->columns([
                TextColumn::make('reference')->label('المرجع')->weight('bold')
                    ->state(fn (Payment $r) => $r->booking?->booking_number ?? $r->contract?->contract_number)
                    ->url(fn (Payment $r) => $r->booking_id
                        ? BookingResource::getUrl('view', ['record' => $r->booking_id])
                        : ContractResource::getUrl('view', ['record' => $r->contract_id])),
                TextColumn::make('customer')->label('العميل')
                    ->state(fn (Payment $r) => ($r->booking ?? $r->contract)?->customer->user->name),
                TextColumn::make('kind')->label('النوع')
                    ->state(fn (Payment $r) => $r->booking_id ? 'زيارة' : 'عقد'),
                TextColumn::make('period_start')->label('الفترة')->placeholder('—')
                    ->formatStateUsing(fn (Payment $r) => $r->period_start ? $r->period_start->format('Y-m-d').' → '.$r->period_end?->format('Y-m-d') : null),
                TextColumn::make('due_date')->label('تاريخ الاستحقاق')->date('Y-m-d')->sortable(),
                TextColumn::make('amount')->label('المبلغ')->weight('bold')
                    ->formatStateUsing(fn ($state, Payment $r) => Format::money($state, $r->currency)),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('collectedBy.name')->label('سجّلها')->placeholder('—')
                    ->description(fn (Payment $r) => $r->collected_at?->format('Y-m-d H:i')),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(PaymentStatus::class)->default(PaymentStatus::Due->value),
                Filter::make('overdue')->label('متأخر')
                    ->query(fn (Builder $q) => $q->due()->whereDate('due_date', '<', now(config('agency.timezone'))
                        ->subDays((int) Settings::get('payments.overdue_after_days'))->toDateString())),
            ])
            ->recordActions([
                Action::make('collect')
                    ->label('تسجيل التحصيل')->icon('heroicon-o-check-circle')->color('success')->button()
                    ->schema([Textarea::make('notes')->label('ملاحظة')->maxLength(255)->rows(2)])
                    ->modalDescription(fn (Payment $record) => 'المبلغ: '.Format::money($record->amount, $record->currency))
                    ->visible(fn (Payment $record) => Admin::can('payments.manage') && $record->status === PaymentStatus::Due)
                    ->action(fn (Payment $record, array $data, Action $action) => Admin::run($action,
                        fn (AdminUser $admin) => app(CollectionService::class)->collect($record, $admin, $data['notes'] ?? null), 'تم تسجيل التحصيل')),
                Action::make('waive')
                    ->label('إعفاء')->icon('heroicon-o-minus-circle')->color('gray')
                    ->schema([Textarea::make('reason')->label('سبب الإعفاء')->required()->maxLength(255)->rows(2)])
                    ->visible(fn (Payment $record) => Admin::can('payments.manage') && $record->status === PaymentStatus::Due)
                    ->action(fn (Payment $record, array $data, Action $action) => Admin::run($action,
                        fn (AdminUser $admin) => app(CollectionService::class)->waive($record, $admin, $data['reason']), 'تم الإعفاء')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/')];
    }
}
