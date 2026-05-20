<?php

namespace Minishop\Enums;

enum ProductType: string
{
    case Simple = 'simple';
    case Variable = 'variable';
    case Bundled = 'bundled';
}
