<?php

use App\Enums\QuantityMode;
use App\Models\Attribute;
use App\Models\BomRule;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Bom\BomGeneratorService;
use App\Services\Configurator\ConfiguratorService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->product = Product::factory()->published()->create();
    $this->length = Attribute::factory()->create(['code' => 'length', 'name' => 'Length']);
    $this->width = Attribute::factory()->create(['code' => 'width', 'name' => 'Width']);

    foreach ([$this->length, $this->width] as $index => $attribute) {
        $this->product->attributes()->attach($attribute->id, [
            'is_required' => false,
            'is_visible' => true,
            'sort_order' => $index,
        ]);
    }

    // A rule without conditions always passes, isolating the quantity decision.
    $this->rule = BomRule::factory()->create([
        'product_id' => $this->product->id,
        'condition_logic' => 'AND',
        'is_active' => true,
    ]);
    $this->component = Component::factory()->create([
        'name' => 'Mounting bracket',
        'status' => 'active',
        'sales_price' => '10.00',
    ]);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function computedQuantityItem(BomRule $rule, Component $component, array $attributes): BomRuleItem
{
    return BomRuleItem::factory()->create([
        'bom_rule_id' => $rule->id,
        'component_id' => $component->id,
        'is_mandatory' => true,
        ...$attributes,
    ]);
}

function computedQuantityActingUser(): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('product_manager');

    return $user;
}

test('a line without a computed mode keeps its stored quantity', function (?string $mode) {
    computedQuantityItem($this->rule, $this->component, ['quantity' => 3, 'quantity_mode' => $mode]);

    $rows = app(BomGeneratorService::class)->generate($this->product->fresh(), []);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['quantity'])->toBe(3.0);
})->with(['null mode' => null, 'fixed mode' => QuantityMode::Fixed->value]);

test('attribute mode multiplies the base quantity by the numeric selection', function () {
    computedQuantityItem($this->rule, $this->component, [
        'quantity' => 2,
        'quantity_mode' => QuantityMode::Attribute->value,
        'quantity_attribute_id' => $this->length->id,
    ]);

    $rows = app(BomGeneratorService::class)->generate($this->product->fresh(), [$this->length->id => '5']);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['quantity'])->toBe(10.0);
    expect($rows[0]['total_price'])->toBe('100.00');
});

test('formula mode rounds to two decimals or up to a whole number', function (bool $roundUp, float $expected) {
    computedQuantityItem($this->rule, $this->component, [
        'quantity' => 1,
        'quantity_mode' => QuantityMode::Formula->value,
        'quantity_formula' => '{length}/3',
        'quantity_round_up' => $roundUp,
    ]);

    $rows = app(BomGeneratorService::class)->generate($this->product->fresh(), [$this->length->id => '10']);

    expect($rows[0]['quantity'])->toBe($expected);
})->with([
    'round up' => [true, 4.0],
    'two decimals' => [false, 3.33],
]);

test('repeated components accumulate their resolved quantities', function () {
    computedQuantityItem($this->rule, $this->component, ['quantity' => 1]);
    computedQuantityItem($this->rule, $this->component, [
        'quantity' => 1,
        'quantity_mode' => QuantityMode::Formula->value,
        'quantity_formula' => '{length} * {width}',
    ]);

    $rows = app(BomGeneratorService::class)->generate($this->product->fresh(), [
        $this->length->id => '2',
        $this->width->id => '3',
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['quantity'])->toBe(7.0);
});

test('a line whose quantity cannot be resolved is omitted with a warning', function (array $itemAttributes, array $selections, string $warning) {
    // Datasets name attributes by code because ids only exist once beforeEach ran.
    if (isset($itemAttributes['quantity_attribute_code'])) {
        $itemAttributes['quantity_attribute_id'] = Attribute::where('code', $itemAttributes['quantity_attribute_code'])->value('id');
        unset($itemAttributes['quantity_attribute_code']);
    }

    computedQuantityItem($this->rule, $this->component, ['quantity' => 1, ...$itemAttributes]);
    $fixedComponent = Component::factory()->create(['status' => 'active']);
    computedQuantityItem($this->rule, $fixedComponent, ['quantity' => 1]);

    $selections = collect($selections)->mapWithKeys(fn (string $value, string $code): array => [
        Attribute::where('code', $code)->value('id') => $value,
    ])->all();

    $result = app(ConfiguratorService::class)->evaluate($this->product->fresh(), $selections);

    expect(array_map('intval', array_column($result->bom, 'component_id')))->toBe([$fixedComponent->id]);
    expect($result->validation['valid'])->toBeTrue();
    expect($result->validation['bom_warnings'])->toBe(["Mounting bracket: {$warning}"]);
    expect($result->validation['warnings'])->toContain("Mounting bracket: {$warning}");
})->with([
    'division by zero' => [
        ['quantity_mode' => 'formula', 'quantity_formula' => '{length}/{width}'],
        ['length' => '4', 'width' => '0'],
        'Quantity formula error: Division by zero',
    ],
    'non-numeric attribute input' => [
        ['quantity_mode' => 'attribute', 'quantity_attribute_code' => 'length'],
        ['length' => 'long'],
        'Length has no numeric value for the quantity.',
    ],
    'non-positive result' => [
        ['quantity_mode' => 'formula', 'quantity_formula' => '{length} - 5'],
        ['length' => '5'],
        'Resolved quantity 0 is not positive.',
    ],
]);

test('generating the BOM stores omitted-line warnings with the snapshot', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('engineering');

    computedQuantityItem($this->rule, $this->component, [
        'quantity' => 1,
        'quantity_mode' => QuantityMode::Formula->value,
        'quantity_formula' => '{length}/{width}',
    ]);

    $session = ConfigurationSession::factory()->forUser($owner)->create(['product_id' => $this->product->id]);
    $session->items()->create(['attribute_id' => $this->length->id, 'value' => '4']);
    $session->items()->create(['attribute_id' => $this->width->id, 'value' => '0']);

    $this->actingAs($owner)
        ->post(route('sales.configuration.generate-bom', $session))
        ->assertRedirect();

    $session->refresh();

    expect($session->bom_snapshot)->toBe([]);
    expect($session->validation_errors['warnings'])
        ->toContain('Mounting bracket: Quantity formula error: Division by zero');
});

test('the admin form persists computed quantity settings for the active mode only', function () {
    $this->actingAs(computedQuantityActingUser())
        ->post(route('admin.bom.store', $this->product), [
            'name' => 'Computed rule',
            'condition_logic' => 'AND',
            'items' => [
                [
                    'component_id' => $this->component->id,
                    'quantity' => '1',
                    'quantity_mode' => 'formula',
                    'quantity_attribute_id' => $this->width->id,
                    'quantity_formula' => '{length} / 2 + 1',
                    'quantity_round_up' => '1',
                ],
                [
                    'component_id' => $this->component->id,
                    'quantity' => '2',
                    'quantity_mode' => 'attribute',
                    'quantity_attribute_id' => $this->length->id,
                    'quantity_formula' => '{width}',
                ],
            ],
        ])
        ->assertRedirect(route('admin.bom.index'))
        ->assertSessionHasNoErrors();

    $items = BomRule::where('name', 'Computed rule')->sole()->items()->orderBy('id')->get();

    expect($items[0]->quantity_mode)->toBe(QuantityMode::Formula);
    expect($items[0]->quantity_formula)->toBe('{length} / 2 + 1');
    expect($items[0]->quantity_attribute_id)->toBeNull();
    expect($items[0]->quantity_round_up)->toBeTrue();
    expect($items[1]->quantity_mode)->toBe(QuantityMode::Attribute);
    expect($items[1]->quantity_attribute_id)->toBe($this->length->id);
    expect($items[1]->quantity_formula)->toBeNull();
    expect($items[1]->quantity_round_up)->toBeFalse();
});

test('the admin form rejects invalid quantity formulas', function (string $formula) {
    $this->actingAs(computedQuantityActingUser())
        ->post(route('admin.bom.store', $this->product), [
            'name' => 'Broken formula',
            'condition_logic' => 'AND',
            'items' => [[
                'component_id' => $this->component->id,
                'quantity' => '1',
                'quantity_mode' => 'formula',
                'quantity_formula' => $formula,
            ]],
        ])
        ->assertSessionHasErrors('items.0.quantity_formula');

    expect(BomRule::where('name', 'Broken formula')->exists())->toBeFalse();
})->with([
    'syntax error' => '{length} +* 2',
    'code not on the product' => '{height} * 2',
    'missing formula' => '',
]);

test('the admin form rejects a quantity attribute not assigned to the product', function () {
    $foreignAttribute = Attribute::factory()->create();

    $this->actingAs(computedQuantityActingUser())
        ->post(route('admin.bom.store', $this->product), [
            'name' => 'Foreign attribute',
            'condition_logic' => 'AND',
            'items' => [[
                'component_id' => $this->component->id,
                'quantity' => '1',
                'quantity_mode' => 'attribute',
                'quantity_attribute_id' => $foreignAttribute->id,
            ]],
        ])
        ->assertSessionHasErrors('items.0.quantity_attribute_id');

    expect(BomRule::where('name', 'Foreign attribute')->exists())->toBeFalse();
});
