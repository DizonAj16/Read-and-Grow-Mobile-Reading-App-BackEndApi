<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;

Route::middleware(['auth:sanctum'])->prefix('profile')->group(function () {
    // Get authenticated profile
    Route::get('me', [UserController::class, 'getAuthProfile']);

    // Profile picture upload (role-based inside controller)
    Route::post('{role}/upload', [UserController::class, 'uploadProfilePicture']);
});

