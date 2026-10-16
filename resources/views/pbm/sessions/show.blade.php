@extends('pbm.layouts.app')

@section('content')
<div class="mb-4">
    <a href="{{ route('pbm.classes.show', $class) }}" class="text-sky-600 hover:underline text-sm flex items-center gap-1 mb-2">
        <i data-feather="arrow-left" class="w-4 h-4"></i> Kembali ke Kelas
    </a>
</div>

<!-- Overlap Warning -->
@if($hasOverlap)
<div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl mb-6 shadow-sm">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <i data-feather="alert-triangle" class="w-5 h-5 text-red-500"></i>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-bold text-red-800">Peringatan Bentrok Jadwal!</h3>
            <div class="mt-1 text-sm text-red-700">
                <p>Anda memiliki jadwal sesi lain yang waktunya bersamaan atau beririsan dengan sesi ini:</p>
                <ul class="list-disc pl-5 mt-1">
                    @foreach($overlapSessions as $ov)
                    <li>
                        <strong>{{ $ov->title }}</strong> di kelas {{ $ov->clinicalClass->name }} 
                        ({{ $ov->start_time->format('H:i') }} - {{ $ov->end_time->format('H:i') }})
                    </li>
                    @endforeach
                </ul>
                <p class="mt-2 font-medium">Sistem tidak memblokir sesi ini, namun mohon perhatikan pembagian waktu Anda.</p>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Flash Messages -->
@if(session('success'))
<div class="bg-green-50 border-l-4 border-green-500 p-3 rounded-r-xl mb-4 text-green-800 text-sm font-medium flex items-center justify-between">
    <div>
        <i data-feather="check-circle" class="w-4 h-4 inline mr-1 text-green-600"></i> {{ session('success') }}
    </div>
</div>
@endif
@if(session('error'))
<div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-r-xl mb-4 text-red-800 text-sm font-medium flex items-center justify-between">
    <div>
        <i data-feather="alert-circle" class="w-4 h-4 inline mr-1 text-red-600"></i> {{ session('error') }}
    </div>
</div>
@endif
@if($errors->any())
<div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl mb-4 text-red-800 text-sm">
    <div class="font-bold flex items-center gap-1.5 mb-1 text-red-900">
        <i data-feather="alert-triangle" class="w-4 h-4 text-red-600"></i> Terjadi kesalahan saat menyimpan data:
    </div>
    <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Session Banner -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-start gap-4">
            <div>
                <span class="bg-sky-100 text-sky-700 text-xs px-2 py-1 rounded-md font-bold uppercase tracking-wider mb-2 inline-block">SESI PERTEMUAN</span>
                <h1 class="text-2xl font-bold text-gray-900">{{ $session->title }}</h1>
                <p class="text-gray-500 mt-1 flex items-center gap-2">
                    <i data-feather="book-open" class="w-4 h-4"></i> {{ $class->name }}
                </p>
            </div>
            @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
            <div class="flex flex-wrap sm:flex-nowrap gap-2 shrink-0 w-full sm:w-auto">
                <a href="{{ route('pbm.classes.sessions.edit', [$class->id, $session->id]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition flex items-center justify-center gap-2 flex-1 sm:flex-none">
                    <i data-feather="edit" class="w-4 h-4"></i> Edit Sesi
                </a>
                <button onclick="document.getElementById('qrModal').classList.remove('hidden')" class="pbm-primary px-4 py-2 rounded-lg text-sm font-medium shadow-sm hover:opacity-90 flex items-center justify-center gap-2 flex-1 sm:flex-none">
                    <i data-feather="play-circle" class="w-4 h-4"></i> Mulai Kelas
                </button>
            </div>
            @endif
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 pt-6 border-t border-gray-100">
            <div>
                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Waktu Pelaksanaan</h4>
                <p class="font-medium text-gray-900 mt-1 flex items-center gap-2">
                    <i data-feather="calendar" class="w-4 h-4 text-sky-600"></i>
                    {{ $session->start_time->format('d M Y') }}
                </p>
                <p class="text-gray-600 text-sm mt-1">
                    {{ $session->start_time->format('H:i') }} - {{ $session->end_time->format('H:i') }} WIB
                </p>
            </div>
            <div>
                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Lokasi & Shift</h4>
                @if($session->location_name)
                <p class="font-medium text-gray-900 mt-1 flex items-center gap-2">
                    <i data-feather="map-pin" class="w-4 h-4 text-sky-600"></i>
                    {{ $session->location_name }}
                </p>
                @endif
                
                @if(!empty($session->lokasi_praktik))
                    <div class="mt-2">
                        <span class="text-[10px] uppercase font-bold text-gray-400">Pilihan Lokasi:</span>
                        <ul class="text-xs text-gray-700 list-disc pl-4 mt-0.5">
                            @foreach($session->lokasi_praktik as $loc)
                                <li>{{ $loc }}</li>
                            @endforeach
                        </ul>
                    </div>
                @elseif(!$session->location_name)
                <p class="font-medium text-gray-900 mt-1 flex items-center gap-2">
                    <i data-feather="map-pin" class="w-4 h-4 text-gray-400"></i>
                    Lokasi Bebas
                </p>
                @endif
                
                @if(!empty($session->shifts))
                    <div class="mt-2">
                        <span class="text-[10px] uppercase font-bold text-gray-400">Pilihan Shift:</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($session->shifts as $shift)
                                <span class="bg-indigo-50 border border-indigo-100 text-indigo-700 text-[10px] px-1.5 py-0.5 rounded font-medium">{{ $shift['name'] }} ({{ $shift['start'] }}-{{ $shift['end'] }})</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2 mt-3">
                    @if($session->require_qr)
                    <span class="bg-gray-100 text-gray-600 text-[10px] px-1.5 py-0.5 rounded flex items-center gap-1 font-medium"><i data-feather="camera" class="w-3 h-3"></i> Wajib QR</span>
                    @endif
                    @if($session->require_gps)
                    <span class="bg-gray-100 text-gray-600 text-[10px] px-1.5 py-0.5 rounded flex items-center gap-1 font-medium"><i data-feather="navigation" class="w-3 h-3"></i> Wajib GPS</span>
                    @endif
                </div>
            </div>
            <div>
                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Rubrik Penilaian</h4>
                @if($session->evaluation_rubric_id && $session->evaluationRubric)
                    <p class="font-medium text-gray-900 mt-1 flex items-center gap-2">
                        <i data-feather="check-square" class="w-4 h-4 text-sky-600"></i>
                        {{ $session->evaluationRubric->judul }}
                    </p>
                    <button onclick="document.getElementById('viewRubricModal').classList.remove('hidden')" class="text-sky-600 text-sm font-medium mt-1 hover:underline">Lihat Rubrik</button>
                    @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
                    <form action="{{ route('pbm.classes.sessions.detach-rubric', [$class->id, $session->id]) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 text-xs font-medium mt-1 hover:underline ml-2">Hapus Rubrik</button>
                    </form>
                    @endif
                @else
                    <p class="text-gray-500 mt-1 italic text-sm">Belum ada rubrik</p>
                    @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
                    <button onclick="document.getElementById('addRubricModal').classList.remove('hidden')" class="text-sky-600 text-sm font-medium mt-1 hover:underline">Tambah Rubrik</button>
                    @endif
                @endif
            </div>
        </div>

        @if($session->description)
        <div class="mt-6 pt-6 border-t border-gray-100">
            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Deskripsi / Instruksi</h4>
            <div class="text-gray-700 text-sm prose max-w-none">
                {{ $session->description }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Materi & Media Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <i data-feather="folder" class="w-5 h-5 text-sky-600"></i> Materi & Media
        </h2>
        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
        <button onclick="document.getElementById('addMaterialModal').classList.remove('hidden')" class="px-3 py-1.5 pbm-primary text-white rounded-lg text-sm font-medium hover:opacity-90 transition flex items-center justify-center gap-2 shrink-0">
            <i data-feather="plus" class="w-4 h-4"></i> Tambah Materi
        </button>
        @endif
    </div>
    <div class="p-5">
        @if($session->materials->count() > 0)
            <div class="space-y-3">
                @foreach($session->materials as $material)
                @php
                    $targetUrl = $material->effective_url ?? $material->url;
                    $embedUrl = $material->embed_url;
                    $embedType = $material->embed_type;
                    $isEmbeddable = !empty($embedUrl);
                @endphp
                <div x-data="{ expanded: false, isFullscreen: false, docHeight: 880 }" @keydown.escape.window="if(isFullscreen) isFullscreen = false" class="p-3.5 border border-gray-200 rounded-xl hover:bg-gray-50/50 transition bg-white shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="mt-0.5 p-2 rounded-lg bg-gray-100 text-gray-600 shrink-0">
                                @if($material->type == 'document')
                                    <i data-feather="file-text" class="w-5 h-5 text-red-500"></i>
                                @elseif($material->type == 'video')
                                    <i data-feather="video" class="w-5 h-5 text-purple-500"></i>
                                @else
                                    <i data-feather="link" class="w-5 h-5 text-sky-500"></i>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-semibold text-gray-900 text-sm truncate">{{ $material->title }}</h4>
                                    @if($material->type == 'document')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">Dokumen</span>
                                    @elseif($material->type == 'video')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200">Video</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-sky-50 text-sky-700 border border-sky-200">Tautan</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Oleh: {{ $material->creator->name ?? 'Pengajar' }} • Ditambahkan: {{ $material->created_at ? $material->created_at->format('d M Y, H:i') : '-' }}
                                </p>
                                @if(!empty($material->description))
                                    <p class="text-xs text-gray-700 mt-2 bg-gray-50 p-2.5 rounded-lg border border-gray-100 whitespace-pre-line leading-relaxed">{{ $material->description }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            @if($isEmbeddable)
                            <button @click="expanded = !expanded" type="button" class="px-2.5 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 transition flex items-center gap-1.5 shadow-2xs">
                                <i data-feather="tv" class="w-3.5 h-3.5 text-sky-600"></i>
                                <span x-text="expanded ? 'Tutup Embed' : 'Tampilkan Embed'">Tampilkan Embed</span>
                            </button>
                            @endif

                            @if($material->file_path)
                            <a href="{{ route('pbm.materials.stream', $material->id) }}" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1.5 bg-sky-50 border border-sky-200 text-sky-700 rounded-lg text-xs font-medium hover:bg-sky-100 transition flex items-center gap-1.5">
                                <i data-feather="external-link" class="w-3.5 h-3.5"></i>
                                <span>Buka Dokumen</span>
                            </a>
                            @elseif($targetUrl)
                            <a href="{{ $targetUrl }}" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1.5 bg-sky-50 border border-sky-200 text-sky-700 rounded-lg text-xs font-medium hover:bg-sky-100 transition flex items-center gap-1.5">
                                <i data-feather="external-link" class="w-3.5 h-3.5"></i>
                                <span>Buka Tautan</span>
                            </a>
                            @endif

                            @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum', 'admin']) || Auth::id() === $material->created_by))
                            <form action="{{ route('pbm.materials.destroy', $material->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi \'{{ addslashes($material->title) }}\'?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus Materi">
                                    <i data-feather="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>

                    @if($isEmbeddable)
                    <div x-show="expanded" class="mt-4 border-t border-gray-100 pt-3">
                        @if($embedType === 'youtube')
                        <div :class="isFullscreen ? 'fixed inset-0 z-50 bg-black/95 p-2 sm:p-6 flex flex-col items-center justify-center backdrop-blur-md' : 'w-full rounded-xl overflow-hidden bg-black border border-gray-200 shadow-sm'">
                            <div :class="isFullscreen ? 'w-full h-full max-w-6xl flex flex-col' : 'w-full'">
                                <div class="flex items-center justify-between px-3 py-2 bg-gray-900 text-white text-xs shrink-0">
                                    <span class="font-medium truncate">{{ $material->title }}</span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="isFullscreen = !isFullscreen" class="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded-lg transition flex items-center gap-1">
                                            <span x-text="isFullscreen ? 'Kecilkan' : 'Perlebar / Layar Penuh'"></span>
                                        </button>
                                        <a href="{{ $embedUrl }}" target="_blank" class="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">Tab Baru</a>
                                    </div>
                                </div>
                                <div class="w-full aspect-video flex-1">
                                    <template x-if="expanded">
                                        <iframe src="{{ $embedUrl }}" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                    </template>
                                </div>
                            </div>
                        </div>
                        @elseif($embedType === 'video')
                        <div :class="isFullscreen ? 'fixed inset-0 z-50 bg-black/95 p-2 sm:p-6 flex flex-col items-center justify-center backdrop-blur-md' : 'w-full rounded-xl overflow-hidden bg-black border border-gray-200 shadow-sm'">
                            <div :class="isFullscreen ? 'w-full h-full max-w-6xl flex flex-col' : 'w-full'">
                                <div class="flex items-center justify-between px-3 py-2 bg-gray-900 text-white text-xs shrink-0">
                                    <span class="font-medium truncate">{{ $material->title }}</span>
                                    <button type="button" @click="isFullscreen = !isFullscreen" class="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">
                                        <span x-text="isFullscreen ? 'Kecilkan' : 'Perlebar / Layar Penuh'"></span>
                                    </button>
                                </div>
                                <div class="w-full flex-1 flex items-center justify-center bg-black">
                                    <template x-if="expanded">
                                        <video controls class="w-full max-h-[85vh]">
                                            <source src="{{ $targetUrl }}">
                                            Browser Anda tidak mendukung pemutaran video langsung.
                                        </video>
                                    </template>
                                </div>
                            </div>
                        </div>
                        @elseif($embedType === 'image')
                        <div :class="isFullscreen ? 'fixed inset-0 z-50 bg-black/95 p-2 sm:p-6 flex flex-col items-center justify-center backdrop-blur-md' : 'w-full rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-gray-50 flex items-center justify-center p-3'">
                            <img src="{{ $targetUrl }}" alt="{{ $material->title }}" class="max-h-[85vh] max-w-full object-contain rounded-lg">
                        </div>
                        @elseif($embedType === 'document')
                        <div :class="isFullscreen ? 'fixed inset-0 z-50 bg-gray-950/85 backdrop-blur-md p-2 sm:p-4 flex flex-col items-center justify-center' : 'w-full rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-white'">
                            <div :class="isFullscreen ? 'w-full h-full max-w-[98vw] max-h-[98vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200' : 'w-full flex flex-col'">
                                <div class="flex flex-wrap items-center justify-between px-4 py-2.5 bg-gray-50 border-b border-gray-200 text-xs text-gray-700 gap-2 shrink-0">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i data-feather="file-text" class="w-4 h-4 text-red-500 shrink-0"></i>
                                        <span class="font-semibold text-gray-900 truncate">{{ $material->title }}</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                                        <!-- Toggle Height (Slide 520px / 1 Halaman A4 880px / Panjang 1250px) -->
                                        <button x-show="!isFullscreen" type="button" @click="docHeight = (docHeight === 520 ? 880 : (docHeight === 880 ? 1250 : 520))" 
                                                class="px-2.5 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs font-medium transition flex items-center gap-1.5 shadow-2xs"
                                                :title="'Tinggi saat ini: ' + docHeight + 'px. Klik untuk beralih mode tinggi.'">
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                            <span x-text="docHeight === 520 ? 'Tinggi: Slide (520px)' : (docHeight === 880 ? 'Tinggi: 1 Hal (880px)' : 'Tinggi: Panjang (1250px)')"></span>
                                        </button>

                                        <!-- Toggle Fullscreen / Ekspansi Layar Penuh -->
                                        <button type="button" @click="isFullscreen = !isFullscreen" 
                                                class="px-3 py-1.5 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition shadow-2xs"
                                                :class="isFullscreen ? 'bg-amber-50 border-amber-300 text-amber-900 hover:bg-amber-100' : 'bg-sky-50 border-sky-200 text-sky-700 hover:bg-sky-100'">
                                            <svg x-show="!isFullscreen" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                            <svg x-show="isFullscreen" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span x-text="isFullscreen ? 'Kecilkan (Esc)' : 'Ekspansi / Layar Penuh'">Ekspansi / Layar Penuh</span>
                                        </button>

                                        @if($material->file_path)
                                        <!-- Buka File PDF Bawaan Browser -->
                                        <a href="{{ route('pbm.materials.stream', $material->id) }}" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-medium transition flex items-center gap-1 shadow-2xs" title="Buka file PDF langsung di browser">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>PDF Browser</span>
                                        </a>
                                        @endif

                                        <!-- Tab Baru -->
                                        <a href="{{ $embedUrl }}" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs font-medium transition flex items-center gap-1 shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            <span>Tab Baru</span>
                                        </a>

                                        @if($targetUrl)
                                        <!-- Unduh Berkas -->
                                        <a href="{{ $targetUrl }}" download class="px-2.5 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs font-medium transition flex items-center gap-1 shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            <span>Unduh</span>
                                        </a>
                                        @endif
                                    </div>
                                </div>
                                <div class="w-full flex-1 bg-gray-100" :style="isFullscreen ? 'width: 100%; height: calc(100% - 50px);' : 'width: 100%; height: ' + docHeight + 'px; min-height: ' + docHeight + 'px;'">
                                    <template x-if="expanded">
                                        <iframe src="{{ $embedUrl }}" class="border-0" style="width: 100%; height: 100%; display: block;" frameborder="0" allow="autoplay; encrypted-media; fullscreen" allowfullscreen></iframe>
                                    </template>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <i data-feather="folder-minus" class="w-8 h-8 mx-auto text-gray-300 mb-2"></i>
                <p class="text-sm">Belum ada materi untuk sesi ini.</p>
            </div>
        @endif
    </div>
</div>

<!-- Aktivitas Section (Tugas & Kuis) -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
            <i data-feather="activity" class="w-5 h-5 text-orange-500"></i> Aktivitas Pembelajaran
        </h2>
        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
        <div class="flex flex-wrap sm:flex-nowrap gap-2 shrink-0">
            <button onclick="document.getElementById('addAssignmentModal').classList.remove('hidden')" class="px-3 py-1.5 bg-orange-50 text-orange-600 border border-orange-200 rounded-lg text-sm font-medium hover:bg-orange-100 transition flex items-center gap-2">
                <i data-feather="file-plus" class="w-4 h-4"></i> Tambah Tugas
            </button>
            <button onclick="document.getElementById('addQuizModal').classList.remove('hidden')" class="px-3 py-1.5 bg-purple-50 text-purple-600 border border-purple-200 rounded-lg text-sm font-medium hover:bg-purple-100 transition flex items-center gap-2">
                <i data-feather="help-circle" class="w-4 h-4"></i> Tambah Kuis
            </button>
        </div>
        @endif
    </div>
    <div class="p-5">
        @if($session->assignments->count() > 0 || $session->quizzes->count() > 0)
            <div class="space-y-4">
                {{-- Tugas List --}}
                @foreach($session->assignments as $assignment)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 border border-orange-100 bg-orange-50/30 rounded-lg hover:bg-orange-50/70 transition">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 p-2 bg-orange-100 rounded-lg text-orange-600 shrink-0">
                            <i data-feather="edit-3" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $assignment->title }}</h4>
                            <p class="text-sm text-gray-600 mt-1 line-clamp-1">{{ $assignment->description }}</p>
                            <p class="text-xs text-orange-700 mt-2 font-medium">
                                <i data-feather="clock" class="w-3 h-3 inline mr-1"></i> Tenggat: {{ $assignment->due_date ? $assignment->due_date->format('d M Y, H:i') : 'Tidak ada' }}
                            </p>
                        </div>
                    </div>
                    <a href="#" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition shrink-0 shadow-sm text-center">
                        Lihat Tugas
                    </a>
                </div>
                @endforeach
                
                {{-- Kuis List --}}
                @foreach($session->quizzes as $quiz)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 border border-purple-100 bg-purple-50/30 rounded-lg hover:bg-purple-50/70 transition">
                    <div class="flex items-start gap-3">
                        <div class="mt-1 p-2 bg-purple-100 rounded-lg text-purple-600 shrink-0">
                            <i data-feather="help-circle" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $quiz->title }}</h4>
                            <p class="text-sm text-gray-600 mt-1 line-clamp-1">{{ $quiz->description }}</p>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                                <p class="text-xs text-purple-700 font-medium">
                                    <i data-feather="clock" class="w-3 h-3 inline mr-1"></i> Waktu: {{ $quiz->start_time ? $quiz->start_time->format('d M, H:i') : 'Bebas' }}
                                </p>
                                <p class="text-xs text-purple-700 font-medium">
                                    <i data-feather="watch" class="w-3 h-3 inline mr-1"></i> Durasi: {{ $quiz->duration_minutes ? $quiz->duration_minutes . ' Menit' : 'Bebas' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex sm:flex-col gap-2 shrink-0">
                        <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" class="px-4 py-2 pbm-primary text-white rounded-lg text-sm font-medium hover:opacity-90 transition text-center shadow-sm flex-1 sm:flex-none">
                            Masuk Kuis
                        </a>
                        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
                        <a href="{{ route('pbm.quizzes.analysis', $quiz->id) }}" class="px-4 py-1.5 bg-white border border-gray-300 text-gray-600 rounded-lg text-xs font-medium hover:bg-gray-50 transition text-center flex items-center gap-1 justify-center flex-1 sm:flex-none">
                            <i data-feather="bar-chart-2" class="w-3 h-3"></i> Analisis Butir
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <i data-feather="inbox" class="w-8 h-8 mx-auto text-gray-300 mb-2"></i>
                <p class="text-sm">Belum ada aktivitas (Tugas/Kuis) untuk sesi ini.</p>
            </div>
        @endif
    </div>
</div>

<!-- Attendance & Grading List -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
        <h2 class="text-lg font-bold text-gray-900">Daftar Kehadiran & Nilai</h2>
        <div class="flex gap-2">
            <a href="{{ route('pbm.classes.sessions.export', [$class->id, $session->id]) }}" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition flex items-center gap-2">
                <i data-feather="download" class="w-4 h-4"></i> Export CSV
            </a>
        </div>
    </div>
    
    <div class="overflow-x-auto swipe-scroll">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Mahasiswa</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Waktu Presensi</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Shift & Lokasi</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Validasi CI</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Nilai Rubrik</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($session->attendances as $att)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4">
                        <div class="font-medium text-gray-900">{{ $att->mahasiswa->name ?? 'Mahasiswa' }}</div>
                        <div class="text-xs text-gray-500">{{ $att->mahasiswa->nim ?? '-' }}</div>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">
                        @if($att->check_in_time)
                            <div class="flex items-center gap-1"><i data-feather="arrow-right-circle" class="w-3 h-3 text-green-500"></i> {{ $att->check_in_time->format('H:i') }}
                            @if($att->check_in_signature_path) <i data-feather="pen-tool" class="w-3 h-3 text-sky-500" title="Ada Tanda Tangan Masuk"></i> @endif
                            </div>
                            @if($att->check_out_time)
                            <div class="flex items-center gap-1 mt-1"><i data-feather="arrow-left-circle" class="w-3 h-3 text-red-500"></i> {{ $att->check_out_time->format('H:i') }}
                            @if($att->check_out_signature_path) <i data-feather="pen-tool" class="w-3 h-3 text-sky-500" title="Ada Tanda Tangan Keluar"></i> @endif
                            </div>
                            @endif
                        @else
                            <span class="text-gray-400 italic">Belum hadir</span>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        @if($att->shift_name || $att->lokasi_praktik)
                            <div class="text-xs font-semibold text-indigo-700">{{ $att->shift_name ?? '-' }}</div>
                            <div class="text-[10px] text-gray-500 mt-0.5">{{ $att->lokasi_praktik ?? '-' }}</div>
                        @else
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        @if($att->status == 'hadir')
                            <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-md font-semibold">Hadir</span>
                        @elseif($att->status == 'izin')
                            <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-md font-semibold">Izin</span>
                        @elseif($att->status == 'sakit')
                            <span class="bg-orange-100 text-orange-700 text-xs px-2 py-1 rounded-md font-semibold">Sakit</span>
                        @else
                            <span class="bg-red-100 text-red-700 text-xs px-2 py-1 rounded-md font-semibold">Alpha</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-sm">
                        @if($att->ci_validated_at)
                            <div class="flex items-center gap-1 text-green-600 font-medium">
                                <i data-feather="check-circle" class="w-4 h-4"></i> Disetujui
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $att->ciValidator->name ?? 'CI' }}</div>
                        @else
                            <span class="text-yellow-600 text-xs font-medium bg-yellow-50 px-2 py-1 rounded border border-yellow-100">Menunggu</span>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        @if($att->final_grade)
                            <span class="font-bold text-gray-900">{{ $att->final_grade }}</span> / 100
                        @else
                            <span class="text-gray-400 italic text-sm">Belum dinilai</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-right">
                        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
                        <button onclick="openGradeModal({{ $att->id }}, '{{ addslashes($att->mahasiswa->name ?? 'Mahasiswa') }}', {{ $att->final_grade ?? 'null' }})" class="text-sky-600 hover:text-sky-800 text-sm font-medium transition">Beri Nilai</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-8 text-center text-gray-500 text-sm">
                        Belum ada data presensi. Jika mahasiswa sudah diundang, data akan muncul di sini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- ==================== MODALS ==================== -->

<!-- Modal: Add Rubric -->
<div id="addRubricModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('addRubricModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-md my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Tambah Rubrik Penilaian</h3>
            <button type="button" onclick="document.getElementById('addRubricModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.sessions.attach-rubric', [$class->id, $session->id]) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Rubrik <span class="text-red-500">*</span></label>
                    <select name="evaluation_rubric_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                        <option value="">-- Pilih Rubrik --</option>
                        @foreach($availableRubrics as $rubric)
                            <option value="{{ $rubric->id }}">{{ $rubric->judul }} ({{ $rubric->kategori_label }})</option>
                        @endforeach
                    </select>
                    <div class="flex flex-wrap justify-between items-center gap-2 mt-2">
                        <p class="text-xs text-gray-500"><i data-feather="info" class="w-3 h-3 inline"></i> Rubrik template akan muncul di sini.</p>
                        <a href="{{ route('admin.rubrik.create') }}" target="_blank" class="text-xs font-medium text-sky-600 hover:text-sky-800 transition flex items-center gap-1">
                            <i data-feather="external-link" class="w-3 h-3"></i> Buat Rubrik Baru
                        </a>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 shrink-0 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addRubricModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg transition text-sm">Batal</button>
                <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm transition text-sm">Pasang Rubrik</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: View Rubric -->
@if($session->evaluationRubric)
<div id="viewRubricModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('viewRubricModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-2xl my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-lg text-gray-900">{{ $session->evaluationRubric->judul }}</h3>
                <div class="text-xs text-gray-500 mt-1">{{ $session->evaluationRubric->kategori_label }} · {{ $session->evaluationRubric->jenis_rubrik_label }}</div>
            </div>
            <button type="button" onclick="document.getElementById('viewRubricModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-5 overflow-y-auto flex-1 space-y-4">
            @if($session->evaluationRubric->deskripsi)
            <p class="text-sm text-gray-600">{{ $session->evaluationRubric->deskripsi }}</p>
            @endif
            <div class="space-y-3">
                @foreach($session->evaluationRubric->items as $item)
                <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                    <div class="flex justify-between items-start gap-2">
                        <div class="font-medium text-gray-900 text-sm">{{ $item->nama_kriteria }}</div>
                        <span class="bg-sky-100 text-sky-700 text-xs px-2 py-0.5 rounded font-semibold shrink-0">Bobot: {{ $item->bobot }}</span>
                    </div>
                    @if($item->deskripsi)
                    <p class="text-xs text-gray-500 mt-1">{{ $item->deskripsi }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            <div class="pt-3 border-t border-gray-100 text-sm text-gray-600">
                <strong>Rumus:</strong> {{ $session->evaluationRubric->rumus_penilaian_label }} · <strong>Skala Max:</strong> {{ $session->evaluationRubric->skala_max }}
            </div>
        </div>
        <div class="p-4 bg-gray-50 border-t border-gray-100 shrink-0 flex justify-end">
            <button type="button" onclick="document.getElementById('viewRubricModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition">Tutup</button>
        </div>
    </div>
</div>
@endif

<!-- Modal: Grade Student -->
<div id="gradeModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('gradeModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-md my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-lg text-gray-900">Beri Nilai</h3>
                <div class="text-sm text-gray-500" id="gradeModalStudentName">-</div>
            </div>
            <button type="button" onclick="document.getElementById('gradeModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="gradeForm" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            @method('PUT')
            <input type="hidden" name="manual_grade" value="1">
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nilai (0 - 100) <span class="text-red-500">*</span></label>
                    <input type="number" name="final_grade" id="gradeInput" min="0" max="100" step="0.01" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-2xl text-center font-bold" placeholder="85">
                    <p class="text-xs text-gray-500 mt-2"><i data-feather="info" class="w-3 h-3 inline"></i> Masukkan nilai akhir secara langsung (skala 0-100).</p>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 shrink-0 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('gradeModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg transition text-sm">Batal</button>
                <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm transition text-sm">Simpan Nilai</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Show QR Code -->
<div id="qrModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('qrModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-sky-50 shrink-0">
            <div>
                <h3 class="font-bold text-lg text-sky-900">Scan QR Code Kehadiran</h3>
                <div class="text-sm text-sky-700">{{ $session->title }}</div>
            </div>
            <button type="button" onclick="document.getElementById('qrModal').classList.add('hidden')" class="p-1 rounded-lg text-sky-600 hover:text-sky-900 hover:bg-sky-100 transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="p-6 flex flex-col items-center justify-center bg-gray-50 flex-1 overflow-y-auto">
            <p class="text-center text-gray-600 mb-6 font-medium text-sm">Silakan minta mahasiswa memindai QR code ini melalui browser mereka untuk melakukan presensi.</p>
            <div class="bg-white p-4 rounded-2xl shadow-md border border-gray-200">
                <img src="{{ (new \App\Support\QrCodeDataUri)->make(route('pbm.sessions.scan', $session->id), 10) }}" alt="QR Code Presensi" class="w-56 h-56 sm:w-64 sm:h-64 mx-auto object-contain">
            </div>
            <div class="mt-6 text-center w-full">
                <p class="text-xs text-gray-500 mb-2">Atau bagikan link presensi ini:</p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-2">
                    <input type="text" readonly value="{{ route('pbm.sessions.scan', $session->id) }}" class="bg-white border border-gray-300 rounded-lg text-xs px-3 py-2 w-full sm:w-64 text-gray-600 outline-none text-center sm:text-left" id="scanLinkInput">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('scanLinkInput').value); alert('Link disalin!')" class="w-full sm:w-auto text-sky-600 bg-sky-50 hover:bg-sky-100 px-3 py-2 rounded-lg text-xs font-semibold border border-sky-200 transition">Copy Link</button>
                </div>
            </div>
        </div>
        <div class="p-4 bg-white border-t border-gray-100 shrink-0 flex justify-end">
            <button type="button" onclick="document.getElementById('qrModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal: Add Material -->
<div id="addMaterialModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs {{ ($errors->any() && old('title')) ? '' : 'hidden' }} flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('addMaterialModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg my-auto bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-bold text-lg text-gray-900">Tambah Materi / Media</h3>
                <p class="text-xs text-gray-500 mt-0.5">Unggah dokumen, video, atau cantumkan tautan pembelajaran.</p>
            </div>
            <button type="button" onclick="document.getElementById('addMaterialModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.sessions.materials.store', [$class->id, $session->id]) }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                @if($errors->any() && old('title'))
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                    <p class="font-bold mb-1 flex items-center gap-1">
                        <i data-feather="alert-circle" class="w-3.5 h-3.5"></i> Perhatian:
                    </p>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Materi <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Contoh: Slide PPT Pertemuan 1 - Pengantar" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Materi <span class="text-red-500">*</span></label>
                    <select name="type" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="document" {{ old('type', 'document') === 'document' ? 'selected' : '' }}>Dokumen (PDF, PPTX, DOCX, XLSX)</option>
                        <option value="video" {{ old('type') === 'video' ? 'selected' : '' }}>Video (File Video / YouTube)</option>
                        <option value="link" {{ old('type') === 'link' ? 'selected' : '' }}>Tautan Eksternal (Google Drive, Canva, Web)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Deskripsi (Opsional)</label>
                    <textarea name="description" rows="2" placeholder="Catatan instruksi atau pengantar materi untuk mahasiswa..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">{{ old('description') }}</textarea>
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload File (Maksimal 100MB)</label>
                        <input type="file" name="file" class="w-full rounded-lg border border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm py-2 px-3 bg-white">
                        <p class="text-xs text-gray-500 mt-1">Format didukung: PDF, PPT, Word, Excel, MP4, dll.</p>
                    </div>

                    <div class="relative flex py-1 items-center">
                        <div class="flex-grow border-t border-gray-200"></div>
                        <span class="flex-shrink mx-3 text-xs uppercase font-medium text-gray-400">Atau Tautan Web</span>
                        <div class="flex-grow border-t border-gray-200"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tautan URL</label>
                        <input type="text" name="url" value="{{ old('url') }}" placeholder="https://youtube.com/... atau https://drive.google.com/..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <p class="text-xs text-gray-500 mt-1">Dapat berupa tautan Google Drive, YouTube, Canva, atau laman materi eksternal.</p>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 shrink-0 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addMaterialModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg transition text-sm">Batal</button>
                <button type="submit" class="pbm-primary px-5 py-2 rounded-lg font-medium text-white hover:opacity-90 shadow-sm transition text-sm flex items-center gap-1.5">
                    <i data-feather="save" class="w-4 h-4"></i> Simpan Materi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Assignment -->
<div id="addAssignmentModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('addAssignmentModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Buat Tugas Baru</h3>
            <button type="button" onclick="document.getElementById('addAssignmentModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.sessions.assignments.store', [$class->id, $session->id]) }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Tugas <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Instruksi</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batas Waktu (Tenggat) <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="due_date" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rubrik Penilaian (Opsional)</label>
                    <select name="evaluation_rubric_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="">-- Penilaian Langsung (Tanpa Rubrik) --</option>
                        @foreach($availableRubrics as $rubric)
                            <option value="{{ $rubric->id }}">{{ $rubric->judul }} ({{ $rubric->kategori_label }})</option>
                        @endforeach
                    </select>
                    <div class="flex flex-wrap justify-between items-center gap-2 mt-1">
                        <p class="text-xs text-gray-500"><i data-feather="info" class="w-3 h-3 inline"></i> Pilih rubrik jika tugas dinilai berdasarkan kriteria tertentu.</p>
                        <a href="{{ route('admin.rubrik.create') }}" target="_blank" class="text-xs font-medium text-sky-600 hover:text-sky-800 transition flex items-center gap-1 shrink-0">
                            <i data-feather="external-link" class="w-3 h-3"></i> Buat Rubrik
                        </a>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apakah ini Praktik Klinik?</label>
                    <select name="is_practical" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="0">Tidak (Teori)</option>
                        <option value="1">Ya (Praktik)</option>
                    </select>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 shrink-0 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addAssignmentModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg transition text-sm">Batal</button>
                <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm transition text-sm">Buat Tugas</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Quiz -->
<div id="addQuizModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('addQuizModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Buat Kuis CBT</h3>
            <button type="button" onclick="document.getElementById('addQuizModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.sessions.quizzes.store', [$class->id, $session->id]) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Kuis <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
                    <textarea name="description" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai</label>
                        <input type="datetime-local" name="start_time" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai</label>
                        <input type="datetime-local" name="end_time" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durasi (Menit) <span class="text-red-500">*</span></label>
                        <input type="number" name="duration_minutes" required min="1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                    </div>
                    <div class="pt-2 sm:pt-6 space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="shuffle_questions" value="1" checked class="rounded border-gray-300 text-sky-600 shadow-sm focus:border-sky-300 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">Acak Urutan Soal</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="show_results" value="1" checked class="rounded border-gray-300 text-sky-600 shadow-sm focus:border-sky-300 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">Tampilkan Nilai di Akhir</span>
                        </label>
                    </div>
                </div>

                <!-- Proctoring & SEB Options -->
                <div class="border-t border-gray-200 pt-4 mt-2">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i data-feather="shield" class="w-4 h-4 text-purple-600"></i> Pengawasan Ujian (Proctoring) & SEB
                    </h4>
                    <div class="space-y-3 bg-purple-50/50 p-3.5 rounded-xl border border-purple-100">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="enable_proctoring" id="create_enable_proctoring" value="1" onchange="toggleProctoringFields(this, 'create_proctoring_options')" class="mt-0.5 rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200">
                            <div>
                                <span class="text-sm font-semibold text-gray-900 block">Aktifkan Fitur Pengawasan (Proctoring)</span>
                                <span class="text-xs text-gray-500">Mencatat aktivitas, pelanggaran integritas, dan memantau pengerjaan ujian.</span>
                            </div>
                        </label>

                        <div id="create_proctoring_options" class="hidden pl-6 pt-2 space-y-3 border-t border-purple-200/50">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" name="require_webcam" value="1" class="mt-0.5 rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200">
                                <div>
                                    <span class="text-xs font-medium text-gray-800 block">Wajibkan Kamera / Webcam</span>
                                    <span class="text-[11px] text-gray-500">Kamera aktif dan mengambil foto snapshot berkala & saat ada indikasi pelanggaran.</span>
                                </div>
                            </label>

                            <div class="space-y-2">
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="require_microphone" id="create_require_mic" value="1" 
                                           onchange="document.getElementById('create_mic_sensitivity_wrap').classList.toggle('hidden', !this.checked)"
                                           class="mt-0.5 rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200">
                                    <div>
                                        <span class="text-xs font-medium text-gray-800 block">Wajibkan Mikrofon / On Mic</span>
                                        <span class="text-[11px] text-gray-500">Mikrofon aktif terus-menerus untuk mendeteksi peserta mengobrol, berbisik, atau bertanya kepada orang lain.</span>
                                    </div>
                                </label>
                                <div id="create_mic_sensitivity_wrap" class="hidden ml-6 p-2.5 bg-white rounded-lg border border-purple-200 space-y-1">
                                    <label class="block text-[11px] font-semibold text-gray-700">Sensitivitas Deteksi Suara:</label>
                                    <select name="mic_sensitivity" class="w-full text-xs rounded-md border-gray-300 py-1 focus:border-purple-500 focus:ring focus:ring-purple-200">
                                        <option value="medium" selected>Sedang (Standar - Rekomendasi)</option>
                                        <option value="high">Tinggi (Ketat / Peka Bisikan)</option>
                                        <option value="low">Rendah (Toleran Ruangan Bising)</option>
                                    </select>
                                </div>
                            </div>

                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" name="enable_browser_lockdown" value="1" checked class="mt-0.5 rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200">
                                <div>
                                    <span class="text-xs font-medium text-gray-800 block">Kunci Layar (Browser Lockdown)</span>
                                    <span class="text-[11px] text-gray-500">Mode fullscreen wajib, deteksi pindah tab/jendela, blokir copy-paste, klik kanan, dan shortcut DevTools.</span>
                                </div>
                            </label>

                            <div class="pt-1">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Batas Maksimal Pelanggaran (Auto-Lock)</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="max_violations" value="3" min="1" max="50" class="w-24 rounded-lg border-gray-300 shadow-sm text-xs py-1.5 focus:border-purple-500 focus:ring focus:ring-purple-200">
                                    <span class="text-[11px] text-gray-500">kali pelanggaran sebelum ujian terkunci otomatis</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 bg-blue-50/50 p-3.5 rounded-xl border border-blue-100 space-y-3">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="require_seb" id="create_require_seb" value="1" onchange="toggleProctoringFields(this, 'create_seb_options')" class="mt-0.5 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                            <div>
                                <span class="text-sm font-semibold text-gray-900 block">Wajibkan Safe Exam Browser (SEB)</span>
                                <span class="text-xs text-gray-500">Hanya izinkan pengerjaan kuis melalui aplikasi Safe Exam Browser di Windows/macOS/iOS.</span>
                            </div>
                        </label>

                        <div id="create_seb_options" class="hidden pl-6 pt-2 border-t border-blue-200/50">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Password Keluar SEB (Quit Password - Opsional)</label>
                            <input type="text" name="seb_quit_password" placeholder="Kosongkan jika bebas keluar" class="w-full rounded-lg border-gray-300 shadow-sm text-xs py-1.5 focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <span class="text-[11px] text-gray-500">Peserta harus memasukkan password ini jika ingin keluar SEB sebelum selesai.</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 shrink-0 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addQuizModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg transition text-sm">Batal</button>
                <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm transition text-sm">Simpan Pengaturan Kuis</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
    window.openGradeModal = function(attendanceId, studentName, currentGrade) {
        document.getElementById('gradeModalStudentName').textContent = studentName;
        document.getElementById('gradeInput').value = currentGrade !== null ? currentGrade : '';
        
        // Build the correct route URL for updating attendance
        var formAction = "{{ url('pbm/sessions') }}/" + {{ $session->id }} + "/attendances/" + attendanceId;
        document.getElementById('gradeForm').action = formAction;
        
        document.getElementById('gradeModal').classList.remove('hidden');
        
        // Re-render feather icons for modal
        if (typeof feather !== 'undefined') { feather.replace(); }
    }

    window.toggleProctoringFields = function(checkbox, targetId) {
        var el = document.getElementById(targetId);
        if (el) {
            if (checkbox.checked) {
                el.classList.remove('hidden');
            } else {
                el.classList.add('hidden');
            }
        }
    }
</script>
@endpush
@endsection
