<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * لوحة إدارة لمعة — /admin
 * Guard منفصل (admin → admin_users)، عربية RTL، هوية Design System (أزرق واحد، خط IBM Plex Sans Arabic).
 * كل الإجراءات تستدعي نفس الـ Services التي يستخدمها الـ API.
 */
class AdminPanelProvider extends PanelProvider
{
    public const GROUP_OPERATIONS = 'العمليات';

    public const GROUP_PEOPLE = 'الأشخاص';

    public const GROUP_CATALOG = 'الخدمات والمالية';

    public const GROUP_SYSTEM = 'النظام';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('admin')
            ->login()
            ->profile(isSimple: false)
            ->brandName('لمعة')
            ->brandLogo(fn () => new HtmlString(
                '<span style="display:inline-flex;align-items:center;gap:.5rem;font-weight:700;font-size:1.25rem;color:#1A499F">'
                .'<img src="'.asset('images/lamaa-mark.svg').'" alt="" style="height:2rem;width:2rem">لمعة</span>'
            ))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/lamaa-mark.svg'))
            ->colors([
                'primary' => '#1F5AC4', // blue-600 من Design System
            ])
            ->font('IBM Plex Sans Arabic')
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()               // جرس إشعارات الإدارة (طلب جديد، استبدال، شكوى...)
            ->databaseNotificationsPolling('30s')
            ->navigationGroups([
                self::GROUP_OPERATIONS,
                self::GROUP_PEOPLE,
                self::GROUP_CATALOG,
                self::GROUP_SYSTEM,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
