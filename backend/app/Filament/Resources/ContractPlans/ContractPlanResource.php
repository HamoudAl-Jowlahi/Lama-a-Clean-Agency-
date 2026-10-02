<?php

namespace App\Filament\Resources\ContractPlans;

use App\Filament\Resources\ContractPlans\Pages\CreateContractPlan;
use App\Filament\Resources\ContractPlans\Pages\EditContractPlan;
use App\Filament\Resources\ContractPlans\Pages\ListContractPlans;
use App\Filament\Support\Format;
use App\Filament\Support\GuardedByAbility;
use App\Models\ContractPlan;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** باقات عقود الخادمات. العقد القائم يحتفظ بنسخة الباقة وقت التعاقد (plan_snapshot). */
class ContractPlanResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'catalog';

    protected static ?string $model = ContractPlan::class;

    protected static ?string $modelLabel = 'باقة عقد';

    protected static ?string $pluralModelLabel = 'باقات العقود';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CATALOG;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الباقة')->columnSpanFull()->columns(3)->schema([
                TextInput::make('name_ar')->label('الاسم (عربي)')->required()->maxLength(120),
                TextInput::make('name_en')->label('الاسم (إنجليزي)')->maxLength(120),
                TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
                TextInput::make('work_days_per_week')->label('أيام العمل أسبوعياً')->numeric()->required()->minValue(1)->maxValue(7),
                TextInput::make('hours_per_day')->label('ساعات اليوم')->numeric()->required()->minValue(1)->maxValue(24),
                TextInput::make('monthly_price')->label('السعر الشهري')->numeric()->required()->minValue(0),
                Select::make('currency')->label('العملة')->required()->native(false)
                    ->options(fn () => [config('agency.currency') => config('agency.currency')])
                    ->default(fn () => config('agency.currency')),
                TextInput::make('min_months')->label('أقل مدة (شهر)')->numeric()->required()->minValue(1)->maxValue(24)->default(1),
                TextInput::make('max_months')->label('أقصى مدة (شهر)')->numeric()->required()->minValue(1)->maxValue(24)->default(12)
                    ->gte('min_months'),
                Textarea::make('description_ar')->label('الوصف (عربي)')->rows(2)->columnSpan(2),
                Textarea::make('description_en')->label('الوصف (إنجليزي)')->rows(2)->columnSpan(2),
                Toggle::make('is_active')->label('مفعّلة في التطبيق')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name_ar')->label('الباقة')->weight('bold'),
                TextColumn::make('work_days_per_week')->label('الأيام')->suffix(' أيام'),
                TextColumn::make('hours_per_day')->label('الساعات')->suffix(' ساعات'),
                TextColumn::make('monthly_price')->label('الشهري')
                    ->formatStateUsing(fn ($state, ContractPlan $r) => Format::money($state, $r->currency)),
                TextColumn::make('max_months')->label('المدة')
                    ->formatStateUsing(fn (ContractPlan $r) => $r->min_months.'–'.$r->max_months.' شهراً'),
                IconColumn::make('is_active')->label('مفعّلة')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('تعديل')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractPlans::route('/'),
            'create' => CreateContractPlan::route('/create'),
            'edit' => EditContractPlan::route('/{record}/edit'),
        ];
    }
}
