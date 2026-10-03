<style>
    .main-content { margin-left:250px; min-height:100vh; width:calc(100% - 250px); overflow-x:hidden; }
    .admin-sidebar { width:250px; height:100vh; background:#212529; position:fixed; inset:0 auto 0 0; border-right:5px solid #dc3545; z-index:1000; overflow-y:auto; }
    .admin-sidebar .nav-link { color:#fff; padding:15px 20px; margin:5px 15px; border-radius:8px; font-size:.9rem; font-weight:600; transition:.3s; text-decoration:none; display:block; }
    .admin-sidebar .nav-link:hover { background:rgba(255,255,255,.1); color:#fff; }
    .admin-sidebar .nav-link.active { background:#dc3545; box-shadow:0 4px 10px rgba(220,53,69,.3); color:#fff; }
    .admin-top-nav { margin-left:250px; width:calc(100% - 250px); min-height:68px; background:#fff; padding:15px 30px; box-shadow:0 2px 10px rgba(0,0,0,.05); display:flex; justify-content:space-between; align-items:center; gap:16px; }
    .admin-top-nav h5 { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .admin-top-nav > div { flex-shrink:0; }
    .admin-sidebar-toggle { display:none; }
    .admin-sidebar-backdrop { display:none; }
    .admin-page-loader {
        position:fixed;
        inset:0;
        z-index:3000;
        display:none;
        align-items:center;
        justify-content:center;
        background:rgba(33,37,41,.5);
    }
    .admin-page-loader.is-visible { display:flex; }
    .admin-page-loader .car-loader-icon { font-size:3rem; color:#fff; animation:car-loader-drive .8s ease-in-out infinite alternate; }
    @keyframes car-loader-drive { from { transform:translateX(-12px); } to { transform:translateX(12px); } }
    @media (max-width:992px) {
        .admin-sidebar-toggle {
            display:block;
            position:fixed;
            top:10px;
            left:10px;
            z-index:1100;
        }
        .admin-sidebar {
            transform:translateX(-100%);
            transition:transform .25s ease;
        }
        .admin-sidebar.is-open { transform:translateX(0); }
        .admin-sidebar-backdrop.is-visible {
            display:block;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.45);
            z-index:999;
        }
        .main-content { margin-left:0; width:100%; }
        .admin-top-nav { margin-left:0; width:100%; padding:12px 16px 12px 60px; }
    }
    @media (max-width:575.98px) {
        .admin-top-nav { min-height:60px; }
        .admin-top-nav h5 { font-size:.85rem; }
        .admin-top-nav .text-end { margin-right:0 !important; }
        .admin-top-nav .text-end span:first-child { max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    }
</style>
@php
    $adminRoute = request()->route()?->getName() ?? '';
    $adminName = trim((string) (session('admin_name') ?: 'Admin Patrick'));
    $adminAccount = $adminAccount ?? (session('admin_user_id') ? \App\Models\User::find(session('admin_user_id')) : null);
    $adminInitials = collect(preg_split('/\s+/', $adminName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
@endphp
<button type="button" class="btn btn-dark admin-sidebar-toggle shadow-sm" id="adminSidebarToggle" aria-label="Open admin navigation" aria-expanded="false">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>
<div class="admin-page-loader" id="adminPageLoader" aria-hidden="true">
    <div class="text-center text-white">
        <div role="status" aria-live="polite"><i class="fas fa-car-side car-loader-icon" aria-hidden="true"></i><span class="visually-hidden">Loading...</span></div>
        <div class="small fw-bold mt-2">Loading...</div>
    </div>
</div>
<aside class="admin-sidebar">
    <div class="p-4 text-center">
        <img src="{{ asset('image/1.png') }}" class="img-fluid mb-2" style="max-height:80px;" alt="Big Boss Car Rental">
        <div class="text-white fw-bold small text-uppercase">Admin Panel</div>
    </div>
    <nav class="nav flex-column mt-3">
        <a class="nav-link {{ str_starts_with($adminRoute, 'admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="fas fa-chart-line fa-fw me-2"></i>Dashboard</a>
        <a class="nav-link {{ str_starts_with($adminRoute, 'admin.reservations') || request()->routeIs('admin.walk-in-bookings') ? 'active' : '' }}" href="{{ route('admin.reservations') }}"><i class="fas fa-calendar-check me-2"></i>Reservations Management</a>
        <a class="nav-link {{ str_starts_with($adminRoute, 'admin.vehicles') ? 'active' : '' }}" href="{{ route('admin.vehicles') }}"><i class="fas fa-car me-2"></i>Vehicle Management</a>
        <a class="nav-link {{ str_starts_with($adminRoute, 'admin.drivers') ? 'active' : '' }}" href="{{ route('admin.drivers') }}"><i class="fas fa-id-badge me-2"></i>Drivers</a>
        <a class="nav-link {{ str_starts_with($adminRoute, 'admin.billing') ? 'active' : '' }}" href="{{ route('admin.billing') }}"><i class="fas fa-file-invoice-dollar me-2"></i>Billing Records</a>
        <a class="nav-link {{ str_starts_with($adminRoute, 'admin.active-rentals') ? 'active' : '' }}" href="{{ route('admin.active-rentals') }}"><i class="fas fa-key fa-fw me-2"></i>Active Rentals</a>
        <a class="nav-link {{ str_starts_with($adminRoute, 'admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}"><i class="fas fa-users fa-fw me-2"></i>User Accounts</a>
        <a class="nav-link {{ str_starts_with($adminRoute, 'admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}"><i class="fas fa-chart-bar fa-fw me-2"></i>Reports</a>
    </nav>
</aside>
<header class="admin-top-nav">
    <h5 class="fw-bold mb-0 text-uppercase">{{ $adminPageTitle ?? 'Admin' }} <span class="text-danger">{{ $adminPageAccent ?? '' }}</span></h5>
    <div class="d-flex align-items-center">
        <div class="text-end me-3">
            <span class="fw-bold d-block small">{{ $adminName }}</span>
            <span class="text-muted small" style="font-size:10px;">SUPER ADMIN</span>
        </div>
        <a href="{{ route('admin.profile') }}" class="text-decoration-none" title="Admin Profile">
            @if($adminAccount?->profile_photo_path)
                <img src="{{ asset('storage/'.$adminAccount->profile_photo_path) }}" class="rounded-circle shadow-sm" width="35" height="35" style="object-fit:cover;" alt="Admin profile">
            @else
                <span class="rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center text-white fw-bold" style="width:35px;height:35px;background:#b71c1c;">{{ $adminInitials ?: 'AP' }}</span>
            @endif
        </a>
    </div>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('.admin-sidebar');
        const toggle = document.getElementById('adminSidebarToggle');
        const backdrop = document.getElementById('adminSidebarBackdrop');
        const loader = document.getElementById('adminPageLoader');

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

        function showLoader() {
            loader?.classList.add('is-visible');
            loader?.setAttribute('aria-hidden', 'false');
        }

        document.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                const href = link.getAttribute('href') || '';
                if (href.startsWith('#') || link.hasAttribute('download') || link.dataset.bsToggle || link.hasAttribute('data-no-page-loader')) {
                    return;
                }
                closeSidebar();
                showLoader();
            });
        });
        document.querySelectorAll('form').forEach(function (form) {
            if (!form.hasAttribute('data-no-page-loader')) {
                form.addEventListener('submit', showLoader);
            }
        });
    });
</script>
