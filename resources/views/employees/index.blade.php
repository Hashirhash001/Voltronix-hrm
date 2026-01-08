@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div>
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
                <button id="reset-filters" class="text-xs text-primary hover:underline">Reset Filters</button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <!-- Search Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Search</label>
                    <input
                        type="text"
                        id="filter-search"
                        class="form-input"
                        placeholder="Name, email, designation..."
                    />
                </div>

                <!-- ✅ Entity Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Entity</label>
                    <select class="form-select" id="filter-entity">
                        <option value="">All Entities</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Status</label>
                    <select class="form-select" id="filter-status">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="vacation">Vacation</option>
                        <option value="terminated">Terminated</option>
                        <option value="resigned">Resigned</option>
                    </select>
                </div>

                <!-- Designation Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Designation</label>
                    <select class="form-select" id="filter-designation">
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
                        id="filter-salary"
                        class="form-input"
                        placeholder="Min salary"
                    />
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loading-indicator" class="panel text-center py-8" style="display: none;">
            <svg class="animate-spin h-8 w-8 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-3 text-gray-600 dark:text-gray-400">Loading employees...</p>
        </div>

        <!-- Employees Table -->
        <div id="employees-table" class="panel overflow-hidden border-0 p-0">
            <div class="table-responsive">
                <table class="table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Staff Number</th>
                            <th>Name</th>
                            <th>Entity</th>
                            <th>Designation</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Salary</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="employees-tbody">
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="panel mt-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div id="pagination-info" class="text-sm text-white-dark"></div>
                    <div id="pagination-controls" class="flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    let filterTimeout = null;
    let currentPage = 1;

    loadEmployees(1);

    $('#filter-search').on('input', function () {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => loadEmployees(1), 500);
    });

    // ✅ Added entity filter listener
    $('#filter-status, #filter-designation, #filter-salary, #filter-entity').on('change', function () {
        loadEmployees(1);
    });

    $('#reset-filters').on('click', function () {
        $('#filter-search').val('');
        $('#filter-status').val('');
        $('#filter-designation').val('');
        $('#filter-salary').val('');
        $('#filter-entity').val(''); // ✅ Reset entity filter
        loadEmployees(1);
    });

    $(document).on('click', '.pagination-btn', function () {
        const page = parseInt($(this).data('page'), 10);
        if (!isNaN(page)) loadEmployees(page);
    });

    function loadEmployees(page = 1) {
        currentPage = page;

        const params = {
            search: $('#filter-search').val(),
            status: $('#filter-status').val(),
            designation: $('#filter-designation').val(),
            min_salary: $('#filter-salary').val(),
            entity_id: $('#filter-entity').val(), // ✅ Added entity filter
            page: currentPage,
            per_page: 10,
            ajax: true
        };

        $('#loading-indicator').show();
        $('#employees-table').hide();

        $.ajax({
            url: '{{ route("employees.index") }}',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function (response) {
                renderEmployees(response.employees || []);
                renderPagination(response.pagination || null);
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load employees. Please try again.' });
            },
            complete: function () {
                $('#loading-indicator').hide();
                $('#employees-table').show();
            }
        });
    }

    function renderEmployees(employees) {
        const tbody = $('#employees-tbody');
        tbody.empty();

        if (!employees.length) {
            tbody.append(`
                <tr>
                    <td colspan="9" class="text-center py-8 text-white-dark">No employees found</td>
                </tr>
            `);
            return;
        }

        employees.forEach(employee => {
            const email = employee.user ? employee.user.email : 'N/A';
            const entityName = employee.entity_name
                ? `<span class="text-sm">${employee.entity_name}</span>`
                : `<span class="text-xs text-gray-400 italic">Not assigned</span>`; // ✅ Show entity

            const imgHtml = employee.employee_image
                ? `<img src="/storage/${employee.employee_image}" class="h-9 w-9 rounded-full object-cover ring-1 ring-white-light" alt="Employee">`
                : `<div class="h-9 w-9 rounded-full bg-white-light/60 flex items-center justify-center ring-1 ring-white-light">
                        <svg class="h-5 w-5 text-white-dark" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5Z" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M20 21c0-4.418-3.582-8-8-8s-8 3.582-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                   </div>`;

            const row = `
                <tr>
                    <td>${imgHtml}</td>
                    <td class="font-semibold">${employee.staff_number}</td>
                    <td>${employee.employee_name}</td>
                    <td>${entityName}</td>
                    <td>${employee.designation}</td>
                    <td class="text-xs text-white-dark">${email}</td>
                    <td>
                        <span class="badge ${getBadgeClass(employee.status)}">
                            ${capitalizeText(employee.status)}
                        </span>
                    </td>
                    <td class="font-semibold">${formatAED(employee.total_salary)}</td>
                    <td class="text-center">
                        <div class="flex gap-1 justify-center">
                            <a href="{{ route('employees.show', '') }}/${employee.id}" class="btn btn-sm btn-outline-info" title="View">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                </svg>
                            </a>
                            <a href="/employees/${employee.id}/edit" class="btn btn-sm btn-outline-warning" title="Edit">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M11 4H4C2.89543 4 2 4.89543 2 6V20C2 21.1046 2.89543 22 4 22H18C19.1046 22 20 21.1046 20 20V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18.5 2.5L21.5 5.5M22 4L16 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-employee" data-id="${employee.id}" data-name="${employee.employee_name}" title="Delete">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 7H20M10 11V17M14 11V17M5 7L6 19C6 20.1046 6.89543 21 8 21H16C17.1046 21 18 20.1046 18 19L19 7M9 7V4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function renderPagination(p) {
        const info = $('#pagination-info');
        const controls = $('#pagination-controls');

        info.empty();
        controls.empty();

        if (!p) return;

        info.text(`Showing page ${p.current_page} of ${p.last_page} • Total ${p.total}`);

        controls.append(`
            <button class="btn btn-sm btn-outline-primary pagination-btn" data-page="${Math.max(1, p.current_page - 1)}"
                ${p.current_page <= 1 ? 'disabled' : ''}>
                Prev
            </button>
        `);

        const start = Math.max(1, p.current_page - 2);
        const end = Math.min(p.last_page, p.current_page + 2);

        for (let i = start; i <= end; i++) {
            controls.append(`
                <button class="btn btn-sm ${i === p.current_page ? 'btn-primary' : 'btn-outline-primary'} pagination-btn"
                        data-page="${i}">
                    ${i}
                </button>
            `);
        }

        controls.append(`
            <button class="btn btn-sm btn-outline-primary pagination-btn" data-page="${Math.min(p.last_page, p.current_page + 1)}"
                ${p.current_page >= p.last_page ? 'disabled' : ''}>
                Next
            </button>
        `);
    }

    $(document).on('click', '.delete-employee', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

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
                const form = $('<form>', {
                    method: 'POST',
                    action: `/employees/${id}`
                }).append(
                    '@csrf',
                    '@method("DELETE")'
                );
                $('body').append(form);
                form.submit();
            }
        });
    });

    function getBadgeClass(status) {
        const classes = {
            'active': 'bg-success',
            'inactive': 'bg-gray-400',
            'vacation': 'bg-warning',
            'terminated': 'bg-danger',
            'resigned': 'bg-danger'
        };
        return classes[status] || 'bg-gray-400';
    }

    function capitalizeText(text) {
        return text.charAt(0).toUpperCase() + text.slice(1);
    }

    function formatAED(amount) {
        return new Intl.NumberFormat('en-AE', {
            style: 'currency',
            currency: 'AED',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }
});
</script>
@endpush
@endsection
