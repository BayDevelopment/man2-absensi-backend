window.loadFaceApiScript =
    window.loadFaceApiScript ||
    function () {
        return new Promise((resolve, reject) => {
            if (window.faceapi) {
                resolve(window.faceapi);
                return;
            }

            const existing = document.querySelector(
                'script[data-face-api="true"]',
            );

            if (existing) {
                existing.addEventListener(
                    "load",
                    () => resolve(window.faceapi),
                    {
                        once: true,
                    },
                );
                existing.addEventListener("error", reject, {
                    once: true,
                });
                return;
            }

            const script = document.createElement("script");
            script.src =
                "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js";
            script.async = true;
            script.dataset.faceApi = "true";
            script.onload = () => resolve(window.faceapi);
            script.onerror = reject;

            document.head.appendChild(script);
        });
    };

window.absenVerifikasi = function ({ siswaList, threshold }) {
    return {
        step: 1,
        modelsLoaded: false,
        modelsLoading: false,
        loadingText: "Memuat model AI…",
        errorMsg: "",
        matchError: "",

        cameraActive: false,
        faceDetected: false,
        multipleFaces: false,
        scanning: false,

        matchResult: null,

        _stream: null,
        _detectionInterval: null,
        _detecting: false,
        _modalObserver: null,

        init() {
            this._waitUntilVisible().then(() => this.loadModels());

            this.$el.addEventListener("alpine:destroy", () => {
                this.stopCamera();
            });

            window.addEventListener("beforeunload", () => {
                this.stopCamera();
            });
        },

        _waitUntilVisible() {
            return new Promise((resolve) => {
                if (this.$el.offsetParent !== null) {
                    requestAnimationFrame(resolve);
                    return;
                }

                const observer = new IntersectionObserver(
                    (entries) => {
                        if (entries[0]?.isIntersecting) {
                            observer.disconnect();
                            requestAnimationFrame(resolve);
                        }
                    },
                    {
                        threshold: 0.1,
                    },
                );

                observer.observe(this.$el);
                this._modalObserver = observer;
            });
        },

        async loadModels() {
            this.modelsLoading = true;
            this.errorMsg = "";

            try {
                await window.loadFaceApiScript();

                const baseUrl =
                    "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights";

                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(baseUrl),
                    faceapi.nets.faceLandmark68TinyNet.loadFromUri(baseUrl),
                    faceapi.nets.faceRecognitionNet.loadFromUri(baseUrl),
                ]);

                this.modelsLoaded = true;
            } catch (error) {
                console.error("[AbsenVerifikasi] loadModels:", error);
                this.errorMsg =
                    "Gagal memuat model AI. Periksa koneksi internet atau CDN face-api.js.";
            } finally {
                this.modelsLoading = false;
            }
        },

        setFilamentFormValue(field, value) {
            const root = this.$root.closest("form") ?? document;

            const selectors = [
                `input[name="${field}"]`,
                `input[name$="[${field}]"]`,
                `input[name$=".${field}"]`,
                `[wire\\:model$="${field}"]`,
                `[wire\\:model\\.live$="${field}"]`,
                `[wire\\:model\\.defer$="${field}"]`,
            ];

            const input = selectors.reduce((found, selector) => {
                return (
                    found ||
                    root.querySelector(selector) ||
                    document.querySelector(selector)
                );
            }, null);

            if (!input) {
                console.warn(
                    `[AbsenVerifikasi] Hidden field "${field}" tidak ditemukan.`,
                );
                return;
            }

            input.value = value;
            input.dispatchEvent(
                new Event("input", {
                    bubbles: true,
                }),
            );
            input.dispatchEvent(
                new Event("change", {
                    bubbles: true,
                }),
            );
        },

        resetFilamentFormValue() {
            this.setFilamentFormValue("siswa_id", "");
            this.setFilamentFormValue("match_distance", "");
            this.setFilamentFormValue("match_confidence", "");
        },

        async startCamera() {
            this.errorMsg = "";
            this.matchError = "";
            this.matchResult = null;
            this.step = 1;
            this.resetFilamentFormValue();
            this.stopCamera();

            if (!this.modelsLoaded) {
                this.errorMsg =
                    "Model AI belum siap. Tunggu proses loading selesai.";
                return;
            }

            if (!navigator.mediaDevices?.getUserMedia) {
                this.errorMsg =
                    "Browser tidak mendukung kamera. Gunakan HTTPS atau localhost.";
                return;
            }

            try {
                this._stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: {
                            ideal: 640,
                        },
                        height: {
                            ideal: 480,
                        },
                        facingMode: "user",
                    },
                    audio: false,
                });

                const video = await this._getVideoElement();

                video.srcObject = this._stream;
                video.setAttribute("playsinline", "true");
                video.muted = true;

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
                console.error("[AbsenVerifikasi] startCamera:", error);

                if (
                    error.name === "NotAllowedError" ||
                    error.name === "PermissionDeniedError"
                ) {
                    this.errorMsg =
                        "Akses kamera ditolak. Izinkan kamera di browser lalu coba lagi.";
                } else if (
                    error.name === "NotFoundError" ||
                    error.name === "DevicesNotFoundError"
                ) {
                    this.errorMsg = "Kamera tidak ditemukan.";
                } else if (error.name === "NotReadableError") {
                    this.errorMsg = "Kamera sedang digunakan aplikasi lain.";
                } else {
                    this.errorMsg =
                        "Kamera tidak dapat diakses: " +
                        (error.message || error.name);
                }

                this.stopCamera();
            }
        },

        _getVideoElement() {
            return new Promise((resolve, reject) => {
                if (this.$refs.video) {
                    resolve(this.$refs.video);
                    return;
                }

                let attempts = 0;

                const timer = setInterval(() => {
                    if (this.$refs.video) {
                        clearInterval(timer);
                        resolve(this.$refs.video);
                        return;
                    }

                    attempts++;

                    if (attempts >= 25) {
                        clearInterval(timer);
                        reject(
                            new Error("Elemen video belum tersedia di DOM."),
                        );
                    }
                }, 100);
            });
        },

        stopCamera() {
            if (this._detectionInterval) {
                clearInterval(this._detectionInterval);
                this._detectionInterval = null;
            }

            this._stream?.getTracks().forEach((track) => track.stop());
            this._stream = null;

            if (this.$refs.video) {
                this.$refs.video.pause?.();
                this.$refs.video.srcObject = null;
            }

            if (this.$refs.overlayCanvas) {
                const ctx = this.$refs.overlayCanvas.getContext("2d");
                ctx?.clearRect(
                    0,
                    0,
                    this.$refs.overlayCanvas.width,
                    this.$refs.overlayCanvas.height,
                );
            }

            this.cameraActive = false;
            this.faceDetected = false;
            this.multipleFaces = false;
            this.scanning = false;
            this._detecting = false;
        },

        startDetectionLoop() {
            if (this._detectionInterval) {
                clearInterval(this._detectionInterval);
            }

            this._detectionInterval = setInterval(async () => {
                if (
                    this._detecting ||
                    !this.cameraActive ||
                    !this.$refs.video ||
                    !this.$refs.overlayCanvas ||
                    !window.faceapi
                ) {
                    return;
                }

                const video = this.$refs.video;
                const canvas = this.$refs.overlayCanvas;

                if (!video.videoWidth || !video.videoHeight) {
                    return;
                }

                this._detecting = true;

                try {
                    const displaySize = {
                        width: video.videoWidth,
                        height: video.videoHeight,
                    };

                    faceapi.matchDimensions(canvas, displaySize);

                    const detections = await faceapi
                        .detectAllFaces(
                            video,
                            new faceapi.TinyFaceDetectorOptions({
                                scoreThreshold: 0.5,
                            }),
                        )
                        .withFaceLandmarks(true)
                        .withFaceDescriptors();

                    const ctx = canvas.getContext("2d");
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    const resized = faceapi.resizeResults(
                        detections,
                        displaySize,
                    );

                    if (resized.length !== 1) {
                        this.faceDetected = false;
                        this.multipleFaces = resized.length > 1;

                        resized.forEach((result) => {
                            this.drawBox(ctx, result.detection.box, "#ef4444");
                        });

                        return;
                    }

                    this.faceDetected = true;
                    this.multipleFaces = false;
                    this.matchError = "";

                    faceapi.draw.drawFaceLandmarks(canvas, resized);
                    this.drawBox(
                        ctx,
                        resized[0].detection.box,
                        this.scanning ? "#22c55e" : "#60a5fa",
                    );

                    if (this.scanning && resized[0]?.descriptor) {
                        await this.runMatch(Array.from(resized[0].descriptor));
                    }
                } catch (error) {
                    console.error("[AbsenVerifikasi] detection:", error);
                } finally {
                    this._detecting = false;
                }
            }, 300);
        },

        drawBox(ctx, box, color) {
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;

            if (typeof ctx.roundRect === "function") {
                ctx.beginPath();
                ctx.roundRect(box.x, box.y, box.width, box.height, 8);
                ctx.stroke();
                return;
            }

            ctx.strokeRect(box.x, box.y, box.width, box.height);
        },

        startVerifikasi() {
            if (!this.cameraActive) {
                this.errorMsg = "Aktifkan kamera terlebih dahulu.";
                return;
            }

            if (!this.faceDetected || this.multipleFaces) {
                this.errorMsg = "Pastikan hanya satu wajah terdeteksi.";
                return;
            }

            this.errorMsg = "";
            this.matchError = "";
            this.matchResult = null;
            this.resetFilamentFormValue();

            this.scanning = true;
            this.step = 2;
        },

        async runMatch(descriptor) {
            if (!this.scanning) {
                return;
            }

            this.scanning = false;

            const labeledDescriptors = siswaList
                .filter(
                    (siswa) =>
                        Array.isArray(siswa.face_descriptor) &&
                        siswa.face_descriptor.length === 128,
                )
                .map((siswa) => {
                    return new faceapi.LabeledFaceDescriptors(
                        String(siswa.id),
                        [new Float32Array(siswa.face_descriptor)],
                    );
                });

            if (!labeledDescriptors.length) {
                this.matchError =
                    "Belum ada siswa yang memiliki data wajah terdaftar.";
                this.step = 1;
                return;
            }

            const matcher = new faceapi.FaceMatcher(
                labeledDescriptors,
                threshold ?? 0.42,
            );
            const best = matcher.findBestMatch(new Float32Array(descriptor));

            if (best.label === "unknown") {
                this.matchError =
                    "Wajah tidak dikenali. Pastikan siswa sudah didaftarkan wajahnya.";
                this.step = 1;
                return;
            }

            const siswa = siswaList.find(
                (item) => String(item.id) === best.label,
            );

            if (!siswa) {
                this.matchError =
                    "Data siswa hasil pencocokan tidak ditemukan.";
                this.step = 1;
                return;
            }

            const confidence = Math.max(
                0,
                Math.min(100, Math.round((1 - best.distance) * 100)),
            );

            this.matchResult = {
                siswa,
                distance: best.distance,
                confidence,
            };

            this.setFilamentFormValue("siswa_id", siswa.id);
            this.setFilamentFormValue("match_distance", best.distance);
            this.setFilamentFormValue("match_confidence", confidence);

            this.step = 3;
            this.stopCamera();
        },

        reset() {
            this.step = 1;
            this.matchResult = null;
            this.matchError = "";
            this.errorMsg = "";
            this.scanning = false;
            this.resetFilamentFormValue();
            this.startCamera();
        },
    };
};
