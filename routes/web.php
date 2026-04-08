<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminController as AdminUIController;
use App\Http\Controllers\Auth\WebAuthController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes
Route::middleware('guest')->group(function() {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Shared Protected Routes (Admin & Teacher)
Route::middleware(['auth'])->group(function() {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/teacher/reports', [DashboardController::class, 'teacherReports'])->name('teacher.reports');
    Route::post('/regenerate-qr', [DashboardController::class, 'regenerateQr'])->name('regenerate-qr');
    Route::post('/simulate-scan', [DashboardController::class, 'simulateScan'])->name('simulate-scan');
});

// Admin & Super Admin Shared UI Management
Route::middleware(['auth', 'role:admin,super_admin'])->group(function() {
    Route::get('/admin/instructors', [AdminUIController::class, 'instructors'])->name('admin.instructors');
    Route::get('/admin/teacher-accounts', [AdminUIController::class, 'teacherAccounts'])->name('admin.teacher-accounts');
    Route::get('/admin/students', [AdminUIController::class, 'students'])->name('admin.students');
    Route::get('/admin/courses', [AdminUIController::class, 'courses'])->name('admin.courses');
    Route::get('/admin/classes', [AdminUIController::class, 'classes'])->name('admin.classes');
    Route::get('/admin/subjects', [AdminUIController::class, 'subjects'])->name('admin.subjects');
    Route::get('/admin/departments', [AdminUIController::class, 'departments'])->name('admin.departments');
    Route::get('/admin/settings', [AdminUIController::class, 'settings'])->name('admin.settings');
    Route::post('/admin/settings', [AdminUIController::class, 'updateSettings'])->name('admin.settings.update');
    Route::get('/admin/settings/export', [AdminUIController::class, 'exportSummaryReport'])->name('admin.settings.export');
    
    // Global Listing Exports
    Route::get('/admin/export/instructors', [AdminUIController::class, 'exportInstructors'])->name('admin.export.instructors');
    Route::get('/admin/export/students', [AdminUIController::class, 'exportStudents'])->name('admin.export.students');
    Route::get('/admin/export/courses', [AdminUIController::class, 'exportCourses'])->name('admin.export.courses');
    Route::get('/admin/export/subjects', [AdminUIController::class, 'exportSubjects'])->name('admin.export.subjects');
    Route::get('/admin/export/departments', [AdminUIController::class, 'exportDepartments'])->name('admin.export.departments');
    Route::get('/admin/export/classes', [AdminUIController::class, 'exportClasses'])->name('admin.export.classes');

    // Telegram Bot Management
    Route::get('/admin/telegram-bots', function() { return redirect()->route('admin.settings'); });
    Route::post('/admin/telegram-bots', [\App\Http\Controllers\Admin\TelegramBotController::class, 'store'])->name('admin.telegram-bots.store');
    Route::post('/admin/telegram-bots/{id}/active', [\App\Http\Controllers\Admin\TelegramBotController::class, 'setActive'])->name('admin.telegram-bots.active');
    Route::post('/admin/telegram-bots/{id}/sync', [\App\Http\Controllers\Admin\TelegramBotController::class, 'sync'])->name('admin.telegram-bots.sync');
    Route::delete('/admin/telegram-bots/{id}', [\App\Http\Controllers\Admin\TelegramBotController::class, 'destroy'])->name('admin.telegram-bots.destroy');
    Route::post('/admin/telegram-bots/{id}/test', [\App\Http\Controllers\Admin\TelegramBotController::class, 'sendTest'])->name('admin.telegram-bots.test');
});

// Student Check-in Flow
Route::get('/scan/{session_id}', [DashboardController::class, 'studentScan'])->name('student.scan');
Route::post('/verify-attendance', [DashboardController::class, 'verifyAttendance'])->name('student.verify');