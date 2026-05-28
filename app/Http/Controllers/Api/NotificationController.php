<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiModel;
use App\Models\PengumumanModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // ← tambahkan ini

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user('sanctum'); // ← eksplisit guard sanctum

        // Guard: pastikan user login
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $notifications = collect();

        // ── 1. PENGUMUMAN AKTIF ──────────────────────────────
        $pengumumans = PengumumanModel::where('is_active', true)
            ->where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            })
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        foreach ($pengumumans as $p) {
            $notifications->push([
                'id'         => 'pengumuman_' . $p->id,
                'type'       => 'announcement',
                'title'      => $p->judul,
                'message'    => Str::limit(strip_tags($p->isi ?? ''), 80),
                'read'       => false,
                'created_at' => $p->published_at,
            ]);
        }

        // ── 2. STATUS ABSENSI HARI INI ───────────────────────
        $today = Carbon::today();
        $absensiHariIni = AbsensiModel::where('siswa_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($absensiHariIni) {
            $notifications->push([
                'id'         => 'absen_' . $absensiHariIni->id,
                'type'       => 'absen_success',
                'title'      => 'Absensi Berhasil',
                'message'    => 'Kamu sudah absen masuk hari ini pukul ' . $absensiHariIni->jam_masuk,
                'read'       => false,
                'created_at' => $absensiHariIni->created_at,
            ]);
        } else {
            $notifications->push([
                'id'         => 'absen_warning_' . $today->format('Ymd'),
                'type'       => 'absen_warning',
                'title'      => 'Belum Absen!',
                'message'    => 'Kamu belum melakukan absensi hari ini.',
                'read'       => false,
                'created_at' => now(),
            ]);
        }

        $sorted = $notifications->sortByDesc('created_at')->values();

        return response()->json($sorted);
    }
}
