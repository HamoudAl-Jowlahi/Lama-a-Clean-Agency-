<?php

namespace Database\Seeders;

use App\Enums\PriceUnit;
use App\Models\ContractPlan;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * الخدمات والأسعار وباقات العقود المبدئية. الأسعار أمثلة — تعدّلها الإدارة من اللوحة.
 * آمن للتشغيل أكثر من مرة (updateOrCreate).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $currency = config('agency.currency');

        $services = [
            ['تنظيف شامل للمنزل', 'Full home cleaning', 'home2', 240,
                'تنظيف كامل للغرف والمطبخ ودورات المياه، يشمل الأرضيات والأسطح.',
                'Complete cleaning of rooms, kitchen and bathrooms, including floors and surfaces.', [
                    ['شقة حتى 3 غرف', 'Apartment up to 3 rooms', PriceUnit::Fixed, 250],
                    ['فيلا / أكثر من 3 غرف', 'Villa / more than 3 rooms', PriceUnit::Fixed, 400],
                ]],
            ['الكنب والسجاد', 'Sofas & carpets', 'sofa', 150,
                'تنظيف الكنب والسجاد بالبخار.',
                'Steam cleaning for sofas and carpets.', [
                    ['كنب — للمقعد', 'Sofa — per seat', PriceUnit::Piece, 25],
                    ['سجاد — للمتر', 'Carpet — per m²', PriceUnit::SquareMeter, 12],
                ]],
            ['النوافذ والواجهات', 'Windows', 'window', 120,
                'تنظيف النوافذ من الداخل والخارج.',
                'Window cleaning inside and out.', [
                    ['حتى 10 نوافذ', 'Up to 10 windows', PriceUnit::Fixed, 120],
                ]],
            ['تنظيف المكاتب', 'Office cleaning', 'building', 180,
                'تنظيف المكاتب والمساحات التجارية.',
                'Cleaning for offices and commercial spaces.', [
                    ['بالساعة', 'Per hour', PriceUnit::Hour, 75],
                ]],
        ];

        foreach ($services as $i => [$ar, $en, $icon, $minutes, $desc, $descEn, $prices]) {
            $service = Service::updateOrCreate(['name_ar' => $ar], [
                'name_en' => $en,
                'icon' => $icon,
                'duration_minutes' => $minutes,
                'description_ar' => $desc,
                'description_en' => $descEn,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);

            foreach ($prices as [$label, $labelEn, $unit, $amount]) {
                $service->prices()->updateOrCreate(['label_ar' => $label], [
                    'label_en' => $labelEn,
                    'unit' => $unit,
                    'amount' => $amount,
                    'currency' => $currency,
                    'is_active' => true,
                ]);
            }
        }

        $plans = [
            ['دوام كامل', 'Full-time', 6, 8, 1800, 12],
            ['نصف يوم', 'Half-day', 6, 4, 1200, 12],
            ['دوام جزئي', 'Part-time', 3, 6, 1000, 6],
        ];

        foreach ($plans as $i => [$ar, $en, $days, $hours, $price, $max]) {
            ContractPlan::updateOrCreate(['name_ar' => $ar], [
                'name_en' => $en,
                'description_ar' => "{$days} أيام أسبوعياً، {$hours} ساعات يومياً",
                'description_en' => "{$days} days a week, {$hours} hours a day",
                'work_days_per_week' => $days,
                'hours_per_day' => $hours,
                'monthly_price' => $price,
                'currency' => $currency,
                'min_months' => 1,
                'max_months' => $max,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
