@extends('admin.layouts.admin')
@section('title', 'Scanner Kehadiran - ' . $unitEvent->judul)
@section('page-title', 'Scanner Kehadiran')
@section('page-subtitle', 'Scan QR Code undangan wisuda untuk mencatat kehadiran secara langsung')

@section('content')
<div class="p-6 max-w-5xl mx-auto">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1.1fr,0.9fr]">
        <!-- Scanner Panel -->
        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex flex-col items-center">
            
            <!-- Header with Dynamic Status -->
            <div class="w-full flex items-center justify-between mb-3">
                <h2 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                    <span id="camera-status-dot" class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <span id="camera-status-text">Menyiapkan Kamera...</span>
                </h2>

                <!-- Camera Switcher / Selector Button -->
                <div class="flex items-center gap-2">
                    <select id="camera-select" class="hidden text-xs rounded-xl border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:border-purple-500 focus:outline-none">
                    </select>
                    <button type="button" id="btn-toggle-camera" class="text-xs font-bold px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-950/40 dark:text-purple-300 transition-colors flex items-center gap-1.5 border border-purple-200 dark:border-purple-800">
                        <span id="btn-camera-icon">📷</span>
                        <span id="btn-camera-text">Buka Kamera</span>
                    </button>
                </div>
            </div>

            <!-- Camera Wrapper with Glassmorphism Overlay -->
            <div class="relative w-full aspect-square max-w-[380px] overflow-hidden rounded-2xl border border-purple-200/60 dark:border-purple-900/60 bg-gray-950 flex items-center justify-center shadow-inner">
                <!-- HTML5 QRCode Video Mount Point -->
                <div id="reader" class="w-full h-full relative"></div>

                <!-- Scanner Laser Line Animation overlay (only visible when camera active) -->
                <div id="scan-line" class="hidden absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-transparent via-purple-400 to-transparent shadow-[0_0_12px_rgba(168,85,247,0.9)] animate-[scan_2s_ease-in-out_infinite] pointer-events-none z-20"></div>

                <!-- Custom Camera Placeholder / Prompt Overlay -->
                <div id="camera-placeholder" class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center bg-gray-50 dark:bg-gray-900 z-10 transition-opacity">
                    <div id="camera-prompt-icon-wrap" class="w-16 h-16 rounded-2xl bg-purple-50 dark:bg-purple-950/60 flex items-center justify-center text-3xl mb-3 border border-purple-100 dark:border-purple-900">
                        <span id="camera-prompt-icon">📹</span>
                    </div>
                    <p class="text-sm font-extrabold text-gray-900 dark:text-white" id="camera-prompt-title">Kamera Belum Aktif</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-[250px] leading-relaxed" id="camera-prompt-desc">
                        Klik tombol di bawah untuk mengizinkan akses kamera pada laptop atau ponsel Anda.
                    </p>
                    <button type="button" id="btn-start-camera-overlay" class="mt-4 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-extrabold shadow-md transition-all flex items-center gap-2 active:scale-95">
                        <span>⚡</span>
                        <span>Aktifkan Kamera Sekarang</span>
                    </button>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3 flex items-center gap-1">
                        <span>🔒</span> Izin kamera aman dan diproses langsung di perangkat Anda
                    </p>
                </div>
            </div>

            <!-- Manual input search fallback for physical scanner / manual code -->
            <div class="w-full mt-6">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Input Manual (Kode / No. Registrasi / Barcode Scanner)</label>
                <form id="manual-scan-form" class="flex gap-2">
                    <input type="text" id="manual-code" placeholder="Contoh: EVT-XXXXXXXX atau scan dengan barcode reader..." class="block flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm focus:border-purple-500 focus:bg-white dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                    <button type="submit" class="rounded-xl bg-purple-600 px-5 py-2 text-sm font-bold text-white transition-colors hover:bg-purple-500 active:scale-95">Kirim</button>
                </form>
            </div>
        </div>

        <!-- Feedback & History Panel -->
        <div class="flex flex-col gap-6">
            <!-- Scan Status Feed -->
            <div id="scan-feedback" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex flex-col items-center justify-center min-h-[160px] text-center transition-all duration-300">
                <p class="text-4xl mb-3" id="feedback-icon">📷</p>
                <h3 class="text-base font-extrabold text-gray-900 dark:text-white" id="feedback-title">Siap Memindai QR Code</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" id="feedback-desc">Arahkan QR Code undangan wisuda tamu di depan kamera</p>
            </div>

            <!-- Scanned List (Real-time feed) -->
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 flex-1 flex flex-col">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4 flex items-center justify-between">
                    <span>Daftar Tamu Masuk</span>
                    <span id="scan-count" class="rounded-full bg-purple-50 px-2 py-0.5 text-xs font-bold text-purple-700 dark:bg-purple-950/40 dark:text-purple-300">0 hadir</span>
                </h3>

                <div class="overflow-y-auto flex-1 max-h-[300px] pr-2 space-y-3" id="history-container">
                    <div class="text-center py-8 text-xs text-gray-400 dark:text-gray-500" id="empty-history">
                        Belum ada tamu yang ter-scan di sesi ini.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load HTML5-QRCode Library using root-relative path to avoid domain mismatch -->
<script src="/js/html5-qrcode.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const readerId = "reader";
    const manualForm = document.getElementById("manual-scan-form");
    const manualInput = document.getElementById("manual-code");
    
    const feedbackBox = document.getElementById("scan-feedback");
    const feedbackIcon = document.getElementById("feedback-icon");
    const feedbackTitle = document.getElementById("feedback-title");
    const feedbackDesc = document.getElementById("feedback-desc");

    const historyContainer = document.getElementById("history-container");
    const emptyHistory = document.getElementById("empty-history");
    const scanCountBadge = document.getElementById("scan-count");

    const cameraPlaceholder = document.getElementById("camera-placeholder");
    const cameraPromptIcon = document.getElementById("camera-prompt-icon");
    const cameraPromptTitle = document.getElementById("camera-prompt-title");
    const cameraPromptDesc = document.getElementById("camera-prompt-desc");
    const btnStartCameraOverlay = document.getElementById("btn-start-camera-overlay");
    const btnToggleCamera = document.getElementById("btn-toggle-camera");
    const btnCameraIcon = document.getElementById("btn-camera-icon");
    const btnCameraText = document.getElementById("btn-camera-text");
    const cameraSelect = document.getElementById("camera-select");
    const cameraStatusDot = document.getElementById("camera-status-dot");
    const cameraStatusText = document.getElementById("camera-status-text");
    const scanLine = document.getElementById("scan-line");
    
    let scannedCount = 0;
    let isProcessing = false;
    let html5QrCode = null;
    let isCameraActive = false;
    let availableCameras = [];
    let selectedCameraId = null;

    // Web Audio Synthesized Beeper
    function playBeep(success = true) {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            if (success) {
                const osc1 = audioCtx.createOscillator();
                const gain1 = audioCtx.createGain();
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.type = "sine";
                osc1.frequency.value = 880;
                gain1.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
                osc1.start(audioCtx.currentTime);
                osc1.stop(audioCtx.currentTime + 0.1);
            } else {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.type = "sawtooth";
                osc.frequency.value = 180;
                gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
                osc.start(audioCtx.currentTime);
                osc.stop(audioCtx.currentTime + 0.3);
            }
        } catch (e) {
            console.error("Audio Context not allowed/supported: ", e);
        }
    }

    // Update feedback panel
    function updateFeedback(type, title, desc) {
        feedbackBox.className = "rounded-3xl border p-6 shadow-sm flex flex-col items-center justify-center min-h-[160px] text-center transition-all duration-300";
        
        if (type === "success") {
            feedbackBox.classList.add("border-green-200", "bg-green-50", "text-green-800", "dark:border-green-900/50", "dark:bg-green-950/20", "dark:text-green-300");
            feedbackIcon.textContent = "✅";
        } else if (type === "warning") {
            feedbackBox.classList.add("border-yellow-200", "bg-yellow-50", "text-yellow-800", "dark:border-yellow-900/50", "dark:bg-yellow-950/20", "dark:text-yellow-300");
            feedbackIcon.textContent = "⚠️";
        } else if (type === "error") {
            feedbackBox.classList.add("border-red-200", "bg-red-50", "text-red-800", "dark:border-red-900/50", "dark:bg-red-950/20", "dark:text-red-300");
            feedbackIcon.textContent = "❌";
        } else {
            feedbackBox.classList.add("border-gray-100", "bg-white", "text-gray-900", "dark:border-gray-800", "dark:bg-gray-900", "dark:text-white");
            feedbackIcon.textContent = "📷";
        }

        feedbackTitle.textContent = title;
        feedbackDesc.textContent = desc;
    }

    // Set UI camera status indicator
    function setCameraStatus(state, label) {
        if (state === "active") {
            cameraStatusDot.className = "inline-block h-2.5 w-2.5 rounded-full bg-green-500 animate-pulse";
            cameraStatusText.textContent = label || "Kamera Scanner Aktif";
            cameraPlaceholder.classList.add("hidden");
            scanLine.classList.remove("hidden");
            btnCameraIcon.textContent = "⏹️";
            btnCameraText.textContent = "Hentikan Kamera";
            btnToggleCamera.classList.remove("bg-purple-50", "text-purple-700");
            btnToggleCamera.classList.add("bg-red-50", "text-red-600", "border-red-200");
            isCameraActive = true;
        } else if (state === "loading") {
            cameraStatusDot.className = "inline-block h-2.5 w-2.5 rounded-full bg-amber-500 animate-ping";
            cameraStatusText.textContent = label || "Menghubungkan Kamera...";
            cameraPlaceholder.classList.remove("hidden");
            scanLine.classList.add("hidden");
            cameraPromptTitle.textContent = "Meminta Izin Kamera...";
            cameraPromptDesc.textContent = "Izinkan akses kamera pada jendela notifikasi browser Anda.";
        } else {
            cameraStatusDot.className = "inline-block h-2.5 w-2.5 rounded-full bg-gray-400";
            cameraStatusText.textContent = label || "Kamera Non-Aktif";
            cameraPlaceholder.classList.remove("hidden");
            scanLine.classList.add("hidden");
            btnCameraIcon.textContent = "📷";
            btnCameraText.textContent = "Buka Kamera";
            btnToggleCamera.classList.remove("bg-red-50", "text-red-600", "border-red-200");
            btnToggleCamera.classList.add("bg-purple-50", "text-purple-700");
            isCameraActive = false;
        }
    }

    // Submit code to backend
    function submitScanCode(code) {
        if (isProcessing) return;
        isProcessing = true;
        
        updateFeedback("neutral", "Memproses...", "Mencari data undangan...");

        fetch("{{ route('admin.unit-events.participants.scanner.scan', [$unitPage, $unitEvent]) }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}",
                "Accept": "application/json"
            },
            body: JSON.stringify({ code: code })
        })
        .then(response => response.json())
        .then(data => {
            isProcessing = false;
            if (data.success) {
                const p = data.data;
                if (data.status === "baru") {
                    playBeep(true);
                    updateFeedback("success", "Berhasil Hadir!", `${p.nama} (${p.peran_label}) - Kursi: ${p.nomor_kursi}`);
                    addHistoryRow(p);
                } else {
                    playBeep(true);
                    updateFeedback("warning", "Sudah Hadir", `${p.nama} (${p.peran_label}) sudah tercatat hadir.`);
                }
            } else {
                playBeep(false);
                updateFeedback("error", "Gagal", data.message || "Tamu tidak ditemukan.");
            }
        })
        .catch(err => {
            isProcessing = false;
            playBeep(false);
            updateFeedback("error", "Error Koneksi", "Gagal menghubungi server.");
            console.error(err);
        });
    }

    // Add entry to history feed
    function addHistoryRow(participant) {
        if (emptyHistory) {
            emptyHistory.remove();
        }
        
        scannedCount++;
        scanCountBadge.textContent = `${scannedCount} hadir`;

        const row = document.createElement("div");
        row.className = "flex items-start justify-between p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800/80 animate-[fadeIn_0.3s_ease-out]";
        row.innerHTML = `
            <div>
                <p class="font-extrabold text-sm text-gray-900 dark:text-white">${participant.nama}</p>
                <p class="text-xs text-purple-600 dark:text-purple-300 font-semibold mt-0.5">Wisudawan: ${participant.nama_wisudawan} · Kursi: ${participant.nomor_kursi}</p>
            </div>
            <div class="text-right">
                <span class="text-[10px] text-gray-400 font-bold block">${participant.attended_at}</span>
                <span class="inline-block mt-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-300">Masuk</span>
            </div>
        `;

        historyContainer.prepend(row);
    }

    // Stop active camera
    function stopCamera() {
        if (html5QrCode && isCameraActive) {
            html5QrCode.stop().then(() => {
                setCameraStatus("stopped", "Kamera Non-Aktif");
                cameraPromptTitle.textContent = "Kamera Dinonaktifkan";
                cameraPromptDesc.textContent = "Klik tombol 'Buka Kamera' untuk menyalakan scanner kembali.";
                btnStartCameraOverlay.classList.remove("hidden");
            }).catch(err => {
                console.warn("Stop camera error:", err);
                setCameraStatus("stopped", "Kamera Non-Aktif");
            });
        } else {
            setCameraStatus("stopped", "Kamera Non-Aktif");
        }
    }

    // Start camera with specified camera ID or facingMode
    function startCamera(cameraIdOrConstraints) {
        if (typeof Html5Qrcode === "undefined") {
            setCameraStatus("stopped", "Library Scanner Belum Siap");
            cameraPromptTitle.textContent = "Gagal Memuat Library Scanner";
            cameraPromptDesc.textContent = "Pastikan koneksi internet stabil dan refresh halaman.";
            return;
        }

        setCameraStatus("loading", "Menghubungkan Kamera...");

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(readerId, { verbose: false });
        }

        // If already scanning, stop first
        const startPromise = isCameraActive ? html5QrCode.stop() : Promise.resolve();

        startPromise.then(() => {
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0,
            };

            const targetSource = cameraIdOrConstraints || selectedCameraId || { facingMode: "environment" };

            return html5QrCode.start(
                targetSource,
                config,
                (decodedText) => {
                    if (!isProcessing) {
                        submitScanCode(decodedText);
                    }
                },
                (errorMessage) => {
                    // Ignore transient per-frame decode misses
                }
            );
        })
        .then(() => {
            setCameraStatus("active", "Kamera Scanner Aktif");
            updateFeedback("neutral", "Siap Memindai QR Code", "Arahkan QR Code undangan wisuda tamu di depan kamera");

            // Populate camera list if not done yet
            if (availableCameras.length === 0) {
                enumerateCameras();
            }
        })
        .catch(err => {
            console.error("Camera startup failed:", err);
            
            // If facingMode environment failed (e.g. laptop webcam with only front camera), fallback to front/user
            if (cameraIdOrConstraints && typeof cameraIdOrConstraints === "object" && cameraIdOrConstraints.facingMode === "environment") {
                console.log("Retrying with user/front camera fallback...");
                startCamera({ facingMode: "user" });
                return;
            }

            setCameraStatus("stopped", "Kamera Gagal Dibuka");
            handleCameraError(err);
        });
    }

    // Detect and populate available cameras
    function enumerateCameras() {
        if (typeof Html5Qrcode === "undefined" || !Html5Qrcode.getCameras) return;

        Html5Qrcode.getCameras().then(devices => {
            if (devices && devices.length) {
                availableCameras = devices;
                cameraSelect.innerHTML = "";
                
                devices.forEach((device, index) => {
                    const option = document.createElement("option");
                    option.value = device.id;
                    option.textContent = device.label || `Kamera ${index + 1}`;
                    cameraSelect.appendChild(option);
                });

                cameraSelect.classList.remove("hidden");

                // Auto-pick environment / rear camera if available on mobile
                if (!selectedCameraId) {
                    const backCam = devices.find(d => 
                        (d.label || "").toLowerCase().includes("back") || 
                        (d.label || "").toLowerCase().includes("belakang") ||
                        (d.label || "").toLowerCase().includes("rear") ||
                        (d.label || "").toLowerCase().includes("environment")
                    );
                    selectedCameraId = backCam ? backCam.id : devices[0].id;
                    cameraSelect.value = selectedCameraId;
                }
            }
        }).catch(err => {
            console.warn("Could not enumerate cameras yet:", err);
        });
    }

    // Handle camera permission and device errors with actionable tips
    function handleCameraError(err) {
        const errName = err.name || "";
        const errMsg = String(err).toLowerCase();

        cameraPromptIcon.textContent = "⚠️";
        btnStartCameraOverlay.classList.remove("hidden");

        if (errName === "NotAllowedError" || errName === "PermissionDeniedError" || errMsg.includes("permission") || errMsg.includes("not allowed")) {
            cameraPromptTitle.textContent = "Izin Kamera Dibutuhkan";
            cameraPromptDesc.textContent = "Izin kamera ditolak di browser. Klik ikon di sebelah kiri URL browser untuk mengizinkan kamera, lalu klik tombol di bawah.";
            updateFeedback("error", "Izin Kamera Ditolak", "Klik ikon gembok/pengaturan situs di address bar browser untuk mengizinkan kamera.");
        } else if (errName === "NotFoundError" || errName === "DevicesNotFoundError" || errMsg.includes("not found")) {
            cameraPromptTitle.textContent = "Kamera Tidak Ditemukan";
            cameraPromptDesc.textContent = "Perangkat tidak memiliki kamera yang terhubung. Anda tetap dapat menggunakan input manual atau scanner barcode USB di bawah.";
            updateFeedback("error", "Kamera Tidak Ada", "Gunakan input manual atau barcode reader USB.");
        } else if (errName === "NotReadableError" || errName === "TrackStartError" || errMsg.includes("busy") || errMsg.includes("already in use")) {
            cameraPromptTitle.textContent = "Kamera Sedang Digunakan";
            cameraPromptDesc.textContent = "Kamera sedang dipakai oleh aplikasi lain (seperti Zoom atau Google Meet). Tutup aplikasi tersebut lalu klik tombol di bawah.";
            updateFeedback("error", "Kamera Sedang Dipakai", "Tutup aplikasi video lain yang sedang aktif.");
        } else {
            cameraPromptTitle.textContent = "Gagal Membuka Kamera";
            cameraPromptDesc.textContent = "Pastikan koneksi browser aman (HTTPS) dan perangkat mendukung kamera web.";
            updateFeedback("error", "Gagal Memulai Kamera", "Gunakan input manual di bawah jika kamera bermasalah.");
        }
    }

    // Event listeners
    btnToggleCamera.addEventListener("click", function () {
        if (isCameraActive) {
            stopCamera();
        } else {
            startCamera(selectedCameraId);
        }
    });

    btnStartCameraOverlay.addEventListener("click", function () {
        startCamera(selectedCameraId);
    });

    cameraSelect.addEventListener("change", function () {
        selectedCameraId = this.value;
        if (isCameraActive) {
            startCamera(selectedCameraId);
        }
    });

    manualForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const code = manualInput.value.trim();
        if (code) {
            submitScanCode(code);
            manualInput.value = "";
            manualInput.focus();
        }
    });

    // Initial load: Enumerate and attempt start
    enumerateCameras();
    setTimeout(() => {
        // Try auto-starting with selected camera or auto-detect
        startCamera(selectedCameraId);
    }, 300);
});
</script>

<style>
/* Custom styling to ensure video fits perfectly inside rounded container */
#reader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 1rem;
}
#reader {
    width: 100% !important;
    height: 100% !important;
    border: none !important;
}
#reader img[alt="Info icon"],
#reader #reader__dashboard,
#reader #reader__header_message {
    display: none !important;
}
@keyframes scan {
    0% { top: 0%; }
    50% { top: 100%; }
    100% { top: 0%; }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection
