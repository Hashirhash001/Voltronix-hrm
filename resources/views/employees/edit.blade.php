@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li><a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a></li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <a href="{{ route('employees.index') }}" class="text-primary hover:underline">Employees</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Edit</span>
        </li>
    </ul>

    <div class="pt-5">
        <form id="employeeForm" action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="panel">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Edit Employee</h5>
                </div>

                {{-- Personal Information --}}
                <h6 class="mb-4 text-base font-bold">Personal Information</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="staff_number">Staff Number <span class="text-danger">*</span></label>
                        <input id="staff_number" type="text" name="staff_number" class="form-input" required
                               value="{{ old('staff_number', $employee->staff_number) }}">
                    </div>
                    <div>
                        <label for="employee_name">Employee Name <span class="text-danger">*</span></label>
                        <input id="employee_name" type="text" name="employee_name" class="form-input" required
                               value="{{ old('employee_name', $employee->employee_name) }}">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="text" class="form-input bg-gray-100 dark:bg-gray-800" readonly
                               value="{{ $employee->user->email ?? 'N/A' }}">
                        <p class="text-xs text-gray-500 mt-1">Email is linked to the user account.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="entity_id">Entity</label>
                        <select id="entity_id" name="entity_id" class="form-select">
                            <option value="">Select Entity (Optional)</option>
                            @foreach($entities as $entity)
                                <option value="{{ $entity->id }}"
                                    @selected(old('entity_id', $employee->entity_id) == $entity->id)>
                                    {{ $entity->entity_name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Assign employee to an entity</p>
                    </div>
                    <div>
                        <label for="employee_image">Employee Image</label>
                        <input id="employee_image" type="file" name="employee_image" class="form-input" accept="image/*">

                        @if($employee->employee_image)
                            <div class="mt-3">
                                <img src="{{ asset('storage/' . $employee->employee_image) }}"
                                     alt="Employee Image"
                                     class="h-20 w-20 rounded object-cover border">
                            </div>
                        @endif
                    </div>
                    <div>
                        <label for="designation">Designation <span class="text-danger">*</span></label>
                        <input id="designation" type="text" name="designation" class="form-input" required
                               value="{{ old('designation', $employee->designation) }}">
                    </div>

                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="qualification">Qualification</label>
                        <input id="qualification" type="text" name="qualification" class="form-input"
                               value="{{ old('qualification', $employee->qualification) }}">
                    </div>
                    <div>
                        <label for="year_of_completion">Year of Completion</label>
                        <input id="year_of_completion" type="number" name="year_of_completion" class="form-input"
                               min="1950" max="{{ date('Y') }}"
                               value="{{ old('year_of_completion', $employee->year_of_completion) }}">
                    </div>
                    <div>
                        <label for="qualification_document">Qualification Document</label>
                        <input id="qualification_document" type="file" name="qualification_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->qualification_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->qualification_document) }}">View current document</a>
                        @endif
                    </div>

                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="pp_status">PP Status</label>
                        <input id="pp_status" type="text" name="pp_status" class="form-input"
                               value="{{ old('pp_status', $employee->pp_status) }}">
                    </div>
                    <div>
                        <label for="uae_contact">UAE Contact</label>
                        <input id="uae_contact" type="text" name="uae_contact" class="form-input"
                               value="{{ old('uae_contact', $employee->uae_contact) }}">
                    </div>
                    <div>
                        <label for="home_country_contact">Home Country Contact</label>
                        <input id="home_country_contact" type="text" name="home_country_contact" class="form-input"
                               value="{{ old('home_country_contact', $employee->home_country_contact) }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="date_of_birth">Date of Birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" class="form-input"
                               value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="current_age">Current Age</label>
                        <input id="current_age" type="number" name="current_age" class="form-input"
                               value="{{ old('current_age', $employee->current_age) }}">
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="active" @selected(old('status', $employee->status)==='active')>Active</option>
                            <option value="inactive" @selected(old('status', $employee->status)==='inactive')>Inactive</option>
                            <option value="vacation" @selected(old('status', $employee->status)==='vacation')>Vacation</option>
                            <option value="terminated" @selected(old('status', $employee->status)==='terminated')>Terminated</option>
                            <option value="resigned" @selected(old('status', $employee->status)==='resigned')>Resigned</option>
                        </select>
                    </div>
                </div>

                {{-- Status-based fields --}}
                <div id="terminated-fields" class="mt-5 hidden">
                    <label for="termination_date">Termination Date <span class="text-danger">*</span></label>
                    <input id="termination_date" type="date" name="termination_date" class="form-input"
                           value="{{ old('termination_date', $employee->termination_date?->format('Y-m-d')) }}">
                </div>

                <div id="resigned-fields" class="mt-5 hidden">
                    <label for="resignation_date">Resignation Date <span class="text-danger">*</span></label>
                    <input id="resignation_date" type="date" name="resignation_date" class="form-input"
                           value="{{ old('resignation_date', $employee->resignation_date?->format('Y-m-d')) }}">
                </div>

                {{-- INFO: Vacation management available on employee profile --}}
                <div id="vacation-info" class="mt-5 hidden">
                    <div class="bg-info/10 border border-info rounded p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 flex-1">
                                <svg class="h-5 w-5 text-info flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-info">Vacation Management</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        To add or manage vacation periods, please use the "Add Vacation" button on the employee's profile page.
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-sm btn-info whitespace-nowrap">
                                View Profile
                            </a>
                        </div>
                    </div>
                </div>


                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                {{-- Duty Information --}}
                <h6 class="mb-4 text-base font-bold">Duty Information</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="duty_joined_date">Duty Joined Date</label>
                        <input id="duty_joined_date" type="date" name="duty_joined_date" class="form-input"
                               value="{{ old('duty_joined_date', $employee->duty_joined_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="duty_end_date">Duty End Date</label>
                        <input id="duty_end_date" type="date" name="duty_end_date" class="form-input"
                               value="{{ old('duty_end_date', $employee->duty_end_date?->format('Y-m-d')) }}">
                    </div>
                    {{-- Removed last_vacation_date --}}
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                {{-- Salary Information --}}
                <h6 class="mb-4 text-base font-bold">Salary Information (AED)</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                    <div>
                        <label for="basic_salary">Basic Salary <span class="text-danger">*</span></label>
                        <input id="basic_salary" type="number" step="0.01" name="basic_salary" class="form-input" required
                               value="{{ old('basic_salary', $employee->basic_salary ?? 0) }}">
                    </div>
                    <div>
                        <label for="allowance">Allowance</label>
                        <input id="allowance" type="number" step="0.01" name="allowance" class="form-input"
                               value="{{ old('allowance', $employee->allowance ?? 0) }}">
                    </div>
                    <div>
                        <label for="fixed_salary">Fixed Salary</label>
                        <input id="fixed_salary" type="number" step="0.01" name="fixed_salary" class="form-input"
                               value="{{ old('fixed_salary', $employee->fixed_salary ?? 0) }}">
                    </div>
                    <div>
                        <label for="total_salary">Total Salary</label>
                        <input id="total_salary" type="number" step="0.01" class="form-input bg-gray-100 dark:bg-gray-800" readonly>
                        <input type="hidden" id="total_salary_input" name="total_salary" value="{{ old('total_salary', $employee->total_salary ?? 0) }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="recent_increment_amount">Recent Increment Amount</label>
                        <input id="recent_increment_amount" type="number" step="0.01" name="recent_increment_amount" class="form-input"
                               value="{{ old('recent_increment_amount', $employee->recent_increment_amount ?? 0) }}">
                    </div>
                    <div>
                        <label for="increment_date">Increment Date</label>
                        <input id="increment_date" type="date" name="increment_date" class="form-input"
                               value="{{ old('increment_date', $employee->increment_date?->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="mt-5">
                    <label for="salary_card_details">Salary Card Details</label>
                    <input id="salary_card_details" type="text" name="salary_card_details" class="form-input"
                           value="{{ old('salary_card_details', $employee->salary_card_details) }}">
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                {{-- Documents (keep same fields as create; show "View current") --}}
                <h6 class="mb-4 text-base font-bold">Personal Documents</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="passport_expiry_date">Passport Expiry Date</label>
                        <input id="passport_expiry_date" type="date" name="passport_expiry_date" class="form-input"
                               value="{{ old('passport_expiry_date', $employee->passport_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="passport_document">Passport Document</label>
                        <input id="passport_document" type="file" name="passport_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->passport_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->passport_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="visa_expiry_date">Visa Expiry Date</label>
                        <input id="visa_expiry_date" type="date" name="visa_expiry_date" class="form-input"
                               value="{{ old('visa_expiry_date', $employee->visa_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="visa_document">Visa Document</label>
                        <input id="visa_document" type="file" name="visa_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->visa_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->visa_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="visit_expiry_date">Visit Permit Expiry Date</label>
                        <input id="visit_expiry_date" type="date" name="visit_expiry_date" class="form-input"
                               value="{{ old('visit_expiry_date', $employee->visit_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="visit_document">Visit Permit Document</label>
                        <input id="visit_document" type="file" name="visit_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->visit_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->visit_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="eid_expiry_date">EID Expiry Date</label>
                        <input id="eid_expiry_date" type="date" name="eid_expiry_date" class="form-input"
                               value="{{ old('eid_expiry_date', $employee->eid_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="eid_document">EID Document</label>
                        <input id="eid_document" type="file" name="eid_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->eid_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->eid_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="health_insurance_expiry_date">Health Insurance Expiry</label>
                        <input id="health_insurance_expiry_date" type="date" name="health_insurance_expiry_date" class="form-input"
                               value="{{ old('health_insurance_expiry_date', $employee->health_insurance_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="health_insurance_document">Health Insurance Document</label>
                        <input id="health_insurance_document" type="file" name="health_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->health_insurance_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->health_insurance_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="driving_license_expiry_date">Driving License Expiry</label>
                        <input id="driving_license_expiry_date" type="date" name="driving_license_expiry_date" class="form-input"
                               value="{{ old('driving_license_expiry_date', $employee->driving_license_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="driving_license_document">Driving License Document</label>
                        <input id="driving_license_document" type="file" name="driving_license_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->driving_license_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->driving_license_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <h6 class="mb-4 text-base font-bold">Company Insurance Documents</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="iloe_insurance_expiry_date">ILOE Insurance Expiry</label>
                        <input id="iloe_insurance_expiry_date" type="date" name="iloe_insurance_expiry_date" class="form-input"
                               value="{{ old('iloe_insurance_expiry_date', $employee->iloe_insurance_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="iloe_insurance_document">ILOE Insurance Document</label>
                        <input id="iloe_insurance_document" type="file" name="iloe_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->iloe_insurance_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->iloe_insurance_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="soe_card_renewal_date">SOE Card Renewal Date</label>
                        <input id="soe_card_renewal_date" type="date" name="soe_card_renewal_date" class="form-input"
                               value="{{ old('soe_card_renewal_date', $employee->soe_card_renewal_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="soe_card_document">SOE Card Document</label>
                        <input id="soe_card_document" type="file" name="soe_card_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->soe_card_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->soe_card_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="dcd_card_renewal_date">DCD Card Renewal Date</label>
                        <input id="dcd_card_renewal_date" type="date" name="dcd_card_renewal_date" class="form-input"
                               value="{{ old('dcd_card_renewal_date', $employee->dcd_card_renewal_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="dcd_card_document">DCD Card Document</label>
                        <input id="dcd_card_document" type="file" name="dcd_card_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->dcd_card_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->dcd_card_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="workman_insurance_expiry_date">Workman Insurance Expiry</label>
                        <input id="workman_insurance_expiry_date" type="date" name="workman_insurance_expiry_date" class="form-input"
                               value="{{ old('workman_insurance_expiry_date', $employee->workman_insurance_expiry_date?->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label for="workman_insurance_document">Workman Insurance Document</label>
                        <input id="workman_insurance_document" type="file" name="workman_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        @if($employee->workman_insurance_document)
                            <a class="text-primary hover:underline text-sm mt-2 inline-block" target="_blank"
                               href="{{ asset('storage/' . $employee->workman_insurance_document) }}">View current document</a>
                        @endif
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <h6 class="mb-4 text-base font-bold">Additional Information</h6>
                <div class="mt-5">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" class="form-textarea">{{ old('remarks', $employee->remarks) }}</textarea>
                </div>

                <div class="mt-8 flex items-center justify-end gap-2">
                    <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-outline-secondary">Back</a>
                    <button type="submit" id="submitBtn" class="btn btn-primary">Update Employee</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function calculateTotal() {
    const basicSalary = parseFloat(document.getElementById('basic_salary')?.value || 0);
    const allowance   = parseFloat(document.getElementById('allowance')?.value || 0);
    const fixedSalary = parseFloat(document.getElementById('fixed_salary')?.value || 0);

    const total = (basicSalary + allowance + fixedSalary).toFixed(2);

    const totalSalary = document.getElementById('total_salary');
    const totalSalaryInput = document.getElementById('total_salary_input');

    if (totalSalary) totalSalary.value = total;
    if (totalSalaryInput) totalSalaryInput.value = total;
}

function toggleStatusFields() {
    const status = document.getElementById('status')?.value;

    const terminatedBox = document.getElementById('terminated-fields');
    const resignedBox   = document.getElementById('resigned-fields');
    const vacationInfo  = document.getElementById('vacation-info');

    const terminationInput = document.getElementById('termination_date');
    const resignationInput = document.getElementById('resignation_date');

    if (terminatedBox) terminatedBox.classList.toggle('hidden', status !== 'terminated');
    if (resignedBox)   resignedBox.classList.toggle('hidden', status !== 'resigned');
    if (vacationInfo)  vacationInfo.classList.toggle('hidden', status !== 'vacation');

    // Clear exit dates if switching away
    if (status !== 'terminated' && terminationInput) terminationInput.value = '';
    if (status !== 'resigned' && resignationInput) resignationInput.value = '';
}

async function parseJsonOrThrow(response) {
    const contentType = response.headers.get('content-type') || '';
    const text = await response.text();

    if (contentType.includes('application/json')) {
        try {
            return JSON.parse(text || '{}');
        } catch (e) {
            console.error('JSON Parse Error:', e);
            throw new Error('Invalid JSON response from server');
        }
    }

    throw new Error(text || ('Non-JSON response (HTTP ' + response.status + ')'));
}

document.addEventListener('DOMContentLoaded', function () {
    calculateTotal();

    ['basic_salary','allowance','fixed_salary'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', calculateTotal);
            el.addEventListener('input', calculateTotal);
        }
    });

    const statusEl = document.getElementById('status');
    if (statusEl) {
        statusEl.addEventListener('change', toggleStatusFields);
        toggleStatusFields();
    }

    const form = document.getElementById('employeeForm');
    const submitBtn = document.getElementById('submitBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // AJAX vacation delete
    document.querySelectorAll('.vacation-delete-form').forEach(function (delForm) {
        delForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (submitBtn && submitBtn.disabled) return;

            Swal.fire({
                title: 'Are you sure?',
                text: "This vacation record will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                try {
                    const res = await fetch(delForm.action, {
                        method: 'POST',
                        body: new FormData(delForm),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                        }
                    });

                    const data = await parseJsonOrThrow(res);

                    if (res.ok && data.success) {
                        await Swal.fire({
                            icon:'success',
                            title:'Deleted',
                            text: data.message || 'Vacation deleted successfully.',
                            confirmButtonColor: '#3085d6',
                            timer: 2000
                        });
                        window.location.reload();
                    } else {
                        const msg = data.errors
                            ? Object.values(data.errors).flat().join("\n")
                            : (data.message || 'Delete failed.');
                        Swal.fire({
                            icon:'error',
                            title:'Delete Failed',
                            text: msg,
                            confirmButtonColor: '#d33'
                        });
                    }
                } catch (err) {
                    console.error('Delete error:', err);
                    Swal.fire({
                        icon:'error',
                        title:'Error!',
                        text: err.message || 'Failed to delete vacation.',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });
    });

    if (form && submitBtn) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            calculateTotal();

            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="animate-spin inline-block mr-2">⟳</span>Updating...';

            try {
                const formData = new FormData(form);

                console.log('Submitting employee update...');
                if (status === 'vacation') {
                    console.log('Adding new vacation:', {
                        start: formData.get('vacation_start_date'),
                        end: formData.get('vacation_end_date'),
                        remarks: formData.get('vacation_remarks')
                    });
                }

                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                    },
                    redirect: 'manual'
                });

                console.log('Response status:', res.status);

                // Handle redirect responses
                if (res.type === 'opaqueredirect' || res.status === 302) {
                    console.error('Received redirect instead of JSON');
                    await Swal.fire({
                        icon: 'error',
                        title: 'Session Expired',
                        text: 'Your session may have expired. Please refresh and try again.',
                        confirmButtonColor: '#d33',
                    });
                    window.location.reload();
                    return;
                }

                const data = await parseJsonOrThrow(res);
                console.log('Response data:', data);

                if (res.ok && data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message || 'Employee updated successfully!',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });
                    window.location.href = data.redirect || window.location.href;
                    return;
                }

                // Handle errors
                let errorMsg = data.message || 'Update failed.';

                if (data.errors && typeof data.errors === 'object') {
                    const errorList = [];
                    for (const [field, messages] of Object.entries(data.errors)) {
                        if (Array.isArray(messages)) {
                            errorList.push(...messages);
                        } else {
                            errorList.push(messages);
                        }
                    }
                    if (errorList.length > 0) {
                        errorMsg = errorList.join('\n');
                    }
                }

                console.error('Update failed:', errorMsg);

                await Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    html: errorMsg.replace(/\n/g, '<br>'),
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'OK'
                });

            } catch (error) {
                console.error('Update error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.message || 'An unexpected error occurred. Please try again.',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'OK'
                });
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Clear vacation fields on page load to ensure fresh start
    const vacStartInput = document.getElementById('vacation_start_date');
    const vacEndInput = document.getElementById('vacation_end_date');
    const vacRemarksInput = document.getElementById('vacation_remarks');

    if (vacStartInput) vacStartInput.value = '';
    if (vacEndInput) vacEndInput.value = '';
    if (vacRemarksInput) vacRemarksInput.value = '';
});
</script>
@endpush
