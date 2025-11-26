@extends('layouts.app')

@section('title', 'Employee Attendance Detail')

@section('content')
<div class="space-y-6">
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li><a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a></li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><a href="{{ route('reports.analytics') }}" class="text-primary hover:underline">Reports</a></li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Employee Detail</span></li>
    </ul>

    <div class="panel">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold">{{ $employee->employee_name }}</h2>
                <p class="text-sm text-gray-600">Staff Number: {{ $employee->staff_number }} | Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.employee-export', ['employee' => $employee->id, 'start_date' => $startDate, 'end_date' => $endDate, 'format' => 'csv']) }}"
                   class="btn btn-outline-success">
                    <svg class="h-5 w-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('reports.employee-export', ['employee' => $employee->id, 'start_date' => $startDate, 'end_date' => $endDate, 'format' => 'pdf']) }}"
                   class="btn btn-outline-danger">
                    <svg class="h-5 w-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Export PDF
                </a>
                <a href="{{ route('reports.analytics', ['start_date' => $startDate, 'end_date' => $endDate, 'employee_id' => $employee->id]) }}" class="btn btn-outline-secondary">
                    <svg class="h-5 w-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-7">
            <div class="panel bg-primary-light">
                <div class="text-center">
                    <p class="text-3xl font-bold text-primary">{{ $stats['attendance_percentage'] }}%</p>
                    <p class="text-xs font-semibold mt-1">Attendance Rate</p>
                </div>
            </div>
            <div class="panel bg-success-light">
                <div class="text-center">
                    <p class="text-2xl font-bold text-success">{{ $stats['present'] }}/{{ $stats['total_working_days'] }}</p>
                    <p class="text-xs font-semibold mt-1">Present / Working Days</p>
                </div>
            </div>
            <div class="panel bg-danger-light">
                <div class="text-center">
                    <p class="text-3xl font-bold text-danger">{{ $stats['absent'] }}</p>
                    <p class="text-xs font-semibold mt-1">Absent Days</p>
                </div>
            </div>
            <div class="panel bg-warning-light">
                <div class="text-center">
                    <p class="text-3xl font-bold text-warning">{{ $stats['half_day'] }}</p>
                    <p class="text-xs font-semibold mt-1">Half Days</p>
                </div>
            </div>
            <div class="panel bg-warning-light">
                <div class="text-center">
                    <p class="text-3xl font-bold text-warning">{{ $stats['late_count'] }}</p>
                    <p class="text-xs font-semibold mt-1">Late Entries</p>
                </div>
            </div>
            <div class="panel bg-info-light">
                <div class="text-center">
                    @php
                        $th = floor($stats['total_hours']);
                        $tm = round(($stats['total_hours'] - $th) * 60);
                        $rh = floor($stats['required_work_hours']);
                        $rm = round(($stats['required_work_hours'] - $rh) * 60);
                    @endphp
                    <p class="text-xl font-bold">{{ $th }}h {{ $tm }}m / {{ $rh }}h {{ $rm }}m</p>
                    <p class="text-xs font-semibold mt-1">Actual / Required Hours</p>
                </div>
            </div>
            <div class="panel {{ $stats['work_efficiency'] >= 90 ? 'bg-success-light' : ($stats['work_efficiency'] >= 75 ? 'bg-warning-light' : 'bg-danger-light') }}">
                <div class="text-center">
                    <p class="text-3xl font-bold {{ $stats['work_efficiency'] >= 90 ? 'text-success' : ($stats['work_efficiency'] >= 75 ? 'text-warning' : 'text-danger') }}">{{ $stats['work_efficiency'] }}%</p>
                    <p class="text-xs font-semibold mt-1">Work Efficiency</p>
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

        <!-- Attendance Table -->
        <div class="panel">
            <div class="mb-4 flex items-center justify-between border-b pb-3">
                <h3 class="text-lg font-semibold">Detailed Attendance Records</h3>
                <p class="text-sm text-gray-600">Total: {{ $attendances->count() }} records</p>
            </div>

            <div class="table-responsive">
                <table class="table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Late Status</th>
                            <th>Regular Hours</th>
                            <th>Overtime</th>
                            <th>Total Hours</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr class="{{ $attendance->is_late ? 'bg-warning-light' : '' }}">
                                <td>{{ $attendance->attendance_date->format('d M Y') }}</td>
                                <td>{{ $attendance->attendance_date->format('l') }}</td>
                                <td>
                                    {{ $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : '-' }}
                                    @if($attendance->is_late)
                                        <span class="badge bg-danger text-xs ml-1">LATE</span>
                                    @endif
                                </td>
                                <td>{{ $attendance->check_out_time ? $attendance->check_out_time->format('H:i') : '-' }}</td>
                                <td>
                                    @if($attendance->is_late)
                                        <span class="text-danger font-semibold">{{ $attendance->late_status }}</span>
                                    @else
                                        <span class="text-success">On Time</span>
                                    @endif
                                </td>
                                <td>{{ number_format($attendance->regular_hours ?? 0, 2) }}h</td>
                                <td>
                                    @if(($attendance->overtime_hours ?? 0) > 0)
                                        <span class="badge bg-warning">{{ number_format($attendance->overtime_hours, 2) }}h</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><strong>{{ number_format($attendance->total_hours ?? 0, 2) }}h</strong></td>
                                <td>
                                    @if($attendance->status == 'present')
                                        <span class="badge bg-success">Present</span>
                                    @elseif($attendance->status == 'absent')
                                        <span class="badge bg-danger">Absent</span>
                                    @elseif($attendance->status == 'half_day')
                                        <span class="badge bg-warning">Half Day</span>
                                    @elseif($attendance->status == 'leave')
                                        <span class="badge bg-info">Leave</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($attendance->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $attendance->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-8 text-gray-500">No attendance records found for this period</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($attendances->count() > 0)
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td colspan="5" class="!text-right font-bold text-gray-700 dark:text-gray-300 py-4" style="padding-left: 18px">
                                    <span class="text-base">Totals:</span>
                                </td>
                                <td class="font-bold text-gray-900 dark:text-white py-4">
                                    <span class="text-base">{{ number_format($stats['regular_hours'], 2) }}h</span>
                                </td>
                                <td class="py-4">
                                    @if($stats['overtime_hours'] > 0)
                                        <span class="badge bg-warning font-bold">{{ number_format($stats['overtime_hours'], 2) }}h</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-4">
                                    <span class="text-base font-bold text-primary">{{ number_format($stats['total_hours'], 2) }}h</span>
                                </td>
                                <td colspan="2" class="py-4"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
