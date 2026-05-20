<?php

namespace Minishop\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Minishop\Filament\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
