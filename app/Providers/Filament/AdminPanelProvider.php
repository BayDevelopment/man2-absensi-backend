<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\CustomRequestPasswordReset;
use App\Filament\Pages\Auth\CustomResetPassword;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset(
                requestAction: CustomRequestPasswordReset::class,
                resetAction: CustomResetPassword::class,
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('poppins')
            ->brandName(new HtmlString('
                <span style="font-style: italic; font-weight: 400; color: #f97316;">
                    E-ABSENSI
                </span>
                <span class="italic font-semibold ml-1 text-black dark:text-white" style="font-style: italic;">
                    Panel
                </span>
            '))
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn() => new HtmlString('
                <div style="text-align:center; margin-top:20px; font-size:12px; color:#6b7280;">
                    Developed by <strong>Bayu Albar Ladici</strong>
                </div>
            ')
            )
            ->renderHook(
                PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER,
                fn() => new HtmlString('
                <div style="text-align:center; margin-top:20px; font-size:12px; color:#6b7280;">
                    Developed by <strong>Bayu Albar Ladici</strong>
                </div>
            ')
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): string => sprintf(
                    '<script type="module" src="%s"></script>',
                    Vite::asset('resources/js/filament-face-api.js')
                ),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
