<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            background: #ffffff;
            color: #0a2218;
            font-size: 11px;
            line-height: 1.5;
        }

        .page {
            width: 100%;
        }

        /* ===== SCHOOL HEADER ===== */
        .school-header {
            background-color: #0d4a2f;
            padding: 20px 30px;
        }

        .school-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .school-logo-placeholder {
            width: 72px;
            height: 72px;
            background-color: rgba(255, 255, 255, 0.08);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            text-align: center;
            vertical-align: middle;
            font-size: 28px;
            line-height: 72px;
        }

        .school-logo {
            width: 72px;
            height: 72px;
            border-radius: 8px;
        }

        .school-divider-cell {
            width: 2px;
            padding: 0 14px;
        }

        .school-divider-line {
            width: 2px;
            height: 64px;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .school-name {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }

        .school-address {
            font-size: 10px;
            color: #a7d9bc;
            margin-top: 4px;
            line-height: 1.5;
        }

        .date-box {
            background-color: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 10px 16px;
            text-align: right;
            white-space: nowrap;
        }

        .date-box-lbl {
            color: #a7d9bc;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .date-box-val {
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            margin-top: 2px;
        }

        .date-box-time {
            color: #6ee7a8;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 11px;
            margin-top: 2px;
        }

        /* ===== DOC TITLE STRIP ===== */
        .doc-title-strip {
            background-color: #1a7a4a;
            padding: 10px 30px;
        }

        .doc-title-strip-table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-title {
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .doc-sub {
            font-size: 10px;
            color: #a7d9bc;
        }

        /* ===== STATS STRIP ===== */
        .stats-strip {
            background-color: #135e3a;
            padding: 0 30px;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stat-cell {
            padding: 12px 20px 12px 0;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            white-space: nowrap;
        }

        .stat-cell:last-child {
            border-right: none;
        }

        .stat-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }

        .stat-count {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1;
            display: inline-block;
            vertical-align: middle;
        }

        .stat-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #7ab898;
            text-transform: uppercase;
            display: block;
            margin-top: 2px;
            padding-left: 16px;
        }

        /* ===== BODY ===== */
        .body {
            padding: 20px 30px 36px;
        }

        /* ===== SUMMARY CARDS ===== */
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 16px;
        }

        .summary-card {
            border: 1px solid #c6e9d7;
            border-radius: 10px;
            padding: 12px 14px;
            width: 20%;
            vertical-align: top;
        }

        .sc-top-hadir {
            border-top: 3px solid #059669;
        }

        .sc-top-terlambat {
            border-top: 3px solid #d97706;
        }

        .sc-top-izin {
            border-top: 3px solid #2563eb;
        }

        .sc-top-sakit {
            border-top: 3px solid #7c3aed;
        }

        .sc-top-alfa {
            border-top: 3px solid #dc2626;
        }

        .sc-number-hadir {
            font-size: 22px;
            font-weight: 800;
            color: #059669;
            line-height: 1;
            margin-bottom: 3px;
        }

        .sc-number-terlambat {
            font-size: 22px;
            font-weight: 800;
            color: #d97706;
            line-height: 1;
            margin-bottom: 3px;
        }

        .sc-number-izin {
            font-size: 22px;
            font-weight: 800;
            color: #2563eb;
            line-height: 1;
            margin-bottom: 3px;
        }

        .sc-number-sakit {
            font-size: 22px;
            font-weight: 800;
            color: #7c3aed;
            line-height: 1;
            margin-bottom: 3px;
        }

        .sc-number-alfa {
            font-size: 22px;
            font-weight: 800;
            color: #dc2626;
            line-height: 1;
            margin-bottom: 3px;
        }

        .sc-label {
            font-size: 10px;
            font-weight: 600;
            color: #4b6358;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sc-pct {
            font-size: 9px;
            color: #8fada3;
            margin-top: 2px;
        }

        /* ===== PROGRESS BAR ===== */
        .progress-section {
            margin-bottom: 16px;
        }

        .progress-label-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .progress-label-left {
            font-size: 10px;
            color: #4b6358;
        }

        .progress-label-right {
            font-size: 10px;
            color: #4b6358;
            text-align: right;
        }

        .progress-bar-outer {
            height: 7px;
            background-color: #e6f4ed;
            border-radius: 4px;
            overflow: hidden;
            width: 100%;
        }

        /* DomPDF doesn't support inline-block width trick well, use table for segments */
        .progress-bar-table {
            width: 100%;
            border-collapse: collapse;
            height: 7px;
        }

        /* ===== SECTION TITLE ===== */
        .section-title-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .section-title-bar-cell {
            width: 4px;
            padding-right: 8px;
        }

        .section-title-bar {
            width: 4px;
            height: 18px;
            background-color: #22a65c;
            border-radius: 2px;
        }

        .section-title-h2 {
            font-size: 13px;
            font-weight: 700;
            color: #0a2218;
        }

        .section-title-sub {
            font-size: 10px;
            color: #4b6358;
        }

        /* ===== TABLE ===== */
        .table-wrap {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #c6e9d7;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead tr {
            background-color: #f0fdf7;
        }

        .data-table thead th {
            padding: 9px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: 700;
            color: #3d7a5a;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-bottom: 2px solid #c6e9d7;
            white-space: nowrap;
        }

        .data-table thead th:first-child {
            padding-left: 12px;
        }

        .data-table thead th:last-child {
            padding-right: 12px;
        }

        .data-table tbody tr.even-row {
            background-color: #f5fdf8;
        }

        .data-table tbody td {
            padding: 9px 8px;
            border-bottom: 1px solid #e6f0ea;
            vertical-align: middle;
        }

        .data-table tbody td:first-child {
            padding-left: 12px;
        }

        .data-table tbody td:last-child {
            padding-right: 12px;
        }

        .row-num {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 10px;
            color: #8fada3;
        }

        .siswa-name {
            font-weight: 700;
            font-size: 11px;
        }

        .siswa-sub {
            font-size: 9px;
            color: #4b6358;
            margin-top: 1px;
        }

        .kelas-badge {
            background-color: #d1fae5;
            color: #065f46;
            font-size: 9px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
            border: 1px solid #a7f3cf;
            white-space: nowrap;
        }

        .mapel-text {
            font-size: 10px;
            color: #4b6358;
        }

        .date-cell,
        .time-cell {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 10px;
            white-space: nowrap;
        }

        .time-cell {
            color: #b0c9be;
        }

        .time-cell-filled {
            color: #0a2218;
            font-weight: 500;
        }

        /* Badges */
        .badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 9.5px;
            font-weight: 700;
            white-space: nowrap;
            display: inline-block;
        }

        .badge-dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            margin-right: 4px;
            vertical-align: middle;
        }

        .badge-hadir {
            background-color: #d1fae5;
            color: #059669;
        }

        .badge-terlambat {
            background-color: #fef3c7;
            color: #d97706;
        }

        .badge-izin {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .badge-sakit {
            background-color: #ede9fe;
            color: #7c3aed;
        }

        .badge-alfa {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .dot-hadir {
            background-color: #059669;
        }

        .dot-terlambat {
            background-color: #d97706;
        }

        .dot-izin {
            background-color: #2563eb;
        }

        .dot-sakit {
            background-color: #7c3aed;
        }

        .dot-alfa {
            background-color: #dc2626;
        }

        .face-chip {
            font-size: 9px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 20px;
            display: inline-block;
        }

        .face-verified {
            background-color: #d1fae5;
            color: #065f46;
        }

        .face-manual {
            background-color: #f1f5f9;
            color: #64748b;
        }

        .keterangan-text {
            font-size: 10px;
            color: #4b6358;
            font-style: italic;
        }

        .dicatat-text {
            font-size: 10px;
            color: #8fada3;
        }

        /* ===== FOOTER ===== */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
            padding-top: 14px;
            border-top: 2px solid #c6e9d7;
        }

        .footer-left {
            font-size: 10px;
            color: #4b6358;
            vertical-align: bottom;
        }

        .footer-right {
            text-align: right;
            font-size: 10px;
            color: #4b6358;
            vertical-align: bottom;
        }

        .ttd-box {
            border: 1px solid #c6e9d7;
            border-radius: 8px;
            padding: 8px 24px;
            margin-bottom: 6px;
            text-align: center;
            display: inline-block;
        }

        .ttd-space {
            height: 40px;
        }

        .ttd-name {
            font-weight: 700;
            color: #0a2218;
            font-size: 11px;
            border-top: 1px solid #0a2218;
            padding-top: 4px;
        }

        .ttd-role {
            font-size: 9px;
            color: #4b6358;
        }

        .watermark {
            text-align: right;
            font-size: 9px;
            color: #cce8d9;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 8px 0 0 0;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            padding: 48px;
            text-align: center;
            color: #4b6358;
        }

        .empty-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .empty-title {
            font-size: 13px;
            font-weight: 600;
        }

        .empty-sub {
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $pengaturan = \App\Models\PengaturanModel::first();
        $now = now();

        $total = $records->count();
        $hadirCount = $records->where('status', 'hadir')->count();
        $lambatCount = $records->where('status', 'terlambat')->count();
        $izinCount = $records->where('status', 'izin')->count();
        $sakitCount = $records->where('status', 'sakit')->count();
        $alfaCount = $records->where('status', 'alfa')->count();

        $pct = fn($n) => $total > 0 ? round(($n / $total) * 100, 1) : 0;

        $statusMap = [
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alfa' => 'Alfa',
        ];
    @endphp

    <div class="page">

        {{-- ===== SCHOOL HEADER ===== --}}
        <div class="school-header">
            <table class="school-header-table">
                <tr>
                    <td style="width:72px; vertical-align:middle;">
                        @if ($pengaturan?->logo)
                            <img src="{{ public_path('storage/' . ltrim($pengaturan->logo, '/')) }}" class="school-logo"
                                alt="Logo">
                        @else
                            <div class="school-logo-placeholder">🏫</div>
                        @endif
                    </td>
                    <td class="school-divider-cell" style="vertical-align:middle;">
                        <div class="school-divider-line"></div>
                    </td>
                    <td style="vertical-align:middle;">
                        <div class="school-name">
                            {{ $pengaturan?->nama_sekolah ?? 'MAN 2 Kota Cilegon' }}
                        </div>
                        <div class="school-address">
                            📍
                            {{ $pengaturan?->alamat ?? 'Jalan Puskesmas Rawaarum, Bujang Gadung, Rawaarum, Grogol, Rw. Arum, Kec. Gerogol, Kota Cilegon, Banten 42036' }}
                        </div>
                        <div class="school-address" style="margin-top:2px;">
                            👤 Kepala Sekolah: {{ $pengaturan?->kepala_sekolah ?? 'H. Munirudin, S.Ag, MM.Pd' }}
                        </div>
                    </td>
                    <td style="vertical-align:middle; text-align:right; white-space:nowrap; padding-left:20px;">
                        <div class="date-box">
                            <div class="date-box-lbl">Dicetak pada</div>
                            <div class="date-box-val">{{ $now->translatedFormat('d F Y') }}</div>
                            <div class="date-box-time">{{ $now->format('H:i:s') }} WIB</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ===== DOC TITLE STRIP ===== --}}
        <div class="doc-title-strip">
            <table class="doc-title-strip-table">
                <tr>
                    <td>
                        <div class="doc-title">Laporan Absensi Siswa</div>
                        <div class="doc-sub">
                            Dicetak oleh: {{ auth()->user()?->name ?? 'Administrator' }} · Sistem Informasi Akademik
                        </div>
                    </td>
                    <td style="text-align:right;">
                        <div class="doc-sub">Tahun Pelajaran 2025/2026</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ===== STATS STRIP ===== --}}
        <div class="stats-strip">
            <table class="stats-table">
                <tr>
                    <td class="stat-cell">
                        <span class="stat-dot" style="background-color:#34d399;"></span>
                        <span class="stat-count">{{ $total }}</span>
                        <span class="stat-label">Total</span>
                    </td>
                    <td class="stat-cell">
                        <span class="stat-dot" style="background-color:#34d399;"></span>
                        <span class="stat-count">{{ $hadirCount }}</span>
                        <span class="stat-label">Hadir</span>
                    </td>
                    <td class="stat-cell">
                        <span class="stat-dot" style="background-color:#fbbf24;"></span>
                        <span class="stat-count">{{ $lambatCount }}</span>
                        <span class="stat-label">Terlambat</span>
                    </td>
                    <td class="stat-cell">
                        <span class="stat-dot" style="background-color:#60a5fa;"></span>
                        <span class="stat-count">{{ $izinCount }}</span>
                        <span class="stat-label">Izin</span>
                    </td>
                    <td class="stat-cell">
                        <span class="stat-dot" style="background-color:#a78bfa;"></span>
                        <span class="stat-count">{{ $sakitCount }}</span>
                        <span class="stat-label">Sakit</span>
                    </td>
                    <td class="stat-cell">
                        <span class="stat-dot" style="background-color:#f87171;"></span>
                        <span class="stat-count">{{ $alfaCount }}</span>
                        <span class="stat-label">Alfa</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- ===== BODY ===== --}}
        <div class="body">

            {{-- Summary Cards --}}
            <table class="summary-table">
                <tr>
                    <td class="summary-card sc-top-hadir">
                        <div class="sc-number-hadir">{{ $hadirCount }}</div>
                        <div class="sc-label">Hadir</div>
                        <div class="sc-pct">{{ $pct($hadirCount) }}% dari total</div>
                    </td>
                    <td style="width:8px;"></td>
                    <td class="summary-card sc-top-terlambat">
                        <div class="sc-number-terlambat">{{ $lambatCount }}</div>
                        <div class="sc-label">Terlambat</div>
                        <div class="sc-pct">{{ $pct($lambatCount) }}% dari total</div>
                    </td>
                    <td style="width:8px;"></td>
                    <td class="summary-card sc-top-izin">
                        <div class="sc-number-izin">{{ $izinCount }}</div>
                        <div class="sc-label">Izin</div>
                        <div class="sc-pct">{{ $pct($izinCount) }}% dari total</div>
                    </td>
                    <td style="width:8px;"></td>
                    <td class="summary-card sc-top-sakit">
                        <div class="sc-number-sakit">{{ $sakitCount }}</div>
                        <div class="sc-label">Sakit</div>
                        <div class="sc-pct">{{ $pct($sakitCount) }}% dari total</div>
                    </td>
                    <td style="width:8px;"></td>
                    <td class="summary-card sc-top-alfa">
                        <div class="sc-number-alfa">{{ $alfaCount }}</div>
                        <div class="sc-label">Alfa</div>
                        <div class="sc-pct">{{ $pct($alfaCount) }}% dari total</div>
                    </td>
                </tr>
            </table>

            {{-- Progress Bar --}}
            @if ($total > 0)
                <div class="progress-section">
                    <table class="progress-label-table">
                        <tr>
                            <td class="progress-label-left">Distribusi Kehadiran</td>
                            <td class="progress-label-right">{{ $pct($hadirCount + $lambatCount) }}% hadir (termasuk
                                terlambat)</td>
                        </tr>
                    </table>
                    {{-- Progress bar via table cells with background colors --}}
                    <table
                        style="width:100%; border-collapse:collapse; height:7px; border-radius:4px; overflow:hidden;">
                        <tr>
                            @if ($pct($hadirCount) > 0)
                                <td style="width:{{ $pct($hadirCount) }}%; background-color:#059669; height:7px;"></td>
                            @endif
                            @if ($pct($lambatCount) > 0)
                                <td style="width:{{ $pct($lambatCount) }}%; background-color:#d97706; height:7px;">
                                </td>
                            @endif
                            @if ($pct($izinCount) > 0)
                                <td style="width:{{ $pct($izinCount) }}%; background-color:#2563eb; height:7px;"></td>
                            @endif
                            @if ($pct($sakitCount) > 0)
                                <td style="width:{{ $pct($sakitCount) }}%; background-color:#7c3aed; height:7px;"></td>
                            @endif
                            @if ($pct($alfaCount) > 0)
                                <td style="width:{{ $pct($alfaCount) }}%; background-color:#dc2626; height:7px;"></td>
                            @endif
                            @php
                                $remaining =
                                    100 -
                                    $pct($hadirCount) -
                                    $pct($lambatCount) -
                                    $pct($izinCount) -
                                    $pct($sakitCount) -
                                    $pct($alfaCount);
                            @endphp
                            @if ($remaining > 0)
                                <td style="width:{{ $remaining }}%; background-color:#e6f4ed; height:7px;"></td>
                            @endif
                        </tr>
                    </table>
                </div>
            @endif

            {{-- Section Title --}}
            <table class="section-title-table">
                <tr>
                    <td class="section-title-bar-cell">
                        <div class="section-title-bar"></div>
                    </td>
                    <td>
                        <span class="section-title-h2">Daftar Absensi</span>
                        <span class="section-title-sub"> — {{ $total }} data ditemukan</span>
                    </td>
                </tr>
            </table>

            {{-- Data Table --}}
            <div class="table-wrap">
                @if ($records->isEmpty())
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p class="empty-title">Tidak ada data absensi</p>
                        <span class="empty-sub">Belum ada data yang sesuai filter.</span>
                    </div>
                @else
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:28px;">#</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Tanggal</th>
                                <th>Jam Masuk</th>
                                <th>Jam Keluar</th>
                                <th>Status</th>
                                <th>Face</th>
                                <th>Keterangan</th>
                                <th>Dicatat Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($records as $i => $row)
                                <tr class="{{ $i % 2 == 1 ? 'even-row' : '' }}">
                                    <td>
                                        <span class="row-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    </td>
                                    <td>
                                        <div class="siswa-name">{{ $row->siswa?->nama_lengkap ?? '—' }}</div>
                                        @if ($row->siswa?->nis)
                                            <div class="siswa-sub">NIS {{ $row->siswa->nis }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($row->kelas)
                                            <span class="kelas-badge">{{ $row->kelas->nama_kelas }}</span>
                                        @else
                                            <span style="color:#a7d9bc;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="mapel-text">
                                            {{ $row->jadwal?->mataPelajaran?->nama ?? ($row->jadwal?->nama ?? '—') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="date-cell">
                                            {{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d M Y') : '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-cell {{ $row->jam_masuk ? 'time-cell-filled' : '' }}">
                                            {{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-cell {{ $row->jam_keluar ? 'time-cell-filled' : '' }}">
                                            {{ $row->jam_keluar ? substr($row->jam_keluar, 0, 5) : '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php $st = $row->status ?? 'alfa'; @endphp
                                        <span class="badge badge-{{ $st }}">
                                            <span class="badge-dot dot-{{ $st }}"></span>
                                            {{ $statusMap[$st] ?? ucfirst($st) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($row->verified_by_face)
                                            <span class="face-chip face-verified">✓ Face</span>
                                        @else
                                            <span class="face-chip face-manual">Manual</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="keterangan-text">
                                            {{ $row->keterangan ? \Illuminate\Support\Str::limit($row->keterangan, 40) : '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dicatat-text">
                                            {{ $row->dicatatOleh?->name ?? '—' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Footer --}}
            <table class="footer-table" style="margin-top:24px; border-top:2px solid #c6e9d7; padding-top:14px;">
                <tr>
                    <td class="footer-left" style="vertical-align:bottom;">
                        Dokumen ini digenerate otomatis oleh <strong>Sistem Informasi Akademik</strong>.<br>
                        Tidak memerlukan tanda tangan basah.
                    </td>
                    <td class="footer-right" style="vertical-align:bottom; text-align:right;">
                        @if ($pengaturan?->kepala_sekolah)
                            <div class="ttd-box">
                                <div style="font-size:9px; color:#8fada3; margin-bottom:2px;">Mengetahui,</div>
                                <div style="font-size:9px; color:#8fada3;">Kepala Sekolah</div>
                                <div class="ttd-space"></div>
                                <div class="ttd-name">{{ $pengaturan->kepala_sekolah }}</div>
                                <div class="ttd-role">Kepala Sekolah</div>
                            </div>
                        @endif
                        <div style="font-family: DejaVu Sans Mono, monospace; font-size:10px; color:#a7d9bc;">
                            {{ $now->format('Y-m-d H:i:s') }}
                        </div>
                    </td>
                </tr>
            </table>

            <div class="watermark">CONFIDENTIAL</div>

        </div>{{-- end .body --}}

    </div>{{-- end .page --}}
</body>

</html>
