<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Payments</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red:#b71c1c; --boss-dark:#121212; --boss-grey:#e0e0e0; }
        body { background:var(--boss-grey); font-family:'Segoe UI',sans-serif; }
        .sidebar { width:250px; height:100vh; background:var(--boss-dark); position:fixed; padding:20px; border-right:4px solid var(--boss-red); z-index:1000; display:flex; flex-direction:column; overflow-y:auto; }
        .nav-link { color:#fff; margin-bottom:10px; border-radius:10px; padding:12px 15px; font-weight:600; text-decoration:none; display:block; }
        .nav-link:hover,.nav-link.active { background:var(--boss-red); color:#fff!important; }
        .sidebar-footer { margin-top:auto; width:100%; border-top:1px solid #333; padding-top:15px; }
        .sidebar-footer a { color:#888; text-decoration:none; font-size:.85rem; }
        .main-content { margin-left:250px; min-height:100vh; }
        .top-nav { background:#fff; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #ccc; position:sticky; top:0; z-index:999; }
        .text-black-bold { color:#000; font-weight:800; } .text-boss-red { color:var(--boss-red); font-weight:800; }
        .content-container { padding:30px; }
        .payment-card { background:#fff; border-radius:20px; box-shadow:0 10px 25px rgba(0,0,0,.05); overflow:hidden; }
        .gcash-header-badge { background:#007dfe; color:#fff; padding:12px 20px; border-radius:15px; font-weight:bold; display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:20px; }
        .qr-code-img { width:200px; height:200px; border:5px solid #007dfe; border-radius:10px; margin-bottom:15px; object-fit:cover; }
        .status-badge { display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; max-width:125px; padding:7px 10px; border-radius:16px; font-weight:700; font-size:.7rem; line-height:1.25; text-align:center; white-space:normal; }
        .status-pending { background:#f5c451; color:#3f2d00; } .status-paid { background:#d1e7dd; color:#0f5132; }
        .status-approved,.status-payment_pending { background:#fff3cd; color:#856404; }
        .status-unpaid { background:#f8d7da; color:#842029; }
        .payment-proof-preview { display:inline-flex; flex-direction:column; align-items:flex-start; gap:5px; border:0; padding:0; background:none; color:#0d6efd; text-align:left; }
        .payment-proof-thumbnail { display:block; width:auto; max-width:100%; height:90px; object-fit:cover; border:1px solid #dee2e6; border-radius:8px; }
        .payment-completed-dialog { max-width:760px; }
        .payment-completed-dialog .modal-content { max-height:75vh; }
        .payment-completed-dialog .modal-body { overflow-y:auto; }
        .payment-layout-row { align-items:stretch; }
        .card.payment-panel { height:760px; min-height:760px; max-height:760px; display:flex; flex-direction:column; overflow:hidden; }
        .payment-card.payment-panel { height:auto; min-height:0; max-height:760px; align-self:flex-start; overflow-y:auto; }
        .payment-history-scroll { flex:1 1 auto; min-height:0; overflow-y:auto; padding-right:4px; }
        .payment-step[hidden] { display:none!important; }
        .payment-history-tabs { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .payment-history-tabs .nav-item { min-width:0; }
        .payment-history-tabs .nav-link { width:100%; min-height:48px; margin:0; padding:10px 8px; color:#212529; background:#f1f3f5; white-space:normal; line-height:1.25; }
        .payment-history-tabs .nav-link.active { color:#fff; background:#0d6efd; }
        @media(max-width:768px){ .sidebar{width:210px}.main-content{margin-left:0}.content-container{padding:15px} .sidebar-footer{position:static;width:100%;} }
        @media(max-width:575.98px){.payment-card{border-radius:15px}.payment-card.p-4{padding:1rem!important}.card.payment-panel{height:auto;min-height:0;max-height:none;overflow:visible}.payment-card.payment-panel{max-height:none;overflow:visible}.payment-history-scroll{max-height:55vh}.gcash-header-badge{padding:10px 12px;font-size:.85rem}.qr-code-img{width:min(180px,65vw);height:auto;aspect-ratio:1}.payment-completed-dialog .modal-content{max-height:85vh}.payment-completed-dialog .modal-body{overflow-y:auto}.payment-history-tabs .nav-link{min-height:44px;padding:8px 5px;font-size:.78rem}.table-responsive{font-size:.85rem}.modal-dialog{margin:.5rem}.modal-body{padding:1rem!important}}
    </style>
</head>
<body>
    @include('user.partials.navigation', ['title' => 'PAYMENT', 'accent' => 'CENTER'])

        <div class="content-container">
            @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

            @php
                $bookingTotalFor = fn ($item) => (float) $item->total_amount > 0
                    ? (float) $item->total_amount
                    : (float) ($item->vehicleUnit?->price ?? 0) * $item->rentalDays()
                        + ($item->driver_option === 'with_driver' ? 1500 * $item->rentalDays() : 0);
            @endphp
            <div class="row g-4 payment-layout-row">
                <div class="col-lg-6">
                    <div class="payment-card payment-panel p-4">
                        <h5 class="fw-bold mb-3">Payment Method</h5>
                        <div class="gcash-header-badge"><strong>GCash</strong> PAY VIA GCASH</div>

                        @if($reservation)
                            <form id="paymentSubmissionForm" action="{{ route('user.payments.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="reservation_id" id="paymentReservationId">
                                <input type="hidden" name="extension_id" id="paymentExtensionId">
                                <div id="paymentControlStep" class="payment-step">
                                    <label for="paymentControlNumber" class="fw-bold small mb-2">Enter Your Control Number</label>
                                    <input type="text" id="paymentControlNumber" name="control_number" class="form-control shadow-sm mb-2" placeholder="Enter your booking control number" autocomplete="off" required>
                                    <div id="paymentTargetMessage" class="small mb-2" aria-live="polite">Enter your Control Number to see which balance to pay.</div>
                                    <div id="paymentTargetSummary" class="alert alert-primary py-2 small mb-3 d-none">
                                        <span class="fw-bold" id="paymentTargetType"></span>
                                        <span class="d-block">Amount Due: <strong id="paymentTargetBalance"></strong></span>
                                    </div>
                                    <div class="text-center">
                                        <h6 class="fw-bold mb-3 text-primary">Scan QR Code to Pay</h6>
                                        <div class="border rounded-3 p-4 text-muted small mb-3">
                                            @if($paymentSettings->gcash_qr_path)
                                                <img src="{{ asset('storage/'.$paymentSettings->gcash_qr_path) }}" alt="GCash QR Code" class="img-fluid" style="max-width: 220px;">
                                            @else
                                                <i class="fas fa-qrcode fa-3x text-primary mb-2 d-block"></i>
                                                GCash QR code will appear here after staff uploads it.
                                            @endif
                                        </div>
                                        <p class="small text-muted mb-4">Account Name: <span class="fw-bold text-dark">{{ $paymentSettings->account_name ?: 'BIG BOSS RENTAL' }}</span><br>Account No: <span class="fw-bold text-dark">{{ $paymentSettings->account_number ?: '0912 345 6789' }}</span></p>
                                    </div>
                                    <hr>
                                    <button type="button" id="paymentDetailsNextButton" class="btn btn-primary w-100 rounded-pill fw-bold py-2 shadow" onclick="showPaymentDetailsStep()" disabled>NEXT <i class="fas fa-arrow-right ms-1"></i></button>
                                </div>
                                <div id="paymentDetailsStep" class="payment-step" hidden>
                                    <hr>
                                    <h6 class="fw-bold mb-3">Payment Details</h6>
                                    <label for="paymentAmount" class="fw-bold small mb-2">Amount You Are Paying (₱)</label>
                                    <input type="number" id="paymentAmount" name="payment_amount" class="form-control mb-3 rounded-pill shadow-sm" min="0.01" step="0.01" required placeholder="Enter amount paid" disabled>
                                    <label for="referenceId" class="fw-bold small mb-2">GCash Ref (or upload a screenshot)</label>
                                    <input type="text" id="referenceId" name="reference_id" class="form-control mb-3 rounded-pill shadow-sm" placeholder="Enter your GCash reference number" maxlength="32" disabled>
                                    <label for="paymentProof" class="fw-bold small mb-2">GCash Screenshot (optional if GCash Ref is provided)</label>
                                    <input type="file" id="paymentProof" name="payment_proof" class="form-control mb-3 rounded-pill shadow-sm" accept="image/jpeg,image/png" disabled>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary w-50 rounded-pill fw-bold py-2" onclick="showPaymentControlStep()"><i class="fas fa-arrow-left me-1"></i> BACK</button>
                                        <button type="submit" id="submitPaymentButton" class="btn btn-danger w-50 rounded-pill fw-bold py-2 shadow" disabled>SUBMIT PAYMENT</button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-info">You do not have a reservation yet. Submit a reservation first before sending payment proof.</div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card payment-panel border-0 rounded-4 shadow-sm p-4">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-history text-danger me-2"></i>Payment History</h6>
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill text-nowrap" data-bs-toggle="modal" data-bs-target="#completedPaymentsModal">View Completed</button>
                        </div>
                        <ul class="nav nav-pills payment-history-tabs gap-2 mb-3" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="pending-history-tab" data-bs-toggle="pill" data-bs-target="#pending-history" type="button" role="tab" aria-controls="pending-history" aria-selected="true">Pending Payment Book</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="extension-history-tab" data-bs-toggle="pill" data-bs-target="#extension-history" type="button" role="tab">Extended Book</button></li>
                        </ul>
                        <div class="tab-content payment-history-scroll">
                            <div class="tab-pane fade show active" id="pending-history" role="tabpanel" aria-labelledby="pending-history-tab">
                                @php
                                    $pendingBookings = $reservations->filter(fn ($item) => !in_array($item->status, ['cancelled', 'void'], true)
                                        && ($bookingTotalFor($item) > (float) $item->paid_amount
                                            || ($item->payment_status === 'pending'
                                                && ($item->payment_submitted_amount > 0 || $item->payment_proof_path || $item->payment_reference_id))));
                                @endphp
                                @forelse($pendingBookings as $item)
                                    @php
                                        $bookingTotal = $bookingTotalFor($item);
                                        $bookingPaid = (float) $item->paid_amount;
                                        $bookingBalance = max($bookingTotal - $bookingPaid, 0);
                                        $bookingProofPending = $item->payment_status === 'pending'
                                            && ($item->payment_submitted_amount > 0 || $item->payment_proof_path || $item->payment_reference_id);
                                    @endphp
                                    <div class="border rounded-3 p-3 mb-2">
                                        <div class="d-flex justify-content-between gap-2">
                                            <div>
                                                <strong>{{ $item->vehicle }}</strong>
                                                <small class="d-block text-muted">Control Number: {{ $item->control_number }} · {{ $item->pickup_date->format('M d, Y') }}</small>
                                            </div>
                                            <span class="status-badge {{ $bookingProofPending ? 'status-pending' : 'status-unpaid' }}">{{ $bookingProofPending ? 'PENDING VERIFICATION' : 'PENDING PAYMENT' }}</span>
                                        </div>
                                        <div class="small mt-2">Total: ₱{{ number_format($bookingTotal, 2) }} · Paid: ₱{{ number_format($bookingPaid, 2) }}</div>
                                        <strong class="small text-danger d-block">Pending Balance: ₱{{ number_format($bookingBalance, 2) }}</strong>
                                        @if($item->payment_submitted_amount)
                                            <small class="d-block text-muted">Submitted for verification: ₱{{ number_format($item->payment_submitted_amount, 2) }}</small>
                                        @endif
                                        @if($item->payment_reference_id)
                                            <small class="d-block text-muted">Reference: {{ $item->payment_reference_id }}</small>
                                        @endif
                                        @if($item->payment_proof_path)
                                            <button type="button" class="payment-proof-preview mt-2" data-screenshot-url="{{ asset('storage/'.$item->payment_proof_path) }}" onclick="openPaymentScreenshot(this)">
                                                <img class="payment-proof-thumbnail" src="{{ asset('storage/'.$item->payment_proof_path) }}" alt="Submitted booking payment screenshot">
                                                <span class="small text-decoration-underline">View submitted screenshot</span>
                                            </button>
                                        @endif
                                        @if(!$bookingProofPending)
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill mt-2" data-control-number="{{ $item->control_number }}" onclick="selectPaymentTarget(this)">Pay This Booking</button>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">No pending booking payments.</p>
                                @endforelse
                            </div>
                            <div class="tab-pane fade" id="extension-history" role="tabpanel" aria-labelledby="extension-history-tab">
                                @php
                                    $extensions = $reservations->flatMap->extensions
                                        ->filter(fn ($item) => in_array($item->status, ['approved', 'payment_pending'], true)
                                            && (float) $item->amount > (float) $item->paid_amount)
                                        ->sortByDesc('created_at');
                                @endphp
                                @forelse($extensions as $extension)
                                    @php
                                        $extensionProofPending = $extension->status === 'payment_pending'
                                            && ($extension->payment_proof_path || $extension->payment_reference_id);
                                        $extensionPaid = (float) $extension->paid_amount;
                                        $extensionBalance = max((float) $extension->amount - $extensionPaid, 0);
                                        $extensionStatus = $extensionBalance <= 0
                                            ? 'COMPLETED'
                                            : ($extensionProofPending
                                                ? 'PENDING VERIFICATION'
                                                : (in_array($extension->status, ['approved', 'payment_pending'], true)
                                                    ? 'PENDING PAYMENT'
                                                    : strtoupper(str_replace('_', ' ', $extension->status))));
                                    @endphp
                                    <div class="border rounded-3 p-3 mb-2">
                                        <div class="d-flex justify-content-between gap-2">
                                            <div>
                                                <strong>{{ $extension->reservation->vehicle }} · Extension</strong>
                                                <small class="d-block text-muted">Control Number: {{ $extension->reservation->control_number }}</small>
                                                <small class="d-block text-muted">{{ $extension->days }} day(s) · Requested {{ $extension->created_at->format('M d, Y') }}</small>
                                            </div>
                                            <span class="status-badge {{ $extensionBalance <= 0 ? 'status-paid' : ($extensionProofPending ? 'status-pending' : 'status-unpaid') }}">{{ $extensionStatus }}</span>
                                        </div>
                                        <div class="small mt-2">Extension Total: ₱{{ number_format($extension->amount, 2) }} · Paid: ₱{{ number_format($extensionPaid, 2) }}</div>
                                        <strong class="small text-danger d-block mt-1">Pending Balance: ₱{{ number_format($extensionBalance, 2) }}</strong>
                                        @if($extension->payment_submitted_amount)
                                            <small class="d-block text-muted">Submitted for verification: ₱{{ number_format($extension->payment_submitted_amount, 2) }}</small>
                                        @endif
                                        @if($extension->payment_reference_id)
                                            <small class="d-block text-muted">Reference: {{ $extension->payment_reference_id }}</small>
                                        @endif
                                        @if($extension->payment_proof_path)
                                            <button type="button" class="payment-proof-preview mt-2" data-screenshot-url="{{ asset('storage/'.$extension->payment_proof_path) }}" onclick="openPaymentScreenshot(this)">
                                                <img class="payment-proof-thumbnail" src="{{ asset('storage/'.$extension->payment_proof_path) }}" alt="Submitted extension payment screenshot">
                                                <span class="small text-decoration-underline">View submitted screenshot</span>
                                            </button>
                                        @endif
                                        @if($extensionBalance > 0 && in_array($extension->status, ['approved', 'payment_pending'], true) && !$extensionProofPending)
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill mt-2" data-control-number="{{ $extension->reservation->control_number }}" onclick="selectPaymentTarget(this)">Pay This Extension</button>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-muted small mb-0">No rental extension history.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="completedPaymentsModal" tabindex="-1" aria-labelledby="completedPaymentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable payment-completed-dialog">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="completedPaymentsModalLabel"><i class="fas fa-circle-check text-success me-2"></i>Completed Payments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php
                        $completedBookings = $reservations->filter(fn ($item) => !in_array($item->status, ['cancelled', 'void'], true)
                            && $item->payment_status === 'approved'
                            && $bookingTotalFor($item) > 0
                            && (float) $item->paid_amount >= $bookingTotalFor($item));
                        $completedExtensions = $reservations->flatMap->extensions
                            ->filter(fn ($item) => $item->status === 'paid' && (float) $item->amount > 0
                                && (float) $item->paid_amount >= (float) $item->amount)
                            ->sortByDesc('created_at');
                        $completedBookingsByReservation = $completedBookings->keyBy('id');
                        $completedExtensionsByReservation = $completedExtensions->groupBy('reservation_id');
                        $completedReservationIds = $completedBookingsByReservation->keys()
                            ->merge($completedExtensionsByReservation->keys())
                            ->unique();
                    @endphp
                    @forelse($completedReservationIds as $reservationId)
                        @php
                            $booking = $completedBookingsByReservation->get($reservationId);
                            $extensionsForBooking = $completedExtensionsByReservation->get($reservationId, collect());
                            $paymentReservation = $booking ?? $extensionsForBooking->first()->reservation;
                        @endphp
                        <div class="border rounded-3 p-3 mb-3" data-completed-control="{{ $paymentReservation->control_number }}">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <strong>{{ $paymentReservation->vehicle }}</strong>
                                <span class="status-badge status-paid">COMPLETED</span>
                            </div>
                            <small class="d-block text-muted mb-3">Control Number: {{ $paymentReservation->control_number }}</small>
                            <div class="row g-3">
                                @if($booking)
                                    <div class="{{ $extensionsForBooking->isNotEmpty() ? 'col-md-6' : 'col-12' }}">
                                        <strong>{{ $booking->vehicle }} · Original Booking</strong>
                                        <div class="small mt-2">Total: ₱{{ number_format($bookingTotalFor($booking), 2) }} · Paid: ₱{{ number_format($booking->paid_amount, 2) }}</div>
                                        @forelse($reservationApprovals->get($booking->id, collect()) as $approval)
                                            <small class="d-block text-success mt-1">Approved/recorded by {{ $approval['role'] }}: {{ $approval['name'] }}@if($approval['amount'] !== null) · ₱{{ number_format((float) $approval['amount'], 2) }}@endif · {{ $approval['date'] }}</small>
                                        @empty
                                            <small class="d-block text-muted mt-1">Approval staff details are unavailable for this payment.</small>
                                        @endforelse
                                        @if($booking->payment_proof_path)
                                            <button type="button" class="btn btn-link btn-sm p-0 mt-2" data-screenshot-url="{{ asset('storage/'.$booking->payment_proof_path) }}" onclick="openPaymentScreenshot(this)">
                                                <i class="fas fa-image me-1"></i>View payment screenshot
                                            </button>
                                        @endif
                                    </div>
                                @endif
                                @foreach($extensionsForBooking as $extension)
                                    <div class="{{ $booking ? 'col-md-6' : 'col-12' }}">
                                        <strong>{{ $extension->reservation->vehicle }} · Extension</strong>
                                        <div class="small mt-2">Extension Total: ₱{{ number_format($extension->amount, 2) }} · Paid: ₱{{ number_format($extension->paid_amount, 2) }}</div>
                                        @forelse($extensionApprovals->get($extension->id, collect()) as $approval)
                                            <small class="d-block text-success mt-1">Approved by {{ $approval['role'] }}: {{ $approval['name'] }}@if($approval['amount'] !== null) · ₱{{ number_format((float) $approval['amount'], 2) }}@endif · {{ $approval['date'] }}</small>
                                        @empty
                                            <small class="d-block text-muted mt-1">Approval staff details are unavailable for this payment.</small>
                                        @endforelse
                                        @if($extension->payment_proof_path)
                                            <button type="button" class="btn btn-link btn-sm p-0 mt-2" data-screenshot-url="{{ asset('storage/'.$extension->payment_proof_path) }}" onclick="openPaymentScreenshot(this)">
                                                <i class="fas fa-image me-1"></i>View payment screenshot
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted mb-0">No completed payments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="paymentScreenshotModal" tabindex="-1" aria-labelledby="paymentScreenshotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="paymentScreenshotModalLabel">Submitted Payment Screenshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center bg-light p-2">
                    <img id="paymentScreenshotImage" src="" alt="Submitted payment screenshot" class="img-fluid rounded" style="max-height:75vh;">
                </div>
            </div>
        </div>
    </div>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2000">
        <div id="paymentToast" class="toast align-items-center text-bg-dark border-0" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex">
                <div id="paymentToastMessage" class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const paymentTargets = @json($paymentTargets);

        function showPaymentToast(message) {
            const toastElement = document.getElementById('paymentToast');
            document.getElementById('paymentToastMessage').textContent = message;
            bootstrap.Toast.getOrCreateInstance(toastElement).show();
        }

        function openPaymentScreenshot(button) {
            const image = document.getElementById('paymentScreenshotImage');
            image.src = button.dataset.screenshotUrl;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentScreenshotModal')).show();
        }

        document.getElementById('paymentScreenshotModal')?.addEventListener('hidden.bs.modal', function() {
            document.getElementById('paymentScreenshotImage').src = '';
        });

        function resetPaymentDetails(message, className) {
            showPaymentControlStep();
            document.getElementById('paymentReservationId').value = '';
            document.getElementById('paymentExtensionId').value = '';
            document.getElementById('paymentDetailsNextButton').disabled = true;
            document.getElementById('paymentAmount').value = '';
            document.getElementById('paymentAmount').removeAttribute('max');
            document.getElementById('paymentAmount').disabled = true;
            document.getElementById('referenceId').value = '';
            document.getElementById('referenceId').disabled = true;
            document.getElementById('paymentProof').value = '';
            document.getElementById('paymentProof').disabled = true;
            document.getElementById('submitPaymentButton').disabled = true;
            document.getElementById('paymentTargetSummary').classList.add('d-none');
            document.getElementById('paymentTargetType').textContent = '';
            document.getElementById('paymentTargetBalance').textContent = '';
            const messageElement = document.getElementById('paymentTargetMessage');
            messageElement.className = 'small mb-3 ' + className;
            messageElement.textContent = message;
        }

        function checkPaymentTarget(showNotFound = false) {
            const controlNumber = document.getElementById('paymentControlNumber').value.trim().toUpperCase();
            if (!controlNumber) {
                resetPaymentDetails('Enter your Control Number to see which balance to pay.', 'text-muted');
                return;
            }
            const target = paymentTargets.find(item => item.controlNumber.toUpperCase() === controlNumber);
            if (!target || target.cancelled) {
                resetPaymentDetails(
                    showNotFound ? 'Control Number was not found in your bookings.' : 'Enter the full Control Number from your booking.',
                    showNotFound ? 'text-danger' : 'text-muted'
                );
                if (showNotFound) {
                    showPaymentToast('Control Number was not found in your bookings.');
                }
                return;
            }

            if (target.bookingWaiting) {
                showWaitingPaymentTarget(
                    target,
                    null,
                    target.bookingBalance,
                    'Pending Payment Book',
                    'Payment is waiting for Admin or Staff verification.'
                );
                return;
            }

            if (target.bookingBalance > 0) {
                activatePaymentTarget(target, null, target.bookingBalance, 'Pending Booking Payment');
                return;
            }

            if (target.extensionId) {
                if (target.extensionWaiting) {
                    showWaitingPaymentTarget(
                        target,
                        target.extensionId,
                        target.extensionBalance,
                        'Extended Book',
                        'Extension payment is waiting for Admin or Staff verification.'
                    );
                    return;
                }
                activatePaymentTarget(target, target.extensionId, target.extensionBalance, 'Extended Rental Payment');
                return;
            }

            resetPaymentDetails('Wala ka nang natitirang babayaran para sa booking na ito.', 'text-success');
            showPaymentToast('Wala ka nang babayaran para sa Control Number na ito.');
        }

        function showWaitingPaymentTarget(target, extensionId, balance, label, message) {
            if (balance > 0) {
                activatePaymentTarget(target, extensionId, balance, label);
            } else {
                resetPaymentDetails(message, 'text-warning');
                document.getElementById('paymentReservationId').value = target.reservationId;
                document.getElementById('paymentExtensionId').value = extensionId || '';
                document.getElementById('paymentTargetType').textContent = label;
                document.getElementById('paymentTargetBalance').textContent = '₱' + Number(balance).toLocaleString('en-PH', { minimumFractionDigits: 2 });
                document.getElementById('paymentTargetSummary').classList.remove('d-none');
            }
            const messageElement = document.getElementById('paymentTargetMessage');
            messageElement.className = 'small mb-3 text-warning';
            messageElement.textContent = message + (balance > 0
                ? ' You can update and resubmit the amount or proof; the new submission will replace the unverified one.'
                : ' No further amount is due unless Admin or Staff updates the booking balance.');
        }

        function activatePaymentTarget(target, extensionId, balance, label) {
            document.getElementById('paymentReservationId').value = target.reservationId;
            document.getElementById('paymentExtensionId').value = extensionId || '';
            document.getElementById('paymentDetailsNextButton').disabled = false;
            document.getElementById('paymentTargetType').textContent = label;
            document.getElementById('paymentTargetBalance').textContent = '₱' + Number(balance).toLocaleString('en-PH', { minimumFractionDigits: 2 });
            document.getElementById('paymentTargetSummary').classList.remove('d-none');
            const amountInput = document.getElementById('paymentAmount');
            amountInput.max = balance;
            amountInput.disabled = false;
            document.getElementById('referenceId').disabled = false;
            document.getElementById('paymentProof').disabled = false;
            document.getElementById('submitPaymentButton').disabled = false;
            const messageElement = document.getElementById('paymentTargetMessage');
            messageElement.className = 'small mb-3 text-success';
            messageElement.textContent = label + ' found for ' + target.controlNumber + '.';
        }

        function showPaymentDetailsStep() {
            if (document.getElementById('paymentDetailsNextButton').disabled) {
                showPaymentToast('Ilagay muna ang valid na Control Number bago magpatuloy.');
                return;
            }
            document.getElementById('paymentControlStep').hidden = true;
            document.getElementById('paymentDetailsStep').hidden = false;
            document.getElementById('paymentAmount').focus();
        }

        function showPaymentControlStep() {
            const controlStep = document.getElementById('paymentControlStep');
            if (controlStep) controlStep.hidden = false;
            const detailsStep = document.getElementById('paymentDetailsStep');
            if (detailsStep) detailsStep.hidden = true;
        }

        function selectPaymentTarget(button) {
            document.getElementById('paymentControlNumber').value = button.dataset.controlNumber;
            checkPaymentTarget();
            document.getElementById('paymentSubmissionForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        document.getElementById('paymentControlNumber')?.addEventListener('input', function() {
            checkPaymentTarget();
        });

        document.getElementById('paymentControlNumber')?.addEventListener('blur', function() {
            if (this.value.trim()) checkPaymentTarget(true);
        });

        document.getElementById('paymentControlNumber')?.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                checkPaymentTarget(true);
            }
        });

        document.getElementById('paymentSubmissionForm')?.addEventListener('submit', function(event) {
            if (document.getElementById('submitPaymentButton').disabled) {
                event.preventDefault();
                showPaymentToast('Ilagay muna ang valid na Control Number bago magbayad.');
            }
        });

    </script>
</body>
</html>
