<?php

use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Service API Routes (Port 8001)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Public Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    
    // Password Reset
    Route::post('/forgot-password/send-otp', [AuthController::class, 'sendResetOtp']);
    Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyResetOtp']);

    // Authenticated Profile & Address Routes
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Addresses
    Route::get('/addresses', [AuthController::class, 'getAddresses']);
    Route::post('/addresses', [AuthController::class, 'addAddress']);
    Route::put('/addresses/{id}', [AuthController::class, 'updateAddress']);
    Route::delete('/addresses/{id}', [AuthController::class, 'deleteAddress']);
    Route::patch('/addresses/{id}/default', [AuthController::class, 'setDefaultAddress']);
});

// Admin User Management
Route::get('/users', [AuthController::class, 'getUsers']);
Route::get('/users/{id}', [AuthController::class, 'getUserDetail']);
Route::patch('/users/{id}/status', [AuthController::class, 'toggleStatus']);

// Customer & Admin Live Chat
Route::get('/messages', [ChatController::class, 'getMessages']);
Route::post('/messages', [ChatController::class, 'sendMessage']);
Route::get('/admin/conversations', [ChatController::class, 'getConversations']);
Route::patch('/admin/messages/read', [ChatController::class, 'markAsRead']);
