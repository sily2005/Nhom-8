<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán đơn hàng - MoMo & COD</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#0B0E17] text-white min-h-screen py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-black text-white tracking-tight">Thanh toán đơn hàng</h1>
            <p class="text-xs text-slate-400 mt-1">Xác nhận thông tin giao hàng & phương thức thanh toán an toàn</p>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="mb-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm">
                {{ session('warning') }}
            </div>
        @endif

        <form action="{{ route('user.payment.process') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            @csrf

            <!-- Left Column: Customer info & Payment method -->
            <div class="lg:col-span-7 space-y-6">
                <!-- 1. Customer Information -->
                <div class="bg-[#131823] border border-white/10 rounded-3xl p-6 space-y-4 shadow-xl">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-lime-400 text-slate-950 font-bold text-xs flex items-center justify-center">1</span>
                        Thông tin nhận hàng
                    </h2>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Họ và tên người nhận <span class="text-rose-400">*</span></label>
                        <input type="text" name="name" required value="{{ old('name', $user->name ?? '') }}" placeholder="Nguyễn Văn A" class="w-full bg-[#0B0E17] border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white outline-none focus:border-lime-400">
                        @error('name') <span class="text-[11px] text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Số điện thoại <span class="text-rose-400">*</span></label>
                        <input type="tel" name="phone" required value="{{ old('phone', $user->phone ?? '') }}" placeholder="0901234567" class="w-full bg-[#0B0E17] border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white outline-none focus:border-lime-400">
                        @error('phone') <span class="text-[11px] text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Địa chỉ chi tiết (Số nhà, tên đường, phường, quận, tỉnh) <span class="text-rose-400">*</span></label>
                        <textarea name="address" required rows="2" placeholder="Số 1 Đại Cồ Việt, Bách Khoa, Hai Bà Trưng, Hà Nội" class="w-full bg-[#0B0E17] border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white outline-none focus:border-lime-400">{{ old('address') }}</textarea>
                        @error('address') <span class="text-[11px] text-rose-400">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Mã Quận/Huyện GHN (ID) <span class="text-rose-400">*</span></label>
                            <input type="number" name="to_district_id" required value="{{ old('to_district_id', 1485) }}" placeholder="1485 (Cầu Giấy)" class="w-full bg-[#0B0E17] border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white outline-none focus:border-lime-400">
                            @error('to_district_id') <span class="text-[11px] text-rose-400">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Mã Phường/Xã GHN (Code) <span class="text-rose-400">*</span></label>
                            <input type="text" name="to_ward_code" required value="{{ old('to_ward_code', '1A0601') }}" placeholder="1A0601 (Dịch Vọng)" class="w-full bg-[#0B0E17] border border-white/10 rounded-xl px-3.5 py-2.5 text-xs text-white outline-none focus:border-lime-400">
                            @error('to_ward_code') <span class="text-[11px] text-rose-400">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- 2. Payment Method Selection (MoMo & COD Only) -->
                <div class="bg-[#131823] border border-white/10 rounded-3xl p-6 space-y-4 shadow-xl">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-lime-400 text-slate-950 font-bold text-xs flex items-center justify-center">2</span>
                        Phương thức thanh toán
                    </h2>

                    <div class="space-y-3">
                        <!-- MoMo Option -->
                        <label class="flex items-center justify-between p-4 rounded-2xl border border-white/10 bg-white/5 cursor-pointer hover:border-pink-500/50 transition">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="momo" class="accent-pink-500 w-4 h-4" checked>
                                <div class="w-9 h-9 rounded-xl bg-pink-500/20 border border-pink-500/40 flex items-center justify-center text-pink-400 font-black text-sm">
                                    M
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-white block">Thanh toán qua Ví MoMo / Thẻ ATM</span>
                                    <span class="text-[10px] text-slate-400">Tự động chuyển hướng tới cổng thanh toán MoMo</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-pink-500/20 text-pink-300 border border-pink-500/30">Khuyên dùng</span>
                        </label>

                        <!-- COD Option -->
                        <label class="flex items-center justify-between p-4 rounded-2xl border border-white/10 bg-white/5 cursor-pointer hover:border-lime-400/50 transition">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="cod" class="accent-lime-400 w-4 h-4">
                                <div class="w-9 h-9 rounded-xl bg-lime-400/20 border border-lime-400/40 flex items-center justify-center text-lime-400 font-bold text-sm">
                                    COD
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-white block">Thanh toán khi nhận hàng (COD)</span>
                                    <span class="text-[10px] text-slate-400">Kiểm tra hàng rồi thanh toán tiền mặt cho shipper GHN</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Right Column: Order Summary -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-[#131823] border border-white/10 rounded-3xl p-6 space-y-5 shadow-xl sticky top-6">
                    <h2 class="text-base font-bold text-white">Tóm tắt đơn hàng</h2>

                    <div class="space-y-2 border-y border-white/10 py-4 text-xs">
                        <div class="flex justify-between text-slate-400">
                            <span>Tạm tính:</span>
                            <span class="font-mono text-white font-bold">{{ number_format($subtotal ?? 0, 0, ',', '.') }}đ</span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>Phí giao hàng dự kiến:</span>
                            <span class="font-mono text-lime-300 font-bold">30.000đ</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <span class="text-sm font-bold text-white">Tổng thanh toán:</span>
                        <span class="font-mono text-xl font-black text-lime-400">{{ number_format(($subtotal ?? 0) + 30000, 0, ',', '.') }}đ</span>
                    </div>

                    <button type="submit" class="w-full py-3.5 bg-lime-400 hover:bg-lime-300 text-slate-950 font-black rounded-2xl text-xs uppercase tracking-wider transition shadow-lg shadow-lime-400/20 cursor-pointer">
                        Xác nhận đặt hàng & Thanh toán
                    </button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
