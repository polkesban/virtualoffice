let generatedEditorCount = 0;
let tinymceLoader = null;
let localTinyMceAssetsLoaded = false;
const runtimeState = {
    ready: false,
    source: null,
    activeKeyId: null,
    activeKeyLabel: null,
    activeKeySource: null,
    lastError: null,
};

const TINYMCE_RUNTIME_URL = '/admin/api-keys/tinymce/runtime';

function exposeTinyMceGlobal(instance) {
    if (!instance) {
        return;
    }

    window.tinymce = instance;

    if (typeof globalThis !== 'undefined') {
        globalThis.tinymce = instance;
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function postTinyMceKeySignal(url) {
    const token = csrfToken();
    if (!token) {
        return;
    }

    try {
        await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({}),
        });
    } catch {
        // Ignore reporting failures so editor fallback can continue.
    }
}

function buildTinyMceCloudUrl(runtime, apiKey) {
    const cdnBase = String(runtime.cdnBase || 'https://cdn.tiny.cloud/1').replace(/\/$/, '');
    const version = encodeURIComponent(String(runtime.version || '7'));
    const encodedKey = encodeURIComponent(String(apiKey || ''));

    return `${cdnBase}/${encodedKey}/tinymce/${version}/tinymce.min.js`;
}

function removeCloudTinyMceArtifacts() {
    document.querySelectorAll('script[data-polkesban-tinymce="cloud"]').forEach((script) => script.remove());

    if (window.tinymce) {
        try {
            window.tinymce.remove();
        } catch {
            // Ignore teardown failure before local fallback.
        }
    }

    try {
        delete window.tinymce;
    } catch {
        window.tinymce = undefined;
    }

    if (typeof globalThis !== 'undefined') {
        try {
            delete globalThis.tinymce;
        } catch {
            globalThis.tinymce = undefined;
        }
    }
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.referrerPolicy = 'origin';
        script.dataset.polkesbanTinymce = 'cloud';
        script.onload = () => resolve();
        script.onerror = () => {
            script.remove();
            reject(new Error(`Failed loading ${src}`));
        };
        document.head.append(script);
    });
}

async function fetchTinyMceRuntime() {
    try {
        const response = await fetch(TINYMCE_RUNTIME_URL, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`TinyMCE runtime returned ${response.status}`);
        }

        const runtime = await response.json();

        return {
            keys: Array.isArray(runtime.keys) ? runtime.keys : [],
            cdnBase: runtime.cdnBase || 'https://cdn.tiny.cloud/1',
            version: runtime.version || '7',
            cloudEnabled: runtime.cloudEnabled !== false,
            localFallback: runtime.localFallback !== false,
        };
    } catch {
        return {
            keys: [],
            cdnBase: 'https://cdn.tiny.cloud/1',
            version: '7',
            cloudEnabled: false,
            localFallback: true,
        };
    }
}

async function loadTinyMceLocal() {
    if (window.tinymce && localTinyMceAssetsLoaded) {
        runtimeState.ready = true;
        runtimeState.source = runtimeState.source || 'local';
        return { tinymce: window.tinymce, source: 'local' };
    }

    const tinymceModule = await import('tinymce/tinymce');
    const instance = tinymceModule.default;
    exposeTinyMceGlobal(instance);

    await Promise.all([
        import('tinymce/icons/default'),
        import('tinymce/themes/silver'),
        import('tinymce/models/dom/model'),
        import('tinymce/skins/ui/oxide/skin.min.css'),
        import('tinymce/skins/content/default/content.min.css'),
    ]);

    await Promise.all([
        import('tinymce/plugins/advlist'),
        import('tinymce/plugins/anchor'),
        import('tinymce/plugins/autolink'),
        import('tinymce/plugins/autoresize'),
        import('tinymce/plugins/charmap'),
        import('tinymce/plugins/code'),
        import('tinymce/plugins/codesample'),
        import('tinymce/plugins/fullscreen'),
        import('tinymce/plugins/image'),
        import('tinymce/plugins/link'),
        import('tinymce/plugins/lists'),
        import('tinymce/plugins/media'),
        import('tinymce/plugins/preview'),
        import('tinymce/plugins/quickbars'),
        import('tinymce/plugins/searchreplace'),
        import('./tinymce-plugin-table'),
        import('tinymce/plugins/visualblocks'),
        import('tinymce/plugins/wordcount'),
    ]);

    localTinyMceAssetsLoaded = true;
    runtimeState.ready = true;
    runtimeState.source = 'local';
    runtimeState.activeKeyId = null;
    runtimeState.activeKeyLabel = null;
    runtimeState.activeKeySource = 'local';
    runtimeState.lastError = null;

    return { tinymce: instance, source: 'local' };
}

async function loadTinyMceCloud(runtime) {
    for (const key of runtime.keys) {
        removeCloudTinyMceArtifacts();

        try {
            await loadScript(buildTinyMceCloudUrl(runtime, key.key));

            if (!window.tinymce) {
                throw new Error('TinyMCE cloud loaded without runtime');
            }

            exposeTinyMceGlobal(window.tinymce);

            if (key.id) {
                postTinyMceKeySignal(`/admin/api-keys/${key.id}/tinymce/used`);
            }

            runtimeState.ready = true;
            runtimeState.source = 'cloud';
            runtimeState.activeKeyId = key.id ?? null;
            runtimeState.activeKeyLabel = key.label ?? null;
            runtimeState.activeKeySource = key.source ?? 'database';
            runtimeState.lastError = null;

            return { tinymce: window.tinymce, source: 'cloud' };
        } catch {
            runtimeState.lastError = `Key TinyMCE Cloud gagal dimuat: ${key.label ?? 'tanpa label'}`;
            if (key.id) {
                postTinyMceKeySignal(`/admin/api-keys/${key.id}/tinymce/exhausted`);
            }
        }
    }

    removeCloudTinyMceArtifacts();
    throw new Error('All TinyMCE cloud keys failed');
}

async function loadTinyMce() {
    if (tinymceLoader) {
        return tinymceLoader;
    }

    tinymceLoader = (async () => {
        const runtime = await fetchTinyMceRuntime();

        if (runtime.cloudEnabled && runtime.keys.length > 0) {
            try {
                return await loadTinyMceCloud(runtime);
            } catch {
                runtimeState.lastError = 'Semua key TinyMCE Cloud gagal dimuat, fallback ke editor lokal.';
                // Fall through to local bundle.
            }
        }

        return loadTinyMceLocal();
    })().catch((error) => {
        tinymceLoader = null;
        runtimeState.ready = false;
        runtimeState.lastError = error?.message || 'TinyMCE gagal dimuat.';
        throw error;
    });

    return tinymceLoader;
}

const fullProfile = {
    menubar: 'file edit view insert format table tools help',
    plugins: [
        'advlist', 'anchor', 'autolink', 'autoresize', 'charmap', 'code', 'codesample',
        'fullscreen', 'help', 'image', 'link', 'lists', 'media', 'preview',
        'quickbars', 'searchreplace', 'table', 'visualblocks', 'wordcount',
    ],
    toolbar: 'undo redo | blocks fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table blockquote | codesample code visualblocks preview fullscreen | removeformat help',
    toolbar_mode: 'wrap',
    quickbars_selection_toolbar: 'bold italic underline | blocks | forecolor backcolor | quicklink blockquote',
    resize: 'both',
    statusbar: true,
    height: 460,
};

const basicProfile = {
    menubar: false,
    plugins: [
        'advlist', 'autolink', 'autoresize', 'charmap', 'code', 'link', 'lists',
        'preview', 'quickbars', 'searchreplace', 'visualblocks', 'wordcount',
    ],
    toolbar: 'undo redo | blocks bold italic underline | bullist numlist | alignleft aligncenter alignright | link | removeformat code preview',
    toolbar_mode: 'wrap',
    quickbars_selection_toolbar: 'bold italic underline | quicklink blockquote',
    resize: 'both',
    statusbar: true,
    height: 320,
};

function stripToolbarItem(toolbar, item) {
    if (typeof toolbar !== 'string') {
        return toolbar;
    }

    return String(toolbar || '')
        .split('|')
        .map((group) => group.trim().split(/\s+/).filter((token) => token && token !== item).join(' '))
        .filter((group) => group !== '')
        .join(' | ');
}

function normalizeProfileForSource(profile, source) {
    if (source !== 'local') {
        return {
            ...profile,
            plugins: [...profile.plugins],
        };
    }

    const plugins = profile.plugins.filter((plugin) => plugin !== 'help');

    return {
        ...profile,
        plugins,
        menubar: stripToolbarItem(profile.menubar, 'help'),
        toolbar: stripToolbarItem(profile.toolbar, 'help'),
    };
}

function buildConfig(textarea, source) {
    const baseProfile = textarea.dataset.richEditorProfile === 'basic' ? basicProfile : fullProfile;
    const profile = normalizeProfileForSource(baseProfile, source);
    const height = Number.parseInt(textarea.dataset.richEditorHeight || '', 10);
    const minHeight = Number.parseInt(textarea.dataset.richEditorMinHeight || '', 10);
    const minWidth = Number.parseInt(textarea.dataset.richEditorMinWidth || '', 10);
    const resize = textarea.dataset.richEditorResize || profile.resize || 'both';

    return {
        target: textarea,
        branding: false,
        promotion: false,
        browser_spellcheck: true,
        width: '100%',
        convert_urls: false,
        relative_urls: false,
        remove_script_host: false,
        ...(source === 'local'
            ? {
                license_key: 'gpl',
                skin: false,
                content_css: false,
            }
            : {}),
        content_style: 'body { font-family: ui-sans-serif, system-ui, sans-serif; font-size: 14px; line-height: 1.65; padding: 1rem; }',
        resize,
        ...profile,
        ...(Number.isFinite(height) ? { height } : {}),
        ...(Number.isFinite(minHeight) ? { min_height: minHeight } : Number.isFinite(height) ? { min_height: height } : {}),
        ...(Number.isFinite(minWidth) ? { min_width: minWidth } : {}),
        setup(editor) {
            editor.on('init', () => {
                textarea.dataset.richEditorInitialized = 'true';
                editor.save();
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.dispatchEvent(new Event('change', { bubbles: true }));
            });

            editor.on('change input undo redo keyup SetContent', () => {
                editor.save();
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.dispatchEvent(new Event('change', { bubbles: true }));
            });
        },
    };
}

async function initRichTextEditors(root = document) {
    if (!root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    const textareas = Array.from(root.querySelectorAll('textarea[data-rich-editor]'));
    if (textareas.length === 0) {
        return;
    }

    const { tinymce, source } = await loadTinyMce();

    for (const textarea of textareas) {
        if (!(textarea instanceof HTMLTextAreaElement)) {
            continue;
        }

        const allowHiddenInit = textarea.dataset.richEditorInitHidden === 'true';
        const isHidden = textarea.getClientRects().length === 0 || window.getComputedStyle(textarea).display === 'none';
        if (isHidden && !allowHiddenInit) {
            continue;
        }

        if (!textarea.id) {
            generatedEditorCount += 1;
            textarea.id = `polkesban-rich-editor-${generatedEditorCount}`;
        }

        if (tinymce.get(textarea.id) || textarea.dataset.richEditorInitialized === 'true') {
            continue;
        }

        await tinymce.init(buildConfig(textarea, source));
    }
}

function observeRichTextEditors() {
    if (!document.body || typeof MutationObserver === 'undefined') {
        return;
    }

    const queue = new Set();
    let scheduled = false;

    const flush = () => {
        const targets = Array.from(queue);
        queue.clear();
        scheduled = false;

        targets.forEach((target) => {
            initRichTextEditors(target).catch(() => {
                // Ignore observer-triggered init failures and keep future retries possible.
            });
        });
    };

    const schedule = (target) => {
        queue.add(target instanceof Element ? target : document);
        if (scheduled) {
            return;
        }

        scheduled = true;
        requestAnimationFrame(() => flush());
    };

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            if (record.type === 'attributes' && record.target instanceof Element) {
                schedule(record.target);
                return;
            }

            record.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    schedule(node);
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class', 'hidden'],
    });
}

function setRichTextContent(id, html) {
    const editor = window.tinymce?.get(id);

    if (editor) {
        editor.setContent(html || '');
        editor.save();
        return true;
    }

    const textarea = document.getElementById(id);
    if (textarea instanceof HTMLTextAreaElement) {
        textarea.value = html || '';
        return true;
    }

    return false;
}

window.PolkesbanRichText = {
    init: initRichTextEditors,
    setContent: setRichTextContent,
    getEditor(id) {
        return window.tinymce?.get(id) ?? null;
    },
    getRuntimeState() {
        return { ...runtimeState };
    },
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initRichTextEditors();
        observeRichTextEditors();
    }, { once: true });
} else {
    initRichTextEditors();
    observeRichTextEditors();
}