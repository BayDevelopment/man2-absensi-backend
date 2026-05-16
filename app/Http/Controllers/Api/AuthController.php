<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiswaModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',   // 'email' dipakai sebagai field name dari Vue
            'password' => 'required|string',
        ]);

        $nisn = $request->email; // Vue mengirim NISN di field 'email'

        // Cari siswa berdasarkan NISN
        $siswa = SiswaModel::where('nisn', $nisn)->where('is_active', true)->first();

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'NISN tidak ditemukan',  // ← Vue baca ini untuk error NISN
            ], 401);
        }

        // Ambil user yang terhubung ke siswa
        $user = $siswa->user;

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah',  // ← Vue baca ini untuk error password
            ], 401);
        }

        // Login berhasil — return data (Sanctum cookie di-set otomatis)
        Auth::login($user, $request->boolean('remember'));

        return response()->json([
            'success' => true,
            'user'    => [
                'id'    => $user->id,
                'name'  => $siswa->nama_lengkap,
                'nisn'  => $siswa->nisn,
                'kelas' => $siswa->kelas?->nama ?? '-',
                'foto'  => $siswa->foto,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout',
        ]);
    }

    /**
     * GET /api/me — cek session masih valid
     */
    public function me(Request $request)
    {
        $user  = $request->user();
        $siswa = $user->siswa; // relasi user → siswa

        return response()->json([
            'success' => true,
            'user'    => [
                'id'    => $user->id,
                'name'  => $siswa?->nama_lengkap ?? $user->name,
                'nisn'  => $siswa?->nisn,
                'kelas' => $siswa?->kelas?->nama ?? '-',
                'foto'  => $siswa?->foto,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }
}
