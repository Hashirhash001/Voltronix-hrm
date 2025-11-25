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
        $designations = ['Electrician', 'Plumber', 'HVAC Technician', 'Civil Engineer', 'Project Manager', 'Safety Officer', 'Supervisor', 'Foreman', 'Architect', 'Site Manager'];
        $qualifications = ['Diploma', 'Bachelor\'s Degree', 'Master\'s Degree', 'Certificate', 'Higher Diploma'];

        $employees = [
            ['staff_number' => 'EMP001', 'name' => 'Ahmed Hassan', 'email' => 'ahmed.hassan@voltronix.com', 'designation' => 'Electrician', 'basic' => 2500, 'allowance' => 500, 'fixed' => 200],
            ['staff_number' => 'EMP002', 'name' => 'Mohammed Ali', 'email' => 'mohammed.ali@voltronix.com', 'designation' => 'Plumber', 'basic' => 2300, 'allowance' => 400, 'fixed' => 200],
            ['staff_number' => 'EMP003', 'name' => 'Fatima Al-Mansouri', 'email' => 'fatima.mansouri@voltronix.com', 'designation' => 'Project Manager', 'basic' => 5000, 'allowance' => 1000, 'fixed' => 500],
            ['staff_number' => 'EMP004', 'name' => 'Ali Salameh', 'email' => 'ali.salameh@voltronix.com', 'designation' => 'HVAC Technician', 'basic' => 2400, 'allowance' => 450, 'fixed' => 200],
            ['staff_number' => 'EMP005', 'name' => 'Layla Qureshi', 'email' => 'layla.qureshi@voltronix.com', 'designation' => 'Civil Engineer', 'basic' => 4500, 'allowance' => 900, 'fixed' => 400],
            ['staff_number' => 'EMP006', 'name' => 'Omar Khalil', 'email' => 'omar.khalil@voltronix.com', 'designation' => 'Safety Officer', 'basic' => 3500, 'allowance' => 600, 'fixed' => 300],
            ['staff_number' => 'EMP007', 'name' => 'Noor Al-Shehhi', 'email' => 'noor.shehhi@voltronix.com', 'designation' => 'Supervisor', 'basic' => 4000, 'allowance' => 800, 'fixed' => 350],
            ['staff_number' => 'EMP008', 'name' => 'Samir Abu', 'email' => 'samir.abu@voltronix.com', 'designation' => 'Foreman', 'basic' => 3800, 'allowance' => 700, 'fixed' => 300],
            ['staff_number' => 'EMP009', 'name' => 'Rasha Al-Mazrouei', 'email' => 'rasha.mazrouei@voltronix.com', 'designation' => 'Electrician', 'basic' => 2600, 'allowance' => 500, 'fixed' => 200],
            ['staff_number' => 'EMP010', 'name' => 'Hassan Ibrahim', 'email' => 'hassan.ibrahim@voltronix.com', 'designation' => 'Plumber', 'basic' => 2400, 'allowance' => 400, 'fixed' => 200],
            ['staff_number' => 'EMP011', 'name' => 'Mariam Al-Nuaimi', 'email' => 'mariam.nuaimi@voltronix.com', 'designation' => 'Architect', 'basic' => 5500, 'allowance' => 1100, 'fixed' => 600],
            ['staff_number' => 'EMP012', 'name' => 'Khalid Al-Falahi', 'email' => 'khalid.falahi@voltronix.com', 'designation' => 'Site Manager', 'basic' => 4200, 'allowance' => 850, 'fixed' => 400],
            ['staff_number' => 'EMP013', 'name' => 'Zainab Al-Mansouri', 'email' => 'zainab.mansouri@voltronix.com', 'designation' => 'Safety Officer', 'basic' => 3600, 'allowance' => 650, 'fixed' => 300],
            ['staff_number' => 'EMP014', 'name' => 'Rashid Al-Mazrouei', 'email' => 'rashid.mazrouei@voltronix.com', 'designation' => 'Supervisor', 'basic' => 4100, 'allowance' => 820, 'fixed' => 350],
            ['staff_number' => 'EMP015', 'name' => 'Aisha Al-Kaabi', 'email' => 'aisha.kaabi@voltronix.com', 'designation' => 'Electrician', 'basic' => 2700, 'allowance' => 550, 'fixed' => 200],
            ['staff_number' => 'EMP016', 'name' => 'Ibrahim Al-Mulla', 'email' => 'ibrahim.mulla@voltronix.com', 'designation' => 'HVAC Technician', 'basic' => 2500, 'allowance' => 475, 'fixed' => 200],
            ['staff_number' => 'EMP017', 'name' => 'Noura Al-Shirawi', 'email' => 'noura.shirawi@voltronix.com', 'designation' => 'Project Manager', 'basic' => 5200, 'allowance' => 1050, 'fixed' => 550],
            ['staff_number' => 'EMP018', 'name' => 'Faisal Al-Ketbi', 'email' => 'faisal.ketbi@voltronix.com', 'designation' => 'Foreman', 'basic' => 3900, 'allowance' => 750, 'fixed' => 300],
            ['staff_number' => 'EMP019', 'name' => 'Hana Al-Marri', 'email' => 'hana.marri@voltronix.com', 'designation' => 'Civil Engineer', 'basic' => 4600, 'allowance' => 920, 'fixed' => 400],
            ['staff_number' => 'EMP020', 'name' => 'Sami Al-Naqbi', 'email' => 'sami.naqbi@voltronix.com', 'designation' => 'Plumber', 'basic' => 2450, 'allowance' => 420, 'fixed' => 200],
        ];

        foreach ($employees as $index => $data) {
            // Create user (ONLY user fields)
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password123'),
            ]);

            // Calculate total salary
            $totalSalary = $data['basic'] + $data['allowance'] + $data['fixed'];

            // Create expiry dates with some already expired and some about to expire (within 90 days)
            $today = Carbon::now();

            // Mix of expired (past), expiring soon (1-30 days), and warning (30-90 days)
            $expiryScenarios = [
                'expired' => $today->copy()->subMonths(rand(1, 3))->toDateString(),
                'critical' => $today->copy()->addDays(rand(1, 20))->toDateString(),
                'warning' => $today->copy()->addDays(rand(21, 60))->toDateString(),
                'safe' => $today->copy()->addMonths(rand(4, 12))->toDateString(),
            ];

            // Rotate through scenarios for variety
            $scenario = array_keys($expiryScenarios)[$index % 4];

            // Create employee with ALL fields
            Employee::create([
                'user_id' => $user->id,
                'staff_number' => $data['staff_number'],
                'employee_name' => $data['name'],
                'designation' => $data['designation'],
                'qualification' => $qualifications[array_rand($qualifications)],
                'pp_status' => rand(0, 1) ? 'Valid' : 'Pending',

                // Contact Information
                'uae_contact' => '+971' . rand(50, 56) . rand(100, 999) . rand(1000, 9999),
                'home_country_contact' => '+' . rand(91, 99) . rand(10000, 99999) . rand(100000, 999999),

                // Personal Information
                'date_of_birth' => Carbon::now()->subYears(rand(25, 55))->toDateString(),
                'current_age' => rand(25, 55),

                // Employment Details
                'duty_joined_date' => Carbon::now()->subYears(rand(1, 10))->toDateString(),
                'duty_end_date' => null,
                'duty_days' => rand(1000, 3650),
                'duty_years' => number_format(rand(1, 30) + (rand(0, 99) / 100), 2), // Max 30.99 years
                'last_vacation_date' => Carbon::now()->subMonths(rand(1, 6))->toDateString(),

                // Salary Information
                'basic_salary' => $data['basic'],
                'allowance' => $data['allowance'],
                'fixed_salary' => $data['fixed'],
                'total_salary' => $totalSalary,
                'recent_increment_amount' => rand(100, 500),
                'increment_date' => Carbon::now()->subMonths(rand(1, 12))->toDateString(),

                // Personal Documents - Using scenario-based dates
                'passport_expiry_date' => $expiryScenarios[$scenario],
                'visa_expiry_date' => $scenario === 'expired' ? $expiryScenarios['expired'] : $expiryScenarios[array_keys($expiryScenarios)[rand(0, 2)]],
                'visit_expiry_date' => $scenario === 'critical' ? $expiryScenarios['critical'] : $expiryScenarios[array_keys($expiryScenarios)[rand(0, 2)]],
                'eid_expiry_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],
                'health_insurance_expiry_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],
                'driving_license_expiry_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],

                // Salary Card
                'salary_card_details' => 'Card ending in ' . rand(1000, 9999),

                // Company & Insurance Documents
                'iloe_insurance_expiry_date' => $scenario === 'warning' ? $expiryScenarios['warning'] : $expiryScenarios[array_keys($expiryScenarios)[rand(0, 2)]],
                'vtnx_trade_license_renewal_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],
                'po_box_renewal_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],
                'soe_card_renewal_date' => $scenario === 'expired' ? $expiryScenarios['expired'] : $expiryScenarios[array_keys($expiryScenarios)[rand(0, 2)]],
                'dcd_card_renewal_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],
                'voltronix_est_card_renewal_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],
                'warehouse_ejari_renewal_date' => $scenario === 'critical' ? $expiryScenarios['critical'] : $expiryScenarios[array_keys($expiryScenarios)[rand(0, 2)]],
                'camp_ejari_renewal_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],
                'workman_insurance_expiry_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],
                'etisalat_contract_expiry_date' => $expiryScenarios[array_keys($expiryScenarios)[rand(0, 3)]],

                // DEWA Details
                'dewa_details' => 'Account #' . rand(100000, 999999),

                // Status & Remarks
                'status' => 'active',
                'remarks' => 'Employee seeded with test data. Some documents expiring soon for testing alerts.',
            ]);
        }

        $this->command->info('20 employees created successfully with various expiry dates!');
        $this->command->info('Scenarios: Expired, Critical (1-20 days), Warning (21-60 days), Safe (4-12 months)');
    }
}
