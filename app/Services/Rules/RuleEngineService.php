<?php

namespace App\Services\Rules;

use App\Enums\RuleOperator;
use App\Models\ConfigurationRule;
use App\Models\Product;
use Illuminate\Support\Collection;

class RuleEngineService
{
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
            $conditionResult = $this->evaluateConditions($rule, $selections);

            if ($debug) {
                $effects['trace'][] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'rule_type' => $rule->type,
                    'conditions_met' => $conditionResult,
                ];
            }

            if (!$conditionResult) {
                continue;
            }

            foreach ($rule->actions as $action) {
                $this->applyAction($action, $effects);
            }
        }

        return $effects;
    }

    private function evaluateConditions(ConfigurationRule $rule, array $selections): bool
    {
        $conditions = $rule->conditions;

        if ($conditions->isEmpty()) {
            return true;
        }

        // Group conditions by group number
        $groups = $conditions->groupBy('group');
        $groupResults = [];

        foreach ($groups as $groupId => $groupConditions) {
            $groupResult = $this->evaluateConditionGroup($groupConditions, $selections);
            $groupResults[] = $groupResult;
        }

        // Top-level logic between groups uses the rule's condition_logic
        if ($rule->condition_logic === 'OR') {
            return in_array(true, $groupResults);
        }

        return !in_array(false, $groupResults);
    }

    private function evaluateConditionGroup(Collection $conditions, array $selections): bool
    {
        $results = [];

        foreach ($conditions as $condition) {
            $attributeId = $condition->attribute_id;
            $actual = $selections[$attributeId] ?? null;
            $operator = RuleOperator::from($condition->operator);
            $results[] = $operator->evaluate($actual, $condition->value);
        }

        // Within a group, use the first condition's logic (AND/OR)
        $logic = $conditions->first()->logic ?? 'AND';

        if ($logic === 'OR') {
            return in_array(true, $results);
        }

        return !in_array(false, $results);
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
