<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\CompatibilityRule;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->product = Product::factory()->published()->create();
    $this->attribute = Attribute::factory()->create();
    $this->targetAttribute = Attribute::factory()->create();
    $this->option = AttributeOption::factory()->create(['attribute_id' => $this->attribute->id]);
    $this->otherOption = AttributeOption::factory()->create(['attribute_id' => $this->attribute->id]);
    $this->targetOption = AttributeOption::factory()->create(['attribute_id' => $this->targetAttribute->id]);
    $this->otherTargetOption = AttributeOption::factory()->create(['attribute_id' => $this->targetAttribute->id]);

    $this->rule = CompatibilityRule::factory()->create([
        'product_id' => $this->product->id,
        'attribute_id' => $this->attribute->id,
        'option_id' => $this->option->id,
        'target_attribute_id' => $this->targetAttribute->id,
        'target_option_id' => $this->targetOption->id,
        'type' => 'incompatible',
        'reason' => 'Original reason',
        'is_active' => true,
    ]);
});

function compatibilityActingUser(string $role): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    return $user;
}

/**
 * @return array<string, mixed>
 */
function compatibilityPayload(CompatibilityRule $rule, array $overrides = []): array
{
    return array_merge([
        'product_id' => $rule->product_id,
        'attribute_id' => $rule->attribute_id,
        'option_id' => $rule->option_id,
        'target_attribute_id' => $rule->target_attribute_id,
        'target_option_id' => $rule->target_option_id,
        'type' => $rule->type,
        'reason' => $rule->reason,
        'is_active' => '1',
    ], $overrides);
}

test('a product manager can update type, reason and options of a compatibility rule', function () {
    $this->actingAs(compatibilityActingUser('product_manager'))
        ->put(route('admin.compatibility.update', $this->rule), compatibilityPayload($this->rule, [
            'option_id' => $this->otherOption->id,
            'target_option_id' => $this->otherTargetOption->id,
            'type' => 'compatible',
            'reason' => 'Updated reason',
            'is_active' => '0',
        ]))
        ->assertRedirect(route('admin.compatibility.index'))
        ->assertSessionHasNoErrors();

    $rule = $this->rule->fresh();

    expect($rule->type)->toBe('compatible');
    expect($rule->reason)->toBe('Updated reason');
    expect($rule->option_id)->toBe($this->otherOption->id);
    expect($rule->target_option_id)->toBe($this->otherTargetOption->id);
    expect($rule->is_active)->toBeFalse();
});

test('an unknown compatibility type is rejected', function () {
    $this->actingAs(compatibilityActingUser('product_manager'))
        ->put(route('admin.compatibility.update', $this->rule), compatibilityPayload($this->rule, [
            'type' => 'sometimes',
        ]))
        ->assertSessionHasErrors('type');

    expect($this->rule->fresh()->type)->toBe('incompatible');
});

test('the edit page renders a prefilled form that submits to the update route', function () {
    $this->actingAs(compatibilityActingUser('product_manager'))
        ->get(route('admin.compatibility.edit', $this->rule))
        ->assertOk()
        ->assertSee('action="'.route('admin.compatibility.update', $this->rule).'"', false)
        ->assertSee('name="_method" value="PUT"', false)
        ->assertSee('value="Original reason"', false);
});

test('non-admin roles cannot open or submit the compatibility edit form', function (string $role) {
    $user = compatibilityActingUser($role);

    $this->actingAs($user)
        ->get(route('admin.compatibility.edit', $this->rule))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('admin.compatibility.update', $this->rule), compatibilityPayload($this->rule, [
            'reason' => 'Hijacked',
        ]))
        ->assertForbidden();

    expect($this->rule->fresh()->reason)->toBe('Original reason');
})->with(['engineering', 'sales']);
