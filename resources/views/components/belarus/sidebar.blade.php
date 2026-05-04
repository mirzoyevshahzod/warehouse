<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <svg class="menu-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
            </svg>
            Dashboard
        </div>
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="{{ route('belarusBenyakoni') }}"
               class="{{ request()->routeIs('belarusBenyakoni') ? 'active' : '' }}">

                Benyakoni
            </a>
        </li>

        <li>
            <a href="{{ route('belarusBrest') }}"
               class="{{ request()->routeIs('belarusBrest') ? 'active' : '' }}">

                Brest
            </a>
        </li>

        <li>
            <a href="{{ route('belarusGigorovschina') }}"
               class="{{ request()->routeIs('belarusGigorovschina') ? 'active' : '' }}">

                Gigorovschina
            </a>
        </li>

        <li>
            <a href="{{ route('belarusKeminnii') }}"
               class="{{ request()->routeIs('belarusKeminnii') ? 'active' : '' }}">

                Keminnii-log
            </a>
        </li>

        <li>
            <a href="{{ route('belarusKozlovichi') }}"
               class="{{ request()->routeIs('belarusKozlovichi') ? 'active' : '' }}">

                Kozlovichi
            </a>
        </li>

        <li>
            <a href="/dashboard">
                Bosh Sahifaga Qaytish
            </a>
        </li>

    </ul>
</aside>
