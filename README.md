# Minishop

A drop-in ecommerce package for Laravel 13 applications. Ships with a Filament v5 admin panel, a pluggable Inertia/Blade storefront, Stripe payments, a Sanctum REST API, and Canadian tax/shipping support.

---

## Requirements

- PHP 8.2+
- Laravel 13
- Filament 5

## Installation

```bash
composer require toadfix/minishop
```

Then run the install command:

```bash
php artisan minishop:install
```

This will:
1. Publish the config file to `config/minishop.php`
2. Publish and run the migrations (re-running the installer is safe — already-published migrations are skipped)
3. Point `AUTH_MODEL` in your `.env` at `Minishop\Models\User` (the model carries the roles, Sanctum and Fortify traits the package needs)
4. Seed the required roles and permissions
5. Optionally create an initial admin user with the `super-admin` role (pass `--no-admin` to skip)

The admin panel is available at `/dashboard` and authenticates with Filament's built-in login.

### Local package development

If you are working on the package itself, `scripts/fresh-host.sh` scaffolds a
throwaway Laravel host app as a sibling directory, wires the package in via a
Composer path repository (symlinked, so edits are live) and runs the installer:

```bash
scripts/fresh-host.sh            # creates ../minishop-app
scripts/fresh-host.sh ../sandbox # or a directory of your choosing
```

### Inertia storefront (optional)

If your host app uses Inertia + Vue, enable the built-in storefront:

```bash
php artisan minishop:install --renderer=inertia
```

Add to your `.env`:

```env
MINISHOP_STOREFRONT=true
```

Then regenerate Wayfinder action files (if you use [Laravel Wayfinder](https://github.com/laravel/wayfinder)):

```bash
php artisan wayfinder:generate
```

### Blade storefront (optional)

If your app uses Blade instead of Inertia:

```bash
php artisan minishop:install --renderer=blade
```

Add to your `.env`:

```env
MINISHOP_STOREFRONT=true
MINISHOP_RENDERER=blade
```

Blade view stubs are published to `resources/views/storefront/`.

---

## Configuration

The published config file is at `config/minishop.php`. Key options:

| Key | Env variable | Default | Description |
|-----|-------------|---------|-------------|
| `load_storefront_routes` | `MINISHOP_STOREFRONT` | `false` | Enable the built-in storefront routes |
| `renderer` | `MINISHOP_RENDERER` | `inertia` | Storefront renderer: `inertia`, `blade`, or a custom FQCN |
| `default_payment_gateway` | `MINISHOP_DEFAULT_GATEWAY` | `stripe` | Default payment driver |
| `panel_path` | `MINISHOP_PANEL_PATH` | `dashboard` | URL path for the Filament admin panel |
| `image_disk` | `MINISHOP_IMAGE_DISK` | `public` | Filesystem disk for product images |
| `low_stock_notification_email` | `MINISHOP_LOW_STOCK_EMAIL` | — | Email address for low-stock alerts |

---

## Environment Variables

Add these to your `.env` as needed:

```env
# Storefront
MINISHOP_STOREFRONT=true
MINISHOP_RENDERER=inertia

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Canada Post (optional)
CANADA_POST_USERNAME=
CANADA_POST_PASSWORD=
CANADA_POST_CUSTOMER_NUMBER=

# Notifications
MINISHOP_LOW_STOCK_EMAIL=store@example.com
```

---

## Admin Panel

The Filament admin panel is available at `/dashboard` (or the path configured in `MINISHOP_PANEL_PATH`).

**Available panels:**
- Products — full CRUD, variants, images, tags, categories, bulk actions, CSV/PDF export
- Orders — lifecycle management, invoice PDF generation, bulk status updates
- Customers — profiles and order history
- Coupons — percentage and fixed discounts with expiry and usage limits
- Returns — approve/reject/refund workflow
- Users — admin user management with roles
- Settings — currency, tax rate, shipping methods, payment gateway configuration
- Activity Log — admin action history

**Roles included:**
- `super-admin` — full access
- `admin` — full access except user management
- `manager` — read/write orders and products
- `customer` — assigned automatically on storefront registration

---

## Storefront Routes

When `MINISHOP_STOREFRONT=true`, the following routes are registered:

| Method | URI | Name |
|--------|-----|------|
| GET | `/` | `storefront.home` |
| GET | `/products` | `storefront.products.index` |
| GET | `/products/{product}` | `storefront.products.show` |
| GET | `/cart` | `storefront.cart.show` |
| GET | `/checkout` | `storefront.checkout.create` |
| POST | `/checkout` | `storefront.checkout.store` |
| GET | `/checkout/confirmation/{order}` | `storefront.checkout.confirmation` |
| GET | `/checkout/payment/{order}` | `storefront.checkout.payment.show` |
| POST | `/webhooks/stripe` | `webhooks.stripe` |
| POST | `/webhooks/{gateway}` | `webhooks.gateway` |

Account routes (require authentication):

| Method | URI | Name |
|--------|-----|------|
| GET | `/account` | `storefront.account.dashboard` |
| GET | `/account/orders` | `storefront.account.orders.index` |
| GET | `/account/orders/{order}` | `storefront.account.orders.show` |
| GET | `/account/address` | `storefront.account.address.edit` |
| PUT | `/account/address` | `storefront.account.address.update` |
| GET | `/account/payment` | `storefront.account.payment.index` |

---

## REST API

The Sanctum API is available at `/api/v1/`. All routes except authentication require a bearer token.

**Authentication:**

```http
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/user
```

**Orders:**

```http
GET /api/v1/orders
GET /api/v1/orders/{order}
```

**Example — register and fetch orders:**

```bash
# Register
curl -X POST http://your-app.test/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Jane","email":"jane@example.com","password":"secret","password_confirmation":"secret"}'

# Fetch orders (use the token from the register response)
curl http://your-app.test/api/v1/orders \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Payment Gateways

Minishop uses a driver-based payment system. The built-in drivers are `stripe` and `cod` (cash on delivery / no payment step).

### Stripe setup

1. Add your Stripe keys to `.env` (see Environment Variables above).
2. Set `MINISHOP_DEFAULT_GATEWAY=stripe` or select Stripe in the admin Settings panel.
3. Configure the webhook in the [Stripe Dashboard](https://dashboard.stripe.com/webhooks) to point at `https://your-app.test/webhooks/stripe` with the `payment_intent.succeeded` event.

### Adding a custom gateway

Implement `Minishop\Payments\Contracts\PaymentGatewayContract` and register the driver in a service provider:

```php
use Minishop\Payments\Facades\Payment as MinishopPayment;

MinishopPayment::extend('paypal', function ($app) {
    return new PayPalGateway(config('services.paypal'));
});
```

The contract requires:

```php
interface PaymentGatewayContract
{
    public function name(): string;
    public function initiate(Order $order, Request $request): JsonResponse|RedirectResponse;
    public function handleWebhook(Request $request): Response;
    public function requiresPaymentStep(): bool;
}
```

---

## Custom Storefront Renderer

To use your own frontend (React, Livewire, etc.), implement `Minishop\Rendering\StorefrontRendererContract` and set the FQCN in your config:

```env
MINISHOP_RENDERER=App\Rendering\ReactRenderer
```

```php
use Minishop\Rendering\StorefrontRendererContract;

class ReactRenderer implements StorefrontRendererContract
{
    public function render(string $view, array $data = []): mixed
    {
        // $view is a slash-separated path, e.g. 'storefront/Products/Index'
        return Inertia::render($view, $data); // or your own adapter
    }
}
```

---

## Seeders

To seed demo data (categories, products, shipping methods, Canadian tax zones, sample orders):

```bash
php artisan db:seed
```

To seed only roles and permissions (required for the app to function):

```bash
php artisan db:seed --class="Minishop\Database\Seeders\RoleAndPermissionSeeder"
```

---

## Testing

The package ships with a full PHPUnit test suite. To run it from the package directory:

```bash
composer install
vendor/bin/phpunit
```

Or via the host app:

```bash
php artisan test --compact
```

---

## License

MIT
