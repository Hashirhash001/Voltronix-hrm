<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Entity;
use App\Models\Vehicle;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        // Employee Statistics
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $onVacation = Employee::where('status', 'vacation')->count();
        $inactiveEmployees = Employee::where('status', 'inactive')->count();

        // Entity & Vehicle Statistics
        $totalEntities = Entity::count();
        $activeEntities = Entity::where('status', 'active')->count();
        $totalVehicles = Vehicle::count();
        $activeVehicles = Vehicle::where('status', 'active')->count();

        // Today's Attendance Statistics
        $todayAttendance = Attendance::whereDate('attendance_date', $today)->count();
        $todayPresent = Attendance::whereDate('attendance_date', $today)->where('status', 'present')->count();
        $todayAbsent = Attendance::whereDate('attendance_date', $today)->where('status', 'absent')->count();
        $todayHalfDay = Attendance::whereDate('attendance_date', $today)->where('status', 'half_day')->count();
        $todayLeave = Attendance::whereDate('attendance_date', $today)->where('status', 'leave')->count();

        // Monthly Statistics with Performance Metrics
        $monthlyStats = $this->getMonthlyAttendanceStats($currentMonth, $currentYear);

        // Document Expiry Alerts (All sources: Employee, Entity, Vehicle)
        $expiringDocuments = $this->getExpiringDocuments();

        // Recent Attendances
        $recentAttendances = Attendance::with(['employee'])
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

    private function getMonthlyAttendanceStats($currentMonth, $currentYear)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday(); // ✅ Calculate up to yesterday
        $activeEmployees = Employee::where('status', 'active')->count();

        // Get actual working days (excluding holidays marked in attendance)
        $workingDaysThisMonth = $this->getWorkingDays($currentMonth, $currentYear);

        // Total possible attendance (active employees × actual working days)
        $expectedAttendance = $activeEmployees * $workingDaysThisMonth;

        // Get attendance counts (up to yesterday only)
        $monthlyPresent = Attendance::whereMonth('attendance_date', $currentMonth)
                                   ->whereYear('attendance_date', $currentYear)
                                   ->whereDate('attendance_date', '<=', $yesterday) // ✅ Only up to yesterday
                                   ->where('status', 'present')
                                   ->count();

        $monthlyAbsent = Attendance::whereMonth('attendance_date', $currentMonth)
                                  ->whereYear('attendance_date', $currentYear)
                                  ->whereDate('attendance_date', '<=', $yesterday) // ✅ Only up to yesterday
                                  ->where('status', 'absent')
                                  ->count();

        $monthlyLeave = Attendance::whereMonth('attendance_date', $currentMonth)
                                 ->whereYear('attendance_date', $currentYear)
                                 ->whereDate('attendance_date', '<=', $yesterday) // ✅ Only up to yesterday
                                 ->where('status', 'leave')
                                 ->count();

        $monthlyHalfDay = Attendance::whereMonth('attendance_date', $currentMonth)
                                   ->whereYear('attendance_date', $currentYear)
                                   ->whereDate('attendance_date', '<=', $yesterday) // ✅ Only up to yesterday
                                   ->where('status', 'half_day')
                                   ->count();

        // Calculate percentages based on actual working days
        $attendanceRate = $expectedAttendance > 0 ? round(($monthlyPresent / $expectedAttendance) * 100, 1) : 0;
        $absenteeismRate = $expectedAttendance > 0 ? round(($monthlyAbsent / $expectedAttendance) * 100, 1) : 0;

        // Average working hours (only for present and half_day statuses, up to yesterday)
        $averageHours = Attendance::whereMonth('attendance_date', $currentMonth)
                                  ->whereYear('attendance_date', $currentYear)
                                  ->whereDate('attendance_date', '<=', $yesterday) // ✅ Only up to yesterday
                                  ->whereIn('status', ['present', 'half_day'])
                                  ->avg('total_hours') ?? 0;

        // Get holiday days count for display
        $holidayDays = $this->getHolidayDays($currentMonth, $currentYear);

        return [
            'attendance_rate' => $attendanceRate,
            'absenteeism_rate' => $absenteeismRate,
            'monthly_present' => $monthlyPresent,
            'monthly_absent' => $monthlyAbsent,
            'monthly_leave' => $monthlyLeave,
            'monthly_half_day' => $monthlyHalfDay,
            'average_hours' => round($averageHours, 1),
            'expected_attendance' => $expectedAttendance,
            'working_days' => $workingDaysThisMonth,
            'holiday_days' => $holidayDays,
            'active_employees' => $activeEmployees,
        ];
    }

    /**
     * Get working days excluding Sundays and holidays
     * ✅ Only counts up to YESTERDAY (not today)
     */
    private function getWorkingDays($month, $year)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $startDate = Carbon::create($year, $month, 1);
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // ✅ Only count up to YESTERDAY if current month
        if ($month == $today->month && $year == $today->year) {
            $endDate = $yesterday; // Changed from $today to $yesterday
        }

        $workingDays = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Exclude Sundays (weekend)
            if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        // Subtract holidays from working days
        $holidayDays = $this->getHolidayDays($month, $year);

        return max(0, $workingDays - $holidayDays);
    }

    /**
     * Get the number of days marked as holiday for ALL employees
     * A day is considered a holiday if all active employees have 'holiday' status
     * ✅ Only counts holidays up to YESTERDAY
     */
    private function getHolidayDays($month, $year)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $activeEmployees = Employee::where('status', 'active')->count();

        if ($activeEmployees === 0) {
            return 0;
        }

        // ✅ Only count holidays up to yesterday if current month
        $query = Attendance::select('attendance_date', DB::raw('COUNT(DISTINCT employee_id) as employee_count'))
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->where('status', 'holiday');

        // If current month, only count up to yesterday
        if ($month == $today->month && $year == $today->year) {
            $query->whereDate('attendance_date', '<=', $yesterday);
        }

        $holidayDates = $query->groupBy('attendance_date')
            ->havingRaw('COUNT(DISTINCT employee_id) = ?', [$activeEmployees])
            ->get()
            ->map(function ($item) {
                return Carbon::parse($item->attendance_date);
            });

        // Filter out Sundays (since they're already excluded from working days)
        $holidayCount = $holidayDates->filter(function ($date) {
            return $date->dayOfWeek !== Carbon::SUNDAY;
        })->count();

        return $holidayCount;
    }

    private function getExpiringDocuments()
    {
        $today = Carbon::today();
        $allAlerts = collect();

        // Employee Documents
        $employeeDocuments = [
            'passport_expiry_date' => 'Passport',
            'visa_expiry_date' => 'Visa',
            'visit_expiry_date' => 'Visit Permit',
            'eid_expiry_date' => 'EID',
            'health_insurance_expiry_date' => 'Health Insurance',
            'driving_license_expiry_date' => 'Driving License',
        ];

        $employees = Employee::where('status', 'active')->get();
        foreach ($employees as $employee) {
            foreach ($employeeDocuments as $field => $documentName) {
                $alert = $this->createDocumentAlert($employee, $field, $documentName, 'Employee', $today);
                if ($alert) {
                    $allAlerts->push($alert);
                }
            }
        }

        // Entity Documents
        $entityDocuments = [
            'trade_license_renewal_date' => 'Trade License',
            'est_card_renewal_date' => 'EST Card',
            'warehouse_ejari_renewal_date' => 'Warehouse EJARI',
            'camp_ejari_renewal_date' => 'Camp EJARI',
            'workman_insurance_expiry_date' => 'Workman Insurance',
        ];

        $entities = Entity::where('status', 'active')->get();
        foreach ($entities as $entity) {
            foreach ($entityDocuments as $field => $documentName) {
                $alert = $this->createDocumentAlert($entity, $field, $documentName, 'Entity', $today);
                if ($alert) {
                    $allAlerts->push($alert);
                }
            }
        }

        // Vehicle Documents
        $vehicleDocuments = [
            'mulkiya_expiry_date' => 'Mulkiya',
            'driving_license_expiry_date' => 'Driving License',
        ];

        $vehicles = Vehicle::where('status', 'active')->get();
        foreach ($vehicles as $vehicle) {
            foreach ($vehicleDocuments as $field => $documentName) {
                $alert = $this->createDocumentAlert($vehicle, $field, $documentName, 'Vehicle', $today);
                if ($alert) {
                    $allAlerts->push($alert);
                }
            }
        }

        return $allAlerts->sortBy('days_until_expiry')->take(8);
    }

    private function createDocumentAlert($model, $field, $documentName, $category, $today)
    {
        $expiryDate = $model->getAttribute($field);

        if (!$expiryDate) {
            return null;
        }

        $expiryDate = Carbon::parse($expiryDate);
        $daysUntilExpiry = $today->diffInDays($expiryDate, false);

        // Only show documents expiring within 90 days or already expired
        if ($daysUntilExpiry > 90) {
            return null;
        }

        // Determine status
        if ($daysUntilExpiry < 0) {
            $statusLabel = 'Expired';
            $statusClass = 'danger';
        } elseif ($daysUntilExpiry <= 30) {
            $statusLabel = 'Critical';
            $statusClass = 'danger';
        } elseif ($daysUntilExpiry <= 60) {
            $statusLabel = 'Warning';
            $statusClass = 'warning';
        } else {
            $statusLabel = 'Notice';
            $statusClass = 'info';
        }

        // Get name based on category
        switch ($category) {
            case 'Employee':
                $name = $model->employee_name;
                $identifier = $model->staff_number;
                break;
            case 'Entity':
                $name = $model->entity_name;
                $identifier = null;
                break;
            case 'Vehicle':
                $name = $model->vehicle_name;
                $identifier = $model->vehicle_number;
                break;
            default:
                $name = 'Unknown';
                $identifier = null;
        }

        return [
            'category' => $category,
            'name' => $name,
            'identifier' => $identifier,
            'document_name' => $documentName,
            'expiry_date' => $expiryDate,
            'days_until_expiry' => $daysUntilExpiry,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
        ];
    }
}
