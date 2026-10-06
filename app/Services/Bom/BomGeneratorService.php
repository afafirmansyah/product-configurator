<?php

namespace App\Services\Bom;

use App\Enums\RuleOperator;
use App\Models\ConfigurationSession;
use App\Models\Product;

class BomGeneratorService
{
    public function generate(Product $product, array $selections): array
    {
        $rules = $product->bomRules()
            ->with(['conditions.attribute', 'items.component.category'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $bom = [];
        $itemNo = 1;

        foreach ($rules as $rule) {
            if (!$this->conditionsMet($rule, $selections)) {
                continue;
            }

            foreach ($rule->items as $item) {
                $componentId = $item->component_id;
                $component = $item->component;

                if (isset($bom[$componentId])) {
                    $bom[$componentId]['quantity'] += (float) $item->quantity;
                    $bom[$componentId]['total_price'] = $bom[$componentId]['quantity'] * $bom[$componentId]['unit_price'];
                } else {
                    $bom[$componentId] = [
                        'item_no'               => str_pad($itemNo++, 2, '0', STR_PAD_LEFT),
                        'component_id'          => $componentId,
                        'part_number'           => $component->internal_part_number ?? $component->code,
                        'sales_part_number'     => $component->sales_part_number,
                        'description'           => $component->name,
                        'long_description'      => $component->description,
                        'type'                  => $component->category?->name ?? 'Component',
                        'manufacturer'          => $component->manufacturer,
                        'manufacturer_part_no'  => $component->manufacturer_part_number,
                        'quantity'              => (float) $item->quantity,
                        'unit'                  => 'EA',
                        'unit_price'            => (float) $component->sales_price,
                        'cost'                  => (float) $component->cost,
                        'total_price'           => (float) $item->quantity * (float) $component->sales_price,
                        'currency'              => $component->currency ?? 'EUR',
                        'is_mandatory'          => (bool) $item->is_mandatory,
                        'lead_time'             => null,
                        'remarks'               => null,
                    ];
                }
            }
        }

        return array_values($bom);
    }

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
            if ($item['quantity'] <= 0) {
                $errors[] = "{$ref}: Invalid quantity ({$item['quantity']}).";
            }
            if ($item['unit_price'] <= 0 && $item['is_mandatory']) {
                $warnings[] = "{$ref}: No unit price defined.";
            }
        }

        // Check duplicates
        $partNumbers = array_column($bomItems, 'part_number');
        $duplicates = array_diff_assoc($partNumbers, array_unique($partNumbers));
        foreach (array_unique($duplicates) as $dup) {
            $warnings[] = "Duplicate part number detected: {$dup}";
        }

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    public function saveToSession(ConfigurationSession $session, array $bomItems): void
    {
        // Delete existing BOM items
        $session->bomItems()->delete();

        foreach ($bomItems as $item) {
            $session->bomItems()->create([
                'component_id' => $item['component_id'],
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'is_mandatory' => $item['is_mandatory'],
            ]);
        }

        $validation = $this->validate($bomItems);

        $session->update([
            'bom_status'       => $validation['valid'] ? 'generated' : 'failed',
            'bom_revision'     => $session->bom_revision + 1,
            'bom_generated_at' => now(),
            'bom_snapshot'     => $bomItems,
            'validation_errors' => $validation,
        ]);
    }

    public function getDocumentName(ConfigurationSession $session, string $type = 'BOM', string $ext = 'pdf'): string
    {
        $product = strtoupper(str_replace(' ', '_', $session->product->name ?? 'PRODUCT'));
        $cfgNo = str_replace('-', '', $session->configuration_number);
        $rev = 'REV' . str_pad($session->bom_revision, 2, '0', STR_PAD_LEFT);
        return "{$type}_{$product}_{$cfgNo}_{$rev}.{$ext}";
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
