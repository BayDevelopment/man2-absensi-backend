<div class="space-y-4 p-1">

    {{-- ── INFO UTAMA ── --}}
    <div class="grid grid-cols-2 gap-3 text-sm">

        <div class="col-span-2 rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Nama Siswa</p>
            <p class="font-bold text-gray-900 dark:text-white text-base">
                {{ $record->siswa?->nama_lengkap ?? '—' }}
            </p>
        </div>

        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Kelas</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $record->kelas?->nama_kelas ?? '—' }}
            </p>
        </div>

        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Mata Pelajaran</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $record->jadwal?->mataPelajaran?->nama ?? '—' }}
            </p>
        </div>

        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Tanggal</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $record->tanggal ? \Carbon\Carbon::parse($record->tanggal)->translatedFormat('d F Y') : '—' }}
            </p>
        </div>

        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Status</p>
            @php
                $statusMap = [
                    'hadir' => ['label' => '✅ Hadir', 'class' => 'bg-green-100 text-green-800'],
                    'terlambat' => ['label' => '⏰ Terlambat', 'class' => 'bg-yellow-100 text-yellow-800'],
                    'izin' => ['label' => '📝 Izin', 'class' => 'bg-blue-100 text-blue-800'],
                    'sakit' => ['label' => '🏥 Sakit', 'class' => 'bg-red-100 text-red-800'],
                    'alfa' => ['label' => '❌ Alfa', 'class' => 'bg-red-100 text-red-800'],
                ];
                $statusInfo = $statusMap[$record->status] ?? [
                    'label' => $record->status,
                    'class' => 'bg-gray-100 text-gray-800',
                ];
            @endphp
            <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusInfo['class'] }}">
                {{ $statusInfo['label'] }}
            </span>
        </div>

        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Jam Masuk</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $record->jam_masuk ? substr($record->jam_masuk, 0, 5) : '—' }}
            </p>
        </div>

        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Jam Keluar</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $record->jam_keluar ? substr($record->jam_keluar, 0, 5) : '—' }}
            </p>
        </div>

        {{-- Verifikasi Wajah --}}
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Verifikasi Wajah</p>
            @if ($record->verified_by_face)
                <span class="inline-flex items-center gap-1 text-green-700 font-semibold text-sm">
                    ✅ Terverifikasi
                    @if ($record->face_confidence)
                        <span class="text-xs text-gray-400">({{ $record->face_confidence }}%)</span>
                    @endif
                </span>
            @else
                <span class="text-gray-400 text-sm">Tidak</span>
            @endif
        </div>

        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Dicatat Oleh</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200">
                {{ $record->dicatatOleh?->name ?? '—' }}
            </p>
        </div>

    </div>

    {{-- ── KETERANGAN ── --}}
    @if ($record->keterangan)
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Keterangan</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                {{ $record->keterangan }}
            </p>
        </div>
    @endif

    {{-- ── BUKTI SURAT SAKIT ── --}}
    @if ($record->status === 'sakit' || $record->dokumen_pendukung_path)
        <div
            class="rounded-lg border-2 border-dashed border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
            <p
                class="text-xs font-bold uppercase tracking-wide text-red-600 dark:text-red-400 mb-3 flex items-center gap-1">
                🏥 Bukti Surat Sakit
            </p>

            @if ($record->dokumen_pendukung_path)
                @php
                    $ext = strtolower(pathinfo($record->dokumen_pendukung_path, PATHINFO_EXTENSION));
                    $url = asset('storage/' . ltrim($record->dokumen_pendukung_path, '/'));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                    $isPdf = $ext === 'pdf';
                @endphp

                @if ($isImage)
                    {{-- Tampilkan gambar langsung --}}
                    <div class="rounded-lg overflow-hidden border border-red-200 mb-3">
                        <img src="{{ $url }}" alt="Bukti Surat Sakit"
                            class="w-full max-h-72 object-contain bg-white"
                            onerror="this.parentElement.innerHTML='<p class=\'text-sm text-red-500 p-3\'>Gambar tidak dapat ditampilkan.</p>'" />
                    </div>
                @elseif($isPdf)
                    {{-- PDF: tampilkan icon + tombol buka --}}
                    <div
                        class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-lg p-3 border border-red-200 mb-3">
                        <div
                            class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-content-center flex-shrink-0 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">
                                {{ basename($record->dokumen_pendukung_path) }}
                            </p>
                            <p class="text-xs text-gray-400">Dokumen PDF</p>
                        </div>
                    </div>
                @endif

                {{-- Tombol Download / Buka --}}

                href="{{ $url }}"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors"
                >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                {{ $isPdf ? 'Buka PDF' : 'Lihat Gambar Penuh' }}
                </a>
            @else
                {{-- Belum ada dokumen --}}
                <div class="flex items-center gap-3 text-yellow-700 dark:text-yellow-400">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm font-medium">Belum ada dokumen pendukung yang diunggah.</p>
                </div>
            @endif
        </div>
    @endif

</div>
