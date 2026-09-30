<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Support\Admin;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** سجل العمليات الإدارية — للقراءة فقط. */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $modelLabel = 'عملية';

    protected static ?string $pluralModelLabel = 'سجل العمليات';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_SYSTEM;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return Admin::can('audit.view');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('admin');
    }

    public static function table(Table $table): Table
    {
        $json = fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('الوقت')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('admin.name')->label('المستخدم')->placeholder('النظام'),
                TextColumn::make('action')->label('العملية')->badge()->color('gray')->searchable(),
                TextColumn::make('subject')->label('السجل')
                    ->state(fn (AuditLog $r) => $r->auditable_type ? $r->auditable_type.' #'.$r->auditable_id : '—'),
                TextColumn::make('old_values')->label('قبل')->formatStateUsing($json)->limit(60)->placeholder('—')->fontFamily('mono'),
                TextColumn::make('new_values')->label('بعد')->formatStateUsing($json)->limit(60)->placeholder('—')->fontFamily('mono'),
                TextColumn::make('ip')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('admin_user_id')->label('المستخدم')->options(fn () => AdminUser::pluck('name', 'id')),
                SelectFilter::make('auditable_type')->label('نوع السجل')
                    ->options(fn () => AuditLog::query()->distinct()->whereNotNull('auditable_type')->pluck('auditable_type', 'auditable_type')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}
