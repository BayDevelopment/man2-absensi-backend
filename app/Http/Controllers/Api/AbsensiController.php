<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiModel;
use App\Models\JadwalModel;
use App\Models\JamSekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    // GET /api/absensi/stats
    public function stats(Request $request)
    {
        $siswa   = $request->user();
        $absensi = AbsensiModel::where('siswa_id', $siswa->id)
            ->whereYear('tanggal', Carbon::now()->year)
            ->get();

        return response()->json([
            'hadir'      => $absensi->whereIn('status', ['hadir', 'terlambat'])->count(),
            'terlambat'  => $absensi->where('status', 'terlambat')->count(),
            'izin'       => $absensi->where('status', 'izin')->count(),
            'alpha'      => $absensi->where('status', 'alpha')->count(),
            'total'      => $absensi->count(),
        ]);
    }

    // GET /api/absensi/riwayat
    public function riwayat(Request $request)
    {
        $siswa   = $request->user();
        $riwayat = AbsensiModel::with('jadwal.mataPelajaran', 'jadwal.guru')
            ->where('siswa_id', $siswa->id)
            ->whereMonth('tanggal', Carbon::now()->month)
            ->orderByDesc('tanggal')
            ->get()
            ->map(fn($a) => [
                'tanggal' => Carbon::parse($a->tanggal)->translatedFormat('d M Y'),
                'mapel'   => $a->jadwal?->mataPelajaran?->nama ?? '-',
                'jam'     => $a->jadwal?->jam_mulai ?? '-',
                'status'  => $a->status,
                'jam_masuk' => $a->jam_masuk ?? '-',
            ]);

        return response()->json($riwayat);
    }

    // POST /api/absensi/check-in
    public function checkIn(Request $request)
    {
        $request->validate([
            'siswa_id'        => 'required|exists:users,id',
            'face_descriptor' => 'required|array',
        ]);

        $siswa = $request->user();
        $today = Carbon::today();

        // Pastikan siswa hanya bisa absen untuk dirinya sendiri
        if ($siswa->id !== (int) $request->siswa_id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        // Cegah duplikat absensi hari ini
        $sudahAbsen = AbsensiModel::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $today)
            ->exists();

        if ($sudahAbsen) {
            return response()->json(['message' => 'Kamu sudah absen hari ini.'], 422);
        }

        // Nama hari dalam bahasa Indonesia yang konsisten
        $namaHari = $this->getNamaHari($today->dayOfWeek);

        // Cari jadwal hari ini berdasarkan kelas siswa
        $jadwal = JadwalModel::where('hari', $namaHari)
            ->where('kelas_id', $siswa->kelas_id)
            ->orderBy('jam_mulai')
            ->first();

        // Tentukan status: hadir atau terlambat
        $jamSekolah = JadwalModel::where('aktif', true)->first();
        $jamMasuk   = now()->format('H:i:s');
        $status     = 'hadir';

        if ($jamSekolah) {
            $status = $jamMasuk <= $jamSekolah->batas_terlambat
                ? 'hadir'
                : 'terlambat';
        }

        $absensi = AbsensiModel::create([
            'siswa_id'  => $siswa->id,
            'jadwal_id' => $jadwal?->id,
            'tanggal'   => $today,
            'status'    => $status,
            'jam_masuk' => now()->format('H:i'),
        ]);

        $pesan = $status === 'terlambat'
            ? 'Absensi dicatat, namun kamu terlambat.'
            : 'Absensi berhasil dicatat.';

        return response()->json([
            'message' => $pesan,
            'status'  => $status,
            'data'    => $absensi,
        ]);
    }

    // Helper: dayOfWeek (0=Minggu, 1=Senin, ..., 6=Sabtu)
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
