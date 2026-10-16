<?php

namespace App\Services\Bom;

use App\Enums\QuantityMode;
use App\Models\BomRule;
use App\Models\BomRuleCondition;
use App\Models\BomRuleItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Persists BOM rules together with their conditions and component lines.
 *
 * Both create() and update() run inside a single transaction. update() replaces
 * the rule's conditions and items wholesale (delete + recreate) so the stored
 * rule always mirrors the submitted form. Item value sets cascade with their
 * item and are rewritten from the submitted data. A line keeps only the
 * quantity settings of its mode: the attribute for "attribute", the formula for
 * "formula"; no mode is stored as null, which the resolver treats as fixed.
 *
 * @phpstan-type BomRuleData array{
 *     name: string,
 *     condition_logic: string,
 *     is_active?: bool|int|string|null,
 *     conditions?: list<array{attribute_id?: int|string|null, operator?: string|null, value?: string|null}>,
 *     items: list<array{
 *         component_id: int|string,
 *         quantity: int|float|string,
 *         quantity_mode?: string|null,
 *         quantity_attribute_id?: int|string|null,
 *         quantity_formula?: string|null,
 *         quantity_round_up?: bool|int|string|null,
 *         is_mandatory?: bool|int|string|null,
 *         value_sets?: list<array{name?: string|null, option_ids: list<int|string>}>|null
 *     }>
 * }
 */
class BomRuleWriter
{
    /**
     * Create a BOM rule for the given product.
     *
     * @param  BomRuleData  $data
     */
    public function create(Product $product, array $data): BomRule
    {
        return DB::transaction(function () use ($product, $data): BomRule {
            $rule = BomRule::create([
                'product_id' => $product->id,
                'name' => $data['name'],
                'code' => 'BOM-'.strtoupper(Str::random(8)),
                'condition_logic' => $data['condition_logic'],
                'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'sort_order' => BomRule::where('product_id', $product->id)->count(),
            ]);

            $this->writeLines($rule, $data);

            return $rule;
        });
    }

    /**
     * Update a BOM rule, replacing all of its conditions and items.
     *
     * @param  BomRuleData  $data
     */
    public function update(BomRule $rule, array $data): BomRule
    {
        return DB::transaction(function () use ($rule, $data): BomRule {
            $rule->update([
                'name' => $data['name'],
                'condition_logic' => $data['condition_logic'],
                'is_active' => array_key_exists('is_active', $data)
                    ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)
                    : $rule->is_active,
            ]);

            $rule->conditions()->delete();
            $rule->items()->delete();

            $this->writeLines($rule, $data);

            return $rule->refresh();
        });
    }

    /**
     * Create the condition and item rows for a rule. Conditions without an
     * attribute are skipped; all conditions join with AND inside group 0.
     *
     * @param  BomRuleData  $data
     */
    private function writeLines(BomRule $rule, array $data): void
    {
        foreach ($data['conditions'] ?? [] as $condition) {
            if (empty($condition['attribute_id'])) {
                continue;
            }

            BomRuleCondition::create([
                'bom_rule_id' => $rule->id,
                'attribute_id' => $condition['attribute_id'],
                'operator' => $condition['operator'],
                'value' => $condition['value'] ?? null,
                'logic' => 'AND',
                'group' => 0,
            ]);
        }

        foreach ($data['items'] as $item) {
            $quantityMode = QuantityMode::tryFrom((string) ($item['quantity_mode'] ?? ''));

            $ruleItem = BomRuleItem::create([
                'bom_rule_id' => $rule->id,
                'component_id' => $item['component_id'],
                'quantity' => $item['quantity'],
                'quantity_mode' => $quantityMode,
                'quantity_attribute_id' => $quantityMode === QuantityMode::Attribute ? $item['quantity_attribute_id'] ?? null : null,
                'quantity_formula' => $quantityMode === QuantityMode::Formula ? $item['quantity_formula'] ?? null : null,
                'quantity_round_up' => filter_var($item['quantity_round_up'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_mandatory' => filter_var($item['is_mandatory'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);

            foreach ($item['value_sets'] ?? [] as $valueSet) {
                $ruleItem->valueSets()
                    ->create(['name' => filled($valueSet['name'] ?? null) ? $valueSet['name'] : null])
                    ->options()
                    ->attach(array_values(array_unique(array_map('intval', $valueSet['option_ids']))));
            }
        }
    }
}
