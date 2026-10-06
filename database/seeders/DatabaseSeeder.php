<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure Admin role exists
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Create Admin User
        $user = User::updateOrCreate(
            ['email' => 'admin@althesa.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'status' => 'Active',
                'joined_at' => now(),
            ]
        );

        $user->assignRole($adminRole);

        // Ensure Security Guard role exists
        $guardRole = Role::firstOrCreate(['name' => 'Security Guard']);
        
        // Create Guard User
        $guard = User::updateOrCreate(
            ['email' => 'guard@althesa.com'],
            [
                'name' => 'Security Officer',
                'password' => Hash::make('password123'),
                'status' => 'Active',
                'joined_at' => now(),
            ]
        );
        $guard->assignRole($guardRole);

        // Ensure Finance Officer role exists
        $financeRole = Role::firstOrCreate(['name' => 'Finance Officer']);
        
        // Create Finance User
        $finance = User::updateOrCreate(
            ['email' => 'finance@althesa.com'],
            [
                'name' => 'Finance Department',
                'password' => Hash::make('password123'),
                'status' => 'Active',
                'joined_at' => now(),
            ]
        );
        $finance->assignRole($financeRole);
    }
}
