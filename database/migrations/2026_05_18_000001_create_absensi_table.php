<?php

namespace App\Exports;

use App\Models\Absensi;
use App\Models\AbsensiModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AbsensiExport implements FromCollection, WithHeadings
{
    protected $records;

    /**
     * Terima data absensi (opsional)
     * Jika null, akan ambil semua data
     */
    public function __construct($records = null)
    {
        $this->records = $records ?? AbsensiModel::with([
            'siswa.user',
            'kelas',
            'jadwal',
            'tahunAjaran',
            'semester',
            'dicatatOleh'
        ])->get();
    }

    /**
     * Collection data untuk Excel
     */
    public function collection()
    {
        return collect($this->records)->map(function ($item) {
            return [
                'ID' => $item->id,
                'Nama Siswa' => $item->siswa->user->nama_lengkap ?? '-',
                'Kelas' => $item->kelas->nama ?? '-',
                'Jadwal' => $item->jadwal->nama ?? '-',
                'Tahun Ajaran' => $item->tahunAjaran->nama ?? '-',
                'Semester' => $item->semester->nama ?? '-',
                'Tanggal' => $item->tanggal,
                'Jam Masuk' => $item->jam_masuk,
                'Jam Keluar' => $item->jam_keluar,
                'Status' => ucfirst($item->status),
                'Keterangan' => $item->keterangan,
                'Dokumen Pendukung' => $item->dokumen_pendukung_path ?? '-',
                'Verified by Face' => $item->verified_by_face ? 'Ya' : 'Tidak',
                'Face Confidence' => $item->face_confidence ?? '-',
                'Dicatat Oleh' => $item->dicatatOleh->nama_lengkap ?? '-',
                'Dibuat' => $item->created_at,
                'Diperbarui' => $item->updated_at,
            ];
        });
    }

    /**
     * Headings kolom di Excel
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nama Siswa',
            'Kelas',
            'Jadwal',
            'Tahun Ajaran',
            'Semester',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Keterangan',
            'Dokumen Pendukung',
            'Verified by Face',
            'Face Confidence',
            'Dicatat Oleh',
            'Dibuat',
            'Diperbarui',
        ];
    }
}
