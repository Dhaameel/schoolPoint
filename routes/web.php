<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\FeePaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SchoolClassController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Teacher\MyClassController;
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

    Route::get('/photos/students/{student}', [PhotoController::class, 'student'])->name('photos.students.show');
    Route::get('/photos/teachers/{teacher}', [PhotoController::class, 'teacher'])->name('photos.teachers.show');

    Route::middleware('role:admin')->group(function () {
        Route::get('/students/import', [StudentImportController::class, 'index'])->name('students.import');
        Route::get('/students/import/template', [StudentImportController::class, 'template'])->name('students.import.template');
        Route::post('/students/import/preview', [StudentImportController::class, 'preview'])->name('students.import.preview');
        Route::post('/students/import/store', [StudentImportController::class, 'import'])->name('students.import.store');
        Route::get('/students/{student}/move-class', [StudentController::class, 'moveClassForm'])->name('students.move-class');
        Route::post('/students/{student}/move-class', [StudentController::class, 'moveClass'])->name('students.move-class.update');
        Route::post('/students/{student}/status', [StudentController::class, 'changeStatus'])->name('students.status');
        Route::resource('/students', StudentController::class)->except('destroy');
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
        Route::get('/attendance', [AdminAttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/take', [AdminAttendanceController::class, 'take'])->name('attendance.take');
        Route::post('/attendance/take', [AdminAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/print', [AdminAttendanceController::class, 'print'])->name('attendance.print');
        Route::get('/attendance/csv', [AdminAttendanceController::class, 'csv'])->name('attendance.csv');
        Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
        Route::get('/fees/structures', [FeeController::class, 'structures'])->name('fees.structures.index');
        Route::get('/fees/structures/create', [FeeController::class, 'createStructure'])->name('fees.structures.create');
        Route::post('/fees/structures', [FeeController::class, 'storeStructure'])->name('fees.structures.store');
        Route::get('/fees/structures/{structure}/edit', [FeeController::class, 'editStructure'])->name('fees.structures.edit');
        Route::put('/fees/structures/{structure}', [FeeController::class, 'updateStructure'])->name('fees.structures.update');
        Route::get('/fees/bill', [FeeController::class, 'billForm'])->name('fees.bill');
        Route::post('/fees/bill', [FeeController::class, 'bill'])->name('fees.bill.store');
        Route::get('/fees/payments/{payment}/receipt', [FeePaymentController::class, 'receipt'])
            ->whereNumber('payment')
            ->name('fees.payments.receipt');
        Route::get('/fees/{fee}', [FeeController::class, 'show'])->whereNumber('fee')->name('fees.show');
        Route::post('/fees/{fee}/payments', [FeePaymentController::class, 'store'])
            ->whereNumber('fee')
            ->name('fees.payments.store');
        Route::post('/fees/{fee}/payments/{payment}/reverse', [FeePaymentController::class, 'reverse'])
            ->whereNumber('fee')
            ->whereNumber('payment')
            ->name('fees.payments.reverse');
        Route::post('/fees/{fee}/void', [FeePaymentController::class, 'void'])
            ->whereNumber('fee')
            ->name('fees.void');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/school/print', [ReportController::class, 'schoolPrint'])->name('reports.school.print');
        Route::get('/reports/school/csv', [ReportController::class, 'schoolCsv'])->name('reports.school.csv');
        Route::get('/reports/subjects', [ReportController::class, 'subjects'])->name('reports.subjects');
        Route::get('/reports/subjects/print', [ReportController::class, 'subjectPrint'])->name('reports.subjects.print');
        Route::get('/reports/subjects/csv', [ReportController::class, 'subjectCsv'])->name('reports.subjects.csv');
        Route::get('/reports/students', [ReportController::class, 'students'])->name('reports.students');
        Route::get('/reports/students/print', [ReportController::class, 'studentPrint'])->name('reports.students.print');
        Route::get('/reports/students/csv', [ReportController::class, 'studentCsv'])->name('reports.students.csv');
        Route::get('/reports/teachers', [ReportController::class, 'teachers'])->name('reports.teachers');
        Route::get('/reports/teachers/print', [ReportController::class, 'teacherPrint'])->name('reports.teachers.print');
        Route::get('/reports/teachers/csv', [ReportController::class, 'teacherCsv'])->name('reports.teachers.csv');
        Route::get('/reports/classes', [ReportController::class, 'classes'])->name('reports.classes');
        Route::get('/reports/classes/print', [ReportController::class, 'classPrint'])->name('reports.classes.print');
        Route::get('/reports/classes/csv', [ReportController::class, 'classCsv'])->name('reports.classes.csv');

        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

        Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
        Route::get('/security/two-factor', [SecurityController::class, 'setup'])->name('security.two-factor.setup');
        Route::post('/security/two-factor', [SecurityController::class, 'enable'])->name('security.two-factor.enable');
        Route::post('/security/two-factor/disable', [SecurityController::class, 'disable'])->name('security.two-factor.disable');
    });

    Route::middleware('role:teacher')->group(function () {
        Route::get('/my-classes', [MyClassController::class, 'index'])->name('teacher.classes');
        Route::get('/my-classes/{class}', [MyClassController::class, 'show'])->name('teacher.classes.show');
        Route::get('/teacher/attendance', [TeacherAttendanceController::class, 'index'])->name('teacher.attendance');
        Route::get('/teacher/attendance/{class}/take', [TeacherAttendanceController::class, 'take'])->name('teacher.attendance.take');
        Route::post('/teacher/attendance/{class}', [TeacherAttendanceController::class, 'store'])->name('teacher.attendance.store');
    });
});
