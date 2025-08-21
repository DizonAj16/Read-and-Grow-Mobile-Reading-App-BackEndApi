<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ClassRoomController;
use App\Http\Controllers\API\TaskMaterialController;

Route::middleware(['auth:sanctum', 'role:student'])->prefix('students')->group(function () {
    Route::get('my-classes', [ClassRoomController::class, 'getStudentClasses']);
    Route::get('tasks', [ClassRoomController::class, 'getStudentTasks']);
    Route::get('profile-pictures', [UserController::class, 'getAllStudentProfilePictures']);
    Route::get('materials', [TaskMaterialController::class, 'getMyClassroomMaterials']);
    Route::get('{id}', [UserController::class, 'getStudent']);
});