<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\AbsensiModel;
use Carbon\Carbon;

class RiwayatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user   = Auth::user();
        $siswa  = $user->siswa; // relasi User → Siswa

        if (!$siswa) {
            return response()->json(['message' => 'Data siswa tidak ditemukan.'], 404);
        }

        // ── Parse filter bulan ────────────────────────────────────────────
        $bulanParam = $request->query('bulan'); // "YYYY-MM" atau null

        if ($bulanParam && preg_match('/^\d{4}-\d{2}$/', $bulanParam)) {
            try {
                $bulan = Carbon::createFromFormat('Y-m', $bulanParam)->startOfMonth();
            } catch (\Exception $e) {
                $bulan = Carbon::now()->startOfMonth();
            }
        } else {
            $bulan = Carbon::now()->startOfMonth();
        }

        $bulanStart = $bulan->copy()->startOfMonth();
        $bulanEnd   = $bulan->copy()->endOfMonth();

        // ── Query absensi ─────────────────────────────────────────────────
        $query = AbsensiModel::with(['jadwal.mataPelajaran', 'jadwal.guru'])
            ->where('siswa_id', $siswa->id)
            ->whereBetween('tanggal', [$bulanStart->toDateString(), $bulanEnd->toDateString()])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'asc');

        // Filter status (opsional)
        $statusParam = $request->query('status');
        $allowedStatus = ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alfa'];

        if ($statusParam && in_array($statusParam, $allowedStatus)) {
            // Backend menyimpan status lowercase → normalkan
            $query->whereRaw('LOWER(status) = ?', [strtolower($statusParam)]);
        }

        $rows = $query->get();

        // ── Map ke format frontend ────────────────────────────────────────
        $data = $rows->map(function (AbsensiModel $item) {
            $jadwal  = $item->jadwal;
            $matpel  = $jadwal?->mataPelajaran?->nama
                ?? $jadwal?->mata_pelajaran
                ?? '—';
            $guru    = $jadwal?->guru?->nama_lengkap
                ?? $jadwal?->guru?->nama
                ?? '—';

            $tanggal = $item->tanggal instanceof Carbon
                ? $item->tanggal
                : Carbon::parse($item->tanggal);

            // Normalisasi status ke Title Case
            $statusMap = [
                'hadir'     => 'Hadir',
                'terlambat' => 'Terlambat',
                'izin'      => 'Izin',
                'sakit'     => 'Sakit',
                'alfa'      => 'Alfa',
            ];
            $statusNorm = $statusMap[strtolower($item->status ?? '')] ?? 'Alfa';

            return [
                'id'      => $item->id,
                'tanggal' => $tanggal->toDateString(),        // "YYYY-MM-DD"
                'bulan'   => $tanggal->format('Y-m'),         // "YYYY-MM"
                'hari'    => $this->hariIndonesia($tanggal->dayOfWeek),
                'matpel'  => $matpel,
                'guru'    => $guru,
                'status'  => $statusNorm,
                'jam'     => $item->jam_masuk
                    ? substr($item->jam_masuk, 0, 5)  // HH:mm
                    : '—',
                'ket'     => $item->keterangan ?: '—',
            ];
        });

        // ── Opsi bulan (6 bulan terakhir + bulan ini) ────────────────────
        $bulanOptions = collect();
        for ($i = 0; $i <= 5; $i++) {
            $m = Carbon::now()->subMonths($i)->startOfMonth();
            $bulanOptions->push([
                'value' => $m->format('Y-m'),
                'label' => $this->bulanIndonesia($m->month) . ' ' . $m->year,
            ]);
        }

        return response()->json([
            'data' => $data->values(),
            'meta' => [
                'bulan_options'     => $bulanOptions->values(),
                'bulan_aktif'       => $bulan->format('Y-m'),
                'tanggal_hari_ini'  => Carbon::now()->toDateString(),
                'siswa' => [
                    'id'           => $siswa->id,
                    'nama_lengkap' => $siswa->nama_lengkap ?? $siswa->nama ?? null,
                    'kelas'        => $siswa->kelas?->nama_kelas ?? null,
                    'nis'          => $siswa->nis ?? null,
                ],
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function hariIndonesia(int $dayOfWeek): string
    {
        return [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ][$dayOfWeek] ?? '—';
    }

    private function bulanIndonesia(int $month): string
    {
        return [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month] ?? '—';
    }
}
