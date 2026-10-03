<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Reservations</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #b71c1c; --boss-dark: #121212; --boss-grey: #e0e0e0; }
        body { background: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }
        .main-content { margin-left: 250px; min-height: 100vh; }
        .top-nav { background: #fff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc; position: sticky; top: 0; z-index: 999; }
        .text-black-bold { color: #000; font-weight: 800; }
        .content-container { padding: 30px; }
        .option-card { border: 2px solid #eee; border-radius: 12px; padding: 15px; cursor: pointer; text-align: center; transition: .3s; }
        .option-card.active { border-color: var(--boss-red); background: #fff5f5; }
        .status-badge { font-size: .75rem; padding: 5px 12px; border-radius: 50px; font-weight: bold; position: absolute; top: 15px; right: 15px; z-index: 10; text-transform: uppercase; }
        .car-info-tag { font-size: .75rem; background: #f8f9fa; padding: 4px 10px; border-radius: 50px; color: #666; font-weight: 600; }
        .specifications-button { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .65rem; border:1px solid #ced4da; border-radius:999px; background:#fff; color:#495057; font-size:.72rem; font-weight:700; line-height:1.2; transition:background-color .15s ease, border-color .15s ease, color .15s ease; }
        .specifications-button:hover, .specifications-button:focus-visible { border-color:#b71c1c; background:#fff5f5; color:#b71c1c; }
        .specifications-button:focus-visible { outline:3px solid rgba(183,28,28,.2); outline-offset:2px; }
        .rating-stars { color: #ffc107; font-size: .85rem; }
        .view-feedback { font-size: .7rem; color: var(--boss-red); text-decoration: none; font-weight: bold; cursor: pointer; }
        .btn-float { position: fixed; bottom: 30px; right: 30px; z-index: 1050; width: 60px; height: 60px; border-radius: 50%; }
        .calendar-table th { background: var(--boss-dark); color: #fff; text-align: center; }
        .calendar-table td { height: 100px; vertical-align: top; border: 1px solid #dee2e6; width: 14.28%; }
        .calendar-table td.today-cell { background: #fff3cd; box-shadow: inset 0 0 0 2px #ffc107; }
        .cal-date { font-weight: bold; margin-bottom: 5px; display: block; }
        .cal-event { font-size: .65rem; padding: 2px 5px; border-radius: 4px; margin-bottom: 2px; color: #fff; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .event-maintenance { background: #ffc107; color: #000; }
        .event-rented { background: #dc3545; }
        .event-reserved { background: #0d6efd; }
        .cal-more { display: block; width: 100%; border: 0; background: transparent; color: #495057; font-size: .68rem; font-weight: 700; text-align: left; padding: 2px 5px; }
        .cal-more:hover { color: var(--boss-red); text-decoration: underline; }
        .calendar-details-modal .modal-dialog { width: min(500px, calc(100vw - 1rem)); max-width: none; }
        .calendar-details-modal .modal-body { max-height: min(15rem, calc(100vh - 8rem)); overflow-y: auto; overscroll-behavior: contain; }
        .catalog-card-body { display: flex; flex-direction: column; height: 100%; }
        .catalog-card-footer { margin-top: auto; }
        .vehicle-card-image { height:140px; max-width:240px; width:100%; object-fit:contain; }
        .guest-vehicle-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap:1.5rem; }
        .guest-vehicle-grid-item { min-width:0; }
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0; }
            .content-container { padding:20px 16px; }
            .top-nav { padding:12px 16px; }
        }
        @media (max-width: 575.98px) {
            .top-nav { align-items:center; gap:8px; padding-left:58px !important; }
            .top-nav h4 { font-size:1rem; }
            .auth-buttons { flex-shrink:0; gap:5px !important; }
            .auth-buttons .btn { padding:.35rem .55rem !important; font-size:.72rem; }
            .content-container { padding:14px 10px 90px; }
            .content-container > .card { padding:14px !important; margin-bottom:1.25rem !important; }
            .content-container .row.g-4 { --bs-gutter-x: .75rem; --bs-gutter-y: .75rem; }
            .content-container .alert { padding:.65rem .75rem; }
            .content-container .form-control,
            .content-container .form-select { min-height:38px; font-size:.9rem; }
            .content-container .btn { font-size:.78rem; }
            .catalog-card-body { padding-left:1rem !important; padding-right:1rem !important; }
            .catalog-card-body h5 { font-size:1rem; }
            .catalog-card-footer .btn { padding:.4rem .8rem !important; }
            .car-info-tag { font-size:.68rem; padding:4px 8px; }
            .specifications-button { font-size:.68rem; }
            .status-badge { top:10px; right:10px; font-size:.65rem; padding:4px 8px; }
            .vehicle-card-image { height:120px; max-width:200px; }
            .btn-float { bottom:16px; right:16px; width:48px; height:48px; }
            .btn-float[style*="right: 105px"] { right:74px !important; }
            .calendar-table { min-width:620px; }
            .calendar-table td { height:75px; }
            .modal-dialog { margin:.5rem; }
            .modal-body { padding:1rem !important; }
        }
    </style>
</head>
<body>
    @include('partials.sidebar')

    <button class="btn btn-danger btn-float" data-bs-toggle="modal" data-bs-target="#calendarModal">
        <i class="fas fa-calendar-alt fa-lg"></i>
    </button>
    <button class="btn btn-dark btn-float" style="right: 105px;" data-bs-toggle="modal" data-bs-target="#guestPriceGuideModal" title="Price Guide">
        <i class="fas fa-tags fa-lg"></i>
    </button>

    <div class="main-content">
        <header class="top-nav shadow-sm">
            <h4 class="mb-0 text-black-bold">RESERVATION</h4>
            <div class="auth-buttons d-flex gap-2">
                <a href="{{ url('/user/login') }}" class="btn btn-sm btn-outline-dark px-3">Login</a>
                <a href="{{ url('/user/register') }}" class="btn btn-sm btn-danger px-3">Register</a>
            </div>
        </header>

        <main class="content-container">
            <div class="alert alert-warning border-0 shadow-sm rounded-4">
                <i class="fas fa-lock me-2"></i>
                <small class="fw-bold">You're browsing as a guest. Please <a href="{{ url('/user/login') }}" class="text-dark">Login</a> or <a href="{{ url('/user/register') }}" class="text-dark">Register</a> to complete a booking.</small>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 mb-5 bg-white">
                <form class="row g-3 align-items-end" method="GET" action="{{ route('reservations') }}">
                    <div class="col-md-5">
                        <label class="small fw-bold mb-1" for="search">Search Car</label>
                        <input id="search" name="search" value="{{ request('search') }}" type="text" class="form-control rounded-pill border-light bg-light" placeholder="Brand or Model">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold mb-1" for="capacity">Capacity Type</label>
                        <select id="capacity" name="capacity" class="form-select rounded-pill border-light bg-light">
                            <option value="">All Seaters</option>
                            <option value="4" @selected(request('capacity') === '4')>4 Seater</option>
                            <option value="5">5 Seater</option>
                            <option value="6" @selected(request('capacity') === '6')>6 Seater</option>
                            <option value="7" @selected(request('capacity') === '7')>7 Seater</option>
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-danger w-100 rounded-pill fw-bold">SEARCH CAR</button></div>
                </form>
            </div>

            <div class="guest-vehicle-grid">
                @forelse ($vehicles as $vehicle)
                    @php
                        $status = $vehicle->status === 'rented' ? 'Ongoing Rental' : ucfirst($vehicle->status);
                        $statusColor = $status === 'Available' ? 'success' : ($status === 'Maintenance' ? 'warning' : 'danger');
                        $schedule = collect($vehicle->schedule ?? []);
                        $reserved = $schedule->first(fn ($entry) => ($entry['type'] ?? null) === 'Reserved');
                        if ($status === 'Available' && $reserved) {
                            $status = 'Reserved';
                            $statusColor = 'primary';
                        }
                        $feedbacks = $vehicle->feedbacks ?? [];
                        $damageLogs = $vehicle->damage_log ?? [];
                        $modelUnits = collect($unitsByModel[$vehicle->name] ?? []);
                        $normalizedCategory = strtolower($vehicle->category ?? '');
                        $displayCategory = str_contains($normalizedCategory, 'diesel')
                            ? 'Diesel'
                            : (str_contains($normalizedCategory, 'pick-up') || str_contains($normalizedCategory, 'pickup')
                                ? 'Pick-up / Expanded'
                                : $vehicle->category);
                        $activeReservation = $activeReservations[$vehicle->id] ?? null;
                        $rentalSchedule = $schedule->filter(fn ($entry) => ($entry['type'] ?? null) === 'Rented')
                            ->filter(fn ($entry) => filled($entry['start']) && filled($entry['end']))
                            ->sortBy('start')
                            ->first();
                        $maintenanceSchedule = collect($vehicle->schedule ?? [])
                            ->filter(fn ($entry) => ($entry['type'] ?? null) === 'Maintenance')
                            ->filter(fn ($entry) => filled($entry['start']) && filled($entry['end']))
                            ->sortBy('start')
                            ->first();
                        if ($vehicle->status === 'rented' && $activeReservation?->status === 'released') {
                            $status = 'Ongoing Rental';
                            $statusColor = 'danger';
                        } elseif ($vehicle->status === 'rented') {
                            $status = 'Reserved';
                            $statusColor = 'primary';
                        }
                    @endphp
                    <div class="guest-vehicle-grid-item">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative">
                            <span id="guest-status-{{ $vehicle->id }}" class="status-badge bg-{{ $statusColor }} {{ $statusColor === 'warning' ? 'text-dark' : 'text-white' }}">{{ $status }}</span>
                            <div class="p-4 text-center bg-white"><img src="{{ $vehicle->image_path ? asset('storage/'.$vehicle->image_path) : asset('image/car'.(($vehicle->id - 1) % 4 + 1).'.png') }}" alt="{{ $vehicle->name }}" class="img-fluid vehicle-card-image" style="height: 140px; max-width: 240px; object-fit: contain;"></div>
                            <div class="p-4 pt-0 bg-white catalog-card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div><h5 id="guest-title-{{ $vehicle->id }}" class="fw-bold mb-0">{{ $vehicle->name }} Unit #{{ $modelUnits->search(fn ($unit) => $unit['id'] === $vehicle->id) + 1 }}</h5>@if($vehicle->brand_name)<small class="text-muted d-block">{{ $vehicle->brand_name }}</small>@endif<small class="text-muted"><i class="fas fa-history me-1"></i>{{ $vehicle->reservations_count }} Times Rented</small></div>
                                    <div class="text-end"><div class="rating-stars">{!! str_repeat('<i class="fas fa-star"></i>', (int) round($vehicle->rating)) !!}</div><a class="view-feedback" href="#feedback-{{ $vehicle->id }}" data-bs-toggle="modal">{{ count($feedbacks) }} Feedback{{ count($feedbacks) === 1 ? '' : 's' }}</a></div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <button type="button" class="car-info-tag clickable border-0" data-card-id="{{ $vehicle->id }}" data-model="{{ $vehicle->name }}" data-units="{{ json_encode($modelUnits) }}" onclick="showGuestUnits(this)"><i class="fas fa-layer-group me-1"></i>Stock: {{ $modelUnits->count() }} Unit{{ $modelUnits->count() === 1 ? '' : 's' }}</button>
                                    <span class="car-info-tag" id="guest-category-{{ $vehicle->id }}">{{ $displayCategory }}</span>
                                    <button type="button" class="specifications-button" id="guest-specs-button-{{ $vehicle->id }}" data-card-id="{{ $vehicle->id }}" data-unit-id="{{ $vehicle->id }}" onclick="showGuestSpecifications(this)"><i class="fas fa-list-ul" aria-hidden="true"></i>View Specifications</button>
                                    <span class="car-info-tag">{{ count($damageLogs) }} Condition Log{{ count($damageLogs) === 1 ? '' : 's' }}</span>
                                </div>
                                <div class="small text-muted mb-1" id="guest-unit-{{ $vehicle->id }}"><strong>Plate:</strong> {{ $vehicle->plate }}</div>
                                <div class="small fw-bold mb-3" id="guest-schedule-{{ $vehicle->id }}"></div>
                                <div class="catalog-card-footer">
                                @if ($activeReservation)
                                    <div class="alert alert-info py-2 px-3 small fw-bold mb-3">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        Reserved dates:
                                        {{ $activeReservation->pickup_date->format('F j, Y') }} -
                                        {{ $activeReservation->return_date->format('F j, Y') }}
                                    </div>
                                @endif
                                @if (!$activeReservation && $rentalSchedule)
                                    <div class="alert alert-danger py-2 px-3 small fw-bold mb-3">
                                        <i class="fas fa-car-side me-1"></i>
                                        Ongoing rental dates:
                                        {{ \Illuminate\Support\Carbon::parse($rentalSchedule['start'])->format('F j, Y') }} -
                                        {{ \Illuminate\Support\Carbon::parse($rentalSchedule['end'])->format('F j, Y') }}
                                    </div>
                                @endif
                                @if ($maintenanceSchedule)
                                    <div class="alert alert-warning py-2 px-3 small fw-bold mb-3">
                                        <i class="fas fa-tools me-1"></i>
                                        Maintenance dates:
                                        {{ \Illuminate\Support\Carbon::parse($maintenanceSchedule['start'])->format('F j, Y') }} -
                                        {{ \Illuminate\Support\Carbon::parse($maintenanceSchedule['end'])->format('F j, Y') }}
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><span class="h5 fw-bold text-danger">₱{{ number_format($vehicle->price, 2) }}</span><small class="text-muted">/day</small></div>
                                    <a href="{{ $status === 'Available' ? route('login', ['vehicle_id' => $vehicle->id]) : '#' }}" class="btn {{ $status === 'Available' ? 'btn-dark' : 'btn-secondary disabled' }} rounded-pill px-4 rent-now-link" data-loading-label="Opening rental form..." @if($status !== 'Available') aria-disabled="true" @endif>{{ $status === 'Available' ? 'Rent Now' : 'Unavailable' }}</a>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-5">No vehicles found.</div>
                @endforelse
            </div>
        </main>
    </div>

    <div class="modal fade" id="guestUnitsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="guestUnitsTitle">Vehicle Units</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="list-group list-group-flush" id="guestUnitsList"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="guestSpecificationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="guestSpecificationsTitle">Vehicle Specifications</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="list-group list-group-flush" id="guestSpecificationsList"></div>
            </div>
        </div>
    </div>

    @foreach ($vehicles as $vehicle)
        @php
            $feedbacks = $vehicle->feedbacks ?? [];
        @endphp
        <div class="modal fade" id="feedback-{{ $vehicle->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title fw-bold"><i class="fas fa-comments me-2"></i>Feedbacks — {{ $vehicle->name }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        @forelse ($feedbacks as $feedback)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $feedback['name'] ?? 'Customer' }}</strong>
                                    <small class="text-muted">{{ $feedback['date'] ?? '' }}</small>
                                </div>
                                <div class="rating-stars my-2">
                                    {!! str_repeat('<i class="fas fa-star"></i>', (int) ($feedback['stars'] ?? 0)) !!}
                                </div>
                                <p class="small mb-0 text-secondary">{{ $feedback['comment'] ?? '' }}</p>
                                @if (!empty($feedback['adminReply']['text']))
                                    <div class="mt-2 p-2 bg-light border-start border-danger border-3 small">
                                        <strong class="text-danger">BossDrive Response</strong>
                                        <div>{{ $feedback['adminReply']['text'] }}</div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="small text-muted text-center mb-0">No feedback yet for this vehicle.</p>
                        @endforelse
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <small class="text-muted me-auto"><i class="fas fa-info-circle me-1"></i>Feedback and ratings are available to registered customers.</small>
                        <button type="button" class="btn btn-dark rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="guestPriceGuideModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0 text-uppercase"><i class="fas fa-tags me-2"></i>Big Boss <span class="text-danger">Price Guide</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">Reference rates per vehicle category. Actual price per unit is shown on each car's card.</p>
                    @php
                        $sedanGuide = $priceGuides->firstWhere('category', 'Sedan');
                        $gasGuide = $priceGuides->firstWhere('category', 'Pick-up / Expanded (7-Seater) (2 Days) — Gas');
                        $dieselGuide = $priceGuides->firstWhere('category', 'Expanded — Diesel');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100">
                                <h6 class="fw-bold text-danger small text-uppercase mb-3"><i class="fas fa-car me-2"></i>Sedan</h6>
                                <table class="table table-sm mb-0 small">
                                    <tbody>
                                        <tr><td>City Driving</td><td class="text-end fw-bold">₱{{ number_format($sedanGuide->city_driving) }}</td></tr>
                                        <tr><td>Province</td><td class="text-end fw-bold">₱{{ number_format($sedanGuide->province) }}</td></tr>
                                        <tr><td>Long Distance (2 Days)</td><td class="text-end fw-bold">₱{{ number_format($sedanGuide->long_distance) }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100">
                                <h6 class="fw-bold text-danger small text-uppercase mb-3"><i class="fas fa-shuttle-van me-2"></i>Pick-up / Expanded (7-Seater) (2 Days) — Gas</h6>
                                <table class="table table-sm mb-0 small">
                                    <tbody>
                                        <tr><td>City Driving</td><td class="text-end fw-bold">₱{{ number_format($gasGuide->city_driving) }}</td></tr>
                                        <tr><td>Province</td><td class="text-end fw-bold">₱{{ number_format($gasGuide->province) }}</td></tr>
                                        <tr><td>Long Distance (2 Days)</td><td class="text-end fw-bold">₱{{ number_format($gasGuide->long_distance) }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100">
                                <h6 class="fw-bold text-danger small text-uppercase mb-3"><i class="fas fa-shuttle-van me-2"></i>Expanded — Diesel</h6>
                                <table class="table table-sm mb-0 small">
                                    <tbody>
                                        <tr><td>City Driving</td><td class="text-end fw-bold">₱{{ number_format($dieselGuide->city_driving) }}</td></tr>
                                        <tr><td>Province</td><td class="text-end fw-bold">₱{{ number_format($dieselGuide->province) }}</td></tr>
                                        <tr><td>Long Distance (2 Days)</td><td class="text-end fw-bold">₱{{ number_format($dieselGuide->long_distance) }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100">
                                <h6 class="fw-bold text-danger small text-uppercase mb-3"><i class="fas fa-clock me-2"></i>Hourly Rates</h6>
                                <table class="table table-sm mb-0 small">
                                    <tbody>
                                        <tr><td>Sedan</td><td class="text-end fw-bold">₱{{ number_format($sedanGuide->hourly) }}/hr</td></tr>
                                        <tr><td>Expanded (Gas)</td><td class="text-end fw-bold">₱{{ number_format($gasGuide->hourly) }}/hr</td></tr>
                                        <tr><td>Expanded (Diesel)</td><td class="text-end fw-bold">₱{{ number_format($dieselGuide->hourly) }}/hr</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="calendarModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title fw-bold"><i class="fas fa-calendar-alt me-2"></i> VEHICLE RENTAL SCHEDULE</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-outline-dark btn-sm" onclick="changeMonth(-1)" aria-label="Previous month"><i class="fas fa-chevron-left"></i></button>
                        <h4 class="fw-bold mb-0" id="calendarMonthYear"></h4>
                        <button class="btn btn-outline-dark btn-sm" onclick="changeMonth(1)" aria-label="Next month"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="d-flex gap-2"><span class="badge bg-danger">Ongoing</span><span class="badge bg-primary">Reserved</span><span class="badge bg-warning text-dark">Maintenance</span></div>
                </div>
                <div class="table-responsive"><table class="table table-bordered bg-white calendar-table"><thead><tr><th>SUN</th><th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th></tr></thead><tbody id="calendarBody"></tbody></table></div>
            </div>

            <div class="modal fade calendar-details-modal" id="calendarDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 rounded-4">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title fw-bold" id="calendarDetailsTitle">Schedules</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="calendarDetailsBody"></div>
                    </div>
                </div>
            </div>
        </div></div>
    </div>

    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">Reserve <span id="modalCarName"></span></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('reservations.submit') }}">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted">Guests must log in before completing a reservation.</p>
                    <label class="small fw-bold" for="pickup_date">Pickup Date</label><input id="pickup_date" name="pickup_date" type="date" class="form-control mb-3" required>
                    <label class="small fw-bold" for="return_date">Return Date</label><input id="return_date" name="return_date" type="date" class="form-control" required>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Continue</button></div>
            </form>
        </div></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const today = new Date();
        let currentViewDate = new Date(today.getFullYear(), today.getMonth(), 1);
        let calendarReservations = @json($calendarReservations ?? []);
        let calendarMaintenance = @json($calendarMaintenance ?? []);
        function displayGuestCategory(category) {
            const normalized = String(category || '').toLowerCase();
            if (normalized.includes('diesel')) return 'Diesel';
            if (normalized.includes('pick-up') || normalized.includes('pickup')) return 'Pick-up / Expanded';
            return category || '';
        }

        function showGuestSpecifications(button) {
            const stockButton = button.closest('.card').querySelector('[data-units]');
            const units = JSON.parse(stockButton.dataset.units || '[]');
            const unitIndex = units.findIndex(function (unit) { return String(unit.id) === button.dataset.unitId; });
            const unit = units[unitIndex];
            if (!unit) return;

            document.getElementById('guestSpecificationsTitle').textContent =
                stockButton.dataset.model + ' Unit #' + (unitIndex + 1) + ' Specifications';
            const list = document.getElementById('guestSpecificationsList');
            list.replaceChildren();
            const specifications = [
                ['Transmission Type', unit.transmission],
                ['Fuel Type', unit.fuel],
                ['Capacity', unit.capacity_type],
            ].filter(function (specification) { return Boolean(specification[1]); });
            specifications.forEach(function (specification) {
                const row = document.createElement('div');
                row.className = 'list-group-item d-flex justify-content-between gap-3';
                const label = document.createElement('strong');
                label.textContent = specification[0];
                const value = document.createElement('span');
                value.className = 'text-end';
                value.textContent = specification[1];
                row.append(label, value);
                list.appendChild(row);
            });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('guestSpecificationsModal')).show();
        }

        function showGuestUnits(button) {
            const units = JSON.parse(button.dataset.units || '[]');
            document.getElementById('guestUnitsTitle').textContent = button.dataset.model + ' Units';
            const list = document.getElementById('guestUnitsList');
            list.replaceChildren();
            units.forEach(function (unit, index) {
                const schedule = unit.schedule || [];
                const maintenance = unit.status === 'maintenance' || schedule.some(function (entry) {
                    return entry.type === 'Maintenance';
                });
                const status = maintenance
                    ? 'Maintenance'
                    : (unit.status === 'rented' ? 'Reserved' : (unit.status === 'unavailable' ? 'Unavailable' : 'Available'));
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                const details = document.createElement('span');
                details.className = 'text-start';
                const title = document.createElement('strong');
                title.textContent = button.dataset.model + ' #' + (index + 1);
                const plate = document.createElement('small');
                plate.className = 'd-block';
                plate.textContent = 'Plate: ' + (unit.plate || 'Not assigned');
                const specifications = document.createElement('small');
                specifications.className = 'd-block text-muted';
                specifications.textContent = [displayGuestCategory(unit.category), unit.transmission, unit.fuel, unit.capacity_type]
                    .filter(Boolean)
                    .join(' · ') || 'Vehicle details not provided';
                details.append(title, plate, specifications);
                const badge = document.createElement('span');
                badge.className = 'badge ' + (status === 'Available' ? 'text-bg-success' : (status === 'Maintenance' ? 'text-bg-warning' : 'text-bg-primary'));
                badge.textContent = status;
                item.append(details, badge);
                item.addEventListener('click', function () {
                    selectGuestUnit(unit, index, button.dataset.cardId, button.dataset.model, status);
                });
                list.appendChild(item);
            });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('guestUnitsModal')).show();
        }

        function selectGuestUnit(unit, index, cardId, modelName, status) {
            document.getElementById('guest-title-' + cardId).textContent = modelName + ' Unit #' + (index + 1);
            document.getElementById('guest-unit-' + cardId).innerHTML = '<strong>Plate:</strong> ' + escapeGuestHtml(unit.plate || 'Not assigned');
            document.getElementById('guest-category-' + cardId).textContent = displayGuestCategory(unit.category);
            document.getElementById('guest-specs-button-' + cardId).dataset.unitId = String(unit.id);
            const badge = document.getElementById('guest-status-' + cardId);
            badge.textContent = status;
            badge.className = 'status-badge ' + (status === 'Available' ? 'bg-success text-white' : (status === 'Maintenance' ? 'bg-warning text-dark' : (status === 'Ongoing Rental' ? 'bg-danger text-white' : 'bg-primary text-white')));
            const rentButton = badge.closest('.card').querySelector('.rent-now-link');
            badge.closest('.card').querySelectorAll('.catalog-card-footer .alert').forEach(function (alert) {
                alert.classList.add('d-none');
            });
            const schedule = unit.schedule || [];
            const relevantSchedule = schedule.find(function (entry) {
                return ['Reserved', 'Rented', 'Special', 'Maintenance'].includes(entry.type) && entry.start && entry.end;
            });
            const scheduleBanner = document.getElementById('guest-schedule-' + cardId);
            if (relevantSchedule) {
                scheduleBanner.textContent = (relevantSchedule.type === 'Special' ? 'Reserved' : relevantSchedule.type) + ': ' + relevantSchedule.start + ' - ' + relevantSchedule.end;
                scheduleBanner.className = 'small fw-bold mb-3 ' + (relevantSchedule.type === 'Maintenance' ? 'text-warning' : (relevantSchedule.type === 'Rented' ? 'text-danger' : 'text-primary'));
            } else {
                scheduleBanner.textContent = '';
            }
            if (status === 'Available') {
                rentButton.href = '{{ url('/user/login') }}?vehicle_id=' + encodeURIComponent(unit.id);
                rentButton.classList.remove('disabled');
                rentButton.classList.remove('btn-secondary');
                rentButton.classList.add('btn-dark');
                rentButton.textContent = 'Rent Now';
                rentButton.removeAttribute('aria-disabled');
            } else {
                rentButton.href = '#';
                rentButton.classList.add('disabled');
                rentButton.classList.remove('btn-dark');
                rentButton.classList.add('btn-secondary');
                rentButton.textContent = 'Unavailable';
                rentButton.setAttribute('aria-disabled', 'true');
            }
            bootstrap.Modal.getInstance(document.getElementById('guestUnitsModal')).hide();
        }

        function escapeGuestHtml(value) {
            return String(value).replace(/[&<>"']/g, function (character) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
            });
        }
        function toGuestISODate(year, month, day) {
            return year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        }

        function guestCalendarEventsForDate(iso) {
            const reservations = calendarReservations
                .filter(function (entry) { return iso >= entry.start && iso <= entry.end; })
                .map(function (entry) {
                    return Object.assign({}, entry, {
                        type: entry.status === 'Ongoing' ? 'Rented' : 'Reserved'
                    });
                });
            const maintenance = calendarMaintenance
                .filter(function (entry) { return iso >= entry.start && iso <= entry.end; })
                .map(function (entry) { return Object.assign({}, entry, { type: 'Maintenance' }); });
            return reservations.concat(maintenance);
        }

        function guestCalendarVehicleLabel(entry) {
            return entry.vehicle + (entry.unitNumber ? ' Unit #' + entry.unitNumber : '');
        }

        function renderCalendar() {
            const year = currentViewDate.getFullYear();
            const month = currentViewDate.getMonth();
            const monthYear = document.getElementById('calendarMonthYear');
            const calendarBody = document.getElementById('calendarBody');
            monthYear.textContent = currentViewDate.toLocaleString('default', { month: 'long', year: 'numeric' });
            calendarBody.replaceChildren();

            const firstDay = new Date(year, month, 1).getDay();
            const days = new Date(year, month + 1, 0).getDate();
            let date = 1;
            for (let rowIndex = 0; rowIndex < 6 && date <= days; rowIndex++) {
                const row = document.createElement('tr');
                for (let column = 0; column < 7; column++) {
                    const cell = document.createElement('td');
                    if ((rowIndex > 0 || column >= firstDay) && date <= days) {
                        const iso = toGuestISODate(year, month, date);
                        const isToday = iso === toGuestISODate(today.getFullYear(), today.getMonth(), today.getDate());
                        if (isToday) cell.className = 'today-cell';
                        let cellHtml = '<span class="cal-date">' + date + (isToday ? ' <small class="text-danger">(Today)</small>' : '') + '</span>';
                        const events = guestCalendarEventsForDate(iso);
                        events.slice(0, 3).forEach(function (entry) {
                            const eventClass = entry.type === 'Maintenance'
                                ? 'event-maintenance'
                                : (entry.type === 'Rented' ? 'event-rented' : 'event-reserved');
                            cellHtml += '<div class="cal-event ' + eventClass + '" title="' +
                                escapeGuestHtml(guestCalendarVehicleLabel(entry) + ' — ' + entry.type) + '">' +
                                escapeGuestHtml(guestCalendarVehicleLabel(entry)) + '</div>';
                        });
                        if (events.length > 3) {
                            cellHtml += '<button type="button" class="cal-more" onclick="openGuestCalendarDetails(\'' + iso + '\')">+' + (events.length - 3) + ' more</button>';
                        }
                        cell.innerHTML = cellHtml;
                        date++;
                    }
                    row.appendChild(cell);
                }
                calendarBody.appendChild(row);
            }
        }

        function openGuestCalendarDetails(iso) {
            const body = document.getElementById('calendarDetailsBody');
            document.getElementById('calendarDetailsTitle').textContent =
                'Schedules for ' + new Date(iso + 'T00:00:00').toLocaleDateString(undefined, { dateStyle: 'long' });
            body.innerHTML = '';
            guestCalendarEventsForDate(iso).forEach(function (entry) {
                const row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between gap-2 border rounded-3 p-2 mb-2 small fw-bold';
                const description = document.createElement('span');
                description.textContent = guestCalendarVehicleLabel(entry) + (entry.plate ? ' (' + entry.plate + ')' : '');
                const status = document.createElement('span');
                status.className = 'badge ' + (entry.type === 'Maintenance'
                    ? 'bg-warning text-dark'
                    : (entry.type === 'Rented' ? 'bg-danger' : 'bg-primary'));
                status.textContent = entry.type === 'Rented' ? 'Ongoing' : entry.type;
                row.append(description, status);
                body.appendChild(row);
            });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('calendarDetailsModal')).show();
        }

        function changeMonth(step) {
            currentViewDate.setMonth(currentViewDate.getMonth() + step);
            renderCalendar();
        }

        let guestAvailabilitySignature = '';
        async function syncGuestAvailability() {
            if (document.hidden || document.querySelector('.modal.show')) return;

            try {
                const response = await fetch('{{ route('vehicles.availability') }}', {
                    headers: {'Accept': 'application/json'},
                    cache: 'no-store'
                });
                if (!response.ok) throw new Error('Vehicle availability refresh failed: ' + response.status);
                const data = await response.json();
                const signature = JSON.stringify(data);
                if (signature === guestAvailabilitySignature) return;
                guestAvailabilitySignature = signature;

                const liveVehicles = new Map(data.vehicles.map(function (vehicle) {
                    return [Number(vehicle.id), vehicle];
                }));
                document.querySelectorAll('[data-units]').forEach(function (stockButton) {
                    const units = JSON.parse(stockButton.dataset.units || '[]').map(function (unit) {
                        const live = liveVehicles.get(Number(unit.id));
                        if (!live) return unit;
                        return Object.assign({}, unit, {
                            status: live.status.toLowerCase(),
                            schedule: live.schedule || []
                        });
                    });
                    stockButton.dataset.units = JSON.stringify(units);

                    const cardId = stockButton.dataset.cardId;
                    const specsButton = document.getElementById('guest-specs-button-' + cardId);
                    const selectedId = Number(specsButton.dataset.unitId || cardId);
                    const selectedIndex = units.findIndex(function (unit) { return Number(unit.id) === selectedId; });
                    const selectedUnit = units[selectedIndex];
                    if (!selectedUnit) return;
                    const selectedLive = liveVehicles.get(selectedId);
                    if (!selectedLive) return;
                    const status = selectedLive.status === 'Rented'
                        ? (selectedLive.currentReservation && selectedLive.currentReservation.status === 'released'
                            ? 'Ongoing Rental'
                            : 'Reserved')
                        : selectedLive.status;
                    selectGuestUnit(selectedUnit, selectedIndex, cardId, stockButton.dataset.model, status);
                });

                calendarReservations = data.reservations;
                calendarMaintenance = data.maintenance;
                renderCalendar();
            } catch (error) {
                console.error('Unable to refresh live guest vehicle availability.', error);
            }
        }

        renderCalendar();
        syncGuestAvailability();
        setInterval(syncGuestAvailability, 5000);
        document.getElementById('bookingModal').addEventListener('show.bs.modal', event => {
            document.getElementById('modalCarName').textContent = event.relatedTarget.dataset.car;
        });
    </script>
</body>
</html>
