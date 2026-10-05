<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\DaycareController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\SchoolCalendarController;
use App\Http\Controllers\Api\SubjectController; // Phase 1A
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route publique : connexion (10 essais par minute maximum)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| DIRECTEUR + SECRETAIRE (connexion obligatoire)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Profil utilisateur & Changement de mot de passe (Directeur & Secrétaire)
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/profile/password', [UserController::class, 'updatePassword']);

    Route::get('/stats', [DashboardController::class, 'index']);

    Route::apiResource('teachers', TeacherController::class)->except(['destroy']);
    Route::apiResource('students', StudentController::class)->except(['destroy']);
    Route::apiResource('daycare', DaycareController::class)->except(['destroy']);

    // Soldes a regler (calcules : seances par enseignant, mois de garderie)
    Route::get('/payment-dues', [PaymentController::class, 'dues']);

    // Extraits de gestion de paiements (Directeur uniquement)
    Route::get('/payments/extract-teachers', [PaymentController::class, 'extractTeachers']);
    Route::get('/payments/extract-students', [PaymentController::class, 'extractStudents']);

    Route::apiResource('payments', PaymentController::class)->except(['destroy']);

    Route::apiResource('schedules', ScheduleController::class);

    // Matieres : consultation publique au personnel connecté
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/subjects/{subject}', [SubjectController::class, 'show']);

    Route::get('/attendances/summary/{studentId}', [\App\Http\Controllers\Api\AttendanceController::class, 'studentSummary']);
    Route::post('/attendances/batch', [\App\Http\Controllers\Api\AttendanceController::class, 'batch']);
    Route::apiResource('attendances', \App\Http\Controllers\Api\AttendanceController::class)->except(['destroy']);

    /*
    |----------------------------------------------------------------------
    | Ecoles primaires
    |----------------------------------------------------------------------
    */
    Route::get('/schools', [SchoolController::class, 'index']);
    Route::post('/schools', [SchoolController::class, 'store']);
    Route::put('/schools/{school}', [SchoolController::class, 'update']);
    Route::delete('/schools/{school}', [SchoolController::class, 'destroy']);
    Route::get('/schools/{school}/classes', [SchoolController::class, 'classes']);

    /*
    |----------------------------------------------------------------------
    | Emplois du temps importes (PDF / JPG / PNG)
    |----------------------------------------------------------------------
    */
    Route::get('/school-calendar-imports', [SchoolCalendarController::class, 'index']);
    Route::post('/school-calendar-imports', [SchoolCalendarController::class, 'store']);
    Route::get('/school-calendar-imports/{id}/file', [SchoolCalendarController::class, 'file']);
    Route::post('/school-calendar-imports/{id}', [SchoolCalendarController::class, 'update']);
    Route::delete('/school-calendar-imports/{id}', [SchoolCalendarController::class, 'destroy']);

    /*
    |----------------------------------------------------------------------
    | ACTIONS RÉSERVÉES AU DIRECTEUR (Rôle 'director' obligatoire)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:director')->group(function () {
        Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy']);
        Route::delete('/students/{student}', [StudentController::class, 'destroy']);
        Route::delete('/daycare/{daycare}', [DaycareController::class, 'destroy']);
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy']);
        Route::delete('/attendances/{attendance}', [\App\Http\Controllers\Api\AttendanceController::class, 'destroy']);

        // Gestion du versement de rémunération des enseignants (marquer si le prof a pris son argent)
        Route::post('/teachers/{id}/toggle-payout', [TeacherController::class, 'togglePayout']);

        // Gestion des matières
        Route::post('/subjects', [SubjectController::class, 'store']);
        Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);

        // Module Comptabilité & Finance Avancée (Réservé Directeur)
        Route::get('/accounting/summary', [\App\Http\Controllers\Api\AccountingController::class, 'summary']);
        Route::get('/accounting/expenses', [\App\Http\Controllers\Api\AccountingController::class, 'expenses']);
        Route::post('/accounting/expenses', [\App\Http\Controllers\Api\AccountingController::class, 'storeExpense']);
        Route::put('/accounting/expenses/{id}', [\App\Http\Controllers\Api\AccountingController::class, 'updateExpense']);
        Route::delete('/accounting/expenses/{id}', [\App\Http\Controllers\Api\AccountingController::class, 'destroyExpense']);
        Route::post('/accounting/expenses/generate-recurring', [\App\Http\Controllers\Api\AccountingController::class, 'generateRecurringExpenses']);
        Route::get('/accounting/audit-logs', [\App\Http\Controllers\Api\AccountingController::class, 'auditLogs']);
        Route::get('/accounting/closings', [\App\Http\Controllers\Api\AccountingController::class, 'closings']);
        Route::post('/accounting/close-month', [\App\Http\Controllers\Api\AccountingController::class, 'closeMonth']);
        Route::post('/accounting/reopen-month', [\App\Http\Controllers\Api\AccountingController::class, 'reopenMonth']);
        Route::get('/accounting/teachers/{id}/payslip', [\App\Http\Controllers\Api\AccountingController::class, 'teacherPayslip']);

        // Gestion des comptes & Secrétaires (Réservé au Directeur - Idée 1)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
});