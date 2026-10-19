/**
 * DataScraper — umumiy JS (layouts/app.blade.php): kirish tekshiruvi, API so'rovlari,
 * yorug'/qorong'i rejim, sidebar, foydalanuvchi menyusi, toast va formatlash.
 * Sahifalar window.App orqali foydalanadi.
 */
(function () {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = '/';
        return;
    }

    const storage = {
        get(key) { try { return localStorage.getItem(key); } catch (e) { return null; } },
        set(key, value) { try { localStorage.setItem(key, value); } catch (e) { /* maxfiy rejim */ } },
    };

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

        const data = response.headers.get('content-type')?.includes('application/json') ? await response.json() : null;
        if (!response.ok) throw new Error(data?.message || 'Xatolik yuz berdi');
        return data;
    }

    // ===== Formatlash =====
    const numberFormat = new Intl.NumberFormat('ru-RU');
    const compactFormat = new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 });

    const fmt = {
        number: v => numberFormat.format(Number(v) || 0),
        compact: v => compactFormat.format(Number(v) || 0),
        date: v => v ? new Date(v).toLocaleDateString('ru-RU') : '—',
        dateTime: v => v ? new Date(v).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—',
        time: v => v ? new Date(v).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }) : '—',
        /** "5 daqiqa oldin", "3 kun oldin" */
        relative(v) {
            if (!v) return 'hech qachon';
            const seconds = Math.round((Date.now() - new Date(v).getTime()) / 1000);
            if (seconds < 60) return 'hozirgina';
            const units = [[60, 'daqiqa'], [24, 'soat'], [30, 'kun'], [12, 'oy'], [Infinity, 'yil']];
            let value = Math.floor(seconds / 60);
            for (const [size, name] of units) {
                if (value < size) return `${value} ${name} oldin`;
                value = Math.floor(value / size);
            }
        },
        duration(ms) {
            if (ms === null || ms === undefined) return '—';
            if (ms < 1000) return `${ms} ms`;
            const s = Math.round(ms / 1000);
            return s < 60 ? `${s} s` : `${Math.floor(s / 60)} daq ${s % 60} s`;
        },
    };

    function esc(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function icon(name, cls = '') {
        return `<svg class="icon ${cls}" aria-hidden="true"><use href="#i-${name}"></use></svg>`;
    }

    // ===== Toast =====
    function toast(message, type = 'success') {
        let box = document.querySelector('.toasts');
        if (!box) {
            box = document.createElement('div');
            box.className = 'toasts';
            box.setAttribute('role', 'status');
            document.body.appendChild(box);
        }
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.innerHTML = `${icon(type === 'error' ? 'alert' : 'check')}<span>${esc(message)}</span>`;
        box.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    // ===== Yorug' / qorong'i rejim =====
    function currentTheme() {
        const saved = document.documentElement.dataset.theme;
        if (saved) return saved;
        return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function setTheme(theme) {
        document.documentElement.dataset.theme = theme;
        storage.set('theme', theme);
        updateThemeButton();
        document.dispatchEvent(new CustomEvent('themechange', { detail: theme }));
    }

    function updateThemeButton() {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;
        const dark = currentTheme() === 'dark';
        btn.innerHTML = icon(dark ? 'sun' : 'moon');
        btn.title = dark ? "Yorug' rejim" : "Qorong'i rejim";
    }

    /** CSS token qiymati (grafiklar uchun) */
    function cssVar(name) {
        return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    }

    // ===== Sidebar va menyular =====
    function initLayout() {
        const app = document.querySelector('.app');

        document.getElementById('menuBtn')?.addEventListener('click', () => app.classList.add('sidebar-open'));
        document.querySelector('.sidebar-backdrop')?.addEventListener('click', () => app.classList.remove('sidebar-open'));

        // guruhlarni yig'ish/yoyish — holati eslab qolinadi; faol sahifa guruhi doim ochiq
        let collapsed = [];
        try { collapsed = JSON.parse(storage.get('sidebar.collapsed') || '[]'); } catch (e) {}
        document.querySelectorAll('.nav-group').forEach(group => {
            const name = group.dataset.group;
            if (collapsed.includes(name) && !group.querySelector('.nav-item.active')) group.classList.add('collapsed');
            group.querySelector('.nav-group-toggle').addEventListener('click', () => {
                group.classList.toggle('collapsed');
                const names = [...document.querySelectorAll('.nav-group.collapsed')].map(g => g.dataset.group);
                storage.set('sidebar.collapsed', JSON.stringify(names));
                group.querySelector('.nav-group-toggle').setAttribute('aria-expanded', String(!group.classList.contains('collapsed')));
            });
        });

        updateThemeButton();
        document.getElementById('themeToggle')?.addEventListener('click', () => setTheme(currentTheme() === 'dark' ? 'light' : 'dark'));

        const userBtn = document.getElementById('userBtn');
        const userMenu = document.getElementById('userMenu');
        userBtn?.addEventListener('click', e => {
            e.stopPropagation();
            userMenu.hidden = !userMenu.hidden;
            userBtn.setAttribute('aria-expanded', String(!userMenu.hidden));
        });
        document.addEventListener('click', e => {
            if (userMenu && !userMenu.hidden && !e.target.closest('#userMenu')) userMenu.hidden = true;
        });
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            if (userMenu) userMenu.hidden = true;
            app.classList.remove('sidebar-open');
        });

        document.getElementById('logoutBtn')?.addEventListener('click', logout);
        loadUser();
    }

    async function loadUser() {
        try {
            const user = await api('/api/user');
            const name = user.name || user.email || 'Foydalanuvchi';
            document.getElementById('userName').textContent = name;
            document.getElementById('userEmail').textContent = user.email || '';
            document.getElementById('userBtn').innerHTML = user.avatar
                ? `<img src="${esc(user.avatar)}" alt="">`
                : esc(name.charAt(0).toUpperCase());
            App.user = user;
        } catch (e) {
            console.error(e);
        }
    }

    async function logout() {
        try {
            await api('/api/logout', { method: 'POST' });
        } catch (e) {
            console.error(e);
        } finally {
            localStorage.removeItem('token');
            window.location.href = '/';
        }
    }

    window.App = { token, api, fmt, esc, icon, toast, cssVar, currentTheme, storage, user: null };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLayout);
    } else {
        initLayout();
    }
})();
