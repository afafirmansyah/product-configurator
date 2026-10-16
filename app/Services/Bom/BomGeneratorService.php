<?php

namespace App\Services\Bom;

use App\Models\AttributeOption;
use App\Models\BomRuleItem;
use App\Models\BomRuleItemValueSet;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Services\Rules\ConditionEvaluator;
use App\Support\Money;

class BomGeneratorService
{
    public function __construct(
        private readonly ConditionEvaluator $conditionEvaluator,
        private readonly SelectionValues $selectionValues,
        private readonly QuantityResolver $quantityResolver,
    ) {}

    /**
     * Generate the BOM rows for the selections (backward-compatible entry point).
     *
     * @param  array<int|string, mixed>  $selections
     * @return list<array<string, mixed>>
     */
    public function generate(Product $product, array $selections): array
    {
        return $this->generateWithWarnings($product, $selections)['items'];
    }

    /**
     * Generate the BOM rows plus warnings for lines omitted because their
     * computed quantity could not be resolved to a positive number.
     *
     * @param  array<int|string, mixed>  $selections
     * @return array{items: list<array<string, mixed>>, warnings: list<string>}
     */
    public function generateWithWarnings(Product $product, array $selections): array
    {
        // Consume the eager-loaded relation when ConfiguratorService provided it;
        // fall back to a constrained load (the bomRules relation already filters
        // is_active and orders by sort_order) only when used standalone.
        $product->loadMissing([
            'productAttributes.attribute',
            'bomRules.conditions.attribute',
            'bomRules.items.component.category',
            'bomRules.items.valueSets.options',
            'bomRules.items.quantityAttribute',
        ]);
        $rules = $product->bomRules;

        /** @var array<string, int> $attributeIdsByCode */
        $attributeIdsByCode = $product->productAttributes
            ->filter(fn (ProductAttribute $productAttribute): bool => $productAttribute->attribute !== null)
            ->mapWithKeys(fn (ProductAttribute $productAttribute): array => [
                $productAttribute->attribute->code => (int) $productAttribute->attribute_id,
            ])
            ->all();

        $bom = [];
        $warnings = [];
        $itemNo = 1;

        foreach ($rules as $rule) {
            if (! $this->conditionEvaluator->passes($rule->conditions, $rule->condition_logic, $selections)) {
                continue;
            }

            foreach ($rule->items as $item) {
                if (! $this->itemMatchesValueSets($item, $selections)) {
                    continue;
                }

                $componentId = $item->component_id;
                $component = $item->component;

                $resolved = $this->quantityResolver->resolve($item, $selections, $attributeIdsByCode);

                if ($resolved['quantity'] <= 0) {
                    $warnings[] = "{$component->name}: ".($resolved['warning'] ?? 'Resolved quantity is not positive.');

                    continue;
                }

                $lineQuantity = $resolved['quantity'];

                if (isset($bom[$componentId])) {
                    $quantity = (float) $bom[$componentId]['quantity'] + $lineQuantity;
                    $unitPriceCents = Money::toCents($bom[$componentId]['unit_price']);

                    $bom[$componentId]['quantity'] = $quantity;
                    $bom[$componentId]['total_price'] = Money::fromCents(Money::mulCents($unitPriceCents, $quantity));
                } else {
                    $unitPriceCents = Money::toCents($component->sales_price);
                    $totalPriceCents = Money::mulCents($unitPriceCents, $lineQuantity);

                    $bom[$componentId] = [
                        'item_no' => str_pad($itemNo++, 2, '0', STR_PAD_LEFT),
                        'component_id' => $componentId,
                        'part_number' => $component->internal_part_number ?? $component->code,
                        'sales_part_number' => $component->sales_part_number,
                        'description' => $component->name,
                        'long_description' => $component->description,
                        'type' => $component->category?->name ?? 'Component',
                        'manufacturer' => $component->manufacturer,
                        'manufacturer_part_no' => $component->manufacturer_part_number,
                        'quantity' => $lineQuantity,
                        'unit' => 'EA',
                        'unit_price' => Money::fromCents($unitPriceCents),
                        'cost' => Money::fromCents(Money::toCents($component->cost)),
                        'total_price' => Money::fromCents($totalPriceCents),
                        'currency' => $component->currency ?? 'EUR',
                        'is_mandatory' => (bool) $item->is_mandatory,
                        'lead_time' => null,
                        'remarks' => null,
                    ];
                }
            }
        }

        return ['items' => array_values($bom), 'warnings' => $warnings];
    }

    /**
     * Decide whether a line of a passing rule applies to the selections.
     *
     * Configuration-set semantics: a line without value sets always applies.
     * Otherwise it applies when ANY of its sets matches, and a set matches when
     * ALL of its options are selected on their attributes. A set left without
     * options (e.g. every option was soft-deleted) never matches.
     *
     * @param  array<int|string, mixed>  $selections
     */
    private function itemMatchesValueSets(BomRuleItem $item, array $selections): bool
    {
        if ($item->valueSets->isEmpty()) {
            return true;
        }

        return $item->valueSets->contains(
            fn (BomRuleItemValueSet $valueSet): bool => $valueSet->options->isNotEmpty()
                && $valueSet->options->every(
                    fn (AttributeOption $option): bool => in_array(
                        (string) $option->id,
                        $this->selectionValues->selectedOptionIds($selections[$option->attribute_id] ?? null),
                        true,
                    ),
                ),
        );
    }

    public function validate(array $bomItems): array
    {
        $errors = [];
        $warnings = [];

        foreach ($bomItems as $item) {
            $ref = "Item {$item['item_no']} ({$item['description']})";

            if (empty($item['part_number'])) {
                $errors[] = "{$ref}: Missing part number.";
            }
            if (empty($item['description'])) {
                $errors[] = "{$ref}: Missing description.";
            }
            if ($item['quantity'] <= 0) {
                $errors[] = "{$ref}: Invalid quantity ({$item['quantity']}).";
            }
            if ($item['unit_price'] <= 0 && $item['is_mandatory']) {
                $warnings[] = "{$ref}: No unit price defined.";
            }
        }

        // Check duplicates
        $partNumbers = array_column($bomItems, 'part_number');
        $duplicates = array_diff_assoc($partNumbers, array_unique($partNumbers));
        foreach (array_unique($duplicates) as $dup) {
            $warnings[] = "Duplicate part number detected: {$dup}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Persist the BOM rows, snapshot, status and revision on the session.
     *
     * @param  list<array<string, mixed>>  $bomItems
     * @param  list<string>  $warnings  Generation warnings (e.g. omitted computed-quantity lines) merged into validation_errors.warnings.
     */
    public function saveToSession(ConfigurationSession $session, array $bomItems, array $warnings = []): void
    {
        // Delete existing BOM items
        $session->bomItems()->delete();

        foreach ($bomItems as $item) {
            $session->bomItems()->create([
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'is_mandatory' => $item['is_mandatory'],
            ]);
        }

        $validation = $this->validate($bomItems);
        $validation['warnings'] = array_values(array_merge($validation['warnings'], $warnings));

        $session->update([
            'bom_status' => $validation['valid'] ? 'generated' : 'failed',
            'bom_revision' => $session->bom_revision + 1,
            'bom_generated_at' => now(),
            'bom_snapshot' => $bomItems,
            'validation_errors' => $validation,
        ]);
    }

    public function getDocumentName(ConfigurationSession $session, string $type = 'BOM', string $ext = 'pdf'): string
    {
        $product = strtoupper(str_replace(' ', '_', $session->product->name ?? 'PRODUCT'));
        $cfgNo = str_replace('-', '', $session->configuration_number);
        $rev = 'REV'.str_pad($session->bom_revision, 2, '0', STR_PAD_LEFT);

        return "{$type}_{$product}_{$cfgNo}_{$rev}.{$ext}";
    }
}
