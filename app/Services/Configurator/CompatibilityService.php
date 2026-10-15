<?php

namespace App\Services\Configurator;

use App\Models\AttributeOption;
use App\Models\CompatibilityRule;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class CompatibilityService
{
    /**
     * Returns disabled options based on current selections.
     *
     * Format: [attribute_id => [option_id => reason]].
     *
     * Incompatible rules fire symmetrically: selecting either side disables the
     * other. Compatible rules restrict the target attribute to only the listed
     * option(s) once their trigger is selected; every other active option of that
     * target attribute is disabled.
     *
     * @param  array<int|string, mixed>  $selections
     * @return array<int, array<int, string>>
     */
    public function getDisabledOptions(Product $product, array $selections): array
    {
        // Consume the eager-loaded relation when ConfiguratorService provided it;
        // fall back to a constrained load (the compatibilityRules relation already
        // filters is_active) only when used standalone. targetAttribute.options is
        // loaded so the compatible-complement needs no extra query.
        $product->loadMissing([
            'compatibilityRules.attribute',
            'compatibilityRules.option',
            'compatibilityRules.targetAttribute.options',
            'compatibilityRules.targetOption',
        ]);

        /** @var Collection<int, CompatibilityRule> $rules */
        $rules = $product->compatibilityRules;

        $disabled = [];

        foreach ($rules as $rule) {
            if ($rule->type === 'incompatible') {
                $this->applyIncompatibleRule($rule, $selections, $disabled);
            }
        }

        $this->applyCompatibleRules($rules, $selections, $disabled);

        return $disabled;
    }

    /**
     * Disable the other side of an incompatible pair in whichever direction the
     * trigger was selected (A->B and B->A).
     *
     * @param  array<int|string, mixed>  $selections
     * @param  array<int, array<int, string>>  $disabled
     */
    private function applyIncompatibleRule(CompatibilityRule $rule, array $selections, array &$disabled): void
    {
        $reason = $rule->reason ?? 'Incompatible with current selection.';

        if ($this->isSelected($selections, $rule->attribute_id, $rule->option_id)) {
            $disabled[$rule->target_attribute_id][$rule->target_option_id] = $reason;
        }

        if ($this->isSelected($selections, $rule->target_attribute_id, $rule->target_option_id)) {
            $disabled[$rule->attribute_id][$rule->option_id] = $reason;
        }
    }

    /**
     * Apply compatible-rule semantics. For every triggered compatible rule, build
     * the allow-list of target options per target attribute across all matching
     * rules, then disable every OTHER active option of that target attribute.
     *
     * @param  Collection<int, CompatibilityRule>  $rules
     * @param  array<int|string, mixed>  $selections
     * @param  array<int, array<int, string>>  $disabled
     */
    private function applyCompatibleRules(Collection $rules, array $selections, array &$disabled): void
    {
        /** @var array<int, array{allowed: array<int, string>, reason: string, options: Collection<int, AttributeOption>}> $allowListsByTarget */
        $allowListsByTarget = [];

        foreach ($rules as $rule) {
            if ($rule->type !== 'compatible') {
                continue;
            }

            if (! $this->isSelected($selections, $rule->attribute_id, $rule->option_id)) {
                continue;
            }

            $targetAttrId = $rule->target_attribute_id;
            $allowListsByTarget[$targetAttrId] ??= [
                'allowed' => [],
                'reason' => $rule->reason ?? 'Only compatible with current selection.',
                // Read the complement from the eager-loaded targetAttribute.options
                // instead of issuing a per-target query.
                'options' => $rule->targetAttribute?->options ?? new Collection,
            ];
            $allowListsByTarget[$targetAttrId]['allowed'][(string) $rule->target_option_id] = true;
        }

        foreach ($allowListsByTarget as $targetAttrId => $allowList) {
            foreach ($allowList['options'] as $option) {
                if (! $option->is_active) {
                    continue;
                }

                if (isset($allowList['allowed'][(string) $option->id])) {
                    continue;
                }

                $disabled[$targetAttrId][$option->id] ??= $allowList['reason'];
            }
        }
    }

    /**
     * Determine whether the given option is the current selection for the attribute.
     *
     * @param  array<int|string, mixed>  $selections
     */
    private function isSelected(array $selections, int $attributeId, int $optionId): bool
    {
        $selectedValue = $selections[$attributeId] ?? null;

        if ($selectedValue === null) {
            return false;
        }

        return (string) $selectedValue === (string) $optionId;
    }
}
