<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;

    public function __construct(string $otp)
    {
        $this->otp = $otp;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mã xác thực OTP đăng ký tài khoản [' . $this->otp . ']',
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
        return '
        <div style="font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; padding: 24px; background-color: #0f172a; color: #f8fafc; border-radius: 12px; border: 1px solid #1e293b;">
            <div style="text-align: center; margin-bottom: 24px;">
                <h1 style="color: #a3e635; font-size: 24px; font-weight: 900; letter-spacing: -0.5px; margin: 0;">XÁC THỰC TÀI KHOẢN</h1>
                <p style="color: #94a3b8; font-size: 14px; margin-top: 6px;">Cảm ơn bạn đã đăng ký tài khoản. Vui lòng sử dụng mã OTP dưới đây để hoàn tất kích hoạt.</p>
            </div>
            <div style="background-color: #1e293b; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0;">
                <span style="font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #a3e635; font-family: monospace;">' . htmlspecialchars($this->otp) . '</span>
            </div>
            <p style="color: #cbd5e1; font-size: 13px; line-height: 1.6; text-align: center;">Mã xác thực này có hiệu lực trong vòng <strong>10 phút</strong>. Tuyệt đối không chia sẻ mã này cho bất kỳ ai để bảo vệ tài khoản của bạn.</p>
            <hr style="border: none; border-top: 1px solid #334155; margin: 24px 0;" />
            <p style="color: #64748b; font-size: 12px; text-align: center; margin: 0;">Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
        </div>';
    }
}
