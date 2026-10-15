<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

$adminIndexRoutes = [
    'admin.configurations.index',
    'admin.quotations.index',
    'admin.audit-logs.index',
];

test('a super admin can load the admin index pages', function (string $routeName) {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route($routeName))
        ->assertOk();
})->with($adminIndexRoutes);

test('a customer is forbidden from the admin index pages', function (string $routeName) {
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route($routeName))
        ->assertForbidden();
})->with($adminIndexRoutes);
