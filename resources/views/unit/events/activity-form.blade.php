@extends('layouts.app')
@section('title', ($fase === 'attendance' ? 'Daftar Hadir' : (\App\Models\UnitEvent::$faseLabels[$fase] ?? ucfirst($fase))) . ' - ' . $event->judul)
@section('description', 'Formulir aktivitas peserta untuk event ' . $event->judul)

@section('content')
@php
    $phaseTitle = $fase === 'attendance' ? 'Daftar Hadir' : (\App\Models\UnitEvent::$faseLabels[$fase] ?? ucfirst($fase));
    $answers = old('answers', $submission?->jawaban ?? []);
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $attendanceDates = $attendanceDates ?? collect();
    $attendanceByDate = $attendanceByDate ?? collect();
    $selectedAttendanceDate = $selectedAttendanceDate ?? $attendanceDates->first();
    $attendanceRecord = $attendanceRecord ?? null;
    $signaturePreview = old('signature_data') ?: ($attendanceRecord?->signature_path ? \App\Support\MediaStorage::url($attendanceRecord->signature_path) : null);
@endphp

<section class="bg-gray-50 py-12 dark:bg-gray-950">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('unit.events.access', [$page->slug, $event->slug, $participant->access_token]) }}" class="text-sm font-semibold text-brand-600 hover:underline dark:text-brand-300">← Kembali ke akses peserta</a>
            <h1 class="mt-3 text-3xl font-black text-gray-900 dark:text-white">{{ $phaseTitle }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $participant->nama }} • {{ $event->judul }}</p>
            @if($fase === 'attendance' && $attendanceRecord?->signed_at)
                <p class="mt-2 text-xs text-gray-400">Pengisian terakhir: {{ $attendanceRecord->signed_at->translatedFormat('d M Y H:i') }}</p>
            @elseif($submission?->submitted_at)
                <p class="mt-2 text-xs text-gray-400">Pengisian terakhir: {{ $submission->submitted_at->translatedFormat('d M Y H:i') }}</p>
            @endif
            @if($submission?->nilai !== null)
                <p class="mt-1 text-sm font-semibold text-brand-600 dark:text-brand-300">Nilai terakhir: {{ number_format($submission->nilai, 2) }}</p>
            @endif
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
              action="{{ $fase === 'attendance' ? route('unit.events.attendance.submit', [$page->slug, $event->slug, $participant->access_token]) : route('unit.events.' . str_replace('_', '-', $fase) . '.submit', [$page->slug, $event->slug, $participant->access_token]) }}"
              class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @csrf

            @if($fase === 'attendance')
                <div class="space-y-6">
                    @if($attendanceDates->count() > 1)
                        <div>
                            <label for="attendance_date_select" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih Hari Kehadiran <span class="text-red-600">*</span></label>
                            <select id="attendance_date_select"
                                    class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-brand-500 dark:focus:ring-brand-900/40">
                                @foreach($attendanceDates as $attendanceDate)
                                    @php
                                        $dateKey = $attendanceDate->toDateString();
                                        $dayRecord = $attendanceByDate->get($dateKey);
                                        $isSelectedDate = old('attendance_date', $selectedAttendanceDate?->toDateString()) === $dateKey;
                                        $statusText = $dayRecord ? ' (Sudah ditandatangani)' : ' (Belum ditandatangani)';
                                    @endphp
                                    <option value="{{ route('unit.events.attendance.show', [$page->slug, $event->slug, $participant->access_token, 'tanggal' => $dateKey]) }}#signatureSection"
                                            {{ $isSelectedDate ? 'selected' : '' }}>
                                        Hari {{ $loop->iteration }}: {{ $attendanceDate->translatedFormat('l, d F Y') }}{{ $statusText }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <input type="hidden" name="attendance_date" value="{{ old('attendance_date', $selectedAttendanceDate?->toDateString()) }}">

                    <div class="rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800/60">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Tanggal Kehadiran</p>
                        <p class="mt-2 text-lg font-extrabold text-gray-900 dark:text-white">{{ $selectedAttendanceDate?->translatedFormat('l, d F Y') }}</p>
                        
                        @if($event->jenis_kegiatan === 'pbm' && $event->sub_jenis_kegiatan === 'praktik')
                            <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-400">Pilih Shift Praktik <span class="text-red-500">*</span></label>
                                        <select name="shift_name" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                            @foreach($event->shifts ?? [] as $shift)
                                                <option value="{{ $shift['name'] }}" @selected(old('shift_name', $attendanceRecord?->shift_name) === $shift['name'])>
                                                    {{ $shift['name'] }} ({{ $shift['start'] }} - {{ $shift['end'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-400">Tipe Presensi <span class="text-red-500">*</span></label>
                                        <select name="tipe_presensi" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                            <option value="datang" @selected(old('tipe_presensi') === 'datang')>Datang (Absen Masuk)</option>
                                            <option value="pulang" @selected(old('tipe_presensi') === 'pulang')>Pulang (Absen Keluar)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3 space-y-1 text-xs font-semibold text-gray-650 dark:text-gray-300">
                                    @if($attendanceRecord?->signed_at)
                                        <p class="text-green-600 dark:text-green-400">✓ Absen Masuk (Datang) tercatat: {{ $attendanceRecord->signed_at->translatedFormat('H:i') }} WIB</p>
                                    @else
                                        <p class="text-amber-600 dark:text-amber-400">⚠ Belum melakukan Absen Masuk (Datang)</p>
                                    @endif
                                    @if($attendanceRecord?->check_out_at)
                                        <p class="text-green-600 dark:text-green-400">✓ Absen Keluar (Pulang) tercatat: {{ $attendanceRecord->check_out_at->translatedFormat('H:i') }} WIB</p>
                                    @else
                                        <p class="text-amber-600 dark:text-amber-400">⚠ Belum melakukan Absen Keluar (Pulang)</p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tanda tangan yang Anda kirim di bawah akan disimpan khusus untuk tanggal ini.</p>
                        @endif
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Catatan Kehadiran</label>
                        <textarea name="attendance_notes" rows="5" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('attendance_notes', $attendanceRecord?->notes ?? $participant->attendance_notes) }}</textarea>
                        <p class="mt-2 text-xs text-gray-400">Gunakan catatan ini untuk nomor kursi, sesi yang diikuti, atau konfirmasi hadir luring/daring.</p>
                    </div>

                    <div id="signatureSection" class="space-y-6 mt-6">
                        @if($event->jenis_kegiatan === 'pbm' && $event->sub_jenis_kegiatan === 'praktik')
                            @php
                                $tipePresensi = old('tipe_presensi', 'datang');
                            @endphp
                            <div class="space-y-6">
                                <!-- Presensi Datang -->
                                <div id="container-tanda-tangan-datang" class="space-y-3" style="display: {{ $tipePresensi === 'datang' ? 'block' : 'none' }};">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <label class="block text-sm font-bold text-gray-755 dark:text-gray-300">Tanda Tangan Datang (Masuk)</label>
                                        <button type="button" id="signature-clear-datang" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-655 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:text-gray-250 dark:hover:border-red-500/40 dark:hover:bg-red-500/10 dark:hover:text-red-300">Hapus</button>
                                    </div>
                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-3 dark:border-gray-700 dark:bg-gray-950">
                                        <canvas id="signature-pad-datang" class="h-44 w-full rounded-xl bg-white" style="touch-action: none;"></canvas>
                                    </div>
                                    <input type="hidden" name="signature_data_datang" id="signature-data-datang" value="{{ old('signature_data_datang', '') }}">
                                    @error('signature_data_datang')
                                        <p class="text-xs font-semibold text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                                    @enderror
                                    
                                    @if($signaturePreview)
                                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                                            <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Tanda Tangan Datang Tersimpan</p>
                                            <img src="{{ $signaturePreview }}" alt="Tanda tangan Datang {{ $participant->nama }}" class="mt-2 max-h-24 rounded-xl bg-white p-2 mx-auto">
                                        </div>
                                    @endif
                                </div>

                                <!-- Presensi Pulang -->
                                <div id="container-tanda-tangan-pulang" class="space-y-3" style="display: {{ $tipePresensi === 'pulang' ? 'block' : 'none' }};">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <label class="block text-sm font-bold text-gray-755 dark:text-gray-300">Tanda Tangan Pulang (Keluar)</label>
                                        <button type="button" id="signature-clear-pulang" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-655 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:text-gray-255 dark:hover:border-red-500/40 dark:hover:bg-red-500/10 dark:hover:text-red-300">Hapus</button>
                                    </div>
                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-3 dark:border-gray-700 dark:bg-gray-950">
                                        <canvas id="signature-pad-pulang" class="h-44 w-full rounded-xl bg-white" style="touch-action: none;"></canvas>
                                    </div>
                                    <input type="hidden" name="signature_data_pulang" id="signature-data-pulang" value="{{ old('signature_data_pulang', '') }}">
                                    @error('signature_data_pulang')
                                        <p class="text-xs font-semibold text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                                    @enderror
                                    
                                    @if($attendanceRecord?->check_out_signature_path)
                                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
                                            <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Tanda Tangan Pulang Tersimpan</p>
                                            <img src="{{ \App\Support\MediaStorage::url($attendanceRecord->check_out_signature_path) }}" alt="Tanda tangan Pulang {{ $participant->nama }}" class="mt-2 max-h-24 rounded-xl bg-white p-2 mx-auto">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div>
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <label class="block text-sm font-bold text-gray-750 dark:text-gray-300">Tanda Tangan</label>
                                    <button type="button" id="signature-clear" class="rounded-xl border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 transition-colors hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-700 dark:text-gray-250 dark:hover:border-red-500/40 dark:hover:bg-red-500/10 dark:hover:text-red-300">Hapus tanda tangan</button>
                                </div>

                                <div class="mt-3 rounded-2xl border border-dashed border-gray-300 bg-white p-3 dark:border-gray-700 dark:bg-gray-950">
                                    <canvas id="signature-pad" class="h-56 w-full rounded-xl bg-white" style="touch-action: none;"></canvas>
                                </div>
                                <input type="hidden" name="signature_data" id="signature-data" value="{{ old('signature_data', '') }}">
                                @error('signature_data')
                                    <p class="text-xs font-semibold text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-xs text-gray-400">Gunakan mouse, touchpad, stylus, atau jari. Jika tanda tangan lama ingin dipakai kembali, biarkan area ini kosong.</p>

                                @if($signaturePreview)
                                    <div class="mt-4 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-750 dark:bg-gray-800/60">
                                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ old('signature_data') ? 'Preview tanda tangan baru' : 'Tanda tangan yang tersimpan' }}</p>
                                        <img src="{{ $signaturePreview }}" alt="Tanda tangan {{ $participant->nama }}" class="mt-3 max-h-36 rounded-xl bg-white p-2">
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($questions as $question)
                        <div class="rounded-2xl bg-gray-50 px-4 py-4 dark:bg-gray-800/60">
                            <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                                <span>Pertanyaan {{ $loop->iteration }}</span>
                                @if($question->wajib)
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-red-600 dark:bg-red-900/20 dark:text-red-300">Wajib</span>
                                @endif
                            </div>
                            <h2 class="mt-2 text-base font-extrabold text-gray-900 dark:text-white">{{ $question->pertanyaan }}</h2>

                            <div class="mt-4">
                                @if($question->tipe_pertanyaan === 'multiple_choice')
                                    <div class="space-y-2">
                                        @foreach($question->opsi ?? [] as $option)
                                            <label class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" @checked(($answers[$question->id] ?? null) === $option) class="mt-0.5">
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($question->tipe_pertanyaan === 'rating')
                                    <div class="grid grid-cols-5 gap-2">
                                        @for($score = 1; $score <= 5; $score++)
                                            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-4 text-sm font-bold text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $score }}" @checked((string) ($answers[$question->id] ?? '') === (string) $score)>
                                                <span>{{ $score }}</span>
                                            </label>
                                        @endfor
                                    </div>
                                @else
                                    <textarea name="answers[{{ $question->id }}]" rows="5" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900">{{ $answers[$question->id] ?? '' }}</textarea>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('unit.events.access', [$page->slug, $event->slug, $participant->access_token]) }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-200">Batal</a>
                <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                    Simpan {{ $phaseTitle }}
                </button>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    if (submitBtn.disabled) {
                        e.preventDefault();
                        return;
                    }
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                    submitBtn.textContent = 'Memproses...';
                }
            });
        }
    });
</script>
@endpush
@endsection

@if($fase === 'attendance')
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const initPad = (canvasId, hiddenInputId, clearBtnId) => {
                    const canvas = document.getElementById(canvasId);
                    const hiddenInput = document.getElementById(hiddenInputId);
                    const clearButton = document.getElementById(clearBtnId);

                    if (!canvas || !hiddenInput) {
                        return null;
                    }

                    const context = canvas.getContext('2d', { willReadFrequently: true });
                    let isDrawing = false;
                    let hasStroke = (hiddenInput.value || '').trim().startsWith('data:image/');
                    let lastPoint = { x: 0, y: 0 };
                    let cachedRect = null;
                    let currentWidth = 0;

                    const resetContext = () => {
                        context.lineCap = 'round';
                        context.lineJoin = 'round';
                        context.lineWidth = 2.4;
                        context.strokeStyle = '#0f172a';
                    };

                    const paintBackground = (width, height) => {
                        context.save();
                        context.setTransform(1, 0, 0, 1, 0, 0);
                        context.fillStyle = '#ffffff';
                        context.fillRect(0, 0, canvas.width, canvas.height);
                        context.restore();
                    };

                    const drawDataUrl = (dataUrl) => {
                        const image = new Image();
                        image.onload = () => {
                            const rect = canvas.getBoundingClientRect();
                            if (rect.width > 0 && rect.height > 0) {
                                paintBackground(rect.width, rect.height);
                                context.drawImage(image, 0, 0, rect.width, rect.height);
                                resetContext();
                            }
                        };
                        image.src = dataUrl;
                    };

                    const resizeCanvas = (force = false) => {
                        const rect = canvas.getBoundingClientRect();
                        if (rect.width <= 0) {
                            return;
                        }

                        const displayWidth = Math.floor(rect.width);
                        const displayHeight = canvas.offsetHeight > 0 ? canvas.offsetHeight : (canvasId.includes('datang') || canvasId.includes('pulang') ? 176 : 224);

                        if (!force && Math.abs(currentWidth - displayWidth) < 2 && canvas.width > 0) {
                            cachedRect = canvas.getBoundingClientRect();
                            return;
                        }

                        currentWidth = displayWidth;
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        const currentData = hiddenInput.value.trim();

                        canvas.width = Math.floor(displayWidth * ratio);
                        canvas.height = Math.floor(displayHeight * ratio);
                        canvas.style.width = displayWidth + 'px';
                        canvas.style.height = displayHeight + 'px';

                        context.setTransform(1, 0, 0, 1, 0, 0);
                        context.scale(ratio, ratio);
                        resetContext();
                        paintBackground(displayWidth, displayHeight);

                        if (currentData.startsWith('data:image/')) {
                            hasStroke = true;
                            drawDataUrl(currentData);
                        }

                        cachedRect = canvas.getBoundingClientRect();
                    };

                    const pointFromEvent = (event) => {
                        if (!cachedRect) {
                            cachedRect = canvas.getBoundingClientRect();
                        }
                        let clientX = 0;
                        let clientY = 0;

                        if (event.touches && event.touches.length > 0) {
                            clientX = event.touches[0].clientX;
                            clientY = event.touches[0].clientY;
                        } else if (event.changedTouches && event.changedTouches.length > 0) {
                            clientX = event.changedTouches[0].clientX;
                            clientY = event.changedTouches[0].clientY;
                        } else if (typeof event.clientX === 'number') {
                            clientX = event.clientX;
                            clientY = event.clientY;
                        }

                        return {
                            x: clientX - cachedRect.left,
                            y: clientY - cachedRect.top,
                        };
                    };

                    const persistSignature = () => {
                        hiddenInput.value = hasStroke ? canvas.toDataURL('image/png') : '';
                    };

                    const startDrawing = (event) => {
                        if (event.cancelable) {
                            event.preventDefault();
                        }
                        cachedRect = canvas.getBoundingClientRect();
                        if (canvas.width === 0 || canvas.height === 0) {
                            resizeCanvas(true);
                        }
                        isDrawing = true;
                        hasStroke = true;
                        lastPoint = pointFromEvent(event);

                        resetContext();
                        context.beginPath();
                        context.moveTo(lastPoint.x, lastPoint.y);
                        context.lineTo(lastPoint.x + 0.5, lastPoint.y + 0.5);
                        context.stroke();

                        if (event.pointerId !== undefined && canvas.setPointerCapture) {
                            try { canvas.setPointerCapture(event.pointerId); } catch(err) {}
                        }
                    };

                    const draw = (event) => {
                        if (!isDrawing) {
                            return;
                        }
                        if (event.cancelable) {
                            event.preventDefault();
                        }
                        const point = pointFromEvent(event);
                        context.beginPath();
                        context.moveTo(lastPoint.x, lastPoint.y);
                        context.lineTo(point.x, point.y);
                        context.stroke();
                        lastPoint = point;
                    };

                    const stopDrawing = (event) => {
                        if (!isDrawing) {
                            return;
                        }
                        if (event && event.cancelable) {
                            event.preventDefault();
                        }
                        isDrawing = false;
                        lastPoint = { x: 0, y: 0 };
                        persistSignature();

                        if (event && event.pointerId !== undefined && canvas.releasePointerCapture) {
                            try { canvas.releasePointerCapture(event.pointerId); } catch(err) {}
                        }
                    };

                    clearButton?.addEventListener('click', () => {
                        hasStroke = false;
                        hiddenInput.value = '';
                        paintBackground(canvas.width, canvas.height);
                        resetContext();
                    });

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

                    window.addEventListener('resize', () => resizeCanvas(false));
                    window.addEventListener('scroll', () => {
                        cachedRect = canvas.getBoundingClientRect();
                    }, { passive: true });

                    const form = canvas.closest('form');
                    if (form) {
                        form.addEventListener('submit', () => {
                            if (hasStroke) {
                                persistSignature();
                            }
                        });
                    }

                    setTimeout(() => resizeCanvas(true), 60);
                    setTimeout(() => resizeCanvas(true), 300);

                    return { resize: () => resizeCanvas(true), persist: persistSignature };
                };

                let padDatang = null;
                let padPulang = null;

                if (document.getElementById('signature-pad-datang')) {
                    padDatang = initPad('signature-pad-datang', 'signature-data-datang', 'signature-clear-datang');
                    padPulang = initPad('signature-pad-pulang', 'signature-data-pulang', 'signature-clear-pulang');
                } else {
                    initPad('signature-pad', 'signature-data', 'signature-clear');
                }

                // Dynamic signature pad switch based on Tipe Presensi
                const tipePresensiSelect = document.querySelector('select[name="tipe_presensi"]');
                const containerDatang = document.getElementById('container-tanda-tangan-datang');
                const containerPulang = document.getElementById('container-tanda-tangan-pulang');

                function toggleSignaturePads() {
                    if (!tipePresensiSelect) return;
                    const val = tipePresensiSelect.value;
                    if (val === 'datang') {
                        if (containerDatang) containerDatang.style.display = 'block';
                        if (containerPulang) containerPulang.style.display = 'none';
                        requestAnimationFrame(() => {
                            if (padDatang) padDatang.resize();
                        });
                    } else if (val === 'pulang') {
                        if (containerDatang) containerDatang.style.display = 'none';
                        if (containerPulang) containerPulang.style.display = 'block';
                        requestAnimationFrame(() => {
                            if (padPulang) padPulang.resize();
                        });
                    }
                }

                if (tipePresensiSelect) {
                    tipePresensiSelect.addEventListener('change', toggleSignaturePads);
                    toggleSignaturePads(); // Initial check
                }

                // Auto reload and scroll to signature on date select change
                const dateSelect = document.getElementById('attendance_date_select');
                if (dateSelect) {
                    dateSelect.addEventListener('change', function() {
                        if (dateSelect.value) {
                            window.location.href = dateSelect.value;
                        }
                    });
                }
            });
        </script>
    @endpush
@endif