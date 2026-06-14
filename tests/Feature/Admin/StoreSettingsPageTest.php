<?php

namespace Minishop\Tests\Feature\Admin;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Filament\Pages\ManageStoreSettings;
use Minishop\Payments\Facades\Payment;
use Minishop\Payments\Gateways\NullGateway;
use Minishop\Tests\TestCase;

class StoreSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_gateways_include_the_built_in_drivers(): void
    {
        $gateways = Payment::availableGateways();

        $this->assertArrayHasKey('stripe', $gateways);
        $this->assertArrayHasKey('cod', $gateways);
    }

    public function test_available_gateways_include_custom_registered_drivers(): void
    {
        Payment::extend('paypal', fn () => new NullGateway);

        $this->assertArrayHasKey('paypal', Payment::availableGateways());
    }

    public function test_settings_page_is_registered_on_the_admin_panel(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('minishop'));

        $this->assertContains(
            ManageStoreSettings::class,
            Filament::getPanel('minishop')->getPages(),
        );
    }
}
