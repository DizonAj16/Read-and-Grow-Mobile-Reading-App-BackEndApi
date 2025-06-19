<?php

use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

// --- Registration Endpoints ---
Route::post('/admin/register', [AuthenticationController::class, 'registerAdmin']);
Route::post('/teacher/register', [AuthenticationController::class, 'registerTeacher']);
Route::post('/student/register', [AuthenticationController::class, 'registerStudent']);

// --- Authentication Endpoints ---
Route::post('/login', [AuthenticationController::class, 'login']);
Route::post('/admin/login', [AuthenticationController::class, 'adminLogin']);
Route::middleware('auth:sanctum')->post('/logout', [AuthenticationController::class, 'logout']);

// --- User Info Endpoints ---
Route::get('/admin/{id}', [UserController::class, 'getAdmin']);
// Route::get('/teacher/{id}', [UserController::class, 'getTeacher']); // Uncomment if needed
Route::get('/student/{id}', [UserController::class, 'getStudent']);

// --- Admin-only Endpoints ---
Route::middleware('auth:sanctum')->get('/users', [AuthenticationController::class, 'getUsers']);

// --- Teacher-only Endpoints ---
Route::middleware('auth:sanctum')->get('/teacher/students', [UserController::class, 'getAllStudents']);
Route::middleware('auth:sanctum')->get('/teachers', [UserController::class, 'getAllTeachers']);

Route::middleware('auth:sanctum')->delete('/user/{id}', [UserController::class, 'deleteUser']);
