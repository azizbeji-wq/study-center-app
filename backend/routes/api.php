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

    Route::get('/stats', [DashboardController::class, 'index']);

    Route::apiResource('teachers', TeacherController::class);

    Route::apiResource('students', StudentController::class);

    Route::apiResource('daycare', DaycareController::class);

    // Soldes a regler (calcules : seances par enseignant, mois de garderie)
    Route::get('/payment-dues', [PaymentController::class, 'dues']);

    Route::apiResource('payments', PaymentController::class);

    Route::apiResource('schedules', ScheduleController::class);

    // Matieres : tout le monde peut consulter, seul le directeur peut modifier
    // (la verification est faite dans SubjectController)
    Route::apiResource('subjects', SubjectController::class);

    Route::get('/attendances/summary/{studentId}', [\App\Http\Controllers\Api\AttendanceController::class, 'studentSummary']);
    Route::post('/attendances/batch', [\App\Http\Controllers\Api\AttendanceController::class, 'batch']);
    Route::apiResource('attendances', \App\Http\Controllers\Api\AttendanceController::class);

    /*
    |----------------------------------------------------------------------
    | Ecoles primaires
    |----------------------------------------------------------------------
    */
    Route::get('/schools', [SchoolController::class, 'index']);

    Route::get('/schools/{school}/classes', [SchoolController::class, 'classes']);

    // Ancienne route desactivee : la methode showClass n'existe plus.
    // Route::get('/school-classes/{classId}/calendar', [SchoolCalendarController::class, 'showClass']);

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
});