<?php
// app/Exports/EmployeeExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromCollection, WithHeadings, WithStyles
{
    private $employees;

    public function __construct($employees)
    {
        $this->employees = $employees;
    }

    public function collection()
    {
        return $this->employees->map(function ($employee) {
            return [
                $employee->staff_number,
                $employee->employee_name,
                $employee->user->email ?? '',
                $employee->designation,
                $employee->qualification,
                $employee->pp_status,
                $employee->uae_contact,
                $employee->home_country_contact,
                $employee->date_of_birth?->format('Y-m-d'),
                $employee->duty_joined_date?->format('Y-m-d'),
                $employee->duty_end_date?->format('Y-m-d'),
                $employee->last_vacation_date?->format('Y-m-d'),
                'AED ' . number_format($employee->basic_salary, 2),
                'AED ' . number_format($employee->allowance, 2),
                'AED ' . number_format($employee->fixed_salary, 2),
                'AED ' . number_format($employee->total_salary, 2),
                'AED ' . number_format($employee->recent_increment_amount ?? 0, 2),
                $employee->increment_date?->format('Y-m-d'),
                $employee->passport_expiry_date?->format('Y-m-d'),
                $employee->visa_expiry_date?->format('Y-m-d'),
                $employee->visit_expiry_date?->format('Y-m-d'),
                $employee->eid_expiry_date?->format('Y-m-d'),
                $employee->health_insurance_expiry_date?->format('Y-m-d'),
                $employee->driving_license_expiry_date?->format('Y-m-d'),
                $employee->salary_card_details,
                $employee->remarks,
                $employee->status,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Staff Number',
            'Employee Name',
            'Email',
            'Designation',
            'Qualification',
            'PP Status',
            'UAE Contact',
            'Home Country Contact',
            'Date of Birth',
            'Duty Joined Date',
            'Duty End Date',
            'Last Vacation Date',
            'Basic Salary (AED)',
            'Allowance (AED)',
            'Fixed Salary (AED)',
            'Total Salary (AED)',
            'Recent Increment Amount (AED)',
            'Increment Date',
            'Passport Expiry Date',
            'Visa Expiry Date',
            'Visit Expiry Date',
            'EID Expiry Date',
            'Health Insurance Expiry Date',
            'Driving License Expiry Date',
            'Salary Card Details',
            'Remarks',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4361EE']]
            ],
        ];
    }
}
