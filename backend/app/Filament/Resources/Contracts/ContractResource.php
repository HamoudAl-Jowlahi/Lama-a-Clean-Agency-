<?php

namespace App\Filament\Resources\Contracts;

use App\Enums\ContractStatus;
use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Filament\Resources\Contracts\Pages\ViewContract;
use App\Filament\Support\Format;
use App\Filament\Support\GuardedByAbility;
use App\Models\Contract;
use App\Models\ContractPlan;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** عقود استئجار الخادمات (CR-2). */
class ContractResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'contracts';

    protected static ?string $model = Contract::class;

    protected static ?string $modelLabel = 'عقد';

    protected static ?string $pluralModelLabel = 'العقود';

    protected static ?string $recordTitleAttribute = 'contract_number';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_OPERATIONS;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

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
        $count = Contract::where('status', ContractStatus::Pending)->count();

        return $count ?: null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer.user', 'currentAssignment.worker.user']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('contract_number')->label('رقم العقد')->searchable()->weight('bold'),
                TextColumn::make('customer.user.name')->label('العميل')->searchable()
                    ->description(fn (Contract $r) => $r->address_snapshot['district'] ?? null),
                TextColumn::make('plan_snapshot.name_ar')->label('الباقة')
                    ->description(fn (Contract $r) => $r->months.' '.($r->months === 1 ? 'شهر' : 'أشهر')),
                TextColumn::make('start_date')->label('الفترة')->date('Y-m-d')->sortable()
                    ->description(fn (Contract $r) => 'حتى '.$r->end_date->format('Y-m-d')),
                TextColumn::make('currentAssignment.worker.user.name')->label('الخادمة الحالية')->placeholder('—'),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('monthly_price')->label('الشهري')
                    ->formatStateUsing(fn ($state, Contract $r) => Format::money($state, $r->currency)),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(ContractStatus::class)->multiple(),
                SelectFilter::make('plan_id')->label('الباقة')->options(fn () => ContractPlan::pluck('name_ar', 'id')),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                ActionGroup::make(ContractActions::all()),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make('العقد')->columnSpan(2)->columns(2)->schema([
                    TextEntry::make('contract_number')->label('رقم العقد')->weight('bold'),
                    TextEntry::make('status')->label('الحالة')->badge(),
                    TextEntry::make('plan_snapshot.name_ar')->label('الباقة')
                        ->formatStateUsing(fn (Contract $record) => ($record->plan_snapshot['name_ar'] ?? '').' — '
                            .($record->plan_snapshot['work_days_per_week'] ?? '?').' أيام × '.($record->plan_snapshot['hours_per_day'] ?? '?').' ساعات'),
                    TextEntry::make('start_date')->label('الفترة')
                        ->formatStateUsing(fn (Contract $record) => $record->start_date->format('Y-m-d').' — '.$record->end_date->format('Y-m-d')),
                    TextEntry::make('customer.user.name')->label('العميل'),
                    TextEntry::make('customer.user.phone')->label('الجوال'),
                    TextEntry::make('address_snapshot')->label('العنوان')->columnSpanFull()
                        ->formatStateUsing(fn (Contract $record) => Format::address($record->address_snapshot)),
                    TextEntry::make('customer_notes')->label('ملاحظات العميل')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('termination_reason')->label('سبب الإنهاء')->columnSpanFull()
                        ->visible(fn (Contract $record) => $record->termination_reason !== null),
                ]),
                Section::make('المبالغ')->columnSpan(1)->schema([
                    TextEntry::make('monthly_price')->label('الشهري')->formatStateUsing(fn ($state, Contract $record) => Format::money($state, $record->currency)),
                    TextEntry::make('total_amount')->label('الإجمالي')->weight('bold')->formatStateUsing(fn ($state, Contract $record) => Format::money($state, $record->currency)),
                    TextEntry::make('currentAssignment.worker.user.name')->label('الخادمة الحالية')->placeholder('—'),
                    TextEntry::make('terms_version')->label('إصدار الشروط'),
                ]),
            ]),
            Section::make('الخادمات في العقد')->columnSpanFull()->schema([
                RepeatableEntry::make('assignments')->hiddenLabel()->columns(4)->placeholder('لا توجد إسنادات بعد')->schema([
                    TextEntry::make('worker.user.name')->label('الخادمة'),
                    TextEntry::make('started_on')->label('من')->date('Y-m-d'),
                    TextEntry::make('ended_on')->label('إلى')->date('Y-m-d')->placeholder('حالياً'),
                    TextEntry::make('end_reason')->label('سبب الانتهاء')->badge()->color('gray')->placeholder('—'),
                ]),
            ]),
            Grid::make(2)->columnSpanFull()->schema([
                Section::make('الاستحقاقات النقدية')->schema([
                    RepeatableEntry::make('payments')->hiddenLabel()->columns(3)->placeholder('لا استحقاقات بعد')->schema([
                        TextEntry::make('period_start')->label('الفترة')
                            ->formatStateUsing(fn ($record) => $record->period_start?->format('m-d').' → '.$record->period_end?->format('m-d')),
                        TextEntry::make('amount')->label('المبلغ')->formatStateUsing(fn ($state) => Format::money($state)),
                        TextEntry::make('status')->label('الحالة')->badge(),
                    ]),
                ]),
                Section::make('طلبات الاستبدال والإنهاء')->schema([
                    RepeatableEntry::make('changeRequests')->hiddenLabel()->columns(3)->placeholder('لا توجد طلبات')->schema([
                        TextEntry::make('request_number')->label('الطلب'),
                        TextEntry::make('type')->label('النوع')->badge(),
                        TextEntry::make('status')->label('الحالة')->badge(),
                    ]),
                ]),
            ]),
            Section::make('السجل الزمني')->columnSpanFull()->collapsible()->collapsed()->schema([
                RepeatableEntry::make('statusLogs')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('to_status')->label('الحالة')->badge()
                        ->formatStateUsing(fn ($state) => Format::enumLabel(ContractStatus::class, $state))
                        ->color(fn ($state) => Format::enumColor(ContractStatus::class, $state)),
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
            'index' => ListContracts::route('/'),
            'view' => ViewContract::route('/{record}'),
        ];
    }
}
