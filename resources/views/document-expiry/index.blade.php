{{-- resources/views/document-expiry/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Document Expiry Alerts')

@section('content')
<div x-data="documentExpiryFilters">
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Document Expiry Alerts</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-semibold dark:text-white-light">Document Expiry Alerts</h2>
        </div>

        <!-- Status Summary Cards - 3 Per Row -->
        <div class="mb-5 grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Total Alerts Card -->
            <div @click="setStatusFilter('all')" class="panel cursor-pointer hover:shadow-lg transition-all" :class="filters.status === 'all' ? 'ring-2 ring-primary' : ''">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-primary" x-text="totalStatusCounts.expired + totalStatusCounts.critical + totalStatusCounts.warning + totalStatusCounts.notice"></p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Total Alerts</h5>
                    </div>
                    <div class="rounded-full bg-primary/10 p-3">
                        <svg class="h-8 w-8 text-primary" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                            <polyline points="13 2 13 9 20 9"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Expired Card -->
            <div @click="setStatusFilter('expired')" class="panel cursor-pointer hover:shadow-lg transition-all" :class="filters.status === 'expired' ? 'ring-2 ring-danger' : ''">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-danger" x-text="totalStatusCounts.expired"></p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Expired</h5>
                    </div>
                    <div class="rounded-full bg-danger/10 p-3">
                        <svg class="h-8 w-8 text-danger" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M14.5 9.50002L9.5 14.5M9.49998 9.5L14.5 14.5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Critical Card -->
            <div @click="setStatusFilter('critical')" class="panel cursor-pointer hover:shadow-lg transition-all" :class="filters.status === 'critical' ? 'ring-2 ring-danger' : ''">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-danger" x-text="totalStatusCounts.critical"></p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Critical (≤30)</h5>
                    </div>
                    <div class="rounded-full bg-danger/10 p-3">
                        <svg class="h-8 w-8 text-danger" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L2 20h20L12 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Warning Card -->
            <div @click="setStatusFilter('warning')" class="panel cursor-pointer hover:shadow-lg transition-all" :class="filters.status === 'warning' ? 'ring-2 ring-warning' : ''">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-warning" x-text="totalStatusCounts.warning"></p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Warning (31-60)</h5>
                    </div>
                    <div class="rounded-full bg-warning/10 p-3">
                        <svg class="h-8 w-8 text-warning" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 7V13M12 17H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Notice Card -->
            <div @click="setStatusFilter('notice')" class="panel cursor-pointer hover:shadow-lg transition-all" :class="filters.status === 'notice' ? 'ring-2 ring-info' : ''">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-bold text-info" x-text="totalStatusCounts.notice"></p>
                        <h5 class="text-sm font-semibold text-[#506690] mt-1">Notice (61-90)</h5>
                    </div>
                    <div class="rounded-full bg-info/10 p-3">
                        <svg class="h-8 w-8 text-info" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8V12M12 16H12.01" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Panel -->
        <div class="mb-5 panel">
            <div class="mb-4 flex items-center justify-between">
                <h6 class="font-semibold">Filters <span class="text-xs text-gray-500" x-show="filteredCount < totalCount">(Showing <span x-text="filteredCount"></span> of <span x-text="totalCount"></span>)</span></h6>
                <button @click="resetFilters()" class="text-xs text-primary hover:underline">Reset Filters</button>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <!-- Search Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Search Employee</label>
                    <input
                        type="text"
                        class="form-input"
                        placeholder="Name or staff number..."
                        x-model="filters.search"
                        @input="debounceFilter"
                    />
                </div>

                <!-- Document Type Filter - ALL 16 DOCUMENTS -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Document Type</label>
                    <select class="form-select" x-model="filters.document" @change="applyFilters">
                        <option value="all">All Documents</option>

                        <optgroup label="Personal Documents">
                            <option value="passport_expiry_date">Passport</option>
                            <option value="visa_expiry_date">Visa</option>
                            <option value="visit_expiry_date">Visit Permit</option>
                            <option value="eid_expiry_date">EID</option>
                            <option value="health_insurance_expiry_date">Health Insurance</option>
                            <option value="driving_license_expiry_date">Driving License</option>
                        </optgroup>

                        <optgroup label="Company & Insurance">
                            <option value="iloe_insurance_expiry_date">ILOE Insurance</option>
                            <option value="vtnx_trade_license_renewal_date">VTNX Trade License</option>
                            <option value="po_box_renewal_date">PO Box</option>
                            <option value="soe_card_renewal_date">SOE Card</option>
                            <option value="dcd_card_renewal_date">DCD Card</option>
                            <option value="voltronix_est_card_renewal_date">Voltronix EST Card</option>
                            <option value="warehouse_ejari_renewal_date">Warehouse EJARI</option>
                            <option value="camp_ejari_renewal_date">Camp EJARI</option>
                            <option value="workman_insurance_expiry_date">Workman Insurance</option>
                            <option value="etisalat_contract_expiry_date">Etisalat Contract</option>
                        </optgroup>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="mb-2 block text-xs font-semibold">Status</label>
                    <select class="form-select" x-model="filters.status" @change="applyFilters">
                        <option value="all">All Status</option>
                        <option value="expired">Expired</option>
                        <option value="critical">Critical (≤30 days)</option>
                        <option value="warning">Warning (31-60 days)</option>
                        <option value="notice">Notice (61-90 days)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <template x-if="isLoading">
            <div class="panel text-center py-8">
                <svg class="animate-spin h-8 w-8 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-3 text-gray-600 dark:text-gray-400">Loading alerts...</p>
            </div>
        </template>

        <!-- Alerts Table -->
        <template x-if="!isLoading">
            <div class="panel overflow-hidden border-0 p-0">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Document Type</th>
                                <th>Expiry Date</th>
                                <th>Days Remaining</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="alerts.length > 0">
                                <template x-for="alert in alerts" :key="alert.employee.id + '-' + alert.document_field">
                                    <tr>
                                        <td>
                                            <div>
                                                <p class="font-semibold" x-text="alert.employee.employee_name"></p>
                                                <p class="text-xs text-white-dark" x-text="alert.employee.staff_number"></p>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <svg class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M13 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V9L13 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <path d="M13 2V9H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span class="font-semibold" x-text="alert.document_name"></span>
                                            </div>
                                        </td>
                                        <td class="font-semibold" x-text="formatDate(alert.expiry_date)"></td>
                                        <td>
                                            <span :class="alert.days_until_expiry < 0 ? 'font-semibold text-danger' : 'font-semibold text-gray-700 dark:text-gray-300'" x-text="formatDaysRemaining(alert.days_until_expiry)"></span>
                                        </td>
                                        <td>
                                            <span class="badge" :class="'bg-' + alert.status_class" x-text="alert.status_label"></span>
                                        </td>
                                        <td class="text-center">
                                            <a :href="`/employees/${alert.employee.id}`" class="btn btn-sm btn-outline-primary" title="View Employee">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                                                </svg>
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </template>
                            <template x-if="alerts.length === 0">
                                <tr>
                                    <td colspan="6" class="text-center py-12 text-white-dark">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-16 w-16 text-success mb-4 opacity-50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                                                <path d="M8 12L10.5 14.5L16 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            <p class="font-semibold text-lg">No document expiry alerts found</p>
                                            <p class="text-sm text-white-dark">All employee documents are up to date</p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <template x-if="alerts.length > 0 && totalPages > 1">
                    <div class="flex items-center justify-between border-t border-white-light px-6 py-4 dark:border-[#1b2e4b]">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span>
                        </p>
                        <div class="flex gap-2 items-center">
                            <!-- Previous Button -->
                            <button
                                @click="goToPage(currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="btn btn-sm btn-outline-secondary flex items-center gap-1"
                                :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                Previous
                            </button>

                            <!-- Page Numbers -->
                            <div class="flex gap-1">
                                <template x-for="page in getPaginationRange()" :key="page">
                                    <button
                                        @click="goToPage(page)"
                                        class="btn btn-sm h-9 w-9 p-0"
                                        :class="currentPage === page
                                            ? 'btn-primary bg-primary text-white font-bold'
                                            : 'btn-outline-secondary text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'"
                                        x-text="page"
                                    ></button>
                                </template>
                            </div>

                            <!-- Next Button -->
                            <button
                                @click="goToPage(currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="btn btn-sm btn-outline-secondary flex items-center gap-1"
                                :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                            >
                                Next
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('documentExpiryFilters', () => ({
            alerts: @json($alerts),
            totalStatusCounts: {
                expired: {{ $totalStatusCounts['expired'] }},
                critical: {{ $totalStatusCounts['critical'] }},
                warning: {{ $totalStatusCounts['warning'] }},
                notice: {{ $totalStatusCounts['notice'] }}
            },
            isLoading: false,
            filters: {
                status: '{{ $statusFilter }}',
                document: '{{ $documentFilter }}',
                search: '{{ $searchQuery }}'
            },
            currentPage: {{ $page }},
            totalPages: {{ $pages }},
            filteredCount: {{ $total }},
            totalCount: function() {
                return this.totalStatusCounts.expired + this.totalStatusCounts.critical + this.totalStatusCounts.warning + this.totalStatusCounts.notice;
            },
            filterTimeout: null,

            debounceFilter() {
                clearTimeout(this.filterTimeout);
                this.filterTimeout = setTimeout(() => {
                    this.currentPage = 1;
                    this.applyFilters();
                }, 500);
            },

            setStatusFilter(status) {
                this.filters.status = status;
                this.currentPage = 1;
                this.applyFilters();
            },

            async applyFilters() {
                this.isLoading = true;

                const params = new URLSearchParams();
                params.append('page', this.currentPage);
                if (this.filters.status && this.filters.status !== 'all') params.append('status', this.filters.status);
                if (this.filters.document && this.filters.document !== 'all') params.append('document', this.filters.document);
                if (this.filters.search) params.append('search', this.filters.search);

                try {
                    const response = await fetch(`{{ route('document-expiry.index') }}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();
                    this.alerts = data.alerts;
                    this.currentPage = data.page;
                    this.totalPages = data.pages;
                    this.filteredCount = data.filteredCount;
                } catch (error) {
                    console.error('Error loading alerts:', error);
                } finally {
                    this.isLoading = false;
                }
            },

            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                    this.applyFilters();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },

            getPaginationRange() {
                const range = [];
                const maxVisible = 7;
                let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
                let end = Math.min(this.totalPages, start + maxVisible - 1);

                if (end - start < maxVisible - 1) {
                    start = Math.max(1, end - maxVisible + 1);
                }

                for (let i = start; i <= end; i++) {
                    range.push(i);
                }

                return range;
            },

            resetFilters() {
                this.filters = {
                    status: 'all',
                    document: 'all',
                    search: ''
                };
                this.currentPage = 1;
                this.applyFilters();
            },

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            },

            formatDaysRemaining(days) {
                if (days < 0) {
                    return `Expired ${Math.abs(days)} days ago`;
                }
                return `${days} days`;
            }
        }));
    });
</script>
@endpush
@endsection
