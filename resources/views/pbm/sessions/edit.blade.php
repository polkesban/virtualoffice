@extends('pbm.layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('pbm.classes.sessions.show', [$class, $session]) }}" class="text-sky-600 hover:underline text-sm flex items-center gap-1 mb-2">
        <i data-feather="arrow-left" class="w-4 h-4"></i> Kembali ke Sesi
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Edit Sesi Pertemuan</h1>
    <p class="text-gray-500 mt-1">Kelas: {{ $class->name }}</p>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <form action="{{ route('pbm.classes.sessions.update', [$class, $session]) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="space-y-6">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Sesi / Pokok Bahasan <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title', $session->title) }}" required placeholder="Cth: Praktik Pemasangan Infus" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Mulai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="start_time" value="{{ old('start_time', $session->start_time->format('Y-m-d\TH:i')) }}" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Selesai <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="end_time" value="{{ old('end_time', $session->end_time->format('Y-m-d\TH:i')) }}" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-3">Pengaturan Kehadiran (Opsional)</h4>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="number" step="any" name="location_lat" value="{{ old('location_lat', $session->location_lat) }}" placeholder="Cth: -6.893192" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="number" step="any" name="location_lng" value="{{ old('location_lng', $session->location_lng) }}" placeholder="Cth: 107.610262" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Radius (meter)</label>
                            <input type="number" name="location_radius" value="{{ old('location_radius', $session->location_radius ?? 100) }}" placeholder="100" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="require_qr" value="1" {{ old('require_qr', $session->require_qr) ? 'checked' : '' }} class="rounded text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-medium text-gray-700">Wajib Scan QR Code</span>
                        </label>
                        
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="require_gps" value="1" {{ old('require_gps', $session->require_gps) ? 'checked' : '' }} class="rounded text-sky-600 focus:ring-sky-500">
                            <span class="text-sm font-medium text-gray-700">Wajib GPS (Radius)</span>
                        </label>
                    </div>
                    
                    <div class="text-xs text-gray-500 mt-1">
                        <i data-feather="info" class="w-3 h-3 inline"></i> Isi Latitude & Longitude dari Google Maps agar GPS validasi bisa berfungsi.
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
                    @php $locations = old('lokasi_praktik') ? json_decode(old('lokasi_praktik'), true) : ($session->lokasi_praktik ?? []); @endphp
                    @foreach($locations as $loc)
                    <div class="flex gap-2 items-center location-item">
                        <input type="text" value="{{ is_string($loc) ? $loc : '' }}" placeholder="Cth: RS Hasan Sadikin - Ruang Anggrek" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onkeyup="updateLocationsJson()">
                        <button type="button" onclick="this.parentElement.remove(); updateLocationsJson()" class="text-red-500 hover:text-red-700 p-2"><i data-feather="trash-2" class="w-4 h-4"></i></button>
                    </div>
                    @endforeach
                </div>
                <input type="hidden" name="lokasi_praktik" id="lokasi_praktik_json" value="{{ old('lokasi_praktik', json_encode($session->lokasi_praktik)) }}">
            </div>

            <!-- Pengaturan Shift -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold text-gray-900">Pengaturan Shift (Opsional)</h4>
                    <button type="button" onclick="addShift()" class="text-xs text-sky-600 hover:text-sky-700 font-medium">+ Tambah Shift</button>
                </div>
                <div class="text-xs text-gray-500 mb-3">Tambahkan shift jika mahasiswa dibagi ke dalam kelompok waktu yang berbeda di hari yang sama.</div>
                <div id="shifts-container" class="space-y-2">
                    @php $shifts = old('shifts') ? json_decode(old('shifts'), true) : ($session->shifts ?? []); @endphp
                    @foreach($shifts as $shift)
                    <div class="flex gap-2 items-center shift-item bg-white p-2 border border-gray-200 rounded-lg">
                        <input type="text" value="{{ $shift['name'] ?? '' }}" placeholder="Nama Shift (Cth: Pagi)" class="shift-name w-1/3 rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onkeyup="updateShiftsJson()">
                        <input type="time" value="{{ $shift['start'] ?? '' }}" class="shift-start w-1/3 rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onchange="updateShiftsJson()">
                        <span class="text-gray-400">-</span>
                        <input type="time" value="{{ $shift['end'] ?? '' }}" class="shift-end w-1/3 rounded-lg border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50" onchange="updateShiftsJson()">
                        <button type="button" onclick="this.parentElement.remove(); updateShiftsJson()" class="text-red-500 hover:text-red-700 p-2"><i data-feather="trash-2" class="w-4 h-4"></i></button>
                    </div>
                    @endforeach
                </div>
                <input type="hidden" name="shifts" id="shifts_json" value="{{ old('shifts', json_encode($session->shifts)) }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Tambahan</label>
                <textarea name="description" rows="3" placeholder="Instruksi untuk mahasiswa..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">{{ old('description', $session->description) }}</textarea>
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
                    if (typeof feather !== 'undefined') feather.replace();
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
                    if (typeof feather !== 'undefined') feather.replace();
                }
            </script>
            
            <div class="pt-4 border-t border-gray-100 flex justify-between">
                <button type="submit" form="deleteSessionForm" class="px-4 py-2 text-red-600 font-medium hover:bg-red-50 rounded-lg transition flex items-center gap-2 cursor-pointer">
                    <i data-feather="trash-2" class="w-4 h-4"></i> Hapus Sesi
                </button>
                <div class="flex gap-3">
                    <a href="{{ route('pbm.classes.sessions.show', [$class, $session]) }}" class="px-5 py-2 text-gray-600 font-medium hover:bg-gray-100 rounded-lg transition">Batal</a>
                    <button type="submit" class="pbm-primary px-5 py-2 rounded-lg font-medium shadow-sm hover:opacity-90">Simpan Perubahan</button>
                </div>
            </div>
            
        </div>
    </form>

    <form id="deleteSessionForm" action="{{ route('pbm.classes.sessions.destroy', [$class, $session]) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus sesi ini? Data kehadiran juga akan terhapus.')" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
