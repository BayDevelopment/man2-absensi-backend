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
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        // Cari langsung di tabel users berdasarkan NISN
        $user = \App\Models\User::where('nisn', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'NISN tidak terdaftar dalam sistem',
            ], 401);
        }

        // Cek role harus siswa
        if ($user->role !== 'siswa') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak',
            ], 403);
        }

        // Cek password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah',
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email belum diverifikasi, silakan cek inbox atau spam',
            ], 403);
        }

        // Buat token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'nisn'  => $user->nisn,
                'roles' => [$user->role],
            ],
        ]);
    }

    /**
     * POST /api/logout
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'nisn'  => $user->nisn,
                'roles' => [$user->role],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout',
        ]);
    }
}
