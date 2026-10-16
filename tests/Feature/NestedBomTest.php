<?php

use App\Enums\RuleOperator;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleCondition;
use App\Models\BomRuleItem;
use App\Models\Component;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Bom\BomDocumentRenderer;
use App\Services\Bom\BomGeneratorService;
use App\Services\Configurator\ConfiguratorService;
use Database\Seeders\RolesAndPermissionsSeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Create an always-applying active BOM rule on $product with a single line.
 *
 * @param  array<string, mixed>  $itemAttributes
 */
function nestedBomLine(Product $product, array $itemAttributes): BomRuleItem
{
    $rule = BomRule::factory()->create([
        'product_id' => $product->id,
        'condition_logic' => 'AND',
        'is_active' => true,
    ]);

    return BomRuleItem::factory()->create(array_merge([
        'bom_rule_id' => $rule->id,
        'is_mandatory' => true,
    ], $itemAttributes));
}

function nestedBomSubAssembly(Product $parent, Product $child, float $quantity): BomRuleItem
{
    return nestedBomLine($parent, ['component_id' => null, 'child_product_id' => $child->id, 'quantity' => $quantity]);
}

function nestedBomActingUser(string $role = 'product_manager'): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    return $user;
}

test('a sub-assembly expands its child BOM using shared selections and child defaults', function () {
    $shared = Attribute::factory()->create();
    $sharedMatch = AttributeOption::factory()->create(['attribute_id' => $shared->id]);
    $sharedOther = AttributeOption::factory()->create(['attribute_id' => $shared->id]);
    $childOnly = Attribute::factory()->create();
    $childDefault = AttributeOption::factory()->create(['attribute_id' => $childOnly->id]);

    $parent = Product::factory()->published()->create();
    $parent->attributes()->attach($shared->id, ['is_required' => false, 'is_visible' => true, 'sort_order' => 0]);

    $child = Product::factory()->create();
    $child->attributes()->attach($shared->id, ['is_required' => false, 'is_visible' => true, 'sort_order' => 0]);
    $child->attributes()->attach($childOnly->id, [
        'is_required' => false,
        'is_visible' => true,
        'sort_order' => 1,
        'default_value' => (string) $childDefault->id,
    ]);

    nestedBomSubAssembly($parent, $child, 2);

    $component = Component::factory()->create();
    $childItem = nestedBomLine($child, ['component_id' => $component->id, 'quantity' => 3]);
    foreach ([[$shared, $sharedMatch], [$childOnly, $childDefault]] as [$attribute, $option]) {
        BomRuleCondition::factory()->create([
            'bom_rule_id' => $childItem->bom_rule_id,
            'attribute_id' => $attribute->id,
            'operator' => RuleOperator::Equals->value,
            'value' => (string) $option->id,
            'logic' => 'AND',
            'group' => 0,
        ]);
    }

    $rows = app(BomGeneratorService::class)->generate($parent->fresh(), [$shared->id => (string) $sharedMatch->id]);

    expect($rows)->toHaveCount(2)
        ->and($rows[0])->toMatchArray([
            'item_no' => '01',
            'component_id' => null,
            'product_id' => $child->id,
            'is_assembly' => true,
            'type' => 'Assembly',
            'level' => 0,
            'path' => '01',
            'parent_path' => null,
            'description' => $child->name,
        ])
        ->and((float) $rows[0]['quantity'])->toBe(2.0)
        ->and($rows[1])->toMatchArray([
            'item_no' => '01.01',
            'component_id' => $component->id,
            'is_assembly' => false,
            'level' => 1,
            'path' => '01.01',
            'parent_path' => '01',
        ])
        ->and((float) $rows[1]['quantity'])->toBe(6.0);

    $withOtherSharedValue = app(BomGeneratorService::class)->generate($parent->fresh(), [$shared->id => (string) $sharedOther->id]);

    expect($withOtherSharedValue)->toHaveCount(1)
        ->and($withOtherSharedValue[0]['is_assembly'])->toBeTrue();
});

test('quantities multiply through three levels', function () {
    $top = Product::factory()->published()->create();
    $middle = Product::factory()->create();
    $bottom = Product::factory()->create();
    $component = Component::factory()->create();

    nestedBomSubAssembly($top, $middle, 2);
    nestedBomSubAssembly($middle, $bottom, 3);
    nestedBomLine($bottom, ['component_id' => $component->id, 'quantity' => 4]);

    $rows = app(BomGeneratorService::class)->generate($top->fresh(), []);

    expect(array_column($rows, 'path'))->toBe(['01', '01.01', '01.01.01'])
        ->and(array_column($rows, 'level'))->toBe([0, 1, 2])
        ->and(array_map(fn (array $row): float => (float) $row['quantity'], $rows))->toBe([2.0, 6.0, 24.0]);
});

test('assembly rows carry no price so totals equal the leaf totals', function () {
    $parent = Product::factory()->published()->create(['base_price' => 999]);
    $child = Product::factory()->create(['base_price' => 500]);
    $topComponent = Component::factory()->create(['sales_price' => 10]);
    $childComponent = Component::factory()->create(['sales_price' => 5]);

    nestedBomLine($parent, ['component_id' => $topComponent->id, 'quantity' => 1]);
    nestedBomSubAssembly($parent, $child, 2);
    nestedBomLine($child, ['component_id' => $childComponent->id, 'quantity' => 3]);

    $rows = app(BomGeneratorService::class)->generate($parent->fresh(), []);
    $assembly = collect($rows)->firstWhere('is_assembly', true);
    $leafTotal = collect($rows)->where('is_assembly', false)->sum(fn (array $row): float => (float) $row['total_price']);

    expect((float) $assembly['unit_price'])->toBe(0.0)
        ->and((float) $assembly['total_price'])->toBe(0.0)
        ->and((float) $assembly['rollup_total'])->toBe(30.0)
        ->and($leafTotal)->toBe(40.0)
        ->and(collect($rows)->sum(fn (array $row): float => (float) $row['total_price']))->toBe(40.0);
});

test('saving a nested BOM persists only leaf rows with multiplied quantities', function () {
    $parent = Product::factory()->published()->create();
    $child = Product::factory()->create();
    $component = Component::factory()->create();

    nestedBomSubAssembly($parent, $child, 2);
    nestedBomLine($child, ['component_id' => $component->id, 'quantity' => 3]);

    $session = ConfigurationSession::factory()->create(['product_id' => $parent->id]);
    $result = app(ConfiguratorService::class)->evaluate($parent->fresh(), []);

    app(BomGeneratorService::class)->saveToSession($session, $result->bom);

    $session->refresh();

    expect($session->bom_snapshot)->toHaveCount(2)
        ->and($session->bomItems)->toHaveCount(1)
        ->and($session->bomItems->first()->component_id)->toBe($component->id)
        ->and((float) $session->bomItems->first()->quantity)->toBe(6.0)
        ->and($session->bom_status)->toBe('generated');
});

test('a generation-time cycle makes the configuration invalid instead of throwing', function () {
    $owner = nestedBomActingUser('engineering');
    $productA = Product::factory()->published()->create();
    $productB = Product::factory()->create();

    nestedBomSubAssembly($productA, $productB, 1);
    nestedBomSubAssembly($productB, $productA, 1);

    $result = app(ConfiguratorService::class)->evaluate($productA->fresh(), []);

    expect($result->status)->toBe('invalid')
        ->and($result->bom)->toBe([])
        ->and(implode(' ', $result->validation['errors']))->toContain('cycle');

    $session = ConfigurationSession::factory()->forUser($owner)->create(['product_id' => $productA->id]);

    $this->actingAs($owner)
        ->post(route('sales.configuration.generate-bom', $session))
        ->assertRedirect()
        ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'cycle'));

    expect($session->fresh()->bom_snapshot)->toBeNull()
        ->and($session->fresh()->bom_revision)->toBe(0);
});

test('a BOM deeper than the configured maximum depth is rejected', function () {
    config(['configurator.bom.max_depth' => 2]);

    $top = Product::factory()->published()->create();
    $middle = Product::factory()->create();
    $bottom = Product::factory()->create();

    nestedBomSubAssembly($top, $middle, 1);
    nestedBomSubAssembly($middle, $bottom, 1);
    nestedBomLine($bottom, ['component_id' => Component::factory()->create()->id, 'quantity' => 1]);

    $result = app(ConfiguratorService::class)->evaluate($top->fresh(), []);

    expect($result->status)->toBe('invalid')
        ->and(implode(' ', $result->validation['errors']))->toContain('maximum nesting depth of 2');

    $twoLevels = app(ConfiguratorService::class)->evaluate($middle->fresh(), []);

    expect($twoLevels->status)->toBe('valid')
        ->and($twoLevels->bom)->toHaveCount(2);
});

test('admin save rejects a product as its own sub-assembly', function () {
    $product = Product::factory()->create();

    $this->actingAs(nestedBomActingUser())
        ->post(route('admin.bom.store', $product), [
            'name' => 'Self reference',
            'condition_logic' => 'AND',
            'items' => [['child_product_id' => $product->id, 'quantity' => '1']],
        ])
        ->assertSessionHasErrors('items.0.child_product_id');

    expect(BomRule::where('product_id', $product->id)->exists())->toBeFalse();
});

test('admin save stores a sub-assembly line and rejects the indirect cycle on update', function () {
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();
    $user = nestedBomActingUser();

    $this->actingAs($user)
        ->post(route('admin.bom.store', $productA), [
            'name' => 'A uses B',
            'condition_logic' => 'AND',
            'items' => [['child_product_id' => $productB->id, 'quantity' => '2']],
        ])
        ->assertSessionHasNoErrors();

    $storedItem = BomRuleItem::whereHas('bomRule', fn ($query) => $query->where('product_id', $productA->id))->sole();

    expect($storedItem->component_id)->toBeNull()
        ->and($storedItem->child_product_id)->toBe($productB->id);

    $ruleB = BomRule::factory()->create(['product_id' => $productB->id, 'condition_logic' => 'AND', 'is_active' => true]);

    $this->actingAs($user)
        ->put(route('admin.bom.update', $ruleB), [
            'name' => 'B uses A',
            'condition_logic' => 'AND',
            'items' => [['child_product_id' => $productA->id, 'quantity' => '1']],
        ])
        ->assertSessionHasErrors('items.0.child_product_id');

    expect($ruleB->items()->count())->toBe(0);
});

test('admin save rejects a line with both a component and a sub-assembly', function () {
    $product = Product::factory()->create();
    $child = Product::factory()->create();

    $this->actingAs(nestedBomActingUser())
        ->post(route('admin.bom.store', $product), [
            'name' => 'Both',
            'condition_logic' => 'AND',
            'items' => [[
                'component_id' => Component::factory()->create()->id,
                'child_product_id' => $child->id,
                'quantity' => '1',
            ]],
        ])
        ->assertSessionHasErrors('items.0.component_id');
});

test('admin save rejects a child product with attributes the parent does not have', function () {
    $parent = Product::factory()->create();
    $child = Product::factory()->create();
    $childOnly = Attribute::factory()->create(['name' => 'Child Only Voltage']);
    $child->attributes()->attach($childOnly->id, ['is_required' => false, 'is_visible' => true, 'sort_order' => 0]);

    $this->actingAs(nestedBomActingUser())
        ->post(route('admin.bom.store', $parent), [
            'name' => 'Ineligible child',
            'condition_logic' => 'AND',
            'items' => [['child_product_id' => $child->id, 'quantity' => '1']],
        ])
        ->assertSessionHasErrors('items.0.child_product_id');

    expect(session('errors')->first('items.0.child_product_id'))->toContain('Child Only Voltage');

    $parent->attributes()->attach($childOnly->id, ['is_required' => false, 'is_visible' => true, 'sort_order' => 0]);

    $this->actingAs(nestedBomActingUser())
        ->post(route('admin.bom.store', $parent), [
            'name' => 'Eligible child',
            'condition_logic' => 'AND',
            'items' => [['child_product_id' => $child->id, 'quantity' => '1']],
        ])
        ->assertSessionHasNoErrors();
});

test('Excel and PDF exports show the nesting level of each row', function () {
    $parent = Product::factory()->published()->create();
    $child = Product::factory()->create();
    $component = Component::factory()->create(['name' => 'Nested Bracket']);

    nestedBomSubAssembly($parent, $child, 2);
    nestedBomLine($child, ['component_id' => $component->id, 'quantity' => 3]);

    $configuration = ConfigurationSession::factory()->create(['product_id' => $parent->id]);
    $bomItems = app(ConfiguratorService::class)->evaluate($parent->fresh(), [])->bom;

    $path = tempnam(sys_get_temp_dir(), 'nested-bom');

    try {
        file_put_contents($path, app(BomDocumentRenderer::class)->renderExcel($configuration, $bomItems, true));
        $sheet = IOFactory::load($path)->getSheetByName('BOM');

        expect($sheet->getCell('B3')->getValue())->toBe('Level')
            ->and($sheet->getCell('K3')->getValue())->toBe('Total Price')
            ->and($sheet->getCell('B4')->getValue())->toBe(0)
            ->and($sheet->getCell('B5')->getValue())->toBe(1)
            ->and($sheet->getCell('D5')->getValue())->toBe('  Nested Bracket');
    } finally {
        @unlink($path);
    }

    $html = view('sales.exports.bom-pdf', [
        'configuration' => $configuration,
        'bomItems' => $bomItems,
        'validation' => ['errors' => [], 'warnings' => []],
        'showPricing' => true,
    ])->render();

    expect($html)->toContain('Nested Bracket')
        ->and($html)->toContain($child->name)
        ->and($html)->toContain('Level');
});
