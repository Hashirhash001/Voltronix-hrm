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
        // Show all employees (including admins) in dropdown for filtering
        $employees = Employee::with('user')
            ->orderBy('employee_name')
            ->get();

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
        // Get all employees excluding admin and inactive
        $employeeQuery = Employee::where('status', 'active')
            ->whereHas('user', function($query) {
                $query->where('role', '!=', 'admin');
            });

        $activeEmployeeCount = $employeeQuery->count();

        $query = Attendance::with('employee.user')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->whereHas('employee', function($q) {
                $q->where('status', 'active')
                ->whereHas('user', function($query) {
                    $query->where('role', '!=', 'admin');
                });
            });

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $attendances = $query->orderBy('attendance_date', 'asc')->get();

        // Calculate overall required hours (only for active non-admin employees)
        $totalWorkingDays = $this->calculateWorkingDays($startDate, $endDate);
        $overallRequiredHours = $totalWorkingDays * 10 * $activeEmployeeCount;

        // Group by employee
        $employeeGroups = $attendances->groupBy('employee_id');

        $employeeReports = [];
        $totalLateEntries = 0;
        $totalApprovedLeaves = 0;
        $totalUnapprovedAbsences = 0;

        foreach ($employeeGroups as $empId => $empAttendances) {
            $employee = $empAttendances->first()->employee;

            $empWorkingDays = $totalWorkingDays;
            $requiredWorkHours = $empWorkingDays * 10;

            $presentDates = [];
            $absentDates = [];
            $halfDayDates = [];
            $leaveDates = [];
            $holidayDates = [];
            $lateDates = [];

            foreach ($empAttendances as $att) {
                $date = $att->attendance_date->format('Y-m-d');

                if ($att->is_late) {
                    $lateDates[] = [
                        'date' => $date,
                        'minutes' => $att->late_minutes,
                        'status' => $att->late_status
                    ];
                    $totalLateEntries++;
                }

                switch ($att->status) {
                    case 'present':
                        $presentDates[] = $date;
                        break;
                    case 'absent':
                        $absentDates[] = $date;
                        $totalUnapprovedAbsences++;
                        break;
                    case 'half_day':
                        $halfDayDates[] = $date;
                        break;
                    case 'leave':
                        $leaveDates[] = $date;
                        $totalApprovedLeaves++;
                        break;
                    case 'holiday':
                        $holidayDates[] = $date;
                        break;
                }
            }

            $recordedDays = $empAttendances->count();
            $unrecordedWorkingDays = max(0, $empWorkingDays - $recordedDays - count($holidayDates));

            $totalHours = $empAttendances->sum('total_hours');
            $regularHours = $empAttendances->sum('regular_hours');
            $overtimeHours = $empAttendances->sum('overtime_hours');

            $workEfficiency = $this->calculateWorkEfficiency([
                'total_working_days' => $empWorkingDays,
                'present_count' => count($presentDates),
                'required_work_hours' => $requiredWorkHours,
                'total_hours' => $totalHours,
                'late_count' => count($lateDates),
                'absent_count' => count($absentDates),
            ]);

            $employeeReports[] = [
                'employee' => $employee,
                'total_working_days' => $empWorkingDays,
                'total_days' => $empAttendances->count(),
                'present_count' => count($presentDates),
                'absent_count' => count($absentDates),
                'half_day_count' => count($halfDayDates),
                'leave_count' => count($leaveDates) + $unrecordedWorkingDays,
                'holiday_count' => count($holidayDates),
                'late_count' => count($lateDates),
                'present_dates' => $presentDates,
                'absent_dates' => $absentDates,
                'half_day_dates' => $halfDayDates,
                'leave_dates' => $leaveDates,
                'holiday_dates' => $holidayDates,
                'late_dates' => $lateDates,
                'total_hours' => $totalHours,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'required_work_hours' => $requiredWorkHours,
                'work_efficiency' => $workEfficiency,
                'attendance_percentage' => $empWorkingDays > 0
                    ? round((count($presentDates) / $empWorkingDays) * 100, 2)
                    : 0,
            ];
        }

        $totalActualHours = $attendances->sum('total_hours');

        // Calculate team efficiency
        $teamEfficiency = $this->calculateWorkEfficiency([
            'total_working_days' => $totalWorkingDays * $activeEmployeeCount,
            'present_count' => $attendances->where('status', 'present')->count(),
            'required_work_hours' => $overallRequiredHours,
            'total_hours' => $totalActualHours,
            'late_count' => $totalLateEntries,
            'absent_count' => $totalUnapprovedAbsences,
        ]);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_working_days' => $totalWorkingDays,
            'active_employee_count' => $activeEmployeeCount,
            'total_records' => $attendances->count(),
            'total_required_hours' => $overallRequiredHours,
            'total_actual_hours' => $totalActualHours,
            'total_late_entries' => $totalLateEntries,
            'total_approved_leaves' => $totalApprovedLeaves,
            'total_unapproved_absences' => $totalUnapprovedAbsences,
            'team_efficiency' => $teamEfficiency,
            'employee_reports' => $employeeReports,
            'summary' => [
                'total_present' => $attendances->where('status', 'present')->count(),
                'total_absent' => $attendances->where('status', 'absent')->count(),
                'total_half_day' => $attendances->where('status', 'half_day')->count(),
                'total_leave' => $attendances->where('status', 'leave')->count(),
                'total_holiday' => $attendances->where('status', 'holiday')->count(),
                'total_regular_hours' => $attendances->sum('regular_hours'),
                'total_overtime_hours' => $attendances->sum('overtime_hours'),
            ]
        ];
    }

    private function calculateWorkingDays($startDate, $endDate)
    {
        $totalWorkingDays = 0;
        $currentDate = Carbon::parse($startDate);
        $endDateCarbon = Carbon::parse($endDate);

        while ($currentDate->lessThanOrEqualTo($endDateCarbon)) {
            if ($currentDate->dayOfWeek !== 0) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
        }

        return $totalWorkingDays;
    }

    /**
     * Convert decimal hours to hours:minutes format
     */
    private function hoursToHoursMinutes($decimalHours)
    {
        $hours = floor($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);
        return sprintf('%dh %02dm', $hours, $minutes);
    }

    /**
     * Calculate Work Efficiency Score
     *
     * Criteria:
     * 1. Attendance Rate (40%): Present days / Working days
     * 2. Work Hours Completion (30%): Actual hours / Required hours
     * 3. Punctuality (20%): Days on time / Present days
     * 4. Absence Impact (10%): Penalty for absences
     *
     * Score: 0-100%
     */
    private function calculateWorkEfficiency($data)
    {
        $score = 0;

        // 1. Attendance Rate (40 points)
        if ($data['total_working_days'] > 0) {
            $attendanceRate = ($data['present_count'] / $data['total_working_days']) * 100;
            $score += ($attendanceRate / 100) * 40;
        }

        // 2. Work Hours Completion (30 points)
        if ($data['required_work_hours'] > 0) {
            $hoursCompletionRate = min(($data['total_hours'] / $data['required_work_hours']) * 100, 100);
            $score += ($hoursCompletionRate / 100) * 30;
        }

        // 3. Punctuality (20 points)
        if ($data['present_count'] > 0) {
            $onTimeDays = $data['present_count'] - $data['late_count'];
            $punctualityRate = ($onTimeDays / $data['present_count']) * 100;
            $score += ($punctualityRate / 100) * 20;
        } else {
            // If no present days, give full punctuality score
            $score += 20;
        }

        // 4. Absence Impact (10 points) - Penalty for absences
        $absencePenalty = min($data['absent_count'] * 2, 10); // 2 points penalty per absence, max 10
        $score += max(10 - $absencePenalty, 0);

        return round($score, 2);
    }

    public function employeeDetail(Request $request, $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->get();

        // Calculate total working days in date range (Mon-Sat only)
        $totalWorkingDays = 0;
        $currentDate = Carbon::parse($startDate);
        $endDateCarbon = Carbon::parse($endDate);

        while ($currentDate->lessThanOrEqualTo($endDateCarbon)) {
            if ($currentDate->dayOfWeek !== 0) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
        }

        // Required work hours (10 hours per working day)
        $requiredWorkHours = $totalWorkingDays * 10;

        $stats = [
            'total_working_days' => $totalWorkingDays,
            'total_days' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'holiday' => $attendances->where('status', 'holiday')->count(),
            'total_hours' => $attendances->sum('total_hours'),
            'regular_hours' => $attendances->sum('regular_hours'),
            'overtime_hours' => $attendances->sum('overtime_hours'),
            'required_work_hours' => $requiredWorkHours,
            'late_count' => $attendances->where('is_late', true)->count(),
            'attendance_percentage' => $totalWorkingDays > 0
                ? round(($attendances->where('status', 'present')->count() / $totalWorkingDays) * 100, 2)
                : 0,
        ];

        // Calculate work efficiency
        $stats['work_efficiency'] = $this->calculateWorkEfficiency([
            'total_working_days' => $totalWorkingDays,
            'present_count' => $stats['present'],
            'required_work_hours' => $requiredWorkHours,
            'total_hours' => $stats['total_hours'],
            'late_count' => $stats['late_count'],
            'absent_count' => $stats['absent'],
        ]);

        return view('reports.employee-detail', compact('employee', 'attendances', 'stats', 'startDate', 'endDate'));
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

            // Report Header
            fputcsv($file, ['Attendance Analytics Report']);
            fputcsv($file, ['Period: ' . $reportData['start_date'] . ' to ' . $reportData['end_date']]);
            fputcsv($file, ['Active Employees: ' . $reportData['active_employee_count']]);
            fputcsv($file, ['Team Efficiency: ' . $reportData['team_efficiency'] . '%']);
            fputcsv($file, []);

            // Summary Statistics
            fputcsv($file, ['Summary Statistics']);
            fputcsv($file, ['Total Present', 'Half Days', 'Approved Leaves', 'Unapproved Absences', 'Late Entries', 'Required Hours', 'Actual Hours', 'Overtime Hours']);
            fputcsv($file, [
                $reportData['summary']['total_present'],
                $reportData['summary']['total_half_day'],
                $reportData['total_approved_leaves'],
                $reportData['total_unapproved_absences'],
                $reportData['total_late_entries'],
                number_format($reportData['total_required_hours'], 2) . 'h',
                number_format($reportData['total_actual_hours'], 2) . 'h',
                number_format($reportData['summary']['total_overtime_hours'], 2) . 'h',
            ]);
            fputcsv($file, []);

            // Employee Details Header
            fputcsv($file, ['Employee Details']);
            fputcsv($file, [
                'Staff Number',
                'Employee Name',
                'Present/Working Days',
                'Attendance %',
                'Work Efficiency %',
                'Absent',
                'Leave',
                'Late Entries',
                'Total Hours',
                'Overtime Hours',
            ]);

            // Employee Data
            foreach ($reportData['employee_reports'] as $report) {
                $hours = floor($report['total_hours']);
                $minutes = round(($report['total_hours'] - $hours) * 60);

                $otHours = floor($report['overtime_hours']);
                $otMinutes = round(($report['overtime_hours'] - $otHours) * 60);

                fputcsv($file, [
                    $report['employee']->staff_number,
                    $report['employee']->employee_name,
                    $report['present_count'] . '/' . $report['total_working_days'],
                    $report['attendance_percentage'] . '%',
                    $report['work_efficiency'] . '%',
                    $report['absent_count'],
                    $report['leave_count'],
                    $report['late_count'],
                    $hours . 'h ' . $minutes . 'm',
                    $otHours . 'h ' . $otMinutes . 'm',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($reportData)
    {
        $pdf = Pdf::loadView('reports.analytics-pdf', $reportData)
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 5)
                ->setOption('margin-right', 5)
                ->setOption('margin-bottom', 5)
                ->setOption('margin-left', 5);

        $filename = 'attendance_analytics_' . $reportData['start_date'] . '_to_' . $reportData['end_date'] . '.pdf';

        return $pdf->download($filename);
    }

    public function employeeExport(Request $request)
    {
        $request->validate([
            'employee' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,pdf'
        ]);

        $employee = Employee::findOrFail($request->employee);
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->get();

        // Calculate total working days
        $totalWorkingDays = 0;
        $currentDate = Carbon::parse($startDate);
        $endDateCarbon = Carbon::parse($endDate);

        while ($currentDate->lessThanOrEqualTo($endDateCarbon)) {
            if ($currentDate->dayOfWeek !== 0) {
                $totalWorkingDays++;
            }
            $currentDate->addDay();
        }

        $requiredWorkHours = $totalWorkingDays * 10;

        $stats = [
            'total_working_days' => $totalWorkingDays,
            'total_days' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'holiday' => $attendances->where('status', 'holiday')->count(),
            'total_hours' => $attendances->sum('total_hours'),
            'regular_hours' => $attendances->sum('regular_hours'),
            'overtime_hours' => $attendances->sum('overtime_hours'),
            'required_work_hours' => $requiredWorkHours,
            'late_count' => $attendances->where('is_late', true)->count(),
            'attendance_percentage' => $totalWorkingDays > 0
                ? round(($attendances->where('status', 'present')->count() / $totalWorkingDays) * 100, 2)
                : 0,
        ];

        $stats['work_efficiency'] = $this->calculateWorkEfficiency([
            'total_working_days' => $totalWorkingDays,
            'present_count' => $stats['present'],
            'required_work_hours' => $requiredWorkHours,
            'total_hours' => $stats['total_hours'],
            'late_count' => $stats['late_count'],
            'absent_count' => $stats['absent'],
        ]);

        if ($request->format === 'csv') {
            return $this->employeeExportCsv($employee, $attendances, $stats, $startDate, $endDate);
        } else {
            return $this->employeeExportPdf($employee, $attendances, $stats, $startDate, $endDate);
        }
    }

    private function employeeExportCsv($employee, $attendances, $stats, $startDate, $endDate)
    {
        $filename = $employee->staff_number . '_' . $employee->employee_name . '_' . $startDate . '_to_' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($employee, $attendances, $stats, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Info
            fputcsv($file, ['Employee Attendance Report']);
            fputcsv($file, ['Employee Name: ' . $employee->employee_name]);
            fputcsv($file, ['Staff Number: ' . $employee->staff_number]);
            fputcsv($file, ['Period: ' . $startDate . ' to ' . $endDate]);
            fputcsv($file, []);

            // Statistics
            fputcsv($file, ['Summary Statistics']);
            fputcsv($file, [
                'Attendance %', 'Present', 'Absent', 'Half Day', 'Leave', 'Late Entries',
                'Total Hours', 'Required Hours', 'Overtime Hours', 'Work Efficiency'
            ]);
            fputcsv($file, [
                $stats['attendance_percentage'] . '%',
                $stats['present'],
                $stats['absent'],
                $stats['half_day'],
                $stats['leave'],
                $stats['late_count'],
                number_format($stats['total_hours'], 2) . 'h',
                number_format($stats['required_work_hours'], 2) . 'h',
                number_format($stats['overtime_hours'], 2) . 'h',
                $stats['work_efficiency'] . '%'
            ]);
            fputcsv($file, []);

            // Detailed Records
            fputcsv($file, ['Detailed Attendance Records']);
            fputcsv($file, [
                'Date', 'Day', 'Check In', 'Check Out', 'Late Status',
                'Regular Hours', 'Overtime', 'Total Hours', 'Status', 'Notes'
            ]);

            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->attendance_date->format('d M Y'),
                    $attendance->attendance_date->format('l'),
                    $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : '-',
                    $attendance->check_out_time ? $attendance->check_out_time->format('H:i') : '-',
                    $attendance->is_late ? $attendance->late_status : 'On Time',
                    number_format($attendance->regular_hours ?? 0, 2),
                    number_format($attendance->overtime_hours ?? 0, 2),
                    number_format($attendance->total_hours ?? 0, 2),
                    ucfirst($attendance->status),
                    $attendance->notes ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function employeeExportPdf($employee, $attendances, $stats, $startDate, $endDate)
    {
        $data = [
            'employee' => $employee,
            'attendances' => $attendances,
            'stats' => $stats,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $pdf = Pdf::loadView('reports.employee-detail-pdf', $data)
                ->setPaper('a4', 'landscape')
                ->setOption('margin-top', 5)
                ->setOption('margin-right', 5)
                ->setOption('margin-bottom', 5)
                ->setOption('margin-left', 5);

        $filename = $employee->staff_number . '_' . $employee->employee_name . '_' . $startDate . '_to_' . $endDate . '.pdf';

        return $pdf->download($filename);
    }

}
