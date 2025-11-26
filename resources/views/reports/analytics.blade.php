@extends('layouts.app')

@section('title', 'Attendance Analytics')

@section('content')
<div class="space-y-6">
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li><a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a></li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Reports</span></li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Analytics</span></li>
    </ul>

    <div class="panel">
        <div class="mb-5">
            <h2 class="text-2xl font-semibold">Attendance Analytics Report</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Detailed employee attendance analysis with date breakdowns</p>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('reports.analytics') }}" class="mb-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label class="mb-2 block text-xs font-semibold">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-input" required>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-input" required>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-semibold">Employee (Optional)</label>
                    <select name="employee_id" id="employee-select-analytics" class="form-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ $employeeId == $employee->id ? 'selected' : '' }}>
                                {{ $employee->employee_name }} ({{ $employee->staff_number }})
                                @if($employee->user && $employee->user->role === 'admin')
                                    - Admin
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" name="generate" value="1" class="btn btn-primary flex-1">
                        <svg class="h-5 w-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Generate
                    </button>
                </div>
            </div>
        </form>

        @if($reportData)
            <!-- Export Buttons -->
            <div class="mb-5 flex gap-2">
                <a href="{{ route('reports.export', array_merge(request()->all(), ['format' => 'csv'])) }}"
                   class="btn btn-outline-success">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('reports.export', array_merge(request()->all(), ['format' => 'pdf'])) }}"
                   class="btn btn-outline-danger">
                    <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Export PDF
                </a>
            </div>

            @if($employeeId)
                {{-- Single Employee View --}}
                @php $report = $reportData['employee_reports'][0] ?? null; @endphp
                @if($report)
                    <div class="panel mb-6">
                        <div class="mb-4 flex items-center justify-between border-b pb-3">
                            <div>
                                <h3 class="text-xl font-semibold text-primary">{{ $report['employee']->employee_name }}</h3>
                                <p class="text-sm text-gray-600">Staff No: {{ $report['employee']->staff_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold {{ $report['work_efficiency'] >= 90 ? 'text-success' : ($report['work_efficiency'] >= 75 ? 'text-warning' : 'text-danger') }}">
                                    {{ $report['work_efficiency'] }}%
                                </p>
                                <p class="text-xs text-gray-600">Work Efficiency</p>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                            <div class="rounded border-l-4 border-primary bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-xl font-bold">{{ $report['present_count'] }}/{{ $report['total_working_days'] }}</p>
                                <p class="text-xs text-gray-600">Present/Working</p>
                            </div>
                            <div class="rounded border-l-4 border-success bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-xl font-bold text-success">{{ $report['attendance_percentage'] }}%</p>
                                <p class="text-xs text-gray-600">Attendance</p>
                            </div>
                            <div class="rounded border-l-4 border-danger bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-xl font-bold text-danger">{{ $report['absent_count'] }}</p>
                                <p class="text-xs text-gray-600">Absent</p>
                            </div>
                            <div class="rounded border-l-4 border-warning bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-xl font-bold text-warning">{{ $report['half_day_count'] }}</p>
                                <p class="text-xs text-gray-600">Half Days</p>
                            </div>
                            <div class="rounded border-l-4 border-info bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-xl font-bold text-info">{{ $report['leave_count'] }}</p>
                                <p class="text-xs text-gray-600">Leave</p>
                            </div>
                            <div class="rounded border-l-4 border-warning bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-xl font-bold text-warning">{{ $report['late_count'] }}</p>
                                <p class="text-xs text-gray-600">Late Entries</p>
                            </div>
                            <div class="rounded border-l-4 border-secondary bg-gray-50 p-3 dark:bg-gray-800">
                                @php
                                    $oth = floor($report['overtime_hours']);
                                    $otm = round(($report['overtime_hours'] - $oth) * 60);
                                @endphp
                                <p class="text-lg font-bold text-secondary">{{ $oth }}h {{ $otm }}m</p>
                                <p class="text-xs text-gray-600">Overtime</p>
                            </div>
                        </div>

                        <!-- Work Hours Details -->
                        <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="rounded border-l-4 border-primary bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-sm text-gray-600 mb-1">Actual Work Hours</p>
                                @php
                                    $th = floor($report['total_hours']);
                                    $tm = round(($report['total_hours'] - $th) * 60);
                                @endphp
                                <p class="text-2xl font-bold text-primary">{{ $th }}h {{ $tm }}m</p>
                            </div>
                            <div class="rounded border-l-4 border-secondary bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-sm text-gray-600 mb-1">Required Work Hours</p>
                                @php
                                    $rh = floor($report['required_work_hours']);
                                    $rm = round(($report['required_work_hours'] - $rh) * 60);
                                @endphp
                                <p class="text-2xl font-bold">{{ $rh }}h {{ $rm }}m</p>
                            </div>
                        </div>

                        <!-- Date Details (collapsible) -->
                        <details class="mt-3" open>
                            <summary class="cursor-pointer text-sm font-semibold text-primary hover:underline">View Date Breakdown</summary>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 mt-3">
                                @if(count($report['present_dates']) > 0)
                                    <div>
                                        <h4 class="mb-2 font-semibold text-success">✓ Present Days ({{ count($report['present_dates']) }})</h4>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($report['present_dates'] as $date)
                                                <span class="badge bg-success">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(count($report['absent_dates']) > 0)
                                    <div>
                                        <h4 class="mb-2 font-semibold text-danger">✗ Absent Days ({{ count($report['absent_dates']) }})</h4>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($report['absent_dates'] as $date)
                                                <span class="badge bg-danger">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(count($report['half_day_dates']) > 0)
                                    <div>
                                        <h4 class="mb-2 font-semibold text-warning">◐ Half Days ({{ count($report['half_day_dates']) }})</h4>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($report['half_day_dates'] as $date)
                                                <span class="badge bg-warning">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(count($report['leave_dates']) > 0)
                                    <div>
                                        <h4 class="mb-2 font-semibold text-info">⊙ Leave Days ({{ count($report['leave_dates']) }})</h4>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($report['leave_dates'] as $date)
                                                <span class="badge bg-info">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </details>
                    </div>
                @endif
            @else
                {{-- All Employees View --}}
                <!-- Team Efficiency Badge -->
                <div class="mb-6 panel text-center
                    {{ $reportData['team_efficiency'] >= 90 ? 'bg-success-light' : ($reportData['team_efficiency'] >= 75 ? 'bg-warning-light' : 'bg-danger-light') }}">
                    <div class="py-4">
                        <p class="text-5xl font-bold
                            {{ $reportData['team_efficiency'] >= 90 ? 'text-success' : ($reportData['team_efficiency'] >= 75 ? 'text-warning' : 'text-danger') }}">
                            {{ $reportData['team_efficiency'] }}%
                        </p>
                        <p class="text-lg font-semibold mt-2">Overall Team Efficiency</p>
                        <p class="text-sm text-gray-600 mt-1">Calculated across all {{ $reportData['active_employee_count'] }} active employees (excluding admins)</p>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="panel bg-success-light">
                        <div class="text-center py-3">
                            <p class="text-4xl font-bold text-success">{{ $reportData['summary']['total_present'] }}/{{ $reportData['total_working_days'] * $reportData['active_employee_count'] }}</p>
                            <p class="text-sm font-semibold mt-2">Total Present / Total Working Days</p>
                            <p class="text-xs text-gray-600 mt-1">Combined across all employees</p>
                        </div>
                    </div>
                    <div class="panel bg-warning-light">
                        <div class="text-center py-3">
                            <p class="text-4xl font-bold text-warning">{{ $reportData['summary']['total_half_day'] }}</p>
                            <p class="text-sm font-semibold mt-2">Half Days</p>
                            <p class="text-xs text-gray-600 mt-1">Partial day attendance</p>
                        </div>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="panel bg-success-light">
                        <div class="flex items-center p-4">
                            <svg class="h-10 w-10 text-success mr-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-3xl font-bold text-success">{{ $reportData['total_approved_leaves'] }}</p>
                                <p class="text-sm font-semibold text-gray-700">Approved Leaves</p>
                                <p class="text-xs text-gray-500 mt-1">Pre-approved absences</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel bg-danger-light">
                        <div class="flex items-center p-4">
                            <svg class="h-10 w-10 text-danger mr-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-3xl font-bold text-danger">{{ $reportData['total_unapproved_absences'] }}</p>
                                <p class="text-sm font-semibold text-gray-700">Unapproved Absences</p>
                                <p class="text-xs text-gray-500 mt-1">Without prior approval</p>
                            </div>
                        </div>
                    </div>
                    <div class="panel bg-warning-light">
                        <div class="flex items-center p-4">
                            <svg class="h-10 w-10 text-warning mr-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-3xl font-bold text-warning">{{ $reportData['total_late_entries'] }}</p>
                                <p class="text-sm font-semibold text-gray-700">Total Late Entries</p>
                                <p class="text-xs text-gray-500 mt-1">Check-ins after 8:00 AM</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Working Hours Comparison -->
                <div class="mb-6 panel">
                    <h3 class="mb-4 text-lg font-semibold border-b pb-2">Combined Employee Work Hours Analysis</h3>
                    <p class="text-sm text-gray-600 mb-4">Total hours across all {{ $reportData['active_employee_count'] }} active employees</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded">
                            <p class="text-sm text-gray-600 mb-1">Required Work Hours</p>
                            @php
                                $reqHours = floor($reportData['total_required_hours']);
                                $reqMinutes = round(($reportData['total_required_hours'] - $reqHours) * 60);
                            @endphp
                            <p class="text-2xl font-bold">{{ $reqHours }}h {{ $reqMinutes }}m</p>
                            <p class="text-xs text-gray-500 mt-1">({{ $reportData['total_working_days'] }} working days × 10 hours × {{ $reportData['active_employee_count'] }} employees)</p>
                        </div>
                        <div class="text-center p-4 bg-primary-light rounded">
                            <p class="text-sm text-gray-600 mb-1">Actual Work Hours</p>
                            @php
                                $actualHours = floor($reportData['total_actual_hours']);
                                $actualMinutes = round(($reportData['total_actual_hours'] - $actualHours) * 60);
                            @endphp
                            <p class="text-2xl font-bold text-primary">{{ $actualHours }}h {{ $actualMinutes }}m</p>
                            <p class="text-xs text-gray-500 mt-1">Combined hours by all employees</p>
                        </div>
                        <div class="text-center p-4 rounded
                            {{ $reportData['total_actual_hours'] >= $reportData['total_required_hours'] ? 'bg-success-light' : 'bg-danger-light' }}">
                            <p class="text-sm text-gray-600 mb-1">
                                {{ $reportData['total_actual_hours'] >= $reportData['total_required_hours'] ? 'Exceeded By' : 'Short By' }}
                            </p>
                            @php
                                $diff = abs($reportData['total_actual_hours'] - $reportData['total_required_hours']);
                                $diffHours = floor($diff);
                                $diffMinutes = round(($diff - $diffHours) * 60);
                                $diffColor = $reportData['total_actual_hours'] >= $reportData['total_required_hours'] ? 'text-success' : 'text-danger';
                            @endphp
                            <p class="text-2xl font-bold {{ $diffColor }}">
                                {{ $reportData['total_actual_hours'] >= $reportData['total_required_hours'] ? '+' : '-' }}{{ $diffHours }}h {{ $diffMinutes }}m
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ round(($reportData['total_actual_hours'] / $reportData['total_required_hours']) * 100, 1) }}% completion
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Work Efficiency Criteria Info -->
                <div class="mb-6 panel bg-gray-50 dark:bg-gray-800">
                    <h4 class="mb-3 font-semibold">Work Efficiency Calculation Criteria</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div class="p-3 bg-white dark:bg-gray-900 rounded">
                            <div class="flex items-center mb-2">
                                <div class="w-3 h-3 bg-primary rounded-full mr-2"></div>
                                <strong class="text-primary">Attendance Rate (40%)</strong>
                            </div>
                            <p class="text-xs text-gray-600">Present days ÷ Total working days<br>Higher attendance = Better score</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-gray-900 rounded">
                            <div class="flex items-center mb-2">
                                <div class="w-3 h-3 bg-success rounded-full mr-2"></div>
                                <strong class="text-success">Work Hours (30%)</strong>
                            </div>
                            <p class="text-xs text-gray-600">Completed hours ÷ Required hours<br>Meeting quota = Full points</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-gray-900 rounded">
                            <div class="flex items-center mb-2">
                                <div class="w-3 h-3 bg-warning rounded-full mr-2"></div>
                                <strong class="text-warning">Punctuality (20%)</strong>
                            </div>
                            <p class="text-xs text-gray-600">On-time arrivals ÷ Present days<br>No late entries = Full points</p>
                        </div>
                        <div class="p-3 bg-white dark:bg-gray-900 rounded">
                            <div class="flex items-center mb-2">
                                <div class="w-3 h-3 bg-danger rounded-full mr-2"></div>
                                <strong class="text-danger">Absence Impact (10%)</strong>
                            </div>
                            <p class="text-xs text-gray-600">2 points deducted per absence<br>Maximum 10 point penalty</p>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-3 text-center">
                        <strong>Working Hours:</strong> 8:00 AM - 6:00 PM (10 hours) |
                        <strong>Overtime:</strong> After 6:00 PM |
                        <strong>Late Entry:</strong> After 8:00 AM
                    </p>
                </div>

                <!-- Employee Reports -->
                <div class="space-y-4">
                    @foreach($reportData['employee_reports'] as $report)
                        <div class="panel">
                            <div class="mb-4 flex items-center justify-between border-b pb-3">
                                <div>
                                    <a href="{{ route('reports.employee-detail', ['employee' => $report['employee']->id, 'start_date' => $reportData['start_date'], 'end_date' => $reportData['end_date']]) }}"
                                       class="text-lg font-semibold text-primary hover:underline">
                                        {{ $report['employee']->employee_name }}
                                    </a>
                                    <p class="text-sm text-gray-600">Staff No: {{ $report['employee']->staff_number }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold
                                        {{ $report['work_efficiency'] >= 90 ? 'text-success' : ($report['work_efficiency'] >= 75 ? 'text-warning' : 'text-danger') }}">
                                        {{ $report['work_efficiency'] }}%
                                    </p>
                                    <p class="text-xs text-gray-600">Work Efficiency</p>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                                <div class="rounded border-l-4 border-primary bg-gray-50 p-3 dark:bg-gray-800">
                                    <p class="text-xl font-bold">{{ $report['present_count'] }}/{{ $report['total_working_days'] }}</p>
                                    <p class="text-xs text-gray-600">Present/Working Days</p>
                                </div>
                                <div class="rounded border-l-4 border-success bg-gray-50 p-3 dark:bg-gray-800">
                                    <p class="text-xl font-bold text-success">{{ $report['attendance_percentage'] }}%</p>
                                    <p class="text-xs text-gray-600">Attendance Rate</p>
                                </div>
                                <div class="rounded border-l-4 border-danger bg-gray-50 p-3 dark:bg-gray-800">
                                    <p class="text-xl font-bold text-danger">{{ $report['absent_count'] }}</p>
                                    <p class="text-xs text-gray-600">Absent Days</p>
                                </div>
                                <div class="rounded border-l-4 border-info bg-gray-50 p-3 dark:bg-gray-800">
                                    <p class="text-xl font-bold text-info">{{ $report['leave_count'] }}</p>
                                    <p class="text-xs text-gray-600">Leave Days</p>
                                </div>
                                <div class="rounded border-l-4 border-warning bg-gray-50 p-3 dark:bg-gray-800">
                                    @php
                                        $late = $report['late_count'];
                                        $onTime = $report['present_count'] - $late;
                                    @endphp
                                    <p class="text-xl font-bold text-warning">{{ $late }}</p>
                                    <p class="text-xs text-gray-600">Late Entries</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $onTime }} on time</p>
                                </div>
                                <div class="rounded border-l-4 border-secondary bg-gray-50 p-3 dark:bg-gray-800">
                                    @php
                                        $oth = floor($report['overtime_hours']);
                                        $otm = round(($report['overtime_hours'] - $oth) * 60);
                                    @endphp
                                    <p class="text-xl font-bold text-secondary">{{ $oth }}h {{ $otm }}m</p>
                                    <p class="text-xs text-gray-600">Total Overtime</p>
                                </div>
                            </div>

                            <!-- Work Hours Details -->
                            <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div class="rounded border-l-4 border-primary bg-gray-50 p-3 dark:bg-gray-800">
                                    <p class="text-sm text-gray-600 mb-1">Actual Work Hours</p>
                                    @php
                                        $th = floor($report['total_hours']);
                                        $tm = round(($report['total_hours'] - $th) * 60);
                                    @endphp
                                    <p class="text-2xl font-bold text-primary">{{ $th }}h {{ $tm }}m</p>
                                </div>
                                <div class="rounded border-l-4 border-secondary bg-gray-50 p-3 dark:bg-gray-800">
                                    <p class="text-sm text-gray-600 mb-1">Required Work Hours</p>
                                    @php
                                        $rh = floor($report['required_work_hours']);
                                        $rm = round(($report['required_work_hours'] - $rh) * 60);
                                    @endphp
                                    <p class="text-2xl font-bold">{{ $rh }}h {{ $rm }}m</p>
                                </div>
                            </div>

                            <!-- Date Details (collapsible) -->
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-semibold text-primary hover:underline">View Date Breakdown</summary>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 mt-3">
                                    @if(count($report['present_dates']) > 0)
                                        <div>
                                            <h4 class="mb-2 font-semibold text-success">✓ Present Days ({{ count($report['present_dates']) }})</h4>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($report['present_dates'] as $date)
                                                    <span class="badge bg-success">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if(count($report['absent_dates']) > 0)
                                        <div>
                                            <h4 class="mb-2 font-semibold text-danger">✗ Absent Days ({{ count($report['absent_dates']) }})</h4>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($report['absent_dates'] as $date)
                                                    <span class="badge bg-danger">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if(count($report['half_day_dates']) > 0)
                                        <div>
                                            <h4 class="mb-2 font-semibold text-warning">◐ Half Days ({{ count($report['half_day_dates']) }})</h4>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($report['half_day_dates'] as $date)
                                                    <span class="badge bg-warning">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if(count($report['leave_dates']) > 0)
                                        <div>
                                            <h4 class="mb-2 font-semibold text-info">⊙ Leave Days ({{ count($report['leave_dates']) }})</h4>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($report['leave_dates'] as $date)
                                                    <span class="badge bg-info">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </details>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <svg class="h-16 w-16 mx-auto mb-4 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="font-semibold text-lg">No Report Generated</p>
                <p class="text-sm mt-2 text-gray-600">Select date range and click Generate to view analytics</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    // Initialize Select2 for Analytics page
    $('#employee-select-analytics').select2({
        placeholder: 'Search employee...',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush
