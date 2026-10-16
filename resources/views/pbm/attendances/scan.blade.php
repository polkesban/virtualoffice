@extends('pbm.layouts.app')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="p-6 bg-gradient-to-r from-sky-500 to-blue-600 text-white text-center">
            <h2 class="text-2xl font-bold mb-1">Presensi Kehadiran</h2>
            <p class="text-sky-100 text-sm opacity-90">{{ $session->title }}</p>
        </div>
        
        <div class="p-6">
            @if(session('error'))
            <div class="bg-red-50 text-red-700 p-3 rounded-lg text-sm mb-4 border border-red-100">
                <i data-feather="alert-circle" class="w-4 h-4 inline mr-1"></i> {{ session('error') }}
            </div>
            @endif

            <form action="{{ route('pbm.sessions.process-scan', $session) }}" method="POST" id="scan-form">
                @csrf
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">
                <input type="hidden" name="signature_data" id="signature_data">
                
                <div class="space-y-5 mb-6">
                    <!-- Tipe Presensi -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipe Presensi <span class="text-red-500">*</span></label>
                        <div class="flex gap-4">
                            <label class="flex-1 border border-gray-200 rounded-lg p-3 flex items-center gap-3 cursor-pointer hover:bg-sky-50 transition">
                                <input type="radio" name="tipe_presensi" value="datang" checked class="text-sky-600 focus:ring-sky-500 w-4 h-4">
                                <span class="font-medium text-gray-800">Datang (In)</span>
                            </label>
                            <label class="flex-1 border border-gray-200 rounded-lg p-3 flex items-center gap-3 cursor-pointer hover:bg-sky-50 transition">
                                <input type="radio" name="tipe_presensi" value="pulang" class="text-sky-600 focus:ring-sky-500 w-4 h-4">
                                <span class="font-medium text-gray-800">Pulang (Out)</span>
                            </label>
                        </div>
                    </div>

                    @if(!empty($session->shifts))
                    <!-- Shift Praktik -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shift Praktik <span class="text-red-500">*</span></label>
                        <select name="shift_name" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                            <option value="">-- Pilih Shift --</option>
                            @foreach($session->shifts as $shift)
                            <option value="{{ $shift['name'] }}">{{ $shift['name'] }} ({{ $shift['start'] }} - {{ $shift['end'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if(!empty($session->lokasi_praktik))
                    <!-- Lokasi Praktik -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Lokasi Praktik <span class="text-red-500">*</span></label>
                        <select name="lokasi_praktik" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-sky-500 focus:ring focus:ring-sky-200 focus:ring-opacity-50">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach($session->lokasi_praktik as $loc)
                            <option value="{{ $loc }}">{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Signature Pad -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanda Tangan <span class="text-red-500">*</span></label>
                        <div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
                            <canvas id="signature-pad" class="w-full h-40 touch-none"></canvas>
                        </div>
                        <div class="flex justify-end mt-1">
                            <button type="button" id="clear-signature" class="text-xs text-red-500 hover:underline">Hapus Tanda Tangan</button>
                        </div>
                    </div>
                </div>

                <div id="gps-status" class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-center text-sm font-medium text-gray-600 mb-6 flex flex-col items-center justify-center">
                    <div class="animate-pulse flex items-center gap-2">
                        <div class="w-2 h-2 bg-sky-500 rounded-full"></div> Membaca GPS...
                    </div>
                </div>
                
                <button type="submit" id="submit-btn" disabled class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <i data-feather="check-circle" class="w-5 h-5 inline mr-1"></i> Konfirmasi Kehadiran
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');
        const statusDiv = document.getElementById('gps-status');
        const submitBtn = document.getElementById('submit-btn');
        const form = document.getElementById('scan-form');
        const signatureDataInput = document.getElementById('signature_data');
        
        // Initialize Signature Pad
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });

        // Resize canvas correctly
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const w = canvas.offsetWidth;
            const h = canvas.offsetHeight;
            if (w <= 0 || h <= 0) return;
            const data = signaturePad.toData();
            canvas.width = Math.floor(w * ratio);
            canvas.height = Math.floor(h * ratio);
            canvas.style.width = w + 'px';
            canvas.style.height = h + 'px';
            const ctx = canvas.getContext("2d");
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.scale(ratio, ratio);
            signaturePad.clear();
            if (data && data.length > 0) {
                signaturePad.fromData(data);
            }
        }
        window.addEventListener("resize", resizeCanvas);
        setTimeout(resizeCanvas, 60);

        document.getElementById('clear-signature').addEventListener('click', function() {
            signaturePad.clear();
        });

        form.addEventListener('submit', function(e) {
            if (signaturePad.isEmpty()) {
                e.preventDefault();
                alert('Tanda tangan tidak boleh kosong!');
                return false;
            }
            signatureDataInput.value = signaturePad.toDataURL('image/png');
        });
        
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                latInput.value = position.coords.latitude;
                lngInput.value = position.coords.longitude;
                
                statusDiv.innerHTML = `<div class="text-green-600 flex items-center gap-2"><i data-feather="check" class="w-4 h-4"></i> Lokasi GPS Berhasil Ditemukan</div>`;
                if (typeof feather !== 'undefined') { feather.replace(); }
                submitBtn.disabled = false;
            }, function(error) {
                statusDiv.innerHTML = `<div class="text-red-500 flex flex-col items-center"><i data-feather="x-circle" class="w-5 h-5 mb-1"></i> Gagal membaca lokasi. Pastikan GPS aktif dan Anda memberikan izin.</div>`;
                if (typeof feather !== 'undefined') { feather.replace(); }
                // Allow submit if GPS is not strictly required by the session, but we will let server validate it.
                submitBtn.disabled = false; 
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            statusDiv.innerHTML = `<div class="text-red-500">Browser Anda tidak mendukung Geolocation.</div>`;
            submitBtn.disabled = false; // Let server deny it if strictly required
        }
    });
</script>
@endpush
@endsection
