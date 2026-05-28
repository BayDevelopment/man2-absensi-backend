<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background:#f0fdf4; font-family: Arial, sans-serif;">

    <div
        style="max-width:480px; margin:40px auto; background:white; border-radius:16px;
              box-shadow:0 4px 24px rgba(0,0,0,0.08); overflow:hidden;">

        {{-- Header --}}
        <div style="background:#16a34a; padding:32px; text-align:center;">
            <h1 style="color:white; margin:0; font-size:22px; letter-spacing:0.5px;">
                Absensi Digital
            </h1>
            <p style="color:rgba(255,255,255,0.8); margin:6px 0 0; font-size:13px;">
                Sistem Absensi Sekolah
            </p>
        </div>

        {{-- Body --}}
        <div style="padding:36px 32px;">
            <h2 style="color:#111; margin:0 0 8px; font-size:18px;">Kode Verifikasi OTP</h2>
            <p style="color:#555; font-size:14px; margin:0 0 28px; line-height:1.6;">
                Gunakan kode berikut untuk menyelesaikan login kamu.
                Kode berlaku selama <strong>5 menit</strong>.
            </p>

            {{-- OTP Box --}}
            <div
                style="background:#f0fdf4; border:2px dashed #16a34a; border-radius:12px;
                  padding:24px; text-align:center; margin-bottom:28px;">
                <span style="font-size:48px; font-weight:bold; letter-spacing:12px; color:#15803d;">
                    {{ $otp }}
                </span>
            </div>

            <p style="color:#888; font-size:13px; line-height:1.6; margin:0;">
                ⚠️ Jangan bagikan kode ini kepada siapapun, termasuk pihak sekolah.<br>
                Jika kamu tidak merasa melakukan login, abaikan email ini.
            </p>
        </div>

        {{-- Footer --}}
        <div
            style="background:#f9fafb; padding:20px 32px; text-align:center;
                border-top:1px solid #e5e7eb;">
            <p style="color:#aaa; font-size:12px; margin:0;">
                © {{ date('Y') }} Absensi Digital · Email otomatis, jangan dibalas.
            </p>
        </div>

    </div>

</body>

</html>
