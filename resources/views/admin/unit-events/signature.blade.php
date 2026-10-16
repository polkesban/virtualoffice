@extends('admin.layouts.admin')
@section('title', 'Tanda Tangan Penanggung Jawab - ' . $unitEvent->judul)
@section('page-title', 'Tanda Tangan Penanggung Jawab')
@section('page-subtitle', 'Bubuhkan tanda tangan digital penanggung jawab yang otomatis diterapkan ke semua dokumen kegiatan')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-200 flex items-center gap-3">
            <span class="text-xl">✅</span>
            <div class="flex-1 font-medium">{{ session('success') }}</div>
        </div>
    @endif

    @if(!empty($errors) && $errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header Kartu Info Kegiatan --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                    {{ \App\Models\UnitEvent::$jenisLabels[$unitEvent->jenis_kegiatan] ?? ucfirst($unitEvent->jenis_kegiatan) }}
                </span>
                <h3 class="mt-2 text-xl font-extrabold text-gray-900 dark:text-white">{{ $unitEvent->judul }}</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    📅 {{ $unitEvent->mulai_at?->translatedFormat('l, d F Y') ?: 'Belum diatur' }} 
                    @if($unitEvent->selesai_at && $unitEvent->selesai_at->format('Y-m-d') !== $unitEvent->mulai_at?->format('Y-m-d'))
                        - {{ $unitEvent->selesai_at->translatedFormat('d F Y') }}
                    @endif
                    • 📍 {{ $unitEvent->lokasi ?: ($unitEvent->zoom_link ? 'Daring / Online' : 'Poltekkes Kemenkes Bandung') }}
                </p>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                @if($unitEvent->penandatangan_signature_path)
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-bold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Tanda Tangan Aktif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-700 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        Belum Ada Tanda Tangan
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Form Bubuhkan Tanda Tangan --}}
    <form action="{{ route('admin.unit-events.signature.store', [$unitPage, $unitEvent]) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Kolom Kiri: Signature Pad & Input --}}
            <div class="lg:col-span-7 space-y-6">
                {{-- Data Pejabat Penandatangan --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>👤</span> 1. Identitas Penanggung Jawab / Pejabat
                    </h4>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Jabatan dalam Dokumen</label>
                        <input type="text" name="penandatangan_jabatan" id="penandatangan_jabatan" 
                               value="{{ old('penandatangan_jabatan', $unitEvent->penandatangan_jabatan ?: 'Panitia / Penanggung Jawab') }}" 
                               class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:bg-white transition"
                               placeholder="Contoh: Panitia / Penanggung Jawab Kegiatan">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap & Gelar</label>
                            <input type="text" name="penandatangan_nama" id="penandatangan_nama" 
                                   value="{{ old('penandatangan_nama', $unitEvent->penandatangan_nama) }}" 
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:bg-white transition"
                                   placeholder="Contoh: Dr. Pramita Iriana, S.Kep., M.Biomed">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">NIP (Opsional)</label>
                            <input type="text" name="penandatangan_nip" id="penandatangan_nip" 
                                   value="{{ old('penandatangan_nip', $unitEvent->penandatangan_nip) }}" 
                                   class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:bg-white transition"
                                   placeholder="Contoh: 197009211996032001">
                        </div>
                    </div>
                </div>

                {{-- Area Tanda Tangan Digital --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3 dark:border-gray-800">
                        <h4 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <span>✍️</span> 2. Bubuhkan Tanda Tangan
                        </h4>

                        {{-- Mode Selector --}}
                        <div class="inline-flex rounded-xl bg-gray-100 p-1 text-xs dark:bg-gray-800" role="tablist">
                            <button type="button" id="tab-draw" class="rounded-lg px-3 py-1.5 font-bold transition-all bg-white text-gray-900 shadow-2xs dark:bg-gray-700 dark:text-white">
                                Gores di Layar
                            </button>
                            <button type="button" id="tab-upload" class="rounded-lg px-3 py-1.5 font-bold transition-all text-gray-650 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                Unggah Berkas Gambar
                            </button>
                        </div>
                    </div>

                    {{-- Mode 1: Signature Canvas (Optimized for iPhone / iPad / Touch / Mouse) --}}
                    <div id="section-draw" class="space-y-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Silakan goreskan tanda tangan Anda pada bidang putih di bawah menggunakan jari (HP iPhone/Android), stylus pen, touchpad, atau mouse.
                        </p>

                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-white p-2.5 dark:border-gray-700 shadow-inner">
                            <canvas id="pjSignatureCanvas" 
                                    style="touch-action: none; -webkit-touch-callout: none; -webkit-user-select: none; user-select: none; display: block; width: 100%; height: 220px; cursor: crosshair; border-radius: 0.75rem; background-color: #ffffff;"
                                    class="rounded-xl"></canvas>
                        </div>

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <button type="button" id="btn-clear-canvas" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:border-red-300 hover:bg-red-50 hover:text-red-700 transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                ↺ Hapus & Ulangi
                            </button>
                            <span class="text-[11px] text-gray-400 italic">
                                Presisi tinggi dengan deteksi sentuh iPhone Retina
                            </span>
                        </div>
                    </div>

                    {{-- Mode 2: Upload File Gambar --}}
                    <div id="section-upload" class="space-y-3" style="display: none;">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Punya file gambar scan tanda tangan transparan atau berlatar putih? Unggah berkas gambar (PNG, JPG, WEBP, maks 4MB).
                        </p>

                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-800/40">
                            <input type="file" name="penandatangan_signature_file" id="penandatangan_signature_file" accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden">
                            <label for="penandatangan_signature_file" class="cursor-pointer flex flex-col items-center justify-center gap-2">
                                <span class="text-3xl">📁</span>
                                <span class="text-sm font-bold text-brand-600 hover:underline dark:text-brand-400">Klik di sini untuk memilih file gambar</span>
                                <span id="upload-file-label" class="text-xs text-gray-400">Format yang didukung: PNG, JPG, JPEG (Maks. 4MB)</span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="penandatangan_signature_data" id="penandatangan_signature_data" value="">
                </div>

                {{-- Action Submit --}}
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="flex-1 rounded-xl bg-brand-600 px-6 py-3 text-sm font-extrabold text-white hover:bg-brand-700 shadow-md shadow-brand-600/20 transition-all flex items-center justify-center gap-2">
                        <span>💾</span>
                        <span>Simpan & Terapkan ke Semua Dokumen</span>
                    </button>
                </div>
            </div>

            {{-- Kolom Kanan: Pratinjau Tanda Tangan & Dokumen Terkait --}}
            <div class="lg:col-span-5 space-y-6">
                {{-- Preview Live Stempel / Dokumen --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>👁️</span> Pratinjau di Cetakan Dokumen
                    </h4>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Inilah tampilan bagian "Mengetahui" pada bagian bawah berkas cetakan resmi:
                    </p>

                    <div class="rounded-2xl border border-gray-200 bg-amber-50/40 p-5 dark:border-gray-700 dark:bg-gray-800/60 font-sans text-center text-gray-900 dark:text-gray-100 space-y-1">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Bandung, {{ $unitEvent->mulai_at?->translatedFormat('d F Y') ?: now()->translatedFormat('d F Y') }}
                        </div>
                        <div class="text-xs font-medium">Mengetahui,</div>
                        <div id="preview-jabatan" class="text-xs font-bold text-gray-800 dark:text-gray-200">
                            {{ $unitEvent->penandatangan_jabatan ?: 'Panitia / Penanggung Jawab' }}
                        </div>

                        {{-- Wadah Gambar Tanda Tangan --}}
                        <div class="h-20 flex items-center justify-center my-2 p-1">
                            <img id="live-signature-preview" 
                                 src="{{ $unitEvent->penandatangan_signature_path ? \App\Support\MediaStorage::url($unitEvent->penandatangan_signature_path) : '' }}" 
                                 alt="Tanda Tangan" 
                                 class="max-h-20 max-w-full object-contain {{ $unitEvent->penandatangan_signature_path ? '' : 'hidden' }}">
                            <div id="signature-placeholder" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg px-4 py-3 {{ $unitEvent->penandatangan_signature_path ? 'hidden' : '' }}">
                                (Tanda tangan akan muncul di sini)
                            </div>
                        </div>

                        <div id="preview-nama" class="text-xs font-extrabold underline">
                            {{ $unitEvent->penandatangan_nama ?: '..............................................' }}
                        </div>
                        <div id="preview-nip" class="text-[11px] text-gray-600 dark:text-gray-400">
                            {{ $unitEvent->penandatangan_nip ? 'NIP. ' . $unitEvent->penandatangan_nip : '' }}
                        </div>
                    </div>

                    @if($unitEvent->penandatangan_signature_path)
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                                ✓ Tanda tangan tersimpan di server
                            </span>
                            
                            <button type="button" onclick="if(confirm('Yakin ingin menghapus tanda tangan penanggung jawab dari semua dokumen kegiatan ini?')) { document.getElementById('form-destroy-signature').submit(); }" class="text-xs font-bold text-red-600 hover:underline">
                                Hapus Tanda Tangan
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Tautan Langsung Cek Dokumen Terkait --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
                    <h4 class="text-sm font-extrabold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <span>📄</span> Dokumen yang Memuat TTD Ini
                    </h4>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Begitu Anda menyimpan tanda tangan di atas, tanda tangan langsung diterapkan ke seluruh lembar resmi berikut:
                    </p>

                    <div class="space-y-2">
                        <a href="{{ route('admin.unit-events.participants.attendance-print', [$unitPage, $unitEvent]) }}" target="_blank"
                           class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs font-bold text-gray-700 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <span class="flex items-center gap-2">
                                <span>📋</span> Rekap Cetak Daftar Hadir Peserta
                            </span>
                            <span>↗</span>
                        </a>

                        @if($unitEvent->jenis_kegiatan === 'kerja_lembur')
                            <a href="{{ route('admin.unit-events.participants.attendance-print', [$unitPage, $unitEvent, 'format' => 'lembur']) }}" target="_blank"
                               class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs font-bold text-gray-700 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                <span class="flex items-center gap-2">
                                    <span>⏱️</span> Cetak Daftar Hadir Kerja Lembur
                                </span>
                                <span>↗</span>
                            </a>
                        @endif

                        <a href="{{ route('unit.events.berita-acara', [$unitPage->slug, $unitEvent->slug]) }}" target="_blank"
                           class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs font-bold text-gray-700 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <span class="flex items-center gap-2">
                                <span>📑</span> Berita Acara Pelaksanaan Kegiatan
                            </span>
                            <span>↗</span>
                        </a>

                        <a href="{{ route('admin.unit-events.documentation.report-print', [$unitPage, $unitEvent]) }}" target="_blank"
                           class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 p-3 text-xs font-bold text-gray-700 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 transition dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <span class="flex items-center gap-2">
                                <span>📊</span> Laporan Dokumentasi & Pertanggungjawaban
                            </span>
                            <span>↗</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Form Hidden untuk Delete Signature --}}
    @if($unitEvent->penandatangan_signature_path)
        <form id="form-destroy-signature" action="{{ route('admin.unit-events.signature.destroy', [$unitPage, $unitEvent]) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabDraw = document.getElementById('tab-draw');
    const tabUpload = document.getElementById('tab-upload');
    const sectionDraw = document.getElementById('section-draw');
    const sectionUpload = document.getElementById('section-upload');

    tabDraw?.addEventListener('click', function() {
        tabDraw.className = "rounded-lg px-3 py-1.5 font-bold transition-all bg-white text-gray-900 shadow-2xs dark:bg-gray-700 dark:text-white";
        tabUpload.className = "rounded-lg px-3 py-1.5 font-bold transition-all text-gray-650 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white";
        sectionDraw.style.display = "block";
        sectionUpload.style.display = "none";
        setTimeout(() => resizeCanvas(true), 50);
    });

    tabUpload?.addEventListener('click', function() {
        tabUpload.className = "rounded-lg px-3 py-1.5 font-bold transition-all bg-white text-gray-900 shadow-2xs dark:bg-gray-700 dark:text-white";
        tabDraw.className = "rounded-lg px-3 py-1.5 font-bold transition-all text-gray-650 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white";
        sectionDraw.style.display = "none";
        sectionUpload.style.display = "block";
    });

    // Real-time preview synchronizer for text inputs
    const inputJabatan = document.getElementById('penandatangan_jabatan');
    const inputNama = document.getElementById('penandatangan_nama');
    const inputNip = document.getElementById('penandatangan_nip');

    const previewJabatan = document.getElementById('preview-jabatan');
    const previewNama = document.getElementById('preview-nama');
    const previewNip = document.getElementById('preview-nip');

    inputJabatan?.addEventListener('input', () => {
        previewJabatan.textContent = inputJabatan.value.trim() || 'Panitia / Penanggung Jawab';
    });
    inputNama?.addEventListener('input', () => {
        previewNama.textContent = inputNama.value.trim() || '..............................................';
    });
    inputNip?.addEventListener('input', () => {
        previewNip.textContent = inputNip.value.trim() ? ('NIP. ' + inputNip.value.trim()) : '';
    });

    // File Upload handling with image preview
    const fileInput = document.getElementById('penandatangan_signature_file');
    const fileLabel = document.getElementById('upload-file-label');
    const liveImg = document.getElementById('live-signature-preview');
    const placeholder = document.getElementById('signature-placeholder');

    fileInput?.addEventListener('change', function(e) {
        const file = e.target.files?.[0];
        if (file) {
            fileLabel.textContent = `Terpilih: ${file.name} (${Math.round(file.size / 1024)} KB)`;
            const reader = new FileReader();
            reader.onload = function(evt) {
                liveImg.src = evt.target.result;
                liveImg.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    });

    // --- BULLETPROOF SIGNATURE PAD FOR IPHONE / IPAD / ANDROID / DESKTOP ---
    const canvas = document.getElementById('pjSignatureCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const hiddenData = document.getElementById('penandatangan_signature_data');
    const clearBtn = document.getElementById('btn-clear-canvas');

    let isDrawing = false;
    let hasStroke = false;
    let cachedRect = null;
    let lastPoint = null;
    let currentWidth = 0;

    function setBrush() {
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = 2.6;
        ctx.strokeStyle = '#0f172a';
    }

    function fillBackground() {
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.restore();
    }

    function resizeCanvas(force = false) {
        const rect = canvas.getBoundingClientRect();
        if (rect.width <= 0) return;

        const displayWidth = Math.floor(rect.width);
        const displayHeight = 220;

        if (!force && Math.abs(currentWidth - displayWidth) < 2 && canvas.width > 0) {
            cachedRect = canvas.getBoundingClientRect();
            return;
        }

        currentWidth = displayWidth;
        const dpr = Math.max(window.devicePixelRatio || 1, 1);
        const existingData = hiddenData.value;

        canvas.width = Math.floor(displayWidth * dpr);
        canvas.height = Math.floor(displayHeight * dpr);
        canvas.style.width = displayWidth + 'px';
        canvas.style.height = displayHeight + 'px';

        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.scale(dpr, dpr);
        fillBackground();
        setBrush();

        if (existingData && existingData.startsWith('data:image/')) {
            const img = new Image();
            img.onload = function() {
                ctx.drawImage(img, 0, 0, displayWidth, displayHeight);
                hasStroke = true;
            };
            img.src = existingData;
        }

        cachedRect = canvas.getBoundingClientRect();
    }

    function getPos(e) {
        if (!cachedRect) {
            cachedRect = canvas.getBoundingClientRect();
        }
        let clientX = 0;
        let clientY = 0;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else if (e.changedTouches && e.changedTouches.length > 0) {
            clientX = e.changedTouches[0].clientX;
            clientY = e.changedTouches[0].clientY;
        } else if (typeof e.clientX === 'number') {
            clientX = e.clientX;
            clientY = e.clientY;
        }

        return {
            x: clientX - cachedRect.left,
            y: clientY - cachedRect.top
        };
    }

    function startDrawing(e) {
        if (e.cancelable) e.preventDefault();
        cachedRect = canvas.getBoundingClientRect();
        if (canvas.width === 0 || canvas.height === 0) {
            resizeCanvas(true);
        }
        isDrawing = true;
        hasStroke = true;
        lastPoint = getPos(e);

        setBrush();
        ctx.beginPath();
        ctx.moveTo(lastPoint.x, lastPoint.y);
        ctx.lineTo(lastPoint.x + 0.5, lastPoint.y + 0.5);
        ctx.stroke();

        if (e.pointerId !== undefined && canvas.setPointerCapture) {
            try { canvas.setPointerCapture(e.pointerId); } catch(err) {}
        }
    }

    function draw(e) {
        if (!isDrawing) return;
        if (e.cancelable) e.preventDefault();
        const pos = getPos(e);

        ctx.beginPath();
        ctx.moveTo(lastPoint.x, lastPoint.y);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        lastPoint = pos;
    }

    function stopDrawing(e) {
        if (!isDrawing) return;
        if (e && e.cancelable) e.preventDefault();
        isDrawing = false;
        lastPoint = null;

        if (hasStroke) {
            const dataUrl = canvas.toDataURL('image/png');
            hiddenData.value = dataUrl;
            liveImg.src = dataUrl;
            liveImg.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }

        if (e && e.pointerId !== undefined && canvas.releasePointerCapture) {
            try { canvas.releasePointerCapture(e.pointerId); } catch(err) {}
        }
    }

    function clearPad() {
        hasStroke = false;
        hiddenData.value = '';
        fillBackground();
        setBrush();
        @if(!$unitEvent->penandatangan_signature_path)
            liveImg.classList.add('hidden');
            placeholder.classList.remove('hidden');
        @else
            liveImg.src = "{{ \App\Support\MediaStorage::url($unitEvent->penandatangan_signature_path) }}";
        @endif
    }

    if (window.PointerEvent) {
        canvas.addEventListener('pointerdown', startDrawing);
        canvas.addEventListener('pointermove', draw);
        canvas.addEventListener('pointerup', stopDrawing);
        canvas.addEventListener('pointercancel', stopDrawing);
    } else {
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseleave', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDrawing, { passive: false });
        canvas.addEventListener('touchcancel', stopDrawing, { passive: false });
    }

    clearBtn?.addEventListener('click', clearPad);

    window.addEventListener('resize', () => resizeCanvas(false));
    window.addEventListener('scroll', () => {
        cachedRect = canvas.getBoundingClientRect();
    }, { passive: true });

    setTimeout(() => resizeCanvas(true), 60);
    setTimeout(() => resizeCanvas(true), 300);

    const form = canvas.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            if (hasStroke) {
                hiddenData.value = canvas.toDataURL('image/png');
            }
        });
    }
});
</script>
@endpush
