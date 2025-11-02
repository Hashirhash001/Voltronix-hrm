{{-- resources/views/employees/import.blade.php --}}
@extends('layouts.app')

@section('title', 'Import Employees')

@section('content')
<div x-data="employeeImport">
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <a href="{{ route('employees.index') }}" class="text-primary hover:underline">Employees</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Import</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Upload Section -->
            <div class="panel">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Upload Employee File</h5>
                    <a href="{{ route('employees.download-template') }}" class="btn btn-sm btn-outline-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3V13M12 13L7 8M12 13L17 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17L2.621 19.485C2.729 20.068 3.210 20.499 3.799 20.499H20.201C20.790 20.499 21.271 20.068 21.379 19.485L22 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Download Template
                    </a>
                </div>

                <div class="space-y-4">
                    <!-- File Input -->
                    <div>
                        <label class="mb-2 block">Select File <span class="text-danger">*</span></label>
                        <div class="flex items-center gap-3">
                            <input
                                type="file"
                                id="fileInput"
                                accept=".xlsx,.xls,.csv"
                                class="hidden"
                                @change="handleFileSelect"
                            />
                            <button type="button" class="btn btn-outline-primary" @click="document.getElementById('fileInput').click()">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 9C18 10.933 16.433 12.5 14.5 12.5M14.5 12.5C12.567 12.5 11 10.933 11 9M14.5 12.5V22M7 13H21M3.2 5H22.8C23.9201 5 24 5.35 24 6.2V17.8C24 18.65 23.9201 19 22.8 19H3.2C2.07989 19 2 18.65 2 17.8V6.2C2 5.35 2.07989 5 3.2 5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                                Choose File
                            </button>
                            <span class="text-sm text-gray-600 dark:text-gray-400" x-text="fileName || 'No file selected'"></span>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Supported formats: XLSX, XLS, CSV</p>
                    </div>

                    <!-- Preview Button -->
                    <button
                        type="button"
                        class="btn btn-primary w-full"
                        @click="previewFile()"
                        :disabled="!fileName || isLoading"
                        x-show="fileName && !showPreview"
                    >
                        <span x-show="!isLoading">Preview Data</span>
                        <span x-show="isLoading" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="panel" x-show="showSummary">
                <div class="mb-5">
                    <h5 class="text-lg font-semibold dark:text-white-light">Import Summary</h5>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Records</p>
                            <p class="text-2xl font-bold text-primary" x-text="summary.total"></p>
                        </div>
                        <svg class="h-8 w-8 text-primary opacity-20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                        </svg>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Valid Records</p>
                            <p class="text-2xl font-bold text-success" x-text="summary.valid"></p>
                        </div>
                        <svg class="h-8 w-8 text-success opacity-20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
                        </svg>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg" x-show="summary.invalid > 0">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Invalid Records</p>
                            <p class="text-2xl font-bold text-danger" x-text="summary.invalid"></p>
                        </div>
                        <svg class="h-8 w-8 text-danger opacity-20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"></path>
                        </svg>
                    </div>

                    <button
                        type="button"
                        class="btn btn-success w-full"
                        @click="importData()"
                        :disabled="summary.valid === 0 || isImporting"
                        x-show="showPreview && summary.valid > 0"
                    >
                        <span x-show="!isImporting">Import All Valid Records</span>
                        <span x-show="isImporting" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importing...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Table -->
        <template x-if="showPreview">
            <div class="mt-6 space-y-4">
                <!-- Valid Records -->
                <template x-if="preview.length > 0">
                    <div class="panel">
                        <div class="mb-4">
                            <h6 class="font-semibold">Preview (First 5 Records)</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table-striped">
                                <thead>
                                    <tr>
                                        <th>Staff #</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Designation</th>
                                        <th>Salary (AED)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="emp in preview" :key="emp.staff_number">
                                        <tr>
                                            <td x-text="emp.staff_number"></td>
                                            <td x-text="emp.employee_name"></td>
                                            <td x-text="emp.email"></td>
                                            <td x-text="emp.designation"></td>
                                            <td class="font-semibold" x-text="formatCurrency(emp.basic_salary)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                <!-- Error Records -->
                <template x-if="errors.length > 0">
                    <div class="panel border border-danger/30">
                        <div class="mb-4">
                            <h6 class="font-semibold text-danger">Records with Errors</h6>
                        </div>
                        <div class="space-y-3">
                            <template x-for="error in errors" :key="error.row">
                                <div class="p-3 bg-danger/10 rounded">
                                    <p class="font-semibold text-sm mb-2">Row <span x-text="error.row"></span>: <span x-text="error.data.employee_name || 'N/A'"></span></p>
                                    <ul class="list-disc list-inside text-xs text-danger space-y-1">
                                        <template x-for="err in error.errors">
                                            <li x-text="err"></li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('employeeImport', () => ({
            fileName: '',
            fileInput: null,
            showPreview: false,
            showSummary: false,
            isLoading: false,
            isImporting: false,
            summary: { total: 0, valid: 0, invalid: 0 },
            employees: [],
            errors: [],
            preview: [],

            handleFileSelect(e) {
                const file = e.target.files[0];
                if (file) {
                    this.fileName = file.name;
                }
            },

            async previewFile() {
                const fileInput = document.getElementById('fileInput');
                if (!fileInput.files[0]) {
                    alert('Please select a file');
                    return;
                }

                this.isLoading = true;
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);

                try {
                    const response = await fetch('{{ route("employees.preview") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.summary = {
                            total: data.total,
                            valid: data.valid,
                            invalid: data.invalid
                        };
                        this.employees = data.employees;
                        this.errors = data.errors;
                        this.preview = data.preview;
                        this.showPreview = true;
                        this.showSummary = true;
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error processing file');
                } finally {
                    this.isLoading = false;
                }
            },

            async importData() {
                if (this.employees.length === 0) {
                    alert('No valid employees to import');
                    return;
                }

                if (!confirm(`Import ${this.summary.valid} employees?`)) {
                    return;
                }

                this.isImporting = true;

                try {
                    const response = await fetch('{{ route("employees.bulk-import") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            employees: JSON.stringify(this.employees)
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            window.location.href = '{{ route("employees.index") }}';
                        });
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error importing employees');
                } finally {
                    this.isImporting = false;
                }
            },

            formatCurrency(amount) {
                return new Intl.NumberFormat('en-AE', {
                    style: 'currency',
                    currency: 'AED'
                }).format(amount);
            }
        }));
    });
</script>
@endpush
@endsection
