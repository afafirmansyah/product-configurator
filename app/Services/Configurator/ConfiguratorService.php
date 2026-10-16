<?php

namespace App\Services\Configurator;

use App\DTOs\ConfigurationResult;
use App\Models\Component;
use App\Models\Product;
use App\Services\Bom\BomGeneratorService;
use App\Services\PartNumber\PartNumberService;
use App\Services\Pricing\PricingService;
use App\Services\Rules\RuleEngineService;
use App\Support\Money;

class ConfiguratorService
{
    public function __construct(
        private readonly RuleEngineService $ruleEngine,
        private readonly CompatibilityService $compatibility,
        private readonly BomGeneratorService $bom,
        private readonly PricingService $pricing,
        private readonly PartNumberService $partNumber,
    ) {}

    public function evaluate(Product $product, array $selections, bool $debug = false): ConfigurationResult
    {
        // 1. Load the full product schema in one pass so every downstream service
        //    consumes already-loaded relations instead of re-querying. The relation
        //    methods (configurationRules/bomRules/compatibilityRules/partNumberPattern)
        //    bake in the Phase 1 is_active filtering and ordering, so loading through
        //    them preserves those guarantees for all consumers. loadMissing keeps a
        //    caller-provided eager load intact rather than discarding it.
        $product->loadMissing([
            'productAttributes.attribute.options',
            'configurationRules.conditions.attribute',
            'configurationRules.actions',
            'bomRules.conditions.attribute',
            'bomRules.items.component.category',
            'bomRules.items.valueSets.options',
            'bomRules.items.quantityAttribute',
            'compatibilityRules.attribute',
            'compatibilityRules.option',
            'compatibilityRules.targetAttribute.options',
            'compatibilityRules.targetOption',
            'partNumberPattern',
        ]);

        // 2. Evaluate rules
        $ruleEffects = $this->ruleEngine->evaluate($product, $selections, $debug);

        // 2a. Apply forced values onto the selections BEFORE pricing/BOM/part-number/validation
        //     so downstream consumers see rule-set values (rule-set overrides user input).
        foreach ($ruleEffects['set_values'] as $attributeId => $forcedValue) {
            $selections[$attributeId] = $forcedValue;
        }

        // 3. Evaluate compatibility
        $compatibilityDisabled = $this->compatibility->getDisabledOptions($product, $selections);

        // Merge disabled options from rules and compatibility. Use the union
        // operator so numeric option-id keys are preserved (array_merge would
        // renumber them). Rule reasons take precedence over compatibility ones.
        $disabledOptions = $compatibilityDisabled;
        foreach ($ruleEffects['disabled_options'] as $attrId => $options) {
            $disabledOptions[$attrId] = $options + ($disabledOptions[$attrId] ?? []);
        }

        // 4. Determine visible/required attributes.
        //    Default visibility comes from the product_attributes.is_visible pivot
        //    (defaults to true). Hide rules remove, show rules add back; show wins
        //    over hide for the same attribute.
        $defaultVisibleIds = $product->productAttributes
            ->where('is_visible', true)
            ->pluck('attribute_id')
            ->toArray();
        $hiddenIds = $ruleEffects['hidden_attributes'];
        $visibleIds = array_values(array_unique(array_merge(
            array_diff($defaultVisibleIds, $hiddenIds),
            $ruleEffects['visible_attributes'],
        )));

        $requiredIds = array_unique(array_merge(
            $product->productAttributes->where('is_required', true)->pluck('attribute_id')->toArray(),
            $ruleEffects['required_attributes'],
        ));
        $requiredIds = array_values(array_diff($requiredIds, $ruleEffects['optional_attributes']));

        // 5. Generate BOM, then apply rule-driven component additions/removals.
        $bomResult = $this->bom->generateWithWarnings($product, $selections);
        $bomItems = $this->applyComponentEffects($bomResult['items'], $ruleEffects);

        // 6. Calculate pricing, applying any rule-driven total override (in cents).
        $pricingResult = $this->pricing->calculate($product, $selections);

        if ($ruleEffects['set_price'] !== null) {
            $overrideTotal = Money::fromCents(Money::toCents($ruleEffects['set_price']));
            $pricingResult['total'] = $overrideTotal;
        }

        // 7. Generate part number, honoring a rule-driven override when present.
        $configCode = $ruleEffects['set_part_number'] !== null && $ruleEffects['set_part_number'] !== ''
            ? (string) $ruleEffects['set_part_number']
            : $this->partNumber->generate($product, $selections);

        // 8. Validate
        $validation = $this->validate($product, $selections, $visibleIds, $requiredIds, $disabledOptions);

        // BOM lines omitted by computed quantities are warnings, never errors. They are
        // kept separately so save paths can persist them alongside the BOM snapshot.
        $validation['bom_warnings'] = $bomResult['warnings'];
        $validation['warnings'] = array_merge($validation['warnings'], $bomResult['warnings']);

        // 9. Build technical specs from selections
        $techSpecs = $this->buildTechSpecs($product, $selections);

        return new ConfigurationResult(
            configurationId: '',
            product: ['code' => $product->code, 'name' => $product->name, 'id' => $product->id],
            configurationCode: $configCode,
            status: $validation['valid'] ? 'valid' : 'invalid',
            options: $selections,
            visibleAttributes: $visibleIds,
            requiredAttributes: $requiredIds,
            disabledOptions: $disabledOptions,
            technicalSpecifications: $techSpecs,
            bom: $bomItems,
            pricing: $pricingResult,
            validation: $validation,
            ruleTrace: $debug ? $ruleEffects['trace'] : [],
        );
    }

    private function validate(Product $product, array $selections, array $visibleIds, array $requiredIds, array $disabledOptions): array
    {
        $errors = [];
        $warnings = [];

        // Check required visible attributes are filled
        foreach ($requiredIds as $attrId) {
            if (! in_array($attrId, $visibleIds)) {
                continue;
            }

            $value = $selections[$attrId] ?? null;
            if (empty($value)) {
                $attr = $product->productAttributes->firstWhere('attribute_id', $attrId)?->attribute;
                $errors[] = ($attr?->name ?? "Attribute #{$attrId}").' is required.';
            }
        }

        // Check no disabled option is selected
        foreach ($disabledOptions as $attrId => $options) {
            $selected = $selections[$attrId] ?? null;
            if ($selected && isset($options[$selected])) {
                $attr = $product->productAttributes->firstWhere('attribute_id', $attrId)?->attribute;
                $errors[] = ($attr?->name ?? "Attribute #{$attrId}").': '.$options[$selected];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Apply rule-driven BOM mutations: drop removed components by id, then append
     * added components as synthetic rows in the rich BOM shape.
     *
     * @param  array<int, array<string, mixed>>  $bomItems
     * @param  array<string, mixed>  $ruleEffects
     * @return array<int, array<string, mixed>>
     */
    private function applyComponentEffects(array $bomItems, array $ruleEffects): array
    {
        $removedIds = $ruleEffects['removed_components'];

        if (! empty($removedIds)) {
            $bomItems = array_values(array_filter(
                $bomItems,
                fn (array $item): bool => ! in_array((int) $item['component_id'], $removedIds, true),
            ));
        }

        if (empty($ruleEffects['added_components'])) {
            return $bomItems;
        }

        $itemNo = count($bomItems) + 1;

        foreach ($ruleEffects['added_components'] as $added) {
            $component = Component::with('category')->find($added['component_id']);

            if (! $component) {
                continue;
            }

            $quantity = (float) $added['quantity'];
            $unitPriceCents = Money::toCents($component->sales_price);

            $bomItems[] = [
                'item_no' => str_pad((string) $itemNo++, 2, '0', STR_PAD_LEFT),
                'component_id' => $component->id,
                'part_number' => $component->internal_part_number ?? $component->code,
                'sales_part_number' => $component->sales_part_number,
                'description' => $component->name,
                'long_description' => $component->description,
                'type' => $component->category?->name ?? 'Component',
                'manufacturer' => $component->manufacturer,
                'manufacturer_part_no' => $component->manufacturer_part_number,
                'quantity' => $quantity,
                'unit' => 'EA',
                'unit_price' => Money::fromCents($unitPriceCents),
                'cost' => Money::fromCents(Money::toCents($component->cost)),
                'total_price' => Money::fromCents(Money::mulCents($unitPriceCents, $quantity)),
                'currency' => $component->currency ?? 'EUR',
                'is_mandatory' => false,
                'lead_time' => null,
                'remarks' => null,
            ];
        }

        return $bomItems;
    }

    private function buildTechSpecs(Product $product, array $selections): array
    {
        $specs = [];

        foreach ($product->productAttributes as $pa) {
            $attribute = $pa->attribute;
            $value = $selections[$attribute->id] ?? null;

            if (empty($value)) {
                continue;
            }

            $displayValue = $value;

            if ($attribute->options->isNotEmpty() && is_numeric($value)) {
                $option = $attribute->options->firstWhere('id', $value);
                $displayValue = $option?->name ?? $value;
            }

            $specs[] = [
                'attribute' => $attribute->name,
                'value' => $displayValue,
                'unit' => $attribute->unit,
            ];
        }

        return $specs;
    }
}
