<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $isStaffMode = $staffMode ?? false;
        $vehicleBaseUrl = $isStaffMode ? url('/staff/vehicles') : url('/admin/vehicles');
        $vehicleStoreUrl = $isStaffMode ? route('staff.vehicles.store') : route('admin.vehicles.store');
        $reservationStoreUrl = $isStaffMode ? route('staff.reservations.store') : url('/admin/reservations');
        $profileUrl = $isStaffMode ? route('staff.profile') : route('admin.profile');
    @endphp
    <title>BossDrive - {{ $isStaffMode ? 'Staff' : 'Admin' }} Vehicle Management</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #dc3545; --boss-dark: #212529; --boss-grey: #f8f9fa; }
        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }

        .sidebar { width: 250px; height: 100vh; background-color: #212529; position: fixed; border-right: 5px solid #dc3545; z-index: 1000; }
        .sidebar .nav-link { color: white; padding: 15px 20px; margin: 5px 15px; border-radius: 8px; font-size: 0.9rem; transition: 0.3s; text-decoration: none; display: block; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { background-color: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); }

        .main-content { margin-left: 250px; }

        .top-nav { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }

        .content-container { padding: 30px; }

        .stat-card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background: white; text-align: center; cursor: pointer; transition: 0.25s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 18px rgba(0,0,0,0.1); }
        .stat-card.active-filter { box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.35), 0 8px 18px rgba(0,0,0,0.1); }
        .filter-active-banner { font-size: 0.8rem; font-weight: 700; }

        .btn-add-vehicle {
            background-color: var(--boss-red); color: white; border-radius: 50px;
            font-weight: 700; padding: 10px 25px; border: none; font-size: 0.8rem;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
        }

        /* ===== Vehicle Cards - styled after the User Reservation catalogue ===== */
        .vehicle-card { border: none; border-radius: 18px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08); background: white; position: relative; height: 100%; transition: 0.25s; }
        .vehicle-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(0,0,0,0.12); }
        .vehicle-photo-wrap { padding: 25px; text-align: center; background: white; }
        .vehicle-photo-wrap img { max-height: 140px; width: auto; }
        .vehicle-card { display: flex; flex-direction: column; }
        .vehicle-card > .p-4 { display: flex; flex: 1 1 auto; flex-direction: column; }
        .vehicle-card-footer { margin-top: auto; }

        .status-badge {
            font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; font-weight: bold;
            position: absolute; top: 15px; right: 15px; z-index: 10; text-transform: uppercase;
        }
        .status-available { background: #198754; color: #fff; }
        .status-reserved { background: #0d6efd; color: #fff; }
        .status-ongoing { background: #dc3545; color: #fff; }
        .status-maintenance { background: #ffc107; color: #212529; }

        .plate-number { font-family: 'Courier New', Courier, monospace; background: #eee; padding: 2px 8px; border-radius: 4px; font-weight: bold; border: 1px solid #ccc; font-size: 0.8rem; }
        .car-info-tag { font-size: 0.75rem; background: #f8f9fa; padding: 4px 10px; border-radius: 50px; color: #666; font-weight: 600; border: 1px solid #eee; }
        .car-info-tag.clickable { cursor: pointer; transition: 0.2s; }
        .car-info-tag.clickable:hover { background: #fff5f5; text-decoration: underline; }
        .car-info-tag.category-tag { background: #212529; color: #fff; border-color: #212529; }
        .specifications-button { display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .65rem; border:1px solid #ced4da; border-radius:999px; background:#fff; color:#495057; font-size:.72rem; font-weight:700; line-height:1.2; transition:background-color .15s ease, border-color .15s ease, color .15s ease; }
        .specifications-button:hover, .specifications-button:focus-visible { border-color:#dc3545; background:#fff5f5; color:#dc3545; }
        .specifications-button:focus-visible { outline:3px solid rgba(220,53,69,.2); outline-offset:2px; }

        .card-action-btn { border-radius: 50px; font-weight: 700; font-size: 0.78rem; padding: 6px 14px; }
        .rating-stars { color: #ffc107; font-size: 0.85rem; }
        .view-feedback { font-size: 0.7rem; color: #dc3545; text-decoration: none; font-weight: bold; cursor: pointer; }
        .view-feedback:hover { text-decoration: underline; }

        .damage-item { background: #fff5f5; border: 1px solid #f5c2c7; border-radius: 10px; padding: 12px 15px; margin-bottom: 10px; }
        .damage-item.repaired { background: #eefdf5; border-color: #b8e6c8; }
        .feedback-item { border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px; }

        /* ===== Admin reply to a client feedback ===== */
        .admin-reply { background: #f8f9fa; border-left: 3px solid var(--boss-red); border-radius: 8px; padding: 10px 14px; margin-top: 10px; }
        .reply-toggle-link { font-size: 0.72rem; }

        /* ===== Condition Check checklist card (matches Vehicle Pickup Condition Check style) ===== */
        .condition-check-card { background: #fcfcfc; border: 1px solid #eee; border-radius: 16px; padding: 20px; }
        .condition-check-icon { width: 32px; height: 32px; border-radius: 50%; background: #fff0f1; color: var(--boss-red); display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; }
        .condition-check-list { background: white; border: 1px solid #eee; border-radius: 12px; overflow: hidden; }
        .condition-check-item { display: flex; align-items: center; gap: 12px; padding: 12px 15px; font-size: 0.85rem; font-weight: 600; color: #333; border-bottom: 1px dashed #eee; cursor: pointer; margin: 0; }
        .condition-check-item:last-child { border-bottom: none; }
        .condition-check-item input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--boss-red); cursor: pointer; flex-shrink: 0; }
        .condition-check-item .check-icon { color: var(--boss-red); width: 18px; text-align: center; flex-shrink: 0; }
        .condition-warning { background: #fff8e1; border: 1px solid #ffe08a; color: #7a5c00; border-radius: 10px; padding: 10px 14px; font-size: 0.78rem; }

        .condition-mini-badge { font-size: 0.65rem; padding: 3px 9px; border-radius: 50px; font-weight: 700; margin-right: 5px; margin-top: 5px; display: inline-flex; align-items: center; gap: 5px; }
        .condition-mini-badge.checked { background: #eefdf5; color: #198754; border: 1px solid #b8e6c8; }
        .condition-mini-badge.unchecked { background: #f8f9fa; color: #999; border: 1px solid #eee; }

        .option-card { border: 2px solid #eee; border-radius: 12px; padding: 12px; cursor: pointer; text-align: center; transition: 0.2s; font-weight: 600; font-size: 0.85rem; }
        .option-card.active { border-color: var(--boss-red); background: #fff5f5; border-width: 2.5px; color: var(--boss-red); }
        .receipt-box { background: #fdfdfd; border: 1px dashed #aaa; padding: 15px; border-radius: 8px; }

        .empty-fleet { text-align: center; padding: 60px 20px; color: #aaa; }

        /* ===== Vehicle card schedule banner — shows the current Reserved/Rented/
           Maintenance date range right on the card, no need to open the calendar ===== */
        .card-date-banner { border-radius: 10px; padding: 8px 12px; font-size: 0.75rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .card-date-banner.reserved { background: #e7f1ff; color: #0d6efd; }
        .card-date-banner.rented { background: #fdeaea; color: #dc3545; }
        .card-date-banner.maintenance { background: #fff3cd; color: #664d03; }

        /* ===== Success / Cancel toast notifications ===== */
        .bd-toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .bd-toast { min-width: 260px; max-width: 340px; padding: 14px 18px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.18); font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; color: #fff; opacity: 0; transform: scale(0.9); transition: 0.25s ease; }
        .bd-toast.show { opacity: 1; transform: scale(1); }
        .bd-toast.success { background: #198754; }
        .bd-toast.cancel { background: #6c757d; }
        .bd-toast.error { background: #dc3545; }

        /* The confirm modal must always sit above any other modal that might
           already be open (e.g. confirming while Vehicle Details is open) —
           without this it can render behind, since it's earlier in the DOM. */
        #bdConfirmModal { z-index: 1090 !important; }

        /* ===== Floating buttons ===== */
        .btn-float {
            position: fixed; bottom: 30px; right: 30px; z-index: 1050;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); border: none; width: 60px; height: 60px; border-radius: 50%;
        }

        /* ===== Fleet Schedule Calendar (editable by admin/staff) ===== */
        .calendar-table th { background: var(--boss-dark); color: white; text-align: center; }
        .calendar-table td { height: 100px; vertical-align: top; border: 1px solid #dee2e6; width: 14.28%; cursor: pointer; transition: 0.15s; }
        .calendar-table td:hover { background: #fff8f8; }
        .calendar-table td.today-cell { background: #fff3cd; box-shadow: inset 0 0 0 2px #ffc107; }
        .cal-date { font-weight: bold; margin-bottom: 5px; display: block; }
        .cal-event { font-size: 0.65rem; padding: 2px 5px; border-radius: 4px; margin-bottom: 2px; color: white; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; cursor: pointer; }
        .cal-event:hover { opacity: 0.85; }
        .event-maintenance { background: #ffc107; color: #000; }
        .event-rented { background: #dc3545; }
        .event-reserved { background: #0d6efd; }
        .event-delivery { background: #198754; }
        .cal-add-hint { font-size: 0.65rem; color: #ccc; text-align: center; opacity: 0; transition: 0.15s; }
        .calendar-table td:hover .cal-add-hint { opacity: 1; }
        .cal-more { display: block; width: 100%; border: 0; background: transparent; color: #495057; font-size: 0.68rem; font-weight: 700; text-align: left; padding: 2px 5px; }
        .cal-more:hover { color: var(--boss-red); text-decoration: underline; }
        .calendar-details-modal .modal-dialog { width: min(500px, calc(100vw - 1rem)); max-width: none; }
        .calendar-details-modal .modal-body { max-height: min(15rem, calc(100vh - 8rem)); overflow-y: auto; overscroll-behavior: contain; }
    </style>
</head>
<body>

    <!-- Success/Cancel toast notifications appear here -->
    <div class="bd-toast-container" id="bdToastContainer"></div>

    <!-- Floating button to open the editable fleet schedule calendar -->
    <button class="btn btn-dark btn-float" data-bs-toggle="modal" data-bs-target="#adminCalendarModal" title="Fleet Schedule Calendar">
        <i class="fas fa-calendar-alt fa-lg"></i>
    </button>

    <!-- ================= CUSTOM CONFIRM MODAL (replaces the native browser confirm() popup) ================= -->
    <div class="modal fade" id="bdConfirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content rounded-4 shadow border-0">
                <div class="modal-body p-4 text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:56px; height:56px; border-radius:50%; background:#fff5f5;">
                        <i class="fas fa-question text-danger" style="font-size:1.4rem;"></i>
                    </div>
                    <p class="fw-bold mb-4" id="bdConfirmMessage" style="font-size:0.95rem;">Are you sure?</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light border fw-bold rounded-pill px-4" id="bdConfirmCancelBtn">Cancel</button>
                        <button type="button" class="btn btn-danger fw-bold rounded-pill px-4" id="bdConfirmOkBtn">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($isStaffMode)
        @include('staff.partials.navigation', ['staffPageTitle' => 'Vehicle', 'staffPageAccent' => 'Management'])
    @else
        @include('admin.partials.navigation', ['adminPageTitle' => 'Vehicle', 'adminPageAccent' => 'Management'])
    @endif

    <div class="main-content">
        <div class="content-container">
            <div class="row g-4 mb-4 text-center">
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-primary border-5" data-filter="all">
                        <small class="text-muted fw-bold d-block text-uppercase">Total Units</small>
                        <h2 class="fw-bold mb-0 mt-2" id="statTotal">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-success border-5" data-filter="Available">
                        <small class="text-muted fw-bold d-block text-uppercase text-success">Available</small>
                        <h2 class="fw-bold mb-0 text-success mt-2" id="statAvailable">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-danger border-5" data-filter="Ongoing">
                        <small class="text-muted fw-bold d-block text-uppercase text-danger">Ongoing Rentals</small>
                        <h2 class="fw-bold mb-0 text-danger mt-2" id="statRented">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-danger border-5" data-filter="Maintenance">
                        <small class="text-muted fw-bold d-block text-uppercase text-danger">Maintenance</small>
                        <h2 class="fw-bold mb-0 text-danger mt-2" id="statMaintenance">0</h2>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="fw-bold m-0 text-uppercase small">Fleet List</h6>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-dark rounded-pill fw-bold" style="font-size:0.8rem; padding:10px 20px;" data-bs-toggle="modal" data-bs-target="#priceGuideModal">
                        <i class="fas fa-tags me-2"></i> PRICE GUIDE
                    </button>
                    <button class="btn btn-outline-danger rounded-pill fw-bold" style="font-size:0.8rem; padding:10px 20px;" data-bs-toggle="modal" data-bs-target="#conditionReportsModal">
                        <i class="fas fa-clipboard-check me-2"></i> CONDITION REPORTS
                    </button>
                    <button class="btn btn-add-vehicle" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                        <i class="fas fa-plus me-2"></i> ADD NEW VEHICLE
                    </button>
                </div>
            </div>

            <div id="filterBanner" class="alert alert-danger py-2 px-3 filter-active-banner d-none d-flex justify-content-between align-items-center">
                <span><i class="fas fa-filter me-2"></i>Showing filtered results: <span id="filterLabel"></span></span>
                <button class="btn btn-sm btn-outline-danger fw-bold" onclick="clearStatFilter()">Clear Filter</button>
            </div>

            <div class="row g-4" id="fleetGrid">
                <!-- Vehicle cards rendered by JS -->
            </div>

            <div id="emptyFleetMsg" class="empty-fleet d-none">
                <i class="fas fa-car-side fa-3x mb-3"></i>
                <p class="fw-bold mb-0">No vehicles in the fleet yet.</p>
                <p class="small">Click "Add New Vehicle" to get started.</p>
            </div>
        </div>
    </div>

    <!-- ================= ADD VEHICLE MODAL ================= -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="fw-bold mb-0" id="addVehicleModalTitle">ADD NEW VEHICLE UNIT</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label small fw-bold">Vehicle Model</label><input type="text" id="addName" class="form-control rounded-3" placeholder="e.g. Vios 2023"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Brand Name</label><input type="text" id="addBrandName" class="form-control rounded-3" placeholder="e.g. Toyota"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Plate No.</label><input type="text" id="addPlate" class="form-control rounded-3" placeholder="e.g. GAS-123"></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Initial Unit Status</label>
                            <select id="addStatus" class="form-select rounded-3">
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small fw-bold mb-0">Price/Day <span class="text-muted fw-normal">(City Driving)</span></label>
                                <a class="small text-danger fw-bold text-decoration-none" style="cursor:pointer; font-size:0.7rem;" data-bs-toggle="modal" data-bs-target="#priceGuideModal"><i class="fas fa-tags me-1"></i>Price Guide</a>
                            </div>
                            <input type="number" id="addPrice" class="form-control rounded-3 mt-1 bg-light" placeholder="Select a category" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Category <span class="text-muted fw-normal">(matches the Price Guide)</span></label>
                            <select id="addCategory" class="form-select rounded-3" onchange="syncVehicleCategoryPrice('add')">
                                <option value="Sedan">Sedan</option>
                                <option value="Pick-up / Expanded (7-Seater) (2 Days) — Gas">Pick-up / Expanded</option>
                                <option value="Expanded — Diesel">Expanded</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Transmission Type</label>
                            <select id="addTransmission" class="form-select rounded-3">
                                <option value="Manual">Manual</option>
                                <option value="Automatic">Automatic</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fuel Type</label>
                            <select id="addFuel" class="form-select rounded-3">
                                <option value="Gasoline">Gasoline</option>
                                <option value="Diesel">Diesel</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Capacity</label>
                            <select id="addCapacityType" class="form-select rounded-3">
                                <option value="4 Seater">4 Seater</option>
                                <option value="5 Seater" selected>5 Seater</option>
                                <option value="6 Seater">6 Seater</option>
                                <option value="7 Seater">7 Seater</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Vehicle Photo <span class="text-muted fw-normal">(optional)</span></label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center border rounded-3 flex-shrink-0" style="width:64px; height:64px; background:#f8f9fa; overflow:hidden;">
                                    <img id="addPhotoPreview" src="" class="d-none" style="max-width:100%; max-height:100%; object-fit:cover;">
                                    <i id="addPhotoPreviewIcon" class="fas fa-image text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" id="addPhotoFile" accept="image/*" class="form-control form-control-sm rounded-3" onchange="handlePhotoUpload(this, 'add')">
                                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Or paste an image URL below.</small>
                                </div>
                            </div>
                            <input type="text" id="addPhoto" class="form-control rounded-3 mt-2" placeholder="Leave blank for default thumbnail" onchange="handlePhotoUrlInput('add')">
                        </div>
                    </div>
                    <div id="addVehicleError"></div>
                    <button class="btn btn-danger w-100 mt-4 py-2 fw-bold rounded-pill shadow-sm" onclick="saveNewVehicle()">SAVE UNIT</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="unitStockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="unitStockTitle">Vehicle Units</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="list-group list-group-flush" id="unitStockList"></div>
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

    <!-- ================= EDIT VEHICLE MODAL ================= -->
    <div class="modal fade" id="editVehicleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow border-0">
                <div class="modal-header bg-light">
                    <h5 class="fw-bold mb-0">EDIT VEHICLE DETAILS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="editId">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label small fw-bold">Vehicle Model</label><input type="text" id="editName" class="form-control rounded-3"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Brand Name</label><input type="text" id="editBrandName" class="form-control rounded-3"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Plate No.</label><input type="text" id="editPlate" class="form-control rounded-3"></div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small fw-bold mb-0">Price/Day <span class="text-muted fw-normal">(City Driving)</span></label>
                                <a class="small text-danger fw-bold text-decoration-none" style="cursor:pointer; font-size:0.7rem;" data-bs-toggle="modal" data-bs-target="#priceGuideModal"><i class="fas fa-tags me-1"></i>Price Guide</a>
                            </div>
                            <input type="number" id="editPrice" class="form-control rounded-3 mt-1 bg-light" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Category <span class="text-muted fw-normal">(matches the Price Guide)</span></label>
                            <select id="editCategory" class="form-select rounded-3" onchange="syncVehicleCategoryPrice('edit')">
                                <option value="Sedan">Sedan</option>
                                <option value="Pick-up / Expanded (7-Seater) (2 Days) — Gas">Pick-up / Expanded</option>
                                <option value="Expanded — Diesel">Expanded</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Transmission Type</label>
                            <select id="editTransmission" class="form-select rounded-3">
                                <option value="Manual">Manual</option>
                                <option value="Automatic">Automatic</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fuel Type</label>
                            <select id="editFuel" class="form-select rounded-3">
                                <option value="Gasoline">Gasoline</option>
                                <option value="Diesel">Diesel</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Capacity</label>
                            <select id="editCapacityType" class="form-select rounded-3">
                                <option value="4 Seater">4 Seater</option>
                                <option value="5 Seater">5 Seater</option>
                                <option value="6 Seater">6 Seater</option>
                                <option value="7 Seater">7 Seater</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Update Status</label>
                            <select id="editStatus" class="form-select rounded-3">
                                <option value="Available">Available</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Start Date</label>
                            <input type="date" id="editScheduleStart" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">End Date</label>
                            <input type="date" id="editScheduleEnd" class="form-control rounded-3">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Vehicle Photo <span class="text-muted fw-normal">(optional)</span></label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center border rounded-3 flex-shrink-0" style="width:64px; height:64px; background:#f8f9fa; overflow:hidden;">
                                    <img id="editPhotoPreview" src="" class="d-none" style="max-width:100%; max-height:100%; object-fit:cover;">
                                    <i id="editPhotoPreviewIcon" class="fas fa-image text-muted"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" id="editPhotoFile" accept="image/*" class="form-control form-control-sm rounded-3" onchange="handlePhotoUpload(this, 'edit')">
                                    <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Or paste an image URL below.</small>
                                </div>
                            </div>
                            <input type="text" id="editPhoto" class="form-control rounded-3 mt-2" onchange="handlePhotoUrlInput('edit')">
                        </div>
                    </div>
                    <button class="btn btn-dark w-100 mt-4 py-2 fw-bold rounded-pill shadow-sm" onclick="saveEditedVehicle()">UPDATE CHANGES</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= ADMIN BOOKING MODAL (Walk-in / Phone booking) ================= -->
    <div class="modal fade" id="adminBookModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0 text-uppercase">Book Unit: <span id="bookCarName" class="text-danger"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="book-vehicle-id">

                    <div id="bookFormArea">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small fw-bold text-danger" id="bookStepLabel">STEP 1 OF 3</span>
                            <span class="small text-muted">Customer Information</span>
                        </div>
                        <div id="bookError" role="alert" aria-live="polite"></div>
                        <div id="bookStep1">
                        <p class="small fw-bold text-muted text-uppercase mb-3"><i class="fas fa-user-tag me-1"></i>Customer Info (Walk-in / Phone Booking)</p>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="small fw-bold">Customer Name</label>
                                <input type="text" id="b-name" class="form-control" placeholder="Full name">
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Contact Number</label>
                                <input type="text" id="b-phone" class="form-control" placeholder="09XXXXXXXXX">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Birthday</label>
                                <input type="date" id="b-birth-date" class="form-control" onchange="calculateBookAge()">
                                <small class="text-muted">Must be 18 years old or above.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Age</label>
                                <input type="number" id="b-age" class="form-control" min="18" max="120" placeholder="Age" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold">Email</label>
                                <input type="email" id="b-email" class="form-control" placeholder="customer@email.com">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6"><div class="option-card active" id="b-opt-pickup" onclick="setBookService('pickup')">Pickup at Garage</div></div>
                            <div class="col-6"><div class="option-card" id="b-opt-deliver" onclick="setBookService('delivery')">Deliver to Customer</div></div>
                        </div>
                        <div id="b-delivery-info" class="mb-3" style="display:none;">
                            <label class="small fw-bold">Delivery Address</label>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label class="small fw-bold" for="b-delivery-province">Province</label>
                                    <select id="b-delivery-province" class="form-select"><option value="">Select Province</option></select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="small fw-bold" for="b-delivery-city">City / Municipality</label>
                                    <select id="b-delivery-city" class="form-select" disabled><option value="">Select City / Municipality</option></select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="small fw-bold" for="b-delivery-barangay">Barangay</label>
                                    <select id="b-delivery-barangay" class="form-select" disabled><option value="">Select Barangay</option></select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="small fw-bold" for="b-delivery-street">Landmark / Street</label>
                                    <input type="text" id="b-delivery-street" class="form-control" maxlength="1000" placeholder="Block & Lot, street, o pamilyar na drop-off point">
                                </div>
                                <div class="col-12">
                                    <label class="small fw-bold" for="b-delivery-notes">Delivery Notes <span class="text-muted fw-normal">(optional)</span></label>
                                    <textarea id="b-delivery-notes" class="form-control" rows="2" maxlength="1000" placeholder="Building, gate, or other instructions"></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="b-next-customer-step" class="btn btn-danger w-100 rounded-pill fw-bold" onclick="nextBookStep()">NEXT: RENTAL DETAILS</button>
                        </div>

                        <div id="bookStep2" class="d-none">
                        <div class="d-flex justify-content-between mb-3"><button type="button" class="btn btn-link p-0" onclick="setBookStep(1)">← BACK</button><span class="small fw-bold text-danger">STEP 2 OF 3</span></div>
                        <label class="small fw-bold mb-2">Rental Location / Rate</label>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill fw-bold mb-2" onclick="toggleBookGuide(event)" aria-expanded="false"><i class="fas fa-map-marked-alt me-1"></i>VIEW GUIDE</button>
                        <div id="bookGuide" class="alert alert-light border border-danger rounded-4 mb-3 d-none">
                            <strong class="text-danger">City Driving:</strong> GMA, Carmona, Biñan, San Pedro, Dasmariñas, Silang, General Trias, Imus, Bacoor, Muntinlupa, Parañaque, Pasay, Makati, Taguig<br>
                            <strong class="text-primary">Province:</strong> Tagaytay, Batangas, Laguna, Rizal, Cavite farther areas, Quezon<br>
                            <strong class="text-success">Long Distance (2 Days):</strong> Baguio, La Union, Vigan, Ilocos, Nueva Ecija, Pangasinan, Bicol, Legazpi
                        </div>
                        <select id="b-rate-type" class="form-select mb-2" onchange="setBookRateType(this.value)">
                            <option value="city">City Driving</option>
                            <option value="province">Province</option>
                            <option value="long_distance">Long Distance (2 Days minimum)</option>
                        </select>
                        <div id="b-date-limit-hint" class="alert alert-warning py-2 px-3 mb-3 small fw-bold"></div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="small fw-bold">Pickup Date</label>
                                <input type="date" id="b-start" class="form-control" onchange="syncBookReturnDateFromPickup(); updateBookCompute()">
                                <label class="small fw-bold mt-2">Pickup Time</label>
                                <input type="time" id="b-start-time" class="form-control" value="09:00" onchange="syncBookReturnTimeFromPickup(); syncBookReturnDateFromPickup(); updateBookCompute()">
                            </div>
                            <div class="col-6">
                                <label class="small fw-bold">Return Date</label>
                                <input type="date" id="b-end" class="form-control" onchange="updateBookCompute()">
                                <label class="small fw-bold mt-2">Return Time</label>
                                <input type="time" id="b-end-time" class="form-control" value="09:00" disabled>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6"><div class="option-card active" id="b-drv-self" onclick="setBookDriver(0)">Self Drive</div></div>
                            <div class="col-6"><div class="option-card" id="b-drv-with" onclick="setBookDriver(1500)">With Driver (+₱1,500)</div></div>
                        </div>
                        <div id="b-driver-select-wrap" class="mb-3 d-none">
                            <label for="b-driver-id" class="small fw-bold">Select Driver</label>
                            <select id="b-driver-id" class="form-select" onchange="updateBookCompute()">
                                <option value="">Select an available driver</option>
                            </select>
                            <div id="b-driver-hint" class="form-text">Driver availability is managed in Driver Management.</div>
                        </div>
                        <button type="button" class="btn btn-danger w-100 rounded-pill fw-bold" onclick="nextBookStep()">NEXT: PAYMENT</button>
                        </div>

                        <div id="bookStep3" class="d-none">
                        <div class="d-flex justify-content-between mb-3"><button type="button" class="btn btn-link p-0" onclick="setBookStep(2)">← BACK</button><span class="small fw-bold text-danger">STEP 3 OF 3</span></div>
                        <div class="row g-2 mb-3">
                            <div class="col-4"><div class="option-card active" id="b-pay-full" onclick="setBookPay('full')">Full Payment</div></div>
                            <div class="col-4"><div class="option-card" id="b-pay-dep" onclick="setBookPay('dep')">Deposit (₱1,000)</div></div>
                            <div class="col-4"><div class="option-card" id="b-pay-cash" onclick="setBookPay('cash')">Walk-in / Cash</div></div>
                        </div>
                        <div id="b-gcash-fields" class="mb-3">
                            <label class="small fw-bold">GCash Reference</label>
                            <input type="text" id="b-gcash-reference" class="form-control" inputmode="numeric" maxlength="17" placeholder="GCash reference">
                        </div>

                        <div class="receipt-box small">
                            <div class="d-flex justify-content-between"><span>Rent Fee (<span id="b-days">1</span> day/s):</span><span id="b-rec-rent">₱0</span></div>
                            <div class="d-flex justify-content-between"><span>Driver Fee:</span><span id="b-rec-driver">₱0</span></div>
                            <div id="b-security-deposit-row" class="d-flex justify-content-between"><span>Security Deposit:</span><span>₱1,000</span></div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold text-danger"><span>PAY NOW:</span><span id="b-rec-now">₱0</span></div>
                        </div>
                        <button type="button" id="bookReviewButton" class="btn btn-danger w-100 rounded-pill fw-bold mt-3" onclick="showBookReview()">REVIEW BOOKING</button>
                        <div id="bookReviewPanel" class="alert alert-light border border-danger rounded-4 mt-3 d-none">
                            <h6 class="fw-bold text-danger mb-3"><i class="fas fa-clipboard-check me-1"></i>Double-check Booking Details</h6>
                            <div id="bookReviewContent" class="small"></div>
                            <button type="button" class="btn btn-link text-danger fw-bold p-0 mt-3" onclick="hideBookReview()">← Edit Details</button>
                        </div>
                        </div>
                    </div>

                    <div id="bookConfirmArea" class="text-center py-3" style="display:none;">
                        <i class="fas fa-check-circle text-success mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="fw-bold text-dark mb-1">Booking Confirmed!</h5>
                        <p class="small text-muted mb-3">Vehicle status has been updated. Give this Control Number to the customer.</p>
                        <div class="bg-light border rounded-4 p-3 mb-3" style="border-style: dashed !important;">
                            <p class="x-small fw-bold text-muted mb-1 text-uppercase">Control Number</p>
                            <h3 class="fw-bold text-danger mb-0" id="b-controlDisplay" style="letter-spacing: 2px;">BD-00000000-0000</h3>
                        </div>
                        <button class="btn btn-outline-dark w-100 rounded-pill fw-bold" data-bs-dismiss="modal">DONE</button>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3" id="bookFormFooter">
                    <button class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                    <button id="confirmAdminBookingButton" class="btn btn-success px-5 rounded-pill fw-bold shadow-sm d-none" onclick="confirmAdminBooking()">
                        <i class="fas fa-check me-2"></i>Confirm Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= VEHICLE DETAILS MODAL (Rating, Feedback, Damage Log) ================= -->
    <div class="modal fade" id="vehicleDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0 text-uppercase" id="detailsCarName">Vehicle Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="details-vehicle-id">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <div class="rating-stars mb-1" id="detailsStars" style="font-size:1.1rem;"></div>
                            <small class="text-muted" id="detailsRatingText"></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                                <i class="fas fa-history me-1"></i><span id="detailsTimesRented"></span> Times Rented
                            </span>
                        </div>
                    </div>

                    <ul class="nav nav-tabs mb-3" id="detailsTab">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabFeedback" type="button">
                                <i class="fas fa-comments me-1"></i> Client Feedbacks
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tabDamageBtn" data-bs-toggle="tab" data-bs-target="#tabDamage" type="button">
                                <i class="fas fa-tools me-1"></i> Condition / Damage Log
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- FEEDBACK TAB -->
                        <div class="tab-pane fade show active" id="tabFeedback">
                            <div id="detailsFeedbackList"></div>
                        </div>

                        <!-- DAMAGE LOG TAB (Admin-editable) -->
                        <div class="tab-pane fade" id="tabDamage">

                            <div class="condition-check-card mb-4">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="condition-check-icon"><i class="fas fa-car-side"></i></span>
                                    <h6 class="fw-bold mb-0">Vehicle Condition Check</h6>
                                </div>
                                <p class="small text-muted mb-3">Tick off what was inspected, then log any damage found on this unit.</p>

                                <div class="condition-check-list mb-3">
                                    <label class="condition-check-item">
                                        <input type="checkbox" id="chk-fuel">
                                        <span class="check-icon"><i class="fas fa-gas-pump"></i></span>
                                        <span>Fuel level checked and noted</span>
                                    </label>
                                    <label class="condition-check-item">
                                        <input type="checkbox" id="chk-exterior">
                                        <span class="check-icon"><i class="fas fa-car-crash"></i></span>
                                        <span>Exterior/body inspected for damage</span>
                                    </label>
                                    <label class="condition-check-item">
                                        <input type="checkbox" id="chk-interior">
                                        <span class="check-icon"><i class="fas fa-broom"></i></span>
                                        <span>Interior checked, clean and complete</span>
                                    </label>
                                    <label class="condition-check-item">
                                        <input type="checkbox" id="chk-tools">
                                        <span class="check-icon"><i class="fas fa-toolbox"></i></span>
                                        <span>Accessories/tools (spare tire, jack, etc.) verified</span>
                                    </label>
                                </div>

                                <label class="small fw-bold text-uppercase text-muted mb-1 d-block">Damage / Condition Notes (if any)</label>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-4"><input type="text" id="dmg-part" class="form-control form-control-sm" placeholder="Part (e.g. Front Bumper)"></div>
                                    <div class="col-md-5"><input type="text" id="dmg-desc" class="form-control form-control-sm" placeholder="e.g. small scratch on rear bumper..."></div>
                                    <div class="col-md-3">
                                        <select id="dmg-status" class="form-select form-select-sm">
                                            <option value="Under Repair">Under Repair</option>
                                            <option value="Repaired">Repaired</option>
                                            <option value="Pending Assessment">Pending</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="condition-warning mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Any damage not logged here will be considered as occurring during the current rental.
                                </div>

                                <small class="text-muted d-block mb-2" style="font-size:0.7rem;">Logged as: Admin Patrick &middot; <span id="dmg-today"></span></small>

                                <button id="damageSubmitButton" class="btn btn-danger w-100 rounded-pill fw-bold py-2" onclick="addDamageEntry()">
                                    <i class="fas fa-check-circle me-2"></i>CONFIRM & LOG CONDITION
                                </button>
                            </div>

                            <h6 class="fw-bold small text-uppercase text-muted mb-2"><i class="fas fa-clipboard-list me-1"></i>Damage / Condition History</h6>
                            <div id="detailsDamageList"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= DAMAGE LOG VIEW MODAL (read-only history, opened from the "X Damage Logs" tag) ================= -->
    <div class="modal fade" id="damageLogViewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0 text-uppercase"><i class="fas fa-tools me-2"></i>Condition / Damage Log <span id="dmgViewCarName" class="text-danger"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3"><i class="fas fa-info-circle me-1"></i>History of everything logged for this unit's condition and damage.</p>
                    <div id="damageLogViewList"></div>
                </div>
                <div class="modal-footer bg-light border-0 p-3 d-flex justify-content-between">
                    <button class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-outline-danger fw-bold rounded-pill px-3" onclick="switchToFullDamageLog()">
                        <i class="fas fa-plus me-1"></i>Log New Condition Check
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= PRICE GUIDE MODAL ================= -->
    <div class="modal fade" id="priceGuideModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0 text-uppercase"><i class="fas fa-tags me-2"></i>BossDrive <span class="text-danger">Price Guide</span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">Reference rates per category — use this when setting a vehicle's Price/Day.</p>
                    @php
                        $sedanGuide = $priceGuides->firstWhere('category', 'Sedan');
                        $gasGuide = $priceGuides->firstWhere('category', 'Pick-up / Expanded (7-Seater) (2 Days) — Gas');
                        $dieselGuide = $priceGuides->firstWhere('category', 'Expanded — Diesel');
                    @endphp
                    @if(!$isStaffMode)
                    <form method="POST" action="{{ route('admin.price-guide.update') }}" class="border rounded-4 p-3 mb-4">
                        @csrf
                        @method('PUT')
                        <h6 class="fw-bold mb-3">Edit Price Guide</h6>
                        @foreach($priceGuides as $guide)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="small fw-bold text-danger mb-2">{{ $guide->category }}</div>
                                <input type="hidden" name="prices[{{ $loop->index }}][id]" value="{{ $guide->id }}">
                                <div class="row g-2">
                                    <div class="col-6 col-md-3"><label class="small">City Driving</label><input type="number" min="0" step="0.01" name="prices[{{ $loop->index }}][city_driving]" value="{{ $guide->city_driving }}" class="form-control form-control-sm" required></div>
                                    <div class="col-6 col-md-3"><label class="small">Province</label><input type="number" min="0" step="0.01" name="prices[{{ $loop->index }}][province]" value="{{ $guide->province }}" class="form-control form-control-sm" required></div>
                                    <div class="col-6 col-md-3"><label class="small">Long Distance</label><input type="number" min="0" step="0.01" name="prices[{{ $loop->index }}][long_distance]" value="{{ $guide->long_distance }}" class="form-control form-control-sm" required></div>
                                    <div class="col-6 col-md-3"><label class="small">Hourly</label><input type="number" min="0" step="0.01" name="prices[{{ $loop->index }}][hourly]" value="{{ $guide->hourly }}" class="form-control form-control-sm" required></div>
                                </div>
                            </div>
                        @endforeach
                        <button type="submit" class="btn btn-danger rounded-pill fw-bold"><i class="fas fa-save me-1"></i>Save Price Guide</button>
                    </form>
                    @endif

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

    <!-- ================= VEHICLE CONDITION REPORTS MODAL ================= -->
    <div class="modal fade" id="conditionReportsModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white p-4">
                    <h5 class="fw-bold mb-0 text-uppercase"><i class="fas fa-clipboard-check me-2"></i>Vehicle Condition Reports</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    @php
                    @endphp
                    <h6 class="fw-bold text-danger text-uppercase small mb-3"><i class="fas fa-car-side me-2"></i>Pickup Condition Reports</h6>
                    <div class="table-responsive border rounded-3 mb-4">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase"><tr><th>Customer</th><th>Vehicle</th><th>Date Submitted</th><th>Checklist</th><th>Notes</th><th>Photo</th></tr></thead>
                            <tbody>
                                @forelse($conditionReports as $report)
                                    @php $checks = collect($report->checks ?? []); $checked = $checks->filter()->count(); @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $report->reservation?->customer_name ?: ($report->user?->name ?? 'Walk-in Customer') }}</td>
                                        <td>{{ $report->reservation?->vehicle ?? 'Unknown vehicle' }}</td>
                                        <td class="small text-muted">{{ $report->created_at?->format('M d, Y g:i A') }}</td>
                                        <td><span class="badge rounded-pill bg-{{ $checked === $checks->count() && $checks->count() > 0 ? 'success' : 'warning' }}">{{ $checked }}/{{ $checks->count() }} Checked</span></td>
                                        <td class="small">{{ $report->notes ?: 'No notes submitted' }}</td>
                                        <td>@if($report->photo_path)<a href="{{ asset('storage/'.$report->photo_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="fas fa-image"></i> View</a>@else<span class="text-muted small">None</span>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No pickup condition reports yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold text-danger text-uppercase small mb-3"><i class="fas fa-clipboard-check me-2"></i>Vehicle Return Condition Check</h6>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small text-uppercase"><tr><th>Customer</th><th>Vehicle</th><th>Checked By</th><th>Date Checked</th><th>Checklist</th><th>Notes</th><th>Photo</th></tr></thead>
                            <tbody>
                                @forelse($returnConditionReports as $reservation)
                                    @php $returnChecks = collect($reservation->return_condition_checks ?? []); $returnChecked = $returnChecks->filter()->count(); @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $reservation->customer_name ?: ($reservation->user?->name ?? 'Walk-in Customer') }}</td>
                                        <td>{{ $reservation->vehicle }}</td>
                                        <td>{{ $reservation->return_condition_checked_by ?: 'Staff/Admin' }} <span class="text-muted small">({{ $reservation->return_condition_checked_by_role ?: 'Staff' }})</span></td>
                                        <td class="small text-muted">{{ $reservation->return_condition_checked_at?->format('M d, Y g:i A') }}</td>
                                        <td><span class="badge rounded-pill bg-success">{{ $returnChecked }}/{{ $returnChecks->count() }} Checked</span></td>
                                        <td class="small">{{ $reservation->return_condition_notes ?: 'No notes submitted' }}</td>
                                        <td>@if($reservation->return_condition_photo_path)<a href="{{ asset('storage/'.$reservation->return_condition_photo_path) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="fas fa-image"></i> View</a>@else<span class="text-muted small">None</span>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No returned vehicles have a completed condition check yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3"><button class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <!-- ================= FLEET SCHEDULE CALENDAR MODAL (editable by admin/staff) ================= -->
    <div class="modal fade" id="adminCalendarModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-calendar-alt me-2"></i> FLEET SCHEDULE CALENDAR</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-outline-dark btn-sm" onclick="changeAdminCalMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                            <h4 class="fw-bold mb-0" id="adminCalendarMonthYear">April 2026</h4>
                            <button class="btn btn-outline-dark btn-sm" onclick="changeAdminCalMonth(1)"><i class="fas fa-chevron-right"></i></button>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="badge bg-danger">Ongoing</span>
                            <span class="badge bg-primary">Reserved</span>
                            <span class="badge bg-warning text-dark">Maintenance</span>
                            <span class="badge bg-success">Delivery</span>
                        </div>
                    </div>
                    <p class="small text-muted mb-2"><i class="fas fa-info-circle me-1"></i>Vehicle rental and maintenance dates are managed through the vehicle edit form. Click an existing schedule to view or edit it.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered bg-white calendar-table">
                            <thead>
                                <tr><th>SUN</th><th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th></tr>
                            </thead>
                            <tbody id="adminCalendarBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade calendar-details-modal" id="adminCalendarDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="adminCalendarDetailsTitle">Schedules</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="adminCalendarDetailsBody"></div>
            </div>
        </div>
    </div>

    <!-- ================= ADD / EDIT SCHEDULE ENTRY MODAL ================= -->
    <div class="modal fade" id="scheduleFormModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header bg-dark text-white">
                    <h5 class="fw-bold mb-0" id="scheduleFormTitle">Edit Schedule Entry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="sched-id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold">Vehicle</label>
                            <select id="sched-vehicle" class="form-select rounded-3"></select>
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold">Schedule Type</label>
                            <select id="sched-type" class="form-select rounded-3">
                                <option value="Reserved">Reserved</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Start Date</label>
                            <input type="date" id="sched-start" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">End Date</label>
                            <input type="date" id="sched-end" class="form-control rounded-3">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" id="sched-notes" class="form-control rounded-3" placeholder="e.g. Customer name, or reason for maintenance">
                        </div>
                        <div class="col-12 form-check">
                            <input class="form-check-input" type="checkbox" id="sched-sync-status">
                            <label class="form-check-label small fw-bold" for="sched-sync-status">Also update the vehicle's current Status to match this entry</label>
                        </div>
                    </div>
                    <div id="scheduleFormError"></div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-outline-danger rounded-pill fw-bold px-3 d-none" id="sched-delete-btn" onclick="deleteScheduleEntry()"><i class="fas fa-trash"></i></button>
                        <button class="btn btn-danger flex-grow-1 rounded-pill fw-bold py-2" onclick="saveScheduleEntry()">SAVE ENTRY</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ================= SUCCESS / CANCEL TOAST NOTIFICATIONS =================
        // Shows a small confirmation banner after any action across this page —
        // green for success, gray for cancelled, red for errors/validation.
        function showToast(message, type) {
            type = type || 'success';
            const container = document.getElementById('bdToastContainer');
            if (!container) return;

            const icon = type === 'success' ? 'fa-check-circle' : (type === 'cancel' ? 'fa-times-circle' : 'fa-exclamation-circle');
            const toast = document.createElement('div');
            toast.className = 'bd-toast ' + type;
            toast.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span>';
            container.appendChild(toast);

            // Trigger the fade/slide-in on next frame
            requestAnimationFrame(function() { toast.classList.add('show'); });

            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() { toast.remove(); }, 250);
            }, 2800);
        }

        // ================= CUSTOM CONFIRM MODAL (replaces native browser confirm()) =================
        // Usage: if (!(await showConfirm('Are you sure?'))) { return; }
        // Pass a second argument (seconds) to require a short countdown before
        // the Confirm button becomes clickable — used for destructive actions
        // like removing a vehicle, so admin/staff can't accidentally rush it.
        let bdConfirmModalInstance = null;
        let bdConfirmResolve = null;
        let bdConfirmCountdownInterval = null;

        function showConfirm(message, countdownSeconds) {
            return new Promise(function(resolve) {
                document.getElementById('bdConfirmMessage').innerText = message;
                bdConfirmResolve = resolve;
                if (!bdConfirmModalInstance) {
                    bdConfirmModalInstance = new bootstrap.Modal(document.getElementById('bdConfirmModal'));
                }

                const okBtn = document.getElementById('bdConfirmOkBtn');

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

                bdConfirmModalInstance.show();

                // Bootstrap gives every backdrop the same default z-index, so when
                // this modal opens on top of another one, push its own backdrop
                // (the most recently added one) above the modal beneath it.
                setTimeout(function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    const ownBackdrop = backdrops[backdrops.length - 1];
                    if (ownBackdrop) ownBackdrop.style.zIndex = 1085;
                }, 0);
            });
        }

        document.getElementById('bdConfirmOkBtn').addEventListener('click', function() {
            if (this.disabled) return; // still counting down — ignore clicks
            if (bdConfirmCountdownInterval) { clearInterval(bdConfirmCountdownInterval); bdConfirmCountdownInterval = null; }
            bdConfirmModalInstance.hide();
            if (bdConfirmResolve) { bdConfirmResolve(true); bdConfirmResolve = null; }
        });
        document.getElementById('bdConfirmCancelBtn').addEventListener('click', function() {
            if (bdConfirmCountdownInterval) { clearInterval(bdConfirmCountdownInterval); bdConfirmCountdownInterval = null; }
            bdConfirmModalInstance.hide();
            if (bdConfirmResolve) { bdConfirmResolve(false); bdConfirmResolve = null; }
        });
        // Dismissing via backdrop/escape isn't possible (data-bs-backdrop="static",
        // data-bs-keyboard="false") so every path always resolves the promise above.

        const serverFleet = @json($vehicleRows);
        let fleet = serverFleet;
        fleet.forEach(function (vehicle) {
            vehicle.brand_name = vehicle.brand_name || '';
            vehicle.capacity_type = vehicle.capacity_type || (vehicle.capacity ? vehicle.capacity + ' Seater' : '');
            if (vehicle.status === 'Rented') {
                vehicle.status = vehicle.currentReservation && vehicle.currentReservation.status === 'released'
                    ? 'Ongoing'
                    : 'Reserved';
            }
        });
        let nextDamageId = fleet.reduce(function (nextId, vehicle) {
            return Math.max(nextId, ...(vehicle.damageLog || []).map(function (entry) { return Number(entry.id) + 1; }));
        }, 1);

        const statusInfo = {
            'Available':   { class: 'status-available',   canBook: true  },
            'Reserved':    { class: 'status-reserved',    canBook: false },
            'Ongoing':     { class: 'status-ongoing',     canBook: false },
            'Maintenance': { class: 'status-maintenance',  canBook: false }
        };
        const isStaffMode = @json($isStaffMode);
        const bookDriverCatalog = @json($driverCatalog ?? []);
        const selectedUnitByModel = {};

        // ================= STAT FILTER STATE =================
        let currentStatFilter = 'all';

        // ================= SCHEDULE BANNER HELPERS =================
        // Formats a start/end ISO date range into something like
        // "April 27 - 30, 2026" (same month) or "Apr 27, 2026 - May 2, 2026".
        function formatDateRange(startISO, endISO) {
            const start = new Date(startISO + 'T00:00:00');
            const end = new Date(endISO + 'T00:00:00');
            const sameMonthYear = start.getFullYear() === end.getFullYear() && start.getMonth() === end.getMonth();

            if (sameMonthYear) {
                const monthName = start.toLocaleString('default', { month: 'long' });
                return monthName + ' ' + start.getDate() + ' - ' + end.getDate() + ', ' + end.getFullYear();
            }
            const startStr = start.toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
            const endStr = end.toLocaleString('default', { month: 'long', day: 'numeric', year: 'numeric' });
            return startStr + ' - ' + endStr;
        }

        // Finds the fleet schedule entry that matches this vehicle's current
        // status (Reserved / Rented / Maintenance), so the card can show the
        // date range without admin/staff needing to open the Fleet Schedule Calendar.
        function findVehicleScheduleEvent(v) {
            if (v.status === 'Available') return null;
            const scheduleType = v.status === 'Ongoing' ? 'Rented' : v.status;
            const matches = scheduleEvents.filter(function(ev) {
                return ev.vehicleId === v.id && (ev.type === scheduleType || (scheduleType === 'Reserved' && ev.type === 'Special'));
            });
            if (matches.length === 0) return null;
            // Prefer the entry closest to/covering today; fall back to the first one.
            const today = todayISO();
            const current = matches.find(function(ev) { return today >= ev.start && today <= ev.end; });
            return current || matches.sort(function(a, b) { return a.start.localeCompare(b.start); })[0];
        }

        // Builds the little colored banner shown on the fleet card — mirrors
        // "Reserved for:", "Under Maintenance:", and "Return Date:" from the
        // reference design, so admin/staff see it at a glance without the calendar.
        function renderScheduleBanner(v) {
            const ev = findVehicleScheduleEvent(v);
            if (!ev) return '';

            const range = formatDateRange(ev.start, ev.end);
            if (v.status === 'Reserved' || ev.type === 'Reserved') {
                return '<div class="card-date-banner reserved"><i class="fas fa-calendar-check"></i>Reserved for: <strong>' + range + '</strong></div>';
            }
            if (v.status === 'Ongoing') {
                return '<div class="card-date-banner rented"><i class="fas fa-car"></i>Ongoing Rental: <strong>' + range + '</strong></div>';
            }
            if (v.status === 'Maintenance') {
                return '<div class="card-date-banner maintenance"><i class="fas fa-tools"></i>Under Maintenance: <strong>' + range + '</strong></div>';
            }
            return '';
        }

        // ================= RENDER FLEET GRID (card style) =================
        function renderFleet() {
            const grid = document.getElementById('fleetGrid');
            const emptyMsg = document.getElementById('emptyFleetMsg');
            const filterBanner = document.getElementById('filterBanner');
            const filterLabel = document.getElementById('filterLabel');

            const visibleFleet = currentStatFilter === 'all'
                ? fleet
                : fleet.filter(function(v){ return v.status === currentStatFilter; });

            // Highlight active stat card
            document.querySelectorAll('.stat-card').forEach(function(card) {
                card.classList.toggle('active-filter', card.getAttribute('data-filter') === currentStatFilter && currentStatFilter !== 'all');
            });

            // Show/hide filter banner
            if (currentStatFilter !== 'all') {
                filterBanner.classList.remove('d-none');
                filterLabel.innerText = currentStatFilter === 'Rented' ? 'Active Rentals' : currentStatFilter;
            } else {
                filterBanner.classList.add('d-none');
            }

            if (visibleFleet.length === 0) {
                grid.innerHTML = '';
                emptyMsg.classList.remove('d-none');
                emptyMsg.querySelector('p.fw-bold').innerText = currentStatFilter === 'all'
                    ? 'No vehicles in the fleet yet.'
                    : 'No vehicles match this filter.';
                updateStatCounts();
                return;
            }
            emptyMsg.classList.add('d-none');

            const visibleModels = new Map();
            visibleFleet.forEach(function (vehicle) {
                if (!visibleModels.has(vehicle.name)) visibleModels.set(vehicle.name, []);
                visibleModels.get(vehicle.name).push(vehicle);
            });

            grid.innerHTML = Array.from(visibleModels.entries()).map(function(entry, index) {
                const modelName = entry[0];
                const filteredUnits = entry[1];
                const units = fleet.filter(function (vehicle) { return vehicle.name === modelName; });
                let selectedId = selectedUnitByModel[modelName];
                if (!units.some(function (unit) { return unit.id === selectedId; })) {
                    const preferred = filteredUnits.find(function (unit) { return unit.status === 'Available'; }) || filteredUnits[0];
                    selectedId = preferred.id;
                    selectedUnitByModel[modelName] = selectedId;
                }
                const v = units.find(function (unit) { return unit.id === selectedId; }) || filteredUnits[0];
                const st = statusInfo[v.status] || statusInfo['Available'];
                const photoSrc = v.photo && v.photo.trim() !== ''
                    ? v.photo
                    : 'https://via.placeholder.com/280x140?text=' + encodeURIComponent(v.name);
                const unitNumber = units.findIndex(function (unit) { return unit.id === v.id; }) + 1;
                const availableCount = units.filter(function (unit) { return unit.status === 'Available'; }).length;

                const bookBtn = st.canBook
                    ? '<button class="btn btn-dark card-action-btn" onclick="openBookModal(' + v.id + ')"><i class="fas fa-calendar-plus me-1"></i>Book This Unit</button>'
                    : '<button class="btn btn-secondary card-action-btn disabled">Unavailable</button>';

                return '' +
                '<div class="col-md-6" id="vehicle-card-' + v.id + '">' +
                    '<div class="vehicle-card">' +
                        '<span class="status-badge ' + st.class + '">' + v.status + '</span>' +
                        '<div class="vehicle-photo-wrap"><img src="' + photoSrc + '" class="img-fluid"></div>' +
                        '<div class="p-4 pt-0">' +
                            '<div class="d-flex justify-content-between align-items-start mb-1">' +
                                '<div>' +
                                    '<h5 class="fw-bold mb-0">' + escapeHtml(modelName) + ' Unit #' + unitNumber + '</h5>' +
                                    (v.brand_name ? '<small class="text-muted d-block">' + escapeHtml(v.brand_name) + '</small>' : '') +
                                    '<span class="plate-number">' + escapeHtml(v.plate) + '</span>' +
                                    '<small class="text-muted d-block mt-1" style="font-size:0.72rem;"><i class="fas fa-history me-1"></i>' + v.timesRented + ' Times Rented</small>' +
                                '</div>' +
                                '<div class="text-end">' +
                                    '<div class="rating-stars">' + renderStars(v.rating) + '</div>' +
                                    '<a class="view-feedback" onclick="openDetailsModal(' + v.id + ')">' + v.feedbacks.length + ' Feedback' + (v.feedbacks.length !== 1 ? 's' : '') + '</a>' +
                                '</div>' +
                            '</div>' +
                            '<div class="d-flex flex-wrap gap-2 mb-3 mt-2">' +
                                '<button type="button" class="car-info-tag clickable" data-model-name="' + escapeHtml(modelName) + '" onclick="openUnitStockModalFromButton(this)"><i class="fas fa-layer-group me-1"></i>Stock: ' + units.length + ' Unit' + (units.length === 1 ? '' : 's') + ' (' + availableCount + ' available)</button>' +
                                (v.category ? '<span class="car-info-tag category-tag">' + escapeHtml(displayVehicleCategory(v.category)) + '</span>' : '') +
                                '<button type="button" class="specifications-button" onclick="openVehicleSpecifications(' + v.id + ')"><i class="fas fa-list-ul" aria-hidden="true"></i>View Specifications</button>' +
                                (function() {
                                    const realDamageCount = v.damageLog.filter(function(d){ return d.hasDamage !== false; }).length;
                                    return realDamageCount > 0
                                        ? '<span class="car-info-tag clickable text-danger" style="border-color:#dc3545;" onclick="openDamageLogView(' + v.id + ')"><i class="fas fa-tools me-1"></i>' + realDamageCount + ' Damage Log' + (realDamageCount !== 1 ? 's' : '') + '</span>'
                                        : '<span class="car-info-tag" style="color:#198754; border-color:#b8e6c8;"><i class="fas fa-check-circle me-1"></i>No Damage Logs</span>';
                                })() +
                            '</div>' +
                            '<div class="vehicle-card-footer">' +
                            renderScheduleBanner(v) +
                            '<div class="d-flex justify-content-between align-items-center mb-3">' +
                                '<div><span class="h5 fw-bold mb-0">₱' + v.price.toLocaleString() + '</span><small class="text-muted">/day</small></div>' +
                                '<button class="btn btn-outline-dark btn-sm card-action-btn" onclick="openDetailsModal(' + v.id + ')"><i class="fas fa-info-circle me-1"></i>View Details</button>' +
                            '</div>' +
                            '<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">' +
                                bookBtn +
                                '<div class="d-flex gap-1">' +
                                    '<button class="btn btn-outline-success btn-sm card-action-btn" onclick="openAddUnitForModel(' + v.id + ')"><i class="fas fa-plus"></i> Add Unit</button>' +
                                    ((['Reserved', 'Rented', 'Ongoing'].includes(v.status))
                                        ? '<button class="btn btn-outline-secondary btn-sm card-action-btn" disabled title="Reserved or ongoing vehicles cannot be edited"><i class="fas fa-lock"></i> Edit Locked</button>'
                                        : '<button class="btn btn-outline-primary btn-sm card-action-btn" onclick="openEditModal(' + v.id + ')"><i class="fas fa-edit"></i> Edit</button>') +
                                    '<button class="btn btn-outline-danger btn-sm card-action-btn" onclick="deleteVehicle(' + v.id + ')"><i class="fas fa-trash"></i> Remove</button>' +
                            '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }).join('');

            updateStatCounts();
        }

        function openUnitStockModalFromButton(button) {
            openUnitStockModal(button.dataset.modelName);
        }

        function openVehicleSpecifications(vehicleId) {
            const vehicle = fleet.find(function (unit) { return Number(unit.id) === Number(vehicleId); });
            if (!vehicle) return;

            const units = fleet.filter(function (unit) { return unit.name === vehicle.name; });
            const unitNumber = units.findIndex(function (unit) { return Number(unit.id) === Number(vehicle.id); }) + 1;
            document.getElementById('vehicleSpecificationsTitle').textContent =
                vehicle.name + ' Unit #' + unitNumber + ' Specifications';

            const specifications = [
                ['Category', displayVehicleCategory(vehicle.category)],
                ['Transmission Type', vehicle.transmission],
                ['Fuel Type', vehicle.fuel],
                ['Capacity', vehicle.capacity_type || (vehicle.capacity ? vehicle.capacity + ' Seater' : '')]
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

        function openUnitStockModal(modelName) {
            const units = fleet.filter(function (unit) { return unit.name === modelName; });
            document.getElementById('unitStockTitle').textContent = modelName + ' Units';
            const list = document.getElementById('unitStockList');
            list.replaceChildren();
            units.forEach(function (unit, index) {
                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                const details = document.createElement('span');
                details.className = 'text-start';
                const title = document.createElement('strong');
                title.textContent = modelName + ' Unit #' + (index + 1);
                const plate = document.createElement('small');
                plate.className = 'd-block';
                plate.textContent = 'Plate: ' + (unit.plate || 'Not assigned');
                details.append(title, plate);
                const status = document.createElement('span');
                status.className = 'badge ' + (unit.status === 'Available'
                    ? 'text-bg-success'
                    : (unit.status === 'Maintenance' ? 'text-bg-warning' : 'text-bg-primary'));
                status.textContent = unit.status;
                row.append(details, status);
                row.addEventListener('click', function () {
                    selectedUnitByModel[modelName] = unit.id;
                    currentStatFilter = 'all';
                    renderFleet();
                    bootstrap.Modal.getInstance(document.getElementById('unitStockModal')).hide();
                });
                list.appendChild(row);
            });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('unitStockModal')).show();
        }

        function openAddUnitForModel(vehicleId) {
            const vehicle = fleet.find(function (unit) { return unit.id === vehicleId; });
            if (!vehicle) return;
            document.getElementById('addName').value = vehicle.name;
            document.getElementById('addName').disabled = true;
            document.getElementById('addVehicleModalTitle').textContent = 'ADD UNIT — ' + vehicle.name;
            document.getElementById('addBrandName').value = vehicle.brand_name || '';
            document.getElementById('addCategory').value = normalizeVehicleCategory(vehicle.category);
            document.getElementById('addTransmission').value = vehicle.transmission || 'Manual';
            document.getElementById('addFuel').value = vehicle.fuel || 'Gasoline';
            document.getElementById('addCapacityType').value = vehicle.capacity_type || (vehicle.capacity + ' Seater');
            document.getElementById('addStatus').value = 'available';
            document.getElementById('addPhoto').value = vehicle.imagePath || '';
            document.getElementById('addPhotoFile').value = '';
            setPhotoPreview('add', vehicle.photo || '');
            syncVehicleCategoryPrice('add');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addVehicleModal')).show();
        }

        // ================= STAR RATING RENDERER =================
        function renderStars(rating) {
            let html = '';
            for (let i = 1; i <= 5; i++) {
                if (rating >= i) html += '<i class="fas fa-star"></i>';
                else if (rating >= i - 0.5) html += '<i class="fas fa-star-half-alt"></i>';
                else html += '<i class="far fa-star"></i>';
            }
            return html;
        }

        // ================= STAT CARDS: auto-count =================
        function updateStatCounts() {
            const total = fleet.length;
            const available = fleet.filter(function(v){ return v.status === 'Available'; }).length;
            const rented = fleet.filter(function(v){ return v.status === 'Ongoing'; }).length;
            const maintenance = fleet.filter(function(v){ return v.status === 'Maintenance'; }).length;

            document.getElementById('statTotal').innerText = total;
            document.getElementById('statAvailable').innerText = available;
            document.getElementById('statRented').innerText = rented;
            document.getElementById('statMaintenance').innerText = maintenance;
        }

        // ================= VEHICLE PHOTO UPLOAD (no backend yet — kept in browser memory) =================
        // Converts a locally-picked photo to a base64 data URL so it can be
        // previewed and stored on the vehicle object right away. Once the Laravel
        // backend is connected, this should instead upload the file via FormData
        // to something like POST /api/vehicles/{id}/photo and store the returned
        // URL here instead of the base64 string.
        function handlePhotoUpload(input, prefix) {
            const file = input.files && input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(prefix + 'Photo').value = e.target.result;
                setPhotoPreview(prefix, e.target.result);
            };
            reader.readAsDataURL(file);
        }

        // Lets the preview also update if the admin/staff types/pastes a URL
        // manually instead of uploading a file.
        function handlePhotoUrlInput(prefix) {
            const url = document.getElementById(prefix + 'Photo').value.trim();
            setPhotoPreview(prefix, url);
        }

        function setPhotoPreview(prefix, src) {
            const preview = document.getElementById(prefix + 'PhotoPreview');
            const icon = document.getElementById(prefix + 'PhotoPreviewIcon');
            if (src) {
                preview.src = src;
                preview.classList.remove('d-none');
                icon.classList.add('d-none');
            } else {
                preview.src = '';
                preview.classList.add('d-none');
                icon.classList.remove('d-none');
            }
        }

        function resetPhotoPreview(prefix) {
            setPhotoPreview(prefix, '');
        }

        const cityDrivingPrices = @json($priceGuides->mapWithKeys(fn ($guide) => [$guide->category => (float) $guide->city_driving]));

        function cityDrivingPrice(category) {
            const normalized = String(category || '').toLowerCase();
            if (normalized.includes('diesel')) return cityDrivingPrices['Expanded — Diesel'];
            if (normalized.includes('pick-up') || normalized.includes('pickup') || normalized.includes('gas')) {
                return cityDrivingPrices['Pick-up / Expanded (7-Seater) (2 Days) — Gas'];
            }
            return cityDrivingPrices.Sedan;
        }

        function syncVehicleCategoryPrice(prefix) {
            const category = document.getElementById(prefix + 'Category').value;
            document.getElementById(prefix + 'Price').value = cityDrivingPrice(category);
        }

        function normalizeVehicleCategory(category) {
            const normalized = String(category || '').toLowerCase();
            if (normalized.includes('diesel')) return 'Expanded — Diesel';
            if (normalized.includes('pick-up') || normalized.includes('pickup') || normalized.includes('gas')) {
                return 'Pick-up / Expanded (7-Seater) (2 Days) — Gas';
            }
            return 'Sedan';
        }

        function displayVehicleCategory(category) {
            const normalized = String(category || '').toLowerCase();
            if (normalized.includes('diesel')) {
                return 'Expanded';
            }
            if (normalized.includes('pick-up') || normalized.includes('pickup')) {
                return 'Pick-up / Expanded';
            }
            return category;
        }

        function resetAddVehicleForm() {
            document.getElementById('addName').value = '';
            document.getElementById('addName').disabled = false;
            document.getElementById('addBrandName').value = '';
            document.getElementById('addPlate').value = '';
            document.getElementById('addCategory').value = 'Sedan';
            document.getElementById('addTransmission').value = 'Manual';
            document.getElementById('addFuel').value = 'Gasoline';
            document.getElementById('addCapacityType').value = '5 Seater';
            document.getElementById('addStatus').value = 'available';
            document.getElementById('addPhoto').value = '';
            document.getElementById('addPhotoFile').value = '';
            document.getElementById('addVehicleError').innerHTML = '';
            document.getElementById('addVehicleModalTitle').textContent = 'ADD NEW VEHICLE UNIT';
            syncVehicleCategoryPrice('add');
            resetPhotoPreview('add');
        }

        // ================= ADD VEHICLE =================
        async function saveNewVehicle() {
            const errorArea = document.getElementById('addVehicleError');
            errorArea.innerHTML = '';

            const name = document.getElementById('addName').value.trim();
            const plate = document.getElementById('addPlate').value.trim();
            const category = document.getElementById('addCategory').value;
            const price = cityDrivingPrice(category);
            document.getElementById('addPrice').value = price;

            if (!name || !plate || !price) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mt-3 mb-0 small fw-bold">Please fill out Vehicle Name, Plate No., and Price/Day.</div>';
                showToast('Please complete the required fields.', 'error');
                return;
            }

            if (!(await showConfirm('Add "' + name + '" to the fleet?'))) {
                showToast('Cancelled — vehicle was not added.', 'cancel');
                return;
            }

            const formData = new FormData();
            formData.append('name', name);
            formData.append('brand_name', document.getElementById('addBrandName').value.trim());
            formData.append('plate', plate);
            formData.append('price', price);
            formData.append('category', category);
            formData.append('transmission', document.getElementById('addTransmission').value);
            formData.append('fuel', document.getElementById('addFuel').value);
            const addCapacityType = document.getElementById('addCapacityType').value;
            formData.append('capacity_type', addCapacityType);
            formData.append('capacity', addCapacityType.split(' ')[0]);
            const addStatus = document.getElementById('addStatus').value;
            formData.append('status', addStatus);
            const imageFile = document.getElementById('addPhotoFile').files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            } else {
                formData.append('image_path', document.getElementById('addPhoto').value.trim());
            }

            const response = await fetch(@json($vehicleStoreUrl), {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
                body: formData
            });
            if (!response.ok) {
                let message = 'Vehicle could not be added.';
                try {
                    const errorResponse = await response.json();
                    const validationErrors = errorResponse.errors ? Object.values(errorResponse.errors).flat() : [];
                    message = validationErrors[0] || errorResponse.message || message;
                } catch (error) {
                    // Keep the generic message when the server does not return JSON.
                }
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mt-3 mb-0 small fw-bold">' + message + '</div>';
                showToast(message, 'error');
                return;
            }
            const savedVehicle = await response.json();
            fleet.push({
                id: savedVehicle.vehicle.id,
                name: name, brand_name: document.getElementById('addBrandName').value.trim(), plate: plate, price: price,
                category: document.getElementById('addCategory').value,
                transmission: document.getElementById('addTransmission').value,
                fuel: document.getElementById('addFuel').value,
                capacity: addCapacityType.split(' ')[0], capacity_type: addCapacityType,
                status: addStatus === 'maintenance' ? 'Maintenance' : 'Available',
                photo: savedVehicle.vehicle.image_path
                    ? '{{ url('/storage') }}/' + savedVehicle.vehicle.image_path
                    : document.getElementById('addPhoto').value.trim(),
                imagePath: savedVehicle.vehicle.image_path || '',
                rating: 0, timesRented: 0, feedbacks: [], damageLog: []
            });
            selectedUnitByModel[name] = savedVehicle.vehicle.id;

            renderFleet();

            bootstrap.Modal.getInstance(document.getElementById('addVehicleModal')).hide();
            showToast('Vehicle added successfully!', 'success');

        }

        document.getElementById('addVehicleModal').addEventListener('hidden.bs.modal', function () {
            resetAddVehicleForm();
        });

        // ================= EDIT VEHICLE =================
        function openEditModal(id) {
            const v = fleet.find(function(x){ return x.id === id; });
            if (!v) return;
            if (['Reserved', 'Rented', 'Ongoing'].includes(v.status)) {
                showToast('Reserved or ongoing vehicles cannot be edited.', 'error');
                return;
            }

            document.getElementById('editId').value = v.id;
            document.getElementById('editName').value = v.name;
            document.getElementById('editBrandName').value = v.brand_name || '';
            document.getElementById('editPlate').value = v.plate;
            document.getElementById('editPrice').value = v.price;
            document.getElementById('editCategory').value = normalizeVehicleCategory(v.category);
            syncVehicleCategoryPrice('edit');
            document.getElementById('editStatus').value = v.status;
            document.getElementById('editTransmission').value = v.transmission;
            document.getElementById('editFuel').value = v.fuel;
            document.getElementById('editCapacityType').value = v.capacity_type || (v.capacity ? v.capacity + ' Seater' : '5 Seater');
            document.getElementById('editPhoto').value = v.photo;
            const currentSchedule = (v.schedule || []).find(function (entry) {
                return entry.type === v.status || (v.status === 'Rented' && entry.type === 'Rented');
            });
            document.getElementById('editScheduleStart').value = currentSchedule ? currentSchedule.start : '';
            document.getElementById('editScheduleEnd').value = currentSchedule ? currentSchedule.end : '';
            document.getElementById('editPhotoFile').value = '';
            setPhotoPreview('edit', v.photo);

            var editModal = new bootstrap.Modal(document.getElementById('editVehicleModal'));
            editModal.show();
        }

        async function saveEditedVehicle() {
            const id = parseInt(document.getElementById('editId').value);
            const idx = fleet.findIndex(function(x){ return x.id === id; });
            if (idx === -1) return;
            const previousStatus = fleet[idx].status;
            const editedStatus = document.getElementById('editStatus').value;
            const scheduleStart = document.getElementById('editScheduleStart').value;
            const scheduleEnd = document.getElementById('editScheduleEnd').value;
            if (['Rented', 'Maintenance'].includes(editedStatus) && (!scheduleStart || !scheduleEnd)) {
                showToast('Please enter the start and end dates for this status.', 'error');
                return;
            }
            if (scheduleStart && scheduleEnd && scheduleEnd < scheduleStart) {
                showToast('End date cannot be earlier than start date.', 'error');
                return;
            }

            const requiresStatusConfirmation = previousStatus !== editedStatus;
            const confirmationMessage = requiresStatusConfirmation
                ? 'Change this vehicle status from ' + previousStatus + ' to ' + editedStatus + '?'
                : 'Save changes to this vehicle?';

            if (!(await showConfirm(confirmationMessage, requiresStatusConfirmation ? 5 : 0))) {
                showToast('Cancelled — changes were not saved.', 'cancel');
                return;
            }

            fleet[idx] = Object.assign({}, fleet[idx], {
                name: document.getElementById('editName').value.trim(),
                brand_name: document.getElementById('editBrandName').value.trim(),
                plate: document.getElementById('editPlate').value.trim(),
                price: cityDrivingPrice(document.getElementById('editCategory').value),
                category: document.getElementById('editCategory').value,
                status: editedStatus,
                transmission: document.getElementById('editTransmission').value,
                fuel: document.getElementById('editFuel').value,
                capacity: document.getElementById('editCapacityType').value.split(' ')[0],
                capacity_type: document.getElementById('editCapacityType').value,
                photo: document.getElementById('editPhoto').value.trim()
            });
            fleet[idx].schedule = (fleet[idx].schedule || []).filter(function (entry) {
                return !['Reserved', 'Rented', 'Maintenance'].includes(entry.type);
            });
            if (scheduleStart && scheduleEnd && ['Rented', 'Maintenance'].includes(editedStatus)) {
                fleet[idx].schedule.push({
                    id: Date.now(),
                    vehicleId: id,
                    type: editedStatus,
                    start: scheduleStart,
                    end: scheduleEnd,
                    notes: editedStatus === 'Rented' ? 'Ongoing rental' : (fleet[idx].maintenance_notes || 'Maintenance')
                });
            }

            scheduleEvents = scheduleEvents.filter(function (entry) {
                return entry.vehicleId !== id || !['Reserved', 'Rented', 'Maintenance'].includes(entry.type);
            });
            (fleet[idx].schedule || []).forEach(function (entry) {
                if (entry.type === 'Maintenance' || ['Reserved', 'Rented'].includes(entry.type)) {
                    scheduleEvents.push(Object.assign({}, entry, { vehicleId: id }));
                }
            });
            renderFleet();
            renderAdminCalendar();
            bootstrap.Modal.getInstance(document.getElementById('editVehicleModal')).hide();
            showToast('Vehicle updated successfully!', 'success');

            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('name', fleet[idx].name);
            formData.append('brand_name', fleet[idx].brand_name || '');
            formData.append('plate', fleet[idx].plate);
            formData.append('price', fleet[idx].price);
            formData.append('category', fleet[idx].category);
            formData.append('transmission', fleet[idx].transmission);
            formData.append('fuel', fleet[idx].fuel);
            formData.append('capacity', fleet[idx].capacity);
            formData.append('capacity_type', fleet[idx].capacity_type || (fleet[idx].capacity ? fleet[idx].capacity + ' Seater' : ''));
            formData.append('status', fleet[idx].status.toLowerCase());
            formData.append('schedule', JSON.stringify(fleet[idx].schedule || []));
            const imageFile = document.getElementById('editPhotoFile').files[0];
            if (imageFile) formData.append('image', imageFile);
            else formData.append('image_path', fleet[idx].imagePath || '');
            const response = await fetch(@json($vehicleBaseUrl) + '/' + id, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
                body: formData
            });
            if (!response.ok) {
                showToast('Vehicle could not be updated.', 'error');
                return;
            }
            const savedVehicle = await response.json();
            fleet[idx].imagePath = savedVehicle.vehicle.image_path || '';
            fleet[idx].photo = savedVehicle.vehicle.image_path
                ? '{{ url('/storage') }}/' + savedVehicle.vehicle.image_path
                : fleet[idx].photo;
        }

        // ================= REMOVE VEHICLE =================
        async function deleteVehicle(id) {
            const v = fleet.find(function(x){ return x.id === id; });
            if (!v) return;
            // 3-second countdown before Confirm is clickable — prevents an
            // accidental/rushed delete of a vehicle from the fleet.
            if (await showConfirm('Are you sure you want to remove "' + v.name + '" (' + v.plate + ')?', 3)) {
                const loader = document.getElementById(isStaffMode ? 'staffPageLoader' : 'adminPageLoader');
                loader?.classList.add('is-visible');
                loader?.setAttribute('aria-hidden', 'false');
                try {
                    const response = await fetch(@json($vehicleBaseUrl) + '/' + id, {
                        method: 'DELETE',
                        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json'}
                    });
                    const result = await response.json().catch(function () { return {}; });
                    if (!response.ok) {
                        showToast(result.message || 'Vehicle could not be removed. Please refresh and try again.', 'error');
                        return;
                    }
                    fleet = fleet.filter(function(x){ return x.id !== id; });
                    renderFleet();
                    showToast(result.message || 'Vehicle removed successfully!', 'success');
                } catch (error) {
                    showToast(error.message || 'Vehicle could not be removed.', 'error');
                } finally {
                    loader?.classList.remove('is-visible');
                    loader?.setAttribute('aria-hidden', 'true');
                }

            } else {
                showToast('Cancelled — vehicle was not removed.', 'cancel');
            }
        }

        // ================= ADMIN BOOKING (WALK-IN / PHONE) =================
        let bookDriverFee = 0;
        let bookPayMode = 'full';
        let bookRateType = 'city';
        let currentBookVehicleId = null;
        let bookStep = 1;
        const bookingToday = new Date().toISOString().slice(0, 10);
        const bookingBirthDateMax = new Date();
        bookingBirthDateMax.setFullYear(bookingBirthDateMax.getFullYear() - 18);
        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (character) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
            });
        }

        async function loadBookProvinces() {
            const province = document.getElementById('b-delivery-province');
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

        async function loadBookCities() {
            const province = document.getElementById('b-delivery-province');
            const city = document.getElementById('b-delivery-city');
            const barangay = document.getElementById('b-delivery-barangay');
            city.innerHTML = '<option value="">Loading cities / municipalities...</option>';
            city.disabled = true;
            barangay.innerHTML = '<option value="">Select city / municipality first</option>';
            barangay.disabled = true;
            const code = province.selectedOptions[0]?.dataset.code;
            if (!code) return;
            try {
                const response = await fetch('https://psgc.gitlab.io/api/provinces/' + code + '/cities-municipalities/');
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

        async function loadBookBarangays() {
            const city = document.getElementById('b-delivery-city');
            const barangay = document.getElementById('b-delivery-barangay');
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

        document.getElementById('b-delivery-province')?.addEventListener('change', loadBookCities);
        document.getElementById('b-delivery-city')?.addEventListener('change', loadBookBarangays);

        function openBookModal(id) {
            const v = fleet.find(function(x){ return x.id === id; });
            if (!v) return;

            currentBookVehicleId = id;
            document.getElementById('book-vehicle-id').value = id;
            document.getElementById('bookCarName').innerText = v.name + ' (' + v.plate + ')';

            document.getElementById('b-name').value = '';
            document.getElementById('b-phone').value = '';
            document.getElementById('b-age').value = '';
            document.getElementById('b-birth-date').value = '';
            document.getElementById('b-birth-date').max = bookingBirthDateMax.toISOString().slice(0, 10);
            document.getElementById('b-start').min = bookingToday;
            document.getElementById('b-end').min = bookingToday;
            loadBookProvinces();
            document.getElementById('b-email').value = '';
            document.getElementById('b-gcash-reference').value = '';
            ['b-delivery-province', 'b-delivery-city', 'b-delivery-barangay', 'b-delivery-street', 'b-delivery-notes'].forEach(function (id) {
                document.getElementById(id).value = '';
            });
            document.getElementById('b-delivery-city').disabled = true;
            document.getElementById('b-delivery-barangay').disabled = true;
            document.getElementById('b-start').value = '';
            document.getElementById('b-end').value = '';
            document.getElementById('b-start-time').value = '09:00';
            document.getElementById('b-end-time').value = '09:00';
            document.getElementById('b-driver-id').value = '';
            document.getElementById('bookError').innerHTML = '';
            setBookService('pickup');
            setBookDriver(0);
            setBookPay('full');
            setBookRateType('city');
            setBookStep(1);
            document.getElementById('bookGuide').classList.add('d-none');
            document.getElementById('bookReviewPanel').classList.add('d-none');
            document.getElementById('bookReviewContent').innerHTML = '';
            document.getElementById('bookFormArea').style.display = 'block';
            document.getElementById('bookConfirmArea').style.display = 'none';
            document.getElementById('bookFormFooter').style.display = 'flex';
            updateBookCompute(v.price);

            const modal = new bootstrap.Modal(document.getElementById('adminBookModal'));
            modal.show();
        }

        function setBookService(t) {
            document.getElementById('b-opt-pickup').classList.toggle('active', t === 'pickup');
            document.getElementById('b-opt-deliver').classList.toggle('active', t === 'delivery');
            document.getElementById('b-delivery-info').style.display = (t === 'delivery') ? 'block' : 'none';
        }

        function setBookStep(step) {
            bookStep = step;
            document.getElementById('bookError').innerHTML = '';
            [1, 2, 3].forEach(function (number) {
                document.getElementById('bookStep' + number).classList.toggle('d-none', number !== step);
            });
            document.getElementById('bookStepLabel').textContent = 'STEP ' + step + ' OF 3';
            document.getElementById('confirmAdminBookingButton').classList.add('d-none');
        }

        function hideBookReview() {
            document.getElementById('bookReviewPanel').classList.add('d-none');
            document.getElementById('bookReviewButton').classList.remove('d-none');
            document.getElementById('confirmAdminBookingButton').classList.add('d-none');
        }

        function showBookReview() {
            const errorArea = document.getElementById('bookError');
            errorArea.innerHTML = '';
            const start = document.getElementById('b-start').value;
            const end = document.getElementById('b-end').value;
            const durationSeconds = getBookDurationSeconds();
            const days = getBookRentalDays();
            const gcashReference = document.getElementById('b-gcash-reference').value.replace(/\s+/g, '');

            if (!start || !end || durationSeconds <= 0 || days > getBookMaximumDays()
                || (bookRateType === 'long_distance' && durationSeconds < 2 * 86400)) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Please select valid pickup and return dates and times within the allowed rental duration.</div>';
                return;
            }
            if (bookPayMode !== 'cash' && !/^\d{13,17}$/.test(gcashReference)) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Please enter a valid GCash reference.</div>';
                return;
            }

            const v = fleet.find(function (x) { return x.id === currentBookVehicleId; });
            const service = document.getElementById('b-opt-deliver').classList.contains('active') ? 'Deliver to Customer' : 'Pickup at Garage';
            const driver = bookDriverFee > 0 ? 'With Driver' : 'Self Drive';
            const payment = bookPayMode === 'cash' ? 'Walk-in / Cash' : (bookPayMode === 'dep' ? 'Deposit' : 'Full Payment');
            const address = [
                document.getElementById('b-delivery-province').value,
                document.getElementById('b-delivery-city').value,
                document.getElementById('b-delivery-barangay').value,
                document.getElementById('b-delivery-street').value
            ].filter(Boolean).join(', ') || 'Main Office';
            const deliveryNotes = document.getElementById('b-delivery-notes').value.trim();
            const total = document.getElementById('b-rec-now').innerText;
            document.getElementById('bookReviewContent').innerHTML =
                '<div class="row g-2">' +
                '<div class="col-6"><b>Name:</b> ' + escapeHtml(document.getElementById('b-name').value) + '</div>' +
                '<div class="col-6"><b>Contact:</b> ' + escapeHtml(document.getElementById('b-phone').value) + '</div>' +
                '<div class="col-6"><b>Birthday:</b> ' + escapeHtml(document.getElementById('b-birth-date').value) + '</div>' +
                '<div class="col-6"><b>Age:</b> ' + escapeHtml(document.getElementById('b-age').value) + '</div>' +
                '<div class="col-12"><b>Email:</b> ' + escapeHtml(document.getElementById('b-email').value) + '</div>' +
                '<div class="col-12"><b>Address:</b> ' + escapeHtml(address) + '</div>' +
                (deliveryNotes ? '<div class="col-12"><b>Delivery Notes:</b> ' + escapeHtml(deliveryNotes) + '</div>' : '') +
                '<div class="col-6"><b>Service:</b> ' + service + '</div>' +
                '<div class="col-6"><b>Driver:</b> ' + driver + (bookDriverFee > 0 ? ' - ' + escapeHtml(document.getElementById('b-driver-id').selectedOptions[0]?.textContent || '') : '') + '</div>' +
                '<div class="col-6"><b>Rental Rate:</b> ' + escapeHtml(bookRateType.replace('_', ' ')) + '</div>' +
                '<div class="col-6"><b>Vehicle:</b> ' + escapeHtml(v ? v.name : '') + '</div>' +
                '<div class="col-6"><b>Pickup:</b> ' + escapeHtml(start) + ' at ' + escapeHtml(document.getElementById('b-start-time').value) + '</div>' +
                '<div class="col-6"><b>Return:</b> ' + escapeHtml(end) + ' at ' + escapeHtml(document.getElementById('b-end-time').value) + '</div>' +
                '<div class="col-6"><b>Payment:</b> ' + payment + '</div>' +
                '<div class="col-6"><b>GCash Reference:</b> ' + escapeHtml(gcashReference || 'N/A') + '</div>' +
                '<div class="col-12 mt-2"><b>Pay Now:</b> <span class="text-danger fw-bold">' + escapeHtml(total) + '</span></div>' +
                '</div>';
            document.getElementById('bookReviewPanel').classList.remove('d-none');
            document.getElementById('bookReviewButton').classList.add('d-none');
            document.getElementById('confirmAdminBookingButton').classList.remove('d-none');
        }

        function nextBookStep() {
            const errorArea = document.getElementById('bookError');
            errorArea.innerHTML = '';
            if (bookStep === 1) {
                const required = ['b-name', 'b-phone', 'b-birth-date', 'b-email'];
                const missingField = required.find(function (id) {
                    return !document.getElementById(id).value.trim();
                });
                if (missingField) {
                    errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Please complete the customer information first.</div>';
                    document.getElementById(missingField).focus();
                    return;
                }
                const emailInput = document.getElementById('b-email');
                if (!emailInput.validity.valid) {
                    errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Please enter a valid customer email address.</div>';
                    emailInput.focus();
                    return;
                }
                calculateBookAge();
                if (Number(document.getElementById('b-age').value) < 18) {
                    errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Customer must be 18 years old or above.</div>';
                    document.getElementById('b-birth-date').focus();
                    return;
                }
                if (document.getElementById('b-opt-deliver').classList.contains('active')) {
                    const addressIds = ['b-delivery-province', 'b-delivery-city', 'b-delivery-barangay'];
                    if (addressIds.some(function (id) { return !document.getElementById(id).value.trim(); })) {
                        errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Please complete the Province, City / Municipality, and Barangay first.</div>';
                        return;
                    }
                    if (!document.getElementById('b-delivery-street').value.trim()) {
                        errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Enter the Block & Lot, street, or familiar drop-off point.</div>';
                        return;
                    }
                }
            }
            if (bookStep === 2) {
                const start = document.getElementById('b-start').value;
                const end = document.getElementById('b-end').value;
                const durationSeconds = getBookDurationSeconds();
                const days = getBookRentalDays();
                if (!start || !end || durationSeconds <= 0 || days > getBookMaximumDays()
                    || (bookRateType === 'long_distance' && durationSeconds < 2 * 86400)) {
                    errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Please select valid pickup and return dates and times within the allowed rental duration.</div>';
                    return;
                }
                updateBookDriverOptions();
                if (bookDriverFee > 0 && !document.getElementById('b-driver-id').value) {
                    errorArea.innerHTML = '<div class="alert alert-danger py-2 small fw-bold">Please select an available driver for these dates.</div>';
                    return;
                }
            }
            setBookStep(bookStep + 1);
        }

        function setBookDriver(fee) {
            bookDriverFee = fee;
            document.getElementById('b-drv-self').classList.toggle('active', fee === 0);
            document.getElementById('b-drv-with').classList.toggle('active', fee > 0);
            document.getElementById('b-driver-select-wrap').classList.toggle('d-none', fee === 0);
            if (fee > 0) {
                updateBookDriverOptions();
            } else {
                document.getElementById('b-driver-id').value = '';
            }
            updateBookCompute();
        }

        function updateBookDriverOptions() {
            const select = document.getElementById('b-driver-id');
            const start = document.getElementById('b-start').value;
            const end = document.getElementById('b-end').value;
            const previousValue = select.value;
            const availableDrivers = bookDriverCatalog.filter(function (driver) {
                if (!driver.availableThisWeek) return false;
                if (!start || !end) return true;
                return !(driver.bookings || []).some(function (booking) {
                    return booking.start <= end && booking.end >= start;
                });
            });

            select.innerHTML = '<option value="">Select an available driver</option>';
            availableDrivers.forEach(function (driver) {
                const option = document.createElement('option');
                option.value = String(driver.id);
                option.textContent = driver.name + ' - ' + driver.contact;
                select.appendChild(option);
            });
            if (availableDrivers.some(function (driver) { return String(driver.id) === previousValue; })) {
                select.value = previousValue;
            }
            document.getElementById('b-driver-hint').textContent = availableDrivers.length
                ? 'Driver availability is managed in Driver Management.'
                : 'No drivers are available for the selected dates. Check Driver Management or choose different dates.';
        }

        function setBookPay(mode) {
            bookPayMode = mode;
            document.getElementById('b-pay-full').classList.toggle('active', mode === 'full');
            document.getElementById('b-pay-dep').classList.toggle('active', mode === 'dep');
            document.getElementById('b-pay-cash').classList.toggle('active', mode === 'cash');
            document.getElementById('b-gcash-fields').classList.toggle('d-none', mode === 'cash');
            updateBookCompute();
        }

        function calculateBookAge() {
            const birthDate = document.getElementById('b-birth-date').value;
            const ageInput = document.getElementById('b-age');
            if (!birthDate) {
                ageInput.value = '';
                return;
            }
            const birth = new Date(birthDate + 'T00:00:00');
            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const birthdayNotReached = today.getMonth() < birth.getMonth()
                || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate());
            if (birthdayNotReached) age--;
            ageInput.value = age >= 0 ? age : '';
        }

        function toggleBookGuide(event) {
            if (event) event.preventDefault();
            document.getElementById('bookGuide').classList.toggle('d-none');
        }

        function getBookMaximumDays() {
            return bookRateType === 'province' ? 14 : (bookRateType === 'long_distance' ? 30 : 7);
        }

        function getBookDurationSeconds() {
            const start = document.getElementById('b-start').value;
            const end = document.getElementById('b-end').value;
            const startTime = document.getElementById('b-start-time').value;
            const endTime = document.getElementById('b-end-time').value;
            if (!start || !end || !startTime || !endTime) return 0;
            return (new Date(end + 'T' + endTime) - new Date(start + 'T' + startTime)) / 1000;
        }

        function syncBookReturnTimeFromPickup() {
            document.getElementById('b-end-time').value = document.getElementById('b-start-time').value;
            updateBookCompute();
        }

        function getBookDateAfter(dateValue, days) {
            const date = new Date(dateValue + 'T00:00:00');
            date.setDate(date.getDate() + days);
            return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0');
        }

        function syncBookReturnDateFromPickup() {
            const pickupDate = document.getElementById('b-start').value;
            const returnDate = document.getElementById('b-end');
            const minimumReturnDate = getBookDateAfter(pickupDate, 1);
            if (!returnDate.value || returnDate.value < minimumReturnDate) {
                returnDate.value = minimumReturnDate;
            }
            updateBookCompute();
        }

        function getBookRentalDays() {
            const durationSeconds = getBookDurationSeconds();
            return durationSeconds > 0 ? Math.ceil(durationSeconds / 86400) : 0;
        }

        function setBookRateType(type) {
            bookRateType = type;
            const start = document.getElementById('b-start');
            const end = document.getElementById('b-end');
            const hint = document.getElementById('b-date-limit-hint');
            const labels = {city: 'City Driving', province: 'Province', long_distance: 'Long Distance'};
            const maxDays = getBookMaximumDays();
            hint.innerHTML = '<strong>' + labels[type] + ' allows up to ' + maxDays + ' rental days.' +
                (type === 'long_distance' ? ' Minimum rental period: 2 full days.' : '') + '</strong>' +
                '<div class="fw-normal mt-1"><i class="fas fa-info-circle me-1"></i>You may request an extension if you need to rent the vehicle longer.</div>';
            end.min = start.value || new Date().toISOString().slice(0, 10);
            if (start.value) {
                const maxDate = new Date(start.value + 'T00:00:00');
                maxDate.setDate(maxDate.getDate() + maxDays);
                end.max = maxDate.toISOString().slice(0, 10);
            } else {
                end.removeAttribute('max');
            }
            updateBookCompute();
        }

        function updateBookCompute(overridePrice) {
            const v = fleet.find(function(x){ return x.id === currentBookVehicleId; });
            const category = String(v ? v.category : '').toLowerCase();
            const provinceSurcharge = category.includes('diesel') ? 500 : 1000;
            const basePrice = overridePrice || (v ? v.price : 0);
            const price = bookRateType === 'province'
                ? basePrice + provinceSurcharge
                : (bookRateType === 'long_distance' ? basePrice + 1500 : basePrice);
            const depositRow = document.getElementById('b-security-deposit-row');

            const startVal = document.getElementById('b-start').value;
            const endVal = document.getElementById('b-end').value;
            const startTime = document.getElementById('b-start-time');
            const endTime = document.getElementById('b-end-time');
            if (startVal) {
                const end = document.getElementById('b-end');
                end.min = getBookDateAfter(startVal, 1);
                const maxDate = new Date(startVal + 'T00:00:00');
                maxDate.setDate(maxDate.getDate() + getBookMaximumDays());
                end.max = maxDate.toISOString().slice(0, 10);
            }
            if (startVal && endVal === startVal) {
                endTime.min = startTime.value || '00:00';
            } else {
                endTime.removeAttribute('min');
            }
            if (bookDriverFee > 0) updateBookDriverOptions();
            const durationSeconds = getBookDurationSeconds();
            const days = bookRateType === 'long_distance' && durationSeconds > 0
                ? Math.max(2, getBookRentalDays())
                : Math.max(1, getBookRentalDays());

            const rentTotal = price * days;
            const driverTotal = bookDriverFee * days;
            const deposit = 1000;
            const total = rentTotal + driverTotal;

            document.getElementById('b-days').innerText = days;
            document.getElementById('b-rec-rent').innerText = '₱' + rentTotal.toLocaleString();
            document.getElementById('b-rec-driver').innerText = '₱' + driverTotal.toLocaleString();

            if (depositRow) {
                const hideDeposit = (bookPayMode === 'cash');
                depositRow.style.display = hideDeposit ? 'none' : 'flex';
                depositRow.classList.toggle('d-none', hideDeposit);
            }

            let payNow;
            if (bookPayMode === 'dep') payNow = deposit;
            else if (bookPayMode === 'cash') payNow = 0;
            else payNow = total;

            document.getElementById('b-rec-now').innerText = bookPayMode === 'cash'
                ? '₱0 (Pay at Garage)'
                : '₱' + payNow.toLocaleString();
        }

        function generateControlNumber() {
            const now = new Date();
            const datePart = now.getFullYear().toString()
                + String(now.getMonth() + 1).padStart(2, '0')
                + String(now.getDate()).padStart(2, '0');
            const randomPart = Math.floor(1000 + Math.random() * 9000);
            return 'BD-' + datePart + '-' + randomPart;
        }

        async function confirmAdminBooking() {
            const errorArea = document.getElementById('bookError');
            errorArea.innerHTML = '';

            const name = document.getElementById('b-name').value.trim();
            const phone = document.getElementById('b-phone').value.trim();
            const age = document.getElementById('b-age').value;
            const birthDate = document.getElementById('b-birth-date').value;
            const email = document.getElementById('b-email').value.trim();
            const gcashReference = document.getElementById('b-gcash-reference').value.replace(/\s+/g, '');
            const start = document.getElementById('b-start').value;
            const end = document.getElementById('b-end').value;
            const pickupTime = document.getElementById('b-start-time').value;
            const returnTime = document.getElementById('b-end-time').value;

            const durationSeconds = getBookDurationSeconds();
            const days = getBookRentalDays();
            if (!name || !phone || !birthDate || !age || !email || !start || !end || !pickupTime || !returnTime) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">Please complete customer information and both pickup/return dates and times.</div>';
                showToast('Please complete the required fields.', 'error');
                return;
            }
            if (Number(age) < 18) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">Customer must be 18 years old or above.</div>';
                return;
            }
            if (bookPayMode !== 'cash' && !/^\d{13,17}$/.test(gcashReference)) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">Please enter a valid 13-17 digit GCash reference number.</div>';
                return;
            }
            if (durationSeconds <= 0 || days > getBookMaximumDays()
                || (bookRateType === 'long_distance' && durationSeconds < 2 * 86400)) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">Selected pickup and return dates/times do not match the allowed rental duration.</div>';
                return;
            }
            if (bookDriverFee > 0 && !document.getElementById('b-driver-id').value) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">Please select an available driver for these dates.</div>';
                return;
            }

            const v = fleet.find(function(x){ return x.id === currentBookVehicleId; });
            if (!v) return;

            try {
                const response = await fetch(@json($reservationStoreUrl), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        vehicle_id: v.id,
                        customer_name: name,
                        customer_phone: phone,
                        customer_age: Number(age),
                        customer_birth_date: birthDate,
                        customer_email: email,
                        start_date: start,
                        end_date: end,
                        pickup_time: pickupTime,
                        return_time: returnTime,
                        rate_type: bookRateType,
                        service_option: document.getElementById('b-opt-deliver').classList.contains('active') ? 'delivery' : 'pickup',
                        driver_option: bookDriverFee > 0 ? 'with_driver' : 'self_drive',
                        driver_id: bookDriverFee > 0 ? document.getElementById('b-driver-id').value : null,
                        delivery_province: document.getElementById('b-delivery-province').value.trim(),
                        delivery_city: document.getElementById('b-delivery-city').value.trim(),
                        delivery_barangay: document.getElementById('b-delivery-barangay').value.trim(),
                        delivery_street: document.getElementById('b-delivery-street').value.trim(),
                        delivery_notes: document.getElementById('b-delivery-notes').value.trim(),
                        gcash_reference: gcashReference,
                        payment_mode: bookPayMode === 'dep' ? 'deposit' : (bookPayMode === 'cash' ? 'walkin' : 'full')
                    })
                });

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Booking could not be saved.');
                }

                v.status = 'Rented';
                document.getElementById('b-controlDisplay').innerText = data.control_number;

                document.getElementById('bookFormArea').style.display = 'none';
                document.getElementById('bookFormFooter').style.display = 'none';
                document.getElementById('bookConfirmArea').style.display = 'block';

                scheduleEvents.push({
                    id: nextScheduleId++,
                    vehicleId: v.id,
                    type: 'Rented',
                    start: start,
                    end: end,
                    notes: name + ' (Walk-in/Phone Booking, ' + data.control_number + ')'
                });

                renderFleet();
                showToast('Booking confirmed successfully!', 'success');
            } catch (error) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-3 small fw-bold">' + escapeHtml(error.message || 'Booking could not be saved.') + '</div>';
                showToast(error.message || 'Booking could not be saved.', 'error');
            }
        }

        // ================= VEHICLE DETAILS MODAL (Rating / Feedback / Damage Log) =================
        let currentDetailsVehicleId = null;

        function openDetailsModal(id) {
            const v = fleet.find(function(x){ return x.id === id; });
            if (!v) return;

            currentDetailsVehicleId = id;
            document.getElementById('details-vehicle-id').value = id;
            document.getElementById('detailsCarName').innerText = v.name + ' (' + v.plate + ')';
            document.getElementById('detailsStars').innerHTML = renderStars(v.rating);
            document.getElementById('detailsRatingText').innerText = v.rating.toFixed(1) + ' out of 5 (' + v.feedbacks.length + ' review' + (v.feedbacks.length !== 1 ? 's' : '') + ')';
            document.getElementById('detailsTimesRented').innerText = v.timesRented;

            const today = new Date();
            document.getElementById('dmg-today').innerText = today.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            document.getElementById('dmg-part').value = '';
            document.getElementById('dmg-desc').value = '';
            document.getElementById('dmg-status').value = 'Under Repair';
            document.getElementById('chk-fuel').checked = false;
            document.getElementById('chk-exterior').checked = false;
            document.getElementById('chk-interior').checked = false;
            document.getElementById('chk-tools').checked = false;

            renderDetailsFeedback(v);
            renderDetailsDamage(v);

            const modal = new bootstrap.Modal(document.getElementById('vehicleDetailsModal'));
            modal.show();
        }

        // Opens the same details modal but jumps straight to the Condition/Damage
        // Log tab — used internally when switching from the read-only view.
        function openDetailsModalToDamage(id) {
            openDetailsModal(id);
            const tabBtn = document.getElementById('tabDamageBtn');
            if (tabBtn) {
                new bootstrap.Tab(tabBtn).show();
            }
        }

        // ================= READ-ONLY DAMAGE LOG VIEW (opened from the fleet card tag) =================
        let currentDamageViewVehicleId = null;

        function openDamageLogView(id) {
            const v = fleet.find(function(x){ return x.id === id; });
            if (!v) return;

            currentDamageViewVehicleId = id;
            document.getElementById('dmgViewCarName').innerText = v.name + ' (' + v.plate + ')';
            renderDamageEntries(v, 'damageLogViewList', true);

            const modal = new bootstrap.Modal(document.getElementById('damageLogViewModal'));
            modal.show();
        }

        // "Log New Condition Check" button inside the view-only modal — closes
        // it and opens the full editable form on the Condition/Damage Log tab.
        function switchToFullDamageLog() {
            const id = currentDamageViewVehicleId;
            const viewModalEl = document.getElementById('damageLogViewModal');
            const viewModal = bootstrap.Modal.getInstance(viewModalEl) || new bootstrap.Modal(viewModalEl);
            viewModal.hide();
            viewModalEl.addEventListener('hidden.bs.modal', function handler() {
                viewModalEl.removeEventListener('hidden.bs.modal', handler);
                openDetailsModalToDamage(id);
            });
        }
        function renderDetailsFeedback(v) {
            const list = document.getElementById('detailsFeedbackList');
            if (v.feedbacks.length === 0) {
                list.innerHTML = '<p class="small text-muted text-center py-3 mb-0">No feedback yet for this vehicle.</p>';
                return;
            }
            list.innerHTML = v.feedbacks.map(function(f, idx) {

                // If admin/staff already replied, show the reply. Otherwise show a
                // "Reply as BossDrive" link that opens a small inline reply box.
                const replySection = f.adminReply
                    ? '' +
                      '<div class="admin-reply">' +
                          '<div class="d-flex justify-content-between align-items-start">' +
                              '<span class="fw-bold small text-danger"><i class="fas fa-reply me-1"></i>BossDrive Response &middot; ' + f.adminReply.by + '</span>' +
                              '<div class="d-flex gap-2">' +
                                  '<a class="view-feedback" style="font-size:0.68rem;" onclick="editFeedbackReply(' + idx + ')"><i class="fas fa-edit"></i></a>' +
                                  '<a class="view-feedback" style="font-size:0.68rem;" onclick="deleteFeedbackReply(' + idx + ')"><i class="fas fa-trash"></i></a>' +
                              '</div>' +
                          '</div>' +
                          '<p class="small mb-0 mt-1 text-secondary">' + f.adminReply.text + '</p>' +
                      '</div>'
                    : '' +
                      '<a class="view-feedback reply-toggle-link" onclick="toggleReplyForm(' + idx + ')"><i class="fas fa-reply me-1"></i>Reply as BossDrive</a>' +
                      '<div class="d-none mt-2" id="reply-form-' + idx + '">' +
                          '<textarea class="form-control form-control-sm mb-2" id="reply-input-' + idx + '" rows="2" placeholder="Write a response to this feedback..."></textarea>' +
                          '<button class="btn btn-danger btn-sm rounded-pill fw-bold px-3" onclick="postFeedbackReply(' + idx + ')">Post Reply</button>' +
                      '</div>';

                return '' +
                '<div class="feedback-item">' +
                    '<div class="d-flex justify-content-between mb-1">' +
                        '<span class="fw-bold">' + f.name + '</span>' +
                        '<small class="text-muted">' + f.date + '</small>' +
                    '</div>' +
                    '<div class="rating-stars mb-2">' + renderStars(f.stars) + '</div>' +
                    '<p class="small mb-0 text-secondary">"' + f.comment + '"</p>' +
                    replySection +
                '</div>';
            }).join('');
        }

        function toggleReplyForm(idx) {
            const box = document.getElementById('reply-form-' + idx);
            if (box) box.classList.toggle('d-none');
        }

        function postFeedbackReply(idx) {
            const v = fleet.find(function(x){ return x.id === currentDetailsVehicleId; });
            if (!v) return;

            const textarea = document.getElementById('reply-input-' + idx);
            const text = textarea.value.trim();
            if (!text) {
                textarea.focus();
                showToast('Please write a reply first.', 'error');
                return;
            }

            v.feedbacks[idx].adminReply = {
                text: text,
                by: 'Admin Patrick',
                date: 'Just now'
            };

            renderDetailsFeedback(v);
            showToast('Reply posted successfully!', 'success');

            // TODO: dito mo ipapadala yung reply sa Laravel backend
            // (POST /api/vehicles/{vehicleId}/feedback/{feedbackId}/reply)
        }

        function editFeedbackReply(idx) {
            const v = fleet.find(function(x){ return x.id === currentDetailsVehicleId; });
            if (!v || !v.feedbacks[idx].adminReply) return;

            const updated = prompt('Edit your reply:', v.feedbacks[idx].adminReply.text);
            if (updated === null) {
                showToast('Cancelled — reply was not changed.', 'cancel');
                return;
            }
            const trimmed = updated.trim();
            if (!trimmed) {
                showToast('Reply cannot be empty.', 'error');
                return;
            }

            v.feedbacks[idx].adminReply.text = trimmed;
            renderDetailsFeedback(v);
            showToast('Reply updated successfully!', 'success');

            // TODO: dito mo ipapadala yung update sa Laravel backend (PUT /api/feedback/{id}/reply)
        }

        async function deleteFeedbackReply(idx) {
            const v = fleet.find(function(x){ return x.id === currentDetailsVehicleId; });
            if (!v || !v.feedbacks[idx].adminReply) return;
            if (!(await showConfirm('Remove this reply?'))) {
                showToast('Cancelled — reply was not removed.', 'cancel');
                return;
            }

            try {
                const response = await fetch(@json($vehicleBaseUrl) + '/' + currentDetailsVehicleId + '/feedback/' + idx + '/reply', {
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Could not remove the reply.');
                }

                v.feedbacks = data.feedbacks;
            } catch (error) {
                showToast(error.message || 'Could not remove the reply. Please try again.', 'error');
                return;
            }

            renderDetailsFeedback(v);
            showToast('Reply removed successfully!', 'success');
        }

        function renderDamageEntries(v, containerId, readOnly) {
            const list = document.getElementById(containerId);
            if (v.damageLog.length === 0) {
                list.innerHTML = '<p class="small text-muted text-center py-3 mb-0"><i class="fas fa-check-circle text-success me-1"></i>No damage or condition issues on record.</p>';
                return;
            }
            list.innerHTML = v.damageLog.map(function(d) {
                // Older entries (or ones logged with no damage) may not have a real
                // part/description — treat those as a plain "condition check" entry
                // instead of showing blank/"N/A" text.
                const isNoDamage = d.hasDamage === false;
                const isRepaired = d.status === 'Repaired';
                const badgeClass = isNoDamage || isRepaired ? 'bg-success' : (d.status === 'Under Repair' ? 'bg-danger' : 'bg-warning text-dark');
                const titleText = isNoDamage ? 'General Condition Check' : (d.part || 'Condition Check');
                const statusLabel = isNoDamage ? 'No Damage Found' : d.status;
                const descText = isNoDamage
                    ? 'Vehicle was inspected — no damage found.'
                    : d.description;

                // Checklist mini-badges — only rendered for entries that actually
                // recorded a checklist (new entries going forward).
                let checklistHtml = '';
                if (d.checklist) {
                    checklistHtml = '<div class="mt-2">' + conditionChecklistFields.map(function(c) {
                        const isChecked = !!d.checklist[c.key];
                        return '<span class="condition-mini-badge ' + (isChecked ? 'checked' : 'unchecked') + '">' +
                                   '<i class="fas ' + (isChecked ? 'fa-check' : 'fa-times') + '"></i>' + c.label +
                               '</span>';
                    }).join('') + '</div>';
                }

                // Edit/Delete are hidden in the read-only "view" version (opened
                // from the fleet card tag) — that one is pure history, no editing.
                const actionsHtml = readOnly ? '' :
                    '<div class="d-flex gap-1">' +
                        '<button class="btn btn-sm btn-outline-primary" style="border-radius:50px; font-size:0.7rem; padding:2px 10px;" onclick="editDamageEntry(' + d.id + ')"><i class="fas fa-edit"></i></button>' +
                        '<button class="btn btn-sm btn-outline-danger" style="border-radius:50px; font-size:0.7rem; padding:2px 10px;" onclick="removeDamageEntry(' + d.id + ')"><i class="fas fa-trash"></i></button>' +
                    '</div>';

                return '' +
                '<div class="damage-item ' + ((isRepaired || isNoDamage) ? 'repaired' : '') + '" id="damage-' + d.id + '">' +
                    '<div class="d-flex justify-content-between align-items-start">' +
                        '<div>' +
                            '<span class="fw-bold small">' + titleText + '</span> ' +
                            '<span class="badge ' + badgeClass + ' ms-2" style="font-size:0.65rem;">' + statusLabel + '</span>' +
                            '<p class="small mb-1 mt-1 text-secondary">' + descText + '</p>' +
                            checklistHtml +
                            '<small class="text-muted d-block mt-2" style="font-size:0.7rem;">Reported by ' + d.reportedBy + ' &middot; ' + d.date + '</small>' +
                        '</div>' +
                        actionsHtml +
                    '</div>' +
                '</div>';
            }).join('');
        }

        function renderDetailsDamage(v) {
            renderDamageEntries(v, 'detailsDamageList', false);
        }



        // The 4 inspection items on the checklist, in display order.
        const conditionChecklistFields = [
            { key: 'fuel',      label: 'Fuel Level',       icon: 'fa-gas-pump'  },
            { key: 'exterior',  label: 'Exterior/Body',    icon: 'fa-car-crash' },
            { key: 'interior',  label: 'Interior',         icon: 'fa-broom'     },
            { key: 'tools',     label: 'Tools/Accessories',icon: 'fa-toolbox'   }
        ];
        let editingDamageId = null;

        async function addDamageEntry() {
            const v = fleet.find(function(x){ return x.id === currentDetailsVehicleId; });
            if (!v) return;

            const part = document.getElementById('dmg-part').value.trim();
            const desc = document.getElementById('dmg-desc').value.trim();
            const status = document.getElementById('dmg-status').value;

            // Part and Description are only required together. Leaving both blank
            // is valid — it just means the inspection didn't find any damage, and
            // the checklist below is what gets logged instead of a forced "N/A".
            if ((part && !desc) || (!part && desc)) {
                alert('Please fill out both the Part and Description fields, or leave both blank if no damage was found.');
                showToast('Please complete both Part and Description, or leave both blank.', 'error');
                return;
            }

            if (!(await showConfirm(editingDamageId ? 'Save changes to this condition/damage check?' : 'Confirm and log this condition/damage check?'))) {
                showToast('Cancelled — condition check was not logged.', 'cancel');
                return;
            }

            const checklist = {
                fuel: document.getElementById('chk-fuel').checked,
                exterior: document.getElementById('chk-exterior').checked,
                interior: document.getElementById('chk-interior').checked,
                tools: document.getElementById('chk-tools').checked
            };
            const wasEditing = editingDamageId !== null;

            const hasDamage = !!(part && desc);
            const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

            const updatedEntry = {
                date: editingDamageId ? v.damageLog.find(function (entry) { return entry.id === editingDamageId; }).date : today,
                part: hasDamage ? part : '',
                description: hasDamage ? desc : '',
                reportedBy: editingDamageId ? v.damageLog.find(function (entry) { return entry.id === editingDamageId; }).reportedBy : 'Admin Patrick',
                status: hasDamage ? status : 'No Damage',
                hasDamage: hasDamage,
                checklist: checklist
            };
            if (editingDamageId) {
                const entry = v.damageLog.find(function (item) { return item.id === editingDamageId; });
                Object.assign(entry, updatedEntry);
            } else {
                v.damageLog.unshift(Object.assign({ id: nextDamageId++ }, updatedEntry));
            }
            editingDamageId = null;

            document.getElementById('dmg-part').value = '';
            document.getElementById('dmg-desc').value = '';
            document.getElementById('dmg-status').value = 'Under Repair';
            document.getElementById('chk-fuel').checked = false;
            document.getElementById('chk-exterior').checked = false;
            document.getElementById('chk-interior').checked = false;
            document.getElementById('chk-tools').checked = false;
            document.getElementById('damageSubmitButton').innerHTML = '<i class="fas fa-check-circle me-2"></i>CONFIRM & LOG CONDITION';

            renderDetailsDamage(v);
            renderFleet(); // update the "X Damage Log" tag on the card
            showToast(wasEditing ? 'Condition/damage log updated successfully!' : 'Condition/damage log saved successfully!', 'success');
            await saveDamageLog(v);
        }

        function editDamageEntry(damageId) {
            const v = fleet.find(function(x){ return x.id === currentDetailsVehicleId; });
            if (!v) return;
            const entry = v.damageLog.find(function(d){ return d.id === damageId; });
            if (!entry) return;

            editingDamageId = damageId;
            document.getElementById('dmg-part').value = entry.part || '';
            document.getElementById('dmg-desc').value = entry.description || '';
            document.getElementById('dmg-status').value = entry.status === 'No Damage' ? 'Under Repair' : entry.status;
            conditionChecklistFields.forEach(function (field) {
                document.getElementById('chk-' + field.key).checked = !!(entry.checklist && entry.checklist[field.key]);
            });
            document.getElementById('damageSubmitButton').innerHTML = '<i class="fas fa-save me-2"></i>SAVE CONDITION CHANGES';
            showToast('Edit the condition details and checklist, then save.', 'success');
        }

        async function saveDamageLog(vehicle) {
            try {
                const response = await fetch(@json($vehicleBaseUrl) + '/' + vehicle.id, {
                    method: 'PATCH',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        name: vehicle.name,
                        plate: vehicle.plate,
                        price: vehicle.price,
                        category: vehicle.category,
                        transmission: vehicle.transmission,
                        fuel: vehicle.fuel,
                        capacity: vehicle.capacity,
                        status: vehicle.status.toLowerCase(),
                        damage_log: vehicle.damageLog,
                        schedule: vehicle.schedule || []
                    })
                });
                if (!response.ok) {
                    showToast('Damage log could not be saved.', 'error');
                    return false;
                }

                return true;
            } catch (error) {
                showToast('Damage log could not be saved. Please try again.', 'error');
                return false;
            }
        }

        async function removeDamageEntry(damageId) {
            const v = fleet.find(function(x){ return x.id === currentDetailsVehicleId; });
            if (!v) return;
            if (!(await showConfirm('Remove this damage/condition entry?'))) {
                showToast('Cancelled — entry was not removed.', 'cancel');
                return;
            }

            const originalDamageLog = v.damageLog;
            v.damageLog = v.damageLog.filter(function(d){ return d.id !== damageId; });
            if (!(await saveDamageLog(v))) {
                v.damageLog = originalDamageLog;
                renderDetailsDamage(v);
                renderFleet();
                return;
            }
            if (editingDamageId === damageId) {
                editingDamageId = null;
                document.getElementById('damageSubmitButton').innerHTML = '<i class="fas fa-check-circle me-2"></i>CONFIRM & LOG CONDITION';
            }
            renderDetailsDamage(v);
            renderFleet();
            showToast('Damage log entry removed successfully!', 'success');
        }

        // ================= STAT CARDS: click to filter =================
        function clearStatFilter() {
            currentStatFilter = 'all';
            renderFleet();
        }

        document.querySelectorAll('.stat-card').forEach(function(card) {
            card.addEventListener('click', function() {
                const filter = card.getAttribute('data-filter');
                currentStatFilter = (currentStatFilter === filter) ? 'all' : filter;
                renderFleet();
            });
        });

        // ================= FLEET SCHEDULE CALENDAR (editable by admin/staff) =================
        // Each entry blocks out a date range for a vehicle as Rented, Reserved,
        // or under Maintenance. In production this should be its own table in
        // the Laravel backend (e.g. vehicle_schedule) shared with the Reservations
        // Management and User Reservation calendar so all 3 stay in sync.
        let scheduleEvents = [];
        fleet.forEach(function (vehicle) {
            (vehicle.schedule || []).forEach(function (entry) {
                scheduleEvents.push(Object.assign({}, entry, { vehicleId: vehicle.id }));
            });
        });
        scheduleEvents = Array.from(scheduleEvents.filter(function (entry) {
            return entry.type === 'Maintenance' || ['Reserved', 'Rented', 'Special'].includes(entry.type);
        }).reduce(function (entries, entry) {
            const key = entry.reservationId
                ? 'reservation:' + entry.reservationId
                : 'manual:' + entry.vehicleId + ':' + entry.id;
            entries.set(key, entry);
            return entries;
        }, new Map()).values());
        let nextScheduleId = scheduleEvents.reduce(function (max, entry) {
            return Math.max(max, Number(entry.id) || 0);
        }, 0) + 1;
        const now = new Date();
        let adminCalViewDate = new Date(now.getFullYear(), now.getMonth(), 1);

        const scheduleTypeClass = {
            'Maintenance': 'event-maintenance',
            'Rented': 'event-rented',
            'Reserved': 'event-reserved',
            'Special': 'event-delivery'
        };

        function todayISO() {
            const d = new Date();
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function toISODate(y, m, d) {
            return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        }

        function calendarEventsForDate(iso) {
            return scheduleEvents.filter(function(ev) {
                return iso >= ev.start && iso <= ev.end;
            }).sort(function(a, b) {
                return (a.type === 'Special' ? 0 : 1) - (b.type === 'Special' ? 0 : 1);
            });
        }

        function vehicleNameById(id) {
            const v = fleet.find(function(x){ return x.id === id; });
            if (!v) return 'Unknown Vehicle';
            const unitNumber = fleet.filter(function (vehicle) { return vehicle.name === v.name; })
                .findIndex(function (vehicle) { return vehicle.id === v.id; }) + 1;
            return v.name + ' Unit #' + unitNumber;
        }

        function populateScheduleVehicleSelect(selectedId) {
            const select = document.getElementById('sched-vehicle');
            select.innerHTML = fleet.map(function(v) {
                return '<option value="' + v.id + '">' + v.name + ' (' + v.plate + ')</option>';
            }).join('');
            if (selectedId) select.value = selectedId;
        }

        function renderAdminCalendar() {
            const year = adminCalViewDate.getFullYear();
            const monthIdx = adminCalViewDate.getMonth();
            document.getElementById('adminCalendarMonthYear').innerText = adminCalViewDate.toLocaleString('default', { month: 'long', year: 'numeric' });

            const firstDay = new Date(year, monthIdx, 1).getDay();
            const daysInMonth = new Date(year, monthIdx + 1, 0).getDate();
            const body = document.getElementById('adminCalendarBody');
            body.innerHTML = '';

            let date = 1;
            for (let i = 0; i < 6; i++) {
                let row = document.createElement('tr');
                for (let j = 0; j < 7; j++) {
                    let cell = document.createElement('td');
                    if (i === 0 && j < firstDay) {
                        cell.innerHTML = '';
                    } else if (date > daysInMonth) {
                        cell.innerHTML = '';
                    } else {
                        const iso = toISODate(year, monthIdx, date);
                        const dayEvents = calendarEventsForDate(iso);

                        const todayClass = iso === todayISO() ? ' today-cell' : '';
                        cell.className = todayClass.trim();
                        let cellHTML = '<span class="cal-date">' + date + (iso === todayISO() ? ' <small class="text-danger">(Today)</small>' : '') + '</span>';
                        dayEvents.slice(0, 3).forEach(function(ev) {
                            const cls = scheduleTypeClass[ev.type] || 'event-reserved';
                            const clickAction = ev.type === 'Special'
                                ? "openAdminCalendarDetails('" + iso + "')"
                                : 'openScheduleForm(' + ev.id + ')';
                            const eventLabel = ev.type === 'Special'
                                ? 'Delivery · ' + vehicleNameById(ev.vehicleId)
                                : vehicleNameById(ev.vehicleId);
                            cellHTML += '<div class="cal-event ' + cls + '" onclick="event.stopPropagation(); ' + clickAction + '" title="' + eventLabel + '">' + eventLabel + '</div>';
                        });
                        if (dayEvents.length > 3) {
                            cellHTML += '<button type="button" class="cal-more" onclick="event.stopPropagation(); openAdminCalendarDetails(\'' + iso + '\')">+' + (dayEvents.length - 3) + ' more</button>';
                        }
                        cellHTML += '<div class="cal-add-hint"><i class="fas fa-plus"></i> add</div>';

                        cell.innerHTML = cellHTML;
                        date++;
                    }
                    row.appendChild(cell);
                }
                body.appendChild(row);
                if (date > daysInMonth) break;
            }
        }

        function openAdminCalendarDetails(iso) {
            const events = calendarEventsForDate(iso);
            const body = document.getElementById('adminCalendarDetailsBody');
            const title = document.getElementById('adminCalendarDetailsTitle');
            title.textContent = 'Schedules for ' + new Date(iso + 'T00:00:00').toLocaleDateString(undefined, { dateStyle: 'long' });
            body.innerHTML = '';
            events.forEach(function(ev) {
                const row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between gap-2 border rounded-3 p-2 mb-2';
                const label = document.createElement('span');
                label.className = 'small fw-bold';
                label.textContent = ev.type === 'Special'
                    ? 'Delivery · ' + vehicleNameById(ev.vehicleId)
                    : vehicleNameById(ev.vehicleId) + ' - ' + (ev.type === 'Rented' ? 'Ongoing' : ev.type);
                if (ev.type === 'Special') {
                    const deliveryInfo = document.createElement('span');
                    deliveryInfo.className = 'small text-muted text-end';
                    deliveryInfo.textContent = 'Control Number: ' + (ev.controlNumber || 'Unavailable')
                        + ' · Delivery Date: ' + (ev.deliveryDate || ev.start);
                    row.appendChild(deliveryInfo);
                } else if (ev.type === 'Maintenance') {
                    const edit = document.createElement('button');
                    edit.type = 'button';
                    edit.className = 'btn btn-sm btn-outline-dark';
                    edit.textContent = 'View / Edit';
                    edit.addEventListener('click', function() {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('adminCalendarDetailsModal')).hide();
                        openScheduleForm(ev.id);
                    });
                    row.appendChild(edit);
                } else {
                    const locked = document.createElement('span');
                    locked.className = 'small text-muted fw-bold';
                    locked.textContent = 'Rental locked';
                    row.appendChild(locked);
                }
                row.insertBefore(label, row.firstChild);
                body.appendChild(row);
            });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('adminCalendarDetailsModal')).show();
        }

        function changeAdminCalMonth(step) {
            adminCalViewDate.setMonth(adminCalViewDate.getMonth() + step);
            renderAdminCalendar();
        }

        // --- ADD / EDIT SCHEDULE ENTRY FORM ---
        function openScheduleForm(scheduleId, prefillDate) {
            const errorArea = document.getElementById('scheduleFormError');
            errorArea.innerHTML = '';
            populateScheduleVehicleSelect();

            const deleteBtn = document.getElementById('sched-delete-btn');

            if (scheduleId) {
                const ev = scheduleEvents.find(function(x){ return x.id === scheduleId; });
                if (!ev) return;
                if (ev.type === 'Reserved' || ev.type === 'Rented' || ev.type === 'Special') {
                    showToast('Rental schedules cannot be edited.', 'error');
                    return;
                }
                document.getElementById('scheduleFormTitle').innerText = 'Edit Schedule Entry';
                document.getElementById('sched-id').value = ev.id;
                document.getElementById('sched-vehicle').value = ev.vehicleId;
                document.getElementById('sched-type').value = ev.type;
                document.getElementById('sched-start').value = ev.start;
                document.getElementById('sched-end').value = ev.end;
                document.getElementById('sched-notes').value = ev.notes || '';
                document.getElementById('sched-sync-status').checked = false;
                deleteBtn.classList.remove('d-none');
            } else {
                document.getElementById('scheduleFormTitle').innerText = 'Add Schedule Entry';
                document.getElementById('sched-id').value = '';
                document.getElementById('sched-type').value = 'Reserved';
                const d = prefillDate || todayISO();
                document.getElementById('sched-start').value = d;
                document.getElementById('sched-end').value = d;
                document.getElementById('sched-notes').value = '';
                document.getElementById('sched-sync-status').checked = false;
                deleteBtn.classList.add('d-none');
            }

            const modal = new bootstrap.Modal(document.getElementById('scheduleFormModal'));
            modal.show();
        }

        async function saveScheduleEntry() {
            const errorArea = document.getElementById('scheduleFormError');
            errorArea.innerHTML = '';

            const id = document.getElementById('sched-id').value;
            const vehicleId = parseInt(document.getElementById('sched-vehicle').value);
            const type = document.getElementById('sched-type').value;
            const start = document.getElementById('sched-start').value;
            const end = document.getElementById('sched-end').value;
            const notes = document.getElementById('sched-notes').value.trim();
            const syncStatus = document.getElementById('sched-sync-status').checked;

            if (type === 'Rented') {
                showToast('Rental schedules cannot be edited here.', 'error');
                return;
            }

            if (!vehicleId || !start || !end) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mt-3 mb-0 small fw-bold">Please select a vehicle and both dates.</div>';
                return;
            }
            if (end < start) {
                errorArea.innerHTML = '<div class="alert alert-danger py-2 px-3 mt-3 mb-0 small fw-bold">End Date can\'t be earlier than Start Date.</div>';
                return;
            }

            if (!(await showConfirm(id ? 'Save changes to this schedule entry?' : 'Add this entry to the fleet schedule?'))) {
                showToast('Cancelled — schedule entry was not saved.', 'cancel');
                return;
            }

            if (id) {
                const ev = scheduleEvents.find(function(x){ return x.id === parseInt(id); });
                if (ev) {
                    ev.vehicleId = vehicleId; ev.type = type; ev.start = start; ev.end = end; ev.notes = notes;
                }
            } else {
                scheduleEvents.push({ id: nextScheduleId++, vehicleId: vehicleId, type: type, start: start, end: end, notes: notes });
            }

            if (syncStatus) {
                const v = fleet.find(function(x){ return x.id === vehicleId; });
                if (v) v.status = type;
            }

            // Always refresh the fleet cards — the "Reserved for / Under
            // Maintenance / Return Date" banner reads straight from
            // scheduleEvents, so any add/edit here must reflect immediately
            // on the card, whether or not the status checkbox was ticked.
            renderFleet();
            renderAdminCalendar();
            bootstrap.Modal.getInstance(document.getElementById('scheduleFormModal')).hide();
            showToast(id ? 'Schedule entry updated successfully!' : 'Schedule entry added successfully!', 'success');
            persistVehicleSchedule(vehicleId);

            // TODO: dito mo ipapadala yung schedule entry sa Laravel backend
            // (POST/PUT /api/vehicle-schedule), para makita rin ito ng user sa
            // kanilang sariling calendar sa User-Reservation.html.
        }

        async function deleteScheduleEntry() {
            const id = parseInt(document.getElementById('sched-id').value);
            if (!id) return;
            if (!(await showConfirm('Remove this schedule entry?'))) {
                showToast('Cancelled — entry was not removed.', 'cancel');
                return;
            }

            const affectedVehicle = fleet.find(function (vehicle) {
                return (vehicle.schedule || []).some(function (entry) { return entry.id === id; });
            });
            if (!affectedVehicle) {
                showToast('Schedule entry could not be found.', 'error');
                return;
            }

            const originalScheduleEvents = scheduleEvents;
            const originalVehicleSchedule = affectedVehicle.schedule;
            scheduleEvents = scheduleEvents.filter(function(x){ return x.id !== id; });
            affectedVehicle.schedule = (affectedVehicle.schedule || []).filter(function (entry) { return entry.id !== id; });
            if (!(await persistVehicleSchedule(affectedVehicle.id))) {
                scheduleEvents = originalScheduleEvents;
                affectedVehicle.schedule = originalVehicleSchedule;
                renderFleet();
                renderAdminCalendar();
                return;
            }

            renderFleet();
            renderAdminCalendar();
            bootstrap.Modal.getInstance(document.getElementById('scheduleFormModal')).hide();
            showToast('Schedule entry removed successfully!', 'success');
        }

        async function persistVehicleSchedule(vehicleId) {
            const vehicle = fleet.find(function (item) { return item.id === vehicleId; });
            if (!vehicle) return false;
            vehicle.schedule = scheduleEvents.filter(function (entry) { return entry.vehicleId === vehicleId; });
            try {
                const response = await fetch(@json($vehicleBaseUrl) + '/' + vehicleId, {
                    method: 'PATCH',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        name: vehicle.name,
                        plate: vehicle.plate,
                        status: vehicle.status.toLowerCase(),
                        damage_log: vehicle.damageLog,
                        image_path: vehicle.photo,
                        schedule: vehicle.schedule
                    })
                });
                if (!response.ok) {
                    showToast('Vehicle schedule could not be saved.', 'error');
                    return false;
                }

                return true;
            } catch (error) {
                showToast('Vehicle schedule could not be saved. Please try again.', 'error');
                return false;
            }
        }

        document.getElementById('adminCalendarModal').addEventListener('show.bs.modal', renderAdminCalendar);

        // Init
        syncVehicleCategoryPrice('add');
        renderFleet();

        let liveAvailabilitySignature = '';
        async function syncFleetAvailability() {
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
                    vehicle.status = live.status === 'Rented' && live.currentReservation?.status === 'released'
                        ? 'Ongoing'
                        : (live.status === 'Rented' ? 'Reserved' : live.status);
                    vehicle.currentReservation = live.currentReservation;
                    vehicle.schedule = live.schedule || [];
                });
                scheduleEvents = fleet.reduce(function (events, vehicle) {
                    return events.concat((vehicle.schedule || []).map(function (entry) {
                        return Object.assign({}, entry, {vehicleId: Number(entry.vehicleId || vehicle.id)});
                    }));
                }, []).filter(function (entry) {
                    return entry.type === 'Maintenance' || ['Reserved', 'Rented', 'Special'].includes(entry.type);
                });
                renderFleet();
            } catch (error) {
                console.error('Unable to refresh live fleet availability.', error);
            }
        }
        syncFleetAvailability();
        setInterval(syncFleetAvailability, 5000);
    </script>
</body>
</html>