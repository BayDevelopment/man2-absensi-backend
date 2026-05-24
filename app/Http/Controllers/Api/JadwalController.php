<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalModel;
use App\Models\SemesterModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JadwalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user()->load('siswa.kelas');

        $kelasId = $user->siswa?->kelas_id;
        $kelasNama = $user->siswa?->kelas?->nama_kelas ?? '';


        // Ambil tanggal awal dari request, kalau tidak ada pakai hari ini
        $tanggalAwal = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))
            : now();

        // Kalau hari ini Minggu, mulai dari Senin besok
        if ($tanggalAwal->isSunday()) {
            $tanggalAwal->addDay();
        }

        // Ambil 6 hari ke depan, tapi hanya Senin-Sabtu
        $tanggalList = collect();

        $cursor = $tanggalAwal->copy();

        while ($tanggalList->count() < 6) {
            if (!$cursor->isSunday()) {
                $tanggalList->push($cursor->copy());
            }

            $cursor->addDay();
        }

        $startDate = $tanggalList->first()->toDateString();
        $endDate = $tanggalList->last()->toDateString();

        // ── Ambil semester aktif ──────────────────────────────────────────────
        $semesterAktif = SemesterModel::with('tahunAjaran')
            ->where('is_active', true)
            ->latest()
            ->first();

        // ── Ambil jadwal berdasarkan tanggal spesifik ─────────────────────────
        $rows = JadwalModel::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $kelasId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('urutan')
            ->orderBy('jam_mulai')
            ->get();

        $mataPelajaranRows = $rows->where('is_break', false);

        $totalJamPelajaran = $mataPelajaranRows->count();

        $jamMulaiAwal = $mataPelajaranRows->min('jam_mulai');
        $jamSelesaiAkhir = $mataPelajaranRows->max('jam_selesai');

        $ruangUtama = $mataPelajaranRows->first()?->ruang ?? '-';

        $jadwal = [];

        foreach ($tanggalList as $tanggal) {
            $tanggalKey = $tanggal->toDateString();

            $hariRows = $rows->filter(function ($row) use ($tanggalKey) {
                return optional($row->tanggal)->toDateString() === $tanggalKey;
            });

            $hariMapelRows = $hariRows->where('is_break', false);

            $jadwal[$tanggalKey] = [
                'tanggal' => $tanggalKey,
                'hari' => $this->hariIndonesia($tanggal),

                'jam_pelajaran' => $hariMapelRows->count(),

                'jam_mulai' => $hariMapelRows->min('jam_mulai')
                    ? substr($hariMapelRows->min('jam_mulai'), 0, 5)
                    : null,

                'jam_selesai' => $hariMapelRows->max('jam_selesai')
                    ? substr($hariMapelRows->max('jam_selesai'), 0, 5)
                    : null,

                'items' => $hariRows->values()->map(fn($j) => [
                    'id' => $j->id,

                    'tanggal' => $j->tanggal?->toDateString(),
                    'hari' => $j->hari,

                    'jam' => $j->jam_mulai && $j->jam_selesai
                        ? substr($j->jam_mulai, 0, 5) . '–' . substr($j->jam_selesai, 0, 5)
                        : '-',

                    'is_break' => (bool) $j->is_break,
                    'label' => $j->label,

                    'matpel' => $j->mataPelajaran->nama ?? null,

                    'guru' => $j->guru
                        ? (($j->guru->gelar_depan ? $j->guru->gelar_depan . ' ' : '') . $j->guru->nama)
                        : null,

                    'ruang' => $j->ruang,
                ]),
            ];
        }

        return response()->json([
            'status' => 'success',

            'kelas' => $kelasNama,

            'semester' => $semesterAktif
                ? $semesterAktif->nama . ' ' . ($semesterAktif->tahunAjaran->nama ?? '')
                : null,

            'total_jam_pelajaran' => $totalJamPelajaran,

            'ruang_utama' => $ruangUtama,

            'jam_operasional' => $jamMulaiAwal && $jamSelesaiAkhir
                ? substr($jamMulaiAwal, 0, 5) . ' – ' . substr($jamSelesaiAkhir, 0, 5) . ' WIB'
                : null,

            'range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],

            'jadwal' => $jadwal,
        ]);
    }

    private function hariIndonesia($tanggal): string
    {
        return match ($tanggal->format('l')) {
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        };
    }

    public function byTanggal(Request $request, string $tanggal)
    {
        try {
            $tanggalObj = Carbon::parse($tanggal);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD.',
            ], 422);
        }

        $user = Auth::user()->load('siswa.kelas');

        $kelasId = $user->siswa?->kelas_id;
        $kelasNama = $user->siswa?->kelas?->nama_kelas ?? '';

        $rows = JadwalModel::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $kelasId)
            ->whereDate('tanggal', $tanggalObj->toDateString())
            ->orderBy('urutan')
            ->orderBy('jam_mulai')
            ->get();

        $mapelRows = $rows->where('is_break', false);

        $items = $rows->map(fn($j) => [
            'id' => $j->id,
            'tanggal' => $j->tanggal?->toDateString(),
            'hari' => $j->hari,

            'jam' => $j->jam_mulai && $j->jam_selesai
                ? substr($j->jam_mulai, 0, 5) . '–' . substr($j->jam_selesai, 0, 5)
                : '-',

            'is_break' => (bool) $j->is_break,
            'label' => $j->label,

            'matpel' => $j->mataPelajaran->nama ?? null,

            'guru' => $j->guru
                ? (($j->guru->gelar_depan ? $j->guru->gelar_depan . ' ' : '') . $j->guru->nama_lengkap)
                : null,

            'ruang' => $j->ruang,
        ]);

        return response()->json([
            'status' => 'success',
            'kelas' => $kelasNama,
            'tanggal' => $tanggalObj->toDateString(),
            'hari' => $this->hariIndonesia($tanggalObj),

            'jam_pelajaran' => $mapelRows->count(),

            'jam_mulai' => $mapelRows->min('jam_mulai')
                ? substr($mapelRows->min('jam_mulai'), 0, 5)
                : null,

            'jam_selesai' => $mapelRows->max('jam_selesai')
                ? substr($mapelRows->max('jam_selesai'), 0, 5)
                : null,

            'data' => $items,
        ]);
    }
}
