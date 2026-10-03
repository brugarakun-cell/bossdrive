<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BossDrive - Staff Reservation Management</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #dc3545; --boss-dark: #212529; --boss-grey: #f8f9fa; }
        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }

        .sidebar { width: 250px; height: 100vh; background-color: #212529; position: fixed; border-right: 5px solid #dc3545; z-index: 1000; }
        .sidebar .nav-link { color: white; padding: 15px 20px; margin: 5px 15px; border-radius: 8px; font-size: 0.9rem; transition: 0.3s; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { background-color: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); }

        .main-content { margin-left: 250px; min-height: 100vh; }
        .top-nav { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }

        .stat-card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.25s ease; background: white; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 18px rgba(0,0,0,0.1); }
        .stat-card.active-filter { box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.35), 0 8px 18px rgba(0,0,0,0.1); }
        .reservation-stat-row { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:1rem; margin-left:0; margin-right:0; padding:0 2px 6px; }
        .reservation-stat-row > .col-md-3 { width:auto; min-width:0; padding:0; }
        .reservation-stat-row .stat-card { min-height:100px; padding:.55rem .5rem !important; }
        .reservation-stat-row small { line-height:1.2; white-space:nowrap; font-size:clamp(.55rem, .9vw, .72rem); }
        .reservation-stat-row h2 { font-size:1.55rem; }
        @media (max-width: 992px) {
            .reservation-stat-row { display:flex; gap:1rem; overflow-x:auto; }
            .reservation-stat-row > .col-md-3 { flex:0 0 155px; }
        }

        .res-card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); background: white; padding: 25px; }

        .pay-tag { font-size: 0.7rem; font-weight: 800; padding: 4px 12px; border-radius: 50px; text-transform: uppercase; border: 1px solid; display: inline-block; }
        .pay-full { color: #198754; border-color: #198754; background: #eefdf5; }
        .pay-not-full { color: #dc3545; border-color: #dc3545; background: #fff5f5; }
        .pay-deposit { color: #996c00; border-color: #f0ad00; background: #fff8df; }
        .pay-walkin { color: #dc3545; border-color: #dc3545; background: #fff5f5; }
        #reservationTable th,
        #reservationTable td { white-space: nowrap; }
        #reservationTable td:nth-child(4) { white-space: normal; min-width: 95px; }
        #reservationTable td:nth-child(5) { width: 125px; }
        #reservationTable .pay-tag { max-width: 118px; overflow: hidden; text-overflow: ellipsis; }
        #verifyModal .modal-dialog { width: calc(100vw - 2rem); max-width: 900px; margin: 1rem auto; }
        #verifyModal .modal-content { max-width: 100%; overflow: hidden; }
        #verifyModal .modal-body { overflow-x: hidden; padding: 1.25rem !important; }
        #verifyModal .row > [class*="col-"] { min-width: 0; }
        .doc-preview { display: block; width: 100% !important; max-width: 100%; height: 110px !important; object-fit: cover; border-radius: 8px; border: 1px solid #eee; transition: 0.3s; cursor: zoom-in; }
        #mPaymentProof { height: 120px !important; max-width: 100%; object-fit: contain; }
        .status-pill { font-size: 0.7rem; font-weight: 800; padding: 4px 12px; border-radius: 50px; text-transform: uppercase; display: inline-block; }
        .status-pill.status-completed { background: #eefdf5; color: #198754; border: 1px solid #198754; }

        .staff-alert-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; pointer-events: none; }
        .staff-alert { min-width: 320px; max-width: 420px; padding: 16px 20px; border-radius: 0 0 14px 14px; background: #dc3545; color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,.2); font-weight: 700; display: flex; align-items: flex-start; gap: 10px; opacity: 0; transform: scale(.92); transition: .2s ease; }
        .staff-alert.show { opacity: 1; transform: scale(1); }
        @media (max-width: 767.98px) {
            #verifyModal .modal-dialog { width: calc(100vw - 1rem); margin: .5rem auto; }
            #verifyModal .modal-body { padding: 1rem !important; }
            #verifyModal .border-end { border-right: 0 !important; border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1rem; }
            .res-card { padding:14px; }
            .res-card > .d-flex { align-items:stretch !important; flex-direction:column; gap:10px; }
            .res-card > .d-flex > .input-group { max-width:none !important; }
            .reservation-stat-row { gap:.65rem; }
            .reservation-stat-row > .col-md-3 { flex:0 0 135px; width:135px; }
            .reservation-stat-row .stat-card { min-height:82px; padding:.7rem .55rem !important; }
            .reservation-stat-row h2 { font-size:1.25rem; }
            .reservation-stat-row small { font-size:.62rem; white-space:normal; }
            .filter-active-banner { gap:8px; align-items:flex-start !important; }
            .filter-active-banner button { flex:0 0 auto; }
        }

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
        .info-label { font-size: 0.7rem; color: #aaa; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-weight: 700; color: #333; display: block; margin-bottom: 7px; }
        .admin-check-box { border-radius: 9px; padding: 10px 12px; display: flex; align-items: center; margin-top: 10px; }
        .admin-check-box i { font-size: 1.25rem !important; }
        .admin-check-box b { font-size: .78rem; }
        .admin-check-box small { font-size: .72rem; }
        .filter-active-banner { font-size: 0.8rem; font-weight: 700; }
        #reservationTable { min-width: 900px; }
        .reservation-action { font-size: .68rem; padding: 4px 9px !important; }
        .reservation-stat-row { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:1rem; overflow:visible; }
        .reservation-stat-row > .col-md-3 { flex:none; width:auto; max-width:none; min-width:0; }
        @media (max-width: 992px) {
            .reservation-stat-row { display:flex; gap:1rem; overflow-x:auto; }
            .reservation-stat-row > .col-md-3 { flex:0 0 155px; width:155px; }
        }

        .driver-assign-box { border-top: 2px dashed #eee; padding-top: 20px; margin-top: 20px; }
        .driver-list-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .driver-option-card { border: 2px solid #eee; border-radius: 12px; padding: 8px 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; transition: 0.2s; }
        .driver-option-card:hover { border-color: #f5c2c7; background: #fff8f8; }
        .driver-option-card.selected { border-color: #dc3545; background: #fff5f5; box-shadow: 0 0 0 2px rgba(220,53,69,0.15); }
        .driver-avatar-sm { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; margin-right: 8px; flex-shrink: 0; }
        .driver-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .dot-available { background: #198754; }
        .dot-busy { background: #dc3545; }

        .driver-name-tag {
            display: inline-flex;
            align-items: flex-start;
            gap: 6px;
            max-width: 170px;
            color: #fff;
            background: #212529;
            padding: 6px 10px;
            border-radius: 10px;
            margin-top: 6px;
            white-space: normal;
            line-height: 1.25;
        }
        .driver-name-tag i { font-size: 0.7rem; margin-top: 2px; color: #ff8080; flex-shrink: 0; }
        .driver-name-tag .dnt-name { font-size: 0.72rem; font-weight: 800; display: block; word-break: break-word; }
        .driver-name-tag .dnt-date { font-size: 0.65rem; font-weight: 500; color: rgba(255,255,255,0.7); display: block; margin-top: 1px; }
        .add-driver-inline { background: #f8f9fa; border: 1px dashed #ccc; border-radius: 12px; padding: 14px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="staff-alert-container" id="staffAlertContainer"></div>
    @include('staff.partials.navigation', ['staffPageTitle' => 'Reservations', 'staffPageAccent' => 'Logs'])

    <div class="main-content">

        <div class="p-4">
            <div class="row g-4 mb-4 text-center reservation-stat-row">
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-primary border-5" data-filter="all">
                        <small class="text-muted fw-bold d-block text-uppercase">Total Bookings</small>
                        <h2 class="fw-bold mb-0 mt-2" id="statTotal">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-warning border-5" data-filter="pending">
                        <small class="text-muted fw-bold d-block text-uppercase">Pending</small>
                        <h2 class="fw-bold mb-0 text-warning mt-2" id="statPending">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-success border-5" data-filter="verified">
                        <small class="text-muted fw-bold d-block text-uppercase">Verified</small>
                        <h2 class="fw-bold mb-0 text-success mt-2" id="statVerified">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-info border-5" data-filter="processing">
                        <small class="text-muted fw-bold d-block text-uppercase">Processing</small>
                        <h2 class="fw-bold mb-0 text-info mt-2" id="statProcessing">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-primary border-5" data-filter="released">
                        <small class="text-muted fw-bold d-block text-uppercase">Released</small>
                        <h2 class="fw-bold mb-0 text-primary mt-2" id="statReleased">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-success border-5" data-filter="returned">
                        <small class="text-muted fw-bold d-block text-uppercase">Returned</small>
                        <h2 class="fw-bold mb-0 text-success mt-2" id="statReturned">0</h2>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 px-1">
                <h6 class="fw-bold mb-0 text-uppercase text-muted"><i class="fas fa-list-alt text-danger me-2"></i>Reservations Logs</h6>
                <div class="btn-group" role="tablist" aria-label="Reservation booking source">
                    <a href="{{ route('staff.reservations') }}" class="btn btn-sm {{ $walkInOnly ? 'btn-outline-danger' : 'btn-danger' }} fw-bold" role="tab" aria-selected="{{ $walkInOnly ? 'false' : 'true' }}">
                        <i class="fas fa-globe me-1"></i>Online Bookings
                    </a>
                    <a href="{{ route('staff.walk-in-bookings', ['walkin' => 1]) }}" class="btn btn-sm {{ $walkInOnly ? 'btn-danger' : 'btn-outline-danger' }} fw-bold" role="tab" aria-selected="{{ $walkInOnly ? 'true' : 'false' }}">
                        <i class="fas fa-walking me-1"></i>Walk-in Bookings
                    </a>
                </div>
            </div>

            <div class="res-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold mb-1 text-uppercase small text-muted"><i class="fas fa-list-alt text-danger me-2"></i>{{ $walkInOnly ? 'Walk-in Booking Logs' : 'Online Booking Logs' }}</h6>
                    </div>
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-danger"></i></span>
                        <input type="text" id="controlSearchInput" class="form-control" placeholder="Search Control No. or Customer..." onkeyup="filterReservations()">
                    </div>
                </div>

                <div id="filterBanner" class="alert alert-danger py-2 px-3 filter-active-banner d-none d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-filter me-2"></i>Showing filtered view: <span id="filterLabel"></span></span>
                    <button class="btn btn-sm btn-outline-danger fw-bold" onclick="clearReservationFilter()">Clear Filter</button>
                </div>

                <div id="noResultMsg" class="alert alert-warning small fw-bold text-center d-none">
                    <i class="fas fa-exclamation-circle me-1"></i> No booking found matching your criteria.
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-hover" id="reservationTable">
                        <thead class="bg-light small text-muted text-uppercase fw-bold">
                            <tr>
                                <th class="py-3">Customer<br>Contact</th>
                                <th>Control<br>Number</th>
                                <th>Service<br>Type</th>
                                <th>Logistics</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reservations ?? [] as $reservation)
                                @php
                                    $customerName = trim((string) ($reservation->customer_name ?: ($reservation->user?->name ?? 'Walk-in Customer')));
                                    $customerPhone = trim((string) ($reservation->customer_phone ?: ($reservation->user?->contact_number ?? 'No contact')));
                                    $serviceText = $reservation->driver_option === 'with_driver' ? 'With Driver' : 'Self-Drive';
                                    $logisticsText = $reservation->service_option === 'delivery' ? 'Delivery' : 'Pick-up';
                                    $logisticsTarget = $reservation->service_option === 'delivery' ? ($reservation->delivery_address ?: 'Main Office') : 'Main Office';
                                    $isPaid = $reservation->payment_status === 'approved'
                                        || ((float) $reservation->paid_amount > 0 && (float) $reservation->paid_amount >= (float) $reservation->total_amount);
                                    $paymentTag = $reservation->payment_mode === 'walkin'
                                        ? 'Walk-in'
                                        : ($reservation->payment_mode === 'deposit'
                                            ? 'Deposit Only'
                                            : ($isPaid ? 'Fully Paid' : 'Payment Pending'));
                                    $paymentClass = $reservation->payment_mode === 'walkin'
                                        ? 'pay-walkin'
                                        : ($reservation->payment_mode === 'deposit'
                                            ? 'pay-deposit'
                                            : ($isPaid ? 'pay-full' : 'pay-not-full'));
                                    $balance = max((float) $reservation->total_amount - (float) $reservation->paid_amount, 0);
                                    if ($reservation->status === 'verified') {
                                        $statusClass = 'bg-success text-white';
                                    } elseif ($reservation->status === 'pending') {
                                        $statusClass = 'bg-warning text-dark';
                                    } elseif ($reservation->status === 'processing') {
                                        $statusClass = 'bg-info text-white';
                                    } elseif ($reservation->status === 'released') {
                                        $statusClass = 'bg-primary text-white';
                                    } elseif ($reservation->status === 'completed') {
                                        $statusClass = 'bg-success text-white';
                                    } elseif ($reservation->status === 'cancelled') {
                                        $statusClass = 'bg-secondary text-white';
                                    } else {
                                        $statusClass = 'bg-light text-muted border';
                                    }
                                @endphp
                                <tr class="res-row" data-pay="{{ $isPaid ? 'full' : 'not-full' }}" data-walkin="{{ $reservation->payment_mode === 'walkin' ? 'true' : 'false' }}" data-status="{{ strtolower($reservation->status) }}" data-search="{{ strtolower($customerName . ' ' . $customerPhone . ' ' . $reservation->control_number) }}">
                                    <td>
                                        <span class="fw-bold text-dark">{{ $customerName }}</span><br>
                                        <small class="text-danger fw-bold">{{ $customerPhone }}</small>
                                    </td>
                                    <td><span class="control-tag">{{ $reservation->control_number }}</span></td>
                                    <td><span class="badge bg-dark rounded-pill px-3">{{ $serviceText }}</span></td>
                                    <td id="logi-{{ $reservation->id }}">
                                        <span class="badge {{ $reservation->service_option === 'delivery' ? 'bg-primary' : 'bg-secondary' }} rounded-pill px-3">{{ $logisticsText }}</span>
                                    </td>
                                    <td>
                                        <span class="pay-tag {{ $paymentClass }}">{{ strtoupper($paymentTag) }}</span><br>
                                    </td>
                                    <td><span id="status-{{ $reservation->id }}" class="status-pill {{ $statusClass }} text-uppercase">{{ ucfirst($reservation->status) }}</span></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-sm btn-outline-danger fw-bold rounded-pill reservation-action" data-reservation-action="review" data-reservation-id="{{ $reservation->id }}">Review</button>
                                            <button
                                                id="btn-{{ $reservation->id }}"
                                                class="btn btn-sm {{ $reservation->status === 'completed' ? 'btn-outline-success' : 'btn-dark' }} fw-bold rounded-pill reservation-action"
                                                data-reservation-action="{{ $reservation->status === 'completed' ? 'completed-inspection' : 'status' }}"
                                                data-reservation-id="{{ $reservation->id }}"
                                                style="display: {{ in_array($reservation->status, ['verified', 'processing', 'released', 'completed'], true) ? 'inline-block' : 'none' }};"
                                            >{{ $reservation->status === 'verified' ? 'Processing' : ($reservation->status === 'processing' ? 'Released' : ($reservation->status === 'released' ? 'Returned' : 'Completed')) }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-calendar-times me-2"></i>No reservations found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-3 border-0 shadow">
                <div class="modal-header bg-dark text-white p-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-uppercase fs-6"><span id="mReviewType">Review Details</span>: <span id="mHeader" class="text-danger"></span></h5>
                        <small class="text-white-50">Control No: <span id="mControlNumber" class="fw-bold text-white"></span></small>
                    </div>
                    <input type="hidden" id="currentResId">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold text-danger mb-3 text-uppercase small"><i class="fas fa-info-circle me-2"></i>Booking Summary</h6>
                            <label class="info-label">Customer Name</label><span class="info-value" id="mName"></span>
                            <label class="info-label">Contact</label><span class="info-value" id="mPhone"></span>
                            <label class="info-label">Email</label><span class="info-value" id="mEmail"></span>
                            <label class="info-label">Service / Logistics</label><span class="info-value"><span id="mService"></span> | <span id="mLogistics"></span></span>
                            <label class="info-label">Destination/Address</label><span class="info-value text-primary" id="mTarget"></span>
                            <label class="info-label">Delivery Notes</label><span class="info-value" id="mDeliveryNotes"></span>
                            <label class="info-label">Assigned Vehicle Unit</label><span class="info-value" id="mVehicleUnit"></span>
                            <label class="info-label">Vehicle Details</label><span class="info-value" id="mVehicleSpecs"></span>
                            <label class="info-label">Pickup Schedule</label><span class="info-value" id="mPickupSchedule"></span>
                            <label class="info-label">Return Schedule</label><span class="info-value" id="mReturnSchedule"></span>
                            <div id="adminClearanceStatus" class="admin-check-box"></div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold text-danger mb-3 text-uppercase small"><i class="fas fa-wallet me-2"></i>Payment Verification</h6>
                            <label class="info-label">Initial Mode</label><span class="info-value" id="mPayMethod"></span>
                            <label class="info-label">Amount Already Paid</label><span class="info-value text-success" id="mPaid"></span>
                            <label class="info-label">Remaining Balance</label><span class="info-value text-danger" id="mBalance"></span>
                            <label class="info-label">Total Rental Payment</label><span class="info-value fw-bold" id="mTotal"></span>
                            <label class="info-label">Transaction Ref</label><span class="info-value" id="mRef"></span>

                        </div>
                    </div>

                    <div id="driverInfoBox" class="driver-assign-box d-none">
                        <h6 class="fw-bold text-danger mb-2 text-uppercase small"><i class="fas fa-id-badge me-2"></i>Assigned Driver</h6>
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="fw-bold" id="reviewDriverName"></div>
                            <div class="small text-muted" id="reviewDriverContact"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
                <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 70px; height: 70px; background-color: #fff5f5;">
                    <i class="fas fa-question text-danger fa-2x"></i>
                </div>
                <h5 class="fw-bold mb-3" id="customConfirmText">Confirm and log this action?</h5>
                <div class="d-flex justify-content-center gap-2 mt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border" data-bs-dismiss="modal" style="min-width: 110px;">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" id="customConfirmActionBtn" style="min-width: 110px; background-color: #dc3545;">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="returnConditionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow"><div class="modal-body p-4">
            <h5 class="fw-bold mb-2"><i class="fas fa-clipboard-check text-danger me-2"></i>Vehicle Return Condition Check</h5>
            <p class="small text-muted">Compare this with the user's Vehicle Pickup Condition Check before marking Returned.</p>
            <div id="userPickupConditionSummary" class="border rounded-3 bg-light p-3 mb-3 small"></div>
            <div id="returnConditionSummary" class="border rounded-3 bg-light p-3 mb-3 small d-none"></div>
            <form id="returnConditionForm" data-no-page-loader>
                <div class="condition-box" id="returnConditionChecklist">
                    <label class="condition-item d-block"><input type="checkbox" name="return_condition_checks[]" value="Fuel level checked" required> Fuel level checked</label>
                    <label class="condition-item d-block"><input type="checkbox" name="return_condition_checks[]" value="Exterior/body inspected" required> Exterior/body inspected</label>
                    <label class="condition-item d-block"><input type="checkbox" name="return_condition_checks[]" value="Interior checked and clean" required> Interior checked and clean</label>
                    <label class="condition-item d-block"><input type="checkbox" name="return_condition_checks[]" value="Accessories/tools verified" required> Accessories/tools verified</label>
                    <label class="condition-item d-block"><input type="checkbox" name="return_condition_checks[]" value="I confirm the vehicle condition" required> I confirm the vehicle condition</label>
                </div>
                <textarea name="return_condition_notes" class="form-control mt-3" rows="2" placeholder="New damage or comparison notes (optional)" id="returnConditionNotes"></textarea>
                <button class="btn btn-dark w-100 rounded-pill fw-bold mt-3" type="submit" id="returnConditionSubmit">SUBMIT CHECK & RETURN</button>
            </form>
        </div></div></div>
    </div>

    <script>
        let verificationModal;
        let customConfirmModal;
        let currentReservationFilter = 'all';
        let pendingActionCallback = null;
        let pendingReturnReservationId = null;

        let driverPool = [
            { id: 'DRV-001', name: 'Mark Santos', contact: '09171234567', status: 'available' },
            { id: 'DRV-002', name: 'Julius Reyes', contact: '09281234567', status: 'available' },
            { id: 'DRV-003', name: 'Ramon Cruz', contact: '09391234567', status: 'available' },
            { id: 'DRV-004', name: 'Andres Villanueva', contact: '09451234567', status: 'available' }
        ];

        let bookingDriverAssignments = {};
        let selectedDriverId = null;
        let selectedScheduleDate = null;

        document.addEventListener("DOMContentLoaded", () => {
            customConfirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
            updateStatCounts();
            filterReservations();
        });

        @php
            $reservationRows = $reservations->map(function ($reservation) {
                $isWalkIn = !$reservation->user_id;
                $documentsVerified = $isWalkIn || (in_array($reservation->status, ['verified', 'processing', 'released', 'completed'], true)
                    && $reservation->user?->documents_verified_at
                    && (!$reservation->user?->documents_rejected_at
                        || $reservation->user->documents_rejected_at <= $reservation->user->documents_verified_at));
                return [
                    'id' => $reservation->id,
                    'status' => strtolower((string) $reservation->status),
                    'control_number' => $reservation->control_number,
                    'vehicle' => $reservation->vehicle,
                    'vehiclePlate' => $reservation->vehicleUnit?->plate,
                    'vehicleCategory' => $reservation->vehicleUnit?->category,
                    'vehicleTransmission' => $reservation->vehicleUnit?->transmission,
                    'vehicleFuel' => $reservation->vehicleUnit?->fuel,
                    'vehicleCapacity' => $reservation->vehicleUnit?->capacity_type ?: (($reservation->vehicleUnit?->capacity ?? null) ? $reservation->vehicleUnit->capacity.' Seater' : null),
                    'name' => trim((string) ($reservation->customer_name ?: ($reservation->user?->name ?? 'Walk-in Customer'))),
                    'phone' => trim((string) ($reservation->customer_phone ?: ($reservation->user?->contact_number ?? 'No contact'))),
                    'age' => $reservation->customer_age ?? $reservation->user?->age ?? 'N/A',
                    'email' => $reservation->customer_email ?: ($reservation->user?->email ?? 'No email'),
                    'assignedDriverName' => $reservation->assigned_driver_name,
                    'assignedDriverContact' => $reservation->assigned_driver_contact,
                    'home' => $reservation->delivery_address ?: 'Main Office',
                    'service' => $reservation->driver_option === 'with_driver' ? 'With Driver' : 'Self-Drive',
                    'logistics' => ($reservation->service_option === 'delivery' ? 'Delivery' : 'Pick-up').' / '.match ($reservation->rate_type) {
                        'province' => 'Province',
                        'long_distance' => 'Long Distance (2 Days)',
                        default => 'City Driving',
                    },
                    'target' => $reservation->service_option === 'delivery' ? ($reservation->delivery_address ?: 'Main Office') : 'Main Office',
                    'deliveryNotes' => $reservation->delivery_notes,
                    'pickupDate' => $reservation->pickup_date?->format('M d, Y'),
                    'pickupTime' => $reservation->pickup_time ? substr($reservation->pickup_time, 0, 5) : null,
                    'returnDate' => $reservation->return_date?->format('M d, Y'),
                    'returnTime' => $reservation->return_time ? substr($reservation->return_time, 0, 5) : null,
                    'payMethod' => ucfirst((string) $reservation->payment_mode),
                    'paid' => '₱'.number_format((float) $reservation->paid_amount, 2),
                    'balance' => '₱'.number_format(max((float) $reservation->total_amount - (float) $reservation->paid_amount, 0), 2),
                    'total' => '₱'.number_format((float) $reservation->total_amount, 2),
                    'ref' => $reservation->payment_reference_id ?: 'No reference',
                    'documentsVerified' => (bool) $documentsVerified,
                    'pickupConditionReport' => $reservation->pickupConditionReports
                        ->sortByDesc('created_at')
                        ->map(fn ($report) => [
                            'checks' => $report->checks ?? [],
                            'notes' => $report->notes,
                            'photo' => $report->photo_path ? \Illuminate\Support\Facades\Storage::url($report->photo_path) : null,
                            'date' => $report->created_at?->format('M j, Y g:i A'),
                        ])->first(),
                    'returnCondition' => [
                        'checks' => $reservation->return_condition_checks ?? [],
                        'notes' => $reservation->return_condition_notes,
                        'date' => $reservation->return_condition_checked_at?->format('M j, Y g:i A'),
                        'name' => $reservation->return_condition_checked_by,
                        'role' => $reservation->return_condition_checked_by_role,
                    ],
                    'isWalkIn' => $isWalkIn,
                    'controlNumber' => $reservation->control_number,
                ];
            })->all();
        @endphp
        let reservationRows = @json($reservationRows);
        const staffWalkInOnly = @json($walkInOnly);
        function setStaffMutationLoader(visible) {
            const loader = document.getElementById('staffPageLoader');
            loader?.classList.toggle('is-visible', visible);
            loader?.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (character) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[character];
            });
        }

        function staffStatusClass(status) {
            return ({
                pending: 'bg-warning text-dark',
                verified: 'bg-success text-white',
                processing: 'bg-info text-white',
                released: 'bg-primary text-white',
                completed: 'status-completed',
                cancelled: 'bg-secondary text-white',
                void: 'bg-light text-muted border'
            })[status] || 'bg-light text-muted border';
        }

        function renderStaffReservations() {
            const tbody = document.querySelector('#reservationTable tbody');
            if (!tbody) return;
            if (!reservationRows.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-calendar-times me-2"></i>No reservations found.</td></tr>';
                return;
            }

            tbody.innerHTML = reservationRows.map(function (reservation) {
                const status = String(reservation.status || '').toLowerCase();
                const payment = String(reservation.payment || '');
                const paymentKey = payment.toLowerCase();
                const paid = reservation.paymentStatus === 'approved'
                    || (Number(reservation.paidAmount) > 0 && Number(reservation.paidAmount) >= Number(reservation.totalAmount));
                const paymentLabel = paymentKey === 'walkin'
                    ? 'WALK-IN'
                    : (paymentKey === 'deposit' ? 'DEPOSIT ONLY' : (paid ? 'FULLY PAID' : 'PAYMENT PENDING'));
                const paymentClass = paymentKey === 'walkin'
                    ? 'pay-walkin'
                    : (paymentKey === 'deposit' ? 'pay-deposit' : (paid ? 'pay-full' : 'pay-not-full'));
                const actionLabels = {verified: 'Processing', processing: 'Released', released: 'Returned', completed: 'Completed'};
                const actionLabel = actionLabels[status] || '';
                const actionButton = actionLabel
                    ? '<button id="btn-' + Number(reservation.id) + '" class="btn btn-sm ' + (status === 'completed' ? 'btn-outline-success' : 'btn-dark') + ' fw-bold rounded-pill reservation-action" data-reservation-action="' + (status === 'completed' ? 'completed-inspection' : 'status') + '" data-reservation-id="' + Number(reservation.id) + '">' + actionLabel + '</button>'
                    : '';
                const searchData = (reservation.name + ' ' + reservation.phone + ' ' + reservation.control).toLowerCase();

                return '<tr class="res-row" data-pay="' + (paid ? 'full' : 'not-full') + '" data-walkin="' + (paymentKey === 'walkin' ? 'true' : 'false') + '" data-status="' + escapeHtml(status) + '" data-search="' + escapeHtml(searchData) + '">' +
                    '<td><span class="fw-bold text-dark">' + escapeHtml(reservation.name) + '</span><br><small class="text-danger fw-bold">' + escapeHtml(reservation.phone) + '</small></td>' +
                    '<td><span class="control-tag">' + escapeHtml(reservation.control) + '</span></td>' +
                    '<td><span class="badge bg-dark rounded-pill px-3">' + escapeHtml(reservation.driver) + '</span></td>' +
                    '<td id="logi-' + Number(reservation.id) + '"><span class="badge ' + (reservation.service === 'Delivery' ? 'bg-primary' : 'bg-secondary') + ' rounded-pill px-3">' + escapeHtml(reservation.service) + '</span></td>' +
                    '<td><span class="pay-tag ' + paymentClass + '">' + paymentLabel + '</span></td>' +
                    '<td><span id="status-' + Number(reservation.id) + '" class="status-pill ' + staffStatusClass(status) + ' text-uppercase">' + escapeHtml(status === 'completed' ? 'Completed' : status.charAt(0).toUpperCase() + status.slice(1)) + '</span>' + (reservation.documentsNeedReview ? '<small class="d-block text-warning fw-bold">Updated ID needs review</small>' : '') + '</td>' +
                    '<td class="text-center"><div class="d-flex justify-content-center gap-2"><button class="btn btn-sm btn-outline-danger fw-bold rounded-pill reservation-action" data-reservation-action="review" data-reservation-id="' + Number(reservation.id) + '">Review</button>' + actionButton + '</div></td>' +
                    '</tr>';
            }).join('');
        }

        function liveReservationToStaffRow(reservation) {
            const rate = reservation.rate === 'Long Distance (2 Days)' ? 'Long Distance (2 Days)' : reservation.rate;
            const logistics = (reservation.service || 'Pick-up') + ' / ' + rate;
            return {
                id: reservation.id,
                status: reservation.status,
                control_number: reservation.control,
                vehicle: reservation.vehicle,
                vehiclePlate: reservation.vehiclePlate,
                vehicleCategory: reservation.vehicleCategory,
                vehicleTransmission: reservation.vehicleTransmission,
                vehicleFuel: reservation.vehicleFuel,
                vehicleCapacity: reservation.vehicleCapacity,
                name: reservation.name,
                phone: reservation.phone,
                age: reservation.age || 'N/A',
                email: reservation.email || 'No email',
                assignedDriverName: reservation.assignedDriverName,
                assignedDriverContact: reservation.assignedDriverContact,
                home: reservation.address,
                service: reservation.driver,
                logistics: logistics,
                target: reservation.target,
                deliveryNotes: reservation.deliveryNotes,
                pickupDate: reservation.pickupDate ? new Date(reservation.pickupDateIso + 'T00:00:00').toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric'}) : null,
                pickupTime: reservation.pickupTime,
                returnDate: reservation.returnDate ? new Date(reservation.returnDateIso + 'T00:00:00').toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric'}) : null,
                returnTime: reservation.returnTime,
                payMethod: reservation.payMethod,
                paid: '₱' + Number(reservation.paidAmount).toLocaleString('en-PH', {minimumFractionDigits: 2}),
                balance: '₱' + Number(reservation.balance).toLocaleString('en-PH', {minimumFractionDigits: 2}),
                total: '₱' + Number(reservation.totalAmount).toLocaleString('en-PH', {minimumFractionDigits: 2}),
                ref: reservation.reference,
                documentsVerified: reservation.documentsVerified,
                documentsNeedReview: reservation.documentsNeedReview,
                documents: reservation.documents,
                pickupConditionReport: reservation.pickupConditionReport,
                returnCondition: reservation.returnCondition,
                isWalkIn: reservation.isWalkIn,
                controlNumber: reservation.control
            };
        }

        let staffReservationSignature = '';
        let staffReservationRequestPending = false;
        async function syncStaffReservations() {
            if (document.hidden || document.querySelector('.modal.show') || staffReservationRequestPending) return;
            staffReservationRequestPending = true;
            const liveUrl = '{{ route('staff.reservations.live') }}' + (staffWalkInOnly ? '?walkin=1' : '');
            try {
                const response = await fetch(liveUrl, {
                    headers: {'Accept': 'application/json'},
                    cache: 'no-store'
                });
                if (!response.ok) throw new Error('Staff reservations refresh failed: ' + response.status);
                const data = await response.json();
                const signature = JSON.stringify(data);
                if (signature === staffReservationSignature) return;
                staffReservationSignature = signature;
                reservationRows = data.map(liveReservationToStaffRow);
                reservationRows.forEach(function (reservation) {
                    if (reservation.assignedDriverName && !bookingDriverAssignments[reservation.id]) {
                        bookingDriverAssignments[reservation.id] = {
                            driverId: null,
                            driverName: reservation.assignedDriverName,
                            driverContact: reservation.assignedDriverContact || ''
                        };
                    }
                });
                renderStaffReservations();
                updateStatCounts();
                filterReservations();
            } catch (error) {
                console.error('Unable to refresh staff reservations.', error);
            } finally {
                staffReservationRequestPending = false;
            }
        }

        reservationRows.forEach(function (reservation) {
            if (reservation.assignedDriverName) {
                bookingDriverAssignments[reservation.id] = {
                    driverId: null,
                    driverName: reservation.assignedDriverName,
                    driverContact: reservation.assignedDriverContact || ''
                };
            }
        });

        function viewDetailsById(id) {
            const reservation = reservationRows.find(item => String(item.id) === String(id));
            if (!reservation) return;
            viewDetails(
                reservation.id, reservation.name, reservation.phone, reservation.age,
                reservation.home, reservation.service, reservation.logistics, reservation.target,
                reservation.payMethod, reservation.paid, reservation.balance, reservation.total, reservation.ref, reservation.documentsVerified,
                reservation.controlNumber, reservation.isWalkIn, reservation.email,
                reservation.assignedDriverName, reservation.assignedDriverContact
            );
            document.getElementById('mDeliveryNotes').innerText = reservation.deliveryNotes || 'None';
            document.getElementById('mPickupSchedule').innerText = [reservation.pickupDate, reservation.pickupTime].filter(Boolean).join(' at ') || 'Not provided';
            document.getElementById('mReturnSchedule').innerText = [reservation.returnDate, reservation.returnTime].filter(Boolean).join(' at ') || 'Not provided';
            document.getElementById('mVehicleUnit').innerText = [reservation.vehicle, reservation.vehiclePlate ? 'Plate: ' + reservation.vehiclePlate : null].filter(Boolean).join(' — ') || 'Not provided';
            document.getElementById('mVehicleSpecs').innerText = [reservation.vehicleCategory, reservation.vehicleTransmission, reservation.vehicleFuel, reservation.vehicleCapacity].filter(Boolean).join(' · ') || 'Not provided';
        }

        function showCustomConfirm(message, callback) {
            document.getElementById('customConfirmText').innerText = message;
            pendingActionCallback = callback;
            customConfirmModal.show();
        }

        function showStaffAlert(message) {
            const container = document.getElementById('staffAlertContainer');
            const alert = document.createElement('div');
            alert.className = 'staff-alert';
            alert.innerHTML = '<i class="fas fa-exclamation-circle mt-1"></i><span>' + message + '</span>';
            container.appendChild(alert);
            requestAnimationFrame(() => alert.classList.add('show'));
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 220);
            }, 3200);
        }

        document.getElementById('customConfirmActionBtn').addEventListener('click', () => {
            if (pendingActionCallback) {
                pendingActionCallback();
            }
            customConfirmModal.hide();
        });

        function viewDetails(id, name, phone, age, home, service, logistics, target, payMethod, paid, balance, total, ref, isAdminVerified, controlNumber, isWalkIn, email, assignedDriverName, assignedDriverContact) {
            document.getElementById('currentResId').value = id;
            document.getElementById('mHeader').innerText = name;
            document.getElementById('mControlNumber').innerText = controlNumber;
            document.getElementById('mName').innerText = name + " (" + age + " yrs old)";
            document.getElementById('mPhone').innerText = phone;
            document.getElementById('mEmail').innerText = email;
            document.getElementById('mService').innerText = service;
            document.getElementById('mLogistics').innerText = logistics;
            document.getElementById('mTarget').innerText = target;
            document.getElementById('mPayMethod').innerText = payMethod;
            document.getElementById('mPaid').innerText = paid;
            document.getElementById('mBalance').innerText = balance;
            document.getElementById('mTotal').innerText = total;
            document.getElementById('mRef').innerText = ref;
            document.getElementById('mReviewType').innerText = isWalkIn ? 'Walk-in Booking Review' : 'Review Details';

            const clearanceBox = document.getElementById('adminClearanceStatus');
            if (isWalkIn) {
                clearanceBox.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i><div><b class="text-success d-block">WALK-IN BOOKING READY</b><small class="text-muted">Printed requirements are handled at the counter. Staff may process, release, and return this booking.</small></div>';
                clearanceBox.style.background = '#f0fdf4';
                clearanceBox.style.borderColor = '#bbf7d0';
            } else if (isAdminVerified) {
                clearanceBox.innerHTML = '<i class="fas fa-check-circle text-success me-2"></i><div><b class="text-success d-block">DOCUMENTS VERIFIED BY ADMIN</b><small class="text-muted">Staff may now process, release, and return this reservation.</small></div>';
                clearanceBox.style.background = '#f0fdf4';
                clearanceBox.style.borderColor = '#bbf7d0';
            } else {
                clearanceBox.innerHTML = '<i class="fas fa-clock text-warning me-2"></i><div><b class="text-warning d-block">WAITING FOR ADMIN VERIFICATION</b><small class="text-muted">Processing is unavailable until Admin confirms the customer documents.</small></div>';
                clearanceBox.style.background = '#fffbeb';
                clearanceBox.style.borderColor = '#fde68a';
            }

            const driverBox = document.getElementById('driverInfoBox');
            const isWithDriver = service.trim() === 'With Driver';
            driverBox.classList.toggle('d-none', !isWithDriver);
            if (isWithDriver) {
                document.getElementById('reviewDriverName').innerText = assignedDriverName || 'Not yet assigned by Admin';
                document.getElementById('reviewDriverContact').innerText = assignedDriverContact || 'Contact to be confirmed';
            }

            verificationModal = new bootstrap.Modal(document.getElementById('verifyModal'));
            verificationModal.show();
        }

        function renderDriverList() {
            const wrap = document.getElementById('driverListWrap');
            wrap.innerHTML = '';
            driverPool.forEach(driver => {
                const isAvailable = driver.status === 'available';
                const isSelected = driver.id === selectedDriverId;
                if (!isAvailable && !isSelected) return;
                const card = document.createElement('div');
                card.className = 'driver-option-card' + (isSelected ? ' selected' : '');
                const leftHtml =
                    '<div class="d-flex align-items-center" style="min-width:0; overflow:hidden;">' +
                        '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(driver.name) + '&background=212529&color=fff" class="driver-avatar-sm">' +
                        '<div style="min-width:0; overflow:hidden;">' +
                            '<span class="fw-bold d-block small text-truncate">' + driver.name + '</span>' +
                            '<span class="text-muted text-truncate d-block" style="font-size:0.75rem;">' + driver.contact + '</span>' +
                        '</div>' +
                    '</div>';
                const rightHtml = isSelected
                    ? '<div class="d-flex align-items-center gap-1" onclick="event.stopPropagation();"><input type="date" class="form-control form-control-sm" style="width:118px; font-size:0.7rem; padding:2px 4px;" value="' + (selectedScheduleDate || '') + '" onchange="setScheduleDate(this.value)"></div>'
                    : '<span class="small fw-bold text-nowrap"><span class="driver-status-dot ' + (isAvailable ? 'dot-available' : 'dot-busy') + '"></span>' + (isAvailable ? 'Available' : 'On Call') + '</span>';
                card.innerHTML = leftHtml + rightHtml;
                card.onclick = function() { selectDriver(driver.id); };
                wrap.appendChild(card);
            });
            if (wrap.innerHTML === '') {
                wrap.innerHTML = '<div class="text-muted small fst-italic">No available drivers. Add a new driver above.</div>';
            }
            updateSelectedDriverInfo();
        }

        function selectDriver(driverId) {
            if (selectedDriverId === driverId) {
                selectedDriverId = null;
                selectedScheduleDate = null;
            } else {
                selectedDriverId = driverId;
                selectedScheduleDate = null;
            }
            renderDriverList();
        }

        function setScheduleDate(dateValue) {
            selectedScheduleDate = dateValue;
            updateSelectedDriverInfo();
        }

        function updateSelectedDriverInfo() {
            const infoBox = document.getElementById('driverSelectedInfo');
            const tag = document.getElementById('driverSelectedTag');
            if (selectedDriverId) {
                const driver = driverPool.find(d => d.id === selectedDriverId);
                if (driver) {
                    infoBox.classList.remove('d-none');
                    tag.innerHTML = buildDriverTagHtml(driver.name, selectedScheduleDate ? formatSchedDate(selectedScheduleDate) : 'no date yet');
                    return;
                }
            }
            infoBox.classList.add('d-none');
        }

        function buildDriverTagHtml(name, dateText) {
            return '<i class="fas fa-id-badge"></i>' + '<span><span class="dnt-name">' + name + '</span><span class="dnt-date">' + dateText + '</span></span>';
        }

        function formatSchedDate(dateValue) {
            const d = new Date(dateValue + 'T00:00:00');
            if (isNaN(d)) return dateValue;
            return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function toggleAddDriverForm() {
            document.getElementById('addDriverForm').classList.toggle('d-none');
        }

        function saveNewDriver() {
            const nameInput = document.getElementById('newDriverName');
            const contactInput = document.getElementById('newDriverContact');
            const name = nameInput.value.trim();
            const contact = contactInput.value.trim();

            if (!name || !contact) {
                showCustomConfirm('Please fill out the driver name and contact number.', null);
                return;
            }

            const newId = 'DRV-' + String(driverPool.length + 1).padStart(3, '0');
            driverPool.push({ id: newId, name: name, contact: contact, status: 'available' });
            nameInput.value = '';
            contactInput.value = '';
            document.getElementById('addDriverForm').classList.add('d-none');
            renderDriverList();
        }

        function assignDriverToBooking(bookingId, driverId, scheduleDate) {
            const previous = bookingDriverAssignments[bookingId];
            if (previous && previous.driverId !== driverId) {
                const prevDriver = driverPool.find(d => d.id === previous.driverId);
                if (prevDriver) prevDriver.status = 'available';
            }

            const driver = driverPool.find(d => d.id === driverId);
            if (!driver) return;

            driver.status = 'busy';
            bookingDriverAssignments[bookingId] = { driverId: driverId, scheduleDate: scheduleDate };
            const logiCell = document.getElementById('logi-' + bookingId);
            if (logiCell) {
                let tag = logiCell.querySelector('.driver-name-tag');
                if (!tag) {
                    tag = document.createElement('div');
                    tag.className = 'driver-name-tag mt-1';
                    logiCell.appendChild(tag);
                }
                tag.innerHTML = buildDriverTagHtml(driver.name, formatSchedDate(scheduleDate));
            }
        }

        function promptConfirmReservation() {
            const id = document.getElementById('currentResId').value;
            const driverBoxVisible = !document.getElementById('driverInfoBox').classList.contains('d-none');

            if (driverBoxVisible && !selectedDriverId) {
                showCustomConfirm('Please assign an on-call driver before confirming.', null);
                return;
            }
            if (driverBoxVisible && selectedDriverId && !selectedScheduleDate) {
                showCustomConfirm('Please set the schedule date for the assigned driver.', null);
                return;
            }

            verificationModal.hide();
            showCustomConfirm('Confirm and approve this reservation?', () => {
                const statusLabel = document.getElementById('status-' + id);
                const releaseBtn = document.getElementById('btn-' + id);

                if (driverBoxVisible && selectedDriverId) {
                    assignDriverToBooking(id, selectedDriverId, selectedScheduleDate);
                }

                statusLabel.innerText = 'Processing';
                statusLabel.className = 'status-pill bg-info text-white';
                releaseBtn.style.display = 'inline-block';
                const row = statusLabel.closest('.res-row');
                if (row) row.setAttribute('data-status', 'processing');
                updateStatCounts();

                setStaffMutationLoader(true);
                fetch('{{ url('/staff/reservations') }}/' + id + '/status', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(Object.assign({ status: 'processing' }, bookingDriverAssignments[id] && bookingDriverAssignments[id].driverId ? {
                        assigned_driver_name: (driverPool.find(function (driver) { return driver.id === bookingDriverAssignments[id].driverId; }) || {}).name || null,
                        assigned_driver_contact: (driverPool.find(function (driver) { return driver.id === bookingDriverAssignments[id].driverId; }) || {}).contact || null
                    } : {}))
                }).then(async response => {
                    if (!response.ok) {
                        let message = 'Unable to update reservation status.';
                        try {
                            const payload = await response.json();
                            message = payload.message || message;
                        } catch (error) {
                        }
                        throw new Error(message);
                    }
                }).catch(async error => {
                    showStaffAlert(error.message);
                    await syncStaffReservations();
                }).finally(() => {
                    setStaffMutationLoader(false);
                });
            });
        }

        function promptRentalStatus(id) {
            const btn = document.getElementById('btn-' + id);
            const statusLabel = document.getElementById('status-' + id);
            const currentStatus = statusLabel ? statusLabel.innerText.trim().toLowerCase() : '';
            if (!btn || !statusLabel) {
                showStaffAlert('Completed reservations cannot be processed or changed.');
                return;
            }
            if (currentStatus === 'completed') {
                openCompletedInspection(id);
                return;
            }
            const actionText = currentStatus === 'verified'
                ? 'Confirm that this reservation is ready for Processing?'
                : (currentStatus === 'processing'
                    ? 'Confirm and Release this unit?'
                    : 'Confirm that this unit has been Returned?');
            if (currentStatus === 'released') {
                openReturnConditionModal(id);
                return;
            }
            showCustomConfirm(actionText, () => { handleRentalStatus(id); });
        }

        function openCompletedInspection(id) {
            const reservation = reservationRows.find(item => String(item.id) === String(id));
            if (!reservation) {
                showStaffAlert('Completed inspection details are unavailable.');
                return;
            }

            renderUserPickupCondition(reservation.pickupConditionReport);
            renderReturnConditionReport(reservation.returnCondition);
            document.getElementById('returnConditionChecklist').classList.add('d-none');
            document.getElementById('returnConditionNotes').classList.add('d-none');
            document.getElementById('returnConditionSubmit').classList.add('d-none');
            document.getElementById('returnConditionSummary').classList.remove('d-none');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('returnConditionModal')).show();
        }

        function openReturnConditionModal(id) {
            pendingReturnReservationId = id;
            const report = reservationRows.find(item => String(item.id) === String(id))?.pickupConditionReport;
            renderUserPickupCondition(report);
            document.getElementById('returnConditionSummary').classList.add('d-none');
            document.getElementById('returnConditionChecklist').classList.remove('d-none');
            document.getElementById('returnConditionNotes').classList.remove('d-none');
            document.getElementById('returnConditionSubmit').classList.remove('d-none');
            document.getElementById('returnConditionForm').reset();
            const modalElement = document.getElementById('returnConditionModal');
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }

        document.getElementById('returnConditionForm').addEventListener('submit', function (event) {
            event.preventDefault();
            const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
            payload.return_condition_checks = Array.from(event.currentTarget.querySelectorAll('[name="return_condition_checks[]"]:checked')).map(input => input.value);
            bootstrap.Modal.getInstance(document.getElementById('returnConditionModal')).hide();
            const reservationId = pendingReturnReservationId;
            pendingReturnReservationId = null;
            handleRentalStatus(reservationId, payload);
        });

        function renderUserPickupCondition(report) {
            const summary = document.getElementById('userPickupConditionSummary');
            if (!report) {
                summary.innerHTML = '<strong class="text-muted">No Vehicle Pickup Condition Check submitted by the user.</strong>';
                return;
            }

            summary.innerHTML = '<strong class="d-block mb-2 text-danger">User Vehicle Pickup Condition Check</strong>' +
                '<div class="mb-2">' + (report.checks || []).map(item => '<span class="badge bg-success me-1 mb-1">' + escapeHtml(item) + '</span>').join('') + '</div>' +
                (report.notes ? '<div><b>Notes:</b> ' + escapeHtml(report.notes) + '</div>' : '<div class="text-muted">No damage notes submitted.</div>') +
                (report.date ? '<small class="text-muted d-block mt-2">Submitted: ' + escapeHtml(report.date) + '</small>' : '') +
                (report.photo ? '<a class="d-block mt-2" href="' + escapeHtml(report.photo) + '" target="_blank" rel="noopener">View user condition photo</a>' : '');
        }

        function renderReturnConditionReport(report) {
            const summary = document.getElementById('returnConditionSummary');
            summary.innerHTML = !report || !(report.checks || []).length
                ? '<strong class="text-muted">No return condition report submitted.</strong>'
                : '<strong class="d-block mb-2 text-dark">Vehicle Return Condition Check</strong>' +
                    '<div class="mb-2">' + report.checks.map(item => '<span class="badge bg-dark me-1 mb-1">' + escapeHtml(item) + '</span>').join('') + '</div>' +
                    (report.notes ? '<div><b>Notes:</b> ' + escapeHtml(report.notes) + '</div>' : '<div class="text-muted">No return notes submitted.</div>') +
                    (report.name ? '<div class="mt-2"><b>Checked by:</b> ' + escapeHtml(report.name) + ' (' + escapeHtml(report.role || 'Staff') + ')</div>' : '') +
                    (report.date ? '<small class="text-muted d-block mt-1">Checked: ' + escapeHtml(report.date) + '</small>' : '');
        }

        function handleRentalStatus(id, conditionPayload) {
            const btn = document.getElementById('btn-' + id);
            const statusLabel = document.getElementById('status-' + id);
            if (!btn || !statusLabel) {
                showStaffAlert('Completed reservations cannot be processed or changed.');
                return;
            }
            if (statusLabel.innerText.trim().toLowerCase() === 'completed') {
                openCompletedInspection(id);
                return;
            }
            const row = statusLabel.closest('.res-row');
            const currentStatus = statusLabel.innerText.trim().toLowerCase();
            if (currentStatus === 'released' && !conditionPayload) {
                openReturnConditionModal(id);
                return;
            }
            const nextStatus = currentStatus === 'verified'
                ? 'processing'
                : (currentStatus === 'processing' ? 'released' : 'completed');

            setStaffMutationLoader(true);
            fetch('{{ url('/staff/reservations') }}/' + id + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(Object.assign({ status: nextStatus }, conditionPayload || {}))
            }).then(async response => {
                if (!response.ok) {
                    let message = 'Unable to update the rental status.';
                    try {
                        const payload = await response.json();
                        message = payload.message || message;
                    } catch (error) {
                    }
                    throw new Error(message);
                }
                return response.json();
            }).then(() => {
                if (nextStatus === 'processing') {
                    statusLabel.innerText = 'Processing';
                    statusLabel.className = 'status-pill bg-info text-white';
                    btn.innerText = 'Released';
                    btn.className = 'btn btn-sm btn-dark fw-bold px-3 rounded-pill';
                    if (row) row.setAttribute('data-status', 'processing');
                } else if (nextStatus === 'released') {
                    statusLabel.innerText = 'Released';
                    statusLabel.className = 'status-pill bg-primary text-white';
                    btn.innerText = 'Returned';
                    btn.className = 'btn btn-sm btn-dark fw-bold px-3 rounded-pill';
                    if (row) row.setAttribute('data-status', 'released');
                } else {
                    statusLabel.innerText = 'Completed';
                    statusLabel.className = 'status-pill status-completed';
                    btn.disabled = false;
                    btn.innerText = 'Completed';
                    btn.className = 'btn btn-sm btn-outline-secondary fw-bold px-3 rounded-pill';
                    if (row) row.setAttribute('data-status', 'completed');
                }
                updateStatCounts();
            }).catch(async error => {
                showStaffAlert(error.message);
                await syncStaffReservations();
            }).finally(() => {
                setStaffMutationLoader(false);
            });
        }

        function updateStatCounts() {
            const rows = document.querySelectorAll('#reservationTable tbody .res-row');
            let total = 0, pending = 0, verified = 0, processing = 0, released = 0, returned = 0;
            rows.forEach(row => {
                total++;
                const status = row.getAttribute('data-status');
                if (status === 'pending') pending++;
                if (status === 'verified') verified++;
                if (status === 'processing') processing++;
                if (status === 'released') released++;
                if (status === 'returned' || status === 'completed') returned++;
            });
            document.getElementById('statTotal').innerText = total;
            document.getElementById('statPending').innerText = pending;
            document.getElementById('statVerified').innerText = verified;
            document.getElementById('statProcessing').innerText = processing;
            document.getElementById('statReleased').innerText = released;
            document.getElementById('statReturned').innerText = returned;
        }

        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', () => {
                const filter = card.getAttribute('data-filter');
                currentReservationFilter = (currentReservationFilter === filter) ? 'all' : filter;
                filterReservations();
            });
        });

        function clearReservationFilter() {
            currentReservationFilter = 'all';
            filterReservations();
        }

        function filterReservations() {
            const query = document.getElementById('controlSearchInput').value.trim().toLowerCase();
            const rows = document.querySelectorAll('#reservationTable tbody .res-row');
            const noResultMsg = document.getElementById('noResultMsg');
            const filterBanner = document.getElementById('filterBanner');
            const filterLabel = document.getElementById('filterLabel');
            let visibleCount = 0;
            const filterNames = { 'all': '', 'pending': 'Pending', 'verified': 'Verified', 'processing': 'Processing (Confirmed)', 'released': 'Released', 'returned': 'Returned' };

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');
                const matchesSearch = searchData.includes(query);
                let matchesStat = true;

                if (currentReservationFilter === 'pending') matchesStat = (status === 'pending');
                else if (currentReservationFilter === 'verified') matchesStat = (status === 'verified');
                else if (currentReservationFilter === 'processing') matchesStat = (status === 'processing');
                else if (currentReservationFilter === 'released') matchesStat = (status === 'released');
                else if (currentReservationFilter === 'returned') matchesStat = (status === 'returned' || status === 'completed');

                const isMatch = matchesSearch && matchesStat;
                row.style.display = isMatch ? '' : 'none';
                if (isMatch) visibleCount++;
            });

            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.toggle('active-filter', card.getAttribute('data-filter') === currentReservationFilter && currentReservationFilter !== 'all');
            });

            if (currentReservationFilter !== 'all') {
                filterBanner.classList.remove('d-none');
                filterLabel.innerText = filterNames[currentReservationFilter];
            } else {
                filterBanner.classList.add('d-none');
            }

            noResultMsg.classList.toggle('d-none', visibleCount !== 0);
        }

        document.querySelector('#reservationTable tbody').addEventListener('click', event => {
            if (!(event.target instanceof Element)) return;
            const button = event.target.closest('button[data-reservation-action]');
            if (!button) return;

            const id = button.dataset.reservationId;
            if (button.dataset.reservationAction === 'review') {
                viewDetailsById(id);
            } else if (button.dataset.reservationAction === 'status') {
                promptRentalStatus(id);
            } else if (button.dataset.reservationAction === 'completed-inspection') {
                openCompletedInspection(id);
            }
        });

        renderStaffReservations();
        updateStatCounts();
        filterReservations();
        syncStaffReservations();
        window.setInterval(syncStaffReservations, 5000);
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
