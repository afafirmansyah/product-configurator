<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleItem;
use App\Models\BomRuleItemValueSet;
use App\Models\Component;
use App\Models\Product;
use App\Models\User;
use App\Services\Bom\BomGeneratorService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->product = Product::factory()->published()->create();
    $this->color = Attribute::factory()->create();
    $this->size = Attribute::factory()->create();

    foreach ([$this->color, $this->size] as $index => $attribute) {
        $this->product->attributes()->attach($attribute->id, [
            'is_required' => false,
            'is_visible' => true,
            'sort_order' => $index,
        ]);
    }

    $this->red = AttributeOption::factory()->create(['attribute_id' => $this->color->id]);
    $this->blue = AttributeOption::factory()->create(['attribute_id' => $this->color->id]);
    $this->small = AttributeOption::factory()->create(['attribute_id' => $this->size->id]);
    $this->large = AttributeOption::factory()->create(['attribute_id' => $this->size->id]);

    // A rule without conditions always passes, isolating the value-set decision.
    $this->rule = BomRule::factory()->create([
        'product_id' => $this->product->id,
        'condition_logic' => 'AND',
        'is_active' => true,
    ]);
    $this->component = Component::factory()->create(['status' => 'active']);
    $this->item = BomRuleItem::factory()->create([
        'bom_rule_id' => $this->rule->id,
        'component_id' => $this->component->id,
        'quantity' => 2,
        'is_mandatory' => true,
    ]);
});

/**
 * @param  array<int, mixed>  $selections
 * @return list<int>
 */
function valueSetBomComponentIds(Product $product, array $selections): array
{
    $rows = app(BomGeneratorService::class)->generate($product->fresh(), $selections);

    return array_map(fn (array $row): int => (int) $row['component_id'], $rows);
}

/**
 * @param  list<int>  $optionIds
 */
function valueSetFor(BomRuleItem $item, array $optionIds): BomRuleItemValueSet
{
    return BomRuleItemValueSet::factory()
        ->for($item, 'item')
        ->withOptions($optionIds)
        ->create();
}

function valueSetActingUser(): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('product_manager');

    return $user;
}

test('a line is included when every option of its set is selected', function () {
    valueSetFor($this->item, [$this->red->id, $this->large->id]);

    $componentIds = valueSetBomComponentIds($this->product, [
        $this->color->id => (string) $this->red->id,
        $this->size->id => (string) $this->large->id,
    ]);

    expect($componentIds)->toBe([$this->component->id]);
});

test('a line is excluded when one option of its set is not selected', function () {
    valueSetFor($this->item, [$this->red->id, $this->large->id]);

    $componentIds = valueSetBomComponentIds($this->product, [
        $this->color->id => (string) $this->red->id,
        $this->size->id => (string) $this->small->id,
    ]);

    expect($componentIds)->toBe([]);
});

test('a line is included when only one of its alternative sets matches', function () {
    valueSetFor($this->item, [$this->red->id, $this->small->id]);
    valueSetFor($this->item, [$this->blue->id, $this->large->id]);

    $componentIds = valueSetBomComponentIds($this->product, [
        $this->color->id => (string) $this->blue->id,
        $this->size->id => (string) $this->large->id,
    ]);

    expect($componentIds)->toBe([$this->component->id]);
});

test('a line without value sets is always included alongside gated lines', function () {
    $gatedComponent = Component::factory()->create(['status' => 'active']);
    $gatedItem = BomRuleItem::factory()->create([
        'bom_rule_id' => $this->rule->id,
        'component_id' => $gatedComponent->id,
        'quantity' => 1,
    ]);
    valueSetFor($gatedItem, [$this->blue->id]);

    $rows = app(BomGeneratorService::class)->generate($this->product->fresh(), [
        $this->color->id => (string) $this->red->id,
    ]);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['component_id'])->toBe($this->component->id);
    expect((float) $rows[0]['quantity'])->toBe(2.0);
});

test('a multi-select value satisfies a set of options on the same attribute', function (string $format) {
    valueSetFor($this->item, [$this->red->id, $this->blue->id]);

    $selected = $format === 'comma string'
        ? $this->red->id.', '.$this->blue->id
        : [(string) $this->red->id, (string) $this->blue->id];

    expect(valueSetBomComponentIds($this->product, [$this->color->id => $selected]))
        ->toBe([$this->component->id]);
    expect(valueSetBomComponentIds($this->product, [$this->color->id => (string) $this->red->id]))
        ->toBe([]);
})->with(['comma string', 'array']);

test('the admin form persists value sets on store and replaces them on update', function () {
    $user = valueSetActingUser();

    $this->actingAs($user)
        ->post(route('admin.bom.store', $this->product), [
            'name' => 'Gated rule',
            'condition_logic' => 'AND',
            'items' => [[
                'component_id' => $this->component->id,
                'quantity' => '1',
                'value_sets' => [
                    ['name' => 'Red large', 'option_ids' => [$this->red->id, $this->large->id]],
                    ['name' => '', 'option_ids' => [$this->blue->id]],
                ],
            ]],
        ])
        ->assertRedirect(route('admin.bom.index'))
        ->assertSessionHasNoErrors();

    $created = BomRule::where('name', 'Gated rule')->with('items.valueSets.options')->sole();
    $valueSets = $created->items->sole()->valueSets;

    expect($valueSets)->toHaveCount(2);
    expect($valueSets[0]->name)->toBe('Red large');
    expect($valueSets[0]->options->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->red->id, $this->large->id])->sort()->values()->all());
    expect($valueSets[1]->name)->toBeNull();
    expect($valueSets[1]->options->pluck('id')->all())->toBe([$this->blue->id]);

    $oldSetIds = $valueSets->pluck('id')->all();

    $this->actingAs($user)
        ->put(route('admin.bom.update', $created), [
            'name' => 'Gated rule',
            'condition_logic' => 'AND',
            'items' => [[
                'component_id' => $this->component->id,
                'quantity' => '1',
                'value_sets' => [
                    ['name' => 'Small only', 'option_ids' => [$this->small->id]],
                ],
            ]],
        ])
        ->assertRedirect(route('admin.bom.index'))
        ->assertSessionHasNoErrors();

    foreach ($oldSetIds as $oldSetId) {
        $this->assertDatabaseMissing('bom_rule_item_value_sets', ['id' => $oldSetId]);
        $this->assertDatabaseMissing('bom_rule_item_value_set_options', ['bom_rule_item_value_set_id' => $oldSetId]);
    }

    $updatedSet = $created->fresh('items.valueSets.options')->items->sole()->valueSets->sole();

    expect($updatedSet->name)->toBe('Small only');
    expect($updatedSet->options->pluck('id')->all())->toBe([$this->small->id]);
});

test('the admin form rejects value-set options of attributes not on the product', function () {
    $foreignAttribute = Attribute::factory()->create();
    $foreignOption = AttributeOption::factory()->create(['attribute_id' => $foreignAttribute->id]);

    $this->actingAs(valueSetActingUser())
        ->from(route('admin.bom.edit', $this->rule))
        ->put(route('admin.bom.update', $this->rule), [
            'name' => 'Hijacked',
            'condition_logic' => 'AND',
            'items' => [[
                'component_id' => $this->component->id,
                'quantity' => '1',
                'value_sets' => [
                    ['option_ids' => [$this->red->id]],
                    ['option_ids' => [$this->blue->id, $foreignOption->id]],
                ],
            ]],
        ])
        ->assertRedirect(route('admin.bom.edit', $this->rule))
        ->assertSessionHasErrors('items.0.value_sets.1.option_ids')
        ->assertSessionDoesntHaveErrors('items.0.value_sets.0.option_ids');

    expect($this->rule->fresh()->name)->not->toBe('Hijacked');
    $this->assertDatabaseHas('bom_rule_items', ['id' => $this->item->id]);
    $this->assertDatabaseCount('bom_rule_item_value_sets', 0);
});

test('a value set without options is rejected', function () {
    $this->actingAs(valueSetActingUser())
        ->put(route('admin.bom.update', $this->rule), [
            'name' => 'Empty set',
            'condition_logic' => 'AND',
            'items' => [[
                'component_id' => $this->component->id,
                'quantity' => '1',
                'value_sets' => [['name' => 'Nothing selected']],
            ]],
        ])
        ->assertSessionHasErrors('items.0.value_sets.0.option_ids');

    $this->assertDatabaseCount('bom_rule_item_value_sets', 0);
});

test('the edit page lists the item value sets with their options selected', function () {
    valueSetFor($this->item, [$this->red->id]);

    $this->actingAs(valueSetActingUser())
        ->get(route('admin.bom.edit', $this->rule))
        ->assertOk()
        ->assertSee('Attribute value sets')
        ->assertSee('<optgroup label="'.e($this->color->name).'">', false)
        ->assertSee('\u0022option_ids\u0022:[\u0022'.$this->red->id.'\u0022]', false);
});
