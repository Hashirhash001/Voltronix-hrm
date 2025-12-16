<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Employee Attendance Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            margin: 10px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }

        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        .header p {
            margin: 2px 0;
            font-size: 8px;
            color: #666;
        }

        .header-logo {
            margin: 0 auto 2px auto;
        }

        .header-logo img {
            width: 100px;
            height: 100px;
            display: block;
            margin: 0 auto;
        }

        .stats {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .stats-row {
            display: table-row;
        }

        .stat-item {
            display: table-cell;
            padding: 8px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        .stat-item strong {
            display: block;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .stat-item p {
            font-size: 7px;
            color: #666;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .table th {
            background: #343a40;
            color: white;
            padding: 6px 3px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #dee2e6;
        }

        .table td {
            padding: 4px 3px;
            border: 1px solid #dee2e6;
            font-size: 7px;
        }

        .table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 2px;
            font-size: 6px;
            margin: 1px;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }

        .color-success {
            color: #28a745;
        }

        .color-danger {
            color: #dc3545;
        }

        .color-warning {
            color: #ffc107;
        }

        .color-info {
            color: #17a2b8;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-logo">
            <img src="https://hrm.voltronix.ae/assets/images/logo/small_logo.jpg" alt="Logo">
        </div>
        <div class="header-content">
            <h2>Employee Attendance Report</h2>
            <p>{{ $employee->employee_name }} ({{ $employee->staff_number }})</p>
            <p>{{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} to
                {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }} | Generated: {{ now()->format('d M Y, h:i A') }}
            </p>
        </div>
    </div>

    <div class="stats">
        <div class="stats-row">
            <div class="stat-item">
                <strong class="color-success">{{ $stats['attendance_percentage'] }}%</strong>
                <p>Attendance Rate</p>
            </div>
            <div class="stat-item">
                <strong class="color-success">{{ $stats['present'] }}/{{ $stats['total_working_days'] }}</strong>
                <p>Present/Working Days</p>
                <p style="font-size: 6px;">(Mon-Sat only)</p>
            </div>
            <div class="stat-item">
                <strong class="color-danger">{{ $stats['absent'] }}</strong>
                <p>Absent Days</p>
            </div>
            <div class="stat-item">
                <strong class="color-warning">{{ $stats['late_count'] }}</strong>
                <p>Late Entries</p>
            </div>
            <div class="stat-item">
                <strong class="color-info">{{ $stats['extra_days_worked'] ?? 0 }}</strong>
                <p>Extra Days Worked</p>
                @php
                    $edh = floor($stats['extra_days_hours'] ?? 0);
                    $edm = round((($stats['extra_days_hours'] ?? 0) - $edh) * 60);
                @endphp
                <p style="font-size: 6px;">({{ $edh }}h {{ $edm }}m)</p>
            </div>
            <div class="stat-item">
                @php
                    $th = floor($stats['total_hours']);
                    $tm = round(($stats['total_hours'] - $th) * 60);
                    $rh = floor($stats['required_work_hours']);
                    $rm = round(($stats['required_work_hours'] - $rh) * 60);
                @endphp
                <strong>{{ $th }}h {{ $tm }}m</strong>
                <p>/ {{ $rh }}h {{ $rm }}m</p>
                <p style="font-size: 6px;">Actual / Required</p>
            </div>
            <div class="stat-item">
                <strong class="{{ $stats['work_efficiency'] >= 90 ? 'color-success' : ($stats['work_efficiency'] >= 75 ? 'color-warning' : 'color-danger') }}">
                    {{ $stats['work_efficiency'] }}%
                </strong>
                <p>Work Efficiency</p>
                @if($stats['work_efficiency'] > 100)
                    <p style="font-size: 6px; color: #28a745;">⭐ Bonus!</p>
                @endif
            </div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Late Status</th>
                <th>Reg Hours</th>
                <th>OT Hours</th>
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
                <tr>
                    <td>{{ $attendance->attendance_date->format('d M Y') }}</td>
                    <td>
                        {{ $attendance->attendance_date->format('D') }}
                        @if($isExtraDay)
                            <span class="badge badge-info">EXTRA</span>
                        @endif
                    </td>
                    <td>{{ $attendance->getFormattedCheckInTime() }}</td>
                    <td>{{ $attendance->getFormattedCheckOutTime() }}</td>
                    <td>
                        @if($attendance->status == 'present' && !$isSunday && !$isHoliday)
                            @if($attendance->is_late)
                                <span class="color-danger">{{ $attendance->late_status }}</span>
                            @else
                                <span class="color-success">On Time</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @php
                            $regH = floor($attendance->regular_hours ?? 0);
                            $regM = round((($attendance->regular_hours ?? 0) - $regH) * 60);
                        @endphp
                        {{ $regH }}h {{ $regM }}m
                    </td>
                    <td>
                        @if(($attendance->overtime_hours ?? 0) > 0)
                            @php
                                $otH = floor($attendance->overtime_hours);
                                $otM = round(($attendance->overtime_hours - $otH) * 60);
                            @endphp
                            <span class="color-warning">{{ $otH }}h {{ $otM }}m</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @php
                            $totH = floor($attendance->total_hours ?? 0);
                            $totM = round((($attendance->total_hours ?? 0) - $totH) * 60);
                        @endphp
                        <strong>{{ $totH }}h {{ $totM }}m</strong>
                    </td>
                    <td>
                        @if($isExtraDay)
                            @if($attendance->status == 'present')
                                <span class="badge badge-info">Present (Extra)</span>
                            @elseif($attendance->status == 'half_day')
                                <span class="badge badge-info">Half Day (Extra)</span>
                            @endif
                        @elseif($attendance->status == 'present')
                            <span class="badge badge-success">Present</span>
                        @elseif($attendance->status == 'absent')
                            @if($isSunday)
                                <span class="badge badge-secondary">Sunday (Off)</span>
                            @else
                                <span class="badge badge-danger">Absent</span>
                            @endif
                        @elseif($attendance->status == 'half_day')
                            <span class="badge badge-warning">Half Day</span>
                        @elseif($attendance->status == 'leave')
                            <span class="badge badge-info">Leave</span>
                        @elseif($attendance->status == 'holiday')
                            <span class="badge badge-info">Holiday</span>
                        @else
                            <span class="badge badge-secondary">{{ ucfirst($attendance->status) }}</span>
                        @endif
                    </td>
                    <td>{{ $attendance->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px;">No attendance records found</td>
                </tr>
            @endforelse
        </tbody>

        {{-- Totals Row --}}
        @if($attendances->count() > 0)
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="5" style="text-align: right; padding-right: 5px;">Totals</td>
                    <td>
                        @php
                            $regH = floor($stats['regular_hours']);
                            $regM = round(($stats['regular_hours'] - $regH) * 60);
                        @endphp
                        {{ $regH }}h {{ $regM }}m
                    </td>
                    <td>
                        @php
                            $otH = floor($stats['overtime_hours']);
                            $otM = round(($stats['overtime_hours'] - $otH) * 60);
                        @endphp
                        <span class="color-warning">{{ $otH }}h {{ $otM }}m</span>
                    </td>
                    <td>
                        @php
                            $totH = floor($stats['total_hours']);
                            $totM = round(($stats['total_hours'] - $totH) * 60);
                        @endphp
                        <strong>{{ $totH }}h {{ $totM }}m</strong>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        <p><strong>© {{ date('Y') }} Voltronix HRM System</strong> | HR Department</p>
        <p>Working Days: Mon-Sat (8:00 AM - 6:00 PM) | Extra Days: Sundays + Holidays worked | OT: After 6:00 PM + Extra Days | Late: After 8:00 AM</p>
    </div>
</body>

</html>
