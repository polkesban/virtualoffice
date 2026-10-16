@extends('pbm.layouts.app')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Clinical Instructor (CI)</h1>
        <p class="text-gray-500 mt-1">Validasi kehadiran mahasiswa praktik klinik</p>
    </div>
    @if(Auth::user() && (Auth::user()->isSuperAdmin() || in_array(Auth::user()->role, ['dosen', 'kaprodi', 'admin_jurusan', 'kajur'])))
    <div class="text-xs bg-sky-50 text-sky-700 px-3 py-1.5 rounded-lg border border-sky-100 flex items-center gap-1.5 self-start sm:self-auto font-medium">
        <i data-feather="shield" class="w-3.5 h-3.5 text-sky-600"></i>
        <span>Hak Akses: <strong>{{ ucfirst(str_replace('_', ' ', Auth::user()->role)) }}</strong></span>
    </div>
    @endif
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <h2 class="text-lg font-bold text-gray-900">Menunggu Validasi</h2>
    </div>
    
    <div class="overflow-x-auto swipe-scroll">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Mahasiswa</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Sesi & Kelas</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Waktu Presensi</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="py-3 px-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($attendances as $att)
                <tr class="hover:bg-gray-50 transition">
                    <td class="py-3 px-4">
                        <div class="font-medium text-gray-900">{{ $att->mahasiswa->name ?? 'Mahasiswa' }}</div>
                        <div class="text-xs text-gray-500">{{ $att->mahasiswa->nim ?? '-' }}</div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="font-medium text-gray-900">{{ $att->session->title ?? '-' }}</div>
                        <div class="text-xs text-sky-600">{{ $att->session->clinicalClass->name ?? '-' }}</div>
                    </td>
                    <td class="py-3 px-4 text-sm text-gray-600">
                        @if($att->check_in_time)
                            <div>In: <span class="font-medium">{{ $att->check_in_time->format('d M Y H:i') }}</span></div>
                            @if($att->check_out_time)
                            <div class="mt-1">Out: <span class="font-medium">{{ $att->check_out_time->format('d M Y H:i') }}</span></div>
                            @endif
                        @else
                            <span class="text-gray-400 italic">Manual by Dosen</span>
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
                    <td class="py-3 px-4 text-right">
                        <form action="{{ route('pbm.sessions.attendances.update', [$att->session_id, $att->id]) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="validate_ci" value="1">
                            <button type="submit" class="bg-green-500 text-white px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-green-600 transition shadow-sm flex items-center gap-1 ml-auto">
                                <i data-feather="check" class="w-4 h-4"></i> Validasi
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-gray-500 text-sm">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-green-400 mx-auto mb-4">
                            <i data-feather="check-circle" class="w-8 h-8"></i>
                        </div>
                        Tidak ada presensi yang perlu divalidasi.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
