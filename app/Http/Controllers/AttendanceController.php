<?php
// app/Http/Controllers/AttendanceController.php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Entity;
use App\Models\Holiday;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\OvertimeRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\Snappy\Facades\SnappyPdf;

class AttendanceController extends Controller
{

    /**
     * Check if a date is a holiday for the given employee's entity
     */
    private function isHoliday($date, $employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) return false;

        return Holiday::active()
            ->where('holiday_date', $date)
            ->where(function($query) use ($employee) {
                $query->whereNull('entity_id') // Public holidays
                      ->orWhere('entity_id', $employee->entity_id); // Entity-specific
            })
            ->exists();
    }

    /**
     *  Calculate hours with holiday overtime logic
     */
    private function calculateAttendanceHours($attendance, $checkInTime, $checkOutTime)
    {
        if (!$checkInTime || !$checkOutTime) {
            return [
                'total_hours' => 0,
                'regular_hours' => 0,
                'overtime_hours' => 0,
                'overtime_before_midnight' => 0,
                'overtime_after_midnight' => 0,
                'is_holiday_work' => false,
            ];
        }

        $checkIn = Carbon::parse($checkInTime);
        $checkOut = Carbon::parse($checkOutTime);
        $totalMinutes = $checkIn->diffInMinutes($checkOut);
        $totalHours = round($totalMinutes / 60, 2);

        // ✅ CHECK IF THIS IS A HOLIDAY
        $isHoliday = $this->isHoliday(
            $attendance->attendance_date instanceof Carbon
                ? $attendance->attendance_date->format('Y-m-d')
                : $attendance->attendance_date,
            $attendance->employee_id
        );

        // ✅ NEW: Check if it's Sunday
        $attendanceDate = $attendance->attendance_date instanceof Carbon
            ? $attendance->attendance_date
            : Carbon::parse($attendance->attendance_date);
        $isSunday = $attendanceDate->dayOfWeek === Carbon::SUNDAY;

        // ✅ IF HOLIDAY OR SUNDAY → ALL HOURS = OVERTIME
        if ($isHoliday || $isSunday) {
            return [
                'total_hours' => $totalHours,
                'regular_hours' => 0,
                'overtime_hours' => $totalHours,
                'overtime_before_midnight' => $totalHours,
                'overtime_after_midnight' => 0,
                'is_holiday_work' => true, // Flag for reporting
            ];
        }

        // NORMAL DAY: Standard 10-hour calculation
        $regularHours = min($totalHours, 10);
        $overtimeHours = max(0, $totalHours - 10);

        // Calculate overtime split (before/after midnight)
        $attendanceDateStr = $attendanceDate->format('Y-m-d');
        $startOfWorkWindow = Carbon::parse($attendanceDateStr . ' 08:00:00');
        $endOfWorkWindow = Carbon::parse($attendanceDateStr . ' 18:00:00');

        $overtimeIntervals = [];

        // Before work window
        $beforeWindowStart = $checkIn->copy();
        $beforeWindowEnd = min($checkOut, $startOfWorkWindow);
        if ($beforeWindowEnd->gt($beforeWindowStart)) {
            $overtimeIntervals[] = [$beforeWindowStart, $beforeWindowEnd];
        }

        // After work window
        $afterWindowStart = max($checkIn, $endOfWorkWindow);
        $afterWindowEnd = $checkOut->copy();
        if ($afterWindowEnd->gt($afterWindowStart)) {
            $overtimeIntervals[] = [$afterWindowStart, $afterWindowEnd];
        }

        $overtimeBeforeMidnight = 0;
        $overtimeAfterMidnight = 0;

        foreach ($overtimeIntervals as [$s, $e]) {
            $midnight = $s->copy()->startOfDay()->addDay();
            if ($e->lte($midnight)) {
                $overtimeBeforeMidnight += $s->diffInMinutes($e) / 60;
            } else {
                $overtimeBeforeMidnight += $s->diffInMinutes($midnight) / 60;
                $overtimeAfterMidnight += $midnight->diffInMinutes($e) / 60;
            }
        }

        return [
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'overtime_before_midnight' => round($overtimeBeforeMidnight, 2),
            'overtime_after_midnight' => round($overtimeAfterMidnight, 2),
            'is_holiday_work' => false,
        ];
    }

    public function index(Request $request)
    {
        // AJAX request
        if ($request->wantsJson() || $request->ajax()) {

            /*
            |--------------------------------------------------------------------------
            | MAIN QUERY (WITH JOIN FOR ORDERING)
            |--------------------------------------------------------------------------
            */
            $query = Attendance::select('attendances.*')
                ->leftJoin('employees', 'attendances.employee_id', '=', 'employees.id')
                ->with([
                    'employee:id,staff_number,employee_name,entity_id',
                    'employee.entity:id,entity_name'
                ]);

            // Date filtering
            if ($request->date) {
                $query->whereDate('attendances.attendance_date', $request->date);
            } elseif ($request->start_date && $request->end_date) {
                $query->whereBetween('attendances.attendance_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            } else {
                $query->whereDate('attendances.attendance_date', now()->toDateString());
            }

            // Entity filter
            if ($request->entity_id) {
                $query->where('employees.entity_id', $request->entity_id);
            }

            // Employee filter
            if ($request->employee_id) {
                $query->where('attendances.employee_id', $request->employee_id);
            }

            // Status filter
            if ($request->status) {
                $query->where('attendances.status', $request->status);
            }

            /*
            |--------------------------------------------------------------------------
            | STATS QUERY (NO JOIN, SAFE & ACCURATE)
            |--------------------------------------------------------------------------
            */
            $baseStatsQuery = Attendance::query();

            // Same date filters
            if ($request->date) {
                $baseStatsQuery->whereDate('attendance_date', $request->date);
            } elseif ($request->start_date && $request->end_date) {
                $baseStatsQuery->whereBetween('attendance_date', [
                    $request->start_date,
                    $request->end_date
                ]);
            } else {
                $baseStatsQuery->whereDate('attendance_date', now()->toDateString());
            }

            // Same entity filter
            if ($request->entity_id) {
                $baseStatsQuery->whereHas('employee', function ($q) use ($request) {
                    $q->where('entity_id', $request->entity_id);
                });
            }

            // Same employee filter
            if ($request->employee_id) {
                $baseStatsQuery->where('employee_id', $request->employee_id);
            }

            //  FIX: CLONE QUERY PER STATUS
            $stats = [
                'present'  => (clone $baseStatsQuery)->where('status', 'present')->count(),
                'absent'   => (clone $baseStatsQuery)->where('status', 'absent')->count(),
                'leave'    => (clone $baseStatsQuery)->where('status', 'leave')->count(),
                'half_day' => (clone $baseStatsQuery)->where('status', 'half_day')->count(),
            ];

            /*
            |--------------------------------------------------------------------------
            | PAGINATED RESULTS
            |--------------------------------------------------------------------------
            */
            $attendances = $query
                ->orderBy('employees.staff_number', 'asc')
                ->orderBy('attendances.attendance_date', 'desc')
                ->orderBy('attendances.id', 'desc')
                ->paginate(15);

            /*
            |--------------------------------------------------------------------------
            | FORMAT RESPONSE
            |--------------------------------------------------------------------------
            */
            $formattedAttendances = $attendances->map(function ($attendance) {

                // Recalculate hours based on current holiday status
                if ($attendance->check_in_time && $attendance->check_out_time) {
                    $hours = $this->calculateAttendanceHours(
                        $attendance,
                        $attendance->check_in_time,
                        $attendance->check_out_time
                    );

                    // Update the model temporarily (not saved to DB yet)
                    $attendance->total_hours = $hours['total_hours'];
                    $attendance->regular_hours = $hours['regular_hours'];
                    $attendance->overtime_hours = $hours['overtime_hours'];
                    $attendance->overtime_before_midnight_hours = $hours['overtime_before_midnight'];
                    $attendance->overtime_after_midnight_hours = $hours['overtime_after_midnight'];
                }

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
                        'entity_name' => $attendance->employee->entity->entity_name ?? 'N/A',
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

            return response()->json([
                'attendances' => $formattedAttendances,
                'pagination' => [
                    'current_page' => $attendances->currentPage(),
                    'last_page' => $attendances->lastPage(),
                    'per_page' => $attendances->perPage(),
                    'total' => $attendances->total(),
                ],
                'stats' => $stats,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | BLADE VIEW (NON-AJAX)
        |--------------------------------------------------------------------------
        */
        $entities = Entity::orderBy('entity_name')->get();

        $employees = Employee::with('entity')
            ->whereIn('status', ['active', 'vacation'])
            ->orderBy('staff_number', 'asc')
            ->orderBy('employee_name')
            ->get();

        return view('attendances.index', [
            'entities' => $entities,
            'employees' => $employees,
            'request' => $request,
        ]);
    }

    // Generate today's attendance
    public function generateToday(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:present,absent,half_day,leave',
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

                $exists = Attendance::where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $validated['date'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

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

                //  Use new calculation method with holiday check
                if ($attendance->check_in_time && $attendance->check_out_time) {
                    $hours = $this->calculateAttendanceHours(
                        $attendance,
                        $attendance->check_in_time,
                        $attendance->check_out_time
                    );

                    $attendance->update([
                        'total_hours' => $hours['total_hours'],
                        'regular_hours' => $hours['regular_hours'],
                        'overtime_hours' => $hours['overtime_hours'],
                        'overtime_before_midnight_hours' => $hours['overtime_before_midnight'],
                        'overtime_after_midnight_hours' => $hours['overtime_after_midnight'],
                    ]);

                    // Create overtime record if applicable
                    if ($hours['overtime_hours'] > 0) {
                        $overtimeRecord = OvertimeRecord::create([
                            'employee_id' => $attendance->employee_id,
                            'attendance_id' => $attendance->id,
                            'overtime_date' => $attendance->attendance_date,
                            'overtime_hours' => $hours['overtime_hours'],
                            'overtime_rate' => 1.5,
                            'status' => 'pending',
                            'notes' => $hours['is_holiday_work'] ? 'Holiday Work - All hours as overtime' : null,
                        ]);

                        if ($employee->basic_salary) {
                            $hourlyRate = $employee->basic_salary / 30 / 10;
                            $overtimeAmount = $hours['overtime_hours'] * $hourlyRate * 1.5;
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
            'status' => 'nullable|in:present,absent,half_day,leave',
            'manual_status_change' => 'nullable|in:true,false,1,0',
        ]);

        try {
            DB::beginTransaction();

            $status = $validated['status'] ?? $attendance->status;
            $isManualStatusChange = in_array($validated['manual_status_change'] ?? false, ['true', '1', 1, true], true);

            $attendanceDate = $attendance->attendance_date instanceof Carbon
                ? $attendance->attendance_date->format('Y-m-d')
                : Carbon::parse($attendance->attendance_date)->format('Y-m-d');

            $checkInTime = null;
            $checkOutTime = null;

            // Build check-in/check-out times
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

                // Handle overnight checkout
                if ($checkInTime && $checkOutTime) {
                    $ci = Carbon::parse($checkInTime);
                    $co = Carbon::parse($checkOutTime);

                    if ($co->lt($ci)) {
                        $co->addDay();
                        $checkOutTime = $co->format('Y-m-d H:i:s');
                    }
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

            //  Calculate hours with holiday check
            $hours = $this->calculateAttendanceHours($attendance, $checkInTime, $checkOutTime);

            // Update attendance
            $attendance->update([
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'status' => $status,
                'total_hours' => $hours['total_hours'],
                'regular_hours' => $hours['regular_hours'],
                'overtime_hours' => $hours['overtime_hours'],
                'overtime_before_midnight_hours' => $hours['overtime_before_midnight'],
                'overtime_after_midnight_hours' => $hours['overtime_after_midnight'],
            ]);

            // Handle overtime record
            if ($hours['overtime_hours'] > 0) {
                $overtimeRecord = OvertimeRecord::updateOrCreate(
                    ['attendance_id' => $attendance->id],
                    [
                        'employee_id' => $attendance->employee_id,
                        'overtime_date' => $attendance->attendance_date,
                        'overtime_hours' => $hours['overtime_hours'],
                        'overtime_rate' => 1.5,
                        'status' => 'pending',
                        'notes' => $hours['is_holiday_work'] ? '🎉 Holiday Work - All hours as overtime' : null,
                    ]
                );

                $employee = $attendance->employee;
                if ($employee && $employee->basic_salary) {
                    $hourlyRate = $employee->basic_salary / 30 / 10;
                    $overtimeAmount = $hours['overtime_hours'] * $hourlyRate * 1.5;
                    $overtimeRecord->update(['overtime_amount' => $overtimeAmount]);
                }
            } else {
                OvertimeRecord::where('attendance_id', $attendance->id)->delete();
            }

            DB::commit();

            $attendance->refresh();
            $attendance->load('employee');

            $ci = $attendance->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
            $co = $attendance->check_out_time ? Carbon::parse($attendance->check_out_time) : null;
            $isCheckoutNextDay = ($ci && $co) ? ($co->toDateString() > $ci->toDateString()) : false;

            return response()->json([
                'success' => true,
                'message' => $hours['is_holiday_work']
                    ? '🎉 Holiday work detected! All hours added as overtime.'
                    : 'Attendance updated successfully!',
                'attendance' => [
                    'id' => $attendance->id,
                    'employee_id' => $attendance->employee_id,
                    'staff_number' => $attendance->staff_number,
                    'attendance_date' => $attendanceDate,
                    'status' => $attendance->status,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'is_checkout_next_day' => $isCheckoutNextDay,
                    'total_hours' => (float) $attendance->total_hours,
                    'formatted_total_hours' => $attendance->getFormattedTotalHours(),
                    'regular_hours' => (float) $attendance->regular_hours,
                    'overtime_hours' => (float) $attendance->overtime_hours,
                    'formatted_overtime_hours' => $attendance->getFormattedOvertimeHours(),
                    'overtime_before_midnight_hours' => (float) ($attendance->overtime_before_midnight_hours ?? 0),
                    'overtime_after_midnight_hours' => (float) ($attendance->overtime_after_midnight_hours ?? 0),
                    'is_holiday_work' => $hours['is_holiday_work'],
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
            'status' => 'required|in:present,absent,half_day,leave',
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
                $hours = $this->calculateAttendanceHours(
                    $attendance,
                    $attendance->check_in_time,
                    $attendance->check_out_time
                );

                $attendance->update([
                    'total_hours' => $hours['total_hours'],
                    'regular_hours' => $hours['regular_hours'],
                    'overtime_hours' => $hours['overtime_hours'],
                    'overtime_before_midnight_hours' => $hours['overtime_before_midnight'],
                    'overtime_after_midnight_hours' => $hours['overtime_after_midnight'],
                ]);

                if ($hours['overtime_hours'] > 0) {
                    $overtimeRecord = OvertimeRecord::create([
                        'employee_id' => $attendance->employee_id,
                        'attendance_id' => $attendance->id,
                        'overtime_date' => $attendance->attendance_date,
                        'overtime_hours' => $hours['overtime_hours'],
                        'overtime_rate' => 1.5,
                        'status' => 'pending',
                        'notes' => $hours['is_holiday_work'] ? 'Holiday Work' : null,
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
            'status' => 'required|in:present,absent,half_day,leave',
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
                $hours = $this->calculateAttendanceHours(
                    $attendance,
                    $attendance->check_in_time,
                    $attendance->check_out_time
                );

                $attendance->update([
                    'total_hours' => $hours['total_hours'],
                    'regular_hours' => $hours['regular_hours'],
                    'overtime_hours' => $hours['overtime_hours'],
                    'overtime_before_midnight_hours' => $hours['overtime_before_midnight'],
                    'overtime_after_midnight_hours' => $hours['overtime_after_midnight'],
                ]);

                if ($hours['overtime_hours'] > 0) {
                    $overtimeRecord = OvertimeRecord::updateOrCreate(
                        ['attendance_id' => $attendance->id],
                        [
                            'employee_id' => $attendance->employee_id,
                            'overtime_date' => $attendance->attendance_date,
                            'overtime_hours' => $hours['overtime_hours'],
                            'overtime_rate' => 1.5,
                            'status' => 'pending',
                            'notes' => $hours['is_holiday_work'] ? 'Holiday Work' : null,
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

            $query = Attendance::with(['employee.entity'])
                ->whereBetween('attendance_date', [$validated['start_date'], $validated['end_date']]);

            if ($request->employee_id) {
                $query->where('employee_id', $request->employee_id);
            }

            $query->whereHas('employee');

            $attendances = $query->orderBy('attendance_date', 'asc')
                                ->orderBy('staff_number', 'asc')
                                ->get();

            if ($attendances->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance records found for the selected period.'
                ], 404);
            }

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
            set_time_limit(900); // ✅ Increased to 15 minutes
            ini_set('memory_limit', '2048M'); // ✅ Increased to 2GB

            $attendancesByEmployee = $attendances->groupBy(function ($item) {
                return $item->employee_id;
            })->sortBy(function ($group) {
                $emp = $group->first()->employee;
                return $emp ? $emp->staff_number : 'ZZZ';
            });

            $employeeIds = $attendancesByEmployee->keys()->values();

            // ✅ LOAD ALL HOLIDAYS ONCE (instead of querying per attendance)
            $holidaysByDate = \App\Models\Holiday::active()
                ->whereBetween('holiday_date', [$startDate, $endDate])
                ->get()
                ->groupBy('holiday_date');

            // Create employee entity map for fast lookup
            $employees = \App\Models\Employee::whereIn('id', $employeeIds)
                ->get()
                ->keyBy('id');

            $vacationsByEmployee = \App\Models\EmployeeVacation::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate)
                ->get()
                ->groupBy('employee_id');

            // ✅ OPTIMIZED RECALCULATION WITH CACHED HOLIDAYS
            foreach ($attendances as $attendance) {
                if ($attendance->check_in_time && $attendance->check_out_time) {
                    $attDate = $attendance->attendance_date instanceof Carbon
                        ? $attendance->attendance_date->format('Y-m-d')
                        : $attendance->attendance_date;

                    $employee = $employees->get($attendance->employee_id);

                    // Check if date is a holiday (using cached data)
                    $isHoliday = false;
                    if ($holidaysByDate->has($attDate)) {
                        $dayHolidays = $holidaysByDate->get($attDate);
                        $isHoliday = $dayHolidays->contains(function($holiday) use ($employee) {
                            return $holiday->entity_id === null ||
                                ($employee && $holiday->entity_id === $employee->entity_id);
                        });
                    }

                    // Check if Sunday
                    $date = Carbon::parse($attDate);
                    $isSunday = $date->dayOfWeek === Carbon::SUNDAY;

                    // Calculate hours
                    $checkIn = Carbon::parse($attendance->check_in_time);
                    $checkOut = Carbon::parse($attendance->check_out_time);
                    $totalMinutes = $checkIn->diffInMinutes($checkOut);
                    $totalHours = round($totalMinutes / 60, 2);

                    if ($isHoliday || $isSunday) {
                        // All hours as overtime
                        $attendance->total_hours = $totalHours;
                        $attendance->regular_hours = 0;
                        $attendance->overtime_hours = $totalHours;
                    } else {
                        // Normal calculation
                        $regularHours = min($totalHours, 10);
                        $overtimeHours = max(0, $totalHours - 10);

                        $attendance->total_hours = $totalHours;
                        $attendance->regular_hours = $regularHours;
                        $attendance->overtime_hours = $overtimeHours;
                    }
                }
            }

            // ✅ NOW calculate stats from recalculated values
            $extraDaysWorked = $attendances->filter(function ($att) use ($holidaysByDate, $employees) {
                $attDate = $att->attendance_date instanceof Carbon
                    ? $att->attendance_date->format('Y-m-d')
                    : $att->attendance_date;

                $isSunday = Carbon::parse($attDate)->dayOfWeek === Carbon::SUNDAY;

                // Check holidays using cached data
                $isHoliday = false;
                if ($holidaysByDate->has($attDate)) {
                    $employee = $employees->get($att->employee_id);
                    $dayHolidays = $holidaysByDate->get($attDate);
                    $isHoliday = $dayHolidays->contains(function($holiday) use ($employee) {
                        return $holiday->entity_id === null ||
                            ($employee && $holiday->entity_id === $employee->entity_id);
                    });
                }

                $hasWorked = in_array($att->status, ['present', 'half_day']);
                return ($isSunday || $isHoliday) && $hasWorked;
            });

            $totalOvertimeHours = $attendances->sum('overtime_hours');

            $reportData = [];
            $periodStart = Carbon::parse($startDate)->startOfDay();
            $periodEnd = Carbon::parse($endDate)->startOfDay();

            foreach ($attendancesByEmployee as $employeeId => $employeeAttendances) {
                $employee = $employeeAttendances->first()->employee;
                if (!$employee) continue;

                $empPresent = $employeeAttendances->where('status', 'present')->count();
                $empAbsent = $employeeAttendances->where('status', 'absent')->count();
                $empHalfDay = $employeeAttendances->where('status', 'half_day')->count();
                $empLeave = $employeeAttendances->where('status', 'leave')->count();

                $empExtraDays = $employeeAttendances->filter(function ($att) use ($holidaysByDate, $employees) {
                    $attDate = $att->attendance_date instanceof Carbon
                        ? $att->attendance_date->format('Y-m-d')
                        : $att->attendance_date;

                    $isSunday = Carbon::parse($attDate)->dayOfWeek === Carbon::SUNDAY;

                    $isHoliday = false;
                    if ($holidaysByDate->has($attDate)) {
                        $empData = $employees->get($att->employee_id);
                        $dayHolidays = $holidaysByDate->get($attDate);
                        $isHoliday = $dayHolidays->contains(function($holiday) use ($empData) {
                            return $holiday->entity_id === null ||
                                ($empData && $holiday->entity_id === $empData->entity_id);
                        });
                    }

                    $hasWorked = in_array($att->status, ['present', 'half_day']);
                    return ($isSunday || $isHoliday) && $hasWorked;
                });

                $empExtraDaysCount = $empExtraDays->count();
                $empTotalHours = $employeeAttendances->sum('total_hours');
                $empOvertimeHours = $employeeAttendances->sum('overtime_hours');

                $vacationDays = [];
                $vacations = $vacationsByEmployee[$employeeId] ?? collect();
                foreach ($vacations as $v) {
                    $cursor = $v->start_date instanceof Carbon ? $v->start_date->copy() : Carbon::parse($v->start_date);
                    $end = $v->end_date instanceof Carbon ? $v->end_date->copy() : Carbon::parse($v->end_date);
                    while ($cursor->lte($end)) {
                        $vacationDays[$cursor->format('Y-m-d')] = true;
                        $cursor->addDay();
                    }
                }

                $attendanceByDate = $employeeAttendances->keyBy(function($att){
                    $d = $att->attendance_date instanceof Carbon ? $att->attendance_date : Carbon::parse($att->attendance_date);
                    return $d->format('Y-m-d');
                });

                $rows = [];
                $cursor = $periodStart->copy();
                while ($cursor->lte($periodEnd)) {
                    $dateKey = $cursor->format('Y-m-d');
                    $attendance = $attendanceByDate->get($dateKey);
                    $isVacationDay = isset($vacationDays[$dateKey]);

                    if (!$attendance && !$isVacationDay) {
                        $cursor->addDay();
                        continue;
                    }

                    $row = [
                        'date_formatted' => $cursor->format('d M Y'),
                        'day_name' => $cursor->format('l'),
                        'check_in' => '-',
                        'check_out' => '-',
                        'regular_hours' => '0h 0m',
                        'overtime_hours' => '0h 0m',
                        'total_hours' => '0h 0m',
                        'status_label' => 'Vacation',
                        'status_class' => 'status-leave',
                        'row_class' => 'vacation-row',
                        'is_vacation' => true,
                        'is_extra_day' => false,
                        'is_next_day_out' => false,
                    ];

                    if ($attendance) {
                        $attendanceDate = $attendance->attendance_date instanceof Carbon
                            ? $attendance->attendance_date
                            : Carbon::parse($attendance->attendance_date);

                        $isSunday = $attendanceDate->dayOfWeek === Carbon::SUNDAY;

                        // Check holidays using cached data
                        $isHoliday = false;
                        if ($holidaysByDate->has($dateKey)) {
                            $dayHolidays = $holidaysByDate->get($dateKey);
                            $isHoliday = $dayHolidays->contains(function($holiday) use ($employee) {
                                return $holiday->entity_id === null || $holiday->entity_id === $employee->entity_id;
                            });
                        }

                        $isExtraDay = ($isSunday || $isHoliday) && in_array($attendance->status, ['present', 'half_day']);

                        $checkInTime = $attendance->check_in_time
                            ? ($attendance->check_in_time instanceof Carbon ? $attendance->check_in_time : Carbon::parse($attendance->check_in_time))
                            : null;

                        $checkOutTime = $attendance->check_out_time
                            ? ($attendance->check_out_time instanceof Carbon ? $attendance->check_out_time : Carbon::parse($attendance->check_out_time))
                            : null;

                        $isNextDayOut = false;
                        if ($checkOutTime && $checkInTime) {
                            $isNextDayOut = $checkOutTime->toDateString() > $checkInTime->toDateString();
                        }

                        $statusLabel = ucfirst(str_replace('_', ' ', $attendance->status));
                        $statusClass = 'status-' . $attendance->status;
                        $rowClass = '';

                        if ($isVacationDay) {
                            $rowClass = 'vacation-row';
                            $statusLabel .= ' (Vacation)';
                        } elseif ($attendance->status === 'absent') {
                            $rowClass = 'absent-row';
                        } elseif ($attendance->status === 'leave') {
                            $rowClass = 'leave-row';
                        }

                        if ($isExtraDay) {
                            $statusLabel .= ' (Extra Day)';
                            $rowClass .= ' extra-day';
                        } elseif ($isSunday && $attendance->status === 'absent') {
                            $statusLabel = 'Sunday (Off)';
                            $statusClass = 'status-holiday';
                        }

                        $row = [
                            'date_formatted' => $attendanceDate->format('d M Y'),
                            'day_name' => $attendanceDate->format('l'),
                            'check_in' => $checkInTime ? $checkInTime->format('h:i A') : '-',
                            'check_out' => $checkOutTime ? $checkOutTime->format('h:i A') : '-',
                            'regular_hours' => $this->formatHoursMinutes($attendance->regular_hours ?? 0),
                            'overtime_hours' => $this->formatHoursMinutes($attendance->overtime_hours ?? 0),
                            'total_hours' => $this->formatHoursMinutes($attendance->total_hours ?? 0),
                            'status_label' => $statusLabel,
                            'status_class' => $statusClass,
                            'row_class' => $rowClass,
                            'is_vacation' => $isVacationDay,
                            'is_extra_day' => $isExtraDay,
                            'is_next_day_out' => $isNextDayOut,
                        ];
                    }

                    $rows[] = $row;
                    $cursor->addDay();
                }

                $reportData[] = [
                    'employee' => $employee,
                    'summary' => [
                        'present' => $empPresent,
                        'absent' => $empAbsent,
                        'half_day' => $empHalfDay,
                        'leave' => $empLeave,
                        'extra_days' => $empExtraDaysCount,
                        'total_hours' => $this->formatHoursMinutes($empTotalHours),
                        'overtime_hours' => $this->formatHoursMinutes($empOvertimeHours),
                    ],
                    'rows' => $rows,
                ];
            }

            $data = [
                'report_data' => $reportData,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_records' => $attendances->count(),
                'total_employees' => count($reportData),
                'summary' => [
                    'total_present'          => $attendances->where('status', 'present')->count(),
                    'total_absent'           => $attendances->where('status', 'absent')->count(),
                    'total_half_day'         => $attendances->where('status', 'half_day')->count(),
                    'total_leave'            => $attendances->where('status', 'leave')->count(),
                    'total_extra_days'       => $extraDaysWorked->count(),
                    'total_holiday'          => 0,
                    'total_hours'            => $this->formatHoursMinutes($attendances->sum('total_hours')),
                    'total_regular_hours'    => $this->formatHoursMinutes($attendances->sum('regular_hours')),
                    'total_overtime_hours'   => $this->formatHoursMinutes($totalOvertimeHours),
                ],
            ];

            $pdf = SnappyPdf::loadView('attendances.report-pdf', $data)
                ->setPaper('a4')
                ->setOrientation('landscape')
                ->setOption('margin-top', 10)
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 10)
                ->setOption('margin-right', 10)
                ->setOption('page-size', 'A4');

            $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('PDF Export error (Snappy): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
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

                // ✅ RECALCULATE ALL ATTENDANCE RECORDS FIRST
                foreach ($attendances as $attendance) {
                    if ($attendance->check_in_time && $attendance->check_out_time) {
                        $hours = $this->calculateAttendanceHours(
                            $attendance,
                            $attendance->check_in_time,
                            $attendance->check_out_time
                        );

                        $attendance->total_hours = $hours['total_hours'];
                        $attendance->regular_hours = $hours['regular_hours'];
                        $attendance->overtime_hours = $hours['overtime_hours'];
                    }
                }

                fputcsv($file, ['ATTENDANCE REPORT']);
                fputcsv($file, ['Period: ' . $startDate . ' to ' . $endDate]);
                fputcsv($file, ['Generated on: ' . now()->format('d M Y h:i:s A')]);
                fputcsv($file, []);

                $extraDaysWorked = $attendances->filter(function ($att) {
                    $attDate = $att->attendance_date instanceof Carbon
                        ? $att->attendance_date
                        : Carbon::parse($att->attendance_date);

                    $isSunday = $attDate->dayOfWeek === Carbon::SUNDAY;

                    $isHoliday = $this->isHoliday(
                        $attDate->format('Y-m-d'),
                        $att->employee_id
                    );

                    $hasWorked = in_array($att->status, ['present', 'half_day']);
                    return ($isSunday || $isHoliday) && $hasWorked;
                });

                $attendancesByEmployee = $attendances->groupBy('employee_id')->sortBy(function ($group) {
                    $emp = $group->first()->employee;
                    return $emp ? $emp->staff_number : 'ZZZ';
                });

                $totalEmployees = $attendancesByEmployee->count();

                if ($totalEmployees > 1) {
                    fputcsv($file, ['OVERALL SUMMARY STATISTICS']);
                    fputcsv($file, [
                        'Total Records',
                        'Total Employees',
                        'Present',
                        'Absent',
                        'Half Day',
                        'Leave',
                        'Extra Days',
                        'Total Hours',
                        'Overtime Hours'
                    ]);

                    fputcsv($file, [
                        $attendances->count(),
                        $totalEmployees,
                        $attendances->where('status', 'present')->count(),
                        $attendances->where('status', 'absent')->count(),
                        $attendances->where('status', 'half_day')->count(),
                        $attendances->where('status', 'leave')->count(),
                        $extraDaysWorked->count(),
                        number_format($attendances->sum('total_hours'), 2) . 'h',
                        number_format($attendances->sum('overtime_hours'), 2) . 'h'
                    ]);

                    fputcsv($file, []);
                    fputcsv($file, []);
                }

                $employeeIds = $attendancesByEmployee->keys()->values();

                $vacationsByEmployee = \App\Models\EmployeeVacation::query()
                    ->whereIn('employee_id', $employeeIds)
                    ->whereDate('start_date', '<=', $endDate)
                    ->whereDate('end_date', '>=', $startDate)
                    ->get()
                    ->groupBy('employee_id');

                foreach ($attendancesByEmployee as $employeeId => $employeeAttendances) {
                    $employee = $employeeAttendances->first()->employee;
                    if (!$employee) continue;

                    $vacationDays = [];
                    foreach (($vacationsByEmployee[$employeeId] ?? collect()) as $v) {
                        $cursor = $v->start_date->copy();
                        $end = $v->end_date->copy();
                        while ($cursor->lte($end)) {
                            $vacationDays[$cursor->format('Y-m-d')] = true;
                            $cursor->addDay();
                        }
                    }

                    fputcsv($file, ['=' . str_repeat('=', 100)]);
                    fputcsv($file, ['EMPLOYEE: ' . $employee->employee_name]);
                    fputcsv($file, ['Staff ID: ' . $employee->staff_number . ' | Designation: ' . ($employee->designation ?? 'N/A') . ' | Entity: ' . ($employee->entity->entity_name ?? 'N/A')]);
                    fputcsv($file, ['=' . str_repeat('=', 100)]);
                    fputcsv($file, []);

                    $empPresent = $employeeAttendances->where('status', 'present')->count();
                    $empAbsent = $employeeAttendances->where('status', 'absent')->count();
                    $empHalfDay = $employeeAttendances->where('status', 'half_day')->count();
                    $empLeave = $employeeAttendances->where('status', 'leave')->count();

                    $empExtraDays = $employeeAttendances->filter(function ($att) {
                        $attDate = $att->attendance_date instanceof Carbon
                            ? $att->attendance_date
                            : Carbon::parse($att->attendance_date);

                        $isSunday = $attDate->dayOfWeek === Carbon::SUNDAY;

                        $isHoliday = $this->isHoliday(
                            $attDate->format('Y-m-d'),
                            $att->employee_id
                        );

                        $hasWorked = in_array($att->status, ['present', 'half_day']);
                        return ($isSunday || $isHoliday) && $hasWorked;
                    });

                    $empExtraDaysCount = $empExtraDays->count();
                    $empTotalHours = $employeeAttendances->sum('total_hours');
                    $empOvertimeHours = $employeeAttendances->sum('overtime_hours'); // ✅ Now uses recalculated values

                    fputcsv($file, ['EMPLOYEE SUMMARY']);
                    fputcsv($file, [
                        'Present',
                        'Absent',
                        'Half Day',
                        'Leave',
                        'Extra Days',
                        'Total Hours',
                        'Overtime Hours'
                    ]);
                    fputcsv($file, [
                        $empPresent,
                        $empAbsent,
                        $empHalfDay,
                        $empLeave,
                        $empExtraDaysCount,
                        number_format($empTotalHours, 2) . 'h',
                        number_format($empOvertimeHours, 2) . 'h'
                    ]);
                    fputcsv($file, []);

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

                    $periodStart = Carbon::parse($startDate)->startOfDay();
                    $periodEnd = Carbon::parse($endDate)->startOfDay();
                    $attendanceByDate = $employeeAttendances->keyBy(function($att){
                        $d = $att->attendance_date instanceof Carbon ? $att->attendance_date : Carbon::parse($att->attendance_date);
                        return $d->format('Y-m-d');
                    });

                    $cursor = $periodStart->copy();
                    while ($cursor->lte($periodEnd)) {
                        $dateKey = $cursor->format('Y-m-d');
                        $attendance = $attendanceByDate->get($dateKey);
                        $isVacationDay = isset($vacationDays[$dateKey]);

                        if (!$attendance && !$isVacationDay) {
                            $cursor->addDay();
                            continue;
                        }

                        if ($attendance) {
                            $attendanceDate = $attendance->attendance_date instanceof Carbon
                                ? $attendance->attendance_date
                                : Carbon::parse($attendance->attendance_date);

                            $dateKey = $attendanceDate->format('Y-m-d');
                            $isVacation = isset($vacationDays[$dateKey]);

                            $isSunday = $attendanceDate->dayOfWeek === Carbon::SUNDAY;

                            $isHoliday = $this->isHoliday(
                                $attendanceDate->format('Y-m-d'),
                                $attendance->employee_id
                            );

                            $isExtraDay = ($isSunday || $isHoliday) && in_array($attendance->status, ['present', 'half_day']);

                            $dayLabel = $attendanceDate->format('l');
                            if ($isExtraDay) {
                                $dayLabel .= ' (EXTRA DAY)';
                            }

                            $statusLabel = ucfirst(str_replace('_', ' ', $attendance->status));
                            if ($isExtraDay) {
                                $statusLabel .= ' (Extra Day)';
                            } elseif ($isSunday && $attendance->status === 'absent') {
                                $statusLabel = 'Sunday (Off Day)';
                            }
                            if ($isVacation) {
                                $statusLabel .= ' (Vacation)';
                            }

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
                        } else {
                            fputcsv($file, [
                                $cursor->format('Y-m-d'),
                                $cursor->format('l'),
                                '-',
                                '-',
                                '0h',
                                '0h',
                                '0h',
                                'Vacation',
                            ]);
                        }

                        $cursor->addDay();
                    }

                    fputcsv($file, []);
                    fputcsv($file, []);
                }

                fputcsv($file, []);
                fputcsv($file, ['=' . str_repeat('=', 100)]);
                fputcsv($file, ['© ' . date('Y') . ' Voltronix HRM System']);
                fputcsv($file, ['Working Days: Mon-Sat (8:00 AM - 6:00 PM)']);
                fputcsv($file, ['Extra Days: Sundays + Holidays worked']);
                fputcsv($file, ['Overtime: Hours beyond 10 hours + Sunday/Holiday work']);
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

    // Helper method for formatting hours (moved from Blade)
    private function formatHoursMinutes($decimalHours)
    {
        if (!$decimalHours || $decimalHours == 0) return '0h 0m';

        $hours = floor($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);

        // Handle rounding edge case
        if ($minutes == 60) {
            $hours++;
            $minutes = 0;
        }

        return $hours . 'h ' . $minutes . 'm';
    }

}
