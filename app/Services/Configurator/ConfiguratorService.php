<?php

namespace App\Services\Configurator;

use App\DTOs\ConfigurationResult;
use App\Models\Product;
use App\Services\Bom\BomGeneratorService;
use App\Services\PartNumber\PartNumberService;
use App\Services\Pricing\PricingService;
use App\Services\Rules\RuleEngineService;

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
        // 1. Load product schema (attributes with options)
        $product->load([
            'productAttributes.attribute.options',
            'configurationRules.conditions.attribute',
            'configurationRules.actions',
            'bomRules.conditions.attribute',
            'bomRules.items.component',
            'compatibilityRules',
            'partNumberPattern',
        ]);

        // 2. Evaluate rules
        $ruleEffects = $this->ruleEngine->evaluate($product, $selections, $debug);

        // 3. Evaluate compatibility
        $compatibilityDisabled = $this->compatibility->getDisabledOptions($product, $selections);

        // Merge disabled options from rules and compatibility
        $disabledOptions = $compatibilityDisabled;
        foreach ($ruleEffects['disabled_options'] as $attrId => $options) {
            $disabledOptions[$attrId] = array_merge($disabledOptions[$attrId] ?? [], $options);
        }

        // 4. Determine visible/required attributes
        $allAttributeIds = $product->productAttributes->pluck('attribute_id')->toArray();
        $hiddenIds = $ruleEffects['hidden_attributes'];
        $visibleIds = array_values(array_diff($allAttributeIds, $hiddenIds));

        $requiredIds = array_unique(array_merge(
            $product->productAttributes->where('is_required', true)->pluck('attribute_id')->toArray(),
            $ruleEffects['required_attributes'],
        ));
        $requiredIds = array_values(array_diff($requiredIds, $ruleEffects['optional_attributes']));

        // 5. Generate BOM
        $bomItems = $this->bom->generate($product, $selections);

        // 6. Calculate pricing
        $pricingResult = $this->pricing->calculate($product, $selections);

        // 7. Generate part number
        $configCode = $this->partNumber->generate($product, $selections);

        // 8. Validate
        $validation = $this->validate($product, $selections, $visibleIds, $requiredIds, $disabledOptions);

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
