<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiswaAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        // Cek apakah user sudah login via Sanctum
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.'
            ], 401);
        }

        // Opsional: pastikan role-nya siswa
        if ($request->user()->role !== 'siswa') {
            return response()->json([
                'message' => 'Akses ditolak. Hanya untuk siswa.'
            ], 403);
        }

        return $next($request);
    }
}
