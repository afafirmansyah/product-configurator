<?php

use App\Livewire\Configurator\ProductConfigurator;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('sales');
    $this->actingAs($user);
});

test('an invalid configuration missing a required attribute is not persisted', function () {
    $product = Product::factory()->published()->create();

    $attribute = Attribute::factory()->create();
    AttributeOption::factory()->create(['attribute_id' => $attribute->id]);

    $product->attributes()->attach($attribute->id, [
        'is_required' => true,
        'is_visible' => true,
        'sort_order' => 0,
    ]);

    Livewire::test(ProductConfigurator::class, ['product' => $product->fresh()])
        ->set('selections', [])
        ->call('saveConfiguration')
        ->assertHasErrors('configuration');

    expect(ConfigurationSession::count())->toBe(0);
});

test('a valid configuration is persisted with its items', function () {
    $product = Product::factory()->published()->create();

    $attribute = Attribute::factory()->create();
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);

    $product->attributes()->attach($attribute->id, [
        'is_required' => true,
        'is_visible' => true,
        'sort_order' => 0,
    ]);

    Livewire::test(ProductConfigurator::class, ['product' => $product->fresh()])
        ->set('selections', [$attribute->id => (string) $option->id])
        ->call('saveConfiguration')
        ->assertHasNoErrors();

    expect(ConfigurationSession::count())->toBe(1);

    $session = ConfigurationSession::first();
    expect($session->items)->toHaveCount(1);
    expect($session->configuration_number)->toStartWith('CFG-'.date('Y').'-');
});
