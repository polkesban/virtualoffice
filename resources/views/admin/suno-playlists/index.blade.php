@extends('admin.layouts.admin')

@section('page-title', 'Suno Playlists')
@section('page-subtitle', 'Kelola daftar URL playlist lagu Suno untuk pemutar musik radio Poltekkes Bandung')

@section('content')

{{-- Realtime Sync Progress Card --}}
<div id="sync-progress-card" 
     class="hidden bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 flex flex-col gap-3 shadow-sm mb-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-xl bg-brand-50 dark:bg-brand-950/20 text-brand-600 dark:text-brand-400 flex items-center justify-center text-lg">
                🔄
            </div>
            <div>
                <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider">Sedang Sinkronisasi Playlist...</h3>
                <p id="sync-action-text" class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Memulai...</p>
            </div>
        </div>
        <span id="sync-percent-text" class="text-xs font-mono font-bold text-brand-600 dark:text-brand-400">0%</span>
    </div>
    
    {{-- Progress Bar --}}
    <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
        <div id="sync-progress-bar" class="h-full bg-brand-500 rounded-full transition-all duration-500" style="width: 0%;"></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    {{-- ── Tambah Playlist Card ─────────────────────────────────────────────────── --}}
    <div class="lg:col-span-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 flex flex-col justify-between self-start">
        <div>
            <div class="flex items-center gap-2 mb-5">
                <span class="text-xl" aria-hidden="true">📻</span>
                <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Tambah Playlist Baru</h2>
            </div>

            @if(session('error'))
                <div class="mb-4 rounded-xl bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 px-4 py-2.5 text-xs text-red-700 dark:text-red-300" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 px-4 py-2.5 text-xs text-green-700 dark:text-green-300" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('admin.suno-playlists.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="playlist-url" class="block text-xs font-bold text-gray-700 dark:text-gray-400 mb-1.5">
                        URL Playlist Suno *
                    </label>
                    <input id="playlist-url" name="url" type="url" required
                           placeholder="https://suno.com/playlist/..."
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 py-2.5 px-3.5 text-xs focus:border-brand-500 focus:outline-none dark:text-white transition-shadow focus:ring-2 focus:ring-brand-500/20">
                    <p class="mt-1.5 text-[10px] text-gray-400 dark:text-gray-500">
                        Masukkan URL playlist publik lengkap yang diambil dari situs Suno.com.
                    </p>
                </div>

                <button type="submit"
                        class="w-full inline-flex h-9 items-center justify-center rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-extrabold shadow-md hover:shadow-lg transition-all focus:outline-none">
                    ➕ Simpan & Sinkronisasi
                </button>
            </form>
        </div>

        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800">
            <h3 class="text-xs font-bold text-gray-900 dark:text-white mb-2">💡 Tips Menemukan URL Playlist</h3>
            <ol class="list-decimal list-inside text-[10px] text-gray-500 dark:text-gray-400 space-y-1">
                <li>Buka akun Anda di <a href="https://suno.com" target="_blank" class="text-brand-600 hover:underline">Suno.com</a>.</li>
                <li>Pilih menu <strong>Playlists</strong> di halaman profil Anda.</li>
                <li>Klik ikon <strong>Share</strong> atau salin URL playlist di bilah pencarian peramban.</li>
            </ol>
        </div>
    </div>

    {{-- ── Daftar Playlist Card ─────────────────────────────────────────────────── --}}
    <div class="lg:col-span-8 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between gap-4 mb-5">
                <div class="flex items-center gap-2">
                    <span class="text-xl" aria-hidden="true">🎼</span>
                    <h2 class="text-sm font-extrabold text-gray-900 dark:text-white">Daftar Playlist Aktif</h2>
                </div>
                
                {{-- Sync Now Action --}}
                <form action="{{ route('admin.suno-playlists.sync') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 px-3 text-[11px] font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 transition-all">
                        🔄 Sinkronkan Semua
                    </button>
                </form>
            </div>

            @if(empty($playlists))
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <span class="text-4xl mb-3">🎵</span>
                    <p class="text-xs font-bold text-gray-400">Belum ada playlist Suno yang terdaftar.</p>
                    <p class="text-[10px] text-gray-400">Silakan tambahkan playlist baru di panel kiri.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800/80">
                                <th class="pb-3 font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-12 text-center">No</th>
                                <th class="pb-3 font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">URL Playlist</th>
                                <th class="pb-3 font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/40">
                            @foreach($playlists as $index => $playlist)
                                <tr>
                                    <td class="py-4 font-bold text-gray-400 text-center">{{ $index + 1 }}</td>
                                    <td class="py-4 font-medium text-gray-900 dark:text-white truncate max-w-md">
                                        <a href="{{ $playlist }}" target="_blank" rel="noopener noreferrer" class="hover:text-brand-600 hover:underline">
                                            {{ $playlist }}
                                        </a>
                                    </td>
                                    <td class="py-4 text-center">
                                        <form action="{{ route('admin.suno-playlists.destroy') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus playlist ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="url" value="{{ $playlist }}">
                                            <button type="submit"
                                                    class="inline-flex h-7 items-center justify-center rounded-lg bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-950/60 px-3 text-[10px] font-bold text-red-600 dark:text-red-400 transition-colors">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 text-[10px] text-gray-400 flex items-center justify-between gap-4">
            <span>Menampilkan {{ count($playlists) }} playlist terdaftar</span>
            <span>Semua lagu yang dimuat akan disimpan di cache agar pemutaran tetap lancar.</span>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const progressCard = document.getElementById('sync-progress-card');
        const actionText = document.getElementById('sync-action-text');
        const percentText = document.getElementById('sync-percent-text');
        const progressBar = document.getElementById('sync-progress-bar');
        const iconEl = progressCard.querySelector('div.h-9');
        
        let pollInterval = null;

        function checkStatus() {
            fetch("{{ route('admin.suno-playlists.status') }}")
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'running') {
                        progressCard.classList.remove('hidden');
                        iconEl.classList.add('animate-spin');
                        
                        const addedInfo = (data.added_songs > 0) ? ` (+${data.added_songs} lagu baru)` : '';
                        actionText.textContent = data.current_action + addedInfo;
                        
                        let percent = 0;
                        if (data.total_songs > 0) {
                            percent = Math.round((data.processed_songs / data.total_songs) * 100);
                        }
                        
                        percentText.textContent = percent + '%';
                        progressBar.style.width = percent + '%';
                        
                        // Start polling if not already
                        if (!pollInterval) {
                            pollInterval = setInterval(checkStatus, 2000);
                        }
                    } else {
                        // If it completed recently, reload page to update counts, otherwise hide
                        const isRecentlyCompleted = data.status === 'completed' && 
                            (new Date() - new Date(data.updated_at)) < 15000;
                        
                        if (isRecentlyCompleted) {
                            progressCard.classList.remove('hidden');
                            iconEl.classList.remove('animate-spin');
                            iconEl.textContent = '✅';
                            
                            const addedStr = (data.added_songs > 0) 
                                ? `Sinkronisasi Selesai! Berhasil menambahkan ${data.added_songs} lagu baru.`
                                : `Sinkronisasi Selesai! Semua lagu sudah up-to-date (tidak ada penambahan lagu baru).`;
                                
                            actionText.textContent = `${addedStr} Memuat ulang...`;
                            progressBar.style.width = '100%';
                            percentText.textContent = '100%';
                            
                            if (pollInterval) {
                                clearInterval(pollInterval);
                            }
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        } else {
                            progressCard.classList.add('hidden');
                            if (pollInterval) {
                                clearInterval(pollInterval);
                                pollInterval = null;
                            }
                        }
                    }
                })
                .catch(err => console.error('Status fetch failed:', err));
        }

        // Initial check and start polling
        checkStatus();
        
        // Also check periodically in case it gets triggered elsewhere
        setInterval(checkStatus, 5000);
    });
</script>
@endpush
