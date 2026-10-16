@extends('pbm.layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('pbm.classes.show', $class) }}" class="text-sky-600 hover:underline text-sm flex items-center gap-1 mb-2">
        <i data-feather="arrow-left" class="w-4 h-4"></i> Kembali ke Kelas
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Buat Sesi Pertemuan</h1>
    <p class="text-gray-500 mt-1">Kelas: {{ $class->name }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <form action="{{ route('pbm.classes.sessions.store', $class) }}" method="POST" class="p-6">
        @csrf
        <div class="space-y-6">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Sesi / Pokok Bahasan <span class="text-red-500">*</span></label>
                <input type="text" name="title" required placeholder="Cth: Praktik Pemasangan Infus" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="start_time" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="end_time" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-3">Pengaturan Kehadiran</h4>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="require_qr" value="1" checked class="rounded text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-medium text-gray-700">Wajib Scan QR Code</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="require_gps" value="1" class="rounded text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-medium text-gray-700">Wajib GPS (Radius 100m)</span>
                        </label>
                    </div>
                    
                    <div class="text-xs text-gray-500 mt-1">
                        <i data-feather="info" class="w-3 h-3 inline"></i> Jika GPS diaktifkan, pastikan Anda menyetel titik koordinat lokasi di halaman edit setelah menyimpan sesi ini.
                    </div>
                </div>
            </div>

            <!-- Pengaturan Lokasi Praktik -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold text-gray-900">Lokasi Praktik (Opsional)</h4>
                    <button type="button" onclick="addLocation()" class="text-xs text-sky-600 hover:text-sky-700 font-medium">+ Tambah Lokasi</button>
                </div>
                <div id="locations-container" class="space-y-2">
                    <div class="flex gap-2 items-center location-item">
                        <input type="text" placeholder="Cth: RS Hasan Sadikin - Ruang Anggrek" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onkeyup="updateLocationsJson()">
                        <button type="button" onclick="this.parentElement.remove(); updateLocationsJson()" class="text-red-500 hover:text-red-700 p-2"><i data-feather="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <input type="hidden" name="lokasi_praktik" id="lokasi_praktik_json" value="">
            </div>

            <!-- Pengaturan Shift -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold text-gray-900">Pengaturan Shift (Opsional)</h4>
                    <button type="button" onclick="addShift()" class="text-xs text-sky-600 hover:text-sky-700 font-medium">+ Tambah Shift</button>
                </div>
                <div class="text-xs text-gray-500 mb-3">Tambahkan shift jika mahasiswa dibagi ke dalam kelompok waktu yang berbeda di hari yang sama.</div>
                <div id="shifts-container" class="space-y-2">
                    <!-- Shift items will be appended here -->
                </div>
                <input type="hidden" name="shifts" id="shifts_json" value="">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Tambahan</label>
                <textarea name="description" rows="3" placeholder="Instruksi untuk mahasiswa..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50"></textarea>
            </div>
            
            <script>
                function updateLocationsJson() {
                    const inputs = document.querySelectorAll('#locations-container input[type="text"]');
                    const locations = Array.from(inputs).map(input => input.value.trim()).filter(val => val !== '');
                    document.getElementById('lokasi_praktik_json').value = locations.length > 0 ? JSON.stringify(locations) : '';
                }

                function addLocation() {
                    const container = document.getElementById('locations-container');
                    const div = document.createElement('div');
                    div.className = 'flex gap-2 items-center location-item';
                    div.innerHTML = `
                        <input type="text" placeholder="Nama lokasi..." class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onkeyup="updateLocationsJson()">
                        <button type="button" onclick="this.parentElement.remove(); updateLocationsJson()" class="text-red-500 hover:text-red-700 p-2"><i data-feather="trash-2" class="w-4 h-4"></i></button>
                    `;
                    container.appendChild(div);
                    feather.replace();
                }

                function updateShiftsJson() {
                    const items = document.querySelectorAll('.shift-item');
                    const shifts = Array.from(items).map(item => {
                        return {
                            name: item.querySelector('.shift-name').value.trim(),
                            start: item.querySelector('.shift-start').value,
                            end: item.querySelector('.shift-end').value
                        };
                    }).filter(shift => shift.name !== '');
                    document.getElementById('shifts_json').value = shifts.length > 0 ? JSON.stringify(shifts) : '';
                }

                function addShift() {
                    const container = document.getElementById('shifts-container');
                    const div = document.createElement('div');
                    div.className = 'flex gap-2 items-center shift-item bg-white p-2 border border-gray-200 rounded-lg';
                    div.innerHTML = `
                        <input type="text" placeholder="Nama Shift (Cth: Pagi)" class="shift-name w-1/3 rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onkeyup="updateShiftsJson()">
                        <input type="time" class="shift-start w-1/3 rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onchange="updateShiftsJson()">
                        <span class="text-gray-400">-</span>
                        <input type="time" class="shift-end w-1/3 rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onchange="updateShiftsJson()">
                        <button type="button" onclick="this.parentElement.remove(); updateShiftsJson()" class="text-red-500 hover:text-red-700 p-2"><i data-feather="trash-2" class="w-4 h-4"></i></button>
                    `;
                    container.appendChild(div);
                    feather.replace();
                }
            </script>
            
            <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                <a href="{{ route('pbm.classes.show', $class) }}" class="px-5 py-2 text-gray-600 font-medium hover:bg-gray-100 rounded-lg transition">Batal</a>
                <button type="submit" class="pbm-primary px-5 py-2 rounded-lg font-medium shadow-sm hover:opacity-90">Simpan Sesi</button>
            </div>
            
        </div>
    </form>
</div>
@endsection
