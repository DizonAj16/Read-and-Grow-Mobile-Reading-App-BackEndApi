<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ClassRoomController;

// ============ AUTHENTICATION ROUTES ============
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthenticationController::class, 'login']);
    Route::post('admin/login', [AuthenticationController::class, 'adminLogin']);
    Route::post('logout', [AuthenticationController::class, 'logout'])->middleware('auth:sanctum');
});

// ============ REGISTRATION ROUTES ============
Route::prefix('register')->group(function () {
    Route::post('admin', [AuthenticationController::class, 'registerAdmin']);
    Route::post('teacher', [AuthenticationController::class, 'registerTeacher']);
    Route::post('student', [AuthenticationController::class, 'registerStudent']);
});

// ============ ADMIN ROUTES ============
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admins')->group(function () {
    Route::get('{id}', [UserController::class, 'getAdmin']);                   // GET /admins/{id}
    Route::get('/', [AuthenticationController::class, 'getUsers']);           // GET /admins/
    Route::delete('users/{id}', [UserController::class, 'deleteUser']);       // DELETE /admins/users/{id}
    Route::put('users/{id}', [UserController::class, 'updateUser']);          // PUT /admins/users/{id}
});

// ============ TEACHER ROUTES ============
Route::middleware(['auth:sanctum', 'role:teacher,admin'])->prefix('teachers')->group(function () {
    Route::get('students', [UserController::class, 'getAllStudents']);        // GET /teachers/students
    Route::get('/', [UserController::class, 'getAllTeachers']);               // GET /teachers/
    Route::delete('users/{id}', [UserController::class, 'deleteUser']);       // DELETE /teachers/users/{id}
    Route::put('users/{id}', [UserController::class, 'updateUser']);          // PUT /teachers/users/{id}
});

// ============ STUDENT ROUTES ============
Route::middleware(['auth:sanctum', 'role:student'])->prefix('students')->group(function () {
    Route::get('my-classes', [ClassRoomController::class, 'getStudentClasses']); // put first
    Route::get('{id}', [UserController::class, 'getStudent']);
});




// ============ CLASSROOM ROUTES ============
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

    // ✅ NEW: Upload background image
    Route::post('{id}/upload-background', [ClassRoomController::class, 'uploadBackground']);
});


// ============ PROFILE PICTURE UPLOAD ROUTES ============
Route::middleware(['auth:sanctum', 'role:student,teacher'])->prefix('profile')->group(function () {
    Route::post('teacher/upload', [UserController::class, 'uploadTeacherPicture']);   // POST /profile/teacher/upload
    Route::post('student/upload', [UserController::class, 'uploadStudentPicture']);   // POST /profile/student/upload
});


// ============ TEST ROLE ============
Route::middleware(['auth:sanctum', 'role:admin'])->get('/test-role-admin', function () {
    return response()->json(['message' => 'You are an admin']);
});

Route::middleware(['auth:sanctum', 'role:teacher'])->get('/test-role-teacher', function () {
    return response()->json(['message' => 'You are a teacher']);
});

Route::middleware(['auth:sanctum', 'role:student'])->get('/test-role-student', function () {
    return response()->json(['message' => 'You are a student']);
});

Route::middleware('auth:sanctum')->get('/test-auth', function () {
    return response()->json([
        'auth_id' => auth()->id(),
        'user' => auth()->user()
    ]);
});

