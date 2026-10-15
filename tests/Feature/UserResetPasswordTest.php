<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('resetting a user password sends a link without changing the password or leaking credentials', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('super_admin');

    $target = User::factory()->create([
        'password' => Hash::make('original-password'),
        'is_active' => true,
    ]);
    $originalHash = $target->password;

    $response = $this->actingAs($admin)
        ->post(route('admin.users.reset-password', $target));

    $response->assertRedirect();

    expect($target->fresh()->password)->toBe($originalHash);

    $successMessage = session('success');
    expect($successMessage)->not->toBeNull();
    expect($successMessage)->not->toContain('password:');
    expect($successMessage)->not->toContain('Temporary');
});
