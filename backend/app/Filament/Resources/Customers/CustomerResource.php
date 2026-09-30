<?php

namespace App\Filament\Resources\Customers;

use App\Enums\UserStatus;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Support\Admin;
use App\Filament\Support\Format;
use App\Filament\Support\GuardedByAbility;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\StaffService;
use Filament\Actions\Action;
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

/** العملاء — يسجلون من التطبيق؛ الإدارة تعرض وتوقف/تفعّل فقط. */
class CustomerResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'customers';

    protected static ?string $model = Customer::class;

    protected static ?string $modelLabel = 'عميل';

    protected static ?string $pluralModelLabel = 'العملاء';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_PEOPLE;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

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
        return parent::getEloquentQuery()->with('user')->withCount(['bookings', 'contracts', 'complaints']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('العميل')->searchable()->weight('bold'),
                TextColumn::make('user.phone')->label('الجوال')->searchable(),
                TextColumn::make('bookings_count')->label('الزيارات')->sortable(),
                TextColumn::make('contracts_count')->label('العقود')->sortable(),
                TextColumn::make('complaints_count')->label('الشكاوى'),
                TextColumn::make('user.status')->label('الحساب')->badge(),
                TextColumn::make('created_at')->label('منذ')->date('Y-m-d'),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحساب')->options(UserStatus::class)
                    ->query(fn (Builder $q, array $data) => $q->when($data['value'], fn ($q, $v) => $q->whereHas('user', fn ($u) => $u->where('status', $v)))),
            ])
            ->recordActions([
                ViewAction::make()->label('عرض'),
                self::toggleAccount(),
            ]);
    }

    public static function toggleAccount(): Action
    {
        return Action::make('toggleAccount')
            ->label(fn (Customer $record) => $record->user->status === UserStatus::Active ? 'إيقاف الحساب' : 'تفعيل الحساب')
            ->icon(fn (Customer $record) => $record->user->status === UserStatus::Active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
            ->color(fn (Customer $record) => $record->user->status === UserStatus::Active ? 'danger' : 'success')
            ->requiresConfirmation()
            ->modalDescription('الحساب الموقوف لا يدخل التطبيق، وتُنهى جلساته الحالية.')
            ->visible(fn () => Admin::can('customers.manage'))
            ->action(fn (Customer $record, Action $action) => Admin::run($action, fn (AdminUser $admin) => app(StaffService::class)->setUserStatus(
                $record->user,
                $record->user->status === UserStatus::Active ? UserStatus::Suspended : UserStatus::Active,
                $admin,
            ), 'تم تحديث الحساب'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('العميل')->columnSpanFull()->columns(4)->schema([
                TextEntry::make('user.name')->label('الاسم')->weight('bold'),
                TextEntry::make('user.phone')->label('الجوال'),
                TextEntry::make('user.email')->label('البريد')->placeholder('—'),
                TextEntry::make('user.status')->label('الحساب')->badge(),
            ]),
            Grid::make(2)->columnSpanFull()->schema([
                Section::make('آخر الزيارات')->schema([
                    RepeatableEntry::make('bookings')->hiddenLabel()->columns(3)->placeholder('لا زيارات')->schema([
                        TextEntry::make('booking_number')->label('الرقم'),
                        TextEntry::make('scheduled_date')->label('الموعد')->date('Y-m-d'),
                        TextEntry::make('status')->label('الحالة')->badge(),
                    ]),
                ]),
                Section::make('العقود')->schema([
                    RepeatableEntry::make('contracts')->hiddenLabel()->columns(3)->placeholder('لا عقود')->schema([
                        TextEntry::make('contract_number')->label('الرقم'),
                        TextEntry::make('start_date')->label('البدء')->date('Y-m-d'),
                        TextEntry::make('status')->label('الحالة')->badge(),
                    ]),
                ]),
            ]),
            Section::make('العناوين')->columnSpanFull()->schema([
                RepeatableEntry::make('addresses')->hiddenLabel()->columns(2)->placeholder('لا عناوين')->schema([
                    TextEntry::make('label')->label('الاسم'),
                    TextEntry::make('district')->label('العنوان')
                        ->formatStateUsing(fn ($record) => Format::address($record->toSnapshot())),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
