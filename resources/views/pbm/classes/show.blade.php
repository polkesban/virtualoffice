@extends('pbm.layouts.app')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.default.css" rel="stylesheet">
    <style>
        .ts-control { border-radius: 0.5rem; border-color: #d1d5db; padding: 0.5rem 0.75rem; }
        .ts-control > input { font-size: 0.875rem; }
        .tom-select.ts-wrapper { width: 100%; }
    </style>
@endpush

@section('content')
<!-- Flash Messages -->
@if(session('success'))
<div class="bg-green-50 border-l-4 border-green-500 p-3 rounded-r-xl mb-4 text-green-800 text-sm font-medium">
    <i data-feather="check-circle" class="w-4 h-4 inline mr-1"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="bg-red-50 border-l-4 border-red-500 p-3 rounded-r-xl mb-4 text-red-800 text-sm font-medium">
    <i data-feather="alert-circle" class="w-4 h-4 inline mr-1"></i> {{ session('error') }}
</div>
@endif

<!-- Classroom Banner -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="h-44 md:h-52 relative overflow-hidden bg-gradient-to-r from-sky-500 to-indigo-600">
        @if($class->cover_url)
            <img src="{{ $class->cover_url }}" alt="{{ $class->name }}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
        @else
            <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(45deg, #ffffff 25%, transparent 25%, transparent 75%, #ffffff 75%, #ffffff), linear-gradient(45deg, #ffffff 25%, transparent 25%, transparent 75%, #ffffff 75%, #ffffff); background-size: 20px 20px; background-position: 0 0, 10px 10px;"></div>
        @endif
        <div class="absolute bottom-4 left-6 text-white text-sm font-medium bg-white/20 px-3 py-1 rounded-full backdrop-blur-sm">
            {{ $class->academic_year ?? 'Semua Tahun' }} - {{ $class->semester ?? '-' }}
        </div>
    </div>
    <div class="p-6 relative">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 leading-tight">{{ $class->name }}</h1>
                <p class="text-gray-500 mt-1 flex items-center gap-2">
                    <i data-feather="book-open" class="w-4 h-4"></i> {{ $class->course_name ? ($class->course_code ? $class->course_code . ' - ' : '') . $class->course_name : 'Matkul Umum' }}
                </p>
            </div>
            @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
            <a href="{{ route('pbm.classes.edit', $class->id) }}" class="text-gray-400 hover:text-sky-600 p-2 rounded-lg hover:bg-sky-50 transition block">
                <i data-feather="settings" class="w-5 h-5"></i>
            </a>
            @endif
        </div>
        @if($class->description)
        <p class="mt-4 text-gray-600 text-sm">{{ $class->description }}</p>
        @endif
        
        <!-- Tabs Navigation -->
        <div class="flex gap-4 sm:gap-6 mt-6 border-b border-gray-200 overflow-x-auto pb-0.5 swipe-scroll flex-nowrap sm:flex-wrap">
            <button onclick="switchTab('sessions')" id="tab-btn-sessions" class="pb-3 border-b-2 font-medium text-sm transition text-sky-600 border-sky-600 flex items-center gap-2 shrink-0">
                <i data-feather="calendar" class="w-4 h-4"></i> Sesi Pertemuan
            </button>
            <button onclick="switchTab('members')" id="tab-btn-members" class="pb-3 border-b-2 font-medium text-sm transition text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300 flex items-center gap-2 shrink-0">
                <i data-feather="users" class="w-4 h-4"></i> Anggota
            </button>
        </div>
    </div>
</div>

<!-- Tab Content: Sessions -->
<div id="tab-sessions" class="space-y-4">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-gray-900">Jadwal Sesi</h2>
        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
        <a href="{{ route('pbm.classes.sessions.create', $class->id) }}" class="pbm-hover px-4 py-2 rounded-lg text-sm font-medium border border-sky-200 pbm-primary-text flex items-center gap-2 bg-white">
            <i data-feather="plus" class="w-4 h-4"></i> Buat Sesi
        </a>
        @endif
    </div>
    
    @forelse($class->sessions as $session)
    <div onclick="window.location.href='{{ route('pbm.classes.sessions.show', [$class->id, $session->id]) }}'" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-start gap-4 hover:border-sky-300 transition group cursor-pointer">
        <div class="w-12 h-12 bg-sky-50 rounded-lg flex flex-col items-center justify-center shrink-0 border border-sky-100 group-hover:bg-sky-500 group-hover:text-white transition">
            <span class="text-xs font-semibold">{{ $session->start_time->format('M') }}</span>
            <span class="text-lg font-bold leading-none">{{ $session->start_time->format('d') }}</span>
        </div>
        <div class="flex-1">
            <div class="flex justify-between items-start">
                <h3 class="font-bold text-gray-900 text-lg group-hover:text-sky-600 transition">{{ $session->title }}</h3>
                <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-md font-semibold">Aktif</span>
            </div>
            <div class="text-sm text-gray-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                <span class="flex items-center gap-1"><i data-feather="clock" class="w-3 h-3"></i> {{ $session->start_time->format('H:i') }} - {{ $session->end_time->format('H:i') }}</span>
                @if($session->location_name)
                <span class="flex items-center gap-1"><i data-feather="map-pin" class="w-3 h-3"></i> {{ $session->location_name }}</span>
                @endif
            </div>
            <div class="mt-3 flex gap-2">
                @if($session->require_qr)
                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded border border-gray-200 flex items-center gap-1"><i data-feather="camera" class="w-3 h-3"></i> QR Code</span>
                @endif
                @if($session->require_gps)
                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded border border-gray-200 flex items-center gap-1"><i data-feather="navigation" class="w-3 h-3"></i> GPS</span>
                @endif
            </div>
        </div>
        <div class="self-center">
            <i data-feather="chevron-right" class="text-gray-400 group-hover:text-sky-500 transition"></i>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-dashed border-gray-300 p-8 text-center">
        <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center text-gray-400 mx-auto mb-3">
            <i data-feather="calendar" class="w-6 h-6"></i>
        </div>
        <h4 class="font-medium text-gray-900">Belum ada sesi pertemuan</h4>
        <p class="text-gray-500 text-sm mt-1">Dosen belum membuat jadwal kegiatan atau praktik.</p>
    </div>
    @endforelse
</div>

<!-- Tab Content: Members -->
<div id="tab-members" class="hidden">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-gray-900">Dosen & CI</h2>
        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
        <button onclick="document.getElementById('addDosenModal').classList.remove('hidden')" class="text-sky-600 text-sm font-medium hover:underline flex items-center gap-1">
            <i data-feather="plus" class="w-3 h-3"></i> Tambah Dosen/CI
        </button>
        @endif
    </div>
    <div x-data="memberList(@js($class->members->whereIn('role', ['dosen', 'ci'])->map(fn($m) => ['id' => $m->id, 'name' => $m->user->name ?? 'User Tidak Diketahui', 'role' => $m->role])->values()), 'dosen')" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-8">
        <!-- Toolbar -->
        <div class="p-3 border-b border-gray-100 flex flex-col sm:flex-row gap-3 justify-between items-center bg-gray-50/50">
            <div class="flex items-center gap-2">
                <input type="checkbox" x-model="selectAll" @change="toggleAll" class="rounded border-gray-300 text-sky-600 shadow-sm focus:border-sky-300 focus:ring focus:ring-offset-0 focus:ring-sky-200 focus:ring-opacity-50">
                <span class="text-sm font-medium text-gray-700">Pilih Semua</span>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select x-model="sortBy" class="text-sm border-gray-200 rounded-lg focus:ring-sky-200 focus:border-sky-500 py-1.5">
                    <option value="name_asc">Nama (A-Z)</option>
                    <option value="name_desc">Nama (Z-A)</option>
                </select>
                <div class="relative w-full sm:w-48">
                    <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" x-model="search" placeholder="Cari dosen..." class="w-full pl-8 pr-3 py-1.5 text-sm border-gray-200 rounded-lg focus:ring-sky-200 focus:border-sky-500">
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div x-show="selected.length > 0" class="p-3 bg-red-50 border-b border-red-100 flex items-center justify-between" x-cloak>
            <span class="text-sm font-bold text-red-800"><span x-text="selected.length"></span> dosen terpilih</span>
            <div class="flex gap-2">
                @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
                <form action="{{ route('pbm.classes.members.bulk-destroy', $class->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus dosen/CI terpilih?')">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="member_ids[]" :value="id">
                    </template>
                    <button type="submit" class="text-xs font-medium px-3 py-1.5 bg-white border border-red-200 rounded-lg shadow-sm text-red-600 hover:bg-red-50 transition">Hapus Terpilih</button>
                </form>
                @endif
            </div>
        </div>

        <ul class="divide-y divide-gray-100">
            <template x-for="member in filteredAndSorted" :key="member.id">
                <li class="p-4 flex items-center justify-between hover:bg-gray-50/50 transition">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" :value="member.id" x-model="selected" class="rounded border-gray-300 text-sky-600 shadow-sm focus:border-sky-300 focus:ring focus:ring-offset-0 focus:ring-sky-200 focus:ring-opacity-50">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold" x-text="member.name.substring(0,1).toUpperCase()"></div>
                        <div>
                            <div class="font-medium text-gray-900" x-text="member.name"></div>
                            <div class="text-xs text-gray-500 uppercase font-semibold" x-text="member.role"></div>
                        </div>
                    </div>
                    @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
                    <form :action="`{{ url('pbm/classes/'.$class->id.'/members') }}/${member.id}`" method="POST" onsubmit="return confirm('Yakin ingin menghapus anggota ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-600 transition p-1">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </form>
                    @endif
                </li>
            </template>
            <li x-show="filteredAndSorted.length === 0" class="p-6 text-center text-gray-500 text-sm" x-cloak>
                <span x-text="search ? 'Tidak ada dosen/CI yang cocok dengan pencarian.' : 'Belum ada dosen/CI yang tergabung.'"></span>
            </li>
        </ul>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <h2 class="text-lg font-bold text-gray-900">Mahasiswa ({{ $class->members->where('role', 'mahasiswa')->count() }})</h2>
        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
        <div class="flex items-center gap-2 sm:gap-3 overflow-x-auto pb-1 swipe-scroll flex-nowrap sm:flex-wrap">
            @if($class->members->where('role', 'mahasiswa')->count() > 0)
            <button type="button" onclick="document.getElementById('autoAssignGroupsModal').classList.remove('hidden')" class="text-orange-600 text-xs sm:text-sm font-medium hover:underline flex items-center gap-1 shrink-0">
                <i data-feather="users" class="w-3.5 h-3.5"></i> Bagi Kelompok Otomatis
            </button>
            @endif
            <button type="button" onclick="document.getElementById('addBatchModal').classList.remove('hidden')" class="text-emerald-600 text-xs sm:text-sm font-medium hover:underline flex items-center gap-1 shrink-0">
                <i data-feather="layers" class="w-3.5 h-3.5"></i> Tambah Rombel
            </button>
            <button type="button" onclick="document.getElementById('addMahasiswaModal').classList.remove('hidden')" class="text-sky-600 text-xs sm:text-sm font-medium hover:underline flex items-center gap-1 shrink-0">
                <i data-feather="plus" class="w-3.5 h-3.5"></i> Tambah Mahasiswa
            </button>
        </div>
        @endif
    </div>
    <div x-data="memberList(@js($class->members->where('role', 'mahasiswa')->map(fn($m) => ['id' => $m->id, 'name' => $m->mahasiswa->name ?? 'Mahasiswa', 'nim' => $m->mahasiswa->nim ?? '-', 'group_name' => $m->group_name])->values()), 'mahasiswa')" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <!-- Toolbar -->
        <div class="p-3 border-b border-gray-100 flex flex-col sm:flex-row gap-3 justify-between items-center bg-gray-50/50">
            <div class="flex items-center gap-2">
                <input type="checkbox" x-model="selectAll" @change="toggleAll" class="rounded border-gray-300 text-sky-600 shadow-sm focus:border-sky-300 focus:ring focus:ring-offset-0 focus:ring-sky-200 focus:ring-opacity-50">
                <span class="text-sm font-medium text-gray-700">Pilih Semua</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <select x-model="filterGroup" class="text-sm border-gray-200 rounded-lg focus:ring-sky-200 focus:border-sky-500 py-1.5 max-w-[170px]">
                    <option value="">Semua Kelompok</option>
                    <option value="none">Belum ada kelompok</option>
                    <template x-for="group in uniqueGroups" :key="group">
                        <option :value="group" x-text="group"></option>
                    </template>
                </select>
                <select x-model="sortBy" class="text-sm border-gray-200 rounded-lg focus:ring-sky-200 focus:border-sky-500 py-1.5">
                    <option value="name_asc">Nama (A-Z)</option>
                    <option value="name_desc">Nama (Z-A)</option>
                    <option value="nim_asc">NIM (A-Z)</option>
                    <option value="group_asc">Kelompok</option>
                </select>
                <div class="relative w-full sm:w-48">
                    <svg class="w-4 h-4 absolute left-2.5 top-2.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" x-model="search" placeholder="Cari mahasiswa..." class="w-full pl-8 pr-3 py-1.5 text-sm border-gray-200 rounded-lg focus:ring-sky-200 focus:border-sky-500">
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div x-show="selected.length > 0" class="p-3 bg-sky-50 border-b border-sky-100 flex items-center justify-between" x-cloak>
            <span class="text-sm font-bold text-sky-800"><span x-text="selected.length"></span> mahasiswa terpilih</span>
            <div class="flex gap-2">
                @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
                <button type="button" @click="openBulkAssignGroupModal()" class="text-xs font-medium px-3 py-1.5 bg-white border border-sky-200 rounded-lg shadow-sm text-sky-700 hover:bg-sky-50 transition">Set Kelompok</button>
                <form action="{{ route('pbm.classes.members.bulk-destroy', $class->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus mahasiswa terpilih?')">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="member_ids[]" :value="id">
                    </template>
                    <button type="submit" class="text-xs font-medium px-3 py-1.5 bg-white border border-red-200 rounded-lg shadow-sm text-red-600 hover:bg-red-50 transition">Hapus Terpilih</button>
                </form>
                @endif
            </div>
        </div>

        <ul class="divide-y divide-gray-100">
            <template x-for="member in filteredAndSorted" :key="member.id">
                <li class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50/50 transition">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" :value="member.id" x-model="selected" class="rounded border-gray-300 text-sky-600 shadow-sm focus:border-sky-300 focus:ring focus:ring-offset-0 focus:ring-sky-200 focus:ring-opacity-50">
                        <div class="w-10 h-10 bg-gray-100 border border-gray-200 rounded-full flex items-center justify-center text-gray-600 font-bold" x-text="member.name.substring(0,1).toUpperCase()"></div>
                        <div>
                            <div class="font-medium text-gray-900" x-text="member.name"></div>
                            <div class="text-xs text-gray-500" x-text="member.nim"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 pl-8 sm:pl-0">
                        <template x-if="member.group_name">
                            <span class="bg-sky-100 text-sky-700 text-xs px-2 py-1 rounded-md font-medium" x-text="member.group_name"></span>
                        </template>
                        <template x-if="!member.group_name">
                            <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-md">Belum ada kelompok</span>
                        </template>
                        
                        @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
                        <button type="button" @click="openEditGroupModal(member.id, member.group_name)" class="text-gray-400 hover:text-sky-600 transition p-1" title="Edit Kelompok">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </button>
                        <form :action="`{{ url('pbm/classes/'.$class->id.'/members') }}/${member.id}`" method="POST" onsubmit="return confirm('Yakin ingin menghapus mahasiswa ini dari kelas?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600 transition p-1" title="Hapus dari Kelas">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </li>
            </template>
            <li x-show="filteredAndSorted.length === 0" class="p-8 text-center text-gray-500 text-sm" x-cloak>
                <span x-text="search || filterGroup ? 'Tidak ada mahasiswa yang cocok dengan filter.' : 'Belum ada mahasiswa yang tergabung di kelas ini.'"></span>
            </li>
        </ul>
        
        <!-- Bulk Assign Group Modal -->
        <div x-show="isBulkAssignOpen" class="fixed inset-0 z-[60] overflow-y-auto p-3 sm:p-4 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center min-h-screen" style="display: none;">
            <div class="fixed inset-0" @click="isBulkAssignOpen = false"></div>
            <div class="relative w-full max-w-sm my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/80 shrink-0">
                    <h3 class="font-bold text-base text-gray-900">Set Kelompok (<span x-text="selected.length"></span> Mhs)</h3>
                    <button @click="isBulkAssignOpen = false" type="button" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <form action="{{ route('pbm.classes.members.bulk-assign-group', $class->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
                    @csrf
                    <div class="p-5 space-y-4 overflow-y-auto flex-1">
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="member_ids[]" :value="id">
                        </template>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelompok</label>
                            <input type="text" name="group_name" placeholder="Kosongkan untuk menghapus kelompok" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        </div>
                    </div>
                    <div class="p-4 border-t border-gray-100 bg-gray-50/80 flex justify-end gap-3 shrink-0">
                        <button type="button" @click="isBulkAssignOpen = false" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg text-sm transition cursor-pointer">Batal</button>
                        <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm text-sm transition cursor-pointer">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- Modal: Add Dosen/CI -->
<div id="addDosenModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('addDosenModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-md my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/80 shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Tambah Dosen / CI</h3>
            <button type="button" onclick="document.getElementById('addDosenModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition cursor-pointer">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.members.store', $class->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="member_type" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="dosen">Dosen</option>
                        <option value="ci">Clinical Instructor (CI)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih User <span class="text-red-500">*</span></label>
                    <select name="user_id" id="dosenSelect" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="">-- Pilih Dosen / CI --</option>
                        @foreach($availableDosen as $dosen)
                            <option value="{{ $dosen->id }}">{{ $dosen->name }} ({{ $dosen->role }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="p-4 sm:p-5 border-t border-gray-100 bg-gray-50/80 flex justify-end gap-3 shrink-0">
                <button type="button" onclick="document.getElementById('addDosenModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg text-sm transition cursor-pointer">Batal</button>
                <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm text-sm transition cursor-pointer">Tambahkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Mahasiswa -->
<div id="addMahasiswaModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('addMahasiswaModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/80 shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Tambah Mahasiswa</h3>
            <button type="button" onclick="document.getElementById('addMahasiswaModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition cursor-pointer">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.members.store', $class->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <input type="hidden" name="member_type" value="mahasiswa">
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter Prodi</label>
                        <select id="filterAddMahasiswaProdi" onchange="filterMahasiswaOptions()" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                            <option value="">Semua Prodi</option>
                            @foreach($availableProdi as $prodi)
                                <option value="{{ $prodi->id }}">{{ $prodi->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter Angkatan</label>
                        <select id="filterAddMahasiswaAngkatan" onchange="filterMahasiswaOptions()" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                            <option value="">Semua Angkatan</option>
                            @foreach($availableAngkatan as $angkatan)
                                <option value="{{ $angkatan }}">{{ $angkatan }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cari Mahasiswa <span class="text-red-500">*</span></label>
                    <select name="mahasiswa_id" required id="mahasiswaSelect" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="" class="default-option">-- Pilih Mahasiswa --</option>
                        @foreach($availableMahasiswa as $mhs)
                            <option value="{{ $mhs->id }}" data-prodi="{{ $mhs->program_studi_id }}" data-angkatan="{{ $mhs->angkatan }}">
                                {{ $mhs->nim ?? '-' }} - {{ $mhs->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-2"><i data-feather="info" class="w-3 h-3 inline"></i> Cari berdasarkan NIM atau nama mahasiswa.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kelompok (Opsional)</label>
                    <input type="text" name="group_name" placeholder="Misal: Kelompok 1, Kelompok A" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                </div>
            </div>
            <div class="p-4 sm:p-5 border-t border-gray-100 bg-gray-50/80 flex justify-end gap-3 shrink-0">
                <button type="button" onclick="document.getElementById('addMahasiswaModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg text-sm transition cursor-pointer">Batal</button>
                <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm text-sm transition cursor-pointer">Tambahkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Batch (Rombel) -->
<div id="addBatchModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('addBatchModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/80 shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Tambah Rombongan Belajar (Rombel)</h3>
            <button type="button" onclick="document.getElementById('addBatchModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition cursor-pointer">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.members.batch', $class->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Program Studi <span class="text-red-500">*</span></label>
                    <select name="program_studi_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="">-- Pilih Program Studi --</option>
                        @foreach($availableProdi as $prodi)
                            <option value="{{ $prodi->id }}">{{ $prodi->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Angkatan <span class="text-red-500">*</span></label>
                    <select name="angkatan" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="">-- Pilih Angkatan --</option>
                        @foreach($availableAngkatan as $angkatan)
                            <option value="{{ $angkatan }}">{{ $angkatan }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pengaturan Kelompok <span class="text-red-500">*</span></label>
                    <select name="group_assignment" id="batchGroupAssignment" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm" onchange="toggleBatchGroupInputs()">
                        <option value="none">Biarkan tanpa kelompok</option>
                        <option value="single">Jadikan 1 kelompok yang sama</option>
                        <option value="auto">Bagi otomatis menjadi beberapa kelompok</option>
                    </select>
                </div>
                
                <div id="batchSingleGroupInput" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelompok</label>
                    <input type="text" name="group_name" placeholder="Misal: Kelas Reguler A" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                    <p class="text-xs text-gray-500 mt-2"><i data-feather="info" class="w-3 h-3 inline"></i> Seluruh mahasiswa rombel ini akan masuk ke kelompok ini.</p>
                </div>

                <div id="batchAutoGroupInput" class="hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Kelompok</label>
                            <input type="number" name="auto_amount" min="1" value="1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prefix Nama</label>
                            <input type="text" name="auto_prefix" value="Kelompok" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2"><i data-feather="info" class="w-3 h-3 inline"></i> Rombel ini akan diacak dan dibagi ke dalam jumlah kelompok yang ditentukan.</p>
                </div>
            </div>

            <div class="p-4 sm:p-5 border-t border-gray-100 bg-gray-50/80 flex justify-end gap-3 shrink-0">
                <button type="button" onclick="document.getElementById('addBatchModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg text-sm transition cursor-pointer">Batal</button>
                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:opacity-90 flex items-center gap-2 text-sm shadow-sm transition cursor-pointer"><i data-feather="layers" class="w-4 h-4"></i> Tambahkan Rombel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Group -->
<div id="editGroupModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('editGroupModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-sm my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/80 shrink-0">
            <h3 class="font-bold text-base text-gray-900">Edit Kelompok</h3>
            <button type="button" onclick="document.getElementById('editGroupModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition cursor-pointer">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="editGroupForm" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            @method('PUT')
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelompok</label>
                    <input type="text" name="group_name" id="edit_group_name" placeholder="Misal: Kelompok 1, Kelompok A" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                </div>
            </div>
            <div class="p-4 border-t border-gray-100 bg-gray-50/80 flex justify-end gap-3 shrink-0">
                <button type="button" onclick="document.getElementById('editGroupModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg text-sm transition cursor-pointer">Batal</button>
                <button type="submit" class="pbm-primary px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm text-sm transition cursor-pointer">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Auto Assign Groups -->
<div id="autoAssignGroupsModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('autoAssignGroupsModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg my-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh] z-10">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/80 shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Bagi Kelompok Otomatis</h3>
            <button type="button" onclick="document.getElementById('autoAssignGroupsModal').classList.add('hidden')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition cursor-pointer">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.auto-assign-groups', $class->id) }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Terapkan Pada (Filter) <span class="text-red-500">*</span></label>
                    <select name="filter_target" required id="filterTargetSelect" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm" onchange="toggleAutoAssignFilter()">
                        <option value="all">Semua Mahasiswa di Kelas</option>
                        <option value="specific">Hanya Prodi &amp; Angkatan Tertentu (Rombel)</option>
                    </select>
                </div>

                <div id="filterSpecificInputs" class="hidden p-4 bg-gray-50 border border-gray-100 rounded-lg space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Program Studi <span class="text-red-500">*</span></label>
                        <select name="filter_prodi_id" id="filterProdiSelect" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                            <option value="">-- Pilih Program Studi --</option>
                            @foreach($availableProdi as $prodi)
                                <option value="{{ $prodi->id }}">{{ $prodi->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Angkatan <span class="text-red-500">*</span></label>
                        <select name="filter_angkatan" id="filterAngkatanSelect" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                            <option value="">-- Pilih Angkatan --</option>
                            @foreach($availableAngkatan as $angkatan)
                                <option value="{{ $angkatan }}">{{ $angkatan }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Pembagian <span class="text-red-500">*</span></label>
                    <select name="division_type" required id="divisionTypeSelect" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm" onchange="toggleAutoAssignInputs()">
                        <option value="by_groups">Berdasarkan Jumlah Kelompok</option>
                        <option value="by_members">Berdasarkan Jumlah Anggota per Kelompok</option>
                        <option value="by_prodi">Berdasarkan Prodi &amp; Angkatan</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="autoAssignInputsRow">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" id="autoAssignAmount" min="1" value="1" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prefix Nama Kelompok</label>
                        <input type="text" name="prefix" id="autoAssignPrefix" value="Kelompok" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                    </div>
                </div>
                <div class="bg-orange-50 border border-orange-100 p-3 rounded-lg text-sm text-orange-800 flex items-start gap-2">
                    <i data-feather="alert-triangle" class="w-5 h-5 shrink-0 text-orange-500"></i>
                    <p class="text-xs sm:text-sm">Tindakan ini akan <strong>menimpa</strong> pengaturan kelompok mahasiswa yang sudah ada dengan pembagian acak secara otomatis.</p>
                </div>
            </div>
            <div class="p-4 sm:p-5 border-t border-gray-100 bg-gray-50/80 flex justify-end gap-3 shrink-0">
                <button type="button" onclick="document.getElementById('autoAssignGroupsModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg text-sm transition cursor-pointer">Batal</button>
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm text-sm transition cursor-pointer">Bagi Otomatis</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.switchTab = function(tab) {
        // Hide all tabs
        document.getElementById('tab-sessions').classList.add('hidden');
        document.getElementById('tab-members').classList.add('hidden');
        
        // Reset buttons
        const btns = ['tab-btn-sessions', 'tab-btn-members'];
        btns.forEach(id => {
            const el = document.getElementById(id);
            el.classList.remove('text-sky-600', 'border-sky-600');
            el.classList.add('text-gray-500', 'border-transparent');
        });
        
        // Show active tab
        document.getElementById('tab-' + tab).classList.remove('hidden');
        
        // Active button styling
        const activeBtn = document.getElementById('tab-btn-' + tab);
        activeBtn.classList.remove('text-gray-500', 'border-transparent');
        activeBtn.classList.add('text-sky-600', 'border-sky-600');

        // Re-render feather icons for newly visible content
        if (typeof feather !== 'undefined') { feather.replace(); }
    }

    window.openEditGroupModal = function(memberId, groupName) {
        document.getElementById('edit_group_name').value = groupName || '';
        document.getElementById('editGroupForm').action = "{{ url('pbm/classes/' . $class->id . '/members') }}/" + memberId + "/group";
        document.getElementById('editGroupModal').classList.remove('hidden');
    }

    window.toggleAutoAssignInputs = function() {
        const type = document.getElementById('divisionTypeSelect').value;
        const row = document.getElementById('autoAssignInputsRow');
        const amountInput = document.getElementById('autoAssignAmount');
        if (type === 'by_prodi') {
            row.classList.add('hidden');
            amountInput.required = false;
        } else {
            row.classList.remove('hidden');
            amountInput.required = true;
        }
    }

    window.toggleBatchGroupInputs = function() {
        const type = document.getElementById('batchGroupAssignment').value;
        const singleInput = document.getElementById('batchSingleGroupInput');
        const autoInput = document.getElementById('batchAutoGroupInput');
        
        if (type === 'single') {
            singleInput.classList.remove('hidden');
            autoInput.classList.add('hidden');
        } else if (type === 'auto') {
            singleInput.classList.add('hidden');
            autoInput.classList.remove('hidden');
        } else {
            singleInput.classList.add('hidden');
            autoInput.classList.add('hidden');
        }
    }

    window.toggleAutoAssignFilter = function() {
        const target = document.getElementById('filterTargetSelect').value;
        const specificInputs = document.getElementById('filterSpecificInputs');
        const prodiSelect = document.getElementById('filterProdiSelect');
        const angkatanSelect = document.getElementById('filterAngkatanSelect');

        if (target === 'specific') {
            specificInputs.classList.remove('hidden');
            prodiSelect.required = true;
            angkatanSelect.required = true;
        } else {
            specificInputs.classList.add('hidden');
            prodiSelect.required = false;
            angkatanSelect.required = false;
        }
    }

    window.filterMahasiswaOptions = function() {
        const prodiId = document.getElementById('filterAddMahasiswaProdi').value;
        const angkatan = document.getElementById('filterAddMahasiswaAngkatan').value;
        const select = document.getElementById('mahasiswaSelect');
        const options = select.querySelectorAll('option:not(.default-option)');

        let hasVisibleOptions = false;
        
        options.forEach(option => {
            const optProdi = option.getAttribute('data-prodi');
            const optAngkatan = option.getAttribute('data-angkatan');
            
            let show = true;
            if (prodiId && optProdi !== prodiId) show = false;
            if (angkatan && optAngkatan !== angkatan) show = false;
            
            if (show) {
                option.style.display = '';
                hasVisibleOptions = true;
            } else {
                option.style.display = 'none';
            }
        });

        // Reset the selection to default if current selection is hidden
        const currentOption = select.options[select.selectedIndex];
        if (currentOption && currentOption.style.display === 'none') {
            select.value = '';
        }
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('memberList', (initialMembers, role) => ({
            members: initialMembers,
            role: role, // 'dosen' or 'mahasiswa'
            search: '',
            sortBy: 'name_asc',
            filterGroup: '',
            selected: [],
            isBulkAssignOpen: false,
            
            get uniqueGroups() {
                if (this.role !== 'mahasiswa') return [];
                const groups = this.members.map(m => m.group_name).filter(g => g !== null && g !== '');
                return [...new Set(groups)].sort();
            },
            
            get selectAll() {
                return this.filteredAndSorted.length > 0 && this.selected.length === this.filteredAndSorted.length;
            },
            
            set selectAll(val) {
                // Computed setter is needed for x-model, but actual logic in toggleAll
            },
            
            toggleAll() {
                if (this.selected.length === this.filteredAndSorted.length) {
                    this.selected = [];
                } else {
                    this.selected = this.filteredAndSorted.map(m => m.id);
                }
            },
            
            get filteredAndSorted() {
                let result = this.members;
                
                // Search
                if (this.search) {
                    let q = this.search.toLowerCase();
                    result = result.filter(m => {
                        return m.name.toLowerCase().includes(q) || (m.nim && m.nim.toLowerCase().includes(q));
                    });
                }
                
                // Filter Group (only for mahasiswa)
                if (this.role === 'mahasiswa') {
                    if (this.filterGroup === 'none') {
                        result = result.filter(m => !m.group_name);
                    } else if (this.filterGroup) {
                        result = result.filter(m => m.group_name === this.filterGroup);
                    }
                }
                
                // Sort
                result = result.sort((a, b) => {
                    if (this.sortBy === 'name_asc') return a.name.localeCompare(b.name);
                    if (this.sortBy === 'name_desc') return b.name.localeCompare(a.name);
                    if (this.sortBy === 'nim_asc') return (a.nim || '').localeCompare(b.nim || '');
                    if (this.sortBy === 'group_asc') return (a.group_name || '').localeCompare(b.group_name || '');
                    return 0;
                });
                
                return result;
            },
            
            openBulkAssignGroupModal() {
                this.isBulkAssignOpen = true;
            }
        }));
    });
</script>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener("turbo:load", function() {
        if (document.getElementById('dosenSelect') && !document.getElementById('dosenSelect').tomselect) {
            new TomSelect('#dosenSelect', {
                create: false,
                sortField: { field: 'text', direction: 'asc' },
                placeholder: '-- Ketik nama dosen --',
                maxOptions: 50
            });
        }
        
        if (document.getElementById('mahasiswaSelect') && !document.getElementById('mahasiswaSelect').tomselect) {
            window.mahasiswaTomSelect = new TomSelect('#mahasiswaSelect', {
                create: false,
                sortField: { field: 'text', direction: 'asc' },
                placeholder: '-- Ketik nama atau NIM mahasiswa --',
                maxOptions: 50
            });
        }
    });

    // Update the existing filterMahasiswaOptions to work with TomSelect
    function filterMahasiswaOptions() {
        const prodiId = document.getElementById('filterAddMahasiswaProdi').value;
        const angkatan = document.getElementById('filterAddMahasiswaAngkatan').value;
        const select = document.getElementById('mahasiswaSelect');
        
        if (window.mahasiswaTomSelect) {
            window.mahasiswaTomSelect.destroy();
        }

        Array.from(select.options).forEach(option => {
            if (option.classList.contains('default-option')) return;
            
            const matchProdi = !prodiId || option.dataset.prodi === prodiId;
            const matchAngkatan = !angkatan || option.dataset.angkatan === angkatan;
            
            if (matchProdi && matchAngkatan) {
                option.style.display = '';
                option.disabled = false;
            } else {
                option.style.display = 'none';
                option.disabled = true;
            }
        });
        
        select.value = "";
        
        window.mahasiswaTomSelect = new TomSelect('#mahasiswaSelect', {
            create: false,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: '-- Ketik nama atau NIM mahasiswa --',
            maxOptions: 50
        });
    }
</script>
@endpush
@endsection
