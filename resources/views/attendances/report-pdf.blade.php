<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .summary-item {
            padding: 8px;
            background: white;
            border-left: 3px solid #4CAF50;
        }
        .summary-item strong {
            display: block;
            color: #666;
            font-size: 9px;
            text-transform: uppercase;
        }
        .summary-item span {
            font-size: 16px;
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
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <img src="https://hrm.voltronix.ae/assets/images/logo/main-logo.jpg" alt="Logo">
        </div>
        <h2>Attendance Report</h2>
        <p>Period: {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} to {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</p>
        <p>Total Records: {{ $total_records }}</p>
    </div>

    <div class="summary">
        <h3 style="margin-top: 0;">Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <strong>Present</strong>
                <span style="color: #28a745;">{{ $summary['total_present'] }}</span>
            </div>
            <div class="summary-item">
                <strong>Absent</strong>
                <span style="color: #dc3545;">{{ $summary['total_absent'] }}</span>
            </div>
            <div class="summary-item">
                <strong>Half Day</strong>
                <span style="color: #ffc107;">{{ $summary['total_half_day'] }}</span>
            </div>
            <div class="summary-item">
                <strong>Leave</strong>
                <span style="color: #17a2b8;">{{ $summary['total_leave'] }}</span>
            </div>
            <div class="summary-item">
                <strong>Holiday</strong>
                <span style="color: #6c757d;">{{ $summary['total_holiday'] }}</span>
            </div>
            <div class="summary-item">
                <strong>Total Hours</strong>
                <span style="color: #333;">{{ number_format($summary['total_regular_hours'], 2) }}h</span>
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
                        <th style="width: 10%;">Staff No</th>
                        <th style="width: 25%;">Employee</th>
                        <th style="width: 10%;">Check In</th>
                        <th style="width: 10%;">Check Out</th>
                        <th style="width: 10%;">Regular</th>
                        <th style="width: 10%;">Overtime</th>
                        <th style="width: 10%;">Total</th>
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dateAttendances as $attendance)
                        <tr>
                            <td>{{ $attendance->staff_number }}</td>
                            <td>{{ $attendance->employee->employee_name ?? 'N/A' }}</td>
                            <td>{{ $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : '-' }}</td>
                            <td>{{ $attendance->check_out_time ? $attendance->check_out_time->format('H:i') : '-' }}</td>
                            <td>{{ number_format($attendance->regular_hours ?? 0, 2) }}h</td>
                            <td>{{ number_format($attendance->overtime_hours ?? 0, 2) }}h</td>
                            <td><strong>{{ number_format($attendance->total_hours ?? 0, 2) }}h</strong></td>
                            <td>
                                <span class="status-badge status-{{ $attendance->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $attendance->status)) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <p>Generated on {{ now()->format('d M Y H:i:s') }} | © {{ date('Y') }} Voltronix HRM System</p>
    </div>
</body>
</html>
