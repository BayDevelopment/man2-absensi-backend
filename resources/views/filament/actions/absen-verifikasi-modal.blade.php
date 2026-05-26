{{--
    ============================================================
    ABSEN MASUK — VERIFIKASI WAJAH
    Filament v3+ · face-api.js via CDN
    ============================================================
    Props yang diharapkan dari Action/Page:
      $jadwal    – object    (mata_pelajaran, kelas, guru, jam_mulai, jam_selesai)
      $siswaList – Collection (siswa dengan face_descriptor & face_image_path)
      $threshold – float     default 0.42
    ============================================================
--}}

@once
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* ── Corner-frame guide ─────────────────────────────────── */
        .cf-wrap::before,
        .cf-wrap::after,
        .cf-wrap>span::before,
        .cf-wrap>span::after {
            content: '';
            position: absolute;
            width: 22px;
            height: 22px;
            border-color: inherit;
            border-style: solid;
        }

        .cf-wrap::before {
            top: -2px;
            left: -2px;
            border-width: 3px 0 0 3px;
            border-radius: 5px 0 0 0;
        }

        .cf-wrap::after {
            top: -2px;
            right: -2px;
            border-width: 3px 3px 0 0;
            border-radius: 0 5px 0 0;
        }

        .cf-wrap>span::before {
            bottom: -2px;
            left: -2px;
            border-width: 0 0 3px 3px;
            border-radius: 0 0 0 5px;
        }

        .cf-wrap>span::after {
            bottom: -2px;
            right: -2px;
            border-width: 0 3px 3px 0;
            border-radius: 0 0 5px 0;
        }

        /* ── Scan sweep ─────────────────────────────────────────── */
        @keyframes sweep {
            0% {
                top: 10%;
                opacity: 1;
            }

            80% {
                opacity: 1;
            }

            100% {
                top: 90%;
                opacity: 0;
            }
        }

        .scan-sweep::after {
            content: '';
            position: absolute;
            left: 5%;
            right: 5%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #22c55e, transparent);
            animation: sweep 1.6s ease-in-out infinite;
            border-radius: 99px;
            box-shadow: 0 0 8px #22c55e88;
        }

        /* ── Match ripple ───────────────────────────────────────── */
        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: .6;
            }

            100% {
                transform: scale(2.4);
                opacity: 0;
            }
        }

        .match-ripple::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: rgba(34, 197, 94, .4);
            animation: ripple .8s ease-out forwards;
        }

        /* ── Utilities ──────────────────────────────────────────── */
        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fsu {
            animation: fadeSlideUp .3s ease-out both;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .shimmer {
            background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
            background-size: 200% 100%;
            animation: shimmer 1.4s infinite;
        }
    </style>

    <script>
        /* ── face-api loader (singleton, race-condition safe) ──── */
        window._faceApiPromise = window._faceApiPromise ?? null;

        window.loadFaceApiScript = function() {
            if (window._faceApiPromise) return window._faceApiPromise;

            window._faceApiPromise = new Promise((resolve, reject) => {
                if (window.faceapi) {
                    resolve(window.faceapi);
                    return;
                }

                const existing = document.querySelector('script[data-face-api]');
                if (existing) {
                    existing.addEventListener('load', () => resolve(window.faceapi), {
                        once: true
                    });
                    existing.addEventListener('error', reject, {
                        once: true
                    });
                    return;
                }

                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
                s.async = true;
                s.dataset.faceApi = 'true';
                s.onload = () => resolve(window.faceapi);
                s.onerror = (err) => {
                    window._faceApiPromise = null;
                    reject(err);
                };
                document.head.appendChild(s);
            });

            return window._faceApiPromise;
        };

        /* ── Alpine component factory ──────────────────────────── */
        window.absenVerifikasi = function({
            siswaList,
            threshold
        }) {
            return {
                /* — state — */
                step: 1, // 1 posisikan | 2 verifikasi | 3 selesai
                modelsLoaded: false,
                modelsLoading: false,
                loadingText: 'Memuat model AI…',
                errorMsg: '',

                cameraActive: false,
                _stream: null,
                _detectionInterval: null,
                _detecting: false,

                faceDetected: false,
                multipleFaces: false,
                scanning: false,

                matchResult: null, // { siswa, distance, confidence }
                matchError: '',

                /* — init — */
                async init() {
                    this.modelsLoading = true;

                    try {
                        await window.loadFaceApiScript();

                        const BASE = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights';

                        await Promise.all([
                            faceapi.nets.tinyFaceDetector.loadFromUri(BASE),
                            faceapi.nets.faceLandmark68TinyNet.loadFromUri(BASE),
                            faceapi.nets.faceRecognitionNet.loadFromUri(BASE),
                        ]);

                        this.modelsLoaded = true;
                    } catch (err) {
                        console.error('[AbsenVerifikasi] loadModels:', err);
                        this.errorMsg = 'Gagal memuat model AI. Periksa koneksi internet.';
                    } finally {
                        this.modelsLoading = false;
                    }
                },

                /* — camera — */
                async startCamera() {
                    this.errorMsg = '';
                    this.matchResult = null;
                    this.matchError = '';
                    this.stopCamera();

                    try {
                        if (!navigator.mediaDevices?.getUserMedia) {
                            throw Object.assign(new Error('Browser tidak mendukung kamera.'), {
                                name: 'NotSupportedError'
                            });
                        }

                        this._stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                width: {
                                    ideal: 640
                                },
                                height: {
                                    ideal: 480
                                },
                                facingMode: 'user'
                            },
                            audio: false,
                        });

                        const video = this.$refs.video;

                        video.srcObject = this._stream;
                        await new Promise(resolve => {
                            if (video.readyState >= 2) {
                                resolve();
                                return;
                            }
                            video.onloadedmetadata = resolve;
                        });

                        await video.play();

                        this.cameraActive = true;
                        this.step = 1;
                        this.startDetectionLoop();
                    } catch (err) {
                        console.error('[AbsenVerifikasi] startCamera:', err);
                        this.errorMsg = 'Kamera tidak dapat diakses. Pastikan izin kamera sudah diberikan.';
                        this.stopCamera();
                    }
                },

                stopCamera() {
                    this._stream?.getTracks().forEach(t => t.stop());
                    this._stream = null;

                    if (this._detectionInterval) {
                        clearInterval(this._detectionInterval);
                        this._detectionInterval = null;
                    }

                    if (this.$refs.video) {
                        this.$refs.video.pause?.();
                        this.$refs.video.srcObject = null;
                    }

                    this.cameraActive = false;
                    this.faceDetected = false;
                    this.multipleFaces = false;
                    this.scanning = false;
                    this._detecting = false;
                },

                /* — detection loop — */
                startDetectionLoop() {
                    if (this._detectionInterval) clearInterval(this._detectionInterval);

                    this._detectionInterval = setInterval(async () => {
                        if (
                            this._detecting ||
                            !this.cameraActive ||
                            !this.$refs.video ||
                            !this.$refs.overlayCanvas
                        ) return;

                        const video = this.$refs.video;
                        const canvas = this.$refs.overlayCanvas;

                        if (!video.videoWidth) return;

                        this._detecting = true;

                        try {
                            const displaySize = {
                                width: video.videoWidth,
                                height: video.videoHeight
                            };

                            faceapi.matchDimensions(canvas, displaySize);

                            const detections = await faceapi
                                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({
                                    scoreThreshold: 0.5
                                }))
                                .withFaceLandmarks(true)
                                .withFaceDescriptors();

                            const ctx = canvas.getContext('2d');
                            ctx.clearRect(0, 0, canvas.width, canvas.height);

                            const resized = faceapi.resizeResults(detections, displaySize);

                            if (resized.length !== 1) {
                                this.faceDetected = false;
                                this.multipleFaces = resized.length > 1;
                                resized.forEach(d => this.drawBox(ctx, d.detection.box, '#ef4444'));
                                return;
                            }

                            this.faceDetected = true;
                            this.multipleFaces = false;

                            faceapi.draw.drawFaceLandmarks(canvas, resized);
                            this.drawBox(ctx, resized[0].detection.box, this.scanning ? '#22c55e' :
                                '#60a5fa');

                            // Jalankan match hanya jika masih dalam mode scanning
                            if (this.scanning && resized[0]?.descriptor) {
                                await this.runMatch(Array.from(resized[0].descriptor));
                            }
                        } catch (err) {
                            console.error('[AbsenVerifikasi] detect:', err);
                        } finally {
                            this._detecting = false;
                        }
                    }, 300);
                },

                drawBox(ctx, box, color) {
                    ctx.strokeStyle = color;
                    ctx.lineWidth = 2;

                    if (typeof ctx.roundRect === 'function') {
                        ctx.beginPath();
                        ctx.roundRect(box.x, box.y, box.width, box.height, 8);
                        ctx.stroke();
                        return;
                    }

                    ctx.strokeRect(box.x, box.y, box.width, box.height);
                },

                /* — verification — */
                startVerifikasi() {
                    if (!this.faceDetected || this.multipleFaces) return;

                    this.errorMsg = '';
                    this.matchError = '';
                    this.matchResult = null;
                    this.scanning = true;
                    this.step = 2;
                },

                async runMatch(descriptor) {
                    // Set false dulu agar detection loop tidak masuk lagi selama proses matching
                    this.scanning = false;

                    const labeledDescriptors = siswaList
                        .filter(s => Array.isArray(s.face_descriptor) && s.face_descriptor.length === 128)
                        .map(s => new faceapi.LabeledFaceDescriptors(
                            String(s.id),
                            [new Float32Array(s.face_descriptor)]
                        ));

                    if (!labeledDescriptors.length) {
                        this.matchError = 'Tidak ada siswa terdaftar dengan data wajah.';
                        this.step = 1;
                        return;
                    }

                    const matcher = new faceapi.FaceMatcher(labeledDescriptors, threshold ?? 0.42);
                    const best = matcher.findBestMatch(new Float32Array(descriptor));

                    if (best.label === 'unknown') {
                        this.matchError = 'Wajah tidak dikenali. Pastikan siswa sudah mendaftarkan wajah.';
                        this.step = 1;
                        return;
                    }

                    const siswa = siswaList.find(s => String(s.id) === best.label);
                    const confidence = Math.max(0, Math.min(100, Math.round((1 - best.distance) * 100)));

                    this.matchResult = {
                        siswa,
                        distance: best.distance,
                        confidence
                    };
                    this.step = 3;
                    this.stopCamera();
                },

                reset() {
                    this.step = 1;
                    this.matchResult = null;
                    this.matchError = '';
                    this.scanning = false;
                    this.startCamera();
                },
            };
        };
    </script>
@endonce

{{-- ============================================================
     VIEW
     ============================================================ --}}
<div wire:ignore x-data="window.absenVerifikasi({
    siswaList: @js($siswaList ?? []),
    threshold: @js($threshold ?? 0.42),
})" x-init="init()" class="space-y-5 pb-1">

    {{-- ── Step indicator ──────────────────────────────────────── --}}
    <div class="flex items-center justify-center gap-0">
        <template x-for="(label, idx) in ['Posisikan wajah', 'Verifikasi', 'Selesai']" :key="idx">
            <div class="flex items-center">
                {{-- Circle --}}
                <div class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-all duration-300 ring-2"
                    :class="{
                        'bg-primary-600 text-white ring-primary-600 shadow-md': step === idx + 1,
                        'bg-green-500   text-white ring-green-500': step > idx + 1,
                        'bg-white text-gray-400 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700': step < idx + 1,
                    }">
                    <template x-if="step > idx + 1">
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="step <= idx + 1">
                        <span x-text="idx + 1"></span>
                    </template>
                </div>

                {{-- Label --}}
                <span class="ml-2 text-xs font-medium transition-colors duration-200"
                    :class="step === idx + 1 ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400 dark:text-gray-600'"
                    x-text="label"></span>

                {{-- Connector --}}
                <template x-if="idx < 2">
                    <div class="mx-3 h-px w-10 transition-colors duration-300"
                        :class="step > idx + 1 ? 'bg-green-400' : 'bg-gray-200 dark:bg-gray-700'"></div>
                </template>
            </div>
        </template>
    </div>

    {{-- ── Jadwal card ──────────────────────────────────────────── --}}
    @isset($jadwal)
        <div
            class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/5">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 10.741-3.342" />
                </svg>
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                    {{ $jadwal->mata_pelajaran ?? '—' }}
                    @isset($jadwal->kelas)
                        <span class="font-normal text-gray-500">— {{ $jadwal->kelas }}</span>
                    @endisset
                </p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    @isset($jadwal->guru)
                        {{ $jadwal->guru }} ·
                    @endisset
                    {{ $jadwal->jam_mulai ?? '' }}–{{ $jadwal->jam_selesai ?? '' }}
                </p>
            </div>

            <div class="shrink-0 text-right">
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ now()->format('H:i') }}</p>
                <p class="text-xs text-gray-400">{{ now()->translatedFormat('d M Y') }}</p>
            </div>
        </div>
    @endisset

    {{-- ── Loading bar ──────────────────────────────────────────── --}}
    <div x-show="modelsLoading" x-cloak
        class="fsu flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-950/40">
        <svg class="h-4 w-4 shrink-0 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
            </circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <div class="flex-1 space-y-1.5">
            <p class="text-xs font-medium text-blue-700 dark:text-blue-300" x-text="loadingText"></p>
            <div class="h-1 w-full overflow-hidden rounded-full bg-blue-100 dark:bg-blue-900">
                <div class="shimmer h-full w-full rounded-full"></div>
            </div>
        </div>
    </div>

    {{-- ── Error banners ────────────────────────────────────────── --}}
    <div x-show="errorMsg" x-cloak
        class="fsu flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800/60 dark:bg-red-950/40">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
        <p class="text-xs text-red-700 dark:text-red-300" x-text="errorMsg"></p>
    </div>

    <div x-show="matchError" x-cloak
        class="fsu flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800/60 dark:bg-amber-950/40">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374L10.05 3.378c.866-1.5 3.032-1.5 3.898 0l6.355 9.748ZM12 15.75h.008v.008H12v-.008Z" />
        </svg>
        <p class="text-xs text-amber-700 dark:text-amber-300" x-text="matchError"></p>
    </div>

    {{-- ── STEP 1 & 2: Camera ───────────────────────────────────── --}}
    <div x-show="step <= 2">

        {{-- Viewport --}}
        <div class="relative w-full overflow-hidden rounded-2xl bg-gray-950 shadow-xl" style="aspect-ratio: 16/10;"
            :class="{
                'ring-2 ring-green-400 ring-offset-2 ring-offset-white dark:ring-offset-gray-900': faceDetected && !
                    multipleFaces && !scanning,
                'ring-2 ring-red-500  ring-offset-2': multipleFaces,
                'ring-2 ring-blue-400 ring-offset-2': scanning,
                'ring-1 ring-gray-700': !faceDetected && !multipleFaces && !scanning && cameraActive,
                'ring-1 ring-gray-800': !cameraActive,
            }">
            <video x-ref="video" autoplay playsinline muted class="absolute inset-0 h-full w-full object-cover"
                x-show="cameraActive"></video>

            <canvas x-ref="overlayCanvas" class="pointer-events-none absolute inset-0 h-full w-full"
                x-show="cameraActive"></canvas>

            {{-- Corner-frame guide --}}
            <div x-show="cameraActive" class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <div class="cf-wrap relative" style="width: 42%; height: 78%;"
                    :class="{
                        'border-green-400': faceDetected && !multipleFaces,
                        'border-red-400': multipleFaces,
                        'border-white/25': !faceDetected && !multipleFaces,
                    }">
                    <span></span>
                    <div x-show="scanning" class="scan-sweep absolute inset-0"></div>
                </div>
            </div>

            {{-- Idle placeholder --}}
            <div x-show="!cameraActive"
                class="absolute inset-0 flex flex-col items-center justify-center gap-4 text-gray-500">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/5 ring-1 ring-white/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <div class="text-center">
                    <p class="text-sm font-semibold text-gray-400">Kamera belum aktif</p>
                    <p class="mt-1 text-xs text-gray-600">Klik tombol di bawah untuk mengaktifkan kamera dan mulai
                        verifikasi</p>
                </div>
            </div>

            {{-- Scanning overlay --}}
            <div x-show="scanning" x-cloak
                class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-gray-950/90 to-transparent px-5 pb-4 pt-8 text-center">
                <p class="text-xs font-semibold tracking-wide text-white/90">
                    <span
                        class="inline-block h-2 w-2 rounded-full bg-green-400 align-middle mr-1.5 animate-pulse"></span>
                    Memproses pencocokan wajah…
                </p>
            </div>

            {{-- Face detected badge --}}
            <div x-show="faceDetected && !multipleFaces && !scanning" x-cloak class="fsu absolute left-3 top-3">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-green-500/90 px-2.5 py-1 text-xs font-bold text-white shadow backdrop-blur-sm">
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                            clip-rule="evenodd" />
                    </svg>
                    Wajah Terdeteksi
                </span>
            </div>

            {{-- Multiple faces badge --}}
            <div x-show="multipleFaces" x-cloak class="fsu absolute left-3 top-3">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-red-500/90 px-2.5 py-1 text-xs font-bold text-white shadow backdrop-blur-sm">
                    ⚠ Lebih dari 1 wajah
                </span>
            </div>

            {{-- Models loading overlay --}}
            <div x-show="modelsLoading"
                class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gray-950/80 backdrop-blur-sm">
                <svg class="h-8 w-8 animate-spin text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                    </path>
                </svg>
                <p class="text-xs font-medium text-gray-400" x-text="loadingText"></p>
            </div>
        </div>

        {{-- Info hint --}}
        <div
            class="mt-3 flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-2.5 dark:border-blue-900/50 dark:bg-blue-950/30">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>
            <p class="text-xs text-blue-700 dark:text-blue-300">
                Pastikan wajah berada di tengah frame, pencahayaan cukup, dan tidak menggunakan masker atau topi.
            </p>
        </div>
    </div>

    {{-- ── STEP 3: Match result ────────────────────────────────── --}}
    <div x-show="step === 3 && matchResult" x-cloak class="fsu space-y-4">

        {{-- Success card --}}
        <div
            class="overflow-hidden rounded-2xl border border-green-200 bg-green-50 dark:border-green-800/50 dark:bg-green-950/30">
            <div class="flex items-center gap-4 p-5">

                {{-- Avatar --}}
                <div class="relative shrink-0">
                    <template x-if="matchResult?.siswa?.face_image_path">
                        <img :src="'/storage/' + matchResult.siswa.face_image_path"
                            :alt="matchResult.siswa.nama_lengkap ?? matchResult.siswa.nama"
                            class="h-16 w-16 rounded-2xl border-2 border-green-300 object-cover shadow">
                    </template>
                    <template x-if="!matchResult?.siswa?.face_image_path">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-green-200 dark:bg-green-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600 dark:text-green-300"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                    </template>

                    <div class="match-ripple absolute inset-0 rounded-2xl"></div>

                    <div
                        class="absolute -bottom-1.5 -right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-green-500 shadow ring-2 ring-white dark:ring-gray-900">
                        <svg class="h-3.5 w-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                {{-- Info --}}
                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-bold text-green-800 dark:text-green-200"
                        x-text="matchResult?.siswa?.nama_lengkap ?? matchResult?.siswa?.nama"></p>
                    <p class="mt-0.5 text-xs text-green-600 dark:text-green-400"
                        x-text="matchResult?.siswa?.nis ? 'NIS: ' + matchResult.siswa.nis : ''"></p>

                    <div class="mt-2 flex items-center gap-3">
                        <div class="flex-1">
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-green-200 dark:bg-green-800/60">
                                <div class="h-full rounded-full bg-green-500 transition-all duration-700"
                                    :style="{ width: (matchResult?.confidence ?? 0) + '%' }"></div>
                            </div>
                        </div>
                        <span class="shrink-0 text-xs font-bold tabular-nums text-green-700 dark:text-green-300"
                            x-text="(matchResult?.confidence ?? 0) + '% cocok'"></span>
                    </div>
                </div>
            </div>

            <div
                class="border-t border-green-200 bg-green-100/60 px-5 py-2.5 dark:border-green-800/50 dark:bg-green-900/20">
                <p class="text-xs font-semibold text-green-700 dark:text-green-300">
                    ✓ Verifikasi berhasil · Absensi dapat dicatat
                </p>
            </div>
        </div>

        {{-- Retry button --}}
        <button type="button" @click="reset()"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 active:scale-95 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Scan Ulang
        </button>
    </div>

    {{-- ── Action buttons (step 1 & 2) ────────────────────────── --}}
    <div x-show="step <= 2" class="flex gap-2 pt-1">
        <button type="button" x-show="!cameraActive" @click="startCamera()"
            :disabled="!modelsLoaded || modelsLoading"
            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white/10 dark:hover:bg-white/20">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
            </svg>
            Aktifkan Kamera
        </button>

        <button type="button" x-show="cameraActive && !scanning" x-cloak @click="startVerifikasi()"
            :disabled="!faceDetected || multipleFaces"
            class="flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
            :class="faceDetected && !multipleFaces ?
                'bg-primary-600 text-white hover:bg-primary-700' :
                'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500'">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M7.864 4.243A7.5 7.5 0 0 1 19.5 10.5c0 2.92-.556 5.709-1.568 8.268M5.742 6.364A7.465 7.465 0 0 0 4.5 10.5a7.464 7.464 0 0 1-1.15 3.993m1.989 3.559A11.209 11.209 0 0 0 8.25 10.5a3.75 3.75 0 1 1 7.5 0c0 .527-.021 1.049-.064 1.565M12 10.5a14.94 14.94 0 0 1-3.6 9.75m6.633-4.596a18.666 18.666 0 0 1-2.485 5.33" />
            </svg>
            Mulai Verifikasi Wajah
        </button>

        <button type="button" x-show="cameraActive" x-cloak @click="stopCamera()"
            class="flex items-center justify-center gap-1.5 rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 active:scale-95 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z" />
            </svg>
            Stop
        </button>
    </div>

</div>
