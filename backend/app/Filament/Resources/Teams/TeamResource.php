<?php

namespace App\Filament\Resources\Teams;

use App\Enums\WorkerStatus;
use App\Enums\WorkerType;
use App\Filament\Resources\Teams\Pages\CreateTeam;
use App\Filament\Resources\Teams\Pages\EditTeam;
use App\Filament\Resources\Teams\Pages\ListTeams;
use App\Filament\Support\GuardedByAbility;
use App\Models\Team;
use App\Models\Worker;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * فرق الزيارات (CR-3). الحفظ عبر TeamService:
 * الأعضاء من نوع "فريق الزيارات" فقط، العضو في فريق واحد، والقائد من الأعضاء.
 */
class TeamResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'staff';

    protected static ?string $model = Team::class;

    protected static ?string $modelLabel = 'فريق';

    protected static ?string $pluralModelLabel = 'فرق الزيارات';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_PEOPLE;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['leader.user', 'members.user'])->withCount('members');
    }

    /** أعضاء فريق الزيارات النشطون غير المرتبطين بفريق آخر. */
    public static function memberOptions(?Team $team): array
    {
        return Worker::with('user')
            ->where('type', WorkerType::Cleaner)
            ->where('status', WorkerStatus::Active)
            ->where(fn ($q) => $q->whereDoesntHave('teams')
                ->when($team, fn ($q) => $q->orWhereHas('teams', fn ($t) => $t->whereKey($team->id))))
            ->get()
            ->mapWithKeys(fn (Worker $w) => [$w->id => $w->user->name])
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الفريق')->columnSpanFull()->columns(2)->schema([
                TextInput::make('name')->label('اسم الفريق')->required()->maxLength(80)
                    ->unique(ignoreRecord: true)->placeholder('مثال: فريق أ'),
                Toggle::make('is_active')->label('مفعّل (يستقبل زيارات)')->default(true)->inline(false),
                Select::make('members')->label('الأعضاء')->multiple()->required()->native(false)->searchable()
                    ->options(fn (?Team $record) => self::memberOptions($record))
                    ->helperText('يظهر هنا فقط أعضاء "فريق الزيارات" النشطون غير المرتبطين بفريق آخر.')
                    ->live(),
                Select::make('leader_id')->label('قائد الفريق')->required()->native(false)
                    ->options(fn (Get $get, ?Team $record) => collect(self::memberOptions($record))
                        ->only(array_map('intval', (array) $get('members')))->all())
                    ->helperText('القائد يقبل الزيارات ويحدّث حالتها ويستلم المبلغ نقداً.'),
                Textarea::make('notes')->label('ملاحظات')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('الفريق')->weight('bold')->searchable(),
                TextColumn::make('leader.user.name')->label('القائد')->placeholder('بلا قائد'),
                TextColumn::make('members')->label('الأعضاء')
                    ->state(fn (Team $r) => $r->members->map(fn ($w) => $w->user->name)->implode('، '))
                    ->description(fn (Team $r) => $r->members_count.' أعضاء')->wrap(),
                IconColumn::make('is_active')->label('مفعّل')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('تعديل')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }
}
