@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
<div id="attendance-page">
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Attendance</span>
        </li>
    </ul>

    <div class="pt-5">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-semibold dark:text-white-light">Attendance Management</h2>
            <div class="flex gap-2">
                <button id="btn-generate-today" class="btn btn-outline-primary">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Generate Today's Attendance
                </button>
                <button id="btn-open-export" class="btn btn-outline-success">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="4" y="13" width="16" height="7" rx="2" stroke-width="2" />
                        <path d="M12 4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M8 8l4-4 4 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Export Report
                </button>
                <button id="btn-open-create" class="btn btn-primary">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Mark Attendance
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-success" id="stat-present">{{ $stats['present'] ?? 0 }}</p>
                        <h5 class="text-sm font-semibold text-white-dark mt-1">Present Today</h5>
                    </div>
                    <div class="rounded-full bg-success/10 p-3">
                        <svg class="h-8 w-8 text-success" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-danger" id="stat-absent">{{ $stats['absent'] ?? 0 }}</p>
                        <h5 class="text-sm font-semibold text-white-dark mt-1">Absent Today</h5>
                    </div>
                    <div class="rounded-full bg-danger/10 p-3">
                        <svg class="h-8 w-8 text-danger" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-warning" id="stat-halfday">{{ $stats['half_day'] ?? 0 }}</p>
                        <h5 class="text-sm font-semibold text-white-dark mt-1">Half Day</h5>
                    </div>
                    <div class="rounded-full bg-warning/10 p-3">
                        <svg class="h-8 w-8 text-warning" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="mb-5 panel">
            <h6 class="mb-4 font-semibold">Filters</h6>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-2 block text-xs font-semibold">Date</label>
                    <input type="date" id="filter-date" class="form-input" value="{{ $request->date ?? date('Y-m-d') }}">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold">Employee</label>
                    <select id="filter-employee" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $request->employee_id == $employee->id ? 'selected' : '' }}>
                                {{ $employee->employee_name }} ({{ $employee->staff_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="btn-reset-filters" class="btn btn-outline-secondary w-full">Reset Filters</button>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loading-indicator" class="panel text-center py-8" style="display: none;">
            <svg class="animate-spin h-8 w-8 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-3 text-gray-600 dark:text-gray-400">Loading...</p>
        </div>

        <!-- Attendance Table -->
        <div id="attendance-table-container" class="panel">
            <div class="mb-4 flex items-center justify-between border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                <div>
                    <h5 class="text-lg font-semibold">Attendance Records</h5>
                    <p class="text-xs text-white-dark mt-1">Click time/status to edit</p>
                </div>
                <p class="text-sm text-white-dark">Total: <span id="total-records">{{ $attendances->total() }}</span></p>
            </div>

            <div class="table-responsive">
                <table class="table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Staff Number</th>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Overtime</th>
                            <th>Total Hours</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="attendance-tbody">
                        @forelse($attendances as $attendance)
                            <tr data-id="{{ $attendance->id }}">
                                <td>{{ $attendance->employee->employee_name ?? '-' }}</td>
                                <td>{{ $attendance->staff_number }}</td>
                                <td>{{ $attendance->attendance_date ? \Carbon\Carbon::parse($attendance->attendance_date)->format('d M Y') : '-' }}</td>
                                <td>
                                    <input type="time" class="form-input w-34 inline-edit-time"
                                        data-id="{{ $attendance->id }}"
                                        data-field="check_in_time"
                                        value="{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '' }}">
                                </td>
                                <td>
                                    <input type="time" class="form-input w-34 inline-edit-time"
                                        data-id="{{ $attendance->id }}"
                                        data-field="check_out_time"
                                        value="{{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '' }}">
                                </td>
                                <td>
                                    @if(($attendance->overtime_hours ?? 0) > 0)
                                        <span class="badge bg-warning">{{ $attendance->getFormattedOvertimeHours() }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="font-semibold">{{ $attendance->getFormattedTotalHours() }}</td>
                                <td>
                                    <select class="form-select w-44 inline-edit-status" data-id="{{ $attendance->id }}" data-field="status">
                                        <option value="present" {{ $attendance->status == 'present' ? 'selected' : '' }}>Present</option>
                                        <option value="absent" {{ $attendance->status == 'absent' ? 'selected' : '' }}>Absent</option>
                                        <option value="half_day" {{ $attendance->status == 'half_day' ? 'selected' : '' }}>Half Day</option>
                                        <option value="leave" {{ $attendance->status == 'leave' ? 'selected' : '' }}>Leave</option>
                                        <option value="holiday" {{ $attendance->status == 'holiday' ? 'selected' : '' }}>Holiday</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger btn-delete-attendance" data-id="{{ $attendance->id }}" title="Delete">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="no-records-row">
                                <td colspan="9" class="text-center py-12 text-white-dark">
                                    <svg class="h-16 w-16 mx-auto mb-4 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="font-semibold text-lg">No attendance records found</p>
                                    <p class="text-sm mt-2">Click "Generate Today's Attendance" to create records</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($attendances->hasPages())
                <div class="flex items-center justify-between border-t border-white-light px-6 py-4 dark:border-[#1b2e4b]" id="pagination-container">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Page {{ $attendances->currentPage() }} of {{ $attendances->lastPage() }}
                    </p>
                    <div class="flex gap-2 items-center">
                        <button class="btn btn-sm btn-outline-secondary btn-pagination" data-page="{{ $attendances->currentPage() - 1 }}" {{ $attendances->currentPage() == 1 ? 'disabled' : '' }}>
                            Previous
                        </button>
                        <div class="flex gap-1" id="page-numbers">
                            @for($i = 1; $i <= $attendances->lastPage(); $i++)
                                <button class="btn btn-sm h-9 w-9 p-0 btn-pagination {{ $attendances->currentPage() == $i ? 'btn-primary bg-primary text-white font-bold' : 'btn-outline-secondary' }}" data-page="{{ $i }}">
                                    {{ $i }}
                                </button>
                            @endfor
                        </div>
                        <button class="btn btn-sm btn-outline-secondary btn-pagination" data-page="{{ $attendances->currentPage() + 1 }}" {{ $attendances->currentPage() == $attendances->lastPage() ? 'disabled' : '' }}>
                            Next
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Modal -->
<div id="create-modal" class="fixed inset-0 z-[999] overflow-y-auto" style="display: none;">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="modal-backdrop fixed inset-0 bg-black bg-opacity-60"></div>
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-lg shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                <h3 class="text-lg font-semibold">Mark Attendance</h3>
                <button class="close-modal text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="create-attendance-form" class="p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-semibold">Employee <span class="text-danger">*</span></label>
                        <select id="create-employee-id" class="form-select" required>
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->employee_name }} ({{ $employee->staff_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" id="create-attendance-date" class="form-input" required>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold">Status <span class="text-danger">*</span></label>
                        <select id="create-status" class="form-select" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="half_day">Half Day</option>
                            <option value="leave">Leave</option>
                            <option value="holiday">Holiday</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold">Check In <span class="text-danger">*</span></label>
                        <input type="time" id="create-check-in" class="form-input" required>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold">Check Out</label>
                        <input type="time" id="create-check-out" class="form-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-semibold">Notes</label>
                        <textarea id="create-notes" rows="2" class="form-textarea" placeholder="Enter notes..."></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-outline-danger close-modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="btn-submit-create">
                        <span class="submit-text">Mark Attendance</span>
                        <span class="submit-loading" style="display: none;">Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="export-modal" class="fixed inset-0 z-[999] overflow-y-auto" style="display: none;">
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="modal-backdrop fixed inset-0 bg-black bg-opacity-60"></div>
        <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-5 py-3">
                <h3 class="text-lg font-semibold">Export Attendance Report</h3>
                <button class="close-export-modal text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form id="export-form" class="p-5">
                <div class="space-y-4">
                    <div>
                        <label class="block mb-2 text-sm font-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" id="export-start-date" class="form-input" required>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold">End Date <span class="text-danger">*</span></label>
                        <input type="date" id="export-end-date" class="form-input" required>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold">Employee (Optional)</label>
                        <select id="export-employee" class="form-select">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->employee_name }} ({{ $employee->staff_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-semibold">Export Format <span class="text-danger">*</span></label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="export-format" value="csv" class="form-radio" checked>
                                <span class="ml-2">CSV</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="export-format" value="pdf" class="form-radio">
                                <span class="ml-2">PDF</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-outline-danger close-export-modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="4" y="13" width="16" height="7" rx="2" stroke-width="2" />
                            <path d="M12 4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8 8l4-4 4 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Export Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    // Initialize form defaults
    const today = new Date().toISOString().split('T')[0];
    $('#create-attendance-date').val(today);
    $('#create-check-in').val('08:00');
    $('#create-check-out').val('18:00');
    $('#create-status').val('present');

    // Generate Today's Attendance
    $('#btn-generate-today').on('click', function(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Generate Today\'s Attendance?',
            text: 'This will create attendance records for all active employees.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, generate!'
        }).then(function(result) {
            if (!result.isConfirmed) return;

            const date = $('#filter-date').val();

            $.ajax({
                url: '{{ route("attendances.generate-today") }}',
                type: 'POST',
                data: { date: date },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Success!', res.message, 'success');
                        setTimeout(function() {
                            loadAttendances();
                        }, 800);
                    } else {
                        Swal.fire('Error!', res.message || 'Failed to generate attendance', 'error');
                    }
                },
                error: function(xhr) {
                    let msg = 'Failed to generate attendance';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Error!', msg, 'error');
                }
            });
        });
    });

    // Load Attendances via AJAX
    function loadAttendances(page = 1) {
        $('#loading-indicator').show();
        $('#attendance-table-container').hide();

        const params = {
            page: page,
            date: $('#filter-date').val(),
            employee_id: $('#filter-employee').val()
        };

        $.ajax({
            url: '{{ route("attendances.index") }}',
            type: 'GET',
            data: params,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                $('#stat-present').text(data.stats.present);
                $('#stat-absent').text(data.stats.absent);
                $('#stat-halfday').text(data.stats.half_day);
                $('#total-records').text(data.pagination.total);

                let tbody = $('#attendance-tbody');
                tbody.empty();

                if (data.attendances.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="9" class="text-center py-12 text-white-dark">
                                <svg class="h-16 w-16 mx-auto mb-4 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="font-semibold text-lg">No attendance records found</p>
                                <p class="text-sm mt-2">Click "Generate Today's Attendance" to create records</p>
                            </td>
                        </tr>
                    `);
                } else {
                    data.attendances.forEach(function(att) {
                        let checkIn = att.check_in_time ? formatTimeValue(att.check_in_time) : '';
                        let checkOut = att.check_out_time ? formatTimeValue(att.check_out_time) : '';
                        let overtimeBadge = att.overtime_hours > 0 ?
                            `<span class="badge bg-warning">${att.formatted_overtime_hours}</span>` : '-';

                        tbody.append(`
                            <tr data-id="${att.id}">
                                <td>${att.employee.employee_name}</td>
                                <td>${att.staff_number}</td>
                                <td>${formatDate(att.attendance_date)}</td>
                                <td>
                                    <input type="time" class="form-input w-34 inline-edit-time"
                                        data-id="${att.id}" data-field="check_in_time" value="${checkIn}">
                                </td>
                                <td>
                                    <input type="time" class="form-input w-34 inline-edit-time"
                                        data-id="${att.id}" data-field="check_out_time" value="${checkOut}">
                                </td>
                                <td>${overtimeBadge}</td>
                                <td class="font-semibold">${att.formatted_total_hours}</td>
                                <td>
                                    <select class="form-select w-44 inline-edit-status" data-id="${att.id}" data-field="status">
                                        <option value="present" ${att.status === 'present' ? 'selected' : ''}>Present</option>
                                        <option value="absent" ${att.status === 'absent' ? 'selected' : ''}>Absent</option>
                                        <option value="half_day" ${att.status === 'half_day' ? 'selected' : ''}>Half Day</option>
                                        <option value="leave" ${att.status === 'leave' ? 'selected' : ''}>Leave</option>
                                        <option value="holiday" ${att.status === 'holiday' ? 'selected' : ''}>Holiday</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger btn-delete-attendance" data-id="${att.id}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }

                // REMOVE old pagination first before adding new one
                $('#pagination-container').remove();

                // Add new pagination if needed
                if (data.pagination.last_page > 1) {
                    let paginationHtml = `
                        <div id="pagination-container" class="flex items-center justify-between border-t border-white-light px-6 py-4 dark:border-[#1b2e4b]">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Page ${data.pagination.current_page} of ${data.pagination.last_page}
                            </p>
                            <div class="flex gap-2 items-center">
                                <button class="btn btn-sm btn-outline-secondary btn-pagination" data-page="${data.pagination.current_page - 1}" ${data.pagination.current_page == 1 ? 'disabled' : ''}>Previous</button>
                                <div class="flex gap-1">
                    `;

                    for (let i = 1; i <= data.pagination.last_page; i++) {
                        let btnClass = i === data.pagination.current_page ? 'btn-primary bg-primary text-white font-bold' : 'btn-outline-secondary';
                        paginationHtml += `<button class="btn btn-sm h-9 w-9 p-0 btn-pagination ${btnClass}" data-page="${i}">${i}</button>`;
                    }

                    paginationHtml += `
                                </div>
                                <button class="btn btn-sm btn-outline-secondary btn-pagination" data-page="${data.pagination.current_page + 1}" ${data.pagination.current_page === data.pagination.last_page ? 'disabled' : ''}>Next</button>
                            </div>
                        </div>
                    `;

                    $('#attendance-table-container .table-responsive').after(paginationHtml);
                }

                $('#loading-indicator').hide();
                $('#attendance-table-container').show();
            },
            error: function(xhr) {
                console.error('Error loading attendances:', xhr);
                $('#loading-indicator').hide();
                $('#attendance-table-container').show();
            }
        });
    }

    // Pagination clicks
    $(document).on('click', '.btn-pagination', function() {
        if ($(this).is(':disabled')) return;
        const page = $(this).data('page');
        loadAttendances(page);
    });

    // Filter changes
    $('#filter-date, #filter-employee').on('change', function() {
        loadAttendances(1);
    });

    // Reset Filters
    $('#btn-reset-filters').on('click', function(e) {
        e.preventDefault();
        $('#filter-date').val(today);
        $('#filter-employee').val('');
        loadAttendances(1);
    });

    // Quick Update inline editing - with smart logic that respects user choices
    $(document).on('change', '.inline-edit-time, .inline-edit-status', function() {
        const id = $(this).data('id');
        const field = $(this).data('field');
        const row = $(`tr[data-id="${id}"]`);

        let userCheckInTime = row.find('input[data-field="check_in_time"]').val();
        let userCheckOutTime = row.find('input[data-field="check_out_time"]').val();
        let userStatus = row.find('select[data-field="status"]').val();

        // Track if this is a manual status change
        let isManualStatusChange = (field === 'status');

        // If STATUS dropdown changed manually, adjust times accordingly
        if (isManualStatusChange) {
            if (userStatus === 'half_day') {
                // Half day: ONLY set default times if BOTH are empty
                if (!userCheckInTime && !userCheckOutTime) {
                    userCheckInTime = '08:00';
                    userCheckOutTime = '12:00';
                    row.find('input[data-field="check_in_time"]').val('08:00');
                    row.find('input[data-field="check_out_time"]').val('12:00');
                }
                // If user already has times set (like 10 hours), KEEP THEM - don't override
            } else if (userStatus === 'absent' || userStatus === 'leave') {
                // Absent/Leave: Always clear times
                userCheckInTime = '';
                userCheckOutTime = '';
                row.find('input[data-field="check_in_time"]').val('');
                row.find('input[data-field="check_out_time"]').val('');
            } else if (userStatus === 'present' || userStatus === 'holiday') {
                // Present/Holiday: Set default times ONLY if both are empty
                if (!userCheckInTime && !userCheckOutTime) {
                    userCheckInTime = '08:00';
                    userCheckOutTime = '18:00';
                    row.find('input[data-field="check_in_time"]').val('08:00');
                    row.find('input[data-field="check_out_time"]').val('18:00');
                }
            }
        }

        // If TIME changed (not status), suggest appropriate status based on hours
        if ((field === 'check_in_time' || field === 'check_out_time') && userCheckInTime && userCheckOutTime) {
            const hoursWorked = calculateHoursDifference(userCheckInTime, userCheckOutTime);

            // Auto-suggest status based on hours worked (but don't force it)
            if (hoursWorked >= 8) {
                // 8+ hours = Present
                userStatus = 'present';
                row.find('select[data-field="status"]').val('present');
            } else if (hoursWorked >= 4 && hoursWorked < 8) {
                // 4-7.99 hours = Half Day
                userStatus = 'half_day';
                row.find('select[data-field="status"]').val('half_day');
            } else if (hoursWorked > 0 && hoursWorked < 4) {
                // Less than 4 hours = Absent (not enough hours)
                userStatus = 'absent';
                row.find('select[data-field="status"]').val('absent');
            }
        }

        // If times are cleared, set to absent
        if (!userCheckInTime && !userCheckOutTime && (field === 'check_in_time' || field === 'check_out_time')) {
            if (userStatus !== 'leave' && userStatus !== 'holiday') {
                userStatus = 'absent';
                row.find('select[data-field="status"]').val('absent');
            }
        }

        $.ajax({
            url: `/attendances/${id}/quick-update`,
            type: 'PATCH',
            data: {
                check_in_time: userCheckInTime,
                check_out_time: userCheckOutTime,
                status: userStatus,
                manual_status_change: isManualStatusChange ? '1' : '0' // Send as string '1' or '0'
            },
            success: function(res) {
                if (res.success && res.attendance) {
                    const att = res.attendance;

                    // Update check-in time display
                    row.find('input[data-field="check_in_time"]').val(
                        att.check_in_time ? formatTimeValue(att.check_in_time) : ''
                    );

                    // Update check-out time display
                    row.find('input[data-field="check_out_time"]').val(
                        att.check_out_time ? formatTimeValue(att.check_out_time) : ''
                    );

                    // Update Overtime
                    if (att.overtime_hours > 0) {
                        row.find('td').eq(5).html(`<span class="badge bg-warning">${att.formatted_overtime_hours}</span>`);
                    } else {
                        row.find('td').eq(5).text('-');
                    }

                    // Update Total Hours
                    row.find('td').eq(6).html(att.formatted_total_hours);

                    // Update status dropdown to reflect backend value
                    row.find('select[data-field="status"]').val(att.status);

                    updateStatsOnly();
                }
            },
            error: function(xhr) {
                console.error('Quick update error:', xhr);
                Swal.fire('Error!', 'Failed to update attendance', 'error');
            }
        });
    });

    // Helper function to calculate hours difference between two times
    function calculateHoursDifference(checkIn, checkOut) {
        if (!checkIn || !checkOut) return 0;

        // Parse times (format: HH:mm)
        const [inHour, inMin] = checkIn.split(':').map(Number);
        const [outHour, outMin] = checkOut.split(':').map(Number);

        // Convert to minutes
        const inMinutes = inHour * 60 + inMin;
        const outMinutes = outHour * 60 + outMin;

        // Calculate difference in hours
        const diffMinutes = outMinutes - inMinutes;
        const diffHours = diffMinutes / 60;

        return diffHours;
    }

    // Update stats only - DO NOT reload table or pagination
    function updateStatsOnly() {
        const params = {
            date: $('#filter-date').val(),
            employee_id: $('#filter-employee').val()
        };

        $.ajax({
            url: '{{ route("attendances.index") }}',
            type: 'GET',
            data: params,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                // ONLY update the stats numbers
                $('#stat-present').text(data.stats.present);
                $('#stat-absent').text(data.stats.absent);
                $('#stat-halfday').text(data.stats.half_day);
                // Do NOT touch the table or pagination
            }
        });
    }

    // Open Create Modal
    $('#btn-open-create').on('click', function() {
        $('#create-employee-id').val('');
        $('#create-attendance-date').val(today);
        $('#create-check-in').val('08:00');
        $('#create-check-out').val('18:00');
        $('#create-status').val('present');
        $('#create-notes').val('');
        $('#create-modal').fadeIn();
    });

    // Close Modals
    $('.close-modal, .modal-backdrop').on('click', function() {
        $('#create-modal, #edit-modal').fadeOut();
    });

    // Submit Create
    $('#create-attendance-form').on('submit', function(e) {
        e.preventDefault();

        $('#btn-submit-create .submit-text').hide();
        $('#btn-submit-create .submit-loading').show();
        $('#btn-submit-create').prop('disabled', true);

        const formData = {
            employee_id: $('#create-employee-id').val(),
            attendance_date: $('#create-attendance-date').val(),
            check_in_time: $('#create-check-in').val(),
            check_out_time: $('#create-check-out').val(),
            status: $('#create-status').val(),
            notes: $('#create-notes').val()
        };

        $.ajax({
            url: '{{ route("attendances.store") }}',
            type: 'POST',
            data: formData,
            success: function(res) {
                $('#btn-submit-create .submit-text').show();
                $('#btn-submit-create .submit-loading').hide();
                $('#btn-submit-create').prop('disabled', false);

                if (res.success) {
                    $('#create-modal').fadeOut();
                    loadAttendances();
                    Swal.fire('Success!', res.message, 'success');
                } else {
                    Swal.fire('Error!', res.message, 'error');
                }
            },
            error: function(xhr) {
                $('#btn-submit-create .submit-text').show();
                $('#btn-submit-create .submit-loading').hide();
                $('#btn-submit-create').prop('disabled', false);

                let msg = 'An error occurred while marking attendance';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Error!', msg, 'error');
            }
        });
    });

    // Delete Attendance
    $(document).on('click', '.btn-delete-attendance', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'You won\'t be able to revert this!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/attendances/${id}`,
                    type: 'DELETE',
                    success: function(res) {
                        if (res.success) {
                            loadAttendances();
                            Swal.fire('Deleted!', res.message, 'success');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'Failed to delete attendance', 'error');
                    }
                });
            }
        });
    });

    // Helper Functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function formatTimeValue(dateTimeString) {
        if (!dateTimeString) return '';

        // Remove any whitespace
        dateTimeString = String(dateTimeString).trim();

        // If it's already just time (HH:mm or HH:mm:ss)
        if (/^\d{2}:\d{2}(:\d{2})?$/.test(dateTimeString)) {
            return dateTimeString.substring(0, 5); // Return HH:mm only
        }

        // If it contains a space (datetime format YYYY-MM-DD HH:mm:ss)
        if (dateTimeString.includes(' ')) {
            const parts = dateTimeString.split(' ');
            if (parts.length === 2 && parts[1].includes(':')) {
                return parts[1].substring(0, 5); // Return HH:mm from time part
            }
        }

        // If it contains T (ISO format YYYY-MM-DDTHH:mm:ss)
        if (dateTimeString.includes('T')) {
            const timePart = dateTimeString.split('T')[1];
            if (timePart && timePart.includes(':')) {
                return timePart.substring(0, 5); // Return HH:mm
            }
        }

        // Last resort: try parsing as Date object
        try {
            const date = new Date(dateTimeString);
            if (!isNaN(date.getTime())) {
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${hours}:${minutes}`;
            }
        } catch (e) {
            console.error('Error parsing time:', dateTimeString, e);
        }

        // If all fails, return empty
        return '';
    }

    // Open Export Modal
    $('#btn-open-export').on('click', function() {
        const today = new Date().toISOString().split('T')[0];
        const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];

        $('#export-start-date').val(firstDay);
        $('#export-end-date').val(today);
        $('#export-employee').val('');
        $('input[name="export-format"][value="csv"]').prop('checked', true);
        $('#export-modal').fadeIn();
    });

    // Close Export Modal
    $('.close-export-modal, #export-modal .modal-backdrop').on('click', function() {
        $('#export-modal').fadeOut();
    });

    // Handle Export Form Submit
    $('#export-form').on('submit', function(e) {
        e.preventDefault();

        const startDate = $('#export-start-date').val();
        const endDate = $('#export-end-date').val();
        const employeeId = $('#export-employee').val();
        const format = $('input[name="export-format"]:checked').val();

        if (!startDate || !endDate) {
            Swal.fire('Error!', 'Please select both start and end dates', 'error');
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            Swal.fire('Error!', 'Start date cannot be after end date', 'error');
            return;
        }

        // Build URL with parameters
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
            format: format
        });

        if (employeeId) {
            params.append('employee_id', employeeId);
        }

        // Trigger download
        window.location.href = '{{ route("attendances.report.export") }}?' + params.toString();

        $('#export-modal').fadeOut();
        Swal.fire('Success!', 'Your report is being downloaded...', 'success');
    });
</script>
@endpush
