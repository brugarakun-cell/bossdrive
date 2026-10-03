<style>
    .staff-sidebar { width:250px; height:100vh; background:#212529; position:fixed; inset:0 auto 0 0; border-right:5px solid #dc3545; z-index:1000; overflow-y:auto; }
    .staff-sidebar .nav-link { color:#fff; padding:15px 20px; margin:5px 15px; border-radius:8px; font-size:.9rem; font-weight:600; transition:.3s; text-decoration:none; display:block; }
    .staff-sidebar .nav-link:hover { background:rgba(255,255,255,.1); color:#fff; }
    .staff-sidebar .nav-link.active { background:#dc3545; box-shadow:0 4px 10px rgba(220,53,69,.3); color:#fff; }
    .staff-main-content { margin-left:250px; min-height:100vh; }
    .staff-top-nav { margin-left:250px; width:calc(100% - 250px); min-height:68px; background:#fff; padding:15px 30px; box-shadow:0 2px 10px rgba(0,0,0,.05); display:flex; justify-content:space-between; align-items:center; gap:16px; }
    .staff-top-nav h5 { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .staff-top-nav > div { flex-shrink:0; }
    .staff-sidebar-toggle { display:none; }
    .staff-sidebar-backdrop { display:none; }
    .staff-page-loader {
        position:fixed;
        inset:0;
        z-index:3000;
        display:none;
        align-items:center;
        justify-content:center;
        background:rgba(33,37,41,.5);
    }
    .staff-page-loader.is-visible { display:flex; }
    .staff-page-loader .car-loader-icon { font-size:3rem; color:#fff; animation:car-loader-drive .8s ease-in-out infinite alternate; }
    @keyframes car-loader-drive { from { transform:translateX(-12px); } to { transform:translateX(12px); } }
    @media (max-width:992px) {
        .staff-sidebar-toggle {
            display:block;
            position:fixed;
            top:10px;
            left:10px;
            z-index:1100;
        }
        .staff-sidebar {
            transform:translateX(-100%);
            transition:transform .25s ease;
        }
        .staff-sidebar.is-open { transform:translateX(0); }
        .staff-sidebar-backdrop.is-visible {
            display:block;
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.45);
            z-index:999;
        }
        .staff-main-content { margin-left:0; }
        .staff-sidebar ~ .main-content { margin-left:0; width:100%; min-width:0; }
        .staff-top-nav { margin-left:0; width:100%; padding:12px 16px 12px 60px; }
    }
    @media (max-width:575.98px) {
        .staff-top-nav { min-height:56px; gap:8px; padding:9px 10px 9px 54px; }
        .staff-top-nav h5 { font-size:.82rem; line-height:1.2; }
        .staff-top-nav .text-end { margin-right:0 !important; }
        .staff-top-nav .text-end span:first-child { max-width:115px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .staff-sidebar-toggle { width:32px; height:32px; padding:5px; line-height:1; }
        .staff-sidebar-toggle i { font-size:.9rem; }
        .staff-main-content,
        .staff-sidebar ~ .main-content { width:100%; min-width:0; overflow-x:hidden; }
        .staff-main-content .container-fluid,
        .staff-main-content .content-container,
        .staff-sidebar ~ .main-content .container-fluid,
        .staff-sidebar ~ .main-content .content-container { padding:12px 9px !important; }
        .staff-main-content .p-4,
        .staff-sidebar ~ .main-content > .p-4 { padding:12px 9px !important; }
        .staff-main-content h1,
        .staff-sidebar ~ .main-content h1 { font-size:1.45rem; }
        .staff-main-content h2,
        .staff-sidebar ~ .main-content h2 { font-size:1.25rem; }
        .staff-main-content h3,
        .staff-sidebar ~ .main-content h3 { font-size:1.1rem; }
        .staff-main-content h4,
        .staff-sidebar ~ .main-content h4 { font-size:1rem; }
        .staff-main-content h5,
        .staff-sidebar ~ .main-content h5 { font-size:.95rem; }
        .staff-main-content h6,
        .staff-sidebar ~ .main-content h6 { font-size:.85rem; }
        .staff-main-content .btn:not(.btn-close),
        .staff-sidebar ~ .main-content .btn:not(.btn-close) { min-height:32px; padding:.35rem .65rem; font-size:.76rem; line-height:1.25; }
        .staff-main-content .btn.btn-sm:not(.btn-close),
        .staff-sidebar ~ .main-content .btn.btn-sm:not(.btn-close) { min-height:29px; padding:.25rem .5rem; font-size:.7rem; }
        .staff-main-content .form-control,
        .staff-main-content .form-select,
        .staff-sidebar ~ .main-content .form-control,
        .staff-sidebar ~ .main-content .form-select { min-height:36px; font-size:16px; }
        .staff-main-content .table-responsive,
        .staff-sidebar ~ .main-content .table-responsive { max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }
        .staff-main-content .table,
        .staff-sidebar ~ .main-content .table { font-size:.78rem; }
        .staff-main-content .modal-dialog,
        .staff-sidebar ~ .main-content .modal-dialog { margin:.5rem; }
        .staff-main-content .modal-body,
        .staff-sidebar ~ .main-content .modal-body { padding:1rem !important; }
        .staff-main-content .card,
        .staff-sidebar ~ .main-content .card,
        .staff-main-content .table-container,
        .staff-sidebar ~ .main-content .table-container,
        .staff-main-content .res-card,
        .staff-sidebar ~ .main-content .res-card { border-radius:13px; }
    }
    @media (max-width:767.98px) {
        .staff-sidebar ~ .main-content .row.text-center:not(.reservation-stat-row) > [class*="col-"] { flex:0 0 50%; max-width:50%; }
        .staff-sidebar ~ .main-content .row.text-center:not(.reservation-stat-row) .stat-card { height:100%; }
    }
</style>
@php
    $sharedStaffName = trim((string) (session('staff_name') ?: 'Staff User'));
    $sharedStaffAccount = $staffAccount ?? (isset($staffUser) ? $staffUser : (session('staff_user_id') ? \App\Models\User::find(session('staff_user_id')) : null));
    $sharedStaffInitials = collect(preg_split('/\s+/', $sharedStaffName))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');
    $sharedStaffRoute = request()->route()?->getName() ?? '';
@endphp
<button type="button" class="btn btn-dark staff-sidebar-toggle shadow-sm" id="staffSidebarToggle" aria-label="Open staff navigation" aria-expanded="false">
    <i class="fas fa-bars"></i>
</button>
<div class="staff-sidebar-backdrop" id="staffSidebarBackdrop"></div>
<div class="staff-page-loader" id="staffPageLoader" aria-hidden="true">
    <div class="text-center text-white">
        <div role="status" aria-live="polite"><i class="fas fa-car-side car-loader-icon" aria-hidden="true"></i><span class="visually-hidden">Loading...</span></div>
        <div class="small fw-bold mt-2">Loading...</div>
    </div>
</div>
<aside class="staff-sidebar">
    <div class="p-4 text-center">
        <img src="{{ asset('image/1.png') }}" class="img-fluid mb-2" style="max-height:80px;" alt="Big Boss Car Rental">
        <p class="text-white fw-bold small mb-0 text-uppercase">Staff Panel</p>
    </div>
    <nav class="nav flex-column mt-3">
        <a class="nav-link {{ str_starts_with($sharedStaffRoute, 'staff.reservations') || request()->routeIs('staff.walk-in-bookings') ? 'active' : '' }}" href="{{ route('staff.reservations') }}"><i class="fas fa-calendar-check me-2"></i>Reservations Management</a>
        <a class="nav-link {{ str_starts_with($sharedStaffRoute, 'staff.vehicles') ? 'active' : '' }}" href="{{ route('staff.vehicles') }}"><i class="fas fa-car me-2"></i>Vehicle Management</a>
        <a class="nav-link {{ str_starts_with($sharedStaffRoute, 'staff.drivers') ? 'active' : '' }}" href="{{ route('staff.drivers') }}"><i class="fas fa-id-badge me-2"></i>Drivers</a>
        <a class="nav-link {{ str_starts_with($sharedStaffRoute, 'staff.billing') ? 'active' : '' }}" href="{{ route('staff.billing') }}"><i class="fas fa-file-invoice-dollar me-2"></i>Billing Records</a>
        <a class="nav-link {{ str_starts_with($sharedStaffRoute, 'staff.active-rentals') ? 'active' : '' }}" href="{{ route('staff.active-rentals') }}"><i class="fas fa-key me-2"></i>Active Rentals</a>
        <a class="nav-link {{ str_starts_with($sharedStaffRoute, 'staff.reports') ? 'active' : '' }}" href="{{ route('staff.reports') }}"><i class="fas fa-chart-bar me-2"></i>Reports</a>
    </nav>
</aside>
<header class="staff-top-nav">
    <h5 class="fw-bold mb-0 text-uppercase">{{ $staffPageTitle ?? 'Staff' }} <span class="text-danger">{{ $staffPageAccent ?? '' }}</span></h5>
    <div class="d-flex align-items-center">
        <div class="text-end me-3">
            <span class="fw-bold d-block small">{{ $sharedStaffName }}</span>
            <span class="text-muted small" style="font-size:10px;">AUTHORIZED STAFF</span>
        </div>
        <a href="{{ route('staff.profile') }}" class="text-decoration-none" title="My Profile">
            @if($sharedStaffAccount?->profile_photo_path)
                <img src="{{ asset('storage/'.$sharedStaffAccount->profile_photo_path) }}" class="rounded-circle shadow-sm" width="35" height="35" alt="Staff profile">
            @else
                <span class="rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center text-white fw-bold" style="width:35px;height:35px;background:#b71c1c;">{{ $sharedStaffInitials ?: 'SU' }}</span>
            @endif
        </a>
    </div>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.querySelector('.staff-sidebar');
        const toggle = document.getElementById('staffSidebarToggle');
        const backdrop = document.getElementById('staffSidebarBackdrop');
        const loader = document.getElementById('staffPageLoader');

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
            link.addEventListener('click', function () {
                const href = link.getAttribute('href') || '';
                if (href.startsWith('#') || link.hasAttribute('download') || link.dataset.bsToggle) {
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
