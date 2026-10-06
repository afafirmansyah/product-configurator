<?php

namespace App\Enums;

enum AttributeType: string
{
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Range = 'range';
    case Currency = 'currency';
    case Date = 'date';

    public function label(): string
    {
        return match($this) {
            self::Text => 'Text',
            self::Integer => 'Integer',
            self::Decimal => 'Decimal',
            self::Boolean => 'Boolean',
            self::Select => 'Select',
            self::MultiSelect => 'Multi-Select',
            self::Radio => 'Radio',
            self::Checkbox => 'Checkbox',
            self::Range => 'Range',
            self::Currency => 'Currency',
            self::Date => 'Date',
        };
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::MultiSelect, self::Radio, self::Checkbox]);
    }
}
