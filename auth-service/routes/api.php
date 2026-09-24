<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Authentication Routes
|--------------------------------------------------------------------------
*/
$authRoutes = function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
};

// Hỗ trợ cả 2 đường dẫn trực tiếp và có tiền tố /auth
$authRoutes();
Route::prefix('auth')->group($authRoutes);

/*
|--------------------------------------------------------------------------
| Protected Routes (Bearer Token via auth:api)
|--------------------------------------------------------------------------
*/
$protectedRoutes = function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);

    // Address Management
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::patch('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);
    Route::patch('/addresses/{address}/default', [AddressController::class, 'setDefault']);

    // User Management (Protected Admin APIs)
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::patch('/users/{user}/status', [AuthController::class, 'updateUserStatus']);
};

Route::middleware('auth:api')->group($protectedRoutes);
Route::prefix('auth')->middleware('auth:api')->group($protectedRoutes);

// Public fallback for direct gateway user query if needed
Route::get('/users', [AuthController::class, 'getUsers']);
Route::patch('/users/{user}/status', [AuthController::class, 'updateUserStatus']);
Route::prefix('auth')->group(function (): void {
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::patch('/users/{user}/status', [AuthController::class, 'updateUserStatus']);
});

/*
|--------------------------------------------------------------------------
| LiveChat Routes (Lab 07)
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\User\ChatController as UserChatController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;

// 1. User Chat Routes
$userChatRoutes = function (): void {
    Route::post('/chat/send', [UserChatController::class, 'send'])->name('chat.send');
    Route::get('/chat/messages', [UserChatController::class, 'getMessages'])->name('chat.messages');
};
$userChatRoutes();
Route::prefix('user')->group($userChatRoutes);

// 2. Admin Chat Routes
$adminChatRoutes = function (): void {
    Route::get('/chat/users', [AdminChatController::class, 'getUsers'])->name('admin.chat.users');
    Route::get('/chat/search', [AdminChatController::class, 'searchCustomers'])->name('admin.chat.search');
    Route::get('/chat/user-detail/{userId}', [AdminChatController::class, 'getUserDetail'])->name('admin.chat.user_detail');
    Route::get('/chat/unread-count', [AdminChatController::class, 'getUnreadCount'])->name('admin.chat.unread_count');
    Route::get('/chat/messages/{userId}', [AdminChatController::class, 'getMessages'])->name('admin.chat.messages');
    Route::post('/chat/send', [AdminChatController::class, 'send'])->name('admin.chat.send');
    Route::patch('/chat/messages/{userId}/read', [AdminChatController::class, 'markAsRead'])->name('admin.chat.mark_read');
};
$adminChatRoutes();
Route::prefix('admin')->group($adminChatRoutes);