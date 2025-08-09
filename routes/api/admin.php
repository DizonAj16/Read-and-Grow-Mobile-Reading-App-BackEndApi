<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admins')->group(function () {
    Route::get('{id}', [UserController::class, 'getAdmin']);
    Route::get('/', [UserController::class, 'getUsers']);
    Route::delete('users/{id}', [UserController::class, 'deleteUser']);
    Route::put('users/{id}', [UserController::class, 'updateUser']);
});