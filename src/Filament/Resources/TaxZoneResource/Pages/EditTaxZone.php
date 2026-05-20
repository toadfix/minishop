<?php

namespace Minishop\Filament\Resources\TaxZoneResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Minishop\Filament\Resources\TaxZoneResource;

class EditTaxZone extends EditRecord
{
    protected static string $resource = TaxZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
