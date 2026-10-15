<?php

namespace App\Services\Rules;

use App\Models\Product;

class RuleEngineService
{
    public function __construct(
        private readonly ConditionEvaluator $conditionEvaluator,
    ) {}

    /**
     * Evaluate all active rules for a product against current selections.
     * Returns structured effects: visible, required, disabled options, warnings, errors.
     */
    public function evaluate(Product $product, array $selections, bool $debug = false): array
    {
        $rules = $product->configurationRules()
            ->with(['conditions.attribute', 'actions'])
            ->get();

        $effects = [
            'visible_attributes' => [],
            'hidden_attributes' => [],
            'required_attributes' => [],
            'optional_attributes' => [],
            'disabled_options' => [],  // [attribute_id => [option_id => reason]]
            'set_values' => [],
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
            'set_value' => $effects['set_values'][$action->target_id] = $action->value,
            'warn' => $effects['warnings'][] = $action->value,
            'error' => $effects['errors'][] = $action->value,
            default => null,
        };
    }
}
