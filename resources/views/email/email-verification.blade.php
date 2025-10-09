<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Email Verification</title>
    <style>
        body {
            background: #f4f6fb;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 480px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            padding: 32px 24px;
        }

        .title {
            color: #2563eb;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
            text-align: center;
        }

        .greeting {
            font-size: 16px;
            margin-bottom: 8px;
            color: #222;
            text-align: center;
        }

        .email-box {
            background: #f1f5f9;
            border-radius: 6px;
            padding: 10px 0;
            text-align: center;
            font-size: 15px;
            color: #2563eb;
            margin-bottom: 18px;
        }

        .verify-btn {
            display: block;
            width: 100%;
            background: #2563eb;
            color: #fff !important;
            text-decoration: none;
            padding: 12px 0;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            margin: 18px 0 10px 0;
            transition: background 0.2s;
        }

        .verify-btn:hover {
            background: #1d4ed8;
        }

        .footer {
            font-size: 13px;
            color: #888;
            text-align: center;
            margin-top: 24px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="title">Xác thực địa chỉ email của bạn</div>
        <div class="greeting">Xin chào <b>{{ $user->name ?? $user->email }}</b>,</div>
        <div style="text-align:center; margin-bottom: 8px;">Cảm ơn bạn đã đăng ký tài khoản!</div>
        <div class="email-box">{{ $user->email }}</div>
        <a class="verify-btn" target="_blank"
            href="{{ route('validEmail', ['token' => $user->remember_token]) . '?redirect=' . urlencode(url('/app/login')) }}">
            Xác thực email
        </a>
        <div style="text-align:center; margin-top: 10px; color:#444; font-size:14px;">Nếu bạn không đăng ký tài khoản,
            vui lòng bỏ qua email này.</div>
        <div class="footer">&copy; {{ date('Y') }} Manager Project. All rights reserved.</div>
    </div>
</body>

</html>
