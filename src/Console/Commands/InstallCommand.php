<?php

namespace Minishop\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\User;
use Spatie\Permission\Models\Role;

class InstallCommand extends Command
{
    protected $signature = 'minishop:install
        {--renderer=inertia : The storefront renderer to use (inertia|blade)}
        {--no-admin : Skip creating the initial admin user}';

    protected $description = 'Publish and set up the Minishop ecommerce package';

    public function handle(): int
    {
        $this->info('Installing Minishop...');

        $this->call('vendor:publish', [
            '--tag' => 'minishop-config',
            '--force' => false,
        ]);

        if ($this->migrationsAlreadyPublished()) {
            $this->line('  Minishop migrations already published — skipping.');
        } else {
            $this->call('vendor:publish', [
                '--tag' => 'minishop-migrations',
                '--force' => false,
            ]);
        }

        $this->configureAuthModel();

        $this->call('migrate');

        $this->call('db:seed', [
            '--class' => RoleAndPermissionSeeder::class,
        ]);

        if (! $this->option('no-admin')) {
            $this->createAdminUser();
        }

        $this->publishFilamentAssets();

        if ($this->option('renderer') === 'blade') {
            $this->call('vendor:publish', [
                '--tag' => 'minishop-blade-stubs',
                '--force' => false,
            ]);
            $this->line('  Blade view stubs published to <fg=cyan>resources/views/storefront/</>.');
            $this->line('  Set <fg=yellow>MINISHOP_RENDERER=blade</> in your .env.');
        }

        $this->newLine();
        $this->info('Minishop installed successfully.');
        $this->newLine();
        $this->line('  Next steps:');
        $this->line('  1. Set <fg=yellow>MINISHOP_LOW_STOCK_EMAIL</> in your .env for low-stock alerts.');
        $this->line('  2. Add Stripe keys to .env: <fg=yellow>STRIPE_KEY</>, <fg=yellow>STRIPE_SECRET</>, <fg=yellow>STRIPE_WEBHOOK_SECRET</>.');
        $this->line('  3. Visit <fg=cyan>/dashboard</> — Filament admin panel is ready.');
        $this->line('  4. API base URL: <fg=cyan>/api/v1/</>.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Publish Filament's compiled CSS/JS to the host's public directory and
     * register a Composer hook so they are re-published on every install/update.
     *
     * Filament serves its frontend from public/css/filament and
     * public/js/filament; without these assets the admin panel renders but its
     * JavaScript never initialises (blank tables, dead sidebar/dropdowns).
     */
    protected function publishFilamentAssets(): void
    {
        $this->call('filament:assets');
        $this->registerFilamentAssetsHook();
    }

    /**
     * Add `@php artisan filament:assets` to the host composer.json's
     * post-autoload-dump scripts so the assets stay in sync after Filament
     * upgrades. No-op if it is already present or composer.json is missing.
     */
    protected function registerFilamentAssetsHook(): void
    {
        $path = base_path('composer.json');

        if (! is_file($path)) {
            return;
        }

        $composer = json_decode(file_get_contents($path), true);

        if (! is_array($composer)) {
            return;
        }

        $hook = '@php artisan filament:assets --ansi';
        $scripts = $composer['scripts']['post-autoload-dump'] ?? [];
        $scripts = is_array($scripts) ? $scripts : [$scripts];

        if (in_array($hook, $scripts, true)) {
            return;
        }

        $scripts[] = $hook;
        $composer['scripts']['post-autoload-dump'] = $scripts;

        file_put_contents(
            $path,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
        );

        $this->line('  Registered <fg=cyan>filament:assets</> in composer.json post-autoload-dump.');
    }

    /**
     * Detect whether Minishop migrations have already been published, so a
     * re-run of the installer does not copy a second, freshly-timestamped set
     * (publishesMigrations() prepends a new timestamp on every publish).
     */
    protected function migrationsAlreadyPublished(): bool
    {
        $sentinel = glob(database_path('migrations/*_create_store_settings_table.php'));

        return ! empty($sentinel);
    }

    /**
     * Point the host application's auth provider at Minishop's User model.
     *
     * Minishop's User model carries the HasRoles, Sanctum and Fortify traits the
     * package relies on. Without this the host app authenticates with the stock
     * App\Models\User and the panel blows up on the first hasRole() call.
     */
    protected function configureAuthModel(): void
    {
        $expected = User::class;

        if (config('auth.providers.users.model') === $expected) {
            return;
        }

        $env = base_path('.env');

        if (! is_file($env)) {
            $this->warn("  Could not find .env — set AUTH_MODEL={$expected} manually.");

            return;
        }

        $contents = file_get_contents($env);

        if (preg_match('/^AUTH_MODEL=.*$/m', $contents)) {
            $contents = preg_replace('/^AUTH_MODEL=.*$/m', 'AUTH_MODEL="'.addslashes($expected).'"', $contents);
        } else {
            $contents = rtrim($contents, "\n")."\n\nAUTH_MODEL=\"".addslashes($expected)."\"\n";
        }

        file_put_contents($env, $contents);

        // Apply for the remainder of this command (migrations/seeders/admin).
        config(['auth.providers.users.model' => $expected]);

        $this->line("  Set <fg=yellow>AUTH_MODEL</> to <fg=cyan>{$expected}</> in .env.");
    }

    /**
     * Create the first admin user with the super-admin role on the web guard.
     */
    protected function createAdminUser(): void
    {
        $this->newLine();

        if (! $this->confirm('Create an admin user now?', true)) {
            return;
        }

        $name = $this->ask('Name', 'Admin');
        $email = $this->ask('Email');

        if (blank($email)) {
            $this->warn('  Skipped admin creation — no email provided.');

            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->warn("  A user with {$email} already exists — skipping.");

            return;
        }

        $password = $this->secret('Password') ?: 'password';

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $role = Role::where('name', 'super-admin')
            ->where('guard_name', config('auth.defaults.guard', 'web'))
            ->first();

        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        $this->line("  Admin user <fg=cyan>{$email}</> created with the <fg=cyan>super-admin</> role.");
    }
}
