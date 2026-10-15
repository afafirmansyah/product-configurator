<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view products', 'create products', 'edit products', 'delete products', 'publish products',
            'view attributes', 'create attributes', 'edit attributes', 'delete attributes',
            'view components', 'create components', 'edit components', 'delete components',
            'view rules', 'create rules', 'edit rules', 'delete rules',
            'view configurations', 'create configurations', 'edit configurations',
            'view quotations', 'create quotations', 'manage quotations',
            'view audit logs',
            'manage users', 'manage roles',
            'access admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $productManager = Role::firstOrCreate(['name' => 'product_manager']);
        $productManager->givePermissionTo([
            'view products', 'create products', 'edit products', 'publish products',
            'view attributes', 'create attributes', 'edit attributes',
            'view components', 'create components', 'edit components',
            'view rules', 'create rules', 'edit rules',
            'view configurations', 'view quotations',
            'access admin',
        ]);

        $engineering = Role::firstOrCreate(['name' => 'engineering']);
        $engineering->givePermissionTo([
            'view products', 'edit products',
            'view attributes', 'create attributes', 'edit attributes',
            'view components', 'create components', 'edit components',
            'view rules', 'create rules', 'edit rules',
            'view configurations',
            'access admin',
        ]);

        $sales = Role::firstOrCreate(['name' => 'sales']);
        $sales->givePermissionTo([
            'view products',
            'view configurations', 'create configurations',
            'view quotations', 'create quotations',
        ]);

        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->givePermissionTo([
            'view products',
            'create configurations',
            'create quotations',
        ]);

        Role::firstOrCreate(['name' => 'viewer'])->givePermissionTo(['view products']);
    }
}
