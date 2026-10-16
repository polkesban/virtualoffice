@extends('admin.layouts.admin')

@section('page-title', 'Knowledge Base')
@section('page-subtitle', 'Dokumen referensi untuk Virtual Concierge (RAG)')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    {{-- ── Upload Card ─────────────────────────────────────────────────────── --}}
    <div class="lg:col-span-4 bg-white dark:bg-gray-900
                rounded-2xl border border-gray-100 dark:border-gray-800 p-6"
         x-data="{ uploadType: 'file' }">

        <div class="flex items-center gap-2 mb-5">
            <span class="text-xl" aria-hidden="true">📄</span>
            <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Upload Dokumen</h2>
        </div>

        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="mb-4 rounded-xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800
                        px-4 py-2.5 text-xs text-green-700 dark:text-green-300" role="alert">
                {{ session('success') }}
            </div>
        @endif

        {{-- Type Toggles --}}
        <div class="flex rounded-xl bg-gray-50 dark:bg-gray-800 p-1 mb-4">
            <button type="button" 
                    @click="uploadType = 'file'" 
                    :class="uploadType === 'file' ? 'bg-white dark:bg-gray-700 shadow-sm text-brand-700 dark:text-brand-300' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all focus:outline-none">
                📁 File
            </button>
            <button type="button" 
                    @click="uploadType = 'url'" 
                    :class="uploadType === 'url' ? 'bg-white dark:bg-gray-700 shadow-sm text-brand-700 dark:text-brand-300' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="flex-1 py-1.5 text-xs font-bold rounded-lg transition-all focus:outline-none">
                🔗 URL Web
            </button>
        </div>

        <form action="{{ route('admin.knowledge.upload') }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="type" :value="uploadType">

            @if($errors->any())
                <div class="rounded-xl bg-red-50 dark:bg-red-950 border border-red-200 px-4 py-2.5 text-xs text-red-700" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label for="kb-name" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                    Nama Dokumen *
                </label>
                <input id="kb-name" name="name" type="text" required
                       placeholder="Panduan Penerimaan 2026"
                       value="{{ old('name') }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-gray-50 dark:bg-gray-800 px-3 py-2.5 text-sm
                              text-gray-900 dark:text-white
                              focus:outline-none focus:ring-2 focus:ring-brand-300">
            </div>

            {{-- Input File --}}
            <div x-show="uploadType === 'file'">
                <label for="kb-file" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                    File (PDF, MD, TXT) *
                </label>
                <input id="kb-file" name="file" type="file" :required="uploadType === 'file'"
                       accept=".pdf,.md,.txt"
                       class="w-full text-xs file:mr-3 file:py-2 file:px-4
                              file:rounded-xl file:border-0
                              file:text-xs file:font-bold
                              file:bg-brand-50 dark:file:bg-brand-950
                              file:text-brand-700 dark:file:text-brand-300
                              file:cursor-pointer
                              text-gray-500 dark:text-gray-400">
                <p class="mt-1 text-[10px] text-gray-400">Max 20 MB. PDF: teks harus extractable (bukan scan).</p>
            </div>

            {{-- Input URL --}}
            <div x-show="uploadType === 'url'" x-cloak>
                <label for="kb-url" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                    Link URL Web *
                </label>
                <input id="kb-url" name="url" type="url" :required="uploadType === 'url'"
                       placeholder="https://example.com/panduan"
                       value="{{ old('url') }}"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-gray-50 dark:bg-gray-800 px-3 py-2.5 text-sm
                              text-gray-900 dark:text-white
                              focus:outline-none focus:ring-2 focus:ring-brand-300">
                <p class="mt-1 text-[10px] text-gray-400">Sistem akan mengunduh dan mengekstrak teks dari halaman web ini.</p>
            </div>

            <div>
                <label for="kb-desc" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                    Deskripsi
                </label>
                <textarea id="kb-desc" name="description" rows="2"
                          placeholder="Opsional — catatan tentang isi dokumen ini"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs resize-none
                                 text-gray-900 dark:text-white
                                 focus:outline-none focus:ring-2 focus:ring-brand-300">{{ old('description') }}</textarea>
            </div>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-2
                           rounded-xl bg-brand-600 hover:bg-brand-500
                           px-5 py-3 text-sm font-bold text-white transition-colors">
                <span x-text="uploadType === 'file' ? '📤 Upload & Indeks' : '🔗 Simpan & Indeks'"></span>
            </button>
        </form>

        {{-- RAG info --}}
        <div class="mt-6 rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Cara Kerja RAG</p>
            <ol class="space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                <li class="flex gap-2"><span class="font-bold text-brand-600">1.</span> Dokumen dipecah menjadi chunk ~500 karakter</li>
                <li class="flex gap-2"><span class="font-bold text-brand-600">2.</span> Setiap chunk diembedding dan disimpan ke DB</li>
                <li class="flex gap-2"><span class="font-bold text-brand-600">3.</span> Pertanyaan pengunjung diembedding lalu dicocokkan</li>
                <li class="flex gap-2"><span class="font-bold text-brand-600">4.</span> 5 chunk paling relevan dikirim sebagai konteks ke AI</li>
            </ol>
        </div>
    </div>

    {{-- ── Documents Table ─────────────────────────────────────────────────── --}}
    <div class="lg:col-span-8 bg-white dark:bg-gray-900
                rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Dokumen ({{ $docs->count() }})</h2>
            <span class="text-xs text-gray-400">
                {{ $docs->where('status', 'indexed')->count() }} terindeks ·
                {{ $docs->sum('chunks_count') }} chunk total
            </span>
        </div>

        @if($docs->isEmpty())
            <div class="flex flex-col items-center py-14 text-gray-400 dark:text-gray-600">
                <div class="text-5xl mb-3" aria-hidden="true">📚</div>
                <p class="text-sm font-medium">Knowledge base kosong.</p>
                <p class="text-xs mt-1">Upload dokumen pertama untuk mengaktifkan Virtual Concierge.</p>
            </div>
        @else
            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                @foreach($docs as $doc)
                    @php $badge = $doc->statusBadge(); @endphp
                    <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors group">
                        {{-- Icon --}}
                        <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center
                                    text-xl flex-shrink-0" aria-hidden="true">
                            {{ $doc->file_type === 'url' ? '🔗' : ($doc->file_type === 'pdf' ? '📄' : '📝') }}
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $doc->name }}</p>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="text-xs text-gray-400">{{ strtoupper($doc->file_type) }}</span>
                                <span class="text-xs text-gray-400">{{ $doc->chunk_count }} chunk</span>
                                @if($doc->indexed_at)
                                <span class="text-xs text-gray-400">{{ $doc->indexed_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            @if($doc->description)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $doc->description }}</p>
                            @endif
                        </div>

                        {{-- Status --}}
                        <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $badge['class'] }}"
                                  @if(in_array($doc->status, ['pending','indexing','error']) || str_starts_with($doc->status, 'indexing:'))
                                      x-data x-init="setInterval(() => location.reload(), 5000)"
                                  @endif>
                                {{ $badge['label'] }}
                            </span>
                            @if(isset($badge['progress']) && $badge['progress'] > 0 && $badge['progress'] < 100)
                                <div class="w-24 bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-brand-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $badge['progress'] }}%"></div>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 flex-shrink-0 opacity-80 md:opacity-50 hover:opacity-100 group-hover:opacity-100 transition-opacity">
                            <form action="{{ route('admin.knowledge.reindex', $doc->id) }}" method="POST">
                                @csrf
                                <button title="Indeks ulang"
                                        class="rounded-lg border border-gray-200 dark:border-gray-700
                                               px-2.5 py-1.5 text-xs font-medium text-gray-500
                                               hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                    ↺
                                </button>
                            </form>
                            <form action="{{ route('admin.knowledge.destroy', $doc->id) }}" method="POST"
                                  onsubmit="return confirm('Hapus dokumen \'{{ addslashes($doc->name) }}\' dan semua chunk-nya?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-red-100 dark:border-red-900
                                               px-2.5 py-1.5 text-xs font-medium text-red-400
                                               hover:bg-red-50 dark:hover:bg-red-950 transition-colors">
                                    🗑
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Tip --}}
        <div class="px-6 py-4 border-t border-gray-50 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40">
            <p class="text-xs text-gray-400">
                💡 <strong>Tips:</strong> Indeks ulang jika dokumen diperbarui.
                Gunakan <code class="bg-gray-200 dark:bg-gray-700 px-1 py-0.5 rounded text-[10px]">php8.3 artisan knowledge:index --force</code> untuk re-index semua via CLI.
            </p>
        </div>
    </div>
</div>

@endsection
