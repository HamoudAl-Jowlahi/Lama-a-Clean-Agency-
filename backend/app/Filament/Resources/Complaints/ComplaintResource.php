<?php

namespace App\Filament\Resources\Complaints;

use App\Enums\ComplaintMessageKind;
use App\Enums\ComplaintStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Filament\Resources\Contracts\ContractResource;
use App\Filament\Support\Format;
use App\Filament\Support\GuardedByAbility;
use App\Models\Complaint;
use App\Models\ComplaintMessage;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** الشكاوى وسجلها — الرد للعميل، والملاحظات الداخلية للإدارة فقط. */
class ComplaintResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'complaints';

    protected static ?string $model = Complaint::class;

    protected static ?string $modelLabel = 'شكوى';

    protected static ?string $pluralModelLabel = 'الشكاوى';

    protected static ?string $recordTitleAttribute = 'complaint_number';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_OPERATIONS;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 4;

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
        $count = Complaint::where('status', ComplaintStatus::Open)->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer.user', 'booking', 'contract', 'assignedAdmin']);
    }

    public static function subjectNumber(Complaint $c): ?string
    {
        return $c->booking?->booking_number ?? $c->contract?->contract_number;
    }

    public static function subjectUrl(Complaint $c): string
    {
        return $c->booking_id
            ? BookingResource::getUrl('view', ['record' => $c->booking_id])
            : ContractResource::getUrl('view', ['record' => $c->contract_id]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('complaint_number')->label('الشكوى')->searchable()->weight('bold')
                    ->description(fn (Complaint $r) => $r->created_at->diffForHumans()),
                TextColumn::make('type')->label('النوع')->formatStateUsing(fn ($state) => __('api.complaint_types.'.$state)),
                TextColumn::make('customer.user.name')->label('العميل')->searchable(),
                TextColumn::make('subject')->label('مرتبطة بـ')
                    ->state(fn (Complaint $r) => self::subjectNumber($r))
                    ->url(fn (Complaint $r) => self::subjectUrl($r)),
                TextColumn::make('description')->label('الوصف')->limit(60)->wrap(),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('assignedAdmin.name')->label('المسؤول')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options(ComplaintStatus::class)->multiple(),
                SelectFilter::make('type')->label('النوع')
                    ->options(fn () => collect(config('agency.complaint_types'))->mapWithKeys(fn ($t) => [$t => __('api.complaint_types.'.$t)])),
            ])
            ->recordActions([ViewAction::make()->label('فتح')]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الشكوى')->columnSpanFull()->columns(4)->schema([
                TextEntry::make('complaint_number')->label('رقم الشكوى')->weight('bold'),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('type')->label('النوع')->formatStateUsing(fn ($state) => __('api.complaint_types.'.$state)),
                TextEntry::make('subject')->label('مرتبطة بـ')
                    ->state(fn (Complaint $record) => self::subjectNumber($record))
                    ->url(fn (Complaint $record) => self::subjectUrl($record)),
                TextEntry::make('customer.user.name')->label('العميل'),
                TextEntry::make('customer.user.phone')->label('الجوال'),
                TextEntry::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i'),
                TextEntry::make('assignedAdmin.name')->label('المسؤول')->placeholder('—'),
            ]),
            Section::make('المحادثة')->columnSpanFull()->description('الملاحظات الداخلية لا تظهر للعميل.')->schema([
                RepeatableEntry::make('messages')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('sender_type')->label('من')->badge()->color(fn (ComplaintMessage $record) => match (true) {
                        $record->kind === ComplaintMessageKind::InternalNote => 'warning',
                        $record->sender_type->value === 'customer' => 'gray',
                        default => 'primary',
                    }),
                    TextEntry::make('kind')->label('النوع'),
                    TextEntry::make('body')->label('النص')->columnSpan(1)
                        ->state(fn (ComplaintMessage $record) => $record->kind === ComplaintMessageKind::StatusChange
                            ? Format::enumLabel(ComplaintStatus::class, $record->meta['from'] ?? null).' ← '.Format::enumLabel(ComplaintStatus::class, $record->meta['to'] ?? null)
                            : $record->body),
                    TextEntry::make('created_at')->label('الوقت')->dateTime('Y-m-d H:i'),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplaints::route('/'),
            'view' => ViewComplaint::route('/{record}'),
        ];
    }
}
