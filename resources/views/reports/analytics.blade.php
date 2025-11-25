@extends('layouts.app')

@section('title', 'Attendance Analytics')

@section('content')
<div class="space-y-6">
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Reports</span>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Analytics</span>
        </li>
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

            <!-- Summary Stats -->
            <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-5">
                <div class="panel bg-success-light">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-success">{{ $reportData['summary']['total_present'] }}</p>
                        <p class="text-xs font-semibold mt-1">Total Present</p>
                    </div>
                </div>
                <div class="panel bg-danger-light">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-danger">{{ $reportData['summary']['total_absent'] }}</p>
                        <p class="text-xs font-semibold mt-1">Total Absent</p>
                    </div>
                </div>
                <div class="panel bg-warning-light">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-warning">{{ $reportData['summary']['total_half_day'] }}</p>
                        <p class="text-xs font-semibold mt-1">Half Days</p>
                    </div>
                </div>
                <div class="panel bg-info-light">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-info">{{ $reportData['summary']['total_leave'] }}</p>
                        <p class="text-xs font-semibold mt-1">Leaves</p>
                    </div>
                </div>
                <div class="panel bg-secondary-light">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-secondary">{{ number_format($reportData['summary']['total_hours'], 2) }}h</p>
                        <p class="text-xs font-semibold mt-1">Total Hours</p>
                    </div>
                </div>
            </div>

            <!-- Employee Reports -->
            <div class="space-y-4">
                @foreach($reportData['employee_reports'] as $report)
                    <div class="panel">
                        <div class="mb-4 flex items-center justify-between border-b pb-3">
                            <div>
                                <h3 class="text-lg font-semibold">{{ $report['employee']->employee_name }}</h3>
                                <p class="text-sm text-gray-600">Staff No: {{ $report['employee']->staff_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-primary">{{ $report['attendance_percentage'] }}%</p>
                                <p class="text-xs text-gray-600">Attendance Rate</p>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                            <div class="rounded border-l-4 border-primary bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-2xl font-bold">{{ $report['total_days'] }}</p>
                                <p class="text-xs text-gray-600">Total Days</p>
                            </div>
                            <div class="rounded border-l-4 border-success bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-2xl font-bold text-success">{{ $report['present_count'] }}</p>
                                <p class="text-xs text-gray-600">Present</p>
                            </div>
                            <div class="rounded border-l-4 border-danger bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-2xl font-bold text-danger">{{ $report['absent_count'] }}</p>
                                <p class="text-xs text-gray-600">Absent</p>
                            </div>
                            <div class="rounded border-l-4 border-warning bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-2xl font-bold text-warning">{{ $report['half_day_count'] }}</p>
                                <p class="text-xs text-gray-600">Half Day</p>
                            </div>
                            <div class="rounded border-l-4 border-info bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-2xl font-bold text-info">{{ $report['leave_count'] }}</p>
                                <p class="text-xs text-gray-600">Leave</p>
                            </div>
                            <div class="rounded border-l-4 border-secondary bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-2xl font-bold">{{ number_format($report['total_hours'], 1) }}h</p>
                                <p class="text-xs text-gray-600">Total Hours</p>
                            </div>
                            <div class="rounded border-l-4 border-warning bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-2xl font-bold text-warning">{{ number_format($report['overtime_hours'], 1) }}h</p>
                                <p class="text-xs text-gray-600">Overtime</p>
                            </div>
                        </div>

                        <!-- Date Details -->
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            @if(count($report['present_dates']) > 0)
                                <div>
                                    <h4 class="mb-2 font-semibold text-success">Present Days ({{ count($report['present_dates']) }})</h4>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($report['present_dates'] as $date)
                                            <span class="badge bg-success">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(count($report['absent_dates']) > 0)
                                <div>
                                    <h4 class="mb-2 font-semibold text-danger">Absent Days ({{ count($report['absent_dates']) }})</h4>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($report['absent_dates'] as $date)
                                            <span class="badge bg-danger">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(count($report['half_day_dates']) > 0)
                                <div>
                                    <h4 class="mb-2 font-semibold text-warning">Half Days ({{ count($report['half_day_dates']) }})</h4>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($report['half_day_dates'] as $date)
                                            <span class="badge bg-warning">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(count($report['leave_dates']) > 0)
                                <div>
                                    <h4 class="mb-2 font-semibold text-info">Leave Days ({{ count($report['leave_dates']) }})</h4>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($report['leave_dates'] as $date)
                                            <span class="badge bg-info">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
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
