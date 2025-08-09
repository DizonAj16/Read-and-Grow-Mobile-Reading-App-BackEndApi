<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\GradeController;
use Illuminate\Support\Facades\Auth;

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

Route::get('/test-grades', [GradeController::class, 'index']);

Route::middleware(['auth:sanctum', 'role:student'])->get('/debug-auth', function () {
    return response()->json([
        'auth_id' => Auth::id(),
        'user' => auth()->user()
    ]);
});