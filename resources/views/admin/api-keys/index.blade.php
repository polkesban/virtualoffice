@extends('admin.layouts.admin')

@section('title', 'API Keys')

@section('content')
<div class="max-w-5xl mx-auto space-y-8"
      x-data="apiKeyDiagnostics({
          testUrlTemplate: @js(route('admin.api-keys.test', ['key' => '__KEY__']))
      })"
      x-init="boot()">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">API Keys</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                Kelola API key AI dan TinyMCE Cloud dengan urutan fallback otomatis.
            </p>
        </div>
        <span class="inline-flex items-center gap-2 rounded-xl bg-brand-50 px-4 py-2 text-xs font-semibold text-brand-700 dark:bg-brand-950/40 dark:text-brand-200">
            Fallback terakhir editor: TinyMCE lokal
        </span>
    </div>

    {{-- ── Cara kerja ──────────────────────────────────────────────────────── --}}
    <div class="rounded-2xl bg-brand-50 dark:bg-brand-950/30 border border-brand-200 dark:border-brand-800 p-5">
        <div class="flex gap-3">
            <span class="text-xl">ℹ️</span>
            <div class="text-sm text-brand-800 dark:text-brand-200 space-y-1">
                <p class="font-semibold">Cara kerja Auto-Fallback:</p>
                <ol class="list-decimal list-inside space-y-0.5 text-brand-700 dark:text-brand-300">
                    <li>Key dicoba berurutan dari <strong>Priority terkecil</strong> terlebih dahulu.</li>
                    <li>Jika response <strong>429 / quota exhausted</strong>, key otomatis ditandai <span class="font-mono text-xs bg-red-100 dark:bg-red-900 px-1 rounded">Habis</span> dan key berikutnya dicoba.</li>
                    <li>Untuk Gemini dan OpenRouter, <span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">GEMINI_API_KEY</span> atau <span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">OPENROUTER_API_KEY</span> di .env tetap menjadi fallback terakhir.</li>
                    <li>Untuk TinyMCE Cloud, browser akan mencoba key aktif berurutan lalu otomatis turun ke editor lokal jika semua key cloud gagal.</li>
                    <li>Klik <strong>Reset</strong> saat quota sudah diisi ulang.</li>
                </ol>
            </div>
        </div>
    </div>

    <section>
        <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
            Provider AI Runtime Aktif
        </h2>
        <div class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-6">
            <form method="POST" action="{{ route('admin.api-keys.active-provider.update') }}" class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                @csrf

                <div class="space-y-1">
                    <p class="text-sm font-extrabold text-gray-900 dark:text-white">Provider aktif saat ini: {{ $providers[$activeProvider] ?? ucfirst($activeProvider) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Pilih provider AI utama untuk Polly, translator, RAG, dan layanan AI runtime lainnya. Jika nilainya sama dengan default server, override database akan dibersihkan.</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5">Provider Aktif</label>
                        <select name="active_provider"
                                class="min-w-[220px] rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700">
                            @foreach(['openrouter', 'openai', 'gemini'] as $provider)
                                <option value="{{ $provider }}" {{ ($activeProvider ?? $defaultProvider) === $provider ? 'selected' : '' }}>
                                    {{ $providers[$provider] ?? ucfirst($provider) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                            Default server: <span class="font-mono">{{ $defaultProvider }}</span>
                            · Runtime sekarang: <span class="font-mono">{{ $activeProvider }}</span>
                            @if($activeProviderOverride)
                                · Override DB aktif
                            @endif
                        </p>
                    </div>

                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 px-5 py-2.5 text-sm font-bold text-white transition-colors shadow-lg shadow-brand-900/20">
                        Simpan Provider Aktif
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section>
        <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
            Model Chat Runtime AI
        </h2>
        <div class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-6">
            <form method="POST" action="{{ route('admin.api-keys.models.update') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    @foreach(['openai', 'openrouter', 'gemini'] as $provider)
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-950/60 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $providers[$provider] ?? ucfirst($provider) }}</h3>
                                <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    @if(!empty($chatModelOverrides[$provider]))
                                        Override aktif dari database
                                    @else
                                        Menggunakan default dari konfigurasi aplikasi
                                    @endif
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ !empty($chatModelOverrides[$provider]) ? 'bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                {{ !empty($chatModelOverrides[$provider]) ? 'DB override' : 'Default' }}
                            </span>
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5">Chat Model</label>
                            <input name="chat_models[{{ $provider }}]"
                                   type="text"
                                   list="chat-model-suggestions-{{ $provider }}"
                                   value="{{ old('chat_models.' . $provider, $chatModels[$provider] ?? '') }}"
                                   placeholder="{{ $chatModelDefaults[$provider] ?? '' }}"
                                   class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700">
                            <datalist id="chat-model-suggestions-{{ $provider }}">
                                @foreach(($chatModelSuggestions[$provider] ?? []) as $modelOption)
                                    <option value="{{ $modelOption }}"></option>
                                @endforeach
                            </datalist>
                            @error('chat_models.' . $provider)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                                Default konfigurasi: <span class="font-mono">{{ $chatModelDefaults[$provider] ?? '—' }}</span>
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Ubah model chat provider di sini. Nilai ini dipakai untuk chat, translator, RAG, dan layanan AI yang membaca model runtime aktif.
                    </p>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 px-5 py-2.5 text-sm font-bold text-white transition-colors shadow-lg shadow-brand-900/20">
                        Simpan Model Runtime
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-[1.05fr_0.95fr] gap-6">
        <div class="rounded-2xl border border-emerald-200 dark:border-emerald-900/70 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Hasil Uji API Key</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Klik tombol Uji pada baris key untuk memeriksa koneksi provider yang sebenarnya.</p>
                </div>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold"
                      :class="testResult
                        ? (testResult.ok ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300')
                        : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'">
                    <span x-text="testResult ? (testResult.ok ? 'Koneksi OK' : 'Koneksi Gagal') : 'Belum diuji'"></span>
                </span>
            </div>

            <div class="mt-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-950/60 p-4 min-h-[180px]">
                <template x-if="!testResult">
                    <div class="h-full flex items-center justify-center text-center text-sm text-gray-500 dark:text-gray-400">
                        Pilih salah satu key lalu tekan <strong class="mx-1">Uji</strong> untuk melihat hasil koneksi, latency, dan cuplikan respons.
                    </div>
                </template>

                <template x-if="testResult">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-1 font-bold text-brand-700 dark:bg-brand-950 dark:text-brand-300" x-text="providerLabel(testResult.provider)"></span>
                            <span class="inline-flex items-center rounded-full bg-gray-200 px-2.5 py-1 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300" x-text="testResult.label"></span>
                            <span class="inline-flex items-center rounded-full bg-white px-2.5 py-1 font-medium text-gray-500 dark:bg-gray-900 dark:text-gray-400" x-show="testResult.latency_ms !== null">
                                <span x-text="`${testResult.latency_ms} ms`"></span>
                            </span>
                        </div>
                        <p class="text-sm font-semibold" :class="testResult.ok ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'" x-text="testResult.message"></p>
                        <template x-if="testResult.preview">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Cuplikan Respons</p>
                                <pre class="mt-1 overflow-x-auto whitespace-pre-wrap rounded-xl bg-slate-950 px-4 py-3 text-xs leading-6 text-slate-100" x-text="testResult.preview"></pre>
                            </div>
                        </template>
                        <template x-if="testResult.meta && Object.keys(testResult.meta).length">
                            <div class="flex flex-wrap gap-2 text-[11px] text-gray-500 dark:text-gray-400">
                                <template x-for="(value, key) in testResult.meta" :key="key">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 font-medium dark:bg-gray-800">
                                        <span x-text="`${key}: ${value}`"></span>
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-2xl border border-brand-200 dark:border-brand-900/70 bg-white dark:bg-gray-900 p-5 shadow-sm" x-ref="editorCard">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Preview TinyMCE</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Editor ini memakai mekanisme yang sama dengan form admin, jadi status di bawah menunjukkan source yang sedang aktif.</p>
                </div>
                <button type="button"
                        @click="refreshEditorPreview()"
                        :disabled="editorLoading"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500 disabled:opacity-60 disabled:cursor-not-allowed px-3 py-2 text-xs font-bold text-white transition-colors">
                    <span x-text="editorLoading ? 'Memuat...' : 'Muat Ulang Preview'"></span>
                </button>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 text-[11px]">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 font-bold"
                      :class="editorStatus.ready ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'">
                    <span x-text="editorStatus.ready ? 'Editor siap dipakai' : 'Editor belum siap'"></span>
                </span>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 font-medium"
                      :class="editorStatus.source === 'cloud' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'">
                    <span x-text="editorSourceLabel()"></span>
                </span>
                <template x-if="editorStatus.activeKeyLabel">
                    <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-1 font-medium text-brand-700 dark:bg-brand-950 dark:text-brand-300" x-text="editorStatus.activeKeyLabel"></span>
                </template>
            </div>

            <template x-if="editorStatus.lastError">
                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-200" x-text="editorStatus.lastError"></div>
            </template>

            <div class="mt-4 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <textarea id="api-key-tinymce-preview"
                          data-rich-editor
                          data-rich-editor-profile="full"
                          data-rich-editor-height="280"
                          class="w-full"
                          x-ref="previewTextarea"><h2>Preview TinyMCE API Key</h2><p>Jika editor tampil lengkap, key atau fallback lokal berjalan dengan baik.</p><ul><li>Toolbar aktif</li><li>Format teks bisa diuji</li><li>Status source terlihat di atas</li></ul></textarea>
            </div>
        </div>
    </section>

    {{-- ── Daftar Key ───────────────────────────────────────────────────────── --}}
    @php
        $grouped = $keys->groupBy('provider');
    @endphp

    @foreach(array_keys($providers) as $prov)
        @if($grouped->has($prov))
        <section>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    {{ $providers[$prov] }}
                </h2>
                @if($grouped[$prov]->contains(fn ($item) => $item->is_exhausted))
                <form method="POST" action="{{ route('admin.api-keys.reset-all') }}">
                    @csrf
                    <input type="hidden" name="provider" value="{{ $prov }}">
                    <button type="submit"
                            onclick="return confirm('Reset semua key {{ $providers[$prov] }} yang exhausted?')"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-400 px-3 py-1.5 text-xs font-semibold text-white transition-colors">
                        ↺ Reset Exhausted
                    </button>
                </form>
                @endif
            </div>
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-xs uppercase tracking-wider
                                  text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold w-6">P</th>
                            <th class="px-5 py-3 text-left font-semibold">Label</th>
                            <th class="px-5 py-3 text-left font-semibold">API Key</th>
                            <th class="px-5 py-3 text-left font-semibold">Status</th>
                            <th class="px-5 py-3 text-left font-semibold">Terakhir Dipakai</th>
                            <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($grouped[$prov] as $key)
                        @php $badge = $key->statusBadge(); @endphp
                        <tr class="bg-white dark:bg-gray-950 group
                                   {{ $key->is_exhausted ? 'opacity-60' : '' }}">
                            {{-- Priority --}}
                            <td class="px-5 py-3 font-mono text-gray-400">{{ $key->priority }}</td>

                            {{-- Label --}}
                            <td class="px-5 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $key->label }}</span>
                                @if($key->is_exhausted && $key->exhausted_at)
                                    <p class="text-xs text-red-500 mt-0.5">
                                        Habis sejak {{ $key->exhausted_at->diffForHumans() }}
                                    </p>
                                @endif
                            </td>

                            {{-- Masked key --}}
                            <td class="px-5 py-3">
                                <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded font-mono">
                                    {{ $key->maskedKey() }}
                                </code>
                            </td>

                            {{-- Status badge --}}
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5
                                             text-xs font-semibold {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                            </td>

                            {{-- last_used_at --}}
                            <td class="px-5 py-3 text-gray-400 text-xs">
                                {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : '—' }}
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button"
                                            @click="testKey({{ $key->id }})"
                                            :disabled="testingId === {{ $key->id }}"
                                            class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed transition-colors">
                                        <span x-show="testingId !== {{ $key->id }}">Uji</span>
                                        <span x-show="testingId === {{ $key->id }}">Menguji...</span>
                                    </button>

                                    {{-- Edit modal trigger --}}
                                    <button type="button"
                                            x-data
                                            @click="$dispatch('open-edit', {{ json_encode(['id'=>$key->id,'label'=>$key->label,'priority'=>$key->priority,'is_active'=>$key->is_active]) }})"
                                            class="text-xs text-gray-400 hover:text-brand-500 transition-colors">
                                        Edit
                                    </button>

                                    {{-- Reset exhausted --}}
                                    @if($key->is_exhausted)
                                    <form method="POST" action="{{ route('admin.api-keys.reset', $key) }}">
                                        @csrf
                                        <button type="submit"
                                                class="text-xs text-amber-500 hover:text-amber-400 transition-colors">
                                            Reset
                                        </button>
                                    </form>
                                    @endif

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('admin.api-keys.destroy', $key) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Hapus key ini?')"
                                                class="text-xs text-red-400 hover:text-red-300 transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @endif
    @endforeach

    @if($keys->isEmpty())
        <div class="rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800 p-10 text-center">
            <p class="text-5xl mb-4">🔑</p>
            <p class="text-gray-500 dark:text-gray-400">Belum ada API key. Tambahkan di bawah.</p>
        </div>
    @endif

    {{-- ── Tambah Key ──────────────────────────────────────────────────────── --}}
    <section>
        <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-3">
            Tambah API Key Baru
        </h2>
        <div class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-6">
            <form method="POST" action="{{ route('admin.api-keys.store') }}"
                  class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5">Label</label>
                          <input name="label" type="text" placeholder="Akun Gemini #1 / OpenRouter #1 / TinyMCE Cloud #1"
                           value="{{ old('label') }}" required
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700">
                    @error('label')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5">Provider</label>
                    <select name="provider"
                            class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                   bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700">
                        <option value="gemini" {{ old('provider','gemini')==='gemini'?'selected':'' }}>Google Gemini</option>
                        <option value="openai" {{ old('provider')==='openai'?'selected':'' }}>OpenAI</option>
                        <option value="openrouter" {{ old('provider')==='openrouter'?'selected':'' }}>OpenRouter</option>
                        <option value="tinymce" {{ old('provider')==='tinymce'?'selected':'' }}>TinyMCE Cloud</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5">
                        API Key
                    </label>
                    <input name="api_key" type="password" placeholder="AIza... / sk-... / tiny-cloud-key..."
                           required autocomplete="off"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm font-mono
                                  focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700">
                    @error('api_key')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5">
                        Priority <span class="text-gray-400 font-normal">(0 = dicoba pertama)</span>
                    </label>
                    <input name="priority" type="number" min="0" max="99"
                           value="{{ old('priority', 0) }}" required
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                                  bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700">
                </div>

                <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-500
                                   px-6 py-2.5 text-sm font-bold text-white transition-colors
                                   shadow-lg shadow-brand-900/20">
                        + Tambah API Key
                    </button>
                </div>
            </form>
        </div>
    </section>

</div>

{{-- ── Edit Modal ───────────────────────────────────────────────────────────── --}}
<div x-data="{
        open: false,
        key: {id: null, label: '', priority: 0, is_active: true},
    }"
     @open-edit.window="key = $event.detail; open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5)">

    <div @click.outside="open = false"
         class="w-full max-w-sm bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6">
        <h3 class="text-base font-bold text-gray-900 dark:text-white mb-5">Edit API Key</h3>

        <form method="POST" :action="`/admin/api-keys/${key.id}`" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5">Label</label>
                <input name="label" type="text" x-model="key.label" required
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1.5">Priority</label>
                <input name="priority" type="number" min="0" max="99" x-model="key.priority"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700
                              bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm
                              focus:outline-none focus:ring-2 focus:ring-brand-300 dark:focus:ring-brand-700">
            </div>

            <div class="flex items-center gap-2">
                <input name="is_active" type="checkbox" id="ea_active" x-model="key.is_active" value="1"
                       class="w-4 h-4 rounded text-brand-600">
                <label for="ea_active" class="text-sm text-gray-700 dark:text-gray-300">Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 rounded-xl bg-brand-600 hover:bg-brand-500 px-4 py-2
                               text-sm font-bold text-white transition-colors">
                    Simpan
                </button>
                <button type="button" @click="open = false"
                        class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700
                               px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400
                               hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('apiKeyDiagnostics', (config) => ({
        testingId: null,
        testResult: null,
        editorLoading: false,
        editorStatus: {
            ready: false,
            source: null,
            activeKeyId: null,
            activeKeyLabel: null,
            activeKeySource: null,
            lastError: null,
        },

        async boot() {
            await this.refreshEditorPreview();
        },

        providerLabel(provider) {
            return {
                gemini: 'Google Gemini',
                openai: 'OpenAI',
                openrouter: 'OpenRouter',
                tinymce: 'TinyMCE Cloud',
            }[provider] || provider;
        },

        editorSourceLabel() {
            if (!this.editorStatus.source) {
                return 'Source editor belum terdeteksi';
            }

            if (this.editorStatus.source === 'cloud') {
                return 'Source: TinyMCE Cloud';
            }

            return 'Source: TinyMCE Lokal';
        },

        async testKey(keyId) {
            this.testingId = keyId;

            try {
                const response = await fetch(config.testUrlTemplate.replace('__KEY__', String(keyId)), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({}),
                });

                const payload = await response.json();
                this.testResult = payload;

                if (payload.provider === 'tinymce') {
                    await this.refreshEditorPreview();
                }
            } catch (error) {
                this.testResult = {
                    ok: false,
                    provider: 'unknown',
                    label: 'Unknown',
                    latency_ms: null,
                    message: error?.message || 'Pengujian gagal dijalankan dari browser.',
                };
            } finally {
                this.testingId = null;
            }
        },

        async refreshEditorPreview() {
            this.editorLoading = true;

            try {
                if (window.PolkesbanRichText?.init) {
                    await window.PolkesbanRichText.init(this.$refs.editorCard);
                }

                this.editorStatus = window.PolkesbanRichText?.getRuntimeState?.() || {
                    ready: false,
                    source: null,
                    activeKeyId: null,
                    activeKeyLabel: null,
                    activeKeySource: null,
                    lastError: 'Runtime TinyMCE tidak tersedia.',
                };
            } catch (error) {
                this.editorStatus = {
                    ready: false,
                    source: null,
                    activeKeyId: null,
                    activeKeyLabel: null,
                    activeKeySource: null,
                    lastError: error?.message || 'Preview TinyMCE gagal dimuat.',
                };
            } finally {
                this.editorLoading = false;
            }
        },
    }));
});
</script>
@endpush
