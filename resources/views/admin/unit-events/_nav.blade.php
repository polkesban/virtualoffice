<div class="mb-6 rounded-2xl border border-gray-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a href="{{ route('admin.halaman-unit.manage', $unitPage) }}" class="text-xs font-semibold uppercase tracking-wider text-brand-600 hover:underline">
                ← Kembali ke Halaman Unit
            </a>
            <h2 class="mt-1 text-lg font-extrabold text-gray-900 dark:text-white">
                Event Unit {{ $unitPage->judul }}
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kelola event, registrasi, materi, tes, evaluasi, dokumentasi/notulen, surat tugas kegiatan maupun peserta, dan sertifikat untuk halaman unit ini.
            </p>
        </div>

        @if(!empty($unitEvent?->id))
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ \App\Models\UnitEvent::$statusLabels[$unitEvent->status] ?? $unitEvent->status }}
                </span>
                <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    {{ \App\Models\UnitEvent::$metodeLabels[$unitEvent->metode] ?? $unitEvent->metode }}
                </span>
                <a href="{{ route('unit.events.show', [$unitPage->slug, $unitEvent->slug]) }}" target="_blank"
                   class="rounded-xl border border-gray-200 px-3 py-2 font-bold text-gray-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-700 dark:text-gray-200 dark:hover:border-brand-700 dark:hover:bg-brand-900/20 dark:hover:text-brand-300">
                    ↗ Lihat Event Publik
                </a>
            </div>
        @endif
    </div>

    <div class="mt-4 flex flex-wrap gap-2 text-sm font-medium">
        <a href="{{ route('admin.unit-events.index', $unitPage) }}"
           class="rounded-xl px-3 py-2 transition-colors {{ request()->routeIs('admin.unit-events.index') ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-900/20 dark:hover:text-brand-300' }}">
            Daftar Event
        </a>

        @if(!empty($unitEvent?->id))
            <a href="{{ route('admin.unit-events.edit', [$unitPage, $unitEvent]) }}"
               class="rounded-xl px-3 py-2 transition-colors {{ request()->routeIs('admin.unit-events.edit') ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-900/20 dark:hover:text-brand-300' }}">
                Pengaturan Event
            </a>
            <a href="{{ route('admin.unit-events.materials.index', [$unitPage, $unitEvent]) }}"
               class="rounded-xl px-3 py-2 transition-colors {{ request()->routeIs('admin.unit-events.materials.*') ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-900/20 dark:hover:text-brand-300' }}">
                Materi
            </a>
            <a href="{{ route('admin.unit-events.questions.index', [$unitPage, $unitEvent]) }}"
               class="rounded-xl px-3 py-2 transition-colors {{ request()->routeIs('admin.unit-events.questions.*') ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-900/20 dark:hover:text-brand-300' }}">
                Pertanyaan
            </a>
            <a href="{{ route('admin.unit-events.documentation.index', [$unitPage, $unitEvent]) }}"
               class="rounded-xl px-3 py-2 transition-colors {{ request()->routeIs('admin.unit-events.documentation.*') ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-900/20 dark:hover:text-brand-300' }}">
                Notulen, Foto & Laporan
            </a>
            <a href="{{ route('admin.unit-events.participants.index', [$unitPage, $unitEvent]) }}"
               class="rounded-xl px-3 py-2 transition-colors {{ request()->routeIs('admin.unit-events.participants.*') ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-900/20 dark:hover:text-brand-300' }}">
                Peserta, Surat Tugas & Sertifikat
            </a>
            <a href="{{ route('admin.unit-events.signature', [$unitPage, $unitEvent]) }}"
               class="rounded-xl px-3 py-2 transition-colors flex items-center gap-1.5 {{ request()->routeIs('admin.unit-events.signature*') ? 'bg-brand-600 text-white' : 'bg-emerald-50 text-emerald-800 font-bold hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                <span>✍️ TTD Penanggung Jawab</span>
                @if($unitEvent->penandatangan_signature_path)
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500" title="Tanda tangan aktif"></span>
                @endif
            </a>
            <a href="{{ route('admin.unit-events.flyer', [$unitPage, $unitEvent]) }}" target="_blank"
               class="rounded-xl px-3 py-2 transition-colors bg-amber-50 text-amber-800 font-bold hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300">
                🖼️ Desain Flyer
            </a>
            <a href="{{ route('admin.unit-events.ai-documents.index', [$unitPage, $unitEvent]) }}"
               class="rounded-xl px-3 py-2 transition-colors {{ request()->routeIs('admin.unit-events.ai-documents.*') ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-brand-900/20 dark:hover:text-brand-300' }}">
                Dokumen AI
            </a>
            <a href="{{ route('admin.rubrik.index', ['unit_page_id' => $unitPage->id, 'unit_event_id' => $unitEvent->id]) }}"
               class="rounded-xl px-3 py-2 font-bold transition-colors bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300">
                📊 Rubrik Penilaian Event
            </a>
        @endif
    </div>
</div>