@php
    $appName = config('app.name');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f3f6fb;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px;">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0"
                       style="background:#ffffff;border-radius:16px;padding:28px;font-family:Arial,sans-serif;">
                    <tr>
                        <td style="text-align:center;">
                            <h2 style="margin:0 0 4px;color:#0a2540;">{{ $appName }}</h2>
                            <p style="margin:0 0 6px;font-size:12px;color:#64748b;letter-spacing:.04em;">MUDAH &middot; AMAN &middot; TERPERCAYA</p>
                            <p style="margin:0 0 20px;font-size:13px;color:#64748b;">Reset Password Akun Anda</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px;color:#334155;line-height:1.6;">
                            <p>Halo{{ $nama ? ', '.$nama : '' }},</p>
                            <p>Berikut adalah <strong>kode OTP</strong> untuk membuat password baru
                               pada akun Anda. Kode berlaku selama <strong>5 menit</strong>.</p>

                            <p style="text-align:center;margin:24px 0;">
                                <span style="display:inline-block;font-size:28px;font-weight:bold;letter-spacing:6px;color:#0a2540;background:#dbeafe;border-radius:10px;padding:12px 20px;">
                                    {{ $code }}
                                </span>
                            </p>

                            <p style="font-size:13px;color:#64748b;">Jika Anda tidak melakukan permintaan reset password, abaikan email ini.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center;padding-top:20px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;">
                            {{ $appName }} - Mudah, Aman, Terpercaya
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
