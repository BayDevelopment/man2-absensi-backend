<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationModel;
use App\Models\UserAppearanceSetting;
use App\Models\UserSecuritySetting;
use App\Models\UserSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $siswa = $user->siswa;

        $currentTokenId = $user->currentAccessToken()?->id;

        $notif = $user->notificationSetting
            ?? NotificationModel::create(['user_id' => $user->id]);

        $security = $user->securitySetting
            ?? UserSecuritySetting::create(['user_id' => $user->id]);

        $appear = $user->appearanceSetting
            ?? UserAppearanceSetting::create(['user_id' => $user->id]);

        UserSession::where('user_id', $user->id)->update([
            'is_current' => false,
        ]);

        if ($currentTokenId) {
            UserSession::where('user_id', $user->id)
                ->where('token_id', $currentTokenId)
                ->update([
                    'is_current' => true,
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
                    'device'      => $s->device ?? 'Unknown Device',
                    'browser'     => $s->browser ?? 'Unknown Browser',
                    'os'          => $s->os ?? 'Unknown OS',
                    'location'    => $s->location ?? null,
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
                    'nama_lengkap'  => $siswa?->nama_lengkap ?? '',
                    'nis'           => $siswa?->nis ?? '',
                    'nisn'          => $user->nisn ?? '',
                    'email'         => $user->email ?? '',
                    'jenis_kelamin' => $siswa?->jenis_kelamin ?? '',
                    'no_hp'         => $siswa?->no_hp ?? '',
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

                'tampilan' => [
                    'tema'        => $appear->tema ?? 'light',
                    'bahasa'      => $appear->bahasa ?? 'id',
                    'ukuran_teks' => $appear->ukuran_teks ?? 'normal',
                ],

                'sesi' => $sessions,
            ],
        ]);
    }

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
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        NotificationModel::updateOrCreate(
            ['user_id' => Auth::id()],
            $validator->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi berhasil disimpan',
        ]);
    }

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
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        UserSecuritySetting::updateOrCreate(
            ['user_id' => Auth::id()],
            $validator->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Keamanan berhasil disimpan',
        ]);
    }

    public function updateTampilan(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tema'        => ['required', 'in:light,dark,system'],
            'bahasa'      => ['required', 'in:id,en'],
            'ukuran_teks' => ['required', 'in:small,normal,large'],
        ], [
            'tema.in'        => 'Tema tidak valid.',
            'bahasa.in'      => 'Bahasa tidak valid.',
            'ukuran_teks.in' => 'Ukuran teks tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        UserAppearanceSetting::updateOrCreate(
            ['user_id' => Auth::id()],
            $validator->validated()
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Tampilan berhasil disimpan',
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'password_lama'       => ['required', 'string'],
            'password_baru'       => ['required', 'string', 'min:8'],
            'password_konfirmasi' => ['required', 'same:password_baru'],
        ], [
            'password_baru.min'        => 'Kata sandi baru minimal 8 karakter.',
            'password_konfirmasi.same' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->password_lama, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kata sandi lama tidak sesuai.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password_baru),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kata sandi berhasil diperbarui',
        ]);
    }

    public function destroySession(int $id): JsonResponse
    {
        $user = Auth::user();
        $currentTokenId = $user->currentAccessToken()?->id;

        $session = UserSession::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($session->token_id && $currentTokenId && (int) $session->token_id === (int) $currentTokenId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi perangkat ini tidak bisa dikeluarkan dari sini.',
            ], 422);
        }

        if ($session->token_id) {
            PersonalAccessToken::where('id', $session->token_id)
                ->where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->delete();
        }

        $session->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesi berhasil dikeluarkan',
        ]);
    }

    public function destroyAllSessions(): JsonResponse
    {
        $user = Auth::user();
        $currentTokenId = $user->currentAccessToken()?->id;

        $sessions = UserSession::where('user_id', $user->id)
            ->when($currentTokenId, function ($query) use ($currentTokenId) {
                $query->where('token_id', '!=', $currentTokenId);
            })
            ->get();

        $tokenIds = $sessions
            ->pluck('token_id')
            ->filter()
            ->values();

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
            'status' => 'success',
            'message' => 'Semua sesi lain berhasil dikeluarkan',
        ]);
    }
}
