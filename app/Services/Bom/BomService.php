<?php

namespace App\Services\Bom;

use App\Enums\RuleOperator;
use App\Models\Product;
use Illuminate\Support\Collection;

class BomService
{
    public function generate(Product $product, array $selections): array
    {
        $rules = $product->bomRules()
            ->with(['conditions.attribute', 'items.component'])
            ->get();

        $bom = [];

        foreach ($rules as $rule) {
            if (!$this->conditionsMet($rule, $selections)) {
                continue;
            }

            foreach ($rule->items as $item) {
                $componentId = $item->component_id;

                if (isset($bom[$componentId])) {
                    $bom[$componentId]['quantity'] += $item->quantity;
                } else {
                    $bom[$componentId] = [
                        'component_id' => $componentId,
                        'code' => $item->component->code,
                        'name' => $item->component->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->component->sales_price,
                        'is_mandatory' => $item->is_mandatory,
                    ];
                }
            }
        }

        return array_values($bom);
    }

    private function conditionsMet(object $rule, array $selections): bool
    {
        $conditions = $rule->conditions;

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
            $groupResults[] = $logic === 'OR' ? in_array(true, $results) : !in_array(false, $results);
        }

        return $rule->condition_logic === 'OR'
            ? in_array(true, $groupResults)
            : !in_array(false, $groupResults);
    }
}
