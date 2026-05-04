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
            <a href="{{ route('eOmborATScraping') }}" class="{{ request()->routeIs('eOmborATScraping') ? 'active' : '' }}">

                E Ombor Scraping AT Bo'yicha
            </a>
        </li>
        <li>
            <a href="{{ route('eOmborRWScraping') }}" class="{{ request()->routeIs('eOmborRWScraping') ? 'active' : '' }}">

                E Ombor Scraping RW Bo'yicha
            </a>
        </li>
        <li>
            <a href="{{ route('eOmborAVIAScraping') }}" class="{{ request()->routeIs('eOmborAVIAScraping') ? 'active' : '' }}">

                E Ombor Scraping AVIA Bo'yicha
            </a>
        </li>
        <li>
            <a href="/dashboard">

                Bosh Sahifaga Qaytish
            </a>
        </li>
    </ul>
</aside>
