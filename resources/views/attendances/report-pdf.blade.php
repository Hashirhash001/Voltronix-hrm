<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 5px 0;
            color: #333;
        }
        .header-logo {
            margin: 0 auto 5px auto;
        }
        .header-logo img {
            width: 100px;
            height: 100px;
            display: block;
            margin: 0 auto;
        }
        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        /* ✅ ROW FORMAT SUMMARY */
        .summary-row {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .summary-row-item {
            display: table-cell;
            padding: 10px;
            background: white;
            border: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
        }
        .summary-row-item strong {
            display: block;
            color: #666;
            font-size: 8px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-row-item span {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .date-group {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .date-header {
            background: #333;
            color: white;
            padding: 8px 10px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th {
            background: #f0f0f0;
            padding: 8px 5px;
            text-align: left;
            font-size: 10px;
            border-bottom: 2px solid #ddd;
        }
        table td {
            padding: 6px 5px;
            border-bottom: 1px solid #eee;
            font-size: 9px;
        }
        .status-badge {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-half_day { background: #fff3cd; color: #856404; }
        .status-leave { background: #d1ecf1; color: #0c5460; }
        .status-holiday { background: #e2e3e5; color: #383d41; }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 7px;
            margin-left: 3px;
        }
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <img src="https://hrm.voltronix.ae/assets/images/logo/main-logo.jpg" alt="Logo">
        </div>
        <h2>Attendance Report</h2>
        <p>Period: {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} to {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</p>
        <p>Total Records: {{ $total_records }} | Extra Days Worked: {{ $summary['total_extra_days'] ?? 0 }}</p>
    </div>

    <div class="summary">
        <h3>Summary Statistics</h3>
        <!-- ✅ ROW FORMAT SUMMARY -->
        <div class="summary-row">
            <div class="summary-row-item">
                <strong>Present</strong>
                <span style="color: #28a745;">{{ $summary['total_present'] }}</span>
            </div>
            <div class="summary-row-item">
                <strong>Absent</strong>
                <span style="color: #dc3545;">{{ $summary['total_absent'] }}</span>
            </div>
            <div class="summary-row-item">
                <strong>Half Day</strong>
                <span style="color: #ffc107;">{{ $summary['total_half_day'] }}</span>
            </div>
            <div class="summary-row-item">
                <strong>Leave</strong>
                <span style="color: #17a2b8;">{{ $summary['total_leave'] }}</span>
            </div>
            <div class="summary-row-item">
                <strong>Holiday</strong>
                <span style="color: #6c757d;">{{ $summary['total_holiday'] }}</span>
            </div>
            <div class="summary-row-item">
                <strong>Extra Days</strong>
                <span style="color: #17a2b8;">{{ $summary['total_extra_days'] ?? 0 }}</span>
            </div>
            <div class="summary-row-item">
                <strong>Total Hours</strong>
                <span style="color: #333;">{{ number_format($summary['total_hours'] ?? 0, 2) }}h</span>
            </div>
            <div class="summary-row-item">
                <strong>Overtime</strong>
                <span style="color: #ffc107;">{{ number_format($summary['total_overtime_hours'] ?? 0, 2) }}h</span>
            </div>
        </div>
    </div>

    @foreach($attendances as $date => $dateAttendances)
        <div class="date-group">
            <div class="date-header">
                {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Staff No</th>
                        <th style="width: 22%;">Employee</th>
                        <th style="width: 8%;">Check In</th>
                        <th style="width: 8%;">Check Out</th>
                        <th style="width: 8%;">Regular</th>
                        <th style="width: 8%;">Overtime</th>
                        <th style="width: 8%;">Total</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 15%;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dateAttendances as $attendance)
                        @php
                            $isSunday = $attendance->attendance_date->dayOfWeek === \Carbon\Carbon::SUNDAY;
                            $isHoliday = $attendance->status === 'holiday';
                            $isExtraDay = ($isSunday || $isHoliday) && in_array($attendance->status, ['present', 'half_day']);
                        @endphp
                        <tr>
                            <td>{{ $attendance->staff_number }}</td>
                            <td>{{ $attendance->employee->employee_name ?? 'N/A' }}</td>
                            <td>{{ $attendance->getFormattedCheckInTime() }}</td>
                            <td>{{ $attendance->getFormattedCheckOutTime() }}</td>
                            <td>{{ number_format($attendance->regular_hours ?? 0, 2) }}h</td>
                            <td>{{ number_format($attendance->overtime_hours ?? 0, 2) }}h</td>
                            <td><strong>{{ number_format($attendance->total_hours ?? 0, 2) }}h</strong></td>
                            <td>
                                @if($isExtraDay)
                                    <span class="status-badge status-{{ $attendance->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $attendance->status)) }}
                                    </span>
                                    <span class="badge-info">EXTRA</span>
                                @elseif($isSunday && $attendance->status === 'absent')
                                    <span class="status-badge badge-secondary">Sunday (Off)</span>
                                @else
                                    <span class="status-badge status-{{ $attendance->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $attendance->status)) }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $attendance->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <p>Generated on {{ now()->format('d M Y H:i:s') }} | © {{ date('Y') }} Voltronix HRM System</p>
        <p>Working Days: Mon-Sat (8:00 AM - 6:00 PM) | Extra Days: Sundays + Holidays worked | Overtime: After 6:00 PM + Extra Days</p>
    </div>
</body>
</html>
