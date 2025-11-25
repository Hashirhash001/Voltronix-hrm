<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function analytics(Request $request)
    {
        $employees = Employee::where('status', 'active')->orderBy('employee_name')->get();

        // Default date range: current month
        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $employeeId = $request->employee_id;

        $reportData = null;

        if ($request->has('generate') || $request->ajax()) {
            $reportData = $this->generateReport($startDate, $endDate, $employeeId);
        }

        if ($request->ajax()) {
            return response()->json($reportData);
        }

        return view('reports.analytics', compact('employees', 'startDate', 'endDate', 'employeeId', 'reportData'));
    }

    private function generateReport($startDate, $endDate, $employeeId = null)
    {
        $query = Attendance::with('employee')
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $attendances = $query->orderBy('attendance_date', 'asc')->get();

        // Group by employee
        $employeeGroups = $attendances->groupBy('employee_id');

        $employeeReports = [];

        foreach ($employeeGroups as $empId => $empAttendances) {
            $employee = $empAttendances->first()->employee;

            $presentDates = [];
            $absentDates = [];
            $halfDayDates = [];
            $leaveDates = [];
            $holidayDates = [];

            foreach ($empAttendances as $att) {
                $date = $att->attendance_date->format('Y-m-d');
                switch ($att->status) {
                    case 'present':
                        $presentDates[] = $date;
                        break;
                    case 'absent':
                        $absentDates[] = $date;
                        break;
                    case 'half_day':
                        $halfDayDates[] = $date;
                        break;
                    case 'leave':
                        $leaveDates[] = $date;
                        break;
                    case 'holiday':
                        $holidayDates[] = $date;
                        break;
                }
            }

            $employeeReports[] = [
                'employee' => $employee,
                'total_days' => $empAttendances->count(),
                'present_count' => count($presentDates),
                'absent_count' => count($absentDates),
                'half_day_count' => count($halfDayDates),
                'leave_count' => count($leaveDates),
                'holiday_count' => count($holidayDates),
                'present_dates' => $presentDates,
                'absent_dates' => $absentDates,
                'half_day_dates' => $halfDayDates,
                'leave_dates' => $leaveDates,
                'holiday_dates' => $holidayDates,
                'total_hours' => $empAttendances->sum('total_hours'),
                'regular_hours' => $empAttendances->sum('regular_hours'),
                'overtime_hours' => $empAttendances->sum('overtime_hours'),
                'attendance_percentage' => $empAttendances->count() > 0
                    ? round((count($presentDates) / $empAttendances->count()) * 100, 2)
                    : 0,
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_records' => $attendances->count(),
            'employee_reports' => $employeeReports,
            'summary' => [
                'total_present' => $attendances->where('status', 'present')->count(),
                'total_absent' => $attendances->where('status', 'absent')->count(),
                'total_half_day' => $attendances->where('status', 'half_day')->count(),
                'total_leave' => $attendances->where('status', 'leave')->count(),
                'total_holiday' => $attendances->where('status', 'holiday')->count(),
                'total_hours' => $attendances->sum('total_hours'),
                'total_regular_hours' => $attendances->sum('regular_hours'),
                'total_overtime_hours' => $attendances->sum('overtime_hours'),
            ]
        ];
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:employees,id',
            'format' => 'required|in:csv,pdf'
        ]);

        $reportData = $this->generateReport(
            $request->start_date,
            $request->end_date,
            $request->employee_id
        );

        if ($request->format === 'csv') {
            return $this->exportToCsv($reportData);
        } else {
            return $this->exportToPdf($reportData);
        }
    }

    private function exportToCsv($reportData)
    {
        $filename = 'attendance_analytics_' . $reportData['start_date'] . '_to_' . $reportData['end_date'] . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($reportData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['Attendance Analytics Report']);
            fputcsv($file, ['Period: ' . $reportData['start_date'] . ' to ' . $reportData['end_date']]);
            fputcsv($file, []);

            fputcsv($file, [
                'Staff Number',
                'Employee Name',
                'Total Days',
                'Present',
                'Absent',
                'Half Day',
                'Leave',
                'Holiday',
                'Total Hours',
                'Regular Hours',
                'Overtime Hours',
                'Attendance %'
            ]);

            foreach ($reportData['employee_reports'] as $report) {
                fputcsv($file, [
                    $report['employee']->staff_number,
                    $report['employee']->employee_name,
                    $report['total_days'],
                    $report['present_count'],
                    $report['absent_count'],
                    $report['half_day_count'],
                    $report['leave_count'],
                    $report['holiday_count'],
                    number_format($report['total_hours'], 2),
                    number_format($report['regular_hours'], 2),
                    number_format($report['overtime_hours'], 2),
                    $report['attendance_percentage'] . '%'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($reportData)
    {
        $pdf = Pdf::loadView('reports.analytics-pdf', $reportData)
                  ->setPaper('a4', 'landscape');

        $filename = 'attendance_analytics_' . $reportData['start_date'] . '_to_' . $reportData['end_date'] . '.pdf';

        return $pdf->download($filename);
    }
}
