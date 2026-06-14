<?php

namespace Minishop;

use Filament\Http\Middleware\Authenticate;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Minishop\Filament\Pages\ManageStoreSettings;
use Minishop\Filament\Resources\ActivityLogResource;
use Minishop\Filament\Resources\CategoryResource;
use Minishop\Filament\Resources\CouponResource;
use Minishop\Filament\Resources\CustomerResource;
use Minishop\Filament\Resources\OrderResource;
use Minishop\Filament\Resources\OrderReturnResource;
use Minishop\Filament\Resources\ProductResource;
use Minishop\Filament\Resources\ShippingMethodResource;
use Minishop\Filament\Resources\TagResource;
use Minishop\Filament\Resources\TaxZoneResource;
use Minishop\Filament\Resources\UserResource;

class MinishopPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('minishop')
            ->path(config('minishop.panel_path', 'dashboard'))
            ->login()
            ->authGuard('web')
            ->darkMode(true)
            ->resources([
                CategoryResource::class,
                TagResource::class,
                ProductResource::class,
                ShippingMethodResource::class,
                TaxZoneResource::class,
                CouponResource::class,
                UserResource::class,
                CustomerResource::class,
                OrderResource::class,
                OrderReturnResource::class,
                ActivityLogResource::class,
            ])
            ->pages([
                ManageStoreSettings::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
