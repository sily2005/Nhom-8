<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected string $catalogUrl;
    protected string $orderUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = (string) config('services.gemini.model', env('GEMINI_MODEL', 'gemini-3.5-flash-lite'));
        $this->catalogUrl = rtrim((string) config('services.microservices.catalog', 'http://127.0.0.1:8002'), '/');
        $this->orderUrl = rtrim((string) config('services.microservices.order', 'http://127.0.0.1:8003'), '/');
    }

    /**
     * Kiểm tra API Key có hợp lệ và không phải là placeholder
     */
    public function hasValidApiKey(): bool
    {
        $key = trim($this->apiKey);
        return !empty($key) 
            && !in_array($key, ['your_gemini_api_key_here', 'your-api-key', 'GEMINI_API_KEY']) 
            && !str_starts_with($key, 'your_') 
            && strlen($key) >= 15;
    }

    /**
     * Tự động tạo phản hồi bằng Gemini AI (Hỗ trợ Vision, Gợi ý sản phẩm, Tra cứu đơn)
     */
    public function generateReply(int $userId, string $incomingMessage, ?string $imagePath = null, ?string $imageMime = null): array
    {
        $hasImage = !empty($imagePath) && file_exists($imagePath);
        $orderTracking = $this->findOrderTracking($userId, $incomingMessage);
        $suggestedProducts = $this->findSuggestedProducts($incomingMessage);
        $dynamicContext = $this->gatherDynamicContext($userId, $incomingMessage);

        // Nếu chưa có API Key hợp lệ -> Dùng fallback offline thông minh
        if (!$this->hasValidApiKey()) {
            return [
                'text' => $this->fallbackReply($incomingMessage, $hasImage),
                'suggested_products' => $suggestedProducts,
                'order_tracking' => $orderTracking,
            ];
        }

        // Lấy 6 tin nhắn gần nhất làm ngữ cảnh hội thoại
        $history = Message::where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
            })
            ->orderByDesc('created_at')->orderByDesc('id')->limit(6)->get()->reverse();

        $contents = [];
        foreach ($history as $msg) {
            $role = (strtoupper((string)$msg->sender_type) === 'CUSTOMER' || $msg->sender_id === $userId) ? 'user' : 'model';
            $contents[] = ['role' => $role, 'parts' => [['text' => (string) $msg->content]]];
        }

        // Tạo part cho tin nhắn hiện tại kèm ảnh (nếu có)
        $currentParts = [];
        if ($hasImage) {
            try {
                $imageData = base64_encode(file_get_contents($imagePath));
                $mime = $imageMime ?: (mime_content_type($imagePath) ?: 'image/jpeg');
                $currentParts[] = ['inline_data' => ['mime_type' => $mime, 'data' => $imageData]];
            } catch (Throwable $e) {
                Log::warning("Gemini Vision image read error: " . $e->getMessage());
            }
        }

        $userPrompt = $incomingMessage;
        if ($hasImage && (empty(trim($incomingMessage)) || str_starts_with($incomingMessage, '[Đã gửi') || in_array($incomingMessage, ['[Hình ảnh]', '[Tệp đính kèm]']))) {
            $userPrompt = "Đây là ảnh sản phẩm khách hàng gửi. Hãy nhận diện thương hiệu, dòng giày, kiểu đế (TF/FG/IC), form chân và tư vấn mẫu giày phù hợp.";
        }
        $currentParts[] = ['text' => $userPrompt];
        $contents[] = ['role' => 'user', 'parts' => $currentParts];

        // System Instruction: Giới hạn nghiêm ngặt chỉ tư vấn sản phẩm thể thao STRIKER
        $systemInstruction = "Bạn là trợ lý AI chuyên biệt của cửa hàng thể thao STRIKER (chuyên giày bóng đá chính hãng, áo đấu, phụ kiện).\n"
            . "NGUYÊN TẮC GIỚI HẠN:\n"
            . "1. CHỈ TRẢ LỜI: Giày đá bóng (Nike, Adidas, Puma, Mizuno), form chân, đinh TF/FG/IC, chọn size, bảo hành 6 tháng, đổi trả 30 ngày, voucher (FREESHIP, WELCOME, STRIKER100K), tra cứu đơn hàng, showroom (123 Cầu Giấy HN, 456 Lê Văn Sỹ HCM).\n"
            . "2. TỪ CHỐI CÂU HỎI NGOÀI LỀ: Tuyệt đối không làm toán (1+1=2), không viết code, không giải bài tập, không trả lời lịch sử/chính trị/khoa học/đời sống ngoài lề. Hãy lịch sự từ chối và hướng khách về sản phẩm giày đá bóng của shop.\n"
            . "3. PHONG CÁCH: Thân thiện, lịch sự, chuẩn tiếng Việt, có emoji (⚽, 👟, 🏆, ✨), súc tích.\n"
            . "DỮ LIỆU CỬA HÀNG:\n" . $dynamicContext;

        $candidateModels = array_values(array_filter(
            array_unique([$this->model, 'gemini-3.5-flash-lite', 'gemini-3.7-flash', 'gemini-3.8-flash']),
            fn ($m) => !empty($m) && !str_contains($m, '2.5') && !str_contains($m, '1.5')
        )) ?: ['gemini-3.5-flash-lite', 'gemini-3.7-flash'];

        $timeoutSec = $hasImage ? 6 : 3;
        foreach ($candidateModels as $currentModel) {
            try {
                $res = Http::withoutVerifying()->timeout($timeoutSec)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$this->apiKey}",
                    [
                        'system_instruction' => ['parts' => [['text' => $systemInstruction]]],
                        'contents' => $contents,
                        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1000],
                    ]
                );

                if ($res->successful()) {
                    $replyText = $res->json('candidates.0.content.parts.0.text');
                    if (!empty($replyText)) {
                        if ($hasImage && empty($suggestedProducts)) {
                            $suggestedProducts = $this->findSuggestedProducts($replyText);
                        }
                        return [
                            'text' => trim($replyText),
                            'suggested_products' => $suggestedProducts,
                            'order_tracking' => $orderTracking,
                        ];
                    }
                }

                if ($res->status() === 400 && str_contains($res->body(), 'API key not valid')) {
                    break;
                }
            } catch (Throwable $e) {
                Log::warning("Gemini model {$currentModel} failed: " . $e->getMessage());
            }
        }

        return [
            'text' => $this->fallbackReply($incomingMessage, $hasImage),
            'suggested_products' => $suggestedProducts,
            'order_tracking' => $orderTracking,
        ];
    }

    /**
     * Tra cứu thông tin đơn hàng và timeline GHN khi khách hỏi
     */
    public function findOrderTracking(int $userId, string $message): ?array
    {
        $q = mb_strtolower(trim($message), 'UTF-8');
        $isOrderQuery = str_contains($q, 'đơn hàng') || str_contains($q, 'đơn của tôi') || str_contains($q, 'kiểm tra đơn')
            || str_contains($q, 'tra cứu đơn') || str_contains($q, 'mã đơn') || str_contains($q, 'vận chuyển') || str_contains($q, 'giao hàng');

        if (!$isOrderQuery) return null;

        try {
            $res = Http::withoutVerifying()->timeout(2)->get("{$this->orderUrl}/api/orders", ['user_id' => $userId]);
            $orders = $res->successful() ? ($res->json('data') ?? []) : [];
            if (empty($orders) || !is_array($orders)) return null;

            $target = $orders[0];
            if (preg_match('/#\s*(\d+)/', $q, $m) || preg_match('/đơn\s*(\d+)/i', $q, $m)) {
                $searchId = (int) $m[1];
                foreach ($orders as $ord) {
                    if ((int)$ord['id'] === $searchId || str_contains(strval($ord['order_number'] ?? ''), (string)$searchId)) {
                        $target = $ord;
                        break;
                    }
                }
            }

            $status = strtolower((string)($target['status'] ?? ($target['order_status'] ?? 'processing')));
            $statusLabels = [
                'pending' => 'Chờ xử lý', 'processing' => 'Đang xử lý', 'confirmed' => 'Đã xác nhận',
                'shipping' => 'Đang giao hàng', 'completed' => 'Giao thành công', 'delivered' => 'Giao thành công', 'cancelled' => 'Đã hủy',
            ];

            $steps = [
                ['key' => 'placed', 'title' => 'Đặt hàng thành công', 'description' => 'Đơn hàng đã ghi nhận', 'completed' => true, 'time' => date('H:i d/m/Y', strtotime($target['created_at'] ?? 'now'))],
                ['key' => 'confirmed', 'title' => 'Đã xác nhận đơn', 'description' => 'Kho đang đóng gói', 'completed' => in_array($status, ['confirmed', 'processing', 'shipping', 'completed', 'delivered']), 'active' => in_array($status, ['confirmed', 'processing'])],
                ['key' => 'shipping', 'title' => 'Đang vận chuyển (GHN)', 'description' => 'Bưu tá đang giao', 'completed' => in_array($status, ['shipping', 'completed', 'delivered']), 'active' => $status === 'shipping'],
                ['key' => 'completed', 'title' => 'Giao hàng thành công', 'description' => 'Khách đã nhận kiện', 'completed' => in_array($status, ['completed', 'delivered']), 'active' => in_array($status, ['completed', 'delivered'])],
            ];

            if ($status === 'cancelled') {
                $steps = [
                    ['key' => 'placed', 'title' => 'Đặt hàng', 'description' => 'Đơn hàng đã tạo', 'completed' => true, 'time' => date('H:i d/m/Y', strtotime($target['created_at'] ?? 'now'))],
                    ['key' => 'cancelled', 'title' => 'Đã hủy', 'description' => 'Đơn đã hủy', 'completed' => true, 'is_error' => true],
                ];
            }

            $items = array_map(fn ($it) => [
                'id' => $it['id'] ?? 0,
                'name' => $it['product_name'] ?? ($it['name'] ?? 'Giày đá bóng chính hãng STRIKER'),
                'image' => $it['product_image'] ?? ($it['image'] ?? ''),
                'price' => (float)($it['price'] ?? 0),
                'quantity' => (int)($it['quantity'] ?? 1),
                'size' => $it['size'] ?? ($it['variant'] ?? null),
            ], $target['items'] ?? []);

            return [
                'order_id' => (int)$target['id'],
                'order_number' => $target['order_number'] ?? ('#STR_' . $target['id']),
                'status' => $status,
                'status_label' => $statusLabels[$status] ?? 'Đang xử lý',
                'total_amount' => (float)($target['total_amount'] ?? 0),
                'shipping_fee' => (float)($target['shipping_fee'] ?? 0),
                'created_at' => $target['created_at'] ?? now()->toIso8601String(),
                'tracking_code' => $target['tracking_code'] ?? ('GHNVN' . str_pad((string)$target['id'], 6, '0', STR_PAD_LEFT)),
                'shipping_carrier' => 'Giao Hàng Nhanh (GHN Express)',
                'payment_method' => strtoupper($target['payment_method'] ?? 'COD'),
                'is_paid' => (bool)($target['is_paid'] ?? in_array($status, ['completed', 'delivered'])),
                'steps' => $steps,
                'items' => $items,
            ];
        } catch (Throwable $e) {
            Log::warning("Order tracking lookup error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Tự động gợi ý tối đa 3 sản phẩm phù hợp từ Catalog
     */
    public function findSuggestedProducts(string $message): array
    {
        $q = mb_strtolower(trim($message), 'UTF-8');
        if (preg_match('/^(\d+)\s*[\+\-\*\/xX]\s*(\d+)$/', $q)) return [];

        $products = Cache::remember('chat_catalog_all_products', 300, function () {
            try {
                $res = Http::withoutVerifying()->timeout(1.5)->get("{$this->catalogUrl}/api/products", ['limit' => 30]);
                return $res->successful() && is_array($res->json('data')) ? $res->json('data') : [];
            } catch (Throwable $e) {
                return [];
            }
        });

        if (empty($products)) return [];

        $matched = [];
        $isShoeQuery = str_contains($q, 'giày') || str_contains($q, 'mẫu') || str_contains($q, 'tư vấn') || str_contains($q, 'size') 
            || str_contains($q, 'nike') || str_contains($q, 'adidas') || str_contains($q, 'puma') || str_contains($q, 'mizuno');

        foreach ($products as $p) {
            $pName = mb_strtolower($p['name'] ?? '', 'UTF-8');
            $pBrand = mb_strtolower($p['brand'] ?? '', 'UTF-8');
            $pDesc = mb_strtolower($p['description'] ?? '', 'UTF-8');
            $score = 0;

            if (!empty($pBrand) && str_contains($q, $pBrand)) $score += 6;
            foreach (['mercurial', 'predator', 'phantom', 'tiempo', 'crazyfast', 'speedportal', 'future', 'morelia', 'copa'] as $kw) {
                if (str_contains($q, $kw) && (str_contains($pName, $kw) || str_contains($pDesc, $kw))) $score += 8;
            }
            if ((str_contains($q, 'cỏ nhân tạo') || str_contains($q, 'tf')) && (str_contains($pName, 'tf') || str_contains($pDesc, 'tf'))) $score += 7;
            if ((str_contains($q, 'cỏ tự nhiên') || str_contains($q, 'fg')) && (str_contains($pName, 'fg') || str_contains($pDesc, 'fg'))) $score += 7;
            if ($isShoeQuery && $score === 0 && in_array(strtolower($p['tag'] ?? ''), ['best seller', 'hot'])) $score += 2;

            if ($score > 0) {
                $img = $p['image_url'] ?? ($p['image'] ?? ($p['images'][0] ?? ''));
                $matched[] = [
                    'score' => $score,
                    'product' => [
                        'id' => (int) $p['id'],
                        'name' => $p['name'],
                        'brand' => $p['brand'] ?? 'STRIKER',
                        'tag' => $p['tag'] ?? null,
                        'price' => (float) ($p['price'] ?? 0),
                        'old_price' => !empty($p['old_price']) ? (float) $p['old_price'] : null,
                        'image' => $img,
                        'slug' => $p['slug'] ?? ('product-' . $p['id']),
                    ],
                ];
            }
        }

        if (empty($matched)) return [];
        usort($matched, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_column(array_slice($matched, 0, 3), 'product');
    }

    /**
     * Thu thập ngữ cảnh động (Cửa hàng, Voucher, Danh mục, Đơn hàng)
     */
    protected function gatherDynamicContext(int $userId, string $message): string
    {
        $q = mb_strtolower(trim($message), 'UTF-8');

        $baseContext = Cache::remember('chat_store_catalog_cache', 300, function () {
            $ctx = "[THÔNG TIN CỬA HÀNG STRIKER]\n"
                . "- Showroom: 123 Cầu Giấy, Hà Nội | 456 Lê Văn Sỹ, Q.3, TP.HCM | Hotline: 0909.999.999\n"
                . "- Chính sách: Đổi size miễn phí 30 ngày, bảo hành keo/chỉ 6 tháng, giao hàng GHN 2-4 ngày, thanh toán COD/MoMo\n";

            try {
                $catRes = Http::withoutVerifying()->timeout(1.5)->get("{$this->catalogUrl}/api/categories");
                if ($catRes->successful() && !empty($catRes->json('data'))) {
                    $ctx .= "- Danh mục: " . implode(', ', array_column($catRes->json('data'), 'name')) . "\n";
                }
                $brandRes = Http::withoutVerifying()->timeout(1.5)->get("{$this->catalogUrl}/api/brands");
                if ($brandRes->successful() && !empty($brandRes->json('data'))) {
                    $ctx .= "- Thương hiệu: " . implode(', ', array_column($brandRes->json('data'), 'name')) . "\n";
                }
                $couponRes = Http::withoutVerifying()->timeout(1.5)->get("{$this->orderUrl}/api/coupons");
                if ($couponRes->successful() && !empty($couponRes->json('data'))) {
                    $ctx .= "- Voucher đang có:\n";
                    foreach ($couponRes->json('data') as $c) {
                        $ctx .= "  + {$c['code']}: {$c['title']} ({$c['description']})\n";
                    }
                }
            } catch (Throwable $e) {}

            return $ctx;
        });

        $context = $baseContext;
        if (str_contains($q, 'đơn hàng') || str_contains($q, 'đơn của tôi') || str_contains($q, 'giao hàng')) {
            try {
                $orderRes = Http::withoutVerifying()->timeout(1.5)->get("{$this->orderUrl}/api/orders", ['user_id' => $userId]);
                if ($orderRes->successful() && !empty($orderRes->json('data'))) {
                    $context .= "\n- Lịch sử đơn hàng của User #{$userId}:\n";
                    foreach (array_slice($orderRes->json('data'), 0, 3) as $ord) {
                        $code = $ord['order_number'] ?? ('#' . $ord['id']);
                        $status = $ord['status'] ?? 'processing';
                        $total = isset($ord['total_amount']) ? number_format($ord['total_amount']) . 'đ' : '';
                        $context .= "  + Đơn {$code}: Trạng thái '{$status}', Tổng tiền {$total}\n";
                    }
                }
            } catch (Throwable $e) {}
        }

        return $context;
    }

    /**
     * Phản hồi Offline dự phòng thông minh (Strict domain STRIKER)
     */
    protected function fallbackReply(string $message, bool $hasImage = false): string
    {
        if ($hasImage) {
            return "Mình đã nhận được ảnh giày bạn gửi! 📸✨\n\n"
                . "Đây là mẫu giày bóng đá chính hãng với thiết kế hiện đại, hỗ trợ kiểm soát bóng và bứt tốc tuyệt vời. STRIKER có sẵn các dòng tương tự (Nike Mercurial, Adidas Predator, Puma Future) đủ đinh TF/FG.\n\n"
                . "Bạn có muốn shop tư vấn thêm về size giày hay mặt sân không ạ? ⚽👟";
        }

        $q = mb_strtolower(trim($message), 'UTF-8');

        // 1. Từ chối câu hỏi ngoài lề (Toán học, lập trình, văn thơ, lịch sử...)
        $isOffTopic = preg_match('/^(\d+)\s*[\+\-\*\/xX]\s*(\d+)/', $q)
            || str_contains($q, 'toán') || str_contains($q, 'phương trình')
            || str_contains($q, 'lập trình') || str_contains($q, 'code')
            || str_contains($q, 'viết văn') || str_contains($q, 'làm thơ')
            || str_contains($q, 'lịch sử') || str_contains($q, 'chính trị') || str_contains($q, 'triết học');

        if ($isOffTopic) {
            return "Dạ, em là trợ lý AI chuyên tư vấn giày bóng đá chính hãng và đơn hàng tại hệ thống STRIKER ⚽. Em chỉ hỗ trợ thông tin về sản phẩm, chọn size, voucher và đơn hàng của shop thôi ạ. Bạn có cần em tư vấn mẫu giày nào không ạ? 😊👟";
        }

        // 2. Tra cứu đơn hàng
        if (str_contains($q, 'đơn hàng') || str_contains($q, 'đơn của tôi') || str_contains($q, 'kiểm tra đơn') || str_contains($q, 'tra cứu đơn')) {
            return "Dưới đây là thẻ trạng thái tiến trình giao hàng chi tiết cho đơn hàng của bạn! Bạn có thể theo dõi trực tiếp các mốc vận chuyển GHN ngay trên thẻ nhé. 📦🚚";
        }

        // 3. Địa chỉ showroom & Hotline
        if (str_contains($q, 'cửa hàng') || str_contains($q, 'shop ở đâu') || str_contains($q, 'địa chỉ') || str_contains($q, 'hotline')) {
            return "Showroom STRIKER hiện có 2 chi nhánh:\n📍 Hà Nội: 123 Cầu Giấy, Q. Cầu Giấy\n📍 TP.HCM: 456 Lê Văn Sỹ, Q.3\n📞 Hotline: 0909.999.999 (8:00 - 22:00 hàng ngày)\nMời bạn ghé shop để thử size giày trực tiếp ạ!";
        }

        // 4. Voucher khuyến mãi
        if (str_contains($q, 'voucher') || str_contains($q, 'mã giảm giá') || str_contains($q, 'khuyến mãi') || str_contains($q, 'ưu đãi')) {
            return "Hiện tại STRIKER đang có các ưu đãi cực hot:\n🎟️ FREESHIP: Miễn phí vận chuyển toàn quốc\n🎟️ WELCOME: Giảm 15% cho đơn đầu tiên\n🎟️ STRIKER100K: Giảm 100.000đ cho đơn từ 500k\nBạn có thể áp dụng trực tiếp tại bước thanh toán giỏ hàng nhé!";
        }

        // 5. Tư vấn size giày & đổi trả
        if (str_contains($q, 'size') || str_contains($q, 'chân') || str_contains($q, 'đổi trả') || str_contains($q, 'bảo hành')) {
            return "Hướng dẫn chọn size giày bóng đá tại STRIKER:\n1. Đo chiều dài bàn chân từ gót đến ngón dài nhất (cm).\n2. Nếu chân thon chọn đúng size cm, nếu chân bè tăng thêm 0.5-1 size.\n3. STRIKER hỗ trợ đổi size miễn phí trong 30 ngày và bảo hành keo/chỉ 6 tháng ạ!";
        }

        // 6. Tư vấn giày đá bóng
        if (str_contains($q, 'giày') || str_contains($q, 'nike') || str_contains($q, 'adidas') || str_contains($q, 'puma') || str_contains($q, 'mizuno') || str_contains($q, 'tf') || str_contains($q, 'fg')) {
            return "STRIKER hiện có sẵn đầy đủ các mẫu giày bóng đá chính hãng hot nhất (Nike Phantom/Mercurial, Adidas Predator, Mizuno Morelia, Puma Future) với đinh TF cỏ nhân tạo & FG cỏ tự nhiên.\n\nBạn đang tìm giày cho chân thon hay chân bè, đá mặt sân nào để mình tư vấn mẫu chuẩn nhất nhé! ⚽👟";
        }

        return "Chào bạn! Mình là trợ lý AI chuyên biệt của STRIKER ⚽. Mình chuyên tư vấn giày bóng đá chính hãng, nhận diện ảnh giày, hướng dẫn chọn size, cung cấp voucher khuyến mãi và tra cứu đơn hàng của shop. Bạn cần STRIKER hỗ trợ thông tin gì ạ? 👟✨";
    }
}
