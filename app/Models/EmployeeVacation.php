<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeVacation extends Model
{
    use HasFactory;

    protected $table = 'employee_vacations';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'remarks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Optional helper: show "ongoing" if end_date is null (only if you allow it in DB)
    // public function getIsOngoingAttribute(): bool
    // {
    //     return $this->end_date === null;
    // }
}
