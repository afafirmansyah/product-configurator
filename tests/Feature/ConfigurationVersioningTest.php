<?php

use App\Livewire\Configurator\ProductConfigurator;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->salesUser = User::factory()->create(['is_active' => true]);
    $this->salesUser->assignRole('sales');

    $this->product = Product::factory()->published()->create();
    $this->attribute = Attribute::factory()->create();
    $this->option = AttributeOption::factory()->create(['attribute_id' => $this->attribute->id]);
    $this->product->attributes()->attach($this->attribute->id, ['is_required' => true, 'is_visible' => true, 'sort_order' => 0]);

    $this->component = Component::factory()->create(['status' => 'active', 'sales_price' => '10.00']);
    $this->rule = BomRule::factory()->create([
        'product_id' => $this->product->id,
        'name' => 'Always',
        'condition_logic' => 'AND',
        'is_active' => true,
    ]);
    BomRuleItem::factory()->create([
        'bom_rule_id' => $this->rule->id,
        'component_id' => $this->component->id,
        'quantity' => 2,
        'is_mandatory' => true,
    ]);
});

function versioningSaveConfiguration(Product $product, Attribute $attribute, AttributeOption $option): ConfigurationSession
{
    Livewire::test(ProductConfigurator::class, ['product' => $product->fresh()])
        ->set('selections', [$attribute->id => (string) $option->id])
        ->call('saveConfiguration')
        ->assertHasNoErrors();

    return ConfigurationSession::sole();
}

test('a stored BOM snapshot only changes, and bumps the version, when the user regenerates it', function () {
    $this->actingAs($this->salesUser);
    $configuration = versioningSaveConfiguration($this->product, $this->attribute, $this->option);

    expect($configuration->version)->toBe('1')
        ->and($configuration->bom_revision)->toBe(1);

    $originalSnapshot = $configuration->bom_snapshot;

    $productManager = User::factory()->create(['is_active' => true]);
    $productManager->assignRole('product_manager');

    $this->actingAs($productManager)
        ->put(route('admin.bom.update', $this->rule), [
            'name' => 'Always',
            'condition_logic' => 'AND',
            'is_active' => '1',
            'items' => [['component_id' => $this->component->id, 'quantity' => '5', 'is_mandatory' => '1']],
        ])
        ->assertSessionHasNoErrors();

    $configuration->refresh();

    expect($configuration->bom_snapshot)->toBe($originalSnapshot)
        ->and((float) $configuration->bom_snapshot[0]['quantity'])->toBe(2.0)
        ->and($configuration->version)->toBe('1');

    $this->actingAs($this->salesUser)
        ->get(route('sales.configuration.detail', $configuration))
        ->assertOk()
        ->assertSee('Product definition or BOM rules changed since this BOM was generated (rev');

    $this->post(route('sales.configuration.generate-bom', $configuration))->assertRedirect();
    $configuration->refresh();

    expect((float) $configuration->bom_snapshot[0]['quantity'])->toBe(5.0)
        ->and($configuration->bom_revision)->toBe(2)
        ->and($configuration->version)->toBe('2');

    $this->get(route('sales.configuration.detail', $configuration))
        ->assertOk()
        ->assertDontSee('Product definition or BOM rules changed since this BOM was generated');

    $this->post(route('sales.configuration.generate-bom', $configuration))->assertRedirect();
    $configuration->refresh();

    expect($configuration->bom_revision)->toBe(3)
        ->and($configuration->version)->toBe('2');
});

test('a duplicated configuration starts again at version 1', function () {
    $configuration = ConfigurationSession::factory()->forUser($this->salesUser)->create([
        'product_id' => $this->product->id,
        'version' => '4',
    ]);

    $this->actingAs($this->salesUser)
        ->post(route('sales.configuration.duplicate', $configuration))
        ->assertRedirect();

    $copy = ConfigurationSession::whereKeyNot($configuration->id)->sole();

    expect($copy->version)->toBe('1');
});
