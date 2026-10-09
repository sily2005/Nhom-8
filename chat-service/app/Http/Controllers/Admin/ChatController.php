<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatFeedback;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class ChatController extends Controller
{
    /**
     * Xác định Admin ID hiện tại
     */
    private function resolveAdminId(Request $request): int
    {
        $adminId = $request->header('X-User-Id') ?? $request->input('admin_id');
        if ($adminId) {
            return (int) $adminId;
        }

        $authHeader = (string) $request->header('Authorization', '');
        if (str_starts_with($authHeader, 'Bearer ')) {
            $parts = explode('.', substr($authHeader, 7));
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                if (isset($payload['sub'])) {
                    return (int) $payload['sub'];
                }
            }
        }

        if (Auth::check()) {
            return (int) Auth::id();
        }

        try {
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                return (int) $admin->id;
            }
        } catch (Throwable $e) {}

        return 1;
    }

    /**
     * Chuẩn hóa cấu trúc dữ liệu khách hàng gửi về frontend
     */
    private function formatCustomerUser(int $userId, mixed $user, int $adminId, ?int $unreadCount = null, ?Message $lastMsg = null): array
    {
        $name = is_array($user) ? ($user['name'] ?? null) : ($user->name ?? null);
        $email = is_array($user) ? ($user['email'] ?? '') : ($user->email ?? '');
        $phone = is_array($user) ? ($user['phone'] ?? ($user['phone_number'] ?? '')) : ($user->phone ?? ($user->phone_number ?? ''));
        $role = is_array($user) ? ($user['role'] ?? 'customer') : ($user->role ?? 'customer');

        if ($lastMsg === null) {
            $lastMsg = Message::where(function ($q) use ($userId, $adminId) {
                $q->where('sender_id', $userId)->where('receiver_id', $adminId);
            })->orWhere(function ($q) use ($userId, $adminId) {
                $q->where('sender_id', $adminId)->where('receiver_id', $userId);
            })->orderByDesc('created_at')->first();
        }

        if ($unreadCount === null) {
            $unreadCount = Message::where('sender_id', $userId)
                ->where('receiver_id', $adminId)
                ->where('is_read', false)
                ->count();
        }

        return [
            'id' => $userId,
            'name' => $name ?: "Khách hàng #{$userId}",
            'email' => $email,
            'phone' => $phone,
            'phone_number' => $phone,
            'avatar' => null,
            'role' => $role,
            'last_message' => $lastMsg ? $lastMsg->content : '',
            'last_message_time' => $lastMsg ? $lastMsg->created_at : null,
            'unread_count' => (int) $unreadCount,
        ];
    }

    /**
     * Tra cứu thông tin 1 User từ Database hoặc Auth Microservice
     */
    private function findUser(int $userId): ?object
    {
        try {
            $user = User::select('id', 'name', 'email', 'phone', 'role', 'created_at')->find($userId);
            if ($user) {
                return $user;
            }
        } catch (Throwable $e) {}

        $authUrl = rtrim((string) config('services.microservices.auth', 'http://127.0.0.1:8001'), '/');
        try {
            $res = Http::timeout(2)->get("{$authUrl}/api/users/{$userId}");
            if ($res->successful()) {
                $userData = $res->json('data') ?? $res->json('user');
                if ($userData) {
                    return (object) $userData;
                }
            }
        } catch (Throwable $ex) {}

        return null;
    }

    /**
     * Lấy danh sách khách hàng đã nhắn tin kèm tin nhắn mới nhất và số tin chưa đọc
     */
    public function getUsers(Request $request): JsonResponse
    {
        $adminId = $this->resolveAdminId($request);

        // 1. Lấy danh sách ID khách hàng đã từng trò chuyện
        $userIds = Message::where(function ($q) use ($adminId) {
                $q->where('receiver_id', $adminId)->where('sender_id', '!=', $adminId);
            })
            ->orWhere(function ($q) use ($adminId) {
                $q->where('sender_id', $adminId)->where('receiver_id', '!=', $adminId);
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($msg) => (int) ($msg->sender_id == $adminId ? $msg->receiver_id : $msg->sender_id))
            ->filter(fn ($id) => $id !== (int) $adminId)
            ->unique()
            ->values()
            ->toArray();

        if (empty($userIds)) {
            return response()->json([
                'success' => true,
                'message' => 'Chưa có cuộc trò chuyện nào.',
                'data' => [],
            ]);
        }

        // 2. Tra cứu thông tin User từ Database Auth hoặc fallback Microservice
        $usersMap = [];
        try {
            $users = User::whereIn('id', $userIds)->where('role', '!=', 'admin')->get();
            foreach ($users as $u) {
                $usersMap[$u->id] = $u;
            }
        } catch (Throwable $e) {
            $authUrl = rtrim((string) config('services.microservices.auth', 'http://127.0.0.1:8001'), '/');
            try {
                $res = Http::timeout(2)->get("{$authUrl}/api/users");
                if ($res->successful() && is_array($res->json('data'))) {
                    foreach ($res->json('data') as $item) {
                        if (in_array((int)$item['id'], $userIds)) {
                            $usersMap[$item['id']] = (object) $item;
                        }
                    }
                }
            } catch (Throwable $ex) {}
        }

        // 3. Gom nhóm số tin nhắn chưa đọc trong 1 truy vấn
        $unreadCounts = Message::whereIn('sender_id', $userIds)
            ->where('receiver_id', $adminId)
            ->where('is_read', false)
            ->select('sender_id', DB::raw('count(*) as total'))
            ->groupBy('sender_id')
            ->pluck('total', 'sender_id')
            ->toArray();

        // 4. Định dạng kết quả và sắp xếp theo tin nhắn mới nhất
        $usersWithDetails = collect($userIds)->map(function ($userId) use ($adminId, $usersMap, $unreadCounts) {
            return $this->formatCustomerUser(
                $userId,
                $usersMap[$userId] ?? null,
                $adminId,
                (int) ($unreadCounts[$userId] ?? 0)
            );
        })->sortByDesc(fn ($item) => $item['last_message_time'] ? strtotime((string)$item['last_message_time']) : 0)->values();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách người dùng nhắn tin thành công.',
            'data' => $usersWithDetails,
        ]);
    }

    /**
     * Tìm kiếm khách hàng theo tên, email, số điện thoại để Admin chủ động nhắn tin
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $adminId = $this->resolveAdminId($request);
        $query = trim((string) $request->input('query', ''));

        $customers = collect();
        try {
            $customers = User::where('id', '!=', $adminId)
                ->where('role', '!=', 'admin')
                ->when($query !== '', function ($q) use ($query) {
                    $q->where(function ($sub) use ($query) {
                        $sub->where('name', 'like', "%{$query}%")
                            ->orWhere('email', 'like', "%{$query}%")
                            ->orWhere('phone', 'like', "%{$query}%");
                    });
                })
                ->limit(30)
                ->get();
        } catch (Throwable $e) {
            $authUrl = rtrim((string) config('services.microservices.auth', 'http://127.0.0.1:8001'), '/');
            try {
                $response = Http::timeout(2)->get("{$authUrl}/api/users", ['search' => $query]);
                if ($response->successful()) {
                    $customers = collect($response->json('data') ?? []);
                }
            } catch (Throwable $ex) {}
        }

        $customersWithDetails = $customers->map(function ($user) use ($adminId) {
            $userId = is_array($user) ? (int)$user['id'] : (int)$user->id;
            return $this->formatCustomerUser($userId, $user, $adminId);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tìm kiếm khách hàng thành công.',
            'data' => $customersWithDetails,
        ]);
    }

    /**
     * Lấy thông tin 1 User để Admin mở cuộc trò chuyện
     */
    public function getUserDetail(Request $request, $userId): JsonResponse
    {
        $user = $this->findUser((int) $userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy khách hàng.',
            ], 404);
        }

        $adminId = $this->resolveAdminId($request);
        return response()->json([
            'success' => true,
            'data' => $this->formatCustomerUser((int) $userId, $user, $adminId, 0),
        ]);
    }

    /**
     * Lấy lịch sử tin nhắn của một User cụ thể và đánh dấu đã đọc
     */
    public function getMessages(Request $request, $userId): JsonResponse
    {
        $adminId = $this->resolveAdminId($request);

        // Đánh dấu tin nhắn từ user gửi tới admin là đã đọc
        Message::where('sender_id', $userId)
            ->where('receiver_id', $adminId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where(function ($q) use ($userId, $adminId) {
                $q->where('sender_id', $userId)->where('receiver_id', $adminId);
            })
            ->orWhere(function ($q) use ($userId, $adminId) {
                $q->where('sender_id', $adminId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy lịch sử tin nhắn thành công.',
            'data' => $messages,
        ]);
    }

    /**
     * Admin gửi tin nhắn phản hồi cho User
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required',
            'message' => 'sometimes|nullable|string',
            'content' => 'sometimes|nullable|string',
        ]);

        $content = trim((string) ($request->input('message') ?? $request->input('content') ?? ''));
        $attachmentUrl = $request->input('attachment_url') ?? $request->input('file_url');
        $attachmentType = $request->input('attachment_type') ?? ($attachmentUrl ? 'image' : null);
        $attachmentName = $request->input('attachment_name');

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
            $destinationPath = public_path('uploads/chat');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $file->move($destinationPath, $fileName);
            $attachmentUrl = '/uploads/chat/' . $fileName;
            $attachmentName = $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
            $attachmentType = str_starts_with((string) $mime, 'image/') ? 'image' : 'file';
        }

        if (empty($content) && empty($attachmentUrl)) {
            return response()->json([
                'success' => false,
                'message' => 'Nội dung tin nhắn không được để trống.',
            ], 400);
        }

        $adminId = $this->resolveAdminId($request);
        $targetUserId = (int) $request->input('user_id');

        $message = Message::create([
            'sender_id' => $adminId,
            'receiver_id' => $targetUserId,
            'content' => $content ?: '[Tệp đính kèm]',
            'sender_type' => 'ADMIN',
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
            'attachment_name' => $attachmentName,
            'is_read' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin gửi tin nhắn thành công.',
            'data' => $message,
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'content' => $message->content,
            'sender_type' => $message->sender_type,
            'attachment_url' => $message->attachment_url,
            'attachment_type' => $message->attachment_type,
            'attachment_name' => $message->attachment_name,
            'is_read' => $message->is_read,
            'created_at' => $message->created_at,
        ]);
    }

    /**
     * Lấy tổng số tin nhắn chưa đọc cho Admin (Badge chuông thông báo)
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $adminId = $this->resolveAdminId($request);

        $totalUnread = Message::where('receiver_id', $adminId)
            ->where('is_read', false)
            ->count();

        $recentMessages = Message::where('receiver_id', $adminId)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'unread_count' => $totalUnread,
            'count' => $totalUnread,
            'data' => [
                'unread_count' => $totalUnread,
                'recent_messages' => $recentMessages,
            ],
        ]);
    }

    /**
     * Đánh dấu toàn bộ tin nhắn của một User là đã đọc
     */
    public function markAsRead(Request $request, $userId): JsonResponse
    {
        $adminId = $this->resolveAdminId($request);

        Message::where('sender_id', $userId)
            ->where('receiver_id', $adminId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu tin nhắn là đã đọc.',
        ]);
    }

    /**
     * Lấy danh sách đánh giá hài lòng (CSAT) và thống kê điểm trung bình
     */
    public function getFeedbacks(Request $request): JsonResponse
    {
        $feedbacks = ChatFeedback::orderByDesc('created_at')->limit(50)->get();
        $total = ChatFeedback::count();
        $avg = $total > 0 ? round((float) ChatFeedback::avg('rating'), 1) : 5.0;

        $grouped = ChatFeedback::select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->toArray();

        $ratingCounts = [
            5 => (int) ($grouped[5] ?? 0),
            4 => (int) ($grouped[4] ?? 0),
            3 => (int) ($grouped[3] ?? 0),
            2 => (int) ($grouped[2] ?? 0),
            1 => (int) ($grouped[1] ?? 0),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'feedbacks' => $feedbacks,
                'stats' => [
                    'total' => $total,
                    'average_rating' => $avg,
                    'rating_counts' => $ratingCounts,
                ],
            ],
        ]);
    }
}
