@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .updated { color: var(--muted); font-size: .85rem; display: inline-flex; align-items: center; gap: 6px; }

    /* Manbalar taqsimoti (gorizontal ulush chizig'i) */
    .share-bar { display: flex; height: 12px; border-radius: 6px; overflow: hidden; gap: 2px; background: var(--surface-3); margin-bottom: 10px; }
    .share-bar span { display: block; height: 100%; min-width: 3px; }

    /* Manba kartochkasi */
    .source-card { display: flex; flex-direction: column; padding: 16px 18px; gap: 10px; transition: border-color .15s, box-shadow .15s; }
    .source-card:hover { border-color: var(--border-strong); box-shadow: var(--shadow-lg); }
    .source-top { display: flex; align-items: center; gap: 10px; }
    .source-icon { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; flex: none; color: var(--group); background: color-mix(in srgb, var(--group) 14%, transparent); }
    .source-name { font-weight: 600; line-height: 1.2; }
    .source-group { color: var(--muted); font-size: .78rem; }
    .source-top .badge { margin-left: auto; }
    .source-rows { display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; }
    .source-rows strong { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; line-height: 1; }
    .source-rows small { color: var(--muted); display: block; margin-top: 4px; }
    .spark { width: 110px; height: 34px; flex: none; }
    .spark rect { fill: var(--group); }
    .spark rect.zero { fill: var(--surface-3); }
    .source-chips { display: flex; gap: 6px; flex-wrap: wrap; }
    .source-foot > span { display: inline-flex; align-items: center; gap: 4px; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .source-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; border-top: 1px solid var(--border); padding-top: 10px; font-size: .82rem; color: var(--text-2); }
    .source-foot a { display: inline-flex; align-items: center; gap: 4px; font-weight: 600; }

    .empty-sources { grid-column: 1 / -1; padding: 14px 18px; }
    .empty-sources .card-title { display: flex; align-items: center; gap: 6px; font-size: .9rem; margin-bottom: 10px; }
    .empty-links { display: flex; flex-wrap: wrap; gap: 8px; }
    .empty-links a { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border: 1px dashed var(--border-strong); border-radius: 999px; color: var(--text-2); font-size: .84rem; }
    .empty-links a:hover { border-color: var(--group); color: var(--text); text-decoration: none; }
    .empty-links i { width: 8px; height: 8px; border-radius: 50%; background: var(--group); }

    /* Yuklashlar jadvali */
    .upload-file { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .upload-error { color: var(--danger); font-size: .78rem; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* O'zbekiston firmalari */
    .uz-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px; }
    .uz-stat { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px; }
    .uz-stat strong { display: block; font-size: 1.25rem; font-weight: 700; }
    .uz-stat span { color: var(--text-2); font-size: .8rem; }
    .uz-stat.primary { background: var(--primary-soft); border-color: transparent; }
    .uz-stat.primary strong { color: var(--primary-text); }
    .top-firms { list-style: none; margin: 12px 0 0; padding: 0; }
    .top-firms li { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--border); }
    .top-firms li:last-child { border-bottom: none; }
    .top-firms .rank { width: 24px; height: 24px; border-radius: 50%; background: var(--surface-3); display: grid; place-items: center; font-size: .75rem; font-weight: 700; color: var(--text-2); flex: none; }
    .top-firms .name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
    .top-firms .rows { color: var(--text-2); font-size: .85rem; white-space: nowrap; }

    .chart-table { max-height: 280px; }
    @media (max-width: 640px) {
        .kpi-value { font-size: 1.4rem; }
        .spark { width: 90px; }
    }
</style>
@endpush

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p>Barcha manbalardagi ma'lumotlar holati: qayerda qancha ma'lumot bor va qachon yangilangan</p>
        </div>
        <div class="page-actions">
            <span class="updated" id="updatedAt"></span>
            <button type="button" class="btn btn-secondary" id="refreshBtn">
                <svg class="icon"><use href="#i-refresh"/></svg>
                Yangilash
            </button>
        </div>
    </div>

    <div id="errorBox"></div>

    {{-- Asosiy ko'rsatkichlar --}}
    <div class="grid grid-kpi" id="kpis">
        @for ($i = 0; $i < 5; $i++)
            <div class="card kpi"><div class="kpi-icon skeleton"></div><div style="flex:1"><div class="skeleton" style="height:14px;width:60%"></div><div class="skeleton" style="height:26px;width:80%;margin-top:8px"></div></div></div>
        @endfor
    </div>

    {{-- Grafiklar --}}
    <div class="grid grid-2 section">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Kunlik qo'shilgan ma'lumotlar</h2>
                    <div class="card-sub">Oxirgi 30 kun, manba guruhlari bo'yicha (Toshkent vaqti)</div>
                </div>
                <div class="segmented" role="tablist" id="dailyView">
                    <button type="button" class="active" data-view="chart"><svg class="icon icon-sm"><use href="#i-chart"/></svg></button>
                    <button type="button" data-view="table"><svg class="icon icon-sm"><use href="#i-table"/></svg></button>
                </div>
            </div>
            <div class="card-body">
                <div class="legend" id="dailyLegend" style="margin-bottom:10px"></div>
                <div class="chart-box" id="dailyChartBox"><canvas id="dailyChart" aria-label="Kunlik qo'shilgan ma'lumotlar grafigi" role="img"></canvas></div>
                <div class="table-wrap chart-table" id="dailyTable" hidden></div>
            </div>
        </div>
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Fayl yuklashlar</h2>
                    <div class="card-sub" id="uploadsSub">Oxirgi 30 kun</div>
                </div>
            </div>
            <div class="card-body">
                <div class="legend" style="margin-bottom:10px">
                    <span><i style="background:var(--status-good)"></i>Muvaffaqiyatli</span>
                    <span><i style="background:var(--status-critical)"></i>Xatolik</span>
                </div>
                <div class="chart-box"><canvas id="uploadsChart" aria-label="Kunlik fayl yuklashlar grafigi" role="img"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Manbalar --}}
    <div class="section">
        <div class="section-head">
            <h2>Manbalar</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <div class="segmented" id="groupFilter"></div>
                <select class="select" id="sourceSort" style="width:auto">
                    <option value="rows">Ko'p ma'lumotli birinchi</option>
                    <option value="last">Oxirgi yangilangan birinchi</option>
                    <option value="stale">Eskirganlari birinchi</option>
                    <option value="name">Nomi bo'yicha</option>
                </select>
            </div>
        </div>
        <div class="share-bar" id="shareBar"></div>
        <div class="legend" id="shareLegend" style="margin-bottom:14px"></div>
        <div class="grid grid-sources" id="sources">
            @for ($i = 0; $i < 8; $i++)
                <div class="card source-card"><div class="skeleton" style="height:36px"></div><div class="skeleton" style="height:40px"></div><div class="skeleton" style="height:16px;width:60%"></div></div>
            @endfor
        </div>
    </div>

    {{-- Yuklashlar tarixi va O'zbekiston firmalari --}}
    <div class="grid grid-2 section">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>Oxirgi yuklashlar</h2>
                    <div class="card-sub">Kim, qachon, qaysi faylni yukladi va natijasi</div>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" id="moreUploads">Ko'proq ko'rsatish</button>
            </div>
            <div class="card-body" style="padding:12px 0 4px">
                <div class="table-wrap" id="uploads"><div class="empty">Yuklanmoqda...</div></div>
            </div>
        </div>
        <div class="card">
            <div class="card-head">
                <div>
                    <h2>O'zbekiston firmalari</h2>
                    <div class="card-sub">STIR bo'yicha takrorlanmas firmalar</div>
                </div>
                <div class="segmented" id="uzTabs">
                    <button type="button" class="active" data-dataset="import">Import</button>
                    <button type="button" data-dataset="export">Eksport</button>
                </div>
            </div>
            <div class="card-body" id="uzFirms"><div class="empty">Yuklanmoqda...</div></div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function () {
    const { api, fmt, esc, icon, cssVar } = App;

    // Guruhlar tartibi — rang tartibi (o'zgarmaydi: rang guruhga bog'langan)
    const GROUPS = @json(array_keys(\App\Support\DataSources::grouped()));
    const groupColor = group => cssVar(`--series-${GROUPS.indexOf(group) + 1}`);
    const STATUS = {
        fresh: ['badge-success', 'Yangi'],
        stale: ['badge-warning', 'Eskirgan'],
        empty: ['', "Bo'sh"],
    };

    let stats = null;
    let charts = {};
    let groupFilter = 'all';
    let uploadsLimit = 10;
    let uzDataset = 'import';

    // ===== Yuklash =====
    async function load(fresh = false) {
        const btn = document.getElementById('refreshBtn');
        btn.classList.add('loading');
        btn.disabled = true;
        try {
            const [result] = await Promise.all([
                api(`/api/dashboard/stats${fresh ? '?fresh=1' : ''}`),
                loadUploads(),
            ]);
            stats = result.data;
            document.getElementById('errorBox').innerHTML = '';
            render();
        } catch (e) {
            document.getElementById('errorBox').innerHTML =
                `<div class="alert" style="margin-bottom:16px">${icon('alert')}<span>${esc(e.message)}</span></div>`;
        } finally {
            btn.classList.remove('loading');
            btn.disabled = false;
        }
    }

    async function loadUploads() {
        const box = document.getElementById('uploads');
        try {
            const result = await api(`/api/dashboard/uploads?limit=${uploadsLimit}`);
            renderUploads(result.data);
        } catch (e) {
            box.innerHTML = `<div class="empty">${esc(e.message)}</div>`;
        }
    }

    function render() {
        document.getElementById('updatedAt').innerHTML = `${icon('clock', 'icon-sm')}Hisoblangan: ${fmt.time(stats.generated_at)}`;
        renderKpis();
        renderDailyChart();
        renderUploadsChart();
        renderGroupFilter();
        renderSources();
        renderUz();
    }

    // ===== KPI =====
    function renderKpis() {
        const t = stats.totals;
        const uz = stats.uzbekistan;
        const kpis = [
            ['database', '', 'Jami yozuvlar', fmt.number(t.rows), t.size_mb !== null ? `${fmt.number(t.size_mb)} MB bazada` : `${t.sources} ta manbada`],
            ['activity', 'success', "Bugun qo'shilgan", fmt.number(t.today), `Oxirgi 7 kun: ${fmt.number(t.week)}`],
            ['chart', 'info', 'Oxirgi 30 kun', fmt.number(t.month), `Kuniga o'rtacha ${fmt.number(Math.round(t.month / 30))}`],
            ['check', t.stale ? 'warning' : 'success', 'Faol manbalar', `${t.active} / ${t.sources}`, `${t.stale} ta eskirgan · ${t.empty} ta bo'sh`],
            ['building', '', "O'zbekiston firmalari", fmt.number(uz.unique_total), `Import ${fmt.number(uz.import.total)} · eksport ${fmt.number(uz.export.total)}`],
        ];
        document.getElementById('kpis').innerHTML = kpis.map(([ic, tone, label, value, meta]) => `
            <div class="card kpi">
                <div class="kpi-icon ${tone}">${icon(ic)}</div>
                <div style="min-width:0">
                    <div class="kpi-label">${label}</div>
                    <div class="kpi-value num">${value}</div>
                    <div class="kpi-meta">${meta}</div>
                </div>
            </div>`).join('');
    }

    // ===== Grafiklar =====
    function chartDefaults() {
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = cssVar('--muted');
        Chart.defaults.borderColor = cssVar('--grid');
    }

    function dayLabel(d) {
        const [, m, day] = d.split('-');
        return `${day}.${m}`;
    }

    /** Manbalar kunlik sonini guruhlarga yig'ish */
    function dailyByGroup() {
        const bySource = Object.fromEntries(stats.sources.map(s => [s.key, s.group]));
        const sums = Object.fromEntries(GROUPS.map(g => [g, stats.daily.dates.map(() => 0)]));
        for (const [key, series] of Object.entries(stats.daily.series)) {
            series.forEach((v, i) => { sums[bySource[key]][i] += v; });
        }
        return sums;
    }

    function barOptions(stackTotalLabel) {
        const surface = cssVar('--surface');
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 300 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: cssVar('--text'),
                    titleColor: cssVar('--bg'),
                    bodyColor: cssVar('--bg'),
                    footerColor: cssVar('--bg'),
                    padding: 10,
                    boxPadding: 4,
                    usePointStyle: true,
                    filter: item => item.raw > 0,
                    callbacks: {
                        label: item => ` ${item.dataset.label}: ${fmt.number(item.raw)}`,
                        footer: items => `${stackTotalLabel}: ${fmt.number(items.reduce((a, i) => a + i.raw, 0))}`,
                    },
                },
            },
            scales: {
                x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12 } },
                y: { stacked: true, beginAtZero: true, border: { display: false }, ticks: { precision: 0, callback: v => fmt.compact(v) } },
            },
            datasets: { bar: { borderColor: surface, borderWidth: { top: 2 }, borderSkipped: 'start', borderRadius: 4, maxBarThickness: 22 } },
        };
    }

    function renderDailyChart() {
        chartDefaults();
        const sums = dailyByGroup();
        const groups = GROUPS.filter(g => sums[g].some(v => v > 0));
        const shown = groups.length ? groups : GROUPS;

        document.getElementById('dailyLegend').innerHTML = shown
            .map(g => `<span><i style="background:${groupColor(g)}"></i>${esc(g)}</span>`).join('');

        charts.daily?.destroy();
        charts.daily = new Chart(document.getElementById('dailyChart'), {
            type: 'bar',
            data: {
                labels: stats.daily.dates.map(dayLabel),
                datasets: shown.map(g => ({ label: g, data: sums[g], backgroundColor: groupColor(g) })),
            },
            options: barOptions('Jami'),
        });

        // jadval ko'rinishi (rang ko'rmaydiganlar va aniq sonlar uchun)
        const rows = stats.daily.dates.map((d, i) => ({ d, values: shown.map(g => sums[g][i]) })).reverse();
        document.getElementById('dailyTable').innerHTML = `
            <table class="table">
                <thead><tr><th>Sana</th>${shown.map(g => `<th class="num">${esc(g)}</th>`).join('')}<th class="num">Jami</th></tr></thead>
                <tbody>${rows.map(r => `<tr><td>${dayLabel(r.d)}</td>${r.values.map(v => `<td class="num">${fmt.number(v)}</td>`).join('')}<td class="num"><strong>${fmt.number(r.values.reduce((a, b) => a + b, 0))}</strong></td></tr>`).join('')}</tbody>
            </table>`;
    }

    function renderUploadsChart() {
        const success = stats.uploads.success;
        const failed = stats.uploads.failed;
        const total = success.reduce((a, b) => a + b, 0) + failed.reduce((a, b) => a + b, 0);
        document.getElementById('uploadsSub').textContent = `Oxirgi 30 kun: ${fmt.number(total)} ta yuklash, ${fmt.number(failed.reduce((a, b) => a + b, 0))} tasi xato`;

        charts.uploads?.destroy();
        charts.uploads = new Chart(document.getElementById('uploadsChart'), {
            type: 'bar',
            data: {
                labels: stats.daily.dates.map(dayLabel),
                datasets: [
                    { label: 'Muvaffaqiyatli', data: success, backgroundColor: cssVar('--status-good') },
                    { label: 'Xatolik', data: failed, backgroundColor: cssVar('--status-critical') },
                ],
            },
            options: barOptions('Jami yuklash'),
        });
    }

    // ===== Manbalar =====
    function renderGroupFilter() {
        const counts = Object.fromEntries(GROUPS.map(g => [g, stats.sources.filter(s => s.group === g).length]));
        document.getElementById('groupFilter').innerHTML = [['all', 'Hammasi', stats.sources.length], ...GROUPS.map(g => [g, g, counts[g]])]
            .map(([value, label, n]) => `<button type="button" data-group="${esc(value)}" class="${groupFilter === value ? 'active' : ''}">${esc(label)} <span class="muted">${n}</span></button>`)
            .join('');

        // guruhlar bo'yicha ma'lumot ulushi
        const totals = GROUPS.map(g => [g, stats.sources.filter(s => s.group === g).reduce((a, s) => a + s.rows, 0)]);
        const sum = totals.reduce((a, [, v]) => a + v, 0) || 1;
        document.getElementById('shareBar').innerHTML = totals.filter(([, v]) => v > 0)
            .map(([g, v]) => `<span style="flex:${v};background:${groupColor(g)}" title="${esc(g)}: ${fmt.number(v)} (${(v * 100 / sum).toFixed(1)}%)"></span>`).join('');
        document.getElementById('shareLegend').innerHTML = totals
            .map(([g, v]) => `<span><i style="background:${groupColor(g)}"></i>${esc(g)} — <strong class="num">${fmt.number(v)}</strong> <span class="muted">(${(v * 100 / sum).toFixed(1)}%)</span></span>`).join('');
    }

    function sparkline(series) {
        const max = Math.max(...series, 1);
        const w = 110, h = 34, gap = 1, bw = (w - gap * (series.length - 1)) / series.length;
        return `<svg class="spark" viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" aria-hidden="true">${series.map((v, i) => {
            const bh = v > 0 ? Math.max(2, (v / max) * h) : 2;
            return `<rect class="${v > 0 ? '' : 'zero'}" x="${(i * (bw + gap)).toFixed(2)}" y="${(h - bh).toFixed(2)}" width="${bw.toFixed(2)}" height="${bh.toFixed(2)}" rx="1"></rect>`;
        }).join('')}</svg>`;
    }

    function renderSources() {
        const sort = document.getElementById('sourceSort').value;
        const order = { stale: 0, empty: 1, fresh: 2 };
        const list = stats.sources
            .filter(s => groupFilter === 'all' || s.group === groupFilter)
            .sort((a, b) => {
                if (sort === 'last') return (b.last_at || '').localeCompare(a.last_at || '');
                if (sort === 'name') return a.label.localeCompare(b.label);
                if (sort === 'stale') return order[a.status] - order[b.status] || b.rows - a.rows;
                return b.rows - a.rows;
            });

        // ma'lumoti yo'q manbalar kartochka emas, bitta ixcham blokda
        const empty = list.filter(s => s.status === 'empty');
        const filled = list.filter(s => s.status !== 'empty');

        document.getElementById('sources').innerHTML = filled.map(s => {
            const [badgeClass, badgeText] = STATUS[s.status];
            return `
                <div class="card source-card" style="--group:${groupColor(s.group)}">
                    <div class="source-top">
                        <div class="source-icon">${icon(s.icon)}</div>
                        <div style="min-width:0">
                            <div class="source-name">${esc(s.label)}</div>
                            <div class="source-group">${esc(s.group)}</div>
                        </div>
                        <span class="badge ${badgeClass}"><span class="dot"></span>${badgeText}</span>
                    </div>
                    <div class="source-rows">
                        <div>
                            <strong class="num">${fmt.number(s.rows)}</strong>
                            <small>qator</small>
                        </div>
                        ${sparkline(stats.daily.series[s.key])}
                    </div>
                    <div class="source-chips">
                        <span class="badge ${s.today ? 'badge-success' : ''}">+${fmt.number(s.today)} bugun</span>
                        <span class="badge ${s.week ? 'badge-primary' : ''}">+${fmt.number(s.week)} 7 kun</span>
                    </div>
                    <div class="source-foot">
                        <span title="${s.last_at ? fmt.dateTime(s.last_at) : ''}">${icon('clock', 'icon-sm')} ${s.last_at ? fmt.relative(s.last_at) : "Ma'lumot yo'q"}${s.size_mb ? ` · ${fmt.number(s.size_mb)} MB` : ''}</span>
                        <a href="${esc(s.url)}">Ochish ${icon('arrow-right', 'icon-sm')}</a>
                    </div>
                </div>`;
        }).join('') + (empty.length ? `
            <div class="card empty-sources">
                <div class="card-title">${icon('database', 'icon-sm')} Ma'lumot yuklanmagan manbalar <span class="muted">(${empty.length})</span></div>
                <div class="empty-links">${empty.map(s => `
                    <a href="${esc(s.url)}" style="--group:${groupColor(s.group)}"><i></i>${esc(s.label)} <span class="muted">· ${esc(s.group)}</span></a>`).join('')}</div>
            </div>` : '') || `<div class="empty">Manba topilmadi</div>`;
    }

    // ===== Yuklashlar tarixi =====
    function renderUploads(items) {
        const box = document.getElementById('uploads');
        document.getElementById('moreUploads').hidden = items.length < uploadsLimit || uploadsLimit >= 50;
        if (!items.length) {
            box.innerHTML = `<div class="empty">${icon('file')}<div>Hali fayl yuklanmagan</div></div>`;
            return;
        }

        const status = {
            success: ['badge-success', 'Tayyor'],
            failed: ['badge-danger', 'Xatolik'],
            processing: ['badge-info', 'Bajarilmoqda'],
            pending: ['', 'Navbatda'],
        };

        box.innerHTML = `
            <table class="table">
                <thead><tr><th>Vaqt</th><th>Manba</th><th>Fayl</th><th class="num">Natija</th></tr></thead>
                <tbody>${items.map(u => {
                    const [cls, text] = status[u.status] || ['', u.status];
                    const result = u.status === 'success'
                        ? (u.added !== null ? `+${fmt.number(u.added)} qator` : `${fmt.number(u.rows || 0)} qator`)
                        : '';
                    return `<tr>
                        <td title="${esc(fmt.dateTime(u.created_at))}" style="white-space:nowrap">${esc(fmt.relative(u.created_at))}
                            <div class="muted" style="font-size:.78rem">${esc(u.user || '—')}</div></td>
                        <td style="white-space:nowrap">${esc(u.label)}</td>
                        <td><div class="upload-file" title="${esc(u.file_name || '')}">${esc(u.file_name || '—')}</div>
                            ${u.error ? `<div class="upload-error" title="${esc(u.error)}">${esc(u.error)}</div>` : ''}</td>
                        <td class="num" style="white-space:nowrap"><span class="badge ${cls}">${text}</span>
                            ${result ? `<div class="muted num" style="font-size:.8rem;margin-top:2px">${result}</div>` : ''}</td>
                    </tr>`;
                }).join('')}</tbody>
            </table>`;
    }

    // ===== O'zbekiston firmalari =====
    function renderUz() {
        const uz = stats.uzbekistan;
        const data = uz[uzDataset];
        document.getElementById('uzFirms').innerHTML = `
            <div class="uz-stats">
                <div class="uz-stat primary"><strong class="num">${fmt.number(uz.unique_total)}</strong><span>Jami takrorlanmas firma</span></div>
                <div class="uz-stat"><strong class="num">${fmt.number(uz.both)}</strong><span>Ham import, ham eksport qilgan</span></div>
                <div class="uz-stat"><strong class="num">${fmt.number(data.total)}</strong><span>${uzDataset === 'import' ? 'Import' : 'Eksport'} firmalari</span></div>
                <div class="uz-stat"><strong class="num">${fmt.number(data.with_contacts)}</strong><span>Xodimi bor (${data.total ? Math.round(data.with_contacts * 100 / data.total) : 0}%)</span></div>
            </div>
            <div class="card-title" style="font-size:.9rem">Eng ko'p qatorli firmalar</div>
            ${data.top.length ? `<ul class="top-firms">${data.top.map((f, i) => `
                <li>
                    <span class="rank">${i + 1}</span>
                    <a class="name" href="/uzbekistan-${uzDataset}?inn=${encodeURIComponent(f.inn)}" title="${esc(f.name)} — jadvalda ko'rish">${esc(f.name || f.inn)}</a>
                    ${f.has_contact ? `<span class="badge badge-success" title="Xodim ma'lumoti bor">${icon('user', 'icon-sm')}</span>` : ''}
                    <span class="rows num">${fmt.number(f.rows)} qator</span>
                </li>`).join('')}</ul>` : `<div class="empty">Ma'lumot yo'q</div>`}`;
    }

    // ===== Hodisalar =====
    document.getElementById('refreshBtn').addEventListener('click', () => load(true));
    document.getElementById('sourceSort').addEventListener('change', renderSources);
    document.getElementById('groupFilter').addEventListener('click', e => {
        const btn = e.target.closest('[data-group]');
        if (!btn) return;
        groupFilter = btn.dataset.group;
        renderGroupFilter();
        renderSources();
    });
    document.getElementById('dailyView').addEventListener('click', e => {
        const btn = e.target.closest('[data-view]');
        if (!btn) return;
        const table = btn.dataset.view === 'table';
        document.querySelectorAll('#dailyView button').forEach(b => b.classList.toggle('active', b === btn));
        document.getElementById('dailyChartBox').hidden = table;
        document.getElementById('dailyLegend').hidden = table;
        document.getElementById('dailyTable').hidden = !table;
    });
    document.getElementById('dailyView').querySelectorAll('button').forEach((b, i) => b.setAttribute('aria-label', i ? "Jadval ko'rinishi" : "Grafik ko'rinishi"));
    document.getElementById('uzTabs').addEventListener('click', e => {
        const btn = e.target.closest('[data-dataset]');
        if (!btn || !stats) return;
        uzDataset = btn.dataset.dataset;
        document.querySelectorAll('#uzTabs button').forEach(b => b.classList.toggle('active', b === btn));
        renderUz();
    });
    document.getElementById('moreUploads').addEventListener('click', () => {
        uploadsLimit = 50;
        loadUploads();
    });
    // rejim almashganda grafik ranglari yangi tokenlardan olinadi
    document.addEventListener('themechange', () => stats && render());

    load();
    // har 5 daqiqada avtomatik yangilanadi
    setInterval(() => document.visibilityState === 'visible' && load(), 5 * 60 * 1000);
})();
</script>
@endpush
