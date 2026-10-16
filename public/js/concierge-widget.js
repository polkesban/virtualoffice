/**
 * Polly — Embeddable AI Chat Assistant Widget
 * Self-contained, dependency-free, vanilla JS widget.
 * 
 * Embed instructions:
 * <script src="https://polkesban.ac.id/js/concierge-widget.js" defer></script>
 */
(function () {
    // Prevent double initialization
    if (window.PollyConciergeInitialized) return;
    window.PollyConciergeInitialized = true;

    // 1. Detect base URL from script tag source
    let baseUrl = 'https://polkesban.ac.id';
    const currentScript = document.currentScript;
    if (currentScript && currentScript.src) {
        try {
            baseUrl = new URL(currentScript.src).origin;
        } catch (e) {
            console.error('[Polly Widget] Failed to parse script origin:', e);
        }
    }
    const apiUrl = `${baseUrl}/api/concierge/chat`;

    // 2. Injected CSS styling (scoped under .polly-widget)
    const styleContent = `
        /* Polly Widget Variables & Styles */
        :root {
            --polly-brand-50: #f0fdf4;
            --polly-brand-100: #dcfce7;
            --polly-brand-200: #bbf7d0;
            --polly-brand-300: #86efac;
            --polly-brand-400: #4ade80;
            --polly-brand-500: #22c55e;
            --polly-brand-600: #16a34a;
            --polly-brand-700: #15803d;
            --polly-brand-800: #166534;
            --polly-brand-900: #14532d;
            --polly-brand-950: #052e16;
        }

        @keyframes polbi-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        @keyframes polbi-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            100% { transform: scale(1.4); opacity: 0; }
        }
        .polly-widget {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            position: fixed;
            bottom: 24px;
            right: 20px;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            box-sizing: border-box;
            animation: polbi-float 3s ease-in-out infinite;
        }
        .polly-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 1px solid rgba(22, 163, 74, 0.6);
            animation: polbi-ring 2s cubic-bezier(0.26, 0.86, 0.44, 0.985) infinite;
            pointer-events: none;
        }
        .polly-fab-video {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            transition: all 0.2s ease;
        }
        .polly-bottom-wrapper {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 4px;
            z-index: 9999999;
            user-select: none;
            transition: all 0.2s ease;
        }
        .polly-mute-btn {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            pointer-events: auto;
            color: #6b7280;
            transition: all 0.2s ease;
        }
        .polly-mute-btn:hover {
            transform: scale(1.1);
        }
        .polly-mute-btn svg {
            width: 12px !important;
            height: 12px !important;
            stroke-width: 2.5 !important;
        }
        .polly-name-badge {
            font-size: 9px;
            font-weight: 900;
            color: #ffffff;
            background-color: var(--polly-brand-600);
            padding: 2px 8px;
            border-radius: 9999px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border: 1px solid #ffffff;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .polly-widget * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* FAB Trigger Button */
        .polly-fab {
            position: relative;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--polly-brand-600);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px -5px rgba(22, 101, 52, 0.4);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            color: #ffffff;
        }

        .polly-fab:hover {
            background-color: var(--polly-brand-500);
            transform: scale(1.1);
        }

        .polly-fab:active {
            transform: scale(0.95);
        }

        .polly-fab svg {
            width: 28px;
            height: 28px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .polly-fab .polly-icon-close {
            position: absolute;
            opacity: 0;
            transform: rotate(45deg) scale(0.75);
            width: 24px;
            height: 24px;
            stroke-width: 2.5;
        }

        .polly-fab.polly-open .polly-icon-chat {
            opacity: 0;
            transform: rotate(-45deg) scale(0.75);
        }

        .polly-fab.polly-open .polly-icon-close {
            opacity: 1;
            transform: rotate(0) scale(1);
        }

        .polly-fab-ping {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: #ef4444;
            border: 2px solid #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 9px;
            font-weight: 900;
            line-height: 1;
        }

        /* Chat Panel */
        .polly-panel {
            display: none; /* Controlled by js class toggles */
            width: 380px;
            max-width: calc(100vw - 40px);
            height: 520px;
            max-height: 80vh;
            border-radius: 24px;
            background-color: #ffffff;
            border: 1px solid #f3f4f6;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            flex-direction: column;
            overflow: hidden;
            animation: pollySlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            transform-origin: bottom right;
        }

        .polly-panel.polly-show {
            display: flex;
        }

        @keyframes pollySlideUp {
            from {
                opacity: 0;
                transform: translateY(24px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Header */
        .polly-header {
            background-color: var(--polly-brand-600);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
        }

        .polly-avatar {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .polly-avatar-status {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #4ade80;
            border: 2px solid var(--polly-brand-600);
        }

        .polly-header-info {
            flex-grow: 1;
            min-width: 0;
        }

        .polly-header-title {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .polly-header-status {
            font-size: 11px;
            color: var(--polly-brand-200);
            margin-top: 2px;
        }

        .polly-lang-switch {
            display: inline-flex;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
            padding: 2px;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            border: none;
            margin-right: 4px;
        }

        .polly-lang-btn {
            background: transparent;
            border: none;
            color: inherit;
            padding: 4px 8px;
            border-radius: 9999px;
            cursor: pointer;
            font-weight: 600;
            font-size: 10px;
            line-height: 1;
            transition: background-color 0.2s, color 0.2s;
        }

        .polly-lang-btn.polly-active {
            background-color: #ffffff;
            color: var(--polly-brand-700);
        }

        .polly-close-btn {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            padding: 6px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s, color 0.2s;
        }

        .polly-close-btn:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        .polly-close-btn svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Message Container */
        .polly-messages {
            flex-grow: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background-color: #ffffff;
            scroll-behavior: smooth;
        }

        /* Welcome view styling */
        .polly-welcome {
            text-align: center;
            padding: 24px 12px;
            color: #9ca3af;
        }

        .polly-welcome-emoji {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .polly-welcome-title {
            font-size: 14px;
            font-weight: 500;
            color: #4b5563;
        }

        .polly-welcome-body {
            font-size: 12px;
            margin-top: 4px;
            color: #6b7280;
        }

        .polly-quick-prompts {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .polly-prompt-btn {
            text-align: left;
            font-size: 12px;
            border-radius: 12px;
            background-color: var(--polly-brand-50);
            border: none;
            padding: 8px 12px;
            color: var(--polly-brand-700);
            cursor: pointer;
            transition: background-color 0.15s ease;
            font-family: inherit;
        }

        .polly-prompt-btn:hover {
            background-color: var(--polly-brand-100);
        }

        /* Messages structure */
        .polly-msg-row {
            display: flex;
            width: 100%;
        }

        .polly-msg-row-user {
            justify-content: flex-end;
        }

        .polly-msg-row-assistant {
            justify-content: flex-start;
        }

        .polly-bubble {
            max-w: 85%;
            padding: 10px 16px;
            border-radius: 16px;
            font-size: 13.5px;
            line-height: 1.5;
            word-break: break-word;
        }

        .polly-bubble p {
            margin-bottom: 6px;
        }
        .polly-bubble p:last-child {
            margin-bottom: 0;
        }

        .polly-bubble-user {
            background-color: var(--polly-brand-600);
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }

        .polly-bubble-assistant {
            background-color: #f3f4f6;
            color: #1f2937;
            border-bottom-left-radius: 2px;
        }

        .polly-bubble-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-bottom-left-radius: 2px;
        }

        /* Source Pills */
        .polly-sources {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px;
        }

        .polly-sources-label {
            font-size: 10px;
            opacity: 0.6;
        }

        .polly-source-pill {
            font-size: 10px;
            border-radius: 9999px;
            background-color: rgba(0, 0, 0, 0.08);
            padding: 2px 8px;
            opacity: 0.8;
            font-weight: 500;
        }

        /* Typing indicator */
        .polly-typing {
            display: inline-flex;
            gap: 4px;
            align-items: center;
            height: 16px;
        }

        .polly-typing span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #9ca3af;
            animation: pollyBounce 1.4s infinite ease-in-out both;
        }

        .polly-typing span:nth-child(1) { animation-delay: -0.32s; }
        .polly-typing span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes pollyBounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        /* Footer / Input form */
        .polly-footer {
            padding: 12px;
            border-top: 1px solid #f3f4f6;
            background-color: #ffffff;
        }

        .polly-form {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            width: 100%;
        }

        .polly-textarea {
            flex-grow: 1;
            resize: none;
            border-radius: 16px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px 16px;
            font-size: 13.5px;
            color: #111827;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: inherit;
            max-height: 100px;
            min-height: 40px;
            overflow-y: auto;
            line-height: 1.4;
        }

        .polly-textarea:focus {
            border-color: var(--polly-brand-400);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }

        .polly-textarea::placeholder {
            color: #9ca3af;
        }

        .polly-textarea:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .polly-send-btn {
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            border-radius: 16px;
            background-color: var(--polly-brand-600);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.15s, opacity 0.15s;
            color: #ffffff;
        }

        .polly-send-btn:hover {
            background-color: var(--polly-brand-500);
        }

        .polly-send-btn:disabled {
            background-color: #e5e7eb;
            cursor: not-allowed;
            color: #9ca3af;
        }

        .polly-disclaimer {
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            margin-top: 8px;
            line-height: 1.3;
        }

        /* ── Dark Mode styling (supports media query OR html/body .dark class) ── */
        @media (prefers-color-scheme: dark) {
            .polly-panel {
                background-color: #111827;
                border-color: #1f2937;
            }
            .polly-messages {
                background-color: #111827;
            }
            .polly-bubble-assistant {
                background-color: #1f2937;
                color: #e5e7eb;
            }
            .polly-welcome-title {
                color: #e5e7eb;
            }
            .polly-welcome-body {
                color: #9ca3af;
            }
            .polly-prompt-btn {
                background-color: #052e16;
                color: var(--polly-brand-300);
            }
            .polly-prompt-btn:hover {
                background-color: #14532d;
            }
            .polly-bubble-error {
                background-color: #450a0a;
                color: #fca5a5;
            }
            .polly-source-pill {
                background-color: rgba(255, 255, 255, 0.1);
            }
            .polly-footer {
                background-color: #111827;
                border-top-color: #1f2937;
            }
            .polly-textarea {
                background-color: #1f2937;
                border-color: #374151;
                color: #ffffff;
            }
            .polly-send-btn:disabled {
                background-color: #374151;
                color: #4b5563;
            }
        }

        /* Class-based dark mode override */
        .dark .polly-panel,
        html.dark .polly-panel,
        body.dark .polly-panel {
            background-color: #111827;
            border-color: #1f2937;
        }
        .dark .polly-messages,
        html.dark .polly-messages,
        body.dark .polly-messages {
            background-color: #111827;
        }
        .dark .polly-bubble-assistant,
        html.dark .polly-bubble-assistant,
        body.dark .polly-bubble-assistant {
            background-color: #1f2937;
            color: #e5e7eb;
        }
        .dark .polly-welcome-title,
        html.dark .polly-welcome-title,
        body.dark .polly-welcome-title {
            color: #e5e7eb;
        }
        .dark .polly-welcome-body,
        html.dark .polly-welcome-body,
        body.dark .polly-welcome-body {
            color: #9ca3af;
        }
        .dark .polly-prompt-btn,
        html.dark .polly-prompt-btn,
        body.dark .polly-prompt-btn {
            background-color: #052e16;
            color: var(--polly-brand-300);
        }
        .dark .polly-prompt-btn:hover,
        html.dark .polly-prompt-btn:hover,
        body.dark .polly-prompt-btn:hover {
            background-color: #14532d;
        }
        .dark .polly-bubble-error,
        html.dark .polly-bubble-error,
        body.dark .polly-bubble-error {
            background-color: #450a0a;
            color: #fca5a5;
        }
        .dark .polly-source-pill,
        html.dark .polly-source-pill,
        body.dark .polly-source-pill {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .dark .polly-footer,
        html.dark .polly-footer,
        body.dark .polly-footer {
            background-color: #111827;
            border-top-color: #1f2937;
        }
        .dark .polly-textarea,
        html.dark .polly-textarea,
        body.dark .polly-textarea {
            background-color: #1f2937;
            border-color: #374151;
            color: #ffffff;
        }
        .dark .polly-send-btn:disabled,
        html.dark .polly-send-btn:disabled,
        body.dark .polly-send-btn:disabled {
            background-color: #374151;
            color: #4b5563;
        }
    `;

    // 3. Multilingual Dictionaries
    const dictionaries = {
        id: {
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
            inputPlaceholder: 'Ketik pertanyaan…',
            disclaimer: 'POLBI dapat membuat kesalahan. Verifikasi info penting ke kampus.',
            openAssistant: 'Buka asisten virtual Poltekkes Bandung',
            closeAssistant: 'Tutup asisten virtual',
        },
        en: {
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
            inputPlaceholder: 'Type your question…',
            disclaimer: 'POLBI can make mistakes. Please verify important information with the campus.',
            openAssistant: 'Open Poltekkes Bandung virtual assistant',
            closeAssistant: 'Close virtual assistant',
        }
    };

    // State Variables
    let open = false;
    let loading = false;
    let messages = [];
    let language = 'id';
    let sessionId = '';

    // Initialize session and language from storage
    const sessionKey = 'polkesban_concierge_session_id';
    const langKey = 'polkesban_concierge_language';

    try {
        sessionId = window.localStorage.getItem(sessionKey) || '';
        const storedLang = window.localStorage.getItem(langKey) || '';
        if (storedLang === 'id' || storedLang === 'en') {
            language = storedLang;
        }
        if (!sessionId) {
            sessionId = Math.random().toString(36).substring(2) + Date.now().toString(36);
            window.localStorage.setItem(sessionKey, sessionId);
        }
    } catch (_) {
        sessionId = Math.random().toString(36).substring(2) + Date.now().toString(36);
    }

    // Translation helper
    function t(key) {
        const dict = dictionaries[language] || dictionaries.id;
        return dict[key] || dictionaries.id[key] || key;
    }

    // Helper to format/render markdown-like syntax
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderMarkdown(text) {
        let escaped = escapeHtml(text);
        // Replace **bold** with <strong>bold</strong>
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Replace newlines with <br>
        escaped = escaped.replace(/\n/g, '<br>');
        return escaped;
    }

    // DOM Elements holder
    let widgetEl, fabEl, panelEl, msgContainerEl, textareaEl, sendBtnEl, statusEl, pingEl;

    // Build DOM layout
    function initLayout() {
        // Inject Styles
        const styleSheet = document.createElement('style');
        styleSheet.type = 'text/css';
        styleSheet.innerText = styleContent;
        document.head.appendChild(styleSheet);

        // Create widget container
        widgetEl = document.createElement('div');
        widgetEl.className = 'polly-widget';
        widgetEl.setAttribute('aria-label', 'POLBI Assistant');

        // FAB HTML
        widgetEl.innerHTML = `
            <div id="polly-panel" class="polly-panel" role="dialog" aria-modal="true">
                <!-- Header -->
                <div class="polly-header">
                    <div class="polly-avatar" aria-hidden="true" style="overflow: hidden; background: #ffffff;">
                        <img src="${baseUrl}/images/polbi.png" style="width: 100%; height: 100%; object-fit: cover;" alt="POLBI Header Avatar">
                        <div class="polly-avatar-status"></div>
                    </div>
                    <div class="polly-header-info">
                        <div class="polly-header-title" id="polly-header-title"></div>
                        <div class="polly-header-status" id="polly-header-status"></div>
                    </div>
                    <div class="polly-lang-switch">
                        <button type="button" class="polly-lang-btn" id="polly-lang-id">ID</button>
                        <button type="button" class="polly-lang-btn" id="polly-lang-en">EN</button>
                    </div>
                    <button class="polly-close-btn" id="polly-close" aria-label="Close">
                        <svg viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Messages -->
                <div class="polly-messages" id="polly-msg-container"></div>

                <!-- Footer -->
                <div class="polly-footer">
                    <form class="polly-form" id="polly-chat-form">
                        <textarea class="polly-textarea" id="polly-input" rows="1" maxlength="1000" required></textarea>
                        <button type="submit" class="polly-send-btn" id="polly-send" disabled aria-label="Send">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transform: rotate(45deg); margin-left: -2px; margin-top: 2px;">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </form>
                    <div class="polly-disclaimer" id="polly-disclaimer"></div>
                </div>
            </div>

            <!-- FAB Trigger Button -->
            <button class="polly-fab" id="polly-fab" aria-expanded="false" aria-label="Open virtual assistant">
                <video class="polly-icon-chat polly-fab-video" id="polly-video" src="${baseUrl}/videos/polbi.mp4" autoplay loop muted playsinline></video>
                <svg class="polly-icon-close" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <div class="polly-fab-ping" id="polly-ping">1</div>
            </button>
            <div class="polly-ring" id="polly-ring"></div>
            <div class="polly-bottom-wrapper" id="polly-bottom-wrapper">
                <button type="button" class="polly-mute-btn" id="polly-mute" aria-label="Mute sound">
                    <svg id="polly-mute-icon-unmuted" class="polly-mute-icon" style="display: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M12 18.75V5.25L7.75 9.5H4.5v5h3.25L12 18.75z"/>
                    </svg>
                    <svg id="polly-mute-icon-muted" class="polly-mute-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5.586 15H4.5v-5h1.086L10 5.75v12.5L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                    </svg>
                </button>
                <span class="polly-name-badge">POLBI</span>
            </div>
        `;

        document.body.appendChild(widgetEl);

        // Bind Elements
        fabEl = document.getElementById('polly-fab');
        panelEl = document.getElementById('polly-panel');
        msgContainerEl = document.getElementById('polly-msg-container');
        textareaEl = document.getElementById('polly-input');
        sendBtnEl = document.getElementById('polly-send');
        statusEl = document.getElementById('polly-header-status');
        pingEl = document.getElementById('polly-ping');

        // Setup DOM event listeners
        fabEl.addEventListener('click', toggleChat);
        document.getElementById('polly-close').addEventListener('click', () => setOpen(false));
        document.getElementById('polly-lang-id').addEventListener('click', () => changeLanguage('id'));
        document.getElementById('polly-lang-en').addEventListener('click', () => changeLanguage('en'));
        document.getElementById('polly-chat-form').addEventListener('submit', handleFormSubmit);

        // Mute button logic
        const muteBtnEl = document.getElementById('polly-mute');
        const videoEl = document.getElementById('polly-video');
        const muteIconUnmuted = document.getElementById('polly-mute-icon-unmuted');
        const muteIconMuted = document.getElementById('polly-mute-icon-muted');

        if (muteBtnEl && videoEl) {
            muteBtnEl.addEventListener('click', function(e) {
                e.stopPropagation(); // prevent opening chat panel
                videoEl.muted = !videoEl.muted;
                if (videoEl.muted) {
                    muteIconMuted.style.display = 'block';
                    muteIconUnmuted.style.display = 'none';
                } else {
                    muteIconMuted.style.display = 'none';
                    muteIconUnmuted.style.display = 'block';
                }
            });
        }

        // Loop smoothing
        if (videoEl) {
            videoEl.addEventListener('ended', function() {
                videoEl.currentTime = 0;
                videoEl.play();
            });
        }

        // Auto growing textarea logic
        textareaEl.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            sendBtnEl.disabled = !this.value.trim() || loading;
        });

        textareaEl.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim() && !loading) {
                    handleFormSubmit(e);
                }
            }
        });

        // Close on escape
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && open) {
                setOpen(false);
            }
        });

        updateLabels();
        renderMessages();
    }

    function toggleChat() {
        setOpen(!open);
    }

    function setOpen(val) {
        open = val;
        if (open) {
            panelEl.classList.add('polly-show');
            fabEl.classList.add('polly-open');
            fabEl.setAttribute('aria-expanded', 'true');
            // Hide notification ping once opened
            pingEl.style.display = 'none';
            // Scroll to bottom
            setTimeout(() => {
                msgContainerEl.scrollTop = msgContainerEl.scrollHeight;
                textareaEl.focus();
            }, 100);
        } else {
            panelEl.classList.remove('polly-show');
            fabEl.classList.remove('polly-open');
            fabEl.setAttribute('aria-expanded', 'false');
        }
        updateLabels();
    }

    function changeLanguage(lang) {
        if (language === lang) return;
        language = lang;
        try {
            window.localStorage.setItem(langKey, language);
        } catch (_) {}
        updateLabels();
        renderMessages();
    }

    function updateLabels() {
        document.getElementById('polly-header-title').innerText = t('title');
        statusEl.innerText = loading ? t('statusTyping') : t('statusReady');
        textareaEl.placeholder = t('inputPlaceholder');
        document.getElementById('polly-disclaimer').innerText = t('disclaimer');
        fabEl.setAttribute('aria-label', open ? t('closeAssistant') : t('openAssistant'));

        // Language toggle active class styling
        const btnId = document.getElementById('polly-lang-id');
        const btnEn = document.getElementById('polly-lang-en');
        if (language === 'id') {
            btnId.classList.add('polly-active');
            btnEn.classList.remove('polly-active');
        } else {
            btnEn.classList.add('polly-active');
            btnId.classList.remove('polly-active');
        }
    }

    function renderMessages() {
        if (messages.length === 0) {
            // Welcome screen
            msgContainerEl.innerHTML = `
                <div class="polly-welcome">
                    <img src="${baseUrl}/images/polbi.png" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; margin-bottom: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);" alt="POLBI Avatar">
                    <div class="polly-welcome-title">${t('welcomeTitle')}</div>
                    <div class="polly-welcome-body">${t('welcomeBody')}</div>
                    <div class="polly-quick-prompts">
                        ${t('quickPrompts').map(prompt => `
                            <button type="button" class="polly-prompt-btn" data-prompt="${escapeHtml(prompt)}">${escapeHtml(prompt)}</button>
                        `).join('')}
                    </div>
                </div>
            `;

            // Bind click handlers to quick prompt buttons
            const promptButtons = msgContainerEl.querySelectorAll('.polly-prompt-btn');
            promptButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const text = this.getAttribute('data-prompt');
                    textareaEl.value = text;
                    textareaEl.dispatchEvent(new Event('input'));
                    sendPrompt(text);
                });
            });
            return;
        }

        let html = '';
        messages.forEach(msg => {
            const isUser = msg.role === 'user';
            const rowClass = isUser ? 'polly-msg-row-user' : 'polly-msg-row-assistant';
            const bubbleClass = isUser 
                ? 'polly-bubble-user' 
                : (msg.error ? 'polly-bubble-assistant polly-bubble-error' : 'polly-bubble-assistant');

            let sourceHtml = '';
            if (msg.sources && msg.sources.length > 0) {
                sourceHtml = `
                    <div class="polly-sources">
                        <span class="polly-sources-label">${t('sourceLabel')}</span>
                        ${msg.sources.map(src => `<span class="polly-source-pill">${escapeHtml(src)}</span>`).join('')}
                    </div>
                `;
            }

            html += `
                <div class="polly-msg-row ${rowClass}">
                    <div class="polly-bubble ${bubbleClass}">
                        <div>${renderMarkdown(msg.text)}</div>
                        ${sourceHtml}
                    </div>
                </div>
            `;
        });

        if (loading) {
            html += `
                <div class="polly-msg-row polly-msg-row-assistant" id="polly-typing-indicator">
                    <div class="polly-bubble polly-bubble-assistant">
                        <div class="polly-typing">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
        }

        msgContainerEl.innerHTML = html;
        msgContainerEl.scrollTop = msgContainerEl.scrollHeight;
    }

    async function sendPrompt(text) {
        if (!text || loading) return;

        messages.push({ role: 'user', text: text });
        loading = true;
        textareaEl.value = '';
        textareaEl.style.height = 'auto';
        textareaEl.disabled = true;
        sendBtnEl.disabled = true;

        updateLabels();
        renderMessages();

        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    message: text,
                    session_id: sessionId,
                    language: language,
                    page_path: window.location.pathname,
                    page_title: document.title
                })
            });

            const contentType = res.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await res.json() : {};

            if (!res.ok) {
                if (res.status === 429) {
                    messages.push({
                        role: 'assistant',
                        text: language === 'en'
                            ? 'Too many requests. Please wait a moment.'
                            : 'Terlalu banyak permintaan. Silakan tunggu sebentar.',
                        sources: [],
                        error: true
                    });
                    loading = false;
                    updateLabels();
                    renderMessages();
                    return;
                }
                throw new Error(`HTTP ${res.status}`);
            }

            messages.push({
                role: 'assistant',
                text: data.answer || t('noResponse'),
                sources: data.sources || [],
                error: !!data.error
            });
        } catch (e) {
            console.error('[Polly Widget] Fetch error:', e);
            messages.push({
                role: 'assistant',
                text: t('connectionFailed'),
                error: true
            });
        } finally {
            loading = false;
            textareaEl.disabled = false;
            updateLabels();
            renderMessages();
            setTimeout(() => textareaEl.focus(), 50);
        }
    }

    function handleFormSubmit(e) {
        e.preventDefault();
        const text = textareaEl.value.trim();
        if (text) {
            sendPrompt(text);
        }
    }

    // 4. Initialize widget on load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initLayout();
    } else {
        window.addEventListener('DOMContentLoaded', initLayout);
    }
})();
