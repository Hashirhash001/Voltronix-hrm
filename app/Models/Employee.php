<?php
// app/Models/Employee.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'staff_number',
        'employee_name',
        'designation',
        'qualification',
        'pp_status',
        'uae_contact',
        'home_country_contact',
        'date_of_birth',
        'current_age',
        'duty_joined_date',
        'duty_end_date',
        'duty_days',
        'duty_years',
        'last_vacation_date',
        'basic_salary',
        'allowance',
        'fixed_salary',
        'total_salary',
        'recent_increment_amount',
        'increment_date',
        'passport_expiry_date',
        'visit_expiry_date',
        'visa_expiry_date',
        'eid_expiry_date',
        'health_insurance_expiry_date',
        'driving_license_expiry_date',
        'salary_card_details',
        'remarks',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'duty_joined_date' => 'date',
        'duty_end_date' => 'date',
        'last_vacation_date' => 'date',
        'increment_date' => 'date',
        'passport_expiry_date' => 'date',
        'visit_expiry_date' => 'date',
        'visa_expiry_date' => 'date',
        'eid_expiry_date' => 'date',
        'health_insurance_expiry_date' => 'date',
        'driving_license_expiry_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowance' => 'decimal:2',
        'fixed_salary' => 'decimal:2',
        'total_salary' => 'decimal:2',
        'recent_increment_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function overtimeRecords()
    {
        return $this->hasMany(OvertimeRecord::class);
    }

    public function documentExpiryAlerts()
    {
        return $this->hasMany(DocumentExpiryAlert::class);
    }

    public function getExpiringDocuments()
    {
        $documents = [];
        $today = Carbon::today();
        $threeMonthsLater = $today->copy()->addMonths(3);

        $documentFields = [
            'passport_expiry_date' => 'Passport',
            'visa_expiry_date' => 'Visa',
            'health_insurance_expiry_date' => 'Health Insurance',
            'driving_license_expiry_date' => 'Driving License',
            'eid_expiry_date' => 'EID',
            'visit_expiry_date' => 'Visit Permit',
        ];

        foreach ($documentFields as $field => $name) {
            if ($this->$field) {
                $expiryDate = Carbon::parse($this->$field);
                $daysUntilExpiry = $today->diffInDays($expiryDate, false);

                if ($daysUntilExpiry <= 90) {
                    $documents[] = [
                        'name' => $name,
                        'expiry_date' => $expiryDate,
                        'days_until_expiry' => $daysUntilExpiry,
                        'status' => $this->getExpiryStatus($daysUntilExpiry),
                    ];
                }
            }
        }

        return collect($documents);
    }

    private function getExpiryStatus($days)
    {
        if ($days < 0) {
            return 'expired';
        } elseif ($days <= 30) {
            return 'critical';
        } elseif ($days <= 60) {
            return 'warning';
        } else {
            return 'notice';
        }
    }
}
