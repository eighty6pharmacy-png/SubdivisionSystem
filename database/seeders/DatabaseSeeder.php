<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $user = User::updateOrCreate(
            ['email' => 'admin@althesa.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'contact_number' => '09123456789',
                'status' => 'Active',
                'role' => 'Admin',
                'joined_at' => now(),
            ]
        );
        $user->assignRole('Admin');

        // Create Guard User
        $guard = User::updateOrCreate(
            ['email' => 'guard@althesa.com'],
            [
                'name' => 'Security Officer',
                'password' => Hash::make('password123'),
                'status' => 'Active',
                'role' => 'Security Guard',
                'joined_at' => now(),
            ]
        );
        $guard->assignRole('Security Guard');

        // Create Finance User
        $finance = User::updateOrCreate(
            ['email' => 'finance@althesa.com'],
            [
                'name' => 'Finance Department',
                'password' => Hash::make('password123'),
                'status' => 'Active',
                'role' => 'Finance Officer',
                'joined_at' => now(),
            ]
        );
        $finance->assignRole('Finance Officer');
    }
}
