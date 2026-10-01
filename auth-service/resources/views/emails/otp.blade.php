<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực tài khoản - STRIKER</title>
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
        .otp-wrapper {
            margin: 28px 0;
            padding: 20px;
            background: #0B0E17;
            border: 1px dashed rgba(163, 230, 53, 0.4);
            border-radius: 16px;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 900;
            color: #A3E635;
            letter-spacing: 10px;
            margin: 0;
        }
        .expiry-note {
            font-size: 12px;
            color: #64748B;
            margin-top: 10px;
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
        .footer a {
            color: #A3E635;
            text-decoration: none;
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
            <div class="badge">MÃ XÁC THỰC OTP</div>
            <h1>Xác Thực Tài Khoản Của Bạn</h1>
            <p>
                Xin chào,<br>
                Bạn vừa yêu cầu mã xác thực để kích hoạt tài khoản tại <strong>STRIKER SPORT</strong>. Vui lòng nhập mã bên dưới để hoàn tất:
            </p>

            <div class="otp-wrapper">
                <div class="otp-code">{{ $otp }}</div>
                <div class="expiry-note">⏱️ Mã có hiệu lực trong vòng <strong>10 phút</strong></div>
            </div>

            <p style="font-size: 13px; color: #64748B; margin-bottom: 0;">
                Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email hoặc liên hệ hỗ trợ để bảo vệ tài khoản.
            </p>
        </div>

        <div class="footer">
            © 2026 STRIKER SPORTSWEAR. Tất cả quyền được bảo lưu.<br>
            Đây là email tự động, vui lòng không phản hồi thư này.
        </div>
    </div>
</body>
</html>
