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

        // ✅ Calculate REGULAR working days ONLY (Mon-Sat, excluding holidays)
        $totalWorkingDays = $this->calculateRegularWorkingDays($startDate, $endDate, $attendances);

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

        // Get regular working dates (Mon-Sat, excluding holidays)
        $regularWorkingDates = $this->getRegularWorkingDates($startDate, $endDate, $attendances);

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
        $totalExtraDays = 0;

        foreach ($employees as $employee) {
            $empAttendances = $attendanceByEmployee->get($employee->id, collect());

            // Map date => status (only for REGULAR working dates)
            $statusByDate = [];
            $lateByDate = [];
            foreach ($empAttendances as $att) {
                $d = $att->attendance_date->format('Y-m-d');
                if (!in_array($d, $regularWorkingDates, true)) {
                    continue;
                }
                $statusByDate[$d] = $att->status;

                if ($att->status === 'present' && $att->is_late) {
                    $lateByDate[$d] = true;
                }
            }

            // Classify each REGULAR working date
            $presentDates = [];
            $leaveDates = [];
            $absentDates = [];
            $halfDayDates = [];
            $holidayDates = [];
            $lateDates = [];

            foreach ($regularWorkingDates as $d) {
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

            // ✅ Calculate EXTRA days worked (Sundays + Holidays with attendance)
            $extraDaysWorked = $empAttendances->filter(function($att) {
                $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
                $isHoliday = $att->status === 'holiday';
                $hasWorked = in_array($att->status, ['present', 'half_day']);

                return ($isSunday || $isHoliday) && $hasWorked;
            });

            $extraDaysCount = $extraDaysWorked->count();
            $extraDaysHours = $extraDaysWorked->sum('total_hours');

            // Hours
            $empTotalHours = $empAttendances->sum('total_hours');
            $empRegularHours = $empAttendances->sum('regular_hours');

            // ✅ Overtime = original overtime + ALL extra day hours
            $empOvertimeHours = $empAttendances->sum('overtime_hours') + $extraDaysHours;

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
                'overtime_hours'       => $empOvertimeHours,  // ✅ Pass overtime for bonus
                'extra_days_count'     => $extraDaysCount,    // ✅ Pass extra days for bonus
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
            $totalExtraDays    += $extraDaysCount;

            $employeeReports[] = [
                'employee'              => $employee,
                'total_working_days'    => $totalWorkingDays,
                'present_count'         => count($presentDates),
                'absent_count'          => count($absentDates),
                'half_day_count'        => count($halfDayDates),
                'leave_count'           => count($leaveDates),
                'holiday_count'         => count($holidayDates),
                'late_count'            => count($lateDates),
                'extra_days_count'      => $extraDaysCount,      // ✅ NEW
                'extra_days_hours'      => $extraDaysHours,      // ✅ NEW
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
                'extra_days_hours_formatted' => $this->formatHoursMinutes($extraDaysHours), // ✅ NEW
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
        $actualSlots   = $totalPresent + $totalLeave + $totalAbsent + $totalHalfDay;

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
            'total_extra_days'      => $totalExtraDays,  // ✅ NEW
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
     * ✅ Get REGULAR working dates ONLY (Mon-Sat, excluding holidays)
     */
    private function getRegularWorkingDates($startDate, $endDate, $attendances)
    {
        $workingDates = [];
        $cursor = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $today = Carbon::today();

        // Get all holiday dates in the range
        $holidayDates = $attendances
            ->where('status', 'holiday')
            ->pluck('attendance_date')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->unique()
            ->toArray();

        while ($cursor->lte($end)) {
            $dateStr = $cursor->format('Y-m-d');

            // Skip future dates
            if ($cursor->gt($today)) {
                $cursor->addDay();
                continue;
            }

            // Skip holidays
            if (in_array($dateStr, $holidayDates)) {
                $cursor->addDay();
                continue;
            }

            // Include only Mon-Sat (regular working days)
            if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                $workingDates[] = $dateStr;
            }

            $cursor->addDay();
        }

        return $workingDates;
    }

    /**
     * ✅ Calculate REGULAR working days ONLY (Mon-Sat, excluding holidays)
     */
    private function calculateRegularWorkingDays($startDate, $endDate, $attendances = null)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $workingDays = 0;

        // Get holiday dates
        $holidayDates = [];

        if ($attendances) {
            $holidayDates = $attendances
                ->where('status', 'holiday')
                ->pluck('attendance_date')
                ->map(fn($date) => $date->format('Y-m-d'))
                ->unique()
                ->toArray();
        }

        while ($start->lte($end)) {
            $dateStr = $start->format('Y-m-d');

            // Skip holidays
            if (in_array($dateStr, $holidayDates)) {
                $start->addDay();
                continue;
            }

            // Count only Mon-Sat (regular working days)
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
     * ✅ UPDATED: Calculate work efficiency score with OVERTIME BONUS
     * Base score (0-100%):
     * - Attendance Rate: 40%
     * - Work Hours: 30%
     * - Punctuality: 20%
     * - Absence Penalty: -10%
     *
     * BONUS (up to +10%):
     * - Overtime hours: +5% bonus (scaled)
     * - Extra days worked: +5% bonus (scaled)
     *
     * Maximum possible: 110%
     */
    private function calculateWorkEfficiency($data)
    {
        $workingDays   = (int) ($data['total_working_days']   ?? 0);
        $presentDays   = (int) ($data['present_count']        ?? 0);
        $requiredHours = (float)($data['required_work_hours'] ?? 0);
        $actualHours   = (float)($data['total_hours']         ?? 0);
        $lateCount     = (int) ($data['late_count']           ?? 0);
        $absentCount   = (int) ($data['absent_count']         ?? 0);
        $overtimeHours = (float)($data['overtime_hours']      ?? 0);
        $extraDays     = (int) ($data['extra_days_count']     ?? 0);

        if ($workingDays <= 0) {
            return 0;
        }

        // ===== BASE SCORE (0-100%) =====

        // 1. Attendance Rate Score (40%)
        $attendanceRate = $workingDays > 0
            ? ($presentDays / $workingDays) * 100
            : 0;
        $attendanceScore = min(100, $attendanceRate) * 0.40;

        // 2. Work Hours Completion Score (30%)
        $hoursCompletionRate = $requiredHours > 0
            ? min(100, ($actualHours / $requiredHours) * 100)
            : 0;
        $hoursScore = $hoursCompletionRate * 0.30;

        // 3. Punctuality Score (20%)
        $onTimeDays = max(0, $presentDays - $lateCount);
        $punctualityRate = $presentDays > 0
            ? ($onTimeDays / max(1, $presentDays)) * 100
            : 100;
        $punctualityScore = $punctualityRate * 0.20;

        // 4. Absence Penalty (10% max deduction)
        $absencePenalty = min(10, $absentCount * 2);

        $baseScore = $attendanceScore + $hoursScore + $punctualityScore - $absencePenalty;

        // ===== BONUS SCORE (up to +10%) =====

        // 5. Overtime Bonus (up to +5%)
        // Scale: 0 hours = 0%, 20+ hours = 5%
        $overtimeBonus = min(5, ($overtimeHours / 20) * 5);

        // 6. Extra Days Bonus (up to +5%)
        // Scale: 0 days = 0%, 3+ days = 5%
        $extraDaysBonus = min(5, ($extraDays / 3) * 5);

        $bonusScore = $overtimeBonus + $extraDaysBonus;

        // Total score can exceed 100% (max 110%)
        $totalScore = $baseScore + $bonusScore;

        return max(0, round($totalScore, 2));
    }

    /**
     * ✅ UPDATED: Employee detail with extra days tracking
     */
    public function employeeDetail(Request $request, $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);

        $startDate = $request->start_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->end_date ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->orderBy('attendance_date', 'desc')
            ->get();

        // ✅ Calculate REGULAR working days ONLY (Mon-Sat, excluding holidays)
        $totalWorkingDays = $this->calculateRegularWorkingDays($startDate, $endDate, $attendances);
        $requiredWorkHours = $totalWorkingDays * 10;

        // ✅ Count ONLY regular working day presents (exclude Sundays and Holidays)
        $regularPresent = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHolidayStatus = $att->status === 'holiday';
            $isPresent = $att->status === 'present';

            // Count only if present AND NOT Sunday AND NOT holiday status
            return $isPresent && !$isSunday && !$isHolidayStatus;
        })->count();

        // ✅ Count ONLY regular working day absents (exclude Sundays and Holidays)
        $regularAbsent = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHolidayStatus = $att->status === 'holiday';
            $isAbsent = $att->status === 'absent';

            // Count only if absent AND NOT Sunday AND NOT holiday status
            return $isAbsent && !$isSunday && !$isHolidayStatus;
        })->count();

        // ✅ Count ONLY regular working day half days (exclude Sundays and Holidays)
        $regularHalfDay = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHolidayStatus = $att->status === 'holiday';
            $isHalfDay = $att->status === 'half_day';

            // Count only if half_day AND NOT Sunday AND NOT holiday status
            return $isHalfDay && !$isSunday && !$isHolidayStatus;
        })->count();

        // Only count late when status is present (on regular working days)
        $lateCount = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHolidayStatus = $att->status === 'holiday';

            return $att->is_late
                && $att->status === 'present'
                && !$isSunday
                && !$isHolidayStatus;
        })->count();

        // ✅ Calculate EXTRA days worked (Sundays + Holidays with attendance)
        $extraDaysWorked = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHoliday = $att->status === 'holiday';
            $hasWorked = in_array($att->status, ['present', 'half_day']);

            return ($isSunday || $isHoliday) && $hasWorked;
        });

        $extraDaysCount = $extraDaysWorked->count();
        $extraDaysHours = $extraDaysWorked->sum('total_hours');

        // ✅ Overtime = original overtime + ALL extra day hours
        $totalOvertimeHours = $attendances->sum('overtime_hours') + $extraDaysHours;

        $stats = [
            'total_working_days'    => $totalWorkingDays,
            'total_days'            => $attendances->count(),
            'present'               => $regularPresent,     // ✅ FIXED: Only regular working day presents
            'absent'                => $regularAbsent,      // ✅ FIXED: Only regular working day absents
            'half_day'              => $regularHalfDay,     // ✅ FIXED: Only regular working day half days
            'leave'                 => $attendances->where('status', 'leave')->count(),
            'holiday'               => $attendances->where('status', 'holiday')->count(),
            'extra_days_worked'     => $extraDaysCount,
            'extra_days_hours'      => $extraDaysHours,
            'total_hours'           => $attendances->sum('total_hours'),
            'regular_hours'         => $attendances->sum('regular_hours'),
            'overtime_hours'        => $totalOvertimeHours,
            'required_work_hours'   => $requiredWorkHours,
            'late_count'            => $lateCount,
            'attendance_percentage' => $totalWorkingDays > 0
                ? round(($regularPresent / $totalWorkingDays) * 100, 2)
                : 0,
        ];

        $stats['work_efficiency'] = $this->calculateWorkEfficiency([
            'total_working_days'   => $totalWorkingDays,
            'present_count'        => $regularPresent,
            'required_work_hours'  => $requiredWorkHours,
            'total_hours'          => $stats['total_hours'],
            'late_count'           => $stats['late_count'],
            'absent_count'         => $regularAbsent,       // ✅ FIXED: Use regular absents only
            'overtime_hours'       => $totalOvertimeHours,
            'extra_days_count'     => $extraDaysCount,
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

    /**
     * ✅ FIXED: Export individual employee report
     */
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

        // ✅ FIXED: Use calculateRegularWorkingDays instead of calculateWorkingDays
        $totalWorkingDays = $this->calculateRegularWorkingDays($startDate, $endDate, $attendances);
        $requiredWorkHours = $totalWorkingDays * 10;

        // ✅ Count ONLY regular working day presents (exclude Sundays and Holidays)
        $regularPresent = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHolidayStatus = $att->status === 'holiday';
            $isPresent = $att->status === 'present';

            return $isPresent && !$isSunday && !$isHolidayStatus;
        })->count();

        // ✅ Count ONLY regular working day absents (exclude Sundays and Holidays)
        $regularAbsent = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHolidayStatus = $att->status === 'holiday';
            $isAbsent = $att->status === 'absent';

            return $isAbsent && !$isSunday && !$isHolidayStatus;
        })->count();

        // ✅ Count ONLY regular working day half days (exclude Sundays and Holidays)
        $regularHalfDay = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHolidayStatus = $att->status === 'holiday';
            $isHalfDay = $att->status === 'half_day';

            return $isHalfDay && !$isSunday && !$isHolidayStatus;
        })->count();

        // Only count late when status is present (on regular working days)
        $lateCount = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHolidayStatus = $att->status === 'holiday';

            return $att->is_late
                && $att->status === 'present'
                && !$isSunday
                && !$isHolidayStatus;
        })->count();

        // ✅ Calculate EXTRA days worked (Sundays + Holidays with attendance)
        $extraDaysWorked = $attendances->filter(function($att) {
            $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
            $isHoliday = $att->status === 'holiday';
            $hasWorked = in_array($att->status, ['present', 'half_day']);

            return ($isSunday || $isHoliday) && $hasWorked;
        });

        $extraDaysCount = $extraDaysWorked->count();
        $extraDaysHours = $extraDaysWorked->sum('total_hours');

        // ✅ Overtime = original overtime + ALL extra day hours
        $totalOvertimeHours = $attendances->sum('overtime_hours') + $extraDaysHours;

        $stats = [
            'total_working_days'    => $totalWorkingDays,
            'total_days'            => $attendances->count(),
            'present'               => $regularPresent,
            'absent'                => $regularAbsent,
            'half_day'              => $regularHalfDay,
            'leave'                 => $attendances->where('status', 'leave')->count(),
            'holiday'               => $attendances->where('status', 'holiday')->count(),
            'extra_days_worked'     => $extraDaysCount,
            'extra_days_hours'      => $extraDaysHours,
            'total_hours'           => $attendances->sum('total_hours'),
            'regular_hours'         => $attendances->sum('regular_hours'),
            'overtime_hours'        => $totalOvertimeHours,
            'required_work_hours'   => $requiredWorkHours,
            'late_count'            => $lateCount,
            'attendance_percentage' => $totalWorkingDays > 0
                ? round(($regularPresent / $totalWorkingDays) * 100, 2)
                : 0,
        ];

        $stats['work_efficiency'] = $this->calculateWorkEfficiency([
            'total_working_days'   => $totalWorkingDays,
            'present_count'        => $regularPresent,
            'required_work_hours'  => $requiredWorkHours,
            'total_hours'          => $stats['total_hours'],
            'late_count'           => $stats['late_count'],
            'absent_count'         => $regularAbsent,
            'overtime_hours'       => $totalOvertimeHours,
            'extra_days_count'     => $extraDaysCount,
        ]);

        if ($request->format === 'csv') {
            return $this->exportEmployeeToCsv($employee, $attendances, $stats, $startDate, $endDate);
        } else {
            return $this->exportEmployeeToPdf($employee, $attendances, $stats, $startDate, $endDate);
        }
    }

    /**
     * ✅ FIXED: Export employee to CSV (corrected method name)
     */
    private function exportEmployeeToCsv($employee, $attendances, $stats, $startDate, $endDate)
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
                'Extra Days', 'Total Hours', 'Required Hours', 'Overtime Hours', 'Work Efficiency'
            ]);

            $totalHoursFormatted = $this->formatHoursMinutes($stats['total_hours']);
            $requiredHoursFormatted = $this->formatHoursMinutes($stats['required_work_hours']);
            $overtimeHoursFormatted = $this->formatHoursMinutes($stats['overtime_hours']);
            $extraDaysHoursFormatted = $this->formatHoursMinutes($stats['extra_days_hours']);

            fputcsv($file, [
                $stats['attendance_percentage'] . '%',
                $stats['present'],
                $stats['absent'],
                $stats['half_day'],
                $stats['leave'],
                $stats['late_count'],
                $stats['extra_days_worked'] . ' (' . $extraDaysHoursFormatted . ')',
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
                $isSunday = $attendance->attendance_date->dayOfWeek === Carbon::SUNDAY;
                $isHoliday = $attendance->status === 'holiday';
                $isExtraDay = ($isSunday || $isHoliday) && in_array($attendance->status, ['present', 'half_day']);

                $lateStatus = '-';
                if ($attendance->status === 'present' && !$isSunday && !$isHoliday) {
                    $lateStatus = $attendance->is_late ? $attendance->late_status : 'On Time';
                }

                $dayLabel = $attendance->attendance_date->format('l');
                if ($isExtraDay) {
                    $dayLabel .= ' (EXTRA DAY)';
                }

                $statusLabel = ucfirst($attendance->status);
                if ($isExtraDay) {
                    $statusLabel .= ' (Extra Day)';
                } elseif ($isSunday && $attendance->status === 'absent') {
                    $statusLabel = 'Sunday (Off Day)';
                }

                fputcsv($file, [
                    $attendance->attendance_date->format('d M Y'),
                    $dayLabel,
                    $attendance->getFormattedCheckInTime(),
                    $attendance->getFormattedCheckOutTime(),
                    $lateStatus,
                    $this->formatHoursMinutes($attendance->regular_hours ?? 0),
                    $this->formatHoursMinutes($attendance->overtime_hours ?? 0),
                    $this->formatHoursMinutes($attendance->total_hours ?? 0),
                    $statusLabel,
                    $attendance->notes ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * ✅ FIXED: Export employee to PDF (corrected method name)
     */
    private function exportEmployeeToPdf($employee, $attendances, $stats, $startDate, $endDate)
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
