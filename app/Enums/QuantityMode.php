<?php

namespace App\Enums;

enum QuantityMode: string
{
    case Fixed = 'fixed';
    case Attribute = 'attribute';
    case Formula = 'formula';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed',
            self::Attribute => '× Attribute',
            self::Formula => 'Formula',
        };
    }
}
