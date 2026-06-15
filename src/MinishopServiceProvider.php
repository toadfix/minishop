<?php

namespace Minishop;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;
use Minishop\Actions\Fortify\CreateNewUser;
use Minishop\Console\Commands\InstallCommand;
use Minishop\Http\Responses\LoginResponse;
use Minishop\Http\Responses\RegisterResponse;
use Minishop\Livewire\AddressForm;
use Minishop\Livewire\AddToCart;
use Minishop\Livewire\CartBadge;
use Minishop\Livewire\CartPage;
use Minishop\Livewire\Checkout;
use Minishop\Livewire\ProductList;
use Minishop\Models\Category;
use Minishop\Models\Coupon;
use Minishop\Models\Order;
use Minishop\Models\OrderReturn;
use Minishop\Models\Product;
use Minishop\Models\ProductImage;
use Minishop\Models\ProductVariant;
use Minishop\Models\ShippingMethod;
use Minishop\Models\StoreSettings;
use Minishop\Models\Tag;
use Minishop\Models\TaxZone;
use Minishop\Models\TaxZoneRate;
use Minishop\Models\User;
use Minishop\Observers\CouponObserver;
use Minishop\Observers\OrderObserver;
use Minishop\Observers\OrderReturnObserver;
use Minishop\Observers\ProductImageObserver;
use Minishop\Observers\ProductObserver;
use Minishop\Observers\ProductVariantObserver;
use Minishop\Observers\StoreSettingsObserver;
use Minishop\Observers\TaxZoneObserver;
use Minishop\Observers\TaxZoneRateObserver;
use Minishop\Payments\PaymentManager;
use Minishop\Policies\CategoryPolicy;
use Minishop\Policies\CouponPolicy;
use Minishop\Policies\OrderPolicy;
use Minishop\Policies\ProductPolicy;
use Minishop\Policies\ReturnPolicy;
use Minishop\Policies\ShippingMethodPolicy;
use Minishop\Policies\TagPolicy;
use Minishop\Policies\TaxZonePolicy;
use Minishop\Policies\UserPolicy;
use Minishop\Rendering\BladeRenderer;
use Minishop\Rendering\StorefrontRendererContract;
use Minishop\Services\Shipping\CanadaPostCarrier;
use Minishop\Services\Shipping\ShippingRateService;

class MinishopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/minishop.php',
            'minishop'
        );

        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            LoginResponse::class
        );
        $this->app->singleton(
            \Laravel\Fortify\Contracts\RegisterResponse::class,
            RegisterResponse::class
        );
        $this->app->singleton(
            CreatesNewUsers::class,
            CreateNewUser::class
        );

        $this->app->singleton('minishop.payment', fn ($app) => new PaymentManager($app));

        $this->app->singleton(StorefrontRendererContract::class, function ($app) {
            $driver = config('minishop.renderer', 'blade');

            return match (true) {
                $driver === 'blade' => new BladeRenderer,
                class_exists($driver) => $app->make($driver),
                default => throw new \InvalidArgumentException("Minishop renderer [{$driver}] is not supported."),
            };
        });
    }

    public function boot(): void
    {
        EncryptCookies::except('cart_token');

        // Fortify's email-verification feature is enabled by default; point its
        // "verify your email" notice route at the storefront view we ship, and
        // send the verification mail when a customer registers.
        Fortify::verifyEmailView(fn () => view('minishop::auth.verify-email'));
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        if ($this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'minishop-migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'minishop');

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/api.php');

        if (config('minishop.load_storefront_routes', false)) {
            Route::middleware('web')
                ->group(__DIR__.'/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }

        $this->publishes([
            __DIR__.'/../config/minishop.php' => config_path('minishop.php'),
        ], 'minishop-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/minishop'),
        ], 'minishop-views');

        // Storefront: let the host override the shipped views and pull in the
        // Tailwind (v4) entrypoint used to build the storefront assets with Vite.
        $this->publishes([
            __DIR__.'/../resources/views/storefront' => resource_path('views/storefront'),
            __DIR__.'/../resources/css/storefront.css' => resource_path('css/storefront.css'),
        ], 'minishop-storefront');

        $this->registerLivewireComponents();
        $this->registerFactoryResolver();
        $this->registerObservers();
        $this->registerPolicies();
        $this->registerShippingService();
        $this->registerSuperAdminGate();
    }

    protected function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component('minishop.cart-badge', CartBadge::class);
        Livewire::component('minishop.add-to-cart', AddToCart::class);
        Livewire::component('minishop.cart-page', CartPage::class);
        Livewire::component('minishop.product-list', ProductList::class);
        Livewire::component('minishop.checkout', Checkout::class);
        Livewire::component('minishop.address-form', AddressForm::class);
    }

    protected function registerFactoryResolver(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            if (str_starts_with($modelName, 'Minishop\\Models\\')) {
                return 'Minishop\\Database\\Factories\\'.class_basename($modelName).'Factory';
            }

            $appNamespace = app()->getNamespace();

            $modelName = str_starts_with($modelName, $appNamespace.'Models\\')
                ? substr($modelName, strlen($appNamespace.'Models\\'))
                : class_basename($modelName);

            return 'Database\\Factories\\'.$modelName.'Factory';
        });

        Factory::guessModelNamesUsing(function (Factory $factory): string {
            $factoryClass = get_class($factory);

            if (str_starts_with($factoryClass, 'Minishop\\Database\\Factories\\')) {
                return 'Minishop\\Models\\'.str_replace('Factory', '', class_basename($factoryClass));
            }

            $appNamespace = app()->getNamespace();
            $namespacedBasename = str_replace(['Database\\Factories\\', 'Factory'], '', $factoryClass);
            $factoryBasename = str_replace('Factory', '', class_basename($factoryClass));

            return class_exists($appNamespace.'Models\\'.$namespacedBasename)
                ? $appNamespace.'Models\\'.$namespacedBasename
                : $appNamespace.$factoryBasename;
        });
    }

    protected function registerObservers(): void
    {
        Order::observe(OrderObserver::class);
        OrderReturn::observe(OrderReturnObserver::class);
        Product::observe(ProductObserver::class);
        ProductImage::observe(ProductImageObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
        Coupon::observe(CouponObserver::class);
        TaxZone::observe(TaxZoneObserver::class);
        TaxZoneRate::observe(TaxZoneRateObserver::class);
        StoreSettings::observe(StoreSettingsObserver::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Coupon::class, CouponPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(OrderReturn::class, ReturnPolicy::class);
        Gate::policy(ShippingMethod::class, ShippingMethodPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(TaxZone::class, TaxZonePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }

    protected function registerShippingService(): void
    {
        $this->app->singleton(ShippingRateService::class, function (): ShippingRateService {
            $service = new ShippingRateService;

            if (config('services.canada_post.username') && config('services.canada_post.customer_number')) {
                $service->registerDriver(new CanadaPostCarrier);
            }

            return $service;
        });
    }

    protected function registerSuperAdminGate(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
