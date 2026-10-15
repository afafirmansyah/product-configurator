<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('a deactivated user cannot log in', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
        'is_active' => false,
    ]);

    $response = $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an active user can log in', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
        'is_active' => true,
    ]);

    $response = $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'secret-password',
    ]);

    $response->assertRedirect(route('catalog.index'));
    $this->assertAuthenticatedAs($user);
});
