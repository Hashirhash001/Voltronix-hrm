<?php
// app/Models/DocumentExpiryAlert.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DocumentExpiryAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'document_type',
        'expiry_date',
        'days_until_expiry',
        'alert_level',
        'is_notified',
        'notified_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_notified' => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    // Update alert level based on days until expiry
    public function updateAlertLevel()
    {
        $today = Carbon::today();
        $this->days_until_expiry = $today->diffInDays($this->expiry_date, false);

        if ($this->days_until_expiry < 0) {
            $this->alert_level = 'expired';
        } elseif ($this->days_until_expiry <= 30) {
            $this->alert_level = 'red';
        } elseif ($this->days_until_expiry <= 60) {
            $this->alert_level = 'yellow';
        } else {
            $this->alert_level = 'green';
        }

        $this->save();
    }

    // Get color for UI
    public function getAlertColor()
    {
        return match($this->alert_level) {
            'expired' => '#dc3545',
            'red' => '#dc3545',
            'yellow' => '#ffc107',
            'green' => '#28a745',
            default => '#6c757d',
        };
    }
}
