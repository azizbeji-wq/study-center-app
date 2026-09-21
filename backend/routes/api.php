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
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/stats', [DashboardController::class, 'index']);

Route::apiResource('teachers', TeacherController::class);

Route::apiResource('students', StudentController::class);

Route::apiResource('daycare', DaycareController::class);

Route::apiResource('payments', PaymentController::class);

Route::apiResource('schedules', ScheduleController::class);

/*
|--------------------------------------------------------------------------
| Écoles primaires
|--------------------------------------------------------------------------
*/

Route::get('/schools', [
    SchoolController::class,
    'index'
]);

Route::get('/schools/{school}/classes', [
    SchoolController::class,
    'classes'
]);

Route::get('/school-classes/{classId}/calendar', [
    SchoolCalendarController::class,
    'showClass'
]);