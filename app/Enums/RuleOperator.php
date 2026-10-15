<?php

namespace App\Enums;

enum RuleOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case In = 'in';
    case NotIn = 'not_in';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';

    public function label(): string
    {
        return match ($this) {
            self::Equals => 'equals',
            self::NotEquals => 'not equals',
            self::In => 'is one of',
            self::NotIn => 'is not one of',
            self::GreaterThan => 'greater than',
            self::GreaterThanOrEqual => 'greater than or equal',
            self::LessThan => 'less than',
            self::LessThanOrEqual => 'less than or equal',
            self::Contains => 'contains',
            self::NotContains => 'does not contain',
            self::IsEmpty => 'is empty',
            self::IsNotEmpty => 'is not empty',
        };
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return match ($this) {
            self::Equals => (string) $actual === (string) $expected,
            self::NotEquals => (string) $actual !== (string) $expected,
            self::In => in_array($actual, is_array($expected) ? $expected : explode(',', $expected)),
            self::NotIn => ! in_array($actual, is_array($expected) ? $expected : explode(',', $expected)),
            self::GreaterThan => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            self::GreaterThanOrEqual => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            self::LessThan => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            self::LessThanOrEqual => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            self::Contains => str_contains((string) $actual, (string) $expected),
            self::NotContains => ! str_contains((string) $actual, (string) $expected),
            self::IsEmpty => empty($actual),
            self::IsNotEmpty => ! empty($actual),
        };
    }
}
