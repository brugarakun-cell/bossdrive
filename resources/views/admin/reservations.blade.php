<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BossDrive - Admin Reservation Management</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #dc3545; --boss-dark: #212529; --boss-grey: #f8f9fa; }
        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }
        
        /* Consistent Sidebar */
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
        }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { background-color: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); }

        .main-content { margin-left: 250px; }
        
        /* Consistent Header */
        .top-nav { 
            background: white; 
            padding: 15px 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card { 
            border-radius: 15px; 
            border: none; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            cursor: pointer;
            transition: 0.25s;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 18px rgba(0,0,0,0.1); }
        .stat-card.active-filter { box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.35), 0 8px 18px rgba(0,0,0,0.1); }
        .reservation-stat-row { display:grid; grid-template-columns:repeat(6, minmax(0, 1fr)); gap:1rem; margin-left:0; margin-right:0; padding:0 2px 6px; }
        .reservation-stat-row > .col { width:auto; min-width:0; padding:0; }
        .reservation-stat-row .stat-card { min-height:100px; padding:.55rem .5rem !important; }
        .reservation-stat-row small { line-height:1.2; white-space:nowrap; font-size:clamp(.55rem, .9vw, .72rem); }
        .reservation-stat-row h2 { font-size:1.55rem; }
        @media (max-width: 992px) {
            .reservation-stat-row { display:flex; gap:1rem; overflow-x:auto; }
            .reservation-stat-row > .col { flex:0 0 155px; }
        }

        .res-card { border-radius: 15px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08); background: white; padding: 25px; }
        
        /* Fixed Payment Tag to prevent breaking into multiple lines */
        .pay-tag { 
            font-size: 0.7rem; 
            font-weight: 800; 
            padding: 4px 12px; 
            border-radius: 50px; 
            text-transform: uppercase; 
            border: 1px solid; 
            white-space: nowrap; 
            display: inline-block;
            margin-bottom: 4px;
        }
        .pay-full { color: #198754; border-color: #198754; background: #eefdf5; }
        .pay-not-full { color: #dc3545; border-color: #dc3545; background: #fff5f5; }
        .pay-deposit { color: #996c00; border-color: #f0ad00; background: #fff8df; }
        .pay-walkin { color: #dc3545; border-color: #dc3545; background: #fff5f5; }
        #reservationTable th,
        #reservationTable td { white-space: nowrap; }
        #reservationTable td:nth-child(4) { white-space: normal; min-width: 95px; }
        #reservationTable td:nth-child(5) { width: 125px; }
        #reservationTable .pay-tag { max-width: 118px; overflow: hidden; text-overflow: ellipsis; }

        #verifyModal .modal-dialog { width: calc(100vw - 6rem); max-width: 1050px; margin: 1rem auto; }
        #verifyModal .modal-content { max-width: 100%; overflow: hidden; }
        #verifyModal .modal-body { overflow-x: hidden; }
        #verifyModal .modal-header { padding: 1rem 1.25rem !important; }
        #verifyModal .modal-body { padding: 1.25rem !important; }
        #verifyModal .driver-assign-box { margin-top: 1rem; padding-top: 1rem; }
        #verifyModal .row > [class*="col-"] { min-width: 0; }
        .doc-preview { display: block; width: 100% !important; max-width: 100%; height: 110px !important; object-fit: cover; border-radius: 8px; border: 1px solid #eee; transition: 0.3s; cursor: zoom-in; }
        #mPaymentProof { height: 120px !important; max-width: 100%; object-fit: contain; }
        .doc-preview:hover { transform: scale(1.02); }
        .info-label { font-size: 0.7rem; color: #aaa; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-weight: 700; color: #333; display: block; margin-bottom: 10px; }

        /* Status Badge Style */
        .status-pill { font-size: 0.7rem; font-weight: 800; padding: 4px 12px; border-radius: 50px; text-transform: uppercase; white-space: nowrap; }
        .status-pill.status-completed { background: #eefdf5; color: #198754; border: 1px solid #198754; }

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

        .filter-active-banner {
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* ===== Driver Assignment (On-Call Pool) ===== */
        .driver-assign-box {
            border-top: 2px dashed #eee;
            padding-top: 20px;
            margin-top: 20px;
        }
        .driver-list-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .driver-option-card {
            border: 2px solid #eee;
            border-radius: 12px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            cursor: pointer;
            transition: 0.2s;
            margin-bottom: 0;
        }
        .driver-option-card:hover { border-color: #f5c2c7; background: #fff8f8; }
        .driver-option-card.selected { border-color: #dc3545; background: #fff5f5; box-shadow: 0 0 0 2px rgba(220,53,69,0.15); }
        .driver-avatar-sm { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; margin-right: 8px; flex-shrink: 0; }
        .driver-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .dot-available { background: #198754; }
        .dot-busy { background: #dc3545; }

        /* ===== Fixed: driver-name-tag was a single pill trying to force
           "Name — Date" onto one line, which made it wrap and look broken
           inside narrow table cells (see reported screenshot). Now it's a
           small rounded card: name on its own line, date underneath in a
           lighter weight, with a max-width so it never blows out the cell. */
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

        /* Exact Match Custom Modal Styles from Image */
        .custom-confirm-box {
            border-radius: 20px !important;
            border: none !important;
            padding: 30px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
        }
        .question-icon-wrapper {
            width: 75px;
            height: 75px;
            background-color: #fff5f5;
            color: #dc3545;
            font-size: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 20px auto;
        }
        .custom-confirm-title {
            font-weight: 700;
            font-size: 1.25rem;
            color: #212529;
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.4;
        }
        .custom-confirm-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .custom-confirm-btns .btn {
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .btn-cancel-custom {
            background: #fff;
            border: 1px solid #dee2e6;
            color: #212529;
        }
        .btn-cancel-custom:hover {
            background: #f8f9fa;
        }
        .btn-confirm-custom {
            background: #dc3545;
            border: none;
            color: #fff;
        }
        .btn-confirm-custom:hover {
            background: #c82333;
        }

        /* ===== Success / Cancel / Error toast notifications ===== */
        .bd-toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; display: flex; flex-direction: column; align-items: center; gap: 10px; pointer-events: none; }
        .bd-toast { min-width: 260px; max-width: 340px; padding: 14px 18px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.18); font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; color: #fff; opacity: 0; transform: scale(0.9); transition: 0.25s ease; }
        .bd-toast.show { opacity: 1; transform: scale(1); }
        .bd-toast.success { background: #198754; }
        .bd-toast.cancel { background: #6c757d; }
        .bd-toast.error { background: #dc3545; }
        .image-preview-dialog { max-width: 92vw; width: max-content; }
        .image-preview-content { width: max-content; max-width: 92vw; }
        .image-preview-content .modal-header { padding: 8px 12px; }
        .image-preview-content .modal-body { max-width: 92vw; }
        #imagePreview { display: block; max-width: 88vw; max-height: 78vh; width: auto; height: auto; }

        /* Keep the confirm modal above anything else that might be open
           (e.g. confirming an action while the Verification modal is open) */
        #customConfirmModal { z-index: 1090 !important; }
        @media (max-width: 767.98px) {
            #verifyModal .modal-dialog { width: calc(100vw - 1rem); margin: .5rem auto; }
            #verifyModal .modal-body { padding: 1rem !important; }
            #verifyModal .border-end { border-right: 0 !important; border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1rem; }
        }
    </style>
</head>
<body>

    <!-- Success/Cancel/Error toast notifications appear here -->
    <div class="bd-toast-container" id="bdToastContainer"></div>

    @include('admin.partials.navigation', ['adminPageTitle' => 'Reservations', 'adminPageAccent' => 'Logs'])
    <div class="main-content">

        <div class="p-4">
            <div class="row g-4 mb-4 text-center flex-nowrap reservation-stat-row">
                <div class="col">
                    <div class="card stat-card p-4 border-bottom border-primary border-5" data-filter="all">
                        <small class="text-muted fw-bold d-block text-uppercase">Total Bookings</small>
                        <h2 class="fw-bold mb-0 mt-2" id="statTotal">0</h2>
                    </div>
                </div>
                <div class="col">
                    <div class="card stat-card p-4 border-bottom border-warning border-5" data-filter="verified">
                        <small class="text-muted fw-bold d-block text-uppercase">Verified</small>
                        <h2 class="fw-bold mb-0 text-warning mt-2" id="statVerified">0</h2>
                    </div>
                </div>
                <div class="col">
                    <div class="card stat-card p-4 border-bottom border-info border-5" data-filter="processing">
                        <small class="text-muted fw-bold d-block text-uppercase">Processing</small>
                        <h2 class="fw-bold mb-0 text-info mt-2" id="statProcessing">0</h2>
                    </div>
                </div>
                <div class="col">
                    <div class="card stat-card p-4 border-bottom border-primary border-5" data-filter="released">
                        <small class="text-muted fw-bold d-block text-uppercase">Released</small>
                        <h2 class="fw-bold mb-0 text-primary mt-2" id="statReleased">0</h2>
                    </div>
                </div>
                <div class="col">
                    <div class="card stat-card p-4 border-bottom border-success border-5" data-filter="returned">
                        <small class="text-muted fw-bold d-block text-uppercase">Returned</small>
                        <h2 class="fw-bold mb-0 text-success mt-2" id="statReturned">0</h2>
                    </div>
                </div>
                <div class="col">
                    <div class="card stat-card p-4 border-bottom border-danger border-5" data-filter="cancelled">
                        <small class="text-muted fw-bold d-block text-uppercase">Cancelled</small>
                        <h2 class="fw-bold mb-0 text-danger mt-2" id="statRejected">0</h2>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 px-1">
                <h6 class="fw-bold mb-0 text-uppercase text-muted"><i class="fas fa-list-alt text-danger me-2"></i>Reservations Logs</h6>
                <div class="btn-group" role="tablist" aria-label="Reservation booking source">
                    <a href="{{ route('admin.reservations') }}" class="btn btn-sm {{ $walkInOnly ? 'btn-outline-danger' : 'btn-danger' }} fw-bold" role="tab" aria-selected="{{ $walkInOnly ? 'false' : 'true' }}">
                        <i class="fas fa-globe me-1"></i>Online Bookings
                    </a>
                    <a href="{{ route('admin.walk-in-bookings', ['walkin' => 1]) }}" class="btn btn-sm {{ $walkInOnly ? 'btn-danger' : 'btn-outline-danger' }} fw-bold" role="tab" aria-selected="{{ $walkInOnly ? 'true' : 'false' }}">
                        <i class="fas fa-walking me-1"></i>Walk-in Bookings
                    </a>
                </div>
            </div>

            <div class="res-card">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-1 text-uppercase small">{{ $walkInOnly ? 'Walk-in Booking Logs' : 'Online Booking Logs' }}</h6>
                    </div>
                    <div class="input-group input-group-sm" style="max-width: 320px;">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-danger"></i></span>
                        <input type="text" id="controlSearchInput" class="form-control" placeholder="Search Control Number..." onkeyup="applyFilters()">
                    </div>
                </div>

                <div id="filterBanner" class="alert alert-danger py-2 px-3 filter-active-banner d-none d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-filter me-2"></i>Showing filtered results: <span id="filterLabel"></span></span>
                    <button class="btn btn-sm btn-outline-danger fw-bold" onclick="clearFilter()">Clear Filter</button>
                </div>

                <div id="noResultMsg" class="alert alert-warning small fw-bold text-center d-none">
                    <i class="fas fa-exclamation-circle me-1"></i> No booking found matching your search/filter.
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
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Modal -->
    <div class="modal fade" id="verifyModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-dark text-white p-4">
                    <div>
                        <h5 class="fw-bold mb-0 text-uppercase"><span id="mReviewType">Verification</span>: <span id="mHeader" class="text-danger"></span></h5>
                        <small class="text-white-50">Control No: <span id="mControlNumber" class="fw-bold text-white"></span></small>
                    </div>
                    <input type="hidden" id="currentResId">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-4 border-end" id="reviewProfilePanel">
                            <h6 class="fw-bold text-danger mb-3 text-uppercase small"><i class="fas fa-user-circle me-2"></i>Profile & Logistics</h6>
                            <label class="info-label">Full Name & Age</label><span class="info-value" id="mName"></span>
                            <label class="info-label">Contact Number</label><span class="info-value text-danger" id="mPhone"></span>
                            <label class="info-label">Registered Home Address</label><span class="info-value" id="mHome"></span>
                            <hr>
                            <label class="info-label">Service & Logistics</label><span class="info-value"><span id="mService"></span> | <span id="mLogistics"></span></span>
                            <label class="info-label">Unit Target Address</label><span class="info-value text-primary" id="mTarget"></span>
                            <label class="info-label">Delivery Notes</label><span class="info-value" id="mDeliveryNotes"></span>
                            <label class="info-label">Pickup Schedule</label><span class="info-value" id="mPickupSchedule"></span>
                            <label class="info-label">Return Schedule</label><span class="info-value" id="mReturnSchedule"></span>
                            <label class="info-label">Assigned Vehicle Unit</label><span class="info-value" id="mVehicleUnit"></span>
                            <label class="info-label">Vehicle Details</label><span class="info-value" id="mVehicleSpecs"></span>
                        </div>
                        <div class="col-md-5 border-end text-center" id="verificationDocumentsPanel">
                            <h6 class="fw-bold text-danger mb-3 text-uppercase small"><i class="fas fa-file-alt me-2"></i>Verification Documents</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="fw-bold text-muted d-block mb-1">Driver's License</small>
                                    <a id="mDriverLicenseLink" href="" rel="noopener" data-no-page-loader onclick="return openImagePreviewFromLink(event, this);">
                                        <img id="mDriverLicense" src="" alt="Driver's License" class="doc-preview" loading="eager">
                                    </a>
                                    <span id="mDriverLicenseMissing" class="small text-muted d-none">File is missing. Ask the customer to upload it again.</span>
                                </div>
                                <div class="col-6">
                                    <small class="fw-bold text-muted d-block mb-1">Gov ID</small>
                                    <a id="mValidIdLink" href="" rel="noopener" data-no-page-loader onclick="return openImagePreviewFromLink(event, this);">
                                        <img id="mValidId" src="" alt="Government ID" class="doc-preview" loading="eager">
                                    </a>
                                    <span id="mValidIdMissing" class="small text-muted d-none">File is missing. Ask the customer to upload it again.</span>
                                </div>
                                <div class="col-12 mt-3">
                                    <small class="fw-bold text-muted d-block mb-1">Proof of Billing</small>
                                    <a id="mProofOfBillingLink" href="" rel="noopener" data-no-page-loader onclick="return openImagePreviewFromLink(event, this);">
                                        <img id="mProofOfBilling" src="" alt="Proof of Billing" class="doc-preview" loading="eager">
                                    </a>
                                    <span id="mProofOfBillingMissing" class="small text-muted d-none">File is missing. Ask the customer to upload it again.</span>
                                </div>
                            </div>
                            <div id="updatedDocumentsNotice" class="alert alert-warning small mt-3 mb-0 d-none">The customer updated documents after verification. Review the latest files and confirm them again.</div>
                        </div>
                        <div class="col-md-3" id="reviewPaymentPanel">
                            <h6 class="fw-bold text-danger mb-3 text-uppercase small"><i class="fas fa-wallet me-2"></i>Payment Status</h6>
                            <label class="info-label">Current Mode</label><span class="info-value" id="mPayMethod"></span>
                            <label class="info-label">Amount Paid for Rental</label><span class="info-value text-success" id="mPaid"></span>
                            <label class="info-label">Remaining Balance</label><span class="info-value text-danger" id="mBalance"></span>
                            <label class="info-label">Ref No.</label><span class="info-value fw-bold" id="mRef"></span>
                            
                            <div class="text-center mt-3">
                                <small class="fw-bold d-block mb-1 text-muted small">Receipt Screenshot</small>
                                <a id="mPaymentProofLink" href="" rel="noopener" data-no-page-loader onclick="return openImagePreviewFromLink(event, this);">
                                    <img id="mPaymentProof" src="" alt="Payment receipt" class="doc-preview" loading="eager" style="height: 120px; width: auto;">
                                </a>
                                <span id="mPaymentProofMissing" class="small text-muted d-none">No payment screenshot is available.</span>
                            </div>
                        </div>
                    </div>

                    <div id="adminPickupConditionReport" class="border rounded-3 bg-light p-3 mt-4 small"></div>

                    <!-- Driver Assignment Panel — only shown when Service is "With Driver" -->
                    <div id="driverAssignBox" class="driver-assign-box d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-danger mb-0 text-uppercase small"><i class="fas fa-id-badge me-2"></i>Assign On-Call Driver</h6>
                            <button type="button" class="btn btn-sm btn-outline-dark rounded-pill fw-bold" onclick="toggleAddDriverForm()"><i class="fas fa-plus me-1"></i> Add Driver</button>
                        </div>
                        <small class="text-muted d-block mb-2">Piliin ang driver na tatawagin para sa booking na ito. Gagamitin ang pickup date na pinili ng customer.</small>

                        <div id="driverListWrap" class="driver-list-grid"></div>

                        <div id="addDriverForm" class="add-driver-inline d-none">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <input type="text" id="newDriverName" class="form-control form-control-sm" placeholder="Driver full name">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" id="newDriverContact" class="form-control form-control-sm" placeholder="Contact number">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-danger btn-sm w-100 fw-bold" onclick="saveNewDriver()">Save Driver</button>
                                </div>
                            </div>
                        </div>

                        <div id="driverSelectedInfo" class="mt-2 d-none">
                            <span class="text-muted small">Naka-assign:</span> <span id="driverSelectedTag" class="driver-name-tag"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Close</button>
                    <button id="rejectReservationBtn" class="btn btn-outline-danger px-4 rounded-pill fw-bold" onclick="promptAction('reject')">Reject</button>
                    <button id="confirmReservationBtn" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm" onclick="promptAction('confirm')">Confirm Reservation</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered image-preview-dialog">
            <div class="modal-content bg-dark border-0 image-preview-content">
                <div class="modal-header border-0">
                    <h5 id="imagePreviewTitle" class="modal-title text-white"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-2">
                    <img id="imagePreview" src="" alt="" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div class="modal fade" id="returnConditionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow"><div class="modal-body p-4">
            <h5 class="fw-bold mb-2"><i class="fas fa-clipboard-check text-danger me-2"></i>Vehicle Return Condition Check</h5>
            <p class="small text-muted">Compare this with the user's Vehicle Pickup Condition Check before marking Returned.</p>
            <div id="userPickupConditionSummary" class="border rounded-3 bg-light p-3 mb-3 small"></div>
            <div id="returnConditionSummary" class="border rounded-3 bg-light p-3 mb-3 small d-none"></div>
            <form id="returnConditionForm" data-no-page-loader>
                <div class="border rounded-3 p-3 bg-light" id="returnConditionChecklist">
                    <label class="d-block border-bottom py-2"><input type="checkbox" name="return_condition_checks[]" value="Fuel level checked" required> Fuel level checked</label>
                    <label class="d-block border-bottom py-2"><input type="checkbox" name="return_condition_checks[]" value="Exterior/body inspected" required> Exterior/body inspected</label>
                    <label class="d-block border-bottom py-2"><input type="checkbox" name="return_condition_checks[]" value="Interior checked and clean" required> Interior checked and clean</label>
                    <label class="d-block border-bottom py-2"><input type="checkbox" name="return_condition_checks[]" value="Accessories/tools verified" required> Accessories/tools verified</label>
                    <label class="d-block py-2"><input type="checkbox" name="return_condition_checks[]" value="I confirm the vehicle condition" required> I confirm the vehicle condition</label>
                </div>
                <textarea name="return_condition_notes" class="form-control mt-3" rows="2" placeholder="New damage or comparison notes (optional)" id="returnConditionNotes"></textarea>
                <button class="btn btn-dark w-100 rounded-pill fw-bold mt-3" type="submit" id="returnConditionSubmit">SUBMIT CHECK & RETURN</button>
            </form>
        </div></div></div>
    </div>
    <div class="modal fade" id="customConfirmModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content custom-confirm-box">
                <div class="question-icon-wrapper">
                    <i class="fas fa-question"></i>
                </div>
                <h3 id="confirmModalTitle" class="custom-confirm-title">Confirm and approve this reservation?</h3>
                <div id="rejectReasonWrap" class="d-none px-4 mb-3 text-start">
                    <label for="rejectReason" class="form-label fw-bold small text-muted">Reason for rejection</label>
                    <textarea id="rejectReason" class="form-control" rows="3" maxlength="1000" placeholder="Explain why this reservation is being rejected."></textarea>
                </div>
                <div class="custom-confirm-btns">
                    <button type="button" class="btn btn-cancel-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmModalBtn" class="btn btn-confirm-custom">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const serverReservations = @json($reservationRows);
        const canReviewUpdatedDocuments = @json((bool) session('is_admin', false));
        function setAdminMutationLoader(visible) {
            const loader = document.getElementById('adminPageLoader');
            loader?.classList.toggle('is-visible', visible);
            loader?.setAttribute('aria-hidden', visible ? 'false' : 'true');
        }

        function renderServerReservations() {
            const tbody = document.querySelector('#reservationTable tbody');
            if (!tbody) return;
            if (!serverReservations.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-walking fa-2x d-block mb-2 text-danger"></i><strong>No walk-in bookings yet.</strong><br><small>Bookings created by Admin or Staff will appear here.</small></td></tr>';
                return;
            }
            tbody.innerHTML = serverReservations.map(function (reservation) {
                const status = String(reservation.status || '').toLowerCase();
                const statusLabel = status === 'completed'
                    ? 'Completed'
                    : status.charAt(0).toUpperCase() + status.slice(1);
                const paymentKey = String(reservation.payment || '').toLowerCase();
                const isWalkInPayment = paymentKey === 'walkin';
                const isDepositPayment = paymentKey === 'deposit';
                const paymentLabel = isWalkInPayment
                    ? 'Walk-in'
                    : (isDepositPayment ? 'Deposit Only' : (reservation.paymentStatus === 'approved' ? 'Fully Paid' : reservation.payment));
                const paymentClass = isWalkInPayment
                    ? 'pay-walkin'
                    : (isDepositPayment ? 'pay-deposit' : (reservation.paymentStatus === 'approved' ? 'pay-full' : 'pay-not-full'));
                const actionLabels = {
                    verified: 'Processing',
                    processing: 'Released',
                    released: 'Returned',
                    completed: 'Completed'
                };
                const buttonLabel = actionLabels[status] || '';
                const hasLifecycleAction = Object.prototype.hasOwnProperty.call(actionLabels, status);
                const showButton = hasLifecycleAction ? '' : 'display: none;';
                const actionHandler = status === 'completed' ? 'openCompletedInspection' : 'handleRentalStatus';
                return '<tr class="res-row" data-control="' + escapeHtml(reservation.control) + '" data-payment="' + escapeHtml(reservation.paymentStatus) + '" data-walkin="' + (reservation.payment === 'Walkin' ? 'yes' : 'no') + '" data-status="' + escapeHtml(status) + '">' +
                    '<td><span class="fw-bold text-dark">' + escapeHtml(reservation.name) + '</span><br><small class="text-danger fw-bold">' + escapeHtml(reservation.phone) + '</small></td>' +
                    '<td><span class="control-tag">' + escapeHtml(reservation.control) + '</span></td>' +
                    '<td><span class="badge bg-dark rounded-pill px-3">' + escapeHtml(reservation.driver) + '</span></td>' +
                    '<td id="logi-' + reservation.id + '"><span class="badge bg-' + (reservation.service === 'Delivery' ? 'primary' : 'secondary') + ' rounded-pill px-3">' + reservation.service + '</span></td>' +
                    '<td><span class="pay-tag ' + paymentClass + '">' + escapeHtml(paymentLabel) + '</span></td>' +
                    '<td class="text-center"><span id="status-' + reservation.id + '" class="status-pill ' + statusClassFor(status) + ' text-uppercase">' + escapeHtml(statusLabel) + '</span>' + (reservation.documentsNeedReview ? '<small class="d-block text-warning fw-bold">Updated ID needs review</small>' : '') + '</td>' +
                    '<td class="text-center"><div class="d-flex justify-content-center gap-2"><button class="btn btn-sm btn-outline-danger fw-bold px-3 rounded-pill" onclick="openServerReservation(' + reservation.id + ')">Review</button><button id="btn-' + reservation.id + '" class="btn btn-sm ' + (status === 'completed' ? 'btn-outline-secondary' : 'btn-dark') + ' fw-bold px-3 rounded-pill" onclick="' + actionHandler + '(' + reservation.id + ')" style="' + showButton + '" title="' + escapeHtml(buttonLabel) + '">' + buttonLabel + '</button><button class="btn btn-sm btn-outline-secondary fw-bold rounded-pill" onclick="deleteReservation(' + reservation.id + ')" title="Delete reservation"><i class="fas fa-trash"></i></button></div></td>' +
                    '</tr>';
            }).join('');
        }

        function statusClassFor(status) {
            const classes = {
                pending: 'bg-warning text-dark',
                verified: 'bg-success text-white',
                processing: 'bg-info text-white',
                released: 'bg-primary text-white',
                completed: 'status-completed',
                cancelled: 'bg-secondary text-white',
                void: 'bg-light text-muted border'
            };
            return classes[status] || 'bg-light text-muted border';
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (character) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
            });
        }

        function openServerReservation(id) {
            const reservation = serverReservations.find(item => item.id === id);
            if (!reservation) return;
            if (reservation.status === 'cancelled') {
                showToast('This booking was already cancelled and cannot be confirmed.', 'error');
                return;
            }
            viewDetails(id, reservation.name, reservation.phone, reservation.age ?? 'N/A', reservation.address, reservation.driver, reservation.service + ' / ' + reservation.rate, reservation.target, reservation.payment, reservation.paidAmount, reservation.balance, reservation.reference, reservation.control, reservation.bookingSource === 'admin_staff');
            document.getElementById('mDeliveryNotes').innerText = reservation.deliveryNotes || 'None';
            document.getElementById('mPickupSchedule').innerText = [reservation.pickupDate, reservation.pickupTime].filter(Boolean).join(' at ') || 'Not provided';
            document.getElementById('mReturnSchedule').innerText = [reservation.returnDate, reservation.returnTime].filter(Boolean).join(' at ') || 'Not provided';
            document.getElementById('mVehicleUnit').innerText = [reservation.vehicle, reservation.vehiclePlate ? 'Plate: ' + reservation.vehiclePlate : null].filter(Boolean).join(' — ');
            document.getElementById('mVehicleSpecs').innerText = [reservation.vehicleCategory, reservation.vehicleTransmission, reservation.vehicleFuel, reservation.vehicleCapacity].filter(Boolean).join(' · ') || 'Not provided';
            setUploadedPreview('mDriverLicense', 'mDriverLicenseLink', 'mDriverLicenseMissing', reservation.documents?.driver_license);
            setUploadedPreview('mValidId', 'mValidIdLink', 'mValidIdMissing', reservation.documents?.valid_id);
            setUploadedPreview('mProofOfBilling', 'mProofOfBillingLink', 'mProofOfBillingMissing', reservation.documents?.proof_of_billing);
            setUploadedPreview('mPaymentProof', 'mPaymentProofLink', 'mPaymentProofMissing', reservation.paymentProof);
            const pickupReport = document.getElementById('adminPickupConditionReport');
            if (reservation.bookingSource === 'admin_staff') {
                pickupReport.classList.add('d-none');
            } else {
                pickupReport.classList.remove('d-none');
                renderAdminPickupConditionReport(reservation.pickupConditionReport);
            }
            const canConfirmDocuments = reservation.status === 'pending'
                || (canReviewUpdatedDocuments && reservation.documentsNeedReview === true);
            const confirmButton = document.getElementById('confirmReservationBtn');
            confirmButton.classList.toggle('d-none', !canConfirmDocuments);
            confirmButton.innerText = reservation.documentsNeedReview ? 'Confirm Updated Documents' : 'Confirm Reservation';
            document.getElementById('updatedDocumentsNotice').classList.toggle('d-none', !reservation.documentsNeedReview);
            document.getElementById('rejectReservationBtn').classList.toggle('d-none', !['pending', 'verified'].includes(reservation.status));
        }

        function setUploadedPreview(imageId, linkId, missingId, url) {
            const image = document.getElementById(imageId);
            const link = document.getElementById(linkId);
            const missing = document.getElementById(missingId);
            const isImage = typeof url === 'string' && /\.(jpe?g|png|webp|gif)(?:[?#].*)?$/i.test(url);
            image.src = isImage ? url : '';
            link.href = isImage ? url : '#';
            link.classList.toggle('d-none', !isImage);
            missing.classList.toggle('d-none', isImage);
        }

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

        function renderAdminPickupConditionReport(report) {
            const container = document.getElementById('adminPickupConditionReport');
            if (!report) {
                container.innerHTML = '<strong class="text-muted">No User Vehicle Pickup Condition Check submitted.</strong>';
                return;
            }

            container.innerHTML = '<strong class="d-block mb-2 text-danger">User Vehicle Pickup Condition Check</strong>' +
                '<div class="mb-2">' + (report.checks || []).map(item => '<span class="badge bg-success me-1 mb-1">' + escapeHtml(item) + '</span>').join('') + '</div>' +
                (report.notes ? '<div><b>Notes:</b> ' + escapeHtml(report.notes) + '</div>' : '<div class="text-muted">No damage notes submitted.</div>') +
                (report.date ? '<small class="text-muted d-block mt-2">Submitted: ' + escapeHtml(report.date) + '</small>' : '') +
                (report.photo ? '<a class="d-block mt-2" href="' + escapeHtml(report.photo) + '" target="_blank" rel="noopener">View user condition photo</a>' : '');
        }

        function renderReturnConditionReport(report) {
            const container = document.getElementById('returnConditionSummary');
            if (!report || !(report.checks || []).length) {
                container.innerHTML = '<strong class="text-muted">No return condition report submitted.</strong>';
                return;
            }
            container.innerHTML = '<strong class="d-block mb-2 text-dark">Vehicle Return Condition Check</strong>' +
                '<div class="mb-2">' + report.checks.map(item => '<span class="badge bg-dark me-1 mb-1">' + escapeHtml(item) + '</span>').join('') + '</div>' +
                (report.notes ? '<div><b>Notes:</b> ' + escapeHtml(report.notes) + '</div>' : '<div class="text-muted">No return notes submitted.</div>') +
                (report.name ? '<div class="mt-2"><b>Checked by:</b> ' + escapeHtml(report.name) + ' (' + escapeHtml(report.role || 'Staff') + ')</div>' : '') +
                (report.date ? '<small class="text-muted d-block mt-1">Checked: ' + escapeHtml(report.date) + '</small>' : '');
        }

        function openCompletedInspection(id) {
            const reservation = serverReservations.find(item => String(item.id) === String(id));
            if (!reservation) {
                showToast('Completed inspection details are unavailable.', 'error');
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

        function openImagePreview(image) {
            const source = image.currentSrc || image.src;
            if (!source || source.endsWith('/')) {
                return;
            }

            const preview = document.getElementById('imagePreview');
            preview.src = source;
            preview.alt = image.alt;
            document.getElementById('imagePreviewTitle').innerText = image.alt;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('imagePreviewModal')).show();
        }

        function openImagePreviewFromLink(event, link) {
            event.preventDefault();
            const image = link.querySelector('img');
            if (image) openImagePreview(image);
            return false;
        }

        renderServerReservations();

        async function updateServerReservationStatus(id, status, rejectionComment, verifyDocuments, conditionPayload) {
            setAdminMutationLoader(true);
            let response;
            try {
                response = await fetch('{{ url('/admin/reservations') }}/' + id + '/status', {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(Object.assign({
                    status: status,
                    rejection_comment: typeof rejectionComment === 'string' ? rejectionComment : null,
                    verify_documents: verifyDocuments === true
                }, conditionPayload || {}, bookingDriverAssignments[id] && bookingDriverAssignments[id].driverId ? {
                    assigned_driver_name: (driverPool.find(function (driver) { return driver.id === bookingDriverAssignments[id].driverId; }) || {}).name || null,
                    assigned_driver_contact: (driverPool.find(function (driver) { return driver.id === bookingDriverAssignments[id].driverId; }) || {}).contact || null
                } : {}))
                });
            } finally {
                setAdminMutationLoader(false);
            }
            if (!response.ok) {
                const error = await response.json().catch(function () { return {}; });
                throw new Error(error.message || 'Reservation status could not be saved.');
            }
            const result = await response.json();
            const reservation = serverReservations.find(item => item.id === id);
            if (reservation) {
                reservation.status = status;
                if (verifyDocuments) reservation.documentsNeedReview = false;
            }
            return result;
        }

        async function deleteReservation(id) {
            if (!window.confirm('Delete this reservation and its uploaded files?')) return;
            setAdminMutationLoader(true);
            try {
                const response = await fetch('{{ url('/admin/reservations') }}/' + id, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const result = await response.json().catch(function () { return {}; });
                if (!response.ok) {
                    showToast(result.message || 'Reservation could not be deleted. Please refresh and try again.', 'error');
                    return;
                }

                const row = document.getElementById('btn-' + id)?.closest('.res-row');
                if (row) row.remove();
                updateStatCounts();
                applyFilters();
                showToast(result.message || 'Reservation deleted.', 'success');
            } catch (error) {
                showToast('Could not reach the server. Reservation was not deleted.', 'error');
            } finally {
                setAdminMutationLoader(false);
            }
        }
        let verificationModal;
        let customConfirmModal;
        let currentStatFilter = 'all';

        // ================= ON-CALL DRIVER POOL =================
        // Every driver here is "on call" — walang fixed shift, tinatawag lang kapag
        // may With Driver booking na kailangan i-assign.
        let driverPool = @json($driverCatalog);
        driverPool = driverPool.map(function (driver) {
            return Object.assign({}, driver, {
                status: driver.bookingStatus || 'available',
                weeklyAvailable: driver.availableThisWeek === true
            });
        });

        // Tracks which driver is assigned to each booking. The schedule date
        // is always derived from that booking's pickup date.
        let bookingDriverAssignments = {};
        serverReservations.forEach(function (reservation) {
            if (reservation.assignedDriverName) {
                bookingDriverAssignments[reservation.id] = {
                    driverId: null,
                    driverName: reservation.assignedDriverName,
                    driverContact: reservation.assignedDriverContact || ''
                };
            }
        });

        // Currently selected (but not yet confirmed) driver + date for the modal in view
        let selectedDriverId = null;
        let selectedScheduleDate = null;

        document.addEventListener("DOMContentLoaded", () => {
            customConfirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
            updateStatCounts();
            applyFilters();
        });

        // ================= SUCCESS / CANCEL / ERROR TOAST NOTIFICATIONS =================
        function showToast(message, type) {
            type = type || 'success';
            const container = document.getElementById('bdToastContainer');
            if (!container) return;

            const icon = type === 'success' ? 'fa-check-circle' : (type === 'cancel' ? 'fa-times-circle' : 'fa-exclamation-circle');
            const toast = document.createElement('div');
            toast.className = 'bd-toast ' + type;
            toast.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span>';
            container.appendChild(toast);

            requestAnimationFrame(function() { toast.classList.add('show'); });

            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() { toast.remove(); }, 250);
            }, 3000);
        }

        // ================= CUSTOM CONFIRM MODAL (promise-based, replaces native confirm()) =================
        // Usage: if (await showConfirm('Are you sure?')) { ... } else { showToast('Cancelled...', 'cancel'); }
        // Pass a second argument (seconds) to require a short countdown before
        // the Confirm button becomes clickable — used for destructive actions
        // like rejecting a booking application, so admin can't accidentally rush it.
        let bdConfirmResolve = null;
        let pendingReturnReservationId = null;
        let pendingReturnCondition = null;
        let bdConfirmCountdownInterval = null;

        function showConfirm(message, countdownSeconds, showReason) {
            return new Promise(function(resolve) {
                document.getElementById('confirmModalTitle').innerText = message;
                const reasonWrap = document.getElementById('rejectReasonWrap');
                reasonWrap.classList.toggle('d-none', !showReason);
                bdConfirmResolve = resolve;

                const okBtn = document.getElementById('confirmModalBtn');

                // Clear any countdown left running from a previous call
                if (bdConfirmCountdownInterval) {
                    clearInterval(bdConfirmCountdownInterval);
                    bdConfirmCountdownInterval = null;
                }

                if (countdownSeconds && countdownSeconds > 0) {
                    let remaining = countdownSeconds;
                    okBtn.disabled = true;
                    okBtn.classList.add('disabled');
                    okBtn.innerText = 'Confirm (' + remaining + 's)';

                    bdConfirmCountdownInterval = setInterval(function() {
                        remaining--;
                        if (remaining <= 0) {
                            clearInterval(bdConfirmCountdownInterval);
                            bdConfirmCountdownInterval = null;
                            okBtn.disabled = false;
                            okBtn.classList.remove('disabled');
                            okBtn.innerText = 'Confirm';
                        } else {
                            okBtn.innerText = 'Confirm (' + remaining + 's)';
                        }
                    }, 1000);
                } else {
                    okBtn.disabled = false;
                    okBtn.classList.remove('disabled');
                    okBtn.innerText = 'Confirm';
                }

                customConfirmModal.show();
            });
        }

        document.getElementById('confirmModalBtn').addEventListener('click', function() {
            if (this.disabled) return; // still counting down — ignore clicks
            if (bdConfirmCountdownInterval) { clearInterval(bdConfirmCountdownInterval); bdConfirmCountdownInterval = null; }
            if (bdConfirmResolve) { bdConfirmResolve(true); bdConfirmResolve = null; }
            customConfirmModal.hide();
        });
        document.getElementById('returnConditionForm').addEventListener('submit', function (event) {
            event.preventDefault();
            pendingReturnCondition = {
                return_condition_checks: Array.from(event.currentTarget.querySelectorAll('[name="return_condition_checks[]"]:checked')).map(input => input.value),
                return_condition_notes: event.currentTarget.querySelector('[name="return_condition_notes"]').value
            };
            bootstrap.Modal.getInstance(document.getElementById('returnConditionModal')).hide();
            completeReturnAfterCondition(pendingReturnReservationId, pendingReturnCondition);
        });

        // Covers Cancel button and any other way the modal gets closed —
        // if it wasn't resolved true by the Confirm button above, treat as cancelled.
        document.getElementById('customConfirmModal').addEventListener('hidden.bs.modal', function() {
            if (bdConfirmCountdownInterval) { clearInterval(bdConfirmCountdownInterval); bdConfirmCountdownInterval = null; }
            if (bdConfirmResolve) { bdConfirmResolve(false); bdConfirmResolve = null; }
        });

        function viewDetails(id, name, phone, age, home, service, logistics, target, payMethod, paid, balance, ref, controlNumber, isWalkIn = false) {
            document.getElementById('currentResId').value = id;
            document.getElementById('mHeader').innerText = name;
            document.getElementById('mControlNumber').innerText = controlNumber;
            document.getElementById('mName').innerText = name + " (" + age + " yrs old)";
            document.getElementById('mPhone').innerText = phone;
            document.getElementById('mHome').innerText = home;
            document.getElementById('mService').innerText = service;
            document.getElementById('mLogistics').innerText = logistics;
            document.getElementById('mTarget').innerText = target;
            document.getElementById('mPayMethod').innerText = payMethod;
            document.getElementById('mPaid').innerText = '₱' + Number(paid || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('mBalance').innerText = '₱' + Number(balance || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('mRef').innerText = ref;
            document.getElementById('verificationDocumentsPanel').classList.toggle('d-none', isWalkIn);
            document.getElementById('mReviewType').innerText = isWalkIn ? 'Walk-in Booking Review' : 'Verification';
            document.getElementById('reviewProfilePanel').classList.toggle('col-md-4', !isWalkIn);
            document.getElementById('reviewProfilePanel').classList.toggle('col-md-6', isWalkIn);
            document.getElementById('reviewPaymentPanel').classList.toggle('col-md-3', !isWalkIn);
            document.getElementById('reviewPaymentPanel').classList.toggle('col-md-6', isWalkIn);

            // Show the driver-assignment panel only for "With Driver" bookings
            const driverBox = document.getElementById('driverAssignBox');
            const isWithDriver = service.trim() === 'With Driver';
            driverBox.classList.toggle('d-none', !isWithDriver);

            if (isWithDriver) {
                document.getElementById('addDriverForm').classList.add('d-none');
                const existing = bookingDriverAssignments[id];
                selectedDriverId = existing ? existing.driverId : null;
                const currentReservation = serverReservations.find(item => String(item.id) === String(id));
                selectedScheduleDate = currentReservation ? currentReservation.pickupDateIso : null;
                renderDriverList();
            }

            verificationModal = new bootstrap.Modal(document.getElementById('verifyModal'));
            verificationModal.show();
        }

        // ================= DRIVER ASSIGNMENT LOGIC =================
        function renderDriverList() {
            const wrap = document.getElementById('driverListWrap');
            wrap.innerHTML = '';

            driverPool.forEach(driver => {
                const isBookingAvailable = driver.status === 'available';
                const isWeeklyAvailable = driver.weeklyAvailable;
                const isSelected = driver.id === selectedDriverId;

                const card = document.createElement('div');
                card.className = 'driver-option-card' + (isSelected ? ' selected' : '') +
                    (!isWeeklyAvailable ? ' opacity-75' : '');

                const leftHtml =
                    '<div class="d-flex align-items-center" style="min-width:0; overflow:hidden;">' +
                        '<img src="https://ui-avatars.com/api/?name=' + encodeURIComponent(driver.name) + '&background=212529&color=fff" class="driver-avatar-sm">' +
                        '<div style="min-width:0; overflow:hidden;">' +
                            '<span class="fw-bold d-block small text-truncate">' + driver.name + '</span>' +
                            '<span class="text-muted text-truncate d-block" style="font-size:0.75rem;">' + driver.contact + '</span>' +
                        '</div>' +
                    '</div>';

                // The driver's schedule always follows the customer's pickup date.
                const rightHtml = isSelected
                    ? '<div class="small fw-bold text-end text-nowrap" onclick="event.stopPropagation();">' +
                          '<span class="d-block text-muted" style="font-size:0.65rem;">Booking date</span>' +
                          '<span>' + (selectedScheduleDate ? formatSchedDate(selectedScheduleDate) : 'No date') + '</span>' +
                      '</div>'
                    : '<span class="small fw-bold text-nowrap"><span class="driver-status-dot ' +
                      (isWeeklyAvailable && isBookingAvailable ? 'dot-available' : 'dot-busy') + '"></span>' +
                      (!isWeeklyAvailable ? 'Not Available' :
                        (driver.status === 'ongoing' ? 'Ongoing' :
                        (driver.status === 'reserved' ? 'Reserved' : 'Available'))) + '</span>';

                card.innerHTML = leftHtml + rightHtml;
                card.onclick = function() {
                    if ((!isWeeklyAvailable || !isBookingAvailable) && !isSelected) {
                        const reason = !isWeeklyAvailable
                            ? 'marked Not Available this week'
                            : (driver.status === 'ongoing' ? 'currently Ongoing' : 'currently Reserved');
                        showToast(driver.name + ' is ' + reason + '.', 'error');
                        return;
                    }
                    selectDriver(driver.id);
                };
                wrap.appendChild(card);
            });

            if (wrap.innerHTML === '') {
                wrap.innerHTML = '<div class="text-muted small fst-italic">Walang available na driver. Mag-add ng bagong driver sa itaas.</div>';
            }

            updateSelectedDriverInfo();
        }

        function selectDriver(driverId) {
            if (selectedDriverId === driverId) {
                selectedDriverId = null;
            } else {
                selectedDriverId = driverId;
            }
            renderDriverList();
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

        // Builds the fixed, non-wrapping-into-a-blob driver tag markup:
        // small icon + name on top, date underneath in a muted smaller line.
        function buildDriverTagHtml(name, dateText) {
            return '<i class="fas fa-id-badge"></i>' +
                   '<span>' +
                       '<span class="dnt-name">' + name + '</span>' +
                       '<span class="dnt-date">' + dateText + '</span>' +
                   '</span>';
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
                showToast('Please fill out the driver name and contact number.', 'error');
                return;
            }

            const newId = 'DRV-' + String(driverPool.length + 1).padStart(3, '0');
            driverPool.push({
                id: newId,
                name: name,
                contact: contact,
                status: 'available',
                availableThisWeek: true,
                weeklyAvailable: true
            });

            nameInput.value = '';
            contactInput.value = '';
            document.getElementById('addDriverForm').classList.add('d-none');

            renderDriverList();
            showToast(name + ' has been added to the on-call driver pool!', 'success');
        }

        async function promptAction(actionType) {
            if (actionType === 'confirm') {
                const id = document.getElementById('currentResId').value;
                const currentReservation = serverReservations.find(item => item.id === Number(id));
                const canConfirmDocuments = currentReservation
                    && (currentReservation.status === 'pending'
                        || (canReviewUpdatedDocuments && currentReservation.documentsNeedReview === true));
                if (currentReservation && !canConfirmDocuments) {
                    showToast('There are no updated documents awaiting review for this reservation.', 'error');
                    return;
                }
                const confirmationMessage = currentReservation?.documentsNeedReview
                    ? 'Confirm the updated documents? The current reservation status will not change.'
                    : 'Confirm and approve this reservation?';
                if (await showConfirm(confirmationMessage)) {
                    executeConfirmReservation();
                } else {
                    showToast('Cancelled — reservation was not approved.', 'cancel');
                }
            } else if (actionType === 'reject') {
                // 3-second countdown before Confirm is clickable — prevents an
                // accidental/rushed rejection of a booking application.
                document.getElementById('rejectReason').value = '';
                if (await showConfirm("Are you sure you want to reject this booking application?", 3, true)) {
                    const reason = document.getElementById('rejectReason').value.trim();
                    if (!reason) {
                        showToast('Please provide the reason for rejecting this booking.', 'error');
                        return;
                    }
                    executeRejectReservation(reason);
                } else {
                    showToast('Cancelled — booking was not cancelled.', 'cancel');
                }
            }
        }

        async function executeConfirmReservation() {
            const id = document.getElementById('currentResId').value;
            const currentReservation = serverReservations.find(item => item.id === Number(id));
            const isDocumentReReview = currentReservation?.documentsNeedReview === true;
            const canConfirmDocuments = currentReservation
                && (currentReservation.status === 'pending'
                    || (canReviewUpdatedDocuments && currentReservation.documentsNeedReview === true));
            if (currentReservation && !canConfirmDocuments) {
                showToast('There are no updated documents awaiting review for this reservation.', 'error');
                return;
            }
            if (currentReservation && currentReservation.status === 'cancelled' && !isDocumentReReview) {
                showToast('This booking was already cancelled and cannot be confirmed.', 'error');
                return;
            }

            const statusLabel = document.getElementById('status-' + id);
            const releaseBtn = document.getElementById('btn-' + id);
            const driverBoxVisible = !document.getElementById('driverAssignBox').classList.contains('d-none');

            // Block the confirm if this is a "With Driver" booking pero walang napiling driver pa
            if (!isDocumentReReview && driverBoxVisible && !selectedDriverId) {
                showToast('Please assign an on-call driver before confirming.', 'error');
                return;
            }
            if (!isDocumentReReview && driverBoxVisible && selectedDriverId && !selectedScheduleDate) {
                showToast('This booking has no pickup date for the assigned driver.', 'error');
                return;
            }
            if (!isDocumentReReview && driverBoxVisible && selectedDriverId) {
                const selectedDriver = driverPool.find(function (driver) { return driver.id === selectedDriverId; });
                if (selectedDriver && !selectedDriver.weeklyAvailable) {
                    showToast(selectedDriver.name + ' is marked Not Available this week.', 'error');
                    return;
                }
            }

            if (!isDocumentReReview && driverBoxVisible && selectedDriverId) {
                assignDriverToBooking(id, selectedDriverId, selectedScheduleDate);
            }

            const currentStatus = currentReservation ? currentReservation.status : 'pending';
            const statusToSave = isDocumentReReview || ['processing', 'released', 'completed', 'cancelled', 'void'].includes(currentStatus)
                ? currentStatus
                : 'verified';

            try {
                await updateServerReservationStatus(id, statusToSave, null, true);
            } catch (error) {
                showToast(error.message, 'error');
                return;
            }

            if (statusToSave === 'verified' && !isDocumentReReview) {
                statusLabel.innerText = "Verified";
                statusLabel.className = "status-pill bg-info text-white";
                releaseBtn.style.display = "inline-block";
                releaseBtn.innerText = "Processing";
                releaseBtn.className = "btn btn-sm btn-dark fw-bold px-3 rounded-pill";
            }

            const row = statusLabel.closest('.res-row');
            if (row) row.setAttribute('data-status', statusToSave);
            if (currentReservation) {
                currentReservation.status = statusToSave;
                currentReservation.documentsNeedReview = false;
            }
            updateStatCounts();

            if (verificationModal) verificationModal.hide();
            showToast(
                statusToSave === 'verified' && !isDocumentReReview
                    ? 'Documents checked and reservation marked as Verified!'
                    : 'Documents confirmed. The reservation status remains ' + statusToSave + '.',
                'success'
            );
        }

        // Marks the driver as scheduled/on-call for this booking, frees up the
        // previous driver if the admin re-assigns, and reflects it on the table row.
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

        async function executeRejectReservation(reason) {
            const id = document.getElementById('currentResId').value;
            try {
                await updateServerReservationStatus(id, 'cancelled', reason);
            } catch (error) {
                showToast(error.message, 'error');
                return;
            }
            if (verificationModal) verificationModal.hide();
            showToast('Reservation application has been cancelled.', 'success');
        }

        async function handleRentalStatus(id, conditionPayload) {
            const btn = document.getElementById('btn-' + id);
            const statusLabel = document.getElementById('status-' + id);

            if (btn.disabled) {
                showToast('This reservation is already completed and cannot be processed again.', 'error');
                return;
            }
            if (statusLabel.innerText.trim().toLowerCase() === 'completed') {
                openCompletedInspection(id);
                return;
            }

            if (btn.innerText === "Processing") {
                if (await showConfirm("Move this verified reservation to Processing?")) {
                    try {
                        await updateServerReservationStatus(id, 'processing');
                    } catch (error) {
                        showToast(error.message, 'error');
                        return;
                    }
                    statusLabel.innerText = "Processing";
                    statusLabel.className = "status-pill bg-info text-white";
                    btn.innerText = "Released";
                    const rowP = statusLabel.closest('.res-row');
                    if (rowP) rowP.setAttribute('data-status', 'processing');
                    showToast('Reservation is now Processing.', 'success');
                    updateStatCounts();
                }
            } else if (btn.innerText === "Released") {
                if (await showConfirm("Mark this vehicle unit as Released?")) {
                    try {
                        await updateServerReservationStatus(id, 'released');
                    } catch (error) {
                        showToast(error.message, 'error');
                        return;
                    }
                    statusLabel.innerText = "Released";
                    statusLabel.className = "status-pill bg-primary text-white";
                    btn.innerText = "Returned";
                    btn.className = "btn btn-sm btn-success fw-bold px-3 rounded-pill";
                    const rowR = statusLabel.closest('.res-row');
                    if (rowR) rowR.setAttribute('data-status', 'released');
                    showToast('Unit has been successfully Released!', 'success');
                    updateStatCounts();
                } else {
                    showToast('Cancelled — unit was not marked as released.', 'cancel');
                }
            } else if (btn.innerText === "Returned") {
                pendingReturnReservationId = id;
                const reservation = serverReservations.find(item => String(item.id) === String(id));
                renderUserPickupCondition(reservation?.pickupConditionReport);
                document.getElementById('returnConditionSummary').classList.add('d-none');
                document.getElementById('returnConditionChecklist').classList.remove('d-none');
                document.getElementById('returnConditionNotes').classList.remove('d-none');
                document.getElementById('returnConditionSubmit').classList.remove('d-none');
                document.getElementById('returnConditionForm').reset();
                new bootstrap.Modal(document.getElementById('returnConditionModal')).show();
            } else if (btn.innerText === "Completed") {
                const reservation = serverReservations.find(item => String(item.id) === String(id));
                renderAdminPickupConditionReport(reservation?.pickupConditionReport);
                renderReturnConditionReport(reservation?.returnCondition);
                document.getElementById('returnConditionChecklist').classList.add('d-none');
                document.getElementById('returnConditionNotes').classList.add('d-none');
                document.getElementById('returnConditionSubmit').classList.add('d-none');
                document.getElementById('returnConditionSummary').classList.remove('d-none');
                new bootstrap.Modal(document.getElementById('returnConditionModal')).show();
            }
        }

        async function completeReturnAfterCondition(id, conditionPayload) {
                const btn = document.getElementById('btn-' + id);
                const statusLabel = document.getElementById('status-' + id);
                if (await showConfirm("Confirm that the vehicle has been returned?")) {
                    try {
                        await updateServerReservationStatus(id, 'completed', null, false, conditionPayload);
                    } catch (error) {
                        showToast(error.message, 'error');
                        return;
                    }
                    statusLabel.innerText = "Completed";
                    statusLabel.className = "status-pill status-completed text-uppercase";
                    btn.disabled = false;
                    btn.innerText = "Completed";
                    btn.className = "btn btn-sm btn-outline-secondary fw-bold px-3 rounded-pill";
                    const rowT = statusLabel.closest('.res-row');
                    if (rowT) rowT.setAttribute('data-status', 'completed');
                    showToast('Transaction completed successfully!', 'success');
                    updateStatCounts();
                } else {
                    showToast('Cancelled — return was not confirmed.', 'cancel');
                }
        }

        function updateStatCounts() {
            const rows = document.querySelectorAll('#reservationTable tbody .res-row');
            let total = 0, verified = 0, processing = 0, released = 0, returned = 0, rejected = 0;

            rows.forEach(row => {
                total++;
                const status = row.getAttribute('data-status');
                if (status === 'verified') verified++;
                if (status === 'processing') processing++;
                if (status === 'released') released++;
                if (status === 'completed') returned++;
                if (status === 'cancelled') rejected++;
            });

            document.getElementById('statTotal').innerText = total;
            document.getElementById('statVerified').innerText = verified;
            document.getElementById('statProcessing').innerText = processing;
            document.getElementById('statReleased').innerText = released;
            document.getElementById('statReturned').innerText = returned;
            document.getElementById('statRejected').innerText = rejected;
        }

        let liveReservationSignature = '';
        let liveReservationRequestPending = false;
        async function syncLiveReservations() {
            if (document.hidden || document.querySelector('.modal.show') || liveReservationRequestPending) return;
            liveReservationRequestPending = true;
            const liveUrl = '{{ route('admin.reservations.live') }}' + ({{ $walkInOnly ? 'true' : 'false' }} ? '?walkin=1' : '');
            try {
                const response = await fetch(liveUrl, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
                if (!response.ok) throw new Error('Admin reservation refresh failed: ' + response.status);
                const data = await response.json();
                const signature = JSON.stringify(data);
                if (signature === liveReservationSignature) return;
                liveReservationSignature = signature;
                serverReservations.splice(0, serverReservations.length, ...data);
                renderServerReservations();
                updateStatCounts();
                applyFilters();
            } catch (error) {
                console.error('Unable to refresh Admin reservations.', error);
            } finally {
                liveReservationRequestPending = false;
            }
        }
        syncLiveReservations();
        setInterval(syncLiveReservations, 5000);

        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('click', () => {
                const filter = card.getAttribute('data-filter');
                currentStatFilter = (currentStatFilter === filter) ? 'all' : filter;
                applyFilters();
            });
        });

        function clearFilter() {
            currentStatFilter = 'all';
            applyFilters();
        }

        function applyFilters() {
            const query = document.getElementById('controlSearchInput').value.trim().toUpperCase();
            const rows = document.querySelectorAll('#reservationTable tbody .res-row');
            const noResultMsg = document.getElementById('noResultMsg');
            const filterBanner = document.getElementById('filterBanner');
            const filterLabel = document.getElementById('filterLabel');
            let visibleCount = 0;

            const filterNames = {
                'all': '',
                'verified': 'Verified',
                'processing': 'Processing (Confirmed)',
                'released': 'Released',
                'returned': 'Returned',
                'cancelled': 'Cancelled'
            };

            rows.forEach(row => {
                const controlNumber = row.getAttribute('data-control').toUpperCase();
                const status = row.getAttribute('data-status');

                const matchesSearch = controlNumber.includes(query);
                let matchesStatFilter = true;
                if (currentStatFilter === 'verified') matchesStatFilter = status === 'verified';
                else if (currentStatFilter === 'processing') matchesStatFilter = status === 'processing';
                else if (currentStatFilter === 'released') matchesStatFilter = status === 'released';
                else if (currentStatFilter === 'returned') matchesStatFilter = status === 'completed';
                else if (currentStatFilter === 'cancelled') matchesStatFilter = status === 'cancelled';

                const isMatch = matchesSearch && matchesStatFilter;
                row.style.display = isMatch ? '' : 'none';
                if (isMatch) visibleCount++;
            });

            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.toggle('active-filter', card.getAttribute('data-filter') === currentStatFilter && currentStatFilter !== 'all');
            });

            if (currentStatFilter !== 'all') {
                filterBanner.classList.remove('d-none');
                filterLabel.innerText = filterNames[currentStatFilter];
            } else {
                filterBanner.classList.add('d-none');
            }

            noResultMsg.classList.toggle('d-none', visibleCount !== 0);
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>