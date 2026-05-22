<?php

namespace Minishop\Console\Commands;

use Illuminate\Console\Command;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;

class InstallCommand extends Command
{
    protected $signature = 'minishop:install';

    protected $description = 'Publish and set up the Minishop ecommerce package';

    public function handle(): int
    {
        $this->info('Installing Minishop...');

        $this->call('vendor:publish', [
            '--tag' => 'minishop-config',
            '--force' => false,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'minishop-migrations',
            '--force' => false,
        ]);

        $this->call('migrate');

        $this->call('db:seed', [
            '--class' => RoleAndPermissionSeeder::class,
        ]);

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
}
