<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    RateLimiter::clear('');
});

test('login is throttled after too many attempts', function () {
    $user = User::factory()->create([
        'password' => Hash::make('secret-password'),
        'is_active' => true,
    ]);

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(302);
    }

    $this->post(route('login.post'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});
