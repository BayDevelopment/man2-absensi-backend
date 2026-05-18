<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiModel;
use App\Models\JadwalModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $siswa = $user?->siswa()->first();

        if (!$siswa) {
            return response()->json([
                'message' => 'Data siswa tidak ditemukan untuk user ini.'
            ], 404);
        }

        $absensi = AbsensiModel::with([
            'siswa',
            'kelas',
            'jadwal.mataPelajaran',
            'jadwal.guru',
        ])
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $absensi
        ]);
    }

    public function absenMasuk(Request $request)
    {
        $validated = $request->validate([
            'jadwal_id' => 'required|exists:jadwals,id',
            'kelas_id'  => 'nullable|exists:kelas,id',
        ]);

        $user  = $request->user();
        $siswa = $user?->siswa()->first();

        if (!$siswa) {
            return response()->json(['message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $jadwal  = JadwalModel::find($validated['jadwal_id']);
        $kelasId = $validated['kelas_id'] ?? $siswa->kelas_id ?? null;

        if (!$jadwal) {
            return response()->json(['message' => 'Jadwal tidak ditemukan.'], 404);
        }

        if ($kelasId && isset($jadwal->kelas_id) && $jadwal->kelas_id != $kelasId) {
            return response()->json(['message' => 'Jadwal ini tidak sesuai dengan kelas kamu.'], 403);
        }

        $today = now()->toDateString();
        $now   = now();

        return DB::transaction(function () use ($user, $siswa, $jadwal, $kelasId, $today, $now) {
            $absensi = AbsensiModel::where('siswa_id', $siswa->id)
                ->where('jadwal_id', $jadwal->id)
                ->whereDate('tanggal', $today)
                ->lockForUpdate()
                ->first();

            if ($absensi && in_array($absensi->status, ['izin', 'sakit'])) {
                return response()->json([
                    'message' => 'Kamu sudah tercatat ' . $absensi->status . ' untuk jadwal ini.'
                ], 422);
            }

            if ($absensi && $absensi->jam_masuk) {
                return response()->json([
                    'message' => 'Kamu sudah melakukan absen masuk untuk jadwal ini.'
                ], 422);
            }

            $jamMulai      = $jadwal->jam_mulai ?? null;
            $batasTerlambat = $jamMulai
                ? Carbon::parse($today . ' ' . $jamMulai)->addMinutes(15)
                : Carbon::parse($today . ' 07:15:00');

            $status = $now->greaterThan($batasTerlambat) ? 'terlambat' : 'hadir';

            $absensi = AbsensiModel::updateOrCreate(
                [
                    'siswa_id'  => $siswa->id,
                    'jadwal_id' => $jadwal->id,
                    'tanggal'   => $today,
                ],
                [
                    'kelas_id'    => $kelasId,
                    'jam_masuk'   => $now->format('H:i:s'),
                    'jam_keluar'  => null,
                    'status'      => $status,
                    'keterangan'  => null,
                    'dicatat_oleh' => $user->id,
                ]
            );

            return response()->json([
                'message' => $status === 'terlambat'
                    ? 'Absen masuk berhasil, tetapi kamu terlambat.'
                    : 'Absen masuk berhasil.',
                'data' => $absensi
            ]);
        });
    }

    public function absenKeluar(Request $request)
    {
        $validated = $request->validate([
            'jadwal_id' => 'required|exists:jadwals,id',
        ]);

        $user  = $request->user();
        $siswa = $user?->siswa()->first();

        if (!$siswa) {
            return response()->json(['message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $today = now()->toDateString();

        return DB::transaction(function () use ($user, $siswa, $validated, $today) {
            $absensi = AbsensiModel::where('siswa_id', $siswa->id)
                ->where('jadwal_id', $validated['jadwal_id'])
                ->whereDate('tanggal', $today)
                ->lockForUpdate()
                ->first();

            if (!$absensi || !$absensi->jam_masuk) {
                return response()->json(['message' => 'Kamu belum melakukan absen masuk.'], 422);
            }

            if (in_array($absensi->status, ['izin', 'sakit', 'alfa'])) {
                return response()->json([
                    'message' => 'Status absensi ini tidak bisa melakukan absen keluar.'
                ], 422);
            }

            if ($absensi->jam_keluar) {
                return response()->json(['message' => 'Kamu sudah melakukan absen keluar.'], 422);
            }

            $absensi->update([
                'jam_keluar'   => now()->format('H:i:s'),
                // ✅ FIX: catat siapa yang melakukan absen keluar
                'dicatat_oleh' => $user->id,
            ]);

            return response()->json([
                'message' => 'Absen keluar berhasil.',
                'data'    => $absensi
            ]);
        });
    }

    public function simpanStatus(Request $request)
    {
        $validated = $request->validate([
            // ✅ FIX: jadwal_id wajib diisi agar unique constraint (siswa_id, jadwal_id, tanggal) bekerja
            //    tanpa jadwal_id, MySQL izinkan duplicate NULL → izin bisa dikirim berkali-kali
            'jadwal_id'         => 'required|exists:jadwals,id',
            'kelas_id'          => 'nullable|exists:kelas,id',
            'tanggal'           => 'required|date',

            // ✅ FIX: alfa dihapus dari sini — alfa TIDAK diajukan siswa sendiri,
            //    alfa di-set otomatis oleh sistem/guru jika siswa tidak hadir
            'status'            => 'required|in:izin,sakit',

            'keterangan'        => 'nullable|string|max:1000|required_if:status,sakit',
            'dokumen_pendukung' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048|required_if:status,sakit',
        ]);

        $user  = $request->user();
        $siswa = $user?->siswa()->first();

        if (!$siswa) {
            return response()->json(['message' => 'Data siswa tidak ditemukan.'], 404);
        }

        $tanggal = $validated['tanggal'];
        $status  = $validated['status'];
        $jadwalId = $validated['jadwal_id'];
        $kelasId  = $validated['kelas_id'] ?? $siswa->kelas_id ?? null;

        // ✅ FIX: Batasi pengajuan izin maksimal H-1 s/d H+1
        //    Siswa tidak bisa izin untuk tanggal yang terlalu jauh ke belakang
        $tglCarbon = Carbon::parse($tanggal);
        if ($tglCarbon->lt(now()->subDay()) || $tglCarbon->gt(now()->addDay())) {
            return response()->json([
                'message' => 'Pengajuan izin/sakit hanya bisa untuk tanggal kemarin, hari ini, atau besok.'
            ], 422);
        }

        $jadwal = JadwalModel::find($jadwalId);

        if (!$jadwal) {
            return response()->json(['message' => 'Jadwal tidak ditemukan.'], 404);
        }

        if ($kelasId && isset($jadwal->kelas_id) && $jadwal->kelas_id != $kelasId) {
            return response()->json(['message' => 'Jadwal ini tidak sesuai dengan kelas kamu.'], 403);
        }

        // ✅ FIX: Upload file SEBELUM transaksi DB dimulai
        //    Jika file diupload di dalam transaksi dan DB gagal,
        //    file sudah tersimpan tapi record tidak ada → orphan file
        $path = null;

        if ($request->hasFile('dokumen_pendukung')) {
            $path = $request->file('dokumen_pendukung')
                ->store('dokumen-absensi', 'public');
        }

        try {
            return DB::transaction(function () use (
                $request,
                $user,
                $siswa,
                $tanggal,
                $status,
                $kelasId,
                $jadwalId,
                $path
            ) {
                $absensi = AbsensiModel::where('siswa_id', $siswa->id)
                    ->where('jadwal_id', $jadwalId)
                    ->whereDate('tanggal', $tanggal)
                    ->lockForUpdate()
                    ->first();

                if ($absensi && $absensi->jam_masuk) {
                    return response()->json([
                        'message' => 'Absensi sudah memiliki jam masuk, status tidak bisa diubah.'
                    ], 422);
                }

                AbsensiModel::updateOrCreate(
                    [
                        'siswa_id'  => $siswa->id,
                        'jadwal_id' => $jadwalId,
                        'tanggal'   => $tanggal,
                    ],
                    [
                        'kelas_id'               => $kelasId,
                        'jam_masuk'              => null,
                        'jam_keluar'             => null,
                        'status'                 => $status,
                        'keterangan'             => $request->keterangan,
                        'dokumen_pendukung_path' => $path,
                        'dicatat_oleh'           => $user->id,
                    ]
                );

                return response()->json([
                    'message' => 'Pengajuan ' . $status . ' berhasil dikirim.',
                ]);
            });
        } catch (\Throwable $e) {
            // ✅ Cleanup file jika transaksi DB gagal
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }

    /**
     * ✅ BARU: Set alfa otomatis oleh sistem/guru — bukan dari siswa
     * Dipanggil via cron job atau oleh guru/admin
     */
    public function setAlfaOtomatis(Request $request)
    {
        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'kelas_id'  => 'required|exists:kelas,id',
            'jadwal_id' => 'required|exists:jadwals,id',
        ]);

        // Ambil semua siswa di kelas tersebut
        $siswaIds = \App\Models\SiswaModel::where('kelas_id', $validated['kelas_id'])
            ->where('is_active', true)
            ->pluck('id');

        // Siswa yang sudah punya absensi di jadwal & tanggal ini
        $sudahAbsen = AbsensiModel::where('jadwal_id', $validated['jadwal_id'])
            ->whereDate('tanggal', $validated['tanggal'])
            ->whereIn('siswa_id', $siswaIds)
            ->pluck('siswa_id');

        // Siswa yang belum absen sama sekali → set alfa
        $belumAbsen = $siswaIds->diff($sudahAbsen);

        $inserted = 0;
        foreach ($belumAbsen as $siswaId) {
            AbsensiModel::create([
                'siswa_id'    => $siswaId,
                'kelas_id'    => $validated['kelas_id'],
                'jadwal_id'   => $validated['jadwal_id'],
                'tanggal'     => $validated['tanggal'],
                'status'      => 'alfa',
                'dicatat_oleh' => $request->user()->id,
            ]);
            $inserted++;
        }

        return response()->json([
            'message' => "Berhasil set $inserted siswa menjadi alfa.",
            'total_alfa' => $inserted,
        ]);
    }
}
