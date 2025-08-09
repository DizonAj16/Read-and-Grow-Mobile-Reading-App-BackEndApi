<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthenticationController::class, 'login']);
    Route::post('admin/login', [AuthenticationController::class, 'adminLogin']);
    Route::post('logout', [AuthenticationController::class, 'logout'])->middleware('auth:sanctum');
});

Route::prefix('register')->group(function () {
    Route::post('admin', [AuthenticationController::class, 'registerAdmin']);
    Route::post('teacher', [AuthenticationController::class, 'registerTeacher']);
    Route::post('student', [AuthenticationController::class, 'registerStudent']);
});