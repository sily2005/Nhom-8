<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $temporaryPassword;
    public ?string $userName;

    public function __construct(string $temporaryPassword, ?string $userName = null)
    {
        $this->temporaryPassword = $temporaryPassword;
        $this->userName = $userName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cấp lại mật khẩu mới thành công',
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
                <h1 style="color: #4ade80; font-size: 24px; font-weight: 900; letter-spacing: -0.5px; margin: 0;">MẬT KHẨU MỚI ĐÃ ĐƯỢC CẤP</h1>
                <p style="color: #94a3b8; font-size: 14px; margin-top: 6px;">Xin chào <strong>' . $nameDisplay . '</strong>, mật khẩu của bạn đã được khởi tạo lại thành công.</p>
            </div>
            <div style="background-color: #1e293b; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0;">
                <p style="color: #94a3b8; font-size: 13px; margin: 0 0 8px 0;">Mật khẩu mới của bạn là:</p>
                <span style="font-size: 24px; font-weight: 800; letter-spacing: 2px; color: #4ade80; font-family: monospace;">' . htmlspecialchars($this->temporaryPassword) . '</span>
            </div>
            <p style="color: #cbd5e1; font-size: 13px; line-height: 1.6; text-align: center;">Vui lòng sử dụng mật khẩu trên để đăng nhập, sau đó bạn có thể đổi lại mật khẩu cá nhân trong phần <strong>Hồ sơ tài khoản</strong>.</p>
            <hr style="border: none; border-top: 1px solid #334155; margin: 24px 0;" />
            <p style="color: #64748b; font-size: 12px; text-align: center; margin: 0;">Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</p>
        </div>';
    }
}
