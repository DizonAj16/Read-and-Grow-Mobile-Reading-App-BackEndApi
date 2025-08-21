<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthenticationController;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthenticationController::class, 'login'])
        ->middleware('throttle:10,1'); // 10 requests per minute

    Route::post('admin/login', [AuthenticationController::class, 'adminLogin'])
        ->middleware('throttle:5,1'); // admin login - 5 requests per minute

    Route::post('logout', [AuthenticationController::class, 'logout'])
        ->middleware(['auth:sanctum', 'throttle:30,1']); // logged-in users
});

Route::prefix('register')->group(function () {
    Route::post('admin', [AuthenticationController::class, 'registerAdmin'])
        ->middleware('throttle:3,10'); // 3 requests every 10 minutes

    Route::post('teacher', [AuthenticationController::class, 'registerTeacher'])
        ->middleware('throttle:3,10'); // same as above

    Route::post('student', [AuthenticationController::class, 'registerStudent'])
        ->middleware('throttle:3,10'); // prevent spam signups
});
