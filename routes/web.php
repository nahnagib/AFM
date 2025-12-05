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
if (app()->environment('local')) {
    
    // DEV: Student Login
    Route::get('/dev/login/student', function () {
        // Simulate AFM session for student "Nahla" (ID: 4401)
        Session::put([
            'afm_token_id' => 'dev-token-student-' . time(),
            'afm_role' => 'student',
            'afm_user_id' => '4401',
            'afm_user_name' => 'Nahla Burweiss',
            'afm_term_code' => '202410',
            'afm_courses' => [
                ['course_reg_no' => 'SE401-202410', 'course_code' => 'SE401', 'course_name' => 'Software Engineering Project'],
                ['course_reg_no' => 'CS301-202410', 'course_code' => 'CS301', 'course_name' => 'Database Systems'],
                ['course_reg_no' => 'IT201-202410', 'course_code' => 'IT201', 'course_name' => 'Web Development'],
            ],
        ]);
        
        return redirect()->route('student.dashboard')->with('success', 'Logged in as Student (DEV MODE)');
    })->name('dev.login.student');
    
    // DEV: QA Officer Login
    Route::get('/dev/login/qa', function () {
        // Simulate AFM session for QA officer
        Session::put([
            'afm_token_id' => 'dev-token-qa-' . time(),
            'afm_role' => 'qa_officer',
            'afm_user_id' => 'qa001',
            'afm_user_name' => 'Dr. Ahmed QA Officer',
            'afm_term_code' => '202410',
        ]);
        
        return redirect()->route('qa.overview')->with('success', 'Logged in as QA Officer (DEV MODE)');
    })->name('dev.login.qa');
    
    // DEV: Admin Login
    Route::get('/dev/login/admin', function () {
        // Simulate AFM session for admin
        Session::put([
            'afm_token_id' => 'dev-token-admin-' . time(),
            'afm_role' => 'admin',
            'afm_user_id' => 'admin001',
            'afm_user_name' => 'System Administrator',
            'afm_term_code' => '202410',
        ]);
        
        return redirect()->route('admin.config.index')->with('success', 'Logged in as Admin (DEV MODE)');
    })->name('dev.login.admin');
    
    // DEV: Logout
    Route::get('/dev/logout', function () {
        // Clear all AFM session keys
        Session::forget(['afm_token_id', 'afm_role', 'afm_user_id', 'afm_user_name', 'afm_term_code', 'afm_courses']);
        
        return redirect('/afm')->with('success', 'Logged out successfully (DEV MODE)');
    })->name('dev.logout');
}

// ==========================================
// AFM LANDING PAGE
// ==========================================
Route::get('/afm', function () {
    // Check if user is authenticated
    if (Session::has('afm_token_id') && Session::has('afm_role')) {
        $role = Session::get('afm_role');
        
        // Redirect based on role
        return match($role) {
            'student' => redirect()->route('student.dashboard'),
            'qa_officer' => redirect()->route('qa.overview'),
            'admin' => redirect()->route('admin.config.index'),
            default => redirect('/')->with('error', 'Unknown role'),
        };
    }
    
    // Not authenticated
    if (app()->environment('local')) {
        // Show dev login links on local environment
        return view('afm.dev-landing');
    }
    
    // Production: redirect to SSO or show message
    return redirect()->route('sso.intake');
})->name('afm.landing');

// ==========================================
// SSO ENTRY POINTS (Production)
// ==========================================
Route::get('/sso/intake', [SsoHandshakeController::class, 'intake'])->name('sso.intake');
Route::get('/sso/handshake/{tokenId}', [SsoHandshakeController::class, 'handshake'])->name('sso.handshake');

// ==========================================
// STUDENT ROUTES (Protected by AFM Auth + Student Role)
// ==========================================
Route::middleware(['afm.auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/form/{formId}', [StudentFormController::class, 'show'])->name('form.show');
    Route::post('/response/{responseId}/draft', [StudentSubmissionController::class, 'saveDraft'])->name('response.draft');
    Route::post('/response/{responseId}/submit', [StudentSubmissionController::class, 'submit'])->name('response.submit');
});

// ==========================================
// QA ROUTES (Protected by AFM Auth + QA Officer Role)
// ==========================================
Route::middleware(['afm.auth', 'role:qa_officer'])->prefix('qa')->name('qa.')->group(function () {
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
    
    // Reminders
    Route::get('/reminders', [QARemindersController::class, 'index'])->name('reminders.index');
    Route::post('/reminders/send', [QARemindersController::class, 'send'])->name('reminders.send');
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
    return view('welcome');
});
