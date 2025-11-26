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
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'is_late' => 'boolean',
    ];

    // Append computed attributes
    protected $appends = ['late_status'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimeRecord()
    {
        return $this->hasOne(OvertimeRecord::class);
    }

    /**
     * Calculate hours, overtime, and late entry
     * Working hours: 8 AM - 6 PM (10 hours standard)
     * After 6 PM = Overtime
     * After 8 AM check-in = Late
     */
    public function calculateHours()
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            $this->regular_hours = 0;
            $this->overtime_hours = 0;
            $this->total_hours = 0;
            $this->late_minutes = 0;
            $this->is_late = false;
            $this->save();
            return;
        }

        $checkIn = Carbon::parse($this->check_in_time);
        $checkOut = Carbon::parse($this->check_out_time);

        // Define work schedule: 8 AM to 6 PM (standard 10-hour workday)
        $workStart = Carbon::parse($this->attendance_date)->setTime(8, 0, 0);
        $workEnd = Carbon::parse($this->attendance_date)->setTime(18, 0, 0);

        // Calculate late entry (if checked in after 8 AM)
        if ($checkIn->greaterThan($workStart)) {
            $this->late_minutes = $checkIn->diffInMinutes($workStart);
            $this->is_late = true;
        } else {
            $this->late_minutes = 0;
            $this->is_late = false;
        }

        // Calculate hours worked
        $totalMinutesWorked = $checkIn->diffInMinutes($checkOut);
        $hoursWorked = $totalMinutesWorked / 60;

        // Split into regular hours and overtime
        if ($checkOut->lessThanOrEqualTo($workEnd)) {
            // Left before or at 6 PM - no overtime
            $this->regular_hours = $hoursWorked;
            $this->overtime_hours = 0;
        } else {
            // Worked past 6 PM - calculate overtime
            // Regular hours = hours worked up to 6 PM
            $minutesUntilWorkEnd = $checkIn->diffInMinutes($workEnd);
            $this->regular_hours = max(0, $minutesUntilWorkEnd / 60);

            // Overtime = hours worked after 6 PM
            $this->overtime_hours = $workEnd->diffInMinutes($checkOut) / 60;
        }

        // Deduct late time from regular hours (not from overtime)
        if ($this->is_late && $this->regular_hours > 0) {
            $lateHours = $this->late_minutes / 60;
            $this->regular_hours = max(0, $this->regular_hours - $lateHours);
        }

        // Total hours = regular + overtime
        $this->total_hours = $this->regular_hours + $this->overtime_hours;

        $this->save();
    }

    // Check if has overtime
    public function hasOvertime()
    {
        return $this->overtime_hours > 0;
    }

    /**
     * Get late entry status text
     */
    public function getLateStatusAttribute()
    {
        // Check if late_minutes column exists
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
}
