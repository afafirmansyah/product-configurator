<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\CompatibilityRule;
use App\Models\Product;
use App\Services\Configurator\CompatibilityService;

test('incompatible rules disable the target in both directions regardless of selection order', function () {
    $product = Product::factory()->create();

    $attributeA = Attribute::factory()->create();
    $optionA = AttributeOption::factory()->create(['attribute_id' => $attributeA->id]);

    $attributeB = Attribute::factory()->create();
    $optionB = AttributeOption::factory()->create(['attribute_id' => $attributeB->id]);

    CompatibilityRule::factory()->create([
        'product_id' => $product->id,
        'attribute_id' => $attributeA->id,
        'option_id' => $optionA->id,
        'target_attribute_id' => $attributeB->id,
        'target_option_id' => $optionB->id,
        'type' => 'incompatible',
        'reason' => 'A and B cannot be combined.',
    ]);

    $service = app(CompatibilityService::class);

    // Forward direction: selecting A disables B.
    $forward = $service->getDisabledOptions($product, [$attributeA->id => (string) $optionA->id]);
    expect($forward)->toHaveKey($attributeB->id);
    expect($forward[$attributeB->id])->toHaveKey($optionB->id);
    expect($forward[$attributeB->id][$optionB->id])->toBe('A and B cannot be combined.');

    // Reverse direction: selecting B disables A.
    $reverse = $service->getDisabledOptions($product, [$attributeB->id => (string) $optionB->id]);
    expect($reverse)->toHaveKey($attributeA->id);
    expect($reverse[$attributeA->id])->toHaveKey($optionA->id);
    expect($reverse[$attributeA->id][$optionA->id])->toBe('A and B cannot be combined.');
});

test('a compatible rule restricts the target attribute to the listed option', function () {
    $product = Product::factory()->create();

    $trigger = Attribute::factory()->create();
    $triggerOption = AttributeOption::factory()->create(['attribute_id' => $trigger->id]);

    $target = Attribute::factory()->create();
    $allowedOption = AttributeOption::factory()->create(['attribute_id' => $target->id]);
    $disallowedOption = AttributeOption::factory()->create(['attribute_id' => $target->id]);

    CompatibilityRule::factory()->create([
        'product_id' => $product->id,
        'attribute_id' => $trigger->id,
        'option_id' => $triggerOption->id,
        'target_attribute_id' => $target->id,
        'target_option_id' => $allowedOption->id,
        'type' => 'compatible',
        'reason' => 'Only the certified option fits.',
    ]);

    $disabled = app(CompatibilityService::class)
        ->getDisabledOptions($product, [$trigger->id => (string) $triggerOption->id]);

    expect($disabled)->toHaveKey($target->id);
    expect($disabled[$target->id])->toHaveKey($disallowedOption->id);
    expect($disabled[$target->id])->not->toHaveKey($allowedOption->id);
    expect($disabled[$target->id][$disallowedOption->id])->toBe('Only the certified option fits.');
});

test('disabled options always carry a human-readable reason string', function () {
    $product = Product::factory()->create();

    $attributeA = Attribute::factory()->create();
    $optionA = AttributeOption::factory()->create(['attribute_id' => $attributeA->id]);

    $attributeB = Attribute::factory()->create();
    $optionB = AttributeOption::factory()->create(['attribute_id' => $attributeB->id]);

    CompatibilityRule::factory()->create([
        'product_id' => $product->id,
        'attribute_id' => $attributeA->id,
        'option_id' => $optionA->id,
        'target_attribute_id' => $attributeB->id,
        'target_option_id' => $optionB->id,
        'type' => 'incompatible',
        'reason' => null,
    ]);

    $disabled = app(CompatibilityService::class)
        ->getDisabledOptions($product, [$attributeA->id => (string) $optionA->id]);

    expect($disabled[$attributeB->id][$optionB->id])->toBe('Incompatible with current selection.');
});
