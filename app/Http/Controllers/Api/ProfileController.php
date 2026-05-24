<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiModel;
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

        $absensiQuery = AbsensiModel::where('siswa_id', $siswa->id);

        $totalHadir = (clone $absensiQuery)->where('status', 'hadir')->count();
        $totalIzin  = (clone $absensiQuery)->where('status', 'izin')->count();
        $totalSakit = (clone $absensiQuery)->where('status', 'sakit')->count();
        $totalAlfa  = (clone $absensiQuery)->where('status', 'alfa')->count();
        $totalHari  = (clone $absensiQuery)->count();

        $persentaseHadir = $totalHari > 0
            ? round(($totalHadir / $totalHari) * 100, 1)
            : 0;

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

                'absensi' => [
                    'total_hari'       => $totalHari,
                    'total_hadir'      => $totalHadir,
                    'total_izin_sakit' => $totalIzin + $totalSakit,
                    'total_alfa'       => $totalAlfa,
                    'persentase_hadir' => $persentaseHadir . '%',
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

            'agama' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'tempat_lahir' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'tanggal_lahir' => [
                'sometimes',
                'nullable',
                'date',
                'before_or_equal:today',
            ],

            'alamat' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

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

            'nama_ayah' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'nama_ibu' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'no_wali' => [
                'sometimes',
                'nullable',
                'string',
                'min:10',
                'max:20',
                'regex:/^(\+62|62|0)[0-9]{8,13}$/',
            ],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.min' => 'Nama minimal 3 karakter.',
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, petik, dan tanda hubung.',

            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',

            'no_hp.regex' => 'Format No. HP tidak valid. Contoh: 08123456789.',
            'no_hp.min' => 'No. HP minimal 10 digit.',

            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan akun lain.',

            'no_wali.regex' => 'Format No. Wali tidak valid. Contoh: 08123456789.',
            'no_wali.min' => 'No. Wali minimal 10 digit.',
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

        if (array_key_exists('agama', $validated)) {
            $siswaData['agama'] = $validated['agama'];
        }

        if (array_key_exists('tempat_lahir', $validated)) {
            $siswaData['tempat_lahir'] = $validated['tempat_lahir'];
        }

        if (array_key_exists('tanggal_lahir', $validated)) {
            $siswaData['tanggal_lahir'] = $validated['tanggal_lahir'];
        }

        if (array_key_exists('alamat', $validated)) {
            $siswaData['alamat'] = $validated['alamat'];
        }

        if (array_key_exists('no_hp', $validated)) {
            $siswaData['no_hp'] = $validated['no_hp'];
        }

        if (array_key_exists('nama_ayah', $validated)) {
            $siswaData['nama_ayah'] = $validated['nama_ayah'];
        }

        if (array_key_exists('nama_ibu', $validated)) {
            $siswaData['nama_ibu'] = $validated['nama_ibu'];
        }

        if (array_key_exists('no_wali', $validated)) {
            $siswaData['no_wali'] = $validated['no_wali'];
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
