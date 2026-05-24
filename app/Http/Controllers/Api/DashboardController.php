<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiModel;
use App\Models\JadwalModel;
use App\Models\PengumumanModel;
use App\Models\JamSekolahModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $jadwalData = $this->getJadwalHariIniData($request);

            return response()->json([
                'success'         => true,
                'nama_siswa'      => $this->getNamaLengkap($request->user()),
                'summary'         => $this->getSummaryData($request),
                'jadwal_hari_ini' => $jadwalData['data'] ?? [],
                'debug_jadwal'    => config('app.debug') ? ($jadwalData['debug'] ?? null) : null,
                'pengumuman'      => $this->getPengumumanData() ?? [],
            ], 200);
        } catch (\Exception $e) {
            Log::error('DashboardController@index failed', [
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success'         => false,
                'message'         => 'Gagal memuat data dashboard.',
                'nama_siswa'      => $this->getNamaLengkap($request->user()),
                'summary'         => $this->emptySummary(),
                'jadwal_hari_ini' => [],
                'pengumuman'      => [],
            ], 500);
        }
    }

    public function summary(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            return response()->json($this->getSummaryData($request), 200);
        } catch (\Exception $e) {
            Log::error('DashboardController@summary failed', [
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json($this->emptySummary(), 500);
        }
    }

    public function jadwalHariIni(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $jadwalData = $this->getJadwalHariIniData($request);

            return response()->json([
                'success' => true,
                'data'    => $jadwalData['data'] ?? [],
                'debug'   => config('app.debug') ? ($jadwalData['debug'] ?? null) : null,
            ], 200);
        } catch (\Exception $e) {
            Log::error('DashboardController@jadwalHariIni failed', [
                'user_id' => $request->user()?->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data'    => [],
                'message' => 'Gagal memuat jadwal.',
            ], 500);
        }
    }

    public function pengumuman(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($this->getPengumumanData() ?? []);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function emptySummary(): array
    {
        return [
            'hadir'            => 0,
            'terlambat'        => 0,
            'izin'             => 0,
            'alpha'            => 0,
            'total'            => 0,
            'persentase_hadir' => 0,
        ];
    }

    private function getSummaryData(Request $request): array
    {
        $siswa = $request->user();

        if (!$siswa) {
            Log::warning('getSummaryData: user null saat dipanggil');
            return $this->emptySummary();
        }

        $absensi = AbsensiModel::where('siswa_id', $siswa->id)
            ->whereYear('tanggal', Carbon::now('Asia/Jakarta')->year)
            ->get();

        $hadir     = $absensi->whereIn('status', ['hadir', 'terlambat'])->count();
        $terlambat = $absensi->where('status', 'terlambat')->count();
        $izin      = $absensi->whereIn('status', ['izin', 'sakit'])->count();
        $alpha     = $absensi->where('status', 'alpha')->count();
        $total     = $absensi->count();

        return [
            'hadir'            => $hadir,
            'terlambat'        => $terlambat,
            'izin'             => $izin,
            'alpha'            => $alpha,
            'total'            => $total,
            'persentase_hadir' => $total > 0 ? round(($hadir / $total) * 100) : 0,
        ];
    }

    private function getJadwalHariIniData(Request $request): array
    {
        $user = $request->user();
        $now  = Carbon::now('Asia/Jakarta');

        Carbon::setLocale('id');
        $namaHari       = Carbon::parse($now)->translatedFormat('l');
        $tanggalHariIni = $now->toDateString();

        $kelasId = $this->getKelasIdFromUser($user);

        // Cache jam sekolah sebagai plain array (bukan Eloquent object)
        $jamSekolahArr = Cache::remember('jam_sekolah_aktif', now()->addMinutes(30), function () {
            $row = JamSekolahModel::where('aktif', true)->first();
            if (!$row) return null;
            return [
                'jam_masuk'       => $row->jam_masuk,
                'batas_terlambat' => $row->batas_terlambat,
            ];
        });

        $bukaAbsenGlobal  = $jamSekolahArr['jam_masuk']       ?? '07:00:00';
        $tutupAbsenGlobal = $jamSekolahArr['batas_terlambat'] ?? '07:15:00';

        $debug = [
            'user_id'                   => $user?->id,
            'user_name'                 => $user?->name,
            'kelas_id_dipakai'          => $kelasId,
            'hari_dicari'               => $namaHari,
            'jam_buka_sekolah_dipakai'  => $bukaAbsenGlobal,
            'jam_tutup_sekolah_dipakai' => $tutupAbsenGlobal,
        ];

        if (!$kelasId) {
            Log::warning('getJadwalHariIniData: kelas_id tidak ditemukan', ['user_id' => $user?->id]);

            return [
                'data'  => [],
                'debug' => array_merge($debug, ['error' => 'kelas_id user login tidak ditemukan']),
            ];
        }

        $absensiTable       = (new AbsensiModel())->getTable();
        $punyaKolomJadwalId = Schema::hasColumn($absensiTable, 'jadwal_id');

        $absensiPerJadwal = collect();
        if ($punyaKolomJadwalId) {
            $absensiPerJadwal = AbsensiModel::where('siswa_id', $user->id)
                ->whereDate('tanggal', $tanggalHariIni)
                ->get()
                ->keyBy('jadwal_id');
        }

        $absensiHariIni = null;
        if (!$punyaKolomJadwalId) {
            $absensiHariIni = AbsensiModel::where('siswa_id', $user->id)
                ->whereDate('tanggal', $tanggalHariIni)
                ->first();
        }

        // Cache hanya menyimpan data jadwal statis (tanpa status absensi per user)
        $cacheKey = "jadwal_{$kelasId}_{$namaHari}";

        $jadwalStatis = Cache::remember($cacheKey, now()->addHour(), function () use (
            $kelasId,
            $namaHari,
            $bukaAbsenGlobal,
            $tutupAbsenGlobal
        ) {
            $rows = JadwalModel::with(['kelas', 'mataPelajaran', 'guru'])
                ->where('kelas_id', $kelasId)
                ->where('hari', $namaHari)
                // Jangan filter is_break, supaya jadwal istirahat tetap tampil
                ->orderByRaw('COALESCE(urutan, 999)')
                ->orderBy('jam_mulai')
                ->get();

            $isBreakRow = function ($j): bool {
                return in_array($j->is_break ?? false, [true, 1, '1', 'true'], true);
            };

            // Jadwal pertama untuk absen mandiri harus mapel, bukan istirahat
            $firstMapelId = $rows
                ->first(fn($j) => !$isBreakRow($j) && !empty($j->mata_pelajaran_id))
                ?->id;

            // Fallback jika field mata_pelajaran_id tidak ada / kosong
            if (!$firstMapelId) {
                $firstMapelId = $rows
                    ->first(fn($j) => !$isBreakRow($j))
                    ?->id;
            }

            return $rows->values()->map(function ($j) use (
                $bukaAbsenGlobal,
                $tutupAbsenGlobal,
                $isBreakRow,
                $firstMapelId
            ) {
                $isBreak = $isBreakRow($j);

                $mapelNormal = $j->mataPelajaran?->nama
                    ?? $j->mataPelajaran?->name
                    ?? $j->mataPelajaran?->nama_mapel
                    ?? $j->mataPelajaran?->nama_mata_pelajaran
                    ?? null;

                $mapel = $isBreak
                    ? ($j->keterangan ?? $j->nama_jadwal ?? $mapelNormal ?? 'Istirahat')
                    : ($mapelNormal ?? '-');

                $namaGuru = $isBreak
                    ? '-'
                    : (
                        $j->guru?->nama
                        ?? $j->guru?->name
                        ?? $j->guru?->nama_guru
                        ?? '-'
                    );

                $isJadwalPertama = !$isBreak && $j->id === $firstMapelId;

                if ($isBreak) {
                    $bukaAbsen  = $j->jam_mulai;
                    $tutupAbsen = $j->jam_selesai;
                    $tipeAbsen  = 'non_absen';
                } elseif ($isJadwalPertama) {
                    $bukaAbsen  = $bukaAbsenGlobal;
                    $tutupAbsen = $tutupAbsenGlobal;
                    $tipeAbsen  = 'mandiri';
                } else {
                    $bukaAbsen  = $j->jam_mulai ?? $bukaAbsenGlobal;
                    $tutupAbsen = $j->jam_selesai ?? $tutupAbsenGlobal;
                    $tipeAbsen  = 'guru';
                }

                return [
                    'id'                => $j->id,
                    'hari'              => $j->hari,
                    'jam_mulai'         => $j->jam_mulai,
                    'jam_selesai'       => $j->jam_selesai,
                    'jam_buka_absen'    => $bukaAbsen,
                    'jam_tutup_absen'   => $tutupAbsen,
                    'is_jadwal_pertama' => $isJadwalPertama,

                    'is_break'          => $isBreak,
                    'is_istirahat'      => $isBreak,
                    'tipe_absen'        => $tipeAbsen,

                    'ruang'             => $j->ruang ?? 'Kelas',
                    'mapel'             => $mapel,
                    'nama_guru'         => $namaGuru,

                    'mata_pelajaran_id' => $j->mata_pelajaran_id ?? null,
                    'guru_id'           => $j->guru_id ?? null,

                    'mata_pelajaran'    => [
                        'id'   => $j->mataPelajaran?->id,
                        'nama' => $mapel,
                    ],
                    'guru'              => [
                        'id'   => $j->guru?->id,
                        'nama' => $namaGuru,
                    ],
                    'kelas'             => [
                        'id'   => $j->kelas?->id,
                        'nama' => $j->kelas?->nama ?? $j->kelas?->name ?? '-',
                    ],
                ];
            })->all();
        });

        // Gabungkan data statis dengan status absensi real-time per user
        $jadwal = collect($jadwalStatis)->map(function ($j) use (
            $punyaKolomJadwalId,
            $absensiPerJadwal,
            $absensiHariIni
        ) {
            $isIstirahat = ($j['is_break'] ?? false)
                || ($j['is_istirahat'] ?? false)
                || (($j['tipe_absen'] ?? null) === 'non_absen');

            // Istirahat tidak punya status absen
            if ($isIstirahat) {
                return array_merge($j, [
                    'sudah_absen'  => false,
                    'status_absen' => null,
                    'waktu_absen'  => null,
                ]);
            }

            $absensi = $punyaKolomJadwalId
                ? $absensiPerJadwal->get($j['id'])
                : $absensiHariIni;

            return array_merge($j, [
                'sudah_absen'  => $absensi !== null,
                'status_absen' => $absensi?->status,
                'waktu_absen'  => $absensi?->created_at
                    ? Carbon::parse($absensi->created_at)->format('H:i')
                    : null,
            ]);
        })->values();

        return [
            'data'  => $jadwal,
            'debug' => array_merge($debug, ['jumlah_data_dikirim' => $jadwal->count()]),
        ];
    }

    private function getKelasIdFromUser($user): ?int
    {
        if (!$user) {
            return null;
        }

        if (!empty($user->kelas_id)) {
            return (int) $user->kelas_id;
        }

        if (isset($user->siswa) && !empty($user->siswa?->kelas_id)) {
            return (int) $user->siswa->kelas_id;
        }

        if (!empty($user->nisn)) {
            foreach (['siswas', 'siswa'] as $table) {
                try {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'nisn')) {
                        $siswa = DB::table($table)->where('nisn', $user->nisn)->first();
                        if ($siswa && !empty($siswa->kelas_id)) {
                            return (int) $siswa->kelas_id;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("getKelasIdFromUser: gagal query tabel {$table} by nisn", ['error' => $e->getMessage()]);
                }
            }
        }

        if (!empty($user->id)) {
            foreach (['siswas', 'siswa'] as $table) {
                try {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                        $siswa = DB::table($table)->where('user_id', $user->id)->first();
                        if ($siswa && !empty($siswa->kelas_id)) {
                            return (int) $siswa->kelas_id;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("getKelasIdFromUser: gagal query tabel {$table} by user_id", ['error' => $e->getMessage()]);
                }
            }
        }

        return null;
    }

    private function getNamaLengkap($user): string
    {
        if (!$user) return 'Siswa';

        if (!empty($user->nama_lengkap)) {
            return $user->nama_lengkap;
        }

        if (isset($user->siswa) && !empty($user->siswa?->nama_lengkap)) {
            return $user->siswa->nama_lengkap;
        }

        foreach (['siswas', 'siswa'] as $table) {
            try {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id') && Schema::hasColumn($table, 'nama_lengkap')) {
                    $siswa = DB::table($table)->where('user_id', $user->id)->first();
                    if ($siswa && !empty($siswa->nama_lengkap)) {
                        return $siswa->nama_lengkap;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("getNamaLengkap: gagal query tabel {$table}", ['error' => $e->getMessage()]);
            }
        }

        return $user->name ?? 'Siswa';
    }

    private function getPengumumanData()
    {
        if (!class_exists(PengumumanModel::class)) {
            return [];
        }

        try {
            $today = Carbon::now('Asia/Jakarta')->toDateString();

            return PengumumanModel::query()
                ->where(function ($q) use ($today) {
                    $q->whereNull('published_at')
                        ->orWhereDate('published_at', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('expired_at')
                        ->orWhereDate('expired_at', '>', $today);
                })
                ->latest('published_at')
                ->latest('created_at')
                ->take(5)
                ->get()
                ->map(fn($p) => [
                    'id'          => $p->id,
                    'judul'       => $p->judul ?? 'Pengumuman',
                    'isi'         => $p->isi ?? null,
                    'dibuat_oleh' => $p->dibuat_oleh ?? null,
                    'tanggal'     => Carbon::parse($p->published_at ?? $p->created_at)
                        ->translatedFormat('d M Y'),
                    'expired_at'  => $p->expired_at
                        ? Carbon::parse($p->expired_at)->translatedFormat('d M Y')
                        : null,
                ])
                ->values();
        } catch (\Exception $e) {
            Log::error('getPengumumanData failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
