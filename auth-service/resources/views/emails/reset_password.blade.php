<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mật khẩu mới - STRIKER SPORT</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0B0E17;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #ffffff;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 540px;
            margin: 30px auto;
            background-color: #131823;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .header {
            padding: 35px 30px 20px;
            text-align: center;
            background: linear-gradient(180deg, rgba(163, 230, 53, 0.1) 0%, rgba(19, 24, 35, 0) 100%);
        }
        .logo {
            display: inline-flex;
            align-items: center;
            font-size: 26px;
            font-weight: 900;
            font-style: italic;
            letter-spacing: 2px;
            color: #ffffff;
            text-decoration: none;
        }
        .logo-box {
            display: inline-block;
            width: 32px;
            height: 32px;
            line-height: 32px;
            text-align: center;
            background-color: #A3E635;
            color: #0B0E17;
            border-radius: 8px;
            font-style: normal;
            font-weight: 900;
            margin-right: 8px;
            vertical-align: middle;
        }
        .content {
            padding: 20px 35px 35px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            background-color: rgba(163, 230, 53, 0.15);
            color: #A3E635;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-radius: 20px;
            border: 1px solid rgba(163, 230, 53, 0.3);
            margin-bottom: 18px;
        }
        h1 {
            font-size: 22px;
            font-weight: 900;
            margin: 0 0 12px;
            color: #ffffff;
            letter-spacing: 0.5px;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: #94A3B8;
            margin: 0 0 24px;
        }
        .password-wrapper {
            margin: 28px 0;
            padding: 20px;
            background: #0B0E17;
            border: 1px solid rgba(163, 230, 53, 0.5);
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(163, 230, 53, 0.1);
        }
        .password-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748B;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .password-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 30px;
            font-weight: 900;
            color: #A3E635;
            letter-spacing: 4px;
            margin: 0;
            word-break: break-all;
        }
        .btn-login {
            display: inline-block;
            margin-top: 20px;
            padding: 14px 32px;
            background-color: #A3E635;
            color: #0B0E17;
            text-decoration: none;
            font-weight: 900;
            font-size: 13px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(163, 230, 53, 0.3);
        }
        .security-note {
            font-size: 12px;
            color: #94A3B8;
            margin-top: 24px;
            line-height: 1.6;
            background: rgba(255, 255, 255, 0.03);
            padding: 14px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .footer {
            padding: 20px 30px;
            background-color: #0B0E17;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            font-size: 12px;
            color: #64748B;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <span class="logo-box">S</span>STRIKER<span style="color: #A3E635;">.</span>
            </div>
        </div>

        <div class="content">
            <div class="badge">KHÔI PHỤC MẬT KHẨU THÀNH CÔNG</div>
            <h1>Mật Khẩu Mới Của Bạn</h1>
            <p>
                Xin chào <strong>{{ $userName }}</strong>,<br>
                Yêu cầu khôi phục mật khẩu của bạn đã được xác minh thành công. Dưới đây là mật khẩu tạm thời mới để bạn đăng nhập vào hệ thống:
            </p>

            <div class="password-wrapper">
                <div class="password-label">Mật khẩu đăng nhập mới:</div>
                <div class="password-code">{{ $temporaryPassword }}</div>
            </div>

            <a href="http://localhost:5173/login" class="btn-login">ĐĂNG NHẬP NGAY &rarr;</a>

            <div class="security-note">
                🔒 <strong>Khuyến nghị an toàn:</strong> Sau khi đăng nhập thành công bằng mật khẩu tạm này, bạn hãy vào mục <strong>Trang cá nhân &rarr; Đổi mật khẩu</strong> để cập nhật lại mật khẩu quen thuộc của mình nhé!
            </div>
        </div>

        <div class="footer">
            © 2026 STRIKER SPORTSWEAR. Tất cả quyền được bảo lưu.<br>
            Đây là email tự động từ hệ thống, vui lòng không phản hồi thư này.
        </div>
    </div>
</body>
</html>
