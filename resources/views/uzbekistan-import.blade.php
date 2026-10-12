<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $dataset === 'export' ? "O'zbekiston Eksport" : "O'zbekiston Import" }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        /* ===== Faqat shu sahifa uchun ===== */
        .main-content { min-width: 0; }

        .btn-filter {
            background: white;
            color: #1976D2;
            border: 2px solid #1976D2;
        }
        .btn-filter.active {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }
        .btn-light {
            background: white;
            color: #424242;
            border: 2px solid #e0e0e0;
        }
        .btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .btn .badge {
            background: #ff6b6b;
            color: white;
            border-radius: 10px;
            padding: 0 7px;
            font-size: .8rem;
        }

        /* Asosiy filtrlar (tepadagi) */
        .main-filter {
            cursor: pointer;
            user-select: none;
            justify-content: space-between;
        }
        .main-filter .value {
            flex: 1;
            color: #999;
            font-size: .95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .main-filter.has-value { border-color: #2196F3; background: #e3f2fd; }
        .main-filter.has-value .value { color: #1a237e; font-weight: 600; }
        .main-filter .clear {
            color: #999;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0 2px;
            display: none;
        }
        .main-filter.has-value .clear { display: inline; }
        .main-filter .clear:hover { color: #c62828; }

        .filters-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
            font-size: .9rem;
            color: #616161;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e3f2fd;
            color: #1565C0;
            border-radius: 16px;
            padding: 4px 10px;
            font-weight: 600;
            font-size: .85rem;
        }
        .chip button {
            border: none;
            background: none;
            color: #1565C0;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
        }
        .link-btn {
            border: none;
            background: none;
            color: #c62828;
            cursor: pointer;
            font-weight: 600;
        }

        /* Jarayonlar (import/export progress) */
        .tasks-section {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }
        .tasks-section h3 {
            font-size: 1rem;
            color: #1a237e;
            margin-bottom: 12px;
        }
        .task {
            display: grid;
            grid-template-columns: 28px minmax(0, 340px) 1fr 130px;
            gap: 14px;
            align-items: center;
            padding: 10px 0;
            border-top: 1px solid #f0f0f0;
        }
        .task:first-of-type { border-top: none; }
        .task .icon { font-size: 1.3rem; text-align: center; }
        .task .name { font-weight: 600; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .task .meta { font-size: .8rem; color: #888; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .task .error { font-size: .8rem; color: #c62828; white-space: normal; }
        .progress {
            height: 10px;
            background: #eceff1;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, #2196F3, #1976D2);
            border-radius: 5px;
            transition: width .4s ease;
        }
        .progress-bar.indeterminate {
            width: 30% !important;
            animation: indeterminate 1.2s infinite ease-in-out;
        }
        .progress-bar.completed { background: linear-gradient(90deg, #4CAF50, #388E3C); }
        .progress-bar.failed { background: #e57373; }
        @keyframes indeterminate {
            0% { margin-left: -30%; }
            100% { margin-left: 100%; }
        }
        .progress-text { font-size: .8rem; color: #616161; margin-top: 4px; display: flex; justify-content: space-between; }
        .task .actions { display: flex; gap: 8px; align-items: center; }
        .btn-sm {
            padding: 6px 12px;
            font-size: .85rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
            color: white;
        }

        /* Jadval */
        .table-wrapper { max-height: 70vh; overflow: auto; }
        thead th {
            position: sticky;
            top: 0;
            background: #1976D2;
            z-index: 2;
            white-space: nowrap;
        }
        th .th-inner { display: flex; align-items: center; gap: 6px; }
        th .sort-mark { font-size: .75rem; opacity: .9; }
        .filter-btn {
            display: none;
            border: 1px solid rgba(255,255,255,.6);
            background: rgba(255,255,255,.15);
            color: white;
            border-radius: 4px;
            width: 22px;
            height: 20px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            padding: 0;
            flex-shrink: 0;
        }
        .filter-mode .filter-btn, .filter-btn.active { display: inline-flex; }
        .filter-btn.active { background: #FFC107; border-color: #FFC107; color: #1a237e; }
        .filter-btn svg { width: 12px; height: 12px; }
        td { white-space: nowrap; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        td.long { max-width: 320px; overflow: hidden; text-overflow: ellipsis; }
        td .empty { color: #bbb; }
        .loading-row td { text-align: center; padding: 30px; color: #999; }

        .table-footer { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .table-footer select { border: 1px solid #ddd; border-radius: 6px; padding: 4px 8px; }

        /* Ochiladigan oyna (filtr / ustunlar) */
        .popup {
            position: fixed;
            z-index: 2000;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 340px;
            max-width: calc(100vw - 20px);
            font-size: .9rem;
            color: #333;
            display: flex;
            flex-direction: column;
            max-height: min(640px, calc(100vh - 20px));
        }
        .popup-title {
            padding: 12px 15px;
            font-weight: 700;
            color: #1a237e;
            border-bottom: 1px solid #eee;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .popup-body { padding: 10px 15px; overflow-y: auto; flex: 1; }
        .popup-footer {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 10px 15px;
            border-top: 1px solid #eee;
        }
        .popup-footer .right { display: flex; gap: 8px; }
        .popup button.p-btn {
            border-radius: 6px;
            padding: 7px 14px;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid #ddd;
            background: white;
            color: #424242;
        }
        .popup button.p-btn.primary { background: #1976D2; border-color: #1976D2; color: white; }
        .popup button.p-btn.danger { color: #c62828; border-color: transparent; padding-left: 0; }
        .sort-row { display: flex; flex-direction: column; gap: 2px; margin-bottom: 8px; }
        .sort-row button {
            text-align: left;
            border: none;
            background: none;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            color: #333;
        }
        .sort-row button:hover { background: #f3f6ff; }
        .sort-row button.active { background: #e3f2fd; color: #1565C0; font-weight: 600; }
        .p-section-title {
            font-weight: 700;
            font-size: .8rem;
            color: #757575;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 10px 0 6px;
        }
        .cond-row { display: flex; gap: 6px; margin-bottom: 6px; flex-wrap: wrap; }
        .cond-row select, .cond-row input, .p-search {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 6px 8px;
            font-size: .85rem;
            outline: none;
            min-width: 0;
        }
        .cond-row select { flex: 1 1 130px; }
        .cond-row input { flex: 1 1 100px; }
        .cond-row select:focus, .cond-row input:focus, .p-search:focus { border-color: #2196F3; }
        .logic-row { display: flex; gap: 14px; margin: 4px 0 8px; font-size: .85rem; }
        .p-search { width: 100%; margin-bottom: 6px; }
        .values-list {
            border: 1px solid #eee;
            border-radius: 6px;
            max-height: 240px;
            overflow-y: auto;
            padding: 4px 0;
        }
        .values-list label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 10px;
            cursor: pointer;
        }
        .values-list label:hover { background: #f5f5f5; }
        .values-list label .v { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .values-list label .c { color: #9e9e9e; font-size: .8rem; }
        .values-list .special { font-style: italic; }
        .values-note { color: #9e9e9e; font-size: .8rem; padding: 6px 10px; }
        .columns-popup .values-list { max-height: 380px; }
        .columns-popup .quick { display: flex; gap: 8px; margin-bottom: 8px; }
        .columns-popup .quick button { flex: 1; }

        @media (max-width: 768px) {
            .task { grid-template-columns: 28px 1fr; }
            .task .progress-wrap, .task .actions { grid-column: 1 / -1; }
        }
    </style>
</head>
<body>
<!-- Hamburger Menu -->
<button class="hamburger" id="hamburger">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
            </svg>
            Dashboard
        </div>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="{{ route('uzbekistanImport') }}" class="{{ request()->routeIs('uzbekistanImport') ? 'active' : '' }}">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                O'zbekiston Import
            </a>
        </li>
        <li>
            <a href="{{ route('uzbekistanExport') }}" class="{{ request()->routeIs('uzbekistanExport') ? 'active' : '' }}">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                O'zbekiston Eksport
            </a>
        </li>
        <li>
            <a href="/dashboard">
                <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Bosh Sahifaga Qaytish
            </a>
        </li>
    </ul>
</aside>

<!-- Main Content -->
<div class="main-content">
    <div class="container">
        <!-- Header -->
        <div class="header">
            @if ($dataset === 'export')
                <h1>O'zbekiston Eksport</h1>
                <p>O'zbekistondan eksport qilingan tovarlar bo'yicha bojxona ma'lumotlari</p>
            @else
                <h1>O'zbekiston Import</h1>
                <p>Bojxona deklaratsiyalari bo'yicha import ma'lumotlari</p>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="button-group">
            <input type="file" id="fileInput" accept=".xlsx" hidden>
            <button class="btn btn-upload" id="uploadBtn">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Fayl Yuklash
            </button>
            <button class="btn btn-download" id="exportBtn">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Fayl Yuklab olish
            </button>
            <button class="btn btn-filter" id="filterModeBtn" title="Har bir ustun boshida filtr belgisini ko'rsatish (Excel'dagi kabi)">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:inherit">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
                Filtr
                <span class="badge" id="filterCount" hidden></span>
            </button>
            <button class="btn btn-light" id="columnsBtn">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:inherit">
                    <rect x="3" y="3" width="7" height="18"></rect>
                    <rect x="14" y="3" width="7" height="18"></rect>
                </svg>
                Ustunlar
            </button>
        </div>

        <!-- Import / export jarayonlari -->
        <div class="tasks-section" id="tasksSection" hidden>
            <h3>Import / Export jarayonlari</h3>
            <div id="tasksList"></div>
        </div>

        <!-- Search Section: asosiy filtrlar -->
        <div class="search-section">
            <div class="search-grid" id="mainFilters"></div>
            <div class="filters-bar" id="filtersBar" hidden></div>
        </div>

        <!-- Table -->
        <div class="table-section">
            <div class="table-wrapper" id="tableWrapper">
                <table>
                    <thead><tr id="tableHead"></tr></thead>
                    <tbody id="tableBody">
                        <tr class="loading-row"><td>Yuklanmoqda...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer">
                <span id="totalText">Jami: <strong>0</strong> ta natija</span>
                <label>
                    Sahifada:
                    <select id="perPage">
                        <option>30</option>
                        <option>50</option>
                        <option>100</option>
                        <option>200</option>
                    </select>
                </label>
            </div>
        </div>
        <div id="pagination" style="padding:15px;text-align:center;"></div>
    </div>
</div>

<script>
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = '/';
    }

    /** Sahifa qaysi ma'lumotlarni ko'rsatadi: import yoki eksport */
    const DATASET = @json($dataset);
    const DATASET_LABEL = { import: 'Import', export: 'Eksport' };

    /** Ro'yxat, filtr va export — shu ma'lumot turiga oid; fayl yuklash va vazifalar — umumiy */
    const API = `/api/uzbekistan-${DATASET}`;
    const TASKS_API = '/api/uzbekistan-import';

    // ustunlar tanlovi va boshqa sozlamalar har bir bo'lim uchun alohida saqlanadi
    const STORAGE_PREFIX = DATASET === 'export' ? 'uzbekistanExport' : 'uzbekistanImport';
    const STORAGE = {
        columns: `${STORAGE_PREFIX}.visibleColumns`,
        filterMode: `${STORAGE_PREFIX}.filterMode`,
        perPage: `${STORAGE_PREFIX}.perPage`,
    };

    /** Birinchi ochilganda ko'rinadigan ustunlar */
    const DEFAULT_COLUMNS = DATASET === 'export' ? [
        'date', 'exporter_name', 'exporter_inn', 'importer_name',
        'hs_code', 'product_description', 'quantity', 'net_weight_kg',
        'invoice_amount_usd', 'customs_value_usd', 'destination_country', 'destination',
        'transaction_method', 'transportation_method',
    ] : [
        'registration_date', 'declaration_number', 'item_no',
        'importer_name', 'importer_inn', 'importer_region',
        'exporter_standard_name', 'exporter_country',
        'hs_code', 'product_description', 'brand',
        'net_weight_kg', 'customs_amount_usd', 'origin_country',
        'trade_mode', 'transport_type', 'vehicle_number', 'trailer_number',
    ];

    /** Uzun matnli ustunlar — qisqartirib ko'rsatiladi */
    const LONG_COLUMNS = [
        'product_description', 'importer_address', 'contract_holder_address', 'exporter_address',
        'applicant_address', 'customs_office', 'exporter_street', 'contract_holder_street', 'applicant_street',
    ];

    const OPERATORS = {
        text: [
            ['equals', 'Teng'],
            ['not_equals', 'Teng emas'],
            ['contains', "O'z ichiga oladi"],
            ['not_contains', "O'z ichiga olmaydi"],
            ['starts_with', 'Bilan boshlanadi'],
            ['not_starts_with', 'Bilan boshlanmaydi'],
            ['ends_with', 'Bilan tugaydi'],
            ['not_ends_with', 'Bilan tugamaydi'],
            ['blank', "Bo'sh"],
            ['not_blank', "Bo'sh emas"],
        ],
        number: [
            ['equals', 'Teng (=)'],
            ['not_equals', 'Teng emas (≠)'],
            ['gt', 'Katta (>)'],
            ['gte', 'Katta yoki teng (≥)'],
            ['lt', 'Kichik (<)'],
            ['lte', 'Kichik yoki teng (≤)'],
            ['between', 'Oralig\'ida'],
            ['not_between', 'Oralig\'ida emas'],
            ['blank', "Bo'sh"],
            ['not_blank', "Bo'sh emas"],
        ],
        date: [
            ['equals', 'Teng'],
            ['not_equals', 'Teng emas'],
            ['gt', 'Dan keyin'],
            ['gte', 'Dan keyin yoki teng'],
            ['lt', 'Dan oldin'],
            ['lte', 'Dan oldin yoki teng'],
            ['between', 'Oralig\'ida'],
            ['not_between', 'Oralig\'ida emas'],
            ['blank', "Bo'sh"],
            ['not_blank', "Bo'sh emas"],
        ],
    };
    const NO_VALUE_OPS = ['blank', 'not_blank'];
    const TWO_VALUE_OPS = ['between', 'not_between'];

    const state = {
        columns: [],          // [{name, label, type}]
        mainFilters: [],
        visible: [],
        filters: {},          // ustun => {values, mode, blanks, conditions, logic}
        sort: [],             // ['-net_weight_kg']
        page: 1,
        perPage: Number(localStorage.getItem(STORAGE.perPage)) || 30,
        filterMode: localStorage.getItem(STORAGE.filterMode) === '1',
    };

    // ================= Yordamchi funksiyalar =================

    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    const numberFormat = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 4 });

    function formatValue(value, type) {
        if (value === null || value === undefined || value === '') return null;
        if (type === 'number') return numberFormat.format(Number(value));
        if (type === 'date') {
            const [y, m, d] = String(value).slice(0, 10).split('-');
            return `${d}.${m}.${y}`;
        }
        return String(value);
    }

    function column(name) {
        return state.columns.find(c => c.name === name);
    }

    function debounce(fn, ms) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    }

    async function api(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
        });

        if (response.status === 401) {
            localStorage.removeItem('token');
            window.location.href = '/';
            throw new Error('Unauthorized');
        }

        const data = response.headers.get('content-type')?.includes('application/json')
            ? await response.json()
            : null;

        if (!response.ok) {
            const message = data?.message || "Xatolik yuz berdi";
            throw new Error(message);
        }

        return data;
    }

    /** filters/sort holatini query string'ga: filters[col][values][]=... */
    function buildParams(params = new URLSearchParams(), { except = null } = {}) {
        for (const [name, f] of Object.entries(state.filters)) {
            if (name === except) continue;
            const prefix = `filters[${name}]`;
            (f.values || []).forEach(v => params.append(`${prefix}[values][]`, v));
            if (f.mode === 'not_in') params.append(`${prefix}[mode]`, 'not_in');
            if (f.blanks) params.append(`${prefix}[blanks]`, '1');
            (f.conditions || []).forEach((c, i) => {
                params.append(`${prefix}[conditions][${i}][op]`, c.op);
                if (c.value !== undefined && c.value !== null) params.append(`${prefix}[conditions][${i}][value]`, c.value);
                if (c.value2 !== undefined && c.value2 !== null) params.append(`${prefix}[conditions][${i}][value2]`, c.value2);
            });
            if ((f.conditions || []).length > 1 && f.logic) params.append(`${prefix}[logic]`, f.logic);
        }
        state.sort.forEach(s => params.append('sort[]', s));
        return params;
    }

    function isActive(f) {
        return !!f && ((f.values && f.values.length) || f.blanks || (f.conditions && f.conditions.length));
    }

    function setFilter(name, filter) {
        if (isActive(filter)) {
            state.filters[name] = filter;
        } else {
            delete state.filters[name];
        }
        state.page = 1;
        renderAll();
        loadData();
    }

    /** Filtrning qisqa matni: "LLC MAX", "3 ta tanlangan", "> 1000" */
    function describeFilter(name) {
        const f = state.filters[name];
        if (!isActive(f)) return null;

        const col = column(name);
        const parts = [];

        if (f.values?.length || f.blanks) {
            const items = (f.values || []).map(v => formatValue(v, col?.type));
            if (f.blanks) items.push("(Bo'sh)");
            if (f.mode === 'not_in') {
                parts.push(items.length === 1 ? `${items[0]} dan tashqari` : `${items.length} ta istisno`);
            } else {
                parts.push(items.length === 1 ? items[0] : `${items.length} ta tanlangan`);
            }
        }

        const ops = Object.fromEntries(OPERATORS[col?.type || 'text']);
        const conds = (f.conditions || []).map(c => {
            if (NO_VALUE_OPS.includes(c.op)) return ops[c.op];
            if (TWO_VALUE_OPS.includes(c.op)) return `${ops[c.op]}: ${formatValue(c.value, col?.type)} – ${formatValue(c.value2, col?.type)}`;
            return `${ops[c.op]}: ${formatValue(c.value, col?.type)}`;
        });
        if (conds.length) parts.push(conds.join(f.logic === 'or' ? ' yoki ' : ' va '));

        return parts.join('; ');
    }

    // ================= Ma'lumotlarni yuklash =================

    let loadToken = 0;

    async function loadData() {
        const current = ++loadToken;
        const params = buildParams();
        params.append('page', state.page);
        params.append('per_page', state.perPage);

        const tbody = document.getElementById('tableBody');
        tbody.style.opacity = '.5';

        try {
            const result = await api(`${API}?${params}`);
            if (current !== loadToken) return; // eskirgan javob

            renderRows(result.data);
            renderPagination(result.data);
            document.getElementById('totalText').innerHTML =
                `Jami: <strong>${numberFormat.format(result.data.total)}</strong> ta natija`;
        } catch (e) {
            if (current !== loadToken) return;
            tbody.innerHTML = `<tr class="loading-row"><td colspan="${state.visible.length}">${esc(e.message)}</td></tr>`;
        } finally {
            if (current === loadToken) tbody.style.opacity = '';
        }
    }

    // ================= Jadval =================

    function renderHead() {
        const head = document.getElementById('tableHead');
        document.querySelector('.table-section').classList.toggle('filter-mode', state.filterMode);

        head.innerHTML = state.visible.map(name => {
            const col = column(name);
            const sortIndex = state.sort.findIndex(s => s.replace('-', '') === name);
            const sortMark = sortIndex === -1 ? '' : (state.sort[sortIndex].startsWith('-') ? '▼' : '▲');
            const active = isActive(state.filters[name]);

            return `
                <th>
                    <div class="th-inner">
                        <span>${esc(col.label)}</span>
                        <span class="sort-mark">${sortMark}</span>
                        <button class="filter-btn ${active ? 'active' : ''}" data-column="${esc(name)}"
                                title="${esc(describeFilter(name) || 'Filtr')}">
                            <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                        </button>
                    </div>
                </th>`;
        }).join('');

        head.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                openColumnFilter(btn.dataset.column, btn);
            });
        });
    }

    function renderRows(paginated) {
        const tbody = document.getElementById('tableBody');

        if (!paginated.data.length) {
            tbody.innerHTML = `<tr class="loading-row"><td colspan="${state.visible.length}">Hech qanday ma'lumot topilmadi</td></tr>`;
            return;
        }

        tbody.innerHTML = paginated.data.map(item => `
            <tr>${state.visible.map(name => {
                const col = column(name);
                const formatted = formatValue(item[name], col.type);
                const classes = [col.type === 'number' ? 'num' : '', LONG_COLUMNS.includes(name) ? 'long' : ''].join(' ');
                return formatted === null
                    ? `<td class="${classes}"><span class="empty">-</span></td>`
                    : `<td class="${classes}" title="${esc(formatted)}">${esc(formatted)}</td>`;
            }).join('')}</tr>
        `).join('');
    }

    function renderPagination(paginated) {
        const paginationDiv = document.getElementById('pagination');
        paginationDiv.innerHTML = '';

        const current = paginated.current_page;
        const last = paginated.last_page;

        if (last <= 1) return;

        function createButton(page) {
            return `
                <button
                    onclick="goToPage(${page})"
                    style="
                        margin:4px;
                        padding:6px 12px;
                        border-radius:6px;
                        border:1px solid #1976D2;
                        background:${page === current ? '#1976D2' : 'white'};
                        color:${page === current ? 'white' : '#1976D2'};
                        cursor:pointer;
                    ">
                    ${page}
                </button>
            `;
        }

        paginationDiv.innerHTML += createButton(1);
        if (current > 3) paginationDiv.innerHTML += `<span style="margin:0 5px;">...</span>`;
        for (let i = current - 1; i <= current + 1; i++) {
            if (i > 1 && i < last) paginationDiv.innerHTML += createButton(i);
        }
        if (current < last - 2) paginationDiv.innerHTML += `<span style="margin:0 5px;">...</span>`;
        paginationDiv.innerHTML += createButton(last);
    }

    function goToPage(page) {
        state.page = page;
        loadData();
        document.getElementById('tableWrapper').scrollTop = 0;
    }

    // ================= Asosiy filtrlar (tepada) =================

    function renderMainFilters() {
        const box = document.getElementById('mainFilters');

        box.innerHTML = state.mainFilters.map(name => {
            const col = column(name);
            const text = describeFilter(name);
            return `
                <div class="search-box">
                    <label>${esc(col.label)}</label>
                    <div class="input-wrapper main-filter ${text ? 'has-value' : ''}" data-column="${esc(name)}">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <span class="value" title="${esc(text || '')}">${esc(text || 'Barchasi')}</span>
                        <span class="clear" title="Tozalash">&times;</span>
                    </div>
                </div>`;
        }).join('');

        box.querySelectorAll('.main-filter').forEach(el => {
            el.addEventListener('click', e => {
                e.stopPropagation();
                if (e.target.classList.contains('clear')) {
                    setFilter(el.dataset.column, null);
                    return;
                }
                openColumnFilter(el.dataset.column, el, { compact: true });
            });
        });
    }

    /** Faol filtrlar qatori va "Filtr" tugmasidagi son */
    function renderFiltersBar() {
        const bar = document.getElementById('filtersBar');
        const active = Object.keys(state.filters).filter(n => isActive(state.filters[n]));
        const badge = document.getElementById('filterCount');

        badge.hidden = !active.length;
        badge.textContent = active.length;

        const sortText = state.sort.map(s => `${column(s.replace('-', ''))?.label} ${s.startsWith('-') ? '▼' : '▲'}`).join(', ');

        if (!active.length && !state.sort.length) {
            bar.hidden = true;
            return;
        }

        bar.hidden = false;
        bar.innerHTML = `
            <span>Faol filtrlar:</span>
            ${active.map(n => `
                <span class="chip" title="${esc(describeFilter(n))}">
                    ${esc(column(n)?.label)}: ${esc(describeFilter(n))}
                    <button data-remove="${esc(n)}" title="Olib tashlash">&times;</button>
                </span>`).join('')}
            ${state.sort.length ? `<span class="chip">Saralash: ${esc(sortText)} <button data-remove-sort title="Olib tashlash">&times;</button></span>` : ''}
            <button class="link-btn" id="clearAll">Hammasini tozalash</button>
        `;

        bar.querySelectorAll('[data-remove]').forEach(b => b.addEventListener('click', () => setFilter(b.dataset.remove, null)));
        bar.querySelector('[data-remove-sort]')?.addEventListener('click', () => {
            state.sort = [];
            renderAll();
            loadData();
        });
        bar.querySelector('#clearAll').addEventListener('click', () => {
            state.filters = {};
            state.sort = [];
            state.page = 1;
            renderAll();
            loadData();
        });
    }

    function renderAll() {
        renderHead();
        renderMainFilters();
        renderFiltersBar();
        document.getElementById('filterModeBtn').classList.toggle('active', state.filterMode);
    }

    // ================= Ochiladigan oynalar =================

    let openPopup = null;
    let popupOpenedAt = 0;

    function closePopup() {
        openPopup?.remove();
        openPopup = null;
    }

    function placePopup(popup, anchor) {
        document.body.appendChild(popup);
        const rect = anchor.getBoundingClientRect();
        const width = popup.offsetWidth;
        let left = Math.min(rect.left, window.innerWidth - width - 10);
        left = Math.max(10, left);
        // qiymatlar yuklangach oyna o'sadi — balandlikni ekranda qolgan joy bilan cheklaymiz,
        // joy kam bo'lsa oynani yuqoriga suramiz; OK tugmasi doim ko'rinadi
        const wanted = Math.min(640, window.innerHeight - 20);
        let top = rect.bottom + 6;
        if (window.innerHeight - top - 10 < Math.min(wanted, 420)) {
            top = Math.max(10, window.innerHeight - wanted - 10);
        }
        popup.style.left = `${left}px`;
        popup.style.top = `${top}px`;
        popup.style.maxHeight = `${window.innerHeight - top - 10}px`;
        openPopup = popup;
        popupOpenedAt = Date.now();
    }

    document.addEventListener('click', e => {
        if (openPopup && !openPopup.contains(e.target)) closePopup();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closePopup();
    });
    window.addEventListener('resize', closePopup);
    // jadval aylantirilsa oyna yopiladi (ochilish paytidagi avtomatik scroll bundan mustasno)
    document.getElementById('tableWrapper').addEventListener('scroll', () => {
        if (Date.now() - popupOpenedAt > 300) closePopup();
    });

    /**
     * Excel'dagi filtr oynasi: saralash, shartlar (Custom Filter) va qiymatlar ro'yxati.
     * compact: true — faqat qiymatlar ro'yxati (tepadagi asosiy filtrlar uchun).
     */
    function openColumnFilter(name, anchor, { compact = false } = {}) {
        closePopup();

        const col = column(name);
        const current = state.filters[name] || {};
        const ops = OPERATORS[col.type];
        const inputType = col.type === 'date' ? 'date' : (col.type === 'number' ? 'number' : 'text');

        // Galochkalar holati (Excel'dagi kabi): selectAll + istisnolar
        const hasValueFilter = (current.values?.length || current.blanks);
        const list = {
            selectAll: !hasValueFilter || current.mode === 'not_in',
            exceptions: new Set(hasValueFilter ? current.values || [] : []),
            blanksChecked: !hasValueFilter ? true : (current.mode === 'not_in' ? !current.blanks : !!current.blanks),
            options: [],
            blanksCount: 0,
            hasMore: false,
            search: '',
        };
        const isChecked = v => list.selectAll ? !list.exceptions.has(v) : list.exceptions.has(v);

        const conds = current.conditions || [];
        const condRow = (i) => {
            const c = conds[i] || {};
            return `
                <div class="cond-row" data-index="${i}">
                    <select class="op">
                        <option value="">— tanlang —</option>
                        ${ops.map(([value, label]) => `<option value="${value}" ${c.op === value ? 'selected' : ''}>${esc(label)}</option>`).join('')}
                    </select>
                    <input class="v1" type="${inputType}" step="any" value="${esc(c.value ?? '')}" placeholder="Qiymat">
                    <input class="v2" type="${inputType}" step="any" value="${esc(c.value2 ?? '')}" placeholder="gacha">
                </div>`;
        };

        const popup = document.createElement('div');
        popup.className = 'popup';
        popup.addEventListener('click', e => e.stopPropagation());
        popup.innerHTML = `
            <div class="popup-title">${esc(col.label)}</div>
            <div class="popup-body">
                ${compact ? '' : `
                    <div class="sort-row">
                        <button data-sort="${esc(name)}">${col.type === 'number' ? 'Kichikdan kattaga ↑' : (col.type === 'date' ? 'Eskidan yangiga ↑' : 'A dan Z gacha ↑')}</button>
                        <button data-sort="-${esc(name)}">${col.type === 'number' ? 'Kattadan kichikka ↓' : (col.type === 'date' ? 'Yangidan eskiga ↓' : 'Z dan A gacha ↓')}</button>
                    </div>
                    <div class="p-section-title">${col.type === 'number' ? 'Raqam filtrlari' : (col.type === 'date' ? 'Sana filtrlari' : 'Matn filtrlari')}</div>
                    ${condRow(0)}
                    <div class="logic-row">
                        <label><input type="radio" name="logic" value="and" ${current.logic !== 'or' ? 'checked' : ''}> VA</label>
                        <label><input type="radio" name="logic" value="or" ${current.logic === 'or' ? 'checked' : ''}> YOKI</label>
                    </div>
                    ${condRow(1)}
                    <div class="p-section-title">Qiymatlar</div>
                `}
                <input class="p-search" type="search" placeholder="Qidirish...">
                <div class="values-list"><div class="values-note">Yuklanmoqda...</div></div>
            </div>
            <div class="popup-footer">
                <button class="p-btn danger" data-action="clear">Filtrni tozalash</button>
                <div class="right">
                    <button class="p-btn" data-action="cancel">Bekor qilish</button>
                    <button class="p-btn primary" data-action="ok">OK</button>
                </div>
            </div>
        `;

        // Saralash tugmalari
        popup.querySelectorAll('[data-sort]').forEach(b => {
            if (state.sort[0] === b.dataset.sort) b.classList.add('active');
            b.addEventListener('click', () => {
                state.sort = state.sort[0] === b.dataset.sort ? [] : [b.dataset.sort];
                state.page = 1;
                closePopup();
                renderAll();
                loadData();
            });
        });

        // "oralig'ida" tanlansa ikkinchi qiymat maydoni chiqadi
        popup.querySelectorAll('.cond-row').forEach(row => {
            const sync = () => {
                const op = row.querySelector('.op').value;
                row.querySelector('.v1').style.display = !op || NO_VALUE_OPS.includes(op) ? 'none' : '';
                row.querySelector('.v2').style.display = TWO_VALUE_OPS.includes(op) ? '' : 'none';
                row.querySelector('.v1').placeholder = TWO_VALUE_OPS.includes(op) ? 'dan' : 'Qiymat';
            };
            row.querySelector('.op').addEventListener('change', sync);
            sync();
        });

        // Qiymatlar ro'yxati
        const listEl = popup.querySelector('.values-list');

        function renderList() {
            const visibleChecked = list.options.filter(o => isChecked(o.value)).length;
            const allChecked = !list.search
                ? list.selectAll && list.exceptions.size === 0 && list.blanksChecked
                : visibleChecked === list.options.length;

            let html = `
                <label class="special">
                    <input type="checkbox" data-all ${allChecked ? 'checked' : ''}>
                    <span class="v">(Hammasini belgilash)</span>
                </label>`;

            html += list.options.map((o, i) => `
                <label>
                    <input type="checkbox" data-i="${i}" ${isChecked(o.value) ? 'checked' : ''}>
                    <span class="v" title="${esc(formatValue(o.value, col.type))}">${esc(formatValue(o.value, col.type))}</span>
                    <span class="c">${numberFormat.format(o.count)}</span>
                </label>`).join('');

            if (!list.search && list.blanksCount > 0) {
                html += `
                    <label class="special">
                        <input type="checkbox" data-blanks ${list.blanksChecked ? 'checked' : ''}>
                        <span class="v">(Bo'sh)</span>
                        <span class="c">${numberFormat.format(list.blanksCount)}</span>
                    </label>`;
            }

            if (!list.options.length && !(list.blanksCount && !list.search)) {
                html += `<div class="values-note">Qiymat topilmadi</div>`;
            }
            if (list.hasMore) {
                html += `<div class="values-note">Faqat birinchi ${list.options.length} ta qiymat ko'rsatilgan — qidiruvdan foydalaning</div>`;
            }

            listEl.innerHTML = html;

            listEl.querySelector('[data-all]').addEventListener('change', e => {
                if (list.search) {
                    // qidiruv natijalarini birdaniga belgilash / olib tashlash
                    list.options.forEach(o => {
                        if (isChecked(o.value) !== e.target.checked) toggleValue(o.value);
                    });
                } else {
                    list.selectAll = e.target.checked;
                    list.exceptions.clear();
                    list.blanksChecked = e.target.checked;
                }
                renderList();
            });
            listEl.querySelectorAll('[data-i]').forEach(cb => {
                cb.addEventListener('change', () => {
                    toggleValue(list.options[cb.dataset.i].value);
                    renderList();
                });
            });
            listEl.querySelector('[data-blanks]')?.addEventListener('change', e => {
                list.blanksChecked = e.target.checked;
                renderList();
            });
        }

        function toggleValue(value) {
            list.exceptions.has(value) ? list.exceptions.delete(value) : list.exceptions.add(value);
        }

        async function loadOptions() {
            const params = buildParams(new URLSearchParams(), { except: name });
            params.append('field', name);
            params.append('limit', 1000);
            if (list.search) params.append('q', list.search);

            try {
                const result = await api(`${API}/filter-options?${params}`);
                list.options = result.data.values;
                list.blanksCount = result.data.blanks;
                list.hasMore = result.data.has_more;
                renderList();
            } catch (e) {
                listEl.innerHTML = `<div class="values-note">${esc(e.message)}</div>`;
            }
        }

        popup.querySelector('.p-search').addEventListener('input', debounce(e => {
            list.search = e.target.value.trim();
            loadOptions();
        }, 300));

        // Tugmalar
        popup.querySelector('[data-action="cancel"]').addEventListener('click', closePopup);
        popup.querySelector('[data-action="clear"]').addEventListener('click', () => {
            closePopup();
            setFilter(name, null);
        });
        popup.querySelector('[data-action="ok"]').addEventListener('click', () => {
            const filter = {};

            // 1) Galochkalar
            if (list.search) {
                // Excel'dagidek: qidiruvda faqat belgilangan topilgan qiymatlar tanlanadi
                const values = list.options.filter(o => isChecked(o.value)).map(o => o.value);
                if (!values.length) {
                    alert('Kamida bitta qiymatni belgilang');
                    return;
                }
                Object.assign(filter, { mode: 'in', values });
            } else if (list.selectAll) {
                if (list.exceptions.size || !list.blanksChecked) {
                    Object.assign(filter, { mode: 'not_in', values: [...list.exceptions], blanks: !list.blanksChecked });
                }
            } else {
                if (!list.exceptions.size && !list.blanksChecked) {
                    alert('Kamida bitta qiymatni belgilang');
                    return;
                }
                Object.assign(filter, { mode: 'in', values: [...list.exceptions], blanks: list.blanksChecked });
            }

            // 2) Shartlar (compact oynada — avvalgisi saqlanadi)
            if (compact) {
                filter.conditions = current.conditions || [];
                filter.logic = current.logic;
            } else {
                filter.conditions = [];
                for (const row of popup.querySelectorAll('.cond-row')) {
                    const op = row.querySelector('.op').value;
                    if (!op) continue;
                    const value = row.querySelector('.v1').value.trim();
                    const value2 = row.querySelector('.v2').value.trim();
                    if (!NO_VALUE_OPS.includes(op) && value === '') continue;
                    if (TWO_VALUE_OPS.includes(op) && value2 === '') {
                        alert("Oraliqning ikkinchi qiymatini kiriting");
                        return;
                    }
                    const condition = { op };
                    if (!NO_VALUE_OPS.includes(op)) condition.value = value;
                    if (TWO_VALUE_OPS.includes(op)) condition.value2 = value2;
                    filter.conditions.push(condition);
                }
                filter.logic = popup.querySelector('input[name="logic"]:checked').value;
            }

            closePopup();
            setFilter(name, filter);
        });

        placePopup(popup, anchor);
        popup.querySelector('.p-search').focus({ preventScroll: true });
        loadOptions();
    }

    // ================= Ustunlarni tanlash =================

    function openColumnsPopup(anchor) {
        closePopup();

        const selected = new Set(state.visible);
        const popup = document.createElement('div');
        popup.className = 'popup columns-popup';
        popup.addEventListener('click', e => e.stopPropagation());
        popup.innerHTML = `
            <div class="popup-title">Ko'rinadigan ustunlar</div>
            <div class="popup-body">
                <div class="quick">
                    <button class="p-btn" data-quick="all">Hammasi</button>
                    <button class="p-btn" data-quick="default">Standart</button>
                    <button class="p-btn" data-quick="none">Hech biri</button>
                </div>
                <input class="p-search" type="search" placeholder="Ustun qidirish...">
                <div class="values-list"></div>
            </div>
            <div class="popup-footer">
                <span style="color:#757575;align-self:center" class="count"></span>
                <div class="right">
                    <button class="p-btn" data-action="cancel">Bekor qilish</button>
                    <button class="p-btn primary" data-action="ok">OK</button>
                </div>
            </div>
        `;

        const listEl = popup.querySelector('.values-list');
        let search = '';

        function renderList() {
            const q = search.toLowerCase();
            listEl.innerHTML = state.columns
                .filter(c => !q || c.label.toLowerCase().includes(q) || c.name.includes(q))
                .map(c => `
                    <label>
                        <input type="checkbox" data-name="${esc(c.name)}" ${selected.has(c.name) ? 'checked' : ''}>
                        <span class="v">${esc(c.label)}</span>
                    </label>`).join('');
            listEl.querySelectorAll('input').forEach(cb => cb.addEventListener('change', () => {
                cb.checked ? selected.add(cb.dataset.name) : selected.delete(cb.dataset.name);
                updateCount();
            }));
            updateCount();
        }

        function updateCount() {
            popup.querySelector('.count').textContent = `${selected.size} / ${state.columns.length}`;
        }

        popup.querySelector('.p-search').addEventListener('input', e => {
            search = e.target.value.trim();
            renderList();
        });
        popup.querySelectorAll('[data-quick]').forEach(b => b.addEventListener('click', () => {
            selected.clear();
            if (b.dataset.quick === 'all') state.columns.forEach(c => selected.add(c.name));
            if (b.dataset.quick === 'default') DEFAULT_COLUMNS.forEach(c => selected.add(c));
            renderList();
        }));
        popup.querySelector('[data-action="cancel"]').addEventListener('click', closePopup);
        popup.querySelector('[data-action="ok"]').addEventListener('click', () => {
            if (!selected.size) {
                alert('Kamida bitta ustunni tanlang');
                return;
            }
            // jadval tartibi — ustunlarning asl tartibida
            state.visible = state.columns.map(c => c.name).filter(n => selected.has(n));
            localStorage.setItem(STORAGE.columns, JSON.stringify(state.visible));
            closePopup();
            renderAll();
            loadData();
        });

        renderList();
        placePopup(popup, anchor);
    }

    // ================= Import / Export jarayonlari =================

    const tasks = new Map();      // id => task
    const autoDownload = new Set(); // shu sahifada boshlangan exportlar — tayyor bo'lsa avtomatik yuklab olinadi
    let uploadProgress = null;    // {name, percent}
    let pollTimer = null;

    const STATUS_TEXT = {
        pending: 'Navbatda',
        processing: 'Bajarilmoqda',
        completed: 'Tayyor',
        failed: 'Xatolik',
    };

    function renderTasks() {
        const section = document.getElementById('tasksSection');
        const list = document.getElementById('tasksList');
        const items = [...tasks.values()].sort((a, b) => b.id - a.id).slice(0, 5);

        section.hidden = !items.length && !uploadProgress;

        let html = '';

        if (uploadProgress) {
            html += `
                <div class="task">
                    <div class="icon">⬆️</div>
                    <div>
                        <div class="name" title="${esc(uploadProgress.name)}">${esc(uploadProgress.name)}</div>
                        <div class="meta">Fayl serverga yuborilmoqda</div>
                    </div>
                    <div class="progress-wrap">
                        <div class="progress"><div class="progress-bar" style="width:${uploadProgress.percent}%"></div></div>
                        <div class="progress-text"><span>Yuborilmoqda</span><span>${uploadProgress.percent}%</span></div>
                    </div>
                    <div class="actions"></div>
                </div>`;
        }

        html += items.map(t => {
            const isImport = t.type === 'import'; // fayl yuklash (aks holda — Excel'ga yuklab olish)
            const dataset = t.dataset || 'import';
            const percent = t.status === 'completed' ? 100 : (t.progress ?? 0);
            const indeterminate = t.status === 'pending' || (t.status === 'processing' && t.progress === null);
            const barClass = t.status === 'completed' ? 'completed' : (t.status === 'failed' ? 'failed' : (indeterminate ? 'indeterminate' : ''));
            const rows = t.total_rows
                ? `${numberFormat.format(t.processed_rows)} / ${numberFormat.format(t.total_rows)} qator`
                : (t.processed_rows ? `${numberFormat.format(t.processed_rows)} qator` : '');
            const waitingLong = t.status === 'pending' && (Date.now() - new Date(t.created_at).getTime() > 30000);
            const time = new Date(t.created_at).toLocaleString('ru-RU');

            return `
                <div class="task">
                    <div class="icon">${isImport ? '📥' : '📤'}</div>
                    <div style="min-width:0">
                        <div class="name" title="${esc(t.original_name || '')}">${isImport ? 'Yuklash' : 'Yuklab olish'} · ${DATASET_LABEL[dataset]} ma'lumotlari</div>
                        <div class="meta" title="${esc(t.original_name || '')}">${esc(t.original_name || '')}</div>
                        <div class="meta">${esc(time)}</div>
                        ${isImport && dataset !== DATASET
                            ? `<div class="meta"><a href="/uzbekistan-${dataset}">${DATASET_LABEL[dataset]} bo'limida ko'rish →</a></div>`
                            : ''}
                    </div>
                    <div class="progress-wrap">
                        <div class="progress"><div class="progress-bar ${barClass}" style="width:${percent}%"></div></div>
                        <div class="progress-text">
                            <span>${STATUS_TEXT[t.status] || t.status}${rows ? ' · ' + rows : ''}</span>
                            <span>${t.status === 'failed' ? '' : (indeterminate ? '' : percent + '%')}</span>
                        </div>
                        ${t.status === 'failed' ? `<div class="error">${esc(t.error || '')}</div>` : ''}
                        ${waitingLong ? `<div class="error">Navbatda uzoq turibdi — queue worker ishlayotganini tekshiring</div>` : ''}
                    </div>
                    <div class="actions">
                        ${!isImport && t.status === 'completed' ? `<button class="btn-sm" data-download="${t.id}">Yuklab olish</button>` : ''}
                    </div>
                </div>`;
        }).join('');

        list.innerHTML = html;
        list.querySelectorAll('[data-download]').forEach(b => b.addEventListener('click', () => downloadExport(tasks.get(Number(b.dataset.download)))));
    }

    async function loadTasks() {
        try {
            const result = await api(`${TASKS_API}/tasks`);
            result.data.data.slice(0, 5).forEach(t => tasks.set(t.id, t));
            renderTasks();
            schedulePoll();
        } catch (e) {
            console.error(e);
        }
    }

    function schedulePoll() {
        clearTimeout(pollTimer);
        const running = [...tasks.values()].some(t => t.status === 'pending' || t.status === 'processing');
        if (running) pollTimer = setTimeout(pollTasks, 1500);
    }

    async function pollTasks() {
        const running = [...tasks.values()].filter(t => t.status === 'pending' || t.status === 'processing');

        await Promise.all(running.map(async t => {
            try {
                const result = await api(`${TASKS_API}/tasks/${t.id}`);
                const updated = result.data;
                tasks.set(updated.id, updated);

                if (updated.status === 'completed' && t.status !== 'completed') {
                    if (updated.type === 'import') {
                        // yangi ma'lumotlar jadvalda ko'rinsin (shu bo'limga tegishli bo'lsa)
                        if ((updated.dataset || 'import') === DATASET) loadData();
                    } else if (autoDownload.has(updated.id)) {
                        autoDownload.delete(updated.id);
                        downloadExport(updated);
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }));

        renderTasks();
        schedulePoll();
    }

    function upload(file) {
        const formData = new FormData();
        formData.append('file', file);

        uploadProgress = { name: file.name, percent: 0 };
        renderTasks();

        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.disabled = true;

        // fetch yuklash foizini bermaydi — XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', `${TASKS_API}/import`);
        xhr.setRequestHeader('Authorization', `Bearer ${token}`);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', e => {
            if (!e.lengthComputable) return;
            uploadProgress.percent = Math.round(e.loaded * 100 / e.total);
            renderTasks();
        });

        xhr.addEventListener('load', () => {
            uploadProgress = null;
            uploadBtn.disabled = false;
            let result = null;
            try { result = JSON.parse(xhr.responseText); } catch (e) {}

            if (xhr.status === 202 && result?.data) {
                tasks.set(result.data.id, result.data);
                renderTasks();
                schedulePoll();
            } else {
                renderTasks();
                const message = result?.errors?.file?.[0] || result?.message
                    || (xhr.status === 413 ? 'Fayl hajmi juda katta (server chegarasi)' : 'Fayl yuklashda xatolik');
                alert(message);
            }
        });

        xhr.addEventListener('error', () => {
            uploadProgress = null;
            uploadBtn.disabled = false;
            renderTasks();
            alert("Server bilan bog'lanishda xatolik!");
        });

        xhr.send(formData);
    }

    async function startExport() {
        const btn = document.getElementById('exportBtn');
        btn.disabled = true;

        try {
            const body = { filters: {}, sort: state.sort };
            for (const [name, f] of Object.entries(state.filters)) {
                body.filters[name] = {
                    values: (f.values || []).map(String),
                    mode: f.mode || 'in',
                    blanks: f.blanks ? 1 : 0,
                    conditions: f.conditions || [],
                    logic: f.logic || 'and',
                };
            }

            const result = await api(`${API}/export`, { method: 'POST', body: JSON.stringify(body) });
            tasks.set(result.data.id, result.data);
            autoDownload.add(result.data.id);
            renderTasks();
            schedulePoll();
        } catch (e) {
            alert(e.message);
        } finally {
            btn.disabled = false;
        }
    }

    async function downloadExport(task) {
        if (!task) return;

        try {
            const response = await fetch(`${TASKS_API}/tasks/${task.id}/download`, {
                headers: { 'Authorization': `Bearer ${token}` },
            });

            if (!response.ok) {
                alert("Fayl yuklab bo'lmadi");
                return;
            }

            const blob = await response.blob();
            const downloadUrl = window.URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = task.original_name || 'export.xlsx';
            document.body.appendChild(a);
            a.click();
            a.remove();

            window.URL.revokeObjectURL(downloadUrl);
        } catch (e) {
            console.error(e);
            alert('Xatolik yuz berdi');
        }
    }

    // ================= Tugmalar =================

    document.getElementById('uploadBtn').addEventListener('click', () => document.getElementById('fileInput').click());
    document.getElementById('fileInput').addEventListener('change', function () {
        const file = this.files[0];
        this.value = '';
        if (file) upload(file);
    });
    document.getElementById('exportBtn').addEventListener('click', startExport);
    document.getElementById('filterModeBtn').addEventListener('click', () => {
        state.filterMode = !state.filterMode;
        localStorage.setItem(STORAGE.filterMode, state.filterMode ? '1' : '0');
        renderAll();
    });
    document.getElementById('columnsBtn').addEventListener('click', e => {
        e.stopPropagation();
        openColumnsPopup(e.currentTarget);
    });
    const perPageSelect = document.getElementById('perPage');
    perPageSelect.value = String(state.perPage);
    perPageSelect.addEventListener('change', () => {
        state.perPage = Number(perPageSelect.value);
        localStorage.setItem(STORAGE.perPage, state.perPage);
        state.page = 1;
        loadData();
    });

    // Hamburger menu toggle
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');

    hamburger.addEventListener('click', function () {
        hamburger.classList.toggle('active');
        sidebar.classList.toggle('active');
    });

    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                hamburger.classList.remove('active');
                sidebar.classList.remove('active');
            }
        });
    });

    // ================= Boshlash =================

    (async function init() {
        try {
            const meta = await api(`${API}/columns`);
            state.columns = meta.columns;
            state.mainFilters = meta.main_filters;

            const names = state.columns.map(c => c.name);
            let saved = [];
            try { saved = JSON.parse(localStorage.getItem(STORAGE.columns) || '[]'); } catch (e) {}
            saved = (Array.isArray(saved) ? saved : []).filter(n => names.includes(n));
            state.visible = saved.length ? saved : DEFAULT_COLUMNS.filter(n => names.includes(n));

            renderAll();
            loadData();
            loadTasks();
        } catch (e) {
            document.getElementById('tableBody').innerHTML =
                `<tr class="loading-row"><td>${esc(e.message)}</td></tr>`;
        }
    })();
</script>
</body>
</html>
