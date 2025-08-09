<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\StudentTaskProgressController;

Route::middleware(['auth:sanctum', 'role:student'])->prefix('progress')->group(function () {
    Route::post('/', [StudentTaskProgressController::class, 'store']);
    Route::get('{student_id}', [StudentTaskProgressController::class, 'showProgress']);
    Route::post('reset/{student_id}/{task_id}', [StudentTaskProgressController::class, 'resetAttempts']);
});