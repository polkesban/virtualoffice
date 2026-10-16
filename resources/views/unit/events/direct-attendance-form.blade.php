@extends('layouts.app')
@section('title', ($formType === 'berita_acara' ? 'Penandatanganan Berita Acara' : 'Daftar Hadir Langsung') . ' - ' . $event->judul)
@section('description', 'Isi daftar hadir untuk event ' . $event->judul . ' tanpa perlu registrasi terlebih dahulu')
@section('og_image', asset('favicon.png'))

@section('content')
@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $attendanceDates = $attendanceDates ?? collect();
    $selectedAttendanceDate = old('attendance_date') ? \Carbon\Carbon::parse(old('attendance_date')) : $attendanceDates->first();
    $formType = old('form_type', $formType ?? 'daftar_hadir');
    $showBeritaAcaraForm = $event->berita_acara_form_aktif ?? false;
@endphp

<section class="bg-gray-50 py-12 dark:bg-gray-950">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('unit.events.show', [$page->slug, $event->slug]) }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300">← Kembali ke event</a>
            <h1 class="mt-3 text-3xl font-black text-gray-900 dark:text-white">{{ $formType === 'berita_acara' ? 'Penandatanganan Berita Acara' : 'Daftar Hadir Langsung' }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $event->judul }}</p>
        </div>

        @if($viewErrors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($viewErrors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ route('unit.events.attendance.direct.submit', [$page->slug, $event->slug]) }}"
              class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @csrf
            <input type="hidden" name="form_type" value="{{ $formType }}">

            <div class="space-y-6">
                {{-- Data Peserta --}}
                <fieldset class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700">
                    <legend class="text-sm font-bold text-gray-700 dark:text-gray-300">Data Peserta</legend>

                    <div class="mt-4 space-y-4">
                        <!-- Fitur Pengisian Berdasarkan NIP/NIM -->
                        <div class="rounded-2xl border border-brand-100 bg-brand-50/30 p-4 dark:border-brand-900/30 dark:bg-brand-950/10">
                            <label for="lookup_nip" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Cari Berdasarkan NIP / NIM (Pegawai / Mahasiswa)</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Masukkan NIP atau NIM Anda untuk mengisi data nama, email, instansi, dan unit kerja secara otomatis.</p>
                            <div class="mt-2 flex gap-2">
                                <input type="text"
                                       id="lookup_nip"
                                       placeholder="Contoh: 1982... atau P173..."
                                       class="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                                <button type="button"
                                        id="btn_lookup_nip"
                                        class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-300 dark:focus:ring-brand-900/40 flex items-center gap-1">
                                    <span id="btn_lookup_text">Cari</span>
                                    <svg id="btn_lookup_spinner" class="animate-spin h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>
                            <p id="nip_lookup_feedback" class="mt-2 text-xs font-semibold hidden"></p>
                        </div>

                        <div>
                            <label for="nama" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nama Lengkap <span class="text-red-600">*</span></label>
                            <input type="text"
                                   id="nama"
                                   name="nama"
                                   value="{{ old('nama', '') }}"
                                   placeholder="Masukkan nama lengkap Anda"
                                   autocomplete="name"
                                   class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500 dark:focus:ring-brand-900/40"
                                   required>
                            @error('nama')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       value="{{ old('email', '') }}"
                                       placeholder="email@contoh.com"
                                       autocomplete="email"
                                       class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                                @error('email')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="no_hp" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">No. HP / WhatsApp</label>
                                <input type="tel"
                                       id="no_hp"
                                       name="no_hp"
                                       value="{{ old('no_hp', '') }}"
                                       placeholder="+62 812 3456 7890"
                                       autocomplete="tel"
                                       class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                                @error('no_hp')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="instansi" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Instansi / Asal</label>
                            <select id="instansi_select"
                                    name="instansi_type"
                                    autocomplete="organization"
                                    class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                                <option value="poltekkes" {{ old('instansi_type', 'poltekkes') == 'poltekkes' ? 'selected' : '' }}>Poltekkes Kemenkes Bandung</option>
                                <option value="other" {{ old('instansi_type') == 'other' ? 'selected' : '' }}>Lainnya</option>
                            </select>
                            @error('instansi_type')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="instansi_manual_container" style="display: {{ old('instansi_type') == 'other' ? 'block' : 'none' }};">
                            <label for="instansi_manual" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Nama Instansi <span class="text-red-600">*</span></label>
                            <input type="text"
                                   id="instansi_manual"
                                   name="instansi_manual"
                                   value="{{ old('instansi_manual', '') }}"
                                   placeholder="Masukkan nama instansi atau organisasi Anda"
                                   autocomplete="organization"
                                   class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                            @error('instansi_manual')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="relative">
                            <label for="unit_kerja" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Unit Kerja <span id="unit_kerja_required" class="text-red-600">*</span></label>
                            <select id="unit_kerja"
                                    name="unit_kerja"
                                    autocomplete="organization"
                                    class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                                <option value="">-- Pilih Unit Kerja --</option>
                                @foreach($unitKerjaOptions as $value => $label)
                                    <option value="{{ $value }}" {{ old('unit_kerja') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('unit_kerja')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="attendance_status_container">
                            <label for="attendance_status" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Status Kehadiran <span class="text-red-600">*</span></label>
                            <select id="attendance_status"
                                    name="attendance_status"
                                    required
                                    autocomplete="attendance-status"
                                    class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                                <option value="hadir" {{ old('attendance_status', 'hadir') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                                <option value="izin" {{ old('attendance_status') == 'izin' ? 'selected' : '' }}>Izin</option>
                                <option value="sakit" {{ old('attendance_status') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                                <option value="tidak_hadir" {{ old('attendance_status') == 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                            </select>
                            @error('attendance_status')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </fieldset>

                {{-- Tanggal Kehadiran --}}
                @if($attendanceDates->count() > 1)
                    <div>
                        <label for="attendance_date_select" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih Hari Kehadiran <span class="text-red-600">*</span></label>
                        <select id="attendance_date_select"
                                name="attendance_date"
                                required
                                class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                            <option value="">-- Pilih Hari / Tanggal Kehadiran --</option>
                            @foreach($attendanceDates as $attendanceDate)
                                @php
                                    $dateKey = $attendanceDate->toDateString();
                                    $isSelectedDate = old('attendance_date', $selectedAttendanceDate?->toDateString()) === $dateKey;
                                @endphp
                                <option value="{{ $dateKey }}" {{ $isSelectedDate ? 'selected' : '' }}>
                                    Hari {{ $loop->iteration }}: {{ $attendanceDate->translatedFormat('l, d F Y') }}
                                </option>
                            @endforeach
                        </select>
                        @error('attendance_date')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div class="rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800/60">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Tanggal Kehadiran</p>
                        <p class="mt-2 text-lg font-extrabold text-gray-900 dark:text-white">{{ $selectedAttendanceDate?->translatedFormat('l, d F Y') }}</p>
                        <input type="hidden" name="attendance_date" value="{{ $selectedAttendanceDate?->toDateString() }}">
                    </div>
                @endif

                {{-- Catatan --}}
                <div>
                    <label for="attendance_notes" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Catatan (Opsional)</label>
                    <textarea id="attendance_notes"
                              name="attendance_notes"
                              rows="3"
                              placeholder="Tambahkan catatan apapun jika diperlukan"
                              autocomplete="off"
                              class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-gray-900 placeholder:text-gray-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-brand-500 dark:focus:ring-brand-900/40">{{ old('attendance_notes', '') }}</textarea>
                    @error('attendance_notes')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konten Berita Acara --}}
                @if($showBeritaAcaraForm)
                    <div id="berita_acara_container" style="display: {{ old('form_type', 'daftar_hadir') === 'berita_acara' ? 'block' : 'none' }};">
                        <div class="rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
                            <h3 class="text-sm font-bold text-brand-700 dark:text-brand-300 mb-3">📄 Konten Berita Acara</h3>
                            <div class="space-y-4">
                                @if(!empty($event->berita_acara_judul))
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Judul Berita Acara</p>
                                        <p class="mt-1 text-sm font-extrabold text-gray-900 dark:text-white">{{ $event->berita_acara_judul }}</p>
                                    </div>
                                @endif
                                @if(!empty($event->berita_acara_ringkasan))
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Ringkasan / Isi Berita Acara</p>
                                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $event->berita_acara_ringkasan }}</p>
                                    </div>
                                @endif
                                @if(empty($event->berita_acara_judul) && empty($event->berita_acara_ringkasan))
                                    <p class="text-sm text-gray-500 italic">Konten berita acara belum diisi oleh admin.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Tanda Tangan --}}
                <fieldset id="signatureSection" class="rounded-2xl border border-gray-200 p-5 dark:border-gray-700">
                    <legend class="text-sm font-bold text-gray-700 dark:text-gray-300">Tanda Tangan Digital <span class="text-red-600">*</span></legend>
                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">Silakan tanda tangani di area di bawah. Gunakan mouse, touchpad, atau layar sentuh Anda.</p>

                    <div class="mt-4 rounded-2xl border-2 border-dashed border-gray-300 bg-white p-2 dark:border-gray-600">
                        <canvas id="signatureCanvas"
                                style="touch-action: none; -webkit-touch-callout: none; -webkit-user-select: none; user-select: none; display: block; width: 100%; height: 240px; cursor: crosshair; border-radius: 0.75rem; background-color: #ffffff;"
                                class="rounded-xl"
                                data-testid="signature-canvas"></canvas>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <button type="button"
                                id="clearSignature"
                                class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Hapus
                        </button>
                        <p class="flex-1 text-xs text-gray-500 dark:text-gray-400">Format PNG • Ukuran otomatis • Sentuh atau gunakan mouse untuk tanda tangan</p>
                    </div>

                    <input type="hidden" id="signature_data" name="signature_data" value="{{ old('signature_data', '') }}">

                    @error('signature_data')
                        <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </fieldset>

                {{-- Preview Tanda Tangan --}}
                <div id="signaturePreview" style="display: none;" class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Preview Tanda Tangan</p>
                    <div id="previewCanvas" class="mt-2 rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-700"></div>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        id="submitDirectAttendanceBtn"
                        class="w-full rounded-2xl bg-brand-600 px-6 py-3 text-center font-bold text-white transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-300 dark:focus:ring-brand-900/40">
                    {{ $formType === 'berita_acara' ? 'Kirim Tandatangan Berita Acara' : 'Kirim Daftar Hadir' }}
                </button>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
    (function() {
        const canvas = document.getElementById('signatureCanvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const signatureInput = document.getElementById('signature_data');
        const clearBtn = document.getElementById('clearSignature');
        const previewDiv = document.getElementById('signaturePreview');
        const previewCanvas = document.getElementById('previewCanvas');

        let isDrawing = false;
        let hasStroke = (signatureInput.value || '').trim().startsWith('data:image/');
        let cachedRect = null;
        let lastPoint = null;
        let currentWidth = 0;

        function setBrush() {
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = 2.4;
            ctx.strokeStyle = '#0f172a';
        }

        function fillBackground() {
            ctx.save();
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.restore();
        }

        function resizeCanvas(forceRedraw = false) {
            const rect = canvas.getBoundingClientRect();
            if (rect.width <= 0) return;

            const displayWidth = Math.floor(rect.width);
            const displayHeight = 240;

            // Avoid destructive re-render if width hasn't changed (e.g. keyboard toggle or scroll)
            if (!forceRedraw && Math.abs(currentWidth - displayWidth) < 2 && canvas.width > 0) {
                cachedRect = canvas.getBoundingClientRect();
                return;
            }

            currentWidth = displayWidth;
            const dpr = Math.max(window.devicePixelRatio || 1, 1);
            const existingData = signatureInput.value;

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
                    updatePreview();
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
                signatureInput.value = canvas.toDataURL('image/png');
                updatePreview();
            }
            if (e && e.pointerId !== undefined && canvas.releasePointerCapture) {
                try { canvas.releasePointerCapture(e.pointerId); } catch(err) {}
            }
        }

        function updatePreview() {
            if (signatureInput.value && previewDiv && previewCanvas) {
                previewDiv.style.display = 'block';
                const img = new Image();
                img.src = signatureInput.value;
                img.style.maxHeight = '80px';
                img.style.display = 'block';
                img.style.margin = '0 auto';
                previewCanvas.innerHTML = '';
                previewCanvas.appendChild(img);
            }
        }

        function clearSignature() {
            hasStroke = false;
            signatureInput.value = '';
            fillBackground();
            setBrush();
            if (previewDiv) previewDiv.style.display = 'none';
            if (previewCanvas) previewCanvas.innerHTML = '';
        }

        // Pointer Events (iOS Safari 13+, Android Chrome, Modern Desktops)
        if (window.PointerEvent) {
            canvas.addEventListener('pointerdown', startDrawing);
            canvas.addEventListener('pointermove', draw);
            canvas.addEventListener('pointerup', stopDrawing);
            canvas.addEventListener('pointercancel', stopDrawing);
        } else {
            // Touch / Mouse fallback
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseleave', stopDrawing);

            canvas.addEventListener('touchstart', startDrawing, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            canvas.addEventListener('touchend', stopDrawing, { passive: false });
            canvas.addEventListener('touchcancel', stopDrawing, { passive: false });
        }

        clearBtn?.addEventListener('click', clearSignature);

        window.addEventListener('resize', () => {
            resizeCanvas(false);
        });

        window.addEventListener('scroll', () => {
            cachedRect = canvas.getBoundingClientRect();
        }, { passive: true });

        // Ensure canvas initializes properly after DOM and layout stabilize
        setTimeout(() => resizeCanvas(true), 50);
        setTimeout(() => resizeCanvas(true), 300);

        const form = canvas.closest('form');
        if (form) {
            form.addEventListener('submit', () => {
                if (hasStroke) {
                    signatureInput.value = canvas.toDataURL('image/png');
                }
            });
        }

        // Restore signature if there was an error
        if (signatureInput.value) {
            updatePreview();
        }

        // Auto scroll to signature pad on attendance date selection
        const dateSelect = document.getElementById('attendance_date_select');
        if (dateSelect) {
            dateSelect.addEventListener('change', function() {
                if (dateSelect.value) {
                    const signatureSection = document.getElementById('signatureSection');
                    if (signatureSection) {
                        setTimeout(() => {
                            signatureSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 100);
                    }
                }
            });
        }

        // Instansi selection handler (with null safety checks)
        const instansiSelect = document.getElementById('instansi_select');
        const instansiManualContainer = document.getElementById('instansi_manual_container');
        const instansiManualInput = document.getElementById('instansi_manual');
        const unitKerjaSelect = document.getElementById('unit_kerja');
        const unitKerjaRequired = document.getElementById('unit_kerja_required');
        const unitKerjaContainer = unitKerjaSelect ? unitKerjaSelect.closest('.relative') : null;

        function toggleInstansiFields() {
            if (!instansiSelect) return;
            const selectedValue = instansiSelect.value;
            if (selectedValue === 'other') {
                if (instansiManualContainer) instansiManualContainer.style.display = 'block';
                if (instansiManualInput) instansiManualInput.required = true;
                if (unitKerjaSelect) {
                    unitKerjaSelect.disabled = true;
                    unitKerjaSelect.required = false;
                    unitKerjaSelect.value = '';
                }
                if (unitKerjaRequired) unitKerjaRequired.style.display = 'none';
                if (unitKerjaContainer) unitKerjaContainer.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                if (instansiManualContainer) instansiManualContainer.style.display = 'none';
                if (instansiManualInput) {
                    instansiManualInput.required = false;
                    instansiManualInput.value = '';
                }
                if (unitKerjaSelect) {
                    unitKerjaSelect.disabled = false;
                    unitKerjaSelect.required = true;
                }
                if (unitKerjaRequired) unitKerjaRequired.style.display = 'inline';
                if (unitKerjaContainer) unitKerjaContainer.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        if (instansiSelect) {
            instansiSelect.addEventListener('change', toggleInstansiFields);
            toggleInstansiFields(); // Initial check
        }

        // Form type initialization
        const formTypeInput = document.querySelector('input[name="form_type"]');
        const formTypeValue = formTypeInput ? formTypeInput.value : 'daftar_hadir';
        const beritaAcaraContainer = document.getElementById('berita_acara_container');
        const submitBtn = document.getElementById('submitDirectAttendanceBtn');
        const attendanceStatusContainer = document.getElementById('attendance_status_container');
        const attendanceStatusSelect = document.getElementById('attendance_status');

        // NIP Lookup elements
        const lookupNipInput = document.getElementById('lookup_nip');
        const btnLookupNip = document.getElementById('btn_lookup_nip');
        const btnLookupText = document.getElementById('btn_lookup_text');
        const btnLookupSpinner = document.getElementById('btn_lookup_spinner');
        const nipLookupFeedback = document.getElementById('nip_lookup_feedback');

        const namaInput = document.getElementById('nama');
        const emailInput = document.getElementById('email');

        if (btnLookupNip) {
            btnLookupNip.addEventListener('click', function() {
                const nip = lookupNipInput.value.trim();
                if (!nip) {
                    showNipFeedback('NIP/NIM tidak boleh kosong.', 'text-red-600 dark:text-red-400');
                    return;
                }

                // Show loading
                btnLookupText.classList.add('hidden');
                btnLookupSpinner.classList.remove('hidden');
                btnLookupNip.disabled = true;
                hideNipFeedback();

                // Construct API url
                const url = `{{ route('unit.events.attendance.nip-lookup', [$page->slug, $event->slug]) }}?nip=${encodeURIComponent(nip)}`;

                fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw new Error(err.message || 'Gagal mencari identitas.') });
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.success && result.data) {
                            const data = result.data;
                            namaInput.value = data.nama || '';
                            if (data.email) {
                                emailInput.value = data.email;
                            }
                            
                            // Set instansi to poltekkes
                            instansiSelect.value = 'poltekkes';
                            toggleInstansiFields();

                            // Set unit kerja
                            if (data.unit_kerja) {
                                let optionExists = Array.from(unitKerjaSelect.options).some(opt => opt.value === data.unit_kerja);
                                if (!optionExists) {
                                    const newOpt = document.createElement('option');
                                    newOpt.value = data.unit_kerja;
                                    newOpt.textContent = data.unit_kerja;
                                    unitKerjaSelect.appendChild(newOpt);
                                }
                                unitKerjaSelect.value = data.unit_kerja;
                            }

                            showNipFeedback(data.is_student ? 'Data mahasiswa berhasil ditemukan!' : 'Data pegawai berhasil ditemukan!', 'text-green-600 dark:text-green-400');
                        } else {
                            showNipFeedback('Identitas tidak ditemukan.', 'text-red-600 dark:text-red-400');
                        }
                    })
                    .catch(error => {
                        showNipFeedback(error.message || 'Terjadi kesalahan sistem.', 'text-red-600 dark:text-red-400');
                    })
                    .finally(() => {
                        btnLookupText.classList.remove('hidden');
                        btnLookupSpinner.classList.add('hidden');
                        btnLookupNip.disabled = false;
                    });
            });
        }

        function showNipFeedback(message, className) {
            if (nipLookupFeedback) {
                nipLookupFeedback.textContent = message;
                nipLookupFeedback.className = 'mt-2 text-xs font-semibold ' + className;
                nipLookupFeedback.classList.remove('hidden');
            }
        }

        function hideNipFeedback() {
            if (nipLookupFeedback) {
                nipLookupFeedback.textContent = '';
                nipLookupFeedback.classList.add('hidden');
            }
        }

        function initFormType() {
            if (formTypeValue === 'berita_acara') {
                if (beritaAcaraContainer) {
                    beritaAcaraContainer.style.display = 'block';
                }
                if (attendanceStatusContainer) {
                    attendanceStatusContainer.style.display = 'none';
                }
                if (attendanceStatusSelect) {
                    attendanceStatusSelect.value = 'hadir';
                }
                if (submitBtn) {
                    submitBtn.textContent = 'Kirim Tandatangan Berita Acara';
                }
            } else {
                if (beritaAcaraContainer) {
                    beritaAcaraContainer.style.display = 'none';
                }
                if (attendanceStatusContainer) {
                    attendanceStatusContainer.style.display = 'block';
                }
                if (submitBtn) {
                    submitBtn.textContent = 'Kirim Daftar Hadir';
                }
            }
        }
        
        if (submitBtn) {
            const form = submitBtn.closest('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (submitBtn.disabled) {
                        e.preventDefault();
                        return;
                    }
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                    submitBtn.textContent = 'Memproses...';
                });
            }
        }
        
        initFormType();
    })();
</script>
@endpush
