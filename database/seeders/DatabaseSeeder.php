<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Project;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@bangalioagro.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create sample projects
        $fieldProject = Project::create([
            'name' => 'Field Project 1',
            'type' => 'field',
            'description' => 'Sample field project',
            'location' => 'Dhaka',
            'is_active' => true,
        ]);

        $nurseryProject = Project::create([
            'name' => 'Nursery Project 1',
            'type' => 'nursery',
            'description' => 'Sample nursery project',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $shopProject = Project::create([
            'name' => 'Shop 1',
            'type' => 'shop',
            'description' => 'Main shop',
            'location' => 'Farmgate',
            'is_active' => true,
        ]);

        // Create a sample user with limited access
        $user = User::create([
            'name' => 'Staff User',
            'email' => 'staff@bangalioagro.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'is_active' => true,
        ]);

        // Assign only field project to staff user
        $user->projects()->attach($fieldProject->id, ['permission' => 'read_write']);

        // Create Walk-in Customer
        Customer::create([
            'name' => 'Walk-in Customer',
            'address' => null,
            'phone' => null,
            'email' => null,
            'total_sale' => 0,
            'total_paid' => 0,
            'total_due' => 0,
            'is_active' => true,
        ]);

        // Create default warehouses
        Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-001',
            'address' => 'Dhaka',
            'phone' => null,
            'manager_name' => null,
            'project_id' => null,
            'is_active' => true,
        ]);
    }
}
