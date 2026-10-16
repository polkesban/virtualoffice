@extends('layouts.app')

@section('title', 'Speedtest Server')
@section('description', 'Uji ping, download, dan upload langsung ke server Poltekkes Bandung dari browser Anda.')

@php
    $speedtestLocaleTag = \App\Support\PublicLocale::localeTag();
    $speedtestMessages = \App\Support\PublicLocale::translateData([
        'pingFailed' => 'Ping gagal',
        'downloadFailed' => 'Download paralel gagal',
        'uploadFailed' => 'Upload gagal',
        'statusMeasuringPing' => 'Mengukur ping ke server',
        'detailMeasuringPing' => 'Tahap 1 dari 3: beberapa request ringan dijalankan untuk mendapatkan latency rata-rata.',
        'statusMeasuringDownload' => 'Mengukur download dari server',
        'detailDownloadPrefix' => 'Tahap 2 dari 3: mengunduh file statis sekitar',
        'detailDownloadMiddle' => 'MB lewat',
        'detailDownloadSuffix' => 'koneksi paralel ke web server.',
        'statusMeasuringUpload' => 'Mengukur upload ke server',
        'detailUploadPrefix' => 'Tahap 3 dari 3: mengirim',
        'detailUploadMiddle' => 'KB per request dalam',
        'detailUploadSuffix' => 'batch kecil.',
        'statusCompleted' => 'Tes selesai',
        'detailCompletedPrefix' => 'Selesai pada',
        'detailCompletedSuffix' => 'Jalankan ulang beberapa kali untuk angka yang lebih stabil.',
        'statusCancelled' => 'Tes dibatalkan',
        'detailCancelled' => 'Proses dihentikan sebelum selesai. Anda bisa memulai ulang kapan saja.',
        'statusFailed' => 'Tes gagal',
        'detailUnknownError' => 'Terjadi kesalahan yang tidak dikenal.',
    ]);
@endphp

@section('content')
<section class="relative overflow-hidden bg-brand-900 py-14 sm:py-18">
    <div class="absolute inset-0 opacity-20" aria-hidden="true" style="background-image: radial-gradient(circle at top right, rgba(14,165,233,0.55), transparent 34%), radial-gradient(circle at bottom left, rgba(34,197,94,0.55), transparent 40%);"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav class="mb-4 flex text-sm text-brand-200" aria-label="Breadcrumb">
            <ol class="flex items-center gap-1.5" role="list">
                <li><a href="{{ route('home') }}" class="transition-colors hover:text-white">Beranda</a></li>
                <li aria-hidden="true"><span>/</span></li>
                <li class="text-white" aria-current="page">Speedtest</li>
            </ol>
        </nav>

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)] lg:items-end">
            <div>
                <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-brand-100">Diagnostik jaringan</span>
                <h1 class="mt-5 max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl">Uji kecepatan langsung ke server website ini.</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-brand-100 sm:text-lg">
                    Halaman ini mengukur jalur dari browser Anda ke server Poltekkes Bandung. Hasilnya cocok untuk mengecek apakah VPS ini layak menjadi origin webinar atau layanan realtime ringan.
                </p>
            </div>

            <div class="rounded-3xl border border-white/15 bg-white/10 p-6 text-sm text-brand-50 shadow-2xl shadow-brand-950/30 backdrop-blur-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-200">Profil tes</p>
                <dl class="mt-4 space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-brand-100">Host tujuan</dt>
                        <dd class="text-right font-semibold text-white">{{ request()->getHost() }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-brand-100">Sampel download</dt>
                        <dd class="text-right font-semibold text-white">{{ number_format($downloadBytes / 1000000, 0) }} MB</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-brand-100">Batas upload per request</dt>
                        <dd class="text-right font-semibold text-white">{{ $maxUploadKb }} KB</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-brand-100">Sumber download</dt>
                        <dd class="text-right font-semibold text-white">File statis langsung dari web server</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</section>

<section class="bg-[linear-gradient(180deg,#f8fafc_0%,#eff6ff_100%)] py-16">
    <div
        id="speedtest-app"
        class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"
        data-ping-url="{{ route('speedtest.ping') }}"
        data-download-url="{{ $downloadUrl }}"
        data-upload-url="{{ route('speedtest.upload') }}"
        data-download-bytes="{{ $downloadBytes }}"
        data-download-parallel="4"
        data-upload-chunk-bytes="{{ $uploadChunkBytes }}"
        data-upload-rounds="{{ $uploadRounds }}"
        data-upload-parallel="{{ $uploadParallel }}"
        data-ping-iterations="{{ $pingIterations }}"
    >
        <div class="grid gap-8 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)]">
            <div class="rounded-[2rem] border border-brand-100 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.08)] sm:p-8">
                <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Panel uji</p>
                        <h2 class="mt-2 text-2xl font-black tracking-tight text-slate-950">Ping, download, dan upload</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Klik mulai untuk menjalankan tes penuh. Hasil akan berubah sesuai jaringan, Wi-Fi, browser, dan lokasi Anda terhadap server.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button id="speedtest-start" type="button" class="inline-flex items-center justify-center rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                            Mulai Tes
                        </button>
                        <button id="speedtest-cancel" type="button" class="inline-flex items-center justify-center rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2" disabled>
                            Batalkan
                        </button>
                    </div>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-slate-200 bg-slate-950 p-6 text-white shadow-inner shadow-black/20 sm:p-8">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-300">Ping</p>
                            <p id="metric-ping" class="mt-4 text-4xl font-black tracking-tight text-white">--</p>
                            <p class="mt-1 text-sm text-slate-400">Latency rata-rata</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-300">Download</p>
                            <p id="metric-download" class="mt-4 text-4xl font-black tracking-tight text-cyan-300">--</p>
                            <p class="mt-1 text-sm text-slate-400">Kecepatan dari server ke browser</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-300">Upload</p>
                            <p id="metric-upload" class="mt-4 text-4xl font-black tracking-tight text-emerald-300">--</p>
                            <p class="mt-1 text-sm text-slate-400">Kecepatan dari browser ke server</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <div class="flex items-center justify-between gap-4 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            <span id="speedtest-status">Siap dijalankan</span>
                            <span id="speedtest-progress-label">0%</span>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-white/10">
                            <div id="speedtest-progress" class="h-full w-0 rounded-full bg-gradient-to-r from-brand-500 via-cyan-400 to-emerald-400 transition-[width] duration-300"></div>
                        </div>
                        <p id="speedtest-detail" class="mt-4 text-sm leading-6 text-slate-300">Download memakai beberapa koneksi paralel ke file statis web server agar hasilnya lebih mendekati throughput nyata, sedangkan upload tetap memakai batch kecil agar aman untuk server produksi.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-[0_20px_70px_rgba(15,23,42,0.06)] sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Cara baca</p>
                    <div class="mt-5 space-y-5 text-sm leading-6 text-slate-600">
                        <div>
                            <h3 class="font-semibold text-slate-950">Ping rendah</h3>
                            <p class="mt-1">Bagus untuk respons UI, kelas daring, dan interaksi realtime ringan.</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-950">Download tinggi</h3>
                            <p class="mt-1">Menunjukkan server ini cepat mengirim konten ke peserta atau penonton.</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-950">Upload tinggi</h3>
                            <p class="mt-1">Penting jika perangkat peserta harus sering mengirim data ke server.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-brand-100 bg-brand-50 p-6 shadow-[0_20px_70px_rgba(22,101,52,0.08)] sm:p-7">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-700">Catatan</p>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-brand-900">
                        <li>Hasil ini mengukur koneksi browser Anda ke server website, bukan kapasitas total backbone provider.</li>
                        <li>Download diambil dari file statis dengan beberapa koneksi paralel agar angka throughput tidak tertahan satu koneksi tunggal browser.</li>
                        <li>Upload dibagi ke beberapa request kecil agar tetap lolos limit body request server web yang umum dipakai.</li>
                        <li>Jika nanti ingin dipakai untuk benchmarking resmi, langkah berikutnya adalah tes uplink server dengan tool jaringan tingkat OS.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('speedtest-app');

    if (!root) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const pingUrl = root.dataset.pingUrl;
    const downloadUrl = root.dataset.downloadUrl;
    const uploadUrl = root.dataset.uploadUrl;
    const downloadBytes = Number(root.dataset.downloadBytes || 12000000);
    const downloadParallel = Number(root.dataset.downloadParallel || 4);
    const uploadChunkBytes = Number(root.dataset.uploadChunkBytes || 500000);
    const uploadRounds = Number(root.dataset.uploadRounds || 4);
    const uploadParallel = Number(root.dataset.uploadParallel || 2);
    const pingIterations = Number(root.dataset.pingIterations || 5);
    const localeTag = @js($speedtestLocaleTag);
    const messages = @js($speedtestMessages);

    const startButton = document.getElementById('speedtest-start');
    const cancelButton = document.getElementById('speedtest-cancel');
    const statusNode = document.getElementById('speedtest-status');
    const detailNode = document.getElementById('speedtest-detail');
    const progressNode = document.getElementById('speedtest-progress');
    const progressLabelNode = document.getElementById('speedtest-progress-label');
    const pingNode = document.getElementById('metric-ping');
    const downloadNode = document.getElementById('metric-download');
    const uploadNode = document.getElementById('metric-upload');

    const state = {
        running: false,
        controller: null,
    };

    function setButtons(isRunning) {
        state.running = isRunning;
        startButton.disabled = isRunning;
        cancelButton.disabled = !isRunning;
        startButton.classList.toggle('opacity-60', isRunning);
        startButton.classList.toggle('cursor-not-allowed', isRunning);
        cancelButton.classList.toggle('opacity-50', !isRunning);
        cancelButton.classList.toggle('cursor-not-allowed', !isRunning);
    }

    function setProgress(percent) {
        const safePercent = Math.max(0, Math.min(100, percent));
        progressNode.style.width = `${safePercent}%`;
        progressLabelNode.textContent = `${Math.round(safePercent)}%`;
    }

    function setStatus(message) {
        statusNode.textContent = message;
    }

    function setDetail(message) {
        detailNode.textContent = message;
    }

    function setMetric(node, value, unit) {
        node.textContent = value === null ? '--' : `${value} ${unit}`;
    }

    function formatMbps(value) {
        if (!Number.isFinite(value)) {
            return null;
        }

        return value >= 100 ? value.toFixed(0) : value.toFixed(1);
    }

    function formatPing(value) {
        if (!Number.isFinite(value)) {
            return null;
        }

        return value >= 100 ? value.toFixed(0) : value.toFixed(1);
    }

    function buildRandomPayload(size) {
        const buffer = new Uint8Array(size);
        const chunkSize = 65536;

        for (let offset = 0; offset < size; offset += chunkSize) {
            crypto.getRandomValues(buffer.subarray(offset, Math.min(offset + chunkSize, size)));
        }

        return new Blob([buffer], { type: 'application/octet-stream' });
    }

    function withCacheBuster(url) {
        return `${url}${url.includes('?') ? '&' : '?'}t=${Date.now()}`;
    }

    async function readResponseBytes(response, onProgress) {
        let receivedBytes = 0;

        if (response.body && typeof response.body.getReader === 'function') {
            const reader = response.body.getReader();

            while (true) {
                const chunk = await reader.read();

                if (chunk.done) {
                    break;
                }

                receivedBytes += chunk.value.byteLength;
                onProgress(chunk.value.byteLength);
            }

            return receivedBytes;
        }

        const buffer = await response.arrayBuffer();
        receivedBytes = buffer.byteLength;
        onProgress(receivedBytes);

        return receivedBytes;
    }

    async function measurePing(signal) {
        const samples = [];

        for (let index = 0; index < pingIterations; index += 1) {
            const startedAt = performance.now();
            const response = await fetch(`${pingUrl}?t=${Date.now()}-${index}`, {
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                },
                signal,
            });

            if (!response.ok) {
                throw new Error(`${messages.pingFailed} (${response.status})`);
            }

            await response.json();
            samples.push(performance.now() - startedAt);
            setProgress(6 + ((index + 1) / pingIterations) * 14);
        }

        return samples.reduce((sum, current) => sum + current, 0) / samples.length;
    }

    async function measureDownload(signal) {
        const connectionCount = Math.max(1, Math.min(downloadParallel, 8));
        const partSize = Math.ceil(downloadBytes / connectionCount);
        let bytesReceived = 0;
        const startedAt = performance.now();
        const downloadTasks = Array.from({ length: connectionCount }, async (_, index) => {
            const rangeStart = index * partSize;

            if (rangeStart >= downloadBytes) {
                return 0;
            }

            const rangeEnd = Math.min(downloadBytes - 1, rangeStart + partSize - 1);
            const response = await fetch(withCacheBuster(`${downloadUrl}${downloadUrl.includes('?') ? '&' : '?'}part=${index}`), {
                cache: 'no-store',
                headers: {
                    Accept: 'application/octet-stream',
                    Range: `bytes=${rangeStart}-${rangeEnd}`,
                },
                signal,
            });

            if (response.status !== 206) {
                throw new Error(`${messages.downloadFailed} (${response.status}).`);
            }

            return readResponseBytes(response, (chunkBytes) => {
                bytesReceived += chunkBytes;
                const ratio = downloadBytes > 0 ? bytesReceived / downloadBytes : 0;
                setProgress(20 + Math.min(1, ratio) * 50);
            });
        });

        const completedParts = await Promise.all(downloadTasks);
        const totalBytes = completedParts.reduce((sum, current) => sum + current, 0);

        const elapsedSeconds = Math.max((performance.now() - startedAt) / 1000, 0.001);

        return (totalBytes * 8) / elapsedSeconds / 1000000;
    }

    async function uploadChunk(payload, round, lane, signal) {
        const response = await fetch(`${uploadUrl}?t=${Date.now()}-${round}-${lane}`, {
            method: 'POST',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/octet-stream',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: payload,
            signal,
        });

        if (!response.ok) {
            throw new Error(`${messages.uploadFailed} (${response.status})`);
        }

        const data = await response.json();
        return Number(data.bytes_received || 0);
    }

    async function measureUpload(signal) {
        const payload = buildRandomPayload(uploadChunkBytes);
        let totalBytes = 0;
        const startedAt = performance.now();

        for (let round = 0; round < uploadRounds; round += 1) {
            const requests = Array.from({ length: uploadParallel }, (_, lane) => uploadChunk(payload, round, lane, signal));
            const results = await Promise.all(requests);

            totalBytes += results.reduce((sum, value) => sum + value, 0);
            setProgress(70 + ((round + 1) / uploadRounds) * 30);
        }

        const elapsedSeconds = Math.max((performance.now() - startedAt) / 1000, 0.001);

        return (totalBytes * 8) / elapsedSeconds / 1000000;
    }

    async function runSpeedTest() {
        if (state.running) {
            return;
        }

        state.controller = new AbortController();
        setButtons(true);
        setProgress(2);
        setMetric(pingNode, null, 'ms');
        setMetric(downloadNode, null, 'Mbps');
        setMetric(uploadNode, null, 'Mbps');
        setStatus(messages.statusMeasuringPing);
        setDetail(messages.detailMeasuringPing);

        try {
            const ping = await measurePing(state.controller.signal);
            setMetric(pingNode, formatPing(ping), 'ms');

            setStatus(messages.statusMeasuringDownload);
            setDetail(`${messages.detailDownloadPrefix} ${(downloadBytes / 1000000).toFixed(0)} ${messages.detailDownloadMiddle} ${downloadParallel} ${messages.detailDownloadSuffix}`);
            const download = await measureDownload(state.controller.signal);
            setMetric(downloadNode, formatMbps(download), 'Mbps');

            setStatus(messages.statusMeasuringUpload);
            setDetail(`${messages.detailUploadPrefix} ${(uploadChunkBytes / 1000).toFixed(0)} ${messages.detailUploadMiddle} ${uploadRounds * uploadParallel} ${messages.detailUploadSuffix}`);
            const upload = await measureUpload(state.controller.signal);
            setMetric(uploadNode, formatMbps(upload), 'Mbps');

            setStatus(messages.statusCompleted);
            setDetail(`${messages.detailCompletedPrefix} ${new Date().toLocaleTimeString(localeTag, { hour: '2-digit', minute: '2-digit', second: '2-digit' })}. ${messages.detailCompletedSuffix}`);
            setProgress(100);
        } catch (error) {
            if (error instanceof DOMException && error.name === 'AbortError') {
                setStatus(messages.statusCancelled);
                setDetail(messages.detailCancelled);
            } else {
                setStatus(messages.statusFailed);
                setDetail(error instanceof Error ? error.message : messages.detailUnknownError);
            }
        } finally {
            state.controller = null;
            setButtons(false);
        }
    }

    startButton.addEventListener('click', runSpeedTest);
    cancelButton.addEventListener('click', () => {
        state.controller?.abort();
    });

    setButtons(false);
    setProgress(0);
});
</script>
@endpush