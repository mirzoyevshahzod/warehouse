<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'DataScraper') · DataScraper</title>
    {{-- mavzu sahifa chizilishidan oldin qo'yiladi (yorug'/qorong'i "miltillamasligi" uchun) --}}
    <script>
        try { const t = localStorage.getItem('theme'); if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t; } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/ui.css') }}?v={{ filemtime(public_path('css/ui.css')) }}">
    @stack('styles')
</head>
<body>
@include('partials.icons')

<div class="app">
    @include('partials.sidebar')
    <div class="sidebar-backdrop"></div>

    <div class="app-main">
        <header class="topbar">
            <button type="button" class="btn btn-ghost btn-icon menu-btn" id="menuBtn" aria-label="Menyuni ochish">
                <svg class="icon"><use href="#i-menu"/></svg>
            </button>
            <div class="topbar-title">@yield('title', 'DataScraper')</div>
            <div class="topbar-actions">
                <button type="button" class="btn btn-ghost btn-icon" id="themeToggle" aria-label="Rejimni almashtirish"></button>
                <div class="menu-wrap">
                    <button type="button" class="avatar" id="userBtn" aria-haspopup="true" aria-expanded="false" aria-label="Foydalanuvchi menyusi">·</button>
                    <div class="menu" id="userMenu" hidden>
                        <div class="menu-header">
                            <strong id="userName">…</strong>
                            <span class="muted" id="userEmail"></span>
                        </div>
                        <a href="/profile" class="menu-item"><svg class="icon"><use href="#i-user"/></svg>Profil</a>
                        <button type="button" class="menu-item danger" id="logoutBtn"><svg class="icon"><use href="#i-logout"/></svg>Chiqish</button>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            @yield('content')
        </main>
    </div>
</div>

<script src="{{ asset('js/ui.js') }}?v={{ filemtime(public_path('js/ui.js')) }}"></script>
@stack('scripts')
</body>
</html>
