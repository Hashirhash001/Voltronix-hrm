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

        // Employee statistics
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $onVacation = Employee::where('status', 'vacation')->count();

        // Today's attendance
        $todayAttendance = Attendance::whereDate('attendance_date', $today)->count();
        $todayPresent = Attendance::whereDate('attendance_date', $today)
                                  ->where('status', 'present')
                                  ->count();

        // Document expiry alerts (3 months)
        $expiringDocuments = $this->getExpiringDocuments($today, $threeMonthsLater);

        // Overtime this month
        $currentMonth = $today->month;
        $currentYear = $today->year;
        $overtimeThisMonth = OvertimeRecord::whereMonth('overtime_date', $currentMonth)
                                          ->whereYear('overtime_date', $currentYear)
                                          ->where('status', 'approved')
                                          ->sum('overtime_hours');

        // Employees with overtime indicators (FIXED QUERY)
        $overtimeEmployees = $this->getOvertimeEmployees($currentMonth, $currentYear);

        // Attendance analytics
        $attendanceAnalytics = $this->getAttendanceAnalytics($currentMonth, $currentYear);

        return view('dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'onVacation',
            'todayAttendance',
            'todayPresent',
            'expiringDocuments',
            'overtimeThisMonth',
            'overtimeEmployees',
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
        // FIXED: Use proper grouping with all selected columns or use subquery
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
            ->get();
    }

    private function getAttendanceAnalytics($month, $year)
    {
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
                                             ->sum('overtime_hours'),
        ];

        // Daily attendance trend
        $analytics['daily_trend'] = Attendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->selectRaw('DATE(attendance_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $analytics;
    }
}
