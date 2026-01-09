<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Entity;
use App\Models\Vehicle;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        // ================= EMPLOYEE STATS =================
        $periodEnd = Carbon::create($currentYear, $currentMonth, 1)->endOfMonth();
        if ($currentMonth == now()->month && $currentYear == now()->year) {
            $periodEnd = Carbon::yesterday();
        }

        $totalEmployees = Employee::count();

        $activeEmployees = Employee::where('status', 'active')
            ->whereDate('duty_joined_date', '<=', $periodEnd)
            ->count();

        $onVacation = Employee::where('status', 'vacation')->count();
        $inactiveEmployees = Employee::where('status', 'inactive')->count();

        // ================= ENTITY / VEHICLE STATS =================
        $totalEntities = Entity::count();
        $activeEntities = Entity::where('status', 'active')->count();

        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('status', 'active')->count();

        // ================= TODAY ATTENDANCE =================
        $todayAttendanceQuery = Attendance::whereDate('attendance_date', $today)
            ->whereIn('status', ['present', 'absent', 'leave', 'half_day'])
            ->whereHas('employee', function ($q) use ($today) {
                $q->where('status', 'active')
                    ->whereDate('duty_joined_date', '<=', $today);
            });

        $todayAttendance = $todayAttendanceQuery->count();

        $todayStatusCounts = $todayAttendanceQuery
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $todayPresent = $todayStatusCounts['present'] ?? 0;
        $todayAbsent = $todayStatusCounts['absent'] ?? 0;
        $todayHalfDay = $todayStatusCounts['half_day'] ?? 0;
        $todayLeave = $todayStatusCounts['leave'] ?? 0;

        // ================= MONTHLY STATS (CACHED) =================
        $holidayVersion = Attendance::where('status', 'holiday')->max('updated_at');

        $monthlyStats = cache()->remember(
            "dashboard_monthly_stats_{$currentMonth}_{$currentYear}_{$holidayVersion}",
            now()->addMinutes(10),
            fn() => $this->getMonthlyAttendanceStats($currentMonth, $currentYear)
        );

        // ================= DOCUMENT ALERTS =================
        $expiringDocuments = $this->getExpiringDocuments();

        // ================= RECENT ATTENDANCE =================
        $recentAttendances = Attendance::with('employee')
            ->whereHas('employee', fn($q) => $q->where('status', 'active'))
            ->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'onVacation',
            'inactiveEmployees',
            'totalEntities',
            'activeEntities',
            'totalVehicles',
            'activeVehicles',
            'todayAttendance',
            'todayPresent',
            'todayAbsent',
            'todayHalfDay',
            'todayLeave',
            'monthlyStats',
            'recentAttendances',
            'expiringDocuments'
        ));
    }

    // ================= MONTHLY ATTENDANCE =================
    private function getMonthlyAttendanceStats($month, $year)
    {
        $yesterday = Carbon::yesterday();

        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();
        if ($month == now()->month && $year == now()->year) {
            $periodEnd = $yesterday;
        }

        // Active employees valid in this period
        $employees = Employee::where('status', 'active')
            ->whereDate('duty_joined_date', '<=', $periodEnd)
            ->get(['id', 'duty_joined_date']);

        if ($employees->isEmpty()) {
            return $this->emptyMonthlyStats();
        }

        // Cache holiday dates ONCE
        $holidayDates = $this->getHolidayDateArray($month, $year);

        // ===== Expected Attendance =====
        $expectedAttendance = 0;

        foreach ($employees as $employee) {
            $start = Carbon::parse($employee->duty_joined_date);
            if ($start->month != $month || $start->year != $year) {
                $start = Carbon::create($year, $month, 1);
            }

            $expectedAttendance += $this->countWorkingDaysBetween(
                $start,
                $periodEnd,
                $holidayDates
            );
        }

        // ===== Attendance Summary (DEDUPED) =====
        $attendance = Attendance::select(
            'status',
            DB::raw('COUNT(DISTINCT CONCAT(employee_id, "-", attendance_date)) as total'),
            DB::raw('AVG(total_hours) as avg_hours')
        )
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->whereDate('attendance_date', '<=', $yesterday)
            ->whereHas('employee', fn($q) => $q->where('status', 'active'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $present   = $attendance['present']->total   ?? 0;
        $absent    = $attendance['absent']->total    ?? 0;
        $leave     = $attendance['leave']->total     ?? 0;
        $halfDay   = $attendance['half_day']->total  ?? 0;

        // ✅ Half day = 0.5
        $effectivePresent = $present + ($halfDay * 0.5);

        // ===== Percentages (CLAMPED) =====
        $attendanceRate = $expectedAttendance > 0
            ? min(100, round(($effectivePresent / $expectedAttendance) * 100, 1))
            : 0;

        $absenteeismRate = $expectedAttendance > 0
            ? round(($absent / $expectedAttendance) * 100, 1)
            : 0;

        // Average hours (present + half day)
        $averageHours = collect(['present', 'half_day'])
            ->map(fn($s) => $attendance[$s]->avg_hours ?? 0)
            ->avg();

        return [
            'attendance_rate'     => $attendanceRate,
            'absenteeism_rate'    => $absenteeismRate,
            'monthly_present'     => $present,
            'monthly_absent'      => $absent,
            'monthly_leave'       => $leave,
            'monthly_half_day'    => $halfDay,
            'average_hours'       => round($averageHours, 1),
            'expected_attendance' => $expectedAttendance,
            'working_days'        => $this->getWorkingDays($month, $year),
            'holiday_days'        => count($holidayDates),
            'active_employees'    => $employees->count(),
        ];
    }

    private function countWorkingDaysBetween($start, $end, array $holidays)
    {
        $days = 0;
        $date = $start->copy();

        while ($date->lte($end)) {
            if (
                $date->dayOfWeek !== Carbon::SUNDAY &&
                !in_array($date->toDateString(), $holidays)
            ) {
                $days++;
            }
            $date->addDay();
        }

        return $days;
    }

    // ================= WORKING / HOLIDAY DAYS =================
    private function getWorkingDays($month, $year)
    {
        $today = Carbon::today();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        if ($month == $today->month && $year == $today->year) {
            $endDate = Carbon::yesterday();
        }

        $workingDays = 0;
        $date = Carbon::create($year, $month, 1);

        while ($date->lte($endDate)) {
            if ($date->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $date->addDay();
        }

        return max(0, $workingDays - $this->getHolidayDays($month, $year));
    }

    private function getHolidayDays($month, $year)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $query = Attendance::whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->where('status', 'holiday')
            ->groupBy('attendance_date');

        // Only up to yesterday for current month
        if ($month == $today->month && $year == $today->year) {
            $query->whereDate('attendance_date', '<=', $yesterday);
        }

        return $query
            ->select(DB::raw('DATE(attendance_date) as date'))
            ->distinct()
            ->get()
            ->filter(fn ($row) =>
                Carbon::parse($row->date)->dayOfWeek !== Carbon::SUNDAY
            )
            ->count();

    }

    private function getHolidayDateArray($month, $year)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $query = Attendance::whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->where('status', 'holiday')
            ->select(DB::raw('DATE(attendance_date) as date'))
            ->distinct();

        if ($month == $today->month && $year == $today->year) {
            $query->whereDate('attendance_date', '<=', $yesterday);
        }

        return $query->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->toArray();

    }

    private function emptyMonthlyStats()
    {
        return [
            'attendance_rate' => 0,
            'absenteeism_rate' => 0,
            'monthly_present' => 0,
            'monthly_absent' => 0,
            'monthly_leave' => 0,
            'monthly_half_day' => 0,
            'average_hours' => 0,
            'expected_attendance' => 0,
            'working_days' => 0,
            'holiday_days' => 0,
            'active_employees' => 0,
        ];
    }

    // ================= DOCUMENT ALERTS =================
    private function getExpiringDocuments()
    {
        $today = Carbon::today();
        $alerts = collect();

        $this->collectDocumentAlerts(Employee::where('status', 'active')->get(), [
            'passport_expiry_date' => 'Passport',
            'visa_expiry_date' => 'Visa',
            'visit_expiry_date' => 'Visit Permit',
            'eid_expiry_date' => 'EID',
            'health_insurance_expiry_date' => 'Health Insurance',
            'driving_license_expiry_date' => 'Driving License',
        ], 'Employee', $alerts, $today);

        $this->collectDocumentAlerts(Entity::where('status', 'active')->get(), [
            'trade_license_renewal_date' => 'Trade License',
            'est_card_renewal_date' => 'EST Card',
            'warehouse_ejari_renewal_date' => 'Warehouse EJARI',
            'camp_ejari_renewal_date' => 'Camp EJARI',
            'workman_insurance_expiry_date' => 'Workman Insurance',
        ], 'Entity', $alerts, $today);

        $this->collectDocumentAlerts(Vehicle::where('status', 'active')->get(), [
            'mulkiya_expiry_date' => 'Mulkiya',
            'driving_license_expiry_date' => 'Driving License',
        ], 'Vehicle', $alerts, $today);

        return $alerts->sortBy('days_until_expiry')->take(8);
    }

    private function collectDocumentAlerts($models, $fields, $category, &$alerts, $today)
    {
        foreach ($models as $model) {
            foreach ($fields as $field => $name) {
                $expiry = $model->$field;
                if (!$expiry) continue;

                $expiry = Carbon::parse($expiry);
                $days = $today->diffInDays($expiry, false);
                if ($days > 90) continue;

                [$label, $class] = match (true) {
                    $days < 0 => ['Expired', 'danger'],
                    $days <= 30 => ['Critical', 'danger'],
                    $days <= 60 => ['Warning', 'warning'],
                    default => ['Notice', 'info'],
                };

                $alerts->push([
                    'category' => $category,
                    'name' => $category === 'Employee' ? $model->employee_name : ($category === 'Entity' ? $model->entity_name : $model->vehicle_name),
                    'identifier' => $category === 'Employee' ? $model->staff_number : ($category === 'Vehicle' ? $model->vehicle_number : null),
                    'document_name' => $name,
                    'expiry_date' => $expiry,
                    'days_until_expiry' => $days,
                    'status_label' => $label,
                    'status_class' => $class,
                ]);
            }
        }
    }
}
