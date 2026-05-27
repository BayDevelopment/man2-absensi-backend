<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AbsensiExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithColumnWidths,
    WithEvents
{
    protected Collection $records;

    // Baris ke berapa data mulai (setelah header sekolah + heading kolom)
    // Baris 1-4 = header sekolah, Baris 5 = heading kolom, Baris 6+ = data
    private const HEADER_ROWS = 5;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    // =========================================================================
    // DATA
    // =========================================================================

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Siswa',
            'NIS',
            'Kelas',
            'Mata Pelajaran',
            'Tanggal',
            'Hari',
            'Jam Masuk',
            'Jam Keluar',
            'Status',
            'Verifikasi Wajah',
            'Keterangan',
            'Dicatat Oleh',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        $statusMap = [
            'hadir'     => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin'      => 'Izin',
            'sakit'     => 'Sakit',
            'alfa'      => 'Alfa',
        ];

        $hariMap = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
            'Sunday'    => 'Minggu',
        ];

        $tanggal = $row->tanggal
            ? Carbon::parse($row->tanggal)->format('d/m/Y')
            : '—';

        $hari = $row->tanggal
            ? ($hariMap[Carbon::parse($row->tanggal)->format('l')] ?? '—')
            : '—';

        return [
            $no,
            $row->siswa?->nama_lengkap ?? '—',
            $row->siswa?->nis ?? '—',
            $row->kelas?->nama_kelas ?? '—',
            $row->jadwal?->mataPelajaran?->nama ?? ($row->jadwal?->nama ?? '—'),
            $tanggal,
            $hari,
            $row->jam_masuk  ? substr($row->jam_masuk,  0, 5) : '—',
            $row->jam_keluar ? substr($row->jam_keluar, 0, 5) : '—',
            $statusMap[$row->status] ?? ucfirst($row->status ?? 'Alfa'),
            $row->verified_by_face ? 'Ya' : 'Tidak',
            $row->keterangan ?? '—',
            $row->dicatatOleh?->name ?? '—',
        ];
    }

    // =========================================================================
    // SHEET META
    // =========================================================================

    public function title(): string
    {
        return 'Data Absensi';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,    // No
            'B' => 28,   // Nama Siswa
            'C' => 14,   // NIS
            'D' => 12,   // Kelas
            'E' => 22,   // Mata Pelajaran
            'F' => 13,   // Tanggal
            'G' => 11,   // Hari
            'H' => 11,   // Jam Masuk
            'I' => 11,   // Jam Keluar
            'J' => 13,   // Status
            'K' => 17,   // Verifikasi Wajah
            'L' => 30,   // Keterangan
            'M' => 20,   // Dicatat Oleh
        ];
    }

    // =========================================================================
    // STYLES
    // =========================================================================

    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'M';
        $lastRow = self::HEADER_ROWS + $this->records->count();

        return [
            // ── Heading kolom (baris 5) ──────────────────────────────────────
            self::HEADER_ROWS => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size'  => 10,
                    'name'  => 'Calibri',
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0F1C3F'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],

            // ── Semua data ───────────────────────────────────────────────────
            (self::HEADER_ROWS + 1) . ':' . $lastRow => [
                'font' => [
                    'size' => 9,
                    'name' => 'Calibri',
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    // =========================================================================
    // EVENTS — header sekolah, warna status, border
    // =========================================================================

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $lastCol  = 'M';
                $total    = $this->records->count();
                $lastRow  = self::HEADER_ROWS + $total;

                // ── 1. Sisipkan 4 baris di atas untuk header sekolah ─────────
                $sheet->insertNewRowBefore(1, self::HEADER_ROWS - 1);

                // ── 2. Header sekolah — baris 1 ─────────────────────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'LAPORAN DATA ABSENSI SISWA');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 16,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name'  => 'Calibri',
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0F1C3F'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(36);

                // ── 3. Sub-header — baris 2 ──────────────────────────────────
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', 'Sistem Informasi Akademik');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'size'  => 10,
                        'color' => ['rgb' => '94A3B8'],
                        'name'  => 'Calibri',
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1A2F5E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // ── 4. Info cetak — baris 3 ──────────────────────────────────
                $sheet->mergeCells("A3:G3");
                $sheet->setCellValue('A3', 'Dicetak pada: ' . Carbon::now()->translatedFormat('d F Y') . ' ' . Carbon::now()->format('H:i') . ' WIB');
                $sheet->getStyle('A3')->applyFromArray([
                    'font'      => ['size' => 9, 'color' => ['rgb' => '64748B'], 'name' => 'Calibri'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->mergeCells("H3:{$lastCol}3");
                $sheet->setCellValue('H3', 'Dicetak oleh: ' . (Auth::user()?->name ?? 'Administrator'));
                $sheet->getStyle("H3")->applyFromArray([
                    'font'      => ['size' => 9, 'color' => ['rgb' => '64748B'], 'name' => 'Calibri'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // ── 5. Statistik ringkas — baris 4 ───────────────────────────
                $hadirCount  = $this->records->where('status', 'hadir')->count();
                $lambatCount = $this->records->where('status', 'terlambat')->count();
                $izinCount   = $this->records->where('status', 'izin')->count();
                $sakitCount  = $this->records->where('status', 'sakit')->count();
                $alfaCount   = $this->records->where('status', 'alfa')->count();

                $statsText = "Total: {$total}  |  Hadir: {$hadirCount}  |  Terlambat: {$lambatCount}  |  Izin: {$izinCount}  |  Sakit: {$sakitCount}  |  Alfa: {$alfaCount}";
                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->setCellValue('A4', $statsText);
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 9,
                        'color' => ['rgb' => '1A2F5E'],
                        'name'  => 'Calibri',
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EFF6FF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(20);

                // ── 6. Tinggi baris heading kolom (baris 5) ──────────────────
                $sheet->getRowDimension(self::HEADER_ROWS)->setRowHeight(22);

                // ── 7. Warna status per baris data ───────────────────────────
                $statusColors = [
                    'hadir'     => ['bg' => 'D1FAE5', 'font' => '065F46'],
                    'terlambat' => ['bg' => 'FEF3C7', 'font' => '92400E'],
                    'izin'      => ['bg' => 'DBEAFE', 'font' => '1E40AF'],
                    'sakit'     => ['bg' => 'EDE9FE', 'font' => '5B21B6'],
                    'alfa'      => ['bg' => 'FEE2E2', 'font' => '991B1B'],
                ];

                foreach ($this->records as $idx => $row) {
                    $excelRow = self::HEADER_ROWS + 1 + $idx;
                    $status   = strtolower($row->status ?? 'alfa');
                    $colors   = $statusColors[$status] ?? ['bg' => 'F8FAFC', 'font' => '334155'];

                    // Warna kolom Status (J)
                    $sheet->getStyle("J{$excelRow}")->applyFromArray([
                        'font' => [
                            'bold'  => true,
                            'size'  => 9,
                            'color' => ['rgb' => $colors['font']],
                        ],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $colors['bg']],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    // Warna alternating row
                    if ($idx % 2 === 1) {
                        $sheet->getStyle("A{$excelRow}:{$lastCol}{$excelRow}")
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F8FAFC');
                    }

                    // Center kolom tertentu
                    foreach (['A', 'D', 'F', 'G', 'H', 'I', 'K'] as $col) {
                        $sheet->getStyle("{$col}{$excelRow}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    // Tinggi baris data
                    $sheet->getRowDimension($excelRow)->setRowHeight(18);
                }

                // ── 8. Border seluruh tabel ──────────────────────────────────
                $sheet->getStyle("A" . self::HEADER_ROWS . ":{$lastCol}{$lastRow}")
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'E2E8F0'],
                            ],
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color'       => ['rgb' => '0F1C3F'],
                            ],
                        ],
                    ]);

                // ── 9. Freeze pane di bawah heading ──────────────────────────
                $sheet->freezePane('A' . (self::HEADER_ROWS + 1));

                // ── 10. Auto filter ───────────────────────────────────────────
                $sheet->setAutoFilter("A" . self::HEADER_ROWS . ":{$lastCol}" . self::HEADER_ROWS);

                // ── 11. Print settings ────────────────────────────────────────
                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getHeaderFooter()
                    ->setOddHeader('&C&B&14Laporan Absensi Siswa')
                    ->setOddFooter('&LDicetak: ' . Carbon::now()->format('d/m/Y H:i') . '&RHalaman &P dari &N');
            },
        ];
    }
}
