<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiModel;
use App\Models\AngkatanModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User belum login.',
            ], 401);
        }

        $siswa = $user->siswa()
            ->with(['kelas', 'angkatan'])
            ->first();

        if (!$siswa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $absensi = AbsensiModel::where('siswa_id', $siswa->id)
            ->whereYear('tanggal', now('Asia/Jakarta')->year)
            ->get();

        $normalize = fn($value) => strtolower(trim((string) $value));

        $totalHadir = $absensi->filter(
            fn($item) =>
            in_array($normalize($item->status), ['hadir', 'terlambat'], true)
        )->count();

        $totalIzin = $absensi->filter(
            fn($item) =>
            $normalize($item->status) === 'izin'
        )->count();

        $totalSakit = $absensi->filter(
            fn($item) =>
            $normalize($item->status) === 'sakit'
        )->count();

        $totalAlfa = $absensi->filter(
            fn($item) =>
            in_array($normalize($item->status), ['alfa', 'alpha'], true)
        )->count();

        $totalHari = $absensi->count();

        $persentase = function (int $jumlah) use ($totalHari): string {
            return $totalHari > 0
                ? round(($jumlah / $totalHari) * 100) . '%'
                : '0%';
        };

        $tanggalLahir = $siswa->tanggal_lahir
            ? $siswa->tanggal_lahir->format('Y-m-d')
            : '';

        $ttl = collect([
            $siswa->tempat_lahir,
            $tanggalLahir,
        ])->filter()->join(', ');

        return response()->json([
            'status' => 'success',
            'data' => [
                'profil' => [
                    'nama'          => $siswa->nama_lengkap ?? '',
                    'nis'           => $siswa->nis ?? '',
                    'kelas'         => $siswa->kelas?->nama_kelas ?? '-',
                    'jurusan'       => $siswa->kelas?->jurusan ?? '-',
                    'angkatan_id'   => $siswa->angkatan_id,
                    'angkatan'      => $siswa->angkatan?->nama ?? '-',
                    'tempat_lahir'  => $siswa->tempat_lahir ?? '',
                    'tanggal_lahir' => $tanggalLahir,
                    'ttl'           => $ttl ?: '-',
                    'jenis_kelamin' => $siswa->jenis_kelamin === 'L'
                        ? 'Laki-laki'
                        : ($siswa->jenis_kelamin === 'P' ? 'Perempuan' : '-'),
                    'agama'         => $siswa->agama ?? '',
                    'alamat'        => $siswa->alamat ?? '',
                    'no_hp'         => $siswa->no_hp ?? '',
                    'no_telp'       => $siswa->no_hp ?? '',
                    'email'         => $user->email ?? '',
                    'nama_ayah'     => $siswa->nama_ayah ?? '',
                    'nama_ibu'      => $siswa->nama_ibu ?? '',
                    'no_wali'       => $siswa->no_wali ?? '',
                    'foto'          => $siswa->foto
                        ? asset('storage/' . $siswa->foto)
                        : null,
                ],

                'angkatan_options' => AngkatanModel::query()
                    ->orderBy('nama')
                    ->get(['id', 'nama']),

                'absensi' => [
                    'total_hari'       => $totalHari,
                    'total_hadir'      => $totalHadir,
                    'total_izin'       => $totalIzin,
                    'total_sakit'      => $totalSakit,
                    'total_alfa'       => $totalAlfa,
                    'persentase_hadir' => $persentase($totalHadir),
                    'persentase_izin'  => $persentase($totalIzin),
                    'persentase_sakit' => $persentase($totalSakit),
                    'persentase_alfa'  => $persentase($totalAlfa),
                ],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User belum login.',
            ], 401);
        }

        $siswa = $user->siswa()->first();

        if (!$siswa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[\pL\s\'\-\.]+$/u',
            ],
            'angkatan_id' => ['sometimes', 'nullable', 'exists:angkatans,id'],
            'agama' => ['sometimes', 'nullable', 'string', 'max:50'],
            'tempat_lahir' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'alamat' => ['sometimes', 'nullable', 'string', 'max:500'],
            'no_hp' => [
                'sometimes',
                'nullable',
                'string',
                'min:10',
                'max:20',
                'regex:/^(\+62|62|0)[0-9]{8,13}$/',
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'nama_ayah' => ['sometimes', 'nullable', 'string', 'max:100'],
            'nama_ibu' => ['sometimes', 'nullable', 'string', 'max:100'],
            'no_wali' => [
                'sometimes',
                'nullable',
                'string',
                'min:10',
                'max:20',
                'regex:/^(\+62|62|0)[0-9]{8,13}$/',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (array_key_exists('email', $validated)) {
            $user->update([
                'email' => $validated['email'],
            ]);
        }

        $siswaData = [];

        if (array_key_exists('nama', $validated)) {
            $siswaData['nama_lengkap'] = $validated['nama'];
        }

        foreach (
            [
                'angkatan_id',
                'agama',
                'tempat_lahir',
                'tanggal_lahir',
                'alamat',
                'no_hp',
                'nama_ayah',
                'nama_ibu',
                'no_wali',
            ] as $field
        ) {
            if (array_key_exists($field, $validated)) {
                $siswaData[$field] = $validated[$field];
            }
        }

        if (!empty($siswaData)) {
            $siswa->update($siswaData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
        ]);
    }
}
