@extends('layouts.app')

@section('title', $dataset === 'export' ? "O'zbekiston eksport" : "O'zbekiston import")

@push('styles')
<style>
    /* ===== O'zbekiston import / eksport sahifasi (ranglar — ui.css tokenlari) ===== */

    .toolbar { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn .count-badge {
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 10px;
        background: var(--primary);
        color: var(--on-primary);
        font-size: .72rem;
        display: inline-grid;
        place-items: center;
    }
    .btn.toggle-on { background: var(--primary-soft); border-color: var(--primary); color: var(--primary-text); }

    /* Asosiy filtrlar (tepada) */
    .filters-card { padding: 16px 20px; margin-bottom: 16px; }
    .main-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
    .main-filter-box { min-width: 0; }
    .main-filter-box label { display: block; font-size: .78rem; font-weight: 600; color: var(--text-2); margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .main-filter {
        display: flex;
        align-items: center;
        gap: 8px;
        height: 38px;
        padding: 0 10px;
        border: 1px solid var(--border-strong);
        border-radius: var(--radius-sm);
        background: var(--surface);
        cursor: pointer;
        user-select: none;
        transition: border-color .15s, background .15s;
    }
    .main-filter:hover { border-color: var(--primary); }
    .main-filter .icon { color: var(--muted); }
    .main-filter .value { flex: 1; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .main-filter.has-value { border-color: var(--primary); background: var(--primary-soft); }
    .main-filter.has-value .value { color: var(--primary-text); font-weight: 600; }
    .main-filter .clear { color: var(--muted); font-size: 1.1rem; line-height: 1; padding: 0 2px; display: none; }
    .main-filter.has-value .clear { display: inline; }
    .main-filter .clear:hover { color: var(--danger); }

    .filters-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 14px; font-size: .85rem; color: var(--text-2); }
    .filters-bar .chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--primary-soft);
        color: var(--primary-text);
        border-radius: 999px;
        padding: 3px 10px;
        font-weight: 600;
        font-size: .82rem;
    }
    .filters-bar .chip button { border: none; background: none; color: inherit; cursor: pointer; font-size: 1rem; line-height: 1; padding: 0; }
    .link-btn { border: none; background: none; color: var(--danger); cursor: pointer; font-weight: 600; }

    /* Jarayonlar (import/export progress) */
    .tasks-section { padding: 14px 20px; margin-bottom: 16px; }
    .tasks-section h3 { font-size: .95rem; font-weight: 600; }
    .tasks-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 4px; }
    .btn-outline {
        height: 30px;
        padding: 0 12px;
        font-size: .82rem;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border-strong);
        background: var(--surface);
        color: var(--text);
        cursor: pointer;
        font-weight: 600;
        white-space: nowrap;
    }
    .btn-outline:hover { background: var(--surface-3); }
    .btn-outline:disabled { opacity: .6; cursor: wait; }
    #tasksList.expanded { max-height: 420px; overflow-y: auto; padding-right: 4px; }
    .tasks-more { text-align: center; padding-top: 8px; }
    .task {
        display: grid;
        grid-template-columns: 28px minmax(0, 340px) 1fr 130px;
        gap: 14px;
        align-items: center;
        padding: 10px 0;
        border-top: 1px solid var(--border);
    }
    .task:first-of-type { border-top: none; }
    .task .task-icon { font-size: 1.3rem; text-align: center; }
    .task .name { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .task .meta { font-size: .8rem; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .task .error { font-size: .8rem; color: var(--danger); white-space: normal; }
    .task .actions { display: flex; gap: 8px; align-items: center; justify-content: flex-end; }
    .progress { height: 8px; background: var(--surface-3); border-radius: 4px; overflow: hidden; }
    .progress-bar { height: 100%; width: 0; background: var(--primary); border-radius: 4px; transition: width .4s ease; }
    .progress-bar.indeterminate { width: 30% !important; animation: indeterminate 1.2s infinite ease-in-out; }
    .progress-bar.completed { background: var(--success); }
    .progress-bar.failed { background: var(--danger); }
    @keyframes indeterminate { 0% { margin-left: -30%; } 100% { margin-left: 100%; } }
    .progress-text { font-size: .8rem; color: var(--text-2); margin-top: 4px; display: flex; justify-content: space-between; }

    /* Jadval */
    .table-card { overflow: hidden; }
    .table-wrapper { max-height: 70vh; overflow: auto; }
    .data-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .86rem; }
    .data-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--surface-2);
        color: var(--text-2);
        font-weight: 600;
        font-size: .78rem;
        text-align: left;
        padding: 10px 12px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }
    .data-table td { padding: 9px 12px; border-bottom: 1px solid var(--border); white-space: nowrap; }
    .data-table tbody tr:hover td { background: var(--surface-2); }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .data-table td.long { max-width: 320px; overflow: hidden; text-overflow: ellipsis; }
    .data-table td .nil { color: var(--muted); }
    .loading-row td { text-align: center; padding: 40px; color: var(--muted); }
    th .th-inner { display: flex; align-items: center; gap: 6px; }
    th .sort-mark { font-size: .72rem; color: var(--primary-text); }
    .filter-btn {
        display: none;
        border: 1px solid var(--border-strong);
        background: var(--surface);
        color: var(--muted);
        border-radius: 5px;
        width: 22px;
        height: 20px;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        padding: 0;
        flex-shrink: 0;
    }
    .filter-btn:hover { color: var(--primary-text); border-color: var(--primary); }
    .filter-mode .filter-btn, .filter-btn.active { display: inline-flex; }
    .filter-btn.active { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
    .filter-btn svg { width: 11px; height: 11px; }

    .table-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 12px 16px;
        border-top: 1px solid var(--border);
        color: var(--text-2);
        font-size: .88rem;
    }
    .table-footer select { height: 30px; border: 1px solid var(--border-strong); border-radius: 6px; padding: 0 8px; background: var(--surface); }
    .pagination { display: flex; justify-content: center; align-items: center; gap: 4px; flex-wrap: wrap; padding: 16px 0 4px; }
    .page-btn {
        min-width: 34px;
        height: 34px;
        padding: 0 8px;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text);
        cursor: pointer;
        font-weight: 600;
    }
    .page-btn:hover { border-color: var(--primary); color: var(--primary-text); }
    .page-btn.active { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
    .page-gap { color: var(--muted); padding: 0 4px; }

    /* Ochiladigan oyna (filtr / ustunlar / modal) */
    .popup {
        position: fixed;
        z-index: 2000;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        width: 340px;
        max-width: calc(100vw - 20px);
        font-size: .88rem;
        color: var(--text);
        display: flex;
        flex-direction: column;
        max-height: min(640px, calc(100vh - 20px));
    }
    .popup-title { padding: 12px 16px; font-weight: 600; border-bottom: 1px solid var(--border); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .popup-body { padding: 10px 16px; overflow-y: auto; flex: 1; }
    .popup-footer { display: flex; justify-content: space-between; gap: 8px; padding: 10px 16px; border-top: 1px solid var(--border); }
    .popup-footer .right { display: flex; gap: 8px; }
    .popup button.p-btn {
        height: 34px;
        border-radius: var(--radius-sm);
        padding: 0 14px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--border-strong);
        background: var(--surface);
        color: var(--text);
    }
    .popup button.p-btn:hover { background: var(--surface-3); }
    .popup button.p-btn.primary { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
    .popup button.p-btn.danger { color: var(--danger); border-color: transparent; padding-left: 0; background: none; }
    .sort-row { display: flex; flex-direction: column; gap: 2px; margin-bottom: 8px; }
    .sort-row button { text-align: left; border: none; background: none; padding: 6px 8px; border-radius: 6px; cursor: pointer; color: var(--text); }
    .sort-row button:hover { background: var(--surface-3); }
    .sort-row button.active { background: var(--primary-soft); color: var(--primary-text); font-weight: 600; }
    .p-section-title { font-weight: 700; font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin: 10px 0 6px; }
    .cond-row { display: flex; gap: 6px; margin-bottom: 6px; flex-wrap: wrap; }
    .cond-row select, .cond-row input, .p-search {
        height: 34px;
        border: 1px solid var(--border-strong);
        border-radius: 6px;
        padding: 0 8px;
        font-size: .85rem;
        outline: none;
        min-width: 0;
        background: var(--surface);
    }
    .cond-row select { flex: 1 1 130px; }
    .cond-row input { flex: 1 1 100px; }
    .cond-row select:focus, .cond-row input:focus, .p-search:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
    .logic-row { display: flex; gap: 14px; margin: 4px 0 8px; font-size: .85rem; }
    .p-search { width: 100%; margin-bottom: 6px; }
    .values-list { border: 1px solid var(--border); border-radius: 6px; max-height: 240px; overflow-y: auto; padding: 4px 0; }
    .values-list label { display: flex; align-items: center; gap: 8px; padding: 4px 10px; cursor: pointer; }
    .values-list label:hover { background: var(--surface-3); }
    .values-list label .v { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .values-list label .c { color: var(--muted); font-size: .8rem; }
    .values-list .special { font-style: italic; }
    .values-list input[type="checkbox"], .logic-row input { accent-color: var(--primary); }
    .values-note { color: var(--muted); font-size: .8rem; padding: 6px 10px; }
    .columns-popup .values-list { max-height: 380px; }
    .columns-popup .quick { display: flex; gap: 8px; margin-bottom: 8px; }
    .columns-popup .quick button { flex: 1; }

    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: var(--overlay);
        z-index: 3000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
    }
    .popup.modal { position: relative; width: 600px; max-height: calc(100vh - 20px); }
    .modal .values-list { max-height: 260px; padding: 0; }

    /* Firmalar oynasi */
    .popup.modal.firms-modal { width: 900px; height: min(780px, calc(100vh - 20px)); }
    .firms-modal .popup-title { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .firms-modal .popup-title .close { border: none; background: none; font-size: 1.4rem; line-height: 1; color: var(--muted); cursor: pointer; }
    .firms-summary { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
    .firms-summary .stat { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 6px 12px; font-size: .8rem; color: var(--text-2); }
    .firms-summary .stat strong { display: block; font-size: 1.05rem; color: var(--text); }
    .firms-note { font-size: .8rem; color: var(--warning); margin-bottom: 8px; }
    .firms-totals { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .firms-totals .stat { flex: 1 1 140px; background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 12px; font-size: .8rem; color: var(--text-2); }
    .firms-totals .stat strong { display: block; font-size: 1.25rem; color: var(--text); }
    .firms-totals .stat.primary { background: var(--primary-soft); border-color: transparent; }
    .firms-totals .stat.primary strong { color: var(--primary-text); }
    .firms-modal .firm-tabs { margin-bottom: 10px; }
    .also-chip { display: inline-block; background: var(--warning-soft); color: var(--warning); border-radius: 10px; padding: 0 8px; font-weight: 600; }
    .also-chip:hover { text-decoration: none; filter: brightness(.95); }
    .firm-card {
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 10px 12px;
        margin-bottom: 8px;
        display: grid;
        grid-template-columns: 34px minmax(0, 1fr) auto;
        gap: 4px 10px;
    }
    .firm-card:hover { border-color: var(--border-strong); }
    .firm-card .rank {
        grid-row: span 2;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--primary-soft);
        color: var(--primary-text);
        font-weight: 700;
        font-size: .8rem;
        display: grid;
        place-items: center;
    }
    .firm-card .title { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .firm-card .side { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; grid-column: 3; grid-row: 1 / span 3; }
    .firm-card .rows { font-weight: 700; white-space: nowrap; text-align: right; }
    .firm-card .sub { font-size: .8rem; color: var(--muted); grid-column: 2; }
    .firm-card .info { grid-column: 2; font-size: .84rem; color: var(--text-2); display: flex; flex-direction: column; gap: 3px; }
    .firm-card .line { display: flex; gap: 6px; align-items: baseline; }
    .firm-card .line > span:first-child { flex: none; width: 18px; text-align: center; }
    .firm-card .contact strong { color: var(--success); }
    .firm-card .muted { font-style: italic; }
    .firm-card .chip { display: inline-block; background: var(--surface-3); border-radius: 10px; padding: 1px 8px; margin: 0 4px 2px 0; font-size: .78rem; }
    .firms-more { text-align: center; padding: 6px 0 2px; }

    /* Xodim qo'shish (eksport) */
    .firm-tabs { display: flex; gap: 6px; margin-bottom: 8px; }
    .firm-tab {
        flex: 1;
        border: 1px solid var(--border-strong);
        background: var(--surface);
        border-radius: var(--radius-sm);
        padding: 7px 10px;
        font-size: .85rem;
        font-weight: 600;
        color: var(--text-2);
        cursor: pointer;
    }
    .firm-tab .count { display: inline-block; min-width: 22px; margin-left: 4px; padding: 0 6px; border-radius: 10px; background: var(--surface-3); font-size: .75rem; }
    .firm-tab.active { border-color: var(--primary); background: var(--primary-soft); color: var(--primary-text); }
    .firm-tab.active .count { background: var(--primary); color: var(--on-primary); }
    .firm-tab[data-tab="with"].active { border-color: var(--success); background: var(--success-soft); color: var(--success); }
    .firm-tab[data-tab="with"].active .count { background: var(--success); color: var(--surface); }
    .firm-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid var(--border); }
    .firm-item:last-child { border-bottom: none; }
    .firm-item:hover { background: var(--surface-3); }
    .firm-item.selected { background: var(--primary-soft); box-shadow: inset 3px 0 0 var(--primary); }
    .firm-item .firm-name { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .firm-item .firm-meta { font-size: .8rem; color: var(--muted); }
    .firm-item .firm-contact { font-size: .8rem; color: var(--success); font-weight: 600; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .form-field { display: flex; flex-direction: column; gap: 4px; }
    .form-field.wide { grid-column: 1 / -1; }
    .form-field label { font-size: .8rem; font-weight: 600; color: var(--text-2); }
    .form-field input { height: 38px; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); padding: 0 10px; outline: none; background: var(--surface); }
    .form-field input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
    .selected-firm { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 10px; margin: 10px 0; font-size: .85rem; color: var(--text-2); }
    .form-error { color: var(--danger); font-size: .85rem; margin-top: 8px; }

    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .firm-card { grid-template-columns: minmax(0, 1fr) auto; }
        .firm-card .rank { display: none; }
        .firm-card .side { grid-column: 2; grid-row: 1 / span 2; }
        .firm-card .sub { grid-column: 1; }
        .firm-card .info { grid-column: 1 / -1; }
        .task { grid-template-columns: 28px 1fr; }
        .task .progress-wrap, .task .actions { grid-column: 1 / -1; }
        .toolbar .btn { flex: 1 1 auto; }
        .main-filters { grid-template-columns: 1fr 1fr; gap: 10px; }
    }
</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            @if ($dataset === 'export')
                <h1>O'zbekiston eksport</h1>
                <p>O'zbekistondan eksport qilingan tovarlar bo'yicha bojxona ma'lumotlari</p>
            @else
                <h1>O'zbekiston import</h1>
                <p>Bojxona deklaratsiyalari bo'yicha import ma'lumotlari</p>
            @endif
        </div>
    </div>

    <div class="toolbar" style="margin-bottom:16px">
        <input type="file" id="fileInput" accept=".xlsx" hidden>
        <button type="button" class="btn btn-primary" id="uploadBtn">
            <svg class="icon"><use href="#i-upload"/></svg>
            Fayl yuklash
        </button>
        <button type="button" class="btn btn-secondary" id="exportBtn">
            <svg class="icon"><use href="#i-download"/></svg>
            Excel'ga yuklab olish
        </button>
        <button type="button" class="btn btn-secondary" id="filterModeBtn" title="Har bir ustun boshida filtr belgisini ko'rsatish (Excel'dagi kabi)">
            <svg class="icon"><use href="#i-filter"/></svg>
            Filtr
            <span class="count-badge" id="filterCount" hidden></span>
        </button>
        <button type="button" class="btn btn-secondary" id="columnsBtn">
            <svg class="icon"><use href="#i-columns"/></svg>
            Ustunlar
        </button>
        <button type="button" class="btn btn-secondary" id="firmsBtn" title="Takrorlanmas firmalar va ularning kontaktlari">
            <svg class="icon"><use href="#i-building"/></svg>
            Firmalar
            <span class="count-badge" id="firmsCount" hidden></span>
        </button>
        @if ($dataset === 'export')
            <button type="button" class="btn btn-secondary" id="contactBtn" title="Eksportyor firmaga mas'ul xodim biriktirish">
                <svg class="icon"><use href="#i-user-plus"/></svg>
                Xodim qo'shish
            </button>
        @endif
    </div>

    <!-- Import / export jarayonlari -->
    <div class="card tasks-section" id="tasksSection" hidden>
        <div class="tasks-head">
            <h3>Import / eksport jarayonlari</h3>
            <button type="button" class="btn-outline" id="tasksToggle" hidden></button>
        </div>
        <div id="tasksList"></div>
    </div>

    <!-- Asosiy filtrlar -->
    <div class="card filters-card">
        <div class="main-filters" id="mainFilters"></div>
        <div class="filters-bar" id="filtersBar" hidden></div>
    </div>

    <!-- Jadval -->
    <div class="card table-card table-section">
        <div class="table-wrapper" id="tableWrapper">
            <table class="data-table">
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
    <div class="pagination" id="pagination"></div>
@endsection

@push('scripts')
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
        tasksExpanded: `${STORAGE_PREFIX}.tasksExpanded`,
    };

    /** Birinchi ochilganda ko'rinadigan ustunlar */
    const DEFAULT_COLUMNS = DATASET === 'export' ? [
        'date', 'exporter_name', 'exporter_inn', 'importer_name',
        'hs_code', 'product_description', 'quantity', 'net_weight_kg',
        'invoice_amount_usd', 'customs_value_usd', 'destination_country', 'destination',
        'transaction_method', 'transportation_method',
        'responsible_person_name', 'responsible_person_position', 'responsible_person_phone',
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
                    ? `<td class="${classes}"><span class="nil">-</span></td>`
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
            return `<button type="button" class="page-btn ${page === current ? 'active' : ''}" onclick="goToPage(${page})">${page}</button>`;
        }

        paginationDiv.innerHTML += createButton(1);
        if (current > 3) paginationDiv.innerHTML += `<span class="page-gap">…</span>`;
        for (let i = current - 1; i <= current + 1; i++) {
            if (i > 1 && i < last) paginationDiv.innerHTML += createButton(i);
        }
        if (current < last - 2) paginationDiv.innerHTML += `<span class="page-gap">…</span>`;
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
                <div class="main-filter-box">
                    <label>${esc(col.label)}</label>
                    <div class="main-filter ${text ? 'has-value' : ''}" data-column="${esc(name)}">
                        <svg class="icon icon-sm"><use href="#i-search"/></svg>
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
        document.getElementById('filterModeBtn').classList.toggle('toggle-on', state.filterMode);
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
                <span style="color:var(--muted);align-self:center" class="count"></span>
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
    const ownUploads = new Set();   // shu sahifada yuklangan fayllar — boshqa bo'limga tushsa ham ko'rinib turadi
    let uploadProgress = null;    // {name, percent}
    let pollTimer = null;
    // yopiq holatda faqat oxirgi jarayon (va hali tugamaganlar), ochiq holatda — butun tarix
    let tasksExpanded = localStorage.getItem(STORAGE.tasksExpanded) === '1';
    let tasksTotal = 0;           // shu bo'limdagi jarayonlar soni (serverdan)
    let tasksLastPage = 1;
    let tasksPage = 0;
    const isRunning = t => t.status === 'pending' || t.status === 'processing';

    const STATUS_TEXT = {
        pending: 'Navbatda',
        processing: 'Bajarilmoqda',
        completed: 'Tayyor',
        failed: 'Xatolik',
    };

    function renderTasks() {
        const section = document.getElementById('tasksSection');
        const list = document.getElementById('tasksList');
        // faqat shu bo'limning vazifalari (+ shu yerda yuklangan, lekin boshqa bo'limga tushgan fayllar)
        const all = [...tasks.values()]
            .filter(t => (t.dataset || 'import') === DATASET || ownUploads.has(t.id))
            .sort((a, b) => b.id - a.id);
        const items = tasksExpanded ? all : all.filter((t, i) => i === 0 || isRunning(t));
        const total = Math.max(tasksTotal, all.length);

        section.hidden = !all.length && !uploadProgress;

        const toggle = document.getElementById('tasksToggle');
        toggle.hidden = total < 2;
        toggle.textContent = tasksExpanded ? 'Faqat oxirgisi' : `Butun tarix (${numberFormat.format(total)})`;
        list.classList.toggle('expanded', tasksExpanded);

        let html = '';

        if (uploadProgress) {
            html += `
                <div class="task">
                    <div class="task-icon">⬆️</div>
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
                    <div class="task-icon">${isImport ? '📥' : '📤'}</div>
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
                        ${!isImport && t.status === 'completed' ? `<button class="btn btn-secondary btn-sm" data-download="${t.id}">Yuklab olish</button>` : ''}
                    </div>
                </div>`;
        }).join('');

        if (tasksExpanded && tasksPage < tasksLastPage) {
            html += `<div class="tasks-more"><button type="button" class="btn-outline" data-more>Yana ko'rsatish</button></div>`;
        }

        list.innerHTML = html;
        list.querySelector('[data-more]')?.addEventListener('click', e => {
            e.currentTarget.disabled = true;
            loadTasks(tasksPage + 1);
        });
        list.querySelectorAll('[data-download]').forEach(b => b.addEventListener('click', () => downloadExport(tasks.get(Number(b.dataset.download)))));
    }

    async function loadTasks(page = 1) {
        try {
            const result = await api(`${TASKS_API}/tasks?dataset=${DATASET}&page=${page}`);
            result.data.data.forEach(t => tasks.set(t.id, t));
            tasksTotal = result.data.total;
            tasksLastPage = result.data.last_page;
            tasksPage = Math.max(tasksPage, page);
            renderTasks();
            schedulePoll();
        } catch (e) {
            console.error(e);
        }
    }

    function schedulePoll() {
        clearTimeout(pollTimer);
        const running = [...tasks.values()].some(isRunning);
        if (running) pollTimer = setTimeout(pollTasks, 1500);
    }

    async function pollTasks() {
        const running = [...tasks.values()].filter(isRunning);

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
                ownUploads.add(result.data.id);
                if ((result.data.dataset || 'import') === DATASET) tasksTotal++;
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
            tasksTotal++;
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

    // ================= Firmalar =================

    const FIRM_LABELS = {
        import: { tab: 'Import firmalar', country: 'Kelib chiqish davlati', innColumn: 'importer_inn', noContact: "Xodim ma'lumoti yo'q", alsoIn: 'Eksport ham qilgan' },
        export: { tab: 'Eksport firmalar', country: 'Boradigan davlat', innColumn: 'exporter_inn', noContact: 'Xodim kiritilmagan', alsoIn: 'Import ham qilgan' },
    };

    /** Import va eksportdagi takrorlanmas firmalar soni (filtrsiz) */
    async function loadFirmsSummary() {
        const result = await api('/api/uzbekistan-firms/summary');
        const badge = document.getElementById('firmsCount');
        badge.textContent = numberFormat.format(result.data[DATASET].total);
        badge.hidden = false;
        return result.data;
    }

    /** Boshqa bo'limga o'tib, jadvalni firma bo'yicha filtrlash (?inn=...) */
    function firmPageUrl(dataset, inn) {
        return `/uzbekistan-${dataset}?inn=${encodeURIComponent(inn)}`;
    }

    /**
     * Takrorlanmas firmalar (STIR bo'yicha) — qatorlar soni bo'yicha kamayish tartibida.
     * Import va eksport firmalari alohida tablarda; joriy bo'limda jadval filtrlari hisobga olinadi.
     */
    function openFirmsModal() {
        closePopup();

        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.innerHTML = `
            <div class="popup modal firms-modal" role="dialog" aria-modal="true">
                <div class="popup-title">
                    <span>Firmalar</span>
                    <button type="button" class="close" title="Yopish">×</button>
                </div>
                <div class="popup-body">
                    <div class="firms-totals"><div class="values-note">Yuklanmoqda...</div></div>
                    <div class="firm-tabs">
                        ${['import', 'export'].map(ds => `
                            <button type="button" class="firm-tab" data-dataset="${ds}">${FIRM_LABELS[ds].tab} <span class="count">…</span></button>`).join('')}
                    </div>
                    <div class="firms-summary"></div>
                    <div class="firms-note" hidden></div>
                    <input class="p-search" type="search" placeholder="Firma nomi yoki STIR bo'yicha qidirish...">
                    <div class="firms-list"><div class="values-note">Yuklanmoqda...</div></div>
                </div>
            </div>`;
        document.body.appendChild(backdrop);

        const modal = backdrop.querySelector('.modal');
        const listEl = modal.querySelector('.firms-list');
        const summaryEl = modal.querySelector('.firms-summary');
        const noteEl = modal.querySelector('.firms-note');
        const body = modal.querySelector('.popup-body');

        let dataset = DATASET;
        let search = '';
        let page = 0;
        let lastPage = 1;
        let shown = 0;
        let requestId = 0;

        const close = () => {
            backdrop.remove();
            document.removeEventListener('keydown', onKey);
        };
        const onKey = e => { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', onKey);
        backdrop.addEventListener('click', e => { if (e.target === backdrop) close(); });
        modal.querySelector('.close').addEventListener('click', close);

        // umumiy sonlar: import, eksport, ikkalasida jami takrorlanmas, ikkalasida ham bor
        loadFirmsSummary().then(totals => {
            modal.querySelector('.firms-totals').innerHTML = `
                <div class="stat"><strong>${numberFormat.format(totals.import.total)}</strong>import firmalar</div>
                <div class="stat"><strong>${numberFormat.format(totals.export.total)}</strong>eksport firmalar</div>
                <div class="stat primary"><strong>${numberFormat.format(totals.unique_total)}</strong>jami takrorlanmas firma</div>
                <div class="stat"><strong>${numberFormat.format(totals.both)}</strong>ham import, ham eksport qilgan</div>`;
            modal.querySelectorAll('.firm-tab').forEach(btn => {
                btn.querySelector('.count').textContent = numberFormat.format(totals[btn.dataset.dataset].total);
            });
        }).catch(e => {
            modal.querySelector('.firms-totals').innerHTML = `<div class="values-note">${esc(e.message)}</div>`;
        });

        function firmCard(f, index) {
            const labels = FIRM_LABELS[dataset];
            const other = dataset === 'export' ? 'import' : 'export';
            const address = f.address
                ? [f.address.region, f.address.district].filter(Boolean).join(', ') || f.address.address
                : null;
            const contacts = f.contacts.length
                ? f.contacts.map(c => `
                    <div class="line contact"><span>👤</span><span>
                        <strong>${esc(c.full_name)}</strong>${c.position ? ' — ' + esc(c.position) : ''}${c.phone ? ' · ' + esc(c.phone) : ''}
                        ${f.contacts.length > 1 ? `<span class="muted">(${numberFormat.format(c.rows)} qator)</span>` : ''}
                    </span></div>`).join('')
                : `<div class="line"><span>👤</span><span class="muted">${labels.noContact}</span></div>`;

            return `
                <div class="firm-card">
                    <div class="rank">${index}</div>
                    <div class="title" title="${esc(f.names.join(' | '))}">${esc(f.name || '-')}</div>
                    <div class="side">
                        <div class="rows">${numberFormat.format(f.rows)} qator</div>
                        <button type="button" class="btn-outline" data-show="${esc(f.inn)}" title="Jadvalni shu firma (STIR) bo'yicha filtrlash">Jadvalda ko'rsatish</button>
                    </div>
                    <div class="sub">
                        STIR: <strong>${esc(f.inn)}</strong>
                        ${f.last_date ? ` · Oxirgi: ${formatValue(f.last_date, 'date')}` : ''}
                        ${f.names.length > 1 ? ` · <span title="${esc(f.names.join(' | '))}">${f.names.length} xil nomda yozilgan</span>` : ''}
                        ${f.in_other ? ` · <a class="also-chip" href="${firmPageUrl(other, f.inn)}" title="${FIRM_LABELS[other].tab} — jadvalda ko'rish">${labels.alsoIn} →</a>` : ''}
                    </div>
                    <div class="info">
                        ${address ? `<div class="line"><span>📍</span><span title="${esc(f.address.address || '')}">${esc(address)}</span></div>` : ''}
                        ${f.phones?.length ? `<div class="line"><span>📞</span><span>${f.phones.map(esc).join(', ')}</span></div>` : ''}
                        ${contacts}
                        ${f.countries.length ? `<div class="line"><span>🌍</span><span>${labels.country}:
                            ${f.countries.map(c => `<span class="chip">${esc(c.value)} · ${numberFormat.format(c.rows)}</span>`).join('')}</span></div>` : ''}
                    </div>
                </div>`;
        }

        async function load(reset) {
            const current = ++requestId;
            if (reset) {
                page = 0;
                shown = 0;
            }

            // jadval filtrlari faqat joriy bo'lim firmalariga qo'llanadi
            const filtered = dataset === DATASET && Object.keys(state.filters).length > 0;
            noteEl.hidden = !filtered;
            noteEl.textContent = 'Jadvaldagi filtrlar hisobga olingan';

            const params = dataset === DATASET ? buildParams() : new URLSearchParams();
            params.append('page', page + 1);
            if (search) params.append('q', search);

            const moreBtn = listEl.querySelector('[data-more]');
            if (moreBtn) moreBtn.disabled = true;

            try {
                const result = await api(`/api/uzbekistan-${dataset}/firms?${params}`);
                if (current !== requestId) return; // eskirgan javob
                const data = result.data;

                page = data.current_page;
                lastPage = data.last_page;

                summaryEl.innerHTML = `
                    <div class="stat"><strong>${numberFormat.format(data.total)}</strong>${filtered || search ? 'topilgan firma' : 'takrorlanmas firma'}</div>
                    <div class="stat"><strong>${numberFormat.format(data.with_contacts)}</strong>xodimi bor</div>
                    <div class="stat"><strong>${numberFormat.format(data.total - data.with_contacts)}</strong>xodimi yo'q</div>
                    <div class="stat"><strong>${numberFormat.format(data.rows)}</strong>qator</div>`;

                const html = data.data.map(f => firmCard(f, ++shown)).join('');
                if (reset) {
                    listEl.innerHTML = html || `<div class="values-note">Firma topilmadi</div>`;
                    body.scrollTop = 0;
                } else {
                    listEl.querySelector('.firms-more')?.remove();
                    listEl.insertAdjacentHTML('beforeend', html);
                }

                if (page < lastPage) {
                    listEl.insertAdjacentHTML('beforeend', `
                        <div class="firms-more">
                            <button type="button" class="btn-outline" data-more>
                                Yana ko'rsatish (${numberFormat.format(shown)} / ${numberFormat.format(data.total)})
                            </button>
                        </div>`);
                    listEl.querySelector('[data-more]').addEventListener('click', () => load(false));
                }
            } catch (e) {
                if (current !== requestId) return;
                if (reset) listEl.innerHTML = `<div class="values-note">${esc(e.message)}</div>`;
                else alert(e.message);
            }
        }

        function selectTab(ds) {
            dataset = ds;
            modal.querySelectorAll('.firm-tab').forEach(btn => btn.classList.toggle('active', btn.dataset.dataset === ds));
            listEl.innerHTML = `<div class="values-note">Yuklanmoqda...</div>`;
            load(true);
        }

        modal.querySelectorAll('.firm-tab').forEach(btn => btn.addEventListener('click', () => selectTab(btn.dataset.dataset)));

        // "Jadvalda ko'rsatish": jadval shu firmaning STIR'i bo'yicha filtrlanadi (boshqa filtrlar saqlanadi).
        // Boshqa bo'lim firmasi bo'lsa — o'sha sahifaga o'tib filtrlanadi.
        listEl.addEventListener('click', e => {
            const btn = e.target.closest('[data-show]');
            if (!btn) return;
            if (dataset !== DATASET) {
                window.location.href = firmPageUrl(dataset, btn.dataset.show);
                return;
            }
            close();
            setFilter(FIRM_LABELS[DATASET].innColumn, { values: [btn.dataset.show], mode: 'in' });
            document.querySelector('.table-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        modal.querySelector('.p-search').addEventListener('input', debounce(e => {
            search = e.target.value.trim();
            load(true);
        }, 300));

        modal.querySelector('.p-search').focus();
        selectTab(DATASET);
    }

    // ================= Xodim qo'shish (faqat eksport) =================

    function toast(message) {
        App.toast(message);
    }

    /**
     * Eksportyor firmani (STIR bo'yicha takrorlanmas) tanlab, mas'ul xodimni kiritish.
     * Saqlanganda xodim shu firmaning barcha qatorlariga yoziladi.
     */
    async function openContactModal() {
        closePopup();

        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.innerHTML = `
            <div class="popup modal" role="dialog" aria-modal="true">
                <div class="popup-title">Xodim qo'shish</div>
                <div class="popup-body">
                    <div class="p-section-title" style="margin-top:0">Eksportyor firma</div>
                    <div class="firm-tabs">
                        <button type="button" class="firm-tab active" data-tab="without">Xodim kiritilmagan <span class="count">0</span></button>
                        <button type="button" class="firm-tab" data-tab="with">Xodim kiritilgan <span class="count">0</span></button>
                    </div>
                    <input class="p-search" type="search" placeholder="Firma nomi yoki STIR bo'yicha qidirish...">
                    <div class="values-list firms"><div class="values-note">Yuklanmoqda...</div></div>

                    <div class="selected-firm">Firmani tanlang</div>

                    <div class="form-grid">
                        <div class="form-field wide">
                            <label for="contactName">Ism familiya *</label>
                            <input id="contactName" type="text" maxlength="255" placeholder="Masalan: Karimov Aziz Botirovich">
                        </div>
                        <div class="form-field">
                            <label for="contactPosition">Lavozim</label>
                            <input id="contactPosition" type="text" maxlength="255" placeholder="Masalan: Direktor">
                        </div>
                        <div class="form-field">
                            <label for="contactPhone">Telefon</label>
                            <input id="contactPhone" type="tel" maxlength="30" placeholder="+998 90 123 45 67">
                        </div>
                    </div>
                    <div class="form-error" hidden></div>
                </div>
                <div class="popup-footer">
                    <button class="p-btn danger" data-action="delete" hidden>Xodimni olib tashlash</button>
                    <div class="right" style="margin-left:auto">
                        <button class="p-btn" data-action="cancel">Bekor qilish</button>
                        <button class="p-btn primary" data-action="save">Saqlash</button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(backdrop);

        const modal = backdrop.querySelector('.modal');
        const listEl = modal.querySelector('.firms');
        const errorEl = modal.querySelector('.form-error');
        const nameInput = modal.querySelector('#contactName');
        const positionInput = modal.querySelector('#contactPosition');
        const phoneInput = modal.querySelector('#contactPhone');
        const deleteBtn = modal.querySelector('[data-action="delete"]');
        const saveBtn = modal.querySelector('[data-action="save"]');

        let firms = [];
        let selected = null;
        let search = '';
        // xodim kiritilgan va kiritilmagan firmalar alohida ro'yxatda
        let tab = 'without';

        const close = () => {
            backdrop.remove();
            document.removeEventListener('keydown', onKey);
        };
        const onKey = e => { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', onKey);
        backdrop.addEventListener('click', e => { if (e.target === backdrop) close(); });
        modal.querySelector('[data-action="cancel"]').addEventListener('click', close);

        function showError(message) {
            errorEl.hidden = !message;
            errorEl.textContent = message || '';
        }

        function renderFirms() {
            const q = search.toLowerCase();
            // firmalar serverdan qatorlar soni bo'yicha kamayish tartibida keladi
            const found = firms.filter(f => !q
                || f.inn.includes(q)
                || f.names.some(n => n.toLowerCase().includes(q)));
            const groups = {
                without: found.filter(f => !f.contact),
                with: found.filter(f => f.contact),
            };
            modal.querySelectorAll('.firm-tab').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tab);
                btn.querySelector('.count').textContent = numberFormat.format(groups[btn.dataset.tab].length);
            });

            const visible = groups[tab];
            if (!visible.length) {
                listEl.innerHTML = `<div class="values-note">${q ? 'Firma topilmadi'
                    : tab === 'with' ? 'Hali hech bir firmaga xodim kiritilmagan' : 'Barcha firmalarga xodim kiritilgan'}</div>`;
                return;
            }

            listEl.innerHTML = visible.map(f => `
                <div class="firm-item ${selected?.inn === f.inn ? 'selected' : ''}" data-inn="${esc(f.inn)}">
                    <div class="firm-name" title="${esc(f.names.join(' | '))}">${esc(f.name || '-')}</div>
                    <div class="firm-meta">STIR: ${esc(f.inn)} · ${numberFormat.format(f.rows)} qator${f.names.length > 1 ? ` · ${f.names.length} xil nomda yozilgan` : ''}</div>
                    ${f.contact ? `<div class="firm-contact">👤 ${esc(f.contact.full_name)}${f.contact.position ? ' — ' + esc(f.contact.position) : ''}${f.contact.phone ? ' · ' + esc(f.contact.phone) : ''}</div>` : ''}
                </div>`).join('');

            listEl.querySelectorAll('.firm-item').forEach(el => el.addEventListener('click', () => {
                select(firms.find(f => f.inn === el.dataset.inn));
            }));
        }

        function select(firm) {
            selected = firm;
            showError('');
            modal.querySelector('.selected-firm').innerHTML = `
                <strong>${esc(firm.name || '-')}</strong> (STIR: ${esc(firm.inn)})<br>
                Xodim shu firmaning barcha <strong>${numberFormat.format(firm.rows)}</strong> ta qatoriga yoziladi`;
            nameInput.value = firm.contact?.full_name || '';
            positionInput.value = firm.contact?.position || '';
            phoneInput.value = firm.contact?.phone || '';
            deleteBtn.hidden = !firm.contact;
            renderFirms();
            nameInput.focus();
        }

        modal.querySelectorAll('.firm-tab').forEach(btn => btn.addEventListener('click', () => {
            tab = btn.dataset.tab;
            listEl.scrollTop = 0;
            renderFirms();
        }));

        modal.querySelector('.p-search').addEventListener('input', e => {
            search = e.target.value.trim();
            renderFirms();
        });

        saveBtn.addEventListener('click', async () => {
            if (!selected) return showError('Avval firmani tanlang');
            if (!nameInput.value.trim()) return showError('Xodimning ism familiyasini kiriting');

            saveBtn.disabled = true;
            try {
                const result = await api(`${API}/exporters/${encodeURIComponent(selected.inn)}/contact`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        full_name: nameInput.value.trim(),
                        position: positionInput.value.trim(),
                        phone: phoneInput.value.trim(),
                    }),
                });
                close();
                toast(result.message);
                loadData();
            } catch (e) {
                showError(e.message);
            } finally {
                saveBtn.disabled = false;
            }
        });

        deleteBtn.addEventListener('click', async () => {
            if (!selected || !confirm(`${selected.name} firmasidan xodim olib tashlansinmi?`)) return;

            try {
                const result = await api(`${API}/exporters/${encodeURIComponent(selected.inn)}/contact`, { method: 'DELETE' });
                close();
                toast(result.message);
                loadData();
            } catch (e) {
                showError(e.message);
            }
        });

        modal.querySelector('.p-search').focus();

        try {
            const result = await api(`${API}/exporters`);
            firms = result.data;
            renderFirms();
        } catch (e) {
            listEl.innerHTML = `<div class="values-note">${esc(e.message)}</div>`;
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
    document.getElementById('firmsBtn').addEventListener('click', e => {
        e.stopPropagation();
        openFirmsModal();
    });
    document.getElementById('tasksToggle').addEventListener('click', () => {
        tasksExpanded = !tasksExpanded;
        localStorage.setItem(STORAGE.tasksExpanded, tasksExpanded ? '1' : '0');
        document.getElementById('tasksList').scrollTop = 0;
        renderTasks();
    });
    document.getElementById('contactBtn')?.addEventListener('click', e => {
        e.stopPropagation();
        openContactModal();
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

            // "Firmalar" oynasidan boshqa bo'limga o'tilganda: ?inn=... — jadval shu firma bo'yicha filtrlanadi
            const url = new URL(window.location.href);
            const inn = url.searchParams.get('inn');
            if (inn) {
                state.filters[FIRM_LABELS[DATASET].innColumn] = { values: [inn], mode: 'in' };
                url.searchParams.delete('inn');
                history.replaceState(null, '', url);
            }

            renderAll();
            loadData();
            loadTasks();
            loadFirmsSummary().catch(e => console.error(e));
        } catch (e) {
            document.getElementById('tableBody').innerHTML =
                `<tr class="loading-row"><td>${esc(e.message)}</td></tr>`;
        }
    })();
</script>
@endpush
