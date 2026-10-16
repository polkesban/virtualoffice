@extends('admin.layouts.admin')
@section('title', 'Konfigurasi Sidebar Admin')

@section('content')
<div class="p-6 max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white">⚙️ Konfigurasi Sidebar Admin</h1>
    </div>

    @if(session('success'))
    <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 p-4 text-sm text-green-700 dark:text-green-400">
        {{ session('success') }}
    </div>
    @endif

    <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 p-4 text-sm text-blue-700 dark:text-blue-300">
        <p class="font-bold">ℹ️ Panduan:</p>
        <ul class="list-disc list-inside text-xs mt-1 space-y-0.5">
            <li>Ubah <strong>Urutan</strong> (angka lebih kecil = muncul lebih atas di sidebar).</li>
            <li><strong>Label</strong> dan <strong>Ikon</strong> — kosongkan untuk menggunakan default bawaan sistem.</li>
            <li><strong>Sembunyikan</strong> = item tidak muncul di sidebar untuk semua pengguna (super admin tetap bisa melihat).</li>
            <li>Untuk menu publik-terkelola seperti <strong>Galeri</strong>, <strong>Agenda</strong>, <strong>Pelatihan</strong>, dan unit terkait, status sembunyikan juga memengaruhi sidebar publik.</li>
            <li>Klik <strong>Simpan Semua</strong> setelah selesai mengonfigurasi.</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('admin.nav-admin.update') }}">
        @csrf
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-x-auto">
            <table class="w-full text-sm divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-500">Key</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-500">Label (kosong = default)</th>
                        <th class="text-left px-4 py-3 text-xs font-bold text-gray-500">Ikon</th>
                        <th class="text-center px-4 py-3 text-xs font-bold text-gray-500 w-24">Urutan</th>
                        <th class="text-center px-4 py-3 text-xs font-bold text-gray-500 w-28">Sembunyikan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($items as $i => $item)
                    <tr class="{{ $item->globally_hidden ? 'opacity-50' : '' }} hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                        <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $item->menu_key }}</td>
                        <td class="px-4 py-2">
                            <input type="text" name="items[{{ $i }}][custom_label]" value="{{ $item->custom_label }}"
                                placeholder="default"
                                class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs px-2 py-1.5 focus:ring-2 focus:ring-indigo-400 text-gray-900 dark:text-white">
                        </td>
                        <td class="px-4 py-2">
                            <input type="text" name="items[{{ $i }}][custom_icon]" value="{{ $item->custom_icon }}"
                                placeholder="default"
                                class="w-20 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs px-2 py-1.5 focus:ring-2 focus:ring-indigo-400 text-gray-900 dark:text-white">
                        </td>
                        <td class="px-4 py-2 text-center">
                            <input type="number" name="items[{{ $i }}][urutan]" value="{{ $item->urutan }}"
                                min="0" max="9999"
                                class="w-20 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs px-2 py-1.5 text-center focus:ring-2 focus:ring-indigo-400 text-gray-900 dark:text-white">
                        </td>
                        <td class="px-4 py-2 text-center">
                            <input type="checkbox" name="items[{{ $i }}][globally_hidden]" value="1"
                                {{ $item->globally_hidden ? 'checked' : '' }}
                                class="rounded border-gray-300 text-red-500">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex justify-end pt-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
                💾 Simpan Semua
            </button>
        </div>
    </form>

</div>
@endsection
