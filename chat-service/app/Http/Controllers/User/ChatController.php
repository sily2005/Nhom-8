<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatFeedback;
use App\Models\Message;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatController extends Controller
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

    /**
     * Xác định ID người dùng từ Request / Header / JWT Token
     */
    private function resolveUserId(Request $request): ?int
    {
        $id = $request->input('sender_id') 
            ?? $request->input('user_id') 
            ?? $request->query('user_id') 
            ?? $request->header('X-User-Id');

        if ($id) {
            return (int) $id;
        }

        if (Auth::check()) {
            return (int) Auth::id();
        }

        // Giải mã JWT Token nếu có trong Authorization header
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

        return null;
    }

    /**
     * Xử lý lưu trữ tệp đính kèm (Multipart file, Base64 Data URL, Đường dẫn server)
     * @return array{url: ?string, type: ?string, name: ?string, local_path: ?string, mime: ?string}
     */
    private function processAttachment(Request $request): array
    {
        $attachmentUrl = $request->input('attachment_url') ?? $request->input('file_url');
        $attachmentType = $request->input('attachment_type');
        $attachmentName = $request->input('attachment_name');
        $localPath = null;
        $mimeType = null;

        $uploadDir = public_path('uploads/chat');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // 1. Upload file từ multipart form-data
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
            $file->move($uploadDir, $fileName);

            $localPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
            $attachmentUrl = '/uploads/chat/' . $fileName;
            $attachmentName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $attachmentType = str_starts_with((string) $mimeType, 'image/') ? 'image' : 'file';
        }
        // 2. Base64 Data URL (data:image/...)
        elseif (!empty($attachmentUrl) && str_starts_with($attachmentUrl, 'data:image/')) {
            if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,(.*)$/s', $attachmentUrl, $matches)) {
                $mimeType = $matches[1];
                $binary = base64_decode($matches[2]);
                if ($binary !== false) {
                    $ext = match ($mimeType) {
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        'image/gif' => 'gif',
                        default => 'jpg',
                    };
                    $fileName = time() . '_' . uniqid() . '.' . $ext;
                    $savePath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
                    file_put_contents($savePath, $binary);

                    $localPath = $savePath;
                    $attachmentUrl = '/uploads/chat/' . $fileName;
                    $attachmentType = 'image';
                    $attachmentName = $attachmentName ?: ('image_' . date('Ymd_His') . '.' . $ext);
                }
            }
        }
        // 3. Đường dẫn ảnh có sẵn trên server
        elseif (!empty($attachmentUrl) && $attachmentType === 'image') {
            $candidatePath = public_path(ltrim((string) parse_url($attachmentUrl, PHP_URL_PATH), '/'));
            if (file_exists($candidatePath)) {
                $localPath = $candidatePath;
                $mimeType = mime_content_type($candidatePath) ?: 'image/jpeg';
            }
        }

        return [
            'url' => $attachmentUrl,
            'type' => $attachmentType ?? ($attachmentUrl ? 'image' : null),
            'name' => $attachmentName,
            'local_path' => $localPath,
            'mime' => $mimeType,
        ];
    }

    /**
     * Gửi tin nhắn từ Khách hàng và kích hoạt Gemini AI phản hồi
     */
    public function send(Request $request): JsonResponse
    {
        $messageText = trim((string) ($request->input('message') ?? $request->input('content') ?? ''));
        $attachment = $this->processAttachment($request);

        if (empty($messageText) && empty($attachment['url'])) {
            return response()->json([
                'success' => false,
                'message' => 'Nội dung tin nhắn hoặc tệp đính kèm không được để trống.',
            ], 400);
        }

        $senderId = $this->resolveUserId($request);
        if (!$senderId) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để gửi tin nhắn.',
            ], 401);
        }

        $adminId = 1;
        $promptText = !empty($messageText) ? $messageText : ($attachment['type'] === 'image' ? '[Hình ảnh]' : '[Tệp đính kèm]');

        try {
            // 1. Lưu tin nhắn người dùng
            $userMessage = Message::create([
                'sender_id' => $senderId,
                'receiver_id' => $adminId,
                'content' => $promptText,
                'sender_type' => 'CUSTOMER',
                'attachment_url' => $attachment['url'],
                'attachment_type' => $attachment['type'],
                'attachment_name' => $attachment['name'],
                'is_read' => false,
            ]);

            // 2. Kích hoạt phản hồi từ Gemini AI (Vision, Cards, Timeline đơn hàng)
            $aiReply = $this->geminiService->generateReply(
                $senderId,
                $promptText,
                $attachment['local_path'],
                $attachment['mime']
            );

            $metadata = array_filter([
                'suggested_products' => !empty($aiReply['suggested_products']) ? $aiReply['suggested_products'] : null,
                'order_tracking' => $aiReply['order_tracking'] ?? null,
            ]);

            // 3. Lưu tin nhắn phản hồi của AI
            $aiMessage = Message::create([
                'sender_id' => $adminId,
                'receiver_id' => $senderId,
                'content' => $aiReply['text'] ?? 'Chào bạn! STRIKER có thể hỗ trợ gì cho bạn hôm nay?',
                'sender_type' => 'AI',
                'metadata' => !empty($metadata) ? $metadata : null,
                'is_read' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gửi tin nhắn thành công.',
                'data' => $userMessage,
                'id' => $userMessage->id,
                'sender_id' => $userMessage->sender_id,
                'receiver_id' => $userMessage->receiver_id,
                'content' => $userMessage->content,
                'sender_type' => $userMessage->sender_type,
                'attachment_url' => $userMessage->attachment_url,
                'attachment_type' => $userMessage->attachment_type,
                'attachment_name' => $userMessage->attachment_name,
                'metadata' => $userMessage->metadata,
                'is_read' => $userMessage->is_read,
                'created_at' => $userMessage->created_at,
                'ai_response' => [
                    'id' => $aiMessage->id,
                    'content' => $aiMessage->content,
                    'sender_type' => 'AI',
                    'metadata' => $aiMessage->metadata,
                    'created_at' => $aiMessage->created_at,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Lỗi gửi tin nhắn chat-service: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi tin nhắn: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy toàn bộ lịch sử tin nhắn của User với Admin/AI
     */
    public function getMessages(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để xem tin nhắn.',
                'data' => [],
            ], 401);
        }

        $adminId = 1;
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
     * Tiếp nhận đánh giá hài lòng cuộc hội thoại (CSAT ⭐)
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để đánh giá.',
            ], 401);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'message_id' => 'sometimes|nullable|integer',
            'feedback_type' => 'sometimes|nullable|string',
            'comment' => 'sometimes|nullable|string|max:500',
            'tags' => 'sometimes|nullable|array',
        ]);

        try {
            $feedback = ChatFeedback::create([
                'user_id' => $userId,
                'message_id' => $validated['message_id'] ?? null,
                'rating' => (int) $validated['rating'],
                'feedback_type' => $validated['feedback_type'] ?? 'ai',
                'comment' => $validated['comment'] ?? null,
                'tags' => $validated['tags'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cảm ơn bạn đã gửi đánh giá hài lòng!',
                'data' => $feedback,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể lưu đánh giá: ' . $e->getMessage(),
            ], 500);
        }
    }
}
