<?php

use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubjectController;
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
        Route::get('/teachers', [PlaceholderController::class, 'admin'])
            ->defaults('section', 'teachers')
            ->name('teachers.index');
        Route::get('/classes', [PlaceholderController::class, 'admin'])
            ->defaults('section', 'classes')
            ->name('classes.index');
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
