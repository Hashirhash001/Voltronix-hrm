<?php
// app/Http/Controllers/AttendanceController.php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\OvertimeRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // Check if this is an AJAX request
        if ($request->wantsJson() || $request->ajax()) {
            // AJAX request - return JSON
            $query = Attendance::with('employee');

            // 1. Date filtering (support both single date and range)
            if ($request->has('date') && $request->date) {
                // Single date mode
                $query->whereDate('attendance_date', $request->date);
            } elseif ($request->has('start_date') && $request->has('end_date')) {
                // Date range mode
                $query->whereBetween('attendance_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            } else {
                // Default: today
                $query->whereDate('attendance_date', now()->format('Y-m-d'));
            }

            // 2. Employee filter
            if ($request->has('employee_id') && $request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            // 3. Status filter
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // ===== Calculate stats AFTER applying filters =====
            $stats = [
                'present' => (clone $query)->where('status', 'present')->count(),
                'absent' => (clone $query)->where('status', 'absent')->count(),
                'leave' => (clone $query)->where('status', 'leave')->count(),
                'half_day' => (clone $query)->where('status', 'half_day')->count(),
            ];

            // Paginate the filtered results
            $attendances = $query->orderBy('attendance_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(15);

            // Format attendances
            $formattedAttendances = $attendances->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'employee' => [
                        'employee_name' => $attendance->employee->employee_name ?? 'N/A',
                    ],
                    'staff_number' => $attendance->staff_number,
                    'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'total_hours' => $attendance->total_hours,
                    'formatted_total_hours' => $attendance->getFormattedTotalHours(),
                    'overtime_hours' => $attendance->overtime_hours,
                    'formatted_overtime_hours' => $attendance->getFormattedOvertimeHours(),
                    'status' => $attendance->status,
                    'notes' => $attendance->notes,
                ];
            });

            $pagination = [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ];

            return response()->json([
                'attendances' => $formattedAttendances,
                'pagination' => $pagination,
                'stats' => $stats,
            ]);
        }

        // Browser request - return Blade view
        $employees = Employee::where('status', 'active')
            ->orderBy('employee_name')
            ->get();

        return view('attendances.index', [
            'employees' => $employees,
            'request' => $request,
        ]);
    }


    public function generateToday(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:present,absent,half_day,leave,holiday',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $created = 0;
            $skipped = 0;
            $status = $validated['status'];

            // Set default times based on status
            $checkInTime = null;
            $checkOutTime = null;

            if ($status === 'present') {
                // Present: 8 AM to 6 PM (10 hours)
                $checkInTime = $validated['check_in_time'] ?? '08:00';
                $checkOutTime = $validated['check_out_time'] ?? '18:00';
            } elseif ($status === 'half_day') {
                // Half day: 8 AM to 12 PM (4 hours)
                $checkInTime = $validated['check_in_time'] ?? '08:00';
                $checkOutTime = $validated['check_out_time'] ?? '12:00';
            }
            // absent, leave, and holiday = no times

            foreach ($validated['employee_ids'] as $employeeId) {
                $employee = Employee::find($employeeId);

                if (!$employee) {
                    continue;
                }

                // Check if attendance already exists
                $exists = Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $validated['date'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Create attendance record
                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'staff_number' => $employee->staff_number,
                    'attendance_date' => $validated['date'],
                    'check_in_time' => $checkInTime ? $validated['date'] . ' ' . $checkInTime : null,
                    'check_out_time' => $checkOutTime ? $validated['date'] . ' ' . $checkOutTime : null,
                    'status' => $status,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Calculate hours if times exist (10 hours standard)
                if ($attendance->check_in_time && $attendance->check_out_time) {
                    $checkIn = Carbon::parse($attendance->check_in_time);
                    $checkOut = Carbon::parse($attendance->check_out_time);

                    $totalMinutes = $checkIn->diffInMinutes($checkOut);
                    $totalHours = round($totalMinutes / 60, 2);

                    $regularHours = min($totalHours, 10);
                    $overtimeHours = max(0, $totalHours - 10);

                    $attendance->update([
                        'total_hours' => $totalHours,
                        'regular_hours' => $regularHours,
                        'overtime_hours' => $overtimeHours,
                    ]);

                    // Create overtime record if applicable
                    if ($overtimeHours > 0) {
                        $overtimeRecord = OvertimeRecord::create([
                            'employee_id' => $attendance->employee_id,
                            'attendance_id' => $attendance->id,
                            'overtime_date' => $attendance->attendance_date,
                            'overtime_hours' => $overtimeHours,
                            'overtime_rate' => 1.5,
                            'status' => 'pending',
                        ]);

                        if ($employee->basic_salary) {
                            $hourlyRate = $employee->basic_salary / 30 / 10;
                            $overtimeAmount = $overtimeHours * $hourlyRate * 1.5;
                            $overtimeRecord->update(['overtime_amount' => $overtimeAmount]);
                        }
                    }
                }

                $created++;
            }

            DB::commit();

            $message = "{$created} attendance record(s) generated successfully!";
            if ($skipped > 0) {
                $message .= " ({$skipped} skipped - already exists)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'created' => $created,
                'skipped' => $skipped,
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
            'manual_status_change' => 'nullable|in:true,false,1,0',
        ]);

        try {
            DB::beginTransaction();

            $status = $validated['status'] ?? $attendance->status;
            $isManualStatusChange = in_array($validated['manual_status_change'] ?? false, ['true', '1', 1, true], true);

            // Get attendance date
            $attendanceDate = $attendance->attendance_date instanceof \Carbon\Carbon
                ? $attendance->attendance_date->format('Y-m-d')
                : Carbon::parse($attendance->attendance_date)->format('Y-m-d');

            $checkInTime = null;
            $checkOutTime = null;

            // Handle status-based logic
            if ($isManualStatusChange) {
                if (in_array($status, ['absent', 'leave', 'holiday'])) {
                    // Absent/Leave/Holiday: Clear times
                    $checkInTime = null;
                    $checkOutTime = null;
                } elseif ($status === 'half_day') {
                    // Half day: 08:00 to 12:00 (4 hours)
                    $checkInTime = $attendanceDate . ' 08:00:00';
                    $checkOutTime = $attendanceDate . ' 12:00:00';
                } elseif ($status === 'present') {
                    // Present: 08:00 to 18:00 (10 hours standard)
                    $checkInTime = $attendanceDate . ' 08:00:00';
                    $checkOutTime = $attendanceDate . ' 18:00:00';
                }
            } else {
                // Time-based updates (not status change)
                if (!empty($validated['check_in_time'])) {
                    $checkInTime = $attendanceDate . ' ' . $validated['check_in_time'] . ':00';
                } elseif ($attendance->check_in_time) {
                    $checkInTime = $attendance->check_in_time;
                }

                if (!empty($validated['check_out_time'])) {
                    $checkOutTime = $attendanceDate . ' ' . $validated['check_out_time'] . ':00';
                } elseif ($attendance->check_out_time) {
                    $checkOutTime = $attendance->check_out_time;
                }

                // Auto-determine status from worked hours
                if ($checkInTime && $checkOutTime) {
                    $hoursWorked = Carbon::parse($checkInTime)->diffInMinutes(Carbon::parse($checkOutTime)) / 60;

                    if ($hoursWorked >= 8) {
                        $status = 'present';
                    } elseif ($hoursWorked >= 4 && $hoursWorked < 8) {
                        $status = 'half_day';
                    } elseif ($hoursWorked > 0 && $hoursWorked < 4) {
                        $status = 'absent';
                    }
                }
            }

            // Calculate hours (10 hours standard work day)
            $totalHours = 0;
            $regularHours = 0;
            $overtimeHours = 0;

            if ($checkInTime && $checkOutTime) {
                $checkIn = Carbon::parse($checkInTime);
                $checkOut = Carbon::parse($checkOutTime);

                $totalMinutes = $checkIn->diffInMinutes($checkOut);
                $totalHours = round($totalMinutes / 60, 2);

                // Standard working hours: 10 hours (8 AM to 6 PM)
                $regularHours = min($totalHours, 10);
                $overtimeHours = max(0, $totalHours - 10);
            }

            // Update attendance
            $attendance->update([
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'status' => $status,
                'total_hours' => $totalHours,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
            ]);

            // Handle overtime record
            if ($overtimeHours > 0) {
                $overtimeRecord = OvertimeRecord::updateOrCreate(
                    ['attendance_id' => $attendance->id],
                    [
                        'employee_id' => $attendance->employee_id,
                        'overtime_date' => $attendance->attendance_date,
                        'overtime_hours' => $overtimeHours,
                        'overtime_rate' => 1.5,
                        'status' => 'pending',
                    ]
                );

                // Calculate overtime amount
                $employee = $attendance->employee;
                if ($employee && $employee->basic_salary) {
                    $hourlyRate = $employee->basic_salary / 30 / 10; // Changed from 8 to 10
                    $overtimeAmount = $overtimeHours * $hourlyRate * 1.5;
                    $overtimeRecord->update(['overtime_amount' => $overtimeAmount]);
                }
            } else {
                OvertimeRecord::where('attendance_id', $attendance->id)->delete();
            }

            DB::commit();

            // Refresh and load relationships
            $attendance->refresh();
            $attendance->load('employee');

            // Return plain string format (not Carbon object)
            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully!',
                'attendance' => [
                    'id' => $attendance->id,
                    'employee_id' => $attendance->employee_id,
                    'staff_number' => $attendance->staff_number,
                    'attendance_date' => $attendanceDate,
                    'status' => $attendance->status,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'total_hours' => (float) $attendance->total_hours,
                    'formatted_total_hours' => $attendance->getFormattedTotalHours(),
                    'overtime_hours' => (float) $attendance->overtime_hours,
                    'formatted_overtime_hours' => $attendance->getFormattedOvertimeHours(),
                    'regular_hours' => (float) $attendance->regular_hours,
                    'employee' => [
                        'id' => $attendance->employee->id,
                        'employee_name' => $attendance->employee->employee_name,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Quick update error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'employee_id' => 'nullable|exists:employees,id',
                'format' => 'required|in:csv,pdf'
            ]);

            $query = Attendance::with('employee')
                ->whereBetween('attendance_date', [$validated['start_date'], $validated['end_date']]);

            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            $attendances = $query->orderBy('attendance_date', 'asc')
                                ->orderBy('staff_number', 'asc')
                                ->get();

            if ($attendances->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance records found for the selected period.'
                ], 404);
            }

            if ($validated['format'] === 'csv') {
                return $this->exportToCsv($attendances, $validated['start_date'], $validated['end_date']);
            } else {
                return $this->exportToPdf($attendances, $validated['start_date'], $validated['end_date']);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function exportToPdf($attendances, $startDate, $endDate)
    {
        try {
            // Clean any output buffers
            if (ob_get_length()) {
                ob_end_clean();
            }

            $groupedAttendances = $attendances->groupBy(function($item) {
                return $item->attendance_date->format('Y-m-d');
            });

            // ✅ Calculate extra days worked
            $extraDaysWorked = $attendances->filter(function($att) {
                $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
                $isHoliday = $att->status === 'holiday';
                $hasWorked = in_array($att->status, ['present', 'half_day']);

                return ($isSunday || $isHoliday) && $hasWorked;
            });

            $extraDaysCount = $extraDaysWorked->count();
            $extraDaysHours = $extraDaysWorked->sum('total_hours');

            // ✅ Calculate overtime including extra day hours
            $totalOvertimeHours = $attendances->sum('overtime_hours') + $extraDaysHours;

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
                    'total_extra_days' => $extraDaysCount,
                    'total_extra_days_hours' => $extraDaysHours,
                    'total_hours' => $attendances->sum('total_hours'),
                    'total_regular_hours' => $attendances->sum('regular_hours'),
                    'total_overtime_hours' => $totalOvertimeHours,
                ]
            ];

            // Set longer timeout for PDF generation
            set_time_limit(120);
            ini_set('memory_limit', '256M');

            $pdf = PDF::loadView('attendances.report-pdf', $data)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'debugKeepTemp' => false,
                    'chroot' => public_path(),
                ]);

            $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF Export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Return error as JSON instead of crashing
            return response()->json([
                'success' => false,
                'message' => 'PDF generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function exportToCsv($attendances, $startDate, $endDate)
    {
        try {
            // Clean any output buffers
            if (ob_get_length()) {
                ob_end_clean();
            }

            $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($attendances, $startDate, $endDate) {
                $file = fopen('php://output', 'w');

                // UTF-8 BOM
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Header information
                fputcsv($file, ['Attendance Report']);
                fputcsv($file, ['Period: ' . $startDate . ' to ' . $endDate]);
                fputcsv($file, ['Total Records: ' . $attendances->count()]);
                fputcsv($file, []);

                // Summary statistics
                $extraDaysWorked = $attendances->filter(function($att) {
                    $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
                    $isHoliday = $att->status === 'holiday';
                    $hasWorked = in_array($att->status, ['present', 'half_day']);
                    return ($isSunday || $isHoliday) && $hasWorked;
                });

                fputcsv($file, ['Summary Statistics']);
                fputcsv($file, [
                    'Total Present',
                    'Total Absent',
                    'Total Half Day',
                    'Total Leave',
                    'Total Holiday',
                    'Extra Days Worked',
                    'Total Hours',
                    'Total Overtime'
                ]);
                fputcsv($file, [
                    $attendances->where('status', 'present')->count(),
                    $attendances->where('status', 'absent')->count(),
                    $attendances->where('status', 'half_day')->count(),
                    $attendances->where('status', 'leave')->count(),
                    $attendances->where('status', 'holiday')->count(),
                    $extraDaysWorked->count(),
                    number_format($attendances->sum('total_hours'), 2) . 'h',
                    number_format($attendances->sum('overtime_hours') + $extraDaysWorked->sum('total_hours'), 2) . 'h'
                ]);
                fputcsv($file, []);

                // Column headers
                fputcsv($file, [
                    'Date',
                    'Day',
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

                // Data rows
                foreach ($attendances as $attendance) {
                    $isSunday = $attendance->attendance_date->dayOfWeek === Carbon::SUNDAY;
                    $isHoliday = $attendance->status === 'holiday';
                    $isExtraDay = ($isSunday || $isHoliday) && in_array($attendance->status, ['present', 'half_day']);

                    $dayLabel = $attendance->attendance_date->format('l');
                    if ($isExtraDay) {
                        $dayLabel .= ' (EXTRA DAY)';
                    }

                    $statusLabel = ucfirst(str_replace('_', ' ', $attendance->status));
                    if ($isExtraDay) {
                        $statusLabel .= ' (Extra Day)';
                    } elseif ($isSunday && $attendance->status === 'absent') {
                        $statusLabel = 'Sunday (Off Day)';
                    }

                    fputcsv($file, [
                        $attendance->attendance_date->format('Y-m-d'),
                        $dayLabel,
                        $attendance->staff_number,
                        $attendance->employee->employee_name ?? 'N/A',
                        $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : '-',
                        $attendance->check_out_time ? $attendance->check_out_time->format('H:i') : '-',
                        number_format($attendance->regular_hours ?? 0, 2) . 'h',
                        number_format($attendance->overtime_hours ?? 0, 2) . 'h',
                        number_format($attendance->total_hours ?? 0, 2) . 'h',
                        $statusLabel,
                        $attendance->notes ?? ''
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('CSV Export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'CSV generation failed: ' . $e->getMessage()
            ], 500);
        }
    }


}
