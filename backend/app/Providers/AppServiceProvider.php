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
use App\Models\Team;
use App\Models\User;
use App\Models\Worker;
use App\Observers\CatalogAuditObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
            'team' => Team::class,
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

        // آخر دخول لمستخدمي الإدارة (يظهر في قسم مستخدمي الإدارة)
        Event::listen(Login::class, function (Login $event) {
            if ($event->guard === 'admin' && $event->user instanceof AdminUser) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });

        // تعديلات الكتالوج من لوحة الإدارة → Audit Log
        foreach ([Service::class, ServicePrice::class, ContractPlan::class] as $model) {
            $model::observe(CatalogAuditObserver::class);
        }

        // في التطوير: خطأ عند lazy loading أو حقل غير موجود بدل التجاهل الصامت
        Model::shouldBeStrict(! $this->app->isProduction());

        // الدخول والتسجيل: 5 محاولات في الدقيقة لكل رقم جوال + IP (ضد التخمين)
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('phone')).'|'.$request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
