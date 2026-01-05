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
        <div class="panel mb-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    {{-- Avatar --}}
                    <div class="flex justify-center sm:justify-start">
                        @if($employee->employee_image)
                            <img
                                src="{{ asset('storage/' . $employee->employee_image) }}"
                                alt="Employee Image"
                                class="h-16 w-16 rounded-full object-cover ring-2 ring-white-light dark:ring-[#1b2e4b]"
                            >
                        @else
                            <div class="h-16 w-16 rounded-full bg-white-light/60 dark:bg-[#1b2e4b]/60 flex items-center justify-center ring-2 ring-white-light dark:ring-[#1b2e4b]">
                                {{-- default user icon --}}
                                <svg class="h-8 w-8 text-white-dark" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5Z" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M20 21c0-4.418-3.582-8-8-8s-8 3.582-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    {{-- Name + meta --}}
                    <div>
                        <h2 class="text-2xl font-semibold dark:text-white-light leading-tight">
                            {{ $employee->employee_name }}
                        </h2>

                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-white-dark">
                            <span class="mr-2">Staff: <span class="font-semibold text-black dark:text-white-light">{{ $employee->staff_number }}</span></span>

                            @php
                                $statusClass = match($employee->status) {
                                    'active' => 'bg-success',
                                    'inactive' => 'bg-warning',
                                    'vacation' => 'bg-info',
                                    'terminated' => 'bg-danger',
                                    'resigned' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp

                            <span class="badge {{ $statusClass }}">{{ ucfirst($employee->status) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 flex-wrap">
                    <button type="button" onclick="openAddVacationModal()" class="btn btn-success gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Vacation
                    </button>
                    <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-info">Edit Employee</a>
                    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
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
                        <span class="text-white-dark">Year of Completion:</span>
                        <span class="font-semibold">{{ $employee->year_of_completion ?? 'N/A' }}</span>
                    </div>

                    @if($employee->qualification_document)
                        <div class="flex justify-between">
                            <span class="text-white-dark">Qualification Doc:</span>
                            <a href="{{ asset('storage/' . $employee->qualification_document) }}" target="_blank"
                               class="text-primary hover:underline flex items-center font-semibold">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                </svg>
                                Download
                            </a>
                        </div>
                    @endif

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
                        @php
                            $statusClass = match($employee->status) {
                                'active' => 'bg-success',
                                'inactive' => 'bg-warning',
                                'vacation' => 'bg-info',
                                'terminated' => 'bg-danger',
                                'resigned' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $statusClass }}">
                            {{ ucfirst($employee->status) }}
                        </span>
                    </div>

                    {{-- NEW: show termination/resignation dates --}}
                    @if($employee->status === 'terminated')
                        <div class="flex justify-between">
                            <span class="text-white-dark">Termination Date:</span>
                            <span class="font-semibold text-danger">
                                {{ $employee->termination_date?->format('d M Y') ?? 'N/A' }}
                            </span>
                        </div>
                    @endif

                    @if($employee->status === 'resigned')
                        <div class="flex justify-between">
                            <span class="text-white-dark">Resignation Date:</span>
                            <span class="font-semibold text-danger">
                                {{ $employee->resignation_date?->format('d M Y') ?? 'N/A' }}
                            </span>
                        </div>
                    @endif
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
                        <span class="font-semibold">AED {{ number_format($employee->basic_salary ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Allowance:</span>
                        <span class="font-semibold">AED {{ number_format($employee->allowance ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Fixed Salary:</span>
                        <span class="font-semibold">AED {{ number_format($employee->fixed_salary ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-white-light pt-3 dark:border-[#1b2e4b]">
                        <span class="text-white-dark font-semibold">Total Salary:</span>
                        <span class="font-bold text-primary text-lg">AED {{ number_format($employee->total_salary ?? 0, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Recent Increment:</span>
                        <span class="font-semibold">AED {{ number_format($employee->recent_increment_amount ?? 0, 2) }}</span>
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
                        <span class="font-semibold {{ in_array($employee->status, ['resigned', 'terminated']) && $employee->duty_end_date ? 'text-danger' : '' }}">
                            {{ $employee->duty_end_date?->format('d M Y') ?? 'Ongoing' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-white-dark">Duty Days:</span>
                        <span class="font-semibold">{{ $employee->duty_days }} days</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-white-dark">Duty Years:</span>
                        <span class="font-semibold">{{ number_format($employee->duty_years, 2) }} years</span>
                    </div>
                </div>
            </div>

            {{-- Vacation History --}}
            <div class="panel lg:col-span-2">
                <div class="mb-4 flex items-center justify-between border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Vacation History</h5>
                    <button type="button" onclick="openAddVacationModal()" class="btn btn-sm btn-success gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Vacation
                    </button>
                </div>

                @if($employee->vacations && $employee->vacations->count())
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th class="text-left">Start Date</th>
                                <th class="text-left">End Date</th>
                                <th class="text-left">Duration</th>
                                <th class="text-left">Remarks</th>
                                <th class="text-left">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($employee->vacations->sortByDesc('start_date') as $vacation)
                                @php
                                    $duration = \Carbon\Carbon::parse($vacation->start_date)->diffInDays(\Carbon\Carbon::parse($vacation->end_date)) + 1;
                                @endphp
                                <tr>
                                    <td>{{ $vacation->start_date?->format('d M Y') ?? '-' }}</td>
                                    <td>{{ $vacation->end_date?->format('d M Y') ?? '-' }}</td>
                                    <td>{{ $duration }} days</td>
                                    <td>{{ $vacation->remarks ?? '-' }}</td>
                                    <td>
                                        <div class="flex gap-2">
                                            <button type="button"
                                                    onclick="openEditVacationModal({{ $vacation->id }}, '{{ $vacation->start_date->format('Y-m-d') }}', '{{ $vacation->end_date->format('Y-m-d') }}', '{{ addslashes($vacation->remarks ?? '') }}')"
                                                    class="btn btn-sm btn-outline-info">
                                                Edit
                                            </button>
                                            <form method="POST"
                                                  class="vacation-delete-form inline-block"
                                                  action="{{ route('employees.vacations.destroy', ['employee' => $employee->id, 'vacation' => $vacation->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-white-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="mt-2 text-white-dark">No vacations recorded yet.</p>
                        <button type="button" onclick="openAddVacationModal()" class="mt-3 btn btn-sm btn-success">
                            Add First Vacation
                        </button>
                    </div>
                @endif
            </div>

            <!-- Personal Documents Expiry -->
            <div class="panel lg:col-span-2">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Personal Documents Expiry</h5>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $personalDocs = [
                            ['field' => 'passport_expiry_date', 'doc_field' => 'passport_document', 'label' => 'Passport'],
                            ['field' => 'visa_expiry_date', 'doc_field' => 'visa_document', 'label' => 'Visa'],
                            ['field' => 'visit_expiry_date', 'doc_field' => 'visit_document', 'label' => 'Visit Permit'],
                            ['field' => 'eid_expiry_date', 'doc_field' => 'eid_document', 'label' => 'EID'],
                            ['field' => 'health_insurance_expiry_date', 'doc_field' => 'health_insurance_document', 'label' => 'Health Insurance'],
                            ['field' => 'driving_license_expiry_date', 'doc_field' => 'driving_license_document', 'label' => 'Driving License'],
                        ];
                    @endphp

                    @foreach($personalDocs as $doc)
                        @php
                            $field = $doc['field'];
                            $docField = $doc['doc_field'];
                            $label = $doc['label'];
                            $hasExpiry = $employee->$field !== null;
                            $hasDocument = $employee->$docField !== null;
                        @endphp

                        @if($hasExpiry)
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
                            <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b] bg-white dark:bg-[#0e1726]">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="font-semibold text-sm dark:text-white-light">{{ $label }}</span>
                                    <span class="badge bg-{{ $status['class'] }} text-xs">{{ $status['label'] }}</span>
                                </div>
                                <p class="text-xs text-white-dark">
                                    {{ \Carbon\Carbon::parse($employee->$field)->format('d M Y') }}
                                </p>
                                @if($daysUntil >= 0)
                                    <p class="mt-1 text-xs font-semibold" style="color: {{ $status['class'] === 'success' ? '#00ab55' : '#e7515a' }}">
                                        {{ $daysUntil }} days left
                                    </p>
                                @else
                                    <p class="mt-1 text-xs font-semibold text-danger">
                                        Expired {{ abs($daysUntil) }} days ago
                                    </p>
                                @endif

                                @if($hasDocument)
                                    <a href="{{ asset('storage/' . $employee->$docField) }}" target="_blank" class="mt-3 btn btn-sm btn-outline-primary w-full gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                        </svg>
                                        View Document
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-white-light p-4 dark:border-[#1b2e4b] bg-white dark:bg-[#0e1726]">
                                <span class="text-xs text-white-dark">{{ $label }}: Not set</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Company & Insurance Documents Expiry -->
            <div class="panel lg:col-span-2">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Company & Insurance Documents Expiry</h5>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                        $companyDocs = [
                            ['field' => 'iloe_insurance_expiry_date', 'doc_field' => 'iloe_insurance_document', 'label' => 'ILOE Insurance'],
                            ['field' => 'soe_card_renewal_date', 'doc_field' => 'soe_card_document', 'label' => 'SOE Card'],
                            ['field' => 'dcd_card_renewal_date', 'doc_field' => 'dcd_card_document', 'label' => 'DCD Card'],
                            ['field' => 'workman_insurance_expiry_date', 'doc_field' => 'workman_insurance_document', 'label' => 'Workman Insurance'],
                        ];
                    @endphp

                    @foreach($companyDocs as $doc)
                        @php
                            $field = $doc['field'];
                            $docField = $doc['doc_field'];
                            $label = $doc['label'];
                            $hasExpiry = $employee->$field !== null;
                            $hasDocument = $employee->$docField !== null;
                        @endphp

                        @if($hasExpiry)
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
                            <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b] bg-white dark:bg-[#0e1726]">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="font-semibold text-sm dark:text-white-light">{{ $label }}</span>
                                    <span class="badge bg-{{ $status['class'] }} text-xs">{{ $status['label'] }}</span>
                                </div>
                                <p class="text-xs text-white-dark">
                                    {{ \Carbon\Carbon::parse($employee->$field)->format('d M Y') }}
                                </p>
                                @if($daysUntil >= 0)
                                    <p class="mt-1 text-xs font-semibold" style="color: {{ $status['class'] === 'success' ? '#00ab55' : '#e7515a' }}">
                                        {{ $daysUntil }} days left
                                    </p>
                                @else
                                    <p class="mt-1 text-xs font-semibold text-danger">
                                        Expired {{ abs($daysUntil) }} days ago
                                    </p>
                                @endif

                                @if($hasDocument)
                                    <a href="{{ asset('storage/' . $employee->$docField) }}" target="_blank" class="mt-3 btn btn-sm btn-outline-primary w-full gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                        </svg>
                                        View Document
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-white-light p-4 dark:border-[#1b2e4b] bg-white dark:bg-[#0e1726]">
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
                        <span class="text-white-dark">Remarks:</span>
                        <p class="mt-1 font-semibold">{{ $employee->remarks ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Add/Edit Vacation Modal --}}
<div id="vacationModal" class="fixed inset-0 bg-[black]/60 z-[999] hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="panel shadow-lg rounded-lg w-full max-w-lg bg-white dark:bg-[#0e1726] relative my-8 animate__animated animate__fadeIn">
            <div class="flex items-center justify-between mb-5">
                <h5 class="text-lg font-semibold dark:text-white-light" id="vacationModalTitle">Add New Vacation Period</h5>
                <button type="button" onclick="closeVacationModal()" class="text-white-dark hover:text-dark">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="vacationForm" method="POST">
                @csrf
                <input type="hidden" id="vacation_method" name="_method" value="POST">
                <input type="hidden" id="vacation_id" name="vacation_id" value="">

                <div class="space-y-5">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span id="vacationModalInfo">This will add a new vacation period without changing the employee's current status.</span>
                        </p>
                    </div>

                    <div>
                        <label for="vacation_start_date" class="font-semibold">Start Date <span class="text-danger">*</span></label>
                        <input id="vacation_start_date"
                               type="date"
                               name="start_date"
                               class="form-input mt-1"
                               required>
                    </div>

                    <div>
                        <label for="vacation_end_date" class="font-semibold">End Date <span class="text-danger">*</span></label>
                        <input id="vacation_end_date"
                               type="date"
                               name="end_date"
                               class="form-input mt-1"
                               required>
                    </div>

                    <div>
                        <label for="vacation_remarks" class="font-semibold">Remarks</label>
                        <textarea id="vacation_remarks"
                                  name="remarks"
                                  rows="3"
                                  class="form-textarea mt-1"
                                  placeholder="Enter vacation details or reason (optional)"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-white-light dark:border-[#1b2e4b]">
                        <button type="button" onclick="closeVacationModal()" class="btn btn-outline-secondary">Cancel</button>
                        <button type="submit" id="vacationSubmitBtn" class="btn btn-success">
                            <span id="vacationBtnText">Add Vacation</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const employeeId = {{ $employee->id }};

    // Open Add Vacation Modal
    function openAddVacationModal() {
        const modal = document.getElementById('vacationModal');
        const form = document.getElementById('vacationForm');
        const title = document.getElementById('vacationModalTitle');
        const info = document.getElementById('vacationModalInfo');
        const btnText = document.getElementById('vacationBtnText');
        const submitBtn = document.getElementById('vacationSubmitBtn');

        // Reset form
        form.reset();
        document.getElementById('vacation_method').value = 'POST';
        document.getElementById('vacation_id').value = '';

        // Set form action for create
        form.action = `/employees/${employeeId}/vacations`;

        // Update modal text
        title.textContent = 'Add New Vacation Period';
        info.textContent = 'This will add a new vacation period without changing the employee\'s current status.';
        btnText.textContent = 'Add Vacation';
        submitBtn.className = 'btn btn-success';

        // Remove date restrictions to allow past dates
        const startDateInput = document.getElementById('vacation_start_date');
        const endDateInput = document.getElementById('vacation_end_date');

        startDateInput.removeAttribute('min');
        endDateInput.removeAttribute('min');

        // Clear end date value
        endDateInput.value = '';

        // Show modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Open Edit Vacation Modal
    function openEditVacationModal(vacationId, startDate, endDate, remarks) {
        const modal = document.getElementById('vacationModal');
        const form = document.getElementById('vacationForm');
        const title = document.getElementById('vacationModalTitle');
        const info = document.getElementById('vacationModalInfo');
        const btnText = document.getElementById('vacationBtnText');
        const submitBtn = document.getElementById('vacationSubmitBtn');

        // Set form values
        document.getElementById('vacation_id').value = vacationId;
        document.getElementById('vacation_start_date').value = startDate;
        document.getElementById('vacation_end_date').value = endDate;
        document.getElementById('vacation_remarks').value = remarks || '';
        document.getElementById('vacation_method').value = 'PUT';

        // Set form action for update
        form.action = `/employees/${employeeId}/vacations/${vacationId}`;

        // Update modal text
        title.textContent = 'Edit Vacation Period';
        info.textContent = 'You can modify the vacation dates and remarks here.';
        btnText.textContent = 'Update Vacation';
        submitBtn.className = 'btn btn-primary';

        // Remove date restrictions to allow past dates
        const startDateInput = document.getElementById('vacation_start_date');
        const endDateInput = document.getElementById('vacation_end_date');

        startDateInput.removeAttribute('min');
        endDateInput.removeAttribute('min');

        // Set end date min to start date
        endDateInput.setAttribute('min', startDate);

        // Show modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Close Vacation Modal
    function closeVacationModal() {
        const modal = document.getElementById('vacationModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        document.getElementById('vacationForm').reset();

        // Clear min attributes
        document.getElementById('vacation_start_date').removeAttribute('min');
        document.getElementById('vacation_end_date').removeAttribute('min');
    }

    // Close modal on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeVacationModal();
        }
    });

    // Close modal on backdrop click
    document.getElementById('vacationModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeVacationModal();
        }
    });

    // IMPORTANT: Update end date min when start date changes
    document.getElementById('vacation_start_date')?.addEventListener('change', function() {
        const endDateInput = document.getElementById('vacation_end_date');
        const startDate = this.value;

        if (startDate) {
            // Set end date minimum to start date
            endDateInput.setAttribute('min', startDate);

            // If end date is before start date, update it to match start date
            if (endDateInput.value && new Date(endDateInput.value) < new Date(startDate)) {
                endDateInput.value = startDate;
            }

            // If end date is empty, set it to start date
            if (!endDateInput.value) {
                endDateInput.value = startDate;
            }
        } else {
            // If start date is cleared, remove min from end date
            endDateInput.removeAttribute('min');
        }
    });

    // IMPORTANT: Ensure end date is always >= start date on input
    document.getElementById('vacation_end_date')?.addEventListener('change', function() {
        const startDateInput = document.getElementById('vacation_start_date');
        const endDate = this.value;
        const startDate = startDateInput.value;

        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
            // If end date is before start date, reset to start date
            this.value = startDate;

            Swal.fire({
                icon: 'warning',
                title: 'Invalid Date',
                text: 'End date cannot be before start date. It has been set to match the start date.',
                confirmButtonColor: '#d33',
                timer: 3000
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        // Handle vacation delete
        document.querySelectorAll('.vacation-delete-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This vacation record will be permanently deleted.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const res = await fetch(form.action, {
                                method: 'POST',
                                body: new FormData(form),
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                                }
                            });

                            const data = await res.json();

                            if (res.ok && data.success) {
                                await Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: data.message || 'Vacation deleted successfully.',
                                    confirmButtonColor: '#3085d6',
                                    timer: 2000
                                });
                                window.location.reload();
                            } else {
                                throw new Error(data.message || 'Delete failed');
                            }
                        } catch (err) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: err.message || 'Failed to delete vacation.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    }
                });
            });
        });

        // Handle vacation form submit (Add/Edit)
        const vacationForm = document.getElementById('vacationForm');
        const vacationSubmitBtn = document.getElementById('vacationSubmitBtn');

        if (vacationForm && vacationSubmitBtn) {
            vacationForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Validate dates
                const startDate = document.getElementById('vacation_start_date').value;
                const endDate = document.getElementById('vacation_end_date').value;

                if (!startDate || !endDate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Dates',
                        text: 'Please enter both start and end dates.',
                        confirmButtonColor: '#d33'
                    });
                    return;
                }

                if (new Date(startDate) > new Date(endDate)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Dates',
                        text: 'Start date must be before or equal to end date.',
                        confirmButtonColor: '#d33'
                    });
                    return;
                }

                const originalText = vacationSubmitBtn.innerHTML;
                vacationSubmitBtn.disabled = true;
                vacationSubmitBtn.innerHTML = '<span class="animate-spin inline-block mr-2">⟳</span>Saving...';

                try {
                    const formData = new FormData(vacationForm);
                    const isEdit = document.getElementById('vacation_method').value === 'PUT';

                    const res = await fetch(vacationForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                        }
                    });

                    const data = await res.json();

                    if (res.ok && data.success) {
                        closeVacationModal();
                        await Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message || (isEdit ? 'Vacation updated successfully!' : 'Vacation added successfully!'),
                            confirmButtonColor: '#3085d6',
                            timer: 2000
                        });
                        window.location.reload();
                    } else {
                        let errorMsg = data.message || 'Operation failed.';

                        if (data.errors) {
                            const errorList = [];
                            for (const [field, messages] of Object.entries(data.errors)) {
                                if (Array.isArray(messages)) {
                                    errorList.push(...messages);
                                } else {
                                    errorList.push(messages);
                                }
                            }
                            if (errorList.length > 0) {
                                errorMsg = errorList.join('\n');
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            html: errorMsg.replace(/\n/g, '<br>'),
                            confirmButtonColor: '#d33'
                        });
                    }
                } catch (error) {
                    console.error('Vacation operation error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.message || 'An unexpected error occurred.',
                        confirmButtonColor: '#d33'
                    });
                } finally {
                    vacationSubmitBtn.disabled = false;
                    vacationSubmitBtn.innerHTML = originalText;
                }
            });
        }
    });
</script>
@endpush


