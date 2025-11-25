{{-- resources/views/employees/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div x-data="employeeFilters">
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Employees</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-semibold dark:text-white-light">Employees List</h2>
            <div class="flex gap-2">
                <a href="{{ route('employees.import') }}" class="btn btn-outline-info gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5V19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19 12L12 19L5 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Import
                </a>

                <a href="{{ route('employees.export') }}" class="btn btn-outline-primary gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 19V5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5 12L12 5L19 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Export
                </a>

                <!-- Add Employee Button -->
                <a href="{{ route('employees.create') }}" class="btn btn-primary gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5V19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M5 12H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Add Employee
                </a>
            </div>
        </div>

        <!-- Filters Panel -->
        <div class="panel mb-5">
            <div class="mb-4 flex items-center justify-between">
                <h6 class="font-semibold">Filters</h6>
                <button @click="resetFilters()" class="text-xs text-primary hover:underline">Reset Filters</button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                <!-- Search Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Search</label>
                    <input
                        type="text"
                        class="form-input"
                        placeholder="Name, email, designation..."
                        x-model="filters.search"
                        @input="debounceFilter"
                    />
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Status</label>
                    <select class="form-select" x-model="filters.status" @change="applyFilters">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="vacation">Vacation</option>
                        <option value="terminated">Terminated</option>
                    </select>
                </div>

                <!-- Designation Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Designation</label>
                    <select class="form-select" x-model="filters.designation" @change="applyFilters">
                        <option value="">All Designations</option>
                        <option value="Electrician">Electrician</option>
                        <option value="Plumber">Plumber</option>
                        <option value="HVAC Technician">HVAC Technician</option>
                        <option value="Civil Engineer">Civil Engineer</option>
                        <option value="Project Manager">Project Manager</option>
                        <option value="Safety Officer">Safety Officer</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Foreman">Foreman</option>
                    </select>
                </div>

                <!-- Salary Range Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Min Salary (AED)</label>
                    <input
                        type="number"
                        class="form-input"
                        placeholder="Min salary"
                        x-model="filters.minSalary"
                        @input="applyFilters"
                    />
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <template x-if="isLoading">
            <div class="panel text-center py-8">
                <svg class="animate-spin h-8 w-8 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-gray-600 dark:text-gray-400">Loading employees...</p>
            </div>
        </template>

        <!-- Employees Table -->
        <template x-if="!isLoading">
            <div class="panel overflow-hidden border-0 p-0">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Staff Number</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Salary</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="employees.length > 0">
                                <template x-for="employee in employees" :key="employee.id">
                                    <tr>
                                        <td class="font-semibold" x-text="employee.staff_number"></td>
                                        <td x-text="employee.employee_name"></td>
                                        <td x-text="employee.designation"></td>
                                        <td class="text-xs text-white-dark" x-text="employee.user.email"></td>
                                        <td>
                                            <span class="badge" :class="getBadgeClass(employee.status)" x-text="capitalizeText(employee.status)"></span>
                                        </td>
                                        <td class="font-semibold" x-text="formatAED(employee.total_salary)"></td>
                                        <td class="text-center">
                                            <div class="flex gap-1 justify-center">
                                                <a :href="`{{ route('employees.show', '') }}/${employee.id}`" class="btn btn-sm btn-outline-info" title="View">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                                    </svg>
                                                </a>
                                                <a :href="`/employees/${employee.id}/edit`" class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M11 4H4C2.89543 4 2 4.89543 2 6V20C2 21.1046 2.89543 22 4 22H18C19.1046 22 20 21.1046 20 20V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <path d="M18.5 2.5L21.5 5.5M22 4L16 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" @click="deleteEmployee(employee.id, employee.employee_name)" title="Delete">
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M4 7H20M10 11V17M14 11V17M5 7L6 19C6 20.1046 6.89543 21 8 21H16C17.1046 21 18 20.1046 18 19L19 7M9 7V4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <template x-if="employees.length === 0">
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-white-dark">
                                        No employees found
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('employeeFilters', () => ({
            employees: [],
            isLoading: false,
            filters: {
                search: '',
                status: '',
                designation: '',
                minSalary: ''
            },
            filterTimeout: null,

            init() {
                this.loadEmployees();
            },

            debounceFilter() {
                clearTimeout(this.filterTimeout);
                this.filterTimeout = setTimeout(() => {
                    this.applyFilters();
                }, 500);
            },

            async applyFilters() {
                this.isLoading = true;

                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.designation) params.append('designation', this.filters.designation);
                if (this.filters.minSalary) params.append('min_salary', this.filters.minSalary);

                try {
                    const response = await fetch(`{{ route('employees.index') }}?${params.toString()}&ajax=true`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();
                    this.employees = data.employees;
                } catch (error) {
                    console.error('Error loading employees:', error);
                } finally {
                    this.isLoading = false;
                }
            },

            async loadEmployees() {
                await this.applyFilters();
            },

            resetFilters() {
                this.filters = {
                    search: '',
                    status: '',
                    designation: '',
                    minSalary: ''
                };
                this.loadEmployees();
            },

            getBadgeClass(status) {
                const classes = {
                    'active': 'bg-success',
                    'inactive': 'bg-gray-400',
                    'vacation': 'bg-warning',
                    'terminated': 'bg-danger'
                };
                return classes[status] || 'bg-gray-400';
            },

            capitalizeText(text) {
                return text.charAt(0).toUpperCase() + text.slice(1);
            },

            formatAED(amount) {
                return new Intl.NumberFormat('en-AE', {
                    style: 'currency',
                    currency: 'AED',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(amount);
            },

            deleteEmployee(id, name) {
                Swal.fire({
                    title: 'Delete Employee?',
                    text: `Are you sure you want to delete ${name}? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/employees/${id}`;
                        form.innerHTML = `
                            @csrf
                            @method('DELETE')
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
        }));
    });
</script>
@endpush
@endsection
