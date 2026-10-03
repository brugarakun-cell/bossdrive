<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BossDrive - Active Rentals</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #dc3545; --boss-dark: #212529; --boss-grey: #f8f9fa; }
        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar Consistency */
        .sidebar { width: 250px; height: 100vh; background-color: #212529; position: fixed; border-right: 5px solid #dc3545; z-index: 1000; }
        .sidebar .nav-link { color: white; padding: 15px 20px; margin: 5px 15px; border-radius: 8px; transition: 0.3s; font-size: 0.9rem; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { background-color: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); }

        .main-content { margin-left: 250px; min-height: 100vh; width: calc(100% - 250px); overflow-x: hidden; }
        .top-nav { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

        /* Tracking Cards */
        .stat-card { 
            border-radius: 15px; 
            border: none; 
            cursor: pointer; 
            transition: all 0.25s ease; 
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 18px rgba(0,0,0,0.1); }
        .stat-card.active-filter { box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.35), 0 8px 18px rgba(0,0,0,0.1); }
        
        /* Table & Status UI */
        .table-container { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .status-pill { 
            font-size: 0.7rem; 
            padding: 6px 12px; 
            border-radius: 50px; 
            font-weight: 800; 
            letter-spacing: 0.5px; 
            white-space: nowrap;
            display: inline-block;
        }
        .status-otr { background: #e7f5ff; color: #0d6efd; border: 1px solid #0d6efd; }
        .status-due { background: #fff9db; color: #f59f00; border: 1px solid #f59f00; }
        .status-overdue { background: #fff5f5; color: #dc3545; border: 1px solid #dc3545; animation: pulse-red 2s infinite; }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }

        .plate-number { font-family: 'Courier New', Courier, monospace; background: #eee; padding: 2px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #ccc; white-space: nowrap; }

        /* Control Number Tag */
        .control-tag {
            font-family: 'Consolas', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--boss-red);
            background: #fff5f5;
            border: 1px dashed var(--boss-red);
            padding: 3px 10px;
            border-radius: 6px;
            display: inline-block;
            white-space: nowrap;
        }

        .filter-active-banner { font-size: 0.8rem; font-weight: 700; }
        #activeRentalsTable { min-width: 1100px; table-layout: fixed; }
        #activeRentalsTable th:nth-child(1), #activeRentalsTable td:nth-child(1) { width: 18%; }
        #activeRentalsTable th:nth-child(2), #activeRentalsTable td:nth-child(2) { width: 16%; }
        #activeRentalsTable th:nth-child(3), #activeRentalsTable td:nth-child(3) { width: 15%; }
        #activeRentalsTable th:nth-child(4), #activeRentalsTable td:nth-child(4) { width: 14%; }
        #activeRentalsTable th:nth-child(5), #activeRentalsTable td:nth-child(5) { width: 16%; }
        #activeRentalsTable th:nth-child(6), #activeRentalsTable td:nth-child(6) { width: 10%; }
        #activeRentalsTable th:nth-child(7), #activeRentalsTable td:nth-child(7) { width: 15%; min-width:155px; }
        #activeRentalsTable th, #activeRentalsTable td { vertical-align: middle; }
        #activeRentalsTable td { overflow:hidden; }
        .rental-vehicle-name, .rental-client-name { display:block; max-width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .rental-client-id { font-size:.72rem; color:#6c757d; white-space:nowrap; }
        .rental-period { line-height:1.45; white-space:nowrap; }
        .rental-source-badge { display:block; width:max-content; margin-top:6px; font-size:.65rem; font-weight:800; padding:4px 9px; }
        .rental-action-btn { min-width:92px; }
        .rental-actions { display:flex; align-items:center; justify-content:center; gap:6px; flex-wrap:wrap; }
        .rental-actions .btn { margin:0 !important; }
        .rental-source-tabs { display:flex; gap:8px; flex-wrap:wrap; }
        .rental-source-tab { border:1px solid #dee2e6; background:#fff; color:#495057; border-radius:999px; padding:7px 16px; font-size:.78rem; font-weight:700; }
        .rental-source-tab.active, .rental-source-tab:hover { background:#212529; border-color:#212529; color:#fff; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; width: 100%; }
            .container-fluid { padding: 1rem !important; }
        }
        @media (max-width: 575.98px) {
            .container-fluid { padding: .75rem !important; }
            .table-container { padding: 14px; border-radius: 12px; }
            .table-container > .d-flex { align-items: stretch !important; flex-direction: column; gap: 10px; }
            .table-container > .d-flex > div { max-width: none !important; }
            .stat-card { padding: 1rem !important; }
            .stat-card h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    @php
        $isStaffMode = $isStaffMode ?? false;
        $activeReservationsBaseUrl = $isStaffMode ? url('/staff/reservations') : url('/admin/reservations');
        $extendRentalRoute = $isStaffMode ? 'staff.active-rentals.extend' : 'admin.active-rentals.extend';
    @endphp

    @if($isStaffMode)
        @include('staff.partials.navigation', ['staffPageTitle' => 'Active', 'staffPageAccent' => 'Rentals Tracking'])
    @else
        @include('admin.partials.navigation', ['adminPageTitle' => 'Active', 'adminPageAccent' => 'Rentals Tracking'])
    @endif

    <div class="main-content">
        <div class="container-fluid p-4">
            <!-- Functional Stat Cards -->
            <div class="row g-4 mb-4 text-center">
                <div class="col-md-4">
                    <div class="card stat-card p-4 shadow-sm" data-filter="all">
                        <i class="fas fa-car-side fa-2x text-primary mb-3"></i>
                        <small class="text-muted fw-bold d-block">UNITS ON THE ROAD</small>
                        <h2 class="fw-bold mb-0" id="statTotalUnits">{{ $processingCount + $releasedCount }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-4 shadow-sm border-bottom border-warning border-5" data-filter="due">
                        <i class="fas fa-history fa-2x text-warning mb-3"></i>
                        <small class="text-muted fw-bold d-block">DUE TODAY</small>
                        <h2 class="fw-bold mb-0 text-warning" id="statDueToday">{{ $todayCount }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-4 shadow-sm border-bottom border-danger border-5" data-filter="overdue">
                        <i class="fas fa-exclamation-circle fa-2x text-danger mb-3"></i>
                        <small class="text-muted fw-bold d-block">OVERDUE ALERT</small>
                        <h2 class="fw-bold mb-0 text-danger" id="statOverdue">{{ $overdueCount }}</h2>
                    </div>
                </div>
            </div>

            <div class="table-container shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                    <h6 class="fw-bold mb-0 text-uppercase small text-muted"><i class="fas fa-satellite-dish text-danger me-2"></i>Live Monitoring Logs</h6>
                    <div class="d-flex gap-2" style="max-width: 320px; width: 100%;">
                        <input type="text" id="activeSearchInput" class="form-control form-control-sm" placeholder="Search vehicle, plate, or control no..." onkeyup="filterActiveRentals()">
                    </div>
                </div>
                <div class="rental-source-tabs mb-3" role="tablist" aria-label="Rental source filter">
                    <button type="button" class="rental-source-tab active" data-source-filter="all">All Rentals</button>
                    <button type="button" class="rental-source-tab" data-source-filter="online">Online Rentals</button>
                    <button type="button" class="rental-source-tab" data-source-filter="walkin">Walk-in Rentals</button>
                </div>

                <div id="filterBanner" class="alert alert-danger py-2 px-3 filter-active-banner d-none d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-filter me-2"></i>Showing filtered view: <span id="filterLabel"></span></span>
                    <button class="btn btn-sm btn-outline-danger fw-bold" onclick="clearActiveFilter()">Clear Filter</button>
                </div>

                <div id="noResultMsg" class="alert alert-warning small fw-bold text-center d-none">
                    <i class="fas fa-exclamation-circle me-1"></i> No active rental found matching your criteria.
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-hover border-top" id="activeRentalsTable">
                        <thead class="bg-light">
                            <tr class="small text-muted text-uppercase">
                                <th class="py-3">Vehicle & Plate</th>
                                <th>Control Number</th>
                                <th>Client Name</th>
                                <th>Rental Period</th>
                                <th>Time Remaining</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reservations as $reservation)
                            @php
                                $isOverdue = $reservation->return_date->isPast() && !$reservation->return_date->isToday();
                                $isDue = $reservation->return_date->isToday();
                                $user = $reservation->user;
                                $address = collect([$user?->province, $user?->city, $user?->barangay, $user?->address])->filter()->implode(', ');
                            @endphp
                            <tr class="rental-row {{ $isOverdue ? 'bg-danger bg-opacity-10' : '' }}" data-source="{{ $reservation->booking_source === 'admin_staff' ? 'walkin' : 'online' }}" data-status="{{ $isOverdue ? 'overdue' : ($isDue ? 'due' : 'all') }}" data-search="{{ strtolower($reservation->vehicle.' '.$reservation->control_number.' '.($reservation->customer_name ?: ($user?->name ?? ''))) }}">
                                <td>
                                    <div class="fw-bold rental-vehicle-name" title="{{ $reservation->vehicle }}">{{ $reservation->vehicle }}</div>
                                    <span class="plate-number small">{{ $vehiclePlates[$reservation->vehicle_id] ?? 'Plate pending' }}</span>
                                    <span class="badge rental-source-badge {{ $reservation->booking_source === 'admin_staff' ? 'bg-warning text-dark' : 'bg-primary' }} rounded-pill">{{ $reservation->booking_source === 'admin_staff' ? 'Walk-in' : 'Online' }}</span>
                                </td>
                                <td><span class="control-tag">{{ $reservation->control_number }}</span></td>
                                <td>
                                    <div class="fw-bold rental-client-name" title="{{ $reservation->customer_name ?: ($user?->name ?? 'Walk-in Customer') }}">{{ $reservation->customer_name ?: ($user?->name ?? 'Walk-in Customer') }}</div>
                                    <small class="rental-client-id">ID: #CUST-{{ str_pad($user?->id ?? 0, 3, '0', STR_PAD_LEFT) }}</small>
                                </td>
                                <td>
                                    <div class="rental-period small"><span class="text-muted">Out</span> <strong>{{ $reservation->pickup_date->format('M d') }}</strong><br><span class="text-muted">In</span> <strong class="{{ $isOverdue ? 'text-danger' : '' }}">{{ $reservation->return_date->format('M d') }}</strong></div>
                                </td>
                                <td>
                                    <span class="badge {{ $isOverdue ? 'bg-danger' : ($isDue ? 'bg-warning text-dark' : 'bg-info text-dark') }} rounded-pill px-3">{{ $isOverdue ? 'OVERDUE' : ($isDue ? 'Due Today' : $reservation->return_date->diffForHumans()) }}</span>
                                </td>
                                <td><span class="status-pill {{ $isOverdue ? 'status-overdue' : ($isDue ? 'status-due' : 'status-otr') }}">{{ strtoupper($reservation->status) }}</span></td>
                                <td class="text-center">
                                    <div class="rental-actions">
                                    <button class="btn btn-sm rental-action-btn {{ $isOverdue ? 'btn-danger shadow-sm' : 'btn-dark' }} rounded-pill px-3 fw-bold"
                                        onclick="showContact(@js($reservation->customer_name ?: ($user?->name ?? 'Walk-in Customer')), @js($reservation->customer_phone ?: ($user?->contact_number ?? 'No contact')), @js($user?->email ?? ''), @js($address ?: ($reservation->delivery_address ?: 'No address provided')), @js($reservation->control_number))">
                                        <i class="fas fa-user-tag me-1"></i> Contact
                                    </button>
                                    @if($reservation->booking_source === 'admin_staff')
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 fw-bold ms-1"
                                            data-bs-toggle="modal" data-bs-target="#extendRentalModal"
                                            data-extension-url="{{ route($extendRentalRoute, $reservation) }}"
                                            data-control-number="{{ $reservation->control_number }}"
                                            data-return-date="{{ $reservation->return_date->format('Y-m-d') }}"
                                            title="Extend walk-in rental">
                                            <i class="fas fa-calendar-plus me-1"></i>Extend
                                        </button>
                                    @endif
                                    @if(!$isStaffMode)
                                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-2 fw-bold ms-1"
                                            onclick="deleteReservation({{ $reservation->id }})" title="Delete reservation">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No active rentals found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="extendRentalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <form method="POST" id="extendRentalForm">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header bg-danger text-white">
                        <div>
                            <h5 class="modal-title fw-bold">Extend Walk-in Rental</h5>
                            <small>Control No: <span id="extensionControlNumber" class="fw-bold"></span></small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <label for="extensionReturnDate" class="form-label fw-bold">New Return Date</label>
                        <input type="date" class="form-control rounded-pill" name="requested_return_date" id="extensionReturnDate" required>
                        <small class="text-muted d-block mt-2">The additional rental charge will be added to Billing Records.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-pill fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger rounded-pill fw-bold"><i class="fas fa-calendar-plus me-1"></i>Save Extension</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-4">
                    <h6 class="fw-bold mb-0 text-uppercase tracking-wider">Client Emergency Card</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <img id="mAvatar" src="" class="rounded-circle border border-4 border-white shadow-sm mb-3" width="100" style="margin-top: -50px; background: white;">
                    <h3 class="fw-bold mb-1" id="mName"></h3>
                    <p class="text-muted small mb-1 text-uppercase fw-bold">Verified Rentee</p>
                    <span class="control-tag mb-3 d-inline-block">Control No: <span id="mControlNumber"></span></span>

                    <div class="text-start">
                        <div class="p-3 bg-light rounded-3 mb-2">
                            <label class="text-muted small fw-bold text-uppercase d-block mb-1">Mobile Contact</label>
                            <h5 class="fw-bold text-danger mb-0" id="mPhone"></h5>
                        </div>
                        <div class="p-3 bg-light rounded-3 mb-2">
                            <label class="text-muted small fw-bold text-uppercase d-block mb-1">Email Address</label>
                            <span class="fw-bold text-dark" id="mEmail"></span>
                        </div>
                        <div class="p-3 bg-light rounded-3">
                            <label class="text-muted small fw-bold text-uppercase d-block mb-1">Home Address</label>
                            <span class="fw-bold text-dark d-block small" id="mAddress"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <a href="#" id="callBtn" class="btn btn-success w-100 rounded-pill fw-bold py-2 shadow">
                        <i class="fas fa-phone-volume me-2"></i>START CALL NOW
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('extendRentalModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const form = document.getElementById('extendRentalForm');
            const returnDate = document.getElementById('extensionReturnDate');
            document.getElementById('extensionControlNumber').textContent = button.dataset.controlNumber;
            form.action = button.dataset.extensionUrl;
            returnDate.min = button.dataset.returnDate;
            returnDate.value = '';
        });
        let currentActiveFilter = 'all';
        let currentRentalSource = 'all';

        function showContact(name, phone, email, address, controlNumber) {
            document.getElementById('mName').innerText = name;
            document.getElementById('mPhone').innerText = phone;
            document.getElementById('mEmail').innerText = email;
            document.getElementById('mAddress').innerText = address;
            document.getElementById('mControlNumber').innerText = controlNumber;
            document.getElementById('mAvatar').src = `https://ui-avatars.com/api/?name=${name}&background=dc3545&color=fff&size=200`;
            document.getElementById('callBtn').href = "tel:" + phone;

            var myModal = new bootstrap.Modal(document.getElementById('contactModal'));
            myModal.show();
        }

        async function deleteReservation(id) {
            if (!window.confirm('Delete this active rental and its uploaded files?')) return;
            const response = await fetch('{{ $activeReservationsBaseUrl }}/' + id, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            if (!response.ok) {
                window.alert('Rental could not be deleted.');
                return;
            }
            window.location.reload();
        }

        // Click event para sa Stat Cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', () => {
                const filter = card.getAttribute('data-filter');
                currentActiveFilter = (currentActiveFilter === filter) ? 'all' : filter;
                filterActiveRentals();
            });
        });

        function clearActiveFilter() {
            currentActiveFilter = 'all';
            filterActiveRentals();
        }

        // --- FILTER FUNCTION: Search + Stat Cards Combined ---
        function filterActiveRentals() {
            const query = document.getElementById('activeSearchInput').value.trim().toLowerCase();
            const rows = document.querySelectorAll('#activeRentalsTable tbody .rental-row');
            const noResultMsg = document.getElementById('noResultMsg');
            const filterBanner = document.getElementById('filterBanner');
            const filterLabel = document.getElementById('filterLabel');
            let visibleCount = 0;

            const filterNames = {
                'all': '',
                'due': 'Due Today Rentals',
                'overdue': 'Overdue Alert Units'
            };

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');
                const source = row.getAttribute('data-source');

                const matchesSearch = searchData.includes(query);
                const matchesSource = currentRentalSource === 'all' || source === currentRentalSource;
                let matchesStat = true;

                if (currentActiveFilter === 'due') matchesStat = (status === 'due');
                else if (currentActiveFilter === 'overdue') matchesStat = (status === 'overdue');

                const isMatch = matchesSearch && matchesSource && matchesStat;
                row.style.display = isMatch ? '' : 'none';
                if (isMatch) visibleCount++;
            });

            // Highlight active stat card border/shadow
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.toggle('active-filter', card.getAttribute('data-filter') === currentActiveFilter && currentActiveFilter !== 'all');
            });

            // Show or hide filter banner indicator
            if (currentActiveFilter !== 'all') {
                filterBanner.classList.remove('d-none');
                filterLabel.innerText = filterNames[currentActiveFilter];
            } else {
                filterBanner.classList.add('d-none');
            }

            noResultMsg.classList.toggle('d-none', visibleCount !== 0);
        }

        document.querySelectorAll('.rental-source-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                currentRentalSource = tab.dataset.sourceFilter || 'all';
                document.querySelectorAll('.rental-source-tab').forEach(item => item.classList.toggle('active', item === tab));
                filterActiveRentals();
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>