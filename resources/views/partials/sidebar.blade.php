<style>
    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: #121212;
        position: fixed;
        padding: 20px;
        border-right: 4px solid #b71c1c;
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    .brand-box {
        display: flex;
        justify-content: center;
        padding: 10px 8px 18px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .brand-logo {
        display: block;
        width: 100%;
        max-width: 190px;
        max-height: 90px;
        object-fit: contain;
    }

    .nav-link {
        color: #ffffff;
        margin-bottom: 10px;
        border-radius: 10px;
        padding: 12px 15px;
        font-weight: 600;
        text-decoration: none;
        display: block;
    }

    .nav-link:hover,
    .nav-link.active {
        background-color: #b71c1c;
        color: #ffffff !important;
    }

    .sidebar-footer {
        margin-top: auto;
        border-top: 1px solid #333;
        padding-top: 15px;
    }

    .sidebar-footer a {
        color: #888;
        text-decoration: none;
        font-size: 0.85rem;
    }

    .page-loader {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(18, 18, 18, 0.45);
    }

    .page-loader.is-visible {
        display: flex;
    }

    .page-loader .car-loader-icon {
        font-size: 3rem;
        color: #fff;
        animation: car-loader-drive .8s ease-in-out infinite alternate;
    }

    @keyframes car-loader-drive {
        from { transform: translateX(-12px); }
        to { transform: translateX(12px); }
    }

    @media (max-width: 991.98px) {
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            border-right: 4px solid #b71c1c;
            border-bottom: none;
            transform: translateX(-100%);
            transition: transform .25s ease;
            overflow-y: auto;
        }
        .sidebar.is-open {
            transform: translateX(0);
        }
    }
</style>

<button type="button" class="btn btn-dark d-lg-none position-fixed top-0 start-0 m-2 shadow-sm" id="mobileSidebarToggle" aria-label="Open navigation" aria-expanded="false" style="z-index:1100;">
    <i class="fas fa-bars"></i>
</button>
<div class="d-lg-none" id="mobileSidebarBackdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;"></div>

<aside class="sidebar">
    <div class="brand-box">
        <img src="{{ asset('image/1.png') }}" alt="Big Boss Car Rental" class="brand-logo">
    </div>

    <nav class="nav flex-column mt-4">
        @auth
        <a class="nav-link" href="{{ route('user.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a class="nav-link" href="{{ route('user.reservations') }}">
            <i class="fas fa-calendar-check me-2"></i> Reservations
        </a>
        <a class="nav-link" href="{{ route('user.active-rental') }}">
            <i class="fas fa-car me-2"></i> Active Rental
        </a>
        <a class="nav-link" href="{{ route('user.payments') }}">
            <i class="fas fa-credit-card me-2"></i> Payments
        </a>
        <a class="nav-link" href="{{ route('user.account') }}">
            <i class="fas fa-user-circle me-2"></i> Account
        </a>
        @else
        <a class="nav-link {{ request()->is('reservations') ? 'active' : '' }}" href="{{ url('/reservations') }}">
            <i class="fas fa-calendar-check me-2"></i> Reservations
        </a>
        <a class="nav-link {{ request()->is('policy') ? 'active' : '' }}" href="{{ url('/policy') }}">
            <i class="fas fa-car me-2"></i> Policy
        </a>
        <a class="nav-link {{ request()->is('about') ? 'active' : '' }}" href="{{ url('/about') }}">
            <i class="fas fa-info-circle me-2"></i> About Us
        </a>
        <a class="nav-link {{ request()->is('contact') ? 'active' : '' }}" href="{{ url('/contact') }}">
            <i class="fas fa-user-circle me-2"></i> Contact
        </a>
        @endauth
    </nav>

    <div class="sidebar-footer">
        @auth
        <small class="d-block mb-1"><a href="{{ route('policy') }}">Policy</a></small>
        <small class="d-block mb-1"><a href="{{ route('about') }}">About Us</a></small>
        <small class="d-block"><a href="{{ route('contact') }}">Contact</a></small>
        @else
        <a href="#">Need help?</a>
        @endauth
    </div>
</aside>

<div class="page-loader" id="pageLoader" aria-hidden="true">
    <div class="text-center text-white">
        <div role="status" aria-live="polite"><i class="fas fa-car-side car-loader-icon" aria-hidden="true"></i><span class="visually-hidden">Loading...</span></div>
        <div id="pageLoaderText" class="small fw-bold mt-2">Loading...</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const loader = document.getElementById('pageLoader');
        const sidebar = document.querySelector('.sidebar');
        const sidebarToggle = document.getElementById('mobileSidebarToggle');
        const sidebarBackdrop = document.getElementById('mobileSidebarBackdrop');

        function closeSidebar() {
            sidebar?.classList.remove('is-open');
            if (sidebarBackdrop) sidebarBackdrop.style.display = 'none';
            sidebarToggle?.setAttribute('aria-expanded', 'false');
        }

        sidebarToggle?.addEventListener('click', function () {
            const isOpen = sidebar?.classList.toggle('is-open');
            if (sidebarBackdrop) sidebarBackdrop.style.display = isOpen ? 'block' : 'none';
            sidebarToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        sidebarBackdrop?.addEventListener('click', closeSidebar);

        document.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                const href = link.getAttribute('href');

                if (!href || href === '#' || href.startsWith('#') || link.target === '_blank') {
                    return;
                }

                const loaderText = document.getElementById('pageLoaderText');
                if (loaderText) {
                    loaderText.textContent = link.dataset.loadingLabel || 'Loading...';
                }
                loader.classList.add('is-visible');
                loader.setAttribute('aria-hidden', 'false');
            });
        });
    });
</script>
