@extends('admin.layouts.admin')
@section('title', 'Kelola Navigasi Publik')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">🧭 Kelola Navigasi Publik</h1>
    </div>

    @if(session('success'))
    <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 p-4 text-sm text-green-700 dark:text-green-400">
        {{ session('success') }}
    </div>
    @endif

    {{-- Info box --}}
    <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 p-4 text-sm text-blue-700 dark:text-blue-300 space-y-1">
        <p class="font-bold">ℹ️ Panduan:</p>
        <ul class="list-disc list-inside space-y-0.5 text-xs">
            <li>Item tanpa <strong>Parent</strong> = item tingkat atas (top-level) di navbar.</li>
            <li>Item dengan <strong>Parent</strong> = sub-menu (dropdown) di bawah parent-nya.</li>
            <li>Biarkan <strong>URL</strong> kosong jika item hanya sebagai judul dropdown tanpa link langsung.</li>
            <li>Klik ▲▼ untuk mengubah urutan tampil di navbar.</li>
            <li>Centang / kosongkan <strong>Aktif</strong> untuk menampilkan / menyembunyikan item.</li>
        </ul>
    </div>

    {{-- Existing items table --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-x-auto">
        <table class="w-full text-sm divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500 w-8">#</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500">Label</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500">URL</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500">Parent</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-500">Target</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-500">Aktif</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-500">Urutan</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($items as $item)
                    {{-- Top level item --}}
                    <tr class="bg-gray-50/50 dark:bg-gray-800/20 hover:bg-gray-100 dark:hover:bg-gray-800/40">
                        <td class="px-4 py-2 text-gray-400 font-mono text-xs">{{ $item->id }}</td>
                        <td class="px-4 py-2 font-semibold text-gray-900 dark:text-white">
                            {{ $item->icon ? $item->icon . ' ' : '' }}{{ $item->label }}
                            @if(!$item->href)<span class="ml-1 text-xs text-indigo-500 font-normal">(dropdown)</span>@endif
                        </td>
                        <td class="px-4 py-2 text-gray-500 font-mono text-xs">{{ $item->href ?: '—' }}</td>
                        <td class="px-4 py-2 text-gray-400 text-xs">—</td>
                        <td class="px-4 py-2 text-gray-400 text-xs">{{ $item->target }}</td>
                        <td class="px-4 py-2 text-center">
                            <form method="POST" action="{{ route('admin.nav-menus.toggle', $item) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="{{ $item->aktif ? 'text-green-600' : 'text-gray-400' }} hover:opacity-70 text-lg" title="{{ $item->aktif ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}">
                                    {{ $item->aktif ? '✅' : '⬜' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-center space-x-1">
                            <form method="POST" action="{{ route('admin.nav-menus.move', $item) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="text-gray-400 hover:text-gray-700 dark:hover:text-white">▲</button>
                            </form>
                            <form method="POST" action="{{ route('admin.nav-menus.move', $item) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="text-gray-400 hover:text-gray-700 dark:hover:text-white">▼</button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <button onclick="openEdit({{ $item->id }}, {{ json_encode($item->label) }}, {{ json_encode($item->href) }}, {{ json_encode($item->icon) }}, null, {{ json_encode($item->target) }}, {{ $item->aktif ? 'true' : 'false' }})"
                                class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Edit</button>
                            <form method="POST" action="{{ route('admin.nav-menus.destroy', $item) }}" class="inline" onsubmit="return confirm('Hapus menu \'{{ $item->label }}\'? Sub-menu di bawahnya akan menjadi top-level.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline font-semibold">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    {{-- Sub-items --}}
                    @foreach($item->children as $child)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        <td class="px-4 py-2 text-gray-400 font-mono text-xs">{{ $child->id }}</td>
                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300 pl-10">
                            ↳ {{ $child->icon ? $child->icon . ' ' : '' }}{{ $child->label }}
                        </td>
                        <td class="px-4 py-2 text-gray-500 font-mono text-xs">{{ $child->href ?: '—' }}</td>
                        <td class="px-4 py-2 text-gray-400 text-xs">{{ $item->label }}</td>
                        <td class="px-4 py-2 text-gray-400 text-xs">{{ $child->target }}</td>
                        <td class="px-4 py-2 text-center">
                            <form method="POST" action="{{ route('admin.nav-menus.toggle', $child) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="{{ $child->aktif ? 'text-green-600' : 'text-gray-400' }} hover:opacity-70 text-lg">
                                    {{ $child->aktif ? '✅' : '⬜' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-center space-x-1">
                            <form method="POST" action="{{ route('admin.nav-menus.move', $child) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="text-gray-400 hover:text-gray-700 dark:hover:text-white">▲</button>
                            </form>
                            <form method="POST" action="{{ route('admin.nav-menus.move', $child) }}" class="inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="text-gray-400 hover:text-gray-700 dark:hover:text-white">▼</button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <button onclick="openEdit({{ $child->id }}, {{ json_encode($child->label) }}, {{ json_encode($child->href) }}, {{ json_encode($child->icon) }}, {{ $child->parent_id }}, {{ json_encode($child->target) }}, {{ $child->aktif ? 'true' : 'false' }})"
                                class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">Edit</button>
                            <form method="POST" action="{{ route('admin.nav-menus.destroy', $child) }}" class="inline" onsubmit="return confirm('Hapus menu \'{{ $child->label }}\'?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline font-semibold">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add new item --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-6">
        <h2 class="text-sm font-extrabold text-gray-900 dark:text-white mb-4">➕ Tambah Item Menu Baru</h2>
        <form method="POST" action="{{ route('admin.nav-menus.store') }}" class="space-y-4">
            @csrf
            @if($errors->any())
            <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 p-3 text-sm text-red-700 dark:text-red-400">
                <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Label <span class="text-red-500">*</span></label>
                    <input type="text" name="label" value="{{ old('label') }}" placeholder="mis: Tentang Kami"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-400 text-gray-900 dark:text-white" required>
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">URL (kosongkan = dropdown)</label>
                    <input type="text" name="href" value="{{ old('href') }}" placeholder="mis: /tentang"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-400 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Ikon (emoji)</label>
                    <input type="text" name="icon" value="{{ old('icon') }}" placeholder="mis: 📄"
                        class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-400 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Parent (opsional)</label>
                    <select name="parent_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-400 text-gray-900 dark:text-white">
                        <option value="">— Top Level —</option>
                        @foreach($parents as $p)
                        <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Target</label>
                    <select name="target" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:ring-2 focus:ring-indigo-400 text-gray-900 dark:text-white">
                        <option value="_self" {{ old('target','_self') === '_self' ? 'selected' : '' }}>_self (tab sama)</option>
                        <option value="_blank" {{ old('target') === '_blank' ? 'selected' : '' }}>_blank (tab baru)</option>
                    </select>
                </div>
                <div class="flex items-end mb-0.5">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="aktif" value="1" checked
                            class="rounded border-gray-300 text-indigo-600">
                        Langsung aktif
                    </label>
                </div>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                ➕ Tambah
            </button>
        </form>
    </div>

</div>

{{-- Edit modal --}}
<div id="editModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-4">
        <h3 class="font-extrabold text-gray-900 dark:text-white">✏️ Edit Menu</h3>
        <form id="editForm" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Label</label>
                    <input id="edit_label" type="text" name="label" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-white" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">URL</label>
                    <input id="edit_href" type="text" name="href" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Ikon</label>
                    <input id="edit_icon" type="text" name="icon" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Parent</label>
                    <select id="edit_parent" name="parent_id" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-white">
                        <option value="">— Top Level —</option>
                        @foreach($parents as $p)
                        <option value="{{ $p->id }}">{{ $p->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Target</label>
                    <select id="edit_target" name="target" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-white">
                        <option value="_self">_self</option>
                        <option value="_blank">_blank</option>
                    </select>
                </div>
                <div class="flex items-end pb-0.5">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input id="edit_aktif" type="checkbox" name="aktif" value="1" class="rounded border-gray-300 text-indigo-600"> Aktif
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeEdit()" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:underline">Batal</button>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2 rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, label, href, icon, parentId, target, aktif) {
    document.getElementById('editForm').action = "{{ url('admin/nav-menus') }}/" + id;
    document.getElementById('edit_label').value  = label  || '';
    document.getElementById('edit_href').value   = href   || '';
    document.getElementById('edit_icon').value   = icon   || '';
    document.getElementById('edit_target').value = target || '_self';
    document.getElementById('edit_aktif').checked = aktif;
    const sel = document.getElementById('edit_parent');
    sel.value = parentId || '';
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>
@endsection
