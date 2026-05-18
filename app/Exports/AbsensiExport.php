<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AbsensiExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle
{
    protected Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    // ── Data ─────────────────────────────────────────────────────────────────
    public function collection(): Collection
    {
        return $this->records;
    }

    // ── Header kolom ─────────────────────────────────────────────────────────
    public function headings(): array
    {
        return [
            'No',
            'Nama Siswa',
            'Kelas',
            'Mata Pelajaran',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Keterangan',
            'Face Recognition',
            'Dicatat Oleh',
            'Waktu Pencatatan',
        ];
    }

    // ── Mapping data ke baris Excel ───────────────────────────────────────────
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->siswa?->nama ?? '-',
            $row->kelas?->nama_kelas ?? '-',
            $row->jadwal?->nama_pelajaran ?? '-',
            $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') : '-',
            $row->jam_masuk  ? \Carbon\Carbon::parse($row->jam_masuk)->format('H:i')  : '-',
            $row->jam_keluar ? \Carbon\Carbon::parse($row->jam_keluar)->format('H:i') : '-',
            match ($row->status) {
                'hadir'     => 'Hadir',
                'terlambat' => 'Terlambat',
                'izin'      => 'Izin',
                'sakit'     => 'Sakit',
                'alfa'      => 'Alfa',
                default     => $row->status,
            },
            $row->keterangan ?? '-',
            $row->verified_by_face ? 'Ya' : 'Tidak',
            $row->dicatatOleh?->name ?? '-',
            $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-',
        ];
    }

    // ── Styling Excel ─────────────────────────────────────────────────────────
    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row bold + background biru
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E40AF'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Absensi';
    }
}
