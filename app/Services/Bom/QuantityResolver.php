<?php

namespace App\Services\Bom;

use App\Enums\QuantityMode;
use App\Exceptions\InvalidQuantityFormulaException;
use App\Models\BomRuleItem;

/**
 * Resolves the quantity of a BOM rule line for a set of selections.
 *
 * Modes:
 * - fixed (or null): the stored base quantity.
 * - attribute: base quantity × the numeric selection of quantity_attribute_id.
 * - formula: quantity_formula evaluated by QuantityFormula with {code} variables
 *   taken from the numeric selections of the product's attributes.
 *
 * Rounding: the resolved quantity is round($q, 2), matching the decimal(8,2)
 * quantity column. When quantity_round_up is set it is ceil($q) instead, for
 * discrete parts; $q is first rounded to 6 decimals so float noise such as
 * 11.000000000000002 does not round up to 12.
 *
 * A resolved quantity <= 0, a non-numeric attribute input, an invalid formula or
 * a division by zero never throws: the quantity is 0 and a warning explains why,
 * so the generator omits the line and reports the warning.
 */
class QuantityResolver
{
    public function __construct(
        private readonly QuantityFormula $formula,
    ) {}

    /**
     * @param  array<int|string, mixed>  $selections  Selected values keyed by attribute id.
     * @param  array<string, int>  $attributeIdsByCode  Product attribute ids keyed by attribute code.
     * @return array{quantity: float, warning: ?string}
     */
    public function resolve(BomRuleItem $item, array $selections, array $attributeIdsByCode): array
    {
        $result = match ($item->quantity_mode ?? QuantityMode::Fixed) {
            QuantityMode::Fixed => ['quantity' => (float) $item->quantity, 'warning' => null],
            QuantityMode::Attribute => $this->resolveAttribute($item, $selections),
            QuantityMode::Formula => $this->resolveFormula($item, $selections, $attributeIdsByCode),
        };

        if ($result['warning'] !== null) {
            return ['quantity' => 0.0, 'warning' => $result['warning']];
        }

        $quantity = $item->quantity_round_up
            ? ceil(round($result['quantity'], 6))
            : round($result['quantity'], 2);

        if ($quantity <= 0) {
            return ['quantity' => 0.0, 'warning' => "Resolved quantity {$quantity} is not positive."];
        }

        return ['quantity' => (float) $quantity, 'warning' => null];
    }

    /**
     * @param  array<int|string, mixed>  $selections
     * @return array{quantity: float, warning: ?string}
     */
    private function resolveAttribute(BomRuleItem $item, array $selections): array
    {
        if ($item->quantity_attribute_id === null) {
            return ['quantity' => 0.0, 'warning' => 'No quantity attribute is configured.'];
        }

        $value = $selections[$item->quantity_attribute_id] ?? null;

        if (! is_numeric($value)) {
            $name = $item->quantityAttribute?->name ?? "Attribute #{$item->quantity_attribute_id}";

            return ['quantity' => 0.0, 'warning' => "{$name} has no numeric value for the quantity."];
        }

        return ['quantity' => (float) $item->quantity * (float) $value, 'warning' => null];
    }

    /**
     * @param  array<int|string, mixed>  $selections
     * @param  array<string, int>  $attributeIdsByCode
     * @return array{quantity: float, warning: ?string}
     */
    private function resolveFormula(BomRuleItem $item, array $selections, array $attributeIdsByCode): array
    {
        $formula = (string) $item->quantity_formula;

        try {
            $variables = [];

            foreach ($this->formula->variables($formula) as $code) {
                if (! array_key_exists($code, $attributeIdsByCode)) {
                    return ['quantity' => 0.0, 'warning' => "Quantity formula references unknown attribute code {{$code}}."];
                }

                $value = $selections[$attributeIdsByCode[$code]] ?? null;

                if (! is_numeric($value)) {
                    return ['quantity' => 0.0, 'warning' => "{{$code}} has no numeric value for the quantity formula."];
                }

                $variables[$code] = (float) $value;
            }

            return ['quantity' => $this->formula->evaluate($formula, $variables), 'warning' => null];
        } catch (InvalidQuantityFormulaException $exception) {
            return ['quantity' => 0.0, 'warning' => "Quantity formula error: {$exception->getMessage()}"];
        }
    }
}
