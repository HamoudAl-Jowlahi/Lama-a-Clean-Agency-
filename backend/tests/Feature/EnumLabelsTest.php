<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ContractStatus;
use Tests\TestCase;

class EnumLabelsTest extends TestCase
{
    /** كل حالة في كل Enum لها نص عربي وإنجليزي (لا مفاتيح ترجمة ناقصة). */
    public function test_every_enum_case_has_arabic_and_english_labels(): void
    {
        foreach (glob(app_path('Enums/*.php')) as $file) {
            $class = 'App\\Enums\\'.basename($file, '.php');
            if (! enum_exists($class)) {
                continue;
            }

            foreach (['ar', 'en'] as $locale) {
                app()->setLocale($locale);
                foreach ($class::cases() as $case) {
                    $key = 'enums.'.$class::LANG_KEY.'.'.$case->value;
                    $this->assertNotSame($key, $case->label(), "Missing [{$locale}] label for {$class}::{$case->name}");
                }
            }
        }
    }

    /** القيم النصية عقد مع تطبيق Flutter والتصاميم — أي تغيير هنا يكسر التطبيق. */
    public function test_status_values_match_the_api_contract(): void
    {
        $this->assertSame(
            ['pending', 'confirmed', 'assigned', 'on_the_way', 'in_progress', 'completed', 'cancelled', 'rejected'],
            BookingStatus::values(),
        );
        $this->assertSame(
            ['pending', 'confirmed', 'assigned', 'active', 'completed', 'terminated', 'cancelled', 'rejected'],
            ContractStatus::values(),
        );
    }

    public function test_default_locale_is_arabic(): void
    {
        $this->assertSame('ar', config('app.locale'));
        $this->assertSame('في الطريق', BookingStatus::OnTheWay->label());
    }
}
