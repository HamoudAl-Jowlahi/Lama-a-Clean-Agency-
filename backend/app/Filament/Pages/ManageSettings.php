<?php

namespace App\Filament\Pages;

use App\Filament\Support\Admin;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\NotificationRouter;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\Facades\Lang;
use App\Support\AuditLogger;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * إعدادات الوكالة (القرارات التجارية). كل حقل هنا مفتاح في config/agency.php،
 * والقيمة المحفوظة تتجاوزه عبر App\Support\Settings. العملة والمنطقة الزمنية في .env.
 */
class ManageSettings extends Page
{
    /** المفاتيح القابلة للتعديل من اللوحة (والنموذج يستخدم نفس الأسماء بـ "__" بدل "."). */
    public const KEYS = [
        'tax_rate', 'working_hours', 'slot_minutes', 'min_booking_lead_hours',
        'booking.customer_cancel_until_status', 'booking.customer_cancel_hours_before',
        'booking.worker_can_reject_assignment', 'booking.show_customer_phone_from_status',
        'contracts.terms_version', 'contracts.min_start_lead_days', 'contracts.min_months', 'contracts.max_months',
        'contracts.payment_schedule', 'contracts.early_termination_calc', 'contracts.replacement_sla_days',
        'contracts.max_replacements', 'contracts.deduct_waiting_days', 'contracts.ending_reminder_days',
        'payments.overdue_after_days',
    ];

    /** أسماء أحداث الإشعارات كما تظهر للإدارة. */
    public const EVENT_LABELS = [
        'booking.created' => 'زيارة جديدة', 'booking.confirmed' => 'تأكيد زيارة', 'booking.rejected' => 'رفض زيارة',
        'booking.assigned' => 'إسناد زيارة لفريق', 'booking.unassigned' => 'سحب الإسناد', 'booking.declined' => 'رفض الفريق للزيارة',
        'booking.on_the_way' => 'الفريق في الطريق', 'booking.in_progress' => 'بدء التنظيف', 'booking.completed' => 'اكتمال الزيارة',
        'booking.cancelled' => 'إلغاء زيارة', 'contract.created' => 'طلب عقد جديد', 'contract.confirmed' => 'تأكيد عقد',
        'contract.rejected' => 'رفض عقد', 'contract.assigned' => 'تعيين خادمة', 'contract.active' => 'بدء العقد',
        'contract.completed' => 'انتهاء العقد', 'contract.terminated' => 'إنهاء مبكر', 'contract.cancelled' => 'إلغاء عقد',
        'contract.ending_soon' => 'قرب نهاية العقد', 'contract.worker_changed' => 'تعيين خادمة بديلة',
        'contract.worker_released' => 'انتهاء فترة الخادمة السابقة', 'change_request.submitted' => 'طلب استبدال/إنهاء جديد',
        'change_request.rejected' => 'رفض طلب الاستبدال/الإنهاء', 'complaint.created' => 'شكوى جديدة',
        'complaint.customer_message' => 'رد العميل على شكوى', 'complaint.replied' => 'رد الإدارة على شكوى',
        'complaint.status_changed' => 'تغيير حالة شكوى', 'payment.due' => 'دفعة عقد مستحقة',
    ];

    private const AUDIENCES = ['customer' => 'العميل', 'staff' => 'الفريق / الخادمة', 'admins' => 'لوحة الإدارة'];

    protected static ?string $title = 'الإعدادات';

    protected static ?string $navigationLabel = 'الإعدادات';

    protected static string|\UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_SYSTEM;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Admin::can('settings.manage');
    }

    public function mount(): void
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[self::field($key)] = Settings::get($key);
        }
        $values['working_hours_start'] = $values['working_hours']['start'] ?? null;
        $values['working_hours_end'] = $values['working_hours']['end'] ?? null;

        foreach (app(NotificationRouter::class)->matrix() as $event => $audiences) {
            $values['notify'][self::eventField($event)] = array_keys(array_filter($audiences));
        }

        $this->form->fill($values);
    }

    private static function field(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    private static function eventField(string $event): string
    {
        return str_replace('.', '_', $event);
    }

    /** مستلمو الحدث الممكنون = من لهم نص معرّف في lang/notifications. */
    private static function audienceOptions(string $event): array
    {
        return collect(self::AUDIENCES)
            ->filter(fn ($label, $audience) => Lang::has("notifications.{$event}.{$audience}.title"))
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        $flow = ['pending' => 'قيد المراجعة', 'confirmed' => 'مؤكد', 'assigned' => 'تم الإسناد'];

        return $schema->statePath('data')->components([
            Section::make('عام')->columns(3)->schema([
                TextEntry::make('currency')->label('العملة')->state(config('agency.currency').' (من ملف .env)'),
                TextEntry::make('timezone')->label('المنطقة الزمنية')->state(config('agency.timezone').' (من ملف .env)'),
                TextInput::make('tax_rate')->label('نسبة الضريبة %')->numeric()->minValue(0)->maxValue(100)->required(),
            ]),
            Section::make('مواعيد الزيارات')->columns(4)->schema([
                TimePicker::make('working_hours_start')->label('بداية العمل')->seconds(false)->required(),
                TimePicker::make('working_hours_end')->label('نهاية العمل')->seconds(false)->required()->after('working_hours_start'),
                TextInput::make('slot_minutes')->label('طول الفترة (دقيقة)')->numeric()->minValue(30)->maxValue(480)->required(),
                TextInput::make('min_booking_lead_hours')->label('أقل مهلة للحجز (ساعة)')->numeric()->minValue(0)->maxValue(168)->required(),
            ]),
            Section::make('سياسات الزيارات')->columns(2)->schema([
                Select::make('booking__customer_cancel_until_status')->label('يلغي العميل حتى حالة')->options($flow)->required()->native(false),
                TextInput::make('booking__customer_cancel_hours_before')->label('مهلة الإلغاء قبل الموعد (ساعة)')->numeric()->minValue(0)->required(),
                Select::make('booking__show_customer_phone_from_status')->label('يظهر هاتف العميل لقائد الفريق من حالة')->native(false)->required()
                    ->options(['assigned' => 'تم الإسناد', 'on_the_way' => 'في الطريق', 'in_progress' => 'جارٍ التنفيذ']),
                Toggle::make('booking__worker_can_reject_assignment')->label('يسمح لقائد الفريق برفض الإسناد')->inline(false),
            ]),
            Section::make('العقود')->columns(3)->schema([
                TextInput::make('contracts__terms_version')->label('إصدار شروط العقد')->required()->maxLength(20)
                    ->helperText('تغييره يلزم العملاء بالموافقة على الشروط الجديدة عند طلب عقد.'),
                TextInput::make('contracts__min_start_lead_days')->label('أقرب بدء بعد (يوم)')->numeric()->minValue(0)->required(),
                TextInput::make('contracts__ending_reminder_days')->label('تذكير قبل النهاية (يوم)')->numeric()->minValue(0)->required(),
                TextInput::make('contracts__min_months')->label('أقل مدة (شهر)')->numeric()->minValue(1)->maxValue(24)->required(),
                TextInput::make('contracts__max_months')->label('أقصى مدة (شهر)')->numeric()->minValue(1)->maxValue(24)->required()->gte('contracts__min_months'),
                Select::make('contracts__payment_schedule')->label('موعد الدفع النقدي')->native(false)->required()
                    ->options(['monthly' => 'نهاية كل شهر', 'end_of_contract' => 'نهاية العقد']),
                Select::make('contracts__early_termination_calc')->label('احتساب الإنهاء المبكر')->native(false)->required()
                    ->options(['actual_days' => 'بالأيام الفعلية', 'full_month' => 'الشهر كاملاً']),
                TextInput::make('contracts__replacement_sla_days')->label('مهلة إرسال البديلة (يوم)')->numeric()->minValue(0)->required(),
                TextInput::make('contracts__max_replacements')->label('أقصى عدد استبدالات')->numeric()->minValue(0)->required()->helperText('0 = بلا حد'),
                Toggle::make('contracts__deduct_waiting_days')->label('لا تُحتسب أيام انتظار البديلة على العميل')->inline(false),
            ]),
            Section::make('التحصيل')->columns(3)->schema([
                TextInput::make('payments__overdue_after_days')->label('يعتبر متأخراً بعد (يوم)')->numeric()->minValue(0)->required(),
            ]),
            Section::make('الإشعارات')
                ->description('من يستلم إشعاراً عند كل حدث. العميل يستطيع أيضاً إيقاف إشعارات الجوال من إعدادات التطبيق.')
                ->collapsible()->collapsed()
                ->columns(3)
                ->schema(collect(self::EVENT_LABELS)->map(fn (string $label, string $event) => CheckboxList::make('notify.'.self::eventField($event))
                    ->label($label)
                    ->options(self::audienceOptions($event)))->values()->all()),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('حفظ الإعدادات')->submit('save')->keyBindings(['mod+s'])]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $state['working_hours'] = ['start' => substr($state['working_hours_start'], 0, 5), 'end' => substr($state['working_hours_end'], 0, 5)];

        $old = [];
        $new = [];
        foreach (self::KEYS as $key) {
            $value = $state[self::field($key)] ?? $state[$key] ?? null;
            $value = is_numeric($value) && ! is_bool($value) ? $value + 0 : $value; // "15" → 15
            $current = Settings::get($key);

            if ($value != $current || gettype($value) !== gettype($current)) {
                $old[$key] = $current;
                $new[$key] = $value;
                Settings::set($key, $value, Admin::user());
            }
        }

        // مصفوفة الإشعارات
        $router = app(NotificationRouter::class);
        $currentMatrix = $router->matrix();
        $matrix = $currentMatrix;
        foreach (self::EVENT_LABELS as $event => $label) {
            $selected = (array) ($state['notify'][self::eventField($event)] ?? []);
            foreach (array_keys(self::audienceOptions($event)) as $audience) {
                $matrix[$event][$audience] = in_array($audience, $selected, true);
            }
        }
        if ($matrix !== $currentMatrix) {
            $old['notifications'] = $currentMatrix;
            $new['notifications'] = $matrix;
            Settings::set('notifications', $matrix, Admin::user());
        }

        if ($new) {
            AuditLogger::log(Admin::user(), 'settings.updated', null, $old, $new);
        }

        Notification::make()->success()->title($new ? 'تم حفظ الإعدادات' : 'لا تغييرات')->send();
    }
}
