<?php
// app/Http/Controllers/AttendanceController.php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with('employee');

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('attendance_date', $request->date);
        } else {
            $query->whereDate('attendance_date', Carbon::today());
        }

        // Filter by employee
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')
                            ->paginate(50);

        $employees = Employee::where('status', 'active')
                            ->orderBy('employee_name')
                            ->get();

        return view('attendances.index', compact('attendances', 'employees'));
    }

    public function create()
    {
        $employees = Employee::where('status', 'active')
                            ->orderBy('employee_name')
                            ->get();
        return view('attendances.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'status' => 'required|in:present,absent,half_day,leave,holiday',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::find($validated['employee_id']);

        DB::beginTransaction();
        try {
            // Create attendance record
            $attendance = Attendance::create([
                'employee_id' => $validated['employee_id'],
                'staff_number' => $employee->staff_number,
                'attendance_date' => $validated['attendance_date'],
                'check_in_time' => $validated['attendance_date'] . ' ' . $validated['check_in_time'],
                'check_out_time' => isset($validated['check_out_time'])
                    ? $validated['attendance_date'] . ' ' . $validated['check_out_time']
                    : null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Calculate hours if check out time is provided
            if ($attendance->check_out_time) {
                $attendance->calculateHours();

                // Create overtime record if applicable
                if ($attendance->hasOvertime()) {
                    $overtimeRecord = OvertimeRecord::create([
                        'employee_id' => $attendance->employee_id,
                        'attendance_id' => $attendance->id,
                        'overtime_date' => $attendance->attendance_date,
                        'overtime_hours' => $attendance->overtime_hours,
                        'overtime_rate' => 1.5,
                        'status' => 'pending',
                    ]);
                    $overtimeRecord->calculateOvertimeAmount();
                }
            }

            DB::commit();

            return redirect()->route('attendances.index')
                           ->with('success', 'Attendance recorded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])
                        ->withInput();
        }
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.employee_id' => 'required|exists:employees,id',
            'attendances.*.check_in_time' => 'required|date_format:H:i',
            'attendances.*.check_out_time' => 'nullable|date_format:H:i',
            'attendances.*.status' => 'required|in:present,absent,half_day,leave,holiday',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['attendances'] as $attendanceData) {
                $employee = Employee::find($attendanceData['employee_id']);

                $attendance = Attendance::updateOrCreate(
                    [
                        'employee_id' => $attendanceData['employee_id'],
                        'attendance_date' => $validated['attendance_date'],
                    ],
                    [
                        'staff_number' => $employee->staff_number,
                        'check_in_time' => $validated['attendance_date'] . ' ' . $attendanceData['check_in_time'],
                        'check_out_time' => isset($attendanceData['check_out_time'])
                            ? $validated['attendance_date'] . ' ' . $attendanceData['check_out_time']
                            : null,
                        'status' => $attendanceData['status'],
                    ]
                );

                if ($attendance->check_out_time) {
                    $attendance->calculateHours();

                    if ($attendance->hasOvertime()) {
                        $overtimeRecord = OvertimeRecord::updateOrCreate(
                            [
                                'attendance_id' => $attendance->id,
                            ],
                            [
                                'employee_id' => $attendance->employee_id,
                                'overtime_date' => $attendance->attendance_date,
                                'overtime_hours' => $attendance->overtime_hours,
                                'overtime_rate' => 1.5,
                                'status' => 'pending',
                            ]
                        );
                        $overtimeRecord->calculateOvertimeAmount();
                    }
                }
            }

            DB::commit();

            return redirect()->route('attendances.index')
                           ->with('success', 'Bulk attendance recorded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])
                        ->withInput();
        }
    }

    // Export to CSV
    public function export(Request $request)
    {
        $query = Attendance::with('employee');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('attendance_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->get();

        $filename = 'attendance_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Date', 'Staff Number', 'Employee Name', 'Check In',
                'Check Out', 'Regular Hours', 'Overtime Hours',
                'Total Hours', 'Status'
            ]);

            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->attendance_date->format('Y-m-d'),
                    $attendance->staff_number,
                    $attendance->employee->employee_name,
                    $attendance->check_in_time?->format('H:i:s'),
                    $attendance->check_out_time?->format('H:i:s'),
                    $attendance->regular_hours,
                    $attendance->overtime_hours,
                    $attendance->total_hours,
                    $attendance->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
