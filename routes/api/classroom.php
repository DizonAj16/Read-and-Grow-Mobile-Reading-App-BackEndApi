<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ClassRoomController;

Route::middleware(['auth:sanctum', 'role:teacher,admin,student'])->prefix('classrooms')->group(function () {
    Route::post('/', [ClassRoomController::class, 'store']);
    Route::get('/', [ClassRoomController::class, 'index']);
    Route::get('{id}', [ClassRoomController::class, 'show']);
    Route::put('{id}', [ClassRoomController::class, 'updateClass']);
    Route::delete('{id}', [ClassRoomController::class, 'deleteClass']);
    Route::post('assign-student', [ClassRoomController::class, 'assignStudent']);
    Route::post('unassign-student', [ClassRoomController::class, 'unassignStudent']);
    Route::get('{class_id}/students', [ClassRoomController::class, 'getAssignedStudents']);
    Route::get('students/unassigned', [ClassRoomController::class, 'getUnassignedStudents']);
    Route::post('{id}/upload-background', [ClassRoomController::class, 'uploadBackground']);
    Route::post('join', [ClassRoomController::class, 'joinClass']);
});