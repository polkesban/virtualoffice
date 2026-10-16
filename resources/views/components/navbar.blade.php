{{--
    Navbar Poltekkes Bandung
    Alpine.js mengelola:
    - x-data: state burger sidebar
    - Tidak ada JS berat - semua reaktivitas via Alpine
--}}
@php
    $guide = $contextualGuide ?? null;
    $navTree = \App\Models\NavMenu::navbarTree();
    $headerAppearance = \App\Support\SiteAppearance::header();
    $logoUrl = \App\Support\SiteAppearance::storageUrl($headerAppearance['logo_path'] ?? null, 'images/logo.png');
    $addressUrl = $headerAppearance['address_url'] ?? '';
    $socialLinks = [
        [
            'label' => 'Instagram Poltekkes Bandung',
            'href' => $headerAppearance['socials']['instagram'] ?? '',
            'icon' => 'instagram',
        ],
        [
            'label' => 'YouTube Poltekkes Bandung',
            'href' => $headerAppearance['socials']['youtube'] ?? '',
            'icon' => 'youtube',
        ],
        [
            'label' => 'Facebook Poltekkes Bandung',
            'href' => $headerAppearance['socials']['facebook'] ?? '',
            'icon' => 'facebook',
        ],
    ];
    $socialLinks = array_values(array_filter($socialLinks, fn (array $social): bool => ! empty($social['href'])));
    $supportedLocales = \App\Support\PublicLocale::supported();
    $currentLocale = \App\Support\PublicLocale::current();
    $localeSwitcherLabel = $currentLocale === 'en' ? 'Language' : 'Bahasa';

    $resolveHref = function (?string $href): string {
        if (! $href) {
            return '#';
        }

        return str_starts_with($href, 'http://') || str_starts_with($href, 'https://')
            ? $href
            : url($href);
    };

    $isActiveHref = function (?string $href): bool {
        if (! $href || str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return false;
        }

        $path = trim($href, '/');

        if ($path === '') {
            return request()->is('/');
        }

        return request()->is($path) || request()->is($path . '/*');
    };

    $hasActiveDescendant = function ($item) use (&$hasActiveDescendant, $isActiveHref): bool {
        if ($isActiveHref($item->href)) {
            return true;
        }

        foreach ($item->childrenRecursive as $child) {
            if ($hasActiveDescendant($child)) {
                return true;
            }
        }

        return false;
    };

    // To keep page load extremely fast, we load songs asynchronously from client-side localStorage/API cache.
    // We do not inline the heavy 400KB lyrics dataset in the HTML.
@endphp
<script>
    window.sunoSongs = [];
    window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && (event.reason.isFromCancelledTransition || event.reason.message === 'The play() request was interrupted by a new load request.')) {
            event.preventDefault();
        }
    });
    window.initNavbarComponent = function() {
        return {
            publicSidebarOpen: false,
            guideOpen: false,
            musicPlayerOpen: false,
            musicPlayerHovered: false,
            fullscreenPlayerOpen: false,
            getLyrics() {
                const song = this.getActiveSong();
                if (!song) return [];
                if (song.lyrics) {
                    let rawLines = song.lyrics.split('\n')
                        .map(line => line.trim())
                        .filter(line => line.length > 0);

                    // Suno extends concatenation check:
                    // If lyrics contain duplicate parts (e.g. Part 1 + Part 1 & 2), we deduplicate them.
                    if (rawLines.length > 10) {
                        let firstLine = rawLines[0];
                        let repeatIndices = [];
                        for (let i = 5; i < rawLines.length; i++) {
                            if (rawLines[i] === firstLine) {
                                repeatIndices.push(i);
                            }
                        }
                        
                        for (let repeatIdx of repeatIndices) {
                            let match = true;
                            let matchCount = 0;
                            for (let j = 0; j < repeatIdx; j++) {
                                if (repeatIdx + j < rawLines.length) {
                                    let clean1 = rawLines[j].toLowerCase().replace(/[\[\]]/g, '');
                                    let clean2 = rawLines[repeatIdx + j].toLowerCase().replace(/[\[\]]/g, '');
                                    if (clean1 !== clean2) {
                                        match = false;
                                        break;
                                    }
                                    matchCount++;
                                }
                            }
                            if (match && matchCount >= 4) {
                                // Keep only the second version as it is the complete extended version.
                                rawLines = rawLines.slice(repeatIdx);
                                break;
                            }
                        }
                    }
                    return rawLines;
                }
                if (song.title.includes('Mahakarya')) {
                    return [
                        "[Bait 1]",
                        "Langkah tegap pengabdi negeri",
                        "Di bawah naungan Poltekkes Bandung",
                        "Membawa harapan, menyalakan terang",
                        "Demi raga yang sehat, demi jiwa yang kuat",
                        "[Reff]",
                        "Poltekkes Bandung tercinta, mahakarya kesehatan",
                        "Berbakti mulia untuk kemanusiaan",
                        "Mendidik tunas bangsa, cerdas dan berbudaya",
                        "Poltekkes Bandung jaya, selamanya!",
                        "[Bait 2]",
                        "Di laboratorium dan ruang kelas",
                        "Semangat menyala tiada batas",
                        "Dengan keahlian dan ketulusan hati",
                        "Kami siap mengabdi, kami siap berbakti"
                    ];
                }
                if (song.title.includes('Jiwa')) {
                    return [
                        "[Bait 1]",
                        "Dalam hening malam ku bermimpi",
                        "Tentang masa depan yang gemilang",
                        "Poltekkes Bandung menuntun jalan suci ini",
                        "Menjadi penyembuh bagi sesama",
                        "[Reff]",
                        "Ini suara jiwa, tekad bulat membara",
                        "Poltekkes Bandung kebanggaan kita",
                        "Menabur kasih, merawat dunia",
                        "Dengan cinta dan karya nyata",
                        "[Bait 2]",
                        "Setiap helaan napas adalah janji",
                        "Untuk setia pada kemanusiaan",
                        "Bersama kita melangkah pasti",
                        "Menuju sehat sejahtera abadi"
                    ];
                }
                return [
                    "[Musik]",
                    "Bernyanyilah mengikuti irama...",
                    song.title,
                    song.desc,
                    "Poltekkes Kemenkes Bandung"
                ];
            },
            lyricOffset: 0,
            lyricTimeline: null,
            lyricTimelineSongId: null,
            lyricTimelineDuration: 0,
            buildLyricTimeline(lines) {
                let estimatedDurations = [];
                let totalEstimated = 0;
                
                lines.forEach((line) => {
                    let dur = 4.0;
                    const isBracket = line.startsWith('[') && line.endsWith(']');
                    if (isBracket) {
                        const content = line.toLowerCase();
                        if (content.includes('intro') || content.includes('instrumental') || content.includes('solo') || content.includes('musik') || content.includes('music') || content.includes('start') || content.includes('interlude') || content.includes('break')) {
                            dur = 12.0; // Allocates 12s for intros/breaks
                        } else if (content.includes('outro') || content.includes('end') || content.includes('finish')) {
                            dur = 15.0; // Allocates 15s for outros
                        } else {
                            dur = 0.0; // Structural headers (Verse, Chorus, etc.) have NO pause
                        }
                    } else {
                        // Estimate duration by word count: 2s base + 450ms per word (capped at 9s)
                        const wordCount = line.split(/\s+/).filter(w => w.length > 0).length;
                        dur = 2.0 + (wordCount * 0.45);
                        if (dur > 9.0) dur = 9.0;
                    }
                    estimatedDurations.push(dur);
                    totalEstimated += dur;
                });
                
                // Scale timeline to fit the exact duration of the audio
                const scale = this.duration / totalEstimated;
                let accumulated = 0;
                this.lyricTimeline = lines.map((line, idx) => {
                    const scaledDur = estimatedDurations[idx] * scale;
                    const start = accumulated;
                    accumulated += scaledDur;
                    return {
                        line: line,
                        start: start,
                        duration: scaledDur
                    };
                });
                this.lyricTimelineSongId = this.activeSongId;
                this.lyricTimelineDuration = this.duration;
            },
            getActiveLyricIndex() {
                const lines = this.getLyrics();
                if (lines.length === 0 || this.duration === 0) return -1;
                
                if (!this.lyricTimeline || this.lyricTimelineSongId !== this.activeSongId || this.lyricTimelineDuration !== this.duration) {
                    this.buildLyricTimeline(lines);
                }
                
                const time = this.currentTime + (this.lyricOffset || 0);
                let activeIdx = 0;
                for (let i = 0; i < this.lyricTimeline.length; i++) {
                    if (time >= this.lyricTimeline[i].start) {
                        activeIdx = i;
                    } else {
                        break;
                    }
                }
                return activeIdx;
            },
            seekToLyric(index) {
                if (this.lyricTimeline && this.lyricTimeline[index]) {
                    const estimatedStart = this.lyricTimeline[index].start;
                    this.lyricOffset = estimatedStart - this.currentTime;
                }
            },
            scrollLoop() {
                // Disabled auto-scroll per user preference
            },
            musicSearchQuery: '',
            searchQuery: '',
            scrolled: false,
            isLoading: false,
            isPlaying: false,
            playMode: 'shuffle',
            playbackPercent: 0,
            currentTimeLabel: '0:00',
            currentTime: 0,
            duration: 0,
            activeSongId: localStorage.getItem('suno_player_song_id') || '7dc62450-95a0-43ca-98a9-a0cdc1c34a90',
            songs: window.sunoSongs || [],
            syncPageLock() {
                document.documentElement.classList.toggle('overflow-hidden', this.publicSidebarOpen || this.fullscreenPlayerOpen);
                document.body.classList.toggle('overflow-hidden', this.publicSidebarOpen || this.fullscreenPlayerOpen);
            },
            muted: false,
            toggleMute() {
                const audio = this.$refs.musicAudio;
                if (audio) {
                    audio.muted = !audio.muted;
                    this.muted = audio.muted;
                }
            },
            async init() {
                // Pre-load full playlist from API to populate dynamic songs list
                await this.loadSongs();

                const savedSongId = localStorage.getItem('suno_player_song_id');
                const savedTime = localStorage.getItem('suno_player_time');
                const savedPlaying = localStorage.getItem('suno_player_playing');
                const savedPlayMode = localStorage.getItem('suno_player_play_mode');

                if (savedPlayMode) {
                    this.playMode = savedPlayMode;
                }

                if ('mediaSession' in navigator) {
                    navigator.mediaSession.setActionHandler('play', () => {
                        this.togglePlay();
                    });
                    navigator.mediaSession.setActionHandler('pause', () => {
                        this.togglePlay();
                    });
                    navigator.mediaSession.setActionHandler('previoustrack', () => {
                        this.playPrev();
                    });
                    navigator.mediaSession.setActionHandler('nexttrack', () => {
                        this.playNext();
                    });
                }

                if (savedSongId) {
                    this.activeSongId = savedSongId;
                    this.$nextTick(() => {
                        const audio = this.$refs.musicAudio;
                        if (audio) {
                            const song = this.getActiveSong();
                            if (song && song.audio_url) {
                                audio.src = song.audio_url;
                                audio.load();
                                if (savedTime) {
                                    audio.currentTime = parseFloat(savedTime);
                                }
                                if (savedPlaying === 'true') {
                                    audio.play().then(() => {
                                        this.isPlaying = true;
                                        this.updateMediaSession();
                                    }).catch(err => {
                                        console.log('Autoplay blocked initially. Waiting for user interaction to resume.');
                                        this.isPlaying = false;
                                        
                                        const playOnInteraction = () => {
                                            if (localStorage.getItem('suno_player_playing') === 'true' && !this.isPlaying) {
                                                audio.play().then(() => {
                                                    this.isPlaying = true;
                                                    this.updateMediaSession();
                                                    document.removeEventListener('click', playOnInteraction);
                                                    document.removeEventListener('touchstart', playOnInteraction);
                                                }).catch(e => console.error('Play on interaction failed:', e));
                                            } else {
                                                document.removeEventListener('click', playOnInteraction);
                                                document.removeEventListener('touchstart', playOnInteraction);
                                            }
                                        };
                                        document.addEventListener('click', playOnInteraction, { passive: true });
                                        document.addEventListener('touchstart', playOnInteraction, { passive: true });
                                    });
                                }
                            }
                        }
                    });
                }

                setInterval(() => {
                    const audio = this.$refs.musicAudio;
                    if (audio) {
                        if (this.isPlaying) {
                            localStorage.setItem('suno_player_time', audio.currentTime);
                            localStorage.setItem('suno_player_song_id', this.activeSongId);
                        }
                        localStorage.setItem('suno_player_play_mode', this.playMode);
                    }
                }, 500);

                window.addEventListener('scroll', () => {
                    this.scrolled = window.scrollY > 20;
                }, { passive: true });

                this.$watch('isPlaying', (value) => {
                    if (value && this.fullscreenPlayerOpen) {
                        this.scrollLoop();
                    }
                });

                this.$watch('activeSongId', () => {
                    this.lyricOffset = 0;
                    this.lyricTimeline = null;
                });

                this.$watch('fullscreenPlayerOpen', (value) => {
                    if (value) {
                        this.musicPlayerOpen = false;
                        if (this.isPlaying) {
                            this.$nextTick(() => this.scrollLoop());
                        }
                    }
                    this.syncPageLock();
                });

                this.$watch('publicSidebarOpen', (value) => {
                    if (value) {
                        this.guideOpen = false;
                        this.musicPlayerOpen = false;
                    }

                    if (!value) {
                        this.searchQuery = '';
                    }

                    this.syncPageLock();
                });
            },
            toggleGuide() {
                if (this.publicSidebarOpen) {
                    this.publicSidebarOpen = false;
                }
                this.musicPlayerOpen = false;
                this.guideOpen = !this.guideOpen;
            },
            toggleMusicPlayer() {
                if (this.publicSidebarOpen) {
                    this.publicSidebarOpen = false;
                }
                this.guideOpen = false;
                this.musicPlayerOpen = !this.musicPlayerOpen;
                if (this.musicPlayerOpen) {
                    this.loadSongs();
                }
            },
            async loadSongs() {
                const cached = localStorage.getItem('suno_player_playlist');
                if (cached) {
                    try {
                        const parsed = JSON.parse(cached);
                        if (parsed && parsed.length > 0) {
                            this.songs = parsed;
                        }
                    } catch (e) {
                        console.error('Failed to parse cached playlist:', e);
                    }
                }
                
                // Fetch from API
                this.isLoading = true;
                try {
                    const res = await fetch('/api/suno-playlist');
                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.length > 0) {
                            this.songs = data;
                            localStorage.setItem('suno_player_playlist', JSON.stringify(data));
                        }
                    }
                } catch (err) {
                    console.error('Failed to load Suno playlist:', err);
                } finally {
                    this.isLoading = false;
                }
            },
            getActiveSong() {
                const list = this.songs || [];
                const found = list.find(s => s.id === this.activeSongId) || list[0];
                return found || {
                    id: null,
                    title: '',
                    desc: '',
                    description: '',
                    duration: '0:00',
                    image_url: '',
                    video_url: '',
                    audio_url: '',
                    prompt: '',
                    tags: '',
                };
            },
            updateMediaSession() {
                if ('mediaSession' in navigator) {
                    const song = this.getActiveSong();
                    if (!song) return;
                    navigator.mediaSession.metadata = new MediaMetadata({
                        title: song.title || 'Lagu Poltekkes Bandung',
                        artist: 'Poltekkes Bandung Radio',
                        album: 'Poltekkes Bandung',
                        artwork: [
                            { src: song.image_url || 'https://ui-avatars.com/api/?name=Poltekkes Bandung&size=512', sizes: '512x512', type: 'image/jpeg' }
                        ]
                    });
                    navigator.mediaSession.playbackState = this.isPlaying ? 'playing' : 'paused';
                }
            },
            playSong(songId) {
                this.activeSongId = songId;
                const song = this.songs.find(s => s.id === songId);
                if (!song) return;
                const audio = this.$refs.musicAudio;
                if (audio) {
                    audio.src = song.audio_url;
                    audio.load();
                    audio.play().then(() => {
                        this.isPlaying = true;
                        localStorage.setItem('suno_player_playing', 'true');
                        localStorage.setItem('suno_player_song_id', songId);
                        this.updateMediaSession();
                    }).catch(err => console.error('Playback failed:', err));
                }
                this.$nextTick(() => {
                    setTimeout(() => {
                        const activeEl = document.getElementById('playlist-song-' + songId);
                        if (activeEl) {
                            activeEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 100);
                });
            },
            togglePlay() {
                const audio = this.$refs.musicAudio;
                if (!audio) return;
                if (this.isPlaying) {
                    audio.pause();
                    this.isPlaying = false;
                    localStorage.setItem('suno_player_playing', 'false');
                    this.updateMediaSession();
                } else {
                    if (!audio.src || audio.src === '') {
                        const currentSong = this.getActiveSong();
                        if (currentSong) {
                            audio.src = currentSong.audio_url;
                            audio.load();
                        }
                    }
                    audio.play().then(() => {
                        this.isPlaying = true;
                        localStorage.setItem('suno_player_playing', 'true');
                        localStorage.setItem('suno_player_song_id', this.activeSongId);
                        this.updateMediaSession();
                    }).catch(err => {
                        const currentSong = this.getActiveSong();
                        if (currentSong) {
                            audio.src = currentSong.audio_url;
                            audio.load();
                            audio.play().then(() => {
                                this.isPlaying = true;
                                localStorage.setItem('suno_player_playing', 'true');
                                localStorage.setItem('suno_player_song_id', this.activeSongId);
                                this.updateMediaSession();
                            }).catch(e => console.error('Playback failed:', e));
                        }
                    });
                }
            },
            togglePlayMode() {
                if (this.playMode === 'loop') {
                    this.playMode = 'shuffle';
                } else if (this.playMode === 'shuffle') {
                    this.playMode = 'repeat_one';
                } else {
                    this.playMode = 'loop';
                }
            },
            playNext() {
                if (this.songs.length === 0) return;
                let nextIndex = 0;
                if (this.playMode === 'shuffle') {
                    nextIndex = Math.floor(Math.random() * this.songs.length);
                } else if (this.playMode === 'repeat_one') {
                    const currentIndex = this.songs.findIndex(s => s.id === this.activeSongId);
                    nextIndex = currentIndex;
                } else {
                    const currentIndex = this.songs.findIndex(s => s.id === this.activeSongId);
                    nextIndex = (currentIndex + 1) % this.songs.length;
                }
                const nextSong = this.songs[nextIndex];
                if (nextSong) {
                    this.playSong(nextSong.id);
                }
            },
            playPrev() {
                if (this.songs.length === 0) return;
                let prevIndex = 0;
                if (this.playMode === 'shuffle') {
                    prevIndex = Math.floor(Math.random() * this.songs.length);
                } else if (this.playMode === 'repeat_one') {
                    const currentIndex = this.songs.findIndex(s => s.id === this.activeSongId);
                    prevIndex = currentIndex;
                } else {
                    const currentIndex = this.songs.findIndex(s => s.id === this.activeSongId);
                    prevIndex = currentIndex - 1;
                    if (prevIndex < 0) prevIndex = this.songs.length - 1;
                }
                const prevSong = this.songs[prevIndex];
                if (prevSong) {
                    this.playSong(prevSong.id);
                }
            },
            shuffleSongs() {
                if (this.songs.length === 0) return;
                this.songs = [...this.songs].sort(() => Math.random() - 0.5);
                this.playSong(this.songs[0].id);
            },
            filteredSongs() {
                let list = this.songs;
                if (this.musicSearchQuery.trim()) {
                    const query = this.musicSearchQuery.toLowerCase();
                    list = this.songs.filter(song => 
                        song.title.toLowerCase().includes(query) || 
                        (song.desc && song.desc.toLowerCase().includes(query))
                    );
                }
                const activeIdx = list.findIndex(song => song.id === this.activeSongId);
                if (activeIdx > -1) {
                    const activeSong = list[activeIdx];
                    const remainder = list.filter((_, idx) => idx !== activeIdx);
                    return [activeSong, ...remainder];
                }
                return list;
            },
            normalizeText(value) {
                return (value || '')
                    .toString()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();
            },
            matchesSearch(searchText) {
                const query = this.normalizeText(this.searchQuery);

                if (!query) {
                    return true;
                }

                return this.normalizeText(searchText).includes(query);
            },
            updateProgress() {
                const audio = this.$refs.musicAudio;
                if (audio) {
                    this.currentTime = audio.currentTime;
                    this.duration = audio.duration || 0;
                    this.playbackPercent = this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
                    const mins = Math.floor(this.currentTime / 60);
                    const secs = Math.floor(this.currentTime % 60);
                    this.currentTimeLabel = mins + ':' + (secs < 10 ? '0' : '') + secs;
                }
            }
        };
    };
</script>
<div id="navbar-container" data-turbo-permanent x-data="initNavbarComponent()" class="sticky top-2 sm:top-3 z-40 w-full">
<header
    id="global-navbar"
    @keydown.escape.window="publicSidebarOpen = false; guideOpen = false; musicPlayerOpen = false; syncPageLock()"
    :class="$store.theme.dark ? 'shadow-xl bg-slate-950 text-white border-slate-800' : 'shadow-md bg-white text-slate-900 border-slate-200'"
    class="mx-2 sm:mx-4 w-[calc(100%-1rem)] sm:w-[calc(100%-2rem)] max-w-full rounded-2xl sm:rounded-3xl border transition-all duration-300 backdrop-blur-2xl"
    role="banner"
>
    <nav class="w-full px-3 sm:px-6 lg:px-8" aria-label="Navigasi utama">
        <div class="flex min-h-[4rem] sm:min-h-[4.75rem] items-center justify-between gap-2 sm:gap-4 py-2 sm:py-3">
            <div class="flex items-center gap-2 sm:gap-4">
                <button type="button"
                        @if(request()->routeIs('admin.*'))
                            @click="$dispatch('toggle-sidebar')"
                            :aria-expanded="(typeof sidebarOpen !== 'undefined' ? sidebarOpen : false).toString()"
                        @else
                            @click="publicSidebarOpen = true"
                            :aria-expanded="publicSidebarOpen.toString()"
                        @endif
                        :class="$store.theme.dark ? 'bg-slate-900 text-white border-slate-700 hover:border-brand-500' : 'bg-white text-slate-700 border-slate-200 hover:border-brand-300'"
                        class="inline-flex h-10 sm:h-11 items-center justify-center gap-1.5 sm:gap-2 rounded-2xl border px-2.5 sm:px-4 text-xs sm:text-sm font-black uppercase tracking-[0.18em] shadow-sm transition"
                        aria-controls="primary-sidebar"
                        aria-label="Buka navigasi situs">
                    <svg class="h-4.5 w-4.5 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    <span class="hidden sm:inline">MENU</span>
                </button>

                {{-- Logo --}}
                <a href="{{ url('/') }}" class="flex shrink-0 items-center" aria-label="Poltekkes Bandung - Beranda">
                    <img src="{{ $logoUrl }}?v={{ file_exists(public_path('images/logo.png')) ? filemtime(public_path('images/logo.png')) : time() }}" alt="{{ $headerAppearance['logo_alt'] }}" width="44" height="44" class="h-8 sm:h-11 w-auto">
                </a>

                {{-- Back to Main Website Button --}}
                <a href="https://beta.poltekkesbandung.ac.id/"
                   target="_blank"
                   rel="noopener noreferrer"
                   :class="$store.theme.dark ? 'bg-slate-900 text-white border-slate-700 hover:border-brand-500' : 'bg-white text-slate-700 border-slate-200 hover:border-brand-300'"
                   class="hidden md:inline-flex h-11 items-center justify-center gap-1.5 rounded-2xl border px-4 text-xs font-bold uppercase tracking-[0.05em] shadow-sm transition"
                   title="Kembali ke Website Utama Poltekkes Bandung">
                    <span>Web Utama</span>
                    <span class="text-[10px] text-slate-400">↗</span>
                </a>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-2.5">
                <div :class="$store.theme.dark ? 'bg-slate-900/90 text-white border-slate-800' : 'bg-slate-50/90 text-slate-700 border-slate-200'" class="hidden items-center gap-3 rounded-full border px-5 py-2 lg:flex">
                    <a href="{{ $addressUrl ?: '#' }}"
                       @if($addressUrl) target="_blank" rel="noopener noreferrer" @endif
                       class="flex items-center gap-3 text-right {{ $addressUrl ? 'hover:text-brand-400 transition-colors' : '' }}">
                        <span class="text-brand-400" aria-hidden="true">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a8.97 8.97 0 0 0 6.219-2.485A8.96 8.96 0 0 0 21 12a9 9 0 1 0-18 0c0 5.25 4.03 8.558 9 9Zm0 0c1.933-2.18 4-5.104 4-8a4 4 0 1 0-8 0c0 2.896 2.067 5.82 4 8Z"/>
                            </svg>
                        </span>
                        <p :class="$store.theme.dark ? 'text-slate-200' : 'text-slate-700'" class="max-w-[24rem] text-sm font-semibold leading-5">{{ $headerAppearance['address_text'] }}</p>
                    </a>
                    @if(count($socialLinks) > 0)
                    <div class="h-8 w-px bg-slate-200 dark:bg-slate-800"></div>
                    <div class="flex items-center gap-2">
                        @foreach ($socialLinks as $social)
                        <a href="{{ $social['href'] }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           :class="$store.theme.dark ? 'bg-slate-800 text-slate-200 border-slate-700' : 'bg-white text-slate-500 border-slate-200'"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-full border transition hover:border-brand-500 hover:text-brand-400"
                           aria-label="{{ $social['label'] }}">
                            @if($social['icon'] === 'instagram')
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"/>
                            </svg>
                            @elseif($social['icon'] === 'youtube')
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M19.812 5.418c.861.23 1.538.907 1.768 1.768C21.998 8.746 22 12 22 12s0 3.255-.418 4.814a2.504 2.504 0 0 1-1.768 1.768c-1.56.419-7.814.419-7.814.419s-6.255 0-7.814-.419a2.505 2.505 0 0 1-1.768-1.768C2 15.255 2 12 2 12s0-3.255.417-4.814a2.507 2.507 0 0 1 1.768-1.768C5.744 5 11.998 5 11.998 5s6.255 0 7.814.418ZM15.194 12 10 15V9l5.194 3Z" clip-rule="evenodd"/>
                            </svg>
                            @else
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"/>
                            </svg>
                            @endif
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div data-no-translate :class="$store.theme.dark ? 'bg-slate-900 text-white border-slate-800' : 'bg-white text-slate-700 border-slate-200'" class="hidden items-center gap-1 rounded-full border px-2 py-1 lg:flex">
                    <span class="px-2 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">{{ $localeSwitcherLabel }}</span>
                    @foreach ($supportedLocales as $localeCode => $localeName)
                    <a href="{{ \App\Support\PublicLocale::switchUrl($localeCode) }}"
                       hreflang="{{ $localeCode }}"
                       @class([
                           'inline-flex rounded-full px-3 py-2 text-xs font-bold transition',
                           'bg-brand-600 text-white shadow-sm' => $currentLocale === $localeCode,
                           'text-slate-300 hover:bg-slate-800 hover:text-white' => $currentLocale !== $localeCode,
                       ])>
                        {{ strtoupper($localeCode) }}
                    </a>
                    @endforeach
                </div>

                {{-- Music Player Toggle & Panel --}}
                <div class="relative shrink-0" 
                     @click.outside="musicPlayerOpen = false"
                     @mouseenter="musicPlayerHovered = true"
                     @mouseleave="musicPlayerHovered = false">
                    <button type="button"
                            @click="toggleMusicPlayer()"
                            :aria-expanded="musicPlayerOpen.toString()"
                            aria-label="Buka Pemutar Musik Poltekkes Bandung"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 dark:border-slate-800 bg-white dark:bg-gray-900 text-slate-500 dark:text-slate-400 shadow-sm transition hover:border-brand-300 dark:hover:border-brand-600 hover:text-brand-700 dark:hover:text-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2 lg:h-11 lg:w-11 relative"
                            :class="musicPlayerOpen ? 'border-brand-300 text-brand-700 bg-brand-50/50' : 'bg-white dark:bg-gray-900'"
                            title="Lagu Poltekkes Bandung">
                        
                        {{-- Circular Progress SVG overlay --}}
                        <svg class="absolute inset-0 w-full h-full -rotate-90 pointer-events-none p-0.5" viewBox="0 0 36 36">
                            <circle class="text-slate-100 dark:text-slate-800" stroke="currentColor" stroke-width="2.5" fill="transparent" cx="18" cy="18" r="15"></circle>
                            <circle class="text-brand-500 transition-all duration-300" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="transparent" cx="18" cy="18" r="15"
                                    stroke-dasharray="94.25"
                                    :stroke-dashoffset="94.25 * (1 - playbackPercent / 100)"></circle>
                        </svg>

                        <svg class="h-5 w-5 relative z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2Zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2ZM9 10l12-3" />
                        </svg>
                        <span class="absolute right-0.5 top-0.5 flex h-2 w-2 z-10" x-show="!isPlaying">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                        </span>
                    </button>

                    {{-- Hover Preview Player Tooltip --}}
                    <div x-show="musicPlayerHovered && !musicPlayerOpen"
                         x-cloak
                         x-transition
                         class="absolute right-0 top-[calc(100%+0.5rem)] z-[90] w-64 rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 p-3 shadow-xl flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg overflow-hidden bg-slate-800 shrink-0 border border-slate-250 dark:border-slate-700 flex items-center justify-center">
                            <template x-if="getActiveSong().image_url">
                                <img :src="getActiveSong().image_url" alt="Cover" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!getActiveSong().image_url">
                                <span class="text-lg">🎵</span>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1 text-left">
                            <button type="button" 
                                    @click="togglePlay()"
                                    class="block text-xs font-bold text-slate-800 dark:text-white truncate hover:text-brand-600 transition-colors w-full text-left"
                                    :title="'Klik untuk ' + (isPlaying ? 'Jeda' : 'Putar')">
                                <span x-text="getActiveSong().title"></span>
                            </button>
                            <p class="text-[10px] text-slate-400 truncate mt-0.5" x-text="getActiveSong().desc"></p>
                            <div class="flex items-center justify-between mt-1 text-[9px] text-slate-500 font-mono">
                                <span x-text="currentTimeLabel">0:00</span>
                                <span x-text="getActiveSong().duration">0:00</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            {{-- Prev Button --}}
                            <button type="button" 
                                    @click="playPrev()"
                                    title="Lagu Sebelumnya"
                                    class="text-slate-400 hover:text-brand-500 p-1 transition-colors">
                                <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M6 6h2v12H6zm3.5 6 8.5 6V6z"/>
                                </svg>
                            </button>

                            {{-- Play/Pause Button --}}
                            <button type="button" 
                                    @click="togglePlay()"
                                    class="h-7 w-7 rounded-full bg-brand-500 text-white flex items-center justify-center shadow hover:bg-brand-600 transition-transform active:scale-95 cursor-pointer shrink-0">
                                <template x-if="isPlaying">
                                    <svg class="h-3 w-3 fill-current" viewBox="0 0 24 24">
                                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                                    </svg>
                                </template>
                                <template x-if="!isPlaying">
                                    <svg class="h-3 w-3 fill-current ml-0.5" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </template>
                            </button>

                            {{-- Next Button --}}
                            <button type="button" 
                                    @click="playNext()"
                                    title="Lagu Berikutnya"
                                    class="text-slate-400 hover:text-brand-500 p-1 transition-colors">
                                <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/>
                                </svg>
                            </button>

                            {{-- Playback Mode Toggle --}}
                            <button type="button" 
                                    @click="togglePlayMode()"
                                    class="text-slate-400 hover:text-brand-500 p-1 transition-colors relative"
                                    :title="playMode === 'loop' ? 'Ulangi Semua (Loop)' : (playMode === 'shuffle' ? 'Putar Acak (Shuffle)' : 'Ulangi Satu Lagu (Repeat One)')">
                                <template x-if="playMode === 'loop'">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="17 1 21 5 17 9"></polyline>
                                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                        <polyline points="7 23 3 19 7 15"></polyline>
                                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                    </svg>
                                </template>
                                <template x-if="playMode === 'shuffle'">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="4" y1="4" x2="9" y2="9"></line>
                                        <line x1="4" y1="20" x2="20" y2="4"></line>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <polyline points="15 21 21 21 21 15"></polyline>
                                        <line x1="15" y1="9" x2="21" y2="3"></line>
                                        <line x1="4" y1="20" x2="9" y2="15"></line>
                                    </svg>
                                </template>
                                <template x-if="playMode === 'repeat_one'">
                                    <svg class="h-3.5 w-3.5 relative" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="17 1 21 5 17 9"></polyline>
                                        <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                        <polyline points="7 23 3 19 7 15"></polyline>
                                        <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                                        <text x="8" y="14" font-size="8" font-family="sans-serif" font-weight="extrabold" fill="currentColor" stroke="none">1</text>
                                    </svg>
                                </template>
                             </button>
                        </div>
                    </div>

                    <div id="navbar-music-panel"
                         x-show="musicPlayerOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                         class="absolute right-0 top-[calc(100%+0.75rem)] z-[80] overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                         style="width: calc(100vw - 1.5rem); max-width: 416px; height: 580px;">
                        
                        <style>
                        @media (max-width: 1023px) {
                          #navbar-music-panel {
                            position: fixed !important;
                            top: 5rem !important;
                            left: 50% !important;
                            right: auto !important;
                            transform: translateX(-50%) !important;
                            width: calc(100vw - 1.5rem) !important;
                            max-width: 416px !important;
                            height: 580px !important;
                          }
                        }
                        </style>

                        <iframe src="/suno-player/embed" class="w-full h-full border-none" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
                    </div>
                </div>

                {{-- Dark Mode Toggle --}}
                <button type="button"
                        @click="$store.theme.toggle()"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-brand-300 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2 lg:h-11 lg:w-11"
                        aria-label="Ganti Tema Gelap/Terang"
                        title="Ganti Tema">
                    <svg x-show="!$store.theme.dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    <svg x-show="$store.theme.dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </button>

                @if($guide)
                <div class="relative shrink-0" @click.outside="guideOpen = false">
                    <button type="button"
                            @click="toggleGuide()"
                            :aria-expanded="guideOpen.toString()"
                            aria-controls="navbar-guide-panel"
                            aria-label="Buka panduan halaman"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-600 text-white shadow-lg shadow-brand-900/25 transition hover:bg-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2 lg:h-11 lg:w-11">
                        <span class="-mt-[1px] text-[1.55rem] font-black leading-none">?</span>
                    </button>

                    <div id="navbar-guide-panel"
                         x-show="guideOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-2 scale-[0.98]"
                         class="absolute right-0 top-[calc(100%+0.75rem)] z-[80] w-[calc(100vw-1.5rem)] max-w-[30rem] overflow-hidden rounded-[2rem] border border-sky-200 bg-white shadow-2xl dark:border-sky-900/60 dark:bg-gray-900">

                        <div class="bg-gradient-to-br from-sky-900 via-brand-800 to-brand-700 px-5 py-5 text-white sm:px-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-white/95">
                                            <span aria-hidden="true">{{ $guide['group_icon'] ?? 'ℹ️' }}</span>
                                            {{ $guide['group_label'] ?? 'Panduan' }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-black/20 px-3 py-1 text-[11px] font-semibold text-white/95">
                                            Help halaman ini
                                        </span>
                                    </div>
                                    <h2 class="mt-3 text-lg font-extrabold leading-tight text-white">{{ $guide['title'] }}</h2>
                                    <p class="mt-2 text-sm leading-6 text-white/95">{{ $guide['summary'] }}</p>
                                </div>

                                <button type="button"
                                        @click="guideOpen = false"
                                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 text-lg text-white transition-colors hover:bg-white/25"
                                        aria-label="Tutup panduan">
                                    ×
                                </button>
                            </div>
                        </div>

                        <div class="max-h-[min(32rem,calc(100vh-8rem))] overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                            @if(!empty($guide['steps']))
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">Langkah Cepat</h3>
                                <ol class="mt-3 space-y-3">
                                    @foreach($guide['steps'] as $index => $step)
                                    <li class="flex gap-3 rounded-2xl bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900 dark:bg-sky-950/30 dark:text-sky-100">
                                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/85 text-[11px] font-black text-gray-900">{{ $index + 1 }}</span>
                                        <span>{{ $step }}</span>
                                    </li>
                                    @endforeach
                                </ol>
                            </div>
                            @endif

                            <div class="mt-4 grid gap-4">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/60">
                                    <h3 class="text-sm font-extrabold text-gray-900 dark:text-white">Fitur Utama</h3>
                                    <ul class="mt-3 space-y-2 text-sm leading-6 text-gray-800 dark:text-gray-100">
                                        @foreach($guide['features'] as $feature)
                                        <li class="flex gap-2">
                                            <span class="mt-1 text-brand-600 dark:text-brand-300">•</span>
                                            <span>{{ $feature }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>

                                @if(!empty($guide['tips']))
                                <div class="rounded-2xl border border-gray-200 bg-amber-50/90 p-4 dark:border-gray-800 dark:bg-amber-950/20">
                                    <h3 class="text-sm font-extrabold text-amber-950 dark:text-amber-100">Tips dan Catatan</h3>
                                    <ul class="mt-3 space-y-2 text-sm leading-6 text-amber-950/90 dark:text-amber-50">
                                        @foreach($guide['tips'] as $tip)
                                        <li class="flex gap-2">
                                            <span class="mt-1 text-amber-600 dark:text-amber-300">•</span>
                                            <span>{{ $tip }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </nav>

    @if(!request()->routeIs('admin.*'))
        <div x-show="publicSidebarOpen"
             x-cloak
             class="fixed inset-0"
             style="z-index: 2000; width: 125vw; height: 125vh;"
             aria-hidden="false">
            <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-md" @click="publicSidebarOpen = false"></div>

            <aside id="primary-sidebar"
                   x-show="publicSidebarOpen"
                   @click.outside="publicSidebarOpen = false"
                   x-transition:enter="transform transition ease-out duration-200"
                   x-transition:enter-start="-translate-x-full opacity-0"
                   x-transition:enter-end="translate-x-0 opacity-100"
                   x-transition:leave="transform transition ease-in duration-150"
                   x-transition:leave-start="translate-x-0 opacity-100"
                   x-transition:leave-end="-translate-x-full opacity-0"
                   class="absolute inset-y-0 left-0 flex w-full max-w-sm flex-col overflow-hidden bg-white dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800 shadow-2xl"
                   style="height: 125vh; min-height: 125vh; z-index: 1;">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 px-5 py-4 bg-white dark:bg-slate-950">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo-polkesban.png') }}" alt="{{ $headerAppearance['logo_alt'] }}" width="42" height="42" class="h-10 w-auto">
                    </div>
                    <button @click="publicSidebarOpen = false"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 transition hover:border-brand-300 dark:hover:border-brand-500 hover:text-brand-700 dark:hover:text-brand-400 bg-white dark:bg-slate-900"
                            aria-label="Tutup navigasi situs">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-4 bg-white dark:bg-slate-950">
                    <p class="px-2 text-[11px] font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Menu Utama</p>
                    <div class="mt-3 px-1">
                        <label for="sidebar-menu-search" class="sr-only">Cari menu</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/>
                                </svg>
                            </span>
                            <input id="sidebar-menu-search"
                                   type="search"
                                   x-model="searchQuery"
                                   placeholder="Cari menu, jurusan, unit, HIMA..."
                                   class="w-full rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 py-3 pl-11 pr-4 text-sm text-slate-800 dark:text-slate-100 outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                        </div>
                    </div>
                    <nav class="mt-3 space-y-3" aria-label="Daftar seluruh menu situs">
                        @include('components.public-sidebar-menu-items', [
                            'items' => $navTree,
                            'depth' => 0,
                            'resolveHref' => $resolveHref,
                            'isActiveHref' => $isActiveHref,
                            'hasActiveDescendant' => $hasActiveDescendant,
                        ])
                    </nav>
                </div>

                <div class="shrink-0 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/90 px-4 py-4">
                    <div class="mt-2">
                        @if(\App\Http\Controllers\PublicSectionController::hasVirtualOfficeAccess())
                            <div class="col-span-2 mb-2 p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                                @if(session()->has('google_user'))
                                    <div class="flex items-center gap-3">
                                        <img src="{{ session('google_user.picture') }}" alt="Google Profile" class="w-10 h-10 rounded-full object-cover border border-brand-200 shrink-0">
                                        <div class="text-left min-w-0">
                                            <p class="text-sm font-bold text-gray-950 dark:text-white truncate leading-tight">{{ session('google_user.name') }}</p>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ session('google_user.email') }}</p>
                                        </div>
                                    </div>
                                @elseif(auth()->check())
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}
                                        </div>
                                        <div class="text-left min-w-0">
                                            <p class="text-sm font-bold text-gray-950 dark:text-white truncate leading-tight">{{ auth()->user()->name }}</p>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ auth()->user()->role_label ?? 'Admin' }}</p>
                                        </div>
                                    </div>
                                @elseif(auth('mahasiswa')->check())
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ mb_substr(auth('mahasiswa')->user()->name ?? 'M', 0, 1) }}
                                        </div>
                                        <div class="text-left min-w-0">
                                            <p class="text-sm font-bold text-gray-950 dark:text-white truncate leading-tight">{{ auth('mahasiswa')->user()->name }}</p>
                                            <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">Mahasiswa</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2">
                                @if(auth()->check())
                                    <a href="{{ route('admin.dashboard') }}"
                                       class="flex h-11 items-center justify-center gap-1.5 rounded-2xl bg-brand-600 hover:bg-brand-700 px-3 text-xs font-bold text-white transition cursor-pointer">
                                        <span aria-hidden="true">🛡️</span>
                                        Dashboard
                                    </a>
                                @elseif(auth('mahasiswa')->check())
                                    <a href="{{ route('mahasiswa.portofolio.index') }}"
                                       class="flex h-11 items-center justify-center gap-1.5 rounded-2xl bg-brand-600 hover:bg-brand-700 px-3 text-xs font-bold text-white transition cursor-pointer">
                                        <span aria-hidden="true">🎓</span>
                                        Portal Mhs
                                    </a>
                                @endif
                                
                                <form method="POST" action="{{ route('logout.virtual-office') }}" class="m-0">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex h-11 items-center justify-center gap-1.5 rounded-2xl bg-red-50 dark:bg-red-950/20 hover:bg-red-100 dark:hover:bg-red-900/30 px-3 text-xs font-bold text-red-700 dark:text-red-400 transition cursor-pointer border border-transparent dark:border-red-900/30">
                                        <span aria-hidden="true">🚪</span>
                                        Keluar VO
                                    </button>
                                </form>
                            </div>

                        @else
                            <a href="{{ route('login.virtual-office') }}"
                               class="flex items-center justify-center gap-2 rounded-2xl border border-slate-200 dark:border-gray-800 bg-slate-50 dark:bg-gray-900 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-gray-300 transition hover:border-slate-300 dark:hover:border-gray-700 hover:bg-slate-100 dark:hover:bg-gray-800">
                                <span aria-hidden="true">🛡️</span>
                                Login Virtual Office
                            </a>
                            @auth('mahasiswa')
                            <a href="{{ route('mahasiswa.portofolio.index') }}"
                               @click="publicSidebarOpen = false"
                               class="inline-flex h-11 items-center justify-center gap-2 rounded-2xl bg-brand-600 px-4 text-xs font-bold text-white transition hover:bg-brand-500 shadow-md">
                                🏠 Portal Mhs
                            </a>
                            @else
                            <a href="{{ route('mahasiswa.login') }}"
                               class="flex items-center justify-center gap-2 rounded-2xl border border-gray-250 dark:border-gray-800 px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-350 transition hover:border-brand-200 dark:hover:border-brand-800 hover:bg-brand-50 dark:hover:bg-brand-950/30 hover:text-brand-700 dark:hover:text-brand-400">
                                <span aria-hidden="true">🎓</span>
                                Portal Mahasiswa
                            </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    @endif
</header>

    {{-- Fullscreen Karaoke Player Overlay --}}
    <div x-show="fullscreenPlayerOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-8 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-8 scale-95"
         class="fixed inset-0 z-[120] flex flex-col overflow-hidden p-6 sm:p-10 select-none"
         style="background-color: rgba(9, 9, 11, 0.99); backdrop-filter: blur(45px); -webkit-backdrop-filter: blur(45px);">
         
         {{-- Blurred Dynamic Cover Art Background --}}
         <div class="absolute inset-0 bg-cover bg-center opacity-25 filter blur-[100px] pointer-events-none scale-110 transition-all duration-700" 
              :style="'background-image: url(' + (getActiveSong().image_url || '') + ')'"></div>

         {{-- Animated Sparkling Stars Background --}}
         <div class="absolute inset-0 overflow-hidden pointer-events-none z-[5] select-none opacity-80">
             <template x-for="i in Array.from({length: 30}, (_, idx) => idx)">
                 <div class="absolute rounded-full bg-white transition-all duration-1000 star-glow"
                      :style="{
                          width: (3 + Math.random() * 4) + 'px',
                          height: (3 + Math.random() * 4) + 'px',
                          left: (Math.random() * 100) + '%',
                          top: (Math.random() * 100) + '%',
                          animation: isPlaying ? 'float-and-sparkle ' + (4 + Math.random() * 6) + 's ease-in-out infinite alternate' : 'none',
                          animationDelay: (Math.random() * 5) + 's'
                      }"></div>
             </template>
         </div>

         <div class="w-full flex items-center justify-between z-30 mb-6 shrink-0">
              <div class="flex items-center gap-3">
                  <button type="button" 
                          @click="fullscreenPlayerOpen = false"
                          class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all backdrop-blur active:scale-95 shadow">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                      </svg>
                      Kembali
                  </button>

                  {{-- Sync Playlist Button in Fullscreen Mode --}}
                  <button type="button" 
                          @click="loadSongs(true)" 
                          title="Sinkronisasi Playlist"
                          class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all backdrop-blur active:scale-95 shadow">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="isLoading ? 'animate-spin' : ''">
                          <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                      </svg>
                      <span>Sinkron</span>
                  </button>
              </div>
              
              <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-[0.25em] text-white/50 bg-white/5 px-4 py-1.5 rounded-full border border-white/5">Karaoke Mode</span>
          </div>

          <div class="flex-1 flex flex-col lg:flex-row min-h-0 w-full overflow-hidden relative z-10">
              {{-- Left Section: Song Info & Controls --}}
              <div class="flex-1 flex flex-col justify-center items-center text-center p-2 lg:p-6 lg:border-r lg:border-white/5 relative z-10 overflow-y-auto max-h-full">
             {{-- POLTEKKES BANDUNG RADIO Brand Title --}}
             <div class="hidden lg:flex flex-col items-center gap-2.5 shrink-0 mb-3">
                 <img src="{{ asset('images/logo-polkesban.png') }}" alt="Logo Poltekkes Bandung" class="h-12 w-auto object-contain opacity-85 filter drop-shadow-[0_0_15px_rgba(255,255,255,0.25)]">
                 <h1 class="text-white font-black text-lg sm:text-xl lg:text-2xl tracking-[0.2em] uppercase drop-shadow shadow-glow mt-1">POLTEKKES BANDUNG RADIO</h1>
             </div>

             {{-- Large Cover Art --}}
             <div class="relative w-28 h-28 sm:w-36 sm:h-36 lg:w-48 lg:h-48 rounded-2xl lg:rounded-[1.75rem] overflow-hidden shadow-2xl border-2 border-white/10 group mb-3 lg:mb-5 shrink-0">
                 <template x-if="getActiveSong().image_url">
                     <img :src="getActiveSong().image_url" alt="Cover Art" 
                          class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                          :class="isPlaying ? 'animate-[spin_20s_linear_infinite]' : ''">
                 </template>
                 <template x-if="!getActiveSong().image_url">
                     <div class="w-full h-full bg-gradient-to-br from-brand-900 to-slate-900 flex items-center justify-center">
                         <span class="text-7xl">🎵</span>
                     </div>
                 </template>
                 <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                     <span class="text-white text-sm font-bold tracking-wider">Poltekkes Bandung Radio</span>
                 </div>
             </div>

             {{-- Song Meta --}}
             <div class="w-full max-w-md px-4 mb-4 lg:mb-8 shrink-0 flex items-center justify-between gap-4">
                 <div class="text-left truncate flex-1">
                     <h2 class="text-lg sm:text-2xl lg:text-3xl font-black text-white tracking-tight drop-shadow-md truncate" x-text="getActiveSong().title"></h2>
                     <p class="hidden sm:inline-block text-xs sm:text-sm text-brand-400 font-semibold mt-2 px-3 py-1 rounded-full bg-brand-500/10 border border-brand-500/20 truncate" x-text="getActiveSong().desc"></p>
                 </div>
                 <a :href="'https://suno.com/song/' + activeSongId" 
                     target="_blank" 
                     class="h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-lg shadow-rose-500/35 hover:from-rose-400 hover:to-pink-400 transition-all flex items-center justify-center shrink-0 group/heart scale-100 hover:scale-105 active:scale-95" 
                     title="Sukai & Subscribe di Suno.com">
                      <svg class="h-5 w-5 sm:h-6 sm:w-6 transition-transform group-hover/heart:scale-110" fill="currentColor" viewBox="0 0 24 24">
                          <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                      </svg>
                  </a>
             </div>

             {{-- Progress Bar --}}
             <div class="w-full max-w-md px-4 mb-6 shrink-0">
                 <div class="relative group cursor-pointer" 
                      @click="
                          const rect = $el.getBoundingClientRect();
                          const percent = ($event.clientX - rect.left) / rect.width;
                          const audio = $refs.musicAudio;
                          if (audio && audio.duration) {
                              audio.currentTime = percent * audio.duration;
                          }
                      ">
                     {{-- Track line --}}
                     <div class="h-1.5 w-full bg-white/10 rounded-full transition-all group-hover:h-2">
                         <div class="h-full bg-gradient-to-r from-brand-400 to-brand-500 rounded-full relative" :style="'width: ' + playbackPercent + '%'">
                             <div class="absolute right-0 top-1/2 -translate-y-1/2 w-3.5 h-3.5 bg-white rounded-full shadow-lg scale-0 group-hover:scale-100 transition-transform"></div>
                         </div>
                     </div>
                 </div>
                 <div class="flex items-center justify-between text-[11px] font-mono text-white/55 mt-2">
                     <span x-text="currentTimeLabel">0:00</span>
                     <span x-text="getActiveSong().duration">0:00</span>
                 </div>
             </div>

             {{-- Control Buttons --}}
             <div class="flex items-center gap-6 sm:gap-8 shrink-0">
                 {{-- Play Mode --}}
                 <button type="button" 
                         @click="togglePlayMode()"
                         class="text-white/40 hover:text-white transition-colors p-2"
                         :title="playMode === 'loop' ? 'Ulangi Semua (Loop)' : (playMode === 'shuffle' ? 'Acak Lagu (Shuffle)' : 'Ulangi Satu Lagu (Repeat One)')">
                      <template x-if="playMode === 'loop'">
                          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <polyline points="17 1 21 5 17 9"></polyline>
                              <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                              <polyline points="7 23 3 19 7 15"></polyline>
                              <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                          </svg>
                      </template>
                      <template x-if="playMode === 'shuffle'">
                          <svg class="h-5 w-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <line x1="4" y1="4" x2="9" y2="9"></line>
                              <line x1="4" y1="20" x2="20" y2="4"></line>
                              <polyline points="15 3 21 3 21 9"></polyline>
                              <polyline points="15 21 21 21 21 15"></polyline>
                              <line x1="15" y1="9" x2="21" y2="3"></line>
                              <line x1="4" y1="20" x2="9" y2="15"></line>
                          </svg>
                      </template>
                      <template x-if="playMode === 'repeat_one'">
                          <svg class="h-5 w-5 text-brand-400 relative" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <polyline points="17 1 21 5 17 9"></polyline>
                              <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                              <polyline points="7 23 3 19 7 15"></polyline>
                              <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                              <text x="8" y="15" font-size="9" font-family="sans-serif" font-weight="black" fill="currentColor">1</text>
                          </svg>
                      </template>
                  </button>

                 {{-- Prev --}}
                 <button type="button" 
                         @click="playPrev()"
                         class="text-white/70 hover:text-white transition-colors p-2 active:scale-90 transition-transform">
                     <svg class="h-7 w-7 fill-current" viewBox="0 0 24 24">
                         <path d="M6 6h2v12H6zm3.5 6 8.5 6V6z"/>
                     </svg>
                 </button>

                 {{-- Play/Pause --}}
                 <button type="button" 
                         @click="togglePlay()"
                         class="h-16 w-16 rounded-full bg-white hover:bg-slate-100 text-slate-950 flex items-center justify-center shadow-2xl active:scale-95 transition-transform">
                     <template x-if="isPlaying">
                         <svg class="h-7 w-7 fill-current" viewBox="0 0 24 24">
                             <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                         </svg>
                     </template>
                     <template x-if="!isPlaying">
                         <svg class="h-7 w-7 fill-current ml-1" viewBox="0 0 24 24">
                             <path d="M8 5v14l11-7z"/>
                         </svg>
                     </template>
                 </button>

                 {{-- Next --}}
                 <button type="button" 
                         @click="playNext()"
                         class="text-white/70 hover:text-white transition-colors p-2 active:scale-90 transition-transform">
                     <svg class="h-7 w-7 fill-current" viewBox="0 0 24 24">
                         <path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/>
                     </svg>
                 </button>

                 {{-- Mute / Unmute Button --}}
                 <button type="button" 
                         @click="toggleMute()"
                         class="text-white/40 hover:text-white transition-colors p-2"
                         :title="muted ? 'Bunyikan' : 'Senyap'">
                     <template x-if="!muted">
                         <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                             <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                             <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                         </svg>
                     </template>
                     <template x-if="muted">
                         <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                             <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                             <line x1="23" y1="9" x2="17" y2="15"></line>
                             <line x1="17" y1="9" x2="23" y2="15"></line>
                         </svg>
                     </template>
                 </button>
             </div>
         </div>

         {{-- Right Section: Karaoke Lyrics --}}
          <div class="flex-1 flex flex-col p-2 lg:p-10 relative z-20 max-h-[42vh] lg:max-h-full overflow-hidden mt-2 lg:mt-0">
              <h3 class="text-white/40 text-[10px] sm:text-xs font-bold uppercase tracking-[0.2em] mb-3 text-center lg:text-left shrink-0">Lirik Lagu / Sing Along</h3>
              
              {{-- Full Lyrics Container --}}
              <div class="lyrics-container flex-1 overflow-y-auto pr-2 space-y-1 text-center lg:text-left scrollbar-thin scrollbar-thumb-white/10">
                  <template x-for="(line, index) in getLyrics()">
                      <div :class="{
                               'text-white/80 font-medium text-xs sm:text-sm lg:text-[13px] leading-relaxed': !(line.startsWith('[') && line.endsWith(']')),
                               'text-brand-400 font-bold uppercase tracking-wider text-[10px] sm:text-xs mt-3 mb-1': line.startsWith('[') && line.endsWith(']')
                           }"
                           class="lyric-line py-0.5 px-2 rounded-lg break-words"
                           x-text="line"></div>
                  </template>
              </div>
          </div>
          </div>

         <style>
             .shadow-glow {
                 text-shadow: 0 0 20px rgba(255, 255, 255, 0.4);
             }
             .star-glow {
                 box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.9), 0 0 20px 4px rgba(255, 255, 255, 0.4);
             }
             @keyframes float-and-sparkle {
                  0% {
                      opacity: 0.25;
                      transform: translateY(0) scale(0.7);
                  }
                  50% {
                      opacity: 1.0;
                      transform: translateY(-20px) scale(1.3);
                  }
                  100% {
                      opacity: 0.25;
                      transform: translateY(-40px) scale(0.7);
                  }
              }
             /* Custom scrollbar styling */
             .scrollbar-thin::-webkit-scrollbar {
                 width: 4px;
             }
             .scrollbar-thin::-webkit-scrollbar-track {
                 background: transparent;
             }
             .scrollbar-thin::-webkit-scrollbar-thumb {
                 background: rgba(255, 255, 255, 0.1);
                 border-radius: 999px;
             }
         </style>
    </div>
</div>
