<?php
// database/seeders/AdminSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user (ONLY user fields)
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@hrm.com',
            'password' => Hash::make('password'),
        ]);

        // Create admin employee record (with user_id reference)
        Employee::create([
            'user_id' => $adminUser->id,
            'staff_number' => 'ADMIN001',
            'employee_name' => 'Admin User',
            'designation' => 'System Administrator',
            'qualification' => 'IT Professional',
            'basic_salary' => 10000.00,
            'allowance' => 2000.00,
            'fixed_salary' => 1000.00,
            'total_salary' => 13000.00,
            'status' => 'active',
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@hrm.com');
        $this->command->info('Password: password');
    }
}
