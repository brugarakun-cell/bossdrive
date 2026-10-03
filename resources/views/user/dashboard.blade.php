<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - User Dashboard</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red:#b71c1c; --boss-dark:#121212; --boss-grey:#e0e0e0; }
        body { background:var(--boss-grey); color:#000; font-family:'Segoe UI',sans-serif; }
        .sidebar { width:250px; height:100vh; background:var(--boss-dark); position:fixed; padding:20px; border-right:4px solid var(--boss-red); z-index:1000; display:flex; flex-direction:column; overflow-y:auto; }
        .nav-link { color:#fff; margin-bottom:10px; border-radius:10px; padding:12px 15px; font-weight:600; text-decoration:none; display:block; }
        .nav-link:hover,.nav-link.active { background:var(--boss-red); color:#fff!important; }
        .sidebar-footer { margin-top:auto; width:100%; border-top:1px solid #333; padding-top:15px; }
        .sidebar-footer a { color:#888; text-decoration:none; font-size:.85rem; }
        .main-content { margin-left:250px; min-height:100vh; }
        .top-nav { background:#fff; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #ccc; position:sticky; top:0; z-index:999; }
        .text-black-bold { color:#000; font-weight:800; } .text-boss-red { color:var(--boss-red); font-weight:800; }
        .content-container { padding:30px; }
        .stat-card,.requirement-card { background:#fff; border-radius:15px; padding:20px; box-shadow:0 4px 10px rgba(0,0,0,.05); cursor:pointer; transition:.3s; }
        .stat-card:hover { transform:translateY(-3px); }
        .status-card { background:#fff; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,.05); position:relative; }
        .status-badge { position:absolute; top:15px; right:20px; background:#ffc107; color:#000; padding:5px 15px; border-radius:20px; font-weight:700; font-size:.8rem; }
        .status-badge.rejected { background:#dc3545; color:#fff; }
        .control-number-box { background:#fff5f5; border:1px dashed var(--boss-red); border-radius:12px; padding:10px 15px; }
        .tracker-wrapper { display:flex; justify-content:space-between; position:relative; margin-top:30px; padding:0 10px; }
        .tracker-wrapper:before { content:''; position:absolute; top:15px; left:0; right:0; height:4px; background:#eee; z-index:1; }
        .step { position:relative; z-index:2; text-align:center; width:20%; }
        .step-icon { width:35px; height:35px; background:#eee; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; color:#aaa; border:3px solid #fff; }
        .step.active .step-icon { background:var(--boss-red); color:#fff; } .step.active .step-text { color:var(--boss-red); font-weight:700; }
        .step-text { font-size:.75rem; color:#888; text-transform:uppercase; } .process-desc { font-size:.65rem; color:#aaa; display:block; }
        .req-item { padding:12px; background:#f8f9fa; border-radius:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; }
        .req-item.verified { border-left:4px solid #198754; }
        .step-tooltip { display:none; position:absolute; bottom:calc(100% + 12px); left:50%; transform:translateX(-50%); width:220px; background:#fff; border-radius:14px; box-shadow:0 10px 25px rgba(0,0,0,.15); padding:14px; text-align:left; z-index:20; }
        .step:hover .step-tooltip { display:block; }
        .step-tooltip-title { font-size:.75rem; font-weight:800; color:var(--boss-red); text-transform:uppercase; display:block; margin-bottom:8px; }
        .step-tooltip-row { display:flex; justify-content:space-between; gap:8px; font-size:.72rem; padding:3px 0; }
        .history-card { border:0; transition:.2s; }
        .history-card:hover { transform:translateY(-2px); background:#fff!important; }
        @media(max-width:768px){ .sidebar{width:210px}.main-content{margin-left:0}.content-container{padding:15px}.tracker-wrapper{font-size:.8rem}.sidebar-footer{position:static;width:100%;} }
        @media(max-width:575.98px){
            .stat-card,.requirement-card{padding:11px}
            .dashboard-summary-row > .col-md-4{flex:0 0 33.333333%;max-width:33.333333%;padding-left:4px;padding-right:4px}
            .dashboard-summary-row .stat-card{height:100%;min-height:106px;padding:9px;flex-direction:column;align-items:flex-start!important;justify-content:flex-start}
            .dashboard-summary-row .stat-card > div:first-child{width:30px;height:30px;padding:0!important;margin:0 0 6px!important;display:flex;align-items:center;justify-content:center;flex:0 0 30px}
            .dashboard-summary-row .stat-card > div:first-child i{font-size:.78rem!important}
            .dashboard-summary-row .stat-card small{font-size:.53rem;line-height:1.15;white-space:normal}
            .dashboard-summary-row .stat-card .fw-bold{display:block;font-size:.62rem;line-height:1.2;overflow-wrap:anywhere}
            .status-card{border-radius:15px}
            .status-badge{top:10px;right:10px;padding:4px 9px;font-size:.68rem}
            .tracker-wrapper{margin-top:22px;padding:0}
            .step-icon{width:30px;height:30px;font-size:.75rem}
            .tracker-wrapper:before{top:13px}
            .step-text{font-size:.58rem}
            .process-desc{font-size:.55rem}
            .step-tooltip{width:min(220px,80vw)}
            .req-item{gap:8px;padding:10px;font-size:.85rem}
            .control-number-box{padding:8px 10px;overflow-wrap:anywhere}
            .history-card{padding:12px !important}
            .modal-dialog{margin:.5rem}
            .modal-body{padding:1rem !important}
        }
    </style>
</head>
<body>
@php
    $reservation = $latestReservation;
    $documents = collect($approvedDocuments ?? [])
        ->filter(fn (?string $path): bool => filled($path)
            && (filter_var($path, FILTER_VALIDATE_URL)
                || \Illuminate\Support\Facades\Storage::disk('public')->exists(ltrim($path, '/'))))
        ->all();
    $completedDocumentsNeedRepair = $reservation
        && $reservation->status === 'completed'
        && collect(['driver_license', 'valid_id', 'proof_of_billing'])
            ->contains(fn (string $key): bool => ! filled($documents[$key] ?? null));
    $documentsApproved = $reservation
        && in_array($reservation->status, ['verified', 'processing', 'released', 'completed'], true)
        && auth()->user()->documents_verified_at
        && (!auth()->user()->documents_rejected_at || auth()->user()->documents_rejected_at <= auth()->user()->documents_verified_at)
        && collect(['driver_license', 'valid_id', 'proof_of_billing'])
            ->every(fn (string $key) => filled($documents[$key] ?? null));
    $documentsNeedAdminReview = $reservation
        && auth()->user()->documents_verified_at
        && auth()->user()->documents_rejected_at
        && auth()->user()->documents_rejected_at->greaterThan(auth()->user()->documents_verified_at)
        && $reservation->documents_updated_at
        && $reservation->documents_updated_at->greaterThan(auth()->user()->documents_verified_at);
    $uploadedFileUrl = static function (?string $path): ?string {
        if (!$path) {
            return null;
        }

        return filter_var($path, FILTER_VALIDATE_URL)
            ? $path
            : asset('storage/'.ltrim($path, '/'));
    };
    $status = strtolower($reservation?->status ?? 'pending');
    $statusLabel = in_array($status, ['cancelled', 'void'], true)
        ? strtoupper($status)
        : strtoupper($reservation?->status ?? 'PENDING');
    $carImages = [
        'Toyota Vios' => 'car1.png',
        'Mitsubishi Mirage' => 'car2.png',
        'Honda Civic' => 'car3.png',
        'Toyota Fortuner' => 'car4.png',
    ];
    $carImage = $reservation ? ($carImages[$reservation->vehicle] ?? 'car1.png') : null;
    $reservationData = $reservations->map(function ($item) use ($carImages) {
        return [
            'id' => $item->id,
            'vehicle' => $item->vehicle,
            'image' => asset('image/'.($carImages[$item->vehicle] ?? 'car1.png')),
            'control_number' => $item->control_number,
            'status' => $item->status === 'cancelled' ? 'CANCELLED' : strtoupper($item->status),
            'service_option' => ucfirst($item->service_option),
            'payment_mode' => ucfirst($item->payment_mode),
            'pickup_date' => $item->pickup_date->format('M d, Y'),
            'pickup_time' => $item->pickup_time ? substr($item->pickup_time, 0, 5) : null,
            'return_date' => $item->return_date->format('M d, Y'),
            'driver_option' => $item->driver_option,
            'assigned_driver_name' => $item->assigned_driver_name,
            'assigned_driver_contact' => $item->assigned_driver_contact,
            'cancel_url' => route('user.reservations.cancel', $item),
            'documents_url' => route('user.reservations.documents.update', $item),
        ];
    })->values();
    $steps = ['Booked', 'Verified', 'Processing', 'Released', 'Returned'];
    $activeStep = match ($status) {
        'verified' => 2,
        'processing' => 3,
        'released' => 4,
        'completed' => 5,
        default => 1,
    };
    $statusBadgeClass = in_array($status, ['cancelled', 'void'], true) ? 'rejected' : '';
@endphp
@include('user.partials.navigation', ['title' => 'DASHBOARD'])
    <div class="content-container">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="row g-3 mb-4 dashboard-summary-row">
            <div class="col-md-4" data-bs-toggle="modal" data-bs-target="#infoRental"><div class="stat-card d-flex align-items-center border-start border-danger border-5"><div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle me-3"><i class="fas fa-car-side fs-4"></i></div><div><small class="text-muted fw-bold d-block">CURRENT RENTAL</small><span class="fw-bold" id="dashboardCurrentVehicle">{{ $reservation?->vehicle ?? 'No reservation yet' }}</span></div></div></div>
            <div class="col-md-4" data-bs-toggle="modal" data-bs-target="#infoBooking"><div class="stat-card d-flex align-items-center"><div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle me-3"><i class="fas fa-history fs-4"></i></div><div><small class="text-muted fw-bold d-block">TOTAL BOOKINGS</small><span class="fw-bold" id="dashboardBookingCount">{{ $reservations->count() }} Booking{{ $reservations->count() === 1 ? '' : 's' }}</span></div></div></div>
            <div class="col-md-4" data-bs-toggle="modal" data-bs-target="#infoMember"><div class="stat-card d-flex align-items-center"><div class="p-3 bg-success bg-opacity-10 text-success rounded-circle me-3"><i class="fas fa-shield-alt fs-4"></i></div><div><small class="text-muted fw-bold d-block">MEMBERSHIP</small><span class="fw-bold text-uppercase">Verified Member</span></div></div></div>
        </div>
        <div class="row g-4">
            <div class="col-lg-8"><div class="status-card p-4 mb-4">
                <div class="status-badge {{ $statusBadgeClass }}"><i id="dashStatusIcon" class="fas {{ in_array($status, ['cancelled', 'void'], true) ? 'fa-times-circle' : 'fa-clock' }} me-1"></i> <span id="dashStatus">{{ $statusLabel }}</span></div><h5 class="fw-bold mb-3">Current Trip Status</h5>
                <div id="dashboardTripContent">
                @if($reservation)
                <div class="control-number-box d-flex justify-content-between align-items-center mb-4"><div><small class="text-muted d-block fw-bold" style="font-size:.65rem;">CONTROL NUMBER</small><span class="fw-bold text-boss-red" id="dashControlNumber" style="letter-spacing:1px;">{{ $reservation->control_number }}</span></div><button type="button" class="btn btn-sm btn-outline-dark rounded-pill" onclick="copyControlNumber()"><i class="fas fa-copy me-1"></i><span id="copyBtnText">Copy</span></button></div>
                <div class="row align-items-center"><div class="col-md-5"><div class="text-center bg-light p-3 rounded-4"><img id="dashCarImage" src="{{ asset('image/'.$carImage) }}" alt="{{ $reservation->vehicle }}" class="img-fluid" style="max-height:110px;"><h6 id="dashVehicle" class="fw-bold mt-2 mb-0">{{ $reservation->vehicle }}</h6><small class="d-block text-muted">Plate: <span id="dashPlate">{{ $reservation->vehicleUnit?->plate ?? 'Not assigned' }}</span></small><small id="dashDates">{{ $reservation->pickup_date->format('M d, Y') }} - {{ $reservation->return_date->format('M d, Y') }}</small></div></div><div class="col-md-7"><div class="d-grid gap-2"><button type="button" class="btn btn-dark rounded-pill fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#garageModal"><i class="fas fa-warehouse me-2"></i>GARAGE ADDRESS</button><button type="button" id="dashDeliveryAddressButton" class="btn btn-outline-danger rounded-pill fw-bold btn-sm {{ $reservation->service_option === 'delivery' ? '' : 'd-none' }}" data-bs-toggle="modal" data-bs-target="#userAddrModal"><i class="fas fa-map-marker-alt me-2"></i>YOUR DELIVERY ADDRESS</button></div><button type="button" id="dashCancelBookingButton" class="btn btn-link text-danger text-decoration-none fw-bold w-100 mt-3 small {{ $reservation->status === 'pending' ? '' : 'd-none' }}" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="fas fa-times-circle me-1"></i> Cancel Booking</button><p id="dashPayment" class="small text-muted mt-2 text-center">Payment: {{ ucfirst($reservation->payment_mode) }}</p></div></div>
                <hr class="my-4"><h6 class="fw-bold mb-1"><i class="fas fa-tasks text-danger me-2"></i>Rental Process Tracker</h6><small class="text-muted d-block mb-2" style="font-size:.7rem;">Hover on a step to see the details.</small>
                <div id="dashReservationAlert" class="alert alert-danger py-2 mb-3" style="{{ in_array($status, ['cancelled', 'void'], true) ? '' : 'display:none;' }}"><i class="fas fa-times-circle me-2"></i><strong id="dashReservationAlertTitle">Booking {{ $status === 'void' ? 'voided' : 'cancelled' }}.</strong> This reservation is no longer active.<div id="dashRejectionReason" class="mt-2" style="{{ $reservation?->rejection_comment ? '' : 'display:none;' }}"><strong>Reason from admin:</strong> <span>{{ $reservation?->rejection_comment }}</span></div></div>
                <div class="small text-muted mb-3"><i class="fas fa-clock me-1 text-danger"></i>Pickup: <strong id="dashPickupTime">{{ $reservation->pickup_date->format('M d, Y') }} at {{ \Illuminate\Support\Carbon::parse($reservation->pickup_time)->format('g:i A') }}</strong><span class="ms-3" id="dashDriverInfo" style="{{ $reservation->driver_option === 'with_driver' ? '' : 'display:none;' }}"><i class="fas fa-user-tie me-1 text-danger"></i>Driver: <strong id="dashDriverName">{{ $reservation->assigned_driver_name ?: 'Not yet assigned' }}</strong> (<span id="dashDriverContact">{{ $reservation->assigned_driver_contact ?: 'Contact to be confirmed' }}</span>)</span></div>
                <div class="tracker-wrapper" id="rentalProcessTracker" data-status="{{ $status }}" data-control-number="{{ $reservation->control_number }}">@foreach($steps as $index => $step)<div class="step {{ $index < $activeStep ? 'active' : '' }}" data-step-index="{{ $index }}"><div class="step-icon"><i class="fas {{ ['fa-file-invoice','fa-id-card','fa-car-side','fa-key','fa-undo-alt'][$index] }}"></i></div><div class="step-text">{{ $step }}</div><span class="process-desc">{{ in_array($status, ['cancelled', 'void'], true) ? 'Not continued' : ($index < $activeStep ? 'Confirmed' : 'Not yet reached') }}</span><div class="step-tooltip"><span class="step-tooltip-title">{{ $step }}</span><div class="step-tooltip-row"><span>Control No.</span><span class="tracker-control-number">{{ $reservation->control_number }}</span></div><div class="step-tooltip-row"><span>Status</span><span class="tracker-status">{{ $statusLabel }}</span></div></div></div>@endforeach</div>
                @else
                    <div id="dashboardEmptyState" class="text-center py-5"><i class="fas fa-calendar-plus text-danger fs-1 mb-3"></i><h6 class="fw-bold">No reservation yet</h6><a href="{{ route('user.reservations') }}" class="btn btn-danger rounded-pill">MAKE A RESERVATION</a></div>
                @endif
                </div>
            </div></div>
            <div class="col-lg-4">
                <div class="requirement-card shadow-sm mb-4">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-id-card text-danger me-2"></i>Document Status
                    </h6>

                    @foreach ([
                        'driver_license' => "Driver's License",
                        'valid_id' => 'Government ID',
                        'proof_of_billing' => 'Proof of Billing',
                    ] as $key => $label)
                        <div class="req-item {{ isset($documents[$key]) && $documentsApproved ? 'verified' : '' }}">
                            <small class="fw-bold">{{ $label }}</small>
                            @if (isset($documents[$key]))
                                <a href="{{ $uploadedFileUrl($documents[$key]) }}" target="_blank" rel="noopener" class="{{ $documentsApproved ? 'text-success' : 'text-warning' }}">
                                    <i class="fas {{ $documentsApproved ? 'fa-check-circle' : 'fa-clock' }}"></i>
                                </a>
                            @else
                                <i class="fas fa-clock text-warning"></i>
                            @endif
                        </div>
                    @endforeach

                    <button type="button" id="dashboardUpdateDocumentsButton" class="btn btn-outline-dark w-100 mt-2 rounded-pill fw-bold btn-sm {{ $reservation ? '' : 'd-none' }}" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                        <i class="fas fa-edit me-1"></i> Update Documents / Replace File
                    </button>
                    @if ($completedDocumentsNeedRepair)
                        <div class="alert alert-warning small mt-2 mb-0 py-2">Some documents are missing from storage. Upload the missing files again to restore them to this completed booking.</div>
                    @endif
                    @if ($documentsNeedAdminReview)
                        <div class="alert alert-warning small mt-3 mb-0 py-2">Updated documents are waiting for admin review. Any next booking step will remain subject to approval.</div>
                    @endif

                    <div class="mt-4 p-3 bg-light rounded-4 border text-center">
                        <h6 class="fw-bold small mb-2 text-uppercase text-muted">Need Assistance?</h6>
                        <span class="d-block fw-bold text-danger">Big Boss Dispatcher</span>
                        <span class="d-block fw-bold h5 mb-0">+63 912 345 6789</span>
                        <a href="https://m.me/BigBossCarRental" target="_blank" class="btn btn-primary w-100 rounded-pill fw-bold btn-sm mt-3">
                            <i class="fab fa-facebook-messenger me-2"></i>Chat on Messenger
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="infoRental" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content p-4 text-center"><h6 class="fw-bold text-danger">Active Rental Detail</h6><hr><p class="small mb-1"><b>Car:</b> <span id="rentalModalVehicle">{{ $reservation?->vehicle ?? 'No reservation yet' }}</span></p><p class="small mb-1"><b>Plate:</b> <span id="rentalModalPlate">{{ $reservation?->vehicleUnit?->plate ?? 'Not assigned' }}</span></p><p class="small mb-1"><b>Rent Date:</b> <span id="rentalModalPickup">{{ $reservation?->pickup_date?->format('M d, Y') ?? '—' }}</span></p><p class="small mb-0"><b>End Date:</b> <span id="rentalModalReturn">{{ $reservation?->return_date?->format('M d, Y') ?? '—' }}</span></p></div></div></div>
<div class="modal fade" id="infoBooking" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow-lg rounded-4"><div class="modal-header"><h5 class="fw-bold"><i class="fas fa-history text-danger me-2"></i>Booking History</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4" id="bookingHistoryList">@forelse($reservations as $item)<button type="button" class="history-card w-100 text-start d-flex justify-content-between p-3 rounded-3 shadow-sm border-start border-success border-5 mb-2 bg-light" data-reservation-id="{{ $item->id }}"><div class="d-flex align-items-center gap-3"><img src="{{ asset('image/'.($carImages[$item->vehicle] ?? 'car1.png')) }}" alt="{{ $item->vehicle }}" style="width:60px;height:45px;object-fit:contain;"><div><h6 class="fw-bold mb-0">{{ $item->vehicle }}</h6><small class="text-muted">{{ $item->pickup_date->format('M d, Y') }} - {{ $item->return_date->format('M d, Y') }}</small><small class="d-block text-muted">{{ $item->control_number }}</small></div></div><span class="badge bg-success align-self-center">{{ strtoupper($item->status) }}</span></button>@empty<p class="text-muted text-center" id="emptyBookingHistory">No bookings yet.</p>@endforelse</div></div></div></div>
<div class="modal fade" id="infoMember" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content p-4 text-center"><h6 class="fw-bold text-success">Verified Member</h6><p class="small text-muted mb-0">Your account is registered with Big Boss Car Rental.</p></div></div></div>
<div class="modal fade" id="garageModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 p-4 text-center"><i class="fas fa-warehouse fa-3x text-danger mb-3"></i><h5 class="fw-bold">Garage Pickup Location</h5><p class="text-muted">Big Boss Garage, GMA, Cavite</p><button type="button" class="btn btn-dark w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Close</button></div></div></div>
<div class="modal fade" id="userAddrModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 p-4 text-center"><i class="fas fa-home fa-3x text-danger mb-3"></i><h5 class="fw-bold">Your Delivery Address</h5><p class="text-muted" id="dashboardDeliveryAddress">{{ $reservation?->delivery_address ?: 'No delivery address was provided.' }}</p><button type="button" class="btn btn-dark w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Close</button></div></div></div>
<div class="modal fade" id="uploadDocModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content p-4"><h5 class="fw-bold mb-3">Update / Replace Verification Document</h5><p class="small text-muted">Upload a clear JPG, PNG, WEBP, or GIF image. Replaced documents are sent to the admin for review.</p><form id="documentUpdateForm" method="POST" action="{{ $reservation ? route('user.reservations.documents.update', $reservation) : '#' }}" enctype="multipart/form-data">@csrf<select name="document_type" class="form-select mb-3 rounded-pill" required><option value="" selected disabled>Select document type...</option><option value="driver_license">Driver's License</option><option value="valid_id">Government ID</option><option value="proof_of_billing">Proof of Billing</option></select><input type="file" name="document" class="form-control mb-3 rounded-pill" accept=".jpg,.jpeg,.png,.webp,.gif" required><button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold">UPDATE & SUBMIT</button></form></div></div></div>
<div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow"><div class="modal-body p-4 text-center"><i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i><h5 class="fw-bold">Cancel Reservation?</h5><div class="alert alert-danger border-0 small mt-3 text-start"><p class="mb-0"><strong>IMPORTANT:</strong> I understand that by cancelling this booking, my <b>₱1,000.00 Reservation Deposit</b> will be <b>forfeited</b> and is non-refundable.</p></div><p class="text-muted small">Are you sure you want to cancel your reservation for <b id="cancelVehicle">{{ $reservation?->vehicle }}</b>?</p><form id="cancelForm" method="POST" action="{{ $reservation ? route('user.reservations.cancel', $reservation) : '#' }}">@csrf<div class="d-grid gap-2"><button type="submit" class="btn btn-danger rounded-pill fw-bold">CONFIRM CANCELLATION</button><button type="button" class="btn btn-light rounded-pill fw-bold" data-bs-dismiss="modal">GO BACK</button></div></form></div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script>
let reservations = @json($reservationData);
let dashboardControlNumber=document.getElementById('dashControlNumber')?.innerText;
let selectedDashboardReservationId = Number(reservations[0]?.id) || null;
const dashboardStatuses = ['Booked', 'Verified', 'Processing', 'Released', 'Returned'];
const dashboardStepIcons = ['fa-file-invoice', 'fa-id-card', 'fa-car-side', 'fa-key', 'fa-undo-alt'];

function mountDashboardReservation(data) {
    const content = document.getElementById('dashboardTripContent');
    if (!content || document.getElementById('rentalProcessTracker')) return;

    content.innerHTML = '<div class="control-number-box d-flex justify-content-between align-items-center mb-4"><div><small class="text-muted d-block fw-bold" style="font-size:.65rem;">CONTROL NUMBER</small><span class="fw-bold text-boss-red" id="dashControlNumber" style="letter-spacing:1px;"></span></div><button type="button" class="btn btn-sm btn-outline-dark rounded-pill" onclick="copyControlNumber()"><i class="fas fa-copy me-1"></i><span id="copyBtnText">Copy</span></button></div>' +
        '<div class="row align-items-center"><div class="col-md-5"><div class="text-center bg-light p-3 rounded-4"><img id="dashCarImage" class="img-fluid" style="max-height:110px;"><h6 id="dashVehicle" class="fw-bold mt-2 mb-0"></h6><small class="d-block text-muted">Plate: <span id="dashPlate"></span></small><small id="dashDates"></small></div></div><div class="col-md-7"><div class="d-grid gap-2"><button type="button" class="btn btn-dark rounded-pill fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#garageModal"><i class="fas fa-warehouse me-2"></i>GARAGE ADDRESS</button><button type="button" id="dashDeliveryAddressButton" class="btn btn-outline-danger rounded-pill fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#userAddrModal"><i class="fas fa-map-marker-alt me-2"></i>YOUR DELIVERY ADDRESS</button></div><button type="button" id="dashCancelBookingButton" class="btn btn-link text-danger text-decoration-none fw-bold w-100 mt-3 small" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="fas fa-times-circle me-1"></i> Cancel Booking</button><p id="dashPayment" class="small text-muted mt-2 text-center"></p></div></div>' +
        '<hr class="my-4"><h6 class="fw-bold mb-1"><i class="fas fa-tasks text-danger me-2"></i>Rental Process Tracker</h6><small class="text-muted d-block mb-2" style="font-size:.7rem;">Hover on a step to see the details.</small>' +
        '<div id="dashReservationAlert" class="alert alert-danger py-2 mb-3" style="display:none;"><i class="fas fa-times-circle me-2"></i><strong id="dashReservationAlertTitle"></strong> This reservation is no longer active.<div id="dashRejectionReason" class="mt-2" style="display:none;"><strong>Reason from admin:</strong> <span></span></div></div>' +
        '<div class="small text-muted mb-3"><i class="fas fa-clock me-1 text-danger"></i>Pickup: <strong id="dashPickupTime"></strong><span class="ms-3" id="dashDriverInfo"><i class="fas fa-user-tie me-1 text-danger"></i>Driver: <strong id="dashDriverName"></strong> (<span id="dashDriverContact"></span>)</span></div>' +
        '<div class="tracker-wrapper" id="rentalProcessTracker"></div>';

    const tracker = document.getElementById('rentalProcessTracker');
    dashboardStatuses.forEach(function (label, index) {
        const step = document.createElement('div');
        step.className = 'step';
        step.dataset.stepIndex = String(index);
        step.innerHTML = '<div class="step-icon"><i class="fas ' + dashboardStepIcons[index] + '"></i></div><div class="step-text"></div><span class="process-desc"></span><div class="step-tooltip"><span class="step-tooltip-title"></span><div class="step-tooltip-row"><span>Control No.</span><span class="tracker-control-number"></span></div><div class="step-tooltip-row"><span>Status</span><span class="tracker-status"></span></div></div>';
        step.querySelector('.step-text').textContent = label;
        step.querySelector('.step-tooltip-title').textContent = label;
        tracker.appendChild(step);
    });
}

function renderBookingHistory() {
    const list = document.getElementById('bookingHistoryList');
    if (!list) return;
    list.replaceChildren();
    if (!reservations.length) {
        const empty = document.createElement('p');
        empty.className = 'text-muted text-center';
        empty.textContent = 'No bookings yet.';
        list.appendChild(empty);
        return;
    }

    reservations.forEach(function (reservation) {
        const card = document.createElement('button');
        card.type = 'button';
        card.className = 'history-card w-100 text-start d-flex justify-content-between p-3 rounded-3 shadow-sm border-start border-success border-5 mb-2 bg-light';
        card.dataset.reservationId = String(reservation.id);
        const details = document.createElement('div');
        details.className = 'd-flex align-items-center gap-3';
        const image = document.createElement('img');
        image.src = reservation.image;
        image.alt = reservation.vehicle;
        image.style.cssText = 'width:60px;height:45px;object-fit:contain;';
        const text = document.createElement('div');
        const vehicle = document.createElement('h6');
        vehicle.className = 'fw-bold mb-0';
        vehicle.textContent = reservation.vehicle;
        const dates = document.createElement('small');
        dates.className = 'text-muted';
        dates.textContent = reservation.pickup_date + ' - ' + reservation.return_date;
        const control = document.createElement('small');
        control.className = 'd-block text-muted';
        control.textContent = reservation.control_number;
        text.append(vehicle, dates, control);
        details.append(image, text);
        const badge = document.createElement('span');
        badge.className = 'badge align-self-center ' + (['CANCELLED', 'VOID'].includes(reservation.status) ? 'bg-danger' : 'bg-success');
        badge.textContent = reservation.status;
        card.append(details, badge);
        list.appendChild(card);
    });
}

function selectReservation(id) {
    const reservation = reservations.find(item => item.id === Number(id));
    if (!reservation) return;
    selectedDashboardReservationId = Number(id);
    mountDashboardReservation(reservation);

    const image = document.getElementById('dashCarImage');
    const vehicle = document.getElementById('dashVehicle');
    const dates = document.getElementById('dashDates');
    const control = document.getElementById('dashControlNumber');
    const status = document.getElementById('dashStatus');
    const payment = document.getElementById('dashPayment');

    if (image) { image.src = reservation.image; image.alt = reservation.vehicle; }
    if (vehicle) vehicle.innerText = reservation.vehicle;
    const plate = document.getElementById('dashPlate');
    if (plate) plate.innerText = reservation.plate || 'Not assigned';
    if (dates) dates.innerText = `${reservation.pickup_date} - ${reservation.return_date}`;
    const pickupTime = document.getElementById('dashPickupTime');
    if (pickupTime && reservation.pickup_date) pickupTime.innerText = `${reservation.pickup_date} at ${formatDashboardTime(reservation.pickup_time)}`;
    const driverName = document.getElementById('dashDriverName');
    const driverContact = document.getElementById('dashDriverContact');
    const driverInfo = document.getElementById('dashDriverInfo');
    if (driverInfo) driverInfo.style.display = reservation.driver_option === 'with_driver' ? '' : 'none';
    if (driverName) driverName.innerText = reservation.assigned_driver_name || 'Not yet assigned';
    if (driverContact) driverContact.innerText = reservation.assigned_driver_contact || '';
    if (control) control.innerText = reservation.control_number;
    if (status) status.innerText = reservation.status;
    const badge = status?.closest('.status-badge');
    if (badge) badge.classList.toggle('rejected', ['CANCELLED', 'VOID'].includes(reservation.status));
    const statusIcon = document.getElementById('dashStatusIcon');
    if (statusIcon) statusIcon.className = 'fas ' + (['CANCELLED', 'VOID'].includes(reservation.status) ? 'fa-times-circle' : 'fa-clock') + ' me-1';
    if (payment) payment.innerText = `Payment: ${reservation.payment_mode}`;
    updateRentalTracker(reservation.status, reservation.control_number);
    dashboardControlNumber = reservation.control_number;
    const tracker = document.getElementById('rentalProcessTracker');
    if (tracker) tracker.dataset.reservationId = String(reservation.id);

    const currentVehicle = document.getElementById('dashboardCurrentVehicle');
    if (currentVehicle) currentVehicle.textContent = reservation.vehicle;
    const rentalModal = document.getElementById('rentalModalVehicle');
    if (rentalModal) rentalModal.textContent = reservation.vehicle;
    const rentalModalPlate = document.getElementById('rentalModalPlate');
    if (rentalModalPlate) rentalModalPlate.textContent = reservation.plate || 'Not assigned';
    const rentalModalPickup = document.getElementById('rentalModalPickup');
    if (rentalModalPickup) rentalModalPickup.textContent = reservation.pickup_date;
    const rentalModalReturn = document.getElementById('rentalModalReturn');
    if (rentalModalReturn) rentalModalReturn.textContent = reservation.return_date;
    const deliveryButton = document.getElementById('dashDeliveryAddressButton');
    if (deliveryButton) deliveryButton.classList.toggle('d-none', reservation.service_option.toLowerCase() !== 'delivery');
    const deliveryAddress = document.getElementById('dashboardDeliveryAddress');
    if (deliveryAddress) deliveryAddress.textContent = reservation.delivery_address || 'No delivery address was provided.';
    const cancelButton = document.getElementById('dashCancelBookingButton');
    if (cancelButton) cancelButton.classList.toggle('d-none', reservation.status !== 'PENDING');
    const alert = document.getElementById('dashReservationAlert');
    const isCancelled = ['CANCELLED', 'VOID'].includes(reservation.status);
    if (alert) alert.style.display = isCancelled ? '' : 'none';
    const alertTitle = document.getElementById('dashReservationAlertTitle');
    if (alertTitle) alertTitle.textContent = reservation.status === 'VOID' ? 'Booking voided.' : 'Booking cancelled.';
    const rejectionReason = document.getElementById('dashRejectionReason');
    if (rejectionReason) {
        rejectionReason.style.display = reservation.rejection_comment ? '' : 'none';
        rejectionReason.querySelector('span').textContent = reservation.rejection_comment || '';
    }

    const cancelForm = document.getElementById('cancelForm');
    const cancelVehicle = document.getElementById('cancelVehicle');
    const documentForm = document.getElementById('documentUpdateForm');
    if (cancelForm) cancelForm.action = reservation.cancel_url;
    if (cancelVehicle) cancelVehicle.innerText = reservation.vehicle;
    if (documentForm) documentForm.action = reservation.documents_url;
    document.getElementById('dashboardUpdateDocumentsButton')?.classList.remove('d-none');

    const count = document.getElementById('dashboardBookingCount');
    if (count) count.textContent = reservations.length + ' Booking' + (reservations.length === 1 ? '' : 's');
    bootstrap.Modal.getInstance(document.getElementById('infoBooking'))?.hide();
}
function formatDashboardTime(value) {
    if (!value) return 'Time to be confirmed';
    const parts = String(value).split(':');
    const hour = Number(parts[0]);
    const minute = parts[1] || '00';
    return ((hour % 12) || 12) + ':' + minute + ' ' + (hour >= 12 ? 'PM' : 'AM');
}
function updateRentalTracker(rawStatus, controlNumber) {
    const status = String(rawStatus || 'pending').toLowerCase();
    const activeStep = { pending: 1, verified: 2, processing: 3, released: 4, completed: 5 }[status] || 0;
    const tracker = document.getElementById('rentalProcessTracker');
    if (!tracker) return;

    tracker.dataset.status = status;
    tracker.dataset.controlNumber = controlNumber || '';
    tracker.querySelectorAll('.step').forEach(function (step) {
        const index = Number(step.dataset.stepIndex);
        step.classList.toggle('active', !['cancelled', 'void'].includes(status) && index < activeStep);
        const description = step.querySelector('.process-desc');
        if (description) {
            description.innerText = ['cancelled', 'void'].includes(status)
                ? 'Not continued'
                : (index < activeStep ? 'Confirmed' : 'Not yet reached');
        }
        const number = step.querySelector('.tracker-control-number');
        const currentStatus = step.querySelector('.tracker-status');
        if (number) number.innerText = controlNumber || '';
        if (currentStatus) currentStatus.innerText = status.toUpperCase();
    });
}
document.getElementById('bookingHistoryList')?.addEventListener('click', event => {
    const card = event.target instanceof Element ? event.target.closest('.history-card') : null;
    if (card) selectReservation(card.dataset.reservationId);
});
function copyControlNumber(){if(!dashboardControlNumber)return;navigator.clipboard.writeText(dashboardControlNumber).then(()=>{let e=document.getElementById('copyBtnText'),o=e.innerText;e.innerText='Copied!';setTimeout(()=>e.innerText=o,1500);});}

let liveDashboardSignature = '';
let liveDashboardRequestPending = false;
async function syncDashboard() {
    if (document.hidden || liveDashboardRequestPending) return;
    liveDashboardRequestPending = true;
    try {
        const response = await fetch('{{ route('user.dashboard.live') }}', {
            headers: {'Accept': 'application/json'},
            cache: 'no-store'
        });
        if (!response.ok) throw new Error('Dashboard refresh failed: ' + response.status);
        const data = await response.json();
        const freshReservations = data.reservations || [];
        const signature = JSON.stringify(freshReservations);
        if (signature === liveDashboardSignature) return;
        liveDashboardSignature = signature;

        const priorIds = new Set(reservations.map(item => Number(item.id)));
        const hasNewBooking = freshReservations.some(item => !priorIds.has(Number(item.id)));
        reservations = freshReservations;
        renderBookingHistory();
        const count = document.getElementById('dashboardBookingCount');
        if (count) count.textContent = reservations.length + ' Booking' + (reservations.length === 1 ? '' : 's');
        if (!reservations.length) return;

        const selectedStillExists = reservations.some(item => Number(item.id) === selectedDashboardReservationId);
        const selectedId = hasNewBooking || !selectedStillExists
            ? Number(reservations[0].id)
            : selectedDashboardReservationId;
        selectReservation(selectedId);
    } catch (error) {
        console.error('Unable to refresh the live dashboard reservation status.', error);
    } finally {
        liveDashboardRequestPending = false;
    }
}
syncDashboard();
setInterval(syncDashboard, 5000);
</script>
</body>
</html>
