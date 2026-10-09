<?php

use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\User\ChatController as UserChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| LiveChat & AI Chatbox Routes (chat-service)
|--------------------------------------------------------------------------
*/

// 1. User Chat Routes
$userChatRoutes = function (): void {
    Route::post('/send', [UserChatController::class, 'send'])->name('chat.send');
    Route::get('/messages', [UserChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/feedback', [UserChatController::class, 'submitFeedback'])->name('chat.feedback');
};

Route::prefix('user/chat')->group($userChatRoutes);
Route::prefix('chat')->group($userChatRoutes);

// 2. Admin Chat Routes
$adminChatRoutes = function (): void {
    Route::get('/users', [AdminChatController::class, 'getUsers'])->name('admin.chat.users');
    Route::get('/search', [AdminChatController::class, 'searchCustomers'])->name('admin.chat.search');
    Route::get('/user-detail/{userId}', [AdminChatController::class, 'getUserDetail'])->name('admin.chat.user_detail');
    Route::get('/unread-count', [AdminChatController::class, 'getUnreadCount'])->name('admin.chat.unread_count');
    Route::get('/messages/{userId}', [AdminChatController::class, 'getMessages'])->name('admin.chat.messages');
    Route::post('/send', [AdminChatController::class, 'send'])->name('admin.chat.send');
    Route::patch('/messages/{userId}/read', [AdminChatController::class, 'markAsRead'])->name('admin.chat.mark_read');
    Route::get('/feedbacks', [AdminChatController::class, 'getFeedbacks'])->name('admin.chat.feedbacks');
};

Route::prefix('admin/chat')->group($adminChatRoutes);