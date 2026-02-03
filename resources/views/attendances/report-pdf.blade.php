<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        * { margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }

        .page-break { page-break-after: always; margin-bottom: 40px; }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
            page-break-after: avoid;
        }

        .header h1 { margin: 10px 0 5px 0; font-size: 20px; color: #333; }
        .header p  { margin: 3px 0; font-size: 11px; color: #666; }

        .header-logo { margin-bottom: 10px; }
        .header-logo img { width: 80px; height: 80px; display: block; margin: 0 auto; }

        .summary {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            page-break-inside: avoid;
        }

        .summary h3 { margin: 0 0 10px 0; font-size: 13px; color: #333; font-weight: bold; }

        .summary-row { display: table; width: 100%; border-collapse: collapse; }

        .summary-row-item {
            display: table-cell;
            padding: 10px;
            background: white;
            border: 1px solid #dee2e6;
            text-align: center;
            vertical-align: middle;
            width: 12.5%;
        }

        .summary-row-item strong {
            display: block;
            color: #666;
            font-size: 8px;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .summary-row-item span { font-size: 16px; font-weight: bold; color: #333; }

        .employee-section { margin-bottom: 40px; page-break-inside: avoid; }

        .employee-header {
            background: #2c3e50;
            color: white;
            padding: 12px 15px;
            margin-bottom: 15px;
            page-break-after: avoid;
            border-radius: 4px;
        }

        .employee-header h2 { font-size: 14px; margin: 0; font-weight: bold; }
        .employee-header p  { font-size: 10px; margin: 3px 0 0 0; color: #ecf0f1; }

        .employee-summary {
            background: #ecf0f1;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
            page-break-inside: avoid;
        }

        .employee-summary-row { display: table; width: 100%; border-collapse: collapse; }
        .employee-summary-item { display: table-cell; vertical-align: top; padding: 6px 8px; width: 14.28%; }

        .employee-summary-item label {
            font-size: 9px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
            margin-bottom: 3px;
        }

        .employee-summary-item span {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        table thead { background: #34495e; color: white; }

        table th {
            padding: 10px 6px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            border: 1px solid #2c3e50;
        }

        table td {
            padding: 8px 6px;
            border: 1px solid #ecf0f1;
            font-size: 9px;
        }

        table tbody tr:nth-child(even) { background: #f8f9fa; }

        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
            text-transform: uppercase;
        }

        .status-present   { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-absent    { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-half_day  { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-leave     { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .status-holiday   { background: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            margin-left: 4px;
            font-weight: bold;
        }

        .badge-vac {
            background: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            margin-left: 4px;
            font-weight: bold;
            border: 1px solid #ffeaa7;
        }

        .time-cell { text-align: center; }
        .hours-cell { text-align: right; font-weight: bold; }

        tr.vacation-row td {
            background: #fff3cd !important;
            border-color: #ffeaa7 !important;
            color: #856404;
        }
        tr.absent-row td {
            background: #f8d7da !important;
            border-color: #f5c6cb !important;
            color: #721c24;
        }
        tr.leave-row td {
            background: #f8d7da !important;
            border-color: #f5c6cb !important;
            color: #721c24;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #7f8c8d;
            border-top: 2px solid #bdc3c7;
            padding-top: 10px;
            page-break-before: avoid;
        }

        .footer p { margin: 3px 0; }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>

<body>

    <!-- MAIN HEADER -->
    <div class="header">
        <div class="header-logo">
            <img src="https://hrm.voltronix.ae/assets/images/logo/main-logo.jpg" alt="Logo">
        </div>
        <h1>Attendance Report</h1>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} to {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}</p>
        <p><strong>Total Records:</strong> {{ $total_records }} | <strong>Employees:</strong> {{ count($report_data) }}</p>
    </div>

    <!-- OVERALL SUMMARY -->
    @if($total_employees > 1)
    <div class="summary">
        <h3>Overall Summary Statistics</h3>
        <div class="summary-row">
            <div class="summary-row-item"><strong>Present</strong><span style="color:#27ae60;">{{ $summary['total_present'] }}</span></div>
            <div class="summary-row-item"><strong>Absent</strong><span style="color:#e74c3c;">{{ $summary['total_absent'] }}</span></div>
            <div class="summary-row-item"><strong>Half Day</strong><span style="color:#f39c12;">{{ $summary['total_half_day'] }}</span></div>
            <div class="summary-row-item"><strong>Leave</strong><span style="color:#3498db;">{{ $summary['total_leave'] }}</span></div>
            <div class="summary-row-item"><strong>Holiday</strong><span style="color:#95a5a6;">{{ $summary['total_holiday'] }}</span></div>
            <div class="summary-row-item"><strong>Extra Days</strong><span style="color:#16a085;">{{ $summary['total_extra_days'] ?? 0 }}</span></div>
            <div class="summary-row-item"><strong>Total Hours</strong><span style="color:#2c3e50;">{{ $summary['total_hours'] }}</span></div>
            <div class="summary-row-item"><strong>Overtime</strong><span style="color:#e67e22;">{{ $summary['total_overtime_hours'] }}</span></div>
        </div>
    </div>
    @endif

    <!-- EMPLOYEE-WISE SECTIONS -->
    @forelse($report_data as $empData)
        @php
            $employee = $empData['employee'];
            $summary = $empData['summary'];
            $rows = $empData['rows'];
            $hasRows = !empty($rows);
        @endphp

        <div class="employee-section">
            <div class="employee-header">
                <h2>{{ $employee->employee_name }}</h2>
                <p>Staff ID: {{ $employee->staff_number }} | Designation: {{ $employee->designation ?? 'N/A' }} | Entity: {{ $employee->entity->entity_name ?? 'N/A' }}</p>
            </div>

            <div class="employee-summary">
                <div class="employee-summary-row">
                    <div class="employee-summary-item"><label>Present</label><span style="color:#27ae60;">{{ $summary['present'] }}</span></div>
                    <div class="employee-summary-item"><label>Absent</label><span style="color:#e74c3c;">{{ $summary['absent'] }}</span></div>
                    <div class="employee-summary-item"><label>Half Day</label><span style="color:#f39c12;">{{ $summary['half_day'] }}</span></div>
                    <div class="employee-summary-item"><label>Leave</label><span style="color:#3498db;">{{ $summary['leave'] }}</span></div>
                    <div class="employee-summary-item"><label>Extra Days</label><span style="color:#16a085;">{{ $summary['extra_days'] }}</span></div>
                    <div class="employee-summary-item"><label>Total Hours</label><span style="color:#2c3e50;">{{ $summary['total_hours'] }}</span></div>
                    <div class="employee-summary-item"><label>Overtime</label><span style="color:#e67e22;">{{ $summary['overtime_hours'] }}</span></div>
                </div>
            </div>

            @if($hasRows)
                <table>
                    <thead>
                        <tr>
                            <th style="width: 14%;">Date</th>
                            <th style="width: 14%;">Day</th>
                            <th style="width: 12%;">Check In</th>
                            <th style="width: 12%;">Check Out</th>
                            <th style="width: 12%;">Regular</th>
                            <th style="width: 12%;">Overtime</th>
                            <th style="width: 12%;">Total</th>
                            <th style="width: 12%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr class="{{ $row['row_class'] }}">
                                <td>{{ $row['date_formatted'] }}</td>
                                <td>{{ $row['day_name'] }}</td>
                                <td class="time-cell">{{ $row['check_in'] }}</td>
                                <td class="time-cell">
                                    {{ $row['check_out'] }}
                                    @if($row['is_next_day_out'])
                                        <span class="badge-info">NEXT DAY</span>
                                    @endif
                                </td>
                                <td class="hours-cell">{{ $row['regular_hours'] }}</td>
                                <td class="hours-cell">{{ $row['overtime_hours'] }}</td>
                                <td class="hours-cell"><strong>{{ $row['total_hours'] }}</strong></td>
                                <td>
                                    <span class="status-badge {{ $row['status_class'] }}">
                                        {{ $row['status_label'] }}
                                    </span>
                                    @if($row['is_extra_day'])
                                        <span class="badge-info">EXTRA</span>
                                    @endif
                                    @if($row['is_vacation'])
                                        <span class="badge-vac">VACATION</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-message">
                    <p>No attendance/vacation records for this period</p>
                </div>
            @endif
        </div>

    @empty
        <div class="empty-message">
            <p>No attendance data available for the selected period.</p>
        </div>
    @endforelse

    <!-- FOOTER -->
    <div class="footer">
        <p><strong>Generated on:</strong> {{ now()->format('d M Y h:i:s A') }} | <strong>© {{ date('Y') }} Voltronix HRM System</strong></p>
        <p>Working Days: Mon-Sat (8:00 AM - 6:00 PM) | Extra Days: Sundays + Holidays worked | Overtime: After 6:00 PM + Extra Days</p>
    </div>
</body>
</html>
