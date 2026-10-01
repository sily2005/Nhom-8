import { useState } from 'react'
import { Mail, ArrowLeft, ShieldAlert, CheckCircle2 } from 'lucide-react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { forgotPassword } from '../../services/auth'
import { toast } from 'sonner'

export function ForgotPassword() {
  const [email, setEmail] = useState('')
  const [sent, setSent] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const submit = async (event: React.FormEvent) => {
    event.preventDefault()
    if (!email.trim()) {
      setError('Vui lòng nhập địa chỉ Email của bạn')
      return
    }

    setLoading(true)
    setError('')
    try {
      await forgotPassword(email.trim())
      setSent(true)
      toast.success('Đã gửi email hướng dẫn khôi phục mật khẩu!')
    } catch (err: any) {
      const msg = err?.response?.data?.message || 'Không thể gửi yêu cầu. Vui lòng thử lại sau.'
      setError(msg)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div
      className="relative min-h-screen w-full flex items-center justify-center bg-cover bg-center bg-no-repeat py-12 px-4 selection:bg-lime-400 selection:text-slate-950 font-sans"
      style={{
        backgroundImage: "url('https://www.sport9.vn/images/thumbs/002/0023958_b%C3%B3ng-da-mon-the-thao-vua.jpeg')",
      }}
    >
      {/* Dark Backdrop Overlay */}
      <div className="absolute inset-0 bg-slate-950/75 backdrop-blur-sm pointer-events-none" />

      {/* Centered Modal Card */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4 }}
        className="relative z-10 w-full max-w-md p-8 sm:p-10 rounded-3xl bg-slate-950/80 border border-white/10 shadow-2xl backdrop-blur-2xl text-white"
      >
        <div className="space-y-6">
          {/* Header & Logo */}
          <div className="space-y-2 text-center">
            <Link to="/" className="inline-flex items-center gap-2 text-2xl font-black italic tracking-tighter text-white hover:opacity-90 transition">
              <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-lime-400 text-slate-950 not-italic font-black text-lg shadow-md shadow-lime-400/30">
                S
              </span>
              STRIKER.
            </Link>

            <h2 className="text-2xl sm:text-3xl font-black italic tracking-tighter uppercase text-white leading-tight">
              KHÔI PHỤC <span className="text-lime-400">MẬT KHẨU.</span>
            </h2>
            <p className="text-xs text-slate-400 leading-relaxed max-w-xs mx-auto">
              Nhập email liên kết với tài khoản Striker của bạn để nhận liên kết đặt lại mật khẩu.
            </p>
          </div>

          {/* Error Alert */}
          {error && (
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              className="flex items-center gap-2.5 rounded-xl border border-rose-500/30 bg-rose-500/20 p-3 text-xs font-semibold text-rose-300"
            >
              <ShieldAlert size={16} className="shrink-0" />
              <span>{error}</span>
            </motion.div>
          )}

          {sent ? (
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              className="rounded-2xl border border-lime-400/30 bg-lime-400/10 p-5 text-center space-y-3"
            >
              <div className="mx-auto grid h-12 w-12 place-items-center rounded-full bg-lime-400/20 text-lime-400">
                <CheckCircle2 size={24} />
              </div>
              <h3 className="text-sm font-bold text-white">Email đã được gửi thành công!</h3>
              <p className="text-xs text-slate-300">
                Vui lòng kiểm tra hộp thư đến (và thư rác) của <b>{email}</b> để tiến hành thiết lập mật khẩu mới.
              </p>
              <button
                type="button"
                onClick={() => setSent(false)}
                className="text-xs font-bold text-lime-400 hover:underline pt-2 block mx-auto"
              >
                Gửi lại email khác
              </button>
            </motion.div>
          ) : (
            <form onSubmit={submit} className="space-y-4">
              <div className="space-y-1.5">
                <label className="text-[10px] font-black uppercase tracking-wider text-slate-400">
                  Địa chỉ Email <span className="text-rose-400">*</span>
                </label>
                <div className="flex items-center gap-3 rounded-xl border border-white/10 bg-slate-900/90 px-3.5 py-3 transition focus-within:border-lime-400 focus-within:ring-1 focus-within:ring-lime-400/30">
                  <Mail size={16} className="text-slate-500 shrink-0" />
                  <input
                    type="email"
                    required
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="striker@gmail.com"
                    className="w-full bg-transparent text-xs text-white outline-none placeholder:text-slate-600 [&:-webkit-autofill]:[-webkit-text-fill-color:white] [&:-webkit-autofill]:[box-shadow:0_0_0px_1000px_#0f172a_inset]"
                  />
                </div>
              </div>

              <button
                type="submit"
                disabled={loading}
                className="w-full mt-2 flex items-center justify-center gap-2 rounded-xl bg-lime-400 py-3.5 text-xs sm:text-sm font-black uppercase tracking-wider text-slate-950 shadow-lg shadow-lime-400/25 transition hover:bg-lime-300 hover:shadow-lime-400/40 active:scale-[0.99] disabled:opacity-50"
              >
                {loading ? (
                  <span className="h-4 w-4 animate-spin rounded-full border-2 border-slate-950 border-t-transparent" />
                ) : (
                  <span>GỬI EMAIL KHÔI PHỤC →</span>
                )}
              </button>
            </form>
          )}

          {/* Footer */}
          <div className="pt-2 text-center text-xs text-slate-400">
            <Link
              to="/login"
              className="inline-flex items-center gap-1.5 font-bold text-slate-400 hover:text-white transition-colors"
            >
              <ArrowLeft size={14} /> Quay lại đăng nhập
            </Link>
          </div>
        </div>
      </motion.div>
    </div>
  )
}