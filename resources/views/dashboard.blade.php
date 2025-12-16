{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div>
    @if(session('success'))
        <div id="successToast" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="fixed top-6 right-6 z-50 animate-pulse bg-success text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor" opacity="0.5"/>
                <path d="M8 12L10.5 14.5L16 9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div>
                <p class="font-semibold">Success!</p>
                <p class="text-sm">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="javascript:;" class="text-primary hover:underline">Dashboard</a>
        </li>
    </ul>

    <div class="pt-5">
        <!-- Main Statistics Cards -->
        <div class="mb-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            <!-- Total Employees -->
            <div class="panel h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-primary">{{ $totalEmployees }}</p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Total Employees</h5>
                        <p class="text-xs text-white-dark mt-1">{{ $activeEmployees }} Active • {{ $inactiveEmployees }} Inactive</p>
                    </div>
                    <div class="rounded-full bg-primary/10 p-3 ring-2 ring-primary/30">
                        <svg class="h-8 w-8 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle opacity="0.5" cx="15" cy="6" r="3" fill="currentColor"/>
                            <ellipse opacity="0.5" cx="16" cy="17" rx="5" ry="3" fill="currentColor"/>
                            <circle cx="9.00098" cy="6" r="4" fill="currentColor"/>
                            <ellipse cx="9.00098" cy="17.001" rx="7" ry="4" fill="currentColor"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance -->
            <div class="panel h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-success">{{ $todayPresent }}</p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Present Today</h5>
                        <p class="text-xs text-white-dark mt-1">{{ $todayAbsent }} Absent • {{ $todayHalfDay }} Half Day • {{ $todayLeave }} Leave</p>
                    </div>
                    <div class="rounded-full bg-success/10 p-3 ring-2 ring-success/30">
                        <svg class="h-8 w-8 text-success" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor" opacity="0.5"/>
                            <path d="M8 12L10.5 14.5L16 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Entities & Vehicles -->
            <div class="panel h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-info">{{ $totalEntities + $totalVehicles }}</p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Total Assets</h5>
                        <p class="text-xs text-white-dark mt-1">{{ $totalEntities }} Entities • {{ $totalVehicles }} Vehicles</p>
                    </div>
                    <div class="rounded-full bg-info/10 p-3 ring-2 ring-info/30">
                        <svg class="h-8 w-8 text-info" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" fill="currentColor" opacity="0.5"/>
                            <polyline points="13 2 13 9 20 9" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Document Alerts -->
            <div class="panel h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-warning">{{ $expiringDocuments->count() }}</p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Document Alerts</h5>
                        <p class="text-xs text-white-dark mt-1">Expiring within 90 days</p>
                    </div>
                    <div class="rounded-full bg-warning/10 p-3 ring-2 ring-warning/30">
                        <svg class="h-8 w-8 text-warning" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 20h20L12 2z" fill="currentColor" opacity="0.5"/>
                            <path d="M12 9V13M12 17H12.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Attendance Performance -->
        <div class="mb-6 panel">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h5 class="text-lg font-semibold dark:text-white-light">Monthly Attendance Performance</h5>
                    <p class="text-xs text-white-dark mt-1">{{ now()->format('F Y') }} • {{ $monthlyStats['working_days'] }} working days • {{ $monthlyStats['active_employees'] }} employees</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Attendance Rate -->
                <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="text-xs font-semibold text-white-dark uppercase">Attendance Rate</h6>
                        <div class="rounded-full p-2 {{ $monthlyStats['attendance_rate'] >= 95 ? 'bg-success/10' : ($monthlyStats['attendance_rate'] >= 85 ? 'bg-warning/10' : 'bg-danger/10') }}">
                            <svg class="h-5 w-5 {{ $monthlyStats['attendance_rate'] >= 95 ? 'text-success' : ($monthlyStats['attendance_rate'] >= 85 ? 'text-warning' : 'text-danger') }}" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" opacity="0.5"/>
                                <path d="M8 12L10.5 14.5L16 9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <p class="text-3xl font-bold {{ $monthlyStats['attendance_rate'] >= 95 ? 'text-success' : ($monthlyStats['attendance_rate'] >= 85 ? 'text-warning' : 'text-danger') }}">
                            {{ $monthlyStats['attendance_rate'] }}%
                        </p>
                        <p class="text-xs text-white-dark mb-1">Target: 95%</p>
                    </div>
                    <p class="text-xs text-white-dark mt-2">{{ $monthlyStats['monthly_present'] }} / {{ $monthlyStats['expected_attendance'] }} present</p>
                </div>

                <!-- Absenteeism Rate -->
                <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="text-xs font-semibold text-white-dark uppercase">Absenteeism Rate</h6>
                        <div class="rounded-full bg-danger/10 p-2">
                            <svg class="h-5 w-5 text-danger" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="12" cy="12" r="10" opacity="0.5"/>
                                <path d="M14.5 9.5L9.5 14.5M9.5 9.5L14.5 14.5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <p class="text-3xl font-bold text-danger">{{ $monthlyStats['absenteeism_rate'] }}%</p>
                        <p class="text-xs text-white-dark mb-1">{{ $monthlyStats['monthly_absent'] }} days</p>
                    </div>
                    <p class="text-xs text-white-dark mt-2">Industry avg: 2-3%</p>
                </div>

                <!-- Planned Leave -->
                <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="text-xs font-semibold text-white-dark uppercase">Planned Leave</h6>
                        <div class="rounded-full bg-warning/10 p-2">
                            <svg class="h-5 w-5 text-warning" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 2V5M16 2V5M3.5 9.09H20.5M21 8.5V17C21 20 19.5 22 16 22H8C4.5 22 3 20 3 17V8.5C3 5.5 4.5 3.5 8 3.5H16C19.5 3.5 21 5.5 21 8.5Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <p class="text-3xl font-bold text-warning">{{ $monthlyStats['monthly_leave'] }}</p>
                        <p class="text-xs text-white-dark mb-1">days taken</p>
                    </div>
                    <p class="text-xs text-white-dark mt-2">{{ $monthlyStats['monthly_half_day'] }} half days</p>
                </div>

                <!-- Average Work Hours -->
                <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                    <div class="flex items-center justify-between mb-3">
                        <h6 class="text-xs font-semibold text-white-dark uppercase">Avg Work Hours</h6>
                        <div class="rounded-full bg-info/10 p-2">
                            <svg class="h-5 w-5 text-info" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M12 8V12L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <p class="text-3xl font-bold text-info">{{ $monthlyStats['average_hours'] }}</p>
                        <p class="text-xs text-white-dark mb-1">hrs/day</p>
                    </div>
                    <p class="text-xs text-white-dark mt-2">Standard: 8 hrs/day</p>
                </div>
            </div>

            <!-- Performance Indicator Bar -->
            <div class="mt-4 rounded-lg bg-gray-50 dark:bg-[#1b2e4b] p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-white-dark">Overall Performance</span>
                    <span class="text-xs font-semibold {{ $monthlyStats['attendance_rate'] >= 95 ? 'text-success' : ($monthlyStats['attendance_rate'] >= 85 ? 'text-warning' : 'text-danger') }}">
                        {{ $monthlyStats['attendance_rate'] >= 95 ? 'Excellent' : ($monthlyStats['attendance_rate'] >= 85 ? 'Good' : 'Needs Attention') }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                    <div class="h-2 rounded-full transition-all {{ $monthlyStats['attendance_rate'] >= 95 ? 'bg-success' : ($monthlyStats['attendance_rate'] >= 85 ? 'bg-warning' : 'bg-danger') }}"
                         style="width: {{ min($monthlyStats['attendance_rate'], 100) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Document Alerts & Recent Attendance -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Document Expiry Alerts -->
            <div class="panel h-full">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Document Expiry Alerts</h5>
                    <a href="{{ route('document-expiry.index') }}" class="btn btn-sm btn-primary gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                        View All
                    </a>
                </div>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($expiringDocuments as $alert)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-white-light dark:border-[#1b2e4b] hover:shadow-md transition-shadow">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="badge badge-outline-secondary text-xs">{{ $alert['category'] }}</span>
                                    <span class="font-semibold text-sm">{{ $alert['name'] }}</span>
                                    @if($alert['identifier'])
                                        <span class="text-xs text-white-dark">{{ $alert['identifier'] }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-white-dark">{{ $alert['document_name'] }} • {{ $alert['expiry_date']->format('d M Y') }}</p>
                            </div>
                            <div class="text-right ml-3">
                                <span class="badge bg-{{ $alert['status_class'] }} whitespace-nowrap">{{ $alert['status_label'] }}</span>
                                <p class="text-xs text-white-dark mt-1 whitespace-nowrap">
                                    @if($alert['days_until_expiry'] < 0)
                                        {{ abs($alert['days_until_expiry']) }} day{{ abs($alert['days_until_expiry']) != 1 ? 's' : '' }} ago
                                    @else
                                        {{ $alert['days_until_expiry'] }} day{{ $alert['days_until_expiry'] != 1 ? 's' : '' }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-success mb-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                <path d="M8 12L10.5 14.5L16 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <p class="text-sm text-white-dark">All documents are up to date</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="panel h-full">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Recent Attendance</h5>
                    <a href="{{ route('attendances.index') }}" class="btn btn-sm btn-outline-primary gap-2">
                        View All
                    </a>
                </div>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($recentAttendances as $attendance)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-white-light dark:border-[#1b2e4b] hover:shadow-md transition-shadow">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-sm">{{ $attendance->employee->employee_name ?? 'N/A' }}</span>
                                    <span class="text-xs text-white-dark">{{ $attendance->staff_number }}</span>
                                </div>
                                <p class="text-xs text-white-dark">
                                    {{ $attendance->attendance_date->format('d M Y') }} •
                                    {{ $attendance->getFormattedCheckInTime() }} -
                                    {{ $attendance->getFormattedCheckOutTime() }}
                                </p>
                            </div>
                            <div class="text-right ml-3">
                                <span class="badge whitespace-nowrap
                                    @if($attendance->status === 'present') bg-success
                                    @elseif($attendance->status === 'absent') bg-danger
                                    @elseif($attendance->status === 'half_day') bg-secondary
                                    @elseif($attendance->status === 'leave') bg-warning
                                    @else bg-info
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $attendance->status)) }}
                                </span>
                                @if($attendance->total_hours > 0)
                                    <p class="text-xs text-white-dark mt-1">{{ number_format($attendance->total_hours, 1) }}h</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <p class="text-sm text-white-dark">No recent attendance records</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
