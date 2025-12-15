<?php
// app/Models/Vehicle.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_number',
        'vehicle_name',
        'vehicle_plate_number',
        'assigned_to',
        'under_company',
        'mulkiya_expiry_date',
        'mulkiya_document',
        'driving_license_expiry_date',
        'driving_license_document',
        'insurance',
        'status',
        'remarks',
    ];

    protected $casts = [
        'mulkiya_expiry_date' => 'date',
        'driving_license_expiry_date' => 'date',
    ];

    public function getDocumentStatus($date)
    {
        if (!$date) return null;

        $daysUntil = Carbon::today()->diffInDays($date, false);

        if ($daysUntil < 0) {
            return ['label' => 'Expired', 'class' => 'danger', 'days' => $daysUntil];
        } elseif ($daysUntil <= 20) {
            return ['label' => 'Critical', 'class' => 'danger', 'days' => $daysUntil];
        } elseif ($daysUntil <= 60) {
            return ['label' => 'Warning', 'class' => 'warning', 'days' => $daysUntil];
        } else {
            return ['label' => 'Valid', 'class' => 'success', 'days' => $daysUntil];
        }
    }
}
