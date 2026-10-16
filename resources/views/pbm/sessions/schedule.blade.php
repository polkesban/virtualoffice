@extends('pbm.layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Jadwal Sesi</h1>
    <p class="text-gray-500 mt-1">Semua sesi pertemuan yang terjadwal</p>
</div>

@if($upcomingSessions->isEmpty() && $pastSessions->isEmpty())
<div class="bg-white rounded-xl border border-dashed border-gray-300 p-12 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 mx-auto mb-4">
        <i data-feather="calendar" class="w-8 h-8"></i>
    </div>
    <h3 class="font-semibold text-gray-900">Belum ada jadwal</h3>
    <p class="text-gray-500 text-sm mt-1">Belum ada sesi pertemuan yang terjadwal untuk kelas Anda.</p>
</div>
@endif

@if($upcomingSessions->isNotEmpty())
<div class="mb-8">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> Mendatang
    </h2>
    <div class="space-y-3">
        @foreach($upcomingSessions as $session)
        <a href="{{ route('pbm.classes.sessions.show', [$session->clinical_class_id, $session->id]) }}" class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-start gap-4 hover:border-sky-300 transition group block">
            <div class="w-14 h-14 bg-sky-50 rounded-lg flex flex-col items-center justify-center shrink-0 border border-sky-100 group-hover:bg-sky-500 group-hover:text-white transition">
                <span class="text-xs font-semibold">{{ $session->start_time->format('M') }}</span>
                <span class="text-lg font-bold leading-none">{{ $session->start_time->format('d') }}</span>
            </div>
            <div class="flex-1">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1">
                    <h3 class="font-bold text-gray-900 group-hover:text-sky-600 transition">{{ $session->title }}</h3>
                    @php
                        $now = now();
                        $isOngoing = $now->between($session->start_time, $session->end_time);
                    @endphp
                    @if($isOngoing)
                        <span class="bg-green-100 text-green-700 text-xs px-2 py-1 rounded-md font-semibold animate-pulse">Sedang Berlangsung</span>
                    @else
                        <span class="bg-sky-100 text-sky-700 text-xs px-2 py-1 rounded-md font-semibold">{{ $session->start_time->diffForHumans() }}</span>
                    @endif
                </div>
                <div class="text-sm text-sky-600 font-medium mt-0.5">{{ $session->clinicalClass->name ?? '-' }}</div>
                <div class="text-sm text-gray-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    <span class="flex items-center gap-1"><i data-feather="clock" class="w-3 h-3"></i> {{ $session->start_time->format('H:i') }} - {{ $session->end_time->format('H:i') }}</span>
                    @if($session->location_name)
                    <span class="flex items-center gap-1"><i data-feather="map-pin" class="w-3 h-3"></i> {{ $session->location_name }}</span>
                    @endif
                </div>
            </div>
            <div class="self-center">
                <i data-feather="chevron-right" class="text-gray-400 group-hover:text-sky-500 transition"></i>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

@if($pastSessions->isNotEmpty())
<div>
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <span class="w-2 h-2 bg-gray-400 rounded-full"></span> Sudah Lewat
    </h2>
    <div class="space-y-3 opacity-75">
        @foreach($pastSessions as $session)
        <a href="{{ route('pbm.classes.sessions.show', [$session->clinical_class_id, $session->id]) }}" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex items-center gap-4 hover:border-gray-300 transition group block">
            <div class="w-12 h-12 bg-gray-50 rounded-lg flex flex-col items-center justify-center shrink-0 border border-gray-100">
                <span class="text-xs font-semibold text-gray-500">{{ $session->start_time->format('M') }}</span>
                <span class="text-lg font-bold leading-none text-gray-600">{{ $session->start_time->format('d') }}</span>
            </div>
            <div class="flex-1">
                <h3 class="font-medium text-gray-700 group-hover:text-gray-900 transition">{{ $session->title }}</h3>
                <div class="text-xs text-gray-500">{{ $session->clinicalClass->name ?? '-' }} · {{ $session->start_time->format('d M Y H:i') }}</div>
            </div>
            <div class="self-center">
                <i data-feather="chevron-right" class="text-gray-300"></i>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif
@endsection
