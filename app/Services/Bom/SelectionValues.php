<?php

namespace App\Services\Bom;

/**
 * Normalises a stored attribute selection into the list of option ids it
 * represents. Selections are [attribute_id => value]; multi-select values may
 * arrive as an array or as a comma-separated string.
 */
class SelectionValues
{
    /**
     * @return list<string>
     */
    public function selectedOptionIds(mixed $value): array
    {
        if (is_array($value)) {
            $values = $value;
        } elseif (is_string($value)) {
            $values = explode(',', $value);
        } elseif (is_scalar($value)) {
            $values = [$value];
        } else {
            return [];
        }

        $ids = [];

        foreach ($values as $element) {
            if (! is_scalar($element)) {
                continue;
            }

            $id = trim((string) $element);

            if ($id !== '') {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
