@once
    <style>
        [x-cloak] {
            display: none !important;
        }

        .rfm-shell {
            --rfm-primary: #16a34a;
            --rfm-primary-dark: #15803d;
            --rfm-danger: #dc2626;
            --rfm-warning: #d97706;
            --rfm-info: #2563eb;
            --rfm-border: #e5e7eb;
            --rfm-muted: #64748b;
            padding-bottom: 2px;
        }

        .rfm-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            border: 1px solid var(--rfm-border);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 18px;
            padding: 14px 16px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        }

        .rfm-user {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .rfm-avatar {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dcfce7;
            color: var(--rfm-primary-dark);
            overflow: hidden;
            border: 1px solid #bbf7d0;
        }

        .rfm-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .rfm-title {
            font-size: 14px;
            font-weight: 800;
            color: #111827;
            margin: 0;
            line-height: 1.25;
        }

        .rfm-subtitle {
            margin-top: 3px;
            font-size: 12px;
            color: var(--rfm-muted);
        }

        .rfm-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .rfm-badge-success {
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
        }

        .rfm-badge-warning {
            color: #92400e;
            background: #fffbeb;
            border: 1px solid #fde68a;
        }

        .rfm-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
        }

        .rfm-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(280px, .85fr);
            gap: 16px;
            margin-top: 16px;
        }

        @media (max-width: 768px) {
            .rfm-grid {
                grid-template-columns: 1fr;
            }

            .rfm-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .rfm-actions {
                flex-direction: column;
            }
        }

        .rfm-card {
            border: 1px solid var(--rfm-border);
            background: #ffffff;
            border-radius: 20px;
            padding: 14px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .07);
        }

        .rfm-section-label {
            margin: 0 0 10px;
            font-size: 11px;
            letter-spacing: .12em;
            text-transform: uppercase;
            font-weight: 900;
            color: #94a3b8;
        }

        .rfm-camera-box {
            position: relative;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            border-radius: 18px;
            background:
                radial-gradient(circle at top, rgba(34, 197, 94, .16), transparent 34%),
                #020617;
            border: 1px solid #1e293b;
        }

        .rfm-camera-box video,
        .rfm-camera-box canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .rfm-camera-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
            color: #94a3b8;
            text-align: center;
            padding: 24px;
        }

        .rfm-camera-icon {
            width: 70px;
            height: 70px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .12);
        }

        .rfm-camera-placeholder strong {
            color: #e5e7eb;
            font-size: 14px;
        }

        .rfm-camera-placeholder span {
            font-size: 12px;
            color: #64748b;
        }

        .rfm-face-guide {
            pointer-events: none;
            position: absolute;
            inset: 12%;
            border: 2px dashed rgba(255, 255, 255, .24);
            border-radius: 22px;
        }

        .rfm-status-pill {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: 900;
            color: white;
            backdrop-filter: blur(8px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, .18);
        }

        .rfm-status-success {
            background: rgba(22, 163, 74, .92);
        }

        .rfm-status-danger {
            background: rgba(220, 38, 38, .92);
        }

        .rfm-scan-bar {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 4;
            padding: 28px 16px 14px;
            background: linear-gradient(to top, rgba(2, 6, 23, .94), transparent);
            color: white;
        }

        .rfm-progress-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
        }

        .rfm-progress-track {
            height: 7px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
            overflow: hidden;
        }

        .rfm-progress-fill {
            height: 100%;
            border-radius: 999px;
            background: #22c55e;
            transition: width .2s ease;
        }

        .rfm-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .rfm-button {
            border: 0;
            border-radius: 14px;
            padding: 11px 14px;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }

        .rfm-button:hover {
            transform: translateY(-1px);
        }

        .rfm-button:active {
            transform: translateY(0);
        }

        .rfm-button:disabled {
            opacity: .45;
            cursor: not-allowed;
            transform: none;
        }

        .rfm-button-primary {
            flex: 1;
            background: #111827;
            color: white;
            box-shadow: 0 10px 22px rgba(17, 24, 39, .18);
        }

        .rfm-button-success {
            flex: 1;
            background: var(--rfm-primary);
            color: white;
            box-shadow: 0 10px 22px rgba(22, 163, 74, .22);
        }

        .rfm-button-secondary {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .rfm-save-button {
            width: 100%;
            margin-top: 12px;
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: white;
            box-shadow: 0 12px 26px rgba(22, 163, 74, .24);
        }

        .rfm-alert {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 12px;
            line-height: 1.5;
            border: 1px solid transparent;
            margin-top: 12px;
        }

        .rfm-alert-icon {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
        }

        .rfm-alert-title {
            font-size: 12px;
            font-weight: 900;
            margin-bottom: 2px;
        }

        .rfm-alert-info {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .rfm-alert-info .rfm-alert-icon {
            background: #dbeafe;
        }

        .rfm-alert-success {
            background: #ecfdf5;
            border-color: #bbf7d0;
            color: #047857;
        }

        .rfm-alert-success .rfm-alert-icon {
            background: #bbf7d0;
        }

        .rfm-alert-warning {
            background: #fffbeb;
            border-color: #fde68a;
            color: #92400e;
        }

        .rfm-alert-warning .rfm-alert-icon {
            background: #fde68a;
        }

        .rfm-alert-danger {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .rfm-alert-danger .rfm-alert-icon {
            background: #fecaca;
        }

        .rfm-preview {
            aspect-ratio: 1 / 1;
            border-radius: 20px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rfm-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .rfm-preview-empty {
            text-align: center;
            color: #94a3b8;
            padding: 20px;
        }

        .rfm-mini-list {
            margin-top: 12px;
            display: grid;
            gap: 8px;
        }

        .rfm-mini-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 12px;
            color: #475569;
        }

        .rfm-mini-check {
            width: 18px;
            height: 18px;
            min-width: 18px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #dcfce7;
            color: #15803d;
            font-weight: 900;
            font-size: 11px;
            margin-top: 1px;
        }
    </style>
@endonce

<div x-data="{
    /* ── state ─────────────────────────────────────────── */
    modelsLoaded: false,
    modelsLoading: false,
    loadingText: 'Memuat model wajah...',
    errorMsg: '',

    cameraActive: false,
    faceDetected: false,
    multipleFaces: false,
    scanning: false,
    sampleCount: 0,

    capturedPhoto: @js($faceImageUrl ?? null),
    descriptorReady: false,
    faceDescriptorArray: [],
    saving: false,

    _stream: null,
    _detectionInterval: null,
    _detecting: false,
    _descriptorSamples: [],

    /* ── init ───────────────────────────────────────────── */
    async init() {
        this.$el.addEventListener('alpine:destroy', () => this.stopCamera());
        await this.loadModels();
    },

    /* ── face-api loader ────────────────────────────────── */
    waitForFaceApi() {
        if (window.faceapi) return Promise.resolve(window.faceapi);
        return new Promise((resolve, reject) => {
            const timer = setTimeout(
                () => reject(new Error('face-api timeout: belum dimuat oleh Vite.')),
                10_000
            );
            window.addEventListener('face-api-ready', () => {
                clearTimeout(timer);
                resolve(window.faceapi);
            }, { once: true });
        });
    },

    async loadModels() {
        this.modelsLoading = true;
        this.errorMsg = '';
        try {
            await this.waitForFaceApi();
            const baseUrl = @js(asset('vendor/vladmandic-face-api/models'));
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(baseUrl),
                faceapi.nets.faceLandmark68TinyNet.loadFromUri(baseUrl),
                faceapi.nets.faceRecognitionNet.loadFromUri(baseUrl),
            ]);
            this.modelsLoaded = true;
        } catch (err) {
            console.error('[RegisterFace] loadModels:', err);
            this.errorMsg = 'Gagal memuat model wajah. Pastikan model ada di public/vendor/vladmandic-face-api/models.';
        } finally {
            this.modelsLoading = false;
        }
    },

    /* ── camera ─────────────────────────────────────────── */
    async startCamera() {
        this.errorMsg = '';
        this.stopCamera();
        this.capturedPhoto = null;
        this.descriptorReady = false;
        this.faceDescriptorArray = [];
        this._descriptorSamples = [];
        this.sampleCount = 0;

        if (!this.modelsLoaded) {
            this.errorMsg = 'Model wajah belum siap. Tunggu proses loading selesai.';
            return;
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            this.errorMsg = 'Browser tidak mendukung kamera. Gunakan Chrome dan akses dari localhost atau HTTPS.';
            return;
        }
        try {
            this._stream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                audio: false,
            });
            await this.$nextTick();
            const video = this.$refs.video;
            if (!video) {
                this.errorMsg = 'Elemen video tidak ditemukan.';
                this.stopCamera();
                return;
            }
            video.srcObject = this._stream;
            video.muted = true;
            video.setAttribute('playsinline', 'true');
            await new Promise(resolve => {
                if (video.readyState >= 2) { resolve(); return; }
                video.onloadedmetadata = resolve;
            });
            await video.play();
            this.cameraActive = true;
            this.startDetectionLoop();
        } catch (err) {
            console.error('[RegisterFace] startCamera:', err);
            const messages = {
                NotAllowedError: 'Izin kamera ditolak. Klik ikon kamera di address bar lalu pilih Allow.',
                NotFoundError: 'Kamera tidak ditemukan di perangkat ini.',
                NotReadableError: 'Kamera sedang dipakai aplikasi lain.',
            };
            this.errorMsg = messages[err.name] ?? ('Kamera gagal aktif: ' + (err.message || err.name));
            this.stopCamera();
        }
    },

    stopCamera() {
        if (this._detectionInterval) {
            clearInterval(this._detectionInterval);
            this._detectionInterval = null;
        }
        this._stream?.getTracks().forEach(t => t.stop());
        this._stream = null;
        if (this.$refs.video) {
            this.$refs.video.pause?.();
            this.$refs.video.srcObject = null;
        }
        if (this.$refs.canvas) {
            const ctx = this.$refs.canvas.getContext('2d');
            ctx?.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
        }
        this.cameraActive = false;
        this.faceDetected = false;
        this.multipleFaces = false;
        this.scanning = false;
        this._detecting = false;
    },

    /* ── detection loop ─────────────────────────────────── */
    startDetectionLoop() {
        if (this._detectionInterval) clearInterval(this._detectionInterval);
        this._detectionInterval = setInterval(async () => {
            if (
                this._detecting ||
                !this.cameraActive ||
                !this.$refs.video ||
                !this.$refs.canvas ||
                !window.faceapi
            ) return;

            const video = this.$refs.video;
            const canvas = this.$refs.canvas;
            if (!video.videoWidth || !video.videoHeight) return;

            this._detecting = true;
            try {
                const displaySize = { width: video.videoWidth, height: video.videoHeight };
                faceapi.matchDimensions(canvas, displaySize);
                const detections = await faceapi
                    .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.5 }))
                    .withFaceLandmarks(true)
                    .withFaceDescriptors();

                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const resized = faceapi.resizeResults(detections, displaySize);

                if (resized.length !== 1) {
                    this.faceDetected = false;
                    this.multipleFaces = resized.length > 1;
                    if (this.scanning) {
                        this.scanning = false;
                        this.sampleCount = 0;
                        this._descriptorSamples = [];
                    }
                    resized.forEach(item => this.drawBox(ctx, item.detection.box, '#ef4444'));
                    return;
                }

                this.errorMsg = '';
                this.faceDetected = true;
                this.multipleFaces = false;
                faceapi.draw.drawFaceLandmarks(canvas, resized);
                this.drawBox(ctx, resized[0].detection.box, this.scanning ? '#22c55e' : '#60a5fa');

                if (this.scanning && resized[0]?.descriptor) {
                    this.collectSample(resized[0]);
                }
            } catch (err) {
                console.error('[RegisterFace] detect:', err);
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

    /* ── scanning ───────────────────────────────────────── */
    startScan() {
        if (!this.cameraActive) {
            this.errorMsg = 'Aktifkan kamera terlebih dahulu.';
            return;
        }
        if (!this.faceDetected || this.multipleFaces) {
            this.errorMsg = 'Pastikan hanya satu wajah siswa yang terdeteksi.';
            return;
        }
        this.errorMsg = '';
        this.scanning = true;
        this.sampleCount = 0;
        this._descriptorSamples = [];
        this.capturedPhoto = null;
        this.descriptorReady = false;
        this.faceDescriptorArray = [];
    },

    collectSample(detection) {
        if (this._descriptorSamples.length >= 5) return;
        const descriptor = Array.from(detection.descriptor);
        if (!Array.isArray(descriptor) || descriptor.length !== 128) {
            this.errorMsg = 'Descriptor wajah tidak valid. Silakan scan ulang.';
            this.scanning = false;
            return;
        }
        this._descriptorSamples.push(descriptor);
        this.sampleCount = this._descriptorSamples.length;
        if (this.sampleCount === 3) this.capturePhoto();
        if (this.sampleCount >= 5) this.finalizeScan();
    },

    capturePhoto() {
        const video = this.$refs.video;
        if (!video?.videoWidth || !video?.videoHeight) return;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        this.capturedPhoto = canvas.toDataURL('image/jpeg', 0.85);
    },

    finalizeScan() {
        this.scanning = false;
        if (this._descriptorSamples.length < 3) {
            this.errorMsg = 'Sampel wajah belum cukup. Silakan scan ulang.';
            return;
        }
        if (!this.capturedPhoto) this.capturePhoto();

        const count = this._descriptorSamples.length;
        const avg = this._descriptorSamples
            .reduce((acc, sample) => acc.map((v, i) => v + sample[i]), new Array(128).fill(0))
            .map(v => v / count);

        const valid = avg.length === 128 && avg.every(v => Number.isFinite(v));
        if (!valid || !this.capturedPhoto) {
            this.errorMsg = 'Data wajah tidak valid. Silakan scan ulang.';
            return;
        }

        this.faceDescriptorArray = avg;
        this.descriptorReady = true;
        this.errorMsg = '';
        this.stopCamera();
    },

    /* ── save ───────────────────────────────────────────── */
    async saveFace() {
        this.errorMsg = '';

        if (!this.descriptorReady || this.faceDescriptorArray.length !== 128) {
            this.errorMsg = 'Data wajah belum valid. Silakan scan ulang sampai indikator siap disimpan.';
            return;
        }
        if (!this.capturedPhoto) {
            this.errorMsg = 'Foto wajah belum tersedia. Silakan scan ulang.';
            return;
        }

        this.saving = true;
        try {
            const response = await fetch(
                @js(route('admin.siswas.register-face', ['siswa' => $siswaId])), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                    },
                    body: JSON.stringify({
                        face_descriptor: this.faceDescriptorArray,
                        face_image_b64: this.capturedPhoto,
                    }),
                }
            );

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(result.message || 'Gagal menyimpan data wajah.');
            }

            this.descriptorReady = false;

            // ✅ Filament v3/v4/v5 — cara resmi kirim toast dari JS
            new FilamentNotification()
                .title('Wajah Berhasil Didaftarkan')
                .body(result.message || 'Data wajah siswa berhasil disimpan.')
                .success()
                .send();

            // ✅ Tutup modal lalu refresh tabel
            setTimeout(() => {
                $wire.dispatch('close-action', { id: 'register_face' });
                setTimeout(() => $wire.$refresh(), 300);
            }, 600);

        } catch (err) {
            console.error('[RegisterFace] saveFace:', err);
            this.errorMsg = err.message || 'Gagal menyimpan data wajah.';
        } finally {
            this.saving = false;
        }
    },
}" x-init="init()" {{-- ✅ ID disesuaikan dengan yang di-dispatch saveFace() --}}
    x-on:close-modal.window="if ($event.detail?.id === 'register_face') stopCamera()" class="rfm-shell">

    {{-- Header --}}
    <div class="rfm-header">
        <div class="rfm-user">
            <div class="rfm-avatar">
                @if ($faceImageUrl)
                    <img src="{{ $faceImageUrl }}" alt="Foto wajah {{ $siswaName ?? 'siswa' }}">
                @else
                    <x-heroicon-o-user class="h-6 w-6" />
                @endif
            </div>
            <div class="min-w-0">
                <p class="rfm-title">{{ $siswaName ?? 'Siswa' }}</p>
                <p class="rfm-subtitle">Pendaftaran biometrik wajah untuk absensi</p>
            </div>
        </div>
        <div class="rfm-badge" :class="descriptorReady ? 'rfm-badge-success' : 'rfm-badge-warning'">
            <span class="rfm-badge-dot"></span>
            <span x-text="descriptorReady ? 'Siap Disimpan' : 'Belum Siap'"></span>
        </div>
    </div>

    {{-- Loading model --}}
    <template x-if="modelsLoading">
        <div class="rfm-alert rfm-alert-info">
            <div class="rfm-alert-icon">i</div>
            <div>
                <div class="rfm-alert-title">Memuat Model Wajah</div>
                <div x-text="loadingText"></div>
            </div>
        </div>
    </template>

    {{-- Error --}}
    <template x-if="errorMsg">
        <div class="rfm-alert rfm-alert-danger">
            <div class="rfm-alert-icon">!</div>
            <div>
                <div class="rfm-alert-title">Terjadi Kesalahan</div>
                <div x-text="errorMsg"></div>
            </div>
        </div>
    </template>

    {{-- Grid --}}
    <div class="rfm-grid">

        {{-- Kolom kiri: kamera --}}
        <div class="rfm-card">
            <p class="rfm-section-label">Live Kamera</p>

            <div class="rfm-camera-box">
                <video x-ref="video" autoplay playsinline muted style="display:block;" x-show="cameraActive"></video>
                <canvas x-ref="canvas" class="pointer-events-none" x-show="cameraActive"></canvas>
                <div x-show="cameraActive" class="rfm-face-guide"></div>

                <div x-show="!cameraActive" class="rfm-camera-placeholder">
                    <div class="rfm-camera-icon">
                        <x-heroicon-o-camera class="h-9 w-9" />
                    </div>
                    <div>
                        <strong>Kamera belum aktif</strong>
                        <span class="block mt-1">Klik Aktifkan Kamera untuk mulai pendaftaran wajah.</span>
                    </div>
                </div>

                <div x-show="faceDetected && !multipleFaces && !scanning" x-cloak
                    class="rfm-status-pill rfm-status-success">
                    ✓ Wajah Terdeteksi
                </div>

                <div x-show="multipleFaces" x-cloak class="rfm-status-pill rfm-status-danger">
                    ! Lebih dari 1 wajah
                </div>

                <div x-show="scanning" x-cloak class="rfm-scan-bar">
                    <div class="rfm-progress-row">
                        <span>Mengambil sampel wajah</span>
                        <span x-text="sampleCount + '/5'"></span>
                    </div>
                    <div class="rfm-progress-track">
                        <div class="rfm-progress-fill" :style="{ width: ((sampleCount / 5) * 100) + '%' }"></div>
                    </div>
                </div>
            </div>

            <div class="rfm-actions">
                <button type="button" x-show="!cameraActive" x-on:click="startCamera()"
                    x-bind:disabled="!modelsLoaded || modelsLoading" class="rfm-button rfm-button-primary">
                    Aktifkan Kamera
                </button>

                <button type="button" x-show="cameraActive && !scanning" x-cloak x-on:click="startScan()"
                    x-bind:disabled="!faceDetected || multipleFaces" class="rfm-button rfm-button-success">
                    Mulai Scan Wajah
                </button>

                <button type="button" x-show="cameraActive" x-cloak x-on:click="stopCamera()"
                    class="rfm-button rfm-button-secondary">
                    Stop
                </button>
            </div>

            <button type="button" x-on:click="saveFace()" x-bind:disabled="!descriptorReady || saving"
                class="rfm-button rfm-save-button">
                <span x-show="!saving">Simpan Data Wajah</span>
                <span x-show="saving" x-cloak>Menyimpan...</span>
            </button>

            <template x-if="descriptorReady">
                <div class="rfm-alert rfm-alert-success">
                    <div class="rfm-alert-icon">✓</div>
                    <div>
                        <div class="rfm-alert-title">Data Wajah Siap Disimpan</div>
                        <div>Foto dan descriptor wajah berhasil dibuat. Klik tombol <strong>Simpan Data Wajah</strong>
                            untuk menyimpan.</div>
                    </div>
                </div>
            </template>

            <template x-if="!descriptorReady && !errorMsg && !modelsLoading">
                <div class="rfm-alert rfm-alert-warning">
                    <div class="rfm-alert-icon">!</div>
                    <div>
                        <div class="rfm-alert-title">Belum Ada Data Wajah Baru</div>
                        <div>Aktifkan kamera, pastikan hanya satu wajah terlihat, lalu scan sampai indikator mencapai
                            5/5.</div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Kolom kanan: preview + panduan --}}
        <div class="rfm-card">
            <p class="rfm-section-label">Foto Referensi</p>

            <div class="rfm-preview">
                <template x-if="capturedPhoto">
                    <img x-bind:src="capturedPhoto" alt="Foto hasil scan">
                </template>
                <template x-if="!capturedPhoto">
                    <div class="rfm-preview-empty">
                        <x-heroicon-o-user class="h-14 w-14" />
                        <p class="text-xs font-semibold">Belum ada foto referensi</p>
                    </div>
                </template>
            </div>

            <div class="rfm-mini-list">
                <div class="rfm-mini-item">
                    <span class="rfm-mini-check">1</span>
                    <span>Pastikan wajah siswa menghadap kamera dengan pencahayaan cukup.</span>
                </div>
                <div class="rfm-mini-item">
                    <span class="rfm-mini-check">2</span>
                    <span>Jangan ada lebih dari satu wajah dalam frame saat proses scan.</span>
                </div>
                <div class="rfm-mini-item">
                    <span class="rfm-mini-check">3</span>
                    <span>Tunggu sampai status berubah menjadi <strong>Siap Disimpan</strong>.</span>
                </div>
            </div>
        </div>

    </div>{{-- /rfm-grid --}}
</div>
