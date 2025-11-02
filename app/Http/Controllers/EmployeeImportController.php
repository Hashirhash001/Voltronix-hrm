<?php
// app/Http/Controllers/EmployeeImportController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class EmployeeImportController extends Controller
{
    /**
     * Show import page
     */
    public function showImport()
    {
        return view('employees.import');
    }

    /**
     * Process file upload and preview
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('file');
            $data = Excel::toArray(null, $file);

            if (empty($data[0])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is empty'
                ], 422);
            }

            $rows = $data[0];
            $header = array_shift($rows); // Remove header row

            // Process and validate data
            $employees = [];
            $errors = [];
            $rowNum = 2;

            foreach ($rows as $row) {
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                $employee = $this->parseRow($row, $header, $rowNum);

                if ($employee['errors']) {
                    $errors[] = [
                        'row' => $rowNum,
                        'data' => $employee['data'],
                        'errors' => $employee['errors']
                    ];
                } else {
                    $employees[] = $employee['data'];
                }

                $rowNum++;
            }

            return response()->json([
                'success' => true,
                'total' => count($employees) + count($errors),
                'valid' => count($employees),
                'invalid' => count($errors),
                'employees' => $employees,
                'errors' => $errors,
                'preview' => array_slice($employees, 0, 5)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reading file: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Parse and validate a row
     */
    private function parseRow($row, $header, $rowNum)
    {
        $data = array_combine($header, $row);
        $errors = [];

        // Validate required fields
        $required = ['staff_number', 'employee_name', 'email', 'designation', 'basic_salary'];
        foreach ($required as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                $errors[] = "$field is required";
            }
        }

        // Check for duplicates
        if (!empty(trim($data['staff_number'] ?? ''))) {
            if (Employee::where('staff_number', trim($data['staff_number']))->exists()) {
                $errors[] = "Staff number already exists";
            }
            if (User::where('staff_number', trim($data['staff_number']))->exists()) {
                $errors[] = "Staff number already in use";
            }
        }

        // Validate email
        if (!empty(trim($data['email'] ?? ''))) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
            if (User::where('email', $data['email'])->exists()) {
                $errors[] = "Email already exists";
            }
        }

        // Validate salary
        if (!empty($data['basic_salary'] ?? '')) {
            if (!is_numeric($data['basic_salary'])) {
                $errors[] = "Basic salary must be numeric";
            }
        }

        // Validate dates
        $dateFields = ['date_of_birth', 'duty_joined_date', 'duty_end_date', 'passport_expiry_date', 'visa_expiry_date', 'health_insurance_expiry_date'];
        foreach ($dateFields as $field) {
            if (!empty(trim($data[$field] ?? ''))) {
                try {
                    Carbon::createFromFormat('Y-m-d', $data[$field]);
                } catch (\Exception $e) {
                    $errors[] = "$field must be in YYYY-MM-DD format";
                }
            }
        }

        return [
            'data' => $data,
            'errors' => $errors
        ];
    }

    /**
     * Import employees to database
     */
    public function import(Request $request)
    {
        $request->validate([
            'employees' => 'required|json'
        ]);

        $employees = json_decode($request->employees, true);

        if (empty($employees)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid employees to import'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($employees as $data) {
                try {
                    // Create user
                    $user = User::create([
                        'staff_number' => $data['staff_number'],
                        'employee_name' => $data['employee_name'],
                        'email' => $data['email'],
                        'password' => Hash::make('password123'),
                        'role' => 'employee',
                    ]);

                    $totalSalary = ($data['basic_salary'] ?? 0) +
                                  ($data['allowance'] ?? 0) +
                                  ($data['fixed_salary'] ?? 0);

                    Employee::create([
                        'user_id' => $user->id,
                        'staff_number' => $data['staff_number'],
                        'employee_name' => $data['employee_name'],
                        'designation' => $data['designation'],
                        'qualification' => $data['qualification'] ?? null,
                        'pp_status' => $data['pp_status'] ?? null,
                        'uae_contact' => $data['uae_contact'] ?? null,
                        'home_country_contact' => $data['home_country_contact'] ?? null,
                        'date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                        'duty_joined_date' => !empty($data['duty_joined_date']) ? $data['duty_joined_date'] : null,
                        'duty_end_date' => !empty($data['duty_end_date']) ? $data['duty_end_date'] : null,
                        'last_vacation_date' => !empty($data['last_vacation_date']) ? $data['last_vacation_date'] : null,
                        'basic_salary' => $data['basic_salary'] ?? 0,
                        'allowance' => $data['allowance'] ?? 0,
                        'fixed_salary' => $data['fixed_salary'] ?? 0,
                        'total_salary' => $totalSalary,
                        'recent_increment_amount' => $data['recent_increment_amount'] ?? null,
                        'increment_date' => !empty($data['increment_date']) ? $data['increment_date'] : null,
                        'passport_expiry_date' => !empty($data['passport_expiry_date']) ? $data['passport_expiry_date'] : null,
                        'visa_expiry_date' => !empty($data['visa_expiry_date']) ? $data['visa_expiry_date'] : null,
                        'visit_expiry_date' => !empty($data['visit_expiry_date']) ? $data['visit_expiry_date'] : null,
                        'eid_expiry_date' => !empty($data['eid_expiry_date']) ? $data['eid_expiry_date'] : null,
                        'health_insurance_expiry_date' => !empty($data['health_insurance_expiry_date']) ? $data['health_insurance_expiry_date'] : null,
                        'driving_license_expiry_date' => !empty($data['driving_license_expiry_date']) ? $data['driving_license_expiry_date'] : null,
                        'salary_card_details' => $data['salary_card_details'] ?? null,
                        'remarks' => $data['remarks'] ?? null,
                        'status' => $data['status'] ?? 'active',
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Error importing {$data['employee_name']}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "$imported employees imported successfully",
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export employees to Excel
     */
    public function export()
    {
        $employees = Employee::with('user')->get();

        $filename = 'employees_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new EmployeeExport($employees), $filename);
    }

    /**
     * Download template
     */
    public function downloadTemplate()
    {
        $filename = 'employee_import_template.xlsx';

        return Excel::download(new EmployeeTemplateExport(), $filename);
    }
}
