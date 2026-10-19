/**
 * Oddiy ma'lumot sahifasi (yuklash / filtrlar / jadval / Excel'ga yuklab olish).
 * Har bir sahifa faqat sozlamani beradi:
 *
 * DataPage.init({
 *     api: '/api/qozoq',                 // ro'yxat (Laravel paginate javobi: {data: {data, total, current_page, last_page}})
 *     importUrl: '/api/qozoq/import',     // fayl yuklash (POST, file)
 *     exportUrl: '/api/qozoq/export',     // Excel (GET, filtrlar bilan)
 *     accept: '.xlsx,.xls,.csv',
 *     filters: [
 *         { param: 'boundary_name', label: 'Chegara', type: 'select', options: ['A', {value: 'uz', label: 'B'}] },
 *         { param: 'date_from', label: 'Sanadan', type: 'date', suffix: ' 00:00:00' },
 *         { param: 'car_number', label: 'Avto raqam', type: 'text', placeholder: '01A123BC' },
 *     ],
 *     columns: [
 *         { key: 'car_number', label: 'Avto raqami' },
 *         { key: 'date_and_time', label: 'Sana', format: 'hourRange' }, // yoki format: (value, row) => '...'
 *     ],
 *     exportName: values => 'fayl.xlsx',  // ixtiyoriy; bo'lmasa server bergan nom
 *     dataKey: 'eombors',                 // ixtiyoriy; paginate javobi qaysi kalitda (default 'data', null — javobning o'zi)
 * });
 */
(function () {
    const { api, fmt, esc, icon, toast, token } = window.App;

    const FORMATS = {
        date: v => v ? fmt.date(v) : null,
        datetime: v => v ? fmt.dateTime(v) : null,
        number: v => (v === null || v === undefined || v === '') ? null : fmt.number(v),
        /** "19.10.2026 10:00-11:00" — navbat vaqti (bir soatlik oraliq) */
        hourRange(v) {
            if (!v) return null;
            const date = new Date(v);
            if (isNaN(date)) return String(v);
            const end = new Date(date);
            end.setHours(end.getHours() + 1);
            const hm = d => `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
            const day = `${String(date.getDate()).padStart(2, '0')}.${String(date.getMonth() + 1).padStart(2, '0')}.${date.getFullYear()}`;
            return `${day} ${hm(date)}-${hm(end)}`;
        },
    };

    function init(config) {
        const root = document.getElementById('dataPage');
        const els = {
            filters: root.querySelector('[data-el="filters"]'),
            clear: root.querySelector('[data-el="clear"]'),
            head: root.querySelector('[data-el="head"]'),
            body: root.querySelector('[data-el="body"]'),
            total: root.querySelector('[data-el="total"]'),
            pagination: root.querySelector('[data-el="pagination"]'),
            upload: root.querySelector('[data-el="upload"]'),
            file: root.querySelector('[data-el="file"]'),
            export: root.querySelector('[data-el="export"]'),
        };
        const filters = config.filters || [];
        const columns = config.columns;
        let page = 1;
        let loadToken = 0;

        // ===== Filtrlar =====
        els.filters.innerHTML = filters.map((f, i) => {
            const id = `dpFilter${i}`;
            let control;
            if (f.type === 'select') {
                const options = [{ value: '', label: 'Barchasi' }, ...f.options.map(o => typeof o === 'string' ? { value: o, label: o } : o)];
                control = `<select class="select" id="${id}">${options.map(o => `<option value="${esc(o.value)}">${esc(o.label)}</option>`).join('')}</select>`;
            } else if (f.type === 'date') {
                control = `<input class="input" type="date" id="${id}">`;
            } else {
                control = `<div class="input-icon">${icon('search', 'icon-sm')}<input class="input" type="search" id="${id}" placeholder="${esc(f.placeholder || '')}"></div>`;
            }
            return `<div class="dp-field"><label for="${id}">${esc(f.label)}</label>${control}</div>`;
        }).join('');

        const inputs = filters.map((f, i) => document.getElementById(`dpFilter${i}`));

        /** Filtr qiymatlari: {param: value} — API'ga yuboriladigan ko'rinishda */
        function values() {
            const out = {};
            filters.forEach((f, i) => {
                const v = inputs[i].value.trim();
                if (v !== '') out[f.param] = v + (f.suffix || '');
            });
            return out;
        }

        function rawValues() {
            const out = {};
            filters.forEach((f, i) => { out[f.param] = inputs[i].value.trim(); });
            return out;
        }

        function updateClear() {
            const active = inputs.filter(i => i.value.trim() !== '').length;
            els.clear.hidden = !active;
            els.clear.querySelector('span').textContent = `Tozalash (${active})`;
        }

        const reload = () => { page = 1; updateClear(); load(); };
        const reloadDebounced = (() => { let t; return () => { clearTimeout(t); t = setTimeout(reload, 350); }; })();
        filters.forEach((f, i) => {
            inputs[i].addEventListener(f.type === 'text' ? 'input' : 'change', f.type === 'text' ? reloadDebounced : reload);
        });
        els.clear.addEventListener('click', () => {
            inputs.forEach(i => { i.value = ''; });
            reload();
        });

        // ===== Jadval =====
        els.head.innerHTML = `<tr>${columns.map(c => `<th class="${c.align === 'right' ? 'num' : ''}">${esc(c.label)}</th>`).join('')}</tr>`;

        function cell(column, row) {
            const raw = row[column.key];
            const formatter = typeof column.format === 'function' ? column.format : FORMATS[column.format];
            const value = formatter ? formatter(raw, row) : raw;
            if (value === null || value === undefined || value === '') return `<td class="nil">—</td>`;
            return `<td class="${column.align === 'right' ? 'num' : ''}" title="${esc(value)}">${esc(value)}</td>`;
        }

        function skeleton() {
            els.body.innerHTML = Array.from({ length: 6 }, () =>
                `<tr>${columns.map(() => `<td><div class="skeleton" style="height:14px"></div></td>`).join('')}</tr>`).join('');
        }

        async function load() {
            const current = ++loadToken;
            const params = new URLSearchParams({ page, ...values() });
            if (!els.body.children.length || els.body.querySelector('.dp-message')) skeleton();
            els.body.style.opacity = '.55';

            try {
                const result = await api(`${config.api}?${params}`);
                if (current !== loadToken) return;
                // ro'yxat odatda result.data'da; ba'zi API'larda boshqa kalitda (dataKey) yoki javobning o'zida (dataKey: null)
                const paginated = config.dataKey === null ? result : result[config.dataKey || 'data'];

                els.body.innerHTML = paginated.data.length
                    ? paginated.data.map(row => `<tr>${columns.map(c => cell(c, row)).join('')}</tr>`).join('')
                    : `<tr><td colspan="${columns.length}" class="dp-message">${icon('search')}<div>Hech qanday ma'lumot topilmadi</div></td></tr>`;
                els.total.innerHTML = `Jami: <strong>${fmt.number(paginated.total)}</strong> ta natija`;
                renderPagination(paginated);
            } catch (e) {
                if (current !== loadToken) return;
                els.body.innerHTML = `<tr><td colspan="${columns.length}" class="dp-message error">${icon('alert')}<div>${esc(e.message)}</div></td></tr>`;
            } finally {
                if (current === loadToken) els.body.style.opacity = '';
            }
        }

        function renderPagination(paginated) {
            const current = paginated.current_page;
            const last = paginated.last_page;
            if (last <= 1) {
                els.pagination.innerHTML = '';
                return;
            }

            const pages = new Set([1, last, current - 1, current, current + 1].filter(p => p >= 1 && p <= last));
            const sorted = [...pages].sort((a, b) => a - b);
            let html = `<button type="button" class="page-btn" data-page="${current - 1}" ${current === 1 ? 'disabled' : ''} aria-label="Oldingi">‹</button>`;
            sorted.forEach((p, i) => {
                if (i && p - sorted[i - 1] > 1) html += `<span class="page-gap">…</span>`;
                html += `<button type="button" class="page-btn ${p === current ? 'active' : ''}" data-page="${p}">${fmt.number(p)}</button>`;
            });
            html += `<button type="button" class="page-btn" data-page="${current + 1}" ${current === last ? 'disabled' : ''} aria-label="Keyingi">›</button>`;
            els.pagination.innerHTML = html;
        }

        els.pagination.addEventListener('click', e => {
            const btn = e.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            page = Number(btn.dataset.page);
            load();
            root.querySelector('.dp-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // ===== Fayl yuklash =====
        els.file.accept = config.accept || '.xlsx,.xls,.csv';
        els.upload.addEventListener('click', () => els.file.click());
        els.file.addEventListener('change', async () => {
            const file = els.file.files[0];
            els.file.value = '';
            if (!file) return;

            const label = els.upload.querySelector('span');
            const text = label.textContent;
            els.upload.disabled = true;
            els.upload.classList.add('loading');
            label.textContent = 'Yuklanmoqda...';

            try {
                const body = new FormData();
                body.append('file', file);
                const result = await api(config.importUrl, { method: 'POST', body });
                toast(result?.message || 'Fayl muvaffaqiyatli yuklandi');
                page = 1;
                load();
            } catch (e) {
                toast(e.message || 'Fayl yuklashda xatolik', 'error');
            } finally {
                els.upload.disabled = false;
                els.upload.classList.remove('loading');
                label.textContent = text;
            }
        });

        // ===== Excel'ga yuklab olish =====
        els.export.addEventListener('click', async () => {
            const label = els.export.querySelector('span');
            const text = label.textContent;
            els.export.disabled = true;
            label.textContent = 'Tayyorlanmoqda...';

            try {
                const response = await fetch(`${config.exportUrl}?${new URLSearchParams(values())}`, {
                    headers: { 'Authorization': `Bearer ${token}` },
                });
                if (!response.ok) throw new Error("Fayl yuklab bo'lmadi");

                const blob = await response.blob();
                const disposition = response.headers.get('content-disposition') || '';
                const serverName = decodeURIComponent((disposition.match(/filename\*=UTF-8''([^;]+)/i) || disposition.match(/filename="?([^";]+)"?/i) || [])[1] || '');
                const name = config.exportName ? config.exportName(rawValues()) : (serverName || 'export.xlsx');

                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = name;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                els.export.disabled = false;
                label.textContent = text;
            }
        });

        load();
    }

    window.DataPage = { init, formats: FORMATS };
})();
