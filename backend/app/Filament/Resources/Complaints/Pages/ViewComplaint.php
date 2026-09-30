<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Support\Admin;
use App\Models\AdminUser;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

/** صفحة الشكوى: الرد على العميل، ملاحظة داخلية، وتغيير الحالة (عبر ComplaintService). */
class ViewComplaint extends ViewRecord
{
    protected static string $resource = ComplaintResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load('messages');
    }

    protected function getHeaderActions(): array
    {
        $manage = fn () => Admin::can('complaints.manage');

        return [
            Action::make('reply')
                ->label('رد / ملاحظة')->icon('heroicon-o-chat-bubble-left')->color('primary')
                ->schema([
                    Textarea::make('body')->label('النص')->required()->maxLength(2000)->rows(4),
                    Toggle::make('internal')->label('ملاحظة داخلية (لا تظهر للعميل)'),
                ])
                ->visible($manage)
                ->action(function (Complaint $record, array $data, Action $action) {
                    Admin::run($action, fn (AdminUser $admin) => app(ComplaintService::class)
                        ->reply($record, $admin, $data['body'], (bool) ($data['internal'] ?? false)), 'تم الحفظ');
                    $this->refreshRecord();
                }),
            Action::make('status')
                ->label('تغيير الحالة')->icon('heroicon-o-arrow-path')->color('gray')
                ->schema([
                    Select::make('status')->label('الحالة الجديدة')->required()->native(false)
                        ->options(fn (Complaint $record) => collect(ComplaintStatus::cases())
                            ->reject(fn (ComplaintStatus $s) => $s === $record->status)
                            ->mapWithKeys(fn (ComplaintStatus $s) => [$s->value => $s->label()])),
                ])
                ->visible(fn (Complaint $record) => $manage() && $record->status !== ComplaintStatus::Closed)
                ->action(function (Complaint $record, array $data, Action $action) {
                    Admin::run($action, fn (AdminUser $admin) => app(ComplaintService::class)
                        ->changeStatus($record, $admin, ComplaintStatus::from($data['status'])), 'تم تغيير الحالة');
                    $this->refreshRecord();
                }),
        ];
    }

    private function refreshRecord(): void
    {
        $this->record = $this->record->fresh(['customer.user', 'booking', 'contract', 'assignedAdmin', 'messages']);
    }
}
