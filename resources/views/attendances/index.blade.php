@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
<div id="attendance-manager">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold dark:text-white-light">Attendance Management</h2>
            <ul class="flex space-x-2 rtl:space-x-reverse mt-2">
                <li>
                    <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
                </li>
                <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                    <span>Attendance</span>
                </li>
            </ul>
        </div>
        <div class="flex gap-2">
            <button id="btn-bulk-generate" class="btn btn-outline-primary">
                <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Bulk Generate
            </button>

            <button id="btn-export" class="btn btn-outline-success">
                <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="4" y="13" width="16" height="7" rx="2" stroke-width="2" />
                    <path d="M12 4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M8 8l4-4 4 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Export Report
            </button>

            <button id="btn-create" class="btn btn-primary">
                <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Mark Attendance
            </button>
        </div>
    </div>

    <!-- Stats Cards with Explanations -->
    <div class="mb-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Present Card -->
        <div class="panel">
            <div class="flex items-center">
                <div class="shrink-0 rounded-full bg-success/10 p-3 text-success ring-2 ring-success/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor" opacity="0.5"/>
                        <path d="M8 12L10.5 14.5L16 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="ltr:ml-3 rtl:mr-3">
                    <p class="text-xl font-bold text-success" id="stat-present">0</p>
                    <h5 class="text-xs text-[#506690]">Present</h5>
                    <p class="text-[10px] text-white-dark mt-1">Marked present</p>
                </div>
            </div>
        </div>

        <!-- Absent Card -->
        <div class="panel">
            <div class="flex items-center">
                <div class="shrink-0 rounded-full bg-danger/10 p-3 text-danger ring-2 ring-danger/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle opacity="0.5" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M14.5 9.50002L9.5 14.5M9.49998 9.5L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="ltr:ml-3 rtl:mr-3">
                    <p class="text-xl font-bold text-danger" id="stat-absent">0</p>
                    <h5 class="text-xs text-[#506690]">Absent</h5>
                    <p class="text-[10px] text-white-dark mt-1">No show/permission</p>
                </div>
            </div>
        </div>

        <!-- Leave Card (NEW) -->
        <div class="panel">
            <div class="flex items-center">
                <div class="shrink-0 rounded-full bg-info/10 p-3 text-info ring-2 ring-info/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12V14C22 17.7712 22 19.6569 20.8284 20.8284C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V12Z" fill="currentColor"/>
                        <path d="M7 4V2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M17 4V2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M21.5 9H16.625H10.75M2 9H5.875" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="ltr:ml-3 rtl:mr-3">
                    <p class="text-xl font-bold text-info" id="stat-leave">0</p>
                    <h5 class="text-xs text-[#506690]">On Leave</h5>
                    <p class="text-[10px] text-white-dark mt-1">Approved leave</p>
                </div>
            </div>
        </div>

        <!-- Half Day Card -->
        <div class="panel">
            <div class="flex items-center">
                <div class="shrink-0 rounded-full bg-warning/10 p-3 text-warning ring-2 ring-warning/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 7V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="12" cy="16" r="1" fill="currentColor"/>
                        <path opacity="0.5" d="M7.84308 3.80211C9.8718 2.6007 10.8862 2 12 2C13.1138 2 14.1282 2.6007 16.1569 3.80211L16.8431 4.20846C18.8718 5.40987 19.8862 6.01057 20.4431 7C21 7.98943 21 9.19084 21 11.5937V12.4063C21 14.8092 21 16.0106 20.4431 17C19.8862 17.9894 18.8718 18.5901 16.8431 19.7915L16.1569 20.1979C14.1282 21.3993 13.1138 22 12 22C10.8862 22 9.8718 21.3993 7.84308 20.1979L7.15692 19.7915C5.1282 18.5901 4.11384 17.9894 3.55692 17C3 16.0106 3 14.8092 3 12.4063V11.5937C3 9.19084 3 7.98943 3.55692 7C4.11384 6.01057 5.1282 5.40987 7.15692 4.20846L7.84308 3.80211Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="ltr:ml-3 rtl:mr-3">
                    <p class="text-xl font-bold text-warning" id="stat-half-day">0</p>
                    <h5 class="text-xs text-[#506690]">Half Day</h5>
                    <p class="text-[10px] text-white-dark mt-1">4-8 hours worked</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Banner explaining differences -->
    <div class="mb-6">
        <div class="panel bg-gradient-to-r from-blue-500/10 to-indigo-500/10 border border-blue-500/20">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-blue-500 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                    <path d="M12 8v4M12 16h.01" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <div class="flex-1">
                    <h6 class="font-semibold mb-2 text-sm">Status Definitions:</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-xs text-white-dark">
                        <div>
                            <span class="font-semibold text-success">Present:</span> Employee worked full day (≥8 hours)
                        </div>
                        <div>
                            <span class="font-semibold text-danger">Absent:</span> No show without prior approval
                        </div>
                        <div>
                            <span class="font-semibold text-info">Leave:</span> Pre-approved absence (sick leave, vacation)
                        </div>
                        <div>
                            <span class="font-semibold text-warning">Half Day:</span> Worked 4-8 hours
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filters Panel -->
    <div class="panel mb-6">
        <div class="mb-5">
            <h5 class="text-lg font-semibold dark:text-white-light mb-4">Filter Attendance</h5>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Single Date Filter -->
                <div>
                    <label class="block text-sm font-semibold mb-2">Date</label>
                    <input type="date" id="filter-date" value="{{ request('date', date('Y-m-d')) }}" class="form-input">
                </div>

                <!-- Employee Filter with Search -->
                <div>
                    <label class="block text-sm font-semibold mb-2">Employee</label>
                    <select id="filter-employee" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->employee_name }} ({{ $employee->staff_number }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-semibold mb-2">Status</label>
                    <select id="filter-status" class="form-select">
                        <option value="">All Status</option>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="leave">Leave</option>
                        <option value="half_day">Half Day</option>
                        <option value="holiday">Holiday</option>
                    </select>
                </div>

                <!-- Quick Date Filters -->
                <div>
                    <label class="block text-sm font-semibold mb-2">Quick Select</label>
                    <select id="filter-quick-date" class="form-select">
                        <option value="">Custom Date</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="thisweek">This Week</option>
                        <option value="lastweek">Last Week</option>
                        <option value="thismonth">This Month</option>
                        <option value="lastmonth">Last Month</option>
                    </select>
                </div>
            </div>

            <!-- Action Buttons Row -->
            <div class="flex gap-2 mt-4">
                <button id="btn-reset-filters" class="btn btn-outline-secondary">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="panel">
        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">Attendance Records</h5>
            <div class="text-sm text-white-dark">
                <span id="showing-info">Showing records</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-hover" id="attendance-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Staff Number</th>
                        <th>Employee Name</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Total Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="attendance-tbody">
                    <tr id="loading-row">
                        <td colspan="8" class="text-center py-10">
                            <div class="flex flex-col items-center justify-center">
                                <div class="loader"></div>
                                <p class="mt-4 text-white-dark font-semibold">Loading attendance records...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-5 flex items-center justify-center" id="pagination-container" style="display: none;">
            <ul class="inline-flex items-center space-x-1 rtl:space-x-reverse" id="pagination-list">
                <!-- Pagination buttons will be inserted here -->
            </ul>
        </div>
    </div>
</div>

<!-- Bulk Generate Modal -->
<div id="bulk-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="text-xl font-semibold dark:text-white-light">Bulk Generate Attendance</h3>
            <button class="modal-close" data-modal="bulk-modal">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="modal-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Date</label>
                    <input type="date" id="bulk-date" value="{{ date('Y-m-d') }}" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Status</label>
                    <select id="bulk-status" class="form-select">
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="half_day">Half Day</option>
                        <option value="leave">Leave</option>
                        <option value="holiday">Holiday</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" id="bulk-time-fields">
                <div>
                    <label class="block text-sm font-semibold mb-2">Check-In Time</label>
                    <input type="time" id="bulk-check-in" value="08:00" class="form-input">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Check-Out Time</label>
                    <input type="time" id="bulk-check-out" value="18:00" class="form-input">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2">Notes (Optional)</label>
                <textarea id="bulk-notes" rows="2" class="form-textarea" placeholder="Add any notes..."></textarea>
            </div>

            <div>
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-semibold">Select Employees</label>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="bulk-select-all" checked class="form-checkbox mr-2">
                        <span class="text-sm font-semibold text-primary">Select All</span>
                    </label>
                </div>

                <div class="border border-white-light dark:border-[#1b2e4b] rounded-lg max-h-64 overflow-y-auto" id="employee-list">
                    @foreach($employees as $employee)
                        <label class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-[#1b2e4b] cursor-pointer border-b border-white-light dark:border-[#1b2e4b] last:border-b-0">
                            <input type="checkbox" class="employee-checkbox form-checkbox mr-3" value="{{ $employee->id }}" checked>
                            <div class="flex-1">
                                <p class="font-semibold text-sm">{{ $employee->employee_name }}</p>
                                <p class="text-xs text-white-dark">{{ $employee->staff_number }} • {{ $employee->designation }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>

                <p class="text-xs text-white-dark mt-2">
                    <span id="selected-count">{{ count($employees) }}</span> of {{ count($employees) }} employees selected
                </p>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-outline-secondary modal-close" data-modal="bulk-modal">Cancel</button>
            <button id="btn-generate-bulk" class="btn btn-primary">
                <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Generate Attendance
            </button>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="form-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container max-w-2xl">
        <div class="modal-header">
            <h3 class="text-xl font-semibold dark:text-white-light" id="form-modal-title">Mark Attendance</h3>
            <button class="modal-close" data-modal="form-modal">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="attendance-form">
            <div class="modal-body">
                <div class="mb-4" id="employee-select-group">
                    <label class="block text-sm font-semibold mb-2">Employee</label>
                    <select id="form-employee" class="form-select" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->employee_name }} ({{ $employee->staff_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Date</label>
                    <input type="date" id="form-date" class="form-input" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Check-In Time</label>
                        <input type="time" id="form-check-in" class="form-input" value="08:00" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Check-Out Time</label>
                        <input type="time" id="form-check-out" class="form-input" value="18:00">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Status</label>
                    <select id="form-status" class="form-select" required>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="half_day">Half Day</option>
                        <option value="leave">Leave</option>
                        <option value="holiday">Holiday</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Notes</label>
                    <textarea id="form-notes" rows="3" class="form-textarea"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary modal-close" data-modal="form-modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="form-submit-btn">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Export Modal -->
<div id="export-modal" class="modal-overlay" style="display: none;">
    <div class="modal-container max-w-md">
        <div class="modal-header">
            <h3 class="text-xl font-semibold dark:text-white-light">Export Attendance Report</h3>
            <button class="modal-close" data-modal="export-modal">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="export-form">
            <div class="modal-body">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Start Date</label>
                    <input type="date" id="export-start" value="{{ date('Y-m-01') }}" class="form-input" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">End Date</label>
                    <input type="date" id="export-end" value="{{ date('Y-m-d') }}" class="form-input" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Employee (Optional)</label>
                    <select id="export-employee" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->employee_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Format</label>
                    <select id="export-format" class="form-select" required>
                        <option value="csv">CSV</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary modal-close" data-modal="export-modal">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="4" y="13" width="16" height="7" rx="2" stroke-width="2" />
                        <path d="M12 4v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M8 8l4-4 4 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Export
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    {{-- Select2 CDN for searchable dropdowns --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // GLOBAL STATE
        let currentPage = 1;
        let currentEditId = null;
        let currentFilters = {}; // NEW: persist filters across pagination
        const employees = @json($employees);

        // DOCUMENT READY
        $(document).ready(function () {
            console.log('Attendance Manager Initialized');

            // 1. Initialize Select2 FIRST
            initializeSelect2();

            // 2. Set initial date
            $('#filter-date').val('{{ $request->date ?? date("Y-m-d") }}');

            // 3. THEN attach event listeners
            initEventListeners();

            // 4. Load initial data with all current filter values
            updateFilters();
            fetchAttendances();
        });

        // INITIALIZE EVENT LISTENERS
        function initEventListeners() {
            // Single date filter change
            $('#filter-date').on('change', function () {
                console.log('Date changed:', $(this).val());
                $('#filter-quick-date').val('').trigger('change.select2');
                currentPage = 1;
                updateFilters();
                fetchAttendances();
                updateStats();
            });

            // Employee filter
            $('#filter-employee').on('select2:select select2:clear', function () {
                console.log('Employee changed:', $(this).val());
                currentPage = 1;
                updateFilters();
                fetchAttendances();
                updateStats(); // ADD THIS
            });

            // Status filter
            $('#filter-status').on('select2:select select2:clear', function () {
                console.log('Status changed:', $(this).val());
                currentPage = 1;
                updateFilters();
                fetchAttendances();
                updateStats(); // ADD THIS
            });

            // Quick date selection
            $('#filter-quick-date').on('select2:select', function (e) {
                const range = e.params.data.id;
                console.log('Quick select:', range);
                if (range) {
                    applyQuickDateFilter(range);
                    updateStats(); // ADD THIS
                }
            });

            // Clear quick date (back to today by default)
            $('#filter-quick-date').on('select2:clear', function () {
                console.log('Quick select cleared - Reset to today');
                $('#filter-date').val('{{ date("Y-m-d") }}');
                currentPage = 1;
                updateFilters();
                fetchAttendances();
            });

            // Reset filters
            $('#btn-reset-filters').on('click', function () {
                resetFilters();
            });

            // Modal triggers
            $('#btn-bulk-generate').on('click', function () {
                resetBulkForm();
                openModal('#bulk-modal');
            });

            $('#btn-create').on('click', function () {
                openCreateModal();
            });

            $('#btn-export').on('click', function () {
                openModal('#export-modal');
            });

            // Modal close buttons
            $(document).on('click', '.modal-close', function () {
                const modalId = $(this).data('modal');
                closeModal('#' + modalId);
            });

            // Close modal on backdrop click
            $(document).on('click', '.modal-overlay', function (e) {
                if (e.target === this) {
                    closeModal('#' + $(this).attr('id'));
                }
            });

            // Bulk status change (show/hide time fields)
            $('#bulk-status').on('change', function () {
                updateBulkTimeFields($(this).val());
            });

            // Bulk select all checkbox
            $('#bulk-select-all').on('change', function () {
                const isChecked = $(this).is(':checked');
                $('.employee-checkbox').prop('checked', isChecked);
                updateSelectedCount();
            });

            // Individual employee checkboxes
            $(document).on('change', '.employee-checkbox', function () {
                updateSelectedCount();
                const totalCheckboxes = $('.employee-checkbox').length;
                const checkedCheckboxes = $('.employee-checkbox:checked').length;
                $('#bulk-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
            });

            // Bulk generate submit
            $('#btn-generate-bulk').on('click', function () {
                generateBulkAttendance();
            });

            // Create/Edit form submit
            $('#attendance-form').on('submit', function (e) {
                e.preventDefault();
                submitForm();
            });

            // Export form submit
            $('#export-form').on('submit', function (e) {
                e.preventDefault();
                exportReport();
            });

            // Quick update - inline time editing
            $(document).on('change', '.quick-update-time', function () {
                const id = $(this).data('id');
                const field = $(this).data('field');
                const value = $(this).val();
                quickUpdate(id, field, value);
            });

            // Quick update - status change with automatic time setting
            $(document).on('change', '.quick-update-status', function () {
                const id = $(this).data('id');
                const newStatus = $(this).val();
                const row = $(this).closest('tr');
                const checkInInput = row.find('.quick-update-time[data-field="check_in_time"]');
                const checkOutInput = row.find('.quick-update-time[data-field="check_out_time"]');

                let checkInTime = null;
                let checkOutTime = null;

                if (newStatus === 'present') {
                    checkInTime = '08:00';
                    checkOutTime = '18:00';
                } else if (newStatus === 'half_day') {
                    checkInTime = '08:00';
                    checkOutTime = '12:00';
                } else if (['absent', 'leave', 'holiday'].includes(newStatus)) {
                    checkInTime = '';
                    checkOutTime = '';
                }

                checkInInput.val(checkInTime);
                checkOutInput.val(checkOutTime);

                quickUpdate(id, 'status', newStatus, true);
            });

            // Delete button
            $(document).on('click', '.btn-delete', function () {
                const id = $(this).data('id');
                deleteAttendance(id);
            });

            // Pagination click event (FIXED to respect filters)
            $(document).on('click', '.pagination-btn', function () {
                if (!$(this).is(':disabled')) {
                    const page = parseInt($(this).data('page'));
                    fetchAttendances(page); // Only page; filters come from currentFilters

                    // Scroll to top
                    $('html, body').animate({
                        scrollTop: $('#attendance-table').offset().top - 100
                    }, 300);
                }
            });
        }

        // UPDATE FILTERS - NEW helper to keep filter state
        function updateFilters() {
            // Always start fresh and collect ALL active filters
            currentFilters = {};

            // 1. Employee filter (always check)
            const employeeId = $('#filter-employee').val();
            if (employeeId) {
                currentFilters.employee_id = employeeId;
            }

            // 2. Status filter (always check)
            const status = $('#filter-status').val();
            if (status) {
                currentFilters.status = status;
            }

            // 3. Date filtering (check which mode is active)
            const quickSelect = $('#filter-quick-date').val();

            if (quickSelect) {
                // Quick select is active - keep start_date/end_date from applyQuickDateFilter
                // Don't override if they're already set
                if (!currentFilters.start_date && !currentFilters.end_date) {
                    // This shouldn't happen but just in case
                    console.warn('Quick select active but no date range set');
                }
            } else {
                // Manual single date mode
                const dateValue = $('#filter-date').val();
                if (dateValue) {
                    currentFilters.date = dateValue;
                }
                // Remove any date range params
                delete currentFilters.start_date;
                delete currentFilters.end_date;
            }

            console.log('✅ Updated filters:', currentFilters);
        }

        // QUICK DATE FILTER (UPDATED to set currentFilters)
        function applyQuickDateFilter(range) {
            console.log('🔹 Applying quick date filter:', range);

            const today = new Date();
            let startDate, endDate;

            switch (range) {
                case 'today':
                    startDate = endDate = formatDateForInput(today);
                    $('#filter-date').val(startDate);

                    // Single date mode for today
                    currentFilters.date = startDate;
                    delete currentFilters.start_date;
                    delete currentFilters.end_date;
                    break;

                case 'yesterday':
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    startDate = endDate = formatDateForInput(yesterday);
                    $('#filter-date').val(startDate);

                    // Single date mode for yesterday
                    currentFilters.date = startDate;
                    delete currentFilters.start_date;
                    delete currentFilters.end_date;
                    break;

                case 'thisweek':
                    const thisWeekStart = getStartOfWeek(today);
                    const thisWeekEnd = new Date(thisWeekStart);
                    thisWeekEnd.setDate(thisWeekEnd.getDate() + 6);
                    startDate = formatDateForInput(thisWeekStart);
                    endDate = formatDateForInput(thisWeekEnd);
                    $('#filter-date').val(''); // Clear single date
                    console.log('📅 This Week:', startDate, 'to', endDate);

                    // Date range mode
                    currentFilters.start_date = startDate;
                    currentFilters.end_date = endDate;
                    delete currentFilters.date;
                    break;

                case 'lastweek':
                    const lastWeekDate = new Date(today);
                    lastWeekDate.setDate(lastWeekDate.getDate() - 7);
                    const lastWeekStart = getStartOfWeek(lastWeekDate);
                    const lastWeekEnd = new Date(lastWeekStart);
                    lastWeekEnd.setDate(lastWeekEnd.getDate() + 6);
                    startDate = formatDateForInput(lastWeekStart);
                    endDate = formatDateForInput(lastWeekEnd);
                    $('#filter-date').val('');
                    console.log('📅 Last Week:', startDate, 'to', endDate);

                    // Date range mode
                    currentFilters.start_date = startDate;
                    currentFilters.end_date = endDate;
                    delete currentFilters.date;
                    break;

                case 'thismonth':
                    const firstDayThisMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    const lastDayThisMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    startDate = formatDateForInput(firstDayThisMonth);
                    endDate = formatDateForInput(lastDayThisMonth);
                    $('#filter-date').val('');
                    console.log('📅 This Month:', startDate, 'to', endDate);

                    // Date range mode
                    currentFilters.start_date = startDate;
                    currentFilters.end_date = endDate;
                    delete currentFilters.date;
                    break;

                case 'lastmonth':
                    const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                    startDate = formatDateForInput(firstDayLastMonth);
                    endDate = formatDateForInput(lastDayLastMonth);
                    $('#filter-date').val('');
                    console.log('📅 Last Month:', startDate, 'to', endDate);

                    // Date range mode
                    currentFilters.start_date = startDate;
                    currentFilters.end_date = endDate;
                    delete currentFilters.date;
                    break;

                default:
                    console.warn('⚠️ Unknown range:', range);
                    return;
            }

            // Now add employee and status filters (they should persist)
            const employeeId = $('#filter-employee').val();
            if (employeeId) {
                currentFilters.employee_id = employeeId;
            }

            const status = $('#filter-status').val();
            if (status) {
                currentFilters.status = status;
            }

            currentPage = 1;
            fetchAttendances();
        }

        // HELPER FUNCTIONS FOR DATES
        function formatDateForInput(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function getStartOfWeek(date) {
            const d = new Date(date);
            const day = d.getDay(); // 0 Sunday, 1 Monday, etc.
            const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
            return new Date(d.setDate(diff));
        }

        // FETCH ATTENDANCES (FIXED to use currentFilters)
        function fetchAttendances(page = 1) {
            $('#loading-row').show();
            $('#attendance-tbody tr:not(#loading-row)').remove();

            let params = { page: page };

            // Merge stored filters
            params = { ...params, ...currentFilters };

            // Remove empty params
            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });

            console.log('🚀 Fetching with params:', params);

            $.ajax({
                url: "{{ route('attendances.index') }}",
                method: 'GET',
                data: params,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    console.log('✅ Response:', response);
                    $('#loading-row').hide();

                    // Update stats
                    $('#stat-present').text(response.stats.present || 0);
                    $('#stat-absent').text(response.stats.absent || 0);
                    $('#stat-leave').text(response.stats.leave || 0);
                    $('#stat-half-day').text(response.stats.half_day || 0);

                    // Update showing info
                    $('#showing-info').text(`Showing ${response.attendances.length} of ${response.pagination.total} records`);

                    // Render attendances and pagination
                    renderAttendances(response.attendances);
                    renderPagination(response.pagination);

                    currentPage = response.pagination.current_page;
                },
                error: function (xhr) {
                    $('#loading-row').hide();
                    showToast('error', 'Failed to load attendance records');
                    console.error('❌ Fetch error:', xhr);
                }
            });
        }

        // RENDER ATTENDANCES (unchanged core logic)
        function renderAttendances(attendances) {
            const tbody = $('#attendance-tbody');
            tbody.find('tr:not(#loading-row)').remove();

            if (attendances.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="8" class="text-center py-10">
                            <p class="text-white-dark">No attendance records found</p>
                        </td>
                    </tr>
                `);
                return;
            }

            const statusColors = {
                present: 'text-success',
                absent: 'text-danger',
                half_day: 'text-warning',
                leave: 'text-info',
                holiday: 'text-secondary'
            };

            // Extract time from various formats
            const extractTime = (datetime) => {
                if (!datetime) return '';

                const datetimeStr = typeof datetime === 'object' ? JSON.stringify(datetime) : String(datetime);

                // Format: "YYYY-MM-DD HH:MM:SS"
                if (datetimeStr.includes(' ')) {
                    const parts = datetimeStr.split(' ');
                    if (parts.length === 2) {
                        const timePart = parts[1];
                        return timePart.substring(0, 5); // HH:MM
                    }
                }

                // ISO format: "2025-12-16T08:00:00.000000Z"
                if (datetimeStr.includes('T')) {
                    try {
                        const date = new Date(datetimeStr);
                        const hours = String(date.getHours()).padStart(2, '0');
                        const minutes = String(date.getMinutes()).padStart(2, '0');
                        return `${hours}:${minutes}`;
                    } catch (e) {
                        console.error('Failed to parse ISO datetime:', datetimeStr, e);
                        return '';
                    }
                }

                // Already HH:MM or HH:MM:SS
                if (datetimeStr.match(/\d{2}:\d{2}/)) {
                    return datetimeStr.substring(0, 5);
                }

                console.warn('Unknown datetime format:', datetimeStr);
                return '';
            };

            attendances.forEach(attendance => {
                const checkInValue = extractTime(attendance.check_in_time);
                const checkOutValue = extractTime(attendance.check_out_time);

                const row = `
                    <tr>
                        <td>${formatDate(attendance.attendance_date)}</td>
                        <td>${attendance.staff_number}</td>
                        <td>
                            <span class="font-semibold">${attendance.employee?.employee_name ?? 'N/A'}</span>
                        </td>
                        <td>
                            <input type="time"
                                   class="form-input w-32 quick-update-time"
                                   data-id="${attendance.id}"
                                   data-field="check_in_time"
                                   value="${checkInValue}"
                                   placeholder="--:--">
                        </td>
                        <td>
                            <input type="time"
                                   class="form-input w-32 quick-update-time"
                                   data-id="${attendance.id}"
                                   data-field="check_out_time"
                                   value="${checkOutValue}"
                                   placeholder="--:--">
                        </td>
                        <td>
                            <span class="font-semibold">${attendance.formatted_total_hours ?? '0h 0m'}</span>
                            ${attendance.overtime_hours > 0
                                ? `<span class="badge bg-warning ml-1 text-xs">${attendance.formatted_overtime_hours} OT</span>`
                                : ''}
                        </td>
                        <td>
                            <select class="form-select w-32 quick-update-status ${statusColors[attendance.status] || ''}"
                                    data-id="${attendance.id}">
                                <option value="present" ${attendance.status === 'present' ? 'selected' : ''}>Present</option>
                                <option value="absent" ${attendance.status === 'absent' ? 'selected' : ''}>Absent</option>
                                <option value="half_day" ${attendance.status === 'half_day' ? 'selected' : ''}>Half Day</option>
                                <option value="leave" ${attendance.status === 'leave' ? 'selected' : ''}>Leave</option>
                                <option value="holiday" ${attendance.status === 'holiday' ? 'selected' : ''}>Holiday</option>
                            </select>
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${attendance.id}" title="Delete">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5
                                                 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0
                                                 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;

                tbody.append(row);
            });
        }

        // RENDER PAGINATION - IMPROVED VERSION
        function renderPagination(pagination) {
            const container = $('#pagination-container');
            const list = $('#pagination-list');

            if (pagination.last_page <= 1) {
                container.hide();
                return;
            }

            container.show();
            list.empty();

            const currentPage = pagination.current_page;
            const lastPage = pagination.last_page;

            // Previous button
            const prevDisabled = currentPage === 1;
            list.append(`
                <li>
                    <button
                        class="pagination-btn flex items-center justify-center w-10 h-10 rounded-full font-semibold transition
                            ${prevDisabled
                                ? 'bg-white-light/60 text-dark/40 cursor-not-allowed dark:bg-[#191e3a]/60 dark:text-white-light/40'
                                : 'bg-white-light text-dark hover:bg-primary hover:text-white dark:bg-[#191e3a] dark:text-white-light dark:hover:bg-primary'}"
                        data-page="${currentPage - 1}"
                        ${prevDisabled ? 'disabled' : ''}>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </li>
            `);

            // Smart page number display (limit to 7 buttons max)
            let startPage = 1;
            let endPage = lastPage;

            if (lastPage > 7) {
                if (currentPage <= 4) {
                    // Show first 5 pages + ellipsis + last page
                    endPage = 5;
                } else if (currentPage >= lastPage - 3) {
                    // Show first page + ellipsis + last 5 pages
                    startPage = lastPage - 4;
                } else {
                    // Show first + ellipsis + current-1, current, current+1 + ellipsis + last
                    startPage = currentPage - 1;
                    endPage = currentPage + 1;
                }
            }

            // First page (if not in range)
            if (startPage > 1) {
                list.append(`
                    <li>
                        <button
                            class="pagination-btn flex items-center justify-center w-10 h-10 rounded-full font-semibold transition
                                ${1 === currentPage
                                    ? 'bg-primary text-white'
                                    : 'bg-white-light text-dark hover:bg-primary hover:text-white dark:bg-[#191e3a] dark:text-white-light dark:hover:bg-primary'}"
                            data-page="1">
                            1
                        </button>
                    </li>
                `);

                if (startPage > 2) {
                    list.append(`
                        <li>
                            <span class="flex items-center justify-center w-10 h-10 text-dark dark:text-white-light">...</span>
                        </li>
                    `);
                }
            }

            // Page numbers in range
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === currentPage;
                list.append(`
                    <li>
                        <button
                            class="pagination-btn flex items-center justify-center w-10 h-10 rounded-full font-semibold transition
                                ${isActive
                                    ? 'bg-primary text-white shadow-lg'
                                    : 'bg-white-light text-dark hover:bg-primary hover:text-white dark:bg-[#191e3a] dark:text-white-light dark:hover:bg-primary'}"
                            data-page="${i}">
                            ${i}
                        </button>
                    </li>
                `);
            }

            // Last page (if not in range)
            if (endPage < lastPage) {
                if (endPage < lastPage - 1) {
                    list.append(`
                        <li>
                            <span class="flex items-center justify-center w-10 h-10 text-dark dark:text-white-light">...</span>
                        </li>
                    `);
                }

                list.append(`
                    <li>
                        <button
                            class="pagination-btn flex items-center justify-center w-10 h-10 rounded-full font-semibold transition
                                ${lastPage === currentPage
                                    ? 'bg-primary text-white'
                                    : 'bg-white-light text-dark hover:bg-primary hover:text-white dark:bg-[#191e3a] dark:text-white-light dark:hover:bg-primary'}"
                            data-page="${lastPage}">
                            ${lastPage}
                        </button>
                    </li>
                `);
            }

            // Next button
            const nextDisabled = currentPage === lastPage;
            list.append(`
                <li>
                    <button
                        class="pagination-btn flex items-center justify-center w-10 h-10 rounded-full font-semibold transition
                            ${nextDisabled
                                ? 'bg-white-light/60 text-dark/40 cursor-not-allowed dark:bg-[#191e3a]/60 dark:text-white-light/40'
                                : 'bg-white-light text-dark hover:bg-primary hover:text-white dark:bg-[#191e3a] dark:text-white-light dark:hover:bg-primary'}"
                        data-page="${currentPage + 1}"
                        ${nextDisabled ? 'disabled' : ''}>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </li>
            `);
        }

        // RESET FILTERS (updated to reset ALL filters)
        function resetFilters() {
            console.log('🔄 Resetting all filters...');

            // Reset UI elements
            $('#filter-date').val('{{ date("Y-m-d") }}');
            $('#filter-employee').val('').trigger('change');
            $('#filter-status').val('').trigger('change');
            $('#filter-quick-date').val('').trigger('change');

            // Reset state
            currentPage = 1;
            currentFilters = {};

            // Update filters and fetch
            updateFilters();
            fetchAttendances();
        }

        // QUICK UPDATE
        function quickUpdate(id, field, value, isManualStatusChange = false) {
            const data = {};
            data[field] = value;

            if (isManualStatusChange) {
                data.manual_status_change = true;
            }

            // If updating check_in_time or check_out_time, send both
            if (field === 'check_in_time' || field === 'check_out_time') {
                const row = $(`input[data-id="${id}"]`).closest('tr');
                const checkInInput = row.find('.quick-update-time[data-field="check_in_time"]');
                const checkOutInput = row.find('.quick-update-time[data-field="check_out_time"]');
                data.check_in_time = checkInInput.val();
                data.check_out_time = checkOutInput.val();
            }

            $.ajax({
                url: `/attendances/${id}/quick-update`,
                method: 'PATCH',
                data: JSON.stringify(data),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    if (response.success) {
                        showToast('success', response.message);
                        // Update only this row, not entire table
                        updateSingleRow(id, response.attendance);
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function (xhr) {
                    showToast('error', 'Failed to update attendance');
                    console.error('Quick update error:', xhr);
                }
            });
        }

        // UPDATE SINGLE ROW AFTER QUICK UPDATE
        function updateSingleRow(id, attendance) {
            const row = $(`input[data-id="${id}"], select[data-id="${id}"]`).first().closest('tr');
            if (!row.length) {
                console.error('Row not found for attendance ID:', id);
                return;
            }

            const statusColors = {
                present: 'text-success',
                absent: 'text-danger',
                half_day: 'text-warning',
                leave: 'text-info',
                holiday: 'text-secondary'
            };

            const extractTime = (datetime) => {
                if (!datetime) return '';
                const datetimeStr = typeof datetime === 'object' ? JSON.stringify(datetime) : String(datetime);
                if (datetimeStr.includes(' ')) {
                    const parts = datetimeStr.split(' ');
                    if (parts.length === 2) return parts[1].substring(0, 5);
                }
                if (datetimeStr.includes('T')) {
                    try {
                        const date = new Date(datetimeStr);
                        const hours = String(date.getHours()).padStart(2, '0');
                        const minutes = String(date.getMinutes()).padStart(2, '0');
                        return `${hours}:${minutes}`;
                    } catch (e) {
                        console.error('Failed to parse:', datetimeStr, e);
                        return '';
                    }
                }
                if (datetimeStr.match(/\d{2}:\d{2}/)) {
                    return datetimeStr.substring(0, 5);
                }
                return '';
            };

            // Update times
            const checkInInput = row.find('.quick-update-time[data-field="check_in_time"]');
            const checkOutInput = row.find('.quick-update-time[data-field="check_out_time"]');

            const checkInValue = extractTime(attendance.check_in_time);
            const checkOutValue = extractTime(attendance.check_out_time);

            checkInInput.val(checkInValue);
            checkOutInput.val(checkOutValue);

            // Update total hours cell
            const totalHoursCell = row.find('td').eq(5);
            let totalHoursHtml = `<span class="font-semibold">${attendance.formatted_total_hours ?? '0h 0m'}</span>`;
            if (attendance.overtime_hours > 0) {
                totalHoursHtml += `<span class="badge bg-warning ml-1 text-xs">${attendance.formatted_overtime_hours} OT</span>`;
            }
            totalHoursCell.html(totalHoursHtml);

            // Update status dropdown
            const statusSelect = row.find('.quick-update-status');
            statusSelect.val(attendance.status);
            statusSelect.removeClass('text-success text-danger text-warning text-info text-secondary');
            statusSelect.addClass(statusColors[attendance.status] || '');

            // Highlight animation
            row.addClass('bg-yellow-50 dark:bg-yellow-900/20');
            setTimeout(() => {
                row.removeClass('bg-yellow-50 dark:bg-yellow-900/20');
            }, 1000);
        }

        // FORMAT TIME FROM DATETIME (spare helper)
        function formatTimeFromDateTime(datetime) {
            if (!datetime) return '';
            try {
                if (typeof datetime === 'string' && datetime.includes(' ')) {
                    const parts = datetime.split(' ');
                    if (parts.length === 2) {
                        const timePart = parts[1];
                        return timePart.slice(0, 5); // HH:MM
                    }
                }
                if (typeof datetime === 'string' && datetime.includes('T')) {
                    const date = new Date(datetime);
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${hours}:${minutes}`;
                }
                if (typeof datetime === 'string' && datetime.match(/\d{2}:\d{2}/)) {
                    return datetime.slice(0, 5);
                }
                return '';
            } catch (error) {
                console.error('Error formatting time:', error, datetime);
                return '';
            }
        }

        // UPDATE STATS
        function updateStats() {
            let params = { ...currentFilters };

            // Remove empty params
            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });

            console.log('📊 Updating stats with filters:', params);

            $.ajax({
                url: "{{ route('attendances.index') }}",
                method: 'GET',
                data: params,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    $('#stat-present').text(response.stats.present || 0);
                    $('#stat-absent').text(response.stats.absent || 0);
                    $('#stat-half-day').text(response.stats.half_day || 0);
                    $('#stat-leave').text(response.stats.leave || 0);
                    console.log('✅ Stats updated:', response.stats);
                },
                error: function (xhr) {
                    console.error('❌ Failed to update stats:', xhr);
                }
            });
        }

        // BULK GENERATE ATTENDANCE
        function generateBulkAttendance() {
            const selectedEmployees = $('.employee-checkbox:checked').map(function () {
                return $(this).val();
            }).get();

            if (selectedEmployees.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Employees Selected',
                    text: 'Please select at least one employee',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            const data = {
                date: $('#bulk-date').val(),
                status: $('#bulk-status').val(),
                check_in_time: $('#bulk-check-in').val(),
                check_out_time: $('#bulk-check-out').val(),
                employee_ids: selectedEmployees,
                notes: $('#bulk-notes').val()
            };

            Swal.fire({
                title: 'Generating Attendance...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('attendances.generate-today') }}",
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            html: `<p>${response.message}</p>
                                   <p class="text-sm text-gray-600 mt-2">
                                        <strong>${response.created || 0}</strong> records created
                                        ${response.skipped ? `<br><strong>${response.skipped}</strong> records skipped` : ''}
                                   </p>`,
                            confirmButtonColor: '#3085d6'
                        });
                        closeModal('#bulk-modal');
                        resetBulkForm();
                        fetchAttendances();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#d33'
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Failed to generate attendance',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        }

        // OPEN CREATE MODAL
        function openCreateModal() {
            currentEditId = null;
            $('#form-modal-title').text('Mark Attendance');
            $('#form-submit-btn').text('Create');
            $('#employee-select-group').show();

            $('#attendance-form')[0].reset();
            $('#form-date').val('{{ date("Y-m-d") }}');
            $('#form-check-in').val('08:00');
            $('#form-check-out').val('18:00');
            $('#form-status').val('present');

            openModal('#form-modal');
        }

        // SUBMIT FORM (create/update)
        function submitForm() {
            const isEdit = currentEditId !== null;
            const url = isEdit ? `/attendances/${currentEditId}` : "{{ route('attendances.store') }}";
            const method = isEdit ? 'PUT' : 'POST';

            const data = {
                employee_id: $('#form-employee').val(),
                attendance_date: $('#form-date').val(),
                check_in_time: $('#form-check-in').val(),
                check_out_time: $('#form-check-out').val(),
                status: $('#form-status').val(),
                notes: $('#form-notes').val()
            };

            $.ajax({
                url: url,
                method: method,
                data: JSON.stringify(data),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        closeModal('#form-modal');
                        fetchAttendances(currentPage);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#d33'
                        });
                    }
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Failed to save attendance',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        }

        // DELETE ATTENDANCE
        function deleteAttendance(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will permanently delete the attendance record!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/attendances/${id}`,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                fetchAttendances(currentPage);
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        },
                        error: function () {
                            Swal.fire('Error!', 'Failed to delete attendance', 'error');
                        }
                    });
                }
            });
        }

        // EXPORT REPORT
        function exportReport() {
            const startDate = $('#export-start').val();
            const endDate = $('#export-end').val();

            if (new Date(endDate) < new Date(startDate)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Date Range',
                    text: 'End date must be after start date'
                });
                return;
            }

            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate,
                format: $('#export-format').val()
            });

            const employeeId = $('#export-employee').val();
            if (employeeId) {
                params.append('employee_id', employeeId);
            }

            Swal.fire({
                title: 'Generating Report...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            window.location.href = "{{ route('attendances.report.export') }}" + '?' + params.toString();

            setTimeout(() => {
                closeModal('#export-modal');
                Swal.close();
                showToast('success', 'Report exported successfully!');
            }, 1500);
        }

        // MODAL FUNCTIONS
        function openModal(modalId) {
            $(modalId).fadeIn(300);
            $('body').css('overflow', 'hidden');
        }

        function closeModal(modalId) {
            $(modalId).fadeOut(300);
            $('body').css('overflow', 'auto');
        }

        // HELPER FUNCTIONS
        function updateBulkTimeFields(status) {
            const timeFields = $('#bulk-time-fields');

            if (['absent', 'leave', 'holiday'].includes(status)) {
                timeFields.hide();
                $('#bulk-check-in').val('');
                $('#bulk-check-out').val('');
            } else {
                timeFields.show();

                if (status === 'half_day') {
                    $('#bulk-check-in').val('08:00');
                    $('#bulk-check-out').val('12:00');
                } else if (status === 'present') {
                    $('#bulk-check-in').val('08:00');
                    $('#bulk-check-out').val('18:00');
                }
            }
        }

        function updateSelectedCount() {
            const count = $('.employee-checkbox:checked').length;
            $('#selected-count').text(count);
        }

        function resetBulkForm() {
            // Reset date to today
            $('#bulk-date').val('{{ date("Y-m-d") }}');

            // Reset status to present
            $('#bulk-status').val('present');

            // Reset times to default
            $('#bulk-check-in').val('08:00');
            $('#bulk-check-out').val('18:00');

            // Clear notes
            $('#bulk-notes').val('');

            // Reset employee selection
            $('#bulk-select-all').prop('checked', true);
            $('.employee-checkbox').prop('checked', true);
            updateSelectedCount();

            // ✅ FIX: Trigger updateBulkTimeFields to show time fields for "present" status
            updateBulkTimeFields('present');

            console.log('✅ Bulk form reset complete - time fields visible');
        }

        function formatDate(date) {
            if (!date) return '-';
            try {
                const d = new Date(date);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}-${month}-${year}`;
            } catch (error) {
                return date;
            }
        }

        // INITIALIZE SELECT2
        function initializeSelect2() {
            // Destroy existing Select2 if any
            if ($('#filter-employee').hasClass('select2-hidden-accessible')) {
                $('#filter-employee').select2('destroy');
            }
            if ($('#filter-status').hasClass('select2-hidden-accessible')) {
                $('#filter-status').select2('destroy');
            }
            if ($('#filter-quick-date').hasClass('select2-hidden-accessible')) {
                $('#filter-quick-date').select2('destroy');
            }

            // Employee filter dropdown WITH search
            $('#filter-employee').select2({
                placeholder: 'Search employee...',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,
                matcher: function (params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }
                    if (typeof data.text === 'undefined') {
                        return null;
                    }
                    if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
                        return data;
                    }
                    return null;
                }
            });

            // Status filter - no search
            $('#filter-status').select2({
                placeholder: 'All Status',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: Infinity
            });

            // Quick date filter - no search
            $('#filter-quick-date').select2({
                placeholder: 'Custom Date',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: Infinity
            });
        }

        // IMPROVED FORMAT TIME FUNCTION (used in some spots)
        function formatTime(time) {
            if (!time) return '';
            try {
                // ISO format
                if (time.includes('T')) {
                    const date = new Date(time);
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${hours}:${minutes}`;
                }
                // "YYYY-MM-DD HH:MM:SS"
                else if (time.includes(' ')) {
                    const timePart = time.split(' ')[1];
                    return timePart.slice(0, 5);
                }
                // "HH:MM:SS"
                else if (time.includes(':')) {
                    return time.slice(0, 5);
                }
                return time;
            } catch (error) {
                console.error('Format time error:', error, time);
                return '';
            }
        }

        // TOAST NOTIFICATION
        function showToast(type, message) {
            const bgColor =
                type === 'success'
                    ? 'bg-success'
                    : type === 'error'
                    ? 'bg-danger'
                    : type === 'warning'
                    ? 'bg-warning'
                    : 'bg-info';

            const toast = $(`
                <div class="fixed top-6 right-6 z-[9999] px-6 py-4 rounded-lg shadow-lg text-white ${bgColor} animate-fade-in" style="margin-top: 50px;">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            ${
                                type === 'success'
                                    ? '<path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor" opacity="0.5"></path><path d="M8 12L10.5 14.5L16 9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>'
                                    : '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"></circle><path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>'
                            }
                        </svg>
                        <span>${message}</span>
                    </div>
                </div>
            `);

            $('body').append(toast);

            setTimeout(() => {
                toast.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3000);
        }
    </script>

    <style>
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .modal-container {
            position: relative;
            width: 100%;
            max-width: 48rem;
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-height: 90vh;
            overflow-y: auto;
        }

        .dark .modal-container {
            background-color: #0e1726;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e0e6ed;
            padding: 1.5rem;
        }

        .dark .modal-header {
            border-color: #1b2e4b;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            border-top: 1px solid #e0e6ed;
            padding: 1.5rem;
        }

        .dark .modal-footer {
            border-color: #1b2e4b;
        }

        .modal-close {
            color: #888ea8;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: #0e1726;
        }

        .dark .modal-close:hover {
            color: #ffffff;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Scrollbar */
        .max-h-64::-webkit-scrollbar {
            width: 6px;
        }

        .max-h-64::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .max-h-64::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .dark .max-h-64::-webkit-scrollbar-track {
            background: #1b2e4b;
        }

        .dark .max-h-64::-webkit-scrollbar-thumb {
            background: #506690;
        }

        /* Row highlight */
        .bg-yellow-50 {
            background-color: #fff7d6;
        }

        .dark .bg-yellow-900\/20 {
            background-color: rgba(113, 63, 18, 0.2);
        }

        tr {
            transition: background-color 0.3s ease;
        }

        /* Select2 styles */
        .select2-container {
            font-size: 14px;
        }

        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #e0e6ed !important;
            border-radius: 6px !important;
            background-color: #fff !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
            color: #3b3f5c !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 8px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #888ea8 !important;
        }

        .select2-dropdown {
            border: 1px solid #e0e6ed !important;
            border-radius: 6px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        }

        .select2-search--dropdown {
            padding: 8px !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #e0e6ed !important;
            border-radius: 4px !important;
            padding: 6px 12px !important;
            font-size: 14px !important;
        }

        .select2-results__option {
            padding: 8px 12px !important;
            font-size: 14px !important;
        }

        .select2-results__option--highlighted {
            background-color: #4361ee !important;
            color: white !important;
        }

        /* Dark Mode */
        .dark .select2-container--default .select2-selection--single {
            background-color: #1b2e4b !important;
            border-color: #191e3a !important;
        }

        .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #bfc9d4 !important;
        }

        .dark .select2-dropdown {
            background-color: #1b2e4b !important;
            border-color: #191e3a !important;
        }

        .dark .select2-search--dropdown .select2-search__field {
            background-color: #0e1726 !important;
            border-color: #191e3a !important;
            color: #bfc9d4 !important;
        }

        .dark .select2-results__option {
            color: #bfc9d4 !important;
            background-color: #1b2e4b !important;
        }

        .dark .select2-results__option--highlighted {
            background-color: #4361ee !important;
            color: white !important;
        }

        /* Focus State */
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #4361ee !important;
            outline: none !important;
        }

        /* Clear Button */
        .select2-container--default .select2-selection__clear {
            font-size: 18px !important;
            margin-right: 20px !important;
            color: #e7515a !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: 26px !important;
            margin-top: 4px !important;
        }

        /* Pagination Styles */
        #pagination-list {
            display: flex;
            align-items: center;
            gap: 0.375rem; /* 6px spacing between buttons */
            flex-wrap: wrap;
        }

        #pagination-list li {
            list-style: none;
        }

        .pagination-btn {
            border: none;
            outline: none;
            cursor: pointer;
            user-select: none;
        }

        .pagination-btn:disabled {
            pointer-events: none;
        }

        .pagination-btn:not(:disabled):hover {
            transform: translateY(-1px);
        }

        .pagination-btn:not(:disabled):active {
            transform: translateY(0);
        }

        /* Smooth transitions */
        .pagination-btn {
            transition: all 0.2s ease-in-out;
        }

        /* Improved Loader Animation */
        .loader {
            width: 48px;
            height: 48px;
            border: 5px solid #e0e6ed;
            border-bottom-color: #4361ee;
            border-radius: 50%;
            display: inline-block;
            animation: rotation 1s linear infinite;
        }

        .dark .loader {
            border-color: #191e3a;
            border-bottom-color: #4361ee;
        }

        @keyframes rotation {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Remove old animate-spin if it conflicts */
        .animate-spin {
            animation: rotation 1s linear infinite !important;
        }
    </style>
@endpush

@endsection
