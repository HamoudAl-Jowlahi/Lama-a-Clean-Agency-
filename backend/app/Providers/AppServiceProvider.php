<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\ContractChangeRequest;
use App\Models\ContractPlan;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // أسماء ثابتة في أعمدة الـ morph (status_logs, audit_logs, ...) بدل أسماء الكلاسات
        Relation::enforceMorphMap([
            'user' => User::class,
            'admin_user' => AdminUser::class,
            'customer' => Customer::class,
            'worker' => Worker::class,
            'booking' => Booking::class,
            'contract' => Contract::class,
            'change_request' => ContractChangeRequest::class,
            'complaint' => Complaint::class,
            'payment' => Payment::class,
            'rating' => Rating::class,
            'service' => Service::class,
            'service_price' => ServicePrice::class,
            'contract_plan' => ContractPlan::class,
        ]);

        // في التطوير: خطأ عند lazy loading أو حقل غير موجود بدل التجاهل الصامت
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
