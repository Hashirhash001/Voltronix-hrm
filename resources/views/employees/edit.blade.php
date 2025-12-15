{{-- resources/views/employees/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <a href="{{ route('employees.index') }}" class="text-primary hover:underline">Employees</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Edit Employee</span>
        </li>
    </ul>

    <div class="pt-5">
        <form id="employeeForm" action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="panel">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Edit Employee: {{ $employee->employee_name }}</h5>
                </div>

                <!-- Personal Information Section -->
                <h6 class="mb-4 text-base font-bold">Personal Information</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="staff_number">Staff Number <span class="text-danger">*</span></label>
                        <input id="staff_number" type="text" name="staff_number" class="form-input" placeholder="e.g., EMP001" value="{{ old('staff_number', $employee->staff_number) }}" required/>
                    </div>

                    <div>
                        <label for="employee_name">Employee Name <span class="text-danger">*</span></label>
                        <input id="employee_name" type="text" name="employee_name" class="form-input" placeholder="Enter full name" value="{{ old('employee_name', $employee->employee_name) }}" required/>
                    </div>

                    <div>
                        <label for="email">Email</label>
                        <input id="email" type="email" class="form-input bg-gray-100 dark:bg-gray-800 cursor-not-allowed" value="{{ $employee->user->email ?? 'N/A' }}" disabled/>
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="designation">Designation <span class="text-danger">*</span></label>
                        <input id="designation" type="text" name="designation" class="form-input" placeholder="e.g., Electrician" value="{{ old('designation', $employee->designation) }}" required>
                    </div>
                    <div>
                        <label for="qualification">Qualification</label>
                        <input id="qualification" type="text" name="qualification" class="form-input" placeholder="e.g., Bachelor of Engineering" value="{{ old('qualification', $employee->qualification) }}">
                    </div>
                    <div>
                        <label for="year_of_completion">Year of Completion</label>
                        <input id="year_of_completion" type="number" name="year_of_completion" class="form-input" placeholder="e.g., 2020" min="1950" max="{{ date('Y') }}" value="{{ old('year_of_completion', $employee->year_of_completion) }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="qualification_document">Qualification Document</label>
                        @if($employee->qualification_document)
                            <div class="mb-2 flex items-center gap-2">
                                <a href="{{ asset('storage/' . $employee->qualification_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current Document
                                </a>
                            </div>
                        @endif
                        <input id="qualification_document" type="file" name="qualification_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Upload new to replace existing (Max: 5MB)</p>
                    </div>
                    <div>
                        <label for="pp_status">PP Status</label>
                        <input id="pp_status" type="text" name="pp_status" class="form-input" placeholder="PP status" value="{{ old('pp_status', $employee->pp_status) }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="uae_contact">UAE Contact</label>
                        <input id="uae_contact" type="text" name="uae_contact" class="form-input" placeholder="+971..." value="{{ old('uae_contact', $employee->uae_contact) }}"/>
                    </div>

                    <div>
                        <label for="home_country_contact">Home Country Contact</label>
                        <input id="home_country_contact" type="text" name="home_country_contact" class="form-input" placeholder="+country..." value="{{ old('home_country_contact', $employee->home_country_contact) }}"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="date_of_birth">Date of Birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" class="form-input" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}"/>
                    </div>

                    <div>
                        <label for="current_age">Current Age</label>
                        <input id="current_age" type="number" name="current_age" class="form-input" placeholder="Age" value="{{ old('current_age', $employee->current_age) }}"/>
                    </div>

                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="active" {{ old('status', $employee->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $employee->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="vacation" {{ old('status', $employee->status) === 'vacation' ? 'selected' : '' }}>Vacation</option>
                            <option value="terminated" {{ old('status', $employee->status) === 'terminated' ? 'selected' : '' }}>Terminated</option>
                            <option value="resigned" {{ old('status', $employee->status) == 'resigned' ? 'selected' : '' }}>Resigned</option>
                        </select>
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Duty Information -->
                <h6 class="mb-4 text-base font-bold">Duty Information</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="duty_joined_date">Duty Joined Date</label>
                        <input id="duty_joined_date" type="date" name="duty_joined_date" class="form-input" value="{{ old('duty_joined_date', $employee->duty_joined_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="duty_end_date">Duty End Date</label>
                        <input id="duty_end_date" type="date" name="duty_end_date" class="form-input" value="{{ old('duty_end_date', $employee->duty_end_date?->format('Y-m-d')) }}">
                        <p class="text-xs text-gray-500 mt-1">Leave blank if ongoing</p>
                    </div>
                    <div>
                        <label for="last_vacation_date">Last Vacation Date</label>
                        <input id="last_vacation_date" type="date" name="last_vacation_date" class="form-input" value="{{ old('last_vacation_date', $employee->last_vacation_date?->format('Y-m-d')) }}">
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Salary Information Section -->
                <h6 class="mb-4 text-base font-bold">Salary Information (AED)</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                    <div>
                        <label for="basic_salary">Basic Salary <span class="text-danger">*</span></label>
                        <input id="basic_salary" type="number" step="0.01" name="basic_salary" class="form-input" placeholder="0.00" value="{{ old('basic_salary', $employee->basic_salary) }}" required/>
                    </div>

                    <div>
                        <label for="allowance">Allowance</label>
                        <input id="allowance" type="number" step="0.01" name="allowance" class="form-input" placeholder="0.00" value="{{ old('allowance', $employee->allowance) }}"/>
                    </div>

                    <div>
                        <label for="fixed_salary">Fixed Salary</label>
                        <input id="fixed_salary" type="number" step="0.01" name="fixed_salary" class="form-input" placeholder="0.00" value="{{ old('fixed_salary', $employee->fixed_salary) }}"/>
                    </div>

                    <div>
                        <label for="total_salary">Total Salary</label>
                        <input id="total_salary" type="number" step="0.01" class="form-input bg-gray-100 dark:bg-gray-800" placeholder="0.00" value="{{ old('total_salary', $employee->total_salary) }}" readonly/>
                        <input type="hidden" id="total_salary_input" name="total_salary" value="{{ $employee->total_salary }}"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="recent_increment_amount">Recent Increment Amount</label>
                        <input id="recent_increment_amount" type="number" step="0.01" name="recent_increment_amount" class="form-input" placeholder="0.00" value="{{ old('recent_increment_amount', $employee->recent_increment_amount) }}"/>
                    </div>

                    <div>
                        <label for="increment_date">Increment Date</label>
                        <input id="increment_date" type="date" name="increment_date" class="form-input" value="{{ old('increment_date', $employee->increment_date?->format('Y-m-d')) }}"/>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="salary_card_details">Salary Card Details</label>
                    <input id="salary_card_details" type="text" name="salary_card_details" class="form-input" placeholder="Enter salary card details" value="{{ old('salary_card_details', $employee->salary_card_details) }}"/>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Personal Documents Section -->
                <h6 class="mb-4 text-base font-bold">Personal Documents</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="passport_expiry_date">Passport Expiry Date</label>
                        <input id="passport_expiry_date" type="date" name="passport_expiry_date" class="form-input" value="{{ old('passport_expiry_date', $employee->passport_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="passport_document">Passport Document</label>
                        @if($employee->passport_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->passport_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="passport_document" type="file" name="passport_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="visa_expiry_date">Visa Expiry Date</label>
                        <input id="visa_expiry_date" type="date" name="visa_expiry_date" class="form-input" value="{{ old('visa_expiry_date', $employee->visa_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="visa_document">Visa Document</label>
                        @if($employee->visa_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->visa_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="visa_document" type="file" name="visa_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="visit_expiry_date">Visit Permit Expiry Date</label>
                        <input id="visit_expiry_date" type="date" name="visit_expiry_date" class="form-input" value="{{ old('visit_expiry_date', $employee->visit_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="visit_document">Visit Permit Document</label>
                        @if($employee->visit_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->visit_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="visit_document" type="file" name="visit_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="eid_expiry_date">EID Expiry Date</label>
                        <input id="eid_expiry_date" type="date" name="eid_expiry_date" class="form-input" value="{{ old('eid_expiry_date', $employee->eid_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="eid_document">EID Document</label>
                        @if($employee->eid_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->eid_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="eid_document" type="file" name="eid_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="health_insurance_expiry_date">Health Insurance Expiry</label>
                        <input id="health_insurance_expiry_date" type="date" name="health_insurance_expiry_date" class="form-input" value="{{ old('health_insurance_expiry_date', $employee->health_insurance_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="health_insurance_document">Health Insurance Document</label>
                        @if($employee->health_insurance_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->health_insurance_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="health_insurance_document" type="file" name="health_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="driving_license_expiry_date">Driving License Expiry</label>
                        <input id="driving_license_expiry_date" type="date" name="driving_license_expiry_date" class="form-input" value="{{ old('driving_license_expiry_date', $employee->driving_license_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="driving_license_document">Driving License Document</label>
                        @if($employee->driving_license_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->driving_license_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="driving_license_document" type="file" name="driving_license_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Company & Insurance Documents -->
                <h6 class="mb-4 text-base font-bold">Company & Insurance Documents</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="iloe_insurance_expiry_date">ILOE Insurance Expiry</label>
                        <input id="iloe_insurance_expiry_date" type="date" name="iloe_insurance_expiry_date" class="form-input" value="{{ old('iloe_insurance_expiry_date', $employee->iloe_insurance_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="iloe_insurance_document">ILOE Insurance Document</label>
                        @if($employee->iloe_insurance_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->iloe_insurance_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="iloe_insurance_document" type="file" name="iloe_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="soe_card_renewal_date">SOE Card Renewal Date</label>
                        <input id="soe_card_renewal_date" type="date" name="soe_card_renewal_date" class="form-input" value="{{ old('soe_card_renewal_date', $employee->soe_card_renewal_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="soe_card_document">SOE Card Document</label>
                        @if($employee->soe_card_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->soe_card_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="soe_card_document" type="file" name="soe_card_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="dcd_card_renewal_date">DCD Card Renewal Date</label>
                        <input id="dcd_card_renewal_date" type="date" name="dcd_card_renewal_date" class="form-input" value="{{ old('dcd_card_renewal_date', $employee->dcd_card_renewal_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="dcd_card_document">DCD Card Document</label>
                        @if($employee->dcd_card_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->dcd_card_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="dcd_card_document" type="file" name="dcd_card_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="workman_insurance_expiry_date">Workman Insurance Expiry</label>
                        <input id="workman_insurance_expiry_date" type="date" name="workman_insurance_expiry_date" class="form-input" value="{{ old('workman_insurance_expiry_date', $employee->workman_insurance_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="workman_insurance_document">Workman Insurance Document</label>
                        @if($employee->workman_insurance_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $employee->workman_insurance_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="workman_insurance_document" type="file" name="workman_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Remarks Section -->
                <h6 class="mb-4 text-base font-bold">Additional Information</h6>

                <div class="mt-5">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" class="form-textarea" placeholder="Enter any remarks about the employee">{{ old('remarks', $employee->remarks) }}</textarea>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex items-center justify-end gap-2">
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-danger">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-success gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 4H4C2.89543 4 2 4.89543 2 6V20C2 21.1046 2.89543 22 4 22H18C19.1046 22 20 21.1046 20 20V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.5 2.5L21.5 5.5M22 4L16 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Update Employee
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function calculateTotal() {
        const basicSalary = parseFloat(document.getElementById('basic_salary').value) || 0;
        const allowance = parseFloat(document.getElementById('allowance').value) || 0;
        const fixedSalary = parseFloat(document.getElementById('fixed_salary').value) || 0;
        const totalSalary = document.getElementById('total_salary');
        const totalSalaryInput = document.getElementById('total_salary_input');

        const total = (basicSalary + allowance + fixedSalary).toFixed(2);
        totalSalary.value = total;
        totalSalaryInput.value = total;
    }

    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();

        ['basic_salary', 'allowance', 'fixed_salary'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', calculateTotal);
                element.addEventListener('input', calculateTotal);
            }
        });

        const form = document.getElementById('employeeForm');
        const submitBtn = document.getElementById('submitBtn');

        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                calculateTotal();

                const originalHTML = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Updating...';

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Try Again',
                        });
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An unexpected error occurred. Please try again.',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Try Again',
                    });
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                });
            });
        }
    });
</script>
@endpush
@endsection
