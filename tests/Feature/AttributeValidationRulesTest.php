<?php

use App\Enums\AttributeType;
use App\Livewire\Configurator\ProductConfigurator;
use App\Models\Attribute;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Configurator\ConfiguratorService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Create a published product with one visible, optional attribute named "Width".
 *
 * @param  array<string, mixed>|null  $rules
 * @return array{0: Product, 1: Attribute}
 */
function validationRulesProduct(?array $rules, AttributeType $type = AttributeType::Text): array
{
    $product = Product::factory()->published()->create();
    $attribute = Attribute::factory()->ofType($type)->create(['name' => 'Width', 'validation_rules' => $rules]);

    $product->attributes()->attach($attribute->id, ['is_required' => false, 'is_visible' => true, 'sort_order' => 0]);

    return [$product->fresh(), $attribute];
}

function validationRulesActingUser(string $role): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    return $user;
}

test('a value outside the configured bounds is invalid with a field-level error', function (string $value, string $message) {
    [$product, $attribute] = validationRulesProduct(['type' => 'numeric', 'min' => 5, 'max' => 20]);

    $result = app(ConfiguratorService::class)->evaluate($product, [$attribute->id => $value]);

    expect($result->status)->toBe('invalid')
        ->and($result->validation['attribute_errors'])->toBe([$attribute->id => [$message]])
        ->and($result->validation['errors'])->toContain($message);
})->with([
    'below min' => ['3', 'Width must be at least 5.'],
    'above max' => ['25', 'Width must be at most 20.'],
]);

test('value type rules reject input of the wrong kind', function (?array $rules, AttributeType $type, string $value, string $message) {
    [$product, $attribute] = validationRulesProduct($rules, $type);

    $result = app(ConfiguratorService::class)->evaluate($product, [$attribute->id => $value]);

    expect($result->status)->toBe('invalid')
        ->and($result->validation['attribute_errors'][$attribute->id])->toBe([$message]);
})->with([
    'integer rule rejects a fraction' => [['type' => 'integer'], AttributeType::Text, '2.5', 'Width must be a whole number.'],
    'numeric rule rejects text' => [['type' => 'numeric'], AttributeType::Text, 'abc', 'Width must be a number.'],
    'decimal attribute implies numeric' => [null, AttributeType::Decimal, 'abc', 'Width must be a number.'],
]);

test('validation_rules.required makes a visible attribute required', function () {
    [$product, $attribute] = validationRulesProduct(['required' => true]);

    $result = app(ConfiguratorService::class)->evaluate($product, []);

    expect($result->status)->toBe('invalid')
        ->and($result->requiredAttributes)->toContain($attribute->id)
        ->and($result->validation['errors'])->toContain('Width is required.');
});

test('a value satisfying every rule is valid', function () {
    [$product, $attribute] = validationRulesProduct(['type' => 'integer', 'min' => 5, 'max' => 20, 'required' => true]);

    $result = app(ConfiguratorService::class)->evaluate($product, [$attribute->id => '12']);

    expect($result->status)->toBe('valid')
        ->and($result->validation['attribute_errors'])->toBe([]);
});

test('the configurator blocks saving and shows the error under the field', function () {
    $this->actingAs(validationRulesActingUser('sales'));
    [$product, $attribute] = validationRulesProduct(['type' => 'numeric', 'max' => 20]);

    Livewire::test(ProductConfigurator::class, ['product' => $product])
        ->set('selections', [$attribute->id => '25'])
        ->assertSeeHtml('id="error-'.$attribute->id.'" role="alert"')
        ->assertSeeHtml('aria-describedby="error-'.$attribute->id.'"')
        ->assertSee('Width must be at most 20.')
        ->call('saveConfiguration')
        ->assertHasErrors('configuration');

    expect(ConfigurationSession::count())->toBe(0);
});

test('an admin attribute update stores normalised validation rules', function () {
    $attribute = Attribute::factory()->ofType(AttributeType::Integer)->create();

    $this->actingAs(validationRulesActingUser('product_manager'))
        ->put(route('admin.attributes.update', $attribute), [
            'name' => $attribute->name,
            'code' => $attribute->code,
            'type' => AttributeType::Integer->value,
            'validation_rules' => ['type' => 'integer', 'min' => '1', 'max' => '10.5', 'required' => '1'],
        ])
        ->assertRedirect(route('admin.attributes.edit', $attribute))
        ->assertSessionHasNoErrors();

    expect($attribute->fresh()->validation_rules)->toBe(['type' => 'integer', 'min' => 1, 'max' => 10.5, 'required' => true]);
});

test('blank validation inputs clear the rules while omitting them keeps the stored rules', function () {
    $attribute = Attribute::factory()->create(['validation_rules' => ['type' => 'numeric', 'min' => 1]]);
    $user = validationRulesActingUser('product_manager');
    $payload = ['name' => $attribute->name, 'code' => $attribute->code, 'type' => $attribute->type];

    $this->actingAs($user)
        ->put(route('admin.attributes.update', $attribute), $payload)
        ->assertSessionHasNoErrors();

    expect($attribute->fresh()->validation_rules)->toBe(['type' => 'numeric', 'min' => 1]);

    $this->actingAs($user)
        ->put(route('admin.attributes.update', $attribute), $payload + [
            'validation_rules' => ['type' => '', 'min' => '', 'max' => '', 'required' => '0'],
        ])
        ->assertSessionHasNoErrors();

    expect($attribute->fresh()->validation_rules)->toBeNull();
});

test('an admin cannot store a maximum below the minimum', function () {
    $attribute = Attribute::factory()->create();

    $this->actingAs(validationRulesActingUser('product_manager'))
        ->put(route('admin.attributes.update', $attribute), [
            'name' => $attribute->name,
            'code' => $attribute->code,
            'type' => $attribute->type,
            'validation_rules' => ['min' => '10', 'max' => '5'],
        ])
        ->assertSessionHasErrors('validation_rules.max');

    expect($attribute->fresh()->validation_rules)->toBeNull();
});
