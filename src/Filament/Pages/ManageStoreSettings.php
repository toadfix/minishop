<?php

namespace Minishop\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Minishop\Enums\TaxMode;
use Minishop\Models\StoreSettings;
use Minishop\Payments\Facades\Payment;

/**
 * Single-record editor for the store's StoreSettings: the active payment
 * gateway plus localisation, tax, inventory and promotion options. Payment
 * gateway *secrets* (Stripe keys/webhook) deliberately live in .env, not here.
 */
class ManageStoreSettings extends Page
{
    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationLabel(): string
    {
        return 'Settings';
    }

    public function getTitle(): string
    {
        return 'Store settings';
    }

    public function mount(): void
    {
        $this->form->fill(StoreSettings::current()->attributesToArray());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payments')
                ->description('Stripe API keys and the webhook secret are read from your .env file, not stored here.')
                ->schema([
                    Select::make('active_payment_gateway')
                        ->label('Active payment gateway')
                        ->options(Payment::availableGateways())
                        ->required()
                        ->native(false),

                    Placeholder::make('stripe_keys_hint')
                        ->label('Stripe credentials')
                        ->content('Set STRIPE_KEY, STRIPE_SECRET and STRIPE_WEBHOOK_SECRET in .env.')
                        ->visible(fn (Get $get) => $get('active_payment_gateway') === 'stripe'),
                ]),

            Section::make('Localisation')
                ->schema([
                    TextInput::make('currency')
                        ->required()
                        ->maxLength(3)
                        ->helperText('ISO 4217 code, e.g. USD, CAD.'),

                    TextInput::make('currency_locale')
                        ->label('Currency locale')
                        ->maxLength(10)
                        ->helperText('e.g. en_US, en_CA.'),
                ])
                ->columns(2),

            Section::make('Tax')
                ->schema([
                    Select::make('tax_mode')
                        ->options(TaxMode::class)
                        ->required()
                        ->native(false),

                    TextInput::make('tax_rate')
                        ->label('Tax rate (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->visible(fn (Get $get) => $get('tax_mode') === TaxMode::FlatRate->value),

                    TextInput::make('gst_number')
                        ->label('GST/Tax number')
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Inventory & promotions')
                ->schema([
                    TextInput::make('low_stock_threshold')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),

                    TextInput::make('sale_discount_percentage')
                        ->label('Sale discount (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0),

                    TextInput::make('origin_postcode')
                        ->label('Shipping origin postcode')
                        ->maxLength(20),
                ])
                ->columns(3),
        ]);
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
        } catch (Halt $exception) {
            return;
        }

        StoreSettings::current()->update($data);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save changes')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }
}
