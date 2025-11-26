<?php
// app/Models/Employee.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Employee extends Model
{
    use SoftDeletes;

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
        'last_vacation_date',
        'basic_salary',
        'allowance',
        'fixed_salary',
        'total_salary',
        'recent_increment_amount',
        'increment_date',
        'passport_expiry_date',
        'visa_expiry_date',
        'visit_expiry_date',
        'eid_expiry_date',
        'health_insurance_expiry_date',
        'driving_license_expiry_date',
        'salary_card_details',
        'iloe_insurance_expiry_date',
        'vtnx_trade_license_renewal_date',
        'po_box_renewal_date',
        'soe_card_renewal_date',
        'dcd_card_renewal_date',
        'voltronix_est_card_renewal_date',
        'warehouse_ejari_renewal_date',
        'camp_ejari_renewal_date',
        'workman_insurance_expiry_date',
        'etisalat_contract_expiry_date',
        'dewa_details',
        'remarks',
        'status',
        'year_of_completion',
        'qualification_document',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'duty_joined_date' => 'date',
        'duty_end_date' => 'date',
        'last_vacation_date' => 'date',
        'increment_date' => 'date',
        'passport_expiry_date' => 'date',
        'visa_expiry_date' => 'date',
        'visit_expiry_date' => 'date',
        'eid_expiry_date' => 'date',
        'health_insurance_expiry_date' => 'date',
        'driving_license_expiry_date' => 'date',
        'iloe_insurance_expiry_date' => 'date',
        'vtnx_trade_license_renewal_date' => 'date',
        'po_box_renewal_date' => 'date',
        'soe_card_renewal_date' => 'date',
        'dcd_card_renewal_date' => 'date',
        'voltronix_est_card_renewal_date' => 'date',
        'warehouse_ejari_renewal_date' => 'date',
        'camp_ejari_renewal_date' => 'date',
        'workman_insurance_expiry_date' => 'date',
        'etisalat_contract_expiry_date' => 'date',
        'resignation_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowance' => 'decimal:2',
        'fixed_salary' => 'decimal:2',
        'total_salary' => 'decimal:2',
        'recent_increment_amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
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

    /**
     * Get all document expiry dates for alerts
     */
    public function getAllDocumentFields()
    {
        return [
            'passport_expiry_date' => 'Passport',
            'visa_expiry_date' => 'Visa',
            'visit_expiry_date' => 'Visit Permit',
            'eid_expiry_date' => 'EID',
            'health_insurance_expiry_date' => 'Health Insurance',
            'driving_license_expiry_date' => 'Driving License',
            'iloe_insurance_expiry_date' => 'ILOE Insurance',
            'vtnx_trade_license_renewal_date' => 'VTNX Trade License',
            'po_box_renewal_date' => 'PO Box',
            'soe_card_renewal_date' => 'SOE Card',
            'dcd_card_renewal_date' => 'DCD Card',
            'voltronix_est_card_renewal_date' => 'Voltronix EST Card',
            'warehouse_ejari_renewal_date' => 'Warehouse EJARI',
            'camp_ejari_renewal_date' => 'Camp EJARI',
            'workman_insurance_expiry_date' => 'Workman Insurance',
            'etisalat_contract_expiry_date' => 'Etisalat Contract',
        ];
    }

    // Auto-calculate duty days
    public function getDutyDaysAttribute()
    {
        if (!$this->duty_joined_date) {
            return 0;
        }

        $endDate = $this->duty_end_date ?? Carbon::now();
        return $this->duty_joined_date->diffInDays($endDate);
    }

    // Auto-calculate duty years
    public function getDutyYearsAttribute()
    {
        if (!$this->duty_joined_date) {
            return 0;
        }

        $endDate = $this->duty_end_date ?? Carbon::now();
        $years = $this->duty_joined_date->diffInDays($endDate) / 365.25;
        return round($years, 2);
    }
}
