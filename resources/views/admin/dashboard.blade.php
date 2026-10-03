<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Admin Dashboard</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --boss-red: #dc3545; 
            --boss-dark: #212529; 
            --boss-grey: #f8f9fa; 
        }

        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }
        
        /* SIDEBAR - Style based on BossDrive design */
        .sidebar { 
            width: 250px; 
            height: 100vh; 
            background-color: #212529; 
            position: fixed; 
            border-right: 5px solid #dc3545; 
            z-index: 1000; 
        }
        .sidebar .nav-link { 
            color: white; 
            padding: 15px 20px; 
            margin: 5px 15px; 
            border-radius: 8px;
            font-size: 0.9rem;
            transition: 0.3s;
            text-decoration: none;
            display: block;
            font-weight: 600;
        }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link.active { background-color: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); color: white; }

        .main-content { margin-left: 250px; }
        
        /* TOP NAV */
        .top-nav { 
            background: white; 
            padding: 15px 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-container { padding: 30px; }
        
        /* STAT BOXES - Static Front-end Version */
        .stat-box {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center;
            border-bottom: 5px solid #ddd;
            transition: 0.3s;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }
        .stat-box:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
        .border-users { border-color: #0d6efd; }
        .border-active { border-color: #198754; }
        .border-rentals { border-color: #dc3545; }

        .dashboard-card {
            background: white; border-radius: 15px; border: none; padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Modal list styling */
        .list-row {
            padding: 12px 15px;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .rent-count-badge {
            font-weight: 800;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

    @include('admin.partials.navigation', ['adminPageTitle' => 'Dashboard', 'adminPageAccent' => 'Overview'])
    <div class="main-content">

        <div class="content-container">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <!-- Total System Users -> links straight to User Accounts page -->
                    <a href="{{ route('admin.users') }}" class="stat-box border-users">
                        <small class="text-muted fw-bold d-block text-uppercase small">Total System Users</small>
                        <h2 class="fw-bold mb-0 mt-2 text-primary" id="totalUsersCount">{{ $totalUsers }}</h2>
                        <small class="text-muted" style="font-size: 10px;"><i class="fas fa-arrow-right me-1"></i>View all accounts</small>
                    </a>
                </div>
                <div class="col-md-4">
                    <!-- Active Users -> opens modal listing who is currently active -->
                    <div class="stat-box border-active" data-bs-toggle="modal" data-bs-target="#activeUsersModal">
                        <small class="text-muted fw-bold d-block text-uppercase small">Active Users</small>
                        <h2 class="fw-bold mb-0 mt-2 text-success" id="activeUsersCount">{{ $activeUsers }}</h2>
                        <small class="text-muted" style="font-size: 10px;"><i class="fas fa-eye me-1"></i>View who's active</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- Total Units Rented -> opens modal listing vehicles + rent count -->
                    <div class="stat-box border-rentals" data-bs-toggle="modal" data-bs-target="#unitsRentedModal">
                        <small class="text-muted fw-bold d-block text-uppercase small">Total Units Rented</small>
                        <h2 class="fw-bold mb-0 mt-2 text-danger" id="totalRentalsCount">{{ $totalRentals }}</h2>
                        <small class="text-muted" style="font-size: 10px;"><i class="fas fa-eye me-1"></i>View rental breakdown</small>
                    </div>
                </div>
            </div>

            <div class="dashboard-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-uppercase small" id="rentalOverviewTitle"><i class="fas fa-chart-bar text-danger me-2"></i>{{ $monthlyRentals['title'] }}</h6>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Rental overview period">
                            @foreach (['year' => 'Year', 'month' => 'Month', 'week' => 'Week', 'day' => 'Day'] as $period => $label)
                                <button type="button" class="btn {{ $period === 'year' ? 'btn-danger' : 'btn-outline-danger' }} rental-period-button" data-period="{{ $period }}">{{ $label }}</button>
                            @endforeach
                        </div>
                        <input type="date" id="rentalOverviewDate" class="form-control form-control-sm" style="width:auto;" value="{{ now()->toDateString() }}" aria-label="Choose rental overview date">
                    </div>
                </div>
                <div style="height: 280px;">
                    <canvas id="rentalBarChart"></canvas>
                </div>
            </div>
            
        </div>
    </div>

    <!-- ================= ACTIVE USERS MODAL ================= -->
    <div class="modal fade" id="activeUsersModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-success text-white p-4">
                    <h6 class="fw-bold mb-0 text-uppercase"><i class="fas fa-circle text-white me-2" style="font-size:8px;"></i>Currently Active Users</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="activeUsersList">
                        <!-- Populated by JS -->
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <a href="{{ route('admin.users') }}" class="btn btn-outline-dark rounded-pill fw-bold w-100">
                        <i class="fas fa-users me-2"></i>Go to User Accounts
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= UNITS RENTED MODAL ================= -->
    <div class="modal fade" id="unitsRentedModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-danger text-white p-4">
                    <h6 class="fw-bold mb-0 text-uppercase"><i class="fas fa-car-side me-2"></i>Vehicles Rented &mdash; Rent Count</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr class="small text-muted text-uppercase">
                                    <th>Vehicle</th>
                                    <th>Plate</th>
                                    <th class="text-center">Times Rented</th>
                                </tr>
                            </thead>
                            <tbody id="unitsRentedTableBody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <a href="{{ route('admin.vehicles') }}" class="btn btn-outline-dark rounded-pill fw-bold w-100">
                        <i class="fas fa-car me-2"></i>Go to Vehicle Management
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Rental Overview
        const ctx = document.getElementById('rentalBarChart').getContext('2d');
        const rentalChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($monthlyRentals['labels']),
                datasets: [{
                    label: 'Units Rented',
                    data: @json($monthlyRentals['values']),
                    backgroundColor: '#dc3545', // BossDrive Red
                    borderRadius: 5,
                    barThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false } 
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [5, 5], color: '#eee' } 
                    },
                    x: { 
                        grid: { display: false } 
                    }
                }
            }
        });

        // ---- Sample data (replace with real data from Laravel backend later) ----
        let activeUsersData = @json($activeUserList->map->only(['name', 'email', 'role'])->values()->all());
        let unitsRentedData = @json($rentedVehicles->map->only(['name', 'plate', 'reservations_count'])->values()->all());

        // ---- Render Active Users list into modal ----
        function renderActiveUsers() {
            const container = document.getElementById('activeUsersList');
            if (activeUsersData.length === 0) {
                container.innerHTML = `<p class="text-muted text-center mb-0">No active users right now.</p>`;
                return;
            }
            container.replaceChildren();
            activeUsersData.forEach(function (user) {
                const row = document.createElement('div');
                row.className = 'list-row';
                const details = document.createElement('div');
                const name = document.createElement('div');
                name.className = 'fw-bold';
                name.textContent = user.name;
                const email = document.createElement('small');
                email.className = 'text-muted';
                email.textContent = user.email;
                details.append(name, email);
                const role = document.createElement('span');
                role.className = 'badge bg-success rounded-pill px-3';
                role.textContent = (user.role || 'user').replace(/^./, character => character.toUpperCase());
                row.append(details, role);
                container.appendChild(row);
            });
        }

        // ---- Render Units Rented table into modal ----
        function renderUnitsRented() {
            const tbody = document.getElementById('unitsRentedTableBody');
            tbody.replaceChildren();
            unitsRentedData.forEach(function (vehicle) {
                const row = tbody.insertRow();
                const name = row.insertCell();
                name.className = 'fw-bold';
                name.textContent = vehicle.name;
                const plateCell = row.insertCell();
                const plate = document.createElement('span');
                plate.className = 'plate-number small';
                plate.style.cssText = "font-family:'Courier New',monospace;background:#eee;padding:2px 8px;border-radius:4px;border:1px solid #ccc;";
                plate.textContent = vehicle.plate || 'Not assigned';
                plateCell.appendChild(plate);
                const countCell = row.insertCell();
                countCell.className = 'text-center';
                const count = document.createElement('span');
                count.className = 'rent-count-badge badge bg-danger rounded-pill px-3';
                count.textContent = vehicle.reservations_count + 'x';
                countCell.appendChild(count);
            });
        }

        renderActiveUsers();
        renderUnitsRented();

        let selectedRentalPeriod = 'year';
        let dashboardRequestPending = false;
        async function refreshDashboardOverview() {
            if (document.hidden || dashboardRequestPending) return;
            dashboardRequestPending = true;
            const date = document.getElementById('rentalOverviewDate').value;
            const url = new URL('{{ route('admin.dashboard.live') }}', window.location.origin);
            url.searchParams.set('period', selectedRentalPeriod);
            url.searchParams.set('date', date);

            try {
                const response = await fetch(url, {
                    headers: {'Accept': 'application/json'},
                    cache: 'no-store'
                });
                if (!response.ok) throw new Error('Dashboard overview refresh failed: ' + response.status);
                const data = await response.json();
                activeUsersData = data.activeUsers;
                unitsRentedData = data.unitsRented;
                document.getElementById('totalUsersCount').textContent = data.totalUsers;
                document.getElementById('activeUsersCount').textContent = data.activeUserCount;
                document.getElementById('totalRentalsCount').textContent = data.totalRentals;
                document.getElementById('rentalOverviewTitle').innerHTML =
                    '<i class="fas fa-chart-bar text-danger me-2"></i>' + data.chart.title;
                rentalChart.data.labels = data.chart.labels;
                rentalChart.data.datasets[0].data = data.chart.values;
                rentalChart.update();
                renderActiveUsers();
                renderUnitsRented();
            } catch (error) {
                console.error('Unable to refresh the Admin Dashboard Overview.', error);
            } finally {
                dashboardRequestPending = false;
            }
        }

        document.querySelectorAll('.rental-period-button').forEach(function (button) {
            button.addEventListener('click', function () {
                selectedRentalPeriod = button.dataset.period;
                document.querySelectorAll('.rental-period-button').forEach(function (periodButton) {
                    const active = periodButton === button;
                    periodButton.classList.toggle('btn-danger', active);
                    periodButton.classList.toggle('btn-outline-danger', !active);
                });
                refreshDashboardOverview();
            });
        });
        document.getElementById('rentalOverviewDate').addEventListener('change', refreshDashboardOverview);
        refreshDashboardOverview();
        window.setInterval(refreshDashboardOverview, 5000);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>