<?php
// app/Http/Controllers/AttendanceController.php

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

        if ($request->has('date') && $request->date) {
            $query->whereDate('attendance_date', $request->date);
        } else {
            $query->whereDate('attendance_date', Carbon::today());
        }

        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        $attendances = $query->orderBy('attendance_date', 'desc')->paginate(15);
        $employees = Employee::where('status', 'active')->orderBy('employee_name')->get();

        $stats = [
            'present' => Attendance::whereDate('attendance_date', $request->date ?? Carbon::today())->where('status', 'present')->count(),
            'absent' => Attendance::whereDate('attendance_date', $request->date ?? Carbon::today())->where('status', 'absent')->count(),
            'half_day' => Attendance::whereDate('attendance_date', $request->date ?? Carbon::today())->where('status', 'half_day')->count(),
        ];

        if ($request->ajax()) {
            $attendancesArray = $attendances->map(function($item) {
                return [
                    'id' => $item->id,
                    'employee' => $item->employee,
                    'staff_number' => $item->staff_number,
                    'attendance_date' => $item->attendance_date,
                    'check_in_time' => $item->check_in_time,
                    'check_out_time' => $item->check_out_time,
                    'total_hours' => $item->total_hours,
                    'formatted_total_hours' => $item->getFormattedTotalHours(),
                    'overtime_hours' => $item->overtime_hours,
                    'formatted_overtime_hours' => $item->getFormattedOvertimeHours(),
                    'status' => $item->status,
                    'notes' => $item->notes,
                ];
            });

            return response()->json([
                'attendances' => $attendancesArray,
                'pagination' => [
                    'current_page' => $attendances->currentPage(),
                    'last_page' => $attendances->lastPage(),
                    'per_page' => $attendances->perPage(),
                    'total' => $attendances->total(),
                ],
                'stats' => $stats,
            ]);
        }

        return view('attendances.index', compact('attendances', 'employees', 'stats', 'request'));
    }

    public function generateToday(Request $request)
    {
        $date = $request->date ?? Carbon::today()->format('Y-m-d');

        try {
            DB::beginTransaction();

            $employees = Employee::where('status', 'active')->get();
            $created = 0;

            foreach ($employees as $employee) {
                $exists = Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $date)
                    ->exists();

                if (!$exists) {
                    $attendance = Attendance::create([
                        'employee_id' => $employee->id,
                        'staff_number' => $employee->staff_number,
                        'attendance_date' => $date,
                        'check_in_time' => $date . ' 08:00:00',
                        'check_out_time' => $date . ' 18:00:00',
                        'status' => 'present',
                        'notes' => null,
                    ]);

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

                    $created++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$created} attendance records generated successfully with default timings (8 AM - 6 PM)!",
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
            'status' => 'nullable|in:present,absent,half_day,leave,holiday',
            'manual_status_change' => 'nullable|in:true,false,1,0', // Accept string or integer boolean
        ]);

        try {
            DB::beginTransaction();

            $status = $validated['status'] ?? $attendance->status;
            // Convert to actual boolean
            $isManualStatusChange = in_array($validated['manual_status_change'] ?? false, ['true', '1', 1, true], true);
            $checkInTime = null;
            $checkOutTime = null;

            // If status was manually changed to absent/leave, clear times
            if ($isManualStatusChange && in_array($status, ['absent', 'leave'])) {
                $checkInTime = null;
                $checkOutTime = null;
            }
            // If status was manually changed to half_day, keep existing times OR set defaults
            elseif ($isManualStatusChange && $status === 'half_day') {
                if (!empty($validated['check_in_time']) && !empty($validated['check_out_time'])) {
                    // User has times, keep them (even if 10 hours - they explicitly chose half_day)
                    $checkInTime = $attendance->attendance_date->format('Y-m-d') . ' ' . $validated['check_in_time'];
                    $checkOutTime = $attendance->attendance_date->format('Y-m-d') . ' ' . $validated['check_out_time'];
                } elseif ($attendance->check_in_time && $attendance->check_out_time) {
                    // Keep existing times from database
                    $checkInTime = $attendance->check_in_time;
                    $checkOutTime = $attendance->check_out_time;
                } else {
                    // No times exist, set default half day times
                    $checkInTime = $attendance->attendance_date->format('Y-m-d') . ' 08:00:00';
                    $checkOutTime = $attendance->attendance_date->format('Y-m-d') . ' 12:00:00';
                }
            }
            // For any other status or time change
            else {
                // Use provided times or existing times or defaults
                if (!empty($validated['check_in_time'])) {
                    $checkInTime = $attendance->attendance_date->format('Y-m-d') . ' ' . $validated['check_in_time'];
                } elseif ($attendance->check_in_time) {
                    $checkInTime = $attendance->check_in_time;
                }

                if (!empty($validated['check_out_time'])) {
                    $checkOutTime = $attendance->attendance_date->format('Y-m-d') . ' ' . $validated['check_out_time'];
                } elseif ($attendance->check_out_time) {
                    $checkOutTime = $attendance->check_out_time;
                }

                // If times are provided but no status change, auto-determine status from hours
                if (!$isManualStatusChange && $checkInTime && $checkOutTime) {
                    $hoursWorked = Carbon::parse($checkInTime)->diffInMinutes(Carbon::parse($checkOutTime)) / 60;

                    // Auto-set status based on hours worked
                    if ($hoursWorked >= 8) {
                        $status = 'present';
                    } elseif ($hoursWorked >= 4 && $hoursWorked < 8) {
                        $status = 'half_day';
                    } elseif ($hoursWorked > 0 && $hoursWorked < 4) {
                        $status = 'absent';
                    }
                }
            }

            $attendance->update([
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'status' => $status,
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
                } else {
                    // Delete overtime record if no longer overtime
                    if ($attendance->overtimeRecord) {
                        $attendance->overtimeRecord->delete();
                    }
                }
            } else {
                // No times, reset hours to 0
                $attendance->update([
                    'regular_hours' => 0,
                    'overtime_hours' => 0,
                    'total_hours' => 0,
                ]);

                // Delete overtime record
                if ($attendance->overtimeRecord) {
                    $attendance->overtimeRecord->delete();
                }
            }

            DB::commit();

            // Return fresh data
            $attendance->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully!',
                'attendance' => [
                    'id' => $attendance->id,
                    'status' => $attendance->status,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'total_hours' => $attendance->total_hours,
                    'formatted_total_hours' => $attendance->getFormattedTotalHours(),
                    'overtime_hours' => $attendance->overtime_hours,
                    'formatted_overtime_hours' => $attendance->getFormattedOvertimeHours(),
                ],
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

            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Date',
                'Staff Number',
                'Employee Name',
                'Check In',
                'Check Out',
                'Total Hours',
                'Overtime Hours',
                'Status',
                'Notes'
            ]);

            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->attendance_date->format('Y-m-d'),
                    $attendance->staff_number,
                    $attendance->employee->employee_name ?? 'N/A',
                    $attendance->check_in_time ? $attendance->check_in_time->format('H:i:s') : '-',
                    $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : '-',
                    $attendance->getFormattedTotalHours(),
                    $attendance->getFormattedOvertimeHours(),
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
                'total_hours' => $attendances->sum('total_hours'),
                'total_overtime_hours' => $attendances->sum('overtime_hours'),
            ]
        ];

        $pdf = PDF::loadView('attendances.report-pdf', $data);

        $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.pdf';

        return $pdf->download($filename);
    }
}
