<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\ConfigurationRule;
use App\Models\Product;
use App\Models\RuleAction;
use App\Services\Configurator\ConfiguratorService;

/**
 * Build a published product with one select attribute carrying two options.
 *
 * @return array{product: Product, attribute: Attribute, optionA: AttributeOption, optionB: AttributeOption}
 */
function makeConfiguredProduct(): array
{
    $product = Product::factory()->published()->create(['base_price' => '100.00']);

    $attribute = Attribute::factory()->create();
    $optionA = AttributeOption::factory()->create([
        'attribute_id' => $attribute->id,
        'name' => 'Option A',
        'price_adjustment' => '0.00',
    ]);
    $optionB = AttributeOption::factory()->create([
        'attribute_id' => $attribute->id,
        'name' => 'Option B',
        'price_adjustment' => '0.00',
    ]);

    $product->attributes()->attach($attribute->id, [
        'is_required' => false,
        'is_visible' => true,
        'sort_order' => 0,
    ]);

    return compact('product', 'attribute', 'optionA', 'optionB');
}

/**
 * Create an always-passing configuration rule (no conditions) with a single action.
 */
function makeRuleWithAction(Product $product, string $actionType, string $targetType, ?int $targetId, ?string $value): void
{
    $rule = ConfigurationRule::factory()->create([
        'product_id' => $product->id,
        'condition_logic' => 'AND',
    ]);

    RuleAction::factory()->create([
        'configuration_rule_id' => $rule->id,
        'action_type' => $actionType,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'value' => $value,
    ]);
}

test('disable_option disables the option keyed by its owning attribute', function () {
    ['product' => $product, 'attribute' => $attribute, 'optionA' => $optionA] = makeConfiguredProduct();

    makeRuleWithAction($product, 'disable_option', 'option', $optionA->id, 'Not available.');

    $result = app(ConfiguratorService::class)->evaluate($product->fresh(), []);

    expect($result->disabledOptions)->toHaveKey($attribute->id);
    expect($result->disabledOptions[$attribute->id])->toHaveKey($optionA->id);
    expect($result->disabledOptions[$attribute->id][$optionA->id])->toBe('Not available.');
});

test('set_value forces the selection so technical specs reflect the forced option', function () {
    ['product' => $product, 'attribute' => $attribute, 'optionB' => $optionB] = makeConfiguredProduct();

    makeRuleWithAction($product, 'set_value', 'attribute', $attribute->id, (string) $optionB->id);

    // User selected nothing; the rule forces optionB.
    $result = app(ConfiguratorService::class)->evaluate($product->fresh(), []);

    expect($result->options[$attribute->id])->toBe((string) $optionB->id);

    $spec = collect($result->technicalSpecifications)->firstWhere('attribute', $attribute->name);
    expect($spec)->not->toBeNull();
    expect($spec['value'])->toBe('Option B');
});

test('add_component appends an extra BOM row', function () {
    ['product' => $product] = makeConfiguredProduct();

    $component = Component::factory()->create(['sales_price' => '50.00']);

    makeRuleWithAction($product, 'add_component', 'component', $component->id, null);

    $result = app(ConfiguratorService::class)->evaluate($product->fresh(), []);

    expect($result->bom)->toHaveCount(1);
    expect($result->bom[0]['component_id'])->toBe($component->id);
    expect($result->bom[0]['is_mandatory'])->toBeFalse();
    expect($result->bom[0]['unit_price'])->toBe('50.00');
});

test('add_component uses a positive numeric action value as the quantity', function (?string $value, float $expectedQuantity) {
    ['product' => $product] = makeConfiguredProduct();

    $component = Component::factory()->create(['sales_price' => '50.00']);

    makeRuleWithAction($product, 'add_component', 'component', $component->id, $value);

    $result = app(ConfiguratorService::class)->evaluate($product->fresh(), []);

    expect($result->bom[0]['quantity'])->toBe($expectedQuantity);
})->with([
    'numeric value' => ['4', 4.0],
    'no value' => [null, 1.0],
    'non-numeric value' => ['many', 1.0],
    'non-positive value' => ['0', 1.0],
]);

test('remove_component drops a BOM row produced by a BOM rule', function () {
    ['product' => $product] = makeConfiguredProduct();

    $component = Component::factory()->create();

    $bomRule = BomRule::factory()->create([
        'product_id' => $product->id,
        'condition_logic' => 'AND',
    ]);
    BomRuleItem::factory()->create([
        'bom_rule_id' => $bomRule->id,
        'component_id' => $component->id,
        'quantity' => 1,
        'is_mandatory' => true,
    ]);

    // Baseline: the BOM rule produces one row.
    expect(app(ConfiguratorService::class)->evaluate($product->fresh(), [])->bom)->toHaveCount(1);

    makeRuleWithAction($product, 'remove_component', 'component', $component->id, null);

    $result = app(ConfiguratorService::class)->evaluate($product->fresh(), []);

    expect($result->bom)->toHaveCount(0);
});

test('set_price overrides the pricing total computed in cents', function () {
    ['product' => $product] = makeConfiguredProduct();

    makeRuleWithAction($product, 'set_price', 'attribute', null, '999.95');

    $result = app(ConfiguratorService::class)->evaluate($product->fresh(), []);

    expect($result->pricing['total'])->toBe('999.95');
});

test('set_part_number overrides the configuration code', function () {
    ['product' => $product] = makeConfiguredProduct();

    makeRuleWithAction($product, 'set_part_number', 'attribute', null, 'CUSTOM-PN-001');

    $result = app(ConfiguratorService::class)->evaluate($product->fresh(), []);

    expect($result->configurationCode)->toBe('CUSTOM-PN-001');
});
