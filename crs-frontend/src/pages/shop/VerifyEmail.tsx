import { motion } from 'framer-motion'
import { Mail, RotateCw, ShieldAlert } from 'lucide-react'
import confetti from 'canvas-confetti'
import { useEffect, useState, useRef } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { AuthLayout } from '../../layouts/AuthLayout'
import { resendOtp, verifyEmail } from '../../services/auth'
import { useApp } from '../../context/AppContext'
import { toast } from 'sonner'

export function VerifyEmail() {
  const [params] = useState(() => new URLSearchParams(window.location.search))
  const email = params.get('email') ?? ''
  const [otp, setOtp] = useState(['', '', '', '', '', ''])
  const [cooldown, setCooldown] = useState(60)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const otpInputsRef = useRef<(HTMLInputElement | null)[]>([])
  const { login } = useApp()
  const navigate = useNavigate()
  const value = otp.join('')

  //Tự động focus vào ô đầu tiên khi mở trang
  useEffect(() => {
    otpInputsRef.current[0]?.focus()
  }, [])

  //Bộ đếm ngược thời gian
  useEffect(() => {
    if (cooldown <= 0) return
    const timer = window.setInterval(() => setCooldown((current) => current - 1), 1000)
    return () => window.clearInterval(timer)
  }, [cooldown])

  const update = (index: number, next: string) => {
    const digit = next.slice(-1).replace(/\D/g, '')
    const copy = [...otp]
    copy[index] = digit
    setOtp(copy)
    setError('')
    if (digit && index < 5) otpInputsRef.current[index + 1]?.focus()
  }

  const paste = (event: React.ClipboardEvent) => {
    const pasted = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
    if (!pasted) return
    setOtp(Array.from({ length: 6 }, (_, index) => pasted[index] ?? ''))
    event.preventDefault()
    const nextFocus = Math.min(pasted.length, 5)
    otpInputsRef.current[nextFocus]?.focus()
  }

  const verify = async () => {
    if (value.length !== 6) {
      setError('Vui lòng nhập đủ 6 chữ số mã OTP')
      return
    }
    setLoading(true)
    setError('')
    try {
      const response = await verifyEmail(email, value)
      login(response.user, response.token)
      confetti({
        particleCount: 140,
        spread: 90,
        origin: { y: 0.65 },
        colors: ['#84cc16', '#10b981', '#38bdf8', '#ffffff'],
      })
      toast.success('🎉 Email đã được xác minh thành công! Đã đăng nhập vào hệ thống.')
      navigate('/')
    } catch (err: any) {
      const errMsg = err?.response?.data?.message || 'Mã OTP không đúng hoặc đã hết hạn.'
      setError(errMsg)
    } finally {
      setLoading(false)
    }
  }

  const resend = async () => {
    if (cooldown > 0 || !email) return
    try {
      await resendOtp(email)
      setCooldown(60)
      toast.success('Mã OTP mới đã được gửi về hòm thư của bạn.')
      setError('')
    } catch {
      toast.error('Không thể gửi lại mã OTP. Vui lòng thử lại sau.')
    }
  }

  return (
    <AuthLayout>
      <div className="space-y-6 text-center">
        {/* Header & Logo */}
        <div className="space-y-2">
          <Link to="/" className="inline-flex items-center gap-2 text-2xl font-black italic tracking-tighter text-white hover:opacity-90 transition">
            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-lime-400 text-slate-950 not-italic font-black text-lg shadow-md shadow-lime-400/30">
              S
            </span>
            STRIKER.
          </Link>

          <div className="flex items-center justify-center gap-1.5 font-mono text-[10px] font-black uppercase tracking-[0.3em] text-lime-400">
            <Mail size={13} className="animate-pulse" /> EMAIL VERIFICATION
          </div>

          <h2 className="text-2xl sm:text-3xl font-black italic tracking-tighter uppercase text-white leading-tight">
            XÁC MINH <span className="text-lime-400">TÀI KHOẢN.</span>
          </h2>
          <p className="text-xs text-slate-400 leading-relaxed max-w-xs mx-auto">
            Nhập mã OTP 6 chữ số đã được gửi tới
            <br />
            <b className="text-lime-400 font-mono text-xs">{email || 'email của bạn'}</b>
          </p>
        </div>

        {error && (
          <motion.div
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            className="flex items-center justify-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/20 p-3 text-xs font-semibold text-rose-300"
          >
            <ShieldAlert size={16} className="shrink-0" />
            <span>{error}</span>
          </motion.div>
        )}

        {/* 6 OTP Inputs */}
        <div className="flex justify-center gap-2 sm:gap-3 py-2">
          {otp.map((digit, index) => (
            <input
              id={`otp-${index}`}
              key={index}
              ref={(el) => {
                otpInputsRef.current[index] = el
              }}
              value={digit}
              onChange={(event) => update(index, event.target.value)}
              onPaste={paste}
              onKeyDown={(event) => {
                if (event.key === 'Backspace' && !digit && index > 0)
                  otpInputsRef.current[index - 1]?.focus()
              }}
              inputMode="numeric"
              maxLength={1}
              className="h-12 w-10 sm:h-14 sm:w-12 rounded-xl border border-white/10 bg-slate-900/90 text-center font-mono text-xl font-black text-lime-400 outline-none transition focus:border-lime-400 focus:ring-2 focus:ring-lime-400/30"
            />
          ))}
        </div>

        <button
          disabled={loading || value.length !== 6}
          onClick={verify}
          className="w-full flex items-center justify-center gap-2 rounded-xl bg-lime-400 py-3.5 text-xs sm:text-sm font-black uppercase tracking-wider text-slate-950 transition hover:bg-lime-300 shadow-lg shadow-lime-400/25 disabled:opacity-40"
        >
          {loading ? (
            <span className="h-4 w-4 animate-spin rounded-full border-2 border-slate-950 border-t-transparent" />
          ) : (
            'XÁC MINH NGAY →'
          )}
        </button>

        <div className="text-xs text-slate-400">
          {cooldown > 0 ? (
            <span>
              Gửi lại mã sau <b className="text-lime-400 font-mono">{cooldown}s</b>
            </span>
          ) : (
            <button
              onClick={resend}
              className="inline-flex items-center gap-1 font-bold text-lime-400 hover:underline"
            >
              <RotateCw size={12} /> Gửi lại mã OTP
            </button>
          )}
        </div>

        <div className="border-t border-white/10 pt-4">
          <Link to="/login" className="text-xs font-semibold text-slate-400 hover:text-white transition">
            ← Quay lại đăng nhập
          </Link>
        </div>
      </div>
    </AuthLayout>
  )
}