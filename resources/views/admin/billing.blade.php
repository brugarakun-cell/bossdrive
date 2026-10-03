<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $isStaffMode = $staffMode ?? false;
        $billingBaseUrl = $isStaffMode ? url('/staff/billing') : url('/admin/billing');
        $billingLiveUrl = $isStaffMode ? route('staff.billing.live') : route('admin.billing.live');
        $billingConfirmUrl = $isStaffMode ? url('/staff/billing') : url('/admin/billing');
        $profileUrl = $isStaffMode ? route('staff.profile') : route('admin.profile');
        $paymentSettingsUrl = $isStaffMode ? null : route('admin.payment-settings.update');
    @endphp
    <title>BossDrive - {{ $isStaffMode ? 'Staff' : 'Admin' }} Payments & Billing</title>
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

        .main-content { margin-left: 250px; min-height: 100vh; }
        
        /* Consistent Header */
        .top-nav { 
            background: white; 
            padding: 15px 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-container { padding: 30px; }
        
        /* Stats Styling */
        .stat-card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); cursor: pointer; transition: all 0.25s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 8px 18px rgba(0,0,0,0.1); }
        .stat-card.active-filter { box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.35), 0 8px 18px rgba(0,0,0,0.1); }
        .filter-active-banner { font-size: 0.8rem; font-weight: 700; }
        .billing-source-tabs { display:flex; gap:8px; flex-wrap:wrap; }
        .billing-source-tab { border:1px solid #dee2e6; background:#fff; color:#495057; border-radius:999px; padding:7px 16px; font-size:.78rem; font-weight:700; }
        .billing-source-tab.active, .billing-source-tab:hover { background:#212529; border-color:#212529; color:#fff; }
        .billing-source-badge { display:inline-block; margin-top:4px; font-size:.62rem; font-weight:800; padding:3px 8px; }

        /* Billing Specific Styles */
        .billing-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: none; }
        .status-badge { font-size: 0.65rem; padding: 5px 10px; border-radius: 50px; font-weight: 800; text-transform: uppercase; border: 1px solid; white-space: nowrap; display: inline-block; }
        .status-paid { background: #eefdf5; color: #198754; border-color: #198754; }
        .status-pending { background: #fff5f5; color: #dc3545; border-color: #dc3545; }
        .status-badge.clickable { cursor: pointer; }
        .status-badge.clickable:hover { filter: brightness(0.95); transform: translateY(-1px); }
        .history-role { font-size: 0.65rem; }
        #paymentHistoryModal .modal-dialog { max-width: 560px; }
        #paymentHistoryModal .modal-body { max-height: 70vh; overflow-y: auto; }
        #paymentHistoryList { max-height: 48vh; overflow-y: auto; padding-right: 6px; }
        #paymentHistoryList::-webkit-scrollbar { width: 7px; }
        #paymentHistoryList::-webkit-scrollbar-thumb { background: #adb5bd; border-radius: 10px; }

        .info-label { font-size: 0.65rem; color: #888; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .info-value { font-weight: 700; color: #222; display: block; margin-bottom: 15px; font-size: 1.1rem; }
        
        .table thead th { background-color: #f8f9fa; border: none; font-size: 0.75rem; letter-spacing: 0.5px; }

        /* GCash QR Settings Styles */
        .qr-preview-box {
            width: 160px;
            height: 160px;
            border-radius: 15px;
            border: 2px dashed #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fafafa;
            flex-shrink: 0;
        }
        .qr-preview-box img { width: 100%; height: 100%; object-fit: contain; padding: 8px; }
        .qr-upload-drop {
            border: 2px dashed #ccc;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
            background: #fafafa;
        }
        .qr-upload-drop:hover { border-color: var(--boss-red); background: #fff5f5; }
        .qr-upload-drop.dragover { border-color: var(--boss-red); background: #fff5f5; }

        /* ===== Success / Cancel / Error toast notifications ===== */
        .bd-toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; display: flex; flex-direction: column; align-items: center; gap: 10px; pointer-events: none; }
        .bd-toast { min-width: 260px; max-width: 340px; padding: 14px 18px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.18); font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; color: #fff; opacity: 0; transform: scale(0.9); transition: 0.25s ease; }
        .bd-toast.show { opacity: 1; transform: scale(1); }
        .bd-toast.success { background: #198754; }
        .bd-toast.cancel { background: #6c757d; }
        .bd-toast.error { background: #dc3545; }

        /* The confirm modal must always sit above any other modal that might
           already be open (e.g. confirming while Invoice/QR modal is open) —
           without this it can render behind, since it's earlier in the DOM. */
        #bdConfirmModal { z-index: 1090 !important; }
    </style>
</head>
<body>

    <!-- Success/Cancel/Error toast notifications appear here -->
    <div class="bd-toast-container" id="bdToastContainer"></div>

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
        @include('staff.partials.navigation', ['staffPageTitle' => 'Payments &', 'staffPageAccent' => 'Billing'])
    @else
        @include('admin.partials.navigation', ['adminPageTitle' => 'Payments &', 'adminPageAccent' => 'Billing'])
    @endif

    <div class="main-content">
        <div class="content-container">
            
            <div class="row g-4 mb-4 text-center">
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-success border-5" data-filter="paid">
                        <small class="text-muted fw-bold text-uppercase">Paid Bookings</small>
                        <h2 class="fw-bold text-success mb-0 mt-2" id="statTotalRevenue">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-dark border-5" data-filter="all">
                        <small class="text-muted fw-bold text-uppercase">Processed Invoices</small>
                        <h2 class="fw-bold text-dark mb-0 mt-2" id="statProcessed">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-warning border-5" data-filter="extension-pending">
                        <small class="text-muted fw-bold text-uppercase">Extension Payments</small>
                        <h2 class="fw-bold text-warning mb-0 mt-2" id="statExtensionPending">0</h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-bottom border-danger border-5" data-filter="unpaid">
                        <small class="text-muted fw-bold text-uppercase">Unpaid Bookings</small>
                        <h2 class="fw-bold text-danger mb-0 mt-2" id="statPending">0</h2>
                    </div>
                </div>
            </div>

            @if(!$isStaffMode)
            <!-- GCash QR Payment Settings -->
            <div class="billing-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-uppercase small text-muted"><i class="fas fa-qrcode me-2 text-danger"></i>GCash Payment Settings</h6>
                    <span class="badge bg-light text-muted fw-normal" style="font-size:0.65rem;">Shown to customers during checkout</span>
                </div>
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div class="qr-preview-box" id="qrPreviewBox">
                        <img id="qrPreviewImg" src="{{ $paymentSettings->gcash_qr_path ? asset('storage/'.$paymentSettings->gcash_qr_path) : 'https://placehold.co/300x300?text=No+QR+Code' }}" alt="GCash QR Code">
                    </div>
                    <div class="flex-grow-1">
                        <label class="info-label">Account Name</label>
                        <span class="info-value" id="gcashName">{{ $paymentSettings->account_name ?: 'Big Boss Car Rental Services' }}</span>
                        <label class="info-label">GCash Number</label>
                        <span class="info-value mb-0" id="gcashNumber">{{ $paymentSettings->account_number ?: '0917 123 4567' }}</span>
                    </div>
                    <div>
                        <button class="btn btn-danger fw-bold px-4 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#qrUploadModal">
                            <i class="fas fa-upload me-2"></i>Replace QR Code
                        </button>
                    </div>
                </div>
            </div>
            @endif

            <div class="billing-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-uppercase small text-muted">Transaction Audit Trail</h6>
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control border-0 bg-light" id="invoiceSearchInput" placeholder="Search Invoice or Ref..." onkeyup="renderInvoices()">
                        </div>
                    </div>
                </div>
                <div class="billing-source-tabs mb-3" role="tablist" aria-label="Billing source filter">
                    <button type="button" class="billing-source-tab active" data-billing-source="all">All Billing Records</button>
                    <button type="button" class="billing-source-tab" data-billing-source="online">Online Billing</button>
                    <button type="button" class="billing-source-tab" data-billing-source="walkin">Walk-in Billing</button>
                </div>

                <div id="filterBanner" class="alert alert-danger py-2 px-3 filter-active-banner d-none d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-filter me-2"></i>Showing filtered view: <span id="filterLabel"></span></span>
                    <button class="btn btn-sm btn-outline-danger fw-bold" onclick="clearBillingFilter()">Clear Filter</button>
                </div>

                <div id="noInvoiceMsg" class="alert alert-warning small fw-bold text-center d-none">
                    <i class="fas fa-exclamation-circle me-1"></i> No invoice found matching your search.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr class="text-muted text-uppercase">
                                <th class="py-3">Invoice #</th>
                                <th>Customer Name</th>
                                <th>Vehicle</th>
                                <th>Grand Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-center">Details</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(!$isStaffMode)
    <!-- GCash QR Upload Modal -->
    <div class="modal fade" id="qrUploadModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 overflow-hidden shadow">
                <div class="modal-header bg-dark text-white p-4">
                    <div>
                        <h5 class="fw-bold mb-0">UPDATE GCASH QR CODE</h5>
                        <small class="text-danger fw-bold">Used across User & Staff checkout screens</small>
                    </div>
                    @endif
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="qr-upload-drop mb-4" id="qrDropZone" onclick="document.getElementById('qrFileInput').click()">
                        <img id="qrModalPreview" src="" alt="" class="img-fluid mb-3 d-none" style="max-height: 180px; border-radius: 10px;">
                        <div id="qrDropPlaceholder">
                            <i class="fas fa-cloud-upload-alt fa-2x text-danger mb-2"></i>
                            <p class="fw-bold mb-1">Click or drag a new QR image here</p>
                            <p class="small text-muted mb-0">PNG or JPG, recommended 500x500px</p>
                        </div>
                    </div>
                    <input type="file" id="qrFileInput" accept="image/png, image/jpeg" class="d-none">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="info-label">Account Name</label>
                            <input type="text" class="form-control" id="qrAccountNameInput" value="{{ $paymentSettings->account_name ?: 'Big Boss Car Rental Services' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="info-label">GCash Number</label>
                            <input type="text" class="form-control" id="qrAccountNumberInput" value="{{ $paymentSettings->account_number ?: '0917 123 4567' }}">
                        </div>
                    </div>

                    <div id="qrErrorMsg" class="text-danger small fw-bold mt-3 d-none"></div>
                </div>
                <div class="modal-footer bg-light border-0 p-4">
                    <button class="btn btn-outline-secondary px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm" onclick="saveQrCode()">
                        <i class="fas fa-check me-2"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 overflow-hidden shadow">
                <div class="modal-header bg-dark text-white p-4">
                    <div>
                        <h5 class="fw-bold mb-0">PAYMENT HISTORY</h5>
                        <small class="text-danger fw-bold" id="historyInvoiceNum"></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
                        <span class="text-muted">Current status</span>
                        <strong id="historyStatus"></strong>
                    </div>
                    <div id="paymentHistoryList" class="small"></div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button class="btn btn-outline-secondary px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 overflow-hidden shadow">
                <div class="modal-header bg-dark text-white p-4">
                    <div>
                        <h5 class="fw-bold mb-0">BILLING SUMMARY</h5>
                        <small class="text-danger fw-bold" id="mInvoiceNum"></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-5">
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold text-dark text-uppercase small mb-4">Customer Info</h6>
                            <label class="info-label">Full Name</label>
                            <span class="info-value text-danger" id="mCustName"></span>
                            
                            <label class="info-label">Payment Method</label>
                            <span class="info-value" id="mPayMethod"></span>

                            <label class="info-label">Proof of Payment</label>
                            <div class="p-4 bg-light rounded-3 text-center border-dashed">
                                <span class="fw-bold d-block mb-2" id="mRef"></span>
                                <button type="button" id="mProofLink" class="btn btn-link btn-sm d-none" onclick="openBillingProof(this)">View Booking Proof</button>
                                <i id="mProofIcon" class="fas fa-file-invoice-dollar fa-3x text-muted opacity-25"></i>
                                <p class="small text-muted mt-2 mb-0" id="mProofLabel">No payment proof uploaded</p>
                                <button type="button" id="confirmBookingPaymentButton" class="btn btn-sm btn-success rounded-pill mt-2 d-none">Confirm Booking Payment</button>
                                <div id="mExtensionProofs" class="mt-3 text-start d-none"></div>
                            </div>
                        </div>
                        <div class="col-md-6 ps-md-5">
                            <h6 class="fw-bold text-dark text-uppercase small mb-4">Financial Breakdown</h6>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted" id="mTotalBillLabel">Total Rental Fee:</span>
                                <span class="fw-bold fs-5" id="mTotalBill"></span>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <span class="text-muted">Amount Paid:</span>
                                <span class="fw-bold text-success fs-5" id="mPaidAmt"></span>
                            </div>
                            <hr class="my-4">
                            <div class="d-flex justify-content-between mb-4">
                                <h4 class="fw-bold">Balance:</h4>
                                <h4 class="fw-bold text-danger" id="mBalance"></h4>
                            </div>
                            <div id="settleArea" class="bg-light p-3 rounded-3">
                                <label class="info-label d-block mb-2">Update Payment</label>
                                <small class="text-muted d-block mb-2">Ilagay ang aktwal na halagang binayad ng customer.</small>
                                <div class="input-group">
                                    <span class="input-group-text border-0">₱</span>
                                    <input type="number" class="form-control border-0" id="settleAmountInput" placeholder="0.00">
                                    <button class="btn btn-danger fw-bold px-4" onclick="processSettlement()">PAY</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-4">
                    <button class="btn btn-dark px-4 rounded-pill fw-bold shadow-sm" onclick="window.print()"><i class="fas fa-print me-2"></i>Download PDF</button>
                    <button class="btn btn-outline-secondary px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Dismiss</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="billingProofModal" tabindex="-1" aria-labelledby="billingProofModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 overflow-hidden shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="billingProofModalTitle">Payment Proof</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light text-center p-3">
                    <img id="billingProofImage" src="" alt="Payment proof" class="img-fluid rounded" style="max-height:75vh;">
                </div>
            </div>
        </div>
    </div>

    <script>
        // ================= BILLING / INVOICE DATA STORE =================
        // In production this should load from the Laravel backend (invoices
        // table, joined with reservations/payments) via an API call instead
        // of a hardcoded JS array.
        let invoices = @json($invoiceRows);

        function peso(n) {
            return '₱' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function invoiceTotals(inv) {
            const extensions = inv.extensionPayments || [];
            const total = Number(inv.total) + extensions.reduce(function(sum, payment) {
                return sum + (Number(payment.amount) || 0);
            }, 0);
            const paid = Number(inv.paid) + extensions.reduce(function(sum, payment) {
                return sum + (Number(payment.paidAmount) || 0);
            }, 0);

            return { total: total, paid: paid, balance: Math.max(total - paid, 0) };
        }

        function invoiceIsPaid(inv) {
            const totals = invoiceTotals(inv);
            return !inv.verificationPending && !invoiceHasExtensionPending(inv) && totals.total > 0
                && totals.balance <= 0;
        }

        function invoiceHasExtensionPending(inv) {
            return (inv.extensionPayments || []).some(function(payment) {
                return payment.status === 'payment_pending' && Number(payment.submittedAmount) > 0;
            });
        }

        // ================= CLICKABLE STAT CARD FILTERS =================
        let currentBillingFilter = 'all';
        let currentBillingSource = 'all';

        function clearBillingFilter() {
            currentBillingFilter = 'all';
            renderInvoices();
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.stat-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    const filter = card.getAttribute('data-filter');
                    // Clicking the same filter again toggles back to "all"
                    currentBillingFilter = (currentBillingFilter === filter && filter !== 'all') ? 'all' : filter;
                    renderInvoices();
                });
            });
        });

        // ================= RENDER TABLE + AUTO-COMPUTE STAT CARDS =================
        function renderInvoices() {
            const query = (document.getElementById('invoiceSearchInput').value || '').trim().toUpperCase();
            const tbody = document.getElementById('invoiceTableBody');
            const noMsg = document.getElementById('noInvoiceMsg');
            const filterBanner = document.getElementById('filterBanner');
            const filterLabel = document.getElementById('filterLabel');

            const filterNames = {
                'all': 'All Processed Invoices',
                'paid': 'Fully Paid Invoices',
                'unpaid': 'Unpaid / Partial Invoices',
                'extension-pending': 'Extension Payments Awaiting Confirmation'
            };

            const visible = invoices.filter(function(inv) {
                const extensionReferences = (inv.extensionPayments || []).map(function(payment) {
                    return payment.reference || '';
                }).join(' ').toUpperCase();
                const matchesSearch = !query || inv.id.toUpperCase().includes(query)
                    || inv.ref.toUpperCase().includes(query) || extensionReferences.includes(query);
                const matchesSource = currentBillingSource === 'all' || inv.source === currentBillingSource;

                const isPaid = invoiceIsPaid(inv);
                let matchesStat = true;
                if (currentBillingFilter === 'paid') {
                    matchesStat = isPaid;
                } else if (currentBillingFilter === 'unpaid') {
                    matchesStat = !isPaid;
                } else if (currentBillingFilter === 'extension-pending') {
                    matchesStat = invoiceHasExtensionPending(inv);
                }

                return matchesSearch && matchesSource && matchesStat;
            });

            // Highlight the active stat card
            document.querySelectorAll('.stat-card').forEach(function(card) {
                const filter = card.getAttribute('data-filter');
                card.classList.toggle('active-filter', filter === currentBillingFilter && currentBillingFilter !== 'all');
            });

            // Show/hide the filter banner
            if (currentBillingFilter !== 'all') {
                filterBanner.classList.remove('d-none');
                filterLabel.innerText = filterNames[currentBillingFilter];
            } else {
                filterBanner.classList.add('d-none');
            }

            if (visible.length === 0) {
                tbody.innerHTML = '';
                noMsg.classList.remove('d-none');
            } else {
                noMsg.classList.add('d-none');
                tbody.innerHTML = visible.map(function(inv) {
                    const isPaid = invoiceIsPaid(inv);
                    const extensionVerificationPending = invoiceHasExtensionPending(inv);
                    const statusClass = inv.verificationPending || extensionVerificationPending || isPaid ? (inv.verificationPending || extensionVerificationPending ? 'status-pending' : 'status-paid') : 'status-pending';
                    const totals = invoiceTotals(inv);
                    const paidAmount = totals.paid;
                    const balance = totals.balance;
                    const statusLabel = inv.verificationPending
                        ? 'Pending Verification'
                        : (extensionVerificationPending
                        ? 'Extension Payment Pending'
                        : (balance <= 0
                        ? 'Fully Paid'
                        : (paidAmount > 0 ? 'Partial' : 'Unpaid')));
                    const totalClass = isPaid ? 'text-success' : 'text-danger';
                    const balanceClass = balance <= 0 ? 'text-success' : 'text-danger';

                    return '' +
                    '<tr>' +
                        '<td class="fw-bold">' + inv.id + '</td>' +
                        '<td>' +
                            '<div class="fw-bold text-dark">' + inv.name + '</div>' +
                            '<small class="text-muted d-block">' + inv.phone + '</small>' +
                            '<span class="badge billing-source-badge ' + (inv.source === 'walkin' ? 'bg-warning text-dark' : 'bg-primary') + ' rounded-pill">' + (inv.source === 'walkin' ? 'Walk-in' : 'Online') + '</span>' +
                        '</td>' +
                        '<td><span class="badge bg-dark rounded-pill px-3">' + inv.vehicle + '</span><small class="d-block text-muted mt-1">Plate: ' + (inv.vehiclePlate || 'Not assigned') + '</small></td>' +
                        '<td class="fw-bold ' + totalClass + '">' + peso(totals.total) + '</td>' +
                        '<td class="fw-bold text-success">' + peso(paidAmount) + '</td>' +
                        '<td class="fw-bold ' + balanceClass + '">' + peso(balance) + '</td>' +
                        '<td class="text-nowrap"><span class="status-badge clickable ' + statusClass + '" onclick="viewPaymentHistory(\'' + inv.id + '\')" title="Click to view payment history">' + statusLabel + '</span></td>' +
                        '<td class="text-center">' +
                            '<button class="btn btn-sm btn-outline-danger px-3 rounded-pill fw-bold" onclick="viewInvoice(\'' + inv.id + '\')">Review Bill</button>' +
                        '</td>' +
                    '</tr>';
                }).join('');
            }

            updateBillingStats();
        }

        document.querySelectorAll('.billing-source-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                currentBillingSource = tab.getAttribute('data-billing-source') || 'all';
                document.querySelectorAll('.billing-source-tab').forEach(function(item) {
                    item.classList.toggle('active', item === tab);
                });
                renderInvoices();
            });
        });

        async function deleteReservation(id) {
            if (!await showConfirm('Delete this reservation and its payment records?')) return;
            const response = await fetch(@json($isStaffMode ? url('/staff/reservations') : url('/admin/reservations')) + '/' + id, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            if (!response.ok) {
                showToast('Reservation could not be deleted.', 'error');
                return;
            }
            invoices = invoices.filter(function(inv) {
                return inv.reservationId !== id;
            });
            renderInvoices();
            showToast('Reservation and payment record deleted.', 'success');
        }

        // Computed directly from the invoices array — always in sync with
        // whatever is settled/paid, instead of hardcoded numbers.
        function updateBillingStats() {
            const paidBookings = invoices.filter(function(inv) { return invoiceIsPaid(inv); }).length;
            const unpaidBookings = invoices.filter(function(inv) { return !invoiceIsPaid(inv); }).length;
            const pendingExtensionPayments = invoices.reduce(function(count, inv) {
                return count + (inv.extensionPayments || []).filter(function(payment) {
                    return payment.status === 'payment_pending' && Number(payment.submittedAmount) > 0;
                }).length;
            }, 0);
            document.getElementById('statTotalRevenue').innerText = paidBookings;
            document.getElementById('statPending').innerText = unpaidBookings;
            document.getElementById('statProcessed').innerText = invoices.length;
            document.getElementById('statExtensionPending').innerText = pendingExtensionPayments;
        }

        let liveBillingSignature = '';
        async function syncLiveBilling() {
            if (document.hidden) return;
            const response = await fetch(@json($billingLiveUrl), {headers: {'Accept': 'application/json'}});
            if (!response.ok) return;
            const data = await response.json();
            const signature = JSON.stringify(data);
            if (signature === liveBillingSignature) return;
            liveBillingSignature = signature;
            data.forEach(function (item) {
                const invoice = invoices.find(row => row.reservationId === item.id);
                if (!invoice) return;
                invoice.total = Number(item.total_amount);
                invoice.paid = Number(item.paid_amount);
                invoice.status = item.payment_status;
                invoice.submittedAmount = Number(item.submitted_amount) || 0;
                invoice.verificationPending = !!item.verification_pending;
                invoice.ref = item.reference || 'No reference';
                invoice.proof = item.proof || null;
                invoice.extensionPayments = item.extensionPayments || [];
            });
            renderInvoices();
            if (currentInvoiceId) viewInvoice(currentInvoiceId, false);
        }
        setInterval(syncLiveBilling, 5000);

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

        // ================= CUSTOM CONFIRM MODAL (replaces native browser confirm()) =================
        // Usage: if (!(await showConfirm('Are you sure?'))) { return; }
        let bdConfirmModalInstance = null;
        let bdConfirmResolve = null;

        function showConfirm(message) {
            return new Promise(function(resolve) {
                document.getElementById('bdConfirmMessage').innerText = message;
                bdConfirmResolve = resolve;
                if (!bdConfirmModalInstance) {
                    bdConfirmModalInstance = new bootstrap.Modal(document.getElementById('bdConfirmModal'));
                }
                bdConfirmModalInstance.show();

                setTimeout(function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    const ownBackdrop = backdrops[backdrops.length - 1];
                    if (ownBackdrop) ownBackdrop.style.zIndex = 1085;
                }, 0);
            });
        }

        document.getElementById('bdConfirmOkBtn').addEventListener('click', function() {
            bdConfirmModalInstance.hide();
            if (bdConfirmResolve) { bdConfirmResolve(true); bdConfirmResolve = null; }
        });
        document.getElementById('bdConfirmCancelBtn').addEventListener('click', function() {
            bdConfirmModalInstance.hide();
            if (bdConfirmResolve) { bdConfirmResolve(false); bdConfirmResolve = null; }
        });

        // ================= INVOICE / BILLING SUMMARY MODAL =================
        let currentInvoiceId = null;

        let invoiceModalInstance = null;

        function viewInvoice(id, showModal) {
            const inv = invoices.find(function(x) { return x.id === id; });
            if (!inv) return;

            currentInvoiceId = id;
            const totals = invoiceTotals(inv);

            document.getElementById('mInvoiceNum').innerText = inv.id;
            document.getElementById('mCustName').innerText = inv.name;
            document.getElementById('mTotalBill').innerText = peso(totals.total);
            document.getElementById('mPaidAmt').innerText = peso(totals.paid);
            document.getElementById('mBalance').innerText = peso(totals.balance);
            document.getElementById('mRef').innerText = "Ref ID: " + inv.ref;
            document.getElementById('mPayMethod').innerText = inv.method;
            document.getElementById('mTotalBillLabel').innerText = 'Grand Total (including extensions):';
            const proofLink = document.getElementById('mProofLink');
            const proofIcon = document.getElementById('mProofIcon');
            const proofLabel = document.getElementById('mProofLabel');
            proofLink.classList.toggle('d-none', !inv.proof);
            proofIcon.classList.toggle('d-none', !!inv.proof);
            proofLink.dataset.proofUrl = inv.proof || '';
            proofLink.dataset.proofTitle = 'Booking Payment Proof';
            proofLink.innerText = 'View Booking Proof';
            proofLabel.innerText = inv.proof
                ? 'Booking payment proof'
                    + (Number(inv.submittedAmount) > 0 ? ' · Submitted amount: ' + peso(Number(inv.submittedAmount)) : '')
                : 'No booking payment proof uploaded';
            const bookingConfirmButton = document.getElementById('confirmBookingPaymentButton');
            bookingConfirmButton.classList.toggle('d-none', !inv.verificationPending);
            bookingConfirmButton.onclick = function () {
                confirmBookingPayment(inv.reservationId);
            };
            const extensionProofs = document.getElementById('mExtensionProofs');
            extensionProofs.classList.toggle('d-none', !(inv.extensionPayments || []).length);
            extensionProofs.innerHTML = (inv.extensionPayments || []).map(function (payment, index) {
                return '<div class="border-top pt-2 mt-2"><small class="fw-bold d-block text-danger">Extension Payment ' + (index + 1) + '</small>' +
                    '<span class="small d-block">Days: ' + Number(payment.days || 0) + '</span>' +
                    '<span class="small d-block">Amount: ' + peso(Number(payment.amount) || 0) + '</span>' +
                    (payment.status === 'payment_pending' && Number(payment.submittedAmount) > 0
                        ? '<span class="small d-block text-warning fw-bold">Submitted for verification: ' + peso(Number(payment.submittedAmount)) + '</span>'
                        : '') +
                    '<span class="small d-block">Ref ID: ' + escapeBillingHtml(payment.reference || 'No reference') + '</span>' +
                    (payment.proof ? '<button type="button" class="btn btn-link btn-sm px-0" data-proof-url="' + escapeBillingAttribute(payment.proof) + '" data-proof-title="Extension Payment ' + (index + 1) + ' Proof" onclick="openBillingProof(this)">View Extension Proof</button>' : '') +
                    (payment.paymentActor || []).map(function (entry) {
                        return '<span class="small d-block text-success">' + escapeBillingHtml(entry.role) + ': ' + escapeBillingHtml(entry.name) +
                            (entry.amount !== null && entry.amount !== undefined ? ' · ' + peso(Number(entry.amount) || 0) : '') +
                            (entry.date ? ' · ' + escapeBillingHtml(entry.date) : '') + '</span>';
                    }).join('') +
                    (payment.status === 'payment_pending' && Number(payment.submittedAmount) > 0
                        ? '<button type="button" class="btn btn-sm btn-success rounded-pill mt-2" onclick="confirmExtensionPayment(' + Number(inv.reservationId) + ', ' + Number(payment.id) + ')">Confirm Extension Payment</button>'
                        : '') +
                    '</div>';
            }).join('');
            document.getElementById('settleAmountInput').value = '';

            document.getElementById('settleArea').style.display = 'block';

            if (showModal !== false) {
                if (!invoiceModalInstance) {
                    invoiceModalInstance = new bootstrap.Modal(document.getElementById('invoiceModal'), {
                        backdrop: true,
                        keyboard: true
                    });
                }

                invoiceModalInstance.show();
            }

            // TODO: kapag naka-connect na sa Laravel backend, dito mo ilo-load
            // yung actual invoice record (GET /api/invoices/{id}) imbes na
            // umasa sa hardcoded `invoices` array sa itaas.
        }

        function viewPaymentHistory(id) {
            const inv = invoices.find(function(x) { return x.id === id; });
            if (!inv) return;

            const totals = invoiceTotals(inv);
            const balance = totals.balance;
            const statusLabel = invoiceHasExtensionPending(inv)
                ? 'Extension Payment Pending'
                : (balance <= 0 ? 'Fully Paid' : (totals.paid > 0 ? 'Partial' : 'Unpaid'));
            document.getElementById('historyInvoiceNum').innerText = inv.id;
            document.getElementById('historyStatus').innerText = statusLabel;

            const list = document.getElementById('paymentHistoryList');
            const history = inv.paymentActor || [];
            list.innerHTML = history.length
                ? history.map(function(entry) {
                    const amount = entry.amount !== null && entry.amount !== undefined
                        ? '<span class="fw-bold text-success">₱' + Number(entry.amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>'
                        : '<span class="text-muted">Status update</span>';
                    return '<div class="border rounded-3 p-3 mb-2">' +
                        '<div class="d-flex justify-content-between align-items-start">' +
                            '<div><strong>' + entry.name + '</strong> <span class="badge bg-dark rounded-pill history-role">' + entry.role + '</span></div>' +
                            amount +
                        '</div>' +
                        '<div class="text-muted mt-1">' + entry.action + ' &middot; ' + entry.date + '</div>' +
                    '</div>';
                }).join('')
                : '<div class="text-center text-muted py-3">No payment history recorded.</div>';

            bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentHistoryModal')).show();
        }

        function escapeBillingHtml(value) {
            const element = document.createElement('span');
            element.textContent = String(value);
            return element.innerHTML;
        }

        function escapeBillingAttribute(value) {
            return escapeBillingHtml(value).replaceAll('"', '&quot;').replaceAll("'", '&#39;');
        }

        let returnToInvoiceAfterProof = false;
        function openBillingProof(button) {
            const proofUrl = button.dataset.proofUrl || '';
            if (!proofUrl) return;

            const invoiceModal = document.getElementById('invoiceModal');
            const invoiceWasOpen = invoiceModal.classList.contains('show');
            document.getElementById('billingProofModalTitle').innerText = button.dataset.proofTitle || 'Booking Payment Proof';
            document.getElementById('billingProofImage').src = proofUrl;
            returnToInvoiceAfterProof = invoiceWasOpen;

            if (invoiceWasOpen) {
                bootstrap.Modal.getInstance(invoiceModal)?.hide();
                invoiceModal.addEventListener('hidden.bs.modal', function showProofModal() {
                    invoiceModal.removeEventListener('hidden.bs.modal', showProofModal);
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('billingProofModal')).show();
                }, { once: true });
                return;
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('billingProofModal')).show();
        }

        document.getElementById('mProofLink').dataset.proofTitle = 'Booking Payment Proof';
        document.getElementById('billingProofModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('billingProofImage').src = '';
            if (returnToInvoiceAfterProof && currentInvoiceId) {
                returnToInvoiceAfterProof = false;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('invoiceModal')).show();
            }
        });

        async function confirmExtensionPayment(reservationId, extensionId) {
            if (!(await showConfirm('Confirm this submitted extension payment?'))) return;
            const response = await fetch(@json($billingBaseUrl) + '/' + reservationId + '/extensions/' + extensionId + '/confirm', {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            if (!response.ok) {
                const result = await response.json().catch(function() { return {}; });
                showToast(result.message || 'Extension payment could not be confirmed.', 'error');
                return;
            }
            await syncLiveBilling();
            if (currentInvoiceId) viewInvoice(currentInvoiceId, false);
            showToast('Extension payment confirmed and balance updated.', 'success');
        }

        async function confirmBookingPayment(reservationId) {
            if (!(await showConfirm('Confirm this submitted booking payment?'))) return;

            const response = await fetch(@json($billingConfirmUrl) + '/' + reservationId + '/confirm', {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            const result = await response.json();
            if (!response.ok) {
                showToast(result.message || 'Booking payment could not be confirmed.', 'error');
                return;
            }

            const invoice = invoices.find(function (item) {
                return Number(item.reservationId) === Number(reservationId);
            });
            if (invoice) {
                invoice.paid = Number(result.paid_amount);
                invoice.submittedAmount = 0;
                invoice.verificationPending = false;
                invoice.status = result.payment_status;
                invoice.paymentActor = invoice.paymentActor || [];
                invoice.paymentActor.unshift({
                    name: result.confirmed_by,
                    role: result.confirmed_role,
                    action: 'Payment confirmed',
                    amount: Number(result.amount),
                    date: result.confirmed_at
                });
                viewInvoice(invoice.id, false);
                renderInvoices();
            }
            showToast('Booking payment confirmed. The updated balance is now available.', 'success');
        }

        async function processSettlement() {
            const inv = invoices.find(function(x) { return x.id === currentInvoiceId; });
            if (!inv) return;

            const input = document.getElementById('settleAmountInput');
            const amount = parseFloat(input.value);

            if (!amount || amount <= 0) {
                showToast('Please enter a valid payment amount.', 'error');
                return;
            }

            const remainingBalance = inv.total - inv.paid;
            if (amount > remainingBalance) {
                showToast('Amount exceeds the remaining balance of ' + peso(remainingBalance) + '.', 'error');
                return;
            }

            if (!(await showConfirm('Process a payment of ' + peso(amount) + ' for ' + inv.id + '?'))) {
                showToast('Cancelled — payment was not processed.', 'cancel');
                return;
            }

            const response = await fetch(@json($billingBaseUrl) + '/' + inv.reservationId + '/payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ amount: amount })
            });
            if (!response.ok) {
                showToast('Payment could not be saved.', 'error');
                return;
            }
            const result = await response.json();
            inv.total = Number(result.total_amount);
            inv.paid = Number(result.paid_amount);
            inv.status = result.payment_status;
            const totals = invoiceTotals(inv);
            document.getElementById('mTotalBill').innerText = peso(totals.total);
            document.getElementById('mPaidAmt').innerText = peso(totals.paid);
            document.getElementById('mBalance').innerText = peso(totals.balance);
            document.getElementById('settleAmountInput').value = '';
            document.getElementById('settleArea').style.display = 'block';
            renderInvoices();
            showToast('Payment saved and billing updated.', 'success');
        }

        // ===== GCash QR Upload Logic =====
        let selectedQrFile = null;
        const qrFileInput = document.getElementById('qrFileInput');
        const qrDropZone = document.getElementById('qrDropZone');
        const qrModalPreview = document.getElementById('qrModalPreview');
        const qrDropPlaceholder = document.getElementById('qrDropPlaceholder');
        const qrErrorMsg = document.getElementById('qrErrorMsg');

        function handleQrFile(file) {
            qrErrorMsg.classList.add('d-none');
            if (!file) return;

            const validTypes = ['image/png', 'image/jpeg'];
            if (!validTypes.includes(file.type)) {
                showToast('Invalid file type. Please upload a PNG or JPG image.', 'error');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showToast('File is too large. Max size is 5MB.', 'error');
                return;
            }

            selectedQrFile = file;
            const reader = new FileReader();
            reader.onload = function(e) {
                qrModalPreview.src = e.target.result;
                qrModalPreview.classList.remove('d-none');
                qrDropPlaceholder.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        }

        if (qrFileInput) {
            qrFileInput.addEventListener('change', (e) => handleQrFile(e.target.files[0]));
        }

        // Drag & drop support
        if (qrDropZone) {
            ['dragenter', 'dragover'].forEach(evt => {
                qrDropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    qrDropZone.classList.add('dragover');
                });
            });
            ['dragleave', 'drop'].forEach(evt => {
                qrDropZone.addEventListener(evt, (e) => {
                    e.preventDefault();
                    qrDropZone.classList.remove('dragover');
                });
            });
            qrDropZone.addEventListener('drop', (e) => {
                const file = e.dataTransfer.files[0];
                handleQrFile(file);
            });
        }

        async function saveQrCode() {
            if (!(await showConfirm(selectedQrFile
                ? 'Save the GCash details and replace the QR code shown to customers during checkout?'
                : 'Save the GCash account details shown to customers during checkout?'))) {
                showToast('Cancelled — QR code was not changed.', 'cancel');
                return;
            }

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PUT');
            if (selectedQrFile) {
                formData.append('gcash_qr', selectedQrFile);
            }
            formData.append('account_name', document.getElementById('qrAccountNameInput').value);
            formData.append('account_number', document.getElementById('qrAccountNumberInput').value);

            const response = await fetch(@json($paymentSettingsUrl), {
                method: 'POST',
                body: formData,
                headers: {'Accept': 'application/json'}
            });
            if (!response.ok) {
                showToast('GCash settings could not be saved.', 'error');
                return;
            }

            document.getElementById('qrPreviewImg').src = qrModalPreview.src;
            document.getElementById('gcashName').innerText = document.getElementById('qrAccountNameInput').value;
            document.getElementById('gcashNumber').innerText = document.getElementById('qrAccountNumberInput').value;

            // Reset modal state
            selectedQrFile = null;
            qrModalPreview.src = '';
            qrModalPreview.classList.add('d-none');
            qrDropPlaceholder.classList.remove('d-none');
            qrFileInput.value = '';

            var myModal = bootstrap.Modal.getInstance(document.getElementById('qrUploadModal'));
            myModal.hide();

            showToast('GCash QR code updated successfully!', 'success');
        }

        // Init
        renderInvoices();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>