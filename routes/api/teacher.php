<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TaskMaterialController;

Route::middleware(['auth:sanctum', 'role:teacher,admin'])->prefix('teachers')->group(function () {
    Route::get('students', [UserController::class, 'getAllStudents']);
    Route::get('/', [UserController::class, 'getAllTeachers']);
    Route::delete('users/{id}', [UserController::class, 'deleteUser']);
    Route::put('users/{id}', [UserController::class, 'updateUser']);

    // PDF Routes
    Route::prefix('materials')->group(function () {
        Route::post('/', [TaskMaterialController::class, 'store']);
        Route::get('{class_room_id}', [TaskMaterialController::class, 'getByClassroom']);
        Route::delete('{id}', [TaskMaterialController::class, 'deleteMaterial']);
    });
});