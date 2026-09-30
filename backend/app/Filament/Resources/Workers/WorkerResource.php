<?php

namespace App\Filament\Resources\Workers;

use App\Enums\UserStatus;
use App\Enums\WorkerStatus;
use App\Enums\WorkerType;
use App\Filament\Resources\Workers\Pages\CreateWorker;
use App\Filament\Resources\Workers\Pages\EditWorker;
use App\Filament\Resources\Workers\Pages\ListWorkers;
use App\Filament\Support\Admin;
use App\Filament\Support\GuardedByAbility;
use App\Http\Requests\ApiRequest;
use App\Models\AdminUser;
use App\Models\Worker;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\StaffService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * الموظفون الميدانيون (CR-3): فريق الزيارات أو خادمة. الحفظ عبر StaffService.
 */
class WorkerResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'staff';

    protected static ?string $model = Worker::class;

    protected static ?string $modelLabel = 'موظف';

    protected static ?string $pluralModelLabel = 'الموظفون';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_PEOPLE;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'teams', 'currentContractAssignment.contract']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الحساب')->description('يدخل الموظف تطبيق لمعة برقم الجوال وكلمة المرور.')->columns(2)->schema([
                TextInput::make('name')->label('الاسم')->required()->maxLength(120),
                TextInput::make('phone')->label('رقم الجوال')->required()->tel()->maxLength(20)
                    ->regex(ApiRequest::PHONE_REGEX)
                    ->unique(table: 'users', column: 'phone', ignorable: fn (?Worker $record) => $record?->user),
                TextInput::make('email')->label('البريد (اختياري)')->email()->maxLength(190)
                    ->unique(table: 'users', column: 'email', ignorable: fn (?Worker $record) => $record?->user),
                TextInput::make('password')->label('كلمة المرور')->password()->revealable()->minLength(8)->maxLength(100)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'اتركها فارغة للإبقاء على الحالية.' : null),
            ]),
            Section::make('العمل')->columns(2)->schema([
                Select::make('type')->label('النوع')->options(WorkerType::class)->required()->native(false)
                    ->helperText('فريق الزيارات: زيارات فقط · خادمة: عقود فقط'),
                Select::make('status')->label('الحالة')->options(WorkerStatus::class)->required()->native(false)
                    ->default(WorkerStatus::Active->value),
                TextInput::make('national_id')->label('رقم الهوية (مشفّر)')->maxLength(30)
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'لا يُعرض المحفوظ. اتركه فارغاً للإبقاء عليه.' : null),
                Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('الاسم')->searchable()->weight('bold'),
                TextColumn::make('type')->label('النوع')->badge(),
                TextColumn::make('user.phone')->label('الجوال')->searchable(),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('work')->label('الفريق / العقد الحالي')
                    ->state(fn (Worker $r) => $r->isCleaner()
                        ? ($r->teams->first()?->name ?? 'بلا فريق').($r->teams->first()?->leader_id === $r->id ? ' — القائد' : '')
                        : ($r->currentContractAssignment?->contract->contract_number ?? 'متاحة لعقد')),
                TextColumn::make('user.status')->label('الحساب')->badge(),
            ])
            ->filters([
                SelectFilter::make('type')->label('النوع')->options(WorkerType::class),
                SelectFilter::make('status')->label('الحالة')->options(WorkerStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                Action::make('toggleAccount')
                    ->label(fn (Worker $record) => $record->user->status === UserStatus::Active ? 'إيقاف الحساب' : 'تفعيل الحساب')
                    ->icon(fn (Worker $record) => $record->user->status === UserStatus::Active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (Worker $record) => $record->user->status === UserStatus::Active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription('الحساب الموقوف لا يدخل التطبيق، وتُنهى جلساته الحالية.')
                    ->visible(fn () => Admin::can('staff.manage'))
                    ->action(fn (Worker $record, Action $action) => Admin::run($action, fn (AdminUser $admin) => app(StaffService::class)->setUserStatus(
                        $record->user,
                        $record->user->status === UserStatus::Active ? UserStatus::Suspended : UserStatus::Active,
                        $admin,
                    ), 'تم تحديث الحساب')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkers::route('/'),
            'create' => CreateWorker::route('/create'),
            'edit' => EditWorker::route('/{record}/edit'),
        ];
    }
}
