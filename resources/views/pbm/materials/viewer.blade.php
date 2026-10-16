<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=3.0">
    <title>{{ $material->title }} - Viewer</title>
    <script src="/vendor/pdfjs/pdf.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #1f2937;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #f3f4f6;
            user-select: none;
        }
        #app {
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
        }
        #topbar {
            height: 48px;
            background-color: #111827;
            border-bottom: 1px solid #374151;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            gap: 8px;
            z-index: 20;
            flex-shrink: 0;
        }
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .tool-btn {
            background-color: #374151;
            color: #f9fafb;
            border: 1px solid #4b5563;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: background 0.15s, border-color 0.15s;
            height: 30px;
        }
        .tool-btn:hover:not(:disabled) {
            background-color: #4b5563;
            border-color: #6b7280;
        }
        .tool-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .page-badge {
            font-size: 12px;
            font-weight: 600;
            color: #e5e7eb;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .page-input {
            width: 42px;
            background: #1f2937;
            border: 1px solid #4b5563;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 2px 4px;
            font-size: 12px;
            font-weight: 600;
        }
        #viewer-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            background-color: #374151;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 16px;
            gap: 16px;
            -webkit-overflow-scrolling: touch;
        }
        .page-wrapper {
            background: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            max-width: 100%;
        }
        canvas {
            display: block;
            max-width: 100%;
            height: auto;
        }
        #loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 30;
            gap: 12px;
            font-size: 13px;
            color: #93c5fd;
        }
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(147, 197, 253, 0.2);
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @media (max-width: 640px) {
            .hide-mobile { display: none !important; }
            #topbar { padding: 0 6px; }
            .tool-btn { padding: 4px 6px; font-size: 11px; }
        }
    </style>
</head>
<body>
    <div id="app">
        <div id="topbar">
            <!-- Navigasi Halaman -->
            <div class="toolbar-group">
                <button id="prevBtn" class="tool-btn" onclick="goToPrevPage()" title="Halaman Sebelumnya (Panah Kiri)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                    <span class="hide-mobile">Sebelumnya</span>
                </button>

                <div class="page-badge">
                    <input type="number" id="pageInput" class="page-input" value="1" min="1" onchange="jumpToPage(this.value)">
                    <span>/ <span id="pageTotal">-</span></span>
                </div>

                <button id="nextBtn" class="tool-btn" onclick="goToNextPage()" title="Halaman Berikutnya (Panah Kanan)">
                    <span class="hide-mobile">Berikutnya</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>

            <!-- Kontrol Zoom & Tampilan -->
            <div class="toolbar-group">
                <button class="tool-btn" onclick="zoomOut()" title="Perkecil (-)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>
                </button>
                <span id="zoomPercent" style="font-size: 11px; font-weight: 600; min-width: 42px; text-align: center;">100%</span>
                <button class="tool-btn" onclick="zoomIn()" title="Perbesar (+)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                </button>

                <button class="tool-btn hide-mobile" onclick="fitToWidth()" title="Sesuaikan Lebar Otomatis">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <span>Pas Lebar</span>
                </button>

                <button id="modeBtn" class="tool-btn hide-mobile" onclick="toggleContinuousMode()" title="Ganti Mode Tampilan (Per Halaman / Semua Halaman)">
                    <span>Semua Hal</span>
                </button>
            </div>
        </div>

        <div id="viewer-container">
            <div id="loading-overlay">
                <div class="spinner"></div>
                <div id="loading-text">Memuat dokumen...</div>
            </div>
        </div>
    </div>

    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = "/vendor/pdfjs/pdf.worker.min.js";

        const pdfUrl = "{{ $streamUrl }}";
        let pdfDoc = null;
        let currentPage = 1;
        let totalPages = 0;
        let scaleMode = 'auto'; // 'auto' or numeric
        let currentScale = 1.0;
        let isContinuous = false;
        let isRendering = false;
        let renderQueue = null;

        const container = document.getElementById('viewer-container');
        const pageInput = document.getElementById('pageInput');
        const pageTotal = document.getElementById('pageTotal');
        const zoomPercent = document.getElementById('zoomPercent');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const modeBtn = document.getElementById('modeBtn');
        const overlay = document.getElementById('loading-overlay');
        const loadingText = document.getElementById('loading-text');

        // Muat Dokumen PDF
        const loadingTask = pdfjsLib.getDocument({
            url: pdfUrl,
            withCredentials: true
        });

        loadingTask.onProgress = function(progress) {
            if (progress.total > 0) {
                const pct = Math.round((progress.loaded / progress.total) * 100);
                loadingText.textContent = 'Memuat dokumen: ' + pct + '%';
            }
        };

        loadingTask.promise.then(pdf => {
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            pageTotal.textContent = totalPages;
            pageInput.max = totalPages;
            overlay.style.display = 'none';
            renderView();
        }).catch(err => {
            console.error('Gagal memuat PDF:', err);
            loadingText.innerHTML = '<span style="color:#ef4444; font-weight:bold;">Gagal memuat dokumen:</span> ' + (err.message || 'Error');
        });

        function updateNavState() {
            prevBtn.disabled = (currentPage <= 1);
            nextBtn.disabled = (currentPage >= totalPages);
            pageInput.value = currentPage;
            zoomPercent.textContent = Math.round(currentScale * 100) + '%';
        }

        function renderView() {
            if (!pdfDoc) return;
            container.innerHTML = '';
            updateNavState();

            if (isContinuous) {
                for (let i = 1; i <= totalPages; i++) {
                    renderSinglePage(i);
                }
            } else {
                renderSinglePage(currentPage);
            }
        }

        function renderSinglePage(num) {
            pdfDoc.getPage(num).then(page => {
                const wrapper = document.createElement('div');
                wrapper.className = 'page-wrapper';
                wrapper.id = 'page-' + num;
                
                const canvas = document.createElement('canvas');
                wrapper.appendChild(canvas);
                container.appendChild(wrapper);

                const baseViewport = page.getViewport({ scale: 1.0 });

                let effectiveScale = currentScale;
                if (scaleMode === 'auto') {
                    // Hitung skala agar pas dengan lebar kontainer
                    const availWidth = Math.max(container.clientWidth - 48, 280);
                    effectiveScale = availWidth / baseViewport.width;
                    currentScale = effectiveScale;
                    zoomPercent.textContent = Math.round(currentScale * 100) + '%';
                }

                const dpr = window.devicePixelRatio || 1;
                const renderViewport = page.getViewport({ scale: effectiveScale * dpr });

                canvas.width = renderViewport.width;
                canvas.height = renderViewport.height;
                canvas.style.width = (renderViewport.width / dpr) + 'px';
                canvas.style.height = (renderViewport.height / dpr) + 'px';

                const ctx = canvas.getContext('2d');
                page.render({
                    canvasContext: ctx,
                    viewport: renderViewport
                });
            });
        }

        function goToPrevPage() {
            if (currentPage > 1) {
                currentPage--;
                renderView();
            }
        }

        function goToNextPage() {
            if (currentPage < totalPages) {
                currentPage++;
                renderView();
            }
        }

        function jumpToPage(num) {
            const p = parseInt(num);
            if (p >= 1 && p <= totalPages) {
                currentPage = p;
                if (isContinuous) {
                    const el = document.getElementById('page-' + p);
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                } else {
                    renderView();
                }
            } else {
                pageInput.value = currentPage;
            }
        }

        function zoomIn() {
            scaleMode = 'manual';
            currentScale = Math.min(currentScale + 0.15, 3.0);
            renderView();
        }

        function zoomOut() {
            scaleMode = 'manual';
            currentScale = Math.max(currentScale - 0.15, 0.4);
            renderView();
        }

        function fitToWidth() {
            scaleMode = 'auto';
            renderView();
        }

        function toggleContinuousMode() {
            isContinuous = !isContinuous;
            modeBtn.textContent = isContinuous ? 'Mode: 1 Halaman' : 'Mode: Semua Hal';
            renderView();
        }

        // Shortcut keyboard navigasi
        window.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT') return;
            if (e.key === 'ArrowLeft' || e.key === 'PageUp') goToPrevPage();
            if (e.key === 'ArrowRight' || e.key === 'PageDown') goToNextPage();
            if (e.key === '+' || e.key === '=') zoomIn();
            if (e.key === '-') zoomOut();
        });

        // Resize otomatis
        let resizeTimer = null;
        window.addEventListener('resize', () => {
            if (scaleMode === 'auto') {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    renderView();
                }, 200);
            }
        });
    </script>
</body>
</html>
