<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - User Reservation</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>

        :root {
            --boss-red: #b71c1c;
            --boss-dark: #121212;
            --boss-grey: #e0e0e0;
        }

        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }

        .sidebar {
            width: 250px; height: 100vh; background-color: var(--boss-dark);
            position: fixed; padding: 20px; border-right: 4px solid var(--boss-red); z-index: 1000; display:flex; flex-direction:column; overflow-y:auto;
        }
        .nav-link {
            color: #ffffff; margin-bottom: 10px; border-radius: 10px;
            padding: 12px 15px; font-weight: 600; text-decoration: none; display: block;
        }
        .nav-link:hover, .nav-link.active { background-color: var(--boss-red); color: white !important; }
        .sidebar-footer { margin-top:auto; width:100%; border-top:1px solid #333; padding-top:15px; }
        .sidebar-footer a { color: #888; text-decoration: none; font-size: 0.85rem; }

        .main-content { margin-left: 250px; padding: 0; min-height: 100vh; }

        .top-nav {
            background-color: #ffffff; padding: 15px 30px; display: flex;
            justify-content: space-between; align-items: center; border-bottom: 1px solid #ccc;
            position: sticky; top: 0; z-index: 999;
        }
        .text-black-bold { color: #000000; font-weight: 800; }
        .text-boss-red { color: var(--boss-red); font-weight: 800; }

        .content-container { padding: 30px; }

        .option-card { border: 2px solid #eee; border-radius: 12px; padding: 15px; cursor: pointer; text-align: center; transition: 0.3s; }
        .option-card.active { border-color: var(--boss-red); background: #fff5f5; border-width: 2.5px; }
        .step-content { display: none; }
        .step-content.active { display: block; }
        .receipt-box { background: #fdfdfd; border: 1px dashed #aaa; padding: 15px; border-radius: 8px; }

        .car-info-tag { font-size: 0.75rem; background: #f8f9fa; padding: 4px 10px; border-radius: 50px; color: #666; font-weight: 600; }
        .car-info-tag.clickable { cursor: pointer; transition: 0.2s; }
        .car-info-tag.clickable:hover { background: #fff5f5; text-decoration: underline; }
        .car-info-tag.category-tag { background: #212529; color: #fff; }
        .specifications-button { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .65rem; border:1px solid #ced4da; border-radius:999px; background:#fff; color:#495057; font-size:.72rem; font-weight:700; line-height:1.2; transition:background-color .15s ease, border-color .15s ease, color .15s ease; }
        .specifications-button:hover, .specifications-button:focus-visible { border-color:#b71c1c; background:#fff5f5; color:#b71c1c; }
        .specifications-button:focus-visible { outline:3px solid rgba(183,28,28,.2); outline-offset:2px; }
        .rating-stars { color: #ffc107; font-size: 0.85rem; }
        .view-feedback { font-size: 0.7rem; color: #b71c1c; text-decoration: none; font-weight: bold; cursor: pointer; }

        .status-badge {
            font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; font-weight: bold;
            position: absolute; top: 15px; right: 15px; z-index: 10; text-transform: uppercase;
        }

        .btn-float {
            position: fixed; bottom: 30px; right: 30px; z-index: 1050;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); border: none; width: 60px; height: 60px; border-radius: 50%;
        }
        .btn-float-price {
            position: fixed; bottom: 100px; right: 30px; z-index: 1050;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); border: none; width: 60px; height: 60px; border-radius: 50%;
        }

        .calendar-table th { background: var(--boss-dark); color: white; text-align: center; }
        .calendar-table td { height: 100px; vertical-align: top; border: 1px solid #dee2e6; width: 14.28%; }
        .calendar-table td.today-cell { background: #fff3cd; box-shadow: inset 0 0 0 2px #ffc107; }
        .cal-date { font-weight: bold; margin-bottom: 5px; display: block; }
        .cal-event { font-size: 0.65rem; padding: 2px 5px; border-radius: 4px; margin-bottom: 2px; color: white; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
        .event-maintenance { background: #ffc107; color: #000; }
        .event-rented { background: #dc3545; }
        .event-reserved { background: #0d6efd; }
        .cal-more { display: block; width: 100%; border: 0; background: transparent; color: #495057; font-size: .68rem; font-weight: 700; text-align: left; padding: 2px 5px; }
        .cal-more:hover { color: var(--boss-red); text-decoration: underline; }
        .calendar-details-modal .modal-dialog { width: min(500px, calc(100vw - 1rem)); max-width: none; }
        .calendar-details-modal .modal-body { max-height: min(15rem, calc(100vh - 8rem)); overflow-y: auto; overscroll-behavior: contain; }
        .catalog-card-body { display: flex; flex-direction: column; height: 100%; }
        .catalog-card-footer { margin-top: auto; }

        .step-indicator { font-size: 0.85rem; font-weight: 800; color: var(--boss-red); letter-spacing: 1px; }
        .btn-next-step { border-radius: 50px; font-weight: bold; padding: 12px; margin-top: 20px; }
        .btn-back-link { color: #666; font-weight: bold; text-decoration: none; font-size: 0.9rem; }

        /* ===== Schedule banner sa card (same info as the admin fleet card) ===== */
        .card-date-banner { border-radius: 10px; padding: 8px 12px; font-size: 0.72rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .card-date-banner.reserved { background: #e7f1ff; color: #084298; }
        .card-date-banner.rented { background: #f8d7da; color: #842029; }
        .card-date-banner.maintenance { background: #fff3cd; color: #664d03; }
        .card-date-banner.selected { background: #cff4fc; color: #055160; }

        /* ===== Damage / Condition log (read-only for users) ===== */
        .damage-item { background: #fff5f5; border: 1px solid #f5c2c7; border-radius: 10px; padding: 12px 15px; margin-bottom: 10px; }
        .damage-item.repaired { background: #eefdf5; border-color: #b8e6c8; }
        .condition-mini-badge { font-size: 0.65rem; padding: 3px 9px; border-radius: 50px; font-weight: 700; margin-right: 5px; margin-top: 5px; display: inline-flex; align-items: center; gap: 5px; }
        .condition-mini-badge.checked { background: #eefdf5; color: #198754; border: 1px solid #b8e6c8; }
        .condition-mini-badge.unchecked { background: #f8f9fa; color: #999; border: 1px solid #eee; }

        /* ===== User feedback form ===== */
        .feedback-item { border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px; }
        .star-picker i { font-size: 1.3rem; color: #ffc107; cursor: pointer; margin-right: 3px; }
        .admin-reply { background: #f8f9fa; border-left: 3px solid var(--boss-red); border-radius: 8px; padding: 10px 14px; margin-top: 10px; }

        .empty-fleet { text-align: center; padding: 60px 20px; color: #aaa; }
        .mobile-sidebar-toggle { display:none; }
        .user-page-loader { position:fixed; inset:0; z-index:3000; display:none; align-items:center; justify-content:center; background:rgba(18,18,18,.5); }
        .user-page-loader.is-visible { display:flex; }
        .user-page-loader .car-loader-icon { font-size:3rem; color:#fff; animation:car-loader-drive .8s ease-in-out infinite alternate; }
        @keyframes car-loader-drive { from { transform:translateX(-12px); } to { transform:translateX(12px); } }
        @media (max-width: 991.98px) {
            .mobile-sidebar-toggle { display:block; position:fixed; top:10px; left:10px; z-index:1100; }
            .sidebar { transform:translateX(-100%); transition:transform .25s ease; overflow-y:auto; }
            .sidebar.is-open { transform:translateX(0); }
            .main-content { margin-left:0; }
            .top-nav { padding:12px 16px 12px 60px; }
            .content-container { padding:20px 16px 90px; }
        }
        @media (max-width: 575.98px) {
            .top-nav { gap:8px; padding:10px 10px 10px 56px; }
            .top-nav h4 { min-width:0; font-size:1rem; }
            .content-container { padding:14px 10px 90px; }
            .content-container > .card { padding:14px !important; }
            .btn-float { width:48px; height:48px; right:16px; bottom:16px; }
            .btn-float-price { width:48px; height:48px; right:16px; bottom:74px; }
            .modal-dialog { margin:.5rem; }
            .option-card { padding:11px 7px; font-size:.85rem; }
            .reservation-card,.booking-card { min-width:0; }
            .form-control,.form-select { font-size:16px; }
            .table-responsive { -webkit-overflow-scrolling:touch; }
            .step-content { padding-left:0; padding-right:0; }
            .modal-content { border-radius:16px; }
            .modal-body { padding:1rem !important; }
        }
    </style>
</head>
<body>    <button class="btn btn-danger btn-float" data-bs-toggle="modal" data-bs-target="#calendarModal">
        <i class="fas fa-calendar-alt fa-lg"></i>
    </button>

    <button class="btn btn-dark btn-float-price" data-bs-toggle="modal" data-bs-target="#priceGuideModal" title="Price Guide">
        <i class="fas fa-tags fa-lg"></i>
    </button>

    @include('user.partials.navigation', ['title' => 'RESERVATION'])
<div class="content-container">
            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-5 bg-white">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="small fw-bold mb-1">Search Car</label>
                        <input type="text" id="searchCar" class="form-control rounded-pill border-light bg-light" placeholder="Brand or Model">
                    </div>
                    <div class="col-md-4">
                        <label class="small fw-bold mb-1">Capacity Type</label>
                        <select id="filterCapacity" class="form-select rounded-pill border-light bg-light">
                            <option value="all">All Seaters</option>
                            <option value="4">4 Seater</option>
                            <option value="5">5 Seater</option>
                            <option value="6">6 Seater</option>
                            <option value="7">7 Seater</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-danger w-100 rounded-pill fw-bold" onclick="renderUserFleet()">SEARCH CAR</button>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0 text-uppercase small text-muted">Available Vehicles</h6>
                <button class="btn btn-outline-dark btn-sm rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#priceGuideModal">
                    <i class="fas fa-tags me-2"></i> VIEW PRICE GUIDE
                </button>
            </div>

            <div class="row g-4" id="userVehicleGrid">
                <!-- Rendered by JS mula sa parehong fleet data ng Admin Vehicle Management -->
            </div>

            <div id="emptyFleetMsg" class="empty-fleet d-none">
                <i class="fas fa-car-side fa-3x mb-3"></i>
                <p class="fw-bold mb-0">No vehicles found.</p>
                <p class="small">Subukan mong baguhin ang search o ang capacity filter.</p>
            </div>
        </div>
    </div>

    <div class="modal fade" id="calendarModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-calendar-alt me-2"></i> VEHICLE RENTAL SCHEDULE</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-outline-dark btn-sm" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                            <h4 class="fw-bold mb-0" id="calendarMonthYear">April 2026</h4>
                            <button class="btn btn-outline-dark btn-sm" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge bg-danger">Ongoing</span>
                            <span class="badge bg-primary">Reserved</span>
                            <span class="badge bg-warning text-dark">Maintenance</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered bg-white calendar-table">
                            <thead>
                                <tr><th>SUN</th><th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th></tr>
                            </thead>
                            <tbody id="calendarBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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

    <!-- ================= PRICE GUIDE MODAL (same reference rates as Admin) ================= -->
    <div class="modal fade" id="priceGuideModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0 text-uppercase"><i class="fas fa-tags me-2"></i>Big Boss <span class="text-boss-red">Price Guide</span></h5>
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
                                <h6 class="fw-bold text-boss-red small text-uppercase mb-3"><i class="fas fa-car me-2"></i>Sedan</h6>
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
                                <h6 class="fw-bold text-boss-red small text-uppercase mb-3"><i class="fas fa-shuttle-van me-2"></i>Pick-up / Expanded (7-Seater) (2 Days) — Gas</h6>
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
                                <h6 class="fw-bold text-boss-red small text-uppercase mb-3"><i class="fas fa-shuttle-van me-2"></i>Expanded — Diesel</h6>
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
                                <h6 class="fw-bold text-boss-red small text-uppercase mb-3"><i class="fas fa-clock me-2"></i>Hourly Rates</h6>
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

    <div class="modal fade" id="unitPickerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="unitPickerTitle">Vehicle Units</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="unitPickerBody" class="list-group list-group-flush"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="vehicleSpecificationsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="vehicleSpecificationsTitle">Vehicle Specifications</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="list-group list-group-flush" id="vehicleSpecificationsList"></div>
            </div>
        </div>
    </div>

    <!-- ================= DAMAGE / CONDITION LOG MODAL (read-only for users) ================= -->
    <div class="modal fade" id="damageLogModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0 text-uppercase"><i class="fas fa-tools me-2"></i>Condition / Damage Log <span id="dmgModalCarName" class="text-boss-red"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3"><i class="fas fa-info-circle me-1"></i>This is the condition/damage history logged by our staff for this unit.</p>
                    <div id="damageLogModalList"></div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-comments me-2"></i> Client Feedbacks <span id="fbModalCarName" class="text-boss-red"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="feedbackModalList"></div>

                    <hr>
                    <h6 class="fw-bold small text-uppercase mb-2"><i class="fas fa-pen me-1"></i>Leave Your Feedback</h6>
                    <div id="fbFormError"></div>
                    <label class="small fw-bold mb-1 d-block">Your Rating</label>
                    <div class="star-picker mb-2" id="fbStarPicker">
                        <i class="far fa-star" data-val="1" onclick="setFbRating(1)"></i>
                        <i class="far fa-star" data-val="2" onclick="setFbRating(2)"></i>
                        <i class="far fa-star" data-val="3" onclick="setFbRating(3)"></i>
                        <i class="far fa-star" data-val="4" onclick="setFbRating(4)"></i>
                        <i class="far fa-star" data-val="5" onclick="setFbRating(5)"></i>
                    </div>
                    <textarea id="fbComment" class="form-control mb-3" rows="2" placeholder="Share your experience with this vehicle..."></textarea>
                    <button class="btn btn-danger w-100 rounded-pill fw-bold" onclick="submitFeedback()"><i class="fas fa-paper-plane me-2"></i>Post Feedback</button>
                </div>
            </div>
        </div>
    </div>

   <div class="modal fade" id="rentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold" id="modalCarName">Reserve Vehicle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="{{ route('user.reservations.store') }}" enctype="multipart/form-data" id="reservationForm">
                    @csrf
                    <input type="hidden" name="vehicle_id" id="vehicle_id">
                    <input type="hidden" name="vehicle" id="vehicle">
                    <input type="hidden" name="rate_type" id="rate_type" value="city">
                    <input type="hidden" name="service_option" id="service_option" value="pickup">
                    <input type="hidden" name="driver_option" id="driver_option" value="self_drive">
                    <input type="hidden" name="payment_mode" id="payment_mode" value="full">

                <div id="step0" class="step-content active">
                    <h6 class="fw-bold mt-3 mb-3 text-danger"><i class="fas fa-file-contract"></i> Rental Agreement & Rules</h6>
                    <div class="bg-light p-3 border rounded mb-3 shadow-sm agreement-box" style="font-size: 0.85rem; height: 280px; overflow-y: scroll; line-height: 1.6;">
                        <p class="fw-bold text-dark">Please read carefully before proceeding:</p>
                        <ol class="ps-3">
                            <li class="mb-2"><strong>Document Requirements:</strong> Renter must provide <b>two (2) valid Government IDs</b> and a <b>Proof of Billing</b> (Electric/Water bill) under their name or immediate family.</li>
                            <li class="mb-2"><strong>Accidents & Damages:</strong> Any damage to the vehicle during the rental period is the <b>sole responsibility of the renter</b>. The renter shall pay for all repair costs and a "Loss of Use" fee equivalent to the daily rate while the vehicle is in the shop.</li>
                            <li class="mb-2"><strong>Liability:</strong> The Renter shall be held liable for any loss or damage to the Vehicle's parts, body, tools, or accessories, as well as any personal belongings of the Owner left inside the Vehicle, sustained during the rental period. The Renter shall likewise be liable for any traffic violations incurred while the Vehicle is in their possession; <b>Big Boss Car Rental</b> reserves the right to collect the equivalent penalty from the Renter even after the rental period has ended. Use of the Vehicle for illegal purposes, towing, or driving instruction/training is <b>strictly PROHIBITED</b>. Should the Renter travel outside the agreed place or province, or exceed the agreed rental time, without prior arrangement with the Owner, additional charges equivalent to the extra mileage and/or time used shall apply.</li>
                            <li class="mb-2"><strong>Payment Policy:</strong> We strictly implement a <b>"Pay Before Drive"</b> policy. A ₱1,000 security deposit is required for reservation. Online payments are accepted via <b>GCash only</b>.</li>
                            <li class="mb-2"><strong>Late Returns:</strong> Overtime returns will be charged an additional hourly rate. If the delay exceeds 5 hours, a full day's rate will be applied.</li>
                            <li class="mb-2"><strong>Carnapping Policy:</strong> Failure to return the vehicle or contact the management within 24 hours of the deadline will be reported to the <b>PNP-HPG as a Carnapping/Theft case</b>.</li>
                            <li class="mb-2"><strong>Maintenance:</strong> The renter must ensure the vehicle has enough oil and coolant. Negligence leading to engine failure will be charged to the renter.</li>
                        </ol>
                    </div>
                    <div id="step0-error-area"></div> <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="mainAgree">
                        <label class="form-check-label small fw-bold text-dark" for="mainAgree">I AGREE to the terms and understand the rules.</label>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">DISAGREE</button></div>
                        <div class="col-6"><button type="button" class="btn btn-danger w-100 fw-bold" onclick="startBooking()">I AGREE</button></div>
                    </div>
                </div>

                <div id="step1" class="step-content">
                    <span class="step-indicator">STEP 1 OF 6</span>
                    <h6 class="fw-bold mt-3 mb-3">Service Option</h6>
                    <div class="row g-2 mb-4">
                        <div class="col-6"><div class="option-card active" id="opt-pickup" onclick="setService('pickup')">Pickup at Garage</div></div>
                        <div class="col-6"><div class="option-card" id="opt-deliver" onclick="setService('delivery')">Deliver to Me</div></div>
                    </div>
                    <div id="pickup-info" class="p-3 bg-light border rounded"><label class="small fw-bold">Garage Address:</label><p class="mb-0 small">Big Boss Garage, GMA, Cavite</p></div>
                    <div id="delivery-info" style="display: none;">
                        <label class="small fw-bold mb-2 text-danger">Delivery Address</label>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="small fw-bold" for="deliveryProvince">Province</label>
                                <select class="form-select" id="deliveryProvince" name="delivery_province"><option value="">Select Province</option></select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="small fw-bold" for="deliveryCity">City / Municipality</label>
                                <select class="form-select" id="deliveryCity" name="delivery_city" disabled><option value="">Select City / Municipality</option></select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="small fw-bold" for="deliveryBarangay">Barangay</label>
                                <select class="form-select" id="deliveryBarangay" name="delivery_barangay" disabled><option value="">Select Barangay</option></select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="small fw-bold" for="deliveryStreet">Landmark / Street</label>
                                <input type="text" class="form-control" id="deliveryStreet" name="delivery_street" maxlength="1000" placeholder="Block & Lot, street, o pamilyar na drop-off point">
                            </div>
                            <div class="col-12">
                                <label class="small fw-bold" for="deliveryNotes">Delivery Notes <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea class="form-control" id="deliveryNotes" name="delivery_notes" rows="2" maxlength="1000" placeholder="Building, gate, or other instructions"></textarea>
                            </div>
                        </div>
                    </div>
                    <div id="step1-error-area"></div>
                    <button type="button" class="btn btn-danger w-100 btn-next-step" id="deliveryNextStep" onclick="validateStep1()">NEXT: DRIVE OPTION</button>
                </div>

                <div id="step2" class="step-content">
                    <div class="d-flex justify-content-between"><a href="javascript:void(0)" class="btn-back-link" onclick="changeStep(1)"><i class="fas fa-chevron-left"></i> BACK</a><span class="step-indicator">STEP 2 OF 6</span></div>
                    <h6 class="fw-bold mt-3 mb-3">Drive Option</h6>
                    <div class="row g-2 mb-4">
                        <div class="col-6"><div class="option-card active" id="d-self" onclick="setDriver(0)">Self Drive</div></div>
                        <div class="col-6"><div class="option-card" id="d-with" onclick="setDriver(1500)">With Driver (+₱1,500)</div></div>
                    </div>

                    <label class="small fw-bold mb-2" for="rateType">Rental Location / Rate</label>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill fw-bold mb-2" onclick="toggleRentalLocationGuide(event)" aria-expanded="false" aria-controls="rentalLocationGuidePanel"><i class="fas fa-map-marked-alt me-1"></i>VIEW GUIDE</button>
                    <div id="rentalLocationGuidePanel" class="alert alert-light border border-danger rounded-4 mb-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-map-marked-alt text-danger me-1"></i>Rental Location Guide</h6>
                            <button type="button" class="btn-close" aria-label="Close" onclick="toggleRentalLocationGuide(event)"></button>
                        </div>
                        <div class="mb-3"><h6 class="fw-bold text-danger">🚗 City Driving</h6><p class="small mb-0">GMA · Carmona · Biñan · San Pedro · Dasmariñas · Silang · General Trias · Imus · Bacoor · Muntinlupa · Parañaque · Pasay · Makati · Taguig</p></div>
                        <div class="mb-3"><h6 class="fw-bold text-primary">🛣️ Province</h6><p class="small mb-0">Tagaytay · Batangas · Laguna · Rizal · Cavite farther areas · Quezon</p></div>
                        <div><h6 class="fw-bold text-success">🚌 Long Distance (2 Days)</h6><p class="small mb-0">Baguio · La Union · Vigan · Ilocos · Nueva Ecija · Pangasinan · Bicol · Legazpi</p></div>
                    </div>
                    <select id="rateType" class="form-select mb-3" onchange="setRateType(this.value)">
                        <option value="city">City Driving</option>
                        <option value="province">Province</option>
                        <option value="long_distance">Long Distance (2 Days minimum)</option>
                    </select>
                    <div id="ratePreview" class="small text-danger fw-bold mb-3"></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="small fw-bold">Pickup Date</label>
                            <input type="date" id="startDate" name="pickup_date" class="form-control" min="{{ now()->toDateString() }}" onchange="syncReturnDateFromPickup(); updateDateLimits(); updateCompute()" required>
                            <input type="time" id="startTime" name="pickup_time" class="form-control mt-2" onchange="syncReturnTimeFromPickup(); syncReturnDateFromPickup(); updateDateLimits(); updateCompute()" required>
                        </div>

                        <div class="col-6">
                            <label class="small fw-bold">Return Date</label>
                            <input type="date" id="endDate" name="return_date" class="form-control" min="{{ now()->toDateString() }}" onchange="updateDateLimits(); updateCompute()" required>
                            <input type="time" id="endTime" class="form-control mt-2" disabled required>
                            <input type="hidden" id="returnTimeValue" name="return_time">
                        </div>
                    </div>
                    <div id="dateLimitHint" class="alert alert-warning py-2 px-3 mb-3 small d-none">
                        <div class="fw-bold"></div>
                        <div class="mt-1 fw-normal"><i class="fas fa-info-circle me-1"></i>You may request an extension if you need to rent the vehicle longer.</div>
                    </div>
                    <div id="selectedDatePreview" class="alert alert-info py-2 px-3 small fw-bold mb-3 d-none"></div>

                    <div id="step2-error-area"></div>
                    <button type="button" class="btn btn-danger w-100 btn-next-step" onclick="validateStep2()">NEXT: UPLOAD DOCUMENTS</button>
                </div>

                <div id="step3" class="step-content">
                    <div class="d-flex justify-content-between"><a href="javascript:void(0)" class="btn-back-link" onclick="changeStep(2)"><i class="fas fa-chevron-left"></i> BACK</a><span class="step-indicator">STEP 3 OF 6</span></div>
                    <h6 class="fw-bold mt-3 mb-3">Verification Documents</h6>

                    <div id="step3-error-area"></div>
                    @if($hasExistingDocuments)
                        <div id="uploadFields" class="alert alert-success small fw-bold">
                            <i class="fas fa-check-circle me-2"></i>Your verification documents are already on file. You do not need to upload them again.
                        </div>
                    @else
                    <div id="uploadFields">
                        <div class="mb-2"><label class="small fw-bold text-dark">Driver's License / ID <span class="text-danger">*</span></label><input type="file" id="doc-license" name="driver_license" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif"></div>
                        <div class="mb-2"><label class="small fw-bold text-dark">Government ID <span class="text-danger">*</span></label><input type="file" id="doc-id" name="valid_id" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif"></div>
                        <div class="mb-2"><label class="small fw-bold text-dark">Proof of Billing <span class="text-danger">*</span></label><input type="file" id="doc-billing" name="proof_of_billing" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif"></div>
                    </div>
                    @endif

                    <button type="button" class="btn btn-danger w-100 btn-next-step text-uppercase fw-bold" onclick="validateStep3()">Next: Data Privacy</button>
                </div>

                <div id="step4" class="step-content">
                    <div class="d-flex justify-content-between"><a href="javascript:void(0)" class="btn-back-link" onclick="changeStep(3)"><i class="fas fa-chevron-left"></i> BACK</a><span class="step-indicator">STEP 4 OF 6</span></div>
                    <h6 class="fw-bold mb-3 mt-3 text-danger"><i class="fas fa-user-shield"></i> Data Privacy Consent</h6>
                    <div class="bg-light p-3 border rounded mb-3 shadow-sm" style="font-size: 0.9rem; line-height: 1.5;">
                        <p class="mb-2 text-dark">In compliance with the <b>Data Privacy Act of 2012</b>, I hereby give my explicit consent to <b>Big Boss Car Rental</b> to collect and store my info for verification.</p>
                    </div>

                    <div id="step4-error-area"></div> <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="privacy_consent" value="1" id="privacyAgree"><label class="form-check-label fw-bold text-dark" for="privacyAgree">I AGREE and provide my consent.</label></div>
                    <button type="button" class="btn btn-danger w-100 btn-next-step fw-bold" onclick="validateStep4()">PROCEED TO PAYMENT</button>
                </div>

                <div id="step5" class="step-content">
                    <div class="d-flex justify-content-between"><a href="javascript:void(0)" class="btn-back-link" onclick="changeStep(4)"><i class="fas fa-chevron-left"></i> BACK</a><span class="step-indicator">STEP 5 OF 6</span></div>
                    <h6 class="fw-bold mt-3 mb-3">Payment Selection</h6>

                    <div class="row g-2 mb-3">
                        <div class="col-4"><div class="option-card active" id="p-full" onclick="setPay('full')">Full Payment</div></div>
                        <div class="col-4"><div class="option-card" id="p-dep" onclick="setPay('dep')">Deposit Only (₱1,000)</div></div>
                        <div class="col-4"><div class="option-card" id="p-walk" onclick="setPay('walkin')">Walk-in / Cash</div></div>
                    </div>

                    <div class="receipt-box mb-3 small">
                        <div class="d-flex justify-content-between"><span>Rent Fee (<span id="rec-days">1</span> day/s):</span><span id="rec-rent">₱0</span></div>
                        <div class="d-flex justify-content-between"><span>Driver:</span><span id="rec-driver">₱0</span></div>
                        <div id="securityDepositRow" class="d-flex justify-content-between"><span>Security Deposit:</span><span>₱1,000</span></div>
                        <hr><div class="d-flex justify-content-between fw-bold text-danger"><span>PAY NOW:</span><span id="rec-now">₱0</span></div>
                    </div>

                    <div id="payment-details-area">
                        <div id="gcash-fields">
                            <div class="text-center bg-light p-2 border rounded mb-2">
                                <p class="small fw-bold mb-1">GCash QR</p>
                                @if($paymentSettings->gcash_qr_path)
                                    <img src="{{ asset('storage/'.$paymentSettings->gcash_qr_path) }}" alt="GCash QR Code" style="width: 180px; height: 180px; object-fit: cover; border-radius: 5px;">
                                @else
                                    <div class="bg-primary mx-auto text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 5px;">QR</div>
                                @endif
                                <p class="small mb-0 mt-2">
                                    Account Name: <strong>{{ $paymentSettings->account_name ?: 'BIG BOSS RENTAL' }}</strong><br>
                                    GCash Number: <strong>{{ $paymentSettings->account_number ?: '0912 345 6789' }}</strong>
                                </p>
                            </div>
                            @error('gcash_payment')
                                <div class="alert alert-danger py-2 small">{{ $message }}</div>
                            @enderror
                            <div class="mb-2"><label class="small fw-bold">GCash Screenshot <span class="text-muted fw-normal">(optional if GCash Ref is provided)</span></label><input type="file" name="gcash_screenshot" class="form-control form-control-sm" accept=".jpg,.jpeg,.png"></div>
                            <label class="small fw-bold">GCash Ref <span class="text-muted fw-normal">(or upload a screenshot)</span></label>
                            <input type="text" name="gcash_reference" class="form-control mb-3" placeholder="GCash Ref" inputmode="numeric" maxlength="17">
                        </div>

                        <div id="address-fields" class="d-none">
                            <div class="alert alert-warning border-warning">
                                <p class="small fw-bold mb-1 text-dark"><i class="fas fa-map-marker-alt"></i> OFFICE/GARAGE ADDRESS:</p>
                                <p class="small mb-0 text-dark">
                                    #123 Sample Street, Brgy. Peace, <br>
                                    Cebu City, Philippines (Near SM City)
                                </p>
                                <hr class="my-1">
                                <p class="x-small mt-2 mb-0 italic text-muted">*Reservation will only be valid for 12 hours without payment.</p>
                            </div>
                        </div>
                    </div>

                    <div id="step5-error-area"></div>
                    <button type="button" class="btn btn-danger w-100 btn-next-step" onclick="validateStep5()">NEXT: FINAL CONFIRMATION</button>
                </div>

                <div id="step6" class="step-content">
                    <div class="d-flex justify-content-between"><a href="javascript:void(0)" class="btn-back-link" onclick="changeStep(5)"><i class="fas fa-chevron-left"></i> BACK</a><span class="step-indicator">STEP 6 OF 6</span></div>
                    <div id="step6-error-area"></div>
                    <div id="userBookingReviewPanel" class="alert alert-light border border-danger rounded-4 d-none">
                        <h6 class="fw-bold text-danger mb-3"><i class="fas fa-clipboard-check me-1"></i>Double-check Booking Details</h6>
                        <div id="userBookingReviewContent" class="small"></div>
                        <button type="button" class="btn btn-link text-danger fw-bold p-0 mt-3" onclick="hideUserBookingReview()">← Edit Details</button>
                    </div>
                    <div id="finalAgreementArea" class="d-none">
                        <div class="alert alert-warning small mb-2">The ₱1,000 security deposit is <b>NON-REFUNDABLE</b> once confirmed.</div>
                        <p class="small fw-bold mb-2 text-dark">FOR WALK-IN / CASH</p>
                        <div class="alert alert-warning small mb-3">Reservation will only be valid for <b>12 hours</b> payment.</div>
                        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="final_agreement" value="1" id="cancelAgree"><label class="form-check-label fw-bold text-dark" for="cancelAgree">I understand and agree.</label></div>
                        <button type="submit" id="finishReservationButton" class="btn btn-success w-100 btn-next-step" onclick="return finalReservationCheck()">FINISH RESERVATION</button>
                    </div>
                </div>
                    </form>

                <div id="step7" class="step-content text-center py-3">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3.5rem;"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Reservation Confirmed!</h5>
                    <p class="small text-muted mb-3">Present this Control Number sa garage o sabihin ito sa staff para i-verify ang booking mo.</p>

                    <div class="bg-light border border-dashed rounded-4 p-3 mb-3" style="border-style: dashed !important;">
                        <p class="x-small fw-bold text-muted mb-1 text-uppercase">Control Number</p>
                        <h3 class="fw-bold text-boss-red mb-0" id="controlNumberDisplay" style="letter-spacing: 2px;">BD-00000000-0000</h3>
                    </div>

                    <button class="btn btn-outline-dark w-100 mb-2 rounded-pill fw-bold" onclick="copyControlNumber()">
                        <i class="fas fa-copy me-1"></i> <span id="copyBtnText">Copy Number</span>
                    </button>
                    <button class="btn btn-danger w-100 rounded-pill fw-bold" data-bs-dismiss="modal">DONE</button>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="user-page-loader" id="userPageLoader" aria-hidden="true">
    <div class="text-center text-white">
        <div role="status" aria-live="polite"><i class="fas fa-car-side car-loader-icon" aria-hidden="true"></i><span class="visually-hidden">Loading...</span></div>
        <div class="small fw-bold mt-2">Loading...</div>
    </div>
</div>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
   document.addEventListener('DOMContentLoaded', function () {
       const sidebar = document.querySelector('.sidebar');
       const toggle = document.getElementById('userSidebarToggle');
       const backdrop = document.getElementById('userSidebarBackdrop');
       const loader = document.getElementById('userPageLoader');
       const closeSidebar = function () {
           sidebar?.classList.remove('is-open');
           backdrop?.classList.add('d-none');
       };
       toggle?.addEventListener('click', function () {
           sidebar?.classList.toggle('is-open');
           backdrop?.classList.toggle('d-none');
       });
       backdrop?.addEventListener('click', closeSidebar);
       document.querySelectorAll('.sidebar a[href]').forEach(function (link) {
           link.addEventListener('click', function () {
               const href = link.getAttribute('href');
               if (!href || href === '#' || href.startsWith('#') || link.target === '_blank') return;
               if (loader) {
                   loader.classList.add('is-visible');
                   loader.setAttribute('aria-hidden', 'false');
               }
           });
       });
       document.querySelectorAll('form').forEach(function (form) {
           form.addEventListener('submit', function () {
               if (form.hasAttribute('data-no-page-loader') || !loader) return;
               loader.classList.add('is-visible');
               loader.setAttribute('aria-hidden', 'false');
           });
       });
   });
    // =====================================================================
    // SHARED FLEET DATA — EXACT same shape/values as `fleet` at
    // `scheduleEvents` sa Admin-Vehicle Management. Iisa lang dapat ang
    // pinagmumulan ng sasakyan ng Admin at User.
    //
    // TODO (backend): palitan ito ng isang API call sa Laravel
    // (GET /api/vehicles at GET /api/vehicle-schedule) na kapareho rin ng
    // ginagamit ng Admin Vehicle Management, para awtomatikong lumalabas
    // dito ang anumang idagdag/i-edit/i-log ng admin o staff.
    // =====================================================================
    let fleet = [
        { id: 1, name: 'Toyota Vios 2023', plate: 'GAS-123', price: 1500, category: 'Sedan', transmission: 'Manual', fuel: 'Gasoline', capacity: '5', status: 'Available', photo: '',
          rating: 4.5, timesRented: 45,
          feedbacks: [
              { name: 'Juan Dela Cruz', date: '2 days ago', stars: 5, comment: 'Sobrang linis ng sasakyan at malamig ang aircon. Highly recommended!', adminReply: null },
              { name: 'Maria Clara', date: '1 week ago', stars: 4, comment: 'Mabait yung owner at on-time nadeliver yung unit. Thank you BossDrive!', adminReply: null }
          ],
          damageLog: [
              { id: 1, date: 'Mar 12, 2026', part: 'Front Bumper', description: 'Minor scratch on left side of front bumper.', reportedBy: 'Staff Mika', status: 'Repaired', hasDamage: true,
                checklist: { fuel: true, exterior: true, interior: true, tools: false } }
          ]
        },
        { id: 2, name: 'Mitsubishi Xpander', plate: 'BOS-888', price: 2500, category: 'Expanded - Diesel', transmission: 'Automatic', fuel: 'Diesel', capacity: '7', status: 'Rented', photo: '',
          rating: 4.0, timesRented: 32,
          feedbacks: [
              { name: 'Pedro Santos', date: '3 weeks ago', stars: 4, comment: 'Maayos ang biyahe, sana mas mababa ang deposit.', adminReply: null }
          ],
          damageLog: []
        },
        { id: 3, name: 'Honda Civic', plate: 'CVC-441', price: 1800, category: 'Sedan', transmission: 'Automatic', fuel: 'Gasoline', capacity: '5', status: 'Maintenance', photo: '',
          rating: 4.0, timesRented: 18,
          feedbacks: [],
          damageLog: [
              { id: 2, date: 'Apr 22, 2026', part: 'Engine', description: 'Check engine light triggered, needs diagnostic and oil change.', reportedBy: 'Admin Patrick', status: 'Under Repair', hasDamage: true,
                checklist: { fuel: true, exterior: false, interior: false, tools: false } }
          ]
        },
        { id: 4, name: 'Toyota Fortuner', plate: 'FTR-909', price: 3200, category: 'Expanded - Diesel', transmission: 'Automatic', fuel: 'Diesel', capacity: '7', status: 'Available', photo: '',
          rating: 4.8, timesRented: 52,
          feedbacks: [
              { name: 'Liza Reyes', date: '5 days ago', stars: 5, comment: 'Sobrang comfortable, sulit sa malayong biyahe!', adminReply: null }
          ],
          damageLog: [
              { id: 3, date: 'Jul 30, 2026', part: '', description: '', reportedBy: 'Staff Mika', status: 'No Damage', hasDamage: false,
                checklist: { fuel: true, exterior: true, interior: true, tools: true } }
          ]
        }
    ];

    let scheduleEvents = [
        { id: 1, vehicleId: 3, type: 'Maintenance', start: '2026-04-24', end: '2026-04-26', notes: 'Check engine light — diagnostic & oil change' },
        { id: 2, vehicleId: 2, type: 'Rented',      start: '2026-04-15', end: '2026-04-18', notes: 'Ongoing rental' },
        { id: 3, vehicleId: 4, type: 'Reserved',    start: '2026-04-27', end: '2026-04-30', notes: 'Advance reservation' }
    ];

    // ================= SERVER DATA (kapag naka-connect na sa Laravel) =================
    // Kapag may ipinasang $vehicles / $schedule ang controller, sila ang
    // gagamitin kapalit ng sample data sa itaas. Normalized muna para
    // tugma sa shape na ginagamit ng Admin page.
    const serverVehicles = @json($vehicles ?? []);
    const serverSchedule = @json($schedule ?? []);
    const hasExistingDocuments = @json($hasExistingDocuments ?? false);

    function normalizeStatus(value) {
        const s = String(value || 'Available').toLowerCase();
        if (s === 'rented' || s === 'ongoing') return 'Rented';
        if (s === 'maintenance') return 'Maintenance';
        if (s === 'unavailable') return 'Unavailable';
        return 'Available';
    }

    if (serverVehicles.length) {
        fleet = serverVehicles.map(function (v, index) {
            return {
                id: v.id || (index + 1),
                name: v.name || 'Unnamed Unit',
                brand_name: v.brand_name || '',
                plate: v.plate || '',
                price: Number(v.price) || 1500,
                category: v.category || '',
                transmission: v.transmission || 'Manual',
                fuel: v.fuel || 'Gasoline',
                capacity: String(v.capacity || '5'),
                capacity_type: v.capacity_type || (String(v.capacity || '5') + ' Seater'),
                status: normalizeStatus(v.status),
                photo: v.image_path ? '{{ asset('storage') }}/' + v.image_path : (v.photo || ''),
                rating: Number(v.rating) || 0,
                timesRented: Number(v.times_rented || v.timesRented) || 0,
                feedbacks: v.feedbacks || [],
                damageLog: v.damage_log || v.damageLog || [],
                schedule: v.schedule || [],
                currentReservation: v.currentReservation || null
            };
        });
    }

    if (serverSchedule.length) {
        scheduleEvents = serverSchedule.map(function (ev, index) {
            return {
                id: ev.id || (index + 1),
                vehicleId: ev.vehicle_id || ev.vehicleId,
                reservationId: ev.reservation_id || ev.reservationId,
                type: ev.type || 'Reserved',
                start: ev.start || ev.start_date,
                end: ev.end || ev.end_date,
                notes: ev.notes || ''
            };
        });
    }
    if (serverVehicles.length && !serverSchedule.length) {
        scheduleEvents = fleet.reduce(function (events, vehicle) {
            return events.concat((vehicle.schedule || []).map(function (ev) {
                return {
                    id: ev.id,
                    vehicleId: ev.vehicleId || vehicle.id,
                    reservationId: ev.reservationId,
                    type: ev.type || 'Reserved',
                    start: ev.start,
                    end: ev.end,
                    notes: ev.notes || ''
                };
            }));
        }, []);
    }
    scheduleEvents = Array.from(scheduleEvents.filter(function (entry) {
        return entry.type === 'Maintenance' || (entry.reservationId && ['Reserved', 'Rented'].includes(entry.type));
    }).reduce(function (entries, entry) {
        const key = entry.reservationId
            ? 'reservation:' + entry.reservationId
            : 'manual:' + entry.vehicleId + ':' + entry.id;
        entries.set(key, entry);
        return entries;
    }, new Map()).values());

    let liveAvailabilitySignature = '';
    async function syncUserVehicleAvailability() {
        if (document.hidden || document.querySelector('.modal.show')) return;

        try {
            const response = await fetch('{{ route('vehicles.availability') }}', {
                headers: {'Accept': 'application/json'},
                cache: 'no-store'
            });
            if (!response.ok) throw new Error('Vehicle availability refresh failed: ' + response.status);
            const data = await response.json();
            const signature = JSON.stringify(data);
            if (signature === liveAvailabilitySignature) return;
            liveAvailabilitySignature = signature;

            const liveVehicles = new Map(data.vehicles.map(function (vehicle) {
                return [Number(vehicle.id), vehicle];
            }));
            fleet.forEach(function (vehicle) {
                const live = liveVehicles.get(Number(vehicle.id));
                if (!live) return;
                vehicle.status = normalizeStatus(live.status);
                vehicle.currentReservation = live.currentReservation;
                vehicle.schedule = (live.schedule || []).map(function (entry) {
                    return Object.assign({}, entry, {
                        type: entry.type === 'Special' ? 'Reserved' : entry.type,
                        vehicleId: Number(entry.vehicleId || vehicle.id)
                    });
                });
            });
            scheduleEvents = fleet.reduce(function (events, vehicle) {
                return events.concat((vehicle.schedule || []).map(function (entry) {
                    return Object.assign({}, entry, {vehicleId: Number(entry.vehicleId || vehicle.id)});
                }));
            }, []).filter(function (entry) {
                return entry.type === 'Maintenance' || (entry.reservationId && ['Reserved', 'Rented'].includes(entry.type));
            });
            renderUserFleet();
            renderCalendar();
        } catch (error) {
            console.error('Unable to refresh live vehicle availability.', error);
        }
    }

    // ================= STATE =================
    let driverFee = 0;
    let payMode = 'full';
    let currentPrice = 0;
    let baseVehiclePrice = 0;
    let currentVehicleId = null;
    const currentMonth = new Date();
    let currentViewDate = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1);
    let isReturningRenter = false;
    let currentControlNumber = "";

    const conditionChecklistFields = [
        { key: 'fuel',      label: 'Fuel Level' },
        { key: 'exterior',  label: 'Exterior/Body' },
        { key: 'interior',  label: 'Interior' },
        { key: 'tools',     label: 'Tools/Accessories' }
    ];

    const scheduleTypeClass = {
        'Maintenance': 'event-maintenance',
        'Rented': 'event-rented',
        'Reserved': 'event-reserved'
    };

    // ================= HELPERS =================
    function escapeHtml(value) {
        return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
        });
    }

    function todayISO() {
        const d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function toISODate(y, m, d) {
        return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
    }

    function vehicleById(id) {
        return fleet.find(function (v) { return v.id === id; });
    }

    function vehicleNameById(id) {
        const v = vehicleById(id);
        if (!v) return 'Unknown Vehicle';
        const unitNumber = fleet.filter(function (unit) { return unit.name === v.name; })
            .findIndex(function (unit) { return unit.id === v.id; }) + 1;
        return v.name + ' Unit #' + unitNumber;
    }

    function renderStars(rating) {
        let html = '';
        for (let i = 1; i <= 5; i++) {
            if (rating >= i) html += '<i class="fas fa-star"></i>';
            else if (rating >= i - 0.5) html += '<i class="fas fa-star-half-alt"></i>';
            else html += '<i class="far fa-star"></i>';
        }
        return html;
    }

    // Kaparehong format ng admin: "April 27 - 30, 2026" o
    // "April 27, 2026 - May 2, 2026" kapag iba ang buwan.
    function formatDateRange(startISO, endISO) {
        const start = new Date(startISO + 'T00:00:00');
        const end = new Date(endISO + 'T00:00:00');
        if (start.getFullYear() === end.getFullYear() && start.getMonth() === end.getMonth()) {
            return start.toLocaleString('default', { month: 'long' }) + ' ' + start.getDate() + ' - ' + end.getDate() + ', ' + end.getFullYear();
        }
        return start.toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' }) + ' - ' +
               end.toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
    }

    // Hinahanap ang pinakabagong schedule entry ng unit na may kaugnayan pa
    // sa kasalukuyang petsa. Mahalaga ang reverse order dahil ang bagong
    // booking ay idinadagdag sa dulo ng vehicle schedule.
    function findVehicleScheduleEvent(vehicleId, type) {
        const today = todayISO();
        const matches = scheduleEvents.filter(function (ev) {
            return ev.vehicleId === vehicleId && (!type || ev.type === type) && ev.end >= today;
        });
        if (!matches.length) return null;
        return matches.slice().reverse().find(function (ev) {
            return today >= ev.start && today <= ev.end;
        }) || matches[matches.length - 1];
    }

    // Ang badge na nakikita ng user. Ang Reserved ay galing sa schedule
    // entry, dahil ang admin status ay Available/Rented/Maintenance lang.
    function displayStatus(v) {
        if (v.status === 'Unavailable') return 'Unavailable';
        if (v.status === 'Rented') {
            return v.currentReservation && v.currentReservation.status === 'released'
                ? 'Ongoing Rental'
                : 'Reserved';
        }
        if (v.status === 'Maintenance') return 'Maintenance';
        if (findVehicleScheduleEvent(v.id, 'Reserved')) return 'Reserved';
        return 'Available';
    }

    function statusBadgeClass(label) {
        if (label === 'Available') return 'bg-success text-white';
        if (label === 'Reserved') return 'bg-primary text-white';
        if (label === 'Maintenance') return 'bg-warning text-dark';
        if (label === 'Unavailable') return 'bg-secondary text-white';
        return 'bg-danger text-white';
    }

    function renderScheduleBanner(v, label) {
        const reservationDates = v.currentReservation &&
            v.currentReservation.pickup_date &&
            v.currentReservation.return_date
            ? formatDateRange(v.currentReservation.pickup_date, v.currentReservation.return_date)
            : null;
        let ev = null;
        if (label === 'Ongoing Rental') ev = findVehicleScheduleEvent(v.id, 'Rented');
        else if (label === 'Maintenance') ev = findVehicleScheduleEvent(v.id, 'Maintenance');
        else if (label === 'Reserved') ev = findVehicleScheduleEvent(v.id, 'Reserved');
        if (!ev) return '';

        const range = escapeHtml(reservationDates || formatDateRange(ev.start, ev.end));
        if (label === 'Reserved') {
            return '<div id="schedule-banner-' + v.id + '" class="card-date-banner reserved"><i class="fas fa-calendar-check"></i><span>Reserved for: <b>' + range + '</b></span></div>';
        }
        if (label === 'Ongoing Rental') {
            return '<div id="schedule-banner-' + v.id + '" class="card-date-banner rented"><i class="fas fa-car-side"></i><span>Current rental ends: <b>' + range + '</b></span></div>';
        }
        return '<div id="schedule-banner-' + v.id + '" class="card-date-banner maintenance"><i class="fas fa-tools"></i><span>Under Maintenance: <b>' + range + '</b></span></div>';
    }

    function vehiclePhoto(v, index) {
        if (v.photo && String(v.photo).trim() !== '') return v.photo;
        return '{{ asset('image') }}/car' + ((index % 4) + 1) + '.png';
    }

    function displayReservationCategory(category) {
        const normalized = String(category || '').toLowerCase();
        if (normalized.includes('diesel')) return 'Diesel';
        if (normalized.includes('pick-up') || normalized.includes('pickup')) return 'Pick-up / Expanded';
        return category || '';
    }

    // ================= RENDER VEHICLE GRID =================
    const selectedUnits = {};
    const requestedVehicleId = Number(@json($selectedVehicleId ?? 0));
    const requestedUnit = fleet.find(function (unit) { return unit.id === requestedVehicleId; });
    if (requestedUnit) selectedUnits[requestedUnit.name] = requestedUnit.id;

    function unitStatusLabel(unit) {
        return displayStatus(unit);
    }

    function openUnitsModal(modelName) {
        const units = fleet.filter(function (unit) { return unit.name === modelName; });
        const selectedId = selectedUnits[modelName] || units.find(function (unit) {
            return unitStatusLabel(unit) === 'Available';
        })?.id || units[0]?.id;
        selectedUnits[modelName] = selectedId;

        document.getElementById('unitPickerTitle').textContent = modelName + ' Units';
        document.getElementById('unitPickerBody').innerHTML = units.map(function (unit, index) {
            const status = unitStatusLabel(unit);
            const active = unit.id === selectedId;
            const dates = unit.currentReservation
                ? '<small class="d-block text-muted">Reserved: ' + escapeHtml(formatDateRange(unit.currentReservation.pickup_date, unit.currentReservation.return_date)) + '</small>'
                : '';
            return '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ' + (active ? 'active' : '') + '" data-model-name="' + escapeHtml(modelName) + '" data-unit-id="' + unit.id + '" onclick="selectVehicleUnitFromButton(this)">' +
                '<span class="text-start"><strong>' + escapeHtml(modelName) + ' #' + (index + 1) + '</strong><small class="d-block">Plate: ' + escapeHtml(unit.plate || 'Not assigned') + '</small><small class="d-block text-muted">' + escapeHtml([displayReservationCategory(unit.category), unit.transmission, unit.fuel, unit.capacity_type || (unit.capacity ? unit.capacity + ' Seater' : '')].filter(Boolean).join(' · ') || 'Vehicle details not provided') + '</small>' + dates + '</span>' +
                '<span class="badge ' + (status === 'Available' ? 'text-bg-success' : (status === 'Maintenance' ? 'text-bg-warning' : 'text-bg-primary')) + '">' + escapeHtml(status) + '</span>' +
                '</button>';
        }).join('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('unitPickerModal')).show();
    }

    function openUnitsModalFromButton(button) {
        openUnitsModal(button.dataset.modelName);
    }

    function selectVehicleUnitFromButton(button) {
        const modelName = button.dataset.modelName;
        const unitId = Number(button.dataset.unitId);
        selectedUnits[modelName] = unitId;
        renderUserFleet();
        bootstrap.Modal.getInstance(document.getElementById('unitPickerModal'))?.hide();
    }

    function openVehicleSpecifications(vehicleId) {
        const vehicle = vehicleById(vehicleId);
        if (!vehicle) return;
        const unitNumber = fleet.filter(function (unit) { return unit.name === vehicle.name; })
            .findIndex(function (unit) { return unit.id === vehicle.id; }) + 1;
        document.getElementById('vehicleSpecificationsTitle').textContent =
            vehicle.name + ' Unit #' + unitNumber + ' Specifications';

        const specifications = [
            ['Transmission Type', vehicle.transmission],
            ['Fuel Type', vehicle.fuel],
            ['Capacity', vehicle.capacity_type || (vehicle.capacity ? vehicle.capacity + ' Seater' : '')],
        ].filter(function (specification) { return Boolean(specification[1]); });
        const list = document.getElementById('vehicleSpecificationsList');
        list.replaceChildren();
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
        bootstrap.Modal.getOrCreateInstance(document.getElementById('vehicleSpecificationsModal')).show();
    }

    function renderUserFleet() {
        const grid = document.getElementById('userVehicleGrid');
        const emptyMsg = document.getElementById('emptyFleetMsg');
        if (!grid) return;

        const keyword = (document.getElementById('searchCar').value || '').trim().toLowerCase();
        const capacity = document.getElementById('filterCapacity').value;

        const matchingModelNames = new Set(fleet.filter(function (v) {
            const matchKeyword = !keyword || (v.name + ' ' + (v.brand_name || '') + ' ' + v.plate + ' ' + (v.category || '')).toLowerCase().indexOf(keyword) !== -1;
            return matchKeyword;
        }).map(function (v) { return v.name; }));
        const visible = fleet.filter(function (v) {
            const matchCapacity = capacity === 'all' || String(v.capacity) === capacity;
            return matchingModelNames.has(v.name) && matchCapacity;
        });

        if (!visible.length) {
            grid.innerHTML = '';
            emptyMsg.classList.remove('d-none');
            return;
        }
        emptyMsg.classList.add('d-none');

        const groups = Array.from(visible.reduce(function (map, unit) {
            if (!map.has(unit.name)) map.set(unit.name, []);
            map.get(unit.name).push(unit);
            return map;
        }, new Map()).entries());

        grid.innerHTML = groups.map(function (entry, index) {
            const modelName = entry[0];
            const units = entry[1];
            const selectedId = selectedUnits[modelName] || units.find(function (unit) {
                return unitStatusLabel(unit) === 'Available';
            })?.id || units[0].id;
            selectedUnits[modelName] = selectedId;
            const v = units.find(function (unit) { return unit.id === selectedId; }) || units[0];
            const label = displayStatus(v);
            const canBook = label === 'Available';
            const dimmed = (label === 'Maintenance' || label === 'Unavailable' || label === 'Ongoing Rental');
            const unitNumber = fleet.filter(function (unit) { return unit.name === modelName; })
                .findIndex(function (unit) { return unit.id === v.id; }) + 1;

            const damageCount = (v.damageLog || []).filter(function (d) { return d.hasDamage !== false; }).length;
            const damageTag = damageCount > 0
                ? '<span class="car-info-tag clickable text-danger" style="border:1px solid #dc3545;" onclick="openDamageLogModal(' + v.id + ')"><i class="fas fa-tools me-1"></i>' + damageCount + ' Damage Log' + (damageCount !== 1 ? 's' : '') + '</span>'
                : '<span class="car-info-tag clickable" style="color:#198754; border:1px solid #b8e6c8;" onclick="openDamageLogModal(' + v.id + ')"><i class="fas fa-check-circle me-1"></i>No Damage Logs</span>';

            let actionBtn;
            if (canBook) {
                actionBtn = '<button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#rentModal" data-vehicle-id="' + v.id + '">Rent Now</button>';
            } else {
                actionBtn = '<button class="btn btn-secondary rounded-pill px-4 disabled">Unavailable</button>';
            }

            return '' +
            '<div class="col-md-6">' +
                '<div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative">' +
                    '<span class="status-badge ' + statusBadgeClass(label) + '">' + label + '</span>' +
                    '<div class="p-4 text-center bg-white"><img src="' + vehiclePhoto(v, index) + '" class="img-fluid" style="max-height: 140px;' + (dimmed ? ' opacity: 0.5;' : '') + '"></div>' +
                    '<div class="p-4 pt-0 bg-white catalog-card-body">' +
                        '<div class="d-flex justify-content-between align-items-start mb-1">' +
                            '<div>' +
                                '<h5 class="fw-bold mb-0' + (dimmed ? ' text-muted' : '') + '">' + escapeHtml(v.name) + ' Unit #' + unitNumber + '</h5>' +
                                (v.brand_name ? '<small class="text-muted d-block">' + escapeHtml(v.brand_name) + '</small>' : '') +
                                '<small class="text-muted d-block">Plate: ' + escapeHtml(v.plate || 'Not assigned') + '</small>' +
                                '<small class="text-muted" style="font-size: 0.7rem;"><i class="fas fa-history me-1"></i>' + v.timesRented + ' Times Rented</small>' +
                            '</div>' +
                            '<div class="text-end">' +
                                '<div class="rating-stars">' + renderStars(v.rating) + '</div>' +
                                '<a class="view-feedback" onclick="openFeedbackModal(' + v.id + ')">' + v.feedbacks.length + ' Feedback' + (v.feedbacks.length !== 1 ? 's' : '') + '</a>' +
                            '</div>' +
                        '</div>' +
                        '<div class="d-flex flex-wrap gap-2 mb-3 mt-2">' +
                            '<button type="button" class="car-info-tag clickable" data-model-name="' + escapeHtml(modelName) + '" onclick="openUnitsModalFromButton(this)"><i class="fas fa-layer-group me-1"></i>Stock: ' + units.length + ' Unit' + (units.length === 1 ? '' : 's') + '</button>' +
                            '<span class="car-info-tag ' + (label === 'Available' ? 'text-success' : (label === 'Maintenance' ? 'text-warning' : 'text-primary')) + '">Selected Unit: ' + escapeHtml(label) + '</span>' +
                            (v.category ? '<span class="car-info-tag category-tag">' + escapeHtml(displayReservationCategory(v.category)) + '</span>' : '') +
                            '<button type="button" class="specifications-button" onclick="openVehicleSpecifications(' + v.id + ')"><i class="fas fa-list-ul" aria-hidden="true"></i>View Specifications</button>' +
                            damageTag +
                        '</div>' +
                        '<div class="catalog-card-footer">' +
                        renderScheduleBanner(v, label) +
                        '<div class="d-flex justify-content-between align-items-center">' +
                            '<div><span class="h5 fw-bold' + (dimmed ? ' text-muted' : '') + '">₱' + v.price.toLocaleString() + '</span><small class="text-muted">/day</small></div>' +
                            actionBtn +
                        '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    document.getElementById('searchCar').addEventListener('input', renderUserFleet);
    document.getElementById('filterCapacity').addEventListener('change', renderUserFleet);

    // ================= CALENDAR (galing sa parehong scheduleEvents ng admin) =================
    function renderCalendar() {
        const year = currentViewDate.getFullYear();
        const monthIdx = currentViewDate.getMonth();
        const monthDisplay = document.getElementById('calendarMonthYear');
        if (monthDisplay) monthDisplay.innerText = currentViewDate.toLocaleString('default', { month: 'long', year: 'numeric' });

        const firstDay = new Date(year, monthIdx, 1).getDay();
        const daysInMonth = new Date(year, monthIdx + 1, 0).getDate();
        const calendarBody = document.getElementById('calendarBody');
        if (!calendarBody) return;

        calendarBody.innerHTML = '';
        const today = todayISO();
        let date = 1;

        for (let i = 0; i < 6; i++) {
            let row = document.createElement('tr');
            for (let j = 0; j < 7; j++) {
                let cell = document.createElement('td');
                if ((i === 0 && j < firstDay) || date > daysInMonth) {
                    cell.innerHTML = '';
                } else {
                    const iso = toISODate(year, monthIdx, date);
                    const isToday = iso === today;
                    if (isToday) cell.className = 'today-cell';
                    let cellHTML = '<span class="cal-date">' + date + (isToday ? ' <small class="text-danger">(Today)</small>' : '') + '</span>';

                    const dayEvents = scheduleEvents.filter(function (ev) {
                        return iso >= ev.start && iso <= ev.end;
                    });
                    dayEvents.slice(0, 3).forEach(function (ev) {
                        const cls = scheduleTypeClass[ev.type] || 'event-reserved';
                        cellHTML += '<div class="cal-event ' + cls + '" title="' + escapeHtml(vehicleNameById(ev.vehicleId)) + ' — ' + escapeHtml(ev.type) + '">' +
                                        escapeHtml(vehicleNameById(ev.vehicleId)) +
                                    '</div>';
                    });
                    if (dayEvents.length > 3) {
                        cellHTML += '<button type="button" class="cal-more" onclick="event.stopPropagation(); openCalendarDetails(\'' + iso + '\')">+' + (dayEvents.length - 3) + ' more</button>';
                    }

                    cell.innerHTML = cellHTML;
                    date++;
                }
                row.appendChild(cell);
            }
            calendarBody.appendChild(row);
            if (date > daysInMonth) break;
        }
    }

    function openCalendarDetails(iso) {
    const events = scheduleEvents.filter(function(ev) {
        return iso >= ev.start && iso <= ev.end;
    });
    const body = document.getElementById('calendarDetailsBody');
    const title = document.getElementById('calendarDetailsTitle');
    title.textContent = 'Schedules for ' + new Date(iso + 'T00:00:00').toLocaleDateString(undefined, { dateStyle: 'long' });
    body.innerHTML = '';
    events.forEach(function(ev) {
        const row = document.createElement('div');
        row.className = 'border rounded-3 p-2 mb-2 small fw-bold';
        row.textContent = vehicleNameById(ev.vehicleId) + ' - ' + (ev.type === 'Rented' ? 'Ongoing' : ev.type);
        body.appendChild(row);
    });
    bootstrap.Modal.getOrCreateInstance(document.getElementById('calendarDetailsModal')).show();
    }

    function changeMonth(step) {
        currentViewDate.setMonth(currentViewDate.getMonth() + step);
        renderCalendar();
    }

    // ================= RENT MODAL =================
    const rentModal = document.getElementById('rentModal');
    if (rentModal) {
        rentModal.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn || !btn.getAttribute('data-vehicle-id')) return;

            const v = vehicleById(parseInt(btn.getAttribute('data-vehicle-id')));
            if (!v) return;

            currentVehicleId = v.id;
            baseVehiclePrice = v.price;
            currentPrice = baseVehiclePrice;

            document.getElementById('modalCarName').innerText = v.name + ' (' + v.plate + ')';
            document.getElementById('vehicle').value = v.name;
            document.getElementById('vehicle_id').value = v.id;
            document.getElementById('rateType').value = 'city';
            setRateType('city');
            updateDateLimits();

            // Reset ang wizard tuwing bagong unit ang pipiliin
            changeStep(0);
            const guidePanel = document.getElementById('rentalLocationGuidePanel');
            if (guidePanel) {
                guidePanel.classList.add('d-none');
            }
            document.getElementById('mainAgree').checked = false;
            updateCompute();
        });
    }

    function toggleRentalLocationGuide(event) {
        if (event) event.preventDefault();

        const panel = document.getElementById('rentalLocationGuidePanel');
        if (!panel) return;

        const isHidden = panel.classList.toggle('d-none');
        const trigger = document.querySelector('[aria-controls="rentalLocationGuidePanel"]');
        if (trigger) trigger.setAttribute('aria-expanded', String(!isHidden));
    }

    function startBooking() {
        const errorArea = document.getElementById('step0-error-area');
        errorArea.innerHTML = "";

        if (document.getElementById('mainAgree').checked) {
            changeStep(1);
        } else {
            errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-center rounded-3 small fw-bold">' +
                '<i class="fas fa-exclamation-circle me-2"></i> Please read and agree to the Terms and Conditions first.</div>';
        }
    }

    function changeStep(n) {
        document.querySelectorAll('.step-content').forEach(function (s) { s.classList.remove('active'); });
        document.getElementById('step' + n).classList.add('active');
    }

    function showStepError(id, message) {
        const area = document.getElementById(id);
        if (area) {
            area.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">' +
                '<i class="fas fa-exclamation-circle me-2"></i>' + message + '</div>';
        }
    }

    async function loadDeliveryProvinces() {
        const province = document.getElementById('deliveryProvince');
        if (!province || province.options.length > 1) return;
        try {
            const response = await fetch('https://psgc.gitlab.io/api/provinces/');
            if (!response.ok) throw new Error('Unable to load provinces.');
            const items = await response.json();
            items.sort((a, b) => a.name.localeCompare(b.name)).forEach(function (item) {
                province.add(new Option(item.name, item.name));
                province.options[province.options.length - 1].dataset.code = item.code;
            });
        } catch (error) {
            province.innerHTML = '<option value="">Unable to load provinces</option>';
        }
    }

    async function loadDeliveryCities() {
        const province = document.getElementById('deliveryProvince');
        const city = document.getElementById('deliveryCity');
        const barangay = document.getElementById('deliveryBarangay');
        city.innerHTML = '<option value="">Loading cities / municipalities...</option>';
        city.disabled = true;
        barangay.innerHTML = '<option value="">Select city / municipality first</option>';
        barangay.disabled = true;
        const code = province.selectedOptions[0]?.dataset.code;
        if (!code) return;
        try {
            const response = await fetch('https://psgc.gitlab.io/api/provinces/' + code + '/cities-municipalities/');
            if (!response.ok) throw new Error('Unable to load cities.');
            const items = await response.json();
            city.innerHTML = '<option value="">Select City / Municipality</option>';
            items.sort((a, b) => a.name.localeCompare(b.name)).forEach(function (item) {
                city.add(new Option(item.name, item.name));
                city.options[city.options.length - 1].dataset.code = item.code;
            });
            city.disabled = false;
        } catch (error) {
            city.innerHTML = '<option value="">Unable to load cities</option>';
        }
    }

    async function loadDeliveryBarangays() {
        const city = document.getElementById('deliveryCity');
        const barangay = document.getElementById('deliveryBarangay');
        barangay.innerHTML = '<option value="">Loading barangays...</option>';
        barangay.disabled = true;
        const code = city.selectedOptions[0]?.dataset.code;
        if (!code) return;
        try {
            const response = await fetch('https://psgc.gitlab.io/api/cities-municipalities/' + code + '/barangays/');
            if (!response.ok) throw new Error('Unable to load barangays.');
            const items = await response.json();
            barangay.innerHTML = '<option value="">Select Barangay</option>';
            items.sort((a, b) => a.name.localeCompare(b.name)).forEach(function (item) {
                barangay.add(new Option(item.name, item.name));
            });
            barangay.disabled = false;
        } catch (error) {
            barangay.innerHTML = '<option value="">Unable to load barangays</option>';
        }
    }

    document.getElementById('deliveryProvince')?.addEventListener('change', loadDeliveryCities);
    document.getElementById('deliveryCity')?.addEventListener('change', loadDeliveryBarangays);
    loadDeliveryProvinces();

    function validateStep1() {
        const errorArea = document.getElementById('step1-error-area');
        errorArea.innerHTML = '';

        if (document.getElementById('service_option').value === 'delivery') {
            const fields = [
                ['delivery_province', 'Province'],
                ['delivery_city', 'City / Municipality'],
                ['delivery_barangay', 'Barangay']
            ];
            const missing = fields.find(function ([name]) {
                return !document.querySelector('[name="' + name + '"]').value.trim();
            });
            if (missing) {
                showStepError('step1-error-area', 'Please complete the delivery address (' + missing[1] + ').');
                return;
            }
            if (!document.getElementById('deliveryStreet').value.trim()) {
                showStepError('step1-error-area', 'Please enter the drop-off point or landmark.');
                return;
            }
        }

        changeStep(2);
    }

    function validateStep2() {
        const errorArea = document.getElementById('step2-error-area');
        errorArea.innerHTML = '';
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const startTime = document.getElementById('startTime').value;

        const endTime = document.getElementById('endTime').value;
        if (!start || !end || !startTime || !endTime) {
            showStepError('step2-error-area', 'Please select the pickup and return dates and times.');
            return;
        }
        const durationSeconds = rentalDurationSeconds();
        if (durationSeconds <= 0) {
            showStepError('step2-error-area', 'Return date and time must be later than pickup date and time.');
            return;
        }
        if (document.getElementById('rate_type').value === 'long_distance' && durationSeconds < 2 * 86400) {
            showStepError('step2-error-area', 'Long Distance bookings require a minimum rental period of 2 full days.');
            return;
        }
        const maximumRentalDays = getMaximumRentalDays();
        if (rentDays() > maximumRentalDays) {
            showStepError('step2-error-area', getRateTypeLabel() + ' bookings can only be rented for up to ' + maximumRentalDays + ' days.');
            return;
        }

        changeStep(3);
    }

    function setService(t) {
        document.getElementById('service_option').value = t;
        document.getElementById('opt-pickup').classList.toggle('active', t === 'pickup');
        document.getElementById('opt-deliver').classList.toggle('active', t === 'delivery');
        document.getElementById('pickup-info').style.display = (t === 'pickup') ? 'block' : 'none';
        document.getElementById('delivery-info').style.display = (t === 'delivery') ? 'block' : 'none';
    }

    function setDriver(f) {
        const withDriver = f > 0;
        driverFee = withDriver ? f : 0;
        document.getElementById('driver_option').value = withDriver ? 'with_driver' : 'self_drive';
        document.getElementById('d-self').classList.toggle('active', !withDriver);
        document.getElementById('d-with').classList.toggle('active', withDriver);
        updateCompute();
    }

    function rateForType(type) {
        const category = String(vehicleById(currentVehicleId)?.category || '').toLowerCase();
        const provinceSurcharge = category.includes('diesel') ? 500 : 1000;
        if (type === 'province') return baseVehiclePrice + provinceSurcharge;
        if (type === 'long_distance') return baseVehiclePrice + 1500;
        return baseVehiclePrice;
    }

    function setRateType(type) {
        document.getElementById('rate_type').value = type;
        currentPrice = rateForType(type);
        const labels = {city: 'City Driving', province: 'Province', long_distance: 'Long Distance (2 Days minimum)'};
        document.getElementById('ratePreview').innerText = labels[type] + ': ₱' + currentPrice.toLocaleString() + ' / day';
        document.querySelector('#dateLimitHint .fw-bold').textContent = labels[type] + ' allows up to ' + getMaximumRentalDays() + ' rental days.' +
            (type === 'long_distance' ? ' Minimum rental period: 2 full days.' : '');
        updateDateLimits();
        updateCompute();
    }

    function getMaximumRentalDays() {
        const type = document.getElementById('rate_type').value;
        return type === 'province' ? 14 : (type === 'long_distance' ? 30 : 7);
    }

    function getRateTypeLabel() {
        const type = document.getElementById('rate_type').value;
        return type === 'province' ? 'Province' : (type === 'long_distance' ? 'Long Distance' : 'City Driving');
    }

    function updateDateLimits() {
        const startInput = document.getElementById('startDate');
        const endInput = document.getElementById('endDate');
        const hint = document.getElementById('dateLimitHint');
        if (!startInput || !endInput) return;

        endInput.min = startInput.value ? getDateAfter(startInput.value, 1) : '{{ now()->toDateString() }}';
        if (startInput.value) {
            const maximumReturnDate = new Date(startInput.value + 'T00:00:00');
            maximumReturnDate.setDate(maximumReturnDate.getDate() + getMaximumRentalDays());
            endInput.max = maximumReturnDate.toISOString().slice(0, 10);
        } else {
            endInput.removeAttribute('max');
        }
        const endTime = document.getElementById('endTime');
        if (endTime) {
            if (startInput.value && endInput.value === startInput.value) {
                endTime.min = document.getElementById('startTime').value || '00:00';
            } else {
                endTime.removeAttribute('min');
            }
        }
        if (hint) {
            hint.querySelector('.fw-bold').textContent = getRateTypeLabel() + ' allows up to ' + getMaximumRentalDays() + ' rental days.' +
                (document.getElementById('rate_type').value === 'long_distance' ? ' Minimum rental period: 2 full days.' : '');
            hint.classList.remove('d-none');
        }
    }

    function rentalDurationSeconds() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        if (!start || !end || !startTime || !endTime) return 0;
        return (new Date(end + 'T' + endTime) - new Date(start + 'T' + startTime)) / 1000;
    }

    function syncReturnTimeFromPickup() {
        const pickupTime = document.getElementById('startTime').value;
        document.getElementById('endTime').value = pickupTime;
        document.getElementById('returnTimeValue').value = pickupTime;
    }

    function getDateAfter(dateValue, days) {
        const date = new Date(dateValue + 'T00:00:00');
        date.setDate(date.getDate() + days);
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' +
            String(date.getDate()).padStart(2, '0');
    }

    function syncReturnDateFromPickup() {
        const pickupDate = document.getElementById('startDate').value;
        if (!pickupDate) return;
        const returnDate = document.getElementById('endDate');
        const minimumReturnDate = getDateAfter(pickupDate, 1);
        if (!returnDate.value || returnDate.value < minimumReturnDate) {
            returnDate.value = minimumReturnDate;
        }
    }

    function rentDays() {
        const durationSeconds = rentalDurationSeconds();
        return durationSeconds > 0 ? Math.ceil(durationSeconds / 86400) : 1;
    }

    function updateCompute() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const selectedDatePreview = document.getElementById('selectedDatePreview');
        const durationSeconds = rentalDurationSeconds();
        const days = document.getElementById('rate_type').value === 'long_distance' && durationSeconds > 0
            ? Math.max(2, rentDays())
            : rentDays();
        const rentalTotal = currentPrice * days;
        const driverTotal = driverFee * days;
        const deposit = 1000;
        const total = rentalTotal + driverTotal;
        const depositRow = document.getElementById('securityDepositRow');

        document.getElementById('rec-days').innerText = days;
        document.getElementById('rec-rent').innerText = '₱' + rentalTotal.toLocaleString();
        document.getElementById('rec-driver').innerText = '₱' + driverTotal.toLocaleString();

        if (depositRow) {
            const hideDeposit = (payMode === 'walkin');
            depositRow.style.display = hideDeposit ? 'none' : 'flex';
            depositRow.classList.toggle('d-none', hideDeposit);
        }

        if (selectedDatePreview) {
            if (start && end) {
                selectedDatePreview.classList.remove('d-none');
                selectedDatePreview.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Selected rental period: <strong>' +
                    escapeHtml(formatDateRange(start, end)) + ' (' + document.getElementById('startTime').value + '–' +
                    document.getElementById('endTime').value + ')</strong>';
                const selectedVehicleId = parseInt(document.getElementById('vehicle_id').value, 10);
                const selectedBanner = document.getElementById('schedule-banner-' + selectedVehicleId);
                if (selectedBanner) {
                    selectedBanner.className = 'card-date-banner selected';
                    selectedBanner.innerHTML = '<i class="fas fa-calendar-check"></i><span>Selected rental dates: <b>' +
                        escapeHtml(formatDateRange(start, end)) + '</b></span>';
                }
            } else {
                selectedDatePreview.classList.add('d-none');
                selectedDatePreview.innerHTML = '';
            }
        }

        if (payMode === 'walkin') {
            document.getElementById('rec-now').innerText = '₱0 (Pay at Shop)';
        } else if (payMode === 'dep') {
            document.getElementById('rec-now').innerText = '₱' + deposit.toLocaleString();
        } else {
            document.getElementById('rec-now').innerText = '₱' + total.toLocaleString();
        }

    }

    function setPay(type) {
        payMode = type;
        document.getElementById('payment_mode').value = (type === 'dep') ? 'deposit' : type;
        document.getElementById('p-full').classList.toggle('active', type === 'full');
        document.getElementById('p-dep').classList.toggle('active', type === 'dep');
        document.getElementById('p-walk').classList.toggle('active', type === 'walkin');

        const gcashFields = document.getElementById('gcash-fields');
        const addressFields = document.getElementById('address-fields');
        if (type === 'walkin') {
            gcashFields.classList.add('d-none');
            addressFields.classList.remove('d-none');
        } else {
            gcashFields.classList.remove('d-none');
            addressFields.classList.add('d-none');
        }

        updateCompute();
    }

    function showVerifyInput() {
        document.getElementById('verify-initial').style.display = 'none';
        document.getElementById('verify-input').style.display = 'block';
    }

    // --- CUSTOMER VERIFICATION LOGIC ---
    function verifyCustomer() {
        const phone = document.getElementById('renterPhone').value;
        const statusDiv = document.getElementById('verify-status');
        const uploadFields = document.getElementById('uploadFields');
        const errorArea3 = document.getElementById('step3-error-area');
        if (errorArea3) errorArea3.innerHTML = "";

        if (phone === "") {
            statusDiv.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-0 small fw-bold rounded-3">Please enter your phone number.</div>';
            return;
        }

        // TODO (backend): palitan ng tunay na check sa Laravel
        // (GET /api/customers/verify?phone=...)
        const registeredNumbers = ["09123456789", "09987654321"];

        if (registeredNumbers.includes(phone)) {
            isReturningRenter = true;
            statusDiv.innerHTML = '<div class="alert alert-success py-2 px-3 mb-0 d-flex align-items-center rounded-3">' +
                '<i class="fas fa-check-circle me-2"></i><div>' +
                '<p class="mb-0 fw-bold small">Records Found!</p>' +
                '<p class="mb-0 x-small text-muted">Documents on file are still valid. No need to re-upload.</p>' +
                '</div></div>';
            if (uploadFields) {
                uploadFields.style.transition = "opacity 0.5s";
                uploadFields.style.opacity = "0.3";
                uploadFields.style.pointerEvents = "none";
            }
        } else {
            isReturningRenter = false;
            statusDiv.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-0 d-flex align-items-center rounded-3">' +
                '<i class="fas fa-exclamation-triangle me-2"></i>' +
                '<p class="mb-0 x-small fw-bold">No records found. Please upload manually.</p></div>';
            if (uploadFields) {
                uploadFields.style.display = "block";
                uploadFields.style.opacity = "1";
                uploadFields.style.pointerEvents = "auto";
            }
        }
    }

    function validateStep3() {
        const errorArea = document.getElementById('step3-error-area');
        errorArea.innerHTML = "";

        if (hasExistingDocuments || isReturningRenter) {
            changeStep(4);
            return;
        }

        const licenseInput = document.getElementById('doc-license');
        const govIdInput = document.getElementById('doc-id');
        const billingInput = document.getElementById('doc-billing');
        const license = licenseInput ? licenseInput.value : '';
        const govId = govIdInput ? govIdInput.value : '';
        const billing = billingInput ? billingInput.value : '';

        if (license === "" || govId === "" || billing === "") {
            errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-center rounded-3 small fw-bold shadow-sm">' +
                '<i class="fas fa-exclamation-circle me-2"></i> Required: Please upload all documents before proceeding.</div>';
            return;
        }

        const files = [
            licenseInput.files[0],
            govIdInput.files[0],
            billingInput.files[0]
        ];
        const invalidFile = files.find(function (file) {
            return file && (file.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'application/pdf'].includes(file.type));
        });
        if (invalidFile) {
            showStepError('step3-error-area', 'Each document must be JPG, PNG, or PDF and not larger than 5 MB.');
            return;
        }

        changeStep(4);
    }

    function validateStep4() {
        const errorArea = document.getElementById('step4-error-area');
        errorArea.innerHTML = "";

        if (document.getElementById('privacyAgree').checked) {
            changeStep(5);
        } else {
            errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-center rounded-3 small fw-bold">' +
                '<i class="fas fa-shield-alt me-2"></i> Please review and agree to the Data Privacy Consent first.</div>';
        }
    }

    function validateStep5() {
        const errorArea = document.getElementById('step5-error-area');
        errorArea.innerHTML = '';
        const paymentMode = document.getElementById('payment_mode').value;
        const screenshot = document.querySelector('[name="gcash_screenshot"]');
        const reference = document.querySelector('[name="gcash_reference"]');

        if (paymentMode !== 'walkin') {
            const referenceDigits = reference.value.replace(/\s+/g, '');
            if (!screenshot.files.length && !referenceDigits) {
                showStepError('step5-error-area', 'Please upload a GCash screenshot or enter the GCash reference number.');
                return;
            }
            if (referenceDigits && !/^\d{13,17}$/.test(referenceDigits)) {
                showStepError('step5-error-area', 'GCash reference must contain 13 to 17 digits.');
                return;
            }
        }

        changeStep(6);
        showUserBookingReview();
    }

    function hideUserBookingReview() {
        document.getElementById('userBookingReviewPanel').classList.add('d-none');
        document.getElementById('finalAgreementArea').classList.add('d-none');
        changeStep(5);
    }

    function showUserBookingReview() {
        const errorArea = document.getElementById('step6-error-area');
        errorArea.innerHTML = '';
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const paymentMode = document.getElementById('payment_mode').value;
        const gcashReference = document.querySelector('[name="gcash_reference"]').value.replace(/\s+/g, '');
        const screenshot = document.querySelector('[name="gcash_screenshot"]').files.length > 0;
        const address = [
            document.querySelector('[name="delivery_province"]')?.value,
            document.querySelector('[name="delivery_city"]')?.value,
            document.querySelector('[name="delivery_barangay"]')?.value,
            document.querySelector('[name="delivery_street"]')?.value
        ].filter(Boolean).join(', ') || 'Main Office';
        const paymentLabel = paymentMode === 'walkin' ? 'Walk-in / Cash' : (paymentMode === 'deposit' ? 'Deposit' : 'Full Payment');

        const durationSeconds = rentalDurationSeconds();
        if (!start || !end || durationSeconds <= 0 || rentDays() > getMaximumRentalDays()
            || (document.getElementById('rate_type').value === 'long_distance' && durationSeconds < 2 * 86400)) {
            errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 small fw-bold">Please review the pickup and return dates first.</div>';
            return;
        }
        if (paymentMode !== 'walkin' && !screenshot && !gcashReference) {
            errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 small fw-bold">Please upload a GCash screenshot or enter the GCash reference first.</div>';
            return;
        }

        const vehicleName = document.getElementById('vehicle').value;
        const service = document.getElementById('service_option').value === 'delivery' ? 'Deliver to Me' : 'Pickup at Garage';
        const driver = document.getElementById('driver_option').value === 'with_driver' ? 'With Driver' : 'Self Drive';
        const total = document.getElementById('rec-now').innerText;
        document.getElementById('userBookingReviewContent').innerHTML =
            '<div class="row g-2">' +
            '<div class="col-12"><b>Customer:</b> ' + escapeHtml(@json(auth()->user()->name)) + '</div>' +
            '<div class="col-6"><b>Vehicle:</b> ' + escapeHtml(vehicleName) + '</div>' +
            '<div class="col-6"><b>Service:</b> ' + service + '</div>' +
            '<div class="col-6"><b>Driver:</b> ' + driver + '</div>' +
            '<div class="col-6"><b>Rental Rate:</b> ' + escapeHtml(document.getElementById('rateType').selectedOptions[0].text) + '</div>' +
            '<div class="col-12"><b>Address:</b> ' + escapeHtml(address) + '</div>' +
            '<div class="col-6"><b>Pickup:</b> ' + escapeHtml(start) + ' at ' + escapeHtml(document.getElementById('startTime').value) + '</div>' +
            '<div class="col-6"><b>Return:</b> ' + escapeHtml(end) + ' at ' + escapeHtml(document.getElementById('endTime').value) + '</div>' +
            '<div class="col-6"><b>Payment:</b> ' + paymentLabel + '</div>' +
            '<div class="col-6"><b>GCash Reference:</b> ' + escapeHtml(gcashReference || 'N/A') + '</div>' +
            '<div class="col-6"><b>Payment Screenshot:</b> ' + (screenshot ? 'Attached' : 'N/A') + '</div>' +
            '<div class="col-6"><b>Rental Days:</b> ' + escapeHtml(document.getElementById('rec-days').innerText) + '</div>' +
            '<div class="col-12 mt-2"><b>Pay Now:</b> <span class="text-danger fw-bold">' + escapeHtml(total) + '</span></div>' +
            '</div>';
        document.getElementById('userBookingReviewPanel').classList.remove('d-none');
        document.getElementById('finalAgreementArea').classList.remove('d-none');
    }

    // TODO (backend): dapat galing sa Laravel ang control number para
    // sigurado ang uniqueness (auto-increment/unique column).
    function generateControlNumber() {
        const now = new Date();
        const datePart = now.getFullYear().toString()
            + String(now.getMonth() + 1).padStart(2, '0')
            + String(now.getDate()).padStart(2, '0');
        const randomPart = Math.floor(1000 + Math.random() * 9000);
        return 'BD-' + datePart + '-' + randomPart;
    }

    function finalReservationCheck() {
        const errorArea = document.getElementById('step6-error-area');
        errorArea.innerHTML = "";

        if (document.getElementById('cancelAgree').checked) {
            return true;
        }
        errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 d-flex align-items-center rounded-3 small fw-bold">' +
            '<i class="fas fa-exclamation-triangle me-2"></i> You must agree to the policy to finish the reservation.</div>';
        return false;
    }

    function copyControlNumber() {
        navigator.clipboard.writeText(currentControlNumber).then(function () {
            const btnText = document.getElementById('copyBtnText');
            btnText.innerText = "Copied!";
            setTimeout(function () { btnText.innerText = "Copy Number"; }, 1500);
        });
    }

    // ================= FEEDBACK MODAL =================
    let currentFeedbackVehicleId = null;
    let selectedFbRating = 0;

    function openFeedbackModal(vehicleId) {
        const v = vehicleById(vehicleId);
        if (!v) return;

        currentFeedbackVehicleId = vehicleId;
        document.getElementById('fbModalCarName').innerText = '— ' + v.name;
        document.getElementById('fbComment').value = '';
        document.getElementById('fbFormError').innerHTML = '';
        setFbRating(0);
        renderFeedbackList();

        new bootstrap.Modal(document.getElementById('feedbackModal')).show();
    }

    function renderFeedbackList() {
        const list = document.getElementById('feedbackModalList');
        const v = vehicleById(currentFeedbackVehicleId);

        if (!v || v.feedbacks.length === 0) {
            list.innerHTML = '<p class="small text-muted text-center py-3 mb-0">No feedback yet for this vehicle. Be the first to leave one!</p>';
            return;
        }

        list.innerHTML = v.feedbacks.map(function (f) {
            // Ipinapakita rin dito ang sagot ng admin/staff mula sa
            // Admin > Vehicle Management > View Details.
            const reply = f.adminReply
                ? '<div class="admin-reply">' +
                      '<span class="fw-bold small text-boss-red"><i class="fas fa-reply me-1"></i>BossDrive Response</span>' +
                      '<p class="small mb-0 mt-1 text-secondary">' + escapeHtml(f.adminReply.text) + '</p>' +
                  '</div>'
                : '';

            return '<div class="feedback-item">' +
                       '<div class="d-flex justify-content-between mb-1">' +
                           '<span class="fw-bold">' + escapeHtml(f.name) + '</span>' +
                           '<small class="text-muted">' + escapeHtml(f.date) + '</small>' +
                       '</div>' +
                       '<div class="rating-stars mb-2">' + renderStars(f.stars) + '</div>' +
                       '<p class="small mb-0 text-secondary">"' + escapeHtml(f.comment) + '"</p>' +
                       reply +
                   '</div>';
        }).join('');
    }

    function setFbRating(val) {
        selectedFbRating = val;
        document.querySelectorAll('#fbStarPicker i').forEach(function (star) {
            const starVal = parseInt(star.getAttribute('data-val'));
            star.className = starVal <= val ? 'fas fa-star' : 'far fa-star';
        });
    }

    function submitFeedback() {
        const errorArea = document.getElementById('fbFormError');
        errorArea.innerHTML = '';

        const comment = document.getElementById('fbComment').value.trim();
        const v = vehicleById(currentFeedbackVehicleId);
        if (!v) return;

        if (selectedFbRating === 0 || !comment) {
            errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">Please give a star rating and write a comment.</div>';
            return;
        }

        fetch('{{ url('/user/vehicles') }}/' + v.id + '/feedback', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ rating: selectedFbRating, comment: comment })
        }).then(function (response) {
            if (!response.ok) throw new Error('Feedback could not be saved.');
            return response.json();
        }).then(function (payload) {
            v.feedbacks = payload.feedbacks || [];
            v.rating = Number(payload.rating) || 0;
            document.getElementById('fbComment').value = '';
            setFbRating(0);
            renderFeedbackList();
            renderUserFleet();
        }).catch(function () {
            document.getElementById('fbFormError').innerHTML =
                '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">Feedback could not be saved. Please try again.</div>';
        });
    }

    // ================= DAMAGE / CONDITION LOG (read-only) =================
    function openDamageLogModal(vehicleId) {
        const v = vehicleById(vehicleId);
        if (!v) return;

        document.getElementById('dmgModalCarName').innerText = '— ' + v.name;
        const list = document.getElementById('damageLogModalList');
        const damageLog = v.damageLog || [];

        if (damageLog.length === 0) {
            list.innerHTML = '<p class="small text-muted text-center py-3 mb-0"><i class="fas fa-check-circle text-success me-1"></i>No damage or condition issues on record.</p>';
        } else {
            list.innerHTML = damageLog.map(function (d) {
                const isNoDamage = d.hasDamage === false;
                const isRepaired = d.status === 'Repaired';
                const badgeClass = (isNoDamage || isRepaired) ? 'bg-success' : (d.status === 'Under Repair' ? 'bg-danger' : 'bg-warning text-dark');
                const titleText = isNoDamage ? 'General Condition Check' : (d.part || 'Condition Check');
                const statusLabel = isNoDamage ? 'No Damage Found' : d.status;
                const descText = isNoDamage ? 'Vehicle was inspected — no damage found.' : d.description;

                let checklistHtml = '';
                if (d.checklist) {
                    checklistHtml = '<div class="mt-2">' + conditionChecklistFields.map(function (c) {
                        const isChecked = !!d.checklist[c.key];
                        return '<span class="condition-mini-badge ' + (isChecked ? 'checked' : 'unchecked') + '">' +
                                   '<i class="fas ' + (isChecked ? 'fa-check' : 'fa-times') + '"></i>' + c.label +
                               '</span>';
                    }).join('') + '</div>';
                }

                return '<div class="damage-item ' + ((isRepaired || isNoDamage) ? 'repaired' : '') + '">' +
                           '<span class="fw-bold small">' + escapeHtml(titleText) + '</span> ' +
                           '<span class="badge ' + badgeClass + ' ms-2" style="font-size:0.65rem;">' + escapeHtml(statusLabel) + '</span>' +
                           '<p class="small mb-1 mt-1 text-secondary">' + escapeHtml(descText) + '</p>' +
                           checklistHtml +
                           '<small class="text-muted d-block mt-2" style="font-size:0.7rem;">Reported by ' + escapeHtml(d.reportedBy) + ' &middot; ' + escapeHtml(d.date) + '</small>' +
                       '</div>';
            }).join('');
        }

        new bootstrap.Modal(document.getElementById('damageLogModal')).show();
    }

    // ================= INIT =================
    document.addEventListener('DOMContentLoaded', function () {
        renderUserFleet();
        renderCalendar();
        syncUserVehicleAvailability();
        setInterval(syncUserVehicleAvailability, 5000);
    });
</script>
</body>
</html>