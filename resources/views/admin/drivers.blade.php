<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BossDrive - {{ $isStaffMode ? 'Staff' : 'Admin' }} Drivers</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #b71c1c; --boss-dark: #121212; --boss-grey: #f1f3f5; }
        body { background: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 250px; min-height: 100vh; }
        .top-nav { background: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; }
        .content-container { padding: 30px; }
        .driver-card { border: 0; border-radius: 18px; background: #fff; box-shadow: 0 4px 18px rgba(0,0,0,.06); }
        .status-pill { border-radius: 50px; font-size: .72rem; font-weight: 800; padding: 5px 12px; }
        .history-table th { font-size: .7rem; text-transform: uppercase; color: #6c757d; white-space: nowrap; }
        .history-table td { font-size: .85rem; vertical-align: middle; }
        @media (max-width: 992px) { .main-content { margin-left: 0; } .content-container { padding: 20px; } }
    </style>
</head>
<body>
    @if($isStaffMode)
        @include('staff.partials.navigation', ['staffPageTitle' => 'Driver', 'staffPageAccent' => 'Management'])
    @else
        @include('admin.partials.navigation', ['adminPageTitle' => 'Driver', 'adminPageAccent' => 'Management'])
    @endif
    <div class="main-content">
        <main class="content-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Drivers</h4>
                    <p class="text-muted small mb-0">Drivers assigned to confirmed bookings with driver service.</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#driverAvailabilityModal">
                        <i class="fas fa-phone me-1"></i> Driver Availability
                    </button>
                    <a href="{{ $isStaffMode ? route('staff.vehicles') : route('admin.vehicles') }}" class="btn btn-outline-dark rounded-pill fw-bold">
                        <i class="fas fa-car me-1"></i> Vehicle Management
                    </a>
                </div>
            </div>

            @php
                $assignedGroups = $driverReservations->groupBy(fn ($reservation) => $reservation->assigned_driver_name);
                $driverGroups = collect($driverCatalog)->mapWithKeys(function ($driver) use ($assignedGroups) {
                    return [$driver['name'] => $assignedGroups->get($driver['name'], collect())];
                });
                $assignedGroups->each(function ($assignments, $driverName) use (&$driverGroups) {
                    if (!$driverGroups->has($driverName)) {
                        $driverGroups->put($driverName, $assignments);
                    }
                });
                $reservedStatuses = ['verified', 'processing'];
            @endphp
            <div class="modal fade" id="driverAvailabilityModal" tabindex="-1" aria-labelledby="driverAvailabilityModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 rounded-4 shadow">
                        <div class="modal-header bg-danger text-white">
                            <div>
                                <h5 class="modal-title fw-bold" id="driverAvailabilityModalLabel">
                                    <i class="fas fa-phone me-2"></i>Driver Availability
                                </h5>
                                <small class="text-white-50">I-edit kung Available o Not Available ang driver para sa linggong ito.</small>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Driver</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($driverCatalog as $driver)
                                            <tr>
                                                <td class="fw-bold">{{ $driver['name'] }}</td>
                                                <td class="text-muted small">{{ $driver['contact'] }}</td>
                                                <td>
                                                    <span class="small fw-bold {{ $driver['availableThisWeek'] ? 'text-success' : 'text-secondary' }}">
                                                        {{ $driver['availableThisWeek'] ? 'Available' : 'Not Available' }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST" class="d-inline-flex align-items-center gap-2" action="{{ route($isStaffMode ? 'staff.drivers.availability' : 'admin.drivers.availability', ['driver' => $driver['id']]) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="available_this_week" class="form-select form-select-sm" required>
                                                            <option value="1" {{ $driver['availableThisWeek'] ? 'selected' : '' }}>Available</option>
                                                            <option value="0" {{ !$driver['availableThisWeek'] ? 'selected' : '' }}>Not Available</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-danger rounded-pill fw-bold text-nowrap">Save</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                @forelse($driverGroups as $driverName => $assignments)
                    @php
                        $active = $assignments->first(fn ($reservation) => $reservation->status === 'released');
                        $reserved = $assignments->first(fn ($reservation) => in_array($reservation->status, $reservedStatuses, true));
                        $latest = $active ?: $reserved ?: $assignments->first();
                        $catalogDriver = collect($driverCatalog)->firstWhere('name', $driverName);
                        $renter = $latest?->customer_name ?: ($latest?->user?->name ?? 'Walk-in Customer');
                        $completedAssignments = $assignments
                            ->where('status', 'completed')
                            ->sortByDesc('return_date');
                        $historyModalId = 'driverHistory'.md5((string) $driverName);
                        $isAvailableThisWeek = $catalogDriver['availableThisWeek'] ?? true;
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="driver-card p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1"><i class="fas fa-id-badge text-danger me-2"></i>{{ $driverName }}</h5>
                                    <small class="text-muted">{{ $latest?->assigned_driver_contact ?: ($catalogDriver['contact'] ?? 'Contact not provided') }}</small>
                                </div>
                                <div class="text-end">
                                    @if($active)
                                        <span class="status-pill bg-danger-subtle text-danger d-block mb-1">ONGOING</span>
                                    @elseif($reserved)
                                        <span class="status-pill bg-warning-subtle text-warning-emphasis d-block mb-1">RESERVED</span>
                                    @elseif($isAvailableThisWeek)
                                        <span class="status-pill bg-success-subtle text-success d-block mb-1">AVAILABLE</span>
                                    @else
                                        <span class="status-pill bg-secondary-subtle text-secondary d-block mb-1">NOT AVAILABLE</span>
                                    @endif
                                </div>
                            </div>
                            @if($active)
                                <div class="border rounded-3 p-3 bg-light small">
                                    <div class="mb-2"><span class="text-muted">Currently rented by:</span><br><strong>{{ $renter }}</strong></div>
                                    <div class="mb-2"><span class="text-muted">Vehicle:</span><br><strong>{{ $active->vehicle }}</strong></div>
                                    <div class="d-flex justify-content-between">
                                        <span><span class="text-muted d-block">Rented/Pickup</span><strong>{{ $active->pickup_date?->format('M d, Y') }}</strong></span>
                                        <span class="text-end"><span class="text-muted d-block">Return</span><strong>{{ $active->return_date?->format('M d, Y') }}</strong></span>
                                    </div>
                                </div>
                            @elseif($reserved)
                                <div class="border rounded-3 p-3 bg-light small">
                                    <div class="mb-2"><span class="text-muted">Reserved for:</span><br><strong>{{ $renter }}</strong></div>
                                    <div class="mb-2"><span class="text-muted">Vehicle:</span><br><strong>{{ $reserved->vehicle }}</strong></div>
                                    <div class="d-flex justify-content-between">
                                        <span><span class="text-muted d-block">Pickup</span><strong>{{ $reserved->pickup_date?->format('M d, Y') }}</strong></span>
                                        <span class="text-end"><span class="text-muted d-block">Return</span><strong>{{ $reserved->return_date?->format('M d, Y') }}</strong></span>
                                    </div>
                                </div>
                            @elseif($isAvailableThisWeek)
                                <div class="alert alert-success py-2 small mb-0"><i class="fas fa-check-circle me-1"></i>Available — no reserved or ongoing rental.</div>
                            @else
                                <div class="alert alert-secondary py-2 small mb-0"><i class="fas fa-phone-slash me-1"></i>Not available this week.</div>
                            @endif
                            <div class="mt-3 d-flex justify-content-between align-items-center gap-2">
                                <span class="small text-muted">{{ $assignments->count() }} assigned booking(s) in history</span>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill fw-bold text-nowrap"
                                            data-bs-toggle="modal" data-bs-target="#{{ $historyModalId }}">
                                        <i class="fas fa-clock-rotate-left me-1"></i>History
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="{{ $historyModalId }}" tabindex="-1" aria-labelledby="{{ $historyModalId }}Label" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0 rounded-4 shadow">
                                <div class="modal-header bg-dark text-white">
                                    <div>
                                        <h5 class="modal-title fw-bold" id="{{ $historyModalId }}Label">
                                            <i class="fas fa-id-badge me-2"></i>{{ $driverName }} - Booking History
                                        </h5>
                                        <small class="text-white-50">Mga booking na natapos na ng driver</small>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @if($completedAssignments->isEmpty())
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-calendar-xmark fa-2x mb-2"></i>
                                            <p class="mb-0">Wala pang natapos na booking para sa driver na ito.</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover history-table mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Customer</th>
                                                        <th>Vehicle</th>
                                                        <th>Booking / Start</th>
                                                        <th>End / Return</th>
                                                        <th class="text-end">Days</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($completedAssignments as $completedBooking)
                                                        @php
                                                            $bookingDays = $completedBooking->pickup_date && $completedBooking->return_date
                                                                ? $completedBooking->pickup_date->diffInDays($completedBooking->return_date) + 1
                                                                : null;
                                                            $completedCustomer = $completedBooking->customer_name ?: ($completedBooking->user?->name ?? 'Walk-in Customer');
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $completedCustomer }}</td>
                                                            <td>{{ $completedBooking->vehicle }}</td>
                                                            <td>{{ $completedBooking->pickup_date?->format('M d, Y') ?? 'N/A' }}</td>
                                                            <td>{{ $completedBooking->return_date?->format('M d, Y') ?? 'N/A' }}</td>
                                                            <td class="text-end fw-bold">{{ $bookingDays ? $bookingDays.' day'.($bookingDays === 1 ? '' : 's') : 'N/A' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="driver-card text-center py-5">
                            <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                            <h5 class="fw-bold">No assigned drivers yet</h5>
                            <p class="text-muted small mb-0">Assign a driver to a confirmed “With Driver” reservation to show it here.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
