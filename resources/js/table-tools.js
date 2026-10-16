const excludedHeaderPatterns = [/^aksi$/i, /^actions?$/i, /^opsi$/i, /^option/i, /^#$/];
const textCollator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });

function normalizeTableText(value) {
    return String(value ?? '').replace(/\s+/g, ' ').trim();
}

function normalizeSearchText(value) {
    return normalizeTableText(value).toLowerCase();
}

function parseNumericValue(value) {
    let candidate = normalizeTableText(value)
        .replace(/rp\.?/gi, '')
        .replace(/idr/gi, '')
        .replace(/[^\d,.-]/g, '');

    if (!/\d/.test(candidate)) {
        return null;
    }

    if (candidate.includes(',') && candidate.includes('.')) {
        if (candidate.lastIndexOf(',') > candidate.lastIndexOf('.')) {
            candidate = candidate.replace(/\./g, '').replace(',', '.');
        } else {
            candidate = candidate.replace(/,/g, '');
        }
    } else if (candidate.includes(',')) {
        const parts = candidate.split(',');
        candidate = parts.length === 2 && parts[1].length <= 2
            ? `${parts[0]}.${parts[1]}`
            : candidate.replace(/,/g, '');
    }

    const parsed = Number(candidate);

    return Number.isFinite(parsed) ? parsed : null;
}

function parseComparableValue(value) {
    const text = normalizeTableText(value);
    if (text === '') {
        return { kind: 'text', value: '' };
    }

    const numeric = parseNumericValue(text);
    if (numeric !== null) {
        return { kind: 'number', value: numeric };
    }

    const timestamp = Date.parse(text);
    if (Number.isFinite(timestamp)) {
        return { kind: 'date', value: timestamp };
    }

    return { kind: 'text', value: text };
}

function compareTableValues(left, right) {
    const leftComparable = parseComparableValue(left);
    const rightComparable = parseComparableValue(right);

    if (leftComparable.kind === rightComparable.kind) {
        if (leftComparable.kind === 'number' || leftComparable.kind === 'date') {
            return leftComparable.value - rightComparable.value;
        }

        return textCollator.compare(leftComparable.value, rightComparable.value);
    }

    return textCollator.compare(normalizeTableText(left), normalizeTableText(right));
}

function isUsableHeader(label) {
    return label !== '' && !excludedHeaderPatterns.some((pattern) => pattern.test(label));
}

function createElement(tagName, className, textContent = '') {
    const element = document.createElement(tagName);
    if (className) {
        element.className = className;
    }
    if (textContent) {
        element.textContent = textContent;
    }

    return element;
}

class SmartTable {
    constructor(table) {
        this.table = table;
        this.tbody = table.tBodies[0] ?? null;
        this.rows = [];
        this.headers = [];
        this.searchInput = null;
        this.sortColumnSelect = null;
        this.sortDirectionSelect = null;
        this.filterColumnSelect = null;
        this.filterValueSelect = null;
        this.resultMeta = null;
        this.emptyRow = null;
    }

    init() {
        if (!this.tbody || this.table.dataset.smartTableInitialized === 'true') {
            return;
        }

        const fallbackHeaderRow = this.table.tHead
            ? null
            : Array.from(this.tbody.rows).find((row) => Array.from(row.cells).every((cell) => cell.tagName === 'TH')) ?? null;
        const headerCells = Array.from(this.table.tHead?.rows?.[0]?.cells ?? fallbackHeaderRow?.cells ?? []);
        const bodyRows = Array.from(this.tbody.rows).filter((row) => row !== fallbackHeaderRow && row.querySelectorAll('td, th').length > 0);

        if (headerCells.length === 0 || bodyRows.length === 0) {
            return;
        }

        this.headers = headerCells.map((cell, index) => {
            const label = normalizeTableText(cell.textContent) || `Kolom ${index + 1}`;

            return {
                index,
                label,
                usable: isUsableHeader(label),
            };
        });

        this.rows = bodyRows.map((row, rowIndex) => {
            const cells = Array.from(row.cells).map((cell) => normalizeTableText(cell.textContent));
            const searchable = this.headers
                .filter((header) => header.usable)
                .map((header) => cells[header.index] ?? '')
                .join(' ');

            return {
                row,
                originalIndex: rowIndex,
                cells,
                searchable: normalizeSearchText(searchable || row.textContent),
            };
        });

        this.renderToolbar();
        this.renderEmptyStateRow();
        this.apply();
        this.table.dataset.smartTableInitialized = 'true';
    }

    renderToolbar() {
        const toolbar = createElement('div', 'polkesban-table-tools');
        const fields = createElement('div', 'polkesban-table-tools__grid');

        const searchField = this.buildField('Cari');
        this.searchInput = createElement('input', 'polkesban-table-tools__control');
        this.searchInput.type = 'search';
        this.searchInput.placeholder = 'Cari di tabel ini';
        this.searchInput.addEventListener('input', () => this.apply());
        searchField.append(this.searchInput);

        const usableHeaders = this.headers.filter((header) => header.usable);

        const sortField = this.buildField('Urutkan');
        this.sortColumnSelect = createElement('select', 'polkesban-table-tools__control');
        this.sortColumnSelect.append(new Option('Urutan awal', ''));
        usableHeaders.forEach((header) => {
            this.sortColumnSelect.append(new Option(header.label, String(header.index)));
        });
        this.sortColumnSelect.addEventListener('change', () => this.apply());
        sortField.append(this.sortColumnSelect);

        const directionField = this.buildField('Arah');
        this.sortDirectionSelect = createElement('select', 'polkesban-table-tools__control');
        this.sortDirectionSelect.append(new Option('Naik (A-Z)', 'asc'));
        this.sortDirectionSelect.append(new Option('Turun (Z-A)', 'desc'));
        this.sortDirectionSelect.addEventListener('change', () => this.apply());
        directionField.append(this.sortDirectionSelect);

        const filterColumnField = this.buildField('Filter Kolom');
        this.filterColumnSelect = createElement('select', 'polkesban-table-tools__control');
        this.filterColumnSelect.append(new Option('Tanpa filter kolom', ''));
        usableHeaders.forEach((header) => {
            this.filterColumnSelect.append(new Option(header.label, String(header.index)));
        });
        this.filterColumnSelect.addEventListener('change', () => {
            this.populateFilterValues();
            this.apply();
        });
        filterColumnField.append(this.filterColumnSelect);

        const filterValueField = this.buildField('Nilai Filter');
        this.filterValueSelect = createElement('select', 'polkesban-table-tools__control');
        this.filterValueSelect.disabled = true;
        this.filterValueSelect.append(new Option('Semua nilai', ''));
        this.filterValueSelect.addEventListener('change', () => this.apply());
        filterValueField.append(this.filterValueSelect);

        fields.append(searchField, sortField, directionField, filterColumnField, filterValueField);

        const footer = createElement('div', 'polkesban-table-tools__footer');
        this.resultMeta = createElement('p', 'polkesban-table-tools__meta');
        const resetButton = createElement('button', 'polkesban-table-tools__button', 'Reset');
        resetButton.type = 'button';
        resetButton.addEventListener('click', () => this.reset());
        footer.append(this.resultMeta, resetButton);

        toolbar.append(fields, footer);

        const anchor = this.table.closest('.overflow-x-auto') || this.table;
        anchor.parentNode?.insertBefore(toolbar, anchor);
    }

    buildField(label) {
        const wrapper = createElement('label', 'polkesban-table-tools__field');
        const title = createElement('span', 'polkesban-table-tools__label', label);
        wrapper.append(title);

        return wrapper;
    }

    renderEmptyStateRow() {
        this.emptyRow = document.createElement('tr');
        this.emptyRow.className = 'polkesban-table-tools__empty-row';
        this.emptyRow.hidden = true;

        const cell = document.createElement('td');
        cell.colSpan = this.headers.length;
        cell.textContent = 'Tidak ada baris yang cocok dengan pencarian atau filter.';
        this.emptyRow.append(cell);
        this.tbody.append(this.emptyRow);
    }

    populateFilterValues() {
        const selectedColumn = this.filterColumnSelect?.value ?? '';

        this.filterValueSelect.innerHTML = '';
        this.filterValueSelect.append(new Option('Semua nilai', ''));

        if (selectedColumn === '') {
            this.filterValueSelect.disabled = true;
            return;
        }

        const columnIndex = Number.parseInt(selectedColumn, 10);
        const uniqueValues = Array.from(new Set(this.rows
            .map((row) => row.cells[columnIndex] ?? '')
            .filter((value) => value !== '')))
            .sort((left, right) => textCollator.compare(left, right));

        uniqueValues.forEach((value) => {
            this.filterValueSelect.append(new Option(value, value));
        });
        this.filterValueSelect.disabled = uniqueValues.length === 0;
    }

    reset() {
        this.searchInput.value = '';
        this.sortColumnSelect.value = '';
        this.sortDirectionSelect.value = 'asc';
        this.filterColumnSelect.value = '';
        this.populateFilterValues();
        this.filterValueSelect.value = '';
        this.apply();
    }

    apply() {
        const searchQuery = normalizeSearchText(this.searchInput?.value ?? '');
        const sortColumn = this.sortColumnSelect?.value ?? '';
        const sortDirection = this.sortDirectionSelect?.value ?? 'asc';
        const filterColumn = this.filterColumnSelect?.value ?? '';
        const filterValue = normalizeTableText(this.filterValueSelect?.value ?? '');

        const matchedRows = this.rows.filter((row) => {
            const matchesSearch = searchQuery === '' || row.searchable.includes(searchQuery);
            const matchesFilter = filterColumn === '' || filterValue === ''
                || normalizeTableText(row.cells[Number.parseInt(filterColumn, 10)] ?? '') === filterValue;

            return matchesSearch && matchesFilter;
        });

        const unmatchedRows = this.rows.filter((row) => !matchedRows.includes(row));

        const sortedRows = [...matchedRows].sort((left, right) => {
            if (sortColumn === '') {
                return left.originalIndex - right.originalIndex;
            }

            const columnIndex = Number.parseInt(sortColumn, 10);
            const result = compareTableValues(left.cells[columnIndex] ?? '', right.cells[columnIndex] ?? '');

            if (result !== 0) {
                return sortDirection === 'desc' ? -result : result;
            }

            return left.originalIndex - right.originalIndex;
        });

        [...sortedRows, ...unmatchedRows].forEach((rowData) => {
            this.tbody.append(rowData.row);
            rowData.row.hidden = !sortedRows.includes(rowData);
        });

        if (this.emptyRow) {
            this.tbody.append(this.emptyRow);
            this.emptyRow.hidden = sortedRows.length > 0;
        }

        if (this.resultMeta) {
            this.resultMeta.textContent = `Menampilkan ${sortedRows.length} dari ${this.rows.length} baris`;
        }
    }
}

function initSmartTables(root = document) {
    if (!root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    const tables = root instanceof HTMLTableElement
        ? [root]
        : Array.from(root.querySelectorAll('table:not([data-table-tools="off"])'));

    tables.forEach((table) => {
        if (!(table instanceof HTMLTableElement)) {
            return;
        }

        if (table.closest('.tox, [data-table-tools-ignore], .table-tools-ignore')) {
            return;
        }

        new SmartTable(table).init();
    });
}

function observeSmartTables() {
    if (!document.body) {
        return;
    }

    const observer = new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach((node) => {
                if (!(node instanceof Element)) {
                    return;
                }

                initSmartTables(node);
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initSmartTables();
        observeSmartTables();
    }, { once: true });
} else {
    initSmartTables();
    observeSmartTables();
}

window.PolkesbanSmartTables = {
    init: initSmartTables,
};