<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LoginNotification;
use App\Models\PengaturanModel;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer'],
            'otp'     => ['required', 'digits:6'],
        ]);

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
                'data'    => null,
            ], 404);
        }

        $cachedOtp = cache()->get("otp_{$user->id}");

        if (!$cachedOtp || !Hash::check($request->otp, $cachedOtp)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP salah atau sudah kadaluarsa',
                'data'    => null,
            ], 401);
        }

        // OTP valid — hapus cache
        cache()->forget("otp_{$user->id}");

        // Lanjut buat token seperti biasa
        $plainTextToken = $user->createToken('auth_token')->plainTextToken;
        $tokenId        = $this->getTokenId($plainTextToken);

        UserSession::where('user_id', $user->id)->update(['is_current' => false]);

        UserSession::create([
            'user_id'        => $user->id,
            'token_id'       => $tokenId,
            'device'         => $this->detectDevice($request->userAgent()),
            'browser'        => $this->detectBrowser($request->userAgent()),
            'os'             => $this->detectOs($request->userAgent()),
            'location'       => 'Indonesia',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'is_current'     => true,
            'auto_logout'    => $user->logout_otomatis,
            'last_active_at' => now(),
        ]);

        $setting = PengaturanModel::getSetting();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token'       => $plainTextToken,
                'auto_logout' => $user->logout_otomatis,
                'user'        => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'nisn'  => $user->nisn,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'roles' => [$user->role],

                    'school_name'    => $setting?->nama_sekolah,
                    'logo_url'       => $setting?->logo
                        ? asset('storage/' . $setting->logo)
                        : null,
                    'alamat'         => $setting?->alamat,
                    'kepala_sekolah' => $setting?->kepala_sekolah,

                    'app_name' => 'Absensi Digital',
                ],
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $setting = PengaturanModel::getSetting();

        return response()->json([
            'success' => true,
            'message' => 'Data halaman login',
            'data'    => [
                'pengaturan' => $setting ? [
                    'nama_sekolah'   => $setting->nama_sekolah,
                    'logo'           => $setting->logo
                        ? asset('storage/' . $setting->logo)
                        : null,
                    'alamat'         => $setting->alamat,
                    'kepala_sekolah' => $setting->kepala_sekolah,
                ] : null,
            ],
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'nisn'     => ['required', 'digits:10'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('nisn', $request->nisn)
            ->where('role', 'siswa')
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'NISN tidak terdaftar dalam sistem',
                'data'    => null,
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah',
                'data'    => null,
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum diverifikasi, silakan cek inbox atau spam',
                'data'    => null,
            ], 403);
        }

        // ── TWO FACTOR ───────────────────────────────────────────────────────────
        $security = $user->securitySetting;

        if ($security?->two_factor) {
            $otp = rand(100000, 999999);
            cache()->put("otp_{$user->id}", Hash::make($otp), now()->addMinutes(5));
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));

            return response()->json([
                'success' => true,
                'message' => 'OTP telah dikirim ke email kamu',
                'data'    => [
                    'require_otp' => true,
                    'user_id'     => $user->id,
                ],
            ], 200);
        }

        // ── BUAT TOKEN ───────────────────────────────────────────────────────────
        $plainTextToken = $user->createToken('auth_token')->plainTextToken;
        $tokenId        = $this->getTokenId($plainTextToken);

        UserSession::where('user_id', $user->id)->update(['is_current' => false]);

        UserSession::create([
            'user_id'        => $user->id,
            'token_id'       => $tokenId,
            'device'         => $this->detectDevice($request->userAgent()),
            'browser'        => $this->detectBrowser($request->userAgent()),
            'os'             => $this->detectOs($request->userAgent()),
            'location'       => 'Indonesia',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'is_current'     => true,
            'auto_logout'    => $security?->logout_otomatis ?? false,
            'last_active_at' => now(),
        ]);

        // ── NOTIF LOGIN BARU ─────────────────────────────────────────────────────
        if ($security?->notif_login) {
            Mail::to($user->email)->send(new LoginNotification($user, $request));
        }

        $setting = PengaturanModel::getSetting();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token'       => $plainTextToken,
                'auto_logout' => $security?->logout_otomatis ?? false,
                'user'        => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'nisn'  => $user->nisn,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'roles' => [$user->role],

                    'school_name'    => $setting?->nama_sekolah,
                    'logo_url'       => $setting?->logo
                        ? asset('storage/' . $setting->logo)
                        : null,
                    'alamat'         => $setting?->alamat,
                    'kepala_sekolah' => $setting?->kepala_sekolah,

                    'app_name' => 'Absensi Digital',
                ],
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $setting = PengaturanModel::getSetting();

        return response()->json([
            'success' => true,
            'message' => 'Data user',
            'data'    => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'nisn'  => $user->nisn,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'roles' => [$user->role],

                    'school_name' => $setting?->nama_sekolah,
                    'logo_url'    => $setting?->logo
                        ? asset('storage/' . $setting->logo)
                        : null,
                    'alamat' => $setting?->alamat,
                    'kepala_sekolah' => $setting?->kepala_sekolah,

                    'app_name' => 'Absensi Digital',
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        if ($currentToken) {
            UserSession::where('user_id', $user->id)
                ->where('token_id', $currentToken->id)
                ->delete();

            $currentToken->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout',
            'data'    => null,
        ]);
    }

    private function getTokenId(string $plainTextToken): ?int
    {
        $parts = explode('|', $plainTextToken);

        return isset($parts[0]) && is_numeric($parts[0])
            ? (int) $parts[0]
            : null;
    }

    private function detectDevice(?string $userAgent): string
    {
        $ua = strtolower($userAgent ?? '');

        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'Mobile';
        }

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'Tablet';
        }

        return 'Desktop';
    }

    private function detectBrowser(?string $userAgent): string
    {
        $ua = strtolower($userAgent ?? '');

        return match (true) {
            str_contains($ua, 'edg') => 'Microsoft Edge',
            str_contains($ua, 'opr') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'chrome') => 'Chrome',
            str_contains($ua, 'firefox') => 'Firefox',
            str_contains($ua, 'safari') => 'Safari',
            default => 'Browser',
        };
    }

    private function detectOs(?string $userAgent): string
    {
        $ua = strtolower($userAgent ?? '');

        return match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') => 'iOS',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            default => 'OS',
        };
    }

    public function saveKeamanan(Request $request): JsonResponse
    {
        $request->validate([
            'two_factor'      => ['required', 'boolean'],
            'notif_login'     => ['required', 'boolean'],
            'logout_otomatis' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->update([
            'two_factor'      => $request->two_factor,
            'notif_login'     => $request->notif_login,
            'logout_otomatis' => $request->logout_otomatis,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan keamanan berhasil disimpan',
            'data'    => [
                'two_factor'      => $user->two_factor,
                'notif_login'     => $user->notif_login,
                'logout_otomatis' => $user->logout_otomatis,
            ],
        ]);
    }
    public function getKeamanan(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Data keamanan',
            'data'    => [
                'two_factor'      => (bool) $user->two_factor,
                'notif_login'     => (bool) $user->notif_login,
                'logout_otomatis' => (bool) $user->logout_otomatis,
            ],
        ]);
    }
}
