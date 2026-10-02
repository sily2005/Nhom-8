import { useEffect } from 'react'
import { useSearchParams, useNavigate, Link } from 'react-router-dom'
import { CheckCircle2, XCircle, ArrowRight, ShoppingBag, RotateCcw, ShieldCheck, Wallet } from 'lucide-react'
import confetti from 'canvas-confetti'
import { useApp } from '../../context/AppContext'

export function PaymentCallback() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { refreshOrders } = useApp()

  // MoMo query parameters
  const orderId = searchParams.get('orderId') || ''
  const amount = Number(searchParams.get('amount')) || 0
  const transId = searchParams.get('transId') || ''
  const resultCode = searchParams.get('resultCode') || ''
  const message = searchParams.get('message') || ''

  const isSuccess = resultCode === '0'

  useEffect(() => {
    // Refresh user order list if success
    if (isSuccess) {
      refreshOrders?.()
      try {
        confetti({
          particleCount: 80,
          spread: 70,
          origin: { y: 0.6 },
        })
      } catch {
        // ignore if canvas-confetti is not loaded
      }
    }
  }, [isSuccess, refreshOrders])

  return (
    <div className="min-h-[80vh] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-lg bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-2xl relative overflow-hidden">
        {/* Glow ambient background */}
        <div
          className={`absolute -top-24 -left-24 w-60 h-60 rounded-full blur-3xl opacity-20 pointer-events-none ${
            isSuccess ? 'bg-emerald-500' : 'bg-rose-500'
          }`}
        />
        <div
          className={`absolute -bottom-24 -right-24 w-60 h-60 rounded-full blur-3xl opacity-20 pointer-events-none ${
            isSuccess ? 'bg-lime-500' : 'bg-amber-500'
          }`}
        />

        {/* Status Header */}
        <div className="text-center relative z-10">
          <div
            className={`w-20 h-20 rounded-full mx-auto flex items-center justify-center mb-5 ${
              isSuccess
                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 ring-8 ring-emerald-500/5'
                : 'bg-rose-500/10 text-rose-400 border border-rose-500/30 ring-8 ring-rose-500/5'
            }`}
          >
            {isSuccess ? <CheckCircle2 className="w-10 h-10" /> : <XCircle className="w-10 h-10" />}
          </div>

          <span
            className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-2 border ${
              isSuccess
                ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400'
                : 'bg-rose-500/10 border-rose-500/20 text-rose-400'
            }`}
          >
            <Wallet className="w-3.5 h-3.5" />
            Cổng Thanh Toán MoMo
          </span>

          <h1 className="text-2xl sm:text-3xl font-bold text-white mb-2 tracking-tight">
            {isSuccess ? 'Thanh Toán Thành Công!' : 'Thanh Toán Không Thành Công'}
          </h1>
          <p className="text-slate-400 text-sm max-w-sm mx-auto">
            {isSuccess
              ? 'Giao dịch của bạn đã được ghi nhận và xác nhận trên hệ thống MoMo AIO Sandbox.'
              : message || 'Giao dịch bị từ chối hoặc đã bị người dùng hủy bỏ.'}
          </p>
        </div>

        {/* Transaction Summary Box */}
        <div className="mt-8 bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-3 relative z-10 text-sm">
          <div className="flex justify-between items-center pb-3 border-b border-slate-800/60">
            <span className="text-slate-400">Mã đơn hàng</span>
            <span className="font-mono font-medium text-white">{orderId || 'N/A'}</span>
          </div>

          {transId && (
            <div className="flex justify-between items-center pb-3 border-b border-slate-800/60">
              <span className="text-slate-400">Mã giao dịch MoMo</span>
              <span className="font-mono text-xs text-pink-400 bg-pink-500/10 px-2 py-0.5 rounded border border-pink-500/20">
                {transId}
              </span>
            </div>
          )}

          <div className="flex justify-between items-center pb-3 border-b border-slate-800/60">
            <span className="text-slate-400">Số tiền thanh toán</span>
            <span className="font-bold text-base text-lime-400">
              {amount > 0 ? amount.toLocaleString('vi-VN') + ' đ' : 'N/A'}
            </span>
          </div>

          <div className="flex justify-between items-center pb-3 border-b border-slate-800/60">
            <span className="text-slate-400">Phương thức</span>
            <span className="text-slate-200 font-medium flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-pink-500" />
              Ví MoMo (QR / ATM)
            </span>
          </div>

          <div className="flex justify-between items-center">
            <span className="text-slate-400">Trạng thái</span>
            <span
              className={`font-semibold ${
                isSuccess ? 'text-emerald-400' : 'text-rose-400'
              }`}
            >
              {isSuccess ? 'Đã thanh toán (Paid)' : 'Thất bại (Failed)'}
            </span>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="mt-8 space-y-3 relative z-10">
          {isSuccess ? (
            <>
              <button
                type="button"
                onClick={() => navigate('/orders')}
                className="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-gradient-to-r from-lime-500 to-emerald-500 hover:from-lime-400 hover:to-emerald-400 text-slate-950 font-bold shadow-lg shadow-lime-500/20 transition-all duration-200"
              >
                <span>Xem đơn hàng của bạn</span>
                <ArrowRight className="w-4 h-4" />
              </button>

              <Link
                to="/shop"
                className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 font-medium transition duration-200 text-sm"
              >
                <ShoppingBag className="w-4 h-4" />
                <span>Tiếp tục mua sắm</span>
              </Link>
            </>
          ) : (
            <>
              <button
                type="button"
                onClick={() => navigate('/checkout')}
                className="w-full flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-bold shadow-lg shadow-rose-500/20 transition duration-200"
              >
                <RotateCcw className="w-4 h-4" />
                <span>Thử thanh toán lại</span>
              </button>

              <Link
                to="/"
                className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-slate-800/80 hover:bg-slate-800 text-slate-300 font-medium transition duration-200 text-sm"
              >
                <span>Về trang chủ</span>
              </Link>
            </>
          )}
        </div>

        {/* Security badge */}
        <div className="mt-6 pt-4 border-t border-slate-800/60 flex items-center justify-center gap-2 text-xs text-slate-500">
          <ShieldCheck className="w-4 h-4 text-emerald-500/70" />
          <span>Giao dịch được bảo vệ và mã hóa qua MoMo AIO & HMAC-SHA256</span>
        </div>
      </div>
    </div>
  )
}
