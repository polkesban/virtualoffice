@extends('admin.layouts.admin')
@section('title', 'Detail Pengaduan — ' . $pengaduan->nomor_tiket)

@section('content')
<div class="p-6 w-full">

    {{-- Breadcrumb --}}
    <div class="mb-4 flex items-center gap-2 text-xs text-gray-400">
        @if($page)
        <a href="{{ route('admin.pengaduan.unit.index', $page) }}" class="hover:text-brand-600 transition-colors">
            ← Pengaduan {{ $page->judul }}
        </a>
        @else
        <a href="{{ route('admin.pengaduan.index') }}" class="hover:text-brand-600 transition-colors">
            ← Rekap Pengaduan
        </a>
        @endif
        <span>/</span>
        <span class="font-mono text-brand-600">{{ $pengaduan->nomor_tiket }}</span>
    </div>

    @if(session('success'))
    <div class="mb-4 rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Left: Complaint Detail --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Header card --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ $pengaduan->jenisIcon() }}</span>
                        <div>
                            <p class="font-extrabold text-gray-900 dark:text-white text-base">
                                {{ $pengaduan->subjek }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $pengaduan->jenisLabel() }} · {{ $pengaduan->nomor_tiket }}</p>
                        </div>
                    </div>
                    @php
                        $statusClass = [
                            'masuk'    => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
                            'diproses' => 'bg-blue-50 text-blue-700 border border-blue-200',
                            'selesai'  => 'bg-green-50 text-green-700 border border-green-200',
                            'ditolak'  => 'bg-red-50 text-red-600 border border-red-200',
                        ][$pengaduan->status] ?? 'bg-gray-50 text-gray-600';
                    @endphp
                    <span class="shrink-0 px-3 py-1 rounded-full text-xs font-bold {{ $statusClass }}">
                        {{ $pengaduan->statusLabel() }}
                    </span>
                </div>

                <div class="px-6 py-5">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Isi Pesan</h2>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $pengaduan->isi }}</div>
                </div>

                @if($pengaduan->file_lampiran)
                <a href="#pratinjau-lampiran" class="px-6 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                    <span class="text-base">📁</span>
                    <span class="text-xs text-brand-600 dark:text-brand-400 font-semibold hover:underline">
                        Memiliki lampiran berkas. Klik untuk lihat pratinjau lengkap di bawah halaman.
                    </span>
                </a>
                @endif
            </div>

            {{-- Tindak lanjut form --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h2 class="font-bold text-gray-900 dark:text-white">⚡ Aksi / Tindak Lanjut</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Perbarui status dan tambahkan catatan tindak lanjut</p>
                </div>
                <form method="POST"
                      action="{{ $page
                          ? route('admin.pengaduan.unit.update', [$page, $pengaduan])
                          : route('admin.pengaduan.update', $pengaduan) }}"
                      class="px-6 py-5 space-y-4">
                    @csrf @method('PUT')

                    {{-- Status --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-2">Status</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach([
                                ['val'=>'masuk',    'label'=>'Masuk',    'class'=>'border-yellow-300 bg-yellow-50 text-yellow-700'],
                                ['val'=>'diproses', 'label'=>'Diproses', 'class'=>'border-blue-300 bg-blue-50 text-blue-700'],
                                ['val'=>'selesai',  'label'=>'Selesai',  'class'=>'border-green-300 bg-green-50 text-green-700'],
                                ['val'=>'ditolak',  'label'=>'Ditolak',  'class'=>'border-red-300 bg-red-50 text-red-600'],
                            ] as $s)
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="{{ $s['val'] }}"
                                       class="peer sr-only"
                                       {{ $pengaduan->status === $s['val'] ? 'checked' : '' }}>
                                <div class="px-4 py-2 rounded-xl border-2 text-sm font-bold transition-all
                                            peer-checked:ring-2 peer-checked:ring-offset-1 peer-checked:ring-brand-400
                                            {{ $s['class'] }}">
                                    {{ $s['label'] }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tindak lanjut --}}
                    <div>
                        <label for="tindak_lanjut" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                            Tindak Lanjut
                            <span class="text-xs font-normal text-gray-400">(dapat dilihat oleh pelapor)</span>
                        </label>
                        <textarea id="tindak_lanjut" name="tindak_lanjut" rows="4"
                                  placeholder="Jelaskan tindakan yang telah atau akan dilakukan..."
                                  class="w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200 outline-none transition resize-none">{{ $pengaduan->tindak_lanjut }}</textarea>
                    </div>

                    {{-- Catatan internal --}}
                    <div>
                        <label for="catatan_internal" class="block text-sm font-bold text-gray-700 dark:text-gray-200 mb-1.5">
                            Catatan Internal
                            <span class="text-xs font-normal text-gray-400">(hanya terlihat admin/unit)</span>
                        </label>
                        <textarea id="catatan_internal" name="catatan_internal" rows="3"
                                  placeholder="Catatan untuk tim internal..."
                                  class="w-full rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-200 outline-none transition resize-none">{{ $pengaduan->catatan_internal }}</textarea>
                    </div>

                    <div class="flex items-center justify-between gap-4 pt-2">
                        <button type="submit"
                                class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm transition-colors shadow-sm">
                            Simpan Perubahan
                        </button>

                        @if(auth()->user()->isSuperAdmin())
                        <form method="POST"
                              action="{{ $page
                                  ? route('admin.pengaduan.unit.destroy', [$page, $pengaduan])
                                  : route('admin.pengaduan.destroy', $pengaduan) }}"
                              onsubmit="return confirm('Hapus pengaduan ini?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-600 font-bold text-sm transition-colors">
                                Hapus
                            </button>
                        </form>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Right: Metadata --}}
        <div class="space-y-4">

            {{-- Pengirim --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Pengirim</h3>
                <div class="space-y-2">
                    <div>
                        <p class="text-xs text-gray-400">Nama</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $pengaduan->anonim ? '— Anonim —' : $pengaduan->nama }}
                        </p>
                    </div>
                    @if(!$pengaduan->anonim && $pengaduan->email)
                    <div>
                        <p class="text-xs text-gray-400">Email</p>
                        <a href="mailto:{{ $pengaduan->email }}"
                           class="text-sm font-semibold text-brand-700 hover:underline">
                            {{ $pengaduan->email }}
                        </a>
                    </div>
                    @endif
                    @if(!$pengaduan->anonim && $pengaduan->telepon)
                    <div>
                        <p class="text-xs text-gray-400">Telepon</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $pengaduan->telepon }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Tujuan & Waktu --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Informasi</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-400">Ditujukan kepada</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $pengaduan->tujuan_label }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Jenis</p>
                        <p class="text-sm font-semibold">{{ $pengaduan->jenisIcon() }} {{ $pengaduan->jenisLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Dikirim</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $pengaduan->created_at->format('d M Y, H:i') }}</p>
                        <p class="text-xs text-gray-400">{{ $pengaduan->created_at->diffForHumans() }}</p>
                    </div>
                    @if($pengaduan->dibaca_at)
                    <div>
                        <p class="text-xs text-gray-400">Dibaca</p>
                        <p class="text-xs text-gray-500">{{ $pengaduan->dibaca_at->format('d M Y, H:i') }}</p>
                    </div>
                    @endif
                    @if($pengaduan->ditangani_at)
                    <div>
                        <p class="text-xs text-gray-400">Ditangani</p>
                        <p class="text-xs text-gray-500">{{ $pengaduan->ditangani_at->format('d M Y, H:i') }}</p>
                        @if($pengaduan->penanggungJawab)
                        <p class="text-xs text-brand-600">oleh {{ $pengaduan->penanggungJawab->name }}</p>
                        @endif
                    </div>
                    @endif
                    @if($pengaduan->diselesaikan_at)
                    <div>
                        <p class="text-xs text-gray-400">Diselesaikan</p>
                        <p class="text-xs text-green-600">{{ $pengaduan->diselesaikan_at->format('d M Y, H:i') }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Tindak lanjut (read-only preview) --}}
            @if($pengaduan->tindak_lanjut)
            <div class="bg-green-50 dark:bg-green-950/20 rounded-2xl border border-green-200 dark:border-green-900 p-5">
                <h3 class="text-xs font-bold uppercase tracking-widest text-green-700 dark:text-green-400 mb-3">Tindak Lanjut</h3>
                <p class="text-sm text-green-800 dark:text-green-200 whitespace-pre-wrap leading-relaxed">{{ $pengaduan->tindak_lanjut }}</p>
            </div>
            @endif

            @if($pengaduan->catatan_internal)
            <div class="bg-amber-50 dark:bg-amber-950/20 rounded-2xl border border-amber-200 dark:border-amber-900 p-5">
                <h3 class="text-xs font-bold uppercase tracking-widest text-amber-700 dark:text-amber-400 mb-3">Catatan Internal</h3>
                <p class="text-sm text-amber-800 dark:text-amber-200 whitespace-pre-wrap leading-relaxed">{{ $pengaduan->catatan_internal }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Full-Width Attachment Preview --}}
    @if($pengaduan->file_lampiran)
    @php
        $extension = strtolower(pathinfo($pengaduan->file_lampiran, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $isPdf = $extension === 'pdf';
        
        $previewUrl = $pengaduan->file_lampiran_url;
        if (\App\Support\MediaStorage::isGoogleReference($pengaduan->file_lampiran)) {
            $parsed = \App\Support\MediaStorage::parseGoogleReference($pengaduan->file_lampiran);
            $previewUrl = 'https://drive.google.com/file/d/' . $parsed['id'] . '/preview';
        }
    @endphp
    <div id="pratinjau-lampiran" class="mt-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>📁</span> Pratinjau Lampiran Berkas
            </h2>
            <a href="{{ $pengaduan->file_lampiran_url }}" target="_blank" class="text-xs font-bold bg-brand-50 hover:bg-brand-100 text-brand-700 px-3 py-1.5 rounded-xl transition-colors">
                ↗ Buka di Tab Baru / Unduh
            </a>
        </div>
        <div class="p-6 bg-gray-50 dark:bg-gray-950/40">
            @if($isImage)
                <div class="flex justify-center">
                    <img src="{{ $pengaduan->file_lampiran_url }}" alt="Lampiran" class="max-w-full h-auto max-h-[850px] object-contain rounded-xl shadow-md">
                </div>
            @elseif($isPdf)
                <div class="w-full rounded-xl overflow-hidden shadow-md border border-gray-200 dark:border-gray-800" style="height: 1150px;">
                    <iframe src="{{ $previewUrl }}" class="w-full h-full border-none" allow="autoplay"></iframe>
                </div>
            @else
                <div class="p-8 text-center bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-200 dark:border-gray-800">
                    <p class="text-3xl mb-2">📦</p>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">File berkas tidak dapat dipratinjau secara langsung.</p>
                    <p class="text-xs text-gray-400 mt-1">Silakan unduh atau buka berkas melalui tombol di kanan atas.</p>
                </div>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
