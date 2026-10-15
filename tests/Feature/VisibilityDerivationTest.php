<?php

use App\Enums\RuleOperator;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ConfigurationRule;
use App\Models\Product;
use App\Models\RuleAction;
use App\Models\RuleCondition;
use App\Services\Configurator\ConfiguratorService;

test('a show rule reveals an attribute hidden by default when its conditions are met', function () {
    $product = Product::factory()->published()->create();

    // Trigger attribute: visible, drives the rule.
    $trigger = Attribute::factory()->create();
    $triggerOption = AttributeOption::factory()->create(['attribute_id' => $trigger->id]);

    // Target attribute: hidden by default via the pivot.
    $hidden = Attribute::factory()->create();

    $product->attributes()->attach($trigger->id, [
        'is_required' => false,
        'is_visible' => true,
        'sort_order' => 0,
    ]);
    $product->attributes()->attach($hidden->id, [
        'is_required' => false,
        'is_visible' => false,
        'sort_order' => 1,
    ]);

    $rule = ConfigurationRule::factory()->create([
        'product_id' => $product->id,
        'condition_logic' => 'AND',
    ]);
    RuleCondition::factory()->create([
        'configuration_rule_id' => $rule->id,
        'attribute_id' => $trigger->id,
        'operator' => RuleOperator::Equals->value,
        'value' => (string) $triggerOption->id,
        'logic' => 'AND',
        'group' => 0,
    ]);
    RuleAction::factory()->create([
        'configuration_rule_id' => $rule->id,
        'action_type' => 'show',
        'target_type' => 'attribute',
        'target_id' => $hidden->id,
    ]);

    $service = app(ConfiguratorService::class);

    // Conditions not met: the attribute stays hidden.
    $before = $service->evaluate($product->fresh(), []);
    expect($before->visibleAttributes)->not->toContain($hidden->id);

    // Conditions met: the show rule reveals the hidden attribute.
    $after = $service->evaluate($product->fresh(), [$trigger->id => (string) $triggerOption->id]);
    expect($after->visibleAttributes)->toContain($hidden->id);
});

test('show overrides hide for the same attribute', function () {
    $product = Product::factory()->published()->create();

    $attribute = Attribute::factory()->create();

    $product->attributes()->attach($attribute->id, [
        'is_required' => false,
        'is_visible' => true,
        'sort_order' => 0,
    ]);

    // A hide rule and a show rule both target the same attribute; show must win.
    $hideRule = ConfigurationRule::factory()->create([
        'product_id' => $product->id,
        'condition_logic' => 'AND',
    ]);
    RuleAction::factory()->create([
        'configuration_rule_id' => $hideRule->id,
        'action_type' => 'hide',
        'target_type' => 'attribute',
        'target_id' => $attribute->id,
    ]);

    $showRule = ConfigurationRule::factory()->create([
        'product_id' => $product->id,
        'condition_logic' => 'AND',
    ]);
    RuleAction::factory()->create([
        'configuration_rule_id' => $showRule->id,
        'action_type' => 'show',
        'target_type' => 'attribute',
        'target_id' => $attribute->id,
    ]);

    $result = app(ConfiguratorService::class)->evaluate($product->fresh(), []);

    expect($result->visibleAttributes)->toContain($attribute->id);
});
