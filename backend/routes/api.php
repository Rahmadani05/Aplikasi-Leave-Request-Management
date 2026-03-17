<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LeaveRequestController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Admin
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);

    // Cuti (Leave Requests)
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']); 
    Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy']); 
    
    // Khusus User
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']); 
    Route::put('/leave-requests/{id}/cancel', [LeaveRequestController::class, 'cancel']); 

    // Khusus Admin
    Route::put('/leave-requests/{id}/respond', [LeaveRequestController::class, 'respond']);
});