<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login Baru Terdeteksi</title>
</head>

<body style="margin:0; padding:0; background-color:#f0fdf4; font-family: 'Segoe UI', Arial, sans-serif;">

    <!-- Wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4; padding: 40px 16px;">
        <tr>
            <td align="center">

                <!-- Card -->
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:520px; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td
                            style="background: linear-gradient(135deg, #16a34a 0%, #15803d 60%, #14532d 100%); padding: 36px 32px; text-align:center;">
                            <!-- Shield Icon -->
                            <div
                                style="display:inline-block; background:rgba(255,255,255,0.15); border-radius:50%; width:64px; height:64px; line-height:64px; text-align:center; margin-bottom:16px;">
                                <span style="font-size:28px;">🔐</span>
                            </div>
                            <h1
                                style="margin:0; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:-0.3px;">
                                Login Baru Terdeteksi</h1>
                            <p style="margin:8px 0 0; color:rgba(255,255,255,0.8); font-size:14px;">Sistem Absensi
                                Digital · MAN 2 Kota Cilegon</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 32px;">

                            <!-- Greeting -->
                            <p style="margin:0 0 8px; color:#374151; font-size:15px;">Halo, <strong
                                    style="color:#15803d;">{{ $user->name }}</strong> 👋</p>
                            <p style="margin:0 0 24px; color:#6b7280; font-size:14px; line-height:1.6;">
                                Kami mendeteksi aktivitas login baru ke akun kamu. Berikut detail loginnya:
                            </p>

                            <!-- Info Card -->
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; overflow:hidden; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:0 16px;">

                                        <!-- Row: Waktu -->
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:14px 0; border-bottom:1px solid #dcfce7;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="width:36px; vertical-align:middle;">
                                                                <span style="font-size:18px;">🕐</span>
                                                            </td>
                                                            <td style="vertical-align:middle;">
                                                                <div
                                                                    style="font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">
                                                                    Waktu Login</div>
                                                                <div
                                                                    style="font-size:14px; color:#111827; font-weight:600; margin-top:2px;">
                                                                    {{ $waktu }}</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Row: IP -->
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:14px 0; border-bottom:1px solid #dcfce7;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="width:36px; vertical-align:middle;">
                                                                <span style="font-size:18px;">🌐</span>
                                                            </td>
                                                            <td style="vertical-align:middle;">
                                                                <div
                                                                    style="font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">
                                                                    IP Address</div>
                                                                <div
                                                                    style="font-size:14px; color:#111827; font-weight:600; margin-top:2px;">
                                                                    {{ $ip }}</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Row: Perangkat -->
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:14px 0;">
                                                    <table width="100%" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="width:36px; vertical-align:middle;">
                                                                <span style="font-size:18px;">💻</span>
                                                            </td>
                                                            <td style="vertical-align:middle;">
                                                                <div
                                                                    style="font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">
                                                                    Perangkat</div>
                                                                <div
                                                                    style="font-size:14px; color:#111827; font-weight:600; margin-top:2px;">
                                                                    {{ $device }}</div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>

                            <!-- Warning Box -->
                            <table width="100%" cellpadding="0" cellspacing="0"
                                style="background:#fef9c3; border:1px solid #fde68a; border-radius:10px; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <table cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="vertical-align:top; padding-right:10px; font-size:18px;">⚠️
                                                </td>
                                                <td style="font-size:13px; color:#92400e; line-height:1.6;">
                                                    <strong>Bukan kamu yang login?</strong><br />
                                                    Segera ganti password akun kamu dan hubungi admin sekolah untuk
                                                    mengamankan akun.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Divider -->
                            <hr style="border:none; border-top:1px solid #e5e7eb; margin:0 0 20px;" />

                            <!-- Footer note -->
                            <p style="margin:0; font-size:12px; color:#9ca3af; line-height:1.6; text-align:center;">
                                Email ini dikirim otomatis oleh sistem.<br />
                                © 2026 MAN 2 Kota Cilegon · Absensi Digital
                            </p>

                        </td>
                    </tr>

                    <!-- Footer Bar -->
                    <tr>
                        <td
                            style="background:#f9fafb; border-top:1px solid #e5e7eb; padding:16px 32px; text-align:center;">
                            <p style="margin:0; font-size:12px; color:#d1d5db;">
                                Jangan balas email ini · Dikirim secara otomatis
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- /Card -->

            </td>
        </tr>
    </table>

</body>

</html>
