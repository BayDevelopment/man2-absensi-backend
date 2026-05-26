<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi — {{ now()->format('d M Y') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');

        :root {
            --navy: #0f1c3f;
            --navy-mid: #1a2f5e;
            --indigo: #2d4fad;
            --sky: #3b82f6;
            --sky-light: #eff6ff;
            --slate: #64748b;
            --slate-light: #f1f5f9;
            --border: #e2e8f0;
            --white: #ffffff;

            --green: #059669;
            --green-bg: #d1fae5;
            --yellow: #d97706;
            --yellow-bg: #fef3c7;
            --blue: #2563eb;
            --blue-bg: #dbeafe;
            --purple: #7c3aed;
            --purple-bg: #ede9fe;
            --red: #dc2626;
            --red-bg: #fee2e2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--white);
            color: var(--navy);
            font-size: 11px;
            line-height: 1.5;
        }

        /* ── PAGE WRAPPER ─────────────────────────────── */
        .page {
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
            padding: 0;
        }

        /* ── HEADER ───────────────────────────────────── */
        .header {
            background: var(--navy);
            padding: 32px 40px 28px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.12);
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -40px;
            right: 120px;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.07);
        }

        .header-inner {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {}

        .header-badge {
            display: inline-block;
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
            border: 1px solid rgba(59, 130, 246, 0.25);
        }

        .header h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--white);
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .header h1 span {
            color: #60a5fa;
        }

        .header-sub {
            color: #94a3b8;
            font-size: 11px;
            margin-top: 6px;
        }

        .header-right {
            text-align: right;
        }

        .header-date-box {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            padding: 12px 18px;
            text-align: right;
        }

        .header-date-box .date-label {
            color: #94a3b8;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .header-date-box .date-value {
            color: var(--white);
            font-size: 14px;
            font-weight: 700;
            margin-top: 2px;
        }

        .header-date-box .time-value {
            color: #60a5fa;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            margin-top: 2px;
        }

        /* ── STATS STRIP ──────────────────────────────── */
        .stats-strip {
            background: var(--navy-mid);
            padding: 0 40px;
            display: flex;
            gap: 0;
        }

        .stat-item {
            padding: 14px 24px 14px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 24px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .stat-info {}

        .stat-count {
            font-size: 18px;
            font-weight: 800;
            color: var(--white);
            line-height: 1;
        }

        .stat-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: #94a3b8;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* ── BODY ─────────────────────────────────────── */
        .body {
            padding: 28px 40px 40px;
        }

        /* ── SECTION TITLE ────────────────────────────── */
        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }

        .section-title-bar {
            width: 4px;
            height: 18px;
            background: var(--sky);
            border-radius: 2px;
        }

        .section-title h2 {
            font-size: 13px;
            font-weight: 700;
            color: var(--navy);
        }

        .section-title span {
            font-size: 10px;
            color: var(--slate);
            font-weight: 500;
        }

        /* ── TABLE ────────────────────────────────────── */
        .table-wrap {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: 0 1px 8px rgba(15, 28, 63, 0.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: var(--slate-light);
        }

        thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 9px;
            font-weight: 700;
            color: var(--slate);
            letter-spacing: 1px;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        thead th:first-child {
            padding-left: 16px;
        }

        thead th:last-child {
            padding-right: 16px;
        }

        tbody tr {
            transition: background 0.15s;
        }

        tbody tr:nth-child(even) {
            background: #fafbfd;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            color: var(--navy);
            vertical-align: middle;
        }

        tbody td:first-child {
            padding-left: 16px;
        }

        tbody td:last-child {
            padding-right: 16px;
        }

        /* Row number */
        .row-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: var(--slate);
            font-weight: 500;
        }

        /* Siswa cell */
        .siswa-name {
            font-weight: 700;
            font-size: 11px;
            color: var(--navy);
        }

        .siswa-sub {
            font-size: 9px;
            color: var(--slate);
            margin-top: 1px;
        }

        /* Kelas badge */
        .kelas-badge {
            display: inline-block;
            background: var(--sky-light);
            color: var(--indigo);
            font-size: 9px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            border: 1px solid #bfdbfe;
            white-space: nowrap;
        }

        /* Mapel */
        .mapel-text {
            font-size: 10px;
            color: var(--slate);
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Date cell */
        .date-cell {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            font-weight: 500;
            color: var(--navy);
            white-space: nowrap;
        }

        /* Time cell */
        .time-cell {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            color: var(--slate);
            white-space: nowrap;
        }

        .time-cell.filled {
            color: var(--navy);
            font-weight: 500;
        }

        .time-separator {
            color: var(--border);
            margin: 0 2px;
        }

        /* Status badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 9.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .badge.hadir {
            background: var(--green-bg);
            color: var(--green);
        }

        .badge.hadir .badge-dot {
            background: var(--green);
        }

        .badge.terlambat {
            background: var(--yellow-bg);
            color: var(--yellow);
        }

        .badge.terlambat .badge-dot {
            background: var(--yellow);
        }

        .badge.izin {
            background: var(--blue-bg);
            color: var(--blue);
        }

        .badge.izin .badge-dot {
            background: var(--blue);
        }

        .badge.sakit {
            background: var(--purple-bg);
            color: var(--purple);
        }

        .badge.sakit .badge-dot {
            background: var(--purple);
        }

        .badge.alfa {
            background: var(--red-bg);
            color: var(--red);
        }

        .badge.alfa .badge-dot {
            background: var(--red);
        }

        /* Face verified */
        .face-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 9px;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .face-chip.verified {
            background: #d1fae5;
            color: #065f46;
        }

        .face-chip.manual {
            background: #f1f5f9;
            color: #64748b;
        }

        /* Keterangan */
        .keterangan-text {
            font-size: 10px;
            color: var(--slate);
            font-style: italic;
            max-width: 140px;
        }

        /* ── EMPTY STATE ──────────────────────────────── */
        .empty-state {
            padding: 48px;
            text-align: center;
            color: var(--slate);
        }

        .empty-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 13px;
            font-weight: 600;
        }

        .empty-state span {
            font-size: 11px;
            display: block;
            margin-top: 4px;
        }

        /* ── SUMMARY CARDS ────────────────────────────── */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 24px;
        }

        .summary-card {
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .summary-card.hadir::before {
            background: var(--green);
        }

        .summary-card.terlambat::before {
            background: var(--yellow);
        }

        .summary-card.izin::before {
            background: var(--blue);
        }

        .summary-card.sakit::before {
            background: var(--purple);
        }

        .summary-card.alfa::before {
            background: var(--red);
        }

        .summary-card .sc-number {
            font-size: 24px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .summary-card.hadir .sc-number {
            color: var(--green);
        }

        .summary-card.terlambat .sc-number {
            color: var(--yellow);
        }

        .summary-card.izin .sc-number {
            color: var(--blue);
        }

        .summary-card.sakit .sc-number {
            color: var(--purple);
        }

        .summary-card.alfa .sc-number {
            color: var(--red);
        }

        .summary-card .sc-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--slate);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-card .sc-pct {
            font-size: 9px;
            color: var(--slate);
            margin-top: 2px;
        }

        /* ── PROGRESS BAR ─────────────────────────────── */
        .progress-section {
            margin-bottom: 24px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 10px;
            color: var(--slate);
        }

        .progress-bar-wrap {
            height: 8px;
            background: var(--slate-light);
            border-radius: 4px;
            overflow: hidden;
            display: flex;
        }

        .progress-segment {
            height: 100%;
            transition: width 0.3s;
        }

        /* ── FOOTER ───────────────────────────────────── */
        .footer {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-left {
            font-size: 10px;
            color: var(--slate);
        }

        .footer-left strong {
            color: var(--navy);
        }

        .footer-right {
            font-size: 10px;
            color: var(--slate);
            font-family: 'JetBrains Mono', monospace;
        }

        .page-watermark {
            position: fixed;
            bottom: 20px;
            right: 30px;
            font-size: 9px;
            color: #cbd5e1;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ── PRINT ────────────────────────────────────── */
        @media print {
            body {
                margin: 0;
            }

            .page {
                max-width: 100%;
            }

            @page {
                margin: 0;
                size: A4 landscape;
            }
        }
    </style>
</head>

<body>

    @php
        $total = $records->count();
        $hadirCount = $records->where('status', 'hadir')->count();
        $lambatCount = $records->where('status', 'terlambat')->count();
        $izinCount = $records->where('status', 'izin')->count();
        $sakitCount = $records->where('status', 'sakit')->count();
        $alfaCount = $records->where('status', 'alfa')->count();

        $pct = fn($n) => $total > 0 ? round(($n / $total) * 100, 1) : 0;
    @endphp

    <div class="page">

        {{-- ── HEADER ── --}}
        <div class="header">
            <div class="header-inner">
                <div class="header-left">
                    <div class="header-badge">Laporan Resmi</div>
                    <h1>Data <span>Absensi</span><br>Siswa</h1>
                    <p class="header-sub">Sistem Informasi Akademik &nbsp;·&nbsp; Dicetak oleh
                        {{ auth()->user()?->name ?? 'Administrator' }}</p>
                </div>
                <div class="header-right">
                    <div class="header-date-box">
                        <div class="date-label">Dicetak pada</div>
                        <div class="date-value">{{ now()->translatedFormat('d F Y') }}</div>
                        <div class="time-value">{{ now()->format('H:i:s') }} WIB</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── STATS STRIP ── --}}
        <div class="stats-strip">
            <div class="stat-item">
                <div class="stat-dot" style="background:#60a5fa;"></div>
                <div class="stat-info">
                    <div class="stat-count">{{ $total }}</div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-dot" style="background:#34d399;"></div>
                <div class="stat-info">
                    <div class="stat-count">{{ $hadirCount }}</div>
                    <div class="stat-label">Hadir</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-dot" style="background:#fbbf24;"></div>
                <div class="stat-info">
                    <div class="stat-count">{{ $lambatCount }}</div>
                    <div class="stat-label">Terlambat</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-dot" style="background:#60a5fa;"></div>
                <div class="stat-info">
                    <div class="stat-count">{{ $izinCount }}</div>
                    <div class="stat-label">Izin</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-dot" style="background:#a78bfa;"></div>
                <div class="stat-info">
                    <div class="stat-count">{{ $sakitCount }}</div>
                    <div class="stat-label">Sakit</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-dot" style="background:#f87171;"></div>
                <div class="stat-info">
                    <div class="stat-count">{{ $alfaCount }}</div>
                    <div class="stat-label">Alfa</div>
                </div>
            </div>
        </div>

        {{-- ── BODY ── --}}
        <div class="body">

            {{-- Summary Cards --}}
            <div class="summary-grid">
                <div class="summary-card hadir">
                    <div class="sc-number">{{ $hadirCount }}</div>
                    <div class="sc-label">Hadir</div>
                    <div class="sc-pct">{{ $pct($hadirCount) }}% dari total</div>
                </div>
                <div class="summary-card terlambat">
                    <div class="sc-number">{{ $lambatCount }}</div>
                    <div class="sc-label">Terlambat</div>
                    <div class="sc-pct">{{ $pct($lambatCount) }}% dari total</div>
                </div>
                <div class="summary-card izin">
                    <div class="sc-number">{{ $izinCount }}</div>
                    <div class="sc-label">Izin</div>
                    <div class="sc-pct">{{ $pct($izinCount) }}% dari total</div>
                </div>
                <div class="summary-card sakit">
                    <div class="sc-number">{{ $sakitCount }}</div>
                    <div class="sc-label">Sakit</div>
                    <div class="sc-pct">{{ $pct($sakitCount) }}% dari total</div>
                </div>
                <div class="summary-card alfa">
                    <div class="sc-number">{{ $alfaCount }}</div>
                    <div class="sc-label">Alfa</div>
                    <div class="sc-pct">{{ $pct($alfaCount) }}% dari total</div>
                </div>
            </div>

            {{-- Progress bar --}}
            @if ($total > 0)
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Distribusi Kehadiran</span>
                        <span>{{ $pct($hadirCount + $lambatCount) }}% hadir</span>
                    </div>
                    <div class="progress-bar-wrap">
                        <div class="progress-segment" style="width:{{ $pct($hadirCount) }}%; background:#059669;">
                        </div>
                        <div class="progress-segment" style="width:{{ $pct($lambatCount) }}%; background:#d97706;">
                        </div>
                        <div class="progress-segment" style="width:{{ $pct($izinCount) }}%; background:#2563eb;"></div>
                        <div class="progress-segment" style="width:{{ $pct($sakitCount) }}%; background:#7c3aed;">
                        </div>
                        <div class="progress-segment" style="width:{{ $pct($alfaCount) }}%; background:#dc2626;"></div>
                    </div>
                </div>
            @endif

            {{-- Table --}}
            <div class="section-title">
                <div class="section-title-bar"></div>
                <h2>Daftar Absensi</h2>
                <span>— {{ $total }} data ditemukan</span>
            </div>

            <div class="table-wrap">
                @if ($records->isEmpty())
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <p>Tidak ada data absensi</p>
                        <span>Belum ada data yang sesuai dengan filter yang dipilih.</span>
                    </div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th style="width:36px;">#</th>
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
                                <tr>
                                    <td><span class="row-num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span></td>

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
                                            <span style="color:#94a3b8;">—</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="mapel-text">
                                            {{ $row->jadwal?->mataPelajaran?->nama ?? ($row->jadwal?->nama ?? '—') }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="date-cell">
                                            {{ $row->tanggal ? \Carbon\Carbon::parse($row->tanggal)->format('d M Y') : '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="time-cell {{ $row->jam_masuk ? 'filled' : '' }}">
                                            {{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="time-cell {{ $row->jam_keluar ? 'filled' : '' }}">
                                            {{ $row->jam_keluar ? substr($row->jam_keluar, 0, 5) : '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        @php
                                            $statusMap = [
                                                'hadir' => 'Hadir',
                                                'terlambat' => 'Terlambat',
                                                'izin' => 'Izin',
                                                'sakit' => 'Sakit',
                                                'alfa' => 'Alfa',
                                            ];
                                        @endphp
                                        <span class="badge {{ $row->status ?? 'alfa' }}">
                                            <span class="badge-dot"></span>
                                            {{ $statusMap[$row->status] ?? ucfirst($row->status ?? 'Alfa') }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($row->verified_by_face)
                                            <span class="face-chip verified">✓ Face</span>
                                        @else
                                            <span class="face-chip manual">Manual</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="keterangan-text">
                                            {{ $row->keterangan ? \Illuminate\Support\Str::limit($row->keterangan, 40) : '—' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span style="font-size:10px; color:#64748b;">
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
            <div class="footer">
                <div class="footer-left">
                    Dokumen ini digenerate otomatis oleh <strong>Sistem Informasi Akademik</strong>.
                    Tidak memerlukan tanda tangan.
                </div>
                <div class="footer-right">
                    {{ now()->format('Y-m-d H:i:s') }}
                </div>
            </div>

        </div>{{-- end .body --}}

    </div>{{-- end .page --}}

    <div class="page-watermark">CONFIDENTIAL</div>

</body>

</html>
