@extends('admin.layouts.admin')
@section('title', 'Kelola Pengumuman')

@section('content')
<div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">📢 Pengumuman</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola pengumuman resmi kampus</p>
        </div>
        <a href="{{ route('admin.pengumuman.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold transition-colors">
            + Tambah Pengumuman
        </a>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari judul..."
               class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm w-56">
        <select name="prioritas" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Prioritas</option>
            <option value="urgent"  @selected(request('prioritas')==='urgent')>🔴 Urgent</option>
            <option value="penting" @selected(request('prioritas')==='penting')>🟡 Penting</option>
            <option value="normal"  @selected(request('prioritas')==='normal')>🟢 Normal</option>
        </select>
        <select name="aktif" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">Semua Status</option>
            <option value="1" @selected(request('aktif')==='1')>Aktif</option>
            <option value="0" @selected(request('aktif')==='0')>Nonaktif</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-700 text-sm font-medium">Cari</button>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Prioritas</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Aktif</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Periode</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($pengumuman as $p)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white line-clamp-1">{{ $p->judul }}</p>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold
                            {{ $p->prioritas === 'urgent' ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                              : ($p->prioritas === 'penting' ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                              : 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400') }}">
                            {{ ucfirst($p->prioritas) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 hidden md:table-cell">
                        <form method="POST" action="{{ route('admin.pengumuman.toggle', $p) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="px-2 py-0.5 rounded-full text-xs font-bold transition-colors
                                        {{ $p->aktif ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-100' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 hover:bg-gray-200' }}">
                                {{ $p->aktif ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-xs hidden lg:table-cell">
                        {{ $p->tanggal_mulai?->format('d M Y') }} —
                        {{ $p->tanggal_selesai?->format('d M Y') ?? '∞' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if(auth()->user()->isSuperAdmin() || auth()->id() === $p->user_id)
                            <a href="{{ route('admin.pengumuman.edit', $p) }}"
                               class="px-3 py-1 rounded-lg text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-brand-50 hover:text-brand-600 transition-colors">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.pengumuman.destroy', $p) }}"
                                  onsubmit="return confirm('Hapus pengumuman ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 rounded-lg text-xs font-bold bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 transition-colors">
                                    Hapus
                                </button>
                            </form>
                            @else
                            <span class="px-3 py-1 text-xs text-gray-400">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                        Belum ada pengumuman. <a href="{{ route('admin.pengumuman.create') }}" class="text-brand-600 hover:underline">Tambah sekarang</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
