<?php

use App\Enums\RuleOperator;
use App\Livewire\Configurator\ProductConfigurator;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleCondition;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\ConfigurationRule;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\RuleAction;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('sales');
    $this->actingAs($user);

    $this->product = Product::factory()->published()->create();
    $this->attribute = Attribute::factory()->create();
    $this->option = AttributeOption::factory()->create(['attribute_id' => $this->attribute->id]);

    $this->product->attributes()->attach($this->attribute->id, [
        'is_required' => true,
        'is_visible' => true,
        'sort_order' => 0,
    ]);

    $this->bomComponent = Component::factory()->create(['sales_price' => '10.00']);

    $bomRule = BomRule::factory()->create([
        'product_id' => $this->product->id,
        'condition_logic' => 'AND',
    ]);
    BomRuleCondition::factory()->create([
        'bom_rule_id' => $bomRule->id,
        'attribute_id' => $this->attribute->id,
        'operator' => RuleOperator::Equals->value,
        'value' => (string) $this->option->id,
        'logic' => 'AND',
        'group' => 0,
    ]);
    BomRuleItem::factory()->create([
        'bom_rule_id' => $bomRule->id,
        'component_id' => $this->bomComponent->id,
        'quantity' => 2,
        'is_mandatory' => true,
    ]);
});

test('saving a configuration stores the BOM snapshot, status and first revision', function () {
    Livewire::test(ProductConfigurator::class, ['product' => $this->product->fresh()])
        ->set('selections.'.$this->attribute->id, (string) $this->option->id)
        ->call('saveConfiguration')
        ->assertHasNoErrors();

    $session = ConfigurationSession::sole();

    expect($session->bom_snapshot)->not->toBeNull();
    expect($session->bom_snapshot)->toHaveCount(1);
    expect($session->bom_snapshot[0]['component_id'])->toBe($this->bomComponent->id);
    expect($session->bom_status)->toBe('generated');
    expect($session->bom_generated_at)->not->toBeNull();
    expect($session->bom_revision)->toBe(1);
    expect($session->bomItems)->toHaveCount(count($session->bom_snapshot));
});

test('a component added by a configuration rule is persisted in the snapshot and BOM rows', function () {
    $addedComponent = Component::factory()->create(['sales_price' => '50.00']);

    $configurationRule = ConfigurationRule::factory()->create([
        'product_id' => $this->product->id,
        'condition_logic' => 'AND',
    ]);
    RuleAction::factory()->create([
        'configuration_rule_id' => $configurationRule->id,
        'action_type' => 'add_component',
        'target_type' => 'component',
        'target_id' => $addedComponent->id,
        'value' => null,
    ]);

    Livewire::test(ProductConfigurator::class, ['product' => $this->product->fresh()])
        ->set('selections.'.$this->attribute->id, (string) $this->option->id)
        ->call('saveConfiguration')
        ->assertHasNoErrors();

    $session = ConfigurationSession::sole();

    expect(array_column($session->bom_snapshot, 'component_id'))
        ->toEqualCanonicalizing([$this->bomComponent->id, $addedComponent->id]);
    expect($session->bomItems->pluck('component_id')->all())
        ->toEqualCanonicalizing([$this->bomComponent->id, $addedComponent->id]);
});
