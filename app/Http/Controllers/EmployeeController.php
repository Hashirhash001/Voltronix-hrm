<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Entity;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\EmployeeDocument;
use App\Models\EmployeeVacation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['user' => function ($q) {
            $q->where('role', '!=', 'admin');
        }, 'entity'])->whereHas('user', function ($q) {
            $q->where('role', '!=', 'admin');
        });

        // filters
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

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Designation filter
        if ($request->filled('designation')) {
            $query->where('designation', $request->designation);
        }

        // Entity filter
        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        // Salary filter
        if ($request->filled('min_salary')) {
            $query->where('total_salary', '>=', $request->min_salary);
        }

        $perPage = (int) ($request->get('per_page', 10));
        $perPage = max(5, min($perPage, 100));

        $employees = $query
            ->orderByRaw("CAST(REGEXP_REPLACE(staff_number, '[^0-9]', '') AS UNSIGNED) ASC")
            ->orderBy('staff_number', 'asc')
            ->paginate($perPage);

            if ($request->ajax() || $request->has('ajax')) {
                return response()->json([
                    'employees' => $employees->map(function($employee) {
                        return [
                            'id' => $employee->id,
                            'staff_number' => $employee->staff_number,
                            'employee_name' => $employee->employee_name,
                            'designation' => $employee->designation,
                            'status' => $employee->status,
                            'total_salary' => $employee->total_salary,
                            'employee_image' => $employee->employee_image,
                            'entity_name' => $employee->entity?->entity_name, // ✅ Include entity name
                            'user' => $employee->user ? [
                                'email' => $employee->user->email
                            ] : null,
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $employees->currentPage(),
                        'last_page'    => $employees->lastPage(),
                        'per_page'     => $employees->perPage(),
                        'total'        => $employees->total(),
                    ],
                ]);
            }

        $entities = Entity::where('status', 'active')
                         ->orderBy('entity_name')
                         ->get();

        return view('employees.index', compact('entities'));
    }

    public function create()
    {
        $entities = Entity::where('status', 'active')
                         ->orderBy('entity_name')
                         ->get();
        return view('employees.create', compact('entities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_number' => 'required|string|unique:employees,staff_number',
            'employee_name' => 'required|string',
            'employee_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:200',
            'email' => 'required|email|unique:users,email',
            'entity_id' => 'nullable|exists:entities,id',
            'designation' => 'required|string',
            'qualification' => 'nullable|string',
            'year_of_completion' => 'nullable|integer|min:1950|max:' . date('Y'),
            'qualification_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'pp_status' => 'nullable|string',
            'uae_contact' => 'nullable|string',
            'home_country_contact' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'current_age' => 'nullable|integer|min:0|max:120',
            'duty_joined_date' => 'nullable|date',
            'duty_end_date' => 'nullable|date',
            'basic_salary' => 'nullable|numeric|min:0',
            'allowance' => 'nullable|numeric|min:0',
            'fixed_salary' => 'nullable|numeric|min:0',
            'total_salary' => 'nullable|numeric|min:0',
            'recent_increment_amount' => 'nullable|numeric|min:0',
            'increment_date' => 'nullable|date',
            'passport_expiry_date' => 'nullable|date',
            'passport_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'visa_expiry_date' => 'nullable|date',
            'visa_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'visit_expiry_date' => 'nullable|date',
            'visit_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'eid_expiry_date' => 'nullable|date',
            'eid_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'health_insurance_expiry_date' => 'nullable|date',
            'health_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'driving_license_expiry_date' => 'nullable|date',
            'driving_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'salary_card_details' => 'nullable|string',
            'iloe_insurance_expiry_date' => 'nullable|date',
            'iloe_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'soe_card_renewal_date' => 'nullable|date',
            'soe_card_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'dcd_card_renewal_date' => 'nullable|date',
            'dcd_card_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'workman_insurance_expiry_date' => 'nullable|date',
            'workman_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
            'remarks' => 'nullable|string',
            'status' => 'required|in:active,inactive,vacation,terminated,resigned',
            'termination_date' => 'nullable|date|required_if:status,terminated',
            'resignation_date' => 'nullable|date|required_if:status,resigned',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['employee_name'],
                'email' => $validated['email'],
                'password' => Hash::make('password123'),
            ]);

            $basicSalary = (float) ($validated['basic_salary'] ?? 0);
            $allowance = (float) ($validated['allowance'] ?? 0);
            $fixedSalary = (float) ($validated['fixed_salary'] ?? 0);
            $totalSalary = $validated['total_salary'] ?? ($basicSalary + $allowance + $fixedSalary);

            // Handle document uploads
            $documentFields = [
                'qualification_document',
                'passport_document',
                'visa_document',
                'visit_document',
                'eid_document',
                'health_insurance_document',
                'driving_license_document',
                'iloe_insurance_document',
                'soe_card_document',
                'dcd_card_document',
                'workman_insurance_document',
            ];

            $employeeData = [
                'user_id' => $user->id,
                'entity_id' => $validated['entity_id'] ?? null,
                'staff_number' => $validated['staff_number'],
                'employee_name' => $validated['employee_name'],
                'designation' => $validated['designation'],
                'qualification' => $validated['qualification'] ?? null,
                'year_of_completion' => $validated['year_of_completion'] ?? null,
                'pp_status' => $validated['pp_status'] ?? null,
                'uae_contact' => $validated['uae_contact'] ?? null,
                'home_country_contact' => $validated['home_country_contact'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'current_age' => $validated['current_age'] ?? null,
                'duty_joined_date' => $validated['duty_joined_date'] ?? null,
                'duty_end_date' => $validated['duty_end_date'] ?? null,
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
                'soe_card_renewal_date' => $validated['soe_card_renewal_date'] ?? null,
                'dcd_card_renewal_date' => $validated['dcd_card_renewal_date'] ?? null,
                'workman_insurance_expiry_date' => $validated['workman_insurance_expiry_date'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'termination_date' => $validated['termination_date'] ?? null,
                'resignation_date' => $validated['resignation_date'] ?? null,
                'status' => $validated['status'],
            ];

            if (($validated['status'] ?? null) !== 'terminated') $employeeData['termination_date'] = null;
            if (($validated['status'] ?? null) !== 'resigned')   $employeeData['resignation_date'] = null;

            // Create employee first to get ID
            $employee = Employee::create($employeeData);

            if ($request->hasFile('employee_image')) {
                $path = $request->file('employee_image')
                    ->store('employee_images/' . $employee->id, 'public');

                $employee->employee_image = $path;
                $employee->save();
            }

            // Upload documents after employee creation
            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filePath = $file->store('employee_documents/' . $employee->id, 'public');
                    $employee->$field = $filePath;
                }
            }

            if ($employee->isDirty()) {
                $employee->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee added successfully! Default password is "password123"',
                'employee_id' => $employee->id,
                'redirect' => route('employees.show', $employee),
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . implode(' ', array_map(fn($err) => implode(' ', $err), $e->errors())),
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Employee Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Employee $employee)
    {
        $employee->load(['user', 'vacations', 'entity']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $entities = Entity::where('status', 'active')
                         ->orderBy('entity_name')
                         ->get();
        $employee->load('vacations');
        return view('employees.edit', compact('employee', 'entities'));
    }

    public function update(Request $request, Employee $employee)
    {
        try {
            DB::beginTransaction();

            Log::info('Employee Update Started', [
                'employee_id' => $employee->id,
                'status_before' => $employee->status,
                'deleted_at_before' => $employee->deleted_at,
            ]);

            // Validate input
            $validated = $request->validate([
                'staff_number' => 'required|string|unique:employees,staff_number,' . $employee->id . ',id,deleted_at,NULL',
                'employee_name' => 'required|string',
                'employee_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:200',
                'entity_id' => 'nullable|exists:entities,id',
                'designation' => 'required|string',
                'qualification' => 'nullable|string',
                'year_of_completion' => 'nullable|integer|min:1950|max:' . date('Y'),

                'qualification_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',
                'pp_status' => 'nullable|string',
                'uae_contact' => 'nullable|string',
                'home_country_contact' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'current_age' => 'nullable|integer',

                'duty_joined_date' => 'nullable|date',
                'duty_end_date' => 'nullable|date',

                'basic_salary' => 'nullable|numeric|min:0',
                'allowance' => 'nullable|numeric|min:0',
                'fixed_salary' => 'nullable|numeric|min:0',
                'total_salary' => 'nullable|numeric|min:0',

                'recent_increment_amount' => 'nullable|numeric|min:0',
                'increment_date' => 'nullable|date',

                'passport_expiry_date' => 'nullable|date',
                'passport_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'visa_expiry_date' => 'nullable|date',
                'visa_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'visit_expiry_date' => 'nullable|date',
                'visit_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'eid_expiry_date' => 'nullable|date',
                'eid_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'health_insurance_expiry_date' => 'nullable|date',
                'health_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'driving_license_expiry_date' => 'nullable|date',
                'driving_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'salary_card_details' => 'nullable|string',

                'iloe_insurance_expiry_date' => 'nullable|date',
                'iloe_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'soe_card_renewal_date' => 'nullable|date',
                'soe_card_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'dcd_card_renewal_date' => 'nullable|date',
                'dcd_card_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'workman_insurance_expiry_date' => 'nullable|date',
                'workman_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:500',

                'remarks' => 'nullable|string',

                'status' => 'required|in:active,inactive,vacation,terminated,resigned',
                'termination_date' => 'nullable|date|required_if:status,terminated',
                'resignation_date' => 'nullable|date|required_if:status,resigned',
            ]);

            // Uploadable docs handled here
            $documentFields = [
                'qualification_document',
                'passport_document',
                'visa_document',
                'visit_document',
                'eid_document',
                'health_insurance_document',
                'driving_license_document',
                'iloe_insurance_document',
                'soe_card_document',
                'dcd_card_document',
                'workman_insurance_document',
            ];

            // Clear exit dates when status changed away
            if (($validated['status'] ?? null) !== 'terminated') {
                $validated['termination_date'] = null;
            }
            if (($validated['status'] ?? null) !== 'resigned') {
                $validated['resignation_date'] = null;
            }

            // Update employee fields ONE BY ONE (avoid mass update issues with SoftDeletes)
            $employee->staff_number = $validated['staff_number'];
            $employee->employee_name = $validated['employee_name'];
            $employee->entity_id = $validated['entity_id'] ?? null;
            $employee->designation = $validated['designation'];
            $employee->qualification = $validated['qualification'] ?? null;
            $employee->year_of_completion = $validated['year_of_completion'] ?? null;
            $employee->pp_status = $validated['pp_status'] ?? null;
            $employee->uae_contact = $validated['uae_contact'] ?? null;
            $employee->home_country_contact = $validated['home_country_contact'] ?? null;
            $employee->date_of_birth = $validated['date_of_birth'] ?? null;
            $employee->current_age = $validated['current_age'] ?? null;
            $employee->duty_joined_date = $validated['duty_joined_date'] ?? null;
            $employee->duty_end_date = $validated['duty_end_date'] ?? null;
            $employee->basic_salary = $validated['basic_salary'] ?? 0;
            $employee->allowance = $validated['allowance'] ?? 0;
            $employee->fixed_salary = $validated['fixed_salary'] ?? 0;
            $employee->total_salary = $validated['total_salary'] ?? 0;
            $employee->recent_increment_amount = $validated['recent_increment_amount'] ?? null;
            $employee->increment_date = $validated['increment_date'] ?? null;
            $employee->passport_expiry_date = $validated['passport_expiry_date'] ?? null;
            $employee->visa_expiry_date = $validated['visa_expiry_date'] ?? null;
            $employee->visit_expiry_date = $validated['visit_expiry_date'] ?? null;
            $employee->eid_expiry_date = $validated['eid_expiry_date'] ?? null;
            $employee->health_insurance_expiry_date = $validated['health_insurance_expiry_date'] ?? null;
            $employee->driving_license_expiry_date = $validated['driving_license_expiry_date'] ?? null;
            $employee->salary_card_details = $validated['salary_card_details'] ?? null;
            $employee->iloe_insurance_expiry_date = $validated['iloe_insurance_expiry_date'] ?? null;
            $employee->soe_card_renewal_date = $validated['soe_card_renewal_date'] ?? null;
            $employee->dcd_card_renewal_date = $validated['dcd_card_renewal_date'] ?? null;
            $employee->workman_insurance_expiry_date = $validated['workman_insurance_expiry_date'] ?? null;
            $employee->remarks = $validated['remarks'] ?? null;
            $employee->status = $validated['status'];
            $employee->termination_date = $validated['termination_date'] ?? null;
            $employee->resignation_date = $validated['resignation_date'] ?? null;

            // Document uploads (replace old file if uploaded)
            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    if (!empty($employee->$field)) {
                        Storage::disk('public')->delete($employee->$field);
                    }
                    $employee->$field = $request->file($field)->store('employee_documents/' . $employee->id, 'public');
                }
            }

            // Employee image upload
            if ($request->hasFile('employee_image')) {
                if (!empty($employee->employee_image)) {
                    Storage::disk('public')->delete($employee->employee_image);
                }
                $employee->employee_image = $request->file('employee_image')->store('employee_images/' . $employee->id, 'public');
            }

            // Save employee BEFORE adding vacation
            $employee->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully!',
                'redirect' => route('employees.show', $employee),
            ], 200);

        } catch (ValidationException $e) {
            DB::rollBack();

            $errorMessage = 'Validation failed: ';
            $errors = $e->errors();
            foreach ($errors as $field => $messages) {
                $errorMessage .= implode(' ', $messages) . ' ';
            }

            Log::warning('Validation error during employee update', [
                'employee_id' => $employee->id ?? null,
                'errors' => $errors,
            ]);

            return response()->json([
                'success' => false,
                'message' => trim($errorMessage),
                'errors'  => $errors,
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Employee Update Error: ' . $e->getMessage(), [
                'employee_id' => $employee->id ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            // Delete all stored documents you manage in store/update
            $documentFields = [
                'qualification_document',
                'passport_document',
                'visa_document',
                'visit_document',
                'eid_document',
                'health_insurance_document',
                'driving_license_document',
                'iloe_insurance_document',
                'soe_card_document',
                'dcd_card_document',
                'workman_insurance_document',
            ];

            foreach ($documentFields as $field) {
                if ($employee->$field) {
                    Storage::disk('public')->delete($employee->$field);
                }
            }

            if ($employee->employee_image) {
                Storage::disk('public')->delete($employee->employee_image);
            }

            // delete all linked vacations
            foreach ($employee->vacations as $vacation) {
                $vacation->delete();
            }

            // delete linked auth user
            if ($employee->user) {
                $employee->user->delete();
            }

            // delete employee (vacations will cascade if FK is set)
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

    public function destroyVacation(Employee $employee, EmployeeVacation $vacation)
    {
        try {
            if ($vacation->employee_id !== $employee->id) abort(404);

            $vacation->delete();

            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Vacation deleted successfully!'], 200);
            }

            return back()->with('success', 'Vacation deleted successfully!');
        } catch (\Throwable $e) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function storeVacation(Request $request, Employee $employee)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'remarks' => 'nullable|string|max:1000',
            ]);

            $vacStart = Carbon::parse($validated['start_date'])->startOfDay();
            $vacEnd = Carbon::parse($validated['end_date'])->startOfDay();

            Log::info('Adding vacation via modal', [
                'employee_id' => $employee->id,
                'start_date' => $vacStart->toDateString(),
                'end_date' => $vacEnd->toDateString(),
            ]);

            // Check for overlaps
            $hasOverlap = $employee->vacations()
                ->where(function($query) use ($vacStart, $vacEnd) {
                    $vacStartStr = $vacStart->toDateString();
                    $vacEndStr = $vacEnd->toDateString();

                    $query
                        ->where(function($q) use ($vacStartStr, $vacEndStr) {
                            $q->where('start_date', '>=', $vacStartStr)
                            ->where('start_date', '<=', $vacEndStr);
                        })
                        ->orWhere(function($q) use ($vacStartStr, $vacEndStr) {
                            $q->where('end_date', '>=', $vacStartStr)
                            ->where('end_date', '<=', $vacEndStr);
                        })
                        ->orWhere(function($q) use ($vacStartStr, $vacEndStr) {
                            $q->where('start_date', '<=', $vacStartStr)
                            ->where('end_date', '>=', $vacEndStr);
                        });
                })
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'This vacation period overlaps with an existing vacation.',
                    'errors' => [
                        'start_date' => ['This vacation period overlaps with an existing vacation.'],
                        'end_date' => ['This vacation period overlaps with an existing vacation.'],
                    ]
                ], 422);
            }

            // Create vacation
            $vacation = $employee->vacations()->create([
                'start_date' => $vacStart->toDateString(),
                'end_date' => $vacEnd->toDateString(),
                'remarks' => $validated['remarks'] ?? null,
            ]);

            Log::info('Vacation added successfully', [
                'vacation_id' => $vacation->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacation added successfully!',
                    'vacation' => $vacation
                ], 200);
            }

            return redirect()->back()->with('success', 'Vacation added successfully!');

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            Log::error('Error adding vacation: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add vacation: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withErrors(['error' => 'Failed to add vacation.'])->withInput();
        }
    }

    public function updateVacation(Request $request, Employee $employee, EmployeeVacation $vacation)
    {
        try {
            // Check if vacation belongs to employee
            if ($vacation->employee_id !== $employee->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vacation record does not belong to this employee.'
                ], 403);
            }

            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'remarks' => 'nullable|string|max:1000',
            ]);

            $vacStart = Carbon::parse($validated['start_date'])->startOfDay();
            $vacEnd = Carbon::parse($validated['end_date'])->startOfDay();

            Log::info('Updating vacation', [
                'vacation_id' => $vacation->id,
                'employee_id' => $employee->id,
                'old_start' => $vacation->start_date,
                'old_end' => $vacation->end_date,
                'new_start' => $vacStart->toDateString(),
                'new_end' => $vacEnd->toDateString(),
            ]);

            // Check for overlaps with OTHER vacations (excluding current one)
            $hasOverlap = $employee->vacations()
                ->where('id', '!=', $vacation->id) // Exclude current vacation
                ->where(function($query) use ($vacStart, $vacEnd) {
                    $vacStartStr = $vacStart->toDateString();
                    $vacEndStr = $vacEnd->toDateString();

                    $query
                        ->where(function($q) use ($vacStartStr, $vacEndStr) {
                            $q->where('start_date', '>=', $vacStartStr)
                            ->where('start_date', '<=', $vacEndStr);
                        })
                        ->orWhere(function($q) use ($vacStartStr, $vacEndStr) {
                            $q->where('end_date', '>=', $vacStartStr)
                            ->where('end_date', '<=', $vacEndStr);
                        })
                        ->orWhere(function($q) use ($vacStartStr, $vacEndStr) {
                            $q->where('start_date', '<=', $vacStartStr)
                            ->where('end_date', '>=', $vacEndStr);
                        });
                })
                ->exists();

            if ($hasOverlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'This vacation period overlaps with another existing vacation.',
                    'errors' => [
                        'start_date' => ['This vacation period overlaps with another existing vacation.'],
                        'end_date' => ['This vacation period overlaps with another existing vacation.'],
                    ]
                ], 422);
            }

            // Update vacation
            $vacation->update([
                'start_date' => $vacStart->toDateString(),
                'end_date' => $vacEnd->toDateString(),
                'remarks' => $validated['remarks'] ?? null,
            ]);

            Log::info('Vacation updated successfully', [
                'vacation_id' => $vacation->id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vacation updated successfully!',
                    'vacation' => $vacation->fresh()
                ], 200);
            }

            return redirect()->back()->with('success', 'Vacation updated successfully!');

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            Log::error('Error updating vacation: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update vacation: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->withErrors(['error' => 'Failed to update vacation.'])->withInput();
        }
    }

}
