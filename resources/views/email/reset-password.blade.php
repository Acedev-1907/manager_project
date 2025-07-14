<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reset Password</title>
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
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(36, 112, 220, 0.10), 0 1.5px 8px rgba(36, 112, 220, 0.07);
            padding: 40px 28px 32px 28px;
        }

        .title {
            color: #2563eb;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 18px;
            text-align: center;
            letter-spacing: 0.01em;
        }

        .greeting {
            font-size: 17px;
            margin-bottom: 8px;
            color: #222;
            text-align: center;
            font-weight: 500;
        }

        .info-text {
            text-align: center;
            margin-bottom: 10px;
            color: #444;
            font-size: 15px;
        }

        .reset-btn {
            display: block;
            width: 100%;
            background: #2563eb;
            color: #fff !important;
            text-decoration: none;
            padding: 15px 0;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            margin: 22px 0 12px 0;
            transition: background 0.2s;
            box-shadow: 0 2px 8px rgba(36, 112, 220, 0.10);
        }

        .reset-btn:hover {
            background: #1d4ed8;
        }

        .footer {
            font-size: 13px;
            color: #888;
            text-align: center;
            margin-top: 28px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="title">Reset your password</div>
        <div class="greeting">Hello <b>{{ $user->name ?? $user->email }}</b>,</div>
        <div class="info-text">You requested to reset your password.</div>
        <a class="reset-btn" target="_blank" href="{{ $resetUrl }}">Reset Password</a>
        <div class="info-text">If you did not request a password reset, please ignore this email.</div>
        <div class="footer">&copy; {{ date('Y') }} Manager Project. All rights reserved.</div>
    </div>
</body>

</html>
