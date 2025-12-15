<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Entity;
use App\Models\Vehicle;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $workingDaysThisMonth = $this->getWorkingDays($currentMonth, $currentYear);
        $activeEmployees = Employee::where('status', 'active')->count();

        // Total possible attendance (active employees × working days so far)
        $currentDay = min($today->day, $workingDaysThisMonth);
        $expectedAttendance = $activeEmployees * $currentDay;

        $monthlyPresent = Attendance::whereMonth('attendance_date', $currentMonth)
                                   ->whereYear('attendance_date', $currentYear)
                                   ->where('status', 'present')
                                   ->count();

        $monthlyAbsent = Attendance::whereMonth('attendance_date', $currentMonth)
                                  ->whereYear('attendance_date', $currentYear)
                                  ->where('status', 'absent')
                                  ->count();

        $monthlyLeave = Attendance::whereMonth('attendance_date', $currentMonth)
                                 ->whereYear('attendance_date', $currentYear)
                                 ->where('status', 'leave')
                                 ->count();

        $monthlyHalfDay = Attendance::whereMonth('attendance_date', $currentMonth)
                                   ->whereYear('attendance_date', $currentYear)
                                   ->where('status', 'half_day')
                                   ->count();

        // Calculate percentages
        $attendanceRate = $expectedAttendance > 0 ? round(($monthlyPresent / $expectedAttendance) * 100, 1) : 0;
        $absenteeismRate = $expectedAttendance > 0 ? round(($monthlyAbsent / $expectedAttendance) * 100, 1) : 0;

        // Average working hours
        $averageHours = Attendance::whereMonth('attendance_date', $currentMonth)
                                  ->whereYear('attendance_date', $currentYear)
                                  ->whereIn('status', ['present', 'half_day'])
                                  ->avg('total_hours') ?? 0;

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
            'active_employees' => $activeEmployees,
        ];
    }

    private function getWorkingDays($month, $year)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $today = Carbon::today();

        // Only count up to today if current month
        if ($month == $today->month && $year == $today->year) {
            $endDate = $today;
        }

        $workingDays = 0;
        while ($startDate->lte($endDate)) {
            // Exclude Fridays (adjust for your country's weekend)
            if ($startDate->dayOfWeek !== Carbon::FRIDAY) {
                $workingDays++;
            }
            $startDate->addDay();
        }

        return $workingDays;
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
