@extends('layouts.app')

@section('title', 'Employee Attendance Detail')

@section('content')
<div class="space-y-6">
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li><a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a></li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <a href="{{ route('reports.analytics') }}" class="text-primary hover:underline">Reports</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Employee Details</span></li>
    </ul>

    <div class="panel">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-semibold">{{ $employee->employee_name }}</h2>
                <p class="text-sm text-gray-600">
                    Staff Number: {{ $employee->staff_number }} |
                    Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </p>
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
                <a href="{{ route('reports.analytics', ['start_date' => $startDate, 'end_date' => $endDate, 'employee_id' => $employee->id]) }}"
                   class="btn btn-outline-secondary">
                    <svg class="h-5 w-5 mr-2 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="mb-6 space-y-4">
            <!-- Row 1: Main Metrics -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                <!-- Card 1: Attendance Rate -->
                <div class="panel bg-primary-light">
                    <div class="text-center py-4">
                        <p class="text-3xl font-bold text-primary">{{ $stats['attendance_percentage'] }}%</p>
                        <p class="text-xs font-semibold mt-1">Attendance Rate</p>
                    </div>
                </div>

                <!-- Card 2: Present / Working Days -->
                <div class="panel bg-success-light">
                    <div class="text-center py-4">
                        <p class="text-2xl font-bold text-success">{{ $stats['present'] }}/{{ $stats['total_working_days'] }}</p>
                        <p class="text-xs font-semibold mt-1">Present / Working Days</p>
                        <p class="text-xs text-gray-500 mt-0.5">(Mon-Sat only)</p>
                    </div>
                </div>

                <!-- Card 3: Absent Days -->
                <div class="panel bg-danger-light">
                    <div class="text-center py-4">
                        <p class="text-3xl font-bold text-danger">{{ $stats['absent'] }}</p>
                        <p class="text-xs font-semibold mt-1">Absent Days</p>
                    </div>
                </div>

                <!-- Card 4: Half Days -->
                <div class="panel bg-warning-light">
                    <div class="text-center py-4">
                        <p class="text-3xl font-bold text-warning">{{ $stats['half_day'] }}</p>
                        <p class="text-xs font-semibold mt-1">Half Days</p>
                    </div>
                </div>
            </div>

            <!-- Row 2: Additional Metrics -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                <!-- Card 5: Late Entries -->
                <div class="panel bg-warning-light">
                    <div class="text-center py-4">
                        <p class="text-3xl font-bold text-warning">{{ $stats['late_count'] }}</p>
                        <p class="text-xs font-semibold mt-1">Late Entries</p>
                    </div>
                </div>

                <!-- Card 6: Extra Days Worked -->
                <div class="panel bg-info-light">
                    <div class="text-center py-4">
                        <p class="text-3xl font-bold text-info">{{ $stats['extra_days_worked'] }}</p>
                        <p class="text-xs font-semibold mt-1">Extra Days Worked</p>
                        @php
                            $edh = floor($stats['extra_days_hours']);
                            $edm = round(($stats['extra_days_hours'] - $edh) * 60);
                        @endphp
                        <p class="text-xs text-gray-500 mt-0.5">({{ $edh }}h {{ $edm }}m)</p>
                    </div>
                </div>

                <!-- Card 7: Actual / Required Hours -->
                <div class="panel bg-secondary-light">
                    <div class="text-center py-4">
                        @php
                            $th = floor($stats['total_hours']);
                            $tm = round(($stats['total_hours'] - $th) * 60);
                            $rh = floor($stats['required_work_hours']);
                            $rm = round(($stats['required_work_hours'] - $rh) * 60);
                        @endphp
                        <p class="text-lg font-bold">{{ $th }}h {{ $tm }}m</p>
                        <p class="text-sm font-bold text-gray-600">/ {{ $rh }}h {{ $rm }}m</p>
                        <p class="text-xs font-semibold mt-1">Actual / Required Hours</p>
                    </div>
                </div>

                <!-- Card 8: Work Efficiency -->
                <div class="panel {{ $stats['work_efficiency'] >= 90 ? 'bg-success-light' : ($stats['work_efficiency'] >= 75 ? 'bg-warning-light' : 'bg-danger-light') }}">
                    <div class="text-center py-4">
                        <p class="text-3xl font-bold {{ $stats['work_efficiency'] >= 90 ? 'text-success' : ($stats['work_efficiency'] >= 75 ? 'text-warning' : 'text-danger') }}">
                            {{ $stats['work_efficiency'] }}%
                        </p>
                        <p class="text-xs font-semibold mt-1">Work Efficiency</p>
                        @if($stats['work_efficiency'] > 100)
                            <p class="text-xs text-success font-semibold mt-0.5">⭐ Bonus!</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>


        <!-- Work Efficiency Criteria Info -->
        <div class="mb-6 panel bg-gray-50 dark:bg-gray-800">
            <h4 class="mb-3 text-lg font-semibold">Work Efficiency Calculation Criteria</h4>

            <!-- Base Criteria (Top Row) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div class="p-4 bg-white dark:bg-gray-900 rounded">
                    <div class="flex items-center mb-2">
                        <div class="w-3 h-3 bg-primary rounded-full mr-2"></div>
                        <strong class="text-primary">Attendance Rate (40%)</strong>
                    </div>
                    <p class="text-xs text-gray-600">Present days ÷ Total working days<br>Higher attendance = Better score</p>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 rounded">
                    <div class="flex items-center mb-2">
                        <div class="w-3 h-3 bg-success rounded-full mr-2"></div>
                        <strong class="text-success">Work Hours (30%)</strong>
                    </div>
                    <p class="text-xs text-gray-600">Completed hours ÷ Required hours<br>Meeting quota = Full points</p>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 rounded">
                    <div class="flex items-center mb-2">
                        <div class="w-3 h-3 bg-warning rounded-full mr-2"></div>
                        <strong class="text-warning">Punctuality (20%)</strong>
                    </div>
                    <p class="text-xs text-gray-600">On-time arrivals ÷ Present days<br>No late entries = Full points</p>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 rounded">
                    <div class="flex items-center mb-2">
                        <div class="w-3 h-3 bg-danger rounded-full mr-2"></div>
                        <strong class="text-danger">Absence Impact (10%)</strong>
                    </div>
                    <p class="text-xs text-gray-600">2 points deducted per absence<br>Maximum 10 point penalty</p>
                </div>
            </div>

            <!-- Bonus Criteria (Bottom Row) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-white dark:bg-gray-900 rounded border-2 border-success">
                    <div class="flex items-center mb-2">
                        <div class="w-3 h-3 bg-success rounded-full mr-2"></div>
                        <strong class="text-success">⭐ Overtime Bonus (+5%)</strong>
                    </div>
                    <p class="text-xs text-gray-600">20+ overtime hours = +5%<br>Scaled bonus for less</p>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900 rounded border-2 border-info">
                    <div class="flex items-center mb-2">
                        <div class="w-3 h-3 bg-info rounded-full mr-2"></div>
                        <strong class="text-info">⭐ Extra Days Bonus (+5%)</strong>
                    </div>
                    <p class="text-xs text-gray-600">3+ extra days = +5%<br>Scaled bonus for less</p>
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-4 text-center border-t pt-3">
                <strong>Working Days:</strong> Mon-Sat (8:00 AM - 6:00 PM) |
                <strong>Extra Days:</strong> Sundays + Holidays worked |
                <strong>Maximum Efficiency:</strong> 110% (with full bonuses)
            </p>
        </div>

        <!-- Attendance Table -->
        <div class="panel">
            <div class="mb-4 flex items-center justify-between border-b pb-3">
                <h3 class="text-lg font-semibold">Detailed Attendance Records</h3>
                <p class="text-sm text-gray-600">Total {{ $attendances->count() }} records</p>
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
                            @php
                                $isSunday = $attendance->attendance_date->dayOfWeek === \Carbon\Carbon::SUNDAY;
                                $isHoliday = $attendance->status === 'holiday';
                                $isExtraDay = ($isSunday || $isHoliday) && in_array($attendance->status, ['present', 'half_day']);
                            @endphp

                            <tr class="{{ $attendance->is_late && $attendance->status === 'present' && !$isSunday ? 'bg-warning-light' : '' }}">
                                <td>{{ $attendance->attendance_date->format('d M Y') }}</td>

                                {{-- Day Column with Badge --}}
                                <td>
                                    {{ $attendance->attendance_date->format('l') }}
                                    @if($isExtraDay)
                                        <span class="badge bg-info text-xs ml-1">EXTRA DAY</span>
                                    @endif
                                </td>

                                {{-- Check In with AM/PM --}}
                                <td>
                                    {{ $attendance->getFormattedCheckInTime() }}
                                    @if($attendance->is_late && $attendance->status === 'present' && !$isSunday && !$isHoliday)
                                        <span class="badge bg-danger text-xs ml-1">LATE</span>
                                    @endif
                                </td>

                                {{-- Check Out with AM/PM --}}
                                <td>{{ $attendance->getFormattedCheckOutTime() }}</td>

                                {{-- Late Status - Only for regular working day presents --}}
                                <td>
                                    @if($attendance->status === 'present' && !$isSunday && !$isHoliday)
                                        @if($attendance->is_late)
                                            <span class="text-danger font-semibold">{{ $attendance->late_status }}</span>
                                        @else
                                            <span class="text-success">On Time</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>

                                {{-- Hours in h:mm format --}}
                                <td>
                                    @php
                                        $regH = floor($attendance->regular_hours ?? 0);
                                        $regM = round((($attendance->regular_hours ?? 0) - $regH) * 60);
                                    @endphp
                                    {{ $regH }}h {{ sprintf('%02d', $regM) }}m
                                </td>

                                <td>
                                    @if(($attendance->overtime_hours ?? 0) > 0)
                                        @php
                                            $otH = floor($attendance->overtime_hours);
                                            $otM = round(($attendance->overtime_hours - $otH) * 60);
                                        @endphp
                                        <span class="badge bg-warning">{{ $otH }}h {{ sprintf('%02d', $otM) }}m</span>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td>
                                    @php
                                        $totH = floor($attendance->total_hours ?? 0);
                                        $totM = round((($attendance->total_hours ?? 0) - $totH) * 60);
                                    @endphp
                                    <strong>{{ $totH }}h {{ sprintf('%02d', $totM) }}m</strong>
                                </td>

                                {{-- Status Column --}}
                                <td>
                                    @if($isExtraDay)
                                        {{-- Show present/half_day status for extra days --}}
                                        @if($attendance->status == 'present')
                                            <span class="badge bg-info">Present (Extra Day)</span>
                                        @elseif($attendance->status == 'half_day')
                                            <span class="badge bg-info">Half Day (Extra Day)</span>
                                        @endif
                                    @elseif($attendance->status == 'present')
                                        <span class="badge bg-success">Present</span>
                                    @elseif($attendance->status == 'absent')
                                        @if($isSunday)
                                            <span class="badge bg-secondary">Sunday (Off Day)</span>
                                        @else
                                            <span class="badge bg-danger">Absent</span>
                                        @endif
                                    @elseif($attendance->status == 'half_day')
                                        <span class="badge bg-warning">Half Day</span>
                                    @elseif($attendance->status == 'leave')
                                        <span class="badge bg-info">Leave</span>
                                    @elseif($attendance->status == 'holiday')
                                        <span class="badge bg-purple">Holiday</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($attendance->status) }}</span>
                                    @endif
                                </td>

                                <td>{{ $attendance->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-8 text-gray-500">
                                    No attendance records found for this period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    {{-- Totals Footer --}}
                    @if($attendances->count() > 0)
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td colspan="5" class="!text-right font-bold text-gray-700 dark:text-gray-300 py-4" style="padding-left: 18px">
                                    <span class="text-base">Totals</span>
                                </td>
                                <td class="font-bold text-gray-900 dark:text-white py-4">
                                    @php
                                        $regH = floor($stats['regular_hours']);
                                        $regM = round(($stats['regular_hours'] - $regH) * 60);
                                    @endphp
                                    <span class="text-base">{{ $regH }}h {{ sprintf('%02d', $regM) }}m</span>
                                </td>
                                <td class="py-4">
                                    @if($stats['overtime_hours'] > 0)
                                        @php
                                            $otH = floor($stats['overtime_hours']);
                                            $otM = round(($stats['overtime_hours'] - $otH) * 60);
                                        @endphp
                                        <span class="badge bg-warning font-bold">{{ $otH }}h {{ sprintf('%02d', $otM) }}m</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-4">
                                    @php
                                        $totH = floor($stats['total_hours']);
                                        $totM = round(($stats['total_hours'] - $totH) * 60);
                                    @endphp
                                    <span class="text-base font-bold text-primary">{{ $totH }}h {{ sprintf('%02d', $totM) }}m</span>
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
