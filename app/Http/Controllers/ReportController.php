<?php
// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\OvertimeRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function analytics(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get analytics data
        $totalEmployees = Employee::where('status', 'active')->count();

        $attendanceStats = [
            'total_present' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                        ->where('status', 'present')
                                        ->count(),
            'total_absent' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                       ->where('status', 'absent')
                                       ->count(),
            'total_leave' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                      ->where('status', 'leave')
                                      ->count(),
            'average_hours' => Attendance::whereBetween('attendance_date', [$startDate, $endDate])
                                        ->avg('total_hours'),
        ];

        $overtimeStats = [
            'total_hours' => OvertimeRecord::whereBetween('overtime_date', [$startDate, $endDate])
                                          ->where('status', 'approved')
                                          ->sum('overtime_hours'),
            'total_amount' => OvertimeRecord::whereBetween('overtime_date', [$startDate, $endDate])
                                           ->where('status', 'approved')
                                           ->sum('overtime_amount'),
        ];

        return view('reports.analytics', compact(
            'totalEmployees',
            'attendanceStats',
            'overtimeStats',
            'month',
            'year'
        ));
    }

    public function export(Request $request)
    {
        // Export logic here
    }
}
