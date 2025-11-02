<?php
// app/Exports/EmployeeTemplateExport.php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            ['EMP001', 'Ahmed Hassan', 'ahmed@company.com', 'Electrician', 'Diploma', '', '+971501234567', '+201023456789', '1990-01-15', '2022-01-10', '', '', '2500', '500', '200', '3200', '', '', '2027-06-15', '2028-12-31', '', '2025-09-20', '2025-12-31', '2026-03-15', '', '', 'active'],
        ];
    }

    public function headings(): array
    {
        return [
            'Staff Number*',
            'Employee Name*',
            'Email*',
            'Designation*',
            'Qualification',
            'PP Status',
            'UAE Contact',
            'Home Country Contact',
            'Date of Birth (YYYY-MM-DD)',
            'Duty Joined Date (YYYY-MM-DD)',
            'Duty End Date (YYYY-MM-DD)',
            'Last Vacation Date (YYYY-MM-DD)',
            'Basic Salary*',
            'Allowance',
            'Fixed Salary',
            'Total Salary',
            'Recent Increment Amount',
            'Increment Date (YYYY-MM-DD)',
            'Passport Expiry Date (YYYY-MM-DD)',
            'Visa Expiry Date (YYYY-MM-DD)',
            'Visit Expiry Date (YYYY-MM-DD)',
            'EID Expiry Date (YYYY-MM-DD)',
            'Health Insurance Expiry Date (YYYY-MM-DD)',
            'Driving License Expiry Date (YYYY-MM-DD)',
            'Salary Card Details',
            'Remarks',
            'Status (active/inactive/vacation/terminated)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(15);

        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4361EE']], 'font' => ['color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
