<?php
// app/Http/Controllers/EmployeeController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['user', 'documentExpiryAlerts']);

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('designation') && $request->designation !== '') {
            $query->where('designation', $request->designation);
        }

        if ($request->has('min_salary') && $request->min_salary !== '') {
            $query->where('total_salary', '>=', $request->min_salary);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                ->orWhere('staff_number', 'like', "%{$search}%")
                ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(50);

        if ($request->has('ajax')) {
            return response()->json([
                'employees' => $employees->items(),
            ]);
        }

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_number' => 'required|unique:employees,staff_number',
            'employee_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'designation' => 'required|string',
            'qualification' => 'nullable|string',
            'pp_status' => 'nullable|string',
            'uae_contact' => 'nullable|string',
            'home_country_contact' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'duty_joined_date' => 'nullable|date',
            'duty_end_date' => 'nullable|date',
            'last_vacation_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'fixed_salary' => 'nullable|numeric|min:0',
            'recent_increment_amount' => 'nullable|numeric|min:0',
            'increment_date' => 'nullable|date',
            'passport_expiry_date' => 'nullable|date',
            'visit_expiry_date' => 'nullable|date',
            'visa_expiry_date' => 'nullable|date',
            'eid_expiry_date' => 'nullable|date',
            'health_insurance_expiry_date' => 'nullable|date',
            'driving_license_expiry_date' => 'nullable|date',
            'salary_card_details' => 'nullable|string',
            'remarks' => 'nullable|string',
            'status' => 'required|in:active,inactive,vacation,terminated',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'staff_number' => $validated['staff_number'],
                'employee_name' => $validated['employee_name'],
                'email' => $validated['email'],
                'password' => Hash::make('password123'),
                'role' => 'employee',
            ]);

            $totalSalary = $validated['basic_salary'] +
                          ($validated['allowance'] ?? 0) +
                          ($validated['fixed_salary'] ?? 0);

            Employee::create(array_merge($validated, [
                'user_id' => $user->id,
                'total_salary' => $totalSalary,
            ]));

            DB::commit();

            return redirect()->route('employees.index')
                           ->with('success', 'Employee created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])
                        ->withInput();
        }
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'attendances' => fn($q) => $q->orderBy('attendance_date', 'desc')->limit(30),
            'overtimeRecords' => fn($q) => $q->orderBy('overtime_date', 'desc')->limit(20),
            'documentExpiryAlerts',
        ]);

        $expiringDocuments = $employee->getExpiringDocuments();

        return view('employees.show', compact('employee', 'expiringDocuments'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:255',
            'designation' => 'required|string',
            'qualification' => 'nullable|string',
            'pp_status' => 'nullable|string',
            'uae_contact' => 'nullable|string',
            'home_country_contact' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'duty_joined_date' => 'nullable|date',
            'duty_end_date' => 'nullable|date',
            'last_vacation_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'fixed_salary' => 'nullable|numeric|min:0',
            'recent_increment_amount' => 'nullable|numeric|min:0',
            'increment_date' => 'nullable|date',
            'passport_expiry_date' => 'nullable|date',
            'visit_expiry_date' => 'nullable|date',
            'visa_expiry_date' => 'nullable|date',
            'eid_expiry_date' => 'nullable|date',
            'health_insurance_expiry_date' => 'nullable|date',
            'driving_license_expiry_date' => 'nullable|date',
            'salary_card_details' => 'nullable|string',
            'remarks' => 'nullable|string',
            'status' => 'required|in:active,inactive,vacation,terminated',
        ]);

        $totalSalary = $validated['basic_salary'] +
                      ($validated['allowance'] ?? 0) +
                      ($validated['fixed_salary'] ?? 0);

        $employee->fill(array_merge($validated, [
            'total_salary' => $totalSalary,
        ]))->save();

        return redirect()->route('employees.show', $employee)
                        ->with('success', 'Employee updated successfully!');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')
                        ->with('success', 'Employee deleted successfully!');
    }

    public function export()
    {
        $employees = Employee::with(['user'])->get();

        $filename = 'employees_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($employees) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Staff Number', 'Employee Name', 'Designation', 'Email',
                'Basic Salary', 'Allowance', 'Total Salary',
                'Passport Expiry', 'Visa Expiry', 'Insurance Expiry',
                'Status', 'Join Date'
            ]);

            foreach ($employees as $employee) {
                fputcsv($file, [
                    $employee->staff_number,
                    $employee->employee_name,
                    $employee->designation,
                    $employee->user->email ?? '',
                    $employee->basic_salary,
                    $employee->allowance,
                    $employee->total_salary,
                    $employee->passport_expiry_date?->format('Y-m-d'),
                    $employee->visa_expiry_date?->format('Y-m-d'),
                    $employee->health_insurance_expiry_date?->format('Y-m-d'),
                    $employee->status,
                    $employee->duty_joined_date?->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
