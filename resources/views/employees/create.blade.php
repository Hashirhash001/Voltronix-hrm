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
        <form id="employeeForm" action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
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
                        <input id="staff_number" type="text" name="staff_number" class="form-input" placeholder="e.g., EMP001" required/>
                    </div>

                    <div>
                        <label for="employee_name">Employee Name <span class="text-danger">*</span></label>
                        <input id="employee_name" type="text" name="employee_name" class="form-input" placeholder="Enter full name" required/>
                    </div>

                    <div>
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input id="email" type="email" name="email" class="form-input" placeholder="Enter email address" required/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="designation">Designation <span class="text-danger">*</span></label>
                        <input id="designation" type="text" name="designation" class="form-input" placeholder="e.g., Electrician" required>
                    </div>
                    <div>
                        <label for="qualification">Qualification</label>
                        <input id="qualification" type="text" name="qualification" class="form-input" placeholder="e.g., Bachelor of Engineering">
                    </div>
                    <div>
                        <label for="year_of_completion">Year of Completion</label>
                        <input id="year_of_completion" type="number" name="year_of_completion" class="form-input" placeholder="e.g., 2020" min="1950" max="{{ date('Y') }}">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="qualification_document">Qualification Document</label>
                        <input id="qualification_document" type="file" name="qualification_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Upload certificate (Max: 5MB)</p>
                    </div>
                    <div>
                        <label for="pp_status">PP Status</label>
                        <input id="pp_status" type="text" name="pp_status" class="form-input" placeholder="PP status">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="uae_contact">UAE Contact</label>
                        <input id="uae_contact" type="text" name="uae_contact" class="form-input" placeholder="+971..."/>
                    </div>
                    <div>
                        <label for="home_country_contact">Home Country Contact</label>
                        <input id="home_country_contact" type="text" name="home_country_contact" class="form-input" placeholder="+country..."/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="date_of_birth">Date of Birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" class="form-input"/>
                    </div>
                    <div>
                        <label for="current_age">Current Age</label>
                        <input id="current_age" type="number" name="current_age" class="form-input" placeholder="Age"/>
                    </div>
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="vacation">Vacation</option>
                            <option value="terminated">Terminated</option>
                            <option value="resigned">Resigned</option>
                        </select>
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Duty Information -->
                <h6 class="mb-4 text-base font-bold">Duty Information</h6>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="duty_joined_date">Duty Joined Date</label>
                        <input id="duty_joined_date" type="date" name="duty_joined_date" class="form-input">
                    </div>
                    <div>
                        <label for="duty_end_date">Duty End Date</label>
                        <input id="duty_end_date" type="date" name="duty_end_date" class="form-input">
                    </div>
                    <div>
                        <label for="last_vacation_date">Last Vacation Date</label>
                        <input id="last_vacation_date" type="date" name="last_vacation_date" class="form-input">
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Salary Information Section -->
                <h6 class="mb-4 text-base font-bold">Salary Information (AED)</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                    <div>
                        <label for="basic_salary">Basic Salary <span class="text-danger">*</span></label>
                        <input id="basic_salary" type="number" step="0.01" name="basic_salary" class="form-input" placeholder="0.00" value="0" required/>
                    </div>
                    <div>
                        <label for="allowance">Allowance</label>
                        <input id="allowance" type="number" step="0.01" name="allowance" class="form-input" placeholder="0.00" value="0"/>
                    </div>
                    <div>
                        <label for="fixed_salary">Fixed Salary</label>
                        <input id="fixed_salary" type="number" step="0.01" name="fixed_salary" class="form-input" placeholder="0.00" value="0"/>
                    </div>
                    <div>
                        <label for="total_salary">Total Salary</label>
                        <input id="total_salary" type="number" step="0.01" class="form-input bg-gray-100 dark:bg-gray-800" placeholder="0.00" readonly/>
                        <input type="hidden" id="total_salary_input" name="total_salary" value="0"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="recent_increment_amount">Recent Increment Amount</label>
                        <input id="recent_increment_amount" type="number" step="0.01" name="recent_increment_amount" class="form-input" placeholder="0.00" value="0"/>
                    </div>
                    <div>
                        <label for="increment_date">Increment Date</label>
                        <input id="increment_date" type="date" name="increment_date" class="form-input"/>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="salary_card_details">Salary Card Details</label>
                    <input id="salary_card_details" type="text" name="salary_card_details" class="form-input" placeholder="Enter salary card details"/>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Personal Documents Section -->
                <h6 class="mb-4 text-base font-bold">Personal Documents</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="passport_expiry_date">Passport Expiry Date</label>
                        <input id="passport_expiry_date" type="date" name="passport_expiry_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="passport_document">Passport Document</label>
                        <input id="passport_document" type="file" name="passport_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="visa_expiry_date">Visa Expiry Date</label>
                        <input id="visa_expiry_date" type="date" name="visa_expiry_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="visa_document">Visa Document</label>
                        <input id="visa_document" type="file" name="visa_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="visit_expiry_date">Visit Permit Expiry Date</label>
                        <input id="visit_expiry_date" type="date" name="visit_expiry_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="visit_document">Visit Permit Document</label>
                        <input id="visit_document" type="file" name="visit_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="eid_expiry_date">EID Expiry Date</label>
                        <input id="eid_expiry_date" type="date" name="eid_expiry_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="eid_document">EID Document</label>
                        <input id="eid_document" type="file" name="eid_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="health_insurance_expiry_date">Health Insurance Expiry</label>
                        <input id="health_insurance_expiry_date" type="date" name="health_insurance_expiry_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="health_insurance_document">Health Insurance Document</label>
                        <input id="health_insurance_document" type="file" name="health_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="driving_license_expiry_date">Driving License Expiry</label>
                        <input id="driving_license_expiry_date" type="date" name="driving_license_expiry_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="driving_license_document">Driving License Document</label>
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
                        <input id="iloe_insurance_expiry_date" type="date" name="iloe_insurance_expiry_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="iloe_insurance_document">ILOE Insurance Document</label>
                        <input id="iloe_insurance_document" type="file" name="iloe_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="soe_card_renewal_date">SOE Card Renewal Date</label>
                        <input id="soe_card_renewal_date" type="date" name="soe_card_renewal_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="soe_card_document">SOE Card Document</label>
                        <input id="soe_card_document" type="file" name="soe_card_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="dcd_card_renewal_date">DCD Card Renewal Date</label>
                        <input id="dcd_card_renewal_date" type="date" name="dcd_card_renewal_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="dcd_card_document">DCD Card Document</label>
                        <input id="dcd_card_document" type="file" name="dcd_card_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="workman_insurance_expiry_date">Workman Insurance Expiry</label>
                        <input id="workman_insurance_expiry_date" type="date" name="workman_insurance_expiry_date" class="form-input"/>
                    </div>
                    <div>
                        <label for="workman_insurance_document">Workman Insurance Document</label>
                        <input id="workman_insurance_document" type="file" name="workman_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Remarks Section -->
                <h6 class="mb-4 text-base font-bold">Additional Information</h6>

                <div class="mt-5">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" rows="3" class="form-textarea" placeholder="Enter any remarks about the employee"></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex items-center justify-end gap-2">
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-danger">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-primary gap-2">
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
            submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Adding...';

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
