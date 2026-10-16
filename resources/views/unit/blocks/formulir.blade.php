@php
    use App\Support\PageFormBlock;

    $cfg = PageFormBlock::normalizeConfig($section->konfigurasi ?? []);
    $fields = $cfg['fields'] ?? [];
    $pengantar = $cfg['pengantar'] ?? '';
    $pesanOk = $cfg['pesan_sukses'] ?? 'Terima kasih, tanggapan Anda telah diterima.';
    $submitLabel = $cfg['judul_submit'] ?? 'Kirim';
    $headerLogoUrl = $cfg['header_logo_url'] ?? '';
    $headerLogoAlt = $cfg['header_logo_alt'] ?? 'Logo formulir';
    $headerImageUrl = $cfg['header_image_url'] ?? '';
    $headerImageAlt = $cfg['header_image_alt'] ?? 'Header formulir';
    $introImageUrl = $cfg['pengantar_image_url'] ?? '';
    $introImageAlt = $cfg['pengantar_image_alt'] ?? 'Gambar pengantar formulir';
    $mode = $cfg['mode'] ?? 'form';
    $isQuiz = $mode === 'quiz';
    $passingScore = (float) ($cfg['passing_score'] ?? 70);
    $durationMinutes = max(0, (int) ($cfg['duration_minutes'] ?? 0));
    $availability = PageFormBlock::availabilityState($cfg);
    $accessState = PageFormBlock::accessState($section, $cfg, request());
    $accessAllowed = (bool) ($accessState['authorized'] ?? false);
    $accessScope = (string) ($cfg['access_scope'] ?? 'public');
    $accessEvent = $accessState['event'] ?? null;
    $proctoringEnabled = $isQuiz && ! empty($cfg['proctoring_enabled']);
    $sectionId = $section->id;
    $sukses = session('blok_sukses_' . $sectionId);
    $quizResult = session('blok_quiz_result_' . $sectionId);
    $inputTypes = ['text', 'email', 'tel', 'number', 'url', 'date', 'time'];
    $questionNumber = 0;
    $hasSignatureField = collect($fields)->contains(static fn (array $field): bool => ($field['tipe'] ?? null) === 'signature');
    $hasHeaderMedia = $headerLogoUrl !== '' || $headerImageUrl !== '' || !empty($section->judul);
    $hasIntroMedia = $pengantar !== '' || $introImageUrl !== '';
    $quizStartSessionKey = 'page_form_quiz_started.' . $sectionId;
    if ($isQuiz && ($availability['is_open'] ?? false) && $accessAllowed && $durationMinutes > 0 && ! $sukses && ! session()->has($quizStartSessionKey)) {
        session()->put($quizStartSessionKey, now()->toIso8601String());
    }
    $quizStartedAt = $isQuiz ? session($quizStartSessionKey) : null;
    $hasMetaRibbon = $isQuiz || ! empty($cfg['opens_at'] ?? null) || ! empty($cfg['closes_at'] ?? null) || $durationMinutes > 0 || $accessScope !== 'public' || $proctoringEnabled;
@endphp
@if(!empty($fields))
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-[28px] border border-gray-200/80 bg-white/95 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-gray-900/90 sm:p-8">
        @if($hasHeaderMedia)
        <div class="mb-6 overflow-hidden rounded-[28px] border border-brand-100 bg-gradient-to-br from-white via-brand-50/70 to-cyan-50 shadow-sm dark:border-brand-900/40 dark:from-gray-900 dark:via-brand-950/20 dark:to-cyan-950/20">
            @if($headerImageUrl !== '')
            <img src="{{ $headerImageUrl }}" alt="{{ $headerImageAlt }}" class="h-44 w-full object-cover sm:h-56">
            @endif
            <div class="flex flex-col gap-4 px-6 py-6 sm:flex-row sm:items-center sm:px-7">
                @if($headerLogoUrl !== '')
                <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl border border-white/70 bg-white/90 p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900/80">
                    <img src="{{ $headerLogoUrl }}" alt="{{ $headerLogoAlt }}" class="h-full w-full object-contain">
                </div>
                @endif
                <div class="space-y-2">
                    <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-brand-700 dark:text-brand-300">Formulir Digital</p>
                    @if(!empty($section->judul))
                    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ $section->judul }}</h2>
                    @endif
                    <p class="max-w-3xl text-sm leading-7 text-gray-600 dark:text-gray-300">Gunakan area ini untuk menampilkan kop, identitas unit, atau visual pembuka yang membuat formulir terasa resmi dan mudah dikenali.</p>
                </div>
            </div>
        </div>
        @endif

        @if($hasIntroMedia)
        <div class="mb-6 overflow-hidden rounded-2xl bg-brand-50/80 dark:bg-brand-950/30">
            @if($introImageUrl !== '')
            <img src="{{ $introImageUrl }}" alt="{{ $introImageAlt }}" class="h-48 w-full object-cover">
            @endif
            @if($pengantar !== '')
            <div class="prose prose-sm max-w-none p-4 text-gray-600 dark:prose-invert dark:text-gray-300">
                {!! \Illuminate\Support\Str::contains($pengantar, '<') ? $pengantar : nl2br(e($pengantar)) !!}
            </div>
            @endif
        </div>
        @endif

        @if($hasMetaRibbon)
        <div class="mb-6 rounded-3xl border border-gray-200 bg-white/80 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950/30">
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-gray-600 dark:text-gray-300">
                @if($isQuiz)
                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">Mode Kuis</span>
                <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-700 dark:bg-gray-900">Batas lulus {{ number_format($passingScore, 0) }}%</span>
                @endif
                @if($accessScope === 'employee_only')
                <span class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-300">Pegawai saja</span>
                @elseif($accessScope === 'student_only')
                <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-sky-700 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300">Mahasiswa saja</span>
                @elseif($accessScope === 'employee_or_student')
                <span class="rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-violet-700 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300">Pegawai atau mahasiswa</span>
                @elseif($accessScope === 'event_participants')
                <span class="rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-1 text-fuchsia-700 dark:border-fuchsia-900 dark:bg-fuchsia-950/30 dark:text-fuchsia-300">Peserta event terdaftar</span>
                @endif
                @if($proctoringEnabled)
                <span class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">Proctoring aktif</span>
                @endif
                @if($durationMinutes > 0)
                <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-700 dark:bg-gray-900">Durasi {{ $durationMinutes }} menit</span>
                @endif
                @if(!empty($cfg['opens_at']))
                <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-700 dark:bg-gray-900">Buka {{ \Carbon\Carbon::parse($cfg['opens_at'])->translatedFormat('d M Y H:i') }}</span>
                @endif
                @if(!empty($cfg['closes_at']))
                <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-700 dark:bg-gray-900">Tutup {{ \Carbon\Carbon::parse($cfg['closes_at'])->translatedFormat('d M Y H:i') }}</span>
                @endif
                @if($isQuiz && $durationMinutes > 0 && ($availability['is_open'] ?? false) && ! $sukses)
                <span class="ml-auto rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-brand-700 dark:border-brand-900 dark:bg-brand-950/30 dark:text-brand-300">Sisa waktu <span data-quiz-timer>--:--</span></span>
                @endif
            </div>
        </div>
        @endif

        @if($sukses)
        <div class="rounded-2xl border border-green-200 bg-green-50 p-6 text-center dark:border-green-800 dark:bg-green-900/20">
            <div class="text-3xl">✅</div>
            <p class="mt-3 text-base font-semibold text-green-700 dark:text-green-300">{{ $pesanOk }}</p>
            @if($quizResult)
            <div class="mx-auto mt-5 grid max-w-3xl gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-green-200 bg-white px-4 py-3 dark:border-green-900 dark:bg-gray-900/60">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Skor</p>
                    <p class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format((float) ($quizResult['score'] ?? 0), 2) }}</p>
                </div>
                <div class="rounded-2xl border border-green-200 bg-white px-4 py-3 dark:border-green-900 dark:bg-gray-900/60">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Maksimum</p>
                    <p class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format((float) ($quizResult['max_score'] ?? 0), 2) }}</p>
                </div>
                <div class="rounded-2xl border border-green-200 bg-white px-4 py-3 dark:border-green-900 dark:bg-gray-900/60">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Persentase</p>
                    <p class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format((float) ($quizResult['percent'] ?? 0), 2) }}%</p>
                </div>
                <div class="rounded-2xl border {{ !empty($quizResult['passed']) ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20' }} px-4 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Status</p>
                    <p class="mt-1 text-lg font-extrabold {{ !empty($quizResult['passed']) ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">{{ !empty($quizResult['passed']) ? 'Lulus' : 'Belum Lulus' }}</p>
                </div>
            </div>
            @endif
            @if(!empty($quizResult['show_rationales_after_submit']) && !empty($quizResult['items']))
            <div class="mx-auto mt-5 max-w-3xl space-y-3 text-left">
                @foreach($fields as $field)
                    @php
                        $resultItem = $quizResult['items'][$field['key'] ?? ''] ?? null;
                        $rationale = is_array($resultItem) ? trim((string) ($resultItem['rationale'] ?? '')) : '';
                    @endphp
                    @if(is_array($resultItem) && $rationale !== '')
                    <div class="rounded-2xl border {{ ($resultItem['correct'] ?? null) === true ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/20' : 'border-amber-200 bg-amber-50/70 dark:border-amber-900 dark:bg-amber-950/20' }} px-4 py-3">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <span>{{ $field['nama'] ?? 'Pertanyaan' }}</span>
                            <span class="rounded-full px-2 py-1 {{ ($resultItem['correct'] ?? null) === true ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' }}">{{ ($resultItem['correct'] ?? null) === true ? 'Benar' : 'Perlu Diperbaiki' }}</span>
                        </div>
                        <div class="prose prose-sm mt-2 max-w-none text-sm leading-6 text-gray-700 dark:prose-invert dark:text-gray-200">
                            {!! \Illuminate\Support\Str::contains($rationale, '<') ? $rationale : nl2br(e($rationale)) !!}
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
        @else
        @if(!($availability['is_open'] ?? false))
        <div class="rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-medium text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
            {{ $availability['message'] ?? 'Akses formulir saat ini ditutup.' }}
        </div>
        @elseif(!$accessAllowed)
        <div class="space-y-4 rounded-3xl border border-indigo-200 bg-indigo-50/70 p-5 dark:border-indigo-900 dark:bg-indigo-950/20">
            @error('form')
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                {{ $message }}
            </div>
            @enderror
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-indigo-700 dark:text-indigo-300">Akses Terbatas</p>
                <h3 class="mt-2 text-xl font-extrabold text-gray-900 dark:text-white">{{ $accessState['message'] ?? 'Formulir ini memiliki pembatasan akses.' }}</h3>
                @if($accessScope === 'event_participants' && $accessEvent)
                <p class="mt-2 text-sm leading-7 text-gray-600 dark:text-gray-300">Masukkan kode registrasi untuk event {{ $accessEvent->judul }} agar formulir ini dapat dibuka.</p>
                @else
                <p class="mt-2 text-sm leading-7 text-gray-600 dark:text-gray-300">Silakan gunakan akun yang sesuai dengan syarat akses formulir ini.</p>
                @endif
            </div>

            @if($accessScope === 'event_participants')
            <form action="{{ route('block.authorize', $section) }}" method="POST" class="grid gap-3 rounded-2xl border border-indigo-200 bg-white/80 p-4 dark:border-indigo-900 dark:bg-gray-900/60 md:grid-cols-[1fr_auto] md:items-end">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-[0.18em] text-gray-400">{{ $cfg['access_registration_code_label'] ?? 'Kode registrasi event' }}</label>
                    <input type="text" name="_access_registration_code" value="{{ old('_access_registration_code') }}" class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-brand-400 focus:ring-2 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" placeholder="Masukkan kode registrasi">
                </div>
                <button type="submit" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">Buka Formulir</button>
            </form>
            @endif

            <div class="flex flex-wrap gap-3 text-sm">
                @if(in_array($accessScope, ['employee_only', 'employee_or_student'], true))
                <a href="{{ route('pegawai.login') }}" class="rounded-2xl border border-indigo-200 bg-white px-4 py-3 font-bold text-indigo-700 transition-colors hover:border-indigo-300 hover:text-indigo-800 dark:border-indigo-900 dark:bg-gray-900 dark:text-indigo-300">Login Pegawai</a>
                @endif
                @if(in_array($accessScope, ['student_only', 'employee_or_student'], true))
                <a href="{{ route('mahasiswa.login') }}" class="rounded-2xl border border-sky-200 bg-white px-4 py-3 font-bold text-sky-700 transition-colors hover:border-sky-300 hover:text-sky-800 dark:border-sky-900 dark:bg-gray-900 dark:text-sky-300">Login Mahasiswa</a>
                @endif
            </div>
        </div>
        @else
        <form action="{{ route('block.submit', $section) }}" method="POST" enctype="multipart/form-data" class="space-y-6" data-polkesban-form data-quiz-enabled="{{ $isQuiz ? '1' : '0' }}" data-quiz-duration-minutes="{{ $durationMinutes }}" data-quiz-started-at="{{ (string) $quizStartedAt }}" data-timeout-message="{{ $cfg['time_limit_message'] ?? 'Waktu pengerjaan untuk formulir ini sudah habis.' }}" data-proctoring-enabled="{{ $proctoringEnabled ? '1' : '0' }}" data-proctoring-require-fullscreen="{{ !empty($cfg['proctoring_require_fullscreen']) ? '1' : '0' }}" data-proctoring-capture-selfie="{{ !empty($cfg['proctoring_capture_selfie']) ? '1' : '0' }}" data-proctoring-block-copy-paste="{{ !empty($cfg['proctoring_block_copy_paste']) ? '1' : '0' }}" data-proctoring-block-context-menu="{{ !empty($cfg['proctoring_block_context_menu']) ? '1' : '0' }}" data-proctoring-auto-submit="{{ !empty($cfg['proctoring_auto_submit']) ? '1' : '0' }}" data-proctoring-max-violations="{{ (int) ($cfg['proctoring_max_violations'] ?? 0) }}" data-proctoring-notice="{{ $cfg['proctoring_notice'] ?? '' }}">
            @csrf
            @if($isQuiz)
            <input type="hidden" name="_quiz_started_at" value="{{ (string) $quizStartedAt }}">
            @endif
            @if($proctoringEnabled)
            <input type="hidden" name="_proctoring[events_json]" value="[]" data-proctoring-events>
            <input type="hidden" name="_proctoring[device_json]" value="{}" data-proctoring-device>
            <input type="hidden" name="_proctoring[started_at]" value="" data-proctoring-started>
            <input type="hidden" name="_proctoring[suspicious_count]" value="0" data-proctoring-count>
            <input type="hidden" name="_proctoring[selfie]" value="" data-proctoring-selfie>
            <div class="rounded-3xl border border-rose-200 bg-rose-50/70 p-5 dark:border-rose-900 dark:bg-rose-950/20" data-proctoring-panel>
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-rose-700 dark:text-rose-300">Proctoring</p>
                        <p class="mt-2 text-sm leading-7 text-gray-700 dark:text-gray-200">{{ $cfg['proctoring_notice'] ?? 'Mode proctoring aktif. Aktivitas peserta akan dipantau selama kuis berlangsung.' }}</p>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-white/80 px-4 py-3 text-sm dark:border-rose-900 dark:bg-gray-900/60">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Pelanggaran Tercatat</p>
                        <p class="mt-1 text-xl font-extrabold text-rose-700 dark:text-rose-300"><span data-proctoring-count-label>0</span></p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-3 text-sm">
                    @if(!empty($cfg['proctoring_require_fullscreen']))
                    <button type="button" data-proctoring-fullscreen class="rounded-2xl border border-rose-200 bg-white px-4 py-3 font-bold text-rose-700 transition-colors hover:border-rose-300 hover:text-rose-800 dark:border-rose-900 dark:bg-gray-900 dark:text-rose-300">Masuk Mode Fullscreen</button>
                    @endif
                    <span class="rounded-2xl border border-rose-200 bg-white px-4 py-3 text-gray-600 dark:border-rose-900 dark:bg-gray-900 dark:text-gray-300" data-proctoring-status>Monitoring belum dimulai.</span>
                </div>
                @if(!empty($cfg['proctoring_capture_selfie']))
                <div class="mt-4 rounded-2xl border border-rose-200 bg-white/90 p-4 dark:border-rose-900 dark:bg-gray-900/60" data-proctoring-selfie-panel>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $cfg['proctoring_selfie_label'] ?? 'Ambil foto verifikasi peserta' }}</p>
                    <p class="mt-1 text-xs leading-6 text-gray-500 dark:text-gray-400">Ambil selfie singkat sebelum mengerjakan kuis agar identitas peserta tercatat pada dashboard admin.</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-[1fr_auto] md:items-start">
                        <div class="rounded-2xl border border-gray-200 bg-gray-950/90 p-3 dark:border-gray-700">
                            <video autoplay playsinline muted class="hidden h-52 w-full rounded-xl object-cover" data-proctoring-video></video>
                            <img alt="Preview selfie proctoring" class="hidden h-52 w-full rounded-xl object-cover" data-proctoring-selfie-preview>
                            <p class="flex h-52 items-center justify-center rounded-xl border border-dashed border-gray-700 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400" data-proctoring-selfie-placeholder>Kamera belum diaktifkan</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <button type="button" data-proctoring-camera-start class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-bold text-gray-700 transition-colors hover:border-brand-300 hover:text-brand-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">Aktifkan Kamera</button>
                            <button type="button" data-proctoring-camera-capture class="rounded-2xl bg-brand-600 px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">Ambil Selfie</button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            @error('form')
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                {{ $message }}
            </div>
            @enderror

            @foreach($fields as $field)
            @php
                $key = $field['key'] ?? 'field_' . $loop->iteration;
                $type = $field['tipe'] ?? 'text';
                $required = !empty($field['wajib']);
                $oldValue = old('fields.' . $key);
                $isAnswerable = PageFormBlock::acceptsResponse($field);
                $visibilityMode = (string) ($field['visibility_mode'] ?? 'always');
                $visibilityMatch = (string) ($field['visibility_match'] ?? 'all');
                $visibilityRules = $field['visibility_rules'] ?? [];
                if ($isAnswerable) {
                    $questionNumber++;
                }
            @endphp

            @if($type === 'section')
            <div class="overflow-hidden rounded-[28px] border border-brand-200 bg-gradient-to-br from-brand-600 via-brand-500 to-cyan-500 text-white shadow-sm" data-field-block data-field-key="{{ $key }}" data-field-answerable="0" data-visibility-mode="{{ $visibilityMode }}" data-visibility-match="{{ $visibilityMatch }}" data-visibility-rules='@json($visibilityRules)'>
                <div class="px-6 py-6 sm:px-7">
                    <p class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/70">Bagian Formulir</p>
                    <h2 class="mt-2 text-2xl font-extrabold">{{ $field['nama'] ?? 'Bagian Baru' }}</h2>
                    @if(!empty($field['deskripsi']))
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-white/85">{{ $field['deskripsi'] }}</p>
                    @endif
                    @if(!empty($field['deskripsi_media_url']))
                    <img src="{{ $field['deskripsi_media_url'] }}" alt="{{ $field['deskripsi_media_alt'] ?? ($field['nama'] ?? 'Gambar bagian formulir') }}" class="mt-4 h-40 w-full rounded-2xl object-cover border border-white/20">
                    @endif
                </div>
            </div>
            @continue
            @endif

            @if($type === 'divider')
            <div class="relative py-2" data-field-block data-field-key="{{ $key }}" data-field-answerable="0" data-visibility-mode="{{ $visibilityMode }}" data-visibility-match="{{ $visibilityMatch }}" data-visibility-rules='@json($visibilityRules)'>
                <div class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-gradient-to-r from-transparent via-gray-300 to-transparent dark:via-gray-700"></div>
                <div class="relative flex justify-center">
                    <span class="rounded-full border border-gray-200 bg-white px-4 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-500">{{ $field['nama'] ?? 'Pemisah' }}</span>
                </div>
                @if(!empty($field['deskripsi']))
                <p class="relative mt-3 text-center text-xs text-gray-500 dark:text-gray-400">{{ $field['deskripsi'] }}</p>
                @endif
            </div>
            @continue
            @endif

            <div class="rounded-2xl border border-gray-200/80 bg-gray-50/70 p-5 dark:border-gray-800 dark:bg-gray-950/40" data-field-block data-field-key="{{ $key }}" data-field-answerable="{{ $isAnswerable ? '1' : '0' }}" data-visibility-mode="{{ $visibilityMode }}" data-visibility-match="{{ $visibilityMatch }}" data-visibility-rules='@json($visibilityRules)'>
                <div class="mb-3">
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white">
                        <span data-question-number>{{ $questionNumber }}</span>. {{ $field['nama'] ?? 'Pertanyaan' }}@if($required)<span class="ml-1 text-red-500">*</span>@endif
                    </label>
                    @if(!empty($field['deskripsi']))
                    <p class="mt-1 text-xs leading-6 text-gray-500 dark:text-gray-400">{{ $field['deskripsi'] }}</p>
                    @endif
                    @if(!empty($field['deskripsi_media_url']))
                    <img src="{{ $field['deskripsi_media_url'] }}" alt="{{ $field['deskripsi_media_alt'] ?? ($field['nama'] ?? 'Gambar petunjuk') }}" class="mt-3 h-44 w-full rounded-2xl object-cover border border-gray-200 dark:border-gray-700">
                    @endif
                </div>

                @if($type === 'textarea')
                <textarea name="fields[{{ $key }}]"
                          rows="5"
                          data-rich-editor
                          data-rich-editor-profile="basic"
                          data-rich-editor-height="240"
                          data-rich-editor-init-hidden="true"
                          placeholder="{{ $field['placeholder'] ?? '' }}"
                          {{ $required ? 'required' : '' }}
                          class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-200 dark:border-gray-700 dark:bg-gray-800">{{ is_scalar($oldValue) ? (string) $oldValue : '' }}</textarea>

                @elseif(in_array($type, ['radio', 'checkbox'], true))
                <div class="space-y-2">
                    @foreach(($field['opsi'] ?? []) as $option)
                    @php
                        $optionValue = (string) ($option['value'] ?? '');
                        $optionLabel = (string) ($option['label'] ?? $optionValue);
                        $optionMediaUrl = (string) ($option['media_url'] ?? '');
                        $optionMediaAlt = (string) ($option['media_alt'] ?? $optionLabel);
                        $isChecked = $type === 'radio'
                            ? (string) $oldValue === $optionValue
                            : in_array($optionValue, is_array($oldValue) ? $oldValue : [], true);
                    @endphp
                    <label class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 transition hover:border-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <input type="{{ $type }}"
                               name="fields[{{ $key }}]{{ $type === 'checkbox' ? '[]' : '' }}"
                               value="{{ $optionValue }}"
                               {{ $isChecked ? 'checked' : '' }}
                               class="mt-0.5 h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="flex flex-1 items-start gap-3">
                            @if($optionMediaUrl !== '')
                            <img src="{{ $optionMediaUrl }}" alt="{{ $optionMediaAlt }}" class="h-16 w-16 rounded-xl object-cover border border-gray-200 dark:border-gray-700">
                            @endif
                            <span class="pt-1">{{ $optionLabel }}</span>
                        </span>
                    </label>
                    @endforeach
                </div>

                @elseif($type === 'select')
                @php
                    $hasSelectOptionMedia = collect($field['opsi'] ?? [])->contains(static fn (array $option): bool => !empty($option['media_url'] ?? ''));
                @endphp
                </select>
                @if($hasSelectOptionMedia)
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach(($field['opsi'] ?? []) as $option)
                    @php
                        $optionValue = (string) ($option['value'] ?? '');
                        $optionLabel = (string) ($option['label'] ?? $optionValue);
                        $optionMediaUrl = (string) ($option['media_url'] ?? '');
                        $optionMediaAlt = (string) ($option['media_alt'] ?? $optionLabel);
                    @endphp
                    <div class="rounded-2xl border {{ (string) $oldValue === $optionValue ? 'border-brand-300 bg-brand-50/70 dark:border-brand-700 dark:bg-brand-950/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} p-3">
                        @if($optionMediaUrl !== '')
                        <img src="{{ $optionMediaUrl }}" alt="{{ $optionMediaAlt }}" class="mb-3 h-28 w-full rounded-xl object-cover border border-gray-200 dark:border-gray-700">
                        @endif
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $optionLabel }}</p>
                    </div>
                    @endforeach
                </div>
                @endif

                @elseif($type === 'scale')
                @php
                    $scaleValues = PageFormBlock::scaleValues($field);
                @endphp
                <div class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-{{ max(2, count($scaleValues)) }}">
                        @foreach($scaleValues as $scaleValue)
                        <label class="flex flex-col items-center rounded-2xl border border-gray-200 bg-white px-3 py-4 text-center transition hover:border-brand-300 dark:border-gray-700 dark:bg-gray-800">
                            <input type="radio"
                                   name="fields[{{ $key }}]"
                                   value="{{ $scaleValue }}"
                                   {{ (string) $oldValue === (string) $scaleValue ? 'checked' : '' }}
                                   class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span class="mt-3 text-lg font-bold text-gray-900 dark:text-white">{{ $scaleValue }}</span>
                        </label>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $field['min_label'] ?? '' }}</span>
                        <span class="text-right">{{ $field['max_label'] ?? '' }}</span>
                    </div>
                </div>

                @elseif($type === 'matrix')
                @php
                    $scaleValues = PageFormBlock::scaleValues($field);
                @endphp
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="py-3 pr-4 font-semibold">Pernyataan</th>
                                @foreach($scaleValues as $scaleValue)
                                <th class="px-2 py-3 text-center font-semibold">{{ $scaleValue }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach(($field['pernyataan'] ?? []) as $statement)
                            @php
                                $rowKey = (string) ($statement['key'] ?? 'row_' . $loop->iteration);
                                $rowOldValue = old('fields.' . $key . '.' . $rowKey);
                            @endphp
                            <tr>
                                <td class="py-3 pr-4 align-top font-medium text-gray-700 dark:text-gray-200">{{ $statement['label'] ?? 'Pernyataan' }}</td>
                                @foreach($scaleValues as $scaleValue)
                                <td class="px-2 py-3 text-center">
                                    <input type="radio"
                                           name="fields[{{ $key }}][{{ $rowKey }}]"
                                           value="{{ $scaleValue }}"
                                           {{ (string) $rowOldValue === (string) $scaleValue ? 'checked' : '' }}
                                           class="h-4 w-4 border-gray-300 text-brand-600 focus:ring-brand-500">
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2 flex items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $field['min_label'] ?? '' }}</span>
                    <span class="text-right">{{ $field['max_label'] ?? '' }}</span>
                </div>

                @elseif($type === 'file')
                @php
                    $allowMultiple = !empty($field['multiple']);
                    $accept = $field['accept'] ?? '';
                    $maxSize = (int) ($field['max_size_mb'] ?? 10);
                    $maxFiles = max(1, (int) ($field['max_files'] ?? 1));
                @endphp
                <div class="space-y-3">
                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-brand-200 bg-brand-50/60 px-6 py-10 text-center transition hover:border-brand-400 hover:bg-brand-50 dark:border-brand-900 dark:bg-brand-950/20 dark:hover:border-brand-700">
                        <span class="text-3xl">📎</span>
                        <span class="mt-3 text-sm font-bold text-gray-900 dark:text-white">{{ $allowMultiple ? 'Pilih satu atau beberapa file' : 'Pilih satu file' }}</span>
                        <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Klik area ini untuk membuka penjelajah file.</span>
                        <input type="file"
                               name="fields[{{ $key }}]{{ $allowMultiple ? '[]' : '' }}"
                               {{ $allowMultiple ? 'multiple' : '' }}
                               @if($accept !== '') accept="{{ $accept }}" @endif
                               {{ $required ? 'required' : '' }}
                               class="hidden">
                    </label>
                    <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                        <span class="rounded-full bg-white px-3 py-1 dark:bg-gray-800">Ukuran maksimal {{ $maxSize }} MB per file</span>
                        <span class="rounded-full bg-white px-3 py-1 dark:bg-gray-800">{{ $allowMultiple ? 'Maksimal ' . $maxFiles . ' file' : 'Satu file per respons' }}</span>
                        @if($accept !== '')
                        <span class="rounded-full bg-white px-3 py-1 dark:bg-gray-800">Format: {{ $accept }}</span>
                        @endif
                    </div>
                </div>

                @elseif($type === 'signature')
                @php
                    $canvasHeight = max(120, (int) ($field['canvas_height'] ?? 220));
                    $strokeColor = $field['stroke_color'] ?? '#111827';
                    $backgroundColor = $field['background_color'] ?? '#ffffff';
                @endphp
                <div class="space-y-3" data-signature-pad data-signature-stroke="{{ $strokeColor }}" data-signature-background="{{ $backgroundColor }}" data-signature-height="{{ $canvasHeight }}">
                    <input type="hidden" name="fields[{{ $key }}]" value="{{ is_string($oldValue) ? $oldValue : '' }}" data-signature-input>
                    <div class="rounded-3xl border border-gray-200 bg-white p-3 shadow-inner dark:border-gray-700 dark:bg-gray-800/80">
                        <canvas data-signature-canvas class="w-full touch-none rounded-2xl"></canvas>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-[11px] leading-6 text-gray-500 dark:text-gray-400">Gunakan mouse, touchpad, stylus, atau layar sentuh untuk menandatangani area di atas.</p>
                        <button type="button" data-signature-clear class="rounded-full border border-gray-200 px-3 py-1 text-[11px] font-semibold text-gray-600 transition hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:text-gray-300">Hapus Tanda Tangan</button>
                    </div>
                </div>

                @else
                <input type="{{ in_array($type, $inputTypes, true) ? $type : 'text' }}"
                       name="fields[{{ $key }}]"
                       value="{{ is_scalar($oldValue) ? (string) $oldValue : '' }}"
                       placeholder="{{ $field['placeholder'] ?? '' }}"
                       {{ $required ? 'required' : '' }}
                       class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-200 dark:border-gray-700 dark:bg-gray-800">
                @endif

                @error('fields.' . $key)
                <p class="mt-2 text-xs font-medium text-red-500">{{ $message }}</p>
                @enderror

                @if($type === 'file' && $errors->has('fields.' . $key . '.*'))
                <p class="mt-2 text-xs font-medium text-red-500">{{ $errors->first('fields.' . $key . '.*') }}</p>
                @endif

                @if($type === 'matrix')
                    @foreach(($field['pernyataan'] ?? []) as $statement)
                        @error('fields.' . $key . '.' . ($statement['key'] ?? ''))
                        <p class="mt-2 text-xs font-medium text-red-500">{{ $message }}</p>
                        @enderror
                    @endforeach
                @endif
            </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-6 py-3 text-sm font-bold text-white transition-colors hover:bg-brand-500">
                    {{ $submitLabel }}
                </button>
            </div>
        </form>
        @endif
        @endif
    </div>
</div>
@endif

@once
<script>
window.PolkesbanConditionalForms = window.PolkesbanConditionalForms || {
    init(root = document) {
        root.querySelectorAll('[data-polkesban-form]').forEach((form) => {
            if (form.dataset.formReady === '1') {
                return;
            }

            form.dataset.formReady = '1';

            const fieldBlocks = Array.from(form.querySelectorAll('[data-field-block]'));
            const timerTarget = form.querySelector('[data-quiz-timer]');
            const startInput = form.querySelector('input[name="_quiz_started_at"]');
            let timerId = null;
            let timerSubmitted = false;

            const parseFieldName = (name) => {
                const match = String(name || '').match(/^fields\[([^\]]+)\](?:\[([^\]]+)\])?/);
                if (!match) {
                    return null;
                }

                return {
                    key: match[1],
                    rowKey: match[2] || null,
                };
            };

            const collectValues = () => {
                const values = {};

                Array.from(form.elements).forEach((element) => {
                    if (!element.name || element.disabled) {
                        return;
                    }

                    const parsed = parseFieldName(element.name);
                    if (!parsed) {
                        return;
                    }

                    const { key, rowKey } = parsed;

                    if (rowKey) {
                        values[key] = values[key] || {};
                        if (element.type === 'radio') {
                            if (element.checked) {
                                values[key][rowKey] = element.value;
                            }

                            return;
                        }

                        values[key][rowKey] = element.value;
                        return;
                    }

                    if (element.type === 'checkbox') {
                        values[key] = values[key] || [];
                        if (element.checked) {
                            values[key].push(element.value);
                        }

                        return;
                    }

                    if (element.type === 'radio') {
                        if (element.checked) {
                            values[key] = element.value;
                        }

                        return;
                    }

                    values[key] = element.value;
                });

                return values;
            };

            const isEmpty = (value) => {
                if (value === null || value === undefined) {
                    return true;
                }

                if (Array.isArray(value)) {
                    return value.length === 0;
                }

                if (typeof value === 'object') {
                    return Object.keys(value).length === 0;
                }

                return String(value).trim() === '';
            };

            const containsValue = (actual, expected) => {
                if (Array.isArray(actual)) {
                    return actual.map((item) => String(item).trim()).includes(String(expected).trim());
                }

                return String(actual || '').toLowerCase().includes(String(expected || '').toLowerCase());
            };

            const equalsValue = (actual, expected) => {
                if (Array.isArray(actual)) {
                    const left = actual.map((item) => String(item).trim()).sort();
                    const right = Array.isArray(expected)
                        ? expected.map((item) => String(item).trim()).sort()
                        : [String(expected).trim()];

                    return JSON.stringify(left) === JSON.stringify(right);
                }

                return String(actual ?? '').trim() === String(expected ?? '').trim();
            };

            const compareNumeric = (actual, expected, operator) => {
                const left = Number(actual);
                const right = Number(expected);
                if (!Number.isFinite(left) || !Number.isFinite(right)) {
                    return false;
                }

                if (operator === 'gt') return left > right;
                if (operator === 'gte') return left >= right;
                if (operator === 'lt') return left < right;
                if (operator === 'lte') return left <= right;

                return false;
            };

            const ruleMatches = (rule, values) => {
                const actual = values[rule.source_key] ?? null;

                if (rule.operator === 'filled') {
                    return !isEmpty(actual);
                }

                if (rule.operator === 'empty') {
                    return isEmpty(actual);
                }

                if (rule.operator === 'contains') {
                    return containsValue(actual, rule.value);
                }

                if (rule.operator === 'not_contains') {
                    return !containsValue(actual, rule.value);
                }

                if (['gt', 'gte', 'lt', 'lte'].includes(rule.operator)) {
                    return compareNumeric(actual, rule.value, rule.operator);
                }

                if (rule.operator === 'not_equals') {
                    return !equalsValue(actual, rule.value);
                }

                return equalsValue(actual, rule.value);
            };

            const shouldShowBlock = (block, values) => {
                const mode = block.dataset.visibilityMode || 'always';
                if (mode !== 'conditional') {
                    return true;
                }

                let rules = [];
                try {
                    rules = JSON.parse(block.dataset.visibilityRules || '[]');
                } catch (error) {
                    rules = [];
                }

                if (!Array.isArray(rules) || rules.length === 0) {
                    return true;
                }

                const results = rules.map((rule) => ruleMatches(rule, values));
                return (block.dataset.visibilityMatch || 'all') === 'any'
                    ? results.includes(true)
                    : !results.includes(false);
            };

            const setBlockVisibility = (block, visible) => {
                block.hidden = !visible;
                block.classList.toggle('hidden', !visible);

                block.querySelectorAll('input, select, textarea, button').forEach((element) => {
                    if (element.name === '_token' || element.name === '_method') {
                        return;
                    }

                    element.disabled = !visible;
                });
            };

            const renumberQuestions = () => {
                let number = 0;

                fieldBlocks.forEach((block) => {
                    if (block.hidden || block.dataset.fieldAnswerable !== '1') {
                        return;
                    }

                    number += 1;
                    block.querySelectorAll('[data-question-number]').forEach((target) => {
                        target.textContent = String(number);
                    });
                });
            };

            const applyVisibility = () => {
                const values = collectValues();
                fieldBlocks.forEach((block) => setBlockVisibility(block, shouldShowBlock(block, values)));
                renumberQuestions();
            };

            form.addEventListener('input', applyVisibility);
            form.addEventListener('change', applyVisibility);
            applyVisibility();

            const quizEnabled = (form.dataset.quizEnabled || '0') === '1';
            const proctoringEnabled = (form.dataset.proctoringEnabled || '0') === '1';

            if (proctoringEnabled) {
                const eventsInput = form.querySelector('[data-proctoring-events]');
                const deviceInput = form.querySelector('[data-proctoring-device]');
                const startedInput = form.querySelector('[data-proctoring-started]');
                const countInput = form.querySelector('[data-proctoring-count]');
                const countLabel = form.querySelector('[data-proctoring-count-label]');
                const statusTarget = form.querySelector('[data-proctoring-status]');
                const selfieInput = form.querySelector('[data-proctoring-selfie]');
                const fullscreenButton = form.querySelector('[data-proctoring-fullscreen]');
                const startCameraButton = form.querySelector('[data-proctoring-camera-start]');
                const captureCameraButton = form.querySelector('[data-proctoring-camera-capture]');
                const video = form.querySelector('[data-proctoring-video]');
                const selfiePreview = form.querySelector('[data-proctoring-selfie-preview]');
                const selfiePlaceholder = form.querySelector('[data-proctoring-selfie-placeholder]');
                const requireFullscreen = (form.dataset.proctoringRequireFullscreen || '0') === '1';
                const captureSelfie = (form.dataset.proctoringCaptureSelfie || '0') === '1';
                const blockCopyPaste = (form.dataset.proctoringBlockCopyPaste || '0') === '1';
                const blockContextMenu = (form.dataset.proctoringBlockContextMenu || '0') === '1';
                const autoSubmit = (form.dataset.proctoringAutoSubmit || '0') === '1';
                const maxViolations = Number.parseInt(form.dataset.proctoringMaxViolations || '0', 10);
                let proctoringEvents = [];
                let proctoringCount = 0;
                let proctoringStream = null;
                let proctoringSubmitted = false;

                const updateStatus = (message) => {
                    if (statusTarget) {
                        statusTarget.textContent = message;
                    }
                };

                const syncProctoring = () => {
                    if (eventsInput) {
                        eventsInput.value = JSON.stringify(proctoringEvents);
                    }

                    if (countInput) {
                        countInput.value = String(proctoringCount);
                    }

                    if (countLabel) {
                        countLabel.textContent = String(proctoringCount);
                    }
                };

                const maybeAutoSubmit = () => {
                    if (!autoSubmit || !Number.isFinite(maxViolations) || maxViolations <= 0 || proctoringCount < maxViolations || proctoringSubmitted) {
                        return;
                    }

                    proctoringSubmitted = true;
                    updateStatus('Batas pelanggaran terlampaui. Formulir dikirim otomatis.');
                    window.setTimeout(() => form.requestSubmit(), 160);
                };

                const pushEvent = (type, meta = {}, countsAsViolation = true) => {
                    proctoringEvents.push({
                        type,
                        at: new Date().toISOString(),
                        counts_as_violation: countsAsViolation,
                        ...meta,
                    });

                    if (countsAsViolation) {
                        proctoringCount += 1;
                    }

                    syncProctoring();
                    maybeAutoSubmit();
                };

                if (startedInput && !String(startedInput.value || '').trim()) {
                    startedInput.value = new Date().toISOString();
                }

                if (deviceInput) {
                    deviceInput.value = JSON.stringify({
                        screen: {
                            width: window.screen?.width || null,
                            height: window.screen?.height || null,
                            pixelRatio: window.devicePixelRatio || 1,
                        },
                        viewport: {
                            width: window.innerWidth,
                            height: window.innerHeight,
                        },
                        platform: navigator.platform || '',
                        language: navigator.language || '',
                        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
                    });
                }

                syncProctoring();
                updateStatus(requireFullscreen ? 'Monitoring aktif. Masuk fullscreen sebelum mulai mengerjakan.' : 'Monitoring aktif. Aktivitas kuis sedang dicatat.');

                if (blockCopyPaste) {
                    ['copy', 'paste', 'cut'].forEach((eventName) => {
                        form.addEventListener(eventName, (event) => {
                            event.preventDefault();
                            pushEvent(`${eventName}_attempt`);
                            updateStatus('Percobaan copy, paste, atau cut terdeteksi dan dicatat.');
                        });
                    });
                }

                if (blockContextMenu) {
                    form.addEventListener('contextmenu', (event) => {
                        event.preventDefault();
                        pushEvent('context_menu_attempt');
                        updateStatus('Klik kanan dinonaktifkan selama proctoring aktif.');
                    });
                }

                document.addEventListener('visibilitychange', () => {
                    if (document.visibilityState === 'hidden') {
                        pushEvent('visibility_hidden');
                        updateStatus('Perpindahan tab atau aplikasi terdeteksi.');
                    }
                });

                window.addEventListener('blur', () => {
                    pushEvent('window_blur');
                    updateStatus('Kehilangan fokus jendela terdeteksi.');
                });

                document.addEventListener('fullscreenchange', () => {
                    if (!requireFullscreen) {
                        return;
                    }

                    if (!document.fullscreenElement) {
                        pushEvent('fullscreen_exit');
                        updateStatus('Peserta keluar dari mode fullscreen.');
                        return;
                    }

                    pushEvent('fullscreen_enter', {}, false);
                    updateStatus('Mode fullscreen aktif.');
                });

                fullscreenButton?.addEventListener('click', async () => {
                    try {
                        await form.requestFullscreen();
                    } catch (error) {
                        updateStatus('Browser menolak permintaan fullscreen. Gunakan browser yang mendukung fitur ini.');
                    }
                });

                startCameraButton?.addEventListener('click', async () => {
                    if (!navigator.mediaDevices?.getUserMedia || !video) {
                        updateStatus('Kamera tidak didukung oleh browser ini.');
                        return;
                    }

                    try {
                        proctoringStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
                        video.srcObject = proctoringStream;
                        video.classList.remove('hidden');
                        selfiePreview?.classList.add('hidden');
                        selfiePlaceholder?.classList.add('hidden');
                        updateStatus('Kamera aktif. Ambil selfie untuk verifikasi.');
                    } catch (error) {
                        updateStatus('Izin kamera ditolak atau perangkat kamera tidak tersedia.');
                    }
                });

                captureCameraButton?.addEventListener('click', () => {
                    if (!video || !selfieInput) {
                        return;
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 960;
                    canvas.height = video.videoHeight || 540;
                    const context = canvas.getContext('2d');
                    if (!context) {
                        updateStatus('Tidak dapat memproses tangkapan kamera.');
                        return;
                    }

                    context.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const dataUrl = canvas.toDataURL('image/jpeg', 0.86);
                    selfieInput.value = dataUrl;
                    if (selfiePreview) {
                        selfiePreview.src = dataUrl;
                        selfiePreview.classList.remove('hidden');
                    }
                    selfiePlaceholder?.classList.add('hidden');
                    video.classList.add('hidden');
                    pushEvent('selfie_captured', {}, false);
                    updateStatus('Selfie verifikasi berhasil direkam.');
                });

                form.addEventListener('submit', (event) => {
                    if (captureSelfie && selfieInput && !String(selfieInput.value || '').trim()) {
                        event.preventDefault();
                        updateStatus('Ambil selfie verifikasi sebelum mengirim kuis.');
                        return;
                    }

                    if (requireFullscreen && !document.fullscreenElement) {
                        event.preventDefault();
                        pushEvent('submit_without_fullscreen');
                        updateStatus('Masuk fullscreen terlebih dahulu sebelum mengirim kuis.');
                        return;
                    }

                    syncProctoring();

                    if (proctoringStream) {
                        proctoringStream.getTracks().forEach((track) => track.stop());
                    }
                });
            }

            if (quizEnabled && timerTarget) {
                const durationMinutes = Number.parseInt(form.dataset.quizDurationMinutes || '0', 10);
                if (!Number.isFinite(durationMinutes) || durationMinutes <= 0) {
                    return;
                }

                let startedAt = String(startInput?.value || form.dataset.quizStartedAt || '').trim();
                if (!startedAt) {
                    startedAt = new Date().toISOString();
                    if (startInput) {
                        startInput.value = startedAt;
                    }
                }

                const tick = () => {
                    const start = new Date(startedAt);
                    if (Number.isNaN(start.getTime())) {
                        timerTarget.textContent = '--:--';
                        return;
                    }

                    const totalSeconds = durationMinutes * 60;
                    const elapsed = Math.max(0, Math.floor((Date.now() - start.getTime()) / 1000));
                    const remaining = Math.max(0, totalSeconds - elapsed);
                    const minutes = String(Math.floor(remaining / 60)).padStart(2, '0');
                    const seconds = String(remaining % 60).padStart(2, '0');
                    timerTarget.textContent = `${minutes}:${seconds}`;

                    if (remaining > 0) {
                        return;
                    }

                    form.querySelectorAll('button[type="submit"]').forEach((button) => {
                        button.disabled = true;
                    });

                    if (!timerSubmitted) {
                        timerSubmitted = true;
                        window.setTimeout(() => form.requestSubmit(), 120);
                    }

                    if (timerId) {
                        window.clearInterval(timerId);
                    }
                };

                tick();
                timerId = window.setInterval(tick, 1000);
            }
        });
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.PolkesbanConditionalForms.init(document), { once: true });
} else {
    window.PolkesbanConditionalForms.init(document);
}
</script>
@endonce

@if($hasSignatureField)
@once
<script>
window.PolkesbanSignaturePads = window.PolkesbanSignaturePads || {
    init(root = document) {
        root.querySelectorAll('[data-signature-pad]').forEach((padRoot) => {
            if (padRoot.dataset.signatureReady === '1') {
                return;
            }

            padRoot.dataset.signatureReady = '1';

            const canvas = padRoot.querySelector('[data-signature-canvas]');
            const input = padRoot.querySelector('[data-signature-input]');
            const clearButton = padRoot.querySelector('[data-signature-clear]');
            const context = canvas.getContext('2d');
            const height = Math.max(parseInt(padRoot.dataset.signatureHeight || '220', 10) || 220, 120);
            const strokeColor = padRoot.dataset.signatureStroke || '#111827';
            const backgroundColor = padRoot.dataset.signatureBackground || '#ffffff';
            let drawing = false;
            let lastPoint = null;

            const configureBrush = () => {
                context.strokeStyle = strokeColor;
                context.lineWidth = 2.4;
                context.lineCap = 'round';
                context.lineJoin = 'round';
            };

            const fillBackground = () => {
                context.save();
                context.setTransform(1, 0, 0, 1, 0, 0);
                context.fillStyle = backgroundColor;
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.restore();
            };

            const loadDataUrl = (dataUrl) => {
                if (!dataUrl) {
                    fillBackground();
                    configureBrush();
                    return;
                }

                const image = new Image();
                image.onload = () => {
                    fillBackground();
                    const width = canvas.width / (window.devicePixelRatio || 1);
                    const targetHeight = canvas.height / (window.devicePixelRatio || 1);
                    context.drawImage(image, 0, 0, width, targetHeight);
                    configureBrush();
                };
                image.src = dataUrl;
            };

            const resizeCanvas = () => {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const width = Math.max(Math.floor(canvas.parentElement.getBoundingClientRect().width), 280);
                canvas.width = Math.floor(width * ratio);
                canvas.height = Math.floor(height * ratio);
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                loadDataUrl(input.value || '');
            };

            const pointFromEvent = (event) => {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: event.clientX - rect.left,
                    y: event.clientY - rect.top,
                };
            };

            const persist = () => {
                input.value = canvas.toDataURL('image/png');
            };

            const start = (event) => {
                drawing = true;
                lastPoint = pointFromEvent(event);
                context.beginPath();
                context.moveTo(lastPoint.x, lastPoint.y);
                if (canvas.setPointerCapture) {
                    canvas.setPointerCapture(event.pointerId);
                }
                event.preventDefault();
            };

            const move = (event) => {
                if (!drawing) {
                    return;
                }

                const point = pointFromEvent(event);
                context.lineTo(point.x, point.y);
                context.stroke();
                lastPoint = point;
                event.preventDefault();
            };

            const end = (event) => {
                if (!drawing) {
                    return;
                }

                move(event);
                drawing = false;
                lastPoint = null;
                persist();
            };

            clearButton?.addEventListener('click', () => {
                input.value = '';
                fillBackground();
                configureBrush();
            });

            canvas.addEventListener('pointerdown', start);
            canvas.addEventListener('pointermove', move);
            canvas.addEventListener('pointerup', end);
            canvas.addEventListener('pointerleave', end);
            canvas.addEventListener('pointercancel', end);
            window.addEventListener('resize', resizeCanvas);

            resizeCanvas();
            configureBrush();
        });
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.PolkesbanSignaturePads.init(document), { once: true });
} else {
    window.PolkesbanSignaturePads.init(document);
}
</script>
@endonce
@endif
