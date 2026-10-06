<?php

namespace App\Services\Configurator;

use App\Models\Product;

class CompatibilityService
{
    /**
     * Returns disabled options based on current selections.
     * Format: [attribute_id => [option_id => reason]]
     */
    public function getDisabledOptions(Product $product, array $selections): array
    {
        $rules = $product->compatibilityRules()
            ->with(['attribute', 'option', 'targetAttribute', 'targetOption'])
            ->get();

        $disabled = [];

        foreach ($rules as $rule) {
            $selectedValue = $selections[$rule->attribute_id] ?? null;

            if ($selectedValue === null) {
                continue;
            }

            // Check if the triggering option is selected
            $isTriggered = (string) $selectedValue === (string) $rule->option_id;

            if (!$isTriggered) {
                continue;
            }

            if ($rule->type === 'incompatible') {
                $targetAttrId = $rule->target_attribute_id;
                $targetOptId = $rule->target_option_id;

                if (!isset($disabled[$targetAttrId])) {
                    $disabled[$targetAttrId] = [];
                }

                $disabled[$targetAttrId][$targetOptId] = $rule->reason
                    ?? 'Incompatible with current selection.';
            }
        }

        return $disabled;
    }
}
