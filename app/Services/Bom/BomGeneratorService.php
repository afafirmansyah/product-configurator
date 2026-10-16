<?php

namespace App\Services\Bom;

use App\Exceptions\BomStructureException;
use App\Models\AttributeOption;
use App\Models\BomRuleItem;
use App\Models\BomRuleItemValueSet;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Services\Rules\ConditionEvaluator;
use App\Support\Money;

/**
 * The single BOM generator for flat and nested (multi-level) BOMs.
 *
 * Row shape: generate() returns a FLAT depth-first list. Besides the component
 * fields every row has level (0 = top), path ('01', '01.02'), parent_path
 * (null on top-level rows), is_assembly and product_id (set on assembly rows).
 * Top-level item_no stays '01', '02', ... so flat BOMs are unchanged apart from
 * the added keys; nested rows use their path as item_no. BomTree::nest() turns
 * the list into a tree for display.
 *
 * Sub-assemblies: a BOM line with child_product_id expands the configurable
 * child product. The child's selections are the parent's selections for shared
 * attribute ids, falling back to the child's product_attributes.default_value.
 * Child quantities are multiplied by the assembly quantity at every level.
 * Cycles and more than configurator.bom.max_depth levels raise
 * BomStructureException.
 *
 * Assembly pricing: an assembly row has unit_price, total_price and cost of 0
 * and an informational rollup_total (sum of its descendant leaf totals); the
 * child product's base_price is not added. Summing total_price over all rows
 * therefore equals the sum over the leaf rows and nothing is counted twice.
 * Only leaf rows (component_id !== null) are persisted to configuration_bom_items.
 */
class BomGeneratorService
{
    /**
     * Relations each product in the BOM tree needs for generation.
     *
     * @var list<string>
     */
    private const PRODUCT_RELATIONS = [
        'productAttributes.attribute',
        'bomRules.conditions.attribute',
        'bomRules.items.component.category',
        'bomRules.items.childProduct',
        'bomRules.items.valueSets.options',
        'bomRules.items.quantityAttribute',
    ];

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
     *
     * @throws BomStructureException
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
     *
     * @throws BomStructureException on a sub-assembly cycle or too many levels.
     */
    public function generateWithWarnings(Product $product, array $selections): array
    {
        $warnings = [];
        $items = $this->buildRows($product, $selections, 1.0, 0, null, [$product->id], $warnings);

        return ['items' => $items, 'warnings' => $warnings];
    }

    /**
     * Build the depth-first rows of one product's BOM level.
     *
     * @param  array<int|string, mixed>  $selections
     * @param  list<int>  $visited  Product ids from the top product down to $product.
     * @param  list<string>  $warnings
     * @return list<array<string, mixed>>
     */
    private function buildRows(Product $product, array $selections, float $multiplier, int $level, ?string $parentPath, array $visited, array &$warnings): array
    {
        // Consume the eager-loaded relations when ConfiguratorService provided them;
        // the bomRules relation already filters is_active and orders by sort_order.
        $product->loadMissing(self::PRODUCT_RELATIONS);

        /** @var array<string, int> $attributeIdsByCode */
        $attributeIdsByCode = $product->productAttributes
            ->filter(fn (ProductAttribute $productAttribute): bool => $productAttribute->attribute !== null)
            ->mapWithKeys(fn (ProductAttribute $productAttribute): array => [
                $productAttribute->attribute->code => (int) $productAttribute->attribute_id,
            ])
            ->all();

        /** @var array<string, array{row: array<string, mixed>, children: list<array<string, mixed>>}> $entries */
        $entries = [];
        $index = 1;

        foreach ($product->bomRules as $rule) {
            if (! $this->conditionEvaluator->passes($rule->conditions, $rule->condition_logic, $selections)) {
                continue;
            }

            foreach ($rule->items as $item) {
                if (! $this->itemMatchesValueSets($item, $selections)) {
                    continue;
                }

                $component = $item->component_id !== null ? $item->component : null;
                $childProduct = $component === null ? $item->childProduct : null;
                $lineName = $component?->name ?? $childProduct?->name ?? "Line #{$item->id}";

                if ($component === null && $childProduct === null) {
                    $warnings[] = "{$lineName}: has no component or sub-assembly.";

                    continue;
                }

                $resolved = $this->quantityResolver->resolve($item, $selections, $attributeIdsByCode);

                if ($resolved['quantity'] <= 0) {
                    $warnings[] = "{$lineName}: ".($resolved['warning'] ?? 'Resolved quantity is not positive.');

                    continue;
                }

                // Strip float noise from the level multiplication only; top-level quantities stay as resolved.
                $lineQuantity = $multiplier === 1.0 ? $resolved['quantity'] : round($resolved['quantity'] * $multiplier, 6);

                if ($component !== null) {
                    $key = "component:{$component->id}";

                    if (isset($entries[$key])) {
                        $quantity = (float) $entries[$key]['row']['quantity'] + $lineQuantity;
                        $unitPriceCents = Money::toCents($entries[$key]['row']['unit_price']);

                        $entries[$key]['row']['quantity'] = $quantity;
                        $entries[$key]['row']['total_price'] = Money::fromCents(Money::mulCents($unitPriceCents, $quantity));

                        continue;
                    }

                    $path = $this->childPath($parentPath, $index++);
                    $unitPriceCents = Money::toCents($component->sales_price);

                    $entries[$key] = [
                        'row' => [
                            'item_no' => $path,
                            'component_id' => $component->id,
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
                            'total_price' => Money::fromCents(Money::mulCents($unitPriceCents, $lineQuantity)),
                            'currency' => $component->currency ?? 'EUR',
                            'is_mandatory' => (bool) $item->is_mandatory,
                            'lead_time' => null,
                            'remarks' => null,
                            'level' => $level,
                            'path' => $path,
                            'parent_path' => $parentPath,
                            'is_assembly' => false,
                            'product_id' => null,
                        ],
                        'children' => [],
                    ];

                    continue;
                }

                if (in_array($childProduct->id, $visited, true)) {
                    throw BomStructureException::cycle([...$visited, $childProduct->id]);
                }

                $maxDepth = (int) config('configurator.bom.max_depth', 5);

                if ($level + 1 >= $maxDepth) {
                    throw BomStructureException::maxDepth($maxDepth);
                }

                $path = $this->childPath($parentPath, $index++);
                $children = $this->buildRows(
                    $childProduct,
                    $this->mapSelectionsForChild($childProduct, $selections),
                    $lineQuantity,
                    $level + 1,
                    $path,
                    [...$visited, $childProduct->id],
                    $warnings,
                );

                $rollupCents = Money::addCents(...array_map(
                    fn (array $row): int => $row['is_assembly'] ? 0 : Money::toCents($row['total_price']),
                    $children,
                ));

                $entries["assembly:{$path}"] = [
                    'row' => [
                        'item_no' => $path,
                        'component_id' => null,
                        'part_number' => $childProduct->internal_part_number ?? $childProduct->code,
                        'sales_part_number' => null,
                        'description' => $childProduct->name,
                        'long_description' => $childProduct->short_description,
                        'type' => 'Assembly',
                        'manufacturer' => $childProduct->manufacturer,
                        'manufacturer_part_no' => null,
                        'quantity' => $lineQuantity,
                        'unit' => 'EA',
                        'unit_price' => Money::fromCents(0),
                        'cost' => Money::fromCents(0),
                        'total_price' => Money::fromCents(0),
                        'currency' => $childProduct->currency ?? 'EUR',
                        'is_mandatory' => (bool) $item->is_mandatory,
                        'lead_time' => null,
                        'remarks' => null,
                        'level' => $level,
                        'path' => $path,
                        'parent_path' => $parentPath,
                        'is_assembly' => true,
                        'product_id' => $childProduct->id,
                        'rollup_total' => Money::fromCents($rollupCents),
                    ],
                    'children' => $children,
                ];
            }
        }

        $rows = [];

        foreach ($entries as $entry) {
            $rows[] = $entry['row'];
            array_push($rows, ...$entry['children']);
        }

        return $rows;
    }

    /**
     * Top-level paths are '01', '02', ...; nested paths append to the parent path ('01.02').
     */
    private function childPath(?string $parentPath, int $index): string
    {
        $segment = str_pad((string) $index, 2, '0', STR_PAD_LEFT);

        return $parentPath === null ? $segment : "{$parentPath}.{$segment}";
    }

    /**
     * Derive a child product's selections: the parent's value for every shared
     * attribute id, otherwise the child's product_attributes.default_value.
     *
     * @param  array<int|string, mixed>  $parentSelections
     * @return array<int, mixed>
     */
    private function mapSelectionsForChild(Product $child, array $parentSelections): array
    {
        $child->loadMissing('productAttributes');
        $selections = [];

        foreach ($child->productAttributes as $productAttribute) {
            $attributeId = (int) $productAttribute->attribute_id;
            $parentValue = $parentSelections[$attributeId] ?? null;

            if ($parentValue !== null && $parentValue !== '' && $parentValue !== []) {
                $selections[$attributeId] = $parentValue;
            } elseif ($productAttribute->default_value !== null) {
                $selections[$attributeId] = $productAttribute->default_value;
            }
        }

        return $selections;
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

    /**
     * Validate BOM rows. Assembly rows carry no price by design and are only
     * checked for part number and description; duplicate part numbers are
     * reported per BOM level (rows sharing a parent_path).
     *
     * @param  list<array<string, mixed>>  $bomItems
     * @return array{valid: bool, errors: list<string>, warnings: list<string>}
     */
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
            if ($item['is_assembly'] ?? false) {
                continue;
            }
            if ($item['quantity'] <= 0) {
                $errors[] = "{$ref}: Invalid quantity ({$item['quantity']}).";
            }
            if ($item['unit_price'] <= 0 && $item['is_mandatory']) {
                $warnings[] = "{$ref}: No unit price defined.";
            }
        }

        // Check duplicates within each BOM level
        $partNumbersByParent = [];
        foreach ($bomItems as $item) {
            $partNumbersByParent[(string) ($item['parent_path'] ?? '')][] = $item['part_number'];
        }
        foreach ($partNumbersByParent as $partNumbers) {
            $duplicates = array_diff_assoc($partNumbers, array_unique($partNumbers));
            foreach (array_unique($duplicates) as $dup) {
                $warnings[] = "Duplicate part number detected: {$dup}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Whether freshly generated rows differ from a stored BOM snapshot.
     *
     * Rows are compared loosely (the snapshot is JSON-decoded, so key order and
     * numeric string/float representations may differ) and only on the keys the
     * stored row has. Snapshots saved before rows gained the nesting keys (level,
     * path, parent_path, is_assembly, product_id) are therefore not reported as
     * changed merely because of those added keys.
     *
     * @param  array<int, mixed>  $snapshot
     * @param  list<array<string, mixed>>  $rows
     */
    public function snapshotDiffers(array $snapshot, array $rows): bool
    {
        $snapshot = array_values($snapshot);
        $rows = array_values($rows);

        if (count($snapshot) !== count($rows)) {
            return true;
        }

        foreach ($snapshot as $index => $storedRow) {
            if (! is_array($storedRow) || $storedRow != array_intersect_key($rows[$index], $storedRow)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Persist the BOM rows, snapshot, status and revision on the session.
     *
     * The snapshot keeps every row (assemblies included); configuration_bom_items
     * receives only leaf rows (component_id !== null) with level-multiplied quantities.
     *
     * Versioning: bom_revision increments on every generation. version is the
     * configuration content revision (starts at '1') and increments only when a
     * previous snapshot existed and the new rows differ from it. Editing products or
     * BOM rules never touches a stored snapshot; it changes only through this method
     * (i.e. when the user saves or regenerates the BOM).
     *
     * @param  list<array<string, mixed>>  $bomItems
     * @param  list<string>  $warnings  Generation warnings (e.g. omitted computed-quantity lines) merged into validation_errors.warnings.
     */
    public function saveToSession(ConfigurationSession $session, array $bomItems, array $warnings = []): void
    {
        $previousSnapshot = $session->bom_snapshot;

        // Delete existing BOM items
        $session->bomItems()->delete();

        foreach ($bomItems as $item) {
            if ($item['component_id'] === null) {
                continue;
            }

            $session->bomItems()->create([
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'is_mandatory' => $item['is_mandatory'],
            ]);
        }

        $validation = $this->validate($bomItems);
        $validation['warnings'] = array_values(array_merge($validation['warnings'], $warnings));

        if ($previousSnapshot !== null && $this->snapshotDiffers($previousSnapshot, $bomItems)) {
            $session->version = (string) ((int) $session->version + 1);
        }

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
