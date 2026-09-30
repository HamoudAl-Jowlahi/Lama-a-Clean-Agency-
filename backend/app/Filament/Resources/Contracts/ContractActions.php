<?php

namespace App\Filament\Resources\Contracts;

use App\Enums\ContractStatus;
use App\Enums\WorkerStatus;
use App\Enums\WorkerType;
use App\Filament\Support\Admin;
use App\Models\AdminUser;
use App\Models\Contract;
use App\Models\Worker;
use App\Services\ContractService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

/** إجراءات الإدارة على العقد — عبر ContractService و ContractStateMachine. */
final class ContractActions
{
    /** @return list<Action> */
    public static function all(): array
    {
        return [self::confirm(), self::assign(), self::reject(), self::withdraw(), self::terminate(), self::cancel()];
    }

    private static function service(): ContractService
    {
        return app(ContractService::class);
    }

    private static function when(ContractStatus ...$statuses): \Closure
    {
        return fn (Contract $record) => Admin::can('contracts.manage') && in_array($record->status, $statuses, true);
    }

    private static function reason(string $label = 'السبب'): Textarea
    {
        return Textarea::make('reason')->label($label)->required()->maxLength(255)->rows(2);
    }

    /** الخادمات النشطات فقط (CR-3) — التعارض في الفترة يُفحص في الخادم. */
    public static function housekeeperSelect(string $name = 'worker_id', string $label = 'الخادمة'): Select
    {
        return Select::make($name)
            ->label($label)
            ->required()
            ->native(false)
            ->searchable()
            ->options(fn () => Worker::with('user')
                ->where('type', WorkerType::Housekeeper)
                ->where('status', WorkerStatus::Active)
                ->get()
                ->mapWithKeys(fn (Worker $w) => [$w->id => $w->user->name.' — '.$w->user->phone]));
    }

    public static function confirm(): Action
    {
        return Action::make('confirm')
            ->label('تأكيد العقد')->icon('heroicon-o-check-circle')->color('primary')
            ->requiresConfirmation()
            ->visible(self::when(ContractStatus::Pending))
            ->action(fn (Contract $record, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->confirm($record, $admin), 'تم تأكيد العقد'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رفض العقد')->icon('heroicon-o-x-circle')->color('danger')
            ->schema([self::reason('سبب الرفض (يظهر للعميل)')])
            ->visible(self::when(ContractStatus::Pending))
            ->action(fn (Contract $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->reject($record, $admin, $data['reason']), 'تم رفض العقد'));
    }

    public static function assign(): Action
    {
        return Action::make('assign')
            ->label('إسناد خادمة')->icon('heroicon-o-user-plus')->color('primary')
            ->schema([self::housekeeperSelect()])
            ->modalDescription('تُسند لكامل فترة العقد. إن كان تاريخ البدء اليوم أو فات، يُفعّل العقد مباشرة.')
            ->visible(self::when(ContractStatus::Confirmed))
            ->action(fn (Contract $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->assign($record, Worker::findOrFail($data['worker_id']), $admin), 'تم إسناد الخادمة'));
    }

    public static function withdraw(): Action
    {
        return Action::make('withdraw')
            ->label('سحب الإسناد')->icon('heroicon-o-arrow-uturn-right')->color('gray')
            ->schema([self::reason()])
            ->visible(self::when(ContractStatus::Assigned))
            ->action(fn (Contract $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->withdrawAssignment($record, $admin, $data['reason']), 'تم سحب الإسناد'));
    }

    public static function terminate(): Action
    {
        return Action::make('terminate')
            ->label('إنهاء مبكر')->icon('heroicon-o-stop-circle')->color('danger')
            ->schema([
                DatePicker::make('last_day')->label('آخر يوم عمل')->required()->native(false)
                    ->default(fn () => now(config('agency.timezone'))->toDateString()),
                self::reason('سبب الإنهاء'),
            ])
            ->modalDescription('يُحسب المستحق عن الأيام الفعلية حسب الإعدادات، ويُنشأ بعد آخر يوم عمل.')
            ->visible(self::when(ContractStatus::Active))
            ->action(fn (Contract $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->terminate($record, $admin, CarbonImmutable::parse($data['last_day']), $data['reason']), 'تم إنهاء العقد'));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('إلغاء العقد')->icon('heroicon-o-no-symbol')->color('danger')
            ->schema([self::reason('سبب الإلغاء')])
            ->visible(self::when(ContractStatus::Pending, ContractStatus::Confirmed, ContractStatus::Assigned))
            ->action(fn (Contract $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->cancelByAdmin($record, $admin, $data['reason']), 'تم إلغاء العقد'));
    }
}
