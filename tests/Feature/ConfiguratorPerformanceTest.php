<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleItem;
use App\Models\CompatibilityRule;
use App\Models\Component;
use App\Models\ConfigurationRule;
use App\Models\Product;
use App\Models\RuleAction;
use App\Models\RuleCondition;
use App\Services\Configurator\ConfiguratorService;
use App\Services\Pricing\PricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Upper bound on the number of SQL queries ConfiguratorService::evaluate() may
 * run for a fully populated product graph. The eager-load tree in evaluate()
 * resolves rules, BOM, compatibility, part-number pattern, attributes/options
 * and the batched pricing options; the few remaining queries are the explicit
 * part-number/pricing lookups. Kept well under a per-rule/per-option N+1.
 */
const CONFIGURATOR_MAX_QUERIES = 25;

/**
 * Build a product graph with multiple attributes, options, configuration rules,
 * BOM rules and a compatibility rule so the eager-load tree is exercised.
 *
 * @return array{product: Product, selections: array<int, string>}
 */
function makePerformanceProduct(): array
{
    $product = Product::factory()->published()->create(['base_price' => '1000.00']);

    /** @var array<int, Attribute> $attributes */
    $attributes = [];
    /** @var array<int, string> $selections */
    $selections = [];

    for ($i = 0; $i < 3; $i++) {
        $attribute = Attribute::factory()->create();
        $options = AttributeOption::factory()->count(3)->create([
            'attribute_id' => $attribute->id,
            'price_adjustment' => '10.00',
            'is_active' => true,
        ]);

        $product->attributes()->attach($attribute->id, [
            'is_required' => false,
            'is_visible' => true,
            'sort_order' => $i,
        ]);

        $attributes[] = $attribute;
        $selections[$attribute->id] = (string) $options->first()->id;
    }

    // Two configuration rules with conditions + actions (show/hide).
    foreach (['show', 'hide'] as $index => $actionType) {
        $rule = ConfigurationRule::factory()->create([
            'product_id' => $product->id,
            'condition_logic' => 'AND',
        ]);
        RuleCondition::factory()->create([
            'configuration_rule_id' => $rule->id,
            'attribute_id' => $attributes[0]->id,
            'operator' => 'equals',
            'value' => $selections[$attributes[0]->id],
            'logic' => 'AND',
            'group' => 0,
        ]);
        RuleAction::factory()->create([
            'configuration_rule_id' => $rule->id,
            'action_type' => $actionType,
            'target_type' => 'attribute',
            'target_id' => $attributes[1 + $index]->id,
            'value' => null,
        ]);
    }

    // Two BOM rules each contributing a component.
    foreach (range(1, 2) as $n) {
        $component = Component::factory()->create(['sales_price' => '25.00']);
        $bomRule = BomRule::factory()->create([
            'product_id' => $product->id,
            'condition_logic' => 'AND',
        ]);
        BomRuleItem::factory()->create([
            'bom_rule_id' => $bomRule->id,
            'component_id' => $component->id,
            'quantity' => $n,
            'is_mandatory' => true,
        ]);
    }

    // One compatibility rule across the first two attributes.
    CompatibilityRule::factory()->create([
        'product_id' => $product->id,
        'attribute_id' => $attributes[0]->id,
        'option_id' => $attributes[0]->options()->first()->id,
        'target_attribute_id' => $attributes[1]->id,
        'target_option_id' => $attributes[1]->options()->first()->id,
        'type' => 'incompatible',
    ]);

    return compact('product', 'selections');
}

test('evaluate runs with no lazy loading and a bounded query count', function () {
    ['product' => $product, 'selections' => $selections] = makePerformanceProduct();

    Model::preventLazyLoading(true);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    try {
        $result = app(ConfiguratorService::class)->evaluate($product->fresh(), $selections);
    } finally {
        Model::preventLazyLoading(false);
    }

    expect($result->status)->toBeIn(['valid', 'invalid']);
    expect($queryCount)->toBeLessThanOrEqual(CONFIGURATOR_MAX_QUERIES);
});

test('pricing issues a single options query regardless of the number of selections', function () {
    ['product' => $product, 'selections' => $selections] = makePerformanceProduct();

    $pricing = app(PricingService::class);

    $countOptionsQueries = function (array $sel) use ($pricing, $product): int {
        $count = 0;
        DB::listen(function ($query) use (&$count): void {
            if (str_contains($query->sql, 'from "attribute_options"')) {
                $count++;
            }
        });

        $pricing->calculate($product->fresh(), $sel);

        return $count;
    };

    $singleSelection = array_slice($selections, 0, 1, true);

    expect($countOptionsQueries($singleSelection))->toBe(1);
    expect($countOptionsQueries($selections))->toBe(1);
});
