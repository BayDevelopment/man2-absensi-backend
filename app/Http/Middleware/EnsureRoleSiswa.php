<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleSiswa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array('siswa', $user->roles ?? [])) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya siswa yang diperbolehkan.'
            ], 403);
        }

        return $next($request);
    }
}
