<?php

namespace App\Filament\Resources\Bookings;

use App\Enums\BookingStatus;
use App\Filament\Support\Admin;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Team;
use App\Services\BookingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

/**
 * إجراءات الإدارة على الزيارة — كل إجراء يظهر فقط في الحالة المسموحة له،
 * وينفذ عبر BookingService (نفس قواعد الـ API والـ State Machine).
 */
final class BookingActions
{
    /** @return list<Action> */
    public static function all(): array
    {
        return [
            self::confirm(), self::assign(), self::reject(),
            self::withdraw(), self::complete(), self::cancel(),
        ];
    }

    private static function service(): BookingService
    {
        return app(BookingService::class);
    }

    private static function when(BookingStatus ...$statuses): \Closure
    {
        return fn (Booking $record) => Admin::can('bookings.manage') && in_array($record->status, $statuses, true);
    }

    private static function reason(string $label = 'السبب'): Textarea
    {
        return Textarea::make('reason')->label($label)->required()->maxLength(255)->rows(2);
    }

    public static function confirm(): Action
    {
        return Action::make('confirm')
            ->label('تأكيد الطلب')
            ->icon('heroicon-o-check-circle')
            ->color('primary')
            ->requiresConfirmation()
            ->visible(self::when(BookingStatus::Pending))
            ->action(fn (Booking $record, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->confirm($record, $admin), 'تم تأكيد الطلب'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رفض الطلب')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->schema([self::reason('سبب الرفض (يظهر للعميل)')])
            ->visible(self::when(BookingStatus::Pending))
            ->action(fn (Booking $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->reject($record, $admin, $data['reason']), 'تم رفض الطلب'));
    }

    /** CR-3: الإسناد لفريق كامل. الفرق المشغولة تُرفض في الخادم مع رسالة واضحة. */
    public static function assign(): Action
    {
        return Action::make('assign')
            ->label('إسناد لفريق')
            ->icon('heroicon-o-user-group')
            ->color('primary')
            ->schema([
                Select::make('team_id')
                    ->label('الفريق')
                    ->required()
                    ->native(false)
                    ->options(fn () => Team::available()->with('leader.user')->withCount('members')->orderBy('name')->get()
                        ->mapWithKeys(fn (Team $t) => [$t->id => "{$t->name} — القائد: {$t->leader?->user->name} · {$t->members_count} أعضاء"])),
            ])
            ->modalDescription('القائد يستلم الطلب في التطبيق ويقبله أو يرفضه.')
            ->visible(self::when(BookingStatus::Confirmed))
            ->action(fn (Booking $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->assign($record, Team::findOrFail($data['team_id']), $admin), 'تم إسناد الطلب للفريق'));
    }

    public static function withdraw(): Action
    {
        return Action::make('withdraw')
            ->label('سحب الإسناد')
            ->icon('heroicon-o-arrow-uturn-right')
            ->color('gray')
            ->schema([self::reason()])
            ->visible(self::when(BookingStatus::Assigned))
            ->action(fn (Booking $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->withdrawAssignment($record, $admin, $data['reason']), 'تم سحب الإسناد — الطلب عاد إلى "مؤكد"'));
    }

    public static function complete(): Action
    {
        return Action::make('complete')
            ->label('تعليم كمكتمل')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('يُنشأ استحقاق نقدي بمبلغ الطلب.')
            ->visible(self::when(BookingStatus::InProgress))
            ->action(fn (Booking $record, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->completeByAdmin($record, $admin), 'تم إكمال الطلب'));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('إلغاء الطلب')
            ->icon('heroicon-o-no-symbol')
            ->color('danger')
            ->schema([self::reason('سبب الإلغاء')])
            ->visible(self::when(BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Assigned, BookingStatus::OnTheWay, BookingStatus::InProgress))
            ->action(fn (Booking $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->cancelByAdmin($record, $admin, $data['reason']), 'تم إلغاء الطلب'));
    }
}
