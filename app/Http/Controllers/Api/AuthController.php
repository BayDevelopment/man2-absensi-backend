<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengaturanModel;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
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

        /*
         * Jangan hapus semua token kalau ingin multi-device login.
         * Kalau ini aktif, perangkat lama akan otomatis logout.
         */
        // $user->tokens()->delete();

        $plainTextToken = $user->createToken('auth_token')->plainTextToken;
        $tokenId = $this->getTokenId($plainTextToken);

        UserSession::where('user_id', $user->id)->update([
            'is_current' => false,
        ]);

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
            'last_active_at' => now(),
        ]);

        $setting = PengaturanModel::getSetting();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token' => $plainTextToken,
                'user'  => [
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
}
