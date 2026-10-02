<?php

namespace App\Filament\Resources\Services;

use App\Enums\PriceUnit;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Filament\Support\GuardedByAbility;
use App\Models\Service;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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
use Illuminate\Database\Eloquent\Builder;

/**
 * الخدمات وأسعارها. تعديل السعر لا يؤثر على الطلبات السابقة (السعر محفوظ داخل كل طلب).
 */
class ServiceResource extends Resource
{
    use GuardedByAbility;

    public const ABILITY = 'catalog';

    protected static ?string $model = Service::class;

    protected static ?string $modelLabel = 'خدمة';

    protected static ?string $pluralModelLabel = 'الخدمات والأسعار';

    protected static ?string $recordTitleAttribute = 'name_ar';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CATALOG;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('prices');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('الخدمة')->columnSpanFull()->columns(2)->schema([
                TextInput::make('name_ar')->label('الاسم (عربي)')->required()->maxLength(120),
                TextInput::make('name_en')->label('الاسم (إنجليزي)')->maxLength(120),
                Textarea::make('description_ar')->label('الوصف (عربي)')->rows(2),
                Textarea::make('description_en')->label('الوصف (إنجليزي)')->rows(2),
                TextInput::make('duration_minutes')->label('المدة التقريبية (دقيقة)')->numeric()->minValue(15)->maxValue(1440),
                TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
                TextInput::make('icon')->label('الأيقونة')->maxLength(40)->helperText('اسم أيقونة من Design System (مثل home2، sofa).'),
                Toggle::make('is_active')->label('مفعّلة في التطبيق')->default(true)->inline(false),
            ]),
            Section::make('الأسعار')->columnSpanFull()->schema([
                Repeater::make('prices')->relationship()->hiddenLabel()->columns(5)->defaultItems(1)
                    ->addActionLabel('إضافة سعر')
                    ->schema([
                        TextInput::make('label_ar')->label('الخيار (عربي)')->required()->maxLength(120)->placeholder('شقة حتى 3 غرف'),
                        TextInput::make('label_en')->label('الخيار (إنجليزي)')->maxLength(120)->placeholder('Apartment up to 3 rooms'),
                        Select::make('unit')->label('الوحدة')->options(PriceUnit::class)->required()->native(false)
                            ->default(PriceUnit::Fixed->value),
                        TextInput::make('amount')->label('السعر')->numeric()->required()->minValue(0),
                        Select::make('currency')->label('العملة')->required()->native(false)
                            ->options(fn () => [config('agency.currency') => config('agency.currency')])
                            ->default(fn () => config('agency.currency')),
                        DatePicker::make('effective_from')->label('ساري من')->native(false),
                        DatePicker::make('effective_to')->label('حتى')->native(false),
                        Toggle::make('is_active')->label('مفعّل')->default(true)->inline(false),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name_ar')->label('الخدمة')->weight('bold')->searchable(),
                TextColumn::make('prices')->label('الأسعار')->wrap()
                    ->state(fn (Service $r) => $r->prices->map(fn ($p) => $p->label_ar.': '.number_format((float) $p->amount, 2))->implode(' · ')),
                TextColumn::make('duration_minutes')->label('المدة')->suffix(' د')->placeholder('—'),
                IconColumn::make('is_active')->label('مفعّلة')->boolean(),
            ])
            ->recordActions([EditAction::make()->label('تعديل')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
