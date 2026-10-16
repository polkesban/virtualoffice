<style>
    @keyframes polbi-float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }
    @keyframes polbi-ring {
        0% { transform: scale(0.95); opacity: 0.8; }
        100% { transform: scale(1.4); opacity: 0; }
    }
    .animate-polbi-float {
        animation: polbi-float 3s ease-in-out infinite;
    }
    .animate-polbi-ring {
        animation: polbi-ring 2s cubic-bezier(0.26, 0.86, 0.44, 0.985) infinite;
    }
</style>
<div
    x-data="{
        open: false,
        loading: false,
        videoMuted: localStorage.getItem('polbi_video_muted') === 'false' ? false : true,
        messages: [],
        input: '',
        sessionId: '',
        language: '{{ config('ai.concierge.default_language', 'id') }}',
        dictionaries: {
            id: {
                widgetAria: 'Asisten Virtual Poltekkes Bandung',
                title: 'POLBI — Asisten Poltekkes Bandung',
                statusTyping: 'Sedang mengetik…',
                statusReady: 'Online · siap membantu',
                closeChat: 'Tutup chat',
                welcomeTitle: 'Halo! Saya POLBI, asisten virtual Poltekkes Bandung.',
                welcomeBody: 'Tanyakan seputar program studi, pendaftaran, atau info kampus.',
                quickPrompts: [
                    'Program studi apa saja yang tersedia?',
                    'Bagaimana cara mendaftar?',
                    'Berapa biaya kuliah?',
                ],
                noResponse: 'Maaf, tidak ada respons.',
                connectionFailed: 'Koneksi gagal. Silakan coba lagi.',
                sourceLabel: 'Sumber:',
                typingAria: 'Mengetik',
                inputLabel: 'Ketik pertanyaan kamu',
                inputPlaceholder: 'Ketik pertanyaan…',
                sendLabel: 'Kirim',
                disclaimer: 'POLBI dapat membuat kesalahan. Verifikasi info penting ke kampus.',
                openAssistant: 'Buka asisten virtual Poltekkes Bandung',
                closeAssistant: 'Tutup asisten virtual',
            },
            en: {
                widgetAria: 'Poltekkes Bandung Virtual Assistant',
                title: 'POLBI — Poltekkes Bandung Assistant',
                statusTyping: 'Typing…',
                statusReady: 'Online · ready to help',
                closeChat: 'Close chat',
                welcomeTitle: 'Hello! I am POLBI, Poltekkes Bandung\'s virtual assistant.',
                welcomeBody: 'Ask me about study programs, admissions, or campus information.',
                quickPrompts: [
                    'What study programs are available?',
                    'How do I apply?',
                    'What is the tuition fee?',
                ],
                noResponse: 'Sorry, there was no response.',
                connectionFailed: 'Connection failed. Please try again.',
                sourceLabel: 'Sources:',
                typingAria: 'Typing',
                inputLabel: 'Type your question',
                inputPlaceholder: 'Type your question…',
                sendLabel: 'Send',
                disclaimer: 'POLBI can make mistakes. Please verify important information with the campus.',
                openAssistant: 'Open Poltekkes Bandung virtual assistant',
                closeAssistant: 'Close virtual assistant',
            },
        },

        t(key) {
            const language = this.dictionaries[this.language] ? this.language : 'id';
            return this.dictionaries[language][key] ?? this.dictionaries.id[key] ?? key;
        },

        setLanguage(language) {
            if (!['id', 'en'].includes(language) || this.language === language) return;

            this.language = language;

            try {
                window.localStorage.setItem('polkesban_concierge_language', language);
            } catch (_) {
                // ignore storage failures
            }
        },

        init() {
            const storageKey = 'polkesban_concierge_session_id';
            const languageKey = 'polkesban_concierge_language';

            try {
                this.sessionId = window.localStorage.getItem(storageKey) || '';
                const storedLanguage = window.localStorage.getItem(languageKey) || '';

                if (storedLanguage === 'id' || storedLanguage === 'en') {
                    this.language = storedLanguage;
                }

                if (!this.sessionId) {
                    this.sessionId = `${Math.random().toString(36).slice(2)}${Date.now().toString(36)}`;
                    window.localStorage.setItem(storageKey, this.sessionId);
                }
            } catch (_) {
                this.sessionId = `${Math.random().toString(36).slice(2)}${Date.now().toString(36)}`;
            }
        },

        async send() {
            const text = this.input.trim();
            if (!text || this.loading) return;

            this.messages.push({ role: 'user', text });
            this.input = '';
            this.loading = true;
            this.$nextTick(() => this.scrollToBottom());

            try {
                const res = await fetch('{{ route('concierge.chat') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        message: text,
                        session_id: this.sessionId,
                        language: this.language,
                        page_path: window.location.pathname,
                        page_title: document.title,
                    }),
                });

                const contentType = res.headers.get('content-type') || '';
                const data = contentType.includes('application/json') ? await res.json() : {};

                if (!res.ok) {
                    if (res.status === 429) {
                        this.messages.push({
                            role:    'assistant',
                            text:    this.language === 'en'
                                ? 'Too many requests. Please wait a moment.'
                                : 'Terlalu banyak permintaan. Silakan tunggu sebentar.',
                            sources: [],
                            error:   true,
                        });
                        this.loading = false;
                        this.$nextTick(() => this.scrollToBottom());
                        return;
                    }
                    throw new Error(`HTTP ${res.status}`);
                }

                this.messages.push({
                    role:    'assistant',
                    text:    data.answer ?? this.t('noResponse'),
                    sources: data.sources ?? [],
                    error:   data.error   ?? false,
                });
            } catch (e) {
                this.messages.push({
                    role:  'assistant',
                    text:  this.t('connectionFailed'),
                    error: true,
                });
            } finally {
                this.loading = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        scrollToBottom() {
            const el = this.$refs.msgContainer;
            if (el) el.scrollTop = el.scrollHeight;
        },

        handleKey(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.send();
            }
        }
    }"
    class="fixed bottom-6 right-5 z-50 flex flex-col items-end gap-3"
    :aria-label="t('widgetAria')"
>
    {{-- ── Chat Panel ──────────────────────────────────────────────────── --}}
    <div
        id="polly-panel"
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        class="w-[22rem] sm:w-96 rounded-3xl shadow-2xl shadow-black/20
               bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800
               flex flex-col overflow-hidden"
        style="max-height: 80vh"
        role="dialog"
        aria-modal="true"
        aria-labelledby="concierge-title"
        @keydown.escape.window="open = false"
    >
        {{-- Header --}}
        <div class="flex items-center gap-3 px-5 py-4
                    bg-brand-600 dark:bg-brand-800">
            <div class="relative w-9 h-9 rounded-full bg-white flex items-center justify-center
                        text-xl flex-shrink-0 overflow-hidden" aria-hidden="true">
                <img src="{{ asset('images/polbi.png') }}" class="w-full h-full object-cover" alt="POLBI Avatar">
                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full
                             bg-green-400 border-2 border-brand-600 dark:border-brand-800"></span>
            </div>
            <div class="flex-1 min-w-0">
                <p id="concierge-title" class="text-sm font-bold text-white leading-tight" x-text="t('title')"></p>
                <p class="text-[11px] text-brand-200" aria-live="polite"
                   x-text="loading ? t('statusTyping') : t('statusReady')"></p>
            </div>
            <div class="inline-flex rounded-full bg-white/10 p-1 text-[11px] font-semibold text-white/80">
                <button @click="setLanguage('id')"
                        type="button"
                        class="rounded-full px-2 py-1 transition-colors"
                        :class="language === 'id' ? 'bg-white text-brand-700' : 'hover:bg-white/10'"
                        aria-label="Bahasa Indonesia">
                    ID
                </button>
                <button @click="setLanguage('en')"
                        type="button"
                        class="rounded-full px-2 py-1 transition-colors"
                        :class="language === 'en' ? 'bg-white text-brand-700' : 'hover:bg-white/10'"
                        aria-label="English">
                    EN
                </button>
            </div>
            <button @click="open = false"
                    onclick="(function(b){var p=b.closest('[x-data]');if(p&&p.__x){p.__x.$data.open=false;}else{p.style.display='none';}})(this)"
                    class="rounded-full p-1.5 text-white/70 hover:text-white hover:bg-white/15
                           transition-colors" :aria-label="t('closeChat')">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div x-ref="msgContainer"
             class="flex-1 overflow-y-auto p-4 space-y-3 scroll-smooth"
             style="min-height:200px; max-height:380px">

            {{-- Welcome message --}}
            <template x-if="messages.length === 0">
                <div class="text-center py-6 text-gray-400 dark:text-gray-500">
                    <img src="{{ asset('images/polbi.png') }}" class="w-16 h-16 mx-auto mb-3 rounded-full object-cover shadow-md" alt="POLBI Avatar">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300" x-text="t('welcomeTitle')"></p>
                    <p class="text-xs mt-1 text-gray-400 dark:text-gray-500" x-text="t('welcomeBody')"></p>
                    {{-- Quick prompts --}}
                    <div class="mt-4 flex flex-col gap-1.5">
                        <template x-for="q in t('quickPrompts')" :key="q">
                            <button @click="input = q; send()"
                                    class="text-left text-xs rounded-xl bg-brand-50 dark:bg-brand-950
                                           px-3 py-2 text-brand-700 dark:text-brand-300
                                           hover:bg-brand-100 dark:hover:bg-brand-900 transition-colors"
                                    x-text="q"></button>
                        </template>
                    </div>
                </div>
            </template>

            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="[
                        'max-w-[85%] px-4 py-2.5 rounded-2xl text-sm leading-relaxed',
                        msg.role === 'user'
                            ? 'bg-brand-600 text-white rounded-br-sm'
                            : msg.error
                                ? 'bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 rounded-bl-sm'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-bl-sm'
                    ]">
                        {{-- Render text with \n as line breaks --}}
                        <p x-html="msg.text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')"></p>
                        {{-- Source pills --}}
                        <template x-if="msg.sources && msg.sources.length > 0">
                            <div class="mt-2 flex flex-wrap gap-1">
                                <span class="text-[10px] opacity-60" x-text="t('sourceLabel')"></span>
                                <template x-for="src in msg.sources" :key="src">
                                    <span class="text-[10px] rounded-full bg-black/10 dark:bg-white/10
                                                 px-2 py-0.5 opacity-70" x-text="src"></span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="loading" class="flex justify-start">
                <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl rounded-bl-sm px-4 py-3">
                    <span class="inline-flex gap-1" :aria-label="t('typingAria')">
                        <span class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style="animation-delay:300ms"></span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="p-3 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
            <form @submit.prevent="send()" class="flex items-end gap-2">
                <label for="concierge-input" class="sr-only" x-text="t('inputLabel')"></label>
                <textarea
                    id="concierge-input"
                    x-model="input"
                    @keydown="handleKey($event)"
                    :disabled="loading"
                    rows="1"
                    :placeholder="t('inputPlaceholder')"
                    maxlength="1000"
                    class="flex-1 resize-none rounded-2xl bg-gray-50 dark:bg-gray-800
                           border border-gray-200 dark:border-gray-700
                           px-4 py-2.5 text-sm text-gray-900 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-brand-300
                           disabled:opacity-50 transition-colors"
                    style="min-height:40px; max-height:100px; overflow-y:auto"
                ></textarea>
                <button type="submit"
                        :disabled="!input.trim() || loading"
                        class="flex-shrink-0 w-10 h-10 rounded-2xl
                               bg-brand-600 hover:bg-brand-500 disabled:bg-gray-200 dark:disabled:bg-gray-700
                               flex items-center justify-center transition-colors"
                        :aria-label="t('sendLabel')">
                    <svg class="w-5 h-5 text-white disabled:text-gray-400" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" aria-hidden="true" :class="{ 'opacity-40': !input.trim() || loading }">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            <p class="text-center text-[10px] text-gray-300 dark:text-gray-600 mt-2" x-text="t('disclaimer')"></p>
        </div>
    </div>

    {{-- ── FAB Trigger Button ──────────────────────────────────────────── --}}
    <div class="relative group w-14 h-14 animate-polbi-float">
        {{-- Pulsing Ring Effect --}}
        <div x-show="!open" class="absolute inset-0 rounded-full border border-brand-500/60 animate-polbi-ring pointer-events-none"></div>

        <button
            @click="open = !open"
            onclick="(function(btn){
                var root = btn.closest('[x-data]');
                var panel = document.getElementById('polly-panel');
                if (root && root.__x) { root.__x.$data.open = !root.__x.$data.open; return; }
                if (panel) panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
            })(this)"
            class="relative w-14 h-14 rounded-full shadow-xl shadow-brand-900/30
                   bg-brand-600 hover:bg-brand-500
                   flex items-center justify-center
                   transition-all duration-200 group-hover:scale-110 active:scale-95"
            :aria-expanded="open.toString()"
            :aria-label="open ? t('closeAssistant') : t('openAssistant')"
        >
            <video x-ref="conciergeVideo"
                   x-show="!open"
                   x-transition:enter="transition duration-200"
                   x-transition:enter-start="opacity-0 scale-75"
                   x-transition:enter-end="opacity-100 scale-100"
                   src="{{ asset('videos/polbi.mp4') }}"
                   class="w-full h-full rounded-full object-cover"
                   autoplay loop muted :muted="videoMuted" playsinline
                   @ended="$refs.conciergeVideo.currentTime = 0; $refs.conciergeVideo.play()">
            </video>
            <svg x-show="open"
                 x-transition:enter="transition duration-200"
                 x-transition:enter-start="opacity-0 rotate-45 scale-75"
                 x-transition:enter-end="opacity-100 rotate-0 scale-100"
                 class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{-- Unread ping (shown only when not open and no messages yet) --}}
            <span x-show="!open && messages.length === 0"
                  class="absolute -top-1 -right-1 w-4 h-4 rounded-full
                         bg-red-500 border-2 border-white flex items-center justify-center">
                <span class="text-[8px] font-black text-white leading-none">1</span>
            </span>
        </button>
        {{-- POLBI Text Badge & Mute Control Wrapper --}}
        <div x-show="!open"
             class="absolute -bottom-2.5 left-1/2 transform -translate-x-1/2
                    flex items-center gap-1 z-20 select-none
                    transition-all duration-200 group-hover:scale-110 group-hover:-translate-y-0.5">
            {{-- Mute Toggle Button --}}
            <button @click.stop="videoMuted = !videoMuted; localStorage.setItem('polbi_video_muted', videoMuted); $refs.conciergeVideo.muted = videoMuted"
                    type="button"
                    class="w-5 h-5 rounded-full pointer-events-auto
                           bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                           flex items-center justify-center shadow-md
                           hover:scale-110 active:scale-95 transition-all text-gray-500 dark:text-gray-400"
                    :aria-label="videoMuted ? 'Aktifkan suara' : 'Bisukan suara'"
            >
                <!-- Speaker Icon (Unmuted) -->
                <svg x-show="!videoMuted" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M12 18.75V5.25L7.75 9.5H4.5v5h3.25L12 18.75z"/>
                </svg>
                <!-- Mute Icon (Muted) -->
                <svg x-show="videoMuted" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5.586 15H4.5v-5h1.086L10 5.75v12.5L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                </svg>
            </button>
            {{-- Name Badge --}}
            <span class="text-[9px] font-extrabold text-white bg-brand-600 dark:bg-brand-700
                         px-2 py-0.5 rounded-full shadow border border-white dark:border-gray-800
                         tracking-wider uppercase">POLBI</span>
        </div>
    </div>
</div>
