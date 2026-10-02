<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng - MoMo & Vận chuyển GHN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#0B0E17] text-white min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight">Lịch sử đơn hàng</h1>
                <p class="text-xs text-slate-400 mt-1">Theo dõi trạng thái đơn hàng, vận chuyển GHN và thanh toán MoMo</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('user.payment.index') }}" class="px-4 py-2 bg-lime-400 hover:bg-lime-300 text-slate-950 font-bold rounded-xl text-xs transition flex items-center gap-2">
                    <span>🛒 Tạo đơn hàng mới</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        <!-- Order List -->
        <div class="space-y-4">
            @forelse($orders ?? [] as $order)
                <div class="bg-[#131823] border border-white/10 rounded-3xl p-6 shadow-xl space-y-4 transition hover:border-white/20">
                    <!-- Order Top Row -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-sm font-bold text-lime-400">#{{ $order->order_number ?? $order->order_code ?? ('ORD-'.$order->id) }}</span>
                            <span class="text-xs text-slate-400">• {{ optional($order->created_at)->format('d/m/Y H:i') ?? 'N/A' }}</span>
                        </div>

                        <!-- Status Badges -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Order Status -->
                            @php
                                $statusMap = [
                                    'pending' => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-400', 'border' => 'border-amber-500/30', 'label' => 'Chờ xử lý'],
                                    'processing' => ['bg' => 'bg-blue-500/10', 'text' => 'text-blue-400', 'border' => 'border-blue-500/30', 'label' => 'Đang xử lý'],
                                    'shipping' => ['bg' => 'bg-purple-500/10', 'text' => 'text-purple-400', 'border' => 'border-purple-500/30', 'label' => 'Đang giao hàng'],
                                    'delivered' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'border' => 'border-emerald-500/30', 'label' => 'Đã giao hàng'],
                                    'cancelled' => ['bg' => 'bg-rose-500/10', 'text' => 'text-rose-400', 'border' => 'border-rose-500/30', 'label' => 'Đã hủy'],
                                ];
                                $curStatus = $statusMap[$order->order_status ?? $order->status ?? 'pending'] ?? $statusMap['pending'];

                                $paymentStatusMap = [
                                    'paid' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'border' => 'border-emerald-500/30', 'label' => 'Đã thanh toán'],
                                    'unpaid' => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-400', 'border' => 'border-amber-500/30', 'label' => 'Chưa thanh toán'],
                                    'pending' => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-400', 'border' => 'border-amber-500/30', 'label' => 'Đang xử lý thanh toán'],
                                    'failed' => ['bg' => 'bg-rose-500/10', 'text' => 'text-rose-400', 'border' => 'border-rose-500/30', 'label' => 'Thanh toán thất bại'],
                                    'refunded' => ['bg' => 'bg-purple-500/10', 'text' => 'text-purple-400', 'border' => 'border-purple-500/30', 'label' => 'Đã hoàn tiền'],
                                ];
                                $curPayment = $paymentStatusMap[$order->payment_status ?? 'unpaid'] ?? $paymentStatusMap['unpaid'];
                            @endphp

                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $curStatus['bg'] }} {{ $curStatus['text'] }} {{ $curStatus['border'] }}">
                                {{ $curStatus['label'] }}
                            </span>

                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold border {{ $curPayment['bg'] }} {{ $curPayment['text'] }} {{ $curPayment['border'] }}">
                                {{ $curPayment['label'] }}
                            </span>
                        </div>
                    </div>

                    <!-- Order Details Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 block mb-1">Người nhận:</span>
                            <div class="font-bold text-white">{{ $order->shipping_name ?? $order->name ?? 'N/A' }}</div>
                            <div class="text-slate-300 font-mono">{{ $order->shipping_phone ?? $order->phone ?? '' }}</div>
                            <div class="text-slate-400 mt-1 line-clamp-2">{{ $order->shipping_address ?? $order->address ?? '' }}</div>
                        </div>

                        <div>
                            <span class="text-slate-400 block mb-1">Phương thức & Vận chuyển:</span>
                            <div class="flex items-center gap-2 mb-1.5">
                                @if(($order->payment_method ?? '') === 'momo')
                                    <span class="px-2 py-0.5 rounded bg-pink-500/20 text-pink-300 border border-pink-500/30 font-bold text-[11px] flex items-center gap-1">
                                        <span>Ví MoMo</span>
                                    </span>
                                @elseif(($order->payment_method ?? '') === 'cod')
                                    <span class="px-2 py-0.5 rounded bg-lime-400/20 text-lime-400 border border-lime-400/30 font-bold text-[11px]">
                                        COD (Tiền mặt)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 border border-blue-500/30 font-bold text-[11px]">
                                        {{ strtoupper($order->payment_method ?? 'Khác') }}
                                    </span>
                                @endif
                            </div>

                            @if(!empty($order->ghn_code))
                                <div class="text-[11px] text-slate-300 flex items-center gap-1">
                                    <span class="text-slate-400">Vận đơn GHN:</span>
                                    <span class="font-mono font-bold text-amber-300 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20">{{ $order->ghn_code }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col justify-between items-start md:items-end">
                            <div>
                                <span class="text-slate-400 block mb-1 md:text-right">Tổng thanh toán:</span>
                                <div class="font-mono text-lg font-black text-lime-400 md:text-right">
                                    {{ number_format($order->total_amount ?? $order->total_price ?? 0, 0, ',', '.') }}đ
                                </div>
                            </div>

                            <!-- MoMo Pay Again Action Button -->
                            @if(($order->payment_method ?? '') === 'momo' && in_array($order->payment_status ?? 'unpaid', ['unpaid', 'failed']) && ($order->order_status ?? $order->status ?? 'pending') !== 'cancelled')
                                <div class="mt-3">
                                    <a href="{{ route('user.orders.momo.pay', $order->id) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-500 hover:to-rose-500 text-white font-bold text-xs shadow-lg shadow-pink-600/20 transition transform hover:-translate-y-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                        <span>Thanh toán lại qua MoMo</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-[#131823] border border-white/10 rounded-3xl p-12 text-center shadow-xl">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/5 flex items-center justify-center text-2xl">
                        📦
                    </div>
                    <h3 class="text-base font-bold text-white mb-1">Chưa có đơn hàng nào</h3>
                    <p class="text-xs text-slate-400 mb-6">Bạn chưa thực hiện đơn đặt hàng nào trong hệ thống.</p>
                    <a href="{{ route('user.payment.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-lime-400 hover:bg-lime-300 text-slate-950 font-bold rounded-xl text-xs transition">
                        <span>Tiến hành mua sắm</span>
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>
