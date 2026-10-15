<?php

namespace App\Services\PartNumber;

use App\Models\AttributeOption;
use App\Models\Product;

class PartNumberService
{
    public function generate(Product $product, array $selections): string
    {
        $pattern = $product->partNumberPattern;

        if (! $pattern) {
            return $product->code;
        }

        $parts = [];

        foreach ($pattern->segments as $segment) {
            $type = $segment['type'] ?? 'literal';
            $value = $segment['value'] ?? '';

            if ($type === 'literal') {
                $parts[] = $value;
            } elseif ($type === 'attribute') {
                // Find attribute by code
                $attribute = $product->attributes()->where('code', $value)->first();

                if (! $attribute) {
                    $parts[] = 'X';

                    continue;
                }

                $selectedValue = $selections[$attribute->id] ?? null;

                if (empty($selectedValue)) {
                    $parts[] = $segment['default'] ?? 'X';

                    continue;
                }

                // If it's an option-based attribute, get the option's part_number or code
                if (is_numeric($selectedValue)) {
                    $option = AttributeOption::find($selectedValue);
                    $parts[] = $option?->part_number ?? $option?->code ?? $selectedValue;
                } else {
                    $parts[] = strtoupper($selectedValue);
                }
            }
        }

        $separator = $pattern->segments[0]['separator'] ?? '-';

        return implode($separator, array_filter($parts));
    }
}
