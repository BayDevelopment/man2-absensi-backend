<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengaturanModel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ---------------------------
    // GET /api/login
    // Data sekolah untuk halaman login
    // ---------------------------
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

    // ---------------------------
    // POST /api/login
    // ---------------------------
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

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'token' => $token,
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'nisn'  => $user->nisn,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'roles' => [$user->role],
                ],
            ],
        ]);
    }

    // ---------------------------
    // GET /api/me
    // ---------------------------
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

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
                ],
            ],
        ]);
    }

    // ---------------------------
    // POST /api/logout
    // ---------------------------
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout',
            'data'    => null,
        ]);
    }
}
