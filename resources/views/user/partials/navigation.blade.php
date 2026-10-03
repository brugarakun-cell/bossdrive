@php
    $navigationUser = $user ?? auth()->user();
    $navigationTitle = $title ?? 'USER';
    $navigationAccent = $accent ?? '';
@endphp

<style>
    .user-sidebar {
        width: 250px;
        height: 100vh;
        background: #121212;
        position: fixed;
        inset: 0 auto 0 0;
        padding: 20px;
        border-right: 4px solid #b71c1c;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    .user-sidebar-logo { display:block; width:100%; max-width:190px; max-height:90px; object-fit:contain; margin:0 auto; }
    .user-sidebar .nav-link {
        color:#fff;
        margin-bottom:10px;
        border-radius:10px;
        padding:12px 15px;
        font-weight:600;
        text-decoration:none;
        display:block;
    }
    .user-sidebar .nav-link:hover,
    .user-sidebar .nav-link.active { background:#b71c1c; color:#fff !important; }
    .user-sidebar-footer { margin-top:auto; border-top:1px solid #333; padding-top:15px; }
    .user-sidebar-footer a { color:#888; text-decoration:none; font-size:.85rem; }
    .user-sidebar-footer a:hover { color:#fff; }
    .user-main-content { margin-left:250px; min-height:100vh; }
    .user-top-nav {
        background:#fff;
        padding:15px 30px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        border-bottom:1px solid #ccc;
        position:sticky;
        top:0;
        z-index:999;
    }
    .user-top-nav h4 { color:#000; font-weight:800; }
    .user-top-nav .accent { color:#b71c1c; }
    .user-sidebar-toggle { display:none; }
    .user-sidebar-toggle-icon { display:flex; width:18px; flex-direction:column; gap:4px; }
    .user-sidebar-toggle-icon span { display:block; width:100%; height:2px; border-radius:2px; background:currentColor; }
    .user-sidebar-backdrop { display:none; }
    .user-navigation-loader {
        position:fixed;
        inset:0;
        z-index:3000;
        display:none;
        align-items:center;
        justify-content:center;
        background:rgba(18,18,18,.5);
    }
    .user-navigation-loader.is-visible { display:flex; }
    .user-navigation-loader .car-loader-icon { font-size:3rem; color:#fff; animation:car-loader-drive .8s ease-in-out infinite alternate; }
    @keyframes car-loader-drive { from { transform:translateX(-12px); } to { transform:translateX(12px); } }
    @media (max-width:991.98px) {
        .user-sidebar-toggle {
            display:block;
            position:fixed;
            top:10px;
            left:10px;
            z-index:1100;
            width:34px;
            height:34px;
            padding:7px;
            line-height:1;
        }
        .user-sidebar {
            transform:translateX(-100%);
            transition:transform .25s ease;
        }
        .user-sidebar.is-open { transform:translateX(0); }
        .user-sidebar-backdrop.is-visible {
            display:block;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.45);
            z-index:999;
        }
        .user-main-content { margin-left:0; }
        .user-top-nav { padding:12px 16px 12px 60px; }
    }
    @media (max-width:575.98px) {
        .user-main-content { width:100%; min-width:0; overflow-x:clip; }
        .user-top-nav { gap:8px; padding:10px 10px 10px 56px; }
        .user-top-nav h4 { min-width:0; font-size:1rem; line-height:1.2; }
        .user-top-nav > a { gap:8px !important; min-width:0; }
        .user-top-nav > a img { width:34px; height:34px; }
        .user-sidebar-toggle { width:32px; height:32px; padding:6px; }
        .user-sidebar-toggle-icon { width:16px; gap:3px; }
        .user-sidebar-toggle-icon span { height:2px; }
        .user-main-content .content-container { padding:12px 9px 22px; }
        .user-main-content .row { --bs-gutter-x:.75rem; --bs-gutter-y:.75rem; }
        .user-main-content .card,
        .user-main-content .modal-content { min-width:0; }
        .user-main-content h1 { font-size:1.5rem; }
        .user-main-content h2 { font-size:1.3rem; }
        .user-main-content h3 { font-size:1.15rem; }
        .user-main-content h4 { font-size:1.05rem; }
        .user-main-content h5 { font-size:.98rem; }
        .user-main-content h6 { font-size:.9rem; }
        .user-main-content .btn:not(.btn-close) { min-height:34px; padding:.38rem .7rem; font-size:.78rem; line-height:1.25; }
        .user-main-content .btn.btn-sm:not(.btn-close) { min-height:30px; padding:.28rem .55rem; font-size:.72rem; }
        .user-main-content .form-control,
        .user-main-content .form-select { min-height:38px; padding:.45rem .65rem; font-size:16px; }
        .user-main-content .alert { padding:.65rem .75rem; font-size:.85rem; }
        .user-main-content .rounded-4 { border-radius:14px !important; }
        .user-main-content img,
        .user-main-content iframe { max-width:100%; }
        .user-main-content .table-responsive { -webkit-overflow-scrolling:touch; }
        .user-main-content .table { font-size:.8rem; }
        .user-main-content .modal-dialog { margin:.5rem; }
        .user-main-content .modal-body { padding:1rem !important; }
    }
</style>

<button type="button" class="btn btn-dark user-sidebar-toggle shadow-sm" id="userSidebarToggle" aria-label="Open navigation" aria-expanded="false">
    <span class="user-sidebar-toggle-icon" aria-hidden="true"><span></span><span></span><span></span></span>
</button>
<div class="user-sidebar-backdrop" id="userSidebarBackdrop"></div>
<div class="user-navigation-loader user-page-loader" id="userPageLoader" aria-hidden="true">
    <div class="text-center text-white">
        <div role="status" aria-live="polite"><i class="fas fa-car-side car-loader-icon" aria-hidden="true"></i><span class="visually-hidden">Loading...</span></div>
        <div class="small fw-bold mt-2">Loading...</div>
    </div>
</div>

<aside class="user-sidebar" id="userSidebar">
    <div class="text-center mb-4">
        <img src="{{ asset('image/1.png') }}" alt="Big Boss Car Rental" class="user-sidebar-logo">
    </div>

    <nav class="nav flex-column mt-4">
        <a class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}" href="{{ route('user.dashboard') }}">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a class="nav-link {{ request()->routeIs('user.reservations*') ? 'active' : '' }}" href="{{ route('user.reservations') }}">
            <i class="fas fa-calendar-check me-2"></i> Reservations
        </a>
        <a class="nav-link {{ request()->routeIs('user.active-rental') ? 'active' : '' }}" href="{{ route('user.active-rental') }}">
            <i class="fas fa-car me-2"></i> Active Rental
        </a>
        <a class="nav-link {{ request()->routeIs('user.payments*') ? 'active' : '' }}" href="{{ route('user.payments') }}">
            <i class="fas fa-credit-card me-2"></i> Payments
        </a>
        <a class="nav-link {{ request()->routeIs('user.account*') ? 'active' : '' }}" href="{{ route('user.account') }}">
            <i class="fas fa-user-circle me-2"></i> Account
        </a>
    </nav>

    <div class="user-sidebar-footer">
        <small class="d-block mb-1"><a href="{{ route('policy') }}">Policy</a></small>
        <small class="d-block mb-1"><a href="{{ route('about') }}">About Us</a></small>
        <small class="d-block mb-2"><a href="{{ route('contact') }}">Contact</a></small>
    </div>
</aside>

<main class="user-main-content">
    <header class="user-top-nav shadow-sm">
        <h4 class="mb-0">{{ $navigationTitle }}@if($navigationAccent) <span class="accent">{{ $navigationAccent }}</span>@endif</h4>
        <a href="{{ route('user.account') }}" class="d-flex align-items-center gap-3 text-decoration-none text-dark">
            <div class="text-end d-none d-md-block">
                <small class="text-muted d-block" style="font-size:.7rem;">{{ $navigationUser->city ?: 'GMA, Cavite' }} User</small>
                <span class="fw-bold" style="font-size:.9rem;">{{ $navigationUser->name }}</span>
            </div>
            <img src="{{ $navigationUser->profile_photo_path ? asset('storage/'.$navigationUser->profile_photo_path) : 'https://ui-avatars.com/api/?name='.urlencode($navigationUser->name).'&background=b71c1c&color=fff' }}" class="rounded-circle border" width="40" height="40" alt="{{ $navigationUser->name }}">
        </a>
    </header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('userSidebar');
        const toggle = document.getElementById('userSidebarToggle');
        const backdrop = document.getElementById('userSidebarBackdrop');

        function closeSidebar() {
            sidebar?.classList.remove('is-open');
            backdrop?.classList.remove('is-visible');
            toggle?.setAttribute('aria-expanded', 'false');
        }

        toggle?.addEventListener('click', function () {
            const open = sidebar?.classList.toggle('is-open');
            backdrop?.classList.toggle('is-visible', Boolean(open));
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        backdrop?.addEventListener('click', closeSidebar);
        sidebar?.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
        document.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                const href = link.getAttribute('href');
                if (!href || href === '#' || href.startsWith('#') || link.target === '_blank'
                    || link.hasAttribute('download') || link.hasAttribute('data-bs-toggle')
                    || href.startsWith('mailto:') || href.startsWith('tel:')) return;
                const loader = document.getElementById('userPageLoader');
                loader?.classList.add('is-visible');
                loader?.setAttribute('aria-hidden', 'false');
            });
        });
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (form.hasAttribute('data-no-page-loader')) return;
                const loader = document.getElementById('userPageLoader');
                loader?.classList.add('is-visible');
                loader?.setAttribute('aria-hidden', 'false');
            });
        });
    });
</script>
