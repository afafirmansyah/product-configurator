<?php

use App\Enums\RuleOperator;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleCondition;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Configurator\ConfiguratorService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('the save path and generate path produce identical BOM rows for identical selections', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('engineering');

    $product = Product::factory()->published()->create();

    $attribute = Attribute::factory()->create();
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);

    $product->attributes()->attach($attribute->id, [
        'is_required' => true,
        'is_visible' => true,
        'sort_order' => 0,
    ]);

    $component = Component::factory()->create();

    $bomRule = BomRule::factory()->create([
        'product_id' => $product->id,
        'condition_logic' => 'AND',
    ]);

    BomRuleCondition::factory()->create([
        'bom_rule_id' => $bomRule->id,
        'attribute_id' => $attribute->id,
        'operator' => RuleOperator::Equals->value,
        'value' => (string) $option->id,
        'logic' => 'AND',
        'group' => 0,
    ]);

    BomRuleItem::factory()->create([
        'bom_rule_id' => $bomRule->id,
        'component_id' => $component->id,
        'quantity' => 3,
        'is_mandatory' => true,
    ]);

    $selections = [$attribute->id => (string) $option->id];

    // Save path: the BOM carried on the ConfigurationResult DTO.
    $savePathBom = app(ConfiguratorService::class)
        ->evaluate($product->fresh(), $selections)
        ->bom;

    // Generate path: the BOM snapshot stored by the controller route.
    $session = ConfigurationSession::factory()->forUser($owner)->create([
        'product_id' => $product->id,
    ]);
    $session->items()->create([
        'attribute_id' => $attribute->id,
        'value' => (string) $option->id,
    ]);

    $this->actingAs($owner)
        ->post(route('sales.configuration.generate-bom', $session))
        ->assertRedirect();

    $generatePathBom = $session->fresh()->bom_snapshot;

    expect($generatePathBom)->toEqual($savePathBom);
    expect($generatePathBom)->toHaveCount(1);
    expect($generatePathBom[0]['component_id'])->toBe($component->id);
    expect((float) $generatePathBom[0]['quantity'])->toBe(3.0);
});
