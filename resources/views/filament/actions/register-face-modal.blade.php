@once
    @vite('resources/js/')
@endonce

<div x-data="{
    modelsLoaded: false,
    modelsLoading: false,
    loadingText: 'Memuat model AI...',
    errorMsg: '',

    cameraActive: false,
    faceDetected: false,
    multipleFaces: false,
    scanning: false,
    sampleCount: 0,

    capturedPhoto: null,
    descriptorReady: false,
    faceDescriptorJson: '',

    stream: null,
    detectionInterval: null,
    detecting: false,
    descriptorSamples: [],

    async init() {
        this.capturedPhoto = null;
        this.descriptorReady = false;
        this.faceDescriptorJson = '';

        await this.loadModels();
    },

    waitForFaceApi() {
        if (window.faceapi) {
            return Promise.resolve(window.faceapi);
        }

        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error('face-api belum dimuat oleh Vite.'));
            }, 10000);

            window.addEventListener('face-api-ready', () => {
                clearTimeout(timeout);
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
        } catch (error) {
            console.error('[RegisterFace] loadModels:', error);
            this.errorMsg = 'Gagal memuat model wajah. Pastikan model ada di public/vendor/vladmandic-face-api/models.';
        } finally {
            this.modelsLoading = false;
        }
    },

    setFilamentValue(field, value) {
        const form = this.$root.closest('form') ?? document;

        const input =
            form.querySelector(`input[name='${field}']`) ||
            form.querySelector(`input[name$='[${field}]']`) ||
            form.querySelector(`input[name$='.${field}']`) ||
            document.querySelector(`input[name='${field}']`) ||
            document.querySelector(`input[name$='[${field}]']`) ||
            document.querySelector(`input[name$='.${field}']`);

        if (!input) {
            console.warn('[RegisterFace] Field tidak ditemukan:', field);
            return;
        }

        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    },

    async startCamera() {
        this.errorMsg = '';
        this.stopCamera();

        this.capturedPhoto = null;
        this.descriptorReady = false;
        this.faceDescriptorJson = '';
        this.descriptorSamples = [];
        this.sampleCount = 0;

        this.setFilamentValue('face_descriptor', '');
        this.setFilamentValue('face_image_b64', '');

        if (!this.modelsLoaded) {
            this.errorMsg = 'Model AI belum siap. Tunggu loading selesai.';
            return;
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            this.errorMsg = 'Browser tidak mendukung kamera. Gunakan Chrome dan akses dari localhost / 127.0.0.1.';
            return;
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user',
                },
                audio: false,
            });

            await this.$nextTick();

            const video = this.$refs.video;

            if (!video) {
                this.errorMsg = 'Elemen video tidak ditemukan.';
                this.stopCamera();
                return;
            }

            video.srcObject = this.stream;
            video.muted = true;
            video.setAttribute('playsinline', 'true');

            await new Promise((resolve) => {
                if (video.readyState >= 2) {
                    resolve();
                    return;
                }

                video.onloadedmetadata = resolve;
            });

            await video.play();

            this.cameraActive = true;
            this.startDetectionLoop();
        } catch (error) {
            console.error('[RegisterFace] startCamera:', error);

            if (error.name === 'NotAllowedError') {
                this.errorMsg = 'Izin kamera ditolak. Klik ikon kamera di address bar lalu pilih Allow.';
            } else if (error.name === 'NotFoundError') {
                this.errorMsg = 'Kamera tidak ditemukan.';
            } else if (error.name === 'NotReadableError') {
                this.errorMsg = 'Kamera sedang dipakai aplikasi lain.';
            } else {
                this.errorMsg = 'Kamera gagal aktif: ' + (error.message || error.name);
            }

            this.stopCamera();
        }
    },

    stopCamera() {
        if (this.detectionInterval) {
            clearInterval(this.detectionInterval);
            this.detectionInterval = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach((track) => track.stop());
            this.stream = null;
        }

        if (this.$refs.video) {
            this.$refs.video.pause();
            this.$refs.video.srcObject = null;
        }

        if (this.$refs.canvas) {
            const ctx = this.$refs.canvas.getContext('2d');
            ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
        }

        this.cameraActive = false;
        this.faceDetected = false;
        this.multipleFaces = false;
        this.scanning = false;
        this.detecting = false;
    },

    startDetectionLoop() {
        if (this.detectionInterval) {
            clearInterval(this.detectionInterval);
        }

        this.detectionInterval = setInterval(async () => {
            if (
                this.detecting ||
                !this.cameraActive ||
                !this.$refs.video ||
                !this.$refs.canvas ||
                !window.faceapi
            ) {
                return;
            }

            const video = this.$refs.video;
            const canvas = this.$refs.canvas;

            if (!video.videoWidth || !video.videoHeight) {
                return;
            }

            this.detecting = true;

            try {
                const displaySize = {
                    width: video.videoWidth,
                    height: video.videoHeight,
                };

                faceapi.matchDimensions(canvas, displaySize);

                const detections = await faceapi
                    .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({
                        scoreThreshold: 0.5,
                    }))
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
                        this.descriptorSamples = [];
                    }

                    resized.forEach((item) => {
                        this.drawBox(ctx, item.detection.box, '#ef4444');
                    });

                    return;
                }

                this.errorMsg = '';
                this.faceDetected = true;
                this.multipleFaces = false;

                faceapi.draw.drawFaceLandmarks(canvas, resized);
                this.drawBox(ctx, resized[0].detection.box, this.scanning ? '#22c55e' : '#60a5fa');

                if (this.scanning && resized[0].descriptor) {
                    this.collectSample(resized[0]);
                }
            } catch (error) {
                console.error('[RegisterFace] detect:', error);
            } finally {
                this.detecting = false;
            }
        }, 300);
    },

    drawBox(ctx, box, color) {
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.strokeRect(box.x, box.y, box.width, box.height);
    },

    startScan() {
        if (!this.cameraActive) {
            this.errorMsg = 'Aktifkan kamera dulu.';
            return;
        }

        if (!this.faceDetected || this.multipleFaces) {
            this.errorMsg = 'Pastikan hanya satu wajah terdeteksi.';
            return;
        }

        this.errorMsg = '';
        this.scanning = true;
        this.sampleCount = 0;
        this.descriptorSamples = [];
        this.capturedPhoto = null;
        this.descriptorReady = false;
        this.faceDescriptorJson = '';

        this.setFilamentValue('face_descriptor', '');
        this.setFilamentValue('face_image_b64', '');
    },

    collectSample(detection) {
        if (this.descriptorSamples.length >= 5) {
            return;
        }

        const descriptor = Array.from(detection.descriptor);

        if (!Array.isArray(descriptor) || descriptor.length !== 128) {
            this.errorMsg = 'Descriptor wajah tidak valid. Scan ulang.';
            this.scanning = false;
            return;
        }

        this.descriptorSamples.push(descriptor);
        this.sampleCount = this.descriptorSamples.length;

        if (this.sampleCount === 3) {
            this.capturePhoto();
        }

        if (this.sampleCount >= 5) {
            this.finalizeScan();
        }
    },

    capturePhoto() {
        const video = this.$refs.video;

        if (!video || !video.videoWidth || !video.videoHeight) {
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        this.capturedPhoto = canvas.toDataURL('image/jpeg', 0.85);
    },

    finalizeScan() {
        this.scanning = false;

        if (this.descriptorSamples.length < 3) {
            this.errorMsg = 'Sampel wajah belum cukup. Scan ulang.';
            return;
        }

        if (!this.capturedPhoto) {
            this.capturePhoto();
        }

        const avg = new Array(128).fill(0);

        for (let i = 0; i < 128; i++) {
            let total = 0;

            this.descriptorSamples.forEach((sample) => {
                total += Number(sample[i]);
            });

            avg[i] = total / this.descriptorSamples.length;
        }

        const valid = avg.length === 128 && avg.every((value) => Number.isFinite(Number(value)));

        if (!valid || !this.capturedPhoto) {
            this.errorMsg = 'Data wajah tidak valid. Scan ulang.';
            return;
        }

        this.faceDescriptorJson = JSON.stringify(avg);
        this.descriptorReady = true;

        this.setFilamentValue('face_descriptor', this.faceDescriptorJson);
        this.setFilamentValue('face_image_b64', this.capturedPhoto);

        this.stopCamera();
    },
}" x-init="init()" x-on:close-modal.window="stopCamera()" class="space-y-4">
    <style>
        [x-cloak] {
            display: none !important;
        }

        .face-camera-box {
            aspect-ratio: 4 / 3;
        }
    </style>

    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-white/10 dark:bg-white/5">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
            {{ $siswaName ?? 'Siswa' }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
            Pendaftaran Biometrik Wajah
        </p>
    </div>

    <div x-show="modelsLoading" x-cloak
        class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700">
        <span x-text="loadingText"></span>
    </div>

    <div x-show="errorMsg" x-cloak class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
        <span x-text="errorMsg"></span>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="space-y-3 md:col-span-3">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
                Live Kamera
            </p>

            <div class="face-camera-box relative overflow-hidden rounded-2xl bg-gray-950 shadow">
                <video x-ref="video" autoplay playsinline muted style="display: block;"
                    class="absolute inset-0 h-full w-full object-cover" x-show="cameraActive"></video>

                <canvas x-ref="canvas" class="pointer-events-none absolute inset-0 h-full w-full"
                    x-show="cameraActive"></canvas>

                <div x-show="!cameraActive"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-center text-gray-400">
                    <x-heroicon-o-camera class="h-14 w-14" />
                    <div>
                        <p class="text-sm font-semibold">Kamera belum aktif</p>
                        <p class="mt-1 text-xs text-gray-500">Klik tombol aktifkan kamera.</p>
                    </div>
                </div>

                <div x-show="faceDetected && !multipleFaces && !scanning" x-cloak
                    class="absolute left-3 top-3 rounded-full bg-green-500 px-3 py-1 text-xs font-bold text-white">
                    Wajah Terdeteksi
                </div>

                <div x-show="multipleFaces" x-cloak
                    class="absolute left-3 top-3 rounded-full bg-red-500 px-3 py-1 text-xs font-bold text-white">
                    Lebih dari 1 wajah
                </div>

                <div x-show="scanning" x-cloak
                    class="absolute bottom-0 left-0 right-0 bg-gray-950/80 px-4 py-3 text-center text-xs font-semibold text-white">
                    Mengambil sampel wajah <span x-text="sampleCount"></span>/5
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" x-show="!cameraActive" x-on:click="startCamera()"
                    x-bind:disabled="!modelsLoaded || modelsLoading"
                    class="flex flex-1 items-center justify-center rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                    Aktifkan Kamera
                </button>

                <button type="button" x-show="cameraActive && !scanning" x-on:click="startScan()"
                    x-bind:disabled="!faceDetected || multipleFaces"
                    class="flex flex-1 items-center justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">
                    Mulai Scan Wajah
                </button>

                <button type="button" x-show="cameraActive" x-on:click="stopCamera()"
                    class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600">
                    Stop
                </button>
            </div>
        </div>

        <div class="space-y-3 md:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">
                Foto Referensi
            </p>

            <div
                class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                <template x-if="capturedPhoto">
                    <img x-bind:src="capturedPhoto" class="h-full w-full object-cover" alt="Foto hasil scan">
                </template>

                <template x-if="!capturedPhoto">
                    <div class="text-center text-gray-400">
                        <x-heroicon-o-user class="mx-auto h-16 w-16" />
                        <p class="mt-2 text-xs">Belum ada foto</p>
                    </div>
                </template>
            </div>

            <div x-show="descriptorReady" x-cloak
                class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-xs font-semibold text-green-700">
                Data wajah siap disimpan. Klik tombol “Simpan Data Wajah”.
            </div>

            <div x-show="!descriptorReady"
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                Aktifkan kamera, posisikan wajah, lalu scan sampai 5/5.
            </div>
        </div>
    </div>
</div>
