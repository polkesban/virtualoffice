@extends('admin.layouts.admin')
@section('title', 'Dokumentasi, Notulen & Laporan Event')
@section('page-title', 'Dokumentasi, Notulen & Laporan Event')
@section('page-subtitle', 'Voice to text browser, transkripsi audio AI Bahasa Indonesia, foto kegiatan, dan draft laporan rapat/kegiatan')

@section('content')
<div id="unit-event-documentation-page" class="p-6 max-w-7xl mx-auto" x-data="eventVoiceToText({ eventId: {{ $unitEvent->id }}, autosaveUrl: '{{ route('admin.unit-events.documentation.autosave', [$unitPage, $unitEvent]) }}', csrfToken: '{{ csrf_token() }}' })">
    @include('admin.unit-events._nav', ['unitPage' => $unitPage, 'unitEvent' => $unitEvent])

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-300">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/50 dark:bg-green-900/20 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Dokumentasi untuk {{ $unitEvent->judul }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gunakan voice to text untuk menangkap jalannya rapat, lalu minta AI menyusun resume dan draft laporan yang siap diedit serta dicetak.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('unit.events.berita-acara', [$unitPage->slug, $unitEvent->slug]) }}" target="_blank" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Cetak Berita Acara</a>
            <a href="{{ route('admin.unit-events.documentation.report-print', [$unitPage, $unitEvent]) }}" target="_blank" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Cetak Laporan</a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Peserta Terdaftar</p>
            <p class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $participantStats['total'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $participantStats['participant'] }} peserta, {{ $participantStats['speaker'] }} narasumber, {{ $participantStats['committee'] }} panitia</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Foto Kegiatan</p>
            <p class="mt-2 text-2xl font-extrabold text-gray-900 dark:text-white">{{ $photos->count() }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Foto cover dipakai sebagai dokumentasi utama pada laporan.</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Resume AI</p>
            <p class="mt-2 text-sm font-extrabold text-gray-900 dark:text-white">{{ $documentation->ai_summary_generated_at?->translatedFormat('d M Y H:i') ?: 'Belum dibuat' }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Model: {{ $documentation->ai_model_used ?: 'Belum ada' }}</p>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white px-4 py-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Draft Laporan</p>
            <p class="mt-2 text-sm font-extrabold text-gray-900 dark:text-white">{{ $documentation->report_generated_at?->translatedFormat('d M Y H:i') ?: 'Belum dibuat' }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Cetak laporan setelah draft diperiksa dan disempurnakan.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.unit-events.documentation.update', [$unitPage, $unitEvent]) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.1fr,0.9fr]">
            <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h4 class="text-lg font-extrabold text-gray-900 dark:text-white">Voice to Text & Transkrip Mentah</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Klik mulai untuk mendikte jalannya rapat langsung ke browser. Hasilnya masuk ke kotak transkrip dan bisa diedit manual.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 dark:border-gray-700 dark:bg-gray-800">
                            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400">Bahasa Dikte:</label>
                            <select x-model="selectedLanguage" @change="changeLanguage()" class="rounded-lg border-0 bg-transparent text-xs font-bold text-gray-800 focus:ring-0 dark:text-gray-200 cursor-pointer">
                                <option value="id-ID">🇮🇩 Indonesia (id-ID)</option>
                                <option value="en-US">🇺🇸 English US (en-US)</option>
                                <option value="en-GB">🇬🇧 English UK (en-GB)</option>
                                <option value="su-ID">🇮🇩 Sunda (su-ID)</option>
                                <option value="jv-ID">🇮🇩 Jawa (jv-ID)</option>
                                <option value="ms-MY">🇲🇾 Melayu (ms-MY)</option>
                                <option value="ar-SA">🇸🇦 Arab (ar-SA)</option>
                                <option value="ja-JP">🇯🇵 Jepang (ja-JP)</option>
                                <option value="zh-CN">🇨🇳 Mandarin (zh-CN)</option>
                            </select>
                        </div>
                        <button type="button" @click="startListening()" :disabled="!supported || listening" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-brand-500 disabled:cursor-not-allowed disabled:opacity-50">Mulai Voice to Text</button>
                        <button type="button" @click="stopListening()" :disabled="!listening" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:border-red-200 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">Hentikan</button>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-900/40 dark:bg-sky-900/20 dark:text-sky-200">
                    <p class="font-semibold" x-text="supported ? (statusMessage || 'Voice to text siap digunakan. Gunakan Chrome atau Edge agar hasilnya stabil.') : 'Browser ini belum mendukung voice to text langsung. Gunakan Google Chrome atau Microsoft Edge versi terbaru.'"></p>
                    <p class="mt-1 text-xs opacity-80" x-text="'Bahasa perekaman dikte browser: ' + getLanguageLabel(selectedLanguage) + ' (' + selectedLanguage + '). Anda dapat memilih/mengganti bahasa dikte kapan saja.'"></p>
                </div>

                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1.2fr,0.8fr,0.8fr,auto] lg:items-end">
                        <div>
                            <label class="mb-1 block text-sm font-bold text-emerald-900 dark:text-emerald-100">Upload Rekaman Audio untuk Transkripsi AI</label>
                            <input type="file" name="audio_transcription_file" accept="audio/*,.m4a,.mp3,.wav,.ogg,.oga,.webm,.mp4" class="block w-full rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm text-gray-700 dark:border-emerald-800 dark:bg-gray-900 dark:text-gray-100">
                            <p class="mt-1 text-xs opacity-80">Format: mp3, wav, m4a, ogg, webm, mp4 (maks 12 MB).</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-emerald-900 dark:text-emerald-100">Bahasa Audio AI</label>
                            <select name="audio_language" class="w-full rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm text-gray-700 dark:border-emerald-800 dark:bg-gray-900 dark:text-gray-100">
                                <option value="id" @selected(old('audio_language', 'id') === 'id')>🇮🇩 Indonesia (id)</option>
                                <option value="en" @selected(old('audio_language') === 'en')>🇺🇸 English (en)</option>
                                <option value="su" @selected(old('audio_language') === 'su')>🇮🇩 Sunda (su)</option>
                                <option value="jv" @selected(old('audio_language') === 'jv')>🇮🇩 Jawa (jv)</option>
                                <option value="ms" @selected(old('audio_language') === 'ms')>🇲🇾 Melayu (ms)</option>
                                <option value="ar" @selected(old('audio_language') === 'ar')>🇸🇦 Arab (ar)</option>
                                <option value="ja" @selected(old('audio_language') === 'ja')>🇯🇵 Jepang (ja)</option>
                                <option value="zh" @selected(old('audio_language') === 'zh')>🇨🇳 Mandarin (zh)</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-emerald-900 dark:text-emerald-100">Mode Hasil Transkripsi</label>
                            <select name="transcription_mode" class="w-full rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm text-gray-700 dark:border-emerald-800 dark:bg-gray-900 dark:text-gray-100">
                                <option value="append" @selected(old('transcription_mode', 'append') === 'append')>Tambahkan ke transkrip yang ada</option>
                                <option value="replace" @selected(old('transcription_mode') === 'replace')>Ganti seluruh transkrip mentah</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" formaction="{{ route('admin.unit-events.documentation.transcribe-audio', [$unitPage, $unitEvent]) }}" class="w-full rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-emerald-500 lg:w-auto">Upload & Transkrip AI</button>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300">Transkrip Mentah / Hasil Dikte</label>
                        <div class="flex items-center gap-2 text-xs">
                            <span x-show="autosaveBadge" x-cloak
                                  :class="{
                                      'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200': autosaveState === 'saved',
                                      'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200': autosaveState === 'saving',
                                      'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300 border-sky-200': autosaveState === 'local',
                                      'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border-red-200': autosaveState === 'error'
                                  }"
                                  class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 font-medium transition-all">
                                <span x-show="autosaveState === 'saving'" class="inline-block h-2 w-2 animate-ping rounded-full bg-amber-500"></span>
                                <span x-show="autosaveState === 'saved'" class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                <span x-show="autosaveState === 'local'" class="inline-block h-2 w-2 rounded-full bg-sky-500"></span>
                                <span x-show="autosaveState === 'error'" class="inline-block h-2 w-2 rounded-full bg-red-500"></span>
                                <span x-text="autosaveBadgeText"></span>
                            </span>
                            <button type="button" @click="triggerManualAutosave()" class="font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 hover:underline" x-show="transcriptChanged" x-cloak>Simpan Draft Now</button>
                        </div>
                    </div>

                    <div x-show="draftRestoredNotice" x-cloak class="mb-2.5 flex items-center justify-between rounded-xl border border-amber-300 bg-amber-50 px-3.5 py-2 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 9 0 11-18 0 9 9 9 0 0118 0z"></path></svg>
                            <span><strong>Transkrip Terpulihkan:</strong> Ditemukan transkrip otomatis tersimpan di browser dari sesi sebelumnya.</span>
                        </div>
                        <button type="button" @click="dismissDraftNotice()" class="font-bold underline hover:no-underline ml-2">Tutup</button>
                    </div>

                    <textarea name="transcript_text" x-ref="transcriptField" @input="onTranscriptInput()" rows="16" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-6 dark:border-gray-700 dark:bg-gray-800" placeholder="Hasil voice to text, poin rapat, percakapan inti, atau catatan mentah...">{{ old('transcript_text', $documentation->transcript_text) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Kolom ini menerima gabungan dari voice to text browser dan hasil upload rekaman audio. Autosave aktif menyimpan ke browser dan server secara berkala.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h4 class="text-lg font-extrabold text-gray-900 dark:text-white">Generator AI & Arahan Laporan</h4>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">AI memakai notulen/transkrip yang tersimpan pada form ini. Jika Anda mengubah isi editor, jalankan tombol AI setelah perubahan selesai.</p>

                <div class="mt-4 space-y-4">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-200">
                        <p class="font-bold">Resume Hasil Rapat</p>
                        <p class="mt-1">AI akan merangkum pokok bahasan, keputusan, dan tindak lanjut dari transkrip maupun notulen.</p>
                        <button type="submit" formaction="{{ route('admin.unit-events.documentation.generate-summary', [$unitPage, $unitEvent]) }}" class="mt-3 rounded-xl bg-amber-500 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-amber-400">Generate Resume AI</button>
                    </div>

                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-200">
                        <p class="font-bold">Draft Laporan Kegiatan</p>
                        <p class="mt-1">AI akan menyusun draft laporan formal berdasarkan notulen, resume, metadata event, dan foto kegiatan yang sudah diunggah.</p>
                        <button type="submit" formaction="{{ route('admin.unit-events.documentation.generate-report', [$unitPage, $unitEvent]) }}" class="mt-3 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-emerald-500">Generate Draft Laporan</button>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Arahan Tambahan untuk AI Laporan</label>
                    <textarea name="report_notes" rows="9" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-6 dark:border-gray-700 dark:bg-gray-800" placeholder="Contoh: tekankan keputusan rapat, tampilkan tindak lanjut per unit, gunakan gaya formal surat dinas...">{{ old('report_notes', $documentation->report_notes) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Arahan ini tidak tampil di laporan cetak, tetapi dipakai AI saat menyusun draft.</p>
                </div>
            </section>
        </div>

        <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h4 class="text-lg font-extrabold text-gray-900 dark:text-white">Berita Acara</h4>
            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Judul Berita Acara</label>
                    <input type="text" name="berita_acara_judul" value="{{ old('berita_acara_judul', $unitEvent->berita_acara_judul) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" placeholder="Judul berita acara (kosongkan untuk menggunakan judul event)">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Ringkasan Berita Acara</label>
                    <textarea id="berita_acara_ringkasan" name="berita_acara_ringkasan" rows="6" data-rich-editor data-rich-editor-profile="minimal" data-rich-editor-height="200" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ old('berita_acara_ringkasan', $unitEvent->berita_acara_ringkasan) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ringkasan singkat untuk berita acara.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Catatan Berita Acara</label>
                    <textarea id="berita_acara_catatan" name="berita_acara_catatan" rows="4" data-rich-editor data-rich-editor-profile="minimal" data-rich-editor-height="150" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ old('berita_acara_catatan', $unitEvent->berita_acara_catatan) }}</textarea>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Isi catatan tambahan yang akan ditampilkan di berita acara.</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h4 class="text-lg font-extrabold text-gray-900 dark:text-white">Notulen Final</h4>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Isi hasil notulen yang sudah dirapikan. Bagian ini menjadi bahan utama resume AI jika tersedia.</p>
                </div>
            </div>
            <div class="mt-4">
                <textarea id="minutes_html" name="minutes_html" rows="14" data-rich-editor data-rich-editor-profile="full" data-rich-editor-height="340" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('minutes_html', $documentation->minutes_html) }}</textarea>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900" x-data="{ summaryMode: 'visual' }">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h4 class="text-lg font-extrabold text-gray-900 dark:text-white">Resume AI Hasil Rapat</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tampilan visual resume AI. Beralih ke mode edit untuk mengubah konten.</p>
                    </div>
                    <div class="inline-flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800 shrink-0">
                        <button type="button" @click="summaryMode = 'visual'" :class="summaryMode === 'visual' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="rounded-lg px-3 py-1 text-xs font-bold transition-all">Preview Visual</button>
                        <button type="button" @click="summaryMode = 'code'; $nextTick(() => window.PolkesbanRichText?.init())" :class="summaryMode === 'code' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="rounded-lg px-3 py-1 text-xs font-bold transition-all">Edit Editor / Kode</button>
                    </div>
                </div>

                <div x-show="summaryMode === 'visual'" class="mt-4 min-h-[300px] rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                    @if(trim($documentation->ai_summary_html))
                        <div class="prose max-w-none dark:prose-invert prose-headings:font-bold prose-h3:text-lg prose-h4:text-base prose-p:leading-relaxed prose-li:my-1 text-gray-800 dark:text-gray-200">
                            {!! $documentation->ai_summary_html !!}
                        </div>
                    @else
                        <p class="text-sm italic text-gray-400">Belum ada resume AI. Klik tombol "Generate Resume AI" di atas untuk membuat resume otomatis.</p>
                    @endif
                </div>

                <div x-show="summaryMode === 'code'" x-cloak class="mt-4">
                    <textarea id="ai_summary_html" name="ai_summary_html" rows="16" data-rich-editor data-rich-editor-profile="full" data-rich-editor-height="360" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('ai_summary_html', $documentation->ai_summary_html) }}</textarea>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900" x-data="{ reportMode: 'visual' }">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h4 class="text-lg font-extrabold text-gray-900 dark:text-white">Draft Laporan</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tampilan visual draft laporan. Beralih ke mode edit untuk merevisi sebelum cetak.</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="inline-flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                            <button type="button" @click="reportMode = 'visual'" :class="reportMode === 'visual' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="rounded-lg px-3 py-1 text-xs font-bold transition-all">Preview Visual</button>
                            <button type="button" @click="reportMode = 'code'; $nextTick(() => window.PolkesbanRichText?.init())" :class="reportMode === 'code' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'" class="rounded-lg px-3 py-1 text-xs font-bold transition-all">Edit Editor / Kode</button>
                        </div>
                        <a href="{{ route('admin.unit-events.documentation.report-print', [$unitPage, $unitEvent]) }}" target="_blank" class="rounded-xl border border-gray-200 bg-white px-3.5 py-1.5 text-xs font-bold text-gray-700 transition-colors hover:border-brand-200 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-brand-500/40 dark:hover:text-brand-300">Cetak</a>
                    </div>
                </div>

                <div x-show="reportMode === 'visual'" class="mt-4 min-h-[340px] rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                    @if(trim($documentation->report_html))
                        <div class="prose max-w-none dark:prose-invert prose-headings:font-bold prose-h3:text-lg prose-h4:text-base prose-p:leading-relaxed prose-li:my-1 text-gray-800 dark:text-gray-200">
                            {!! $documentation->report_html !!}
                        </div>
                    @else
                        <p class="text-sm italic text-gray-400">Belum ada draft laporan AI. Klik tombol "Generate Draft Laporan" di atas untuk membuat draft otomatis.</p>
                    @endif
                </div>

                <div x-show="reportMode === 'code'" x-cloak class="mt-4">
                    <textarea id="report_html" name="report_html" rows="18" data-rich-editor data-rich-editor-profile="full" data-rich-editor-height="420" class="w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('report_html', $documentation->report_html) }}</textarea>
                </div>
            </section>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500">Simpan Dokumentasi</button>
        </div>
    </form>

    <section class="mt-6 rounded-2xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h4 class="text-lg font-extrabold text-gray-900 dark:text-white">Foto Kegiatan</h4>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Unggah dokumentasi rapat atau kegiatan. Foto-foto ini akan tampil pada halaman laporan cetak.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.unit-events.documentation.photos.store', [$unitPage, $unitEvent]) }}" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 gap-4 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60 lg:grid-cols-[1.2fr,1fr,1fr,auto]">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Upload Foto Kegiatan</label>
                <input type="file" name="photos[]" accept="image/*" multiple required class="block w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="mt-1 text-xs text-gray-400">Bisa unggah beberapa foto sekaligus. Maksimal 6 MB per foto.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Caption Bersama</label>
                <input type="text" name="caption" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900" placeholder="Opsional, akan dipakai sebagai caption awal semua foto baru">
            </div>
            <div>
                <label class="mb-1 block text-sm font-bold text-gray-700 dark:text-gray-300">Tanggal Pengambilan</label>
                <input type="datetime-local" name="taken_at" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-500 lg:w-auto">Upload Foto</button>
            </div>
        </form>

        @if($photos->isEmpty())
            <div class="mt-4 rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center text-sm text-gray-400 dark:border-gray-700">
                Belum ada foto kegiatan yang diunggah untuk event ini.
            </div>
        @else
            <div class="mt-5 grid grid-cols-1 gap-4 xl:grid-cols-2">
                @foreach($photos as $photo)
                    <article class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="grid grid-cols-1 md:grid-cols-[220px,1fr]">
                            <div class="relative h-52 bg-gray-100 dark:bg-gray-800">
                                <img src="{{ \App\Support\MediaStorage::url($photo->image_path) }}" alt="{{ $photo->title ?: 'Foto kegiatan' }}" class="h-full w-full object-cover">
                                @if($photo->is_cover)
                                    <span class="absolute left-3 top-3 rounded-full bg-amber-400 px-3 py-1 text-[11px] font-bold text-slate-900">Foto Cover</span>
                                @endif
                            </div>
                            <div class="p-4">
                                <form method="POST" action="{{ route('admin.unit-events.documentation.photos.update', [$unitPage, $unitEvent, $photo]) }}" class="space-y-3">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-400">Judul Foto</label>
                                            <input type="text" name="title" value="{{ old('title', $photo->title) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-400">Urutan</label>
                                            <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $photo->sort_order) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-400">Tanggal Foto</label>
                                            <input type="datetime-local" name="taken_at" value="{{ old('taken_at', $photo->taken_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-gray-400">Caption</label>
                                        <textarea name="caption" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">{{ old('caption', $photo->caption) }}</textarea>
                                    </div>
                                    <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" name="is_cover" value="1" @checked($photo->is_cover) class="rounded">
                                        Jadikan foto cover dokumentasi
                                    </label>
                                    <div class="flex flex-wrap justify-between gap-2">
                                        <button type="submit" class="rounded-xl bg-gray-900 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">Simpan Keterangan Foto</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('admin.unit-events.documentation.photos.destroy', [$unitPage, $unitEvent, $photo]) }}" onsubmit="return confirm('Hapus foto kegiatan ini?')" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl bg-red-50 px-4 py-2 text-sm font-bold text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300">Hapus Foto</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const createEventVoiceToText = (config = {}) => ({
        supported: false,
        listening: false,
        statusMessage: '',
        recognition: null,
        manualStop: false,

        // Language settings
        selectedLanguage: localStorage.getItem('polkesban_vtt_lang') || 'id-ID',

        // Autosave configuration & state
        eventId: config.eventId || null,
        autosaveUrl: config.autosaveUrl || '',
        csrfToken: config.csrfToken || '',
        autosaveState: 'idle',
        autosaveBadgeText: '',
        autosaveBadge: false,
        transcriptChanged: false,
        draftRestoredNotice: false,
        saveDebounceTimer: null,
        periodicSaveInterval: null,
        lastSavedContent: '',

        init() {
            const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.supported = !!Recognition;

            this.$nextTick(() => {
                this.checkAndRestoreDraft();
                this.setupFormSubmitHandler();
            });

            window.addEventListener('beforeunload', (e) => {
                if (this.listening || this.transcriptChanged) {
                    const message = 'Voice to text sedang berlangsung atau ada draft transkrip belum tersimpan ke server. Yakin ingin keluar?';
                    e.preventDefault();
                    e.returnValue = message;
                    return message;
                }
            });

            if (!this.supported) {
                return;
            }

            this.recognition = new Recognition();
            this.recognition.lang = this.selectedLanguage;
            this.recognition.continuous = true;
            this.recognition.interimResults = true;

            this.recognition.onstart = () => {
                this.listening = true;
                this.statusMessage = 'Mendengarkan suara rapat...';
                this.startPeriodicAutosave();
            };

            this.recognition.onend = () => {
                this.listening = false;
                this.stopPeriodicAutosave();
                if (!this.manualStop) {
                    this.statusMessage = 'Melanjutkan mendengarkan suara rapat...';
                    setTimeout(() => {
                        if (!this.manualStop && !this.listening) {
                            try {
                                this.recognition.start();
                            } catch {
                                this.statusMessage = 'Perekaman berhenti. Klik "Mulai Voice to Text" untuk melanjutkan.';
                            }
                        }
                    }, 300);
                }
            };

            this.recognition.onerror = (event) => {
                this.listening = false;
                this.stopPeriodicAutosave();
                this.statusMessage = 'Voice to text gagal: ' + (event.error || 'unknown_error');
            };

            this.recognition.onresult = (event) => {
                let finalTranscript = '';
                let interimTranscript = '';

                for (let index = event.resultIndex; index < event.results.length; index += 1) {
                    const result = event.results[index];
                    const transcript = result[0]?.transcript || '';

                    if (result.isFinal) {
                        finalTranscript += transcript.trim() + ' ';
                    } else {
                        interimTranscript += transcript;
                    }
                }

                if (finalTranscript.trim() !== '') {
                    this.appendTranscript(finalTranscript.trim());
                }

                this.statusMessage = interimTranscript.trim() !== ''
                    ? 'Terdeteksi: ' + interimTranscript.trim()
                    : 'Mendengarkan suara rapat...';
            };
        },

        startListening() {
            if (!this.supported || !this.recognition || this.listening) {
                return;
            }

            this.manualStop = false;
            this.recognition.start();
        },

        stopListening() {
            if (!this.recognition || !this.listening) {
                return;
            }

            this.manualStop = true;
            this.recognition.stop();
            this.statusMessage = 'Voice to text dihentikan.';
            this.stopPeriodicAutosave();
            this.triggerServerAutosave();
        },

        changeLanguage() {
            try {
                localStorage.setItem('polkesban_vtt_lang', this.selectedLanguage);
            } catch (e) {}

            if (this.recognition) {
                this.recognition.lang = this.selectedLanguage;
            }

            if (this.listening && this.recognition) {
                this.statusMessage = 'Mengubah bahasa dikte ke ' + this.getLanguageLabel(this.selectedLanguage) + '...';
                this.manualStop = true;
                this.recognition.stop();
                setTimeout(() => {
                    this.manualStop = false;
                    try {
                        this.recognition.lang = this.selectedLanguage;
                        this.recognition.start();
                    } catch (e) {}
                }, 350);
            }
        },

        getLanguageLabel(code) {
            const labels = {
                'id-ID': 'Bahasa Indonesia',
                'en-US': 'English (US)',
                'en-GB': 'English (UK)',
                'su-ID': 'Bahasa Sunda',
                'jv-ID': 'Bahasa Jawa',
                'ms-MY': 'Bahasa Melayu',
                'ar-SA': 'Bahasa Arab',
                'ja-JP': 'Bahasa Jepang',
                'zh-CN': 'Bahasa Mandarin'
            };
            return labels[code] || code;
        },

        appendTranscript(text) {
            const field = this.$refs.transcriptField;
            if (!field) {
                return;
            }

            const prefix = field.value.trim() === '' ? '' : '\n';
            field.value += prefix + text;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        },

        getLocalStorageKey() {
            return 'polkesban_event_doc_draft_' + (this.eventId || 'default');
        },

        checkAndRestoreDraft() {
            const field = this.$refs.transcriptField;
            if (!field) return;

            this.lastSavedContent = field.value;
            const localKey = this.getLocalStorageKey();

            try {
                const savedDraftJson = localStorage.getItem(localKey);
                if (savedDraftJson) {
                    const draft = JSON.parse(savedDraftJson);
                    const localText = (draft && draft.text) ? draft.text.trim() : '';
                    const currentText = field.value.trim();

                    if (localText !== '' && localText !== currentText && localText.length > currentText.length) {
                        field.value = draft.text;
                        this.draftRestoredNotice = true;
                        this.autosaveState = 'local';
                        this.autosaveBadge = true;
                        const savedTime = draft.timestamp ? new Date(draft.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                        this.autosaveBadgeText = 'Draft lokal dipulihkan (' + (savedTime || 'browser') + ')';
                        this.transcriptChanged = true;
                    }
                }
            } catch (e) {
                // Ignore storage error
            }
        },

        dismissDraftNotice() {
            this.draftRestoredNotice = false;
        },

        saveToLocalStorage(content) {
            const localKey = this.getLocalStorageKey();
            try {
                localStorage.setItem(localKey, JSON.stringify({
                    text: content,
                    timestamp: new Date().toISOString()
                }));
                if (this.autosaveState !== 'saving' && this.autosaveState !== 'saved') {
                    this.autosaveState = 'local';
                    this.autosaveBadge = true;
                    const nowStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    this.autosaveBadgeText = 'Draft tersimpan di browser (' + nowStr + ')';
                }
            } catch (e) {
                // Ignore storage error
            }
        },

        clearLocalStorageDraft() {
            const localKey = this.getLocalStorageKey();
            try {
                localStorage.removeItem(localKey);
            } catch (e) {}
        },

        onTranscriptInput() {
            const field = this.$refs.transcriptField;
            if (!field) return;

            const currentVal = field.value;
            this.transcriptChanged = (currentVal !== this.lastSavedContent);

            this.saveToLocalStorage(currentVal);

            clearTimeout(this.saveDebounceTimer);
            this.saveDebounceTimer = setTimeout(() => {
                this.triggerServerAutosave();
            }, 3000);
        },

        startPeriodicAutosave() {
            this.stopPeriodicAutosave();
            this.periodicSaveInterval = setInterval(() => {
                if (this.listening || this.transcriptChanged) {
                    this.triggerServerAutosave();
                }
            }, 12000);
        },

        stopPeriodicAutosave() {
            if (this.periodicSaveInterval) {
                clearInterval(this.periodicSaveInterval);
                this.periodicSaveInterval = null;
            }
        },

        triggerManualAutosave() {
            clearTimeout(this.saveDebounceTimer);
            this.triggerServerAutosave();
        },

        triggerServerAutosave() {
            const field = this.$refs.transcriptField;
            if (!field || !this.autosaveUrl) return;

            const currentContent = field.value;

            this.saveToLocalStorage(currentContent);

            this.autosaveState = 'saving';
            this.autosaveBadge = true;
            this.autosaveBadgeText = 'Menyimpan ke server...';

            const formData = new FormData();
            formData.append('transcript_text', currentContent);
            if (this.csrfToken) {
                formData.append('_token', this.csrfToken);
            }

            fetch(this.autosaveUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    this.autosaveState = 'saved';
                    this.lastSavedContent = currentContent;
                    this.transcriptChanged = false;
                    const timeStr = data.saved_at || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    this.autosaveBadgeText = 'Tersimpan otomatis (' + timeStr + ')';
                } else {
                    throw new Error('Save failed');
                }
            })
            .catch(() => {
                this.autosaveState = 'local';
                const nowStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                this.autosaveBadgeText = 'Tersimpan di browser (' + nowStr + ')';
            });
        },

        setupFormSubmitHandler() {
            const field = this.$refs.transcriptField;
            if (!field) return;

            const form = field.closest('form');
            if (form) {
                form.addEventListener('submit', () => {
                    this.clearLocalStorageDraft();
                    this.transcriptChanged = false;
                    window.onbeforeunload = null;
                });
            }
        }
    });

    const registerEventVoiceToText = () => {
        window.eventVoiceToText = createEventVoiceToText;

        if (window.Alpine?.data) {
            window.Alpine.data('eventVoiceToText', createEventVoiceToText);
        }

        const root = document.getElementById('unit-event-documentation-page');
        if (root && window.Alpine?.initTree && !root.__x) {
            window.Alpine.initTree(root);
        }
    };

    document.addEventListener('alpine:init', registerEventVoiceToText);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerEventVoiceToText, { once: true });
    } else {
        registerEventVoiceToText();
    }
})();
</script>
@endpush
