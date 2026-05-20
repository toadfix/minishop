<?php

namespace Minishop\Filament\Resources\OrderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Minishop\Filament\Resources\OrderResource;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
}
