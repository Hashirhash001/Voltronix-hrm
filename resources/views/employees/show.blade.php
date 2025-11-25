{{-- resources/views/employees/show.blade.php --}}
@extends('layouts.app')

@section('title', 'View Employee')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <a href="{{ route('employees.index') }}" class="text-primary hover:underline">Employees</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>{{ $employee->employee_name }}</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-semibold dark:text-white-light">{{ $employee->employee_name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-info">Edit</a>
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Personal Information -->
            <div class="panel">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Personal Information</h5>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-white-dark">Staff Number:</span>
                        <span class="font-semibold">{{ $employee->staff_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Email:</span>
                        <span class="font-semibold">{{ $employee->user->email ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Designation:</span>
                        <span class="font-semibold">{{ $employee->designation }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Qualification:</span>
                        <span class="font-semibold">{{ $employee->qualification ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">PP Status:</span>
                        <span class="font-semibold">{{ $employee->pp_status ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Date of Birth:</span>
                        <span class="font-semibold">{{ $employee->date_of_birth?->format('d M Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Age:</span>
                        <span class="font-semibold">{{ $employee->current_age ?? 'N/A' }} years</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Status:</span>
                        <span class="badge {{ $employee->status === 'active' ? 'bg-success' : ($employee->status === 'inactive' ? 'bg-warning' : 'bg-danger') }}">
                            {{ ucfirst($employee->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="panel">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Contact Information</h5>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-white-dark">UAE Contact:</span>
                        <span class="font-semibold">{{ $employee->uae_contact ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Home Contact:</span>
                        <span class="font-semibold">{{ $employee->home_country_contact ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Salary Information -->
            <div class="panel">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Salary Information (AED)</h5>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-white-dark">Basic Salary:</span>
                        <span class="font-semibold">AED {{ number_format($employee->basic_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Allowance:</span>
                        <span class="font-semibold">AED {{ number_format($employee->allowance, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Fixed Salary:</span>
                        <span class="font-semibold">AED {{ number_format($employee->fixed_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-white-light pt-3 dark:border-[#1b2e4b]">
                        <span class="text-white-dark font-semibold">Total Salary:</span>
                        <span class="font-bold text-primary text-lg">AED {{ number_format($employee->total_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Recent Increment:</span>
                        <span class="font-semibold">AED {{ number_format($employee->recent_increment_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Increment Date:</span>
                        <span class="font-semibold">{{ $employee->increment_date?->format('d M Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Salary Card:</span>
                        <span class="font-semibold">{{ $employee->salary_card_details ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Duty Information -->
            <div class="panel">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Duty Information</h5>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-white-dark">Duty Joined:</span>
                        <span class="font-semibold">{{ $employee->duty_joined_date?->format('d M Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Duty End Date:</span>
                        <span class="font-semibold">{{ $employee->duty_end_date?->format('d M Y') ?? 'Ongoing' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Duty Days:</span>
                        <span class="font-semibold">{{ $employee->duty_days ?? 'N/A' }} days</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Duty Years:</span>
                        <span class="font-semibold">{{ number_format($employee->duty_years, 2) ?? 'N/A' }} years</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Last Vacation:</span>
                        <span class="font-semibold">{{ $employee->last_vacation_date?->format('d M Y') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Personal Documents - Individual -->
            <div class="panel lg:col-span-2">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Personal Documents Expiry</h5>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $personalDocs = [
                            'passport_expiry_date' => 'Passport',
                            'visa_expiry_date' => 'Visa',
                            'visit_expiry_date' => 'Visit Permit',
                            'eid_expiry_date' => 'EID',
                            'health_insurance_expiry_date' => 'Health Insurance',
                            'driving_license_expiry_date' => 'Driving License',
                        ];
                    @endphp

                    @foreach($personalDocs as $field => $label)
                        @if($employee->$field)
                            @php
                                $daysUntil = \Carbon\Carbon::today()->diffInDays($employee->$field, false);
                                if ($daysUntil < 0) {
                                    $status = ['label' => 'Expired', 'class' => 'danger'];
                                } elseif ($daysUntil <= 20) {
                                    $status = ['label' => 'Critical', 'class' => 'danger'];
                                } elseif ($daysUntil <= 60) {
                                    $status = ['label' => 'Warning', 'class' => 'warning'];
                                } else {
                                    $status = ['label' => 'Valid', 'class' => 'success'];
                                }
                            @endphp
                            <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="font-semibold text-sm">{{ $label }}</span>
                                    <span class="badge bg-{{ $status['class'] }} text-xs">{{ $status['label'] }}</span>
                                </div>
                                <p class="text-xs text-white-dark">
                                    {{ \Carbon\Carbon::parse($employee->$field)->format('d M Y') }}
                                </p>
                                @if($daysUntil >= 0)
                                    <p class="mt-1 text-xs font-semibold text-{{ $status['class'] }}">
                                        {{ $daysUntil }} days left
                                    </p>
                                @else
                                    <p class="mt-1 text-xs font-semibold text-danger">
                                        Expired {{ abs($daysUntil) }} days ago
                                    </p>
                                @endif
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-white-light p-4 dark:border-[#1b2e4b]">
                                <span class="text-xs text-white-dark">{{ $label }}: Not set</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Company & Insurance Documents -->
            <div class="panel lg:col-span-2">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Company & Insurance Documents Expiry</h5>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $companyDocs = [
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
                    @endphp

                    @foreach($companyDocs as $field => $label)
                        @if($employee->$field)
                            @php
                                $daysUntil = \Carbon\Carbon::today()->diffInDays($employee->$field, false);
                                if ($daysUntil < 0) {
                                    $status = ['label' => 'Expired', 'class' => 'danger'];
                                } elseif ($daysUntil <= 20) {
                                    $status = ['label' => 'Critical', 'class' => 'danger'];
                                } elseif ($daysUntil <= 60) {
                                    $status = ['label' => 'Warning', 'class' => 'warning'];
                                } else {
                                    $status = ['label' => 'Valid', 'class' => 'success'];
                                }
                            @endphp
                            <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="font-semibold text-sm">{{ $label }}</span>
                                    <span class="badge bg-{{ $status['class'] }} text-xs">{{ $status['label'] }}</span>
                                </div>
                                <p class="text-xs text-white-dark">
                                    {{ \Carbon\Carbon::parse($employee->$field)->format('d M Y') }}
                                </p>
                                @if($daysUntil >= 0)
                                    <p class="mt-1 text-xs font-semibold text-{{ $status['class'] }}">
                                        {{ $daysUntil }} days left
                                    </p>
                                @else
                                    <p class="mt-1 text-xs font-semibold text-danger">
                                        Expired {{ abs($daysUntil) }} days ago
                                    </p>
                                @endif
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-white-light p-4 dark:border-[#1b2e4b]">
                                <span class="text-xs text-white-dark">{{ $label }}: Not set</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Additional Information -->
            <div class="panel lg:col-span-2">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Additional Information</h5>
                </div>
                <div class="space-y-4">
                    <div>
                        <span class="text-white-dark">DEWA Details:</span>
                        <p class="mt-1 font-semibold">{{ $employee->dewa_details ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-white-dark">Remarks:</span>
                        <p class="mt-1 font-semibold">{{ $employee->remarks ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="panel lg:col-span-2">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Recent Attendance</h5>
                </div>
                <div class="table-responsive">
                    <table class="table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                                <th>Overtime</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->attendances()->latest('attendance_date')->limit(10)->get() as $attendance)
                                <tr>
                                    <td>{{ $attendance->attendance_date->format('d M Y') }}</td>
                                    <td>{{ $attendance->check_in_time?->format('H:i') ?? '-' }}</td>
                                    <td>{{ $attendance->check_out_time?->format('H:i') ?? '-' }}</td>
                                    <td>{{ number_format($attendance->total_hours, 2) }}</td>
                                    <td>
                                        @if($attendance->overtime_hours > 0)
                                            <span class="badge bg-warning">{{ number_format($attendance->overtime_hours, 2) }}h</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $attendance->status === 'present' ? 'success' : ($attendance->status === 'absent' ? 'danger' : 'info') }}">
                                            {{ ucfirst($attendance->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-white-dark">No attendance records</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
