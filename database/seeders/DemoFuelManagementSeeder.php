<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\BomRule;
use App\Models\BomRuleCondition;
use App\Models\BomRuleItem;
use App\Models\CompatibilityRule;
use App\Models\Component;
use App\Models\ComponentCategory;
use App\Models\ConfigurationRule;
use App\Models\PartNumberPattern;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductFamily;
use App\Models\ProductPrice;
use App\Models\RuleAction;
use App\Models\RuleCondition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoFuelManagementSeeder extends Seeder
{
    public function run(): void
    {
        $family = ProductFamily::where('code', 'STAT_FMS')->first();

        // ── Product ──────────────────────────────────────────────────────────
        $product = Product::firstOrCreate(
            ['code' => 'HECONOMY'],
            [
                'ulid' => Str::ulid(),
                'product_family_id' => $family->id,
                'name' => 'Heconomy',
                'slug' => 'heconomy',
                'sku' => 'HEC-FMS-001',
                'manufacturer' => 'Demo Industrial',
                'short_description' => 'Advanced fuel management system for stationary applications with multi-tank support.',
                'long_description' => "The Heconomy is a comprehensive fuel management system designed for industrial and commercial fuel dispensing operations.\n\nIt supports multiple tank configurations, various communication protocols, and integrates with RFID access control systems.",
                'applications' => "- Industrial fuel depots\n- Commercial fleet fueling\n- Mining operations\n- Construction sites\n- Agricultural facilities",
                'features' => "- Multi-tank support (up to 8 tanks)\n- Multiple communication options\n- RFID access control\n- Real-time monitoring\n- Comprehensive reporting",
                'base_price' => 2500.00,
                'currency' => 'EUR',
                'status' => 'published',
                'version' => '2.0',
                'is_configurable' => true,
            ]
        );

        // ── Attributes & Options ─────────────────────────────────────────────
        $attrs = $this->createAttributes();

        // ── Assign attributes to product ─────────────────────────────────────
        $steps = [
            'system_type' => ['step' => 'System Type',    'order' => 0, 'required' => true],
            'tank_quantity' => ['step' => 'System Type',    'order' => 1, 'required' => true],
            'communication' => ['step' => 'Communication',  'order' => 2, 'required' => true],
            'flowmeter_type' => ['step' => 'Flowmeter',      'order' => 3, 'required' => true],
            'rfid' => ['step' => 'Accessories',    'order' => 4, 'required' => false],
            'display' => ['step' => 'Accessories',    'order' => 5, 'required' => false],
            'power_supply' => ['step' => 'Installation',   'order' => 6, 'required' => true],
            'enclosure' => ['step' => 'Installation',   'order' => 7, 'required' => true],
            'installation' => ['step' => 'Installation',   'order' => 8, 'required' => true],
        ];

        $syncData = [];
        foreach ($steps as $code => $cfg) {
            if (isset($attrs[$code])) {
                $syncData[$attrs[$code]->id] = [
                    'is_required' => $cfg['required'],
                    'is_visible' => true,
                    'sort_order' => $cfg['order'],
                    'step_name' => $cfg['step'],
                ];
            }
        }
        $product->attributes()->sync($syncData);

        // ── Components ───────────────────────────────────────────────────────
        $components = $this->createComponents();

        // ── Price List ───────────────────────────────────────────────────────
        $priceList = PriceList::firstOrCreate(
            ['code' => 'EUR_STANDARD'],
            [
                'name' => 'EUR Standard Price List',
                'currency' => 'EUR',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        ProductPrice::firstOrCreate(
            ['product_id' => $product->id, 'price_list_id' => $priceList->id],
            ['base_price' => 2500.00]
        );

        // ── Rules ────────────────────────────────────────────────────────────
        $this->createRules($product, $attrs, $components);

        // ── BOM Rules ────────────────────────────────────────────────────────
        $this->createBomRules($product, $attrs, $components);

        // ── Compatibility Rules ───────────────────────────────────────────────
        $this->createCompatibilityRules($product, $attrs);

        // ── Part Number Pattern ───────────────────────────────────────────────
        PartNumberPattern::firstOrCreate(
            ['product_id' => $product->id],
            [
                'name' => 'Heconomy Part Number',
                'is_active' => true,
                'segments' => [
                    ['type' => 'literal', 'value' => 'HEC', 'separator' => '-'],
                    ['type' => 'literal', 'value' => 'FMS', 'separator' => '-'],
                    ['type' => 'attribute', 'value' => 'tank_quantity', 'separator' => '-', 'default' => 'XX'],
                    ['type' => 'attribute', 'value' => 'communication', 'separator' => '-', 'default' => 'X'],
                    ['type' => 'attribute', 'value' => 'flowmeter_type', 'separator' => '-', 'default' => 'X'],
                    ['type' => 'attribute', 'value' => 'rfid', 'separator' => '', 'default' => ''],
                ],
            ]
        );
    }

    private function createAttributes(): array
    {
        $defs = [
            [
                'code' => 'system_type',
                'name' => 'System Type',
                'type' => 'select',
                'options' => [
                    ['code' => 'STANDARD', 'name' => 'Standard', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['code' => 'ADVANCED', 'name' => 'Advanced', 'price_adjustment' => 500, 'sort_order' => 1],
                    ['code' => 'ENTERPRISE', 'name' => 'Enterprise', 'price_adjustment' => 1200, 'sort_order' => 2],
                ],
            ],
            [
                'code' => 'tank_quantity',
                'name' => 'Tank Quantity',
                'type' => 'integer',
                'unit' => 'Tanks',
                'options' => [],
            ],
            [
                'code' => 'communication',
                'name' => 'Communication',
                'type' => 'select',
                'options' => [
                    ['code' => 'RS485', 'name' => 'RS485', 'price_adjustment' => 0, 'part_number' => 'RS', 'sort_order' => 0],
                    ['code' => 'ETHERNET', 'name' => 'Ethernet', 'price_adjustment' => 80, 'part_number' => 'E', 'sort_order' => 1],
                    ['code' => 'WIFI', 'name' => 'Wi-Fi', 'price_adjustment' => 120, 'part_number' => 'W', 'sort_order' => 2],
                    ['code' => 'CELLULAR', 'name' => 'Cellular (4G LTE)', 'price_adjustment' => 250, 'part_number' => 'C', 'sort_order' => 3],
                    ['code' => 'LORA', 'name' => 'LoRa', 'price_adjustment' => 180, 'part_number' => 'L', 'sort_order' => 4],
                ],
            ],
            [
                'code' => 'flowmeter_type',
                'name' => 'Flowmeter Type',
                'type' => 'select',
                'options' => [
                    ['code' => 'PULSE', 'name' => 'Pulse Output', 'price_adjustment' => 0, 'part_number' => 'P', 'sort_order' => 0],
                    ['code' => 'RS485_FM', 'name' => 'RS485 Modbus', 'price_adjustment' => 150, 'part_number' => 'R', 'sort_order' => 1],
                    ['code' => 'CORIOLIS', 'name' => 'Coriolis', 'price_adjustment' => 800, 'part_number' => 'CO', 'sort_order' => 2],
                ],
            ],
            [
                'code' => 'rfid',
                'name' => 'RFID Access Control',
                'type' => 'select',
                'options' => [
                    ['code' => 'DISABLED', 'name' => 'Disabled', 'price_adjustment' => 0, 'part_number' => '', 'sort_order' => 0],
                    ['code' => 'ENABLED', 'name' => 'Enabled', 'price_adjustment' => 350, 'part_number' => 'RFID', 'sort_order' => 1],
                ],
            ],
            [
                'code' => 'display',
                'name' => 'Display',
                'type' => 'select',
                'options' => [
                    ['code' => 'NONE', 'name' => 'No Display', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['code' => 'LCD', 'name' => 'LCD Display', 'price_adjustment' => 120, 'sort_order' => 1],
                    ['code' => 'TOUCHSCREEN', 'name' => 'Touchscreen', 'price_adjustment' => 350, 'sort_order' => 2],
                ],
            ],
            [
                'code' => 'power_supply',
                'name' => 'Power Supply',
                'type' => 'select',
                'options' => [
                    ['code' => '24VDC', 'name' => '24 VDC', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['code' => '110VAC', 'name' => '110 VAC', 'price_adjustment' => 50, 'sort_order' => 1],
                    ['code' => '220VAC', 'name' => '220 VAC', 'price_adjustment' => 50, 'sort_order' => 2],
                    ['code' => 'SOLAR', 'name' => 'Solar + Battery', 'price_adjustment' => 600, 'sort_order' => 3],
                ],
            ],
            [
                'code' => 'enclosure',
                'name' => 'Enclosure',
                'type' => 'select',
                'options' => [
                    ['code' => 'INDOOR', 'name' => 'Indoor (IP20)', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['code' => 'OUTDOOR', 'name' => 'Outdoor (IP65)', 'price_adjustment' => 200, 'sort_order' => 1],
                    ['code' => 'HAZARDOUS', 'name' => 'Hazardous Area (ATEX)', 'price_adjustment' => 800, 'sort_order' => 2],
                ],
            ],
            [
                'code' => 'installation',
                'name' => 'Installation Type',
                'type' => 'select',
                'options' => [
                    ['code' => 'INDOOR_INST', 'name' => 'Indoor Installation', 'price_adjustment' => 0, 'sort_order' => 0],
                    ['code' => 'OUTDOOR_INST', 'name' => 'Outdoor Installation', 'price_adjustment' => 300, 'sort_order' => 1],
                    ['code' => 'REMOTE', 'name' => 'Remote Site', 'price_adjustment' => 600, 'sort_order' => 2],
                ],
            ],
        ];

        $result = [];
        foreach ($defs as $def) {
            $attr = Attribute::firstOrCreate(
                ['code' => $def['code']],
                [
                    'name' => $def['name'],
                    'type' => $def['type'],
                    'unit' => $def['unit'] ?? null,
                    'is_global' => false,
                    'is_active' => true,
                ]
            );

            foreach ($def['options'] as $opt) {
                AttributeOption::firstOrCreate(
                    ['attribute_id' => $attr->id, 'code' => $opt['code']],
                    array_merge($opt, ['attribute_id' => $attr->id, 'is_active' => true])
                );
            }

            $result[$def['code']] = $attr->fresh(['options']);
        }

        return $result;
    }

    private function createComponents(): array
    {
        $ctrlCat = ComponentCategory::where('code', 'CONTROLLER')->first();
        $commCat = ComponentCategory::where('code', 'COMM_MODULE')->first();
        $rfidCat = ComponentCategory::where('code', 'RFID')->first();
        $powerCat = ComponentCategory::where('code', 'POWER')->first();
        $encCat = ComponentCategory::where('code', 'ENCLOSURE')->first();
        $cableCat = ComponentCategory::where('code', 'CABLE')->first();

        $defs = [
            ['code' => 'HEC-CTRL-STD', 'name' => 'Standard Controller', 'cat' => $ctrlCat, 'cost' => 400, 'price' => 600],
            ['code' => 'HEC-CTRL-ADV', 'name' => 'Advanced Controller', 'cat' => $ctrlCat, 'cost' => 700, 'price' => 1000],
            ['code' => 'HEC-COMM-RS485', 'name' => 'RS485 Interface Module', 'cat' => $commCat, 'cost' => 50, 'price' => 80],
            ['code' => 'HEC-COMM-ETH', 'name' => 'Ethernet Module', 'cat' => $commCat, 'cost' => 60, 'price' => 90],
            ['code' => 'HEC-COMM-CELL', 'name' => 'Cellular Modem 4G LTE', 'cat' => $commCat, 'cost' => 150, 'price' => 220],
            ['code' => 'HEC-COMM-WIFI', 'name' => 'Wi-Fi Module', 'cat' => $commCat, 'cost' => 80, 'price' => 120],
            ['code' => 'HEC-COMM-LORA', 'name' => 'LoRa Module', 'cat' => $commCat, 'cost' => 100, 'price' => 150],
            ['code' => 'HEC-ANT-CELL', 'name' => 'Cellular Antenna', 'cat' => $cableCat, 'cost' => 30, 'price' => 50],
            ['code' => 'HEC-ANT-WIFI', 'name' => 'Wi-Fi Antenna', 'cat' => $cableCat, 'cost' => 20, 'price' => 35],
            ['code' => 'HEC-RFID-RDR', 'name' => 'RFID Reader Module', 'cat' => $rfidCat, 'cost' => 200, 'price' => 300],
            ['code' => 'HEC-PWR-24V', 'name' => 'Power Supply 24VDC', 'cat' => $powerCat, 'cost' => 80, 'price' => 120],
            ['code' => 'HEC-ENC-IND', 'name' => 'Indoor Enclosure IP20', 'cat' => $encCat, 'cost' => 100, 'price' => 150],
            ['code' => 'HEC-ENC-OUT', 'name' => 'Outdoor Enclosure IP65', 'cat' => $encCat, 'cost' => 200, 'price' => 300],
            ['code' => 'HEC-ENC-ATEX', 'name' => 'ATEX Enclosure', 'cat' => $encCat, 'cost' => 600, 'price' => 900],
        ];

        $result = [];
        foreach ($defs as $def) {
            $comp = Component::firstOrCreate(
                ['code' => $def['code']],
                [
                    'component_category_id' => $def['cat']?->id,
                    'name' => $def['name'],
                    'cost' => $def['cost'],
                    'sales_price' => $def['price'],
                    'currency' => 'EUR',
                    'status' => 'active',
                ]
            );
            $result[$def['code']] = $comp;
        }

        return $result;
    }

    private function createRules(Product $product, array $attrs, array $components): void
    {
        $rules = [
            [
                'name' => 'Cellular requires modem',
                'code' => 'HEC-RULE-001',
                'type' => 'required',
                'conditions' => [
                    ['attribute' => 'communication', 'operator' => 'equals', 'value' => 'CELLULAR'],
                ],
                'actions' => [
                    ['action_type' => 'require', 'target_type' => 'attribute', 'target_code' => 'communication'],
                ],
            ],
            [
                'name' => 'Tank > 4 requires Advanced Controller',
                'code' => 'HEC-RULE-002',
                'type' => 'warning',
                'conditions' => [
                    ['attribute' => 'tank_quantity', 'operator' => 'greater_than', 'value' => '4'],
                ],
                'actions' => [
                    ['action_type' => 'warn', 'target_type' => 'attribute', 'target_code' => null, 'value' => 'More than 4 tanks requires Advanced or Enterprise system type.'],
                ],
            ],
            [
                'name' => 'Outdoor installation requires outdoor enclosure',
                'code' => 'HEC-RULE-003',
                'type' => 'warning',
                'conditions' => [
                    ['attribute' => 'installation', 'operator' => 'equals', 'value' => 'OUTDOOR_INST'],
                ],
                'actions' => [
                    ['action_type' => 'warn', 'target_type' => 'attribute', 'target_code' => null, 'value' => 'Outdoor installation requires at minimum Outdoor (IP65) enclosure.'],
                ],
            ],
        ];

        foreach ($rules as $ruleDef) {
            $rule = ConfigurationRule::firstOrCreate(
                ['code' => $ruleDef['code']],
                [
                    'product_id' => $product->id,
                    'name' => $ruleDef['name'],
                    'type' => $ruleDef['type'],
                    'condition_logic' => 'AND',
                    'priority' => 0,
                    'is_active' => true,
                ]
            );

            if ($rule->conditions()->count() === 0) {
                foreach ($ruleDef['conditions'] as $i => $cond) {
                    $attr = $attrs[$cond['attribute']] ?? null;
                    if (! $attr) {
                        continue;
                    }
                    RuleCondition::create([
                        'configuration_rule_id' => $rule->id,
                        'attribute_id' => $attr->id,
                        'operator' => $cond['operator'],
                        'value' => $cond['value'],
                        'logic' => 'AND',
                        'group' => 0,
                        'sort_order' => $i,
                    ]);
                }
            }

            if ($rule->actions()->count() === 0) {
                foreach ($ruleDef['actions'] as $i => $action) {
                    $targetId = null;
                    if ($action['target_code'] && isset($attrs[$action['target_code']])) {
                        $targetId = $attrs[$action['target_code']]->id;
                    }
                    RuleAction::create([
                        'configuration_rule_id' => $rule->id,
                        'action_type' => $action['action_type'],
                        'target_type' => $action['target_type'],
                        'target_id' => $targetId,
                        'value' => $action['value'] ?? null,
                        'sort_order' => $i,
                    ]);
                }
            }
        }
    }

    private function createBomRules(Product $product, array $attrs, array $components): void
    {
        $bomRules = [
            [
                'name' => 'Base BOM - Always',
                'code' => 'HEC-BOM-BASE',
                'conditions' => [],
                'items' => [
                    ['component' => 'HEC-CTRL-STD', 'qty' => 1],
                    ['component' => 'HEC-PWR-24V', 'qty' => 1],
                    ['component' => 'HEC-ENC-IND', 'qty' => 1],
                ],
            ],
            [
                'name' => 'BOM - RS485 Communication',
                'code' => 'HEC-BOM-RS485',
                'conditions' => [['attribute' => 'communication', 'operator' => 'equals', 'value' => 'RS485']],
                'items' => [
                    ['component' => 'HEC-COMM-RS485', 'qty' => 1],
                ],
            ],
            [
                'name' => 'BOM - Ethernet Communication',
                'code' => 'HEC-BOM-ETH',
                'conditions' => [['attribute' => 'communication', 'operator' => 'equals', 'value' => 'ETHERNET']],
                'items' => [
                    ['component' => 'HEC-COMM-ETH', 'qty' => 1],
                ],
            ],
            [
                'name' => 'BOM - Cellular Communication',
                'code' => 'HEC-BOM-CELL',
                'conditions' => [['attribute' => 'communication', 'operator' => 'equals', 'value' => 'CELLULAR']],
                'items' => [
                    ['component' => 'HEC-COMM-CELL', 'qty' => 1],
                    ['component' => 'HEC-ANT-CELL', 'qty' => 1],
                ],
            ],
            [
                'name' => 'BOM - Wi-Fi Communication',
                'code' => 'HEC-BOM-WIFI',
                'conditions' => [['attribute' => 'communication', 'operator' => 'equals', 'value' => 'WIFI']],
                'items' => [
                    ['component' => 'HEC-COMM-WIFI', 'qty' => 1],
                    ['component' => 'HEC-ANT-WIFI', 'qty' => 1],
                ],
            ],
            [
                'name' => 'BOM - RFID Enabled',
                'code' => 'HEC-BOM-RFID',
                'conditions' => [['attribute' => 'rfid', 'operator' => 'equals', 'value' => 'ENABLED']],
                'items' => [
                    ['component' => 'HEC-RFID-RDR', 'qty' => 1],
                ],
            ],
            [
                'name' => 'BOM - Outdoor Enclosure',
                'code' => 'HEC-BOM-ENC-OUT',
                'conditions' => [['attribute' => 'enclosure', 'operator' => 'equals', 'value' => 'OUTDOOR']],
                'items' => [
                    ['component' => 'HEC-ENC-OUT', 'qty' => 1],
                ],
            ],
        ];

        foreach ($bomRules as $def) {
            $rule = BomRule::firstOrCreate(
                ['code' => $def['code']],
                [
                    'product_id' => $product->id,
                    'name' => $def['name'],
                    'condition_logic' => 'AND',
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            if ($rule->conditions()->count() === 0) {
                foreach ($def['conditions'] as $cond) {
                    $attr = $attrs[$cond['attribute']] ?? null;
                    if (! $attr) {
                        continue;
                    }
                    BomRuleCondition::create([
                        'bom_rule_id' => $rule->id,
                        'attribute_id' => $attr->id,
                        'operator' => $cond['operator'],
                        'value' => $cond['value'],
                        'logic' => 'AND',
                        'group' => 0,
                    ]);
                }
            }

            if ($rule->items()->count() === 0) {
                foreach ($def['items'] as $item) {
                    $comp = $components[$item['component']] ?? null;
                    if (! $comp) {
                        continue;
                    }
                    BomRuleItem::create([
                        'bom_rule_id' => $rule->id,
                        'component_id' => $comp->id,
                        'quantity' => $item['qty'],
                        'is_mandatory' => true,
                    ]);
                }
            }
        }
    }

    private function createCompatibilityRules(Product $product, array $attrs): void
    {
        $commAttr = $attrs['communication'];
        $encAttr = $attrs['enclosure'];

        $rs485Option = $commAttr->options->firstWhere('code', 'RS485');
        $indoorOption = $encAttr->options->firstWhere('code', 'INDOOR');

        if ($rs485Option && $indoorOption) {
            CompatibilityRule::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'attribute_id' => $commAttr->id,
                    'option_id' => $rs485Option->id,
                    'target_attribute_id' => $encAttr->id,
                    'target_option_id' => $indoorOption->id,
                ],
                [
                    'type' => 'compatible',
                    'reason' => 'RS485 works with indoor enclosures.',
                    'is_active' => true,
                ]
            );
        }
    }
}
