<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public ?string $userName;

    public function __construct(string $otp, ?string $userName = null)
    {
        $this->otp = $otp;
        $this->userName = $userName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mã OTP khôi phục mật khẩu [' . $this->otp . ']',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    private function buildHtml(): string
    {
        $nameDisplay = $this->userName ? htmlspecialchars($this->userName) : 'Quý khách';
        return '
        <div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; padding: 24px; background-color: #0f172a; color: #f8fafc; border-radius: 12px; border: 1px solid #1e293b;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h1 style="color: #38bdf8; font-size: 24px; font-weight: 900; letter-spacing: -0.5px; margin: 0;">KHÔI PHỤC MẬT KHẨU</h1>
                <p style="color: #94a3b8; font-size: 14px; margin-top: 6px;">Xin chào <strong>' . $nameDisplay . '</strong>, bạn vừa yêu cầu khôi phục mật khẩu đăng nhập.</p>
            </div>
            <div style="background-color: #1e293b; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0;">
                <span style="font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #38bdf8; font-family: monospace;">' . htmlspecialchars($this->otp) . '</span>
            </div>
            <p style="color: #cbd5e1; font-size: 13px; line-height: 1.6; text-align: center;">Mã xác thực này có hiệu lực trong vòng <strong>10 phút</strong>. Vui lòng không chia sẻ mã OTP này cho người khác.</p>
            <hr style="border: none; border-top: 1px solid #334155; margin: 24px 0;" />
            <p style="color: #64748b; font-size: 12px; text-align: center; margin: 0;">Nếu bạn không gửi yêu cầu đặt lại mật khẩu, vui lòng liên hệ quản trị viên ngay lập tức.</p>
        </div>';
    }
}
