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
            font-size: 10px;
            margin: 15px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 10px 0;
            font-size: 20px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            font-size: 11px;
            color: #666;
        }
        .summary {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #333;
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
            padding: 10px;
            background: white;
            text-align: center;
            border: 1px solid #dee2e6;
            vertical-align: middle;
        }
        .summary-item strong {
            display: block;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .summary-item p {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
        }
        .color-success { color: #28a745; }
        .color-danger { color: #dc3545; }
        .color-warning { color: #ffc107; }
        .color-info { color: #17a2b8; }
        .color-secondary { color: #6c757d; }

        .employee-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            overflow: hidden;
        }
        .employee-header {
            background: #343a40;
            color: white;
            padding: 10px 15px;
            font-size: 12px;
        }
        .employee-header strong {
            font-size: 13px;
        }
        .employee-body {
            padding: 15px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .stats-row {
            display: table-row;
        }
        .stat-box {
            display: table-cell;
            padding: 8px 5px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-left: 3px solid #4CAF50;
        }
        .stat-box strong {
            display: block;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .stat-box p {
            font-size: 7px;
            color: #666;
            text-transform: uppercase;
        }
        .stat-box.stat-primary { border-left-color: #007bff; }
        .stat-box.stat-success { border-left-color: #28a745; }
        .stat-box.stat-danger { border-left-color: #dc3545; }
        .stat-box.stat-warning { border-left-color: #ffc107; }
        .stat-box.stat-info { border-left-color: #17a2b8; }

        .dates-section {
            margin-top: 15px;
        }
        .date-group {
            margin-bottom: 10px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 3px;
        }
        .date-group h5 {
            margin: 0 0 5px 0;
            font-size: 9px;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 3px 7px;
            margin: 2px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Attendance Analytics Report</h2>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($start_date)->format('d F Y') }} to {{ \Carbon\Carbon::parse($end_date)->format('d F Y') }}</p>
        <p><strong>Total Records:</strong> {{ $total_records }}</p>
        <p><strong>Generated:</strong> {{ now()->format('d F Y, h:i A') }}</p>
    </div>

    <div class="summary">
        <h3>Overall Summary</h3>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-item">
                    <strong class="color-success">{{ $summary['total_present'] }}</strong>
                    <p>Total Present</p>
                </div>
                <div class="summary-item">
                    <strong class="color-danger">{{ $summary['total_absent'] }}</strong>
                    <p>Total Absent</p>
                </div>
                <div class="summary-item">
                    <strong class="color-warning">{{ $summary['total_half_day'] }}</strong>
                    <p>Half Days</p>
                </div>
                <div class="summary-item">
                    <strong class="color-info">{{ $summary['total_leave'] }}</strong>
                    <p>Leaves</p>
                </div>
                <div class="summary-item">
                    <strong class="color-secondary">{{ $summary['total_holiday'] }}</strong>
                    <p>Holidays</p>
                </div>
            </div>
        </div>
        <div style="margin-top: 10px; text-align: center;">
            <strong>Total Hours Worked:</strong> {{ number_format($summary['total_regular_hours'], 2) }}h
            <span style="margin: 0 15px;">|</span>
            <strong>Total Overtime:</strong> {{ number_format($summary['total_overtime_hours'], 2) }}h
        </div>
    </div>

    @foreach($employee_reports as $index => $report)
        <div class="employee-section">
            <div class="employee-header">
                <strong>{{ $report['employee']->employee_name }}</strong>
                (Staff No: {{ $report['employee']->staff_number }})
                <span style="float: right;">Attendance Rate: <strong>{{ $report['attendance_percentage'] }}%</strong></span>
            </div>

            <div class="employee-body">
                <div class="stats-grid">
                    <div class="stats-row">
                        <div class="stat-box stat-primary">
                            <strong>{{ $report['total_days'] }}</strong>
                            <p>Total Days</p>
                        </div>
                        <div class="stat-box stat-success">
                            <strong class="color-success">{{ $report['present_count'] }}</strong>
                            <p>Present</p>
                        </div>
                        <div class="stat-box stat-danger">
                            <strong class="color-danger">{{ $report['absent_count'] }}</strong>
                            <p>Absent</p>
                        </div>
                        <div class="stat-box stat-warning">
                            <strong class="color-warning">{{ $report['half_day_count'] }}</strong>
                            <p>Half Day</p>
                        </div>
                        <div class="stat-box stat-info">
                            <strong class="color-info">{{ $report['leave_count'] }}</strong>
                            <p>Leave</p>
                        </div>
                        <div class="stat-box">
                            <strong>{{ number_format($report['total_hours'], 1) }}h</strong>
                            <p>Total Hours</p>
                        </div>
                        <div class="stat-box stat-warning">
                            <strong class="color-warning">{{ number_format($report['overtime_hours'], 1) }}h</strong>
                            <p>Overtime</p>
                        </div>
                    </div>
                </div>

                <div class="dates-section">
                    @if(count($report['present_dates']) > 0)
                        <div class="date-group">
                            <h5 class="color-success">✓ Present Days ({{ count($report['present_dates']) }})</h5>
                            @foreach($report['present_dates'] as $date)
                                <span class="badge badge-success">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if(count($report['absent_dates']) > 0)
                        <div class="date-group">
                            <h5 class="color-danger">✗ Absent Days ({{ count($report['absent_dates']) }})</h5>
                            @foreach($report['absent_dates'] as $date)
                                <span class="badge badge-danger">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if(count($report['half_day_dates']) > 0)
                        <div class="date-group">
                            <h5 class="color-warning">◐ Half Days ({{ count($report['half_day_dates']) }})</h5>
                            @foreach($report['half_day_dates'] as $date)
                                <span class="badge badge-warning">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if(count($report['leave_dates']) > 0)
                        <div class="date-group">
                            <h5 class="color-info">⊙ Leave Days ({{ count($report['leave_dates']) }})</h5>
                            @foreach($report['leave_dates'] as $date)
                                <span class="badge badge-info">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if(count($report['holiday_dates']) > 0)
                        <div class="date-group">
                            <h5 class="color-secondary">☼ Holiday Days ({{ count($report['holiday_dates']) }})</h5>
                            @foreach($report['holiday_dates'] as $date)
                                <span class="badge badge-info">{{ \Carbon\Carbon::parse($date)->format('d M') }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if(($index + 1) % 2 == 0 && ($index + 1) < count($employee_reports))
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="footer">
        <p><strong>© {{ date('Y') }} Voltronix HRM System</strong> | Generated on {{ now()->format('d F Y, h:i:s A') }}</p>
        <p>This is a system-generated report. For any queries, please contact HR Department.</p>
    </div>
</body>
</html>
