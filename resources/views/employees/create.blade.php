{{-- resources/views/employees/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Add Employee')

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
            <span>Add Employee</span>
        </li>
    </ul>

    <div class="pt-5">
        <form action="{{ route('employees.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="panel">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Add New Employee</h5>
                </div>

                <!-- Personal Information Section -->
                <h6 class="mb-4 text-base font-bold">Personal Information</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="staff_number">Staff Number <span class="text-danger">*</span></label>
                        <input id="staff_number" type="text" name="staff_number" class="form-input" placeholder="e.g., EMP001" value="{{ old('staff_number') }}" required/>
                        @error('staff_number')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="employee_name">Employee Name <span class="text-danger">*</span></label>
                        <input id="employee_name" type="text" name="employee_name" class="form-input" placeholder="Enter full name" value="{{ old('employee_name') }}" required/>
                        @error('employee_name')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input id="email" type="email" name="email" class="form-input" placeholder="Enter email address" value="{{ old('email') }}" required/>
                        @error('email')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="designation">Designation <span class="text-danger">*</span></label>
                        <input id="designation" type="text" name="designation" class="form-input" placeholder="e.g., Electrician" value="{{ old('designation') }}" required/>
                        @error('designation')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="qualification">Qualification</label>
                        <input id="qualification" type="text" name="qualification" class="form-input" placeholder="e.g., Diploma" value="{{ old('qualification') }}"/>
                    </div>

                    <div>
                        <label for="pp_status">PP Status</label>
                        <input id="pp_status" type="text" name="pp_status" class="form-input" placeholder="PP status" value="{{ old('pp_status') }}"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="uae_contact">UAE Contact</label>
                        <input id="uae_contact" type="text" name="uae_contact" class="form-input" placeholder="+971..." value="{{ old('uae_contact') }}"/>
                    </div>

                    <div>
                        <label for="home_country_contact">Home Country Contact</label>
                        <input id="home_country_contact" type="text" name="home_country_contact" class="form-input" placeholder="+country..." value="{{ old('home_country_contact') }}"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="date_of_birth">Date of Birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" class="form-input" value="{{ old('date_of_birth') }}"/>
                    </div>

                    <div>
                        <label for="duty_joined_date">Duty Joined Date</label>
                        <input id="duty_joined_date" type="date" name="duty_joined_date" class="form-input" value="{{ old('duty_joined_date') }}"/>
                    </div>

                    <div>
                        <label for="duty_end_date">Duty End Date</label>
                        <input id="duty_end_date" type="date" name="duty_end_date" class="form-input" value="{{ old('duty_end_date') }}"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="last_vacation_date">Last Vacation Date</label>
                        <input id="last_vacation_date" type="date" name="last_vacation_date" class="form-input" value="{{ old('last_vacation_date') }}"/>
                    </div>

                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="vacation" {{ old('status') === 'vacation' ? 'selected' : '' }}>Vacation</option>
                            <option value="terminated" {{ old('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                        </select>
                        @error('status')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Salary Information Section -->
                <h6 class="mb-4 text-base font-bold">Salary Information</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                    <div>
                        <label for="basic_salary">Basic Salary <span class="text-danger">*</span></label>
                        <input id="basic_salary" type="number" step="0.01" name="basic_salary" class="form-input" placeholder="0.00" value="{{ old('basic_salary', 0) }}" required onchange="calculateTotal()"/>
                        @error('basic_salary')
                            <span class="text-danger text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="allowance">Allowance</label>
                        <input id="allowance" type="number" step="0.01" name="allowance" class="form-input" placeholder="0.00" value="{{ old('allowance', 0) }}" onchange="calculateTotal()"/>
                    </div>

                    <div>
                        <label for="fixed_salary">Fixed Salary</label>
                        <input id="fixed_salary" type="number" step="0.01" name="fixed_salary" class="form-input" placeholder="0.00" value="{{ old('fixed_salary', 0) }}" onchange="calculateTotal()"/>
                    </div>

                    <div>
                        <label for="total_salary">Total Salary</label>
                        <input id="total_salary" type="number" step="0.01" class="form-input bg-gray-100 dark:bg-gray-800" placeholder="0.00" readonly/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="recent_increment_amount">Recent Increment Amount</label>
                        <input id="recent_increment_amount" type="number" step="0.01" name="recent_increment_amount" class="form-input" placeholder="0.00" value="{{ old('recent_increment_amount', 0) }}"/>
                    </div>

                    <div>
                        <label for="increment_date">Increment Date</label>
                        <input id="increment_date" type="date" name="increment_date" class="form-input" value="{{ old('increment_date') }}"/>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="salary_card_details">Salary Card Details</label>
                    <input id="salary_card_details" type="text" name="salary_card_details" class="form-input" placeholder="Enter salary card details" value="{{ old('salary_card_details') }}"/>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Document Expiry Information Section -->
                <h6 class="mb-4 text-base font-bold">Document Expiry Details</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="passport_expiry_date">Passport Expiry Date</label>
                        <input id="passport_expiry_date" type="date" name="passport_expiry_date" class="form-input" value="{{ old('passport_expiry_date') }}"/>
                    </div>

                    <div>
                        <label for="visa_expiry_date">Visa Expiry Date</label>
                        <input id="visa_expiry_date" type="date" name="visa_expiry_date" class="form-input" value="{{ old('visa_expiry_date') }}"/>
                    </div>

                    <div>
                        <label for="visit_expiry_date">Visit Permit Expiry Date</label>
                        <input id="visit_expiry_date" type="date" name="visit_expiry_date" class="form-input" value="{{ old('visit_expiry_date') }}"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="eid_expiry_date">EID Expiry Date</label>
                        <input id="eid_expiry_date" type="date" name="eid_expiry_date" class="form-input" value="{{ old('eid_expiry_date') }}"/>
                    </div>

                    <div>
                        <label for="health_insurance_expiry_date">Health Insurance Expiry</label>
                        <input id="health_insurance_expiry_date" type="date" name="health_insurance_expiry_date" class="form-input" value="{{ old('health_insurance_expiry_date') }}"/>
                    </div>

                    <div>
                        <label for="driving_license_expiry_date">Driving License Expiry</label>
                        <input id="driving_license_expiry_date" type="date" name="driving_license_expiry_date" class="form-input" value="{{ old('driving_license_expiry_date') }}"/>
                    </div>
                </div>

                <!-- Remarks Section -->
                <div class="mt-5">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" class="form-textarea" placeholder="Enter any remarks about the employee">{{ old('remarks') }}</textarea>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex items-center justify-end gap-2">
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-danger">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M5 12H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        Add Employee
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

        totalSalary.value = (basicSalary + allowance + fixedSalary).toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', calculateTotal);
</script>
@endpush
@endsection
