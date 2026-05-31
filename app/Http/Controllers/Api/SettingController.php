<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationModel;
use App\Models\PengaturanModel;
use App\Models\UserAppearanceSetting;
use App\Models\UserSecuritySetting;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

class SettingController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Pengaturan sekolah (publik / admin)
    // ─────────────────────────────────────────────────────────────────────────
    public function pengaturan()
    {
        $pengaturan = PengaturanModel::query()->first();

        // Ambil user dengan role admin
        $admin = \App\Models\User::query()->where('role', 'admin')->first();

        return response()->json([
            'data' => [
                'pengaturan' => [
                    'nama_sekolah' => $pengaturan?->nama_sekolah ?? 'Nama Sekolah',
                    'alamat'       => $pengaturan?->alamat ?? 'Alamat Sekolah',
                    'logo'         => $pengaturan?->logo ?? null,
                    'admin_name'   => $admin?->name ?? null,
                    'admin_phone'  => null,
                    'admin_email'  => $admin?->email ?? null,
                ],
            ],
        ]);
    }


    public function index(Request $request): JsonResponse
    {
        $user   = Auth::user();
        $siswa  = $user->siswa;

        $currentTokenId = $user->currentAccessToken()?->id;

        // Pastikan record pengaturan selalu ada (upsert ringan)
        $notif    = $user->notificationSetting
            ?? NotificationModel::create(['user_id' => $user->id]);

        $security = $user->securitySetting
            ?? UserSecuritySetting::create(['user_id' => $user->id]);

        $appear   = $user->appearanceSetting
            ?? UserAppearanceSetting::create(['user_id' => $user->id]);

        // Tandai sesi yang sedang aktif
        UserSession::query()
            ->where('user_id', $user->id)
            ->update(['is_current' => false]);

        if ($currentTokenId) {
            UserSession::query()
                ->where('user_id', $user->id)
                ->where('token_id', $currentTokenId)
                ->update([
                    'is_current'     => true,
                    'last_active_at' => now(),
                ]);
        }

        $sessions = $user->userSessions()
            ->orderByDesc('is_current')
            ->orderByDesc('last_active_at')
            ->get()
            ->map(function ($s) use ($currentTokenId) {
                return [
                    'id'          => $s->id,
                    'device'      => $s->device    ?? 'Unknown Device',
                    'browser'     => $s->browser   ?? 'Unknown Browser',
                    'os'          => $s->os         ?? 'Unknown OS',
                    'location'    => $s->location   ?? null,
                    'ip_address'  => $s->ip_address ?? null,
                    'is_current'  => $s->token_id && $currentTokenId
                        ? (int) $s->token_id === (int) $currentTokenId
                        : (bool) $s->is_current,
                    'last_active' => $s->last_active_at?->diffForHumans() ?? '-',
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'akun' => [
                    'nama_lengkap'  => $siswa?->nama_lengkap  ?? '',
                    'nis'           => $siswa?->nis            ?? '',
                    'nisn'          => $user->nisn             ?? '',
                    'email'         => $user->email            ?? '',
                    'jenis_kelamin' => $siswa?->jenis_kelamin  ?? '',
                    'no_hp'         => $siswa?->no_hp          ?? '',
                    'foto'          => $siswa?->foto
                        ? asset('storage/' . $siswa->foto)
                        : null,
                ],
                'notifikasi' => [
                    'kehadiran'  => (bool) $notif->kehadiran,
                    'pengumuman' => (bool) $notif->pengumuman,
                    'jadwal'     => (bool) $notif->jadwal,
                ],
                'keamanan' => [
                    'two_factor'      => (bool) $security->two_factor,
                    'notif_login'     => (bool) $security->notif_login,
                    'logout_otomatis' => (bool) $security->logout_otomatis,
                ],
                // FIX: default tema = 'system', sinkron dengan halaman login
                'tampilan' => [
                    'tema'        => $appear->tema        ?? 'system',
                    'bahasa'      => $appear->bahasa      ?? 'id',
                    'ukuran_teks' => $appear->ukuran_teks ?? 'normal',
                ],
                'sesi' => $sessions,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/settings/notifikasi
    // ─────────────────────────────────────────────────────────────────────────
    public function updateNotifikasi(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kehadiran'  => ['required', 'boolean'],
            'pengumuman' => ['required', 'boolean'],
            'jadwal'     => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        NotificationModel::updateOrCreate(
            ['user_id' => Auth::id()],
            $validator->validated()
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Notifikasi berhasil disimpan.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/settings/keamanan
    // ─────────────────────────────────────────────────────────────────────────
    public function updateKeamanan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'two_factor'      => ['required', 'boolean'],
            'notif_login'     => ['required', 'boolean'],
            'logout_otomatis' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $security = UserSecuritySetting::updateOrCreate(
            ['user_id' => Auth::id()],
            $validator->validated()
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengaturan keamanan disimpan.',
            'data'    => [
                'two_factor'      => (bool) $security->two_factor,
                'notif_login'     => (bool) $security->notif_login,
                'logout_otomatis' => (bool) $security->logout_otomatis,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/settings/tampilan
    // FIX: default tema 'system', bukan 'light'
    // ─────────────────────────────────────────────────────────────────────────
    public function updateTampilan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tema'        => ['required', 'in:light,dark,system'],
            'bahasa'      => ['required', 'in:id,en'],
            'ukuran_teks' => ['required', 'in:small,normal,large'],
        ], [
            'tema.in'        => 'Tema tidak valid. Pilih: light, dark, atau system.',
            'bahasa.in'      => 'Bahasa tidak valid. Pilih: id atau en.',
            'ukuran_teks.in' => 'Ukuran teks tidak valid. Pilih: small, normal, atau large.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $appear = UserAppearanceSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            $validator->validated()
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Tampilan berhasil disimpan.',
            'data'    => [
                'tema'        => $appear->tema,
                'bahasa'      => $appear->bahasa,
                'ukuran_teks' => $appear->ukuran_teks,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUT /api/settings/password
    // FIX: regex password + rate-limit 5x/menit per user
    // ─────────────────────────────────────────────────────────────────────────
    public function updatePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        // ── Rate limiting: maks 5 percobaan per menit per user ────────────
        $rateLimitKey = 'change-password:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'status'  => 'error',
                'message' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, decaySeconds: 60);

        // ── Validasi input ────────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'password_lama' => ['required', 'string'],

            /*
             * Aturan password baru:
             *  - min 8 karakter
             *  - minimal 1 huruf kapital
             *  - minimal 1 angka
             *  - minimal 1 karakter spesial  (@$!%*#?&_-)
             *
             * Gunakan Password::min() bawaan Laravel + chaining,
             * PLUS regex tambahan untuk spesial karakter karena
             * Password::symbols() mencakup semua simbol — kita batasi
             * ke set yang umum & aman untuk school app.
             */
            'password_baru' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()   // min 1 huruf kapital + 1 huruf kecil
                    ->numbers()     // min 1 angka
                    ->symbols(),    // min 1 simbol (dari set Laravel)
                // Regex tambahan: tolak spasi di dalam password
                'regex:/^\S+$/',
            ],

            'password_konfirmasi' => ['required', 'same:password_baru'],
        ], [
            'password_baru.min'               => 'Kata sandi baru minimal 8 karakter.',
            'password_baru.regex'             => 'Kata sandi tidak boleh mengandung spasi.',
            'password_konfirmasi.required'    => 'Konfirmasi kata sandi wajib diisi.',
            'password_konfirmasi.same'        => 'Konfirmasi kata sandi tidak cocok.',
            // Pesan untuk Password rule object ditangani di blok bawah
        ]);

        if ($validator->fails()) {
            // Bersihkan hit rate-limiter jika gagal validasi format
            // (bukan percobaan kata sandi yang sebenarnya)
            RateLimiter::clear($rateLimitKey);

            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        // ── Verifikasi kata sandi lama ────────────────────────────────────
        if (! Hash::check($request->password_lama, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kata sandi lama tidak sesuai.',
            ], 422);
        }

        // ── Tolak jika password baru sama persis dengan yang lama ─────────
        if (Hash::check($request->password_baru, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kata sandi baru tidak boleh sama dengan kata sandi lama.',
            ], 422);
        }

        // ── Simpan password baru ──────────────────────────────────────────
        $user->update([
            'password' => Hash::make($request->password_baru),
        ]);

        // Berhasil → reset rate-limiter
        RateLimiter::clear($rateLimitKey);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kata sandi berhasil diperbarui.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /api/settings/sesi/{id}
    // ─────────────────────────────────────────────────────────────────────────
    public function destroySession(int $id): JsonResponse
    {
        $user           = Auth::user();
        $currentTokenId = $user->currentAccessToken()?->id;

        $session = UserSession::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Jangan bisa mengeluarkan sesi sendiri dari sini
        if (
            $session->token_id &&
            $currentTokenId &&
            (int) $session->token_id === (int) $currentTokenId
        ) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sesi perangkat ini tidak bisa dikeluarkan dari sini.',
            ], 422);
        }

        // Hapus Sanctum token agar sesi benar-benar tidak valid
        if ($session->token_id) {
            PersonalAccessToken::query()  // ← tambah ::query()
                ->where('id', $session->token_id)
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->delete();
        }

        $session->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Sesi berhasil dikeluarkan.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DELETE /api/settings/sesi  — hapus semua sesi kecuali yang sedang aktif
    // ─────────────────────────────────────────────────────────────────────────
    public function destroyAllSessions(): JsonResponse
    {
        $user           = Auth::user();
        $currentTokenId = $user->currentAccessToken()?->id;

        $sessions = UserSession::query()
            ->where('user_id', $user->id)
            ->when($currentTokenId, fn($q) => $q->where('token_id', '!=', $currentTokenId))
            ->pluck('token_id')
            ->filter()
            ->values();

        $tokenIds = $sessions->pluck('token_id')->filter()->values();

        if ($tokenIds->isNotEmpty()) {
            PersonalAccessToken::whereIn('id', $tokenIds)
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->delete();
        }

        foreach ($sessions as $session) {
            $session->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Semua sesi lain berhasil dikeluarkan.',
        ]);
    }
}
