<?php

namespace App\Filament\Resources\Ratings;

use App\Filament\Resources\Ratings\Pages\ListRatings;
use App\Filament\Support\Admin;
use App\Filament\Support\GuardedByAbility;
use App\Models\Rating;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\AuditLogger;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** التقييمات: الزيارة للفريق، والعقد للخادمة. الإدارة تستطيع إخفاء تعليق غير لائق. */
class RatingResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'ratings';

    protected static ?string $model = Rating::class;

    protected static ?string $modelLabel = 'تقييم';

    protected static ?string $pluralModelLabel = 'التقييمات';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CATALOG;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer.user', 'team', 'worker.user', 'booking', 'contract']);
    }

    public static function table(Table $table): Table
    {
        $stars = fn (?int $n) => $n ? str_repeat('★', $n).str_repeat('☆', 5 - $n) : '—';

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('reference')->label('الزيارة / العقد')->weight('bold')
                    ->state(fn (Rating $r) => $r->booking?->booking_number ?? $r->contract?->contract_number),
                TextColumn::make('customer.user.name')->label('العميل'),
                TextColumn::make('rated')->label('الفريق / الخادمة')
                    ->state(fn (Rating $r) => $r->team?->name ?? $r->worker?->user->name ?? '—'),
                TextColumn::make('service_score')->label('الخدمة')->formatStateUsing($stars)->color('primary'),
                TextColumn::make('worker_score')->label('الفريق / الخادمة')->formatStateUsing($stars)->color('primary')->placeholder('—'),
                TextColumn::make('comment')->label('التعليق')->wrap()->limit(80)->placeholder('—'),
                IconColumn::make('is_hidden')->label('مخفي')->boolean()->trueColor('danger')->falseColor('gray'),
            ])
            ->filters([
                Filter::make('low')->label('3 نجوم وأقل')->query(fn (Builder $q) => $q->where('service_score', '<=', 3)),
                TernaryFilter::make('is_hidden')->label('مخفي'),
            ])
            ->recordActions([
                Action::make('toggleHidden')
                    ->label(fn (Rating $record) => $record->is_hidden ? 'إظهار' : 'إخفاء')
                    ->icon(fn (Rating $record) => $record->is_hidden ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn () => Admin::can('ratings.manage'))
                    ->action(function (Rating $record) {
                        $record->update(['is_hidden' => ! $record->is_hidden]);
                        AuditLogger::log(Admin::user(), $record->is_hidden ? 'rating.hidden' : 'rating.shown', $record);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListRatings::route('/')];
    }
}
