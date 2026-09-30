<?php

namespace App\Filament\Resources\ChangeRequests;

use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRequestType;
use App\Filament\Resources\ChangeRequests\Pages\ListChangeRequests;
use App\Filament\Resources\ChangeRequests\Pages\ViewChangeRequest;
use App\Filament\Resources\Contracts\ContractResource;
use App\Filament\Support\GuardedByAbility;
use App\Models\ContractChangeRequest;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** طلبات العملاء على العقود: استبدال الخادمة أو الإنهاء المبكر. */
class ChangeRequestResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'contracts';

    protected static ?string $model = ContractChangeRequest::class;

    protected static ?string $modelLabel = 'طلب استبدال/إنهاء';

    protected static ?string $pluralModelLabel = 'طلبات الاستبدال والإنهاء';

    protected static ?string $recordTitleAttribute = 'request_number';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_OPERATIONS;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

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
        $count = ContractChangeRequest::whereIn('status', [ChangeRequestStatus::Open, ChangeRequestStatus::UnderReview])->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['contract.currentAssignment.worker.user', 'customer.user'])->withCount('attachments');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('request_number')->label('الطلب')->searchable()->weight('bold')
                    ->description(fn (ContractChangeRequest $r) => $r->created_at->diffForHumans()),
                TextColumn::make('type')->label('النوع')->badge(),
                TextColumn::make('contract.contract_number')->label('العقد')
                    ->url(fn (ContractChangeRequest $r) => ContractResource::getUrl('view', ['record' => $r->contract_id])),
                TextColumn::make('customer.user.name')->label('العميل')->searchable(),
                TextColumn::make('contract.currentAssignment.worker.user.name')->label('الخادمة الحالية')->placeholder('—'),
                TextColumn::make('reason_type')->label('السبب')->formatStateUsing(fn ($state) => __('api.change_request_reasons.'.$state)),
                TextColumn::make('requested_date')->label('تاريخ الإنهاء')->date('Y-m-d')->placeholder('—'),
                TextColumn::make('status')->label('الحالة')->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(ChangeRequestStatus::class)
                    ->default(null),
                SelectFilter::make('type')->label('النوع')->options(ChangeRequestType::class),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                ActionGroup::make(ChangeRequestActions::all()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الطلب')->columnSpanFull()->columns(3)->schema([
                TextEntry::make('request_number')->label('رقم الطلب')->weight('bold'),
                TextEntry::make('type')->label('النوع')->badge(),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('contract.contract_number')->label('العقد')
                    ->url(fn (ContractChangeRequest $record) => ContractResource::getUrl('view', ['record' => $record->contract_id])),
                TextEntry::make('customer.user.name')->label('العميل'),
                TextEntry::make('contract.currentAssignment.worker.user.name')->label('الخادمة الحالية')->placeholder('—'),
                TextEntry::make('reason_type')->label('السبب')->formatStateUsing(fn ($state) => __('api.change_request_reasons.'.$state)),
                TextEntry::make('requested_date')->label('تاريخ الإنهاء المطلوب')->date('Y-m-d')->placeholder('—'),
                TextEntry::make('attachments_count')->label('المرفقات'),
                TextEntry::make('details')->label('التفاصيل')->placeholder('—')->columnSpanFull(),
                TextEntry::make('admin_response')->label('رد الإدارة')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChangeRequests::route('/'),
            'view' => ViewChangeRequest::route('/{record}'),
        ];
    }
}
