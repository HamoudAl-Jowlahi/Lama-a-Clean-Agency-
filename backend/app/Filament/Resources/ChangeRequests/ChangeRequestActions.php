<?php

namespace App\Filament\Resources\ChangeRequests;

use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRequestType;
use App\Filament\Resources\Contracts\ContractActions;
use App\Filament\Support\Admin;
use App\Models\AdminUser;
use App\Models\ContractChangeRequest;
use App\Models\Worker;
use App\Services\ContractChangeService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;

/** معالجة طلبات العميل: استبدال الخادمة أو إنهاء العقد (عبر ContractChangeService). */
final class ChangeRequestActions
{
    /** @return list<Action> */
    public static function all(): array
    {
        return [self::review(), self::approveReplacement(), self::approveTermination(), self::reject()];
    }

    private static function service(): ContractChangeService
    {
        return app(ContractChangeService::class);
    }

    private static function open(?ChangeRequestType $type = null): \Closure
    {
        return fn (ContractChangeRequest $record) => Admin::can('contracts.manage')
            && in_array($record->status, [ChangeRequestStatus::Open, ChangeRequestStatus::UnderReview], true)
            && ($type === null || $record->type === $type);
    }

    private static function response(bool $required = false): Textarea
    {
        return Textarea::make('response')->label('رد للعميل')->required($required)->maxLength(1000)->rows(2);
    }

    public static function review(): Action
    {
        return Action::make('review')
            ->label('قيد المراجعة')->icon('heroicon-o-eye')->color('warning')
            ->visible(fn (ContractChangeRequest $record) => Admin::can('contracts.manage') && $record->status === ChangeRequestStatus::Open)
            ->action(fn (ContractChangeRequest $record, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->markUnderReview($record, $admin), 'تم تحويل الطلب إلى "قيد المراجعة"'));
    }

    /** "إخراج" الخادمة: تنتهي فترتها اليوم، والبديلة تبدأ في التاريخ المحدد. العقد يستمر. */
    public static function approveReplacement(): Action
    {
        return Action::make('approveReplacement')
            ->label('اعتماد وتعيين بديلة')->icon('heroicon-o-arrows-right-left')->color('success')
            ->schema([
                ContractActions::housekeeperSelect('worker_id', 'الخادمة البديلة'),
                DatePicker::make('start_on')->label('تاريخ بدء البديلة')->required()->native(false)
                    ->default(fn () => now(config('agency.timezone'))->addDay()->toDateString()),
                self::response(),
            ])
            ->modalDescription('تنتهي فترة الخادمة الحالية اليوم. الأيام حتى وصول البديلة لا تُحتسب على العميل (حسب الإعدادات).')
            ->visible(self::open(ChangeRequestType::ReplaceWorker))
            ->action(fn (ContractChangeRequest $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->approveReplacement(
                    $record, $admin, Worker::findOrFail($data['worker_id']), CarbonImmutable::parse($data['start_on']), $data['response'] ?? null,
                ), 'تم اعتماد الاستبدال'));
    }

    public static function approveTermination(): Action
    {
        return Action::make('approveTermination')
            ->label('اعتماد الإنهاء')->icon('heroicon-o-stop-circle')->color('danger')
            ->schema([self::response()])
            ->modalDescription(fn (ContractChangeRequest $record) => 'آخر يوم عمل: '.$record->requested_date?->format('Y-m-d').'. يُحسب المستحق بالأيام الفعلية حسب الإعدادات.')
            ->visible(self::open(ChangeRequestType::Terminate))
            ->action(fn (ContractChangeRequest $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->approveTermination($record, $admin, $data['response'] ?? null), 'تم إنهاء العقد'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('رفض الطلب')->icon('heroicon-o-x-circle')->color('gray')
            ->schema([self::response(required: true)])
            ->visible(self::open())
            ->action(fn (ContractChangeRequest $record, array $data, Action $action) => Admin::run($action,
                fn (AdminUser $admin) => self::service()->reject($record, $admin, $data['response']), 'تم رفض الطلب'));
    }
}
