@extends('admin.layouts.admin')
@section('title', 'Isi Laporan WFH Baru')
@section('page-title', 'Isi Laporan WFH Baru')
@section('page-subtitle', 'Silakan masukkan tanggal dan rincian kegiatan WFH harian Anda.')

@section('content')
<div class="mx-auto max-w-5xl p-6">
    <div class="mb-6">
        <a href="{{ route('admin.wfh.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
            ⬅️ Kembali ke Rekap
        </a>
    </div>

    @if(session('error'))
        <div class="mb-6 rounded-2xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.wfh.store') }}" class="space-y-6">
        @csrf

        <!-- Card: Metadata -->
        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="max-w-md space-y-2">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-200">
                    <span>Tanggal Kegiatan WFH</span>
                    <input type="date" name="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required
                        class="mt-1 w-full rounded-xl border border-gray-250 bg-white px-4 py-2.5 text-sm font-medium text-gray-950 dark:bg-gray-950 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                </label>
                @error('tanggal')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Card: Activities List -->
        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
                <h3 class="text-base font-black text-gray-900 dark:text-white">Rincian Kegiatan / Agenda</h3>
                <button type="button" onclick="addCard()" class="inline-flex items-center gap-2 rounded-xl bg-brand-50 border border-brand-200 px-4 py-2 text-xs font-bold text-brand-700 hover:bg-brand-100 dark:bg-brand-950/40 dark:border-brand-800 dark:text-brand-300 dark:hover:bg-brand-900/50">
                    ➕ Tambah Kegiatan
                </button>
            </div>

            <div id="activities-container" class="space-y-6">
                <!-- Initial Card -->
                <div class="activity-card rounded-2xl border border-gray-200 p-5 dark:border-gray-800 dark:bg-gray-950 relative space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-sm font-bold text-gray-500 dark:text-gray-400">Kegiatan #<span class="row-number">1</span></span>
                        <button type="button" onclick="removeCard(this)" class="text-red-500 hover:text-red-700 text-xs font-bold transition-colors">
                            ❌ Hapus Kegiatan
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Waktu -->
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Waktu Kegiatan</span>
                            <input type="text" name="activities[0][waktu]" placeholder="e.g. 07.30 - 09.00"
                                class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                        </label>

                        <!-- Penanggung Jawab (PJ) -->
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Penanggung Jawab (PJ)</span>
                            <input type="text" name="activities[0][penanggung_jawab]" placeholder="e.g. Kepala Unit / Mandiri"
                                class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                        </label>

                        <!-- Target Waktu -->
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Target Waktu / Output</span>
                            <input type="text" name="activities[0][target_waktu]" placeholder="e.g. 1 Laporan / Selesai"
                                class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                        </label>

                        <!-- Keterangan -->
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Keterangan</span>
                            <input type="text" name="activities[0][keterangan]" placeholder="Keterangan tambahan..."
                                class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                        </label>
                    </div>

                    <!-- Kegiatan / Agenda -->
                    <label class="block space-y-1">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Kegiatan / Agenda (Wajib)</span>
                        <textarea name="activities[0][kegiatan]" rows="3" required placeholder="Tulis rincian kegiatan yang Anda lakukan..."
                            class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500"></textarea>
                    </label>

                    <!-- Hasil Kegiatan -->
                    <label class="block space-y-1">
                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Hasil Kegiatan</span>
                        <textarea name="activities[0][hasil]" rows="2" placeholder="Hasil dari kegiatan..."
                            class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500"></textarea>
                    </label>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <!-- Kendala / Masalah -->
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Kendala / Masalah</span>
                            <textarea name="activities[0][kendala]" rows="2" placeholder="Kendala yang dihadapi (jika ada)..."
                                class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500"></textarea>
                        </label>

                        <!-- Rencana Tindak Lanjut (RTL) -->
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Rencana Tindak Lanjut (RTL)</span>
                            <textarea name="activities[0][rtl]" rows="2" placeholder="Rencana tindak lanjut..."
                                class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500"></textarea>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('admin.wfh.index') }}" class="rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-bold text-gray-750 transition hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-750 dark:text-gray-200">
                    Batal
                </a>
                <button type="submit" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-brand-500">
                    Simpan Laporan WFH
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    let rowIndex = 1;

    function addCard() {
        const container = document.getElementById('activities-container');
        const card = document.createElement('div');
        card.className = 'activity-card rounded-2xl border border-gray-200 p-5 dark:border-gray-800 dark:bg-gray-950 relative space-y-4';
        card.innerHTML = `
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-2">
                <span class="text-sm font-bold text-gray-500 dark:text-gray-400">Kegiatan #<span class="row-number"></span></span>
                <button type="button" onclick="removeCard(this)" class="text-red-500 hover:text-red-700 text-xs font-bold transition-colors">
                    ❌ Hapus Kegiatan
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block space-y-1">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Waktu Kegiatan</span>
                    <input type="text" name="activities[\${rowIndex}][waktu]" placeholder="e.g. 07.30 - 09.00"
                        class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Penanggung Jawab (PJ)</span>
                    <input type="text" name="activities[\${rowIndex}][penanggung_jawab]" placeholder="e.g. Kepala Unit / Mandiri"
                        class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Target Waktu / Output</span>
                    <input type="text" name="activities[\${rowIndex}][target_waktu]" placeholder="e.g. 1 Laporan / Selesai"
                        class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Keterangan</span>
                    <input type="text" name="activities[\${rowIndex}][keterangan]" placeholder="Keterangan tambahan..."
                        class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500">
                </label>
            </div>

            <label class="block space-y-1">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Kegiatan / Agenda (Wajib)</span>
                <textarea name="activities[\${rowIndex}][kegiatan]" rows="3" required placeholder="Tulis rincian kegiatan yang Anda lakukan..."
                    class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500"></textarea>
            </label>

            <label class="block space-y-1">
                <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Hasil Kegiatan</span>
                <textarea name="activities[\${rowIndex}][hasil]" rows="2" placeholder="Hasil dari kegiatan..."
                    class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500"></textarea>
            </label>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block space-y-1">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Kendala / Masalah</span>
                    <textarea name="activities[\${rowIndex}][kendala]" rows="2" placeholder="Kendala yang dihadapi (jika ada)..."
                        class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500"></textarea>
                </label>

                <label class="block space-y-1">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Rencana Tindak Lanjut (RTL)</span>
                    <textarea name="activities[\${rowIndex}][rtl]" rows="2" placeholder="Rencana tindak lanjut..."
                        class="w-full rounded-xl border border-gray-250 bg-white px-3 py-2.5 text-sm dark:bg-gray-900 dark:border-gray-750 dark:text-white outline-none focus:border-brand-500"></textarea>
                </label>
            </div>
        `;
        container.appendChild(card);
        rowIndex++;
        updateRowNumbers();
    }

    function removeCard(btn) {
        const cards = document.querySelectorAll('.activity-card');
        if (cards.length <= 1) {
            alert('Laporan harus memiliki minimal 1 kegiatan.');
            return;
        }
        btn.closest('.activity-card').remove();
        updateRowNumbers();
    }

    function updateRowNumbers() {
        const rowNums = document.querySelectorAll('.row-number');
        rowNums.forEach((el, index) => {
            el.textContent = index + 1;
        });
    }

    // Initialize row numbers
    updateRowNumbers();
</script>
@endsection
