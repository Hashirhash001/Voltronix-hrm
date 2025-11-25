<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\DocumentExpiryAlert;
use App\Models\OvertimeRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $threeMonthsLater = $today->copy()->addMonths(3);

        // Employee Statistics
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $onVacation = Employee::where('status', 'vacation')->count();
        $inactiveEmployees = Employee::where('status', 'inactive')->count();

        // Today's Attendance Statistics
        $todayAttendance = Attendance::whereDate('attendance_date', $today)->count();
        $todayPresent = Attendance::whereDate('attendance_date', $today)
                                  ->where('status', 'present')
                                  ->count();
        $todayAbsent = Attendance::whereDate('attendance_date', $today)
                                 ->where('status', 'absent')
                                 ->count();
        $todayLeave = Attendance::whereDate('attendance_date', $today)
                                ->where('status', 'leave')
                                ->count();

        // This Month's Statistics
        $currentMonth = $today->month;
        $currentYear = $today->year;

        $monthlyAttendance = Attendance::whereMonth('attendance_date', $currentMonth)
                                      ->whereYear('attendance_date', $currentYear)
                                      ->count();

        $monthlyPresent = Attendance::whereMonth('attendance_date', $currentMonth)
                                   ->whereYear('attendance_date', $currentYear)
                                   ->where('status', 'present')
                                   ->count();

        // Overtime Statistics
        $monthlyOvertime = OvertimeRecord::whereMonth('overtime_date', $currentMonth)
                                        ->whereYear('overtime_date', $currentYear)
                                        ->where('status', 'approved')
                                        ->sum('overtime_hours') ?? 0;

        $overtimeThisMonth = $monthlyOvertime;

        $pendingOvertimeRequests = OvertimeRecord::where('status', 'pending')->count();

        // Recent Attendances
        $recentAttendances = Attendance::with(['employee.user'])
                                      ->orderBy('attendance_date', 'desc')
                                      ->limit(10)
                                      ->get() ?? collect();

        // Top Overtime Employees (Current Month)
        $topOvertimeEmployees = $this->getOvertimeEmployees($currentMonth, $currentYear) ?? collect();

        // Document Expiry Alerts (limit to 5 for dashboard)
        $employees = Employee::where('status', 'active')->get() ?? collect();
        $expiringDocuments = collect();

        $documentTypes = [
            'passport_expiry_date' => 'Passport',
            'visa_expiry_date' => 'Visa',
            'visit_expiry_date' => 'Visit Permit',
            'eid_expiry_date' => 'EID',
            'health_insurance_expiry_date' => 'Health Insurance',
            'driving_license_expiry_date' => 'Driving License',
        ];

        foreach ($employees as $employee) {
            foreach ($documentTypes as $field => $documentName) {
                if ($employee->$field) {
                    $expiryDate = Carbon::parse($employee->$field);
                    $daysUntilExpiry = $today->diffInDays($expiryDate, false);

                    if ($daysUntilExpiry <= 90) {
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

                        $expiringDocuments->push([
                            'employee' => $employee,
                            'document_name' => $documentName,
                            'expiry_date' => $expiryDate,
                            'days_until_expiry' => $daysUntilExpiry,
                            'status_label' => $statusLabel,
                            'status_class' => $statusClass,
                        ]);
                    }
                }
            }
        }

        $expiringDocuments = $expiringDocuments->sortBy('days_until_expiry');

        // Attendance Analytics
        $attendanceAnalytics = $this->getAttendanceAnalytics($currentMonth, $currentYear) ?? [];

        return view('dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'onVacation',
            'inactiveEmployees',
            'todayAttendance',
            'todayPresent',
            'todayAbsent',
            'todayLeave',
            'monthlyAttendance',
            'monthlyPresent',
            'monthlyOvertime',
            'overtimeThisMonth',
            'pendingOvertimeRequests',
            'recentAttendances',
            'topOvertimeEmployees',
            'expiringDocuments',
            'attendanceAnalytics'
        ));
    }

    private function getExpiringDocuments($startDate, $endDate)
    {
        $employees = Employee::where('status', 'active')->get();
        $expiringDocs = [];

        foreach ($employees as $employee) {
            $documents = [
                'passport_expiry_date' => 'Passport',
                'visa_expiry_date' => 'Visa',
                'health_insurance_expiry_date' => 'Health Insurance',
                'driving_license_expiry_date' => 'Driving License',
                'eid_expiry_date' => 'EID',
            ];

            foreach ($documents as $field => $name) {
                if ($employee->$field) {
                    $expiryDate = Carbon::parse($employee->$field);

                    if ($expiryDate->between($startDate, $endDate) || $expiryDate->isPast()) {
                        $daysUntilExpiry = $startDate->diffInDays($expiryDate, false);

                        $expiringDocs[] = [
                            'employee' => $employee,
                            'document_type' => $name,
                            'expiry_date' => $expiryDate,
                            'days_until_expiry' => $daysUntilExpiry,
                            'status' => $this->getExpiryStatus($daysUntilExpiry),
                        ];
                    }
                }
            }
        }

        return collect($expiringDocs)->sortBy('days_until_expiry');
    }

    private function getExpiryStatus($days)
    {
        if ($days < 0) {
            return ['label' => 'Expired', 'class' => 'danger', 'color' => '#dc3545'];
        } elseif ($days <= 30) {
            return ['label' => 'Critical', 'class' => 'danger', 'color' => '#dc3545'];
        } elseif ($days <= 60) {
            return ['label' => 'Warning', 'class' => 'warning', 'color' => '#ffc107'];
        } else {
            return ['label' => 'Notice', 'class' => 'info', 'color' => '#17a2b8'];
        }
    }

    private function getOvertimeEmployees($month, $year)
    {
        try {
            return Employee::select(
                    'employees.id',
                    'employees.staff_number',
                    'employees.employee_name',
                    'employees.designation',
                    DB::raw('SUM(overtime_records.overtime_hours) as total_overtime')
                )
                ->join('overtime_records', 'employees.id', '=', 'overtime_records.employee_id')
                ->whereMonth('overtime_records.overtime_date', $month)
                ->whereYear('overtime_records.overtime_date', $year)
                ->where('overtime_records.status', 'approved')
                ->groupBy(
                    'employees.id',
                    'employees.staff_number',
                    'employees.employee_name',
                    'employees.designation'
                )
                ->having('total_overtime', '>', 0)
                ->orderBy('total_overtime', 'desc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    private function getAttendanceAnalytics($month, $year)
    {
        try {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $analytics = [
                'total_days' => $endDate->day,
                'present' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                      ->where('status', 'present')
                                      ->count(),
                'absent' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                     ->where('status', 'absent')
                                     ->count(),
                'leave' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                    ->where('status', 'leave')
                                    ->count(),
                'half_day' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                       ->where('status', 'half_day')
                                       ->count(),
                'average_hours' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                            ->avg('total_hours') ?? 0,
                'total_overtime' => OvertimeRecord::whereBetween('overtime_date', [$startDate, $endDate])
                                                 ->where('status', 'approved')
                                                 ->sum('overtime_hours') ?? 0,
            ];

            $analytics['daily_trend'] = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->selectRaw('DATE(attendance_date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return $analytics;
        } catch (\Exception $e) {
            return [
                'total_days' => 0,
                'present' => 0,
                'absent' => 0,
                'leave' => 0,
                'half_day' => 0,
                'average_hours' => 0,
                'total_overtime' => 0,
                'daily_trend' => collect()
            ];
        }
    }
}
