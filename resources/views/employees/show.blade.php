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
                        <span class="text-white-dark">Date of Birth:</span>
                        <span class="font-semibold">{{ $employee->date_of_birth?->format('d M Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Status:</span>
                        <span class="badge {{ $employee->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($employee->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Salary Information -->
            <div class="panel">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Salary Information</h5>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-white-dark">Basic Salary:</span>
                        <span class="font-semibold">{{ number_format($employee->basic_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Allowance:</span>
                        <span class="font-semibold">{{ number_format($employee->allowance, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Fixed Salary:</span>
                        <span class="font-semibold">{{ number_format($employee->fixed_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-white-light pt-3 dark:border-[#1b2e4b]">
                        <span class="text-white-dark">Total Salary:</span>
                        <span class="font-bold text-primary">{{ number_format($employee->total_salary, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Document Expiry Status -->
            <div class="panel lg:col-span-2">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Document Expiry Status</h5>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $documents = [
                            'passport_expiry_date' => 'Passport',
                            'visa_expiry_date' => 'Visa',
                            'health_insurance_expiry_date' => 'Health Insurance',
                            'driving_license_expiry_date' => 'Driving License',
                            'eid_expiry_date' => 'EID',
                        ];
                    @endphp

                    @foreach($documents as $field => $label)
                        @if($employee->$field)
                            @php
                                $daysUntil = \Carbon\Carbon::today()->diffInDays($employee->$field, false);
                                if ($daysUntil < 0) {
                                    $status = ['label' => 'Expired', 'class' => 'danger'];
                                } elseif ($daysUntil <= 30) {
                                    $status = ['label' => 'Critical', 'class' => 'danger'];
                                } elseif ($daysUntil <= 60) {
                                    $status = ['label' => 'Warning', 'class' => 'warning'];
                                } else {
                                    $status = ['label' => 'Valid', 'class' => 'success'];
                                }
                            @endphp
                            <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="font-semibold">{{ $label }}</span>
                                    <span class="badge bg-{{ $status['class'] }}">{{ $status['label'] }}</span>
                                </div>
                                <p class="text-xs text-white-dark">
                                    {{ \Carbon\Carbon::parse($employee->$field)->format('d M Y') }}
                                </p>
                                @if($daysUntil >= 0)
                                    <p class="mt-1 text-xs font-semibold text-{{ $status['class'] }}">
                                        {{ $daysUntil }} days remaining
                                    </p>
                                @else
                                    <p class="mt-1 text-xs font-semibold text-danger">
                                        Expired {{ abs($daysUntil) }} days ago
                                    </p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Attendance Summary -->
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
