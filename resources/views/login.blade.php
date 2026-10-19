<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tizimga kirish · DataScraper</title>
    <script>
        try { const t = localStorage.getItem('theme'); if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t; } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/ui.css') }}?v={{ filemtime(public_path('css/ui.css')) }}">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px 16px;
            background:
                radial-gradient(1200px 600px at 10% -10%, color-mix(in srgb, var(--primary) 16%, transparent), transparent 60%),
                radial-gradient(900px 500px at 110% 110%, color-mix(in srgb, #3b82f6 14%, transparent), transparent 60%),
                var(--bg);
        }
        .login { width: 100%; max-width: 400px; }
        .login-brand { display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 700; font-size: 1.2rem; margin-bottom: 24px; }
        .login-brand .brand-mark { width: 38px; height: 38px; border-radius: 11px; }
        .login-card { padding: 28px; }
        .login-card h1 { font-size: 1.35rem; font-weight: 700; }
        .login-card > p { color: var(--text-2); margin: 4px 0 22px; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: .84rem; font-weight: 600; color: var(--text-2); margin-bottom: 6px; }
        .field .input { height: 42px; }
        .password { position: relative; }
        .password .input { padding-right: 44px; }
        .password button { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; border: none; background: none; color: var(--muted); cursor: pointer; border-radius: 6px; display: grid; place-items: center; }
        .password button:hover { color: var(--text); background: var(--surface-3); }
        .login-card .btn-primary { width: 100%; height: 42px; margin-top: 6px; }
        .login-error { display: flex; gap: 8px; align-items: flex-start; padding: 10px 12px; border-radius: var(--radius-sm); background: var(--danger-soft); color: var(--danger); font-size: .88rem; margin-bottom: 14px; }
        .login-foot { text-align: center; color: var(--muted); font-size: .82rem; margin-top: 18px; }
        .theme-btn { position: fixed; top: 16px; right: 16px; }
    </style>
</head>
<body>
@include('partials.icons')
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
        <symbol id="i-eye" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></symbol>
        <symbol id="i-eye-off" viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></symbol>
    </defs>
</svg>

<button type="button" class="btn btn-ghost btn-icon theme-btn" id="themeToggle" aria-label="Rejimni almashtirish"></button>

<main class="login">
    <div class="login-brand">
        <span class="brand-mark"><svg class="icon"><use href="#i-layers"/></svg></span>
        DataScraper
    </div>

    <div class="card login-card">
        <h1>Tizimga kirish</h1>
        <p>Davom etish uchun hisobingizga kiring</p>

        <div class="login-error" id="loginError" role="alert" hidden>
            <svg class="icon icon-sm"><use href="#i-alert"/></svg>
            <span></span>
        </div>

        <form id="loginForm" novalidate>
            <div class="field">
                <label for="email">Email</label>
                <input class="input" type="email" id="email" name="email" placeholder="email@example.com" autocomplete="username" required autofocus>
            </div>
            <div class="field">
                <label for="password">Parol</label>
                <div class="password">
                    <input class="input" type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                    <button type="button" id="togglePassword" aria-label="Parolni ko'rsatish"><svg class="icon icon-sm"><use href="#i-eye"/></svg></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" id="loginBtn">Kirish</button>
        </form>
    </div>

    <div class="login-foot">&copy; {{ date('Y') }} DataScraper</div>
</main>

<script>
(function () {
    const form = document.getElementById('loginForm');
    const errorBox = document.getElementById('loginError');
    const btn = document.getElementById('loginBtn');
    const passwordInput = document.getElementById('password');

    // allaqachon kirgan bo'lsa — to'g'ridan-to'g'ri dashboard'ga
    const saved = localStorage.getItem('token');
    if (saved) {
        fetch('/api/user', { headers: { 'Authorization': `Bearer ${saved}`, 'Accept': 'application/json' } })
            .then(r => { if (r.ok) window.location.href = '/dashboard'; else localStorage.removeItem('token'); })
            .catch(() => {});
    }

    // yorug' / qorong'i rejim
    const themeBtn = document.getElementById('themeToggle');
    const isDark = () => (document.documentElement.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')) === 'dark';
    const paintTheme = () => { themeBtn.innerHTML = `<svg class="icon"><use href="#i-${isDark() ? 'sun' : 'moon'}"/></svg>`; };
    themeBtn.addEventListener('click', () => {
        const next = isDark() ? 'light' : 'dark';
        document.documentElement.dataset.theme = next;
        try { localStorage.setItem('theme', next); } catch (e) {}
        paintTheme();
    });
    paintTheme();

    document.getElementById('togglePassword').addEventListener('click', e => {
        const show = passwordInput.type === 'password';
        passwordInput.type = show ? 'text' : 'password';
        e.currentTarget.innerHTML = `<svg class="icon icon-sm"><use href="#i-${show ? 'eye-off' : 'eye'}"/></svg>`;
        e.currentTarget.setAttribute('aria-label', show ? 'Parolni yashirish' : "Parolni ko'rsatish");
    });

    function showError(message) {
        errorBox.hidden = !message;
        errorBox.querySelector('span').textContent = message || '';
    }

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();
        const password = passwordInput.value;
        if (!email || !password) return showError('Email va parolni kiriting');

        showError('');
        btn.disabled = true;
        btn.textContent = 'Kirilmoqda...';

        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ email, password }),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.token) {
                showError(result.message || "Email yoki parol noto'g'ri");
                return;
            }
            localStorage.setItem('token', result.token);
            window.location.href = '/dashboard';
        } catch (err) {
            showError("Server bilan bog'lanishda xatolik");
        } finally {
            btn.disabled = false;
            btn.textContent = 'Kirish';
        }
    });
})();
</script>
</body>
</html>
