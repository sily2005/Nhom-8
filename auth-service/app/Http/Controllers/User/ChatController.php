<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Gửi tin nhắn từ User tới Admin
     */
    public function send(Request $request): JsonResponse
    {
        // 1. Lấy nội dung tin nhắn
        $messageText = $request->input('message') ?? $request->input('content');

        // 2. Kiểm tra nội dung trống
        if (empty(trim((string) $messageText))) {
            return response()->json([
                'success' => false,
                'message' => 'Nội dung tin nhắn không được để trống.',
                'error' => 'Nội dung tin nhắn không được để trống',
            ], 400);
        }

        // 3. Xác định User gửi tin nhắn
        $senderId = auth('api')->id() ?? Auth::id() ?? $request->input('sender_id');
        if (!$senderId) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để gửi tin nhắn.',
            ], 401);
        }

        // 4. Xác định Admin nhận tin
        $admin = User::where('role', 'admin')->first();
        $receiverId = $admin ? $admin->id : 1;

        try {
            // 5. Lưu tin nhắn vào Database
            $message = Message::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'content' => trim((string) $messageText),
                'is_read' => false,
            ]);

            $message->load(['sender', 'receiver']);

            return response()->json([
                'success' => true,
                'message' => 'Gửi tin nhắn thành công.',
                'data' => $message,
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'content' => $message->content,
                'is_read' => $message->is_read,
                'created_at' => $message->created_at,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi tin nhắn: ' . $e->getMessage(),
                'error' => 'Không thể gửi tin nhắn: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy lịch sử chat giữa User hiện tại và Admin
     */
    public function getMessages(Request $request): JsonResponse
    {
        $userId = auth('api')->id() ?? Auth::id() ?? $request->query('user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để xem tin nhắn.',
                'data' => [],
            ], 401);
        }

        // Tìm Admin để lọc tin nhắn qua lại
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : 1;

        // Lấy toàn bộ hội thoại giữa 2 người
        $messages = Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($userId, $adminId) {
                // Tin nhắn User gửi cho Admin
                $q->where('sender_id', $userId)
                  ->where('receiver_id', $adminId);
            })
            ->orWhere(function ($q) use ($userId, $adminId) {
                // Tin nhắn Admin phản hồi cho User
                $q->where('sender_id', $adminId)
                  ->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy lịch sử tin nhắn thành công.',
            'data' => $messages,
        ]);
    }
}
