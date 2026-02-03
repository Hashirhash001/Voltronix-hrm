@extends('layouts.app')

@section('title', 'Holiday Calendar')

@section('content')
<div id="holiday-manager" x-data="holidayCalendar()">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-2xl font-semibold dark:text-white-light">Holiday Calendar</h2>
            <ul class="flex space-x-2 rtl:space-x-reverse mt-2">
                <li><a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a></li>
                <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Holidays</span></li>
            </ul>
        </div>
        <div class="flex gap-2">
            <button @click="openAddModal()" class="btn btn-primary">
                <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Holiday
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="panel">
            <div class="flex items-center">
                <div class="shrink-0 rounded-full bg-danger/10 p-3 text-danger ring-2 ring-danger/30">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ltr:ml-3 rtl:mr-3">
                    <p class="text-xl font-bold text-danger" x-text="stats.total">0</p>
                    <h5 class="text-xs text-[#506690]">Total Holidays</h5>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="flex items-center">
                <div class="shrink-0 rounded-full bg-success/10 p-3 text-success ring-2 ring-success/30">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ltr:ml-3 rtl:mr-3">
                    <p class="text-xl font-bold text-success" x-text="stats.upcoming">0</p>
                    <h5 class="text-xs text-[#506690]">Upcoming</h5>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="flex items-center">
                <div class="shrink-0 rounded-full bg-warning/10 p-3 text-warning ring-2 ring-warning/30">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                </div>
                <div class="ltr:ml-3 rtl:mr-3">
                    <p class="text-xl font-bold text-warning" x-text="stats.public">0</p>
                    <h5 class="text-xs text-[#506690]">Public Holidays</h5>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="flex items-center">
                <div class="shrink-0 rounded-full bg-info/10 p-3 text-info ring-2 ring-info/30">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ltr:ml-3 rtl:mr-3">
                    <p class="text-xl font-bold text-info" x-text="stats.entitySpecific">0</p>
                    <h5 class="text-xs text-[#506690]">Entity Specific</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- View Toggle & Navigation -->
    <div class="panel mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <!-- Month Navigation -->
            <div class="flex items-center gap-3">
                <button @click="previousMonth()" class="btn btn-outline-primary btn-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h3 class="text-lg font-semibold min-w-[200px] text-center" x-text="currentMonthYear">Loading...</h3>
                <button @click="nextMonth()" class="btn btn-outline-primary btn-sm">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <button @click="goToToday()" class="btn btn-outline-secondary btn-sm">Today</button>
            </div>

            <!-- View Toggles -->
            <div class="flex items-center gap-2">
                <button @click="currentView = 'calendar'"
                        :class="currentView === 'calendar' ? 'btn-primary' : 'btn-outline-primary'"
                        class="btn btn-sm">
                    <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2"/>
                        <line x1="3" y1="9" x2="21" y2="9" stroke-width="2"/>
                    </svg>
                    Calendar
                </button>
                <button @click="currentView = 'list'"
                        :class="currentView === 'list' ? 'btn-primary' : 'btn-outline-primary'"
                        class="btn btn-sm">
                    <svg class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <line x1="8" y1="6" x2="21" y2="6" stroke-width="2" stroke-linecap="round"/>
                        <line x1="8" y1="12" x2="21" y2="12" stroke-width="2" stroke-linecap="round"/>
                        <line x1="8" y1="18" x2="21" y2="18" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="4" cy="6" r="1" fill="currentColor"/>
                        <circle cx="4" cy="12" r="1" fill="currentColor"/>
                        <circle cx="4" cy="18" r="1" fill="currentColor"/>
                    </svg>
                    List
                </button>
            </div>

            <!-- Entity Filter -->
            <div class="w-64">
                <select x-model="selectedEntity" @change="loadHolidays()" class="form-select">
                    <option value="">All Entities</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Calendar View -->
    <div x-show="currentView === 'calendar'" class="panel" x-cloak>
        <div class="calendar-container">
            <!-- Day Headers -->
            <div class="calendar-header">
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid">
                <template x-for="(day, index) in calendarDays" :key="index">
                    <div :class="{
                        'calendar-day': true,
                        'other-month': !day.isCurrentMonth,
                        'today': day.isToday,
                        'has-holiday': day.holidays && day.holidays.length > 0
                    }" @click="day.isCurrentMonth && openDayModal(day)">
                        <!-- Date Header -->
                        <div class="date-header">
                            <span class="date-number" x-text="day.date"></span>
                            <div class="flex items-center gap-1">
                                <!-- ✅ Holiday Indicator -->
                                <span x-show="day.holidays && day.holidays.length > 0"
                                    class="holiday-dot"
                                    :title="day.holidays.length + ' holiday(s)'">
                                </span>
                                <!-- Sunday Badge -->
                                <span x-show="day.dayOfWeek === 0 && day.isCurrentMonth"
                                    class="badge bg-secondary text-[10px] px-1">Sun</span>
                            </div>
                        </div>

                        <!-- Holidays -->
                        <div class="holidays-list">
                            <template x-for="holiday in day.holidays" :key="holiday.id">
                                <div @click.stop="openEditModal(holiday)"
                                    :class="{
                                        'holiday-badge': true,
                                        'holiday-public': holiday.type === 'public',
                                        'holiday-optional': holiday.type === 'optional',
                                        'holiday-entity': holiday.type === 'entity_specific'
                                    }"
                                    :title="holiday.holiday_name + (holiday.entity ? ' (' + holiday.entity.entity_name + ')' : ' (All Entities)')">
                                    <span x-show="!holiday.entity_id" class="mr-1">🌍</span>
                                    <span x-text="holiday.holiday_name" class="truncate"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>

    <!-- List View -->
    <div x-show="currentView === 'list'" class="panel" x-cloak>
        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Holiday Name</th>
                        <th>Type</th>
                        <th>Entity</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="holiday in sortedHolidays" :key="holiday.id">
                        <tr class="bg-info/10">
                            <td>
                                <span class="font-semibold" x-text="formatDate(holiday.holiday_date)"></span>
                            </td>
                            <td>
                                <span x-text="getDayName(holiday.holiday_date)"></span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <span x-show="!holiday.entity_id" class="text-lg" title="Applies to all entities">🌍</span>
                                    <span class="font-semibold" x-text="holiday.holiday_name"></span>
                                </div>
                            </td>
                            <td>
                                <span :class="{
                                    'badge bg-danger': holiday.type === 'public',
                                    'badge bg-warning': holiday.type === 'optional',
                                    'badge bg-info': holiday.type === 'entity_specific'
                                }" x-text="holiday.type.replace('_', ' ').toUpperCase()"></span>
                            </td>
                            <td>
                                <span x-text="holiday.entity?.entity_name || 'All Entities'"
                                      :class="!holiday.entity_id ? 'font-semibold text-primary' : ''"></span>
                            </td>
                            <td>
                                <span class="text-white-dark text-sm" x-text="holiday.description || '-'"></span>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <button @click="openEditModal(holiday)" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button @click="deleteHoliday(holiday.id)" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="holidays.length === 0">
                        <td colspan="7" class="text-center py-8 text-white-dark">
                            <div class="flex flex-col items-center">
                                <svg class="h-12 w-12 mb-2 text-white-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p>No holidays found for this period</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-[999] overflow-y-auto bg-[black]/60 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="closeModal()"
             class="panel w-full max-w-3xl mx-auto my-8 overflow-hidden rounded-lg shadow-2xl"
             style="max-height: 90vh; overflow-y: auto;">

            <!-- Modal Header -->
            <div class="flex items-center justify-between bg-white dark:bg-[#0e1726] border-b border-white-light dark:border-[#1b2e4b] px-6 py-4 sticky top-0 z-10">
                <h3 class="text-xl font-semibold dark:text-white-light" x-text="editingHoliday ? 'Edit Holiday' : 'Add Holiday'"></h3>
                <button @click="closeModal()"
                        type="button"
                        class="text-white-dark hover:text-dark dark:hover:text-white-light transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <form @submit.prevent="saveHoliday()">
                <div class="p-6 space-y-5">

                    <!-- Holiday Name -->
                    <div>
                        <label class="block text-sm font-semibold mb-2 dark:text-white-light">
                            Holiday Name <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               x-model="form.holiday_name"
                               class="form-input"
                               placeholder="e.g., Eid Al-Fitr, Christmas"
                               required>
                    </div>

                    <!-- Date and Type Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2 dark:text-white-light">
                                Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   x-model="form.holiday_date"
                                   class="form-input"
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-2 dark:text-white-light">
                                Type <span class="text-danger">*</span>
                            </label>
                            <select x-model="form.type" class="form-select" required>
                                <option value="public">Public Holiday</option>
                                <option value="optional">Optional Holiday</option>
                                <option value="entity_specific">Entity Specific</option>
                            </select>
                        </div>
                    </div>

                    <!-- Apply to All Entities Checkbox -->
                    {{-- <div x-show="!editingHoliday" class="bg-primary/5 dark:bg-primary/10 p-4 rounded-lg border border-primary/20">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox"
                                   x-model="form.apply_to_all"
                                   class="form-checkbox mt-1 text-primary">
                            <div class="ltr:ml-3 rtl:mr-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🌍</span>
                                    <span class="font-semibold dark:text-white-light">Apply to all entities</span>
                                </div>
                                <p class="text-xs text-white-dark mt-1">
                                    When enabled, this holiday will be created for all existing entities
                                </p>
                            </div>
                        </label>
                    </div> --}}

                    <!-- Entity Selection (conditionally shown) -->
                    <div x-show="form.type === 'entity_specific' && !form.apply_to_all">
                        <label class="block text-sm font-semibold mb-2 dark:text-white-light">
                            Entity <span class="text-danger">*</span>
                        </label>
                        <select x-model="form.entity_id"
                                class="form-select"
                                :required="form.type === 'entity_specific' && !form.apply_to_all">
                            <option value="">Select Entity</option>
                            @foreach($entities as $entity)
                                <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold mb-2 dark:text-white-light">Description</label>
                        <textarea x-model="form.description"
                                  rows="3"
                                  class="form-textarea"
                                  placeholder="Additional notes about this holiday..."></textarea>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-3 bg-gray-50 dark:bg-[#1b2e4b] border-t border-white-light dark:border-[#191e3a] px-6 py-4 sticky bottom-0">
                    <button type="button"
                            @click="closeModal()"
                            class="btn btn-outline-secondary">
                        Cancel
                    </button>
                    <button type="submit"
                            class="btn btn-primary">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="editingHoliday ? 'Update Holiday' : 'Create Holiday'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
function holidayCalendar() {
    return {
        currentView: 'calendar',
        currentDate: new Date(),
        holidays: [],
        selectedEntity: '',
        showModal: false,
        editingHoliday: null,
        form: {
            holiday_name: '',
            holiday_date: '',
            type: 'public',
            entity_id: '',
            description: '',
            apply_to_all: false
        },
        stats: {
            total: 0,
            upcoming: 0,
            public: 0,
            entitySpecific: 0
        },

        init() {
            this.loadHolidays();
        },

        get currentMonthYear() {
            return this.currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        },

        get calendarDays() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const prevLastDay = new Date(year, month, 0);

            const firstDayOfWeek = firstDay.getDay();
            const totalDays = lastDay.getDate();
            const prevMonthDays = prevLastDay.getDate();

            let days = [];

            // Previous month days
            for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                const date = prevMonthDays - i;
                days.push(this.createDayObject(date, false, new Date(year, month - 1, date)));
            }

            // Current month days
            for (let i = 1; i <= totalDays; i++) {
                days.push(this.createDayObject(i, true, new Date(year, month, i)));
            }

            // Next month days
            const remainingDays = 42 - days.length;
            for (let i = 1; i <= remainingDays; i++) {
                days.push(this.createDayObject(i, false, new Date(year, month + 1, i)));
            }

            return days;
        },

        get sortedHolidays() {
            return [...this.holidays].sort((a, b) => new Date(a.holiday_date) - new Date(b.holiday_date));
        },

        createDayObject(date, isCurrentMonth, fullDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const isToday = fullDate.toDateString() === today.toDateString();

            // at date as YYYY-MM-DD in local timezone
            const year = fullDate.getFullYear();
            const month = String(fullDate.getMonth() + 1).padStart(2, '0');
            const day = String(fullDate.getDate()).padStart(2, '0');
            const dateString = `${year}-${month}-${day}`;

            return {
                date,
                isCurrentMonth,
                isToday,
                fullDate,
                dayOfWeek: fullDate.getDay(),
                holidays: this.holidays.filter(h => h.holiday_date === dateString)
            };
        },

        loadHolidays() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth() + 1;
            const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
            const lastDay = new Date(year, month, 0).getDate();
            const endDate = `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;

            fetch(`/holidays/range?start_date=${startDate}&end_date=${endDate}&entity_id=${this.selectedEntity}`)
                .then(res => res.json())
                .then(data => {
                    this.holidays = data.holidays || [];
                    this.updateStats();
                })
                .catch(err => {
                    console.error('Failed to load holidays:', err);
                    this.showAlert('error', 'Failed to load holidays');
                });
        },

        updateStats() {
            //  Compare dates as strings
            const today = new Date();
            const todayString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

            this.stats.total = this.holidays.length;
            this.stats.upcoming = this.holidays.filter(h => h.holiday_date >= todayString).length;
            this.stats.public = this.holidays.filter(h => h.type === 'public').length;
            this.stats.entitySpecific = this.holidays.filter(h => h.type === 'entity_specific').length;
        },

        previousMonth() {
            this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1);
            this.loadHolidays();
        },

        nextMonth() {
            this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1);
            this.loadHolidays();
        },

        goToToday() {
            this.currentDate = new Date();
            this.loadHolidays();
        },

        openAddModal() {
            this.editingHoliday = null;
            this.form = {
                holiday_name: '',
                holiday_date: '',
                type: 'public',
                entity_id: '',
                description: '',
                apply_to_all: false
            };
            this.showModal = true;
        },

        openDayModal(day) {
            this.editingHoliday = null;

            const year = day.fullDate.getFullYear();
            const month = String(day.fullDate.getMonth() + 1).padStart(2, '0');
            const date = String(day.fullDate.getDate()).padStart(2, '0');
            const formattedDate = `${year}-${month}-${date}`;

            this.form = {
                holiday_name: '',
                holiday_date: formattedDate,
                type: 'public',
                entity_id: '',
                description: '',
                apply_to_all: false
            };
            this.showModal = true;
        },

        openEditModal(holiday) {
            this.editingHoliday = holiday;
            this.form = {
                holiday_name: holiday.holiday_name,
                holiday_date: holiday.holiday_date,
                type: holiday.type,
                entity_id: holiday.entity_id || '',
                description: holiday.description || '',
                apply_to_all: false
            };
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.editingHoliday = null;
        },

        saveHoliday() {
            const url = this.editingHoliday
                ? `/holidays/${this.editingHoliday.id}`
                : '/holidays';
            const method = this.editingHoliday ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(this.form)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.loadHolidays();
                    this.closeModal();
                    this.showAlert(
                        'success',
                        this.editingHoliday ? 'Holiday Updated!' : 'Holiday Created!',
                        data.message
                    );
                } else {
                    this.showAlert('error', 'Error', data.message);
                }
            })
            .catch(err => {
                console.error('Save error:', err);
                this.showAlert('error', 'Failed to save holiday', 'Please try again');
            });
        },

        deleteHoliday(id) {
            Swal.fire({
                title: 'Delete Holiday?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/holidays/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            this.loadHolidays();
                            this.showAlert('success', 'Deleted!', data.message);
                        } else {
                            this.showAlert('error', 'Error', data.message);
                        }
                    })
                    .catch(err => {
                        console.error('Delete error:', err);
                        this.showAlert('error', 'Failed to delete holiday');
                    });
                }
            });
        },

        // Parse as local date
        formatDate(dateString) {
            if (!dateString) return 'Invalid Date';

            const [year, month, day] = dateString.split('-').map(Number);
            const date = new Date(year, month - 1, day);

            if (isNaN(date.getTime())) return 'Invalid Date';

            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        },

        getDayName(dateString) {
            if (!dateString) return 'Invalid Day';

            const [year, month, day] = dateString.split('-').map(Number);
            const date = new Date(year, month - 1, day);

            if (isNaN(date.getTime())) return 'Invalid Day';

            return date.toLocaleDateString('en-US', { weekday: 'long' });
        },

        showAlert(icon, title, text = '') {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonColor: '#4f46e5',
                timer: icon === 'success' ? 2000 : null,
                showConfirmButton: icon !== 'success'
            });
        }
    }
}
</script>
@endpush

@push('styles')
<style>
    /* Hide elements with x-cloak */
    [x-cloak] {
        display: none !important;
    }

    /* Calendar Container */
    .calendar-container {
        width: 100%;
    }

    /* Calendar Header (Days of Week) */
    .calendar-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
        margin-bottom: 8px;
    }

    .calendar-day-header {
        padding: 12px 8px;
        text-align: center;
        font-weight: 600;
        font-size: 0.875rem;
        background: rgba(var(--primary), 0.1);
        color: rgb(var(--primary));
        border-radius: 6px;
    }

    /* Calendar Grid */
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 4px;
    }

    /* Calendar Day Cell */
    .calendar-day {
        min-height: 120px;
        padding: 8px;
        border: 1px solid #e0e6ed;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark .calendar-day {
        background: #0e1726;
        border-color: #1b2e4b;
    }

    .calendar-day:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .dark .calendar-day:hover {
        background: #1a2942;
    }

    /* Other Month Days */
    .calendar-day.other-month {
        background: #f9fafb;
        opacity: 0.5;
        cursor: default;
    }

    .dark .calendar-day.other-month {
        background: #0a1222;
    }

    .calendar-day.other-month:hover {
        transform: none;
        box-shadow: none;
    }

    /* Today */
    .calendar-day.today {
        background: rgba(var(--primary), 0.05);
        border: 2px solid rgb(var(--primary));
        box-shadow: 0 0 0 3px rgba(var(--primary), 0.1);
    }

    /* Date Header */
    .date-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }

    .date-number {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1f2937;
    }

    .dark .date-number {
        color: #e5e7eb;
    }

    .calendar-day.other-month .date-number {
        color: #9ca3af;
    }

    .calendar-day.today .date-number {
        color: rgb(var(--primary));
        font-size: 1rem;
        font-weight: 700;
    }

    /* Holidays List */
    .holidays-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
        max-height: 80px;
        overflow-y: auto;
    }

    /* Holiday Badge */
    .holiday-badge {
        font-size: 0.75rem;
        padding: 4px 6px;
        border-radius: 4px;
        cursor: pointer;
        transition: opacity 0.2s;
        display: flex;
        align-items: center;
        white-space: nowrap;
        overflow: hidden;
    }

    .holiday-badge:hover {
        opacity: 0.8;
    }

    .holiday-public {
        background: #ef4444;
        color: white;
    }

    .holiday-optional {
        background: #f59e0b;
        color: white;
    }

    .holiday-entity {
        background: #3b82f6;
        color: white;
    }

    /* Scrollbar for holidays */
    .holidays-list::-webkit-scrollbar {
        width: 4px;
    }

    .holidays-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .holidays-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
    }

    .dark .holidays-list::-webkit-scrollbar-thumb {
        background: #475569;
    }

    /* Holiday Indicator Dot */
    .holiday-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    /* Has Holiday - Visual Enhancement */
    .calendar-day.has-holiday {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.02) 100%);
    }

    .dark .calendar-day.has-holiday {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
    }

    .calendar-day.has-holiday:hover {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
    }


    /* Modal Animation */
    .panel {
        animation: modalSlideIn 0.2s ease-out;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(-10px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .calendar-day {
            min-height: 80px;
            padding: 4px;
        }

        .calendar-day-header {
            padding: 8px 4px;
            font-size: 0.75rem;
        }

        .date-number {
            font-size: 0.75rem;
        }

        .holiday-badge {
            font-size: 0.625rem;
            padding: 2px 4px;
        }
    }
</style>
@endpush
@endsection
