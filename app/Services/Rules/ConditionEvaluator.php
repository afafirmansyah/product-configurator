<?php

namespace App\Services\Rules;

use App\Enums\RuleOperator;

class ConditionEvaluator
{
    /**
     * Evaluate a set of grouped conditions against the given selections.
     *
     * Conditions are grouped by their 'group' column. Within a group the logic
     * is taken from the first condition's 'logic' value (OR => any condition
     * true; otherwise all conditions must be true). Across groups the provided
     * $ruleLogic is applied (OR => any group true; otherwise all groups true).
     * An empty condition set always passes.
     *
     * @param  iterable<int, object>  $conditions  Conditions exposing attribute_id, operator, value, logic, group.
     * @param  string  $ruleLogic  Top-level logic between groups ('AND' or 'OR').
     * @param  array<int|string, mixed>  $selections  Map of attribute_id => selected value.
     */
    public function passes(iterable $conditions, string $ruleLogic, array $selections): bool
    {
        $conditions = collect($conditions);

        if ($conditions->isEmpty()) {
            return true;
        }

        $groups = $conditions->groupBy('group');
        $groupResults = [];

        foreach ($groups as $groupConditions) {
            $results = [];

            foreach ($groupConditions as $condition) {
                $actual = $selections[$condition->attribute_id] ?? null;
                $operator = RuleOperator::from($condition->operator);
                $results[] = $operator->evaluate($actual, $condition->value);
            }

            $logic = $groupConditions->first()->logic ?? 'AND';
            $groupResults[] = $logic === 'OR'
                ? in_array(true, $results)
                : ! in_array(false, $results);
        }

        return $ruleLogic === 'OR'
            ? in_array(true, $groupResults)
            : ! in_array(false, $groupResults);
    }
}
