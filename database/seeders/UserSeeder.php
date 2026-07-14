<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(['email' => 'superadmin@example.com'], [
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->syncRoles(['superadmin']);

        $owner = User::updateOrCreate(['email' => 'owner@example.com'], [
            'name' => 'Owner',
            'username' => 'owner',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $owner->syncRoles(['owner']);

        $admin = User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->syncRoles(['admin']);
    }
}
