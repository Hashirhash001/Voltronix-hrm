<?php
// app/Models/OvertimeRecord.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'overtime_date',
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'status',
        'reason',
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    // Calculate overtime amount
    public function calculateOvertimeAmount()
    {
        $hourlyRate = $this->employee->basic_salary / 30 / 8; // Monthly salary to hourly
        $this->overtime_amount = round($hourlyRate * $this->overtime_rate * $this->overtime_hours, 2);
        $this->save();
    }
}
