<?php

namespace Minishop\Enums;

enum ReturnReason: string
{
    case Defective = 'defective';
    case WrongItem = 'wrong_item';
    case NotAsDescribed = 'not_as_described';
    case ChangeOfMind = 'change_of_mind';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Defective => 'Defective / Damaged',
            self::WrongItem => 'Wrong Item Received',
            self::NotAsDescribed => 'Not as Described',
            self::ChangeOfMind => 'Change of Mind',
            self::Other => 'Other',
        };
    }
}
