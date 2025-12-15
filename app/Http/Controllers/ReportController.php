<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function analytics(Request $request)
    {
        $employees = Employee::with('user')
            ->orderBy('employee_name')
            ->get();

        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::today()->format('Y-m-d');
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
        // 1) Active employees (exclude admins)
        $employeeQuery = Employee::where('status', 'active')
            ->whereHas('user', function ($q) {
                $q->where('role', '!=', 'admin');
            });

        if ($employeeId) {
            $employeeQuery->where('id', $employeeId);
        }

        $employees = $employeeQuery->get();
        $activeEmployeeCount = $employees->count();

        // 2) Attendance records in range
        $attendanceQuery = Attendance::with('employee.user')
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->whereHas('employee', function ($q) {
                $q->where('status', 'active')
                  ->whereHas('user', function ($qq) {
                      $qq->where('role', '!=', 'admin');
                  });
            });

        if ($employeeId) {
            $attendanceQuery->where('employee_id', $employeeId);
        }

        $attendances = $attendanceQuery->orderBy('attendance_date', 'asc')->get();
        $attendanceByEmployee = $attendances->groupBy('employee_id');

        // 3) Working days in range (Mon–Sat)
        $totalWorkingDays = $this->calculateWorkingDays($startDate, $endDate);

        // Guard against zero working days or employees
        if ($totalWorkingDays <= 0 || $activeEmployeeCount <= 0) {
            return [
                'start_date'            => $startDate,
                'end_date'              => $endDate,
                'total_working_days'    => $totalWorkingDays,
                'active_employee_count' => $activeEmployeeCount,
                'employee_reports'      => [],
                'team_efficiency'       => 0,
                'total_required_hours'  => 0,
                'total_actual_hours'    => 0,
                'total_late_entries'    => 0,
                'total_approved_leaves' => 0,
                'total_unapproved_absences' => 0,
                'summary' => [
                    'total_present'        => 0,
                    'total_absent'         => 0,
                    'total_half_day'       => 0,
                    'total_leave'          => 0,
                    'total_holiday'        => 0,
                    'total_overtime_hours' => 0,
                    'expected_slots'       => 0,
                    'actual_slots'         => 0,
                    'total_overtime_hours_formatted' => '0h 00m',
                ],
                'total_required_hours_formatted' => '0h 00m',
                'total_actual_hours_formatted'   => '0h 00m',
            ];
        }

        $overallRequiredHours = $totalWorkingDays * 10 * $activeEmployeeCount;

        // Pre-build list of working dates UP TO TODAY ONLY
        $workingDates = [];
        $cursor = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $today = Carbon::today(); // Don't mark future dates as absent

        while ($cursor->lte($end)) {
            // Only include working dates up to today
            if ($cursor->dayOfWeek !== Carbon::SUNDAY && $cursor->lte($today)) {
                $workingDates[] = $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        // 4) Per-employee stats
        $employeeReports = [];
        $totalPresent = 0;
        $totalLeave = 0;
        $totalAbsent = 0;
        $totalHalfDay = 0;
        $totalHoliday = 0;
        $totalLate = 0;
        $totalActualHours = 0;
        $totalOvertimeHours = 0;

        foreach ($employees as $employee) {
            $empAttendances = $attendanceByEmployee->get($employee->id, collect());

            // Map date => status (only for working dates)
            $statusByDate = [];
            $lateByDate = [];
            foreach ($empAttendances as $att) {
                $d = $att->attendance_date->format('Y-m-d');
                if (!in_array($d, $workingDates, true)) {
                    continue; // ignore non‑working or future days
                }
                $statusByDate[$d] = $att->status;

                if ($att->status === 'present' && $att->is_late) {
                    $lateByDate[$d] = true;
                }
            }

            // Classify each working date for this employee
            $presentDates = [];
            $leaveDates = [];
            $absentDates = [];
            $halfDayDates = [];
            $holidayDates = [];
            $lateDates = [];

            foreach ($workingDates as $d) {
                $status = $statusByDate[$d] ?? null;

                switch ($status) {
                    case 'present':
                        $presentDates[] = $d;
                        if (!empty($lateByDate[$d])) {
                            $lateDates[] = $d;
                        }
                        break;
                    case 'leave':
                        $leaveDates[] = $d;
                        break;
                    case 'half_day':
                        $halfDayDates[] = $d;
                        break;
                    case 'holiday':
                        $holidayDates[] = $d;
                        break;
                    case 'absent':
                        $absentDates[] = $d;
                        break;
                    default:
                        // No record for this past working day -> count as Absent
                        $absentDates[] = $d;
                        break;
                }
            }

            // Hours
            $empTotalHours = $empAttendances->sum('total_hours');
            $empRegularHours = $empAttendances->sum('regular_hours');
            $empOvertimeHours = $empAttendances->sum('overtime_hours');

            $requiredWorkHours = $totalWorkingDays * 10;

            $attendancePercentage = $totalWorkingDays > 0
                ? round((count($presentDates) / $totalWorkingDays) * 100, 2)
                : 0;

            $workEfficiency = $this->calculateWorkEfficiency([
                'total_working_days'   => $totalWorkingDays,
                'present_count'        => count($presentDates),
                'required_work_hours'  => $requiredWorkHours,
                'total_hours'          => $empTotalHours,
                'late_count'           => count($lateDates),
                'absent_count'         => count($absentDates),
            ]);

            // Aggregate all‑employee totals
            $totalPresent      += count($presentDates);
            $totalLeave        += count($leaveDates);
            $totalAbsent       += count($absentDates);
            $totalHalfDay      += count($halfDayDates);
            $totalHoliday      += count($holidayDates);
            $totalLate         += count($lateDates);
            $totalActualHours  += $empTotalHours;
            $totalOvertimeHours+= $empOvertimeHours;

            $employeeReports[] = [
                'employee'              => $employee,
                'total_working_days'    => $totalWorkingDays,
                'present_count'         => count($presentDates),
                'absent_count'          => count($absentDates),
                'half_day_count'        => count($halfDayDates),
                'leave_count'           => count($leaveDates),
                'holiday_count'         => count($holidayDates),
                'late_count'            => count($lateDates),
                'present_dates'         => $presentDates,
                'absent_dates'          => $absentDates,
                'half_day_dates'        => $halfDayDates,
                'leave_dates'           => $leaveDates,
                'holiday_dates'         => $holidayDates,
                'late_dates'            => $lateDates,
                'total_hours'           => round($empTotalHours, 2),
                'regular_hours'         => round($empRegularHours, 2),
                'overtime_hours'        => round($empOvertimeHours, 2),
                'required_work_hours'   => $requiredWorkHours,
                'attendance_percentage' => $attendancePercentage,
                'work_efficiency'       => $workEfficiency,

                // Add formatted versions for hours:minutes display
                'total_hours_formatted'    => $this->formatHoursMinutes($empTotalHours),
                'regular_hours_formatted'  => $this->formatHoursMinutes($empRegularHours),
                'overtime_hours_formatted' => $this->formatHoursMinutes($empOvertimeHours),
                'required_hours_formatted' => $this->formatHoursMinutes($requiredWorkHours),
            ];
        }

        // Sort employees by efficiency
        usort($employeeReports, fn($a, $b) => $b['work_efficiency'] <=> $a['work_efficiency']);

        // Team efficiency
        $teamEfficiency = $activeEmployeeCount > 0
            ? round(collect($employeeReports)->avg('work_efficiency'), 2)
            : 0;

        // Verification
        $expectedSlots = $totalWorkingDays * $activeEmployeeCount;
        $actualSlots   = $totalPresent + $totalLeave + $totalAbsent + $totalHalfDay + $totalHoliday;

        if ($expectedSlots !== $actualSlots) {
            Log::warning('Attendance slot mismatch', [
                'expected_slots' => $expectedSlots,
                'actual_slots'   => $actualSlots,
                'present'        => $totalPresent,
                'leave'          => $totalLeave,
                'absent'         => $totalAbsent,
                'half_day'       => $totalHalfDay,
                'holiday'        => $totalHoliday,
            ]);
        }

        return [
            'start_date'            => $startDate,
            'end_date'              => $endDate,
            'total_working_days'    => $totalWorkingDays,
            'active_employee_count' => $activeEmployeeCount,
            'employee_reports'      => $employeeReports,
            'team_efficiency'       => $teamEfficiency,
            'total_required_hours'  => $overallRequiredHours,
            'total_actual_hours'    => round($totalActualHours, 2),
            'total_late_entries'    => $totalLate,
            'total_approved_leaves' => $totalLeave,
            'total_unapproved_absences' => $totalAbsent,
            'summary' => [
                'total_present'        => $totalPresent,
                'total_absent'         => $totalAbsent,
                'total_half_day'       => $totalHalfDay,
                'total_leave'          => $totalLeave,
                'total_holiday'        => $totalHoliday,
                'total_overtime_hours' => round($totalOvertimeHours, 2),
                'expected_slots'       => $expectedSlots,
                'actual_slots'         => $actualSlots,

                // Add formatted version
                'total_overtime_hours_formatted' => $this->formatHoursMinutes($totalOvertimeHours),
            ],
            // Add formatted versions for display
            'total_required_hours_formatted' => $this->formatHoursMinutes($overallRequiredHours),
            'total_actual_hours_formatted'   => $this->formatHoursMinutes($totalActualHours),
        ];
    }

    /**
     * Calculate working days between two dates (Monday to Saturday, excluding Sunday)
     */
    private function calculateWorkingDays($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $workingDays = 0;

        while ($start->lte($end)) {
            if ($start->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $start->addDay();
        }

        return $workingDays;
    }

    /**
     * Convert decimal hours to hours:minutes format
     */
    private function formatHoursMinutes($decimalHours)
    {
        $hours = floor($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);
        return sprintf('%dh %02dm', $hours, $minutes);
    }

    /**
     * Calculate work efficiency score (0-100%)
     *
     * Weighted criteria:
     * - Attendance Rate: 40% (present days / working days)
     * - Work Hours Completion: 30% (actual hours / required hours)
     * - Punctuality: 20% (on-time arrivals / present days)
     * - Absence Penalty: 10% (deduct 2 points per absence, max 10 points)
     */
    private function calculateWorkEfficiency($data)
    {
        $workingDays   = (int) ($data['total_working_days']   ?? 0);
        $presentDays   = (int) ($data['present_count']        ?? 0);
        $requiredHours = (float)($data['required_work_hours'] ?? 0);
        $actualHours   = (float)($data['total_hours']         ?? 0);
        $lateCount     = (int) ($data['late_count']           ?? 0);
        $absentCount   = (int) ($data['absent_count']         ?? 0);

        // If no working days at all, efficiency is 0 – avoid any division.
        if ($workingDays <= 0) {
            return 0;
        }

        // 1. Attendance Rate Score (40%) – safe division
        $attendanceRate = $workingDays > 0
            ? ($presentDays / $workingDays) * 100
            : 0;
        $attendanceScore = min(100, $attendanceRate) * 0.40;

        // 2. Work Hours Completion Score (30%) – safe division
        $hoursCompletionRate = $requiredHours > 0
            ? min(100, ($actualHours / $requiredHours) * 100)
            : 0;
        $hoursScore = $hoursCompletionRate * 0.30;

        // 3. Punctuality Score (20%) – safe division
        $onTimeDays = max(0, $presentDays - $lateCount);
        $punctualityRate = $presentDays > 0
            ? ($onTimeDays / max(1, $presentDays)) * 100
            : 100; // if no present days, treat as neutral (full points)
        $punctualityScore = $punctualityRate * 0.20;

        // 4. Absence Penalty (10% max deduction)
        $absencePenalty = min(10, $absentCount * 2);

        $totalScore = $attendanceScore + $hoursScore + $punctualityScore - $absencePenalty;

        return max(0, round($totalScore, 2));
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

        $totalWorkingDays = $this->calculateWorkingDays($startDate, $endDate);
        $requiredWorkHours = $totalWorkingDays * 10;

        // Only count late when status is present
        $lateCount = $attendances->where('is_late', true)
                                 ->where('status', 'present')
                                 ->count();

        $stats = [
            'total_working_days'    => $totalWorkingDays,
            'total_days'            => $attendances->count(),
            'present'               => $attendances->where('status', 'present')->count(),
            'absent'                => $attendances->where('status', 'absent')->count(),
            'half_day'              => $attendances->where('status', 'half_day')->count(),
            'leave'                 => $attendances->where('status', 'leave')->count(),
            'holiday'               => $attendances->where('status', 'holiday')->count(),
            'total_hours'           => $attendances->sum('total_hours'),
            'regular_hours'         => $attendances->sum('regular_hours'),
            'overtime_hours'        => $attendances->sum('overtime_hours'),
            'required_work_hours'   => $requiredWorkHours,
            'late_count'            => $lateCount,
            'attendance_percentage' => $totalWorkingDays > 0
                ? round(($attendances->where('status', 'present')->count() / $totalWorkingDays) * 100, 2)
                : 0,
        ];

        $stats['work_efficiency'] = $this->calculateWorkEfficiency([
            'total_working_days'   => $totalWorkingDays,
            'present_count'        => $stats['present'],
            'required_work_hours'  => $requiredWorkHours,
            'total_hours'          => $stats['total_hours'],
            'late_count'           => $stats['late_count'],
            'absent_count'         => $stats['absent'],
        ]);

        return view('reports.employee-detail', compact('employee', 'attendances', 'stats', 'startDate', 'endDate'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:employees,id',
            'format'     => 'required|in:csv,pdf'
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
            fputcsv($file, ['Active Employees: ' . $reportData['active_employee_count']]);
            fputcsv($file, ['Team Efficiency: ' . $reportData['team_efficiency'] . '%']);
            fputcsv($file, []);

            fputcsv($file, ['Summary Statistics']);
            fputcsv($file, ['Total Present', 'Half Days', 'Approved Leaves', 'Unapproved Absences', 'Late Entries', 'Required Hours', 'Actual Hours', 'Overtime Hours']);
            fputcsv($file, [
                $reportData['summary']['total_present'],
                $reportData['summary']['total_half_day'],
                $reportData['total_approved_leaves'],
                $reportData['total_unapproved_absences'],
                $reportData['total_late_entries'],
                $reportData['total_required_hours_formatted'],
                $reportData['total_actual_hours_formatted'],
                $reportData['summary']['total_overtime_hours_formatted'],
            ]);
            fputcsv($file, []);

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

            foreach ($reportData['employee_reports'] as $report) {
                fputcsv($file, [
                    $report['employee']->staff_number,
                    $report['employee']->employee_name,
                    $report['present_count'] . '/' . $report['total_working_days'],
                    $report['attendance_percentage'] . '%',
                    $report['work_efficiency'] . '%',
                    $report['absent_count'],
                    $report['leave_count'],
                    $report['late_count'],
                    $report['total_hours_formatted'],
                    $report['overtime_hours_formatted'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($reportData)
    {
        // Add total_records to the data
        $reportData['total_records'] = count($reportData['employee_reports'] ?? []);

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
            'employee'   => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'format'     => 'required|in:csv,pdf'
        ]);

        $employee = Employee::findOrFail($request->employee);
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->get();

        $totalWorkingDays = $this->calculateWorkingDays($startDate, $endDate);
        $requiredWorkHours = $totalWorkingDays * 10;

        // Only count late when status is present
        $lateCount = $attendances->where('is_late', true)
                                 ->where('status', 'present')
                                 ->count();

        $stats = [
            'total_working_days'    => $totalWorkingDays,
            'total_days'            => $attendances->count(),
            'present'               => $attendances->where('status', 'present')->count(),
            'absent'                => $attendances->where('status', 'absent')->count(),
            'half_day'              => $attendances->where('status', 'half_day')->count(),
            'leave'                 => $attendances->where('status', 'leave')->count(),
            'holiday'               => $attendances->where('status', 'holiday')->count(),
            'total_hours'           => $attendances->sum('total_hours'),
            'regular_hours'         => $attendances->sum('regular_hours'),
            'overtime_hours'        => $attendances->sum('overtime_hours'),
            'required_work_hours'   => $requiredWorkHours,
            'late_count'            => $lateCount,
            'attendance_percentage' => $totalWorkingDays > 0
                ? round(($attendances->where('status', 'present')->count() / $totalWorkingDays) * 100, 2)
                : 0,
        ];

        $stats['work_efficiency'] = $this->calculateWorkEfficiency([
            'total_working_days'   => $totalWorkingDays,
            'present_count'        => $stats['present'],
            'required_work_hours'  => $requiredWorkHours,
            'total_hours'          => $stats['total_hours'],
            'late_count'           => $stats['late_count'],
            'absent_count'         => $stats['absent'],
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

            fputcsv($file, ['Employee Attendance Report']);
            fputcsv($file, ['Employee Name: ' . $employee->employee_name]);
            fputcsv($file, ['Staff Number: ' . $employee->staff_number]);
            fputcsv($file, ['Period: ' . $startDate . ' to ' . $endDate]);
            fputcsv($file, []);

            fputcsv($file, ['Summary Statistics']);
            fputcsv($file, [
                'Attendance %', 'Present', 'Absent', 'Half Day', 'Leave', 'Late Entries',
                'Total Hours', 'Required Hours', 'Overtime Hours', 'Work Efficiency'
            ]);

            $totalHoursFormatted = $this->formatHoursMinutes($stats['total_hours']);
            $requiredHoursFormatted = $this->formatHoursMinutes($stats['required_work_hours']);
            $overtimeHoursFormatted = $this->formatHoursMinutes($stats['overtime_hours']);

            fputcsv($file, [
                $stats['attendance_percentage'] . '%',
                $stats['present'],
                $stats['absent'],
                $stats['half_day'],
                $stats['leave'],
                $stats['late_count'],
                $totalHoursFormatted,
                $requiredHoursFormatted,
                $overtimeHoursFormatted,
                $stats['work_efficiency'] . '%'
            ]);
            fputcsv($file, []);

            fputcsv($file, ['Detailed Attendance Records']);
            fputcsv($file, [
                'Date', 'Day', 'Check In', 'Check Out', 'Late Status',
                'Regular Hours', 'Overtime', 'Total Hours', 'Status', 'Notes'
            ]);

            foreach ($attendances as $attendance) {
                $lateStatus = '-';
                if ($attendance->status === 'present') {
                    $lateStatus = $attendance->is_late ? $attendance->late_status : 'On Time';
                }

                fputcsv($file, [
                    $attendance->attendance_date->format('d M Y'),
                    $attendance->attendance_date->format('l'),
                    $attendance->check_in_time ? $attendance->check_in_time->format('h:i A') : '-',
                    $attendance->check_out_time ? $attendance->check_out_time->format('h:i A') : '-',
                    $lateStatus,
                    $this->formatHoursMinutes($attendance->regular_hours ?? 0),
                    $this->formatHoursMinutes($attendance->overtime_hours ?? 0),
                    $this->formatHoursMinutes($attendance->total_hours ?? 0),
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
            'employee'   => $employee,
            'attendances' => $attendances,
            'stats'      => $stats,
            'start_date' => $startDate,
            'end_date'   => $endDate,
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
