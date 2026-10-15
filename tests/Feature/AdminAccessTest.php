<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole($role);

    return $user;
}

test('a customer cannot access the admin dashboard', function () {
    $this->actingAs(userWithRole('customer'))
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a customer cannot access the admin product index', function () {
    $this->actingAs(userWithRole('customer'))
        ->get(route('admin.products.index'))
        ->assertForbidden();
});

test('a customer cannot delete a product through the admin panel', function () {
    $product = Product::factory()->create();

    $this->actingAs(userWithRole('customer'))
        ->delete(route('admin.products.destroy', $product))
        ->assertForbidden();
});

test('an engineering user is excluded from the admin panel', function () {
    $this->actingAs(userWithRole('engineering'))
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a sales user is excluded from the admin panel', function () {
    $this->actingAs(userWithRole('sales'))
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('a product manager can access the admin dashboard', function () {
    $this->actingAs(userWithRole('product_manager'))
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('a super admin can access the admin dashboard', function () {
    $this->actingAs(userWithRole('super_admin'))
        ->get(route('admin.dashboard'))
        ->assertOk();
});
