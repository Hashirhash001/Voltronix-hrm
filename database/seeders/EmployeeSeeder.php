<?php
// database/seeders/EmployeeSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $designations = ['Electrician', 'Plumber', 'HVAC Technician', 'Civil Engineer', 'Project Manager', 'Safety Officer', 'Supervisor', 'Foreman'];
        $qualifications = ['Diploma', 'Bachelor\'s Degree', 'Master\'s Degree', 'Certificate', 'Higher Diploma'];

        $employees = [
            [
                'staff_number' => 'EMP001',
                'employee_name' => 'Ahmed Hassan',
                'email' => 'ahmed.hassan@voltronix.com',
                'designation' => 'Electrician',
                'basic_salary' => 2500,
                'allowance' => 500,
                'fixed_salary' => 200,
            ],
            [
                'staff_number' => 'EMP002',
                'employee_name' => 'Mohammed Ali',
                'email' => 'mohammed.ali@voltronix.com',
                'designation' => 'Plumber',
                'basic_salary' => 2300,
                'allowance' => 400,
                'fixed_salary' => 200,
            ],
            [
                'staff_number' => 'EMP003',
                'employee_name' => 'Fatima Al-Mansouri',
                'email' => 'fatima.mansouri@voltronix.com',
                'designation' => 'Project Manager',
                'basic_salary' => 5000,
                'allowance' => 1000,
                'fixed_salary' => 500,
            ],
            [
                'staff_number' => 'EMP004',
                'employee_name' => 'Ali Salameh',
                'email' => 'ali.salameh@voltronix.com',
                'designation' => 'HVAC Technician',
                'basic_salary' => 2400,
                'allowance' => 450,
                'fixed_salary' => 200,
            ],
            [
                'staff_number' => 'EMP005',
                'employee_name' => 'Layla Qureshi',
                'email' => 'layla.qureshi@voltronix.com',
                'designation' => 'Civil Engineer',
                'basic_salary' => 4500,
                'allowance' => 900,
                'fixed_salary' => 400,
            ],
            [
                'staff_number' => 'EMP006',
                'employee_name' => 'Omar Khalil',
                'email' => 'omar.khalil@voltronix.com',
                'designation' => 'Safety Officer',
                'basic_salary' => 3500,
                'allowance' => 600,
                'fixed_salary' => 300,
            ],
            [
                'staff_number' => 'EMP007',
                'employee_name' => 'Noor Al-Shehhi',
                'email' => 'noor.shehhi@voltronix.com',
                'designation' => 'Supervisor',
                'basic_salary' => 4000,
                'allowance' => 800,
                'fixed_salary' => 350,
            ],
            [
                'staff_number' => 'EMP008',
                'employee_name' => 'Samir Abu',
                'email' => 'samir.abu@voltronix.com',
                'designation' => 'Foreman',
                'basic_salary' => 3800,
                'allowance' => 700,
                'fixed_salary' => 300,
            ],
            [
                'staff_number' => 'EMP009',
                'employee_name' => 'Rasha Al-Mazrouei',
                'email' => 'rasha.mazrouei@voltronix.com',
                'designation' => 'Electrician',
                'basic_salary' => 2600,
                'allowance' => 500,
                'fixed_salary' => 200,
            ],
            [
                'staff_number' => 'EMP010',
                'employee_name' => 'Hassan Ibrahim',
                'email' => 'hassan.ibrahim@voltronix.com',
                'designation' => 'Plumber',
                'basic_salary' => 2400,
                'allowance' => 400,
                'fixed_salary' => 200,
            ],
        ];

        foreach ($employees as $data) {
            // Create user
            $user = User::create([
                'staff_number' => $data['staff_number'],
                'employee_name' => $data['employee_name'],
                'email' => $data['email'],
                'password' => Hash::make('password123'),
                'role' => 'employee',
            ]);

            // Calculate total salary
            $totalSalary = $data['basic_salary'] + $data['allowance'] + $data['fixed_salary'];

            // Create employee
            Employee::create([
                'user_id' => $user->id,
                'staff_number' => $data['staff_number'],
                'employee_name' => $data['employee_name'],
                'designation' => $data['designation'],
                'qualification' => $qualifications[array_rand($qualifications)],
                'basic_salary' => $data['basic_salary'],
                'allowance' => $data['allowance'],
                'fixed_salary' => $data['fixed_salary'],
                'total_salary' => $totalSalary,
                'uae_contact' => '+971' . rand(50, 56) . rand(100, 999) . rand(1000, 9999),
                'home_country_contact' => '+' . rand(91, 99) . rand(10000, 99999) . rand(100000, 999999),
                'date_of_birth' => Carbon::now()->subYears(rand(25, 55))->toDateString(),
                'duty_joined_date' => Carbon::now()->subYears(rand(1, 10))->toDateString(),
                'last_vacation_date' => Carbon::now()->subMonths(rand(1, 6))->toDateString(),
                'passport_expiry_date' => Carbon::now()->addMonths(rand(1, 36))->toDateString(),
                'visa_expiry_date' => Carbon::now()->addMonths(rand(1, 24))->toDateString(),
                'health_insurance_expiry_date' => Carbon::now()->addMonths(rand(3, 12))->toDateString(),
                'driving_license_expiry_date' => Carbon::now()->addMonths(rand(6, 48))->toDateString(),
                'eid_expiry_date' => Carbon::now()->addMonths(rand(6, 24))->toDateString(),
                'status' => 'active',
            ]);
        }

        $this->command->info('10 employees created successfully!');
    }
}
