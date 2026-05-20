<?php

namespace Minishop\Filament\Resources\CustomerResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Minishop\Filament\Resources\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
