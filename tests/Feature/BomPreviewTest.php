<?php

use App\Livewire\Configurator\ProductConfigurator;
use App\Models\BomRule;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Bom\BomGeneratorService;
use App\Services\Configurator\ConfiguratorService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->user = User::factory()->create(['is_active' => true]);
    $this->user->assignRole('sales');

    $this->parent = Product::factory()->published()->create();
    $this->child = Product::factory()->create(['name' => 'Pump Sub-Assembly']);
    $this->component = Component::factory()->create(['name' => 'Impeller Seal']);

    $assemblyRule = BomRule::factory()->create(['product_id' => $this->parent->id, 'condition_logic' => 'AND', 'is_active' => true]);
    BomRuleItem::factory()->create([
        'bom_rule_id' => $assemblyRule->id,
        'component_id' => null,
        'child_product_id' => $this->child->id,
        'quantity' => 2,
    ]);

    $childRule = BomRule::factory()->create(['product_id' => $this->child->id, 'condition_logic' => 'AND', 'is_active' => true]);
    BomRuleItem::factory()->create([
        'bom_rule_id' => $childRule->id,
        'component_id' => $this->component->id,
        'quantity' => 3,
    ]);
});

test('the configuration detail page previews the BOM as a nested tree', function () {
    $configuration = ConfigurationSession::factory()->forUser($this->user)->create(['product_id' => $this->parent->id]);
    $bom = app(ConfiguratorService::class)->evaluate($this->parent->fresh(), [])->bom;
    app(BomGeneratorService::class)->saveToSession($configuration, $bom);

    $this->actingAs($this->user)
        ->get(route('sales.configuration.detail', $configuration))
        ->assertOk()
        ->assertSee('role="tree"', false)
        ->assertSeeInOrder(['aria-level="1"', 'Pump Sub-Assembly', 'Assembly', 'aria-level="2"', 'Impeller Seal'], false);
});

test('the configurator review step shows the nested BOM tree', function () {
    $this->actingAs($this->user);

    Livewire::test(ProductConfigurator::class, ['product' => $this->parent->fresh()])
        ->set('showSummary', true)
        ->assertSeeHtml('role="tree"')
        ->assertSeeHtmlInOrder(['Pump Sub-Assembly', 'aria-level="2"', 'Impeller Seal']);
});
