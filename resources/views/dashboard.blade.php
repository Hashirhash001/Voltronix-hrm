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

    <!-- Statistics Cards -->
    <div class="pt-5">
        <div class="mb-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            <!-- Total Employees -->
            <div class="panel h-full sm:col-span-2 xl:col-span-1">
                <div class="flex items-center">
                    <div class="shrink-0 rounded-full bg-primary/10 p-3 text-primary ring-2 ring-primary/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle opacity="0.5" cx="15" cy="6" r="3" fill="currentColor"/>
                            <ellipse opacity="0.5" cx="16" cy="17" rx="5" ry="3" fill="currentColor"/>
                            <circle cx="9.00098" cy="6" r="4" fill="currentColor"/>
                            <ellipse cx="9.00098" cy="17.001" rx="7" ry="4" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="ltr:ml-3 rtl:mr-3">
                        <p class="text-xl font-bold text-primary">{{ $totalEmployees }}</p>
                        <h5 class="text-xs text-[#506690]">Total Employees</h5>
                    </div>
                </div>
            </div>

            <!-- Active Employees -->
            <div class="panel h-full sm:col-span-2 xl:col-span-1">
                <div class="flex items-center">
                    <div class="shrink-0 rounded-full bg-success/10 p-3 text-success ring-2 ring-success/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor" opacity="0.5"/>
                            <path d="M8 12L10.5 14.5L16 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="ltr:ml-3 rtl:mr-3">
                        <p class="text-xl font-bold text-success">{{ $activeEmployees }}</p>
                        <h5 class="text-xs text-[#506690]">Active Employees</h5>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance -->
            <div class="panel h-full sm:col-span-2 xl:col-span-1">
                <div class="flex items-center">
                    <div class="shrink-0 rounded-full bg-info/10 p-3 text-info ring-2 ring-info/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12V14C22 17.7712 22 19.6569 20.8284 20.8284C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V12Z" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <div class="ltr:ml-3 rtl:mr-3">
                        <p class="text-xl font-bold text-info">{{ $todayPresent }}/{{ $todayAttendance }}</p>
                        <h5 class="text-xs text-[#506690]">Today's Attendance</h5>
                    </div>
                </div>
            </div>

            <!-- Overtime This Month -->
            <div class="panel h-full sm:col-span-2 xl:col-span-1">
                <div class="flex items-center">
                    <div class="shrink-0 rounded-full bg-warning/10 p-3 text-warning ring-2 ring-warning/30">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M12 8V12L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="ltr:ml-3 rtl:mr-3">
                        <p class="text-xl font-bold text-warning">{{ number_format($overtimeThisMonth, 1) }}</p>
                        <h5 class="text-xs text-[#506690]">Overtime Hours</h5>
                    </div>
                </div>
            </div>
        </div>

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

            <div class="space-y-4">
                @forelse($expiringDocuments->take(5) as $alert)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-white-light dark:border-[#1b2e4b]">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold">{{ $alert['employee']->employee_name }}</span>
                                <span class="badge badge-outline-primary text-xs">{{ $alert['employee']->staff_number }}</span>
                            </div>
                            <p class="text-xs text-white-dark">{{ $alert['document_name'] }} expires on {{ $alert['expiry_date']->format('d M Y') }}</p>
                        </div>
                        <div class="text-right">
                            <span class="badge bg-{{ $alert['status_class'] }}">{{ $alert['status_label'] }}</span>
                            <p class="text-xs text-white-dark mt-1">{{ abs($alert['days_until_expiry']) }} days{{ $alert['days_until_expiry'] < 0 ? ' ago' : '' }}</p>
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

        <!-- Employees with Overtime -->
        @if($topOvertimeEmployees && $topOvertimeEmployees->count() > 0)
            <div class="panel mt-6">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">
                        Employees with Overtime This Month
                        <span class="badge bg-warning ml-2">{{ $topOvertimeEmployees->count() }}</span>
                    </h5>
                </div>

                <div class="table-responsive">
                    <table class="table-striped">
                        <thead>
                            <tr>
                                <th>Staff Number</th>
                                <th>Employee Name</th>
                                <th>Designation</th>
                                <th>Overtime Hours</th>
                                <th class="text-center">Indicator</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topOvertimeEmployees as $employee)
                                <tr>
                                    <td>{{ $employee->staff_number }}</td>
                                    <td>
                                        <a href="{{ route('employees.show', $employee->id) }}" class="text-primary hover:underline">
                                            {{ $employee->employee_name }}
                                        </a>
                                    </td>
                                    <td>{{ $employee->designation }}</td>
                                    <td>
                                        <span class="font-semibold">{{ number_format($employee->total_overtime, 1) }} hrs</span>
                                    </td>
                                    <td class="text-center">
                                        @if($employee->total_overtime > 40)
                                            <span class="badge bg-danger">High</span>
                                        @elseif($employee->total_overtime > 20)
                                            <span class="badge bg-warning">Medium</span>
                                        @else
                                            <span class="badge bg-info">Low</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Attendance Analytics -->
        @if($attendanceAnalytics)
            <div class="panel mt-6">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Attendance Analytics - This Month</h5>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs text-white-dark">Present</h6>
                                <p class="text-2xl font-semibold text-success">{{ $attendanceAnalytics['present'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-full bg-success/10 p-2">
                                <svg class="h-6 w-6 text-success" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="currentColor" opacity="0.5"/>
                                    <path d="M8 12L10.5 14.5L16 9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs text-white-dark">Absent</h6>
                                <p class="text-2xl font-semibold text-danger">{{ $attendanceAnalytics['absent'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-full bg-danger/10 p-2">
                                <svg class="h-6 w-6 text-danger" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle opacity="0.5" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M14.5 9.50002L9.5 14.5M9.49998 9.5L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs text-white-dark">Leave</h6>
                                <p class="text-2xl font-semibold text-warning">{{ $attendanceAnalytics['leave'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-full bg-warning/10 p-2">
                                <svg class="h-6 w-6 text-warning" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 7V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    <circle cx="12" cy="16" r="1" fill="currentColor"/>
                                    <path opacity="0.5" d="M7.84308 3.80211C9.8718 2.6007 10.8862 2 12 2C13.1138 2 14.1282 2.6007 16.1569 3.80211L16.8431 4.20846C18.8718 5.40987 19.8862 6.01057 20.4431 7C21 7.98943 21 9.19084 21 11.5937V12.4063C21 14.8092 21 16.0106 20.4431 17C19.8862 17.9894 18.8718 18.5901 16.8431 19.7915L16.1569 20.1979C14.1282 21.3993 13.1138 22 12 22C10.8862 22 9.8718 21.3993 7.84308 20.1979L7.15692 19.7915C5.1282 18.5901 4.11384 17.9894 3.55692 17C3 16.0106 3 14.8092 3 12.4063V11.5937C3 9.19084 3 7.98943 3.55692 7C4.11384 6.01057 5.1282 5.40987 7.15692 4.20846L7.84308 3.80211Z" fill="currentColor"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b]">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs text-white-dark">Avg Hours</h6>
                                <p class="text-2xl font-semibold text-info">{{ number_format($attendanceAnalytics['average_hours'] ?? 0, 1) }}</p>
                            </div>
                            <div class="rounded-full bg-info/10 p-2">
                                <svg class="h-6 w-6 text-info" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M12 8V12L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
