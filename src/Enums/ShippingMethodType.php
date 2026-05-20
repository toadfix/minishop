<?php

namespace Minishop\Enums;

enum ShippingMethodType: string
{
    case FlatRate = 'flat_rate';
    case Calculated = 'calculated';
}
