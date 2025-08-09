<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;

Route::middleware(['auth:sanctum', 'role:student,teacher'])->prefix('profile')->group(function () {
    // Profile data
    Route::get('me', [UserController::class, 'getAuthProfile']);

    // Profile picture uploads
    Route::post('teacher/upload', [UserController::class, 'uploadTeacherPicture']);
    Route::post('student/upload', [UserController::class, 'uploadStudentPicture']);
});