{{-- Asosiy menyu: manbalar App\Support\DataSources ro'yxatidan guruhlab chiqariladi --}}
<aside class="sidebar" id="sidebar" aria-label="Asosiy menyu">
    <a href="/dashboard" class="sidebar-brand">
        <span class="brand-mark"><svg class="icon"><use href="#i-layers"/></svg></span>
        DataScraper
    </a>

    <nav class="sidebar-nav">
        <a href="/dashboard" class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
            <svg class="icon"><use href="#i-dashboard"/></svg>
            Dashboard
        </a>

        @foreach (\App\Support\DataSources::grouped() as $group => $sources)
            <div class="nav-group" data-group="{{ $group }}">
                <button type="button" class="nav-group-toggle" aria-expanded="true">
                    {{ $group }}
                    <svg class="icon"><use href="#i-chevron-down"/></svg>
                </button>
                <div class="nav-group-items">
                    @foreach ($sources as $source)
                        <a href="{{ route($source['route']) }}" class="nav-item {{ request()->routeIs($source['route']) ? 'active' : '' }}">
                            <svg class="icon"><use href="#i-{{ $source['icon'] }}"/></svg>
                            {{ $source['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <a href="/profile" class="nav-item {{ request()->is('profile') ? 'active' : '' }}">
            <svg class="icon"><use href="#i-user"/></svg>
            Profil
        </a>
    </div>
</aside>
