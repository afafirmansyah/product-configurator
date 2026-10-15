<?php

use App\Livewire\Admin\ProductBuilder;
use App\Livewire\Admin\ProductList;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('a customer cannot mount the product builder', function () {
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');

    $this->actingAs($customer);

    Livewire::test(ProductBuilder::class)->assertStatus(403);
});

test('a super admin can mount the product builder', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Livewire::test(ProductBuilder::class)->assertStatus(200);
});

test('a customer cannot mount the product list', function () {
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');

    $this->actingAs($customer);

    Livewire::test(ProductList::class)->assertStatus(403);
});

test('a super admin can mount the product list', function () {
    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    Livewire::test(ProductList::class)->assertStatus(200);
});
