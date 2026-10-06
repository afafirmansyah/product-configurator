<?php

namespace Database\Seeders;

use App\Models\ComponentCategory;
use App\Models\ProductCategory;
use App\Models\ProductFamily;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            [
                'name' => 'Fuel Management',
                'code' => 'FUEL_MGMT',
                'families' => [
                    ['name' => 'Stationary FMS', 'code' => 'STAT_FMS'],
                    ['name' => 'Mobile FMS', 'code' => 'MOB_FMS'],
                    ['name' => 'Automatic Tank Gauging', 'code' => 'ATG'],
                ],
            ],
            [
                'name' => 'Flow Measurement',
                'code' => 'FLOW_MEAS',
                'families' => [
                    ['name' => 'Flowmetering Systems', 'code' => 'FLOW_SYS'],
                    ['name' => 'Fuel Terminals', 'code' => 'FUEL_TERM'],
                ],
            ],
            [
                'name' => 'Control & Communication',
                'code' => 'CTRL_COMM',
                'families' => [
                    ['name' => 'Controllers', 'code' => 'CTRL'],
                    ['name' => 'Communication Modules', 'code' => 'COMM_MOD'],
                    ['name' => 'RFID Systems', 'code' => 'RFID_SYS'],
                ],
            ],
        ];

        foreach ($catalog as $i => $catData) {
            $category = ProductCategory::firstOrCreate(
                ['code' => $catData['code']],
                [
                    'name' => $catData['name'],
                    'slug' => Str::slug($catData['name']),
                    'sort_order' => $i,
                    'is_active' => true,
                ]
            );

            foreach ($catData['families'] as $j => $famData) {
                ProductFamily::firstOrCreate(
                    ['code' => $famData['code']],
                    [
                        'product_category_id' => $category->id,
                        'name' => $famData['name'],
                        'slug' => Str::slug($famData['name']),
                        'sort_order' => $j,
                        'is_active' => true,
                    ]
                );
            }
        }

        $componentCategories = [
            'CONTROLLER' => 'Controller',
            'COMM_MODULE' => 'Communication Module',
            'SENSOR' => 'Sensor',
            'FLOWMETER' => 'Flowmeter',
            'RFID' => 'RFID Reader',
            'POWER' => 'Power Supply',
            'DISPLAY' => 'Display',
            'ENCLOSURE' => 'Enclosure',
            'CABLE' => 'Cable & Antenna',
            'SOFTWARE' => 'Software',
            'SERVICE' => 'Service',
            'ACCESSORY' => 'Accessory',
        ];

        foreach ($componentCategories as $code => $name) {
            ComponentCategory::firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
    }
}
