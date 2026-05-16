<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiModel;
use App\Models\JadwalModel;
use App\Models\PengumumanModel;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // GET /api/dashboard/summary
    public function summary(Request $request)
    {
        $siswa   = $request->user();
        $absensi = AbsensiModel::where('siswa_id', $siswa->id)
            ->whereYear('tanggal', Carbon::now()->year)
            ->get();

        $hadir = $absensi->whereIn('status', ['hadir', 'terlambat'])->count();
        $total = $absensi->count();

        return response()->json([
            'hadir'            => $hadir,
            'terlambat'        => $absensi->where('status', 'terlambat')->count(),
            'izin'             => $absensi->where('status', 'izin')->count(),
            'alpha'            => $absensi->where('status', 'alpha')->count(),
            'total'            => $total,
            'persentase_hadir' => $total > 0 ? round(($hadir / $total) * 100) : 0,
        ]);
    }

    // GET /api/dashboard/jadwal-hari-ini
    public function jadwalHariIni(Request $request)
    {
        $siswa    = $request->user();
        $namaHari = $this->getNamaHari(Carbon::now()->dayOfWeek);

        $jadwal = JadwalModel::with('mataPelajaran', 'guru')
            ->where('kelas_id', $siswa->kelas_id)
            ->where('hari', $namaHari)
            ->orderBy('jam_mulai')
            ->get()
            ->map(fn($j) => [
                'jam'   => $j->jam_mulai,
                'mapel' => $j->mataPelajaran?->nama ?? '-',
                'guru'  => $j->guru?->name ?? '-',
                'ruang' => $j->ruang ?? 'Kelas',
            ]);

        return response()->json($jadwal);
    }

    // GET /api/dashboard/pengumuman
    public function pengumuman()
    {
        // Cek apakah model Pengumuman & tabelnya sudah ada
        // Kalau belum, kembalikan array kosong dulu
        if (!class_exists(PengumumanModel::class)) {
            return response()->json([]);
        }

        $pengumuman = PengumumanModel::latest()
            ->take(5)
            ->get()
            ->map(fn($p) => [
                'judul'   => $p->judul,
                'isi'     => $p->isi,
                'tanggal' => Carbon::parse($p->created_at)
                    ->translatedFormat('d M Y'),
            ]);

        return response()->json($pengumuman);
    }

    // Helper hari — sama dengan AbsensiController
    private function getNamaHari(int $dayOfWeek): string
    {
        return [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ][$dayOfWeek] ?? 'Senin';
    }
}
