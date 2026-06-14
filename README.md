# Minishop

A drop-in ecommerce package for Laravel 13 applications. Ships with a Filament v5 admin panel, a Livewire storefront, Stripe payments, a Sanctum REST API, and Canadian tax/shipping support.

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

### Livewire storefront (optional)

The package ships a complete Livewire + Blade storefront (home, products,
cart, checkout, and customer account). Publish it and enable the routes:

```bash
php artisan minishop:install --storefront
```

Add to your `.env`:

```env
MINISHOP_STOREFRONT=true
```

The storefront is styled with Tailwind v4. Build the assets with your app's
Vite setup:

```bash
npm install
npm run build
```

`minishop:install --storefront` publishes:

- `resources/views/storefront/` — the page views (override any of them freely).
- `resources/css/storefront.css` — the Tailwind entrypoint. Add it to your
  `vite.config.js` inputs (alongside your existing CSS) and reference it with
  `@vite(['resources/css/storefront.css'])` — the shipped layout already does.

Until the assets are built, the layout falls back to the Tailwind Play CDN so
the storefront is usable out of the box.

> **Upgrading:** publishing copies the storefront views into your app, and your
> copies take precedence over the package's. After upgrading the package, the
> shipped views may have changed — re-publish to pick up the new versions:
>
> ```bash
> php artisan vendor:publish --tag=minishop-storefront --force
> ```
>
> `--force` overwrites the published views, so re-apply any local edits
> afterwards (or diff before publishing). If you haven't customised the views,
> deleting `resources/views/storefront/` and re-publishing is the cleanest path.

---

## Configuration

The published config file is at `config/minishop.php`. Key options:

| Key | Env variable | Default | Description |
|-----|-------------|---------|-------------|
| `load_storefront_routes` | `MINISHOP_STOREFRONT` | `false` | Enable the built-in storefront routes |
| `renderer` | `MINISHOP_RENDERER` | `blade` | Storefront renderer: `blade` (Livewire) or a custom FQCN |
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
MINISHOP_RENDERER=blade

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
- Products — full CRUD with options, variants, and images (managed inline as relation managers); bulk delete
- Categories & Tags — full CRUD
- Orders — lifecycle management, bulk status updates, and downloadable invoice PDFs (also emailed to the customer on confirmation)
- Customers — profiles and order history
- Coupons — percentage and fixed discounts with expiry and usage limits
- Shipping Methods — flat-rate and carrier-calculated methods
- Tax Zones — zone-based tax rates
- Returns — approve/reject/receive/refund workflow
- Users — admin user management with roles
- Settings — currency & locale, tax rate/mode, GST number, active payment gateway, inventory and promotion options
- Activity Log — admin action history

**Roles included:**
- `super-admin` — full access (bypasses all permission checks)
- `admin` — full management of every resource, including users
- `manager` — products and orders (no deletes), returns processing, and read access to categories/customers/tax zones/tags; no user, coupon, shipping, or settings management
- `customer` — assigned automatically on storefront registration; no admin access

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
| GET | `/order-confirmation/{order}` | `storefront.order.confirmation` |
| GET | `/checkout/pay/{order}` | `storefront.checkout.payment.show` |
| POST | `/checkout/pay/{order}/stripe` | `storefront.checkout.payment.stripe` |
| POST | `/webhooks/stripe` | `webhooks.stripe` |
| POST | `/webhooks/{gateway}` | `webhooks.gateway` |

The cart is also driven by JSON sub-routes (`storefront.cart.items.store`,
`.items.update`, `.items.destroy`, `.clear`, `.sync`) used by the Livewire
components.

Account routes (require authentication):

| Method | URI | Name |
|--------|-----|------|
| GET | `/account` | `account.dashboard` |
| GET | `/account/orders` | `account.orders.index` |
| GET | `/account/orders/{order}` | `account.orders.show` |
| GET | `/account/address` | `account.address.edit` |
| PUT | `/account/address` | `account.address.update` |
| GET | `/account/payment` | `account.payment.index` |

---

## REST API

The Sanctum API is available at `/api/v1/`. Catalog and cart endpoints are public; orders and the authenticated-user endpoint require a bearer token.

**Authentication:**

```http
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/user
```

**Catalog (public):**

```http
GET /api/v1/products
GET /api/v1/products/{product}
GET /api/v1/categories
GET /api/v1/categories/{category}
POST /api/v1/coupons/validate
```

Product responses include each image's resolved `url` (and raw `path`).

**Cart:**

```http
GET    /api/v1/cart
POST   /api/v1/cart/items
PATCH  /api/v1/cart/items/{cartItem}
DELETE /api/v1/cart/items/{cartItem}
```

**Orders (require a bearer token):**

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

To use your own frontend (Inertia, a custom Blade theme, an SPA API bridge,
etc.) instead of the built-in Livewire storefront, implement
`Minishop\Rendering\StorefrontRendererContract` and set the FQCN in your config:

```env
MINISHOP_RENDERER=App\Rendering\CustomRenderer
```

```php
use Minishop\Rendering\StorefrontRendererContract;

class CustomRenderer implements StorefrontRendererContract
{
    public function render(string $view, array $data = []): mixed
    {
        // $view is a slash-separated path, e.g. 'storefront/Products/Index'.
        // Map it to your own templates, an Inertia page, an API payload, etc.
        return view('shop.'.str_replace('/', '.', strtolower($view)), $data);
    }
}
```

> The package no longer ships an Inertia renderer — the default (and only
> built-in) renderer is `blade`, which powers the Livewire storefront. A custom
> renderer is only needed if you are replacing that frontend entirely.

---

## Seeders

To seed demo data (roles, categories, products, shipping methods, Canadian tax zones, coupons, sample orders), run the package's aggregate seeder — `php artisan db:seed` alone runs your host app's `DatabaseSeeder`, not Minishop's:

```bash
php artisan db:seed --class="Minishop\Database\Seeders\MinishopSeeder"
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
