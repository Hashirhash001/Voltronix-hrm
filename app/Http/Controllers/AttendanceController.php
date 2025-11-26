<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\OvertimeRecord;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with('employee');

        // Filter by date
        if ($request->has('date') && $request->date) {
            $query->whereDate('attendance_date', $request->date);
        } else {
            $query->whereDate('attendance_date', Carbon::today());
        }

        // Filter by employee
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->paginate(15);
        $employees = Employee::where('status', 'active')->orderBy('employee_name')->get();

        // Calculate statistics
        $stats = [
            'present' => Attendance::whereDate('attendance_date', $request->date ?? Carbon::today())->where('status', 'present')->count(),
            'absent' => Attendance::whereDate('attendance_date', $request->date ?? Carbon::today())->where('status', 'absent')->count(),
            'half_day' => Attendance::whereDate('attendance_date', $request->date ?? Carbon::today())->where('status', 'half_day')->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'attendances' => $attendances->items(),
                'pagination' => [
                    'current_page' => $attendances->currentPage(),
                    'last_page' => $attendances->lastPage(),
                    'per_page' => $attendances->perPage(),
                    'total' => $attendances->total(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('attendances.index', compact('attendances', 'employees', 'stats'));
    }

    public function generateToday(Request $request)
    {
        $date = $request->date ?? Carbon::today()->format('Y-m-d');

        try {
            DB::beginTransaction();

            $employees = Employee::where('status', 'active')->get();
            $created = 0;

            foreach ($employees as $employee) {
                // Only create if doesn't exist
                $exists = Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $date)
                    ->exists();

                if (!$exists) {
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'staff_number' => $employee->staff_number,
                        'attendance_date' => $date,
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'status' => 'absent',
                        'notes' => null,
                    ]);
                    $created++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$created} attendance records generated successfully!",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function quickUpdate(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,half_day,leave,holiday',
        ]);

        try {
            DB::beginTransaction();

            $attendance->update([
                'check_in_time' => $validated['check_in_time']
                    ? $attendance->attendance_date->format('Y-m-d') . ' ' . $validated['check_in_time']
                    : null,
                'check_out_time' => $validated['check_out_time']
                    ? $attendance->attendance_date->format('Y-m-d') . ' ' . $validated['check_out_time']
                    : null,
                'status' => $validated['status'],
            ]);

            if ($attendance->check_in_time && $attendance->check_out_time) {
                $attendance->calculateHours();

                if ($attendance->hasOvertime()) {
                    $overtimeRecord = OvertimeRecord::updateOrCreate(
                        ['attendance_id' => $attendance->id],
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully!',
                'attendance' => $attendance->fresh()->load('employee'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance: ' . $e->getMessage(),
            ], 422);
        }
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

        try {
            DB::beginTransaction();

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

            if ($attendance->check_out_time) {
                $attendance->calculateHours();

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

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record attendance: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function edit(Attendance $attendance)
    {
        $attendance->load('employee');
        return response()->json([
            'success' => true,
            'attendance' => $attendance,
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'check_in_time' => 'required|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'status' => 'required|in:present,absent,half_day,leave,holiday',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $attendance->update([
                'check_in_time' => $attendance->attendance_date->format('Y-m-d') . ' ' . $validated['check_in_time'],
                'check_out_time' => isset($validated['check_out_time'])
                    ? $attendance->attendance_date->format('Y-m-d') . ' ' . $validated['check_out_time']
                    : null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($attendance->check_out_time) {
                $attendance->calculateHours();

                if ($attendance->hasOvertime()) {
                    $overtimeRecord = OvertimeRecord::updateOrCreate(
                        ['attendance_id' => $attendance->id],
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Attendance $attendance)
    {
        try {
            DB::beginTransaction();

            // Delete related overtime record if exists
            if ($attendance->overtimeRecord) {
                $attendance->overtimeRecord->delete();
            }

            $attendance->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully!',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attendance: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:employees,id',
            'format' => 'required|in:csv,pdf'
        ]);

        $query = Attendance::with('employee')
            ->whereBetween('attendance_date', [$request->start_date, $request->end_date]);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        $attendances = $query->orderBy('attendance_date', 'asc')
                            ->orderBy('staff_number', 'asc')
                            ->get();

        if ($request->format === 'csv') {
            return $this->exportToCsv($attendances, $request->start_date, $request->end_date);
        } else {
            return $this->exportToPdf($attendances, $request->start_date, $request->end_date);
        }
    }

    private function exportToCsv($attendances, $startDate, $endDate)
    {
        $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Add headers
            fputcsv($file, [
                'Date',
                'Staff Number',
                'Employee Name',
                'Check In',
                'Check Out',
                'Regular Hours',
                'Overtime Hours',
                'Total Hours',
                'Status',
                'Notes'
            ]);

            // Add data rows
            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->attendance_date->format('Y-m-d'),
                    $attendance->staff_number,
                    $attendance->employee->employee_name ?? 'N/A',
                    $attendance->check_in_time ? $attendance->check_in_time->format('H:i:s') : '-',
                    $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : '-',
                    number_format($attendance->regular_hours ?? 0, 2),
                    number_format($attendance->overtime_hours ?? 0, 2),
                    number_format($attendance->total_hours ?? 0, 2),
                    ucfirst($attendance->status),
                    $attendance->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($attendances, $startDate, $endDate)
    {
        // Group attendances by date for better organization
        $groupedAttendances = $attendances->groupBy(function($item) {
            return $item->attendance_date->format('Y-m-d');
        });

        $data = [
            'attendances' => $groupedAttendances,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_records' => $attendances->count(),
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

        $pdf = PDF::loadView('attendances.report-pdf', $data);

        $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.pdf';

        return $pdf->download($filename);
    }

}
