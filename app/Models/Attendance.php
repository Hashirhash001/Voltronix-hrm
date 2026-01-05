<?php
// app/Models/Attendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'staff_number',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'regular_hours',
        'overtime_hours',
        'total_hours',
        'status',
        'notes',
        'late_minutes',
        'is_late',
        'overtime_before_midnight_hours',
        'overtime_after_midnight_hours',
    ];

    // Cast only attendance_date as date
    protected $casts = [
        'attendance_date' => 'date',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'is_late' => 'boolean',
        'overtime_before_midnight_hours' => 'decimal:2',
        'overtime_after_midnight_hours' => 'decimal:2',
    ];

    protected $appends = ['late_status', 'check_in_carbon', 'check_out_carbon'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimeRecord()
    {
        return $this->hasOne(OvertimeRecord::class);
    }

    /**
     * Get check_in_time as Carbon instance for formatting
     */
    public function getCheckInCarbonAttribute()
    {
        return $this->check_in_time ? Carbon::parse($this->check_in_time) : null;
    }

    /**
     * Get check_out_time as Carbon instance for formatting
     */
    public function getCheckOutCarbonAttribute()
    {
        return $this->check_out_time ? Carbon::parse($this->check_out_time) : null;
    }

    /**
     * Calculate hours and overtime
     * Standard working hours: 10 hours (8 AM to 6 PM)
     * After 6 PM = Overtime
     * ✅ ALL SUNDAY WORK = OVERTIME
     */
    public function calculateHours()
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            $this->update([
                'total_hours' => 0,
                'regular_hours' => 0,
                'overtime_hours' => 0,
                'overtime_before_midnight_hours' => 0,
                'overtime_after_midnight_hours' => 0,
            ]);
            return;
        }

        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);

        // ✅ Cross-midnight handling
        if ($checkOut->lessThan($checkIn)) {
            $checkOut = $checkOut->copy()->addDay();
        }

        $totalMinutes = $checkIn->diffInMinutes($checkOut);
        $totalHours = round($totalMinutes / 60, 2);

        $attendanceDate = $this->attendance_date instanceof Carbon
            ? $this->attendance_date
            : Carbon::parse($this->attendance_date);

        $isSunday = $attendanceDate->dayOfWeek === Carbon::SUNDAY;

        // Midnight boundary = next day 00:00
        $midnight = $attendanceDate->copy()->addDay()->startOfDay();

        // Split shift into before/after midnight minutes
        $beforeMidnightMinutes = 0;
        $afterMidnightMinutes = 0;

        if ($checkOut->lessThanOrEqualTo($midnight)) {
            $beforeMidnightMinutes = $checkIn->diffInMinutes($checkOut);
        } elseif ($checkIn->greaterThanOrEqualTo($midnight)) {
            $afterMidnightMinutes = $checkIn->diffInMinutes($checkOut);
        } else {
            $beforeMidnightMinutes = $checkIn->diffInMinutes($midnight);
            $afterMidnightMinutes = $midnight->diffInMinutes($checkOut);
        }

        $beforeMidnightHours = round($beforeMidnightMinutes / 60, 2);
        $afterMidnightHours = round($afterMidnightMinutes / 60, 2);

        // ✅ Sunday rule: all hours are overtime (your existing approach) [file:4]
        if ($isSunday) {
            $this->update([
                'total_hours' => $totalHours,
                'regular_hours' => 0,
                'overtime_hours' => $totalHours,
                'overtime_before_midnight_hours' => $beforeMidnightHours,
                'overtime_after_midnight_hours' => $afterMidnightHours,
            ]);
            return;
        }

        // Standard working hours: 10 hours (your existing rule) [file:4]
        $regularHours = min($totalHours, 10);
        $overtimeHours = max(0, $totalHours - 10);

        // Split overtime into before/after midnight (after-midnight OT cannot exceed total OT)
        $overtimeAfterMidnight = min($overtimeHours, $afterMidnightHours);
        $overtimeBeforeMidnight = max(0, $overtimeHours - $overtimeAfterMidnight);

        $this->update([
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'overtime_before_midnight_hours' => $overtimeBeforeMidnight,
            'overtime_after_midnight_hours' => $overtimeAfterMidnight,
        ]);
    }

    public function hasOvertime()
    {
        return $this->overtime_hours > 0;
    }

    public function getLateStatusAttribute()
    {
        if (!isset($this->attributes['late_minutes']) || !isset($this->attributes['is_late'])) {
            return 'On Time';
        }

        if (!$this->is_late || $this->late_minutes <= 0) {
            return 'On Time';
        }

        $hours = floor($this->late_minutes / 60);
        $minutes = $this->late_minutes % 60;

        if ($hours > 0) {
            return "Late by {$hours}h {$minutes}m";
        }

        return "Late by {$minutes}m";
    }

    public function getFormattedTotalHours()
    {
        if (!$this->total_hours) {
            return '0h 0m';
        }

        $hours = floor($this->total_hours);
        $minutes = round(($this->total_hours - $hours) * 60);

        return "{$hours}h {$minutes}m";
    }

    public function getFormattedOvertimeHours()
    {
        if (!$this->overtime_hours) {
            return '0h 0m';
        }

        $hours = floor($this->overtime_hours);
        $minutes = round(($this->overtime_hours - $hours) * 60);

        return "{$hours}h {$minutes}m";
    }

    /**
     * Format check in time for display
     */
    public function getFormattedCheckInTime()
    {
        return $this->check_in_carbon ? $this->check_in_carbon->format('h:i A') : '-';
    }

    /**
     * Format check out time for display
     */
    public function getFormattedCheckOutTime()
    {
        return $this->check_out_carbon ? $this->check_out_carbon->format('h:i A') : '-';
    }

    /**
     * Check if employee should be marked on leave
     * Based on work schedule (Mon-Sat)
     */
    public function shouldBeOnLeave()
    {
        $dayOfWeek = Carbon::parse($this->attendance_date)->dayOfWeek;

        // Sunday = 0
        // If it's Sunday, it's a weekly off, not a leave
        if ($dayOfWeek === 0) {
            return false;
        }

        // Mon-Sat are working days
        return true;
    }

    /**
     * Helper to format decimal hours to h m format
     */
    private function formatHoursMinutes($decimalHours)
    {
        if (!$decimalHours || $decimalHours <= 0) {
            return '0h 0m';
        }

        $hours = intval($decimalHours);
        $minutes = round(($decimalHours - $hours) * 60);

        // Handle case where minutes round to 60
        if ($minutes == 60) {
            $hours++;
            $minutes = 0;
        }

        return "{$hours}h {$minutes}m";
    }
}
