<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DocumentExpiryController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\EmployeeDocumentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Employee Import/Export Routes (Must come BEFORE resource routes)
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('import', [EmployeeImportController::class, 'showImport'])->name('import');
        Route::post('preview', [EmployeeImportController::class, 'preview'])->name('preview');
        Route::post('bulk-import', [EmployeeImportController::class, 'import'])->name('bulk-import');
        Route::get('export', [EmployeeImportController::class, 'export'])->name('export');
        Route::get('download-template', [EmployeeImportController::class, 'downloadTemplate'])->name('download-template');
    });

    // Employees Resource Routes (Must come AFTER specific routes)
    Route::resource('employees', EmployeeController::class);

    // Document Expiry Alerts
    Route::get('/document-expiry-alerts', [DocumentExpiryController::class, 'index'])->name('document-expiry.index');

    // Attendance
    Route::resource('attendances', AttendanceController::class);
    Route::post('attendances/generate-today', [AttendanceController::class, 'generateToday'])
        ->name('attendances.generate-today');
    Route::patch('attendances/{attendance}/quick-update', [AttendanceController::class, 'quickUpdate'])
        ->name('attendances.quick-update');
    Route::get('attendances-report/export', [AttendanceController::class, 'export'])
        ->name('attendances.report.export');

    // Reports
    Route::get('/reports/analytics', [ReportController::class, 'analytics'])->name('reports.analytics');
    Route::get('/reports-export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/employee/{employee}', [ReportController::class, 'employeeDetail'])->name('reports.employee-detail');
    Route::get('/reports/employee-export', [ReportController::class, 'employeeExport'])->name('reports.employee-export');

    // Entities Routes
    Route::resource('entities', EntityController::class);

    // Vehicles Routes
    Route::resource('vehicles', VehicleController::class);

    // Profile Routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/update', [ProfileController::class, 'update'])->name('update');
        Route::patch('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    });
});

// php artisan storage:link
Route::get('/storage-link', function () {
    Artisan::call('storage:link');
    return redirect()->back();
});
