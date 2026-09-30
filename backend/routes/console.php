<?php

use App\Services\ContractService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| المهمة اليومية للعقود: تفعيل العقود التي وصل تاريخ بدئها، إنهاء المنتهية،
| وإنشاء الاستحقاقات النقدية للفترات المنتهية. آمنة للتكرار.
| في الخادم: cron كل دقيقة → php artisan schedule:run
*/
Artisan::command('lamaa:contracts-daily', function (ContractService $contracts) {
    $stats = $contracts->runDaily();
    $this->info("activated={$stats['activated']} completed={$stats['completed']} payments={$stats['payments']} reminders={$stats['reminders']}");
})->purpose('Activate/complete contracts and create due cash payments');

Schedule::command('lamaa:contracts-daily')
    ->dailyAt('00:10')
    ->timezone(config('agency.timezone'))
    ->withoutOverlapping()
    ->onOneServer();
