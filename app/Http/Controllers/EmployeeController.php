<?php
// app/Http/Controllers/EmployeeController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(Request $request)
    {
        $query = Employee::query()->with('user');

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

        if ($request->ajax()) {
            $employees = $query->get();
            return response()->json(['employees' => $employees]);
        }

        $employees = $query->paginate(50);
        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource
     */
    public function create()
    {
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_number' => 'required|string|unique:employees,staff_number',
            'employee_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'designation' => 'required|string',
            'qualification' => 'nullable|string',
            'pp_status' => 'nullable|string',
            'uae_contact' => 'nullable|string',
            'home_country_contact' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'current_age' => 'nullable|integer|min:0|max:120',
            'duty_joined_date' => 'nullable|date',
            'duty_end_date' => 'nullable|date',
            'duty_days' => 'nullable|integer|min:0',
            'duty_years' => 'nullable|numeric|min:0|max:999.99',
            'last_vacation_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0|max:9999999.99',
            'allowance' => 'nullable|numeric|min:0|max:9999999.99',
            'fixed_salary' => 'nullable|numeric|min:0|max:9999999.99',
            'total_salary' => 'nullable|numeric|min:0|max:9999999.99',
            'recent_increment_amount' => 'nullable|numeric|min:0|max:9999999.99',
            'increment_date' => 'nullable|date',
            'passport_expiry_date' => 'nullable|date',
            'visa_expiry_date' => 'nullable|date',
            'visit_expiry_date' => 'nullable|date',
            'eid_expiry_date' => 'nullable|date',
            'health_insurance_expiry_date' => 'nullable|date',
            'driving_license_expiry_date' => 'nullable|date',
            'salary_card_details' => 'nullable|string',
            'iloe_insurance_expiry_date' => 'nullable|date',
            'vtnx_trade_license_renewal_date' => 'nullable|date',
            'po_box_renewal_date' => 'nullable|date',
            'soe_card_renewal_date' => 'nullable|date',
            'dcd_card_renewal_date' => 'nullable|date',
            'voltronix_est_card_renewal_date' => 'nullable|date',
            'warehouse_ejari_renewal_date' => 'nullable|date',
            'camp_ejari_renewal_date' => 'nullable|date',
            'workman_insurance_expiry_date' => 'nullable|date',
            'etisalat_contract_expiry_date' => 'nullable|date',
            'dewa_details' => 'nullable|string',
            'remarks' => 'nullable|string',
            'status' => 'required|in:active,inactive,vacation,terminated',
        ]);

        try {
            DB::beginTransaction();

            // Step 1: Create User with ONLY user fields
            $user = User::create([
                'name' => $validated['employee_name'],
                'email' => $validated['email'],
                'password' => Hash::make('password123'),
            ]);

            // Step 2: Prepare salary values
            $basicSalary = (float) ($validated['basic_salary'] ?? 0);
            $allowance = (float) ($validated['allowance'] ?? 0);
            $fixedSalary = (float) ($validated['fixed_salary'] ?? 0);
            $totalSalary = $validated['total_salary'] ?? ($basicSalary + $allowance + $fixedSalary);

            // Step 3: Prepare duty_years - ensure it's within range
            $dutyYears = $validated['duty_years'] ?? null;
            if ($dutyYears !== null) {
                $dutyYears = min((float) $dutyYears, 999.99);
                $dutyYears = max($dutyYears, 0);
            }

            // Step 4: Build employee data array
            $employeeData = [
                'user_id' => $user->id,
                'staff_number' => $validated['staff_number'],
                'employee_name' => $validated['employee_name'],
                'designation' => $validated['designation'],
                'qualification' => $validated['qualification'] ?? null,
                'pp_status' => $validated['pp_status'] ?? null,
                'uae_contact' => $validated['uae_contact'] ?? null,
                'home_country_contact' => $validated['home_country_contact'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'current_age' => $validated['current_age'] ?? null,
                'duty_joined_date' => $validated['duty_joined_date'] ?? null,
                'duty_end_date' => $validated['duty_end_date'] ?? null,
                'duty_days' => $validated['duty_days'] ?? null,
                'duty_years' => $dutyYears,
                'last_vacation_date' => $validated['last_vacation_date'] ?? null,
                'basic_salary' => $basicSalary,
                'allowance' => $allowance,
                'fixed_salary' => $fixedSalary,
                'total_salary' => (float) $totalSalary,
                'recent_increment_amount' => $validated['recent_increment_amount'] ?? null,
                'increment_date' => $validated['increment_date'] ?? null,
                'passport_expiry_date' => $validated['passport_expiry_date'] ?? null,
                'visa_expiry_date' => $validated['visa_expiry_date'] ?? null,
                'visit_expiry_date' => $validated['visit_expiry_date'] ?? null,
                'eid_expiry_date' => $validated['eid_expiry_date'] ?? null,
                'health_insurance_expiry_date' => $validated['health_insurance_expiry_date'] ?? null,
                'driving_license_expiry_date' => $validated['driving_license_expiry_date'] ?? null,
                'salary_card_details' => $validated['salary_card_details'] ?? null,
                'iloe_insurance_expiry_date' => $validated['iloe_insurance_expiry_date'] ?? null,
                'vtnx_trade_license_renewal_date' => $validated['vtnx_trade_license_renewal_date'] ?? null,
                'po_box_renewal_date' => $validated['po_box_renewal_date'] ?? null,
                'soe_card_renewal_date' => $validated['soe_card_renewal_date'] ?? null,
                'dcd_card_renewal_date' => $validated['dcd_card_renewal_date'] ?? null,
                'voltronix_est_card_renewal_date' => $validated['voltronix_est_card_renewal_date'] ?? null,
                'warehouse_ejari_renewal_date' => $validated['warehouse_ejari_renewal_date'] ?? null,
                'camp_ejari_renewal_date' => $validated['camp_ejari_renewal_date'] ?? null,
                'workman_insurance_expiry_date' => $validated['workman_insurance_expiry_date'] ?? null,
                'etisalat_contract_expiry_date' => $validated['etisalat_contract_expiry_date'] ?? null,
                'dewa_details' => $validated['dewa_details'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => $validated['status'],
            ];

            // Step 5: Create Employee
            $employee = Employee::create($employeeData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee added successfully! Default password is "password123"',
                'employee_id' => $employee->id,
                'redirect' => route('employees.show', $employee),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Store Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee: ' . $e->getMessage(),
            ], 422);
        }
    }
    /**
     * Display the specified resource
     */
    public function show(Employee $employee)
    {
        $employee->load('user');
        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'staff_number' => 'required|string|unique:employees,staff_number,' . $employee->id,
            'employee_name' => 'required|string',
            'designation' => 'required|string',
            'qualification' => 'nullable|string',
            'pp_status' => 'nullable|string',
            'uae_contact' => 'nullable|string',
            'home_country_contact' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'current_age' => 'nullable|integer',
            'duty_joined_date' => 'nullable|date',
            'duty_end_date' => 'nullable|date',
            'duty_days' => 'nullable|integer',
            'duty_years' => 'nullable|numeric',
            'last_vacation_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'fixed_salary' => 'nullable|numeric|min:0',
            'total_salary' => 'nullable|numeric|min:0',
            'recent_increment_amount' => 'nullable|numeric|min:0',
            'increment_date' => 'nullable|date',
            'passport_expiry_date' => 'nullable|date',
            'visa_expiry_date' => 'nullable|date',
            'visit_expiry_date' => 'nullable|date',
            'eid_expiry_date' => 'nullable|date',
            'health_insurance_expiry_date' => 'nullable|date',
            'driving_license_expiry_date' => 'nullable|date',
            'salary_card_details' => 'nullable|string',
            'iloe_insurance_expiry_date' => 'nullable|date',
            'vtnx_trade_license_renewal_date' => 'nullable|date',
            'po_box_renewal_date' => 'nullable|date',
            'soe_card_renewal_date' => 'nullable|date',
            'dcd_card_renewal_date' => 'nullable|date',
            'voltronix_est_card_renewal_date' => 'nullable|date',
            'warehouse_ejari_renewal_date' => 'nullable|date',
            'camp_ejari_renewal_date' => 'nullable|date',
            'workman_insurance_expiry_date' => 'nullable|date',
            'etisalat_contract_expiry_date' => 'nullable|date',
            'dewa_details' => 'nullable|string',
            'remarks' => 'nullable|string',
            'status' => 'required|in:active,inactive,vacation,terminated',
        ]);

        try {
            DB::beginTransaction();

            $employee->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully!',
                'redirect' => route('employees.show', $employee),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            if ($employee->user) {
                $employee->user->delete();
            }

            $employee->delete();

            DB::commit();

            return redirect()->route('employees.index')
                ->with('success', 'Employee deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete employee: ' . $e->getMessage()]);
        }
    }
}
