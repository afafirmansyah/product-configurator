<?php

use App\Models\Attribute;
use App\Models\BomRule;
use App\Models\BomRuleCondition;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->product = Product::factory()->published()->create();
    $this->attributeA = Attribute::factory()->create();
    $this->attributeB = Attribute::factory()->create();

    foreach ([$this->attributeA, $this->attributeB] as $index => $attribute) {
        $this->product->attributes()->attach($attribute->id, [
            'is_required' => false,
            'is_visible' => true,
            'sort_order' => $index,
        ]);
    }

    $this->oldComponent = Component::factory()->create(['status' => 'active']);
    $this->rule = BomRule::factory()->create([
        'product_id' => $this->product->id,
        'name' => 'Original rule',
        'condition_logic' => 'AND',
        'is_active' => true,
    ]);
    $this->oldCondition = BomRuleCondition::factory()->create([
        'bom_rule_id' => $this->rule->id,
        'attribute_id' => $this->attributeA->id,
        'operator' => 'equals',
        'value' => 'old',
        'logic' => 'AND',
        'group' => 0,
    ]);
    $this->oldItem = BomRuleItem::factory()->create([
        'bom_rule_id' => $this->rule->id,
        'component_id' => $this->oldComponent->id,
        'quantity' => 1,
        'is_mandatory' => true,
    ]);
});

function bomRuleActingUser(string $role): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    return $user;
}

test('a product manager update replaces the rule conditions and items', function () {
    $mandatoryComponent = Component::factory()->create(['status' => 'active']);
    $optionalComponent = Component::factory()->create(['status' => 'active']);

    $this->actingAs(bomRuleActingUser('product_manager'))
        ->put(route('admin.bom.update', $this->rule), [
            'name' => 'Updated rule',
            'condition_logic' => 'OR',
            'is_active' => '0',
            'conditions' => [
                ['attribute_id' => $this->attributeB->id, 'operator' => 'not_equals', 'value' => 'new'],
            ],
            'items' => [
                ['component_id' => $mandatoryComponent->id, 'quantity' => '2.5', 'is_mandatory' => '1'],
                ['component_id' => $optionalComponent->id, 'quantity' => '4'],
            ],
        ])
        ->assertRedirect(route('admin.bom.index'))
        ->assertSessionHasNoErrors();

    $rule = $this->rule->fresh(['conditions', 'items']);

    expect($rule->name)->toBe('Updated rule');
    expect($rule->condition_logic)->toBe('OR');
    expect($rule->is_active)->toBeFalse();

    $this->assertDatabaseMissing('bom_rule_conditions', ['id' => $this->oldCondition->id]);
    $this->assertDatabaseMissing('bom_rule_items', ['id' => $this->oldItem->id]);

    expect($rule->conditions)->toHaveCount(1);
    expect($rule->conditions->first()->attribute_id)->toBe($this->attributeB->id);
    expect($rule->conditions->first()->operator)->toBe('not_equals');
    expect($rule->conditions->first()->value)->toBe('new');

    expect($rule->items)->toHaveCount(2);
    $mandatoryItem = $rule->items->firstWhere('component_id', $mandatoryComponent->id);
    $optionalItem = $rule->items->firstWhere('component_id', $optionalComponent->id);
    expect($mandatoryItem->quantity)->toBe('2.50');
    expect($mandatoryItem->is_mandatory)->toBeTrue();
    expect($optionalItem->quantity)->toBe('4.00');
    expect($optionalItem->is_mandatory)->toBeFalse();
});

test('an update without items is rejected and leaves the rule untouched', function () {
    $this->actingAs(bomRuleActingUser('product_manager'))
        ->from(route('admin.bom.edit', $this->rule))
        ->put(route('admin.bom.update', $this->rule), [
            'name' => 'Updated rule',
            'condition_logic' => 'AND',
            'items' => [],
        ])
        ->assertRedirect(route('admin.bom.edit', $this->rule))
        ->assertSessionHasErrors('items');

    expect($this->rule->fresh()->name)->toBe('Original rule');
    $this->assertDatabaseHas('bom_rule_items', ['id' => $this->oldItem->id]);
});

test('a product manager can create a BOM rule through the shared writer', function () {
    $component = Component::factory()->create(['status' => 'active']);

    $this->actingAs(bomRuleActingUser('product_manager'))
        ->post(route('admin.bom.store', $this->product), [
            'name' => 'Created rule',
            'condition_logic' => 'AND',
            'is_active' => '1',
            'conditions' => [
                ['attribute_id' => $this->attributeA->id, 'operator' => 'equals', 'value' => '7'],
            ],
            'items' => [
                ['component_id' => $component->id, 'quantity' => '3', 'is_mandatory' => '1'],
            ],
        ])
        ->assertRedirect(route('admin.bom.index'))
        ->assertSessionHasNoErrors();

    $created = BomRule::where('name', 'Created rule')->with(['conditions', 'items'])->sole();

    expect($created->product_id)->toBe($this->product->id);
    expect($created->code)->toStartWith('BOM-');
    expect($created->is_active)->toBeTrue();
    expect($created->sort_order)->toBe(1);
    expect($created->conditions)->toHaveCount(1);
    expect($created->conditions->first()->logic)->toBe('AND');
    expect($created->items)->toHaveCount(1);
    expect($created->items->first()->quantity)->toBe('3.00');
});

test('the edit page renders a form that submits to the update route', function () {
    $this->actingAs(bomRuleActingUser('product_manager'))
        ->get(route('admin.bom.edit', $this->rule))
        ->assertOk()
        ->assertSee('action="'.route('admin.bom.update', $this->rule).'"', false)
        ->assertSee('name="_method" value="PUT"', false)
        ->assertSee('value="Original rule"', false);
});

test('non-admin roles cannot open or submit the BOM rule edit form', function (string $role) {
    $user = bomRuleActingUser($role);

    $this->actingAs($user)
        ->get(route('admin.bom.edit', $this->rule))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('admin.bom.update', $this->rule), [
            'name' => 'Hijacked',
            'condition_logic' => 'AND',
            'items' => [['component_id' => $this->oldComponent->id, 'quantity' => '9']],
        ])
        ->assertForbidden();

    expect($this->rule->fresh()->name)->toBe('Original rule');
})->with(['engineering', 'sales']);
