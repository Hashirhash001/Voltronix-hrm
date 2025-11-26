<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('user');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                  ->orWhere('staff_number', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('designation')) {
            $query->where('designation', $request->designation);
        }

        if ($request->filled('min_salary')) {
            $query->where('total_salary', '>=', $request->min_salary);
        }

        $employees = $query->orderBy('created_at', 'desc')->get();

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'employees' => $employees
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
            'staff_number' => 'required|string|unique:employees,staff_number',
            'employee_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'designation' => 'required|string',
            'qualification' => 'nullable|string',
            'year_of_completion' => 'nullable|integer|min:1950|max:' . date('Y'),
            'qualification_document' => 'nullable|file|mimes:pdf|max:2048',
            'pp_status' => 'nullable|string',
            'uae_contact' => 'nullable|string',
            'home_country_contact' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'current_age' => 'nullable|integer|min:0|max:120',
            'duty_joined_date' => 'nullable|date',
            'duty_end_date' => 'nullable|date',
            'last_vacation_date' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0|max:9999999.99',
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
            'status' => 'required|in:active,inactive,vacation,terminated,resigned',
        ]);

        try {
            DB::beginTransaction();

            // Step 1: Create User
            $user = User::create([
                'name' => $validated['employee_name'],
                'email' => $validated['email'],
                'password' => Hash::make('password123'),
            ]);

            // Step 2: Handle file upload
            $qualificationDocumentPath = null;
            if ($request->hasFile('qualification_document')) {
                $qualificationDocumentPath = $request->file('qualification_document')
                    ->store('qualification_documents', 'public');
            }

            // Step 3: Prepare salary values
            $basicSalary = (float) ($validated['basic_salary'] ?? 0);
            $allowance = (float) ($validated['allowance'] ?? 0);
            $fixedSalary = (float) ($validated['fixed_salary'] ?? 0);
            $totalSalary = $validated['total_salary'] ?? ($basicSalary + $allowance + $fixedSalary);

            // Step 4: Build employee data array with correct field names
            $employeeData = [
                'user_id' => $user->id,
                'staff_number' => $validated['staff_number'],
                'employee_name' => $validated['employee_name'],
                'designation' => $validated['designation'],
                'qualification' => $validated['qualification'] ?? null,
                'year_of_completion' => $validated['year_of_completion'] ?? null,
                'qualification_document' => $qualificationDocumentPath,
                'pp_status' => $validated['pp_status'] ?? null,
                'uae_contact' => $validated['uae_contact'] ?? null,
                'home_country_contact' => $validated['home_country_contact'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'current_age' => $validated['current_age'] ?? null,
                'duty_joined_date' => $validated['duty_joined_date'] ?? null,
                'duty_end_date' => $validated['duty_end_date'] ?? null,
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

    public function show(Employee $employee)
    {
        $employee->load('user');
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'staff_number' => 'required|string|unique:employees,staff_number,' . $employee->id,
            'employee_name' => 'required|string',
            'designation' => 'required|string',
            'qualification' => 'nullable|string',
            'year_of_completion' => 'nullable|integer|min:1950|max:' . date('Y'),
            'qualification_document' => 'nullable|file|mimes:pdf|max:2048',
            'pp_status' => 'nullable|string',
            'uae_contact' => 'nullable|string',
            'home_country_contact' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'current_age' => 'nullable|integer',
            'duty_joined_date' => 'nullable|date',
            'duty_end_date' => 'nullable|date',
            'last_vacation_date' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0',
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
            'status' => 'required|in:active,inactive,vacation,terminated,resigned',
        ]);

        try {
            DB::beginTransaction();

            // Handle file upload
            if ($request->hasFile('qualification_document')) {
                // Delete old document if exists
                if ($employee->qualification_document) {
                    Storage::disk('public')->delete($employee->qualification_document);
                }
                $validated['qualification_document'] = $request->file('qualification_document')
                    ->store('qualification_documents', 'public');
            }

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

    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            // Delete qualification document if exists
            if ($employee->qualification_document) {
                Storage::disk('public')->delete($employee->qualification_document);
            }

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
