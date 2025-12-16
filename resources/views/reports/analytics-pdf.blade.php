<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Attendance Analytics Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8px;
            margin: 8px;
            color: #333;
            line-height: 1.15;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
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

        .header-content h2 {
            margin: 0 0 2px 0;
            font-size: 14px;
        }

        .header-content p {
            margin: 1px 0;
            font-size: 7px;
            color: #666;
        }

        .team-efficiency {
            text-align: center;
            background: #f8f9fa;
            padding: 6px;
            margin-bottom: 8px;
            border: 2px solid #dee2e6;
            border-radius: 3px;
        }

        .team-efficiency .score {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .team-efficiency .label {
            font-size: 9px;
            font-weight: bold;
        }

        .team-efficiency .note {
            font-size: 6px;
            color: #666;
            margin-top: 1px;
        }

        .summary {
            background: #f8f9fa;
            padding: 5px;
            margin-bottom: 8px;
            border: 1px solid #dee2e6;
        }

        .summary h3 {
            margin: 0 0 4px 0;
            font-size: 9px;
        }

        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .summary-row {
            display: table-row;
        }

        .summary-item {
            display: table-cell;
            padding: 3px 1px;
            background: white;
            text-align: center;
            border: 1px solid #dee2e6;
            font-size: 6px;
        }

        .summary-item strong {
            display: block;
            font-size: 10px;
            margin-bottom: 1px;
        }

        .summary-item p {
            font-size: 5px;
            color: #666;
        }

        .employee-section {
            margin-bottom: 6px;
            border: 1px solid #dee2e6;
            break-inside: avoid;
        }

        .employee-header {
            background: #343a40;
            color: white;
            padding: 3px 5px;
            font-size: 7px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .employee-header strong {
            font-size: 8px;
        }

        .employee-header .efficiency {
            font-size: 8px;
            font-weight: bold;
        }

        .employee-body {
            padding: 3px;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 3px;
            border-collapse: collapse;
        }

        .stats-row {
            display: table-row;
        }

        .stat-box {
            display: table-cell;
            padding: 2px 1px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            font-size: 6px;
        }

        .stat-box strong {
            display: block;
            font-size: 8px;
            margin-bottom: 0px;
            line-height: 1;
        }

        .stat-box p {
            font-size: 5px;
            color: #666;
            line-height: 1;
        }

        .dates-section {
            margin-top: 3px;
        }

        .date-group {
            margin-bottom: 2px;
            padding: 2px;
            background: #f8f9fa;
            border-radius: 2px;
        }

        .date-group h5 {
            margin: 0;
            font-size: 5px;
            font-weight: bold;
            line-height: 1.1;
        }

        .badge {
            display: inline-block;
            padding: 0px 2px;
            margin: 0.5px;
            border-radius: 2px;
            font-size: 5px;
            line-height: 1;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .footer {
            margin-top: 10px;
            text-align: center;
            font-size: 5px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 4px;
        }

        .page-break {
            page-break-after: always;
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

        @media print {
            body {
                margin: 0;
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-logo">
            <img src="https://hrm.voltronix.ae/assets/images/logo/main-logo.jpg" alt="Logo">
        </div>
        <div class="header-content">
            <h2>Attendance Analytics Report</h2>
            <p>{{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} to
                {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }} | Active Employees: {{ $active_employee_count }}
                | Records: {{ count($employee_reports ?? []) }}</p>
            <p>Working Days: {{ $total_working_days }} (Mon-Sat) | Extra Days: {{ $total_extra_days ?? 0 }} (Sun+Holidays) | Generated: {{ now()->format('d M Y, h:i A') }}</p>
        </div>
    </div>

    <div class="team-efficiency">
        <div
            class="score {{ $team_efficiency >= 90 ? 'color-success' : ($team_efficiency >= 75 ? 'color-warning' : 'color-danger') }}">
            {{ $team_efficiency }}%
            @if($team_efficiency > 100)
                <span style="font-size: 14px;">⭐</span>
            @endif
        </div>
        <div class="label">Overall Team Efficiency</div>
        <div class="note">Across {{ $active_employee_count }} active employees (excluding admins) • Maximum: 110% with bonuses</div>
    </div>

    <div class="summary">
        <h3>Summary Statistics</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-item">
                    <strong class="color-success">{{ $summary['total_present'] }}</strong>
                    <p>Present</p>
                </div>
                <div class="summary-item">
                    <strong>{{ $summary['total_half_day'] }}</strong>
                    <p>Half Day</p>
                </div>
                <div class="summary-item">
                    <strong class="color-success">{{ $total_approved_leaves }}</strong>
                    <p>Approved Leaves</p>
                </div>
                <div class="summary-item">
                    <strong class="color-danger">{{ $total_unapproved_absences }}</strong>
                    <p>Unapproved Absences</p>
                </div>
                <div class="summary-item">
                    <strong class="color-warning">{{ $total_late_entries }}</strong>
                    <p>Late Entries</p>
                </div>
                <div class="summary-item">
                    <strong class="color-info">{{ $total_extra_days ?? 0 }}</strong>
                    <p>Extra Days</p>
                </div>
                <div class="summary-item">
                    <strong>{{ $total_required_hours_formatted ?? '0h 00m' }}</strong>
                    <p>Required Hours</p>
                </div>
                <div class="summary-item">
                    <strong class="color-info">{{ $total_actual_hours_formatted ?? '0h 00m' }}</strong>
                    <p>Actual Hours</p>
                </div>
                <div class="summary-item">
                    <strong class="color-warning">{{ $summary['total_overtime_hours_formatted'] ?? '0h 00m' }}</strong>
                    <p>Overtime</p>
                </div>
            </div>
        </div>
    </div>

    @php $page_height = 0; @endphp
    @foreach ($employee_reports as $index => $report)
        @php
            // Estimate height of employee section (~30px)
            $section_height = 30;
            $max_page_height = 210; // A4 height in mm, adjusted for margins

            if ($page_height + $section_height > $max_page_height) {
                $page_height = 0;
            } else {
                $page_height += $section_height;
            }
        @endphp

        <div class="employee-section">
            <div class="employee-header">
                <div>
                    <strong>{{ $report['employee']->employee_name }}</strong>
                    ({{ $report['employee']->staff_number }})
                </div>
                <div class="efficiency">
                    Eff: {{ $report['work_efficiency'] }}%
                    @if($report['work_efficiency'] > 100)
                        ⭐
                    @endif
                </div>
            </div>
            <div class="employee-body">
                <div class="stats-grid">
                    <div class="stats-row">
                        <div class="stat-box">
                            <strong>{{ $report['present_count'] }}/{{ $report['total_working_days'] }}</strong>
                            <p>Present/Work Days</p>
                        </div>
                        <div class="stat-box">
                            <strong class="color-success">{{ $report['attendance_percentage'] }}%</strong>
                            <p>Attendance%</p>
                        </div>
                        <div class="stat-box">
                            <strong class="color-danger">{{ $report['absent_count'] }}</strong>
                            <p>Absent</p>
                        </div>
                        <div class="stat-box">
                            <strong class="color-info">{{ $report['leave_count'] }}</strong>
                            <p>Leave</p>
                        </div>
                        <div class="stat-box">
                            <strong class="color-warning">{{ $report['late_count'] }}</strong>
                            <p>Late</p>
                        </div>
                        <div class="stat-box">
                            <strong class="color-info">{{ $report['extra_days_count'] ?? 0 }}</strong>
                            <p>Extra Days</p>
                            <p style="font-size: 4px;">({{ $report['extra_days_hours_formatted'] ?? '0h 00m' }})</p>
                        </div>
                        <div class="stat-box">
                            <strong>{{ $report['total_hours_formatted'] ?? '0h 00m' }}</strong>
                            <p>/ {{ $report['required_hours_formatted'] ?? '0h 00m' }}</p>
                            <p style="font-size: 4px;">Actual/Required</p>
                        </div>
                        <div class="stat-box">
                            <strong class="color-warning">{{ $report['overtime_hours_formatted'] ?? '0h 00m' }}</strong>
                            <p>Overtime</p>
                        </div>
                    </div>
                </div>

                @if (count($report['present_dates']) > 0 || count($report['absent_dates']) > 0 || count($report['leave_dates']) > 0)
                    <div class="dates-section">
                        @if (count($report['present_dates']) > 0)
                            <div class="date-group">
                                <h5 class="color-success">✓ {{ count($report['present_dates']) }} Present:</h5>
                                @foreach ($report['present_dates'] as $date)
                                    <span class="badge badge-success">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if (count($report['absent_dates']) > 0)
                            <div class="date-group">
                                <h5 class="color-danger">✗ {{ count($report['absent_dates']) }} Absent:</h5>
                                @foreach ($report['absent_dates'] as $date)
                                    <span class="badge badge-danger">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if (count($report['leave_dates']) > 0)
                            <div class="date-group">
                                <h5 class="color-info">⊙ {{ count($report['leave_dates']) }} Leave:</h5>
                                @foreach ($report['leave_dates'] as $date)
                                    <span class="badge badge-info">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <div class="footer">
        <p><strong>© {{ date('Y') }} Voltronix HRM System</strong> | HR Department</p>
        <p>Working Days: Mon-Sat (8:00 AM - 6:00 PM) | Extra Days: Sundays + Holidays worked | OT: After 6:00 PM + Extra Days | Late: After 8:00 AM</p>
        <p>Efficiency Calculation: Attendance (40%) + Work Hours (30%) + Punctuality (20%) - Absences (10%) + Overtime Bonus (5%) + Extra Days Bonus (5%)</p>
    </div>
</body>

</html>
