<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\Product;
use App\Services\Bom\BomGeneratorService;
use App\Services\Pricing\PricingService;

test('two option adjustments 0.10 and 0.20 sum to exactly 0.30 without float drift', function () {
    $product = Product::factory()->create(['base_price' => '0.00']);

    $attribute = Attribute::factory()->create();
    $optionA = AttributeOption::factory()->create([
        'attribute_id' => $attribute->id,
        'price_adjustment' => '0.10',
    ]);
    $optionB = AttributeOption::factory()->create([
        'attribute_id' => $attribute->id,
        'price_adjustment' => '0.20',
    ]);

    $result = app(PricingService::class)->calculate($product, [
        $attribute->id => [(string) $optionA->id, (string) $optionB->id],
    ]);

    expect($result['options_total'])->toBe('0.30');
    expect($result['total'])->toBe('0.30');
});

test('product base plus several adjustments equals the exact decimal total', function () {
    $product = Product::factory()->create(['base_price' => '100.05']);

    $attribute = Attribute::factory()->create();
    $optionA = AttributeOption::factory()->create([
        'attribute_id' => $attribute->id,
        'price_adjustment' => '0.10',
    ]);
    $optionB = AttributeOption::factory()->create([
        'attribute_id' => $attribute->id,
        'price_adjustment' => '0.20',
    ]);

    $result = app(PricingService::class)->calculate($product, [
        $attribute->id => [(string) $optionA->id, (string) $optionB->id],
    ]);

    expect($result['base_price'])->toBe('100.05');
    expect($result['subtotal'])->toBe('100.35');
    expect($result['total'])->toBe('100.35');
});

test('a BOM row total_price equals quantity times unit_price exactly', function () {
    $product = Product::factory()->create();

    $component = Component::factory()->create(['sales_price' => '12.34']);

    $bomRule = BomRule::factory()->create([
        'product_id' => $product->id,
        'condition_logic' => 'AND',
    ]);

    BomRuleItem::factory()->create([
        'bom_rule_id' => $bomRule->id,
        'component_id' => $component->id,
        'quantity' => 3,
        'is_mandatory' => true,
    ]);

    $bom = app(BomGeneratorService::class)->generate($product->fresh(), []);

    expect($bom)->toHaveCount(1);
    expect($bom[0]['unit_price'])->toBe('12.34');
    expect($bom[0]['total_price'])->toBe('37.02');
});
