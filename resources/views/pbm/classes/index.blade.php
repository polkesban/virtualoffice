@extends('pbm.layouts.app')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Ruang Kelas</h1>
        <p class="text-gray-500 text-sm mt-0.5">Daftar kelas pembelajaran dan praktik klinik</p>
    </div>
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur', 'pj_kurikulum'])))
    <button type="button" onclick="document.getElementById('createClassModal').classList.remove('hidden')" class="pbm-primary px-4 py-2.5 rounded-xl font-medium shadow-sm hover:opacity-90 flex items-center gap-2 text-sm shrink-0 cursor-pointer">
        <i data-feather="plus" class="w-4 h-4"></i> Buat Kelas
    </button>
    @endif
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    @forelse($classes as $class)
    <a href="{{ route('pbm.classes.show', $class->id) }}" class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden hover:shadow-md hover:border-sky-300 transition group block">
        <div class="h-32 sm:h-36 relative overflow-hidden bg-gradient-to-r from-sky-500 to-indigo-600">
            @if($class->cover_url)
                <img src="{{ $class->cover_url }}" alt="{{ $class->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
            @else
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 16px 16px;"></div>
            @endif
        </div>
        <div class="p-5 relative">
            <div class="absolute -top-10 right-5 w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-sm p-1">
                <div class="w-full h-full rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold border border-blue-200">
                    {{ substr($class->creator->name ?? 'D', 0, 1) }}
                </div>
            </div>
            
            <div class="text-xs font-semibold text-sky-600 mb-1 uppercase tracking-wider">{{ $class->academic_year ?? 'Semua Tahun' }} - {{ $class->semester ?? '-' }}</div>
            <h3 class="font-bold text-lg text-gray-900 leading-tight group-hover:text-sky-600 transition">{{ $class->name }}</h3>
            
            <div class="mt-4 text-sm text-gray-500 flex items-center gap-2">
                <i data-feather="book-open" class="w-4 h-4 text-gray-400"></i> {{ $class->course_name ? ($class->course_code ? $class->course_code . ' - ' : '') . $class->course_name : 'Matkul Umum' }}
            </div>
            <div class="mt-2 text-sm text-gray-500 flex items-center gap-2">
                <i data-feather="users" class="w-4 h-4 text-gray-400"></i> {{ $class->members()->count() }} Anggota
            </div>
        </div>
    </a>
    @empty
    <div class="col-span-full bg-white rounded-xl border border-dashed border-gray-300 p-8 sm:p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 mx-auto mb-4">
            <i data-feather="inbox" class="w-8 h-8"></i>
        </div>
        <h3 class="font-semibold text-gray-900">Belum ada kelas</h3>
        <p class="text-gray-500 text-sm mt-1">Anda belum tergabung atau membuat kelas apapun.</p>
    </div>
    @endforelse
</div>

<!-- Modal Create Class -->
<div id="createClassModal" class="fixed inset-0 z-50 overflow-y-auto p-3 sm:p-4 md:p-6 bg-gray-900/60 backdrop-blur-xs hidden flex items-center justify-center min-h-screen">
    <div class="fixed inset-0" onclick="document.getElementById('createClassModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden my-auto flex flex-col max-h-[92vh] z-10">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/80 shrink-0">
            <h3 class="font-bold text-lg text-gray-900">Buat Kelas Baru</h3>
            <button type="button" onclick="document.getElementById('createClassModal').classList.add('hidden')" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-200 transition cursor-pointer">
                <i data-feather="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form action="{{ route('pbm.classes.store') }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required placeholder="Cth: Gizi Klinik Tk.3 Kelas A" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Mata Kuliah</label>
                        <input type="text" name="course_code" placeholder="Cth: BDG123" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Mata Kuliah / Blok</label>
                        <input type="text" name="course_name" placeholder="Cth: Asuhan Kebidanan" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Program Studi</label>
                    <select name="unit_page_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                        <option value="">-- Pilih Program Studi --</option>
                        @foreach($unitPages as $page)
                            <option value="{{ $page->id }}">{{ $page->judul }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran</label>
                        <select name="academic_year" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                            @php $currentYear = date('Y'); @endphp
                            @for($i = $currentYear - 1; $i <= $currentYear + 2; $i++)
                                <option value="{{ $i }}/{{ $i+1 }}" {{ $i == $currentYear ? 'selected' : '' }}>{{ $i }}/{{ $i+1 }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                        <select name="semester" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm">
                            <option value="">-- Pilih Semester --</option>
                            @for($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                    <textarea name="description" rows="3" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50 text-sm" placeholder="Deskripsi singkat mengenai kelas..."></textarea>
                </div>
            </div>
            <div class="p-4 sm:p-5 border-t border-gray-100 bg-gray-50/80 flex justify-end gap-3 shrink-0">
                <button type="button" onclick="document.getElementById('createClassModal').classList.add('hidden')" class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-200 rounded-lg text-sm cursor-pointer transition">Batal</button>
                <button type="submit" class="pbm-primary px-5 py-2 rounded-lg font-medium hover:opacity-90 shadow-sm text-sm cursor-pointer transition">Simpan Kelas</button>
            </div>
        </form>
    </div>
</div>
@endsection
