<?php

use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaceholderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/two-factor/challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor/challenge', [TwoFactorChallengeController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->name('two-factor.verify');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::middleware('role:admin')->group(function () {
        Route::get('/students', [PlaceholderController::class, 'admin'])
            ->defaults('section', 'students')
            ->name('students.index');
        Route::get('/teachers/slip', [TeacherController::class, 'slip'])->name('teachers.slip');
        Route::post('/teachers/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('teachers.reset-password');
        Route::post('/teachers/{teacher}/deactivate', [TeacherController::class, 'deactivate'])->name('teachers.deactivate');
        Route::post('/teachers/{teacher}/reactivate', [TeacherController::class, 'reactivate'])->name('teachers.reactivate');
        Route::resource('/teachers', TeacherController::class);
        Route::post('/classes/{class}/archive', [SchoolClassController::class, 'archive'])->name('classes.archive');
        Route::put('/classes/{class}/class-teacher', [SchoolClassController::class, 'updateClassTeacher'])->name('classes.class-teacher.update');
        Route::post('/classes/{class}/subjects', [SchoolClassController::class, 'storeSubject'])->name('classes.subjects.store');
        Route::put('/classes/{class}/subjects/{classSubject}', [SchoolClassController::class, 'updateSubject'])->name('classes.subjects.update');
        Route::post('/classes/{class}/move-preview', [SchoolClassController::class, 'bulkMovePreview'])->name('classes.move.preview');
        Route::post('/classes/{class}/move', [SchoolClassController::class, 'bulkMove'])->name('classes.move');
        Route::resource('/classes', SchoolClassController::class)->except('destroy');
        Route::resource('/subjects', SubjectController::class)->except('show');
        Route::get('/attendance', [PlaceholderController::class, 'admin'])
            ->defaults('section', 'attendance')
            ->name('attendance.index');
        Route::get('/fees', [PlaceholderController::class, 'admin'])
            ->defaults('section', 'fees')
            ->name('fees.index');
        Route::get('/reports', [PlaceholderController::class, 'admin'])
            ->defaults('section', 'reports')
            ->name('reports.index');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
        Route::get('/security/two-factor', [SecurityController::class, 'setup'])->name('security.two-factor.setup');
        Route::post('/security/two-factor', [SecurityController::class, 'enable'])->name('security.two-factor.enable');
        Route::post('/security/two-factor/disable', [SecurityController::class, 'disable'])->name('security.two-factor.disable');
    });

    Route::middleware('role:teacher')->group(function () {
        Route::get('/my-classes', [PlaceholderController::class, 'teacher'])
            ->defaults('section', 'my-classes')
            ->name('teacher.classes');
        Route::get('/teacher/attendance', [PlaceholderController::class, 'teacher'])
            ->defaults('section', 'attendance')
            ->name('teacher.attendance');
    });
});
