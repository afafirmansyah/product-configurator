<?php

namespace App\Services\Rules;

use App\Models\AttributeOption;
use App\Models\Product;

class RuleEngineService
{
    public function __construct(
        private readonly ConditionEvaluator $conditionEvaluator,
    ) {}

    /**
     * Evaluate all active rules for a product against current selections.
     *
     * @return array{
     *     visible_attributes: list<int|string>,
     *     hidden_attributes: list<int|string>,
     *     required_attributes: list<int|string>,
     *     optional_attributes: list<int|string>,
     *     disabled_options: array<int|string, array<int|string, string>>,
     *     set_values: array<int|string, mixed>,
     *     set_price: string|int|float|null,
     *     set_part_number: string|null,
     *     added_components: list<array{component_id: int, quantity: int}>,
     *     removed_components: list<int>,
     *     warnings: list<string>,
     *     errors: list<string>,
     *     trace: list<array<string, mixed>>
     * }
     */
    public function evaluate(Product $product, array $selections, bool $debug = false): array
    {
        // Consume the eager-loaded relation when ConfiguratorService provided it;
        // fall back to a constrained load (which preserves is_active + ordering)
        // only when this service is used standalone.
        $product->loadMissing(['configurationRules.conditions.attribute', 'configurationRules.actions']);
        $rules = $product->configurationRules;

        $effects = [
            'visible_attributes' => [],
            'hidden_attributes' => [],
            'required_attributes' => [],
            'optional_attributes' => [],
            'disabled_options' => [],  // [attribute_id => [option_id => reason]]
            'set_values' => [],        // [attribute_id => forced value]
            'set_price' => null,       // override total price (major units string/number)
            'set_part_number' => null, // override configuration code
            'added_components' => [],  // [['component_id' => int, 'quantity' => int]]
            'removed_components' => [], // [component_id, ...]
            'warnings' => [],
            'errors' => [],
            'trace' => [],
        ];

        foreach ($rules as $rule) {
            $conditionResult = $this->conditionEvaluator->passes(
                $rule->conditions,
                $rule->condition_logic,
                $selections,
            );

            if ($debug) {
                $effects['trace'][] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'rule_type' => $rule->type,
                    'conditions_met' => $conditionResult,
                ];
            }

            if (! $conditionResult) {
                continue;
            }

            foreach ($rule->actions as $action) {
                $this->applyAction($action, $effects);
            }
        }

        return $effects;
    }

    private function applyAction(object $action, array &$effects): void
    {
        match ($action->action_type) {
            'show' => $effects['visible_attributes'][] = $action->target_id,
            'hide' => $effects['hidden_attributes'][] = $action->target_id,
            'require' => $effects['required_attributes'][] = $action->target_id,
            'optional' => $effects['optional_attributes'][] = $action->target_id,
            'disable_option' => $this->applyDisableOption($action, $effects),
            'set_value' => $effects['set_values'][$action->target_id] = $action->value,
            'set_price' => $effects['set_price'] = $action->value,
            'set_part_number' => $effects['set_part_number'] = $action->value,
            'add_component' => $effects['added_components'][] = [
                'component_id' => (int) $action->target_id,
                'quantity' => 1,
            ],
            'remove_component' => $effects['removed_components'][] = (int) $action->target_id,
            'warn' => $effects['warnings'][] = $action->value,
            'error' => $effects['errors'][] = $action->value,
            default => null,
        };
    }

    /**
     * Register a disabled option keyed by its owning attribute so
     * ConfiguratorService can read it as [attribute_id => [option_id => reason]].
     */
    private function applyDisableOption(object $action, array &$effects): void
    {
        $reason = $action->value ?: 'Disabled by configuration rule.';

        if ($action->target_type === 'option') {
            $option = AttributeOption::find($action->target_id);

            if (! $option) {
                return;
            }

            $attributeId = $option->attribute_id;
            $optionId = $option->id;
        } else {
            $attributeId = $action->target_id;
            $optionId = $action->target_id;
        }

        if (! isset($effects['disabled_options'][$attributeId])) {
            $effects['disabled_options'][$attributeId] = [];
        }

        $effects['disabled_options'][$attributeId][$optionId] = $reason;
    }
}
