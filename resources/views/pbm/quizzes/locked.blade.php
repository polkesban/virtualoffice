@extends('pbm.layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-12 px-4 sm:px-6">
    <div class="bg-white rounded-3xl shadow-xl border-2 border-red-200 overflow-hidden text-center p-8 sm:p-12 relative">
        <!-- Floating Red Accent Glow -->
        <div class="absolute -top-16 -right-16 w-40 h-40 bg-red-100 rounded-full blur-2xl opacity-60"></div>
        <div class="absolute -bottom-16 -left-16 w-40 h-40 bg-amber-100 rounded-full blur-2xl opacity-60"></div>

        <!-- Lock Icon Badge -->
        <div class="relative mx-auto w-20 h-20 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mb-6 shadow-md border border-red-100">
            <i data-feather="lock" class="w-10 h-10"></i>
        </div>

        <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-red-100 text-red-800 mb-3">
            Sesi Ujian Dikunci (Auto-Lock)
        </span>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-3">
            Akses Ujian Anda Terkunci
        </h1>

        <p class="text-sm sm:text-base text-gray-600 mb-6 max-w-md mx-auto leading-relaxed">
            Sistem pengawas ujian otomatis mendeteksi aktivitas yang melanggar aturan integritas pelaksanaan CBT.
        </p>

        <!-- Violation Info Box -->
        <div class="bg-red-50/70 rounded-2xl p-5 border border-red-200 text-left mb-8 space-y-3">
            <div class="flex items-start gap-3">
                <i data-feather="alert-octagon" class="w-5 h-5 text-red-600 shrink-0 mt-0.5"></i>
                <div class="text-xs sm:text-sm text-red-900">
                    <span class="font-bold block mb-1">Alasan Penguncian:</span>
                    <p class="text-red-700 bg-white/70 p-2.5 rounded-lg border border-red-200 font-mono text-xs">
                        {{ $attempt->lock_reason ?? 'Telah melebihi batas toleransi pelanggaran yang ditentukan pengawas.' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-red-200/60 text-xs">
                <div>
                    <span class="text-gray-500 block">Total Pelanggaran:</span>
                    <span class="font-bold text-red-700 text-sm">{{ $attempt->violation_count }} Pelanggaran</span>
                </div>
                <div>
                    <span class="text-gray-500 block">Batas Maksimal:</span>
                    <span class="font-bold text-gray-700 text-sm">{{ $quiz->max_violations ?? 3 }} Pelanggaran</span>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 text-xs text-gray-600 mb-8 text-left flex items-start gap-3">
            <i data-feather="info" class="w-4 h-4 text-sky-600 shrink-0 mt-0.5"></i>
            <div>
                <strong class="text-gray-900 block mb-0.5">Apa yang harus dilakukan?</strong>
                Silakan hubungi <strong>Dosen Pengampu</strong> atau <strong>Pengawas Ujian</strong>. Pengawas memiliki wewenang untuk meninjau log foto rekaman pengawasan dan membuka kunci ujian Anda jika terbukti tidak disengaja.
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button onclick="window.location.reload()" 
                    class="w-full sm:w-auto px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold text-sm shadow-md transition flex items-center justify-center gap-2">
                <i data-feather="refresh-cw" class="w-4 h-4"></i> Cek Status Buka Kunci
            </button>
            <a href="{{ route('pbm.quizzes.show', $quiz->id) }}" 
               class="w-full sm:w-auto px-6 py-3 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl font-semibold text-sm transition flex items-center justify-center gap-2">
                <i data-feather="arrow-left" class="w-4 h-4"></i> Kembali ke Informasi Kuis
            </a>
        </div>
    </div>
</div>
@endsection
