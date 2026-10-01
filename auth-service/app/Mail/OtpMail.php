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

    public $otp; // Khai báo biến lưu mã OTP

    public function __construct($otp)
    {
        $this->otp = $otp; // Nhận mã OTP khi khởi tạo
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mã xác thực tài khoản của bạn', // Tiêu đề email
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp', // Đường dẫn tới file giao diện email
        );
    }
}