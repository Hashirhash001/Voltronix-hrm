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

            // Base query for main results (with JOIN for ordering)
            $query = Attendance::select('attendances.*')
                ->leftJoin('employees', 'attendances.employee_id', '=', 'employees.id')
                ->with(['employee:id,staff_number,employee_name']);

            // 1. Date filtering
            if ($request->has('date') && $request->date) {
                $query->whereDate('attendances.attendance_date', $request->date);
            } elseif ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
                $query->whereBetween('attendances.attendance_date', [$request->start_date, $request->end_date]);
            } else {
                $query->whereDate('attendances.attendance_date', now()->format('Y-m-d'));
            }

            // 2. Employee filter
            if ($request->has('employee_id') && $request->employee_id) {
                $query->where('attendances.employee_id', $request->employee_id);
            }

            // 3. Status filter
            if ($request->has('status') && $request->status) {
                $query->where('attendances.status', $request->status); // ✅ FIXED: Table prefix
            }

            // ===== SEPARATE STATS QUERY without JOIN (avoids ambiguity) =====
            $statsQuery = Attendance::query(); // Fresh query without JOIN

            // Apply same date filters to stats
            if ($request->has('date') && $request->date) {
                $statsQuery->whereDate('attendance_date', $request->date);
            } elseif ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
                $statsQuery->whereBetween('attendance_date', [$request->start_date, $request->end_date]);
            } else {
                $statsQuery->whereDate('attendance_date', now()->format('Y-m-d'));
            }

            // Apply same employee filter to stats
            if ($request->has('employee_id') && $request->employee_id) {
                $statsQuery->where('employee_id', $request->employee_id);
            }

            $stats = [
                'present'   => $statsQuery->where('status', 'present')->count(),
                'absent'    => $statsQuery->where('status', 'absent')->count(),
                'leave'     => $statsQuery->where('status', 'leave')->count(),
                'half_day'  => $statsQuery->where('status', 'half_day')->count(),
            ];

            // ✅ Main query ordering (JOIN works here)
            $attendances = $query->orderBy('employees.staff_number', 'asc')
                ->orderBy('attendances.attendance_date', 'desc')
                ->orderBy('attendances.id', 'desc')
                ->paginate(15);

            // Format attendances
            $formattedAttendances = $attendances->map(function ($attendance) {
                $checkIn  = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
                $checkOut = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;

                $isCheckoutNextDay = false;
                if ($checkIn && $checkOut) {
                    $isCheckoutNextDay = $checkOut->toDateString() > $checkIn->toDateString();
                }

                return [
                    'id' => $attendance->id,
                    'employee' => [
                        'id' => $attendance->employee_id,
                        'employee_name' => $attendance->employee->employee_name ?? 'N/A',
                        'staff_number' => $attendance->employee->staff_number ?? 'N/A',
                    ],
                    'staff_number' => $attendance->employee->staff_number ?? 'N/A',
                    'attendance_date' => $attendance->attendance_date->format('Y-m-d'),

                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'is_checkout_next_day' => $isCheckoutNextDay,

                    'total_hours' => (float) ($attendance->total_hours ?? 0),
                    'formatted_total_hours' => $attendance->getFormattedTotalHours(),

                    'overtime_hours' => (float) ($attendance->overtime_hours ?? 0),
                    'formatted_overtime_hours' => $attendance->getFormattedOvertimeHours(),
                    'overtime_before_midnight_hours' => (float) ($attendance->overtime_before_midnight_hours ?? 0),
                    'overtime_after_midnight_hours' => (float) ($attendance->overtime_after_midnight_hours ?? 0),

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
        $employees = Employee::whereIn('status', ['active', 'vacation'])
            ->orderBy('staff_number', 'asc')
            ->orderBy('employee_name')
            ->get();

        return view('attendances.index', [
            'employees' => $employees,
            'request' => $request,
        ]);
    }

    // Generate today's attendance
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
            $checkInTime = null;
            $checkOutTime = null;

            // Set default times based on status
            if ($status === 'present') {
                $checkInTime = $validated['check_in_time'] ?? '08:00';
                $checkOutTime = $validated['check_out_time'] ?? '18:00';
            } elseif ($status === 'half_day') {
                $checkInTime = $validated['check_in_time'] ?? '08:00';
                $checkOutTime = $validated['check_out_time'] ?? '12:00';
            }

            foreach ($validated['employee_ids'] as $employeeId) {
                $employee = Employee::find($employeeId);
                if (!$employee) continue;

                // ✅ Check if attendance already exists for this date
                $exists = Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $validated['date'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Build datetime values (safely handles overnight)
                $checkInDT = $checkInTime ? Carbon::parse($validated['date'] . ' ' . $checkInTime) : null;
                $checkOutDT = $checkOutTime ? Carbon::parse($validated['date'] . ' ' . $checkOutTime) : null;

                if ($checkInDT && $checkOutDT && $checkOutDT->lessThan($checkInDT)) {
                    $checkOutDT->addDay();
                }

                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'staff_number' => $employee->staff_number,
                    'attendance_date' => $validated['date'],
                    'check_in_time' => $checkInDT?->format('Y-m-d H:i:s'),
                    'check_out_time' => $checkOutDT?->format('Y-m-d H:i:s'),
                    'status' => $status,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Use model calculation (now includes after-midnight)
                if ($attendance->check_in_time && $attendance->check_out_time) {
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

                        if ($employee->basic_salary) {
                            $hourlyRate = $employee->basic_salary / 30 / 10;
                            $overtimeAmount = $attendance->overtime_hours * $hourlyRate * 1.5;
                            $overtimeRecord->update(['overtime_amount' => $overtimeAmount]);
                        }
                    }
                }

                $created++;
            }

            DB::commit();

            $message = "$created attendance records generated successfully!";
            if ($skipped > 0) {
                $message .= " ($skipped skipped - already exists)";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'created' => $created,
                'skipped' => $skipped,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate attendance: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get employees who don't have attendance for the selected date
     */
    public function getAvailableEmployees(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        // Get all active employees
        $allEmployees = Employee::whereIn('status', ['active'])
            ->orderBy('employee_name')
            ->get(['id', 'employee_name', 'staff_number', 'designation']);

        // Get employee IDs who already have attendance for this date
        $employeesWithAttendance = Attendance::whereDate('attendance_date', $date)
            ->pluck('employee_id')
            ->toArray();

        // Filter out employees who already have attendance
        $availableEmployees = $allEmployees->filter(function ($employee) use ($employeesWithAttendance) {
            return !in_array($employee->id, $employeesWithAttendance);
        })->values();

        return response()->json([
            'success' => true,
            'employees' => $availableEmployees,
            'date' => $date,
            'total_available' => $availableEmployees->count(),
            'total_already_marked' => count($employeesWithAttendance),
        ]);
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

            // Attendance date as string
            $attendanceDate = $attendance->attendance_date instanceof Carbon
                ? $attendance->attendance_date->format('Y-m-d')
                : Carbon::parse($attendance->attendance_date)->format('Y-m-d');

            $checkInTime = null;
            $checkOutTime = null;

            // -------------------------------
            // 1) Build check-in/check-out
            // -------------------------------
            if ($isManualStatusChange) {

                if (in_array($status, ['absent', 'leave', 'holiday'])) {
                    $checkInTime = null;
                    $checkOutTime = null;
                } elseif ($status === 'half_day') {
                    $checkInTime = $attendanceDate . ' 08:00:00';
                    $checkOutTime = $attendanceDate . ' 12:00:00';
                } elseif ($status === 'present') {
                    $checkInTime = $attendanceDate . ' 08:00:00';
                    $checkOutTime = $attendanceDate . ' 18:00:00';
                }

            } else {

                // If UI sends new time, use it; else keep existing DB time
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

                /* ✅ ADD THIS HERE (right after both times are set) */
                if ($checkInTime && $checkOutTime) {
                    $ci = Carbon::parse($checkInTime);
                    $co = Carbon::parse($checkOutTime);

                    if ($co->lt($ci)) {
                        $co->addDay();
                        $checkOutTime = $co->format('Y-m-d H:i:s');
                    }
                }
                /* ✅ END ADD */

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

                // ✅ If checkout is "earlier" than checkin, assume next day
                // Example: in 20:00, out 02:00 -> next day 02:00
                if ($checkInTime && $checkOutTime) {
                    $ci = Carbon::parse($checkInTime);
                    $co = Carbon::parse($checkOutTime);

                    if ($co->lt($ci)) {
                        $co->addDay();
                        $checkOutTime = $co->format('Y-m-d H:i:s');
                    }
                }

                // Auto-determine status from worked hours (keep your logic)
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

            // -------------------------------
            // 2) Calculate hours + OT split
            // -------------------------------
            $totalHours = 0;
            $regularHours = 0;
            $overtimeHours = 0;

            $overtimeBeforeMidnight = 0;
            $overtimeAfterMidnight = 0;

            $isCheckoutNextDay = false;

            if ($checkInTime && $checkOutTime) {
                $checkIn = Carbon::parse($checkInTime);
                $checkOut = Carbon::parse($checkOutTime);

                $isCheckoutNextDay = $checkOut->toDateString() > $checkIn->toDateString();

                $totalMinutes = $checkIn->diffInMinutes($checkOut);
                $totalHours = round($totalMinutes / 60, 2);

                // Standard working hours: 10 hours (8 AM to 6 PM)
                $regularHours = min($totalHours, 10);
                $overtimeHours = max(0, $totalHours - 10);

                // ✅ Split overtime across midnight (based on time portion, not just >10h)
                // We consider overtime as "worked time outside 08:00-18:00 window".
                $startOfWorkWindow = Carbon::parse($attendanceDate . ' 08:00:00');
                $endOfWorkWindow   = Carbon::parse($attendanceDate . ' 18:00:00');

                // "Overtime interval" = portions of [checkIn, checkOut] outside [08:00,18:00]
                // 1) Before work window
                $beforeWindowStart = $checkIn->copy();
                $beforeWindowEnd = min($checkOut, $startOfWorkWindow);

                // 2) After work window (can cross midnight)
                $afterWindowStart = max($checkIn, $endOfWorkWindow);
                $afterWindowEnd = $checkOut->copy();

                $overtimeIntervals = [];

                if ($beforeWindowEnd->gt($beforeWindowStart)) {
                    $overtimeIntervals[] = [$beforeWindowStart, $beforeWindowEnd];
                }

                if ($afterWindowEnd->gt($afterWindowStart)) {
                    $overtimeIntervals[] = [$afterWindowStart, $afterWindowEnd];
                }

                // Split intervals by midnight boundary
                foreach ($overtimeIntervals as [$s, $e]) {
                    $midnight = $s->copy()->startOfDay()->addDay(); // next midnight after s

                    if ($e->lte($midnight)) {
                        // Entirely before midnight
                        $overtimeBeforeMidnight += $s->diffInMinutes($e) / 60;
                    } else {
                        // Part before midnight
                        $overtimeBeforeMidnight += $s->diffInMinutes($midnight) / 60;
                        // Part after midnight
                        $overtimeAfterMidnight += $midnight->diffInMinutes($e) / 60;
                    }
                }

                $overtimeBeforeMidnight = round($overtimeBeforeMidnight, 2);
                $overtimeAfterMidnight = round($overtimeAfterMidnight, 2);

                // Optional: keep totals consistent
                // If you want "overtime_hours" to equal split sum:
                // $overtimeHours = round($overtimeBeforeMidnight + $overtimeAfterMidnight, 2);
            }

            // -------------------------------
            // 3) Save attendance
            // -------------------------------
            $attendance->update([
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'status' => $status,
                'total_hours' => $totalHours,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'overtime_before_midnight_hours' => $overtimeBeforeMidnight,
                'overtime_after_midnight_hours' => $overtimeAfterMidnight,
            ]);

            // -------------------------------
            // 4) Handle overtime record (based on overtime_hours)
            // -------------------------------
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

                $employee = $attendance->employee;
                if ($employee && $employee->basic_salary) {
                    $hourlyRate = $employee->basic_salary / 30 / 10; // 10-hour day
                    $overtimeAmount = $overtimeHours * $hourlyRate * 1.5;
                    $overtimeRecord->update(['overtime_amount' => $overtimeAmount]);
                }
            } else {
                OvertimeRecord::where('attendance_id', $attendance->id)->delete();
            }

            DB::commit();

            $attendance->refresh();
            $attendance->load('employee');

            // Recompute next-day flag from saved values (safe)
            $ci = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
            $co = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;
            $isCheckoutNextDay = ($ci && $co) ? ($co->toDateString() > $ci->toDateString()) : false;

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

                    // ✅ needed for checkout +1 indicator
                    'is_checkout_next_day' => $isCheckoutNextDay,

                    'total_hours' => (float) $attendance->total_hours,
                    'formatted_total_hours' => $attendance->getFormattedTotalHours(),

                    'regular_hours' => (float) $attendance->regular_hours,

                    // ✅ overtime totals + split (needed for OT 12 AM column)
                    'overtime_hours' => (float) $attendance->overtime_hours,
                    'formatted_overtime_hours' => $attendance->getFormattedOvertimeHours(),
                    'overtime_before_midnight_hours' => (float) ($attendance->overtime_before_midnight_hours ?? 0),
                    'overtime_after_midnight_hours' => (float) ($attendance->overtime_after_midnight_hours ?? 0),

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
            'check_out_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,half_day,leave,holiday',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::find($validated['employee_id']);

        try {
            DB::beginTransaction();

            $checkInDT = Carbon::parse($validated['attendance_date'] . ' ' . $validated['check_in_time'] . ':00');
            $checkOutDT = isset($validated['check_out_time']) && $validated['check_out_time']
                ? Carbon::parse($validated['attendance_date'] . ' ' . $validated['check_out_time'] . ':00')
                : null;

            if ($checkInDT && $checkOutDT && $checkOutDT->lessThan($checkInDT)) {
                $checkOutDT->addDay();
            }

            $attendance = Attendance::create([
                'employee_id' => $validated['employee_id'],
                'staff_number' => $employee->staff_number,
                'attendance_date' => $validated['attendance_date'],
                'check_in_time' => $checkInDT->format('Y-m-d H:i:s'),
                'check_out_time' => $checkOutDT?->format('Y-m-d H:i:s'),
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
            'check_out_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,half_day,leave,holiday',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $dateStr = $attendance->attendance_date instanceof Carbon
                ? $attendance->attendance_date->format('Y-m-d')
                : Carbon::parse($attendance->attendance_date)->format('Y-m-d');

            $checkInDT = Carbon::parse($dateStr . ' ' . $validated['check_in_time'] . ':00');
            $checkOutDT = isset($validated['check_out_time']) && $validated['check_out_time']
                ? Carbon::parse($dateStr . ' ' . $validated['check_out_time'] . ':00')
                : null;

            if ($checkInDT && $checkOutDT && $checkOutDT->lessThan($checkInDT)) {
                $checkOutDT->addDay();
            }

            $attendance->update([
                'check_in_time' => $checkInDT->format('Y-m-d H:i:s'),
                'check_out_time' => $checkOutDT?->format('Y-m-d H:i:s'),
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

            // Clean output buffer BEFORE choosing format
            while (ob_get_level() > 0) {
                ob_end_clean();
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
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            ini_set('display_errors', '0');

            // Group attendances by employee instead of date
            $attendancesByEmployee = $attendances->groupBy(function ($item) {
                return $item->employee_id;
            })->sortBy(function ($group) {
                return $group->first()->employee->employee_name;
            });

            // ✅ Load vacations in the selected date range for employees in this report
            $employeeIds = $attendancesByEmployee->keys()->values();

            $vacationsByEmployee = \App\Models\EmployeeVacation::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate)
                ->get()
                ->groupBy('employee_id');

            // Calculate summary for entire period
            $extraDaysWorked = $attendances->filter(function ($att) {
                $isSunday = $att->attendance_date->dayOfWeek === Carbon::SUNDAY;
                $isHoliday = $att->status === 'holiday';
                $hasWorked = in_array($att->status, ['present', 'half_day']);
                return ($isSunday || $isHoliday) && $hasWorked;
            });

            $totalOvertimeHours = $attendances->sum('overtime_hours') + $extraDaysWorked->sum('total_hours');

            $data = [
                'attendancesByEmployee' => $attendancesByEmployee,
                'vacationsByEmployee'   => $vacationsByEmployee, // ✅ NEW for timeline highlight
                'start_date'            => $startDate,
                'end_date'              => $endDate,
                'total_records'         => $attendances->count(),
                'summary' => [
                    'total_present'          => $attendances->where('status', 'present')->count(),
                    'total_absent'           => $attendances->where('status', 'absent')->count(),
                    'total_half_day'         => $attendances->where('status', 'half_day')->count(),
                    'total_leave'            => $attendances->where('status', 'leave')->count(),
                    'total_holiday'          => $attendances->where('status', 'holiday')->count(),
                    'total_extra_days'       => $extraDaysWorked->count(),
                    'total_extra_days_hours' => $extraDaysWorked->sum('total_hours'),
                    'total_hours'            => $attendances->sum('total_hours'),
                    'total_regular_hours'    => $attendances->sum('regular_hours'),
                    'total_overtime_hours'   => $totalOvertimeHours,
                ],
            ];

            set_time_limit(120);
            ini_set('memory_limit', '256M');

            $pdf = PDF::loadView('attendances.report-pdf', $data)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'debugKeepTemp'        => false,
                    'chroot'               => public_path(),
                    'enable_php'           => false,
                ]);

            $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF Export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PDF generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function exportToCsv($attendances, $startDate, $endDate)
    {
        try {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            ini_set('display_errors', '0');

            $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0'
            ];

            $callback = function () use ($attendances, $startDate, $endDate) {
                $file = fopen('php://output', 'w');

                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // ===== MAIN HEADER =====
                fputcsv($file, ['ATTENDANCE REPORT']);
                fputcsv($file, ['Period: ' . $startDate . ' to ' . $endDate]);
                fputcsv($file, ['Generated on: ' . now()->format('d M Y h:i:s A')]); // ✅ AM/PM
                fputcsv($file, []);

                // ===== OVERALL SUMMARY STATISTICS =====
                $extraDaysWorked = $attendances->filter(function ($att) {
                    $attDate = $att->attendance_date instanceof Carbon
                        ? $att->attendance_date
                        : Carbon::parse($att->attendance_date);

                    $isSunday = $attDate->dayOfWeek === Carbon::SUNDAY;
                    $isHoliday = $att->status === 'holiday';
                    $hasWorked = in_array($att->status, ['present', 'half_day']);
                    return ($isSunday || $isHoliday) && $hasWorked;
                });

                fputcsv($file, ['OVERALL SUMMARY STATISTICS']);
                fputcsv($file, [
                    'Total Records',
                    'Total Employees',
                    'Present',
                    'Absent',
                    'Half Day',
                    'Leave',
                    'Holiday',
                    'Extra Days',
                    'Total Hours',
                    'Overtime Hours'
                ]);

                $totalEmployees = $attendances->groupBy('employee_id')->count();

                fputcsv($file, [
                    $attendances->count(),
                    $totalEmployees,
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
                fputcsv($file, []);

                // ===== EMPLOYEE-WISE ATTENDANCE DETAILS =====
                $attendancesByEmployee = $attendances->groupBy('employee_id')->sortBy(function ($group) {
                    return $group->first()->employee->employee_name;
                });

                // ✅ Vacation data used in CSV too (optional column removed; used for label)
                $employeeIds = $attendancesByEmployee->keys()->values();

                $vacationsByEmployee = \App\Models\EmployeeVacation::query()
                    ->whereIn('employee_id', $employeeIds)
                    ->whereDate('start_date', '<=', $endDate)
                    ->whereDate('end_date', '>=', $startDate)
                    ->get()
                    ->groupBy('employee_id');

                foreach ($attendancesByEmployee as $employeeId => $employeeAttendances) {
                    $employee = $employeeAttendances->first()->employee;

                    // Build date=>true vacation map for this employee
                    $vacationDays = [];
                    foreach (($vacationsByEmployee[$employeeId] ?? collect()) as $v) {
                        $cursor = $v->start_date->copy();
                        $end = $v->end_date->copy();
                        while ($cursor->lte($end)) {
                            $vacationDays[$cursor->format('Y-m-d')] = true;
                            $cursor->addDay();
                        }
                    }

                    // Employee Header
                    fputcsv($file, ['=' . str_repeat('=', 100)]);
                    fputcsv($file, ['EMPLOYEE: ' . $employee->employee_name]);
                    fputcsv($file, ['Staff ID: ' . $employee->staff_number . ' | Designation: ' . ($employee->designation ?? 'N/A')]);
                    fputcsv($file, ['=' . str_repeat('=', 100)]);
                    fputcsv($file, []);

                    // Employee Summary (unchanged)
                    $empPresent = $employeeAttendances->where('status', 'present')->count();
                    $empAbsent = $employeeAttendances->where('status', 'absent')->count();
                    $empHalfDay = $employeeAttendances->where('status', 'half_day')->count();
                    $empLeave = $employeeAttendances->where('status', 'leave')->count();
                    $empHoliday = $employeeAttendances->where('status', 'holiday')->count();

                    $empExtraDays = $employeeAttendances->filter(function ($att) {
                        $attDate = $att->attendance_date instanceof Carbon
                            ? $att->attendance_date
                            : Carbon::parse($att->attendance_date);

                        $isSunday = $attDate->dayOfWeek === Carbon::SUNDAY;
                        $isHoliday = $att->status === 'holiday';
                        $hasWorked = in_array($att->status, ['present', 'half_day']);
                        return ($isSunday || $isHoliday) && $hasWorked;
                    });

                    $empExtraDaysCount = $empExtraDays->count();
                    $empExtraHours = $empExtraDays->sum('total_hours');
                    $empTotalHours = $employeeAttendances->sum('total_hours');
                    $empOvertimeHours = $employeeAttendances->sum('overtime_hours') + $empExtraHours;

                    fputcsv($file, ['EMPLOYEE SUMMARY']);
                    fputcsv($file, [
                        'Present',
                        'Absent',
                        'Half Day',
                        'Leave',
                        'Holiday',
                        'Extra Days',
                        'Total Hours',
                        'Overtime Hours'
                    ]);
                    fputcsv($file, [
                        $empPresent,
                        $empAbsent,
                        $empHalfDay,
                        $empLeave,
                        $empHoliday,
                        $empExtraDaysCount,
                        number_format($empTotalHours, 2) . 'h',
                        number_format($empOvertimeHours, 2) . 'h'
                    ]);
                    fputcsv($file, []);

                    // ✅ Headers: remove Notes, keep Status (and Status will include VACATION tag when applicable)
                    fputcsv($file, [
                        'Date',
                        'Day',
                        'Check In',
                        'Check Out',
                        'Regular Hours',
                        'Overtime Hours',
                        'Total Hours',
                        'Status'
                    ]);

                    foreach ($employeeAttendances->sortBy('attendance_date') as $attendance) {
                        $attendanceDate = $attendance->attendance_date instanceof Carbon
                            ? $attendance->attendance_date
                            : Carbon::parse($attendance->attendance_date);

                        $dateKey = $attendanceDate->format('Y-m-d');
                        $isVacation = isset($vacationDays[$dateKey]);

                        $isSunday = $attendanceDate->dayOfWeek === Carbon::SUNDAY;
                        $isHoliday = $attendance->status === 'holiday';
                        $isExtraDay = ($isSunday || $isHoliday) && in_array($attendance->status, ['present', 'half_day']);

                        // Day label
                        $dayLabel = $attendanceDate->format('l');
                        if ($isExtraDay) {
                            $dayLabel .= ' (EXTRA DAY)';
                        }

                        // Status label (+ Vacation marker)
                        $statusLabel = ucfirst(str_replace('_', ' ', $attendance->status));
                        if ($isExtraDay) {
                            $statusLabel .= ' (Extra Day)';
                        } elseif ($isSunday && $attendance->status === 'absent') {
                            $statusLabel = 'Sunday (Off Day)';
                        }
                        if ($isVacation) {
                            $statusLabel .= ' (Vacation)';
                        }

                        // ✅ Time formatting AM/PM + next-day indicator on checkout
                        $checkInFormatted = '-';
                        if ($attendance->check_in_time) {
                            $checkInTime = $attendance->check_in_time instanceof Carbon
                                ? $attendance->check_in_time
                                : Carbon::parse($attendance->check_in_time);
                            $checkInFormatted = $checkInTime->format('h:i A');
                        }

                        $checkOutFormatted = '-';
                        if ($attendance->check_out_time) {
                            $checkOutTime = $attendance->check_out_time instanceof Carbon
                                ? $attendance->check_out_time
                                : Carbon::parse($attendance->check_out_time);

                            $checkOutFormatted = $checkOutTime->format('h:i A');

                            if ($checkOutTime->toDateString() !== $attendanceDate->toDateString()) {
                                $checkOutFormatted .= ' (+1 DAY)';
                            }
                        }

                        fputcsv($file, [
                            $attendanceDate->format('Y-m-d'),
                            $dayLabel,
                            $checkInFormatted,
                            $checkOutFormatted,
                            number_format($attendance->regular_hours ?? 0, 2) . 'h',
                            number_format($attendance->overtime_hours ?? 0, 2) . 'h',
                            number_format($attendance->total_hours ?? 0, 2) . 'h',
                            $statusLabel,
                        ]);
                    }

                    fputcsv($file, []);
                    fputcsv($file, []);
                }

                // Footer
                fputcsv($file, []);
                fputcsv($file, ['=' . str_repeat('=', 100)]);
                fputcsv($file, ['© ' . date('Y') . ' Voltronix HRM System']);
                fputcsv($file, ['Working Days: Mon-Sat (8:00 AM - 6:00 PM)']);
                fputcsv($file, ['Extra Days: Sundays + Holidays worked']);
                fputcsv($file, ['Overtime: After 6:00 PM + Extra Days']);
                fputcsv($file, ['=' . str_repeat('=', 100)]);

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('CSV Export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'line'  => $e->getLine(),
                'file'  => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'CSV generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

}
