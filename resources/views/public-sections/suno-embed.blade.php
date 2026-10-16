<!DOCTYPE html>
<html lang="id" class="h-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poltekkes Bandung Radio Embed Player</title>
    <!-- Web App and Background Play Support -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Poltekkes Bandung Radio">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

    <!-- Vite Assets (Tailwind CSS and Alpine.js) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --color-brand-50: #f2fbf7;
            --color-brand-100: #e2f6ed;
            --color-brand-200: #c5ecd9;
            --color-brand-300: #99dbba;
            --color-brand-400: #64c194;
            --color-brand-500: #3ea575;
            --color-brand-600: #2e865c;
            --color-brand-700: #266c4c;
            --color-brand-800: #21563f;
            --color-brand-900: #1c4735;
            --color-brand-950: #0f291e;
        }
        body {
            font-family: "Plus Jakarta Sans", sans-serif;
        }
        .simpel-bg-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
            pointer-events: none;
        }
        .simpel-particle {
            position: absolute;
            bottom: -120px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.3) 0%, rgba(0, 162, 154, 0.14) 50%, rgba(255, 255, 255, 0) 100%);
            border-radius: 50%;
            pointer-events: none;
            animation: floatUp 15s linear infinite;
        }
        @keyframes floatUp {
            0% {
                transform: translateY(0) translateX(0) scale(0.8);
                opacity: 0;
            }
            10% {
                opacity: 0.75;
            }
            90% {
                opacity: 0.75;
            }
            100% {
                transform: translateY(-125vh) translateX(80px) scale(1.3);
                opacity: 0;
            }
        }
        @keyframes soundwave {
            0%, 100% { height: 4px; }
            50% { height: 16px; }
        }
        .wave-bar {
            width: 2.5px;
            background-color: currentColor;
            animation: soundwave 1s ease-in-out infinite;
            border-radius: 9999px;
        }
        .wave-bar:nth-child(2) { animation-delay: 0.15s; }
        .wave-bar:nth-child(3) { animation-delay: 0.3s; }
        .wave-bar:nth-child(4) { animation-delay: 0.45s; }
        
        /* Custom scrollbar for premium feel */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(15, 23, 42, 0.2);
            border-radius: 9999px;
        }
        .dark ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }
        .dark ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body x-data="initEmbedPlayer()" 
      class="min-h-screen bg-gradient-to-br from-[#004d47] via-[#006e65] to-[#008f87] font-sans antialiased flex items-center justify-center p-4 relative overflow-hidden select-none">

    {{-- Floating waterdrops background matching the virtual office login page --}}
    <div class="simpel-bg-particles" id="simpelBgParticles"></div>

    {{-- Ambient light effects / Futuristic Tech Mesh --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden z-0">
        <div class="absolute -top-40 -left-40 h-[600px] w-[600px] rounded-full bg-teal-500/10 dark:bg-teal-500/20 blur-[130px] animate-pulse" style="animation-duration: 8s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[800px] w-[800px] rounded-full bg-cyan-500/5 dark:bg-cyan-500/10 blur-[160px]"></div>
        <div class="absolute -bottom-40 -right-40 h-[600px] w-[600px] rounded-full bg-yellow-500/5 dark:bg-yellow-500/15 blur-[130px] animate-pulse" style="animation-duration: 10s;"></div>
        
        <!-- Grid overlay -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#00a29a05_1px,transparent_1px),linear-gradient(to_bottom,#00a29a05_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#ffffff03_1px,transparent_1px),linear-gradient(to_bottom,#ffffff03_1px,transparent_1px)] bg-[size:3.5rem_3.5rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)]"></div>
    </div>

    {{-- Safe global data declaration matching navbar --}}
    <script>
        window.sunoSongs = @json($sunoSongs);
        window.podcastEpisodes = @json($podcastEpisodes);
        window.videoItems = @json($videoItems ?? []);
        
        window.addEventListener('unhandledrejection', function(event) {
            if (event.reason && (event.reason.isFromCancelledTransition || event.reason.message === 'The play() request was interrupted by a new load request.')) {
                event.preventDefault();
            }
        });

        window.initEmbedPlayer = function() {
            return {
                isLoading: false,
                isPlaying: false,
                isAudioLoading: false,
                playMode: 'shuffle',
                playbackPercent: 0,
                currentTimeLabel: '0:00',
                currentTime: 0,
                duration: 0,
                activeSongId: localStorage.getItem('suno_player_song_id') || '',
                nextSongIdToPlay: null,
                prefetchTimeoutId: null,
                autoCacheTimeoutId: null,
                cachedSongIds: [],
                downloadingSongIds: [],
                songs: window.sunoSongs || [],
                podcastEpisodes: window.podcastEpisodes || [],
                videos: window.videoItems || [],
                activeTab: localStorage.getItem('suno_player_active_tab') || 'radio',
                musicSearchQuery: '',
                
                get currentPlaylist() {
                    if (this.activeTab === 'radio') return this.songs;
                    if (this.activeTab === 'video') return this.videos;
                    return this.podcastEpisodes;
                },
                get currentMediaElement() {
                    if (this.activeTab === 'video') {
                        return this.fullscreenPlayerOpen ? this.$refs.fullscreenVideo : this.$refs.musicVideo;
                    }
                    return this.$refs.musicAudio;
                },
                fullscreenPlayerOpen: true, // Directly to Karaoke mode!
                showEmbedPlaylist: true,     // Directly show playlist drawer!
                showZoomedCover: false,      // Zoomed image modal
                muted: false,
                lyricOffset: 0,
                lyricTimeline: null,
                lyricTimelineSongId: null,
                lyricTimelineDuration: 0,
                isNativeFullscreen: false,
                wakeLock: null,

                async requestWakeLock() {
                    try {
                        if ('wakeLock' in navigator) {
                            this.wakeLock = await navigator.wakeLock.request('screen');
                            console.log('Screen Wake Lock is active');
                        }
                    } catch (err) {
                        console.error('Failed to lock screen wake:', err);
                    }
                },

                releaseWakeLock() {
                    if (this.wakeLock) {
                        this.wakeLock.release().then(() => {
                            this.wakeLock = null;
                            console.log('Screen Wake Lock released');
                        });
                    }
                },

                toggleNativeFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().then(() => {
                            this.isNativeFullscreen = true;
                        }).catch(err => {
                            console.error('Fullscreen request blocked:', err);
                        });
                    } else {
                        document.exitFullscreen().then(() => {
                            this.isNativeFullscreen = false;
                        });
                    }
                },
                
                async init() {
                    // Register Service Worker for offline and background caching
                    if ('serviceWorker' in navigator) {
                        navigator.serviceWorker.register('/suno-player-sw.js')
                            .then(reg => console.log('Service Worker registered successfully:', reg.scope))
                            .catch(err => console.error('Service Worker registration failed:', err));
                    }

                    // Listen for native fullscreen changes (e.g. Esc press)
                    document.addEventListener('fullscreenchange', () => {
                        this.isNativeFullscreen = !!document.fullscreenElement;
                    });

                    // Manage Wake Lock on visibility changes
                    document.addEventListener('visibilitychange', async () => {
                        if (this.isPlaying && document.visibilityState === 'visible') {
                            await this.requestWakeLock();
                        }
                    });

                    // Request wake lock initially if active
                    if (this.isPlaying) {
                        await this.requestWakeLock();
                    }

                    // Fetch dynamic updates
                    await this.loadSongs();
                    await this.checkOfflineCache();

                    const savedSongId = localStorage.getItem('suno_player_song_id');
                    const savedTime = localStorage.getItem('suno_player_time');
                    const savedPlaying = localStorage.getItem('suno_player_playing');
                    const savedPlayMode = localStorage.getItem('suno_player_play_mode');

                    if (savedPlayMode) {
                        this.playMode = savedPlayMode;
                    }

                    if (savedSongId) {
                        this.activeSongId = savedSongId;
                        this.$nextTick(() => {
                            const media = this.currentMediaElement;
                            if (media) {
                                const song = this.getActiveSong();
                                if (song) {
                                    const srcUrl = this.activeTab === 'video' ? song.video_url : song.audio_url;
                                    if (srcUrl) {
                                        media.src = srcUrl;
                                        media.load();
                                        if (savedTime) {
                                            media.currentTime = parseFloat(savedTime);
                                        }
                                        if (savedPlaying === 'true') {
                                            media.play().then(() => {
                                                this.isPlaying = true;
                                            }).catch(err => {
                                                console.log('Autoplay blocked initially.');
                                                this.isPlaying = false;
                                            });
                                        }
                                    }
                                }
                            }
                        });
                    }

                    setInterval(() => {
                        const media = this.currentMediaElement;
                        if (media) {
                            if (this.isPlaying) {
                                localStorage.setItem('suno_player_time', media.currentTime);
                                localStorage.setItem('suno_player_song_id', this.activeSongId);
                            }
                            localStorage.setItem('suno_player_play_mode', this.playMode);
                        }
                    }, 500);

                    this.$watch('isPlaying', async (value) => {
                        if (value) {
                            if (this.fullscreenPlayerOpen) {
                                this.scrollLoop();
                            }
                            await this.requestWakeLock();
                        } else {
                            this.releaseWakeLock();
                        }
                        if ('mediaSession' in navigator) {
                            navigator.mediaSession.playbackState = value ? 'playing' : 'paused';
                        }
                    });

                    this.$watch('activeSongId', () => {
                        this.lyricOffset = 0;
                        this.lyricTimeline = null;
                        this.updateMediaSessionMetadata();
                    });

                    this.$watch('fullscreenPlayerOpen', (value) => {
                        if (value) {
                            if (this.isPlaying) {
                                this.$nextTick(() => this.scrollLoop());
                            }
                        }
                        if (this.activeTab === 'video') {
                            this.$nextTick(() => {
                                const embedVid = this.$refs.musicVideo;
                                const fsVid = this.$refs.fullscreenVideo;
                                if (embedVid && fsVid) {
                                    if (value) {
                                        fsVid.src = embedVid.src || this.getActiveSong().video_url;
                                        fsVid.currentTime = embedVid.currentTime;
                                        if (this.isPlaying) {
                                            embedVid.pause();
                                            fsVid.play().catch(() => {});
                                        }
                                    } else {
                                        embedVid.src = fsVid.src || this.getActiveSong().video_url;
                                        embedVid.currentTime = fsVid.currentTime;
                                        if (this.isPlaying) {
                                            fsVid.pause();
                                            embedVid.play().catch(() => {});
                                        }
                                    }
                                }
                            });
                        }
                    });

                    // Trigger initial scroll loop if already playing and open
                    if (this.isPlaying && this.fullscreenPlayerOpen) {
                        this.$nextTick(() => this.scrollLoop());
                    }

                    // Setup background audio controls (Media Session API)
                    this.setupMediaSession();
                    this.updateMediaSessionMetadata();
                },

                async checkOfflineCache() {
                    try {
                        const cache = await caches.open('polkesban-radio-audio-v1');
                        const cachedKeys = await cache.keys();
                        const cachedUrls = cachedKeys.map(k => k.url);
                        
                        this.cachedSongIds = [];
                        this.songs.forEach(song => {
                            const absUrl = new URL(song.audio_url, window.location.origin).href;
                            if (cachedUrls.includes(absUrl) || cachedUrls.includes(song.audio_url)) {
                                this.cachedSongIds.push(song.id);
                            }
                        });
                        this.podcastEpisodes.forEach(ep => {
                            const absUrl = new URL(ep.audio_url, window.location.origin).href;
                            if (cachedUrls.includes(absUrl) || cachedUrls.includes(ep.audio_url)) {
                                this.cachedSongIds.push(ep.id);
                            }
                        });
                        this.videos.forEach(video => {
                            const urlStr = video.video_url || '';
                            if (urlStr) {
                                const absUrl = new URL(urlStr, window.location.origin).href;
                                if (cachedUrls.includes(absUrl) || cachedUrls.includes(urlStr)) {
                                    this.cachedSongIds.push(video.id);
                                }
                            }
                        });
                    } catch(e) {
                        console.warn('Cache Storage not accessible:', e);
                    }
                },

                async downloadOffline(song) {
                    if (this.downloadingSongIds.includes(song.id) || this.cachedSongIds.includes(song.id)) return;
                    this.downloadingSongIds.push(song.id);
                    
                    const fileUrl = this.activeTab === 'video' ? song.video_url : song.audio_url;
                    try {
                        const cache = await caches.open('polkesban-radio-audio-v1');
                        const response = await fetch(fileUrl);
                        if (response.ok) {
                            await cache.put(fileUrl, response);
                            this.cachedSongIds.push(song.id);
                        } else {
                            alert('Gagal mengunduh: Respons server tidak valid.');
                        }
                    } catch (e) {
                        console.error('Download offline failed:', e);
                        alert('Gagal mengunduh berkas offline. Periksa koneksi internet Anda.');
                    } finally {
                        this.downloadingSongIds = this.downloadingSongIds.filter(id => id !== song.id);
                    }
                },

                async removeOffline(song) {
                    const fileUrl = this.activeTab === 'video' ? song.video_url : song.audio_url;
                    try {
                        const cache = await caches.open('polkesban-radio-audio-v1');
                        await cache.delete(fileUrl);
                        this.cachedSongIds = this.cachedSongIds.filter(id => id !== song.id);
                    } catch (e) {
                        console.error('Delete offline failed:', e);
                    }
                },
                
                 async loadSongs(force = false) {
                    if (this.isLoading) return;
                    
                    const cachedSongs = localStorage.getItem('suno_player_playlist');
                    if (cachedSongs) {
                        try {
                            const parsed = JSON.parse(cachedSongs);
                            if (parsed && parsed.length > 0) {
                                this.songs = parsed;
                            }
                        } catch (e) {}
                    }

                    const cachedPodcasts = localStorage.getItem('suno_player_podcasts');
                    if (cachedPodcasts) {
                        try {
                            const parsed = JSON.parse(cachedPodcasts);
                            if (parsed && parsed.length > 0) {
                                this.podcastEpisodes = parsed;
                            }
                        } catch (e) {}
                    }

                    const cachedVideos = localStorage.getItem('suno_player_videos');
                    if (cachedVideos) {
                        try {
                            const parsed = JSON.parse(cachedVideos);
                            if (parsed && parsed.length > 0) {
                                this.videos = parsed;
                            }
                        } catch (e) {}
                    }

                    this.isLoading = true;
                    try {
                        const [songsRes, podcastsRes, videosRes] = await Promise.all([
                            fetch('/api/suno-playlist'),
                            fetch('/api/podcast-playlist'),
                            fetch('/api/video-playlist')
                        ]);

                        if (songsRes.ok) {
                            const data = await songsRes.json();
                            if (data && data.length > 0) {
                                this.songs = data;
                                localStorage.setItem('suno_player_playlist', JSON.stringify(data));
                            }
                        }

                        if (podcastsRes.ok) {
                            const data = await podcastsRes.json();
                            if (data && data.length > 0) {
                                this.podcastEpisodes = data;
                                localStorage.setItem('suno_player_podcasts', JSON.stringify(data));
                            }
                        }

                        if (videosRes.ok) {
                            const data = await videosRes.json();
                            if (data && data.length > 0) {
                                this.videos = data;
                                localStorage.setItem('suno_player_videos', JSON.stringify(data));
                            }
                        }
                    } catch (err) {
                        console.error('Failed to load dynamic playlists:', err);
                    } finally {
                        this.isLoading = false;
                        // Select default song if none is set
                        if (!this.activeSongId) {
                            const list = this.currentPlaylist;
                            if (list && list.length > 0) {
                                this.activeSongId = list[0].id;
                            }
                        }
                    }
                },

                getActiveSong() {
                    const list = this.currentPlaylist || [];
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
                        published_at: '',
                        views_count: 0,
                        likes_count: 0
                    };
                },

                getNextSong() {
                    const id = this.nextSongIdToPlay;
                    if (!id) return null;
                    return this.currentPlaylist.find(s => s.id === id) || null;
                },

                switchTab(tab) {
                    if (this.activeTab === tab) return;
                    
                    const oldMedia = this.currentMediaElement;
                    if (oldMedia) {
                        oldMedia.pause();
                        oldMedia.removeAttribute('src');
                        oldMedia.load();
                    }
                    this.isPlaying = false;
                    
                    this.activeTab = tab;
                    localStorage.setItem('suno_player_active_tab', tab);
                    
                    const list = this.currentPlaylist;
                    if (list && list.length > 0) {
                        this.activeSongId = list[0].id;
                        localStorage.setItem('suno_player_song_id', list[0].id);
                        
                        this.$nextTick(() => {
                            const newMedia = this.currentMediaElement;
                            if (newMedia) {
                                const song = this.getActiveSong();
                                const srcUrl = this.activeTab === 'video' ? song.video_url : song.audio_url;
                                newMedia.src = srcUrl;
                                newMedia.load();
                            }
                        });
                    } else {
                        this.activeSongId = '';
                    }
                    this.updateMediaSessionMetadata();
                },

                setupMediaSession() {
                    if ('mediaSession' in navigator) {
                        navigator.mediaSession.setActionHandler('play', () => {
                            const audio = this.$refs.musicAudio;
                            if (audio) {
                                audio.play().then(() => { this.isPlaying = true; }).catch(() => {});
                            }
                        });
                        navigator.mediaSession.setActionHandler('pause', () => {
                            const audio = this.$refs.musicAudio;
                            if (audio) {
                                audio.pause();
                                this.isPlaying = false;
                            }
                        });
                        navigator.mediaSession.setActionHandler('previoustrack', () => {
                            this.playPrev();
                        });
                        navigator.mediaSession.setActionHandler('nexttrack', () => {
                            this.playNext();
                        });
                    }
                },

                updateMediaSessionMetadata() {
                    if ('mediaSession' in navigator) {
                        const song = this.getActiveSong();
                        if (song) {
                            navigator.mediaSession.metadata = new MediaMetadata({
                                title: song.title,
                                artist: 'Poltekkes Bandung Radio',
                                album: song.desc || 'Poltekkes Bandung',
                                artwork: [
                                    { src: song.image_url || '', sizes: '512x512', type: 'image/jpeg' }
                                ]
                            });
                        }
                    }
                },
                
                prefetchNextSong() {
                    // Disable prefetching for Podcast tab because podcast files are huge (30MB+) 
                    // and preloading them blocks network bandwidth causing lag.
                    if (this.activeTab !== 'radio') return;

                    if (this.songs.length === 0) return;
                    const currentIndex = this.songs.findIndex(s => s.id === this.activeSongId);
                    if (currentIndex === -1) return;
                    
                    let nextIndex = 0;
                    if (this.playMode === 'shuffle') {
                        nextIndex = Math.floor(Math.random() * this.songs.length);
                    } else if (this.playMode === 'repeat_one') {
                        nextIndex = currentIndex;
                    } else {
                        nextIndex = (currentIndex + 1) % this.songs.length;
                    }
                    
                    const nextSong = this.songs[nextIndex];
                    if (nextSong) {
                        this.nextSongIdToPlay = nextSong.id;
                        if (nextSong.audio_url) {
                            if (this.prefetchTimeoutId) {
                                clearTimeout(this.prefetchTimeoutId);
                            }
                            this.prefetchTimeoutId = setTimeout(() => {
                                if (document.hidden) return; // Skip background fetch if screen is off/tab is inactive to prevent TCP hanging
                                console.log('Preloading next song in background after delay: ' + nextSong.title);
                                caches.open('polkesban-radio-audio-v1').then(cache => {
                                    fetch(nextSong.audio_url, { priority: 'low' }).then(response => {
                                        if (response.ok) {
                                            cache.put(nextSong.audio_url, response.clone());
                                            if (!this.cachedSongIds.includes(nextSong.id)) {
                                                this.cachedSongIds.push(nextSong.id);
                                            }
                                            console.log('Preload and cache successful for: ' + nextSong.title);
                                        }
                                    }).catch(err => console.warn('Preload failed:', err));
                                });
                            }, 8000);
                        }
                    }
                },

                 playSong(songId) {
                    if (this.prefetchTimeoutId) {
                        clearTimeout(this.prefetchTimeoutId);
                        this.prefetchTimeoutId = null;
                    }
                    if (this.autoCacheTimeoutId) {
                        clearTimeout(this.autoCacheTimeoutId);
                        this.autoCacheTimeoutId = null;
                    }
                    this.nextSongIdToPlay = null;
                    this.activeSongId = songId;
                    const song = this.currentPlaylist.find(s => s.id === songId);
                    if (!song) return;

                    // Pause the other media to avoid dual playback
                    const otherMedia = this.activeTab === 'video' ? this.$refs.musicAudio : (this.fullscreenPlayerOpen ? this.$refs.fullscreenVideo : this.$refs.musicVideo);
                    if (otherMedia && typeof otherMedia.pause === 'function') {
                        otherMedia.pause();
                    }

                    // Auto-cache in background after 4 seconds of active playback
                    const fileUrl = this.activeTab === 'video' ? song.video_url : song.audio_url;
                    if (navigator.onLine && fileUrl && !this.cachedSongIds.includes(songId)) {
                        this.autoCacheTimeoutId = setTimeout(() => {
                            if (document.hidden) return; // Skip background fetch if screen is off/tab is inactive to prevent TCP hanging
                            caches.open('polkesban-radio-audio-v1').then(cache => {
                                fetch(fileUrl).then(response => {
                                    if (response.ok) {
                                        cache.put(fileUrl, response);
                                        if (!this.cachedSongIds.includes(songId)) {
                                            this.cachedSongIds.push(songId);
                                        }
                                    }
                                }).catch(() => {});
                            });
                        }, 4000);
                    }

                    this.$nextTick(() => {
                        const media = this.currentMediaElement;
                        if (media) {
                            media.src = fileUrl;
                            media.load();
                            media.play().then(() => {
                                this.isPlaying = true;
                                localStorage.setItem('suno_player_playing', 'true');
                                localStorage.setItem('suno_player_song_id', songId);
                                this.prefetchNextSong();
                            }).catch(err => {
                                if (err.name !== 'AbortError') {
                                    console.error('Playback failed:', err);
                                }
                            });
                        }
                        setTimeout(() => {
                            const activeEl = document.getElementById('playlist-song-' + songId);
                            if (activeEl) {
                                activeEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                        }, 100);
                    });
                },
                
                togglePlay() {
                    const media = this.currentMediaElement;
                    if (!media) return;

                    // Pause the other media to avoid dual playback
                    const otherMedia = this.activeTab === 'video' ? this.$refs.musicAudio : (this.fullscreenPlayerOpen ? this.$refs.fullscreenVideo : this.$refs.musicVideo);
                    if (otherMedia && typeof otherMedia.pause === 'function') {
                        otherMedia.pause();
                    }

                    if (this.isPlaying) {
                        media.pause();
                        this.isPlaying = false;
                        localStorage.setItem('suno_player_playing', 'false');
                    } else {
                        const currentSong = this.getActiveSong();
                        if (currentSong) {
                            const fileUrl = this.activeTab === 'video' ? currentSong.video_url : currentSong.audio_url;
                            if (!media.src || !media.src.includes(fileUrl)) {
                                media.src = fileUrl;
                                media.load();
                            }
                        }
                        media.play().then(() => {
                            this.isPlaying = true;
                            localStorage.setItem('suno_player_playing', 'true');
                            localStorage.setItem('suno_player_song_id', this.activeSongId);
                            this.prefetchNextSong();
                        }).catch(err => {
                            if (err.name === 'AbortError') return;
                            const currentSong = this.getActiveSong();
                            if (currentSong) {
                                const fileUrl = this.activeTab === 'video' ? currentSong.video_url : currentSong.audio_url;
                                media.src = fileUrl;
                                media.load();
                                media.play().then(() => {
                                    this.isPlaying = true;
                                    localStorage.setItem('suno_player_playing', 'true');
                                    localStorage.setItem('suno_player_song_id', this.activeSongId);
                                    this.prefetchNextSong();
                                }).catch(e => {
                                    if (e.name !== 'AbortError') {
                                        console.error('Playback failed:', e);
                                    }
                                });
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
                    const playlist = this.currentPlaylist;
                    if (playlist.length === 0) return;
                    
                    let nextSong = null;
                    if (this.nextSongIdToPlay) {
                        nextSong = playlist.find(s => s.id === this.nextSongIdToPlay);
                    }
                    
                    if (!nextSong) {
                        let nextIndex = 0;
                        if (this.playMode === 'shuffle') {
                            nextIndex = Math.floor(Math.random() * playlist.length);
                        } else if (this.playMode === 'repeat_one') {
                            const currentIndex = playlist.findIndex(s => s.id === this.activeSongId);
                            nextIndex = currentIndex;
                        } else {
                            const currentIndex = playlist.findIndex(s => s.id === this.activeSongId);
                            nextIndex = (currentIndex + 1) % playlist.length;
                        }
                        nextSong = playlist[nextIndex];
                    }
                    
                    this.nextSongIdToPlay = null; // Reset
                    if (nextSong) {
                        this.playSong(nextSong.id);
                    }
                },
                
                playPrev() {
                    const playlist = this.currentPlaylist;
                    if (playlist.length === 0) return;
                    this.nextSongIdToPlay = null; // Reset preloaded queue on manual navigation
                    let prevIndex = 0;
                    if (this.playMode === 'shuffle') {
                        prevIndex = Math.floor(Math.random() * playlist.length);
                    } else if (this.playMode === 'repeat_one') {
                        const currentIndex = playlist.findIndex(s => s.id === this.activeSongId);
                        prevIndex = currentIndex;
                    } else {
                        const currentIndex = playlist.findIndex(s => s.id === this.activeSongId);
                        prevIndex = currentIndex - 1;
                        if (prevIndex < 0) prevIndex = playlist.length - 1;
                    }
                    const prevSong = playlist[prevIndex];
                    if (prevSong) {
                        this.playSong(prevSong.id);
                    }
                },
                
                shuffleSongs() {
                    const list = this.currentPlaylist;
                    if (list.length === 0) return;
                    const shuffled = [...list].sort(() => Math.random() - 0.5);
                    if (this.activeTab === 'radio') {
                        this.songs = shuffled;
                    } else if (this.activeTab === 'video') {
                        this.videos = shuffled;
                    } else {
                        this.podcastEpisodes = shuffled;
                    }
                    this.playSong(shuffled[0].id);
                },
                
                filteredSongs() {
                    let list = this.currentPlaylist;
                    if (this.musicSearchQuery.trim()) {
                        const query = this.musicSearchQuery.toLowerCase();
                        list = list.filter(song => 
                            song.title.toLowerCase().includes(query) || 
                            (song.desc && song.desc.toLowerCase().includes(query)) ||
                            (song.description && song.description.toLowerCase().includes(query))
                        );
                    }
                    
                    const activeSong = list.find(song => song.id === this.activeSongId);
                    let remainder = list.filter(song => song.id !== this.activeSongId);
                    
                    // Sort cached songs to the top of the remainder
                    remainder.sort((a, b) => {
                        const aCached = this.cachedSongIds.includes(a.id);
                        const bCached = this.cachedSongIds.includes(b.id);
                        if (aCached && !bCached) return -1;
                        if (!aCached && bCached) return 1;
                        return 0;
                    });
                    
                    if (activeSong) {
                        return [activeSong, ...remainder];
                    }
                    return remainder;
                },
                
                updateProgress() {
                    const media = this.currentMediaElement;
                    if (media) {
                        this.currentTime = media.currentTime;
                        this.duration = media.duration || 0;
                        this.playbackPercent = this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
                        const mins = Math.floor(this.currentTime / 60);
                        const secs = Math.floor(this.currentTime % 60);
                        this.currentTimeLabel = mins + ':' + (secs < 10 ? '0' : '') + secs;
                    }
                },

                getLyrics() {
                    const song = this.getActiveSong();
                    if (!song) return [];
                    if (song.lyrics) {
                        let rawLines = song.lyrics.split('\n')
                            .map(line => line.trim())
                            .filter(line => line.length > 0);

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
                                    rawLines = rawLines.slice(repeatIdx);
                                    break;
                                }
                            }
                        }
                        return rawLines;
                    }
                    return [
                        "[Musik]",
                        "Bernyanyilah mengikuti irama...",
                        song.title,
                        song.desc,
                        "Poltekkes Kemenkes Bandung"
                    ];
                },

                buildLyricTimeline(lines) {
                    let estimatedDurations = [];
                    let totalEstimated = 0;
                    
                    lines.forEach((line) => {
                        let dur = 4.0;
                        const isBracket = line.startsWith('[') && line.endsWith(']');
                        if (isBracket) {
                            const content = line.toLowerCase();
                            if (content.includes('intro') || content.includes('instrumental') || content.includes('solo') || content.includes('musik') || content.includes('music') || content.includes('start') || content.includes('interlude') || content.includes('break')) {
                                dur = 12.0;
                            } else if (content.includes('outro') || content.includes('end') || content.includes('finish')) {
                                dur = 15.0;
                            } else {
                                dur = 0.0;
                            }
                        } else {
                            const wordCount = line.split(/\s+/).filter(w => w.length > 0).length;
                            dur = 2.0 + (wordCount * 0.45);
                            if (dur > 9.0) dur = 9.0;
                        }
                        estimatedDurations.push(dur);
                        totalEstimated += dur;
                    });
                    
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

                toggleMute() {
                    const media = this.currentMediaElement;
                    if (media) {
                        media.muted = !media.muted;
                        this.muted = media.muted;
                    }
                }
            };
        };
    </script>

    <div id="embed-player"
         class="w-full h-[calc(100vh-2rem)] sm:h-[600px] sm:max-w-[430px] overflow-hidden rounded-none sm:rounded-[2.5rem] border border-gray-850/60 dark:border-gray-800/60 bg-gray-950/50 backdrop-blur-xl shadow-2xl flex flex-col relative z-10">
        
        <!-- Native Audio -->
        <audio x-ref="musicAudio" 
               x-on:ended="playNext()"
               x-on:timeupdate="updateProgress"
               x-on:loadstart="isAudioLoading = true"
               x-on:waiting="isAudioLoading = true"
               x-on:playing="isAudioLoading = false"
               x-on:canplay="isAudioLoading = false"
               x-on:error="isAudioLoading = false"
               x-on:abort="isAudioLoading = false"
               class="hidden"></audio>

        <!-- YouTube-style loading bar at the top of the player card -->
        <div x-show="isAudioLoading" 
             class="absolute top-0 left-0 right-0 h-1 bg-brand-500/20 z-[99] overflow-hidden" 
             x-cloak>
            <div class="h-full bg-brand-400 w-1/3 rounded animate-infinite-loading"></div>
        </div>

        <!-- Header Panel -->
        <div class="bg-white/5 border-b border-white/5 px-5 py-4 text-white shrink-0 backdrop-blur-md">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-white/10 flex items-center justify-center text-xl shadow-inner">
                        📻
                    </div>
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wider">Poltekkes Bandung Radio</h2>
                        <p class="text-[11px] text-white/80">Lagu tentang Poltekkes Bandung (<span x-text="songs.length"></span> lagu)</p>
                    </div>
                </div>
                
                <!-- Animated Soundwave -->
                <div x-show="isPlaying" class="flex items-end gap-0.5 h-4 text-brand-300">
                    <span class="wave-bar"></span>
                    <span class="wave-bar"></span>
                    <span class="wave-bar"></span>
                    <span class="wave-bar"></span>
                </div>
            </div>
        </div>

        <!-- Tab Switcher -->
        <div class="flex border-b border-white/5 bg-white/5 shrink-0 text-[11px] font-black uppercase tracking-wider">
            <button @click="switchTab('radio')" 
                    :class="activeTab === 'radio' ? 'border-b-2 border-brand-500 text-brand-400 bg-white/5' : 'text-slate-400 hover:text-white'"
                    class="flex-1 py-3.5 text-center transition-all flex items-center justify-center gap-2 focus:outline-none">
                <span>📻</span> Radio Lagu
            </button>
            <button @click="switchTab('podcast')" 
                    :class="activeTab === 'podcast' ? 'border-b-2 border-brand-500 text-brand-400 bg-white/5' : 'text-slate-400 hover:text-white'"
                    class="flex-1 py-3.5 text-center transition-all flex items-center justify-center gap-2 focus:outline-none">
                <span>🎙️</span> Podcast
            </button>
            <button @click="switchTab('video')" 
                    :class="activeTab === 'video' ? 'border-b-2 border-brand-500 text-brand-400 bg-white/5' : 'text-slate-400 hover:text-white'"
                    class="flex-1 py-3.5 text-center transition-all flex items-center justify-center gap-2 focus:outline-none">
                <span>📹</span> Video
            </button>
        </div>

        <!-- Embedded Video Player Area -->
        <div x-show="activeTab === 'video'" class="w-full aspect-video bg-black border-b border-white/5 relative z-10 shrink-0" x-cloak>
            <video x-ref="musicVideo"
                   x-on:ended="playNext()"
                   x-on:timeupdate="updateProgress"
                   x-on:loadstart="isAudioLoading = true"
                   x-on:waiting="isAudioLoading = true"
                   x-on:playing="isAudioLoading = false"
                   x-on:canplay="isAudioLoading = false"
                   x-on:error="isAudioLoading = false"
                   x-on:abort="isAudioLoading = false"
                   class="w-full h-full object-contain"
                   playsinline
                   controls></video>
        </div>

        <!-- Custom Premium Player Dashboard -->
        <div class="bg-black/20 p-5 border-b border-white/5 flex flex-col gap-4 shrink-0 backdrop-blur-md">
            <div class="flex items-center gap-4">
                {{-- Cover Art --}}
                <div class="h-16 w-16 rounded-2xl overflow-hidden bg-slate-800 shrink-0 shadow-inner flex items-center justify-center border border-white/10 cursor-pointer hover:scale-105 active:scale-95 transition-transform"
                     @click="fullscreenPlayerOpen = true"
                     title="Buka Tampilan Karaoke">
                    <template x-if="getActiveSong().image_url">
                        <img :src="getActiveSong().image_url" alt="Cover" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!getActiveSong().image_url">
                        <span class="text-3xl text-slate-400">🎵</span>
                    </template>
                </div>
                {{-- Title & Artist --}}
                <div class="min-w-0 flex-1 cursor-pointer"
                     @click="fullscreenPlayerOpen = true"
                     title="Buka Tampilan Karaoke">
                    <h3 class="text-sm font-black text-white truncate" x-text="getActiveSong().title"></h3>
                    <p class="text-xs text-slate-400 truncate mt-1" x-text="getActiveSong().desc || getActiveSong().description || 'Poltekkes Bandung Podcast'"></p>
                    <div x-show="getNextSong()" class="mt-1.5 flex items-center gap-1.5 text-[9px] text-white/40">
                        <span class="bg-white/10 px-1 py-0.5 rounded text-[8px] uppercase tracking-wider font-bold shrink-0 text-white/60">Berikutnya</span>
                        <span class="truncate font-semibold text-white/50" x-text="getNextSong() ? getNextSong().title : ''"></span>
                    </div>
                </div>
                {{-- Like button --}}
                <template x-if="activeTab === 'radio'">
                    <a :href="'https://suno.com/song/' + activeSongId" 
                       target="_blank" 
                       class="h-9 w-9 rounded-full bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-lg shadow-rose-500/35 hover:from-rose-400 hover:to-pink-400 transition-all flex items-center justify-center shrink-0 group/heart scale-100 hover:scale-105 active:scale-95" 
                       title="Sukai & Subscribe di Suno.com">
                        <svg class="h-4.5 w-4.5 transition-transform group-hover/heart:scale-110" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </a>
                </template>
            </div>

            {{-- Playback Controls --}}
            <div class="flex items-center justify-center gap-5">
                {{-- Playback Mode Toggle --}}
                <button type="button" 
                        @click="togglePlayMode()"
                        class="p-2 transition-colors text-slate-400 hover:text-white"
                        :title="playMode === 'loop' ? 'Ulangi Semua (Loop)' : (playMode === 'shuffle' ? 'Putar Acak (Shuffle)' : 'Ulangi Satu Lagu (Repeat One)')">
                    <template x-if="playMode === 'loop'">
                        <svg class="h-5 w-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="17 1 21 5 17 9"></polyline>
                            <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                            <polyline points="7 23 3 19 7 15"></polyline>
                            <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                        </svg>
                    </template>
                    <template x-if="playMode === 'shuffle'">
                        <svg class="h-5 w-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 3 21 3 21 8"></polyline>
                            <line x1="4" y1="20" x2="21" y2="3"></line>
                            <polyline points="21 16 21 21 16 21"></polyline>
                            <line x1="15" y1="15" x2="21" y2="21"></line>
                            <line x1="4" y1="4" x2="9" y2="9"></line>
                        </svg>
                    </template>
                    <template x-if="playMode === 'repeat_one'">
                        <div class="relative flex items-center justify-center">
                            <svg class="h-5 w-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="17 1 21 5 17 9"></polyline>
                                <path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
                                <polyline points="7 23 3 19 7 15"></polyline>
                                <path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
                            </svg>
                            <span class="absolute text-[8px] font-black text-brand-400" style="font-size: 8px; transform: scale(0.9);">1</span>
                        </div>
                    </template>
                </button>

                {{-- Previous Song --}}
                <button type="button" 
                        @click="playPrev()"
                        class="p-2 transition-colors text-slate-300 hover:text-white"
                        title="Lagu Sebelumnya">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="19 20 9 12 19 4 19 20"></polygon>
                        <line x1="5" y1="19" x2="5" y2="5"></line>
                    </svg>
                </button>

                {{-- Play / Pause Button --}}
                <button type="button" 
                        @click="togglePlay()"
                        class="h-12 w-12 rounded-full bg-brand-500 hover:bg-brand-400 text-white flex items-center justify-center shadow-lg transition-transform hover:scale-105 active:scale-[0.95]"
                        title="Mainkan / Jeda">
                    <template x-if="isAudioLoading">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <template x-if="!isPlaying && !isAudioLoading">
                        <svg class="h-5 w-5 fill-current ml-0.5" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </template>
                    <template x-if="isPlaying && !isAudioLoading">
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                        </svg>
                    </template>
                </button>

                {{-- Next Song --}}
                <button type="button" 
                        @click="playNext()"
                        class="p-2 transition-colors text-slate-300 hover:text-white"
                        title="Lagu Selanjutnya">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="5 4 15 12 5 20 5 4"></polygon>
                        <line x1="19" y1="5" x2="19" y2="19"></line>
                    </svg>
                </button>

                {{-- Info Badges --}}
                <div class="text-[9px] font-bold text-slate-500 uppercase tracking-widest border border-slate-700 px-2 py-1 rounded-md">
                    Poltekkes Bandung MP3
                </div>
            </div>

            {{-- Progress Bar & Time Labels --}}
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-mono text-slate-400 w-8 text-right" x-text="currentTimeLabel">0:00</span>
                <div class="flex-1 relative group cursor-pointer h-1.5 bg-slate-800 rounded-full overflow-hidden"
                     @click="
                        const rect = $el.getBoundingClientRect();
                        const pos = ($event.clientX - rect.left) / rect.width;
                        const media = currentMediaElement;
                        if (media && duration > 0) {
                            media.currentTime = pos * duration;
                        }
                     ">
                    <div class="absolute left-0 top-0 h-full bg-brand-400 rounded-full" :style="{ width: playbackPercent + '%' }"></div>
                </div>
                <span class="text-[10px] font-mono text-slate-400 w-8" x-text="getActiveSong().duration || '0:00'">0:00</span>
            </div>
        </div>

        <!-- Search, Acak, and Karaoke Actions -->
        <div class="p-4 border-b border-white/5 bg-black/10 flex items-center gap-2 shrink-0 backdrop-blur-md">
            <div class="relative flex-1">
                <svg class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" 
                       x-model="musicSearchQuery"
                       placeholder="Cari lagu..." 
                       class="w-full rounded-2xl border border-white/10 bg-white/5 py-2 pl-10 pr-4 text-xs focus:border-brand-500 focus:outline-none text-white transition-shadow focus:ring-2 focus:ring-brand-500/20">
            </div>
            
            <button type="button" 
                    @click="shuffleSongs()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-2xl border border-white/10 bg-white/5 px-3 text-xs font-bold text-white/80 transition hover:bg-white/10 hover:scale-[1.02] active:scale-[0.98] shadow-sm">
                <span>Acak</span>
            </button>

            <button type="button" 
                    @click="fullscreenPlayerOpen = true"
                    class="inline-flex h-9 items-center gap-1.5 rounded-2xl border border-brand-500/30 bg-brand-500/10 px-3 text-xs font-bold text-brand-400 transition hover:bg-brand-500/20 hover:scale-[1.02] active:scale-[0.98] shadow-sm"
                    title="Buka Layar Penuh Karaoke">
                <span>🎤 Karaoke</span>
            </button>
        </div>

        <!-- Song Playlist Section -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-transparent">
            <template x-for="song in filteredSongs()" :key="song.id">
                <button type="button" 
                        @click="playSong(song.id)"
                        :id="'playlist-song-' + song.id"
                        :class="activeSongId === song.id 
                            ? 'border-brand-500/40 bg-brand-500/10 text-brand-400 shadow-sm' 
                            : 'border-white/5 bg-white/5 text-white/80 hover:border-white/10 hover:bg-white/10 shadow-sm'"
                        class="w-full rounded-2xl border p-3 flex items-center justify-between gap-3 text-left transition-all hover:scale-[1.01] active:scale-[0.99] group backdrop-blur-sm">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                             :class="activeSongId === song.id ? 'bg-brand-500 text-white' : 'bg-white/5 text-white/40 group-hover:text-brand-400'">
                            <template x-if="activeSongId === song.id && isPlaying">
                                <svg class="h-4.5 w-4.5 fill-current" viewBox="0 0 24 24">
                                    <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                                </svg>
                            </template>
                            <template x-if="activeSongId !== song.id || !isPlaying">
                                <svg class="h-4.5 w-4.5 fill-current ml-0.5" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold truncate text-white" x-text="song.title"></h4>
                            <p class="text-[10px] text-white/40 truncate mt-0.5" x-text="song.desc"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-[10px] font-mono text-white/40" x-text="song.duration">0:00</span>
                        
                        <!-- Offline Cache Button -->
                        <span role="button" 
                                @click.stop="cachedSongIds.includes(song.id) ? removeOffline(song) : downloadOffline(song)"
                                :title="cachedSongIds.includes(song.id) ? 'Hapus dari Offline' : 'Unduh untuk Offline'"
                                class="p-1.5 rounded-xl hover:bg-white/10 transition-all flex items-center justify-center relative z-20 cursor-pointer">
                            <!-- Is Downloading Spinner -->
                            <template x-if="downloadingSongIds.includes(song.id)">
                                <svg class="animate-spin h-3.5 w-3.5 text-brand-400" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <!-- Is Cached Green Check -->
                            <template x-if="cachedSongIds.includes(song.id) && !downloadingSongIds.includes(song.id)">
                                <svg class="h-3.5 w-3.5 text-green-500 fill-current" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </template>
                            <!-- Not Cached Download Icon -->
                            <template x-if="!cachedSongIds.includes(song.id) && !downloadingSongIds.includes(song.id)">
                                <svg class="h-3.5 w-3.5 text-white/40 hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </template>
                        </span>
                    </div>
                </button>
            </template>
        </div>

        <!-- Footer Playlist Link Removed -->
    </div>

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
             
             <!-- YouTube-style loading bar at the top of the fullscreen overlay -->
             <div x-show="isAudioLoading" 
                  class="absolute top-0 left-0 right-0 h-1 bg-brand-500/20 z-[999] overflow-hidden" 
                  x-cloak>
                 <div class="h-full bg-brand-400 w-1/3 rounded animate-infinite-loading"></div>
             </div>

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
                  <div class="flex items-center gap-2 sm:gap-3">
                      <button type="button" 
                              @click="fullscreenPlayerOpen = false"
                              title="Kembali"
                              class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all backdrop-blur active:scale-95 shadow">
                          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                          </svg>
                          <span class="hidden sm:inline">Kembali</span>
                      </button>

                      {{-- Sync Playlist Button in Fullscreen Mode --}}
                      <button type="button" 
                              @click="loadSongs(true)" 
                              title="Sinkronisasi Playlist"
                              class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all backdrop-blur active:scale-95 shadow">
                          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="isLoading ? 'animate-spin' : ''">
                              <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                          </svg>
                          <span class="hidden sm:inline">Sinkron</span>
                      </button>

                      {{-- Fullscreen Tab Switcher Button --}}
                        <button type="button" 
                                @click="switchTab(activeTab === 'radio' ? 'podcast' : (activeTab === 'podcast' ? 'video' : 'radio'))" 
                                class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-brand-500/20 hover:bg-brand-500/35 border border-brand-500/30 text-white text-xs font-bold transition-all backdrop-blur active:scale-95 shadow"
                                :title="activeTab === 'radio' ? 'Pindah ke Podcast' : (activeTab === 'podcast' ? 'Pindah ke Video' : 'Pindah ke Radio')">
                            <span x-show="activeTab === 'radio'">🎙️</span>
                            <span x-show="activeTab === 'podcast'">📹</span>
                            <span x-show="activeTab === 'video'">📻</span>
                            <span class="hidden sm:inline" x-text="activeTab === 'radio' ? 'Putar Podcast' : (activeTab === 'podcast' ? 'Putar Video' : 'Putar Radio')"></span>
                        </button>

                      {{-- Show Playlist Button --}}
                       <button type="button" 
                               @click="showEmbedPlaylist = !showEmbedPlaylist" 
                               :title="activeTab === 'radio' ? 'Daftar Lagu' : (activeTab === 'video' ? 'Daftar Video' : 'Daftar Podcast')"
                               class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all backdrop-blur active:scale-95 shadow">
                           <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                           </svg>
                           <span class="hidden sm:inline" x-text="activeTab === 'radio' ? 'Daftar Lagu' : (activeTab === 'video' ? 'Daftar Video' : 'Daftar Podcast')">Daftar Lagu</span>
                       </button>

                      {{-- Native Fullscreen Toggle Button --}}
                      <button type="button" 
                              @click="toggleNativeFullscreen()" 
                              :title="isNativeFullscreen ? 'Keluar Layar Penuh' : 'Layar Penuh'"
                              class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all backdrop-blur active:scale-95 shadow">
                          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path x-show="!isNativeFullscreen" stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75v4.5m0-4.5h-4.5m4.5 0L15 9m5.25 11.25v-4.5m0 4.5h-4.5m4.5 0L15 15" />
                              <path x-show="isNativeFullscreen" stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9V4.5M15 9h4.5M15 9l5.25-5.25M15 15v4.5M15 15h4.5M15 15l5.25 5.25" />
                          </svg>
                          <span class="hidden sm:inline" x-text="isNativeFullscreen ? 'Jendela' : 'Layar Penuh'">Layar Penuh</span>
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

                  {{-- Large Cover Art / Fullscreen Video --}}
                  <template x-if="activeTab !== 'video'">
                      <div class="relative w-28 h-28 sm:w-36 sm:h-36 lg:w-48 lg:h-48 rounded-2xl lg:rounded-[1.75rem] overflow-hidden shadow-2xl border-2 border-white/10 group mb-3 lg:mb-5 shrink-0 cursor-zoom-in"
                           @click="if(getActiveSong().image_url) showZoomedCover = true"
                           title="Klik untuk memperbesar gambar/infografis">
                          <template x-if="getActiveSong().image_url">
                              <img :src="getActiveSong().image_url" alt="Cover Art" 
                                   class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                          </template>
                          <template x-if="!getActiveSong().image_url">
                              <div class="w-full h-full bg-gradient-to-br from-brand-900 to-slate-900 flex items-center justify-center">
                                  <span class="text-7xl">🎵</span>
                              </div>
                          </template>
                          <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-1">
                              <span class="text-lg">🔍</span>
                              <span class="text-white text-[9px] font-black uppercase tracking-wider">Perbesar</span>
                          </div>
                      </div>
                  </template>

                  <template x-if="activeTab === 'video'">
                      <div class="relative w-full max-w-xl aspect-video rounded-3xl overflow-hidden shadow-2xl border-2 border-white/10 mb-3 lg:mb-5 shrink-0 bg-black" x-cloak>
                          <video x-ref="fullscreenVideo"
                                 x-on:ended="playNext()"
                                 x-on:timeupdate="updateProgress"
                                 x-on:loadstart="isAudioLoading = true"
                                 x-on:waiting="isAudioLoading = true"
                                 x-on:playing="isAudioLoading = false"
                                 x-on:canplay="isAudioLoading = false"
                                 x-on:error="isAudioLoading = false"
                                 x-on:abort="isAudioLoading = false"
                                 class="w-full h-full object-contain"
                                 playsinline
                                 controls></video>
                      </div>
                  </template>

                  {{-- Song Meta --}}
                  <div class="w-full max-w-md px-4 mb-3 lg:mb-5 shrink-0 flex items-center justify-between gap-4">
                       <div class="text-left truncate flex-1">
                           <h2 class="text-base sm:text-xl lg:text-2xl font-black text-white tracking-tight drop-shadow-md truncate" x-text="getActiveSong().title"></h2>
                           <p class="hidden sm:inline-block text-[10px] sm:text-xs text-brand-400 font-semibold mt-1 px-2.5 py-0.5 rounded-full bg-brand-500/10 border border-brand-500/20 truncate" x-text="getActiveSong().desc || getActiveSong().description || 'Poltekkes Bandung Podcast'"></p>
                           <div x-show="getNextSong()" class="mt-1.5 flex items-center gap-1.5 text-[10px] text-white/50">
                               <span class="bg-white/10 px-1.5 py-0.5 rounded text-[8px] sm:text-[9px] uppercase tracking-wider font-bold shrink-0 text-white/70">Berikutnya</span>
                               <span class="truncate font-semibold text-white/70" x-text="getNextSong() ? getNextSong().title : ''"></span>
                               <span class="shrink-0 font-mono opacity-80" x-text="getNextSong() ? '• ' + getNextSong().duration : ''"></span>
                           </div>
                       </div>
                      <template x-if="activeTab === 'radio'">
                          <a :href="'https://suno.com/song/' + activeSongId" 
                             target="_blank" 
                             class="h-9 w-9 sm:h-11 sm:w-11 rounded-full bg-gradient-to-r from-rose-500 to-pink-500 text-white shadow-lg shadow-rose-500/35 hover:from-rose-400 hover:to-pink-400 transition-all flex items-center justify-center shrink-0 group/heart scale-100 hover:scale-105 active:scale-95" 
                             title="Sukai & Subscribe di Suno.com">
                              <svg class="h-4.5 w-4.5 sm:h-5.5 sm:w-5.5 transition-transform group-hover/heart:scale-110" fill="currentColor" viewBox="0 0 24 24">
                                  <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                              </svg>
                          </a>
                      </template>
                 </div>

                 {{-- Progress Bar --}}
                 <div class="w-full max-w-md px-4 mb-6 shrink-0">
                     <div class="relative group cursor-pointer" 
                          @click="
                              const rect = $el.getBoundingClientRect();
                              const percent = ($event.clientX - rect.left) / rect.width;
                              const media = currentMediaElement;
                              if (media && media.duration) {
                                  media.currentTime = percent * media.duration;
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

             {{-- Right Section: Karaoke Lyrics, Podcast Notes, or Video Notes --}}
             <div class="flex-1 flex flex-col p-2 lg:p-10 relative z-20 max-h-[42vh] lg:max-h-full overflow-hidden mt-2 lg:mt-0 animate-[fadeIn_0.5s_ease-out]">
                 <template x-if="activeTab === 'radio'">
                     <h3 class="text-white/40 text-[10px] sm:text-xs font-bold uppercase tracking-[0.2em] mb-3 text-center lg:text-left shrink-0">Lirik Lagu / Sing Along</h3>
                 </template>
                 <template x-if="activeTab === 'podcast'">
                     <h3 class="text-white/40 text-[10px] sm:text-xs font-bold uppercase tracking-[0.2em] mb-3 text-center lg:text-left shrink-0">Catatan / Deskripsi Podcast</h3>
                 </template>
                 <template x-if="activeTab === 'video'">
                     <h3 class="text-white/40 text-[10px] sm:text-xs font-bold uppercase tracking-[0.2em] mb-3 text-center lg:text-left shrink-0">Catatan / Deskripsi Video</h3>
                 </template>
                 
                 {{-- Full Content Container --}}
                 <div class="lyrics-container flex-1 overflow-y-auto pr-2 space-y-1 text-center lg:text-left scrollbar-thin scrollbar-thumb-white/10">
                     <template x-if="activeTab === 'radio'">
                         <div>
                             <template x-for="(line, index) in getLyrics()">
                                 <div :class="{
                                          'text-white/80 font-medium text-xs sm:text-sm lg:text-[13px] leading-relaxed': !(line.startsWith('[') && line.endsWith(']')),
                                          'text-brand-400 font-bold uppercase tracking-wider text-[10px] sm:text-xs mt-3 mb-1': line.startsWith('[') && line.endsWith(']')
                                      }"
                                      class="lyric-line py-0.5 px-2 rounded-lg break-words"
                                      x-text="line"></div>
                             </template>
                         </div>
                     </template>
                     <template x-if="activeTab === 'podcast'">
                         <div class="text-white/80 font-medium text-xs sm:text-sm lg:text-[13px] leading-relaxed py-4 px-2 break-words" 
                              x-text="getActiveSong().description || getActiveSong().desc || 'Tidak ada catatan deskripsi untuk episode ini.'"></div>
                     </template>
                     <template x-if="activeTab === 'video'">
                         <div class="text-white/80 font-medium text-xs sm:text-sm lg:text-[13px] leading-relaxed py-4 px-2 break-words" 
                              x-text="getActiveSong().description || getActiveSong().desc || 'Tidak ada catatan deskripsi untuk video ini.'"></div>
                     </template>
                 </div>
             </div>
         </div>

         {{-- Collapsible Playlist Sidebar/Drawer --}}
         <div x-show="showEmbedPlaylist"
              x-cloak
              x-transition:enter="transition ease-out duration-300 transform"
              x-transition:enter-start="translate-x-full"
              x-transition:enter-end="translate-x-0"
              x-transition:leave="transition ease-in duration-200 transform"
              x-transition:leave-start="translate-x-0"
              x-transition:leave-end="translate-x-full"
              class="fixed top-0 right-0 bottom-0 w-full max-w-xs sm:w-80 bg-slate-950/95 border-l border-white/10 backdrop-blur-xl z-[130] flex flex-col p-5 shadow-2xl">
              
              <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-4">
                  <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                      <span x-show="activeTab === 'radio'">📻 Daftar Lagu (<span x-text="songs.length"></span>)</span>
                      <span x-show="activeTab === 'podcast'">🎙️ Daftar Podcast (<span x-text="podcastEpisodes.length"></span>)</span>
                      <span x-show="activeTab === 'video'">📹 Daftar Video (<span x-text="videos.length"></span>)</span>
                  </h3>
                  <button type="button" 
                          @click="showEmbedPlaylist = false"
                          class="p-1 rounded-full bg-white/5 hover:bg-white/10 text-white/70 hover:text-white transition-colors">
                      <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                  </button>
              </div>

              {{-- Search Input --}}
              <div class="relative mb-4">
                  <svg class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-white/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  <input type="text" 
                         x-model="musicSearchQuery"
                         placeholder="Cari lagu..." 
                         class="w-full rounded-xl border border-white/10 bg-white/5 py-2 pl-9 pr-4 text-xs text-white placeholder-white/30 focus:border-brand-500 focus:outline-none transition-shadow focus:ring-2 focus:ring-brand-500/20">
              </div>

              {{-- Songs List --}}
              <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                  <template x-for="song in filteredSongs()" :key="song.id">
                      <button type="button" 
                              @click="playSong(song.id)"
                              :class="activeSongId === song.id 
                                  ? 'border-brand-500/45 bg-brand-500/10 text-brand-400' 
                                  : 'border-white/5 bg-white/5 text-white/80 hover:border-white/10 hover:bg-white/10'"
                              class="w-full rounded-xl border p-2.5 flex items-center justify-between gap-3 text-left transition-all active:scale-[0.98] group">
                          <div class="flex items-center gap-2.5 min-w-0">
                              <div class="h-7 w-7 rounded-lg flex items-center justify-center shrink-0"
                                   :class="activeSongId === song.id ? 'bg-brand-500 text-white' : 'bg-white/5 text-white/40 group-hover:text-brand-400'">
                                  <template x-if="activeSongId === song.id && isPlaying">
                                      <svg class="h-3.5 w-3.5 fill-current" viewBox="0 0 24 24">
                                          <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                                      </svg>
                                  </template>
                                  <template x-if="activeSongId !== song.id || !isPlaying">
                                      <svg class="h-3.5 w-3.5 fill-current ml-0.5" viewBox="0 0 24 24">
                                          <path d="M8 5v14l11-7z"/>
                                      </svg>
                                  </template>
                              </div>
                              <div class="min-w-0">
                                  <h4 class="text-xs font-bold truncate" x-text="song.title"></h4>
                                  <p class="text-[10px] text-white/40 truncate mt-0.5" x-text="song.desc"></p>
                              </div>
                          </div>
                          <div class="flex items-center gap-1.5 shrink-0">
                              <span class="text-[9px] font-mono text-white/40" x-text="song.duration">0:00</span>
                              
                              <!-- Offline Cache Button -->
                              <span role="button" 
                                      @click.stop="cachedSongIds.includes(song.id) ? removeOffline(song) : downloadOffline(song)"
                                      :title="cachedSongIds.includes(song.id) ? 'Hapus dari Offline' : 'Unduh untuk Offline'"
                                      class="p-1 rounded-lg hover:bg-white/10 transition-all flex items-center justify-center relative z-20 cursor-pointer">
                                  <!-- Is Downloading Spinner -->
                                  <template x-if="downloadingSongIds.includes(song.id)">
                                      <svg class="animate-spin h-3.5 w-3.5 text-brand-400" viewBox="0 0 24 24" fill="none">
                                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                      </svg>
                                  </template>
                                  <!-- Is Cached Green Check -->
                                  <template x-if="cachedSongIds.includes(song.id) && !downloadingSongIds.includes(song.id)">
                                      <svg class="h-3.5 w-3.5 text-green-500 fill-current" viewBox="0 0 24 24">
                                          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                      </svg>
                                  </template>
                                  <!-- Not Cached Download Icon -->
                                  <template x-if="!cachedSongIds.includes(song.id) && !downloadingSongIds.includes(song.id)">
                                      <svg class="h-3.5 w-3.5 text-white/40 hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                      </svg>
                                  </template>
                              </span>
                          </div>
                      </button>
                  </template>
              </div>
         </div>

             <style>
                  @keyframes infinite-loading {
                      0% { transform: translateX(-100%); }
                      100% { transform: translateX(300%); }
                  }
                  .animate-infinite-loading {
                      animation: infinite-loading 1.2s linear infinite;
                  }

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

             <!-- Zoomed Image Modal Overlay -->
             <div x-show="showZoomedCover" 
                  x-cloak 
                  x-transition:enter="transition ease-out duration-300"
                  x-transition:enter-start="opacity-0 scale-95"
                  x-transition:enter-end="opacity-100 scale-100"
                  x-transition:leave="transition ease-in duration-200"
                  x-transition:leave-start="opacity-100 scale-100"
                  x-transition:leave-end="opacity-0 scale-95"
                  class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 backdrop-blur-md p-4 cursor-zoom-out"
                  @click="showZoomedCover = false">
                  
                  <div class="relative max-w-full max-h-[90vh] rounded-2xl overflow-hidden shadow-2xl border border-white/10" @click.stop>
                      <!-- Close Button -->
                      <button class="absolute top-4 right-4 h-10 w-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center text-lg transition-colors border border-white/10 focus:outline-none"
                              @click="showZoomedCover = false">
                          ✕
                      </button>
                      <img :src="getActiveSong().image_url" alt="Infografis Penuh" class="max-w-full max-h-[85vh] object-contain">
                  </div>
             </div>
        </div>

    <script>
        (function(){
            var container = document.getElementById('simpelBgParticles');
            if (!container) return;
            var particleCount = 25;
            for (var i = 0; i < particleCount; i++) {
                var particle = document.createElement('div');
                particle.className = 'simpel-particle';
                var size = Math.random() * 80 + 20;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                particle.style.left = (Math.random() * 100) + '%';
                var duration = Math.random() * 15 + 10;
                var delay = Math.random() * -25;
                particle.style.animationDuration = duration + 's';
                particle.style.animationDelay = delay + 's';
                container.appendChild(particle);
            }
        })();
    </script>
</body>
</html>
