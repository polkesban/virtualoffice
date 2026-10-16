@extends('layouts.app')

@section('title', 'Sejarah')
@section('description', 'Sejarah Politeknik Kesehatan Kemenkes Bandung sejak berdiri tahun 2001 hingga saat ini.')

@section('content')
<div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-gray-950">

    {{-- Hero Banner --}}
    <div class="bg-gradient-to-br from-brand-800 to-brand-950 py-16">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-[11px] font-black uppercase tracking-widest text-brand-300 mb-2">Tentang Kami</p>
            <h1 class="text-3xl sm:text-4xl font-black text-white">Sejarah Poltekkes Kemenkes Bandung</h1>
            <nav class="mt-4 flex justify-center gap-1 text-sm text-brand-300" aria-label="Breadcrumb">
                <a href="{{ url('/') }}" class="hover:text-white transition-colors">Beranda</a>
                <span class="text-brand-600 mx-1">/</span>
                <span class="text-white">Sejarah</span>
            </nav>
        </div>
    </div>

    {{-- Profil Sub-Navigation --}}
    <div class="border-b border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <nav class="flex gap-0 overflow-x-auto scrollbar-none" aria-label="Navigasi profil">
                @php $tabs = [
                    ['href' => '/profil/sejarah',    'label' => 'Sejarah'],
                    ['href' => '/profil/visi-misi',  'label' => 'Visi & Misi'],
                    ['href' => '/profil/struktur',   'label' => 'Struktur Organisasi'],
                    ['href' => '/profil/akreditasi', 'label' => 'Akreditasi'],
                ]; @endphp
                @foreach($tabs as $tab)
                <a href="{{ url($tab['href']) }}"
                   class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition-colors
                          {{ request()->is(ltrim($tab['href'], '/'))
                             ? 'border-brand-600 bg-brand-50 text-brand-800 shadow-sm dark:bg-brand-950/40 dark:text-brand-300'
                             : 'border-transparent text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' }}">
                    {{ $tab['label'] }}
                </a>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Content --}}
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="prose prose-lg max-w-none rounded-3xl border border-slate-200 bg-white p-7 text-slate-800 shadow-sm prose-headings:text-slate-950 prose-p:text-slate-700 prose-strong:text-slate-950 dark:prose-invert dark:border-gray-800 dark:bg-gray-900 dark:prose-p:text-gray-200">

            <div class="not-prose mb-8 rounded-2xl border border-brand-200 bg-brand-50 p-5 shadow-sm dark:border-brand-900 dark:bg-brand-950">
                <p class="text-sm font-semibold text-brand-800 dark:text-brand-300">
                    🏛️ Ditetapkan berdasarkan SK Menkes Nomor 298/MenkesKessos/SK/IV/2001 tanggal 16 April 2001
                </p>
            </div>

            <p>Politeknik Kesehatan Kementerian Kesehatan Bandung yang ditetapkan berdasarkan Surat Keputusan Menteri Kesehatan dan Kesejahteraan Sosial RI Nomor 298/MenkesKessos/SK/IV/2001 tanggal 16 April 2001, merupakan unit pelaksana teknis di lingkungan Kementerian Kesehatan yang berada di bawah pembinaan Badan Pengembangan dan Pemberdayaan Sumber Daya Manusia (PPSDM) Kesehatan.</p>

            <p>Poltekkes Kemenkes Bandung dipimpin oleh Direktur yang bertanggung jawab kepada Kepala Badan Pengembangan dan Pemberdayaan Sumber Daya Manusia (PPSDM) Kesehatan Kementerian Kesehatan dan pembinaan secara teknis edukatif dilakukan oleh Pusat Pendidikan dan Pelatihan Tenaga Kesehatan (Pusdiklatnakes). Pembinaan secara administrasi dilakukan oleh Sekretariat Badan PPSDM Kesehatan.</p>

            <p>Berdasarkan Peraturan Presiden Nomor 18 tahun 2021 tentang Kementerian Kesehatan yang ditandatangani oleh Presiden Jokowi pada tanggal 17 Maret 2021, Badan Pengembangan dan Pemberdayaan Sumber Daya Manusia (PPSDM) dihapuskan dan dilebur ke dalam direktorat baru yaitu Ditjen Tenaga Kesehatan yang dipimpin seorang Dirjen.</p>

            <p>Pada tahun 2009 melalui Surat Keputusan Menteri Keuangan Republik Indonesia Nomor 499/KMK.05/2009 tentang Penetapan Politeknik Kesehatan Bandung pada Departemen Kesehatan sebagai Instansi Pemerintah yang menerapkan Pengelolaan Keuangan Badan Layanan Umum, maka Poltekkes Depkes Bandung menyelenggarakan layanan Tri Dharma Perguruan Tinggi dengan pendekatan pengelolaan keuangan BLU.</p>

            <p>Kemudian seiring dengan perubahan nama kelembagaan dari Departemen Kesehatan menjadi Kementerian Kesehatan, melalui surat Sekretariat Jenderal Kementerian Kesehatan RI Nomor TU 05.02/II/II/1535/2010, tanggal 18 Februari 2010 tentang Perubahan Nomenklatur Departemen Kesehatan RI menjadi Kementerian Kesehatan RI, maka Politeknik Kesehatan Bandung mengalami perubahan menjadi Politeknik Kesehatan Kemenkes Bandung.</p>

            <p>Poltekkes Kemenkes Bandung telah mendapatkan Piala Citra Pelayanan Prima dari Presiden RI. Pada tahun 2008, dengan komitmen kuat pertama kalinya mendapatkan sertifikat ISO 9001:2008 dari SAI Global hingga tahun 2011, secara reguler, setiap tahunnya dilakukan surveillance. Berdasarkan keputusan Menteri Keuangan tanggal 18 Desember 2009 No. 499/KMK.05/2009, Politeknik Kesehatan Kemenkes Bandung telah ditetapkan sebagai Badan Layanan Umum (BLU).</p>

            <p>Dengan demikian, maka Politeknik Kesehatan Kemenkes Bandung harus meningkatkan kualitas pengelolaan keuangan untuk dapat lebih meningkatkan kualitas kinerja, khususnya kualitas pelayanan di bidang pendidikan kesehatan.</p>

        </div>

        {{-- Timeline milestones --}}
        <div class="mt-12 rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-6 text-xl font-extrabold text-slate-950 dark:text-white">Tonggak Sejarah</h2>
            <div class="relative space-y-8 border-l-2 border-brand-200 pl-6 dark:border-brand-800">
                @php $milestones = [
                    ['year' => '2001', 'event' => 'Berdiri berdasarkan SK Menkes No. 298/MenkesKessos/SK/IV/2001'],
                    ['year' => '2008', 'event' => 'Meraih Piala Citra Pelayanan Prima dari Presiden RI & sertifikasi ISO 9001:2008'],
                    ['year' => '2009', 'event' => 'Ditetapkan sebagai Badan Layanan Umum (BLU) oleh Menteri Keuangan'],
                    ['year' => '2010', 'event' => 'Berubah nama menjadi Politeknik Kesehatan Kemenkes Bandung'],
                    ['year' => '2021', 'event' => 'Perpres No. 18/2021 – PPSDM dilebur ke Ditjen Tenaga Kesehatan'],
                ]; @endphp
                @foreach($milestones as $m)
                <div class="relative">
                    <div class="absolute top-1 -left-8 h-3 w-3 rounded-full border-2 border-white bg-brand-600 dark:border-gray-950"></div>
                    <span class="text-xs font-black uppercase tracking-wider text-brand-700 dark:text-brand-400">{{ $m['year'] }}</span>
                    <p class="mt-1 text-slate-700 dark:text-gray-300">{{ $m['event'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
