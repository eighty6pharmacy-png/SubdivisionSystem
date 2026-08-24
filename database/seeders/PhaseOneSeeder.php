<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Lot;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PhaseOneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        // 1. Create Roles
        $roles = ['Admin', 'Resident', 'Security Guard', 'Finance Officer'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // 2. Create Lots
        $mockLots = [
            ['block' => '1', 'lot_number' => '5', 'provider_managed' => true],
            ['block' => '2', 'lot_number' => '12', 'provider_managed' => true],
            ['block' => '3', 'lot_number' => '8', 'provider_managed' => true],
            ['block' => 'A1', 'lot_number' => '4', 'provider_managed' => false],
            ['block' => 'A2', 'lot_number' => '1', 'provider_managed' => false],
            ['block' => 'C1', 'lot_number' => '10', 'provider_managed' => true],
            ['block' => '1', 'lot_number' => '22', 'provider_managed' => true],
        ];

        $lotModels = [];
        foreach ($mockLots as $lotData) {
            $lotModels[$lotData['block'] . '-' . $lotData['lot_number']] = Lot::firstOrCreate(
                ['block' => $lotData['block'], 'lot_number' => $lotData['lot_number']],
                [
                    'status' => 'Available',
                    'provider_managed' => $lotData['provider_managed'],
                ]
            );
        }

        // 3. Create Users
        $mockUsers = [
            ['name' => 'System Admin', 'role' => 'Admin', 'email' => 'admin@althesa.com', 'status' => 'Active', 'joined' => '2023-01-01', 'block' => null, 'lot_number' => null],
            ['name' => 'Jepuso', 'role' => 'Resident', 'email' => 'jepuso@my.cspc.edu.ph', 'status' => 'Active', 'joined' => '2024-01-15', 'block' => '1', 'lot_number' => '5'],
            ['name' => 'Sgt. Robert Miller', 'role' => 'Security Guard', 'email' => 'robert.guard@althesa.com', 'status' => 'Active', 'joined' => '2023-10-10', 'block' => null, 'lot_number' => null],
            ['name' => 'CPA Michael Tan', 'role' => 'Finance Officer', 'email' => 'michael.finance@althesa.com', 'status' => 'Active', 'joined' => '2023-11-20', 'block' => null, 'lot_number' => null],
        ];

        foreach ($mockUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password123'),
                    'status' => $userData['status'],
                    'joined_at' => $userData['joined'],
                ]
            );

            $user->assignRole($userData['role']);

            if ($userData['block'] && $userData['lot_number']) {
                $lotKey = $userData['block'] . '-' . $userData['lot_number'];
                if (isset($lotModels[$lotKey])) {
                    $user->lots()->attach($lotModels[$lotKey]);
                }
            }
        }
    }
}
