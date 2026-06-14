<?php

namespace Minishop\Tests;

use Barryvdh\DomPDF\ServiceProvider as DomPDFServiceProvider;
use Filament\FilamentServiceProvider;
use Laravel\Ai\AiServiceProvider;
use Laravel\Fortify\Features;
use Laravel\Fortify\FortifyServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use Livewire\LivewireServiceProvider;
use Minishop\MinishopPanelProvider;
use Minishop\MinishopServiceProvider;
use Minishop\Models\User;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function defineDatabaseMigrations(): void
    {
        if (! static::usesRefreshDatabaseTestingConcern()) {
            return;
        }

        $this->loadMigrationsFrom(\Orchestra\Testbench\default_migration_path());
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            AiServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            SanctumServiceProvider::class,
            FortifyServiceProvider::class,
            PermissionServiceProvider::class,
            DomPDFServiceProvider::class,
            MinishopServiceProvider::class,
            MinishopPanelProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        app('router')->aliasMiddleware('role', RoleMiddleware::class);
        app('router')->aliasMiddleware('permission', PermissionMiddleware::class);
        app('router')->aliasMiddleware('role_or_permission', RoleOrPermissionMiddleware::class);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('minishop.load_storefront_routes', true);

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);

        $app['config']->set('permission.guard_name', 'web');

        $app['config']->set('view.paths', [
            __DIR__.'/fixtures/views',
            resource_path('views'),
        ]);

        $app['config']->set('fortify.guard', 'web');
        $app['config']->set('fortify.features', [
            Features::registration(),
            Features::resetPasswords(),
            Features::emailVerification(),
            Features::updateProfileInformation(),
            Features::updatePasswords(),
        ]);
    }
}
