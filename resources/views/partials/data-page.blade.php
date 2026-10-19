{{--
    Oddiy ma'lumot sahifasi: sarlavha, yuklash/yuklab olish, filtrlar, jadval.
    Logika — public/js/data-page.js; sahifa DataPage.init({...}) bilan sozlaydi.
    Parametrlar: $title, $subtitle
--}}
@push('styles')
<style>
    .dp-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
    .dp-field { min-width: 0; }
    .dp-field label { display: block; font-size: .78rem; font-weight: 600; color: var(--text-2); margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dp-filters-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
    .dp-filters-head h2 { font-size: .95rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .dp-table .table-wrap { max-height: 70vh; }
    .dp-table .table th { position: sticky; top: 0; z-index: 1; }
    .dp-table .table td { white-space: nowrap; max-width: 360px; overflow: hidden; text-overflow: ellipsis; }
    .dp-table .table td.nil { color: var(--muted); }
    .dp-table .table td.num, .dp-table .table th.num { text-align: right; font-variant-numeric: tabular-nums; }
    .dp-message { text-align: center !important; padding: 48px 16px !important; color: var(--muted); white-space: normal !important; }
    .dp-message .icon { width: 28px; height: 28px; margin-bottom: 6px; }
    .dp-message.error { color: var(--danger); }
    .dp-footer { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; padding: 12px 16px; border-top: 1px solid var(--border); color: var(--text-2); font-size: .88rem; }
    .pagination { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
    .page-btn { min-width: 34px; height: 34px; padding: 0 8px; border-radius: var(--radius-sm); border: 1px solid var(--border); background: var(--surface); color: var(--text); cursor: pointer; font-weight: 600; }
    .page-btn:hover:not(:disabled) { border-color: var(--primary); color: var(--primary-text); }
    .page-btn.active { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
    .page-btn:disabled { opacity: .4; cursor: default; }
    .page-gap { color: var(--muted); padding: 0 4px; }
    @media (max-width: 640px) {
        .dp-filters { grid-template-columns: 1fr 1fr; gap: 10px; }
        .dp-toolbar .btn { flex: 1 1 auto; }
        .dp-footer { justify-content: center; }
    }
</style>
@endpush

<div id="dataPage">
    <div class="page-head">
        <div>
            <h1>{{ $title }}</h1>
            @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
        </div>
        <div class="page-actions dp-toolbar">
            <input type="file" data-el="file" hidden>
            <button type="button" class="btn btn-primary" data-el="upload">
                <svg class="icon"><use href="#i-upload"/></svg>
                <span>Fayl yuklash</span>
            </button>
            <button type="button" class="btn btn-secondary" data-el="export">
                <svg class="icon"><use href="#i-download"/></svg>
                <span>Excel'ga yuklab olish</span>
            </button>
        </div>
    </div>

    <div class="card card-body" style="margin-bottom:16px">
        <div class="dp-filters-head">
            <h2><svg class="icon icon-sm"><use href="#i-filter"/></svg>Filtrlar</h2>
            <button type="button" class="btn btn-ghost btn-sm" data-el="clear" hidden>
                <svg class="icon icon-sm"><use href="#i-x"/></svg><span>Tozalash</span>
            </button>
        </div>
        <div class="dp-filters" data-el="filters"></div>
    </div>

    <div class="card dp-table">
        <div class="table-wrap">
            <table class="table">
                <thead data-el="head"></thead>
                <tbody data-el="body"></tbody>
            </table>
        </div>
        <div class="dp-footer">
            <span data-el="total">&nbsp;</span>
            <div class="pagination" data-el="pagination"></div>
        </div>
    </div>
</div>

@prepend('scripts')
<script src="{{ asset('js/data-page.js') }}?v={{ filemtime(public_path('js/data-page.js')) }}"></script>
@endprepend
