import './bootstrap';
import './rich-text';
import './table-tools';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const localeMessages = {
    id: {
        show: 'Lihat',
        hide: 'Sembunyikan',
        showPassword: 'Lihat password',
        hidePassword: 'Sembunyikan password',
    },
    en: {
        show: 'Show',
        hide: 'Hide',
        showPassword: 'Show password',
        hidePassword: 'Hide password',
    },
};

const externalRoutePaths = new Set(['/penerimaan', '/portal', '/perpustakaan']);

function currentLocale() {
    return document.documentElement.lang?.toLowerCase().startsWith('en') ? 'en' : 'id';
}

function normalizePath(pathname) {
    const normalized = (pathname || '/').replace(/\/+$/, '');
    return normalized || '/';
}

function shouldOpenInNewTab(anchor) {
    const href = anchor.getAttribute('href');

    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) {
        return false;
    }

    try {
        const url = new URL(href, window.location.origin);

        if (!['http:', 'https:'].includes(url.protocol)) {
            return false;
        }

        return url.origin !== window.location.origin || externalRoutePaths.has(normalizePath(url.pathname));
    } catch {
        return false;
    }
}

function markExternalLink(anchor) {
    if (!shouldOpenInNewTab(anchor)) {
        return;
    }

    anchor.target = '_blank';

    const rel = new Set((anchor.getAttribute('rel') || '').split(/\s+/).filter(Boolean));
    rel.add('noopener');
    rel.add('noreferrer');
    anchor.setAttribute('rel', Array.from(rel).join(' '));
}

function markExternalLinks(root = document) {
    if (root instanceof HTMLAnchorElement) {
        markExternalLink(root);
        return;
    }

    if (!('querySelectorAll' in root)) {
        return;
    }

    root.querySelectorAll('a[href]').forEach(markExternalLink);
}

function initExternalLinks() {
    markExternalLinks();

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            if (record.type === 'attributes' && record.target instanceof HTMLAnchorElement) {
                markExternalLink(record.target);
            }

            record.addedNodes.forEach((node) => {
                if (!(node instanceof Element)) {
                    return;
                }

                markExternalLinks(node);
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['href'],
    });
}

function initPasswordToggles(root = document) {
    const groups = root instanceof Element && root.matches('[data-password-toggle]')
        ? [root]
        : Array.from(root.querySelectorAll?.('[data-password-toggle]') ?? []);

    groups.forEach((group) => {
        if (!(group instanceof HTMLElement) || group.dataset.passwordToggleInitialized === 'true') {
            return;
        }

        const input = group.querySelector('[data-password-toggle-input]');
        const button = group.querySelector('[data-password-toggle-button]');

        if (!(input instanceof HTMLInputElement) || !(button instanceof HTMLButtonElement)) {
            return;
        }

        const labels = localeMessages[currentLocale()];
        const showLabel = button.dataset.showLabel || labels.show;
        const hideLabel = button.dataset.hideLabel || labels.hide;

        const syncState = () => {
            const isVisible = input.type === 'text';
            button.textContent = isVisible ? hideLabel : showLabel;
            button.setAttribute('aria-pressed', String(isVisible));
            button.setAttribute('aria-label', isVisible ? labels.hidePassword : labels.showPassword);
        };

        button.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            syncState();
        });

        syncState();
        group.dataset.passwordToggleInitialized = 'true';
    });
}

function observePasswordToggles() {
    if (!document.body) {
        return;
    }

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach((node) => {
                if (!(node instanceof Element)) {
                    return;
                }

                initPasswordToggles(node);
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

// ── Theme store: persistent dark mode ─────────────────────────────────────
Alpine.store('theme', {
    dark: localStorage.getItem('polkesban-theme') === 'dark' ||
          (!localStorage.getItem('polkesban-theme') &&
           window.matchMedia('(prefers-color-scheme: dark)').matches),
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('polkesban-theme', this.dark ? 'dark' : 'light');
    },
});

Alpine.start();

function initAppUi() {
    initExternalLinks();
    initPasswordToggles();
    observePasswordToggles();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAppUi, { once: true });
} else {
    initAppUi();
}
