@extends('admin.layouts.admin')

@section('title', 'Editor Situs')
@section('page-title', 'Editor Situs')
@section('page-subtitle', 'Kelola header, footer, dan pengaturan utama beranda untuk super admin')

@section('content')
@php
    $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $quizQuestionsJson = old('home.quiz.questions_json', json_encode($home['quiz']['questions'], $jsonFlags));
    $quizMapJson = old('home.quiz.map_json', json_encode($home['quiz']['map'], $jsonFlags));
    $quizMap2Json = old('home.quiz.map2_json', json_encode($home['quiz']['map2'], $jsonFlags));
    $previewConfig = [
        'assets' => [
            'savedHeaderLogoUrl' => \App\Support\SiteAppearance::storageUrl($header['logo_path'], 'images/logo.png'),
            'defaultHeaderLogoUrl' => asset('images/logo.png'),
            'savedHeroPosterUrl' => \App\Support\SiteAppearance::storageUrl($home['hero']['poster_path'], 'images/og-default.jpg'),
            'defaultHeroPosterUrl' => asset('images/og-default.jpg'),
            'savedHeroVideoLabel' => $home['hero']['video_path']
                ? basename((string) $home['hero']['video_path'])
                : 'Video default website',
        ],
    ];
    $sidebarGuideItems = [
        [
            'label' => 'Beranda, Header, Footer, Quiz, CTA, Newsletter',
            'path' => 'Super Admin > Editor Situs',
            'href' => route('admin.site-appearance.edit'),
            'icon' => '🌐',
            'note' => 'Untuk hero, statistik, header utilitas, footer, copy beranda, dan logika quiz rekomendasi prodi.',
        ],
        [
            'label' => 'Menu Navbar Publik',
            'path' => 'Super Admin > Kelola Navigasi Publik',
            'href' => route('admin.nav-menus.index'),
            'icon' => '🧭',
            'note' => 'Untuk struktur menu utama, dropdown, dan urutan navigasi publik.',
        ],
        [
            'label' => 'Berita',
            'path' => 'Konten > Berita',
            'href' => route('admin.berita.index'),
            'icon' => '📰',
            'note' => 'Untuk card berita beranda serta halaman daftar dan detail berita.',
        ],
        [
            'label' => 'Pengumuman',
            'path' => 'Konten > Pengumuman',
            'href' => route('admin.pengumuman.index'),
            'icon' => '📢',
            'note' => 'Untuk blok pengumuman yang tampil di beranda dan halaman pengumuman.',
        ],
        [
            'label' => 'Galeri',
            'path' => 'Konten > Galeri',
            'href' => route('admin.galeri.index'),
            'icon' => '🖼️',
            'note' => 'Untuk foto galeri yang diprioritaskan tampil di beranda.',
        ],
        [
            'label' => 'Program Studi',
            'path' => 'Akademik & Kemahasiswaan > Program Studi',
            'href' => route('admin.program-studi.index'),
            'icon' => '🎓',
            'note' => 'Untuk data prodi, halaman prodi, dan blok rekomendasi yang dituju oleh quiz.',
        ],
        [
            'label' => 'Mahasiswa Berprestasi',
            'path' => 'Akademik & Kemahasiswaan > Mahasiswa Berprestasi',
            'href' => route('admin.berprestasi.index'),
            'icon' => '🏆',
            'note' => 'Untuk blok mahasiswa berprestasi yang tampil di beranda.',
        ],
        [
            'label' => 'Halaman Statis',
            'path' => 'Super Admin > Kelola Halaman Statis',
            'href' => route('admin.halaman-statis.index'),
            'icon' => '📑',
            'note' => 'Untuk page builder halaman statis dan blok konten tambahan seperti teks, tabel, embed, dan Google Drive viewer.',
        ],
        [
            'label' => 'Halaman Unit / Page Builder',
            'path' => 'Halaman Unit > Halaman Unit Saya',
            'href' => route('admin.halaman-unit.index'),
            'icon' => '🗂️',
            'note' => 'Untuk halaman unit, blok konten builder, event unit, dan blok Google Drive baru.',
        ],
    ];
@endphp
<script>
    window.siteAppearanceEditorPreview = function (config) {
        return {
            assets: config.assets ?? {},
            uploads: {
                headerLogoUrl: null,
                heroPosterUrl: null,
                heroVideoLabel: null,
            },
            header: {
                logoUrl: '',
                logoAlt: '',
                addressText: '',
                addressUrl: '',
                socials: [],
            },
            hero: {
                posterUrl: '',
                badgeText: '',
                titleLine1: '',
                titleHighlight: '',
                titleLine2: '',
                body: '',
                primaryLabel: '',
                secondaryLabel: '',
                scrollHint: '',
                videoLabel: '',
            },
            footer: {
                mapTitle: '',
                addressText: '',
                phoneText: '',
                emailText: '',
                newsletterTitle: '',
                newsletterBody: '',
                newsletterPlaceholder: '',
                newsletterButtonLabel: '',
                bottomText: '',
                quickLinks: [],
                aboutLinks: [],
                policyLinks: [],
            },
            init() {
                this.$nextTick(() => this.refresh());
            },
            fields(name) {
                return Array.from(document.getElementsByName(name));
            },
            inputValue(name, fallback = '') {
                const field = this.fields(name).find((item) => ! ['checkbox', 'radio', 'hidden'].includes(item.type));

                if (field) {
                    return field.value ?? fallback;
                }

                const hidden = this.fields(name).find((item) => item.type === 'hidden');

                return hidden ? (hidden.value ?? fallback) : fallback;
            },
            isChecked(name) {
                const checkbox = this.fields(name).find((item) => item.type === 'checkbox');

                return checkbox ? checkbox.checked : false;
            },
            collectLinks(baseName, count) {
                const links = [];

                for (let index = 0; index < count; index++) {
                    const label = this.inputValue(`${baseName}[${index}][label]`).trim();
                    const href = this.inputValue(`${baseName}[${index}][href]`).trim();

                    if (label !== '' || href !== '') {
                        links.push({
                            label: label || 'Tanpa label',
                            href,
                        });
                    }
                }

                return links;
            },
            handleFilePreview(event, target) {
                const file = event.target.files?.[0] ?? null;

                if (target === 'heroVideoLabel') {
                    this.uploads.heroVideoLabel = file ? file.name : null;
                    this.refresh();

                    return;
                }

                if (! file) {
                    this.uploads[target] = null;
                    this.refresh();

                    return;
                }

                const reader = new FileReader();
                reader.onload = () => {
                    this.uploads[target] = String(reader.result ?? '');
                    this.refresh();
                };
                reader.readAsDataURL(file);
            },
            formatDynamic(text) {
                const now = new Date();

                return String(text ?? '')
                    .replace(/\{year\}/g, String(now.getFullYear()))
                    .replace(/\{next_year\}/g, String(now.getFullYear() + 1));
            },
            refresh() {
                this.header.logoAlt = this.inputValue('header[logo_alt]');
                this.header.addressText = this.inputValue('header[address_text]');
                this.header.addressUrl = this.inputValue('header[address_url]');
                this.header.socials = [
                    { label: 'Instagram', url: this.inputValue('header[socials][instagram]').trim() },
                    { label: 'YouTube', url: this.inputValue('header[socials][youtube]').trim() },
                    { label: 'Facebook', url: this.inputValue('header[socials][facebook]').trim() },
                ].filter((item) => item.url !== '');
                this.header.logoUrl = this.isChecked('header[use_default_logo]')
                    ? this.assets.defaultHeaderLogoUrl
                    : (this.uploads.headerLogoUrl ?? this.assets.savedHeaderLogoUrl);

                this.hero.badgeText = this.inputValue('home[hero][badge_text]');
                this.hero.titleLine1 = this.inputValue('home[hero][title_line_1]');
                this.hero.titleHighlight = this.inputValue('home[hero][title_highlight]');
                this.hero.titleLine2 = this.inputValue('home[hero][title_line_2]');
                this.hero.body = this.inputValue('home[hero][body]');
                this.hero.primaryLabel = this.inputValue('home[hero][primary_label]');
                this.hero.secondaryLabel = this.inputValue('home[hero][secondary_label]');
                this.hero.scrollHint = this.inputValue('home[hero][scroll_hint]');
                this.hero.posterUrl = this.isChecked('home[hero][use_default_poster]')
                    ? this.assets.defaultHeroPosterUrl
                    : (this.uploads.heroPosterUrl ?? this.assets.savedHeroPosterUrl);
                this.hero.videoLabel = this.isChecked('home[hero][use_default_video]')
                    ? 'Video default website'
                    : (this.uploads.heroVideoLabel ?? this.assets.savedHeroVideoLabel);

                this.footer.mapTitle = this.inputValue('footer[map_title]');
                this.footer.addressText = this.inputValue('footer[address_text]');
                this.footer.phoneText = this.inputValue('footer[phone_text]');
                this.footer.emailText = this.inputValue('footer[email_text]');
                this.footer.newsletterTitle = this.inputValue('footer[newsletter_title]');
                this.footer.newsletterBody = this.inputValue('footer[newsletter_body]');
                this.footer.newsletterPlaceholder = this.inputValue('footer[newsletter_placeholder]');
                this.footer.newsletterButtonLabel = this.inputValue('footer[newsletter_button_label]');
                this.footer.bottomText = this.inputValue('footer[bottom_text]');
                this.footer.quickLinks = this.collectLinks('footer[quick_links]', 8);
                this.footer.aboutLinks = this.collectLinks('footer[about_links]', 5);
                this.footer.policyLinks = this.collectLinks('footer[policy_links]', 3);
            },
        };
    };
</script>
<div class="p-6 max-w-7xl mx-auto space-y-6"
     x-data="window.siteAppearanceEditorPreview(@js($previewConfig))"
     x-init="init()"
     @input.debounce.80ms="refresh()"
     @change.debounce.80ms="refresh()">
    @if(session('success'))
    <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-sm text-green-700 dark:text-green-400">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-400">
        <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-brand-100 dark:border-brand-900 bg-white dark:bg-gray-900 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Navigasi</p>
            <h2 class="mt-2 text-lg font-extrabold text-gray-900 dark:text-white">Header memakai menu publik aktif</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Teks alamat, logo, dan tautan sosial diedit di halaman ini. Struktur menu atas tetap dikelola dari modul navigasi publik.</p>
            <a href="{{ route('admin.nav-menus.index') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-sm font-bold text-white hover:bg-brand-500 transition-colors">
                🧭 Buka Kelola Navigasi Publik
            </a>
        </div>

        <div class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Konten Dinamis</p>
            <h2 class="mt-2 text-lg font-extrabold text-gray-900 dark:text-white">Blok beranda yang sudah punya modul sendiri</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Berita, pengumuman, galeri, program studi, dan mahasiswa berprestasi tetap ditarik dari modul admin masing-masing.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('admin.berita.index') }}" class="rounded-xl bg-gray-100 dark:bg-gray-800 px-3 py-2 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">📰 Berita</a>
                <a href="{{ route('admin.pengumuman.index') }}" class="rounded-xl bg-gray-100 dark:bg-gray-800 px-3 py-2 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">📢 Pengumuman</a>
                <a href="{{ route('admin.galeri.index') }}" class="rounded-xl bg-gray-100 dark:bg-gray-800 px-3 py-2 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">🖼️ Galeri</a>
                <a href="{{ route('admin.program-studi.index') }}" class="rounded-xl bg-gray-100 dark:bg-gray-800 px-3 py-2 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">🎓 Program Studi</a>
                <a href="{{ route('admin.berprestasi.index') }}" class="rounded-xl bg-gray-100 dark:bg-gray-800 px-3 py-2 text-xs font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">🏆 Mahasiswa Berprestasi</a>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20 p-5">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700 dark:text-amber-300">Placeholder Teks</p>
            <h2 class="mt-2 text-lg font-extrabold text-amber-950 dark:text-amber-100">Tag dinamis yang didukung</h2>
            <p class="mt-2 text-sm text-amber-800/80 dark:text-amber-100/80">Anda bisa menulis <strong>{'{year}'}</strong> dan <strong>{'{next_year}'}</strong> pada badge hero, badge CTA, dan footer copyright. Nilainya diisi otomatis saat halaman dirender.</p>
        </div>
    </div>

    <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-5 shadow-sm">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Peta Menu Sidebar Admin</p>
                <h2 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white">Letak menu untuk seluruh area yang bisa diedit admin</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gunakan panduan ini jika ingin tahu modul mana di sidebar yang mengontrol bagian tertentu di website, misalnya Beranda, Berita, Galeri, atau page builder.</p>
            </div>
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/60 px-4 py-3 text-xs text-gray-500 dark:text-gray-300">
                Format path mengikuti grup sidebar saat ini, misalnya <strong>Konten &gt; Berita</strong> atau <strong>Super Admin &gt; Editor Situs</strong>.
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($sidebarGuideItems as $item)
            <a href="{{ $item['href'] }}" class="group rounded-3xl border border-gray-200 dark:border-gray-800 bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-950 p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-50 text-2xl dark:bg-brand-900/30">{{ $item['icon'] }}</div>
                    <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-gray-500 dark:text-gray-300">{{ $item['path'] }}</span>
                </div>
                <h3 class="mt-4 text-base font-extrabold text-gray-900 dark:text-white group-hover:text-brand-700 dark:group-hover:text-brand-300">{{ $item['label'] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $item['note'] }}</p>
                <p class="mt-4 text-xs font-bold text-brand-600 dark:text-brand-400">Buka menu ini →</p>
            </a>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-6 space-y-5 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Preview Live</p>
                <h2 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white">Perubahan header, hero, dan footer terlihat sebelum disimpan</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ketik atau pilih file di form di bawah, lalu preview akan diperbarui otomatis. Konten publik baru berubah setelah tombol simpan ditekan.</p>
            </div>
            <div class="rounded-2xl bg-slate-100 dark:bg-slate-900 px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                Menu publik tetap mengikuti modul navigasi. Preview ini fokus pada identitas header, hero beranda, dan footer.
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1fr,1.25fr,1fr]">
            <article class="rounded-3xl overflow-hidden bg-gradient-to-br from-emerald-600 via-teal-700 to-slate-900 text-white shadow-lg shadow-emerald-950/20">
                <div class="p-5 border-b border-white/10">
                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-emerald-100/80">Header</p>
                    <div class="mt-4 flex items-start gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/95 p-3 shadow-lg shadow-black/15">
                            <img :src="header.logoUrl" :alt="header.logoAlt || 'Logo header'" class="max-h-full w-auto object-contain">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-extrabold text-white" x-text="header.logoAlt || 'Logo Poltekkes Bandung'"></p>
                            <p class="mt-1 text-sm text-emerald-50/85" x-text="header.addressText || 'Alamat header belum diisi'"></p>
                            <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-100/60">Tautan sosial</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="social in header.socials" :key="social.label">
                                    <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold text-white/90" x-text="social.label"></span>
                                </template>
                                <span x-show="header.socials.length === 0" class="rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-medium text-white/60">Belum ada sosial aktif</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-4 text-xs text-white/70">
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-white/8 px-3 py-2">
                        <span>Alamat klik</span>
                        <span class="truncate text-right font-semibold text-white/90" x-text="header.addressUrl || '#'"></span>
                    </div>
                </div>
            </article>

            <article class="relative min-h-[340px] overflow-hidden rounded-3xl bg-slate-950 text-white shadow-lg shadow-slate-950/20">
                <img :src="hero.posterUrl" alt="Preview hero" class="absolute inset-0 h-full w-full object-cover opacity-35">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950/90 via-slate-950/70 to-emerald-900/70"></div>
                <div class="relative flex h-full flex-col justify-between p-6">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-emerald-200/80">Hero</p>
                        <span class="mt-4 inline-flex max-w-full items-center rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold text-emerald-100/90 backdrop-blur-sm" x-text="formatDynamic(hero.badgeText || 'Badge hero')"></span>
                        <h3 class="mt-4 max-w-md text-3xl font-black leading-tight">
                            <span x-text="hero.titleLine1 || 'Judul baris 1'"></span>
                            <span class="block bg-gradient-to-r from-emerald-200 via-lime-200 to-sky-200 bg-clip-text text-transparent" x-text="hero.titleHighlight || 'Highlight'"></span>
                            <span x-text="hero.titleLine2 || 'Judul baris 2'"></span>
                        </h3>
                        <p class="mt-3 max-w-lg whitespace-pre-line text-sm leading-relaxed text-white/75" x-text="hero.body || 'Deskripsi hero akan tampil di sini.'"></p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-2xl bg-emerald-400/20 px-3 py-2 text-sm font-bold text-emerald-50" x-text="hero.primaryLabel || 'CTA primer'"></span>
                            <span class="inline-flex items-center rounded-2xl border border-white/20 px-3 py-2 text-sm font-semibold text-white/85" x-text="hero.secondaryLabel || 'CTA sekunder'"></span>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-white/65">
                            <span x-text="hero.scrollHint || 'Hint scroll'"></span>
                            <span class="rounded-full bg-white/10 px-2.5 py-1 font-semibold text-white/80" x-text="hero.videoLabel || 'Video default website'"></span>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rounded-3xl overflow-hidden bg-slate-950 text-white shadow-lg shadow-slate-950/20">
                <div class="border-b border-white/10 px-5 py-4">
                    <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-300/80">Footer</p>
                    <h3 class="mt-3 text-lg font-extrabold text-white" x-text="footer.mapTitle || 'Map footer'"></h3>
                    <div class="mt-3 space-y-1.5 text-sm text-slate-300">
                        <p x-text="footer.addressText || 'Alamat footer belum diisi'"></p>
                        <p x-text="footer.phoneText || 'Telepon'"></p>
                        <p x-text="footer.emailText || 'Email'"></p>
                    </div>
                </div>
                <div class="grid gap-4 px-5 py-4 text-sm text-slate-300">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Newsletter</p>
                        <p class="mt-2 font-semibold text-white" x-text="footer.newsletterTitle || 'Newsletter'"></p>
                        <p class="mt-1 text-slate-400 line-clamp-3 whitespace-pre-line" x-text="footer.newsletterBody || 'Deskripsi newsletter footer'"></p>
                        <div class="mt-3 flex items-center gap-2 rounded-2xl bg-white/5 px-3 py-2 text-xs">
                            <span class="truncate" x-text="footer.newsletterPlaceholder || 'email@anda.com'"></span>
                            <span class="ml-auto rounded-full bg-white/10 px-2 py-1 font-semibold text-white/85" x-text="footer.newsletterButtonLabel || 'Langganan'"></span>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Quick Links</p>
                            <div class="mt-2 space-y-1.5 text-xs">
                                <template x-for="link in footer.quickLinks.slice(0, 4)" :key="`${link.label}-${link.href}`">
                                    <p class="line-clamp-1" x-text="link.label"></p>
                                </template>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Tentang</p>
                            <div class="mt-2 space-y-1.5 text-xs">
                                <template x-for="link in footer.aboutLinks.slice(0, 4)" :key="`${link.label}-${link.href}`">
                                    <p class="line-clamp-1" x-text="link.label"></p>
                                </template>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-slate-500">Kebijakan</p>
                            <div class="mt-2 space-y-1.5 text-xs">
                                <template x-for="link in footer.policyLinks" :key="`${link.label}-${link.href}`">
                                    <p class="line-clamp-1" x-text="link.label"></p>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="border-t border-white/10 px-5 py-4 text-xs text-slate-400" x-text="formatDynamic(footer.bottomText || '© {year} Poltekkes Bandung')"></div>
            </article>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.site-appearance.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Header</p>
                    <h2 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white">Identitas dan utilitas bagian atas</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Logo, alamat ringkas, dan tautan sosial yang muncul di navbar publik.</p>
                </div>
                @if($header['logo_path'])
                <img src="{{ \App\Support\SiteAppearance::storageUrl($header['logo_path'], 'images/logo.png') }}" alt="{{ $header['logo_alt'] }}" class="h-12 w-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white p-2">
                @endif
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Logo Header</label>
                    <input type="file" name="header_logo_file" accept="image/*" class="text-sm text-gray-500 dark:text-gray-400" @change="handleFilePreview($event, 'headerLogoUrl')">
                    <label class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <input type="hidden" name="header[use_default_logo]" value="0">
                        <input type="checkbox" name="header[use_default_logo]" value="1" class="rounded text-brand-600">
                        Gunakan kembali logo default website
                    </label>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Alt Text Logo</label>
                    <input type="text" name="header[logo_alt]" value="{{ old('header.logo_alt', $header['logo_alt']) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Alamat Ringkas</label>
                    <input type="text" name="header[address_text]" value="{{ old('header.address_text', $header['address_text']) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Link Alamat / Maps</label>
                    <input type="text" name="header[address_url]" value="{{ old('header.address_url', $header['address_url']) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Instagram</label>
                    <input type="text" name="header[socials][instagram]" value="{{ old('header.socials.instagram', $header['socials']['instagram']) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">YouTube</label>
                    <input type="text" name="header[socials][youtube]" value="{{ old('header.socials.youtube', $header['socials']['youtube']) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Facebook</label>
                    <input type="text" name="header[socials][facebook]" value="{{ old('header.socials.facebook', $header['socials']['facebook']) }}"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Beranda</p>
                <h2 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white">Copy utama, statistik, CTA, dan label-section</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pengaturan ini mengontrol bagian beranda yang sebelumnya hardcoded di template.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Meta Description SEO</label>
                    <textarea name="home[description]" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm resize-none">{{ old('home.description', $home['description']) }}</textarea>
                </div>
            </div>

            <div class="rounded-2xl border border-brand-100 dark:border-brand-900 bg-brand-50/60 dark:bg-brand-950/20 p-5 space-y-4">
                <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Hero Utama</h3>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Video Hero</label>
                        <input type="file" name="home_hero_video_file" accept="video/mp4,video/webm" class="text-sm text-gray-500 dark:text-gray-400" @change="handleFilePreview($event, 'heroVideoLabel')">
                        <label class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <input type="hidden" name="home[hero][use_default_video]" value="0">
                            <input type="checkbox" name="home[hero][use_default_video]" value="1" class="rounded text-brand-600">
                            Gunakan kembali video default
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Poster Hero</label>
                        <input type="file" name="home_hero_poster_file" accept="image/*" class="text-sm text-gray-500 dark:text-gray-400" @change="handleFilePreview($event, 'heroPosterUrl')">
                        <label class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <input type="hidden" name="home[hero][use_default_poster]" value="0">
                            <input type="checkbox" name="home[hero][use_default_poster]" value="1" class="rounded text-brand-600">
                            Gunakan kembali poster default
                        </label>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Badge Hero</label>
                        <input type="text" name="home[hero][badge_text]" value="{{ old('home.hero.badge_text', $home['hero']['badge_text']) }}"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Hint Scroll</label>
                        <input type="text" name="home[hero][scroll_hint]" value="{{ old('home.hero.scroll_hint', $home['hero']['scroll_hint']) }}"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Baris 1</label>
                        <input type="text" name="home[hero][title_line_1]" value="{{ old('home.hero.title_line_1', $home['hero']['title_line_1']) }}"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Teks Highlight</label>
                        <input type="text" name="home[hero][title_highlight]" value="{{ old('home.hero.title_highlight', $home['hero']['title_highlight']) }}"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Baris 2</label>
                        <input type="text" name="home[hero][title_line_2]" value="{{ old('home.hero.title_line_2', $home['hero']['title_line_2']) }}"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Hero</label>
                        <textarea name="home[hero][body]" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm resize-none">{{ old('home.hero.body', $home['hero']['body']) }}</textarea>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">CTA Primer Label</label>
                            <input type="text" name="home[hero][primary_label]" value="{{ old('home.hero.primary_label', $home['hero']['primary_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">CTA Primer Link</label>
                            <input type="text" name="home[hero][primary_href]" value="{{ old('home.hero.primary_href', $home['hero']['primary_href']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 sm:col-span-2">
                            <input type="hidden" name="home[hero][primary_new_tab]" value="0">
                            <input type="checkbox" name="home[hero][primary_new_tab]" value="1" @checked(old('home.hero.primary_new_tab', $home['hero']['primary_new_tab'])) class="rounded text-brand-600">
                            Buka CTA primer di tab baru
                        </label>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">CTA Sekunder Label</label>
                            <input type="text" name="home[hero][secondary_label]" value="{{ old('home.hero.secondary_label', $home['hero']['secondary_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">CTA Sekunder Link</label>
                            <input type="text" name="home[hero][secondary_href]" value="{{ old('home.hero.secondary_href', $home['hero']['secondary_href']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 sm:col-span-2">
                            <input type="hidden" name="home[hero][secondary_new_tab]" value="0">
                            <input type="checkbox" name="home[hero][secondary_new_tab]" value="1" @checked(old('home.hero.secondary_new_tab', $home['hero']['secondary_new_tab'])) class="rounded text-brand-600">
                            Buka CTA sekunder di tab baru
                        </label>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 dark:border-gray-800 p-5 space-y-4">
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Statistik Kampus</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Edit empat kartu statistik yang muncul di sisi kanan hero.</p>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Eyebrow</label>
                        <input type="text" name="home[statistics][eyebrow]" value="{{ old('home.statistics.eyebrow', $home['statistics']['eyebrow']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul</label>
                        <input type="text" name="home[statistics][title]" value="{{ old('home.statistics.title', $home['statistics']['title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                </div>
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach($home['statistics']['items'] as $index => $item)
                    <div class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 p-4 grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Ikon</label>
                            <input type="text" name="home[statistics][items][{{ $index }}][icon]" value="{{ old("home.statistics.items.$index.icon", $item['icon']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Angka Target</label>
                            <input type="number" name="home[statistics][items][{{ $index }}][target]" value="{{ old("home.statistics.items.$index.target", $item['target']) }}" min="0" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Suffix</label>
                            <input type="text" name="home[statistics][items][{{ $index }}][suffix]" value="{{ old("home.statistics.items.$index.suffix", $item['suffix']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Label</label>
                            <input type="text" name="home[statistics][items][{{ $index }}][label]" value="{{ old("home.statistics.items.$index.label", $item['label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-2xl border border-gray-100 dark:border-gray-800 p-5 space-y-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Panel Quiz Jurusan</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mengatur judul, catatan hasil, informasi desktop, serta logika pertanyaan dan mapping rekomendasi prodi.</p>
                    </div>
                    <div class="grid gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Quiz</label>
                            <input type="text" name="home[quiz][title]" value="{{ old('home.quiz.title', $home['quiz']['title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Subjudul Quiz</label>
                            <input type="text" name="home[quiz][subtitle]" value="{{ old('home.quiz.subtitle', $home['quiz']['subtitle']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Hasil</label>
                            <input type="text" name="home[quiz][result_title]" value="{{ old('home.quiz.result_title', $home['quiz']['result_title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Catatan Hasil</label>
                            <input type="text" name="home[quiz][result_note]" value="{{ old('home.quiz.result_note', $home['quiz']['result_note']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Label Tombol Semua Prodi</label>
                                <input type="text" name="home[quiz][all_programs_label]" value="{{ old('home.quiz.all_programs_label', $home['quiz']['all_programs_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Label Tombol Reset</label>
                                <input type="text" name="home[quiz][reset_label]" value="{{ old('home.quiz.reset_label', $home['quiz']['reset_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Label Total Prodi Desktop</label>
                            <input type="text" name="home[quiz][desktop_total_label]" value="{{ old('home.quiz.desktop_total_label', $home['quiz']['desktop_total_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            @foreach($home['quiz']['desktop_badges'] as $index => $badge)
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">Badge {{ $index + 1 }}</label>
                                <input type="text" name="home[quiz][desktop_badges][{{ $index }}]" value="{{ old("home.quiz.desktop_badges.$index", $badge) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                            </div>
                            @endforeach
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Panel Desktop</label>
                            <textarea name="home[quiz][desktop_description]" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm resize-none">{{ old('home.quiz.desktop_description', $home['quiz']['desktop_description']) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Label Link Desktop</label>
                            <input type="text" name="home[quiz][desktop_link_label]" value="{{ old('home.quiz.desktop_link_label', $home['quiz']['desktop_link_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>

                        <details class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50/70 dark:bg-amber-950/20 p-4" open>
                            <summary class="cursor-pointer list-none text-sm font-extrabold text-amber-950 dark:text-amber-100">Logika Quiz Rekomendasi (JSON)</summary>
                            <p class="mt-3 text-sm text-amber-900/80 dark:text-amber-100/80">Gunakan JSON valid. `questions_json` harus berupa array pertanyaan dengan `q`, `icon`, dan `opts`. `map_json` dan `map2_json` harus berupa object dengan key kombinasi jawaban dan isi `nama`, `emoji`, `slug`.</p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Key Hasil Default</label>
                                    <input type="text" name="home[quiz][default_result_key]" value="{{ old('home.quiz.default_result_key', $home['quiz']['default_result_key']) }}" class="w-full rounded-xl border border-amber-200 dark:border-amber-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-sm font-mono" spellcheck="false">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Pertanyaan Quiz JSON</label>
                                    <textarea name="home[quiz][questions_json]" rows="14" class="w-full rounded-2xl border border-amber-200 dark:border-amber-700 bg-white dark:bg-slate-900 px-4 py-3 text-xs font-mono leading-6" spellcheck="false">{{ $quizQuestionsJson }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Mapping Hasil Utama JSON</label>
                                    <textarea name="home[quiz][map_json]" rows="18" class="w-full rounded-2xl border border-amber-200 dark:border-amber-700 bg-white dark:bg-slate-900 px-4 py-3 text-xs font-mono leading-6" spellcheck="false">{{ $quizMapJson }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Mapping Hasil Alternatif JSON</label>
                                    <textarea name="home[quiz][map2_json]" rows="18" class="w-full rounded-2xl border border-amber-200 dark:border-amber-700 bg-white dark:bg-slate-900 px-4 py-3 text-xs font-mono leading-6" spellcheck="false">{{ $quizMap2Json }}</textarea>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-100 dark:border-gray-800 p-5 space-y-4">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">CTA dan Newsletter Beranda</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kotak CTA kanan bawah dan strip newsletter paling bawah.</p>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Badge CTA</label>
                            <input type="text" name="home[cta][badge_text]" value="{{ old('home.cta.badge_text', $home['cta']['badge_text']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul CTA Baris 1</label>
                                <input type="text" name="home[cta][title_line_1]" value="{{ old('home.cta.title_line_1', $home['cta']['title_line_1']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul CTA Baris 2</label>
                                <input type="text" name="home[cta][title_line_2]" value="{{ old('home.cta.title_line_2', $home['cta']['title_line_2']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi CTA</label>
                            <textarea name="home[cta][body]" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm resize-none">{{ old('home.cta.body', $home['cta']['body']) }}</textarea>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">CTA Primer Label</label>
                                <input type="text" name="home[cta][primary_label]" value="{{ old('home.cta.primary_label', $home['cta']['primary_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">CTA Primer Link</label>
                                <input type="text" name="home[cta][primary_href]" value="{{ old('home.cta.primary_href', $home['cta']['primary_href']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                            </div>
                            <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 sm:col-span-2">
                                <input type="hidden" name="home[cta][primary_new_tab]" value="0">
                                <input type="checkbox" name="home[cta][primary_new_tab]" value="1" @checked(old('home.cta.primary_new_tab', $home['cta']['primary_new_tab'])) class="rounded text-brand-600">
                                Buka CTA primer di tab baru
                            </label>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">CTA Sekunder Label</label>
                                <input type="text" name="home[cta][secondary_label]" value="{{ old('home.cta.secondary_label', $home['cta']['secondary_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 dark:text-gray-400 mb-1">CTA Sekunder Link</label>
                                <input type="text" name="home[cta][secondary_href]" value="{{ old('home.cta.secondary_href', $home['cta']['secondary_href']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm">
                            </div>
                            <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 sm:col-span-2">
                                <input type="hidden" name="home[cta][secondary_new_tab]" value="0">
                                <input type="checkbox" name="home[cta][secondary_new_tab]" value="1" @checked(old('home.cta.secondary_new_tab', $home['cta']['secondary_new_tab'])) class="rounded text-brand-600">
                                Buka CTA sekunder di tab baru
                            </label>
                        </div>
                        <div class="border-t border-gray-100 dark:border-gray-800 pt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Eyebrow Newsletter</label>
                                <input type="text" name="home[newsletter][eyebrow]" value="{{ old('home.newsletter.eyebrow', $home['newsletter']['eyebrow']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Newsletter</label>
                                <input type="text" name="home[newsletter][title]" value="{{ old('home.newsletter.title', $home['newsletter']['title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Newsletter</label>
                                <textarea name="home[newsletter][body]" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm resize-none">{{ old('home.newsletter.body', $home['newsletter']['body']) }}</textarea>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Placeholder Email</label>
                                    <input type="text" name="home[newsletter][placeholder]" value="{{ old('home.newsletter.placeholder', $home['newsletter']['placeholder']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Label Tombol</label>
                                    <input type="text" name="home[newsletter][button_label]" value="{{ old('home.newsletter.button_label', $home['newsletter']['button_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Pesan Sukses</label>
                                <input type="text" name="home[newsletter][success_message]" value="{{ old('home.newsletter.success_message', $home['newsletter']['success_message']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-100 dark:border-gray-800 p-5 space-y-4">
                <div>
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Judul Section Beranda</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Label untuk blok program studi, berita, pengumuman, prestasi, dan galeri.</p>
                </div>
                <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    <div class="space-y-3 rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-4">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Berita</p>
                        <input type="text" name="home[news][title]" value="{{ old('home.news.title', $home['news']['title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Judul">
                        <input type="text" name="home[news][subtitle]" value="{{ old('home.news.subtitle', $home['news']['subtitle']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Subjudul">
                        <input type="text" name="home[news][link_label]" value="{{ old('home.news.link_label', $home['news']['link_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Label link">
                    </div>
                    <div class="space-y-3 rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-4">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Pengumuman</p>
                        <input type="text" name="home[announcements][title]" value="{{ old('home.announcements.title', $home['announcements']['title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Judul">
                        <input type="text" name="home[announcements][link_label]" value="{{ old('home.announcements.link_label', $home['announcements']['link_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Label link">
                        <input type="text" name="home[announcements][empty_text]" value="{{ old('home.announcements.empty_text', $home['announcements']['empty_text']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Pesan kosong">
                    </div>
                    <div class="space-y-3 rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-4">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Mahasiswa Berprestasi</p>
                        <input type="text" name="home[achievements][title]" value="{{ old('home.achievements.title', $home['achievements']['title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Judul">
                        <input type="text" name="home[achievements][subtitle]" value="{{ old('home.achievements.subtitle', $home['achievements']['subtitle']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Subjudul">
                        <input type="text" name="home[achievements][link_label]" value="{{ old('home.achievements.link_label', $home['achievements']['link_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Label link">
                        <textarea name="home[achievements][empty_cta]" rows="2" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm resize-none" placeholder="CTA saat kosong">{{ old('home.achievements.empty_cta', $home['achievements']['empty_cta']) }}</textarea>
                    </div>
                    <div class="space-y-3 rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-4">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Program Studi</p>
                        <input type="text" name="home[programs][eyebrow]" value="{{ old('home.programs.eyebrow', $home['programs']['eyebrow']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Eyebrow">
                        <input type="text" name="home[programs][title]" value="{{ old('home.programs.title', $home['programs']['title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Judul">
                        <input type="text" name="home[programs][desktop_link_label]" value="{{ old('home.programs.desktop_link_label', $home['programs']['desktop_link_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Link desktop">
                        <input type="text" name="home[programs][mobile_link_label]" value="{{ old('home.programs.mobile_link_label', $home['programs']['mobile_link_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Link mobile">
                    </div>
                    <div class="space-y-3 rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-4">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">Galeri</p>
                        <input type="text" name="home[gallery][eyebrow]" value="{{ old('home.gallery.eyebrow', $home['gallery']['eyebrow']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Eyebrow">
                        <input type="text" name="home[gallery][title]" value="{{ old('home.gallery.title', $home['gallery']['title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Judul">
                        <input type="text" name="home[gallery][link_label]" value="{{ old('home.gallery.link_label', $home['gallery']['link_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Label link">
                        <input type="text" name="home[gallery][lightbox_label]" value="{{ old('home.gallery.lightbox_label', $home['gallery']['lightbox_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm" placeholder="Label lightbox">
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 space-y-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-400">Footer</p>
                <h2 class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white">Kontak, peta, daftar tautan, dan copyright</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Seluruh konten footer publik diedit di sini, termasuk placeholder newsletter footer.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Peta</label>
                    <input type="text" name="footer[map_title]" value="{{ old('footer.map_title', $footer['map_title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Embed URL Google Maps</label>
                    <input type="text" name="footer[map_embed_url]" value="{{ old('footer.map_embed_url', $footer['map_embed_url']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Alamat Footer</label>
                    <input type="text" name="footer[address_text]" value="{{ old('footer.address_text', $footer['address_text']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Teks Telepon</label>
                        <input type="text" name="footer[phone_text]" value="{{ old('footer.phone_text', $footer['phone_text']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Href Telepon</label>
                        <input type="text" name="footer[phone_href]" value="{{ old('footer.phone_href', $footer['phone_href']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Teks Email</label>
                        <input type="text" name="footer[email_text]" value="{{ old('footer.email_text', $footer['email_text']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Href Email</label>
                        <input type="text" name="footer[email_href]" value="{{ old('footer.email_href', $footer['email_href']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Label Link Maps</label>
                    <input type="text" name="footer[map_link_label]" value="{{ old('footer.map_link_label', $footer['map_link_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">URL Link Maps</label>
                    <input type="text" name="footer[map_link_url]" value="{{ old('footer.map_link_url', $footer['map_link_url']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2.5 text-sm">
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Tautan Cepat</label>
                        <input type="text" name="footer[quick_links_title]" value="{{ old('footer.quick_links_title', $footer['quick_links_title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    @foreach($footer['quick_links'] as $index => $link)
                    <div class="grid gap-3 sm:grid-cols-[1.2fr,1.6fr,auto]">
                        <input type="text" name="footer[quick_links][{{ $index }}][label]" value="{{ old("footer.quick_links.$index.label", $link['label']) }}" placeholder="Label link" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        <input type="text" name="footer[quick_links][{{ $index }}][href]" value="{{ old("footer.quick_links.$index.href", $link['href']) }}" placeholder="/path atau https://..." class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <input type="hidden" name="footer[quick_links][{{ $index }}][external]" value="0">
                            <input type="checkbox" name="footer[quick_links][{{ $index }}][external]" value="1" @checked(old("footer.quick_links.$index.external", $link['external'])) class="rounded text-brand-600">
                            Eksternal
                        </label>
                    </div>
                    @endforeach
                </div>

                <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul Tentang</label>
                        <input type="text" name="footer[about_links_title]" value="{{ old('footer.about_links_title', $footer['about_links_title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    @foreach($footer['about_links'] as $index => $link)
                    <div class="grid gap-3 sm:grid-cols-[1.2fr,1.6fr,auto]">
                        <input type="text" name="footer[about_links][{{ $index }}][label]" value="{{ old("footer.about_links.$index.label", $link['label']) }}" placeholder="Label link" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        <input type="text" name="footer[about_links][{{ $index }}][href]" value="{{ old("footer.about_links.$index.href", $link['href']) }}" placeholder="/path atau https://..." class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <input type="hidden" name="footer[about_links][{{ $index }}][external]" value="0">
                            <input type="checkbox" name="footer[about_links][{{ $index }}][external]" value="1" @checked(old("footer.about_links.$index.external", $link['external'])) class="rounded text-brand-600">
                            Eksternal
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-5 space-y-4">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Newsletter Footer</h3>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Judul</label>
                        <input type="text" name="footer[newsletter_title]" value="{{ old('footer.newsletter_title', $footer['newsletter_title']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi</label>
                        <textarea name="footer[newsletter_body]" rows="3" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm resize-none">{{ old('footer.newsletter_body', $footer['newsletter_body']) }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Placeholder</label>
                            <input type="text" name="footer[newsletter_placeholder]" value="{{ old('footer.newsletter_placeholder', $footer['newsletter_placeholder']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Label Tombol</label>
                            <input type="text" name="footer[newsletter_button_label]" value="{{ old('footer.newsletter_button_label', $footer['newsletter_button_label']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1">Teks Copyright</label>
                        <input type="text" name="footer[bottom_text]" value="{{ old('footer.bottom_text', $footer['bottom_text']) }}" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm">
                    </div>
                </div>

                <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/40 p-5 space-y-4">
                    <h3 class="text-lg font-extrabold text-gray-900 dark:text-white">Link Kebijakan</h3>
                    @foreach($footer['policy_links'] as $index => $link)
                    <div class="grid gap-3 sm:grid-cols-[1.2fr,1.6fr,auto]">
                        <input type="text" name="footer[policy_links][{{ $index }}][label]" value="{{ old("footer.policy_links.$index.label", $link['label']) }}" placeholder="Label link" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        <input type="text" name="footer[policy_links][{{ $index }}][href]" value="{{ old("footer.policy_links.$index.href", $link['href']) }}" placeholder="/path atau https://..." class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm">
                        <label class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <input type="hidden" name="footer[policy_links][{{ $index }}][external]" value="0">
                            <input type="checkbox" name="footer[policy_links][{{ $index }}][external]" value="1" @checked(old("footer.policy_links.$index.external", $link['external'])) class="rounded text-brand-600">
                            Eksternal
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white hover:bg-brand-500 transition-colors">
                💾 Simpan Editor Situs
            </button>
        </div>
    </form>
</div>
@endsection