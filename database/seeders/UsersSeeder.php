<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin', 'email' => 'admin@demo.com', 'role' => 'super_admin'],
            ['name' => 'Product Manager', 'email' => 'pm@demo.com', 'role' => 'product_manager'],
            ['name' => 'Engineer', 'email' => 'engineer@demo.com', 'role' => 'engineering'],
            ['name' => 'Sales User', 'email' => 'sales@demo.com', 'role' => 'sales'],
            ['name' => 'Demo Customer', 'email' => 'customer@demo.com', 'role' => 'customer'],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );
            $user->assignRole($data['role']);
        }
    }
}
