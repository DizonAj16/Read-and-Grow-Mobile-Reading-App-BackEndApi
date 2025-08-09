<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\GradeController;
use App\Http\Controllers\API\TaskController;

Route::middleware(['auth:sanctum', 'role:teacher,admin,student'])->prefix('grades')->group(function () {
    Route::get('/', [GradeController::class, 'index']);
    Route::get('{grade}/tasks', [TaskController::class, 'index']);
});