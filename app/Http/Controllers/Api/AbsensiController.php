<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiModel;
use App\Models\JadwalModel;
use App\Models\JamSekolahModel;
use App\Models\SiswaModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AbsensiController extends Controller
{
    private const TZ = 'Asia/Jakarta';

    /**
     * Ubah menjadi true jika frontend sudah mengirim face_descriptor dari face-api.
     * Untuk AbsensiPage.vue final di bawah, nilainya dibuat false agar tombol absen tetap jalan
     * walaupun modul kamera/face-api belum dipasang di frontend.
     */
    private const REQUIRE_FACE_FOR_FIRST_MAPEL = false;

    /**
     * Threshold descriptor wajah.
     * Semakin kecil semakin ketat. Umumnya 0.50 - 0.60.
     */
    private const FACE_THRESHOLD = 0.55;

    public function index(Request $request)
    {
        $siswa = $this->currentSiswa($request);

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'type' => 'siswa_not_found',
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $now = Carbon::now(self::TZ);
        $range = $request->query('range', 'bulan');

        $query = AbsensiModel::with([
            'siswa',
            'kelas',
            'jadwal.mataPelajaran',
            'jadwal.guru',
        ])->where('siswa_id', $this->siswaId($siswa));

        if ($range === 'hari') {
            $query->whereDate('tanggal', $now->toDateString());
        } elseif ($range === 'minggu') {
            $query->whereBetween('tanggal', [
                $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ]);
        } elseif ($range === 'bulan') {
            $query->whereMonth('tanggal', $now->month)
                ->whereYear('tanggal', $now->year);
        }

        if ($request->filled('status')) {
            $query->where('status', strtolower($request->query('status')));
        }

        if ($request->filled('jadwal_id')) {
            $query->where('jadwal_id', $request->query('jadwal_id'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');

            $query->where(function ($q) use ($search) {
                $q->whereHas('jadwal.mataPelajaran', function ($mp) use ($search) {
                    $mp->where('nama', 'like', "%{$search}%");
                })->orWhereHas('jadwal.guru', function ($guru) use ($search) {
                    $guru->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            });
        }

        $data = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($item) => $this->formatAbsensiRow($item, $now));

        /*
         * Penting:
         * Tambahkan jadwal hari ini sebagai placeholder agar Vue tetap bisa menampilkan:
         * - tombol Absen Masuk,
         * - tombol Izin,
         * - pilihan jadwal pada modal Izin/Sakit,
         * meskipun siswa belum punya record di tabel absensis.
         */
        if (in_array($range, ['hari', 'minggu', 'bulan', 'all'], true)) {
            $data = $this->appendTodayScheduleRows($data, $siswa, $now);
        }

        $data = $data
            ->sortBy(function ($row) {
                return ($row['tanggal'] ?? '0000-00-00') . ' ' . ($row['jam_jadwal_mulai'] ?? '00:00:00');
            })
            ->values();

        return response()->json([
            'success' => true,
            'stats' => $this->buildStats($data),
            'meta' => [
                'tanggal_hari_ini' => $now->toDateString(),
                'hari_hari_ini' => $this->namaHariIndonesia($now),
                'siswa' => [
                    'id' => $this->siswaId($siswa),
                    'nama_lengkap' => $this->siswaDisplayName($siswa),
                    'kelas_id' => $this->siswaKelasId($siswa),
                    'kelas' => [
                        'id' => $this->siswaKelasId($siswa),
                        'nama_kelas' => $siswa->kelas?->nama_kelas ?? $siswa->kelas?->nama ?? null,
                        'nama' => $siswa->kelas?->nama ?? $siswa->kelas?->nama_kelas ?? null,
                    ],
                ],
                'jam_sekolah' => $this->activeJamSekolahInfo(),
            ],
            'data' => $data,
        ]);
    }

    public function absenMasuk(Request $request)
    {
        $validated = $request->validate([
            'jadwal_id' => ['required', 'exists:jadwals,id'],
            'kelas_id' => ['nullable', 'integer'],
            'face_descriptor' => ['nullable', 'array', 'size:128'],
            'face_descriptor.*' => ['nullable', 'numeric'],
            'face_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = $request->user();
        $siswa = $this->currentSiswa($request);

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'type' => 'siswa_not_found',
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $siswaId = $this->siswaId($siswa);
        $kelasId = $this->siswaKelasId($siswa);

        if (!$kelasId) {
            return response()->json([
                'success' => false,
                'type' => 'kelas_not_found',
                'message' => 'Data kelas siswa tidak ditemukan.',
            ], 422);
        }

        $now = Carbon::now(self::TZ);
        $tanggalHariIni = $now->toDateString();
        $jamSekarang = $now->format('H:i:s');

        $jadwal = JadwalModel::with(['mataPelajaran', 'guru'])
            ->where('id', $validated['jadwal_id'])
            ->where('kelas_id', $kelasId)
            ->where(function ($q) use ($now) {
                $this->applyHariFilter($q, $now);
            })
            ->where(function ($q) {
                $q->where('is_break', false)->orWhereNull('is_break');
            })
            ->whereNotNull('mata_pelajaran_id')
            ->first();

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'type' => 'invalid_schedule',
                'message' => 'Jadwal tidak valid atau tidak tersedia untuk kelas Anda hari ini.',
            ], 422);
        }

        $isFirstMapel = $this->isFirstMapelToday($siswa, $jadwal, $now);

        if (!$isFirstMapel) {
            return response()->json([
                'success' => false,
                'type' => 'student_only_morning',
                'message' => 'Siswa hanya dapat melakukan absen pada jam masuk pagi. Absensi mapel berikutnya dikelola guru.',
            ], 422);
        }

        $batas = $this->getBatasAbsensi($jadwal, $siswa, $now);

        if ($jamSekarang < $batas['jam_buka']) {
            return response()->json([
                'success' => false,
                'type' => 'attendance_not_open',
                'message' => 'Absensi belum dibuka. Silakan absen pada pukul ' . substr($batas['jam_buka'], 0, 5) . '.',
            ], 422);
        }

        if ($jamSekarang > $batas['batas_terlambat']) {
            return response()->json([
                'success' => false,
                'type' => 'attendance_closed',
                'message' => 'Batas absen siswa sudah lewat. Silakan hubungi guru untuk pencatatan absensi.',
            ], 422);
        }

        $existing = AbsensiModel::where('siswa_id', $siswaId)
            ->where('jadwal_id', $jadwal->getKey())
            ->whereDate('tanggal', $tanggalHariIni)
            ->first();

        if ($existing && in_array($existing->status, ['izin', 'sakit'], true)) {
            return response()->json([
                'success' => false,
                'type' => 'already_status',
                'message' => 'Anda sudah tercatat ' . $existing->status . ' pada mapel ini.',
            ], 422);
        }

        if ($existing) {
            return response()->json([
                'success' => false,
                'type' => 'already_absen',
                'message' => 'Anda sudah memiliki catatan di jadwal ini.',
            ], 422);
        }

        $faceVerified = false;
        $faceConfidence = null;
        $faceImagePath = null;

        $registeredDescriptor = $this->normalizeDescriptor($siswa->face_descriptor ?? null);
        $incomingDescriptor = $this->normalizeDescriptor($validated['face_descriptor'] ?? null);

        if (self::REQUIRE_FACE_FOR_FIRST_MAPEL) {
            if (!($siswa->is_face_registered ?? false) || empty($registeredDescriptor)) {
                return response()->json([
                    'success' => false,
                    'type' => 'face_not_registered',
                    'message' => 'Data wajah siswa belum didaftarkan. Hubungi guru atau operator.',
                ], 422);
            }

            if (empty($incomingDescriptor)) {
                return response()->json([
                    'success' => false,
                    'type' => 'face_required',
                    'message' => 'Absen mapel pertama wajib menggunakan verifikasi wajah.',
                ], 422);
            }
        }

        if (!empty($registeredDescriptor) && !empty($incomingDescriptor)) {
            $faceResult = $this->compareFaceDescriptors($registeredDescriptor, $incomingDescriptor);

            $faceVerified = (bool) ($faceResult['matched'] ?? false);
            $faceConfidence = $faceResult['confidence'] ?? null;

            if (!$faceVerified) {
                return response()->json([
                    'success' => false,
                    'type' => 'face_not_match',
                    'message' => 'Wajah tidak sama. Pastikan wajah sesuai dengan akun siswa yang login.',
                    'data' => [
                        'distance' => $faceResult['distance'] ?? null,
                        'confidence' => $faceResult['confidence'] ?? null,
                    ],
                ], 403);
            }

            if ($request->hasFile('face_image')) {
                $faceImagePath = $request->file('face_image')->store('face-absensi', 'public');
            }
        }

        $statusKehadiran = $jamSekarang > $batas['jam_buka']
            ? 'terlambat'
            : 'hadir';

        try {
            $absensi = DB::transaction(function () use (
                $siswaId,
                $kelasId,
                $jadwal,
                $tanggalHariIni,
                $jamSekarang,
                $statusKehadiran,
                $faceVerified,
                $faceConfidence,
                $faceImagePath,
                $now,
                $user
            ) {
                $lockedExisting = AbsensiModel::where('siswa_id', $siswaId)
                    ->where('jadwal_id', $jadwal->getKey())
                    ->whereDate('tanggal', $tanggalHariIni)
                    ->lockForUpdate()
                    ->first();

                if ($lockedExisting) {
                    throw new \RuntimeException('Anda sudah memiliki catatan di jadwal ini.');
                }

                return AbsensiModel::create([
                    'siswa_id' => $siswaId,
                    'jadwal_id' => $jadwal->getKey(),
                    'tanggal' => $tanggalHariIni,
                    'kelas_id' => $kelasId,
                    'jam_masuk' => $jamSekarang,
                    'jam_keluar' => null,
                    'status' => $statusKehadiran,
                    'keterangan' => null,
                    'verified_by_face' => $faceVerified,
                    'face_confidence' => $faceConfidence,
                    'face_image_path' => $faceImagePath,
                    'face_verified_at' => $faceVerified ? $now : null,
                    'dicatat_oleh' => $this->userId($user),
                ]);
            });

            $absensi->load(['kelas', 'jadwal.mataPelajaran', 'jadwal.guru']);

            return response()->json([
                'success' => true,
                'type' => 'attendance_success',
                'message' => 'Absen pagi berhasil dengan status: ' . ucfirst($statusKehadiran) . '.',
                'data' => $this->formatAbsensiRow($absensi, $now),
            ]);
        } catch (\Throwable $e) {
            $this->deletePublicFile($faceImagePath);

            return response()->json([
                'success' => false,
                'type' => 'server_error',
                'message' => 'Gagal menyimpan absensi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function absenKeluar(Request $request)
    {
        return response()->json([
            'success' => false,
            'type' => 'student_checkout_disabled',
            'message' => 'Siswa hanya melakukan absen masuk pagi. Absensi mapel dan jam keluar dikelola oleh guru.',
        ], 422);
    }

    public function simpanStatus(Request $request)
    {
        $validated = $request->validate([
            'jadwal_id' => ['required', 'exists:jadwals,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:izin,sakit'],
            'keterangan' => ['nullable', 'string', 'max:1000', 'required_if:status,sakit'],
            'dokumen_pendukung' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048', 'required_if:status,sakit'],
        ]);

        $user = $request->user();
        $siswa = $this->currentSiswa($request);

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'type' => 'siswa_not_found',
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $siswaId = $this->siswaId($siswa);
        $kelasId = $this->siswaKelasId($siswa);

        if (!$kelasId) {
            return response()->json([
                'success' => false,
                'type' => 'kelas_not_found',
                'message' => 'Data kelas siswa tidak ditemukan.',
            ], 422);
        }

        $now = Carbon::now(self::TZ);
        $tanggal = Carbon::parse($validated['tanggal'], self::TZ)->toDateString();
        $jamSekarang = $now->format('H:i:s');

        if ($tanggal !== $now->toDateString()) {
            return response()->json([
                'success' => false,
                'type' => 'invalid_date',
                'message' => 'Izin/Sakit hanya bisa diajukan untuk hari ini.',
            ], 422);
        }

        $jadwal = JadwalModel::with(['mataPelajaran', 'guru'])
            ->where('id', $validated['jadwal_id'])
            ->where('kelas_id', $kelasId)
            ->where(function ($q) use ($now) {
                $this->applyHariFilter($q, $now);
            })
            ->where(function ($q) {
                $q->where('is_break', false)->orWhereNull('is_break');
            })
            ->whereNotNull('mata_pelajaran_id')
            ->first();

        if (!$jadwal) {
            return response()->json([
                'success' => false,
                'type' => 'invalid_schedule',
                'message' => 'Jadwal tidak valid atau tidak sesuai dengan tanggal yang dipilih.',
            ], 422);
        }

        if (!$this->isFirstMapelToday($siswa, $jadwal, $now)) {
            return response()->json([
                'success' => false,
                'type' => 'schedule_not_first_mapel',
                'message' => 'Izin/Sakit hanya dapat diajukan pada jadwal absen pagi.',
            ], 422);
        }

        $batas = $this->getBatasAbsensi($jadwal, $siswa, $now);

        if ($jamSekarang < $batas['jam_buka']) {
            return response()->json([
                'success' => false,
                'type' => 'cannot_request_status',
                'message' => 'Pengajuan izin/sakit belum dibuka. Silakan ajukan mulai pukul ' . substr($batas['jam_buka'], 0, 5) . '.',
            ], 422);
        }

        if ($jamSekarang > $batas['batas_terlambat']) {
            return response()->json([
                'success' => false,
                'type' => 'attendance_closed',
                'message' => 'Batas pengajuan izin/sakit sudah lewat. Silakan hubungi guru.',
            ], 422);
        }

        $existing = AbsensiModel::where('siswa_id', $siswaId)
            ->where('jadwal_id', $jadwal->getKey())
            ->whereDate('tanggal', $tanggal)
            ->first();

        if ($existing && in_array($existing->status, ['hadir', 'terlambat'], true)) {
            return response()->json([
                'success' => false,
                'type' => 'already_present',
                'message' => 'Anda sudah diabsen hadir di mapel ini.',
            ], 422);
        }

        if ($existing && $existing->jam_masuk) {
            return response()->json([
                'success' => false,
                'type' => 'already_present',
                'message' => 'Absensi sudah memiliki jam masuk, status tidak bisa diubah menjadi izin/sakit.',
            ], 422);
        }

        $newPath = null;
        $oldPath = $existing?->dokumen_pendukung_path;

        if ($request->hasFile('dokumen_pendukung')) {
            $newPath = $request->file('dokumen_pendukung')->store('dokumen-absensi', 'public');
        }

        try {
            $absensi = DB::transaction(function () use ($siswaId, $kelasId, $jadwal, $validated, $tanggal, $request, $newPath, $oldPath, $user) {
                return AbsensiModel::updateOrCreate(
                    [
                        'siswa_id' => $siswaId,
                        'jadwal_id' => $jadwal->getKey(),
                        'tanggal' => $tanggal,
                    ],
                    [
                        'kelas_id' => $kelasId,
                        'status' => $validated['status'],
                        'keterangan' => $request->input('keterangan'),
                        'dokumen_pendukung_path' => $newPath ?: $oldPath,
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'verified_by_face' => false,
                        'dicatat_oleh' => $this->userId($user),
                    ]
                );
            });

            if ($newPath && $oldPath && $oldPath !== $newPath) {
                $this->deletePublicFile($oldPath);
            }

            $absensi->refresh()->load(['kelas', 'jadwal.mataPelajaran', 'jadwal.guru']);

            return response()->json([
                'success' => true,
                'type' => 'status_success',
                'message' => 'Pengajuan berhasil dikirim.',
                'data' => $this->formatAbsensiRow($absensi, $now),
            ]);
        } catch (\Throwable $e) {
            $this->deletePublicFile($newPath);

            return response()->json([
                'success' => false,
                'type' => 'server_error',
                'message' => 'Gagal menyimpan pengajuan: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function appendTodayScheduleRows($data, SiswaModel $siswa, Carbon $now)
    {
        $tanggalHariIni = $now->toDateString();
        $kelasId = $this->siswaKelasId($siswa);

        if (!$kelasId) {
            return $data;
        }

        $jadwalHariIni = JadwalModel::with(['mataPelajaran', 'guru'])
            ->where('kelas_id', $kelasId)
            ->where(function ($q) use ($now) {
                $this->applyHariFilter($q, $now);
            })
            ->where(function ($q) {
                $q->where('is_break', false)->orWhereNull('is_break');
            })
            ->whereNotNull('mata_pelajaran_id')
            ->orderByRaw('COALESCE(urutan, 999)')
            ->orderBy('jam_mulai')
            ->get();

        foreach ($jadwalHariIni as $index => $jadwal) {
            $exists = $data->first(function ($item) use ($tanggalHariIni, $jadwal) {
                return ($item['tanggal'] ?? null) === $tanggalHariIni
                    && (int) ($item['jadwal_id'] ?? 0) === (int) $jadwal->getKey();
            });

            if (!$exists) {
                $data->push($this->formatScheduleRowWithoutAbsensi($siswa, $jadwal, $now, $index === 0));
            }
        }

        return $data;
    }

    private function formatAbsensiRow(AbsensiModel $absensi, ?Carbon $now = null): array
    {
        $now ??= Carbon::now(self::TZ);

        $jadwal = $absensi->jadwal;
        $tanggal = Carbon::parse($absensi->tanggal, self::TZ)->toDateString();

        $mapel = $jadwal?->mataPelajaran?->nama
            ?? $jadwal?->matpel?->nama
            ?? $jadwal?->mapel?->nama
            ?? $jadwal?->nama_mapel
            ?? '—';

        $guru = $jadwal?->guru?->nama_lengkap
            ?? $jadwal?->guru?->nama
            ?? $jadwal?->guru?->name
            ?? '—';

        $kelasNama = $absensi->kelas?->nama_kelas
            ?? $absensi->kelas?->nama
            ?? $absensi->siswa?->kelas?->nama_kelas
            ?? $absensi->siswa?->kelas?->nama
            ?? '—';

        $batas = $jadwal
            ? $this->getBatasAbsensi($jadwal, $absensi->siswa ?? $this->fakeSiswaFromAbsensi($absensi), $now)
            : [
                'jam_buka' => null,
                'batas_terlambat' => null,
                'jam_tutup' => null,
                'is_first_mapel' => false,
            ];

        $status = strtolower($absensi->status ?? 'alfa');
        $jamSekarang = $now->format('H:i:s');
        $jamSelesai = $this->timeOnly($jadwal?->jam_selesai);

        return [
            'id' => $absensi->getKey(),
            'siswa_id' => $absensi->siswa_id,
            'kelas_id' => $absensi->kelas_id,
            'jadwal_id' => $absensi->jadwal_id,
            'tanggal' => $tanggal,
            'hari' => $this->namaHariIndonesia(Carbon::parse($tanggal, self::TZ)),
            'matpel' => $mapel,
            'mata_pelajaran' => $mapel,
            'guru' => $guru,
            'kelas' => [
                'id' => $absensi->kelas_id,
                'nama_kelas' => $kelasNama,
                'nama' => $kelasNama,
            ],
            'nama_kelas' => $kelasNama,

            'jam_masuk' => $this->timeOnly($absensi->jam_masuk),
            'jam_keluar' => $this->timeOnly($absensi->jam_keluar),
            'jam_jadwal_mulai' => $this->timeOnly($jadwal?->jam_mulai),
            'jam_jadwal_selesai' => $this->timeOnly($jadwal?->jam_selesai),
            'jam_buka_absensi' => $batas['jam_buka'],
            'jam_tutup_absensi' => $jamSelesai,
            'batas_terlambat' => $batas['batas_terlambat'],

            'status' => $status,
            'keterangan' => $absensi->keterangan ?: $this->actionTextForExistingAbsensi($absensi),
            'dokumen_pendukung_path' => $absensi->dokumen_pendukung_path,
            'dokumen_pendukung_url' => $this->publicStorageUrl($absensi->dokumen_pendukung_path),

            'verified_by_face' => (bool) ($absensi->verified_by_face ?? false),
            'face_confidence' => $absensi->face_confidence ?? null,
            'face_required' => false,
            'is_first_mapel' => (bool) ($batas['is_first_mapel'] ?? false),

            'can_absen_masuk' => false,
            'can_absen_keluar' => false,
            'can_izin_sakit' => false,
            'is_mapel_selesai' => $tanggal === $now->toDateString()
                && $jamSelesai
                && $jamSekarang > $jamSelesai,

            'action_state' => 'sudah_tercatat',
            'action_text' => $this->actionTextForExistingAbsensi($absensi),

            'jadwal' => [
                'id' => $jadwal?->getKey(),
                'hari' => $jadwal?->hari,
                'jam_mulai' => $this->timeOnly($jadwal?->jam_mulai),
                'jam_selesai' => $this->timeOnly($jadwal?->jam_selesai),
                'mata_pelajaran' => [
                    'nama' => $mapel,
                ],
                'guru' => [
                    'nama_lengkap' => $guru,
                    'nama' => $guru,
                    'name' => $guru,
                ],
            ],
        ];
    }

    private function formatScheduleRowWithoutAbsensi(
        SiswaModel $siswa,
        JadwalModel $jadwal,
        Carbon $now,
        bool $isFirstMapel
    ): array {
        $tanggal = $now->toDateString();
        $jamSekarang = $now->format('H:i:s');
        $batas = $this->getBatasAbsensi($jadwal, $siswa, $now);

        $jamBuka = $batas['jam_buka'];
        $batasTerlambat = $batas['batas_terlambat'];
        $jamTutup = $this->timeOnly($jadwal->jam_selesai);

        $isBelumDibuka = $isFirstMapel && $jamBuka && $jamSekarang < $jamBuka;
        $isLewatBatas = $isFirstMapel && $batasTerlambat && $jamSekarang > $batasTerlambat;
        $isMapelSelesai = $jamTutup && $jamSekarang > $jamTutup;
        $canAbsenPagi = $isFirstMapel && !$isBelumDibuka && !$isLewatBatas;

        $actionState = 'hanya_absen_pagi';
        $actionText = 'Absensi siswa hanya untuk jam masuk pagi';

        if ($isFirstMapel && $isBelumDibuka) {
            $actionState = 'belum_dibuka';
            $actionText = 'Absensi dibuka pukul ' . substr($jamBuka, 0, 5);
        } elseif ($isFirstMapel && $isLewatBatas) {
            $actionState = 'lewat_batas_absen';
            $actionText = 'Batas absen siswa sudah lewat. Hubungi guru.';
        } elseif ($canAbsenPagi) {
            $actionState = 'bisa_absen_masuk';
            $actionText = 'Absen Masuk Pagi';
        }

        $mapel = $jadwal->mataPelajaran?->nama
            ?? $jadwal->matpel?->nama
            ?? $jadwal->mapel?->nama
            ?? $jadwal->nama_mapel
            ?? '—';

        $guru = $jadwal->guru?->nama_lengkap
            ?? $jadwal->guru?->nama
            ?? $jadwal->guru?->name
            ?? '—';

        $kelasNama = $siswa->kelas?->nama_kelas
            ?? $siswa->kelas?->nama
            ?? '—';

        return [
            'id' => null,
            'siswa_id' => $this->siswaId($siswa),
            'kelas_id' => $this->siswaKelasId($siswa),
            'jadwal_id' => $jadwal->getKey(),
            'tanggal' => $tanggal,
            'hari' => $this->namaHariIndonesia($now),
            'matpel' => $mapel,
            'mata_pelajaran' => $mapel,
            'guru' => $guru,
            'kelas' => [
                'id' => $this->siswaKelasId($siswa),
                'nama_kelas' => $kelasNama,
                'nama' => $kelasNama,
            ],
            'nama_kelas' => $kelasNama,

            'jam_masuk' => null,
            'jam_keluar' => null,
            'jam_jadwal_mulai' => $this->timeOnly($jadwal->jam_mulai),
            'jam_jadwal_selesai' => $jamTutup,
            'jam_buka_absensi' => $jamBuka,
            'jam_tutup_absensi' => $jamTutup,
            'batas_terlambat' => $batasTerlambat,

            'status' => 'alfa',
            'keterangan' => $actionText,
            'dokumen_pendukung_path' => null,
            'dokumen_pendukung_url' => null,

            'verified_by_face' => false,
            'face_confidence' => null,
            'face_required' => self::REQUIRE_FACE_FOR_FIRST_MAPEL && $isFirstMapel,
            'is_first_mapel' => $isFirstMapel,

            'can_absen_masuk' => $canAbsenPagi,
            'can_absen_keluar' => false,
            'can_izin_sakit' => $canAbsenPagi,
            'is_mapel_selesai' => (bool) ($isLewatBatas || $isMapelSelesai),

            'action_state' => $actionState,
            'action_text' => $actionText,

            'jadwal' => [
                'id' => $jadwal->getKey(),
                'hari' => $jadwal->hari,
                'jam_mulai' => $this->timeOnly($jadwal->jam_mulai),
                'jam_selesai' => $jamTutup,
                'mata_pelajaran' => [
                    'nama' => $mapel,
                ],
                'guru' => [
                    'nama_lengkap' => $guru,
                    'nama' => $guru,
                    'name' => $guru,
                ],
            ],
        ];
    }

    private function currentSiswa(Request $request): ?SiswaModel
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if (method_exists($user, 'siswa')) {
            return $user->siswa()->with('kelas')->first();
        }

        return SiswaModel::with('kelas')
            ->where('user_id', $this->userId($user))
            ->first();
    }

    private function fakeSiswaFromAbsensi(AbsensiModel $absensi): SiswaModel
    {
        $siswa = new SiswaModel();
        $siswa->setAttribute('id', $absensi->siswa_id);
        $siswa->setAttribute('kelas_id', $absensi->kelas_id);

        return $siswa;
    }

    private function isFirstMapelToday(SiswaModel $siswa, JadwalModel $jadwal, Carbon $now): bool
    {
        $kelasId = $this->siswaKelasId($siswa);

        if (!$kelasId) {
            return false;
        }

        $firstJadwal = JadwalModel::query()
            ->where('kelas_id', $kelasId)
            ->where(function ($q) use ($now) {
                $this->applyHariFilter($q, $now);
            })
            ->where(function ($q) {
                $q->where('is_break', false)->orWhereNull('is_break');
            })
            ->whereNotNull('mata_pelajaran_id')
            ->orderByRaw('COALESCE(urutan, 999)')
            ->orderBy('jam_mulai')
            ->first();

        return $firstJadwal && (int) $firstJadwal->getKey() === (int) $jadwal->getKey();
    }

    private function getBatasAbsensi(JadwalModel $jadwal, SiswaModel $siswa, Carbon $date): array
    {
        $isFirstMapel = $this->isFirstMapelToday($siswa, $jadwal, $date);
        $jamSekolah = $isFirstMapel ? $this->activeJamSekolah() : null;

        if ($isFirstMapel) {
            $jamBuka = $this->timeOnly($jamSekolah?->jam_masuk)
                ?? $this->timeOnly($jadwal->jam_mulai)
                ?? '07:00:00';

            $batasTerlambat = $this->timeOnly($jamSekolah?->batas_terlambat)
                ?? Carbon::parse($jamBuka)->addMinutes(15)->format('H:i:s');
        } else {
            $jamBuka = $this->timeOnly($jadwal->jam_mulai) ?? '00:00:00';

            $batasTerlambat = $this->timeOnly($jadwal->batas_terlambat ?? null)
                ?? Carbon::parse($jamBuka)->addMinutes(15)->format('H:i:s');
        }

        return [
            'jam_buka' => $jamBuka,
            'batas_terlambat' => $batasTerlambat,
            'jam_tutup' => $this->timeOnly($jadwal->jam_selesai),
            'is_first_mapel' => $isFirstMapel,
        ];
    }

    private function siswaId(SiswaModel $siswa): int
    {
        return (int) $siswa->getKey();
    }

    private function siswaKelasId(SiswaModel $siswa): ?int
    {
        $kelasId = $siswa->getAttribute('kelas_id');

        return $kelasId === null ? null : (int) $kelasId;
    }

    private function siswaDisplayName(SiswaModel $siswa): ?string
    {
        $nama = trim((string) ($siswa->getAttribute('nama_lengkap') ?? $siswa->getAttribute('nama') ?? ''));

        return $nama !== '' ? $nama : null;
    }

    private function userId($user): ?int
    {
        if (!$user) {
            return null;
        }

        if (method_exists($user, 'getAuthIdentifier')) {
            return (int) $user->getAuthIdentifier();
        }

        if (method_exists($user, 'getKey')) {
            return (int) $user->getKey();
        }

        return null;
    }

    private function activeJamSekolah(): ?JamSekolahModel
    {
        return JamSekolahModel::query()
            ->where('aktif', '=', 1)
            ->first();
    }

    private function activeJamSekolahInfo(): array
    {
        $jamSekolah = $this->activeJamSekolah();

        return [
            'jam_masuk' => $this->timeOnly($jamSekolah?->jam_masuk) ?? '07:00:00',
            'batas_terlambat' => $this->timeOnly($jamSekolah?->batas_terlambat) ?? '07:15:00',
        ];
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    private function applyHariFilter($query, Carbon $date): void
    {
        $hariId = $this->namaHariIndonesia($date);
        $hariEn = $date->format('l');

        $query->whereIn('hari', [
            $hariId,
            strtolower($hariId),
            strtoupper($hariId),
            $hariEn,
            strtolower($hariEn),
            strtoupper($hariEn),
        ]);
    }

    private function actionTextForExistingAbsensi(AbsensiModel $absensi): string
    {
        return match (strtolower($absensi->status ?? '')) {
            'hadir' => $absensi->jam_keluar ? 'Absensi Selesai' : 'Sudah Absen Masuk',
            'terlambat' => $absensi->jam_keluar ? 'Absensi Selesai' : 'Sudah Absen Terlambat',
            'izin' => 'Izin Tercatat',
            'sakit' => 'Sakit Tercatat',
            'alfa' => 'Alfa',
            default => 'Sudah Tercatat',
        };
    }

    private function buildStats($rows): array
    {
        $rows = collect($rows);
        $total = $rows->count();
        $hadir = $rows->where('status', 'hadir')->count();
        $terlambat = $rows->where('status', 'terlambat')->count();
        $izin = $rows->where('status', 'izin')->count();
        $sakit = $rows->where('status', 'sakit')->count();
        $alfa = $rows->where('status', 'alfa')->count();

        return [
            'total_hadir' => $hadir,
            'total_terlambat' => $terlambat,
            'total_izin_sakit' => $izin + $sakit,
            'total_alfa' => $alfa,
            'persentase_kehadiran' => $total > 0
                ? round((($hadir + $terlambat) / $total) * 100)
                : 0,
        ];
    }

    private function namaHariIndonesia(Carbon $date): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$date->dayOfWeekIso] ?? $date->format('l');
    }

    private function timeOnly($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            $value = (string) $value;

            if (strlen($value) === 5) {
                return $value . ':00';
            }

            return substr($value, 0, 8);
        }
    }

    private function normalizeDescriptor($descriptor): ?array
    {
        if (!$descriptor) {
            return null;
        }

        if (is_string($descriptor)) {
            $decoded = json_decode($descriptor, true);
            $descriptor = is_array($decoded) ? $decoded : null;
        }

        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return null;
        }

        return array_map('floatval', array_values($descriptor));
    }

    private function compareFaceDescriptors(?array $registeredDescriptor, ?array $incomingDescriptor): array
    {
        if (!$registeredDescriptor || !$incomingDescriptor) {
            return [
                'matched' => false,
                'distance' => null,
                'confidence' => null,
            ];
        }

        $sum = 0.0;

        for ($i = 0; $i < 128; $i++) {
            $diff = (float) $registeredDescriptor[$i] - (float) $incomingDescriptor[$i];
            $sum += $diff * $diff;
        }

        $distance = sqrt($sum);
        $matched = $distance <= self::FACE_THRESHOLD;
        $confidence = max(0, min(100, round((1 - ($distance / self::FACE_THRESHOLD)) * 100, 2)));

        return [
            'matched' => $matched,
            'distance' => round($distance, 6),
            'confidence' => $confidence,
        ];
    }

    private function deletePublicFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
