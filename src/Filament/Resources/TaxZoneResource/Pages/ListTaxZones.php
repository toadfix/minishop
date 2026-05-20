<?php

namespace Minishop\Filament\Resources\TaxZoneResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Minishop\Filament\Resources\TaxZoneResource;

class ListTaxZones extends ListRecords
{
    protected static string $resource = TaxZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
