<!DOCTYPE html>
<html lang="en" class="notranslate" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="googlebot" content="notranslate">
    <meta name="robots" content="notranslate">
    <title>Tayangan Wisudawan - {{ $event->judul }} - Poltekkes Kemenkes Bandung</title>
    <meta name="description" content="Tayangan slide resmi data para wisudawan/wisudawati {{ $event->judul }} Poltekkes Kemenkes Bandung.">

    <!-- Fonts from whitelisted CSP domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;800;900&family=Merriweather:ital,wght@0,700;0,900;1,700;1,900&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Service Worker Cleanup: ensure no rogue service worker hijacks static asset requests -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(regs) {
                for (var i = 0; i < regs.length; i++) {
                    if (regs[i].active && regs[i].active.scriptURL.indexOf('suno-player-sw') !== -1) {
                        regs[i].unregister().then(function() {
                            console.log('Unregistered suno-player-sw from wisudawan slides page.');
                        });
                    }
                }
            }).catch(function(e) {
                console.warn('SW check notice:', e);
            });
        }
    </script>

    <!-- PURE VANILLA CSS - 100% CSP COMPLIANT -->
    <style>
        :root {
            --bg-dark: #070a12;
            --bg-surface: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.7);
            --border-glass: rgba(255, 255, 255, 0.08);
            --gold-light: #fef08a;
            --gold-300: #e0c04f;
            --gold-400: #d7ab29;
            --gold-500: #c59419;
            --gold-600: #a87513;
            --gold-dark: #5f3817;
            --emerald-500: #10b981;
            --emerald-600: #059669;
            --emerald-800: #0c4937;
            --emerald-900: #04271c;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --font-tegas: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --font-serif: var(--font-tegas);
            --teal-academic: #085059;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 50% 0%, #1e293b 0%, transparent 65%),
                radial-gradient(circle at 10% 40%, rgba(12, 73, 55, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 90% 60%, rgba(197, 148, 25, 0.15) 0%, transparent 45%);
            color: var(--text-primary);
            font-family: var(--font-sans);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Navbar */
        .top-navbar {
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border-glass);
            position: sticky;
            top: 0;
            z-index: 50;
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            transition: all 0.3s ease;
        }

        .navbar-inner {
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            min-width: 0;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #cbd5e1;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .btn-back:hover {
            background: #334155;
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.2);
        }

        .event-title-badge {
            font-size: 0.825rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #34d399;
            box-shadow: 0 0 10px #34d399;
            animation: pulseAnim 2s infinite;
            flex-shrink: 0;
        }
        @keyframes pulseAnim {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            flex-shrink: 0;
        }

        .prodi-select {
            background: #1e293b;
            color: #f1f5f9;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.45rem 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            cursor: pointer;
            outline: none;
            max-width: 220px;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: border-color 0.2s;
        }
        .prodi-select:hover, .prodi-select:focus {
            border-color: var(--gold-400);
        }

        .search-box-wrapper {
            position: relative;
            display: none;
        }
        @media (min-width: 1024px) {
            .search-box-wrapper { display: block; }
        }
        .search-input {
            width: 180px;
            background: #1e293b;
            color: #ffffff;
            font-size: 0.75rem;
            border-radius: 8px;
            padding: 0.45rem 0.75rem 0.45rem 2rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            outline: none;
            transition: width 0.25s, border-color 0.2s;
        }
        .search-input:focus {
            width: 230px;
            border-color: var(--gold-400);
            box-shadow: 0 0 0 2px rgba(215, 171, 41, 0.25);
        }
        .search-icon {
            position: absolute;
            left: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            color: #94a3b8;
            pointer-events: none;
        }

        .btn-icon {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            padding: 0.45rem;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-icon:hover {
            background: #334155;
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
        }

        .btn-fullscreen {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, #059669, #0d9488);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35);
            transition: all 0.2s ease;
        }
        .btn-fullscreen:hover {
            background: linear-gradient(135deg, #10b981, #14b8a6);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(5, 150, 105, 0.45);
        }

        /* Presentation Workspace */
        .presentation-workspace {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
        }
        @media (min-width: 640px) {
            .presentation-workspace { padding: 1.5rem; }
        }

        /* 16:9 Presentation Canvas */
        .stage-wrapper {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slide-stage-container {
            width: 100%;
            max-width: 1360px;
            aspect-ratio: 16 / 9;
            margin: 0 auto;
            position: relative;
            background: #ffffff;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            user-select: none;
        }

        /* Fullscreen Theater Mode */
        :fullscreen .slide-stage-container,
        :-webkit-full-screen .slide-stage-container,
        .slide-stage-container.is-fullscreen {
            max-width: none;
            width: 100vw;
            height: 100vh;
            border-radius: 0;
            box-shadow: none;
            background: #ffffff;
        }

        /* Single Authentic Slide Background Frame (Contains ribbon top, official single logo, and ribbon bottom) */
        .slide-background-frame {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            pointer-events: none;
        }
        .slide-bg-image {
            width: 100%;
            height: 100%;
            object-fit: fill;
            display: block;
        }

        /* Ceremony text in the bottom center (Always visible in normal and fullscreen view) */
        .ribbon-bottom-text {
            position: absolute;
            bottom: 3.5%;
            left: 0;
            right: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0 1rem;
            z-index: 20;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }
        .gold-divider-line {
            width: 460px;
            max-width: 48%;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, #c59419 25%, #fef08a 50%, #c59419 75%, transparent 100%);
            margin-bottom: 0.35vw;
        }
        .ceremony-footer-text {
            font-family: var(--font-serif);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            text-align: center;
            line-height: 1.32;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.85);
        }
        .ceremony-footer-text .ceremony-title-row {
            font-size: clamp(0.78rem, 1.25vw, 1.35rem);
        }
        .ceremony-footer-text .txt-wisuda {
            color: #785317;
        }
        .ceremony-footer-text .txt-gelombang {
            color: #b47910;
        }
        .ceremony-footer-text .ceremony-levels-row {
            font-size: clamp(0.72rem, 1.12vw, 1.2rem);
            color: #785317;
        }
        .ceremony-footer-text .ceremony-date-row {
            font-size: clamp(0.75rem, 1.15vw, 1.25rem);
            color: #785317;
        }

        /* Hide floating arrow buttons in fullscreen (keyboard and click zones handle navigation) */
        :fullscreen .stage-nav-btn,
        :-webkit-full-screen .stage-nav-btn,
        .slide-stage-container.is-fullscreen .stage-nav-btn {
            display: none !important;
        }

        /* Slide Viewport - 16:9 Inner Presentation Layout */
        .slide-viewport {
            position: absolute;
            top: 10%;
            bottom: 18%;
            left: 6%;
            right: 6%;
            z-index: 15;
            box-sizing: border-box;
            pointer-events: none;
        }

        :fullscreen .slide-viewport,
        :-webkit-full-screen .slide-viewport,
        .slide-stage-container.is-fullscreen .slide-viewport {
            top: 10%;
            bottom: 18%;
            left: 6%;
            right: 6%;
        }

        .slide-card-inner {
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: clamp(220px, 20vw, 300px) 1fr;
            gap: clamp(2rem, 4vw, 5rem);
            align-items: center;
            pointer-events: auto;
            box-sizing: border-box;
            transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1), transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Left Column: Proportional Graduate Photo */
        .photo-column {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-frame {
            position: relative;
            width: 100%;
            aspect-ratio: 3 / 4;
            max-height: 420px;
            border-radius: clamp(14px, 1.6vw, 24px);
            overflow: hidden;
            box-shadow: 0 16px 36px -6px rgba(0, 0, 0, 0.28);
            border: 3px solid rgba(215, 171, 41, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            background: #e2e8f0;
        }

        .graduate-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
            display: block;
        }

        /* Right Column: Authoritative, Tegas Academic Details Grid */
        .details-column {
            width: 100%;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .academic-info-grid {
            display: grid;
            grid-template-columns: max-content max-content 1fr;
            column-gap: clamp(0.75rem, 1.2vw, 1.5rem);
            row-gap: clamp(0.6rem, 1.6vh, 1.5rem);
            align-items: baseline;
            width: 100%;
        }

        .academic-info-grid .field-label {
            font-family: var(--font-tegas);
            font-size: clamp(1rem, 1.7vw, 2.1rem);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #085059;
            white-space: nowrap;
            line-height: 1.25;
        }

        .academic-info-grid .field-colon {
            font-family: var(--font-tegas);
            font-size: clamp(1rem, 1.7vw, 2.1rem);
            font-weight: 800;
            color: #085059;
            white-space: nowrap;
            line-height: 1.25;
            text-align: center;
        }

        .academic-info-grid .field-value {
            font-family: var(--font-tegas);
            font-size: clamp(1.1rem, 1.8vw, 2.25rem);
            font-weight: 800;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            word-break: break-word;
            line-height: 1.25;
        }

        .academic-info-grid .field-value.val-nama {
            font-size: clamp(1.25rem, 2.2vw, 2.7rem);
            font-weight: 900;
            color: #000000;
            letter-spacing: 0.04em;
        }

        .academic-predikat {
            font-family: var(--font-tegas);
            font-size: clamp(0.9rem, 1.35vw, 1.6rem);
            font-weight: 800;
            font-style: italic;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            margin-left: 0.75rem;
            display: inline;
        }

        .academic-predikat.cumlaude {
            color: #b45309;
        }

        .academic-predikat.memuaskan {
            color: #047857;
        }

        /* ==========================================================================
           INTERACTIVE CLICK ZONES (Left = Before, Right = Next)
           ========================================================================== */
        .slide-click-zone {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 50%;
            z-index: 25;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
            outline: none;
        }
        .slide-click-zone.zone-left {
            left: 0;
        }
        .slide-click-zone.zone-right {
            right: 0;
        }

        /* Idle cursor auto-hide in fullscreen */
        .hide-cursor, .hide-cursor * {
            cursor: none !important;
        }

        /* Floating Nav Arrows (Windowed view only) */
        .stage-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 30;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.2s ease;
            pointer-events: auto;
        }
        .stage-nav-btn.prev { left: 1rem; }
        .stage-nav-btn.next { right: 1rem; }
        .slide-stage-container:hover .stage-nav-btn {
            opacity: 0.75;
        }
        .stage-nav-btn:hover {
            opacity: 1 !important;
            background: rgba(15, 23, 42, 0.85);
            transform: translateY(-50%) scale(1.08);
        }

        /* Autoplay Progress Bar */
        .slide-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #d7ab29, #10b981);
            z-index: 40;
            width: 0%;
            transition: width 0.05s linear;
        }

        /* Bottom Controls Bar */
        .bottom-controls-bar {
            width: 100%;
            max-width: 1360px;
            margin-top: 1rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0 0.5rem;
            user-select: none;
        }

        .controls-group-left {
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }

        .btn-play-pause {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1.1rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #ffffff;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            transition: all 0.2s ease;
        }
        .btn-play-pause:hover {
            background: #334155;
            border-color: rgba(255, 255, 255, 0.25);
        }

        .speed-selector-group {
            display: flex;
            align-items: center;
            background: rgba(30, 41, 59, 0.8);
            border-radius: 12px;
            padding: 3px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .speed-btn {
            background: transparent;
            border: none;
            padding: 0.35rem 0.65rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .speed-btn:hover {
            color: #ffffff;
        }
        .speed-btn.active {
            background: var(--gold-400);
            color: #0f172a;
            font-weight: 800;
        }

        .controls-group-center {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .counter-badge {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.45rem 1.1rem;
            font-size: 0.75rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #cbd5e1;
        }
        .counter-current {
            font-size: 0.95rem;
            font-weight: 900;
            color: var(--gold-300);
        }

        .controls-group-right {
            display: none;
            align-items: center;
            gap: 0.85rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }
        @media (min-width: 768px) {
            .controls-group-right { display: flex; }
        }
        kbd {
            display: inline-block;
            padding: 0.15rem 0.4rem;
            border-radius: 5px;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 0.65rem;
            font-family: inherit;
            color: #e2e8f0;
        }

        /* Grid Drawer (Modal/Panel) */
        .drawer-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.8);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 100;
            display: none;
            justify-content: flex-end;
            transition: opacity 0.25s ease;
        }
        .drawer-backdrop.active {
            display: flex;
        }

        .drawer-panel {
            width: 100%;
            max-width: 440px;
            height: 100%;
            background: #0f172a;
            border-left: 1px solid var(--border-glass);
            box-shadow: -10px 0 35px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            animation: slideInRight 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        .drawer-header {
            padding: 1.15rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .drawer-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .drawer-sub {
            font-size: 0.725rem;
            color: #94a3b8;
            margin-top: 0.15rem;
        }

        .drawer-search-container {
            padding: 0.75rem 1.15rem;
            border-bottom: 1px solid var(--border-glass);
            background: rgba(15, 23, 42, 0.6);
        }
        .drawer-search-input {
            width: 100%;
            background: #1e293b;
            color: #ffffff;
            font-size: 0.75rem;
            border-radius: 8px;
            padding: 0.5rem 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            outline: none;
        }
        .drawer-search-input:focus {
            border-color: var(--gold-400);
        }

        .drawer-list {
            flex: 1;
            overflow-y: auto;
            padding: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .drawer-item {
            padding: 0.6rem 0.75rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(30, 41, 59, 0.4);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.15s ease;
        }
        .drawer-item:hover {
            background: #1e293b;
            border-color: rgba(215, 171, 41, 0.5);
            transform: translateX(2px);
        }
        .drawer-item.active {
            border-color: var(--gold-400);
            background: rgba(215, 171, 41, 0.12);
        }

        .drawer-avatar {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            overflow: hidden;
            background: #334155;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .drawer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .drawer-meta {
            min-width: 0;
            flex: 1;
        }
        .drawer-name {
            font-size: 0.75rem;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .drawer-subtext {
            font-size: 0.685rem;
            color: #94a3b8;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .drawer-ipk {
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--gold-300);
            flex-shrink: 0;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>
</head>
<body class="notranslate">

    <!-- Top Navigation Toolbar -->
    <header id="topNavbar" class="top-navbar">
        <div class="navbar-inner">
            
            <!-- Left: Back Button & Title -->
            <div class="nav-left">
                <a href="{{ route('unit.events.show', [$page->slug, $event->slug]) }}" class="btn-back">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Halaman Event</span>
                </a>

                <div class="event-title-badge">
                    <span class="pulse-dot"></span>
                    <span>Tayangan Wisudawan: {{ $event->judul }}</span>
                </div>
            </div>

            <!-- Center/Right: Action Buttons & Filter -->
            <div class="nav-right">
                
                <!-- Filter Prodi Dropdown -->
                <select id="prodiFilterSelect" onchange="filterByProdi(this.value)" class="prodi-select">
                    <option value="">Semua Program Studi ({{ count($slidesData) }})</option>
                    @foreach($prodis as $p)
                        <option value="{{ $p }}" {{ $activeProdi == $p ? 'selected' : '' }}>
                            {{ $p }}
                        </option>
                    @endforeach
                </select>

                <!-- Instant Search Input -->
                <div class="search-box-wrapper">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Cari NIM / Nama..." 
                           oninput="handleSearch(this.value)"
                           class="search-input">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Directory Drawer Button -->
                <button onclick="toggleGridDrawer(true)" class="btn-icon" title="Daftar Wisudawan (G)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Fullscreen Button -->
                <button onclick="toggleFullscreen()" id="fullscreenBtn" class="btn-fullscreen" title="Mode Layar Penuh (F)">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <span>Layar Penuh</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Presentation Workspace -->
    <main class="presentation-workspace">
        <!-- 16:9 Presentation Stage -->
        <div id="stageWrapper" class="stage-wrapper">
            
            <div id="slideStage" class="slide-stage-container notranslate">
                
                <!-- OFFICIAL SLIDE BACKGROUND FRAME (Contains top ribbon, single official logo, and bottom ribbon) -->
                <div class="slide-background-frame">
                    <img src="{{ asset('images/wisuda-slide/border-bottom.png') }}" alt="Frame Wisuda Polkesban" class="slide-bg-image">
                </div>

                <!-- FOOTER CEREMONY TEXT IN THE MIDDLE (Always visible in normal view and Fullscreen) -->
                <div class="ribbon-bottom-text notranslate" translate="no">
                    <div class="gold-divider-line"></div>
                    <div class="ceremony-footer-text notranslate" translate="no">
                        <div class="ceremony-title-row notranslate" translate="no"><span class="txt-wisuda notranslate" translate="no">WISUDA</span> <span class="txt-gelombang notranslate" translate="no">GELOMBANG II</span></div>
                        <div class="ceremony-levels-row notranslate" translate="no">AHLI MADYA, SARJANA TERAPAN DAN PROFESI</div>
                        <div class="ceremony-date-row notranslate" translate="no">30 SEPTEMBER 2026</div>
                    </div>
                </div>

                <!-- SLIDE CONTENT VIEWPORT -->
                <div id="slideViewport" class="slide-viewport">
                    <!-- Dynamic Slide Card -->
                    <div id="slideCard" class="slide-card-inner">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- INTERACTIVE CLICK ZONES (Left = Before, Right = Next) -->
                <div id="clickZoneLeft" 
                     class="slide-click-zone zone-left" 
                     onclick="prevSlide()" 
                     title="Klik bagian kiri untuk Slide Sebelumnya (&larr;)">
                </div>

                <div id="clickZoneRight" 
                     class="slide-click-zone zone-right" 
                     onclick="nextSlide()" 
                     title="Klik bagian kanan untuk Slide Selanjutnya (&rarr;)">
                </div>

                <!-- FLOATING ON-STAGE NAVIGATION ARROWS (Visible in windowed view on hover, hidden in Fullscreen) -->
                <button onclick="prevSlide()" aria-label="Previous Slide" class="stage-nav-btn prev">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button onclick="nextSlide()" aria-label="Next Slide" class="stage-nav-btn next">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- AUTO-PLAY PROGRESS BAR -->
                <div id="slideProgressBar" class="slide-progress-bar"></div>

            </div>

        </div>

        <!-- BOTTOM CONTROL BAR (Play, Speed, Slide Counter, Shortcuts) -->
        <div id="bottomControls" class="bottom-controls-bar">
            
            <!-- Left: Play/Pause & Speed -->
            <div class="controls-group-left">
                <button id="playPauseBtn" onclick="toggleAutoplay()" class="btn-play-pause">
                    <svg id="playIcon" width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="color: #34d399;">
                        <path d="M4 4l12 6-12 6V4z" />
                    </svg>
                    <svg id="pauseIcon" width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="color: #fbbf24; display: none;">
                        <path d="M5 4h3v12H5V4zm7 0h3v12h-3V4z" />
                    </svg>
                    <span id="playPauseText">Mulai Slideshow</span>
                </button>

                <div class="speed-selector-group">
                    <button onclick="setSpeed(3000)" class="speed-btn" data-speed="3000">3s</button>
                    <button onclick="setSpeed(5000)" class="speed-btn active" data-speed="5000">5s</button>
                    <button onclick="setSpeed(8000)" class="speed-btn" data-speed="8000">8s</button>
                    <button onclick="setSpeed(10000)" class="speed-btn" data-speed="10000">10s</button>
                </div>
            </div>

            <!-- Center: Slide Position Indicator -->
            <div class="controls-group-center">
                <button onclick="prevSlide()" class="btn-icon" title="Slide Sebelumnya (&larr;)">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <div class="counter-badge">
                    Slide <span id="currentSlideDisplay" class="counter-current">1</span> / <span id="totalSlidesDisplay">0</span>
                </div>

                <button onclick="nextSlide()" class="btn-icon" title="Slide Selanjutnya (&rarr;)">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <!-- Right: Keyboard Shortcut Hint -->
            <div class="controls-group-right">
                <span><kbd>Klik Kiri / &larr;</kbd> Before</span>
                <span><kbd>Klik Kanan / &rarr;</kbd> Next</span>
                <span><kbd>F</kbd> Layar Penuh</span>
                <span><kbd>G</kbd> Direktori</span>
            </div>

        </div>

    </main>

    <!-- RIGHT DRAWER: WISUDAWAN DIRECTORY GRID (Modal/Panel) -->
    <div id="gridDrawer" class="drawer-backdrop" onclick="handleDrawerBackdropClick(event)">
        <div class="drawer-panel" onclick="event.stopPropagation()">
            
            <!-- Drawer Header -->
            <div class="drawer-header">
                <div>
                    <h2 class="drawer-title">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="color: #d7ab29;">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.973 10.973 0 00-.25 2.349v2.2a1 1 0 00.553.894l6 3a1 1 0 00.894 0l6-3a1 1 0 00.553-.894v-2.2c0-.79-.089-1.565-.25-2.349l2.644-1.131a1 1 0 000-1.84l-7-3zM6.75 13.5v-1.7c0-.528.06-1.044.174-1.54l3.076 1.318 3.076-1.318c.114.496.174 1.012.174 1.54v1.7l-3.25 1.625a1 1 0 01-.894 0L6.75 13.5z" />
                        </svg>
                        <span>Daftar Wisudawan</span>
                    </h2>
                    <p class="drawer-sub">Pilih wisudawan untuk langsung menampilkan slide</p>
                </div>
                <button onclick="toggleGridDrawer(false)" class="btn-icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Drawer Search -->
            <div class="drawer-search-container">
                <input type="text" 
                       id="drawerSearchInput" 
                       placeholder="Filter nama atau NIM..." 
                       oninput="filterDrawerList(this.value)"
                       class="drawer-search-input">
            </div>

            <!-- Drawer Participant List -->
            <div id="drawerListContainer" class="drawer-list" onscroll="handleDrawerScroll(this)">
                <!-- Dynamically populated -->
            </div>

        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const allSlides = @json($slidesData);
        let filteredSlides = [...allSlides];
        let currentIndex = 0;
        let isAutoplayActive = false;
        let currentSpeed = 5000;
        let progressInterval = null;
        let progressPercent = 0;
        let cursorTimeout = null;
        const defaultAvatarUrl = "{{ asset('images/wisuda-slide/default-avatar.svg') }}";
        const initialNimParam = "{{ $initialNim }}";

        // Lazy drawer rendering variables
        let drawerVisibleCount = 40;
        let activeDrawerItems = [];

        document.addEventListener('DOMContentLoaded', () => {
            if (initialNimParam) {
                const foundIdx = allSlides.findIndex(s => s.nim === initialNimParam);
                if (foundIdx !== -1) {
                    currentIndex = foundIdx;
                }
            }
            updateSlideView();
            initDrawerList(filteredSlides);
            setupFullscreenListeners();
        });

        // Fullscreen Mode Change Listeners
        function setupFullscreenListeners() {
            const stage = document.getElementById('slideStage');
            
            function onFullscreenChanged() {
                const isFull = !!(document.fullscreenElement || document.webkitFullscreenElement);
                if (isFull) {
                    stage.classList.add('is-fullscreen');
                    resetCursorTimer();
                } else {
                    stage.classList.remove('is-fullscreen');
                    stage.classList.remove('hide-cursor');
                    clearTimeout(cursorTimeout);
                }
            }

            document.addEventListener('fullscreenchange', onFullscreenChanged);
            document.addEventListener('webkitfullscreenchange', onFullscreenChanged);

            // Auto-hide mouse cursor after 2.5s of inactivity in fullscreen
            document.addEventListener('mousemove', () => {
                const isFull = !!(document.fullscreenElement || document.webkitFullscreenElement || stage.classList.contains('is-fullscreen'));
                if (isFull) {
                    resetCursorTimer();
                }
            });

            // Touch swipe gesture support for mobile / touch screens
            let touchStartX = 0;
            stage.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            stage.addEventListener('touchend', e => {
                const touchEndX = e.changedTouches[0].screenX;
                const diff = touchEndX - touchStartX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) prevSlide();
                    else nextSlide();
                }
            }, { passive: true });
        }

        function resetCursorTimer() {
            const stage = document.getElementById('slideStage');
            stage.classList.remove('hide-cursor');
            clearTimeout(cursorTimeout);
            cursorTimeout = setTimeout(() => {
                const isFull = !!(document.fullscreenElement || document.webkitFullscreenElement || stage.classList.contains('is-fullscreen'));
                if (isFull) {
                    stage.classList.add('hide-cursor');
                }
            }, 2500);
        }

        // Update presentation stage
        function updateSlideView() {
            if (!filteredSlides || filteredSlides.length === 0) {
                document.getElementById('slideCard').innerHTML = `
                    <div style="width: 100%; text-align: center; padding: 4rem 1rem; color: #64748b;">
                        <p style="font-size: 1.25rem; font-weight: 800; color: #334155;">Tidak Ada Data Wisudawan</p>
                        <p style="font-size: 0.85rem; margin-top: 0.35rem;">Silakan sesuaikan filter program studi atau kata kunci pencarian.</p>
                    </div>
                `;
                document.getElementById('currentSlideDisplay').innerText = '0';
                document.getElementById('totalSlidesDisplay').innerText = '0';
                return;
            }

            if (currentIndex >= filteredSlides.length) currentIndex = 0;
            if (currentIndex < 0) currentIndex = filteredSlides.length - 1;

            const item = filteredSlides[currentIndex];
            document.getElementById('currentSlideDisplay').innerText = currentIndex + 1;
            document.getElementById('totalSlidesDisplay').innerText = filteredSlides.length;

            // Academic Distinction Text
            let predikatHtml = '';
            if (item.predikat) {
                const isCumlaude = item.predikat === 'Dengan Pujian';
                predikatHtml = `
                    <span class="academic-predikat ${isCumlaude ? 'cumlaude' : 'memuaskan'}">
                        (${item.predikat})
                    </span>
                `;
            }

            // Safe photo URL with fallback
            const photoSrc = item.foto_url ? item.foto_url : defaultAvatarUrl;
            const formattedIpk = item.ipk ? String(item.ipk).replace('.', ',') : '-';

            const slideHtml = `
                <!-- LEFT COLUMN: PROPORTIONAL GRADUATE PHOTO -->
                <div class="photo-column notranslate" translate="no">
                    <div class="photo-frame">
                        <img src="${photoSrc}" 
                             alt="${item.nama}" 
                             onerror="this.onerror=null; this.src='${defaultAvatarUrl}';" 
                             class="graduate-img">
                    </div>
                </div>

                <!-- RIGHT COLUMN: AUTHORITATIVE ACADEMIC DETAILS -->
                <div class="details-column notranslate" translate="no">
                    <div class="academic-info-grid notranslate" translate="no">
                        <div class="field-label notranslate" translate="no">NAMA</div>
                        <div class="field-colon">:</div>
                        <div class="field-value val-nama notranslate" translate="no">${item.nama}</div>

                        <div class="field-label notranslate" translate="no">NIM</div>
                        <div class="field-colon">:</div>
                        <div class="field-value notranslate" translate="no">${item.nim || '-'}</div>

                        <div class="field-label notranslate" translate="no">PROGRAM STUDI</div>
                        <div class="field-colon">:</div>
                        <div class="field-value notranslate" translate="no">${item.prodi || '-'}</div>

                        <div class="field-label notranslate" translate="no">IPK</div>
                        <div class="field-colon">:</div>
                        <div class="field-value notranslate" translate="no">
                            ${formattedIpk}
                            ${predikatHtml}
                        </div>
                    </div>
                </div>
            `;

            const card = document.getElementById('slideCard');
            card.innerHTML = slideHtml;
            card.style.opacity = '1';

            highlightActiveDrawerItem();
        }

        // Slide Navigation
        function nextSlide() {
            if (filteredSlides.length === 0) return;
            currentIndex = (currentIndex + 1) % filteredSlides.length;
            resetProgress();
            updateSlideView();
        }

        function prevSlide() {
            if (filteredSlides.length === 0) return;
            currentIndex = (currentIndex - 1 + filteredSlides.length) % filteredSlides.length;
            resetProgress();
            updateSlideView();
        }

        function jumpToSlide(index) {
            if (index >= 0 && index < filteredSlides.length) {
                currentIndex = index;
                resetProgress();
                updateSlideView();
                toggleGridDrawer(false);
            }
        }

        // Filtering
        function filterByProdi(prodi) {
            if (!prodi) {
                filteredSlides = [...allSlides];
            } else {
                filteredSlides = allSlides.filter(s => s.prodi === prodi);
            }
            currentIndex = 0;
            resetProgress();
            updateSlideView();
            initDrawerList(filteredSlides);
        }

        function handleSearch(keyword) {
            keyword = (keyword || '').toLowerCase().trim();
            const selectedProdi = document.getElementById('prodiFilterSelect').value;
            let baseList = selectedProdi ? allSlides.filter(s => s.prodi === selectedProdi) : allSlides;

            if (!keyword) {
                filteredSlides = [...baseList];
            } else {
                filteredSlides = baseList.filter(s => {
                    return (s.nama && s.nama.toLowerCase().includes(keyword)) ||
                           (s.nim && s.nim.toLowerCase().includes(keyword));
                });
            }

            currentIndex = 0;
            resetProgress();
            updateSlideView();
            initDrawerList(filteredSlides);
        }

        // Autoplay & Timer Progress
        function toggleAutoplay() {
            if (isAutoplayActive) {
                stopAutoplay();
            } else {
                startAutoplay();
            }
        }

        function startAutoplay() {
            isAutoplayActive = true;
            document.getElementById('playIcon').style.display = 'none';
            document.getElementById('pauseIcon').style.display = 'inline-block';
            document.getElementById('playPauseText').innerText = 'Jeda Slideshow';
            resetProgress();
            runProgress();
        }

        function stopAutoplay() {
            isAutoplayActive = false;
            document.getElementById('playIcon').style.display = 'inline-block';
            document.getElementById('pauseIcon').style.display = 'none';
            document.getElementById('playPauseText').innerText = 'Mulai Slideshow';
            clearInterval(progressInterval);
            resetProgress();
        }

        function runProgress() {
            clearInterval(progressInterval);
            const stepMs = 50;
            const totalSteps = currentSpeed / stepMs;
            let currentStep = 0;

            progressInterval = setInterval(() => {
                if (!isAutoplayActive) {
                    clearInterval(progressInterval);
                    return;
                }
                currentStep++;
                progressPercent = (currentStep / totalSteps) * 100;
                document.getElementById('slideProgressBar').style.width = progressPercent + '%';

                if (currentStep >= totalSteps) {
                    nextSlide();
                    runProgress();
                }
            }, stepMs);
        }

        function resetProgress() {
            progressPercent = 0;
            document.getElementById('slideProgressBar').style.width = '0%';
        }

        function setSpeed(ms) {
            currentSpeed = ms;
            document.querySelectorAll('.speed-btn').forEach(btn => {
                if (parseInt(btn.getAttribute('data-speed')) === ms) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            if (isAutoplayActive) {
                runProgress();
            }
        }

        // Fullscreen API
        function toggleFullscreen() {
            const stage = document.getElementById('slideStage');
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                if (stage.requestFullscreen) {
                    stage.requestFullscreen().catch(() => {
                        stage.classList.toggle('is-fullscreen');
                    });
                } else if (stage.webkitRequestFullscreen) {
                    stage.webkitRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
            }
        }

        // Drawer functions with optimized lazy rendering
        function toggleGridDrawer(show) {
            const drawer = document.getElementById('gridDrawer');
            if (typeof show === 'boolean') {
                if (show) drawer.classList.add('active');
                else drawer.classList.remove('active');
            } else {
                drawer.classList.toggle('active');
            }
            if (drawer.classList.contains('active')) {
                highlightActiveDrawerItem();
                setTimeout(() => {
                    const activeEl = document.querySelector('.drawer-item.active');
                    if (activeEl) activeEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }, 100);
            }
        }

        function handleDrawerBackdropClick(event) {
            if (event.target.id === 'gridDrawer') {
                toggleGridDrawer(false);
            }
        }

        function initDrawerList(items) {
            activeDrawerItems = items || [];
            drawerVisibleCount = 40;
            renderDrawerChunk();
        }

        function renderDrawerChunk() {
            const container = document.getElementById('drawerListContainer');
            if (!activeDrawerItems || activeDrawerItems.length === 0) {
                container.innerHTML = `<div style="padding: 2.5rem 1rem; text-align: center; color: #64748b; font-size: 0.75rem;">Tidak ada wisudawan ditemukan</div>`;
                return;
            }

            const visibleSlice = activeDrawerItems.slice(0, drawerVisibleCount);
            container.innerHTML = visibleSlice.map((item, idx) => {
                const photoSrc = item.foto_url ? item.foto_url : defaultAvatarUrl;
                return `
                    <div onclick="jumpToSlide(${idx})" 
                         data-index="${idx}"
                         class="drawer-item ${idx === currentIndex ? 'active' : ''}">
                        <div class="drawer-avatar">
                            <img src="${photoSrc}" 
                                 alt="${item.nama}" 
                                 loading="lazy" 
                                 onerror="this.onerror=null; this.src='${defaultAvatarUrl}';">
                        </div>
                        <div class="drawer-meta">
                            <div class="drawer-name">${item.nama}</div>
                            <div class="drawer-subtext">${item.nim || '-'} &bull; ${item.prodi || '-'}</div>
                        </div>
                        <div class="drawer-ipk">${item.ipk || '-'}</div>
                    </div>
                `;
            }).join('');

            highlightActiveDrawerItem();
        }

        function handleDrawerScroll(container) {
            if (container.scrollTop + container.clientHeight >= container.scrollHeight - 150) {
                if (drawerVisibleCount < activeDrawerItems.length) {
                    drawerVisibleCount += 30;
                    renderDrawerChunk();
                }
            }
        }

        function filterDrawerList(keyword) {
            keyword = (keyword || '').toLowerCase().trim();
            if (!keyword) {
                initDrawerList(filteredSlides);
                return;
            }
            const filtered = filteredSlides.filter(s => {
                return (s.nama && s.nama.toLowerCase().includes(keyword)) ||
                       (s.nim && s.nim.toLowerCase().includes(keyword));
            });
            initDrawerList(filtered);
        }

        function highlightActiveDrawerItem() {
            document.querySelectorAll('.drawer-item').forEach(el => {
                const idx = parseInt(el.getAttribute('data-index'));
                if (idx === currentIndex) {
                    el.classList.add('active');
                } else {
                    el.classList.remove('active');
                }
            });
        }

        // Keyboard Shortcuts (Arrow keys & presenter clicks)
        document.addEventListener('keydown', (e) => {
            if (['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target.tagName)) {
                if (e.key === 'Escape') {
                    e.target.blur();
                    toggleGridDrawer(false);
                }
                return;
            }

            switch(e.key) {
                case 'ArrowRight':
                case 'PageDown':
                    e.preventDefault();
                    nextSlide();
                    break;
                case 'ArrowLeft':
                case 'PageUp':
                    e.preventDefault();
                    prevSlide();
                    break;
                case ' ':
                    e.preventDefault();
                    if (isAutoplayActive) {
                        stopAutoplay();
                    } else {
                        nextSlide();
                    }
                    break;
                case 'f':
                case 'F':
                    e.preventDefault();
                    toggleFullscreen();
                    break;
                case 'g':
                case 'G':
                    e.preventDefault();
                    toggleGridDrawer();
                    break;
                case 'Home':
                    e.preventDefault();
                    jumpToSlide(0);
                    break;
                case 'End':
                    e.preventDefault();
                    jumpToSlide(filteredSlides.length - 1);
                    break;
                case 'Escape':
                    toggleGridDrawer(false);
                    break;
            }
        });
    </script>

</body>
</html>
