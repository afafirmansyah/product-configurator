<?php

namespace App\Services\Configurator;

use App\Enums\AttributeType;
use App\Models\Attribute;

/**
 * Checks a single configured value against its attribute's validation_rules
 * (see the shape documented on {@see Attribute}).
 *
 * A value must be numeric when the rules set a type or a min/max bound, or when the
 * attribute itself is an integer, decimal or range attribute. Empty values are not
 * checked here; "required" is enforced by the configurator's required-attribute check.
 */
class AttributeValueValidator
{
    private const NUMERIC_ATTRIBUTE_TYPES = [AttributeType::Integer, AttributeType::Decimal, AttributeType::Range];

    /**
     * @return list<string>
     */
    public function errorsFor(Attribute $attribute, mixed $value): array
    {
        if ($value === null || $value === '' || is_array($value)) {
            return [];
        }

        $rules = $attribute->validation_rules ?? [];
        $type = $rules['type'] ?? null;
        $min = $this->numericRule($rules['min'] ?? null);
        $max = $this->numericRule($rules['max'] ?? null);

        $mustBeNumeric = $type !== null
            || $min !== null
            || $max !== null
            || in_array(AttributeType::tryFrom((string) $attribute->type), self::NUMERIC_ATTRIBUTE_TYPES, true);

        if (! $mustBeNumeric) {
            return [];
        }

        $name = $attribute->name;

        if (! is_numeric($value)) {
            return ["{$name} must be a number."];
        }

        $number = (float) $value;
        $errors = [];

        if ($type === 'integer' && $number !== floor($number)) {
            $errors[] = "{$name} must be a whole number.";
        }

        if ($min !== null && $number < $min) {
            $errors[] = "{$name} must be at least {$this->format($min)}.";
        }

        if ($max !== null && $number > $max) {
            $errors[] = "{$name} must be at most {$this->format($max)}.";
        }

        return $errors;
    }

    private function numericRule(mixed $bound): ?float
    {
        return is_numeric($bound) ? (float) $bound : null;
    }

    private function format(float $bound): string
    {
        return $bound === floor($bound) ? (string) (int) $bound : (string) $bound;
    }
}
