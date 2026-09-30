<?php

namespace App\Filament\Resources\AdminUsers;

use App\Enums\AdminRole;
use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Filament\Support\Admin;
use App\Models\AdminUser;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** مستخدمو لوحة الإدارة وأدوارهم — للمدير العام فقط. */
class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;

    protected static ?string $modelLabel = 'مستخدم إدارة';

    protected static ?string $pluralModelLabel = 'مستخدمو الإدارة';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_SYSTEM;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Admin::can('admins.manage');
    }

    public static function canCreate(): bool
    {
        return Admin::can('admins.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Admin::can('admins.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return false; // الإيقاف بدل الحذف — سجل العمليات يشير إليهم
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(2)->schema([
                TextInput::make('name')->label('الاسم')->required()->maxLength(120),
                TextInput::make('email')->label('البريد')->email()->required()->maxLength(190)->unique(ignoreRecord: true),
                Select::make('role')->label('الدور')->options(AdminRole::class)->required()->native(false)
                    ->helperText('مدير العمليات: التشغيل اليومي · خدمة العملاء: الشكاوى والتقييمات والعرض فقط'),
                TextInput::make('password')->label('كلمة المرور')->password()->revealable()->minLength(10)->maxLength(100)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'اتركها فارغة للإبقاء على الحالية.' : null),
                Toggle::make('is_active')->label('الحساب مفعّل')->default(true)->inline(false)
                    // لا يوقف المدير نفسه
                    ->disabled(fn (?AdminUser $record) => $record?->is(Admin::user())),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->weight('bold'),
                TextColumn::make('email')->label('البريد'),
                TextColumn::make('role')->label('الدور')->badge(),
                IconColumn::make('is_active')->label('مفعّل')->boolean(),
                TextColumn::make('last_login_at')->label('آخر دخول')->since()->placeholder('—'),
            ])
            ->recordActions([EditAction::make()->label('تعديل')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminUsers::route('/'),
            'create' => CreateAdminUser::route('/create'),
            'edit' => EditAdminUser::route('/{record}/edit'),
        ];
    }
}
