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
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'total_hours' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimeRecord()
    {
        return $this->hasOne(OvertimeRecord::class);
    }

    // Calculate hours worked
    public function calculateHours()
    {
        if ($this->check_in_time && $this->check_out_time) {
            $checkIn = Carbon::parse($this->check_in_time);
            $checkOut = Carbon::parse($this->check_out_time);

            $totalHours = $checkOut->diffInMinutes($checkIn) / 60;
            $this->total_hours = round($totalHours, 2);

            // Standard working hours (8 hours)
            $standardHours = 8;

            if ($totalHours > $standardHours) {
                $this->regular_hours = $standardHours;
                $this->overtime_hours = round($totalHours - $standardHours, 2);
            } else {
                $this->regular_hours = round($totalHours, 2);
                $this->overtime_hours = 0;
            }

            $this->save();
        }
    }

    // Check if has overtime
    public function hasOvertime()
    {
        return $this->overtime_hours > 0;
    }
}
