<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Web\SsoHandshakeController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentFormController;
use App\Http\Controllers\Student\StudentSubmissionController;
use App\Http\Controllers\QA\QAOverviewController;
use App\Http\Controllers\QA\QAFormsController;
use App\Http\Controllers\QA\QAFormBuilderController;
use App\Http\Controllers\QA\QAReportsController;
use App\Http\Controllers\QA\QARemindersController;
use App\Http\Controllers\QA\QAStaffController;
use App\Http\Controllers\Admin\AdminConfigController;
use App\Http\Controllers\Admin\AdminAuditController;

/*
|--------------------------------------------------------------------------
| AFM Session Keys Contract (Used by Middleware & Controllers)
|--------------------------------------------------------------------------
| Required for afm.auth middleware:
|   - afm_token_id: Session token identifier
|   - afm_role: User role (student|qa_officer|admin)
|
| Required for Student dashboard:
|   - afm_user_id: Student SIS ID (e.g., "4401")
|   - afm_user_name: Student display name
|   - afm_term_code: Current term (e.g., "202410")
|   - afm_courses: Array of enrolled courses with structure:
|       [
|           ['course_reg_no' => 'SE401-202410', 'course_code' => 'SE401', 'course_name' => 'Software Engineering'],
|           ...
|       ]
|
| Required for QA/Admin:
|   - afm_user_id: Officer/Admin ID
|   - afm_user_name: Display name
*/

// ==========================================
// DEV-ONLY LOGIN SHORTCUTS (Local Environment Only)
// ==========================================
// ==========================================
// DEV-ONLY: SIMULATOR (Local Environment Only)
// ==========================================
if (app()->environment('local')) {
    Route::get('/dev/simulator', [\App\Http\Controllers\DevSimulatorController::class, 'index'])->name('dev.simulator');
    Route::post('/dev/simulator/login', [\App\Http\Controllers\DevSimulatorController::class, 'login'])->name('dev.simulator.login');
    
    // Legacy/Convenience Redirect for old dev usage
    Route::get('/dev/login/student', function() {
        return redirect()->route('dev.simulator')->with('info', 'Please use the new Simulator to login.');
    });

    Route::get('/dev/logout', function () {
        // Clear all AFM-related session keys
        Session::forget([
            'afm_role',
            'afm_user_id',
            'afm_user_name',
            'afm_term_code',
            'afm_courses',
        ]);

        return redirect()->route('dev.simulator');
    })->name('dev.logout');
}

// ==========================================
// AFM LANDING PAGE
// ==========================================
Route::get('/afm', function () {
    $role = Session::get('afm_role');

    if ($role === 'student') {
        return redirect('/student/dashboard');
    }

    if ($role === 'qa' || $role === 'qa_officer') {
        return redirect('/qa');
    }

    if ($role === 'admin') {
        return redirect('/admin');
    }

    return redirect('/dev/simulator');
})->name('afm.landing');

// ==========================================
// SSO ENTRY POINTS (Production / Integration)
// ==========================================
// JSON Intake (New Standard)
Route::post('/sso/json-intake', [\App\Http\Controllers\SsoJsonIntakeController::class, 'store'])->name('sso.json_intake');

// Legacy Handshake (Keep if needed for transition or remove if fully replaced)
Route::get('/sso/intake', [SsoHandshakeController::class, 'intake'])->name('sso.intake');
Route::get('/sso/handshake/{tokenId}', [SsoHandshakeController::class, 'handshake'])->name('sso.handshake');



// ==========================================
// STUDENT ROUTES (Protected by AFM Auth + Student Role)
// ==========================================
Route::middleware(['web', 'afm.student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/form/{formId}', [StudentFormController::class, 'show'])->name('form.show');
    Route::post('/response/{responseId}/draft', [StudentSubmissionController::class, 'saveDraft'])->name('response.draft');
    Route::post('/response/{responseId}/submit', [StudentSubmissionController::class, 'submit'])->name('response.submit');
});

// ==========================================
// QA ROUTES (Protected by AFM Auth + QA Officer Role)
// ==========================================
Route::middleware(['web', \App\Http\Middleware\EnsureAfmQaRole::class])->prefix('qa')->name('qa.')->group(function () {
    // Overview
    Route::get('/', [QAOverviewController::class, 'index'])->name('overview');
    
    // Forms Management
    Route::get('/forms', [QAFormsController::class, 'index'])->name('forms.index');
    Route::get('/forms/create', [QAFormsController::class, 'create'])->name('forms.create');
    Route::post('/forms', [QAFormsController::class, 'store'])->name('forms.store');
    Route::get('/forms/{id}', [QAFormsController::class, 'show'])->name('forms.show');
    Route::get('/forms/{id}/edit', [QAFormsController::class, 'edit'])->name('forms.edit');
    Route::put('/forms/{id}', [QAFormsController::class, 'update'])->name('forms.update');
    Route::delete('/forms/{id}', [QAFormsController::class, 'destroy'])->name('forms.destroy');
    Route::post('/forms/{id}/publish', [QAFormsController::class, 'publish'])->name('forms.publish');
    Route::post('/forms/{id}/archive', [QAFormsController::class, 'archive'])->name('forms.archive');
    Route::post('/forms/{id}/duplicate', [QAFormsController::class, 'duplicate'])->name('forms.duplicate');
    
    // Form Builder
    Route::post('/forms/{formId}/sections', [QAFormBuilderController::class, 'addSection'])->name('forms.sections.add');
    Route::post('/sections/{sectionId}/questions', [QAFormBuilderController::class, 'addQuestion'])->name('sections.questions.add');
    Route::delete('/sections/{sectionId}', [QAFormBuilderController::class, 'deleteSection'])->name('sections.delete');
    Route::delete('/questions/{questionId}', [QAFormBuilderController::class, 'deleteQuestion'])->name('questions.delete');
    
    // Reports
    Route::get('/reports/completion', [QAReportsController::class, 'completionReport'])->name('reports.completion');
    Route::get('/reports/students', [QAReportsController::class, 'studentReport'])->name('reports.students');
    Route::get('/reports/non-completers', [QAReportsController::class, 'nonCompleters'])->name('reports.non_completers');
    Route::get('/reports/analysis/{formId}', [QAReportsController::class, 'responseAnalysis'])->name('reports.analysis');
    Route::get('/reports/responses', [\App\Http\Controllers\QA\QAResponsesReportController::class, 'index'])->name('reports.responses');
    
    // Reminders
    Route::get('/reminders', [QARemindersController::class, 'index'])->name('reminders.index');
    Route::post('/reminders/send', [QARemindersController::class, 'send'])->name('reminders.send');

    // Staff Management
    Route::get('/staff', [QAStaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [QAStaffController::class, 'store'])->name('staff.store');
    Route::put('/staff/{id}', [QAStaffController::class, 'update'])->name('staff.update');
    Route::post('/staff/{id}/toggle', [QAStaffController::class, 'toggle'])->name('staff.toggle');
});

// ==========================================
// ADMIN ROUTES (Protected by AFM Auth + Admin Role)
// ==========================================
Route::middleware(['afm.auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/config', [AdminConfigController::class, 'index'])->name('config.index');
    Route::post('/config', [AdminConfigController::class, 'update'])->name('config.update');
    
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/{id}', [AdminAuditController::class, 'show'])->name('audit.show');
});

// ==========================================
// PUBLIC/LANDING
// ==========================================
Route::get('/', function () {
    return redirect('/afm');
});
