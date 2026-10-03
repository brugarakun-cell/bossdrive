<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BossDrive - My Active Rental</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--boss-red:#b71c1c;--boss-dark:#121212;--boss-grey:#e0e0e0}
        body{background:var(--boss-grey);font-family:'Segoe UI',sans-serif}.sidebar{width:250px;height:100vh;background:var(--boss-dark);position:fixed;padding:20px;border-right:4px solid var(--boss-red);z-index:1000;display:flex;flex-direction:column;overflow-y:auto}.nav-link{color:#fff;margin-bottom:10px;border-radius:10px;padding:12px 15px;font-weight:600;text-decoration:none;display:block}.nav-link:hover,.nav-link.active{background:var(--boss-red);color:#fff!important}.sidebar-footer{margin-top:auto;width:100%;border-top:1px solid #333;padding-top:15px}.sidebar-footer a{color:#888;text-decoration:none;font-size:.85rem}.main-content{margin-left:250px;min-height:100vh}.top-nav{background:#fff;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #ccc;position:sticky;top:0;z-index:999}.content-container{padding:30px}.status-card{background:#fff;border-radius:20px;box-shadow:0 10px 25px rgba(0,0,0,.05);overflow:hidden}.card-accent{height:5px;background:var(--boss-red)}.car-preview{background:#f8f9fa;border-radius:15px;padding:20px;text-align:center}.label-text{font-size:.7rem;color:#888;font-weight:800;text-transform:uppercase;display:block}.data-text{font-size:1rem;font-weight:700}.info-box{padding:10px 0;border-bottom:1px solid #f0f0f0}.condition-box{background:#fafafa;border:1px solid #eee;border-radius:12px;padding:15px}.condition-item{padding:8px 0;border-bottom:1px dashed #eee}.condition-item:last-child{border-bottom:0}.progress-container{margin:20px 0}.progress{height:12px;border-radius:10px;background:#eee}.progress-bar{background:var(--boss-red);border-radius:10px}.countdown-timer{background:#fff5f5;border:1px solid #ffcdd2;padding:15px;border-radius:12px;text-align:center}.tracker{display:flex;justify-content:space-between;position:relative;margin:25px 0}.tracker:before{content:'';position:absolute;top:15px;left:0;right:0;height:4px;background:#eee}.step{position:relative;z-index:1;text-align:center;width:20%}.step-icon{width:35px;height:35px;border-radius:50%;background:#eee;color:#aaa;display:flex;align-items:center;justify-content:center;margin:auto;border:3px solid #fff}.step.active .step-icon{background:var(--boss-red);color:#fff}.step.active .step-text{color:var(--boss-red);font-weight:700}.step-text{font-size:.7rem;text-transform:uppercase;color:#888;margin-top:5px}@media(max-width:768px){.sidebar{width:210px}.main-content{margin-left:0}.content-container{padding:15px}}@media(max-width:575.98px){.status-card{border-radius:15px}.status-card>.p-4{padding:1rem!important}.tracker{margin:18px 0}.step-icon{width:30px;height:30px;font-size:.75rem}.tracker:before{top:13px}.step-text{font-size:.58rem}.data-text{font-size:.92rem}.countdown-timer{padding:12px}.countdown-timer h4{font-size:1.1rem}.condition-box{padding:12px}.modal-dialog{margin:.5rem}.modal-body{padding:1rem!important}}
    </style>
</head>
<body>
@include('user.partials.navigation', ['title' => 'ACTIVE', 'accent' => 'RENTAL'])
<div class="content-container">
@if(!$reservation)
<div class="status-card"><div class="card-accent"></div><div class="p-5 text-center"><i class="fas fa-hourglass-half text-warning fa-2x mb-3"></i><h5 class="fw-bold">No Active Rental Yet</h5><p class="text-muted small mb-0">Your Active Rental will open automatically after Admin confirms your documents.</p><a href="{{ route('user.dashboard') }}" class="btn btn-outline-danger rounded-pill fw-bold mt-3">Back to Dashboard</a></div></div>
@else
@php
    $carImages = ['Toyota Vios'=>'car1.png','Mitsubishi Mirage'=>'car2.png','Honda Civic'=>'car3.png','Toyota Fortuner'=>'car4.png'];
    $image = $carImages[$reservation->vehicle] ?? 'car1.png';
    $released = $reservation->status === 'released';
@endphp
<div class="status-card">
    <div class="card-accent"></div>
    <div class="p-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="car-preview mb-3">
                    <img src="{{ asset('image/'.$image) }}" class="img-fluid" style="max-height:180px">
                    <h5 class="fw-bold mt-2">{{ $reservation->vehicle }}</h5>
                    <small class="text-muted">Plate: {{ $activeVehicle?->plate ?? 'Not assigned' }}</small>
                    <small class="text-muted fw-bold">{{ $reservation->control_number }}</small>
                </div>
                <div class="alert alert-{{ $released ? 'success' : 'warning' }} small" id="awaitingReleasePanel">
                    <i class="fas fa-{{ $released ? 'key' : 'hourglass-half' }} me-1"></i>
                    {{ $released ? 'Vehicle released. Your rental is active.' : 'Awaiting Vehicle Release. Staff must mark this unit as Released.' }}
                </div>
                <div class="progress-container">
                    <div class="d-flex justify-content-between mb-1">
                        <small class="fw-bold">Trip Progress</small>
                        <small class="text-danger fw-bold">{{ $released ? '75' : '50' }}%</small>
                    </div>
                    <div class="progress"><div class="progress-bar" style="width:{{ $released ? '75' : '50' }}%"></div></div>
                </div>
                <div class="countdown-timer">
                    <span class="label-text">Time Remaining</span>
                    <h4 class="fw-bold text-danger mb-0" id="timeRemainingText">--d : --h : --m</h4>
                </div>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-bold border-bottom pb-2 mb-3">Rental Information</h6>
                <div class="info-box"><span class="label-text">Start Date</span><span class="data-text">{{ $reservation->pickup_date->format('F j, Y') }}</span></div>
                <div class="info-box"><span class="label-text">Current Return Date</span><span class="data-text text-danger">{{ $reservation->return_date->format('F j, Y') }}</span></div>
                <div class="info-box"><span class="label-text">Service</span><span class="data-text">{{ ucfirst($reservation->service_option) }}</span></div>
                <button type="button" class="btn btn-outline-danger w-100 mt-4 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#pickupConditionModal"><i class="fas fa-car-side me-2"></i>VEHICLE PICKUP CONDITION CHECK</button>
                <button type="button" class="btn btn-dark w-100 mt-2 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#extendModal" {{ $released ? '' : 'disabled' }}><i class="fas fa-clock me-2"></i>REQUEST EXTENSION</button>
                <button type="button" class="btn btn-outline-warning w-100 mt-2 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#ratingModal" {{ $released && $activeVehicle ? '' : 'disabled' }}><i class="fas fa-star me-2"></i>RATE YOUR RENTAL</button>
            </div>
            <div class="col-lg-4">
                <div class="condition-box h-100">
                    <h6 class="fw-bold mb-3"><i class="fas fa-clipboard-list text-danger me-2"></i>Return Reminders</h6>
                    <p class="small mb-2"><i class="fas fa-gas-pump text-danger me-2"></i>Vehicle must be returned with a full tank.</p>
                    <p class="small mb-2"><i class="fas fa-car-crash text-danger me-2"></i>Report existing damage before use.</p>
                    <p class="small mb-2"><i class="fas fa-clock text-danger me-2"></i>Late returns may have additional charges.</p>
                    <p class="small mb-1"><i class="fas fa-coins text-danger me-2"></i>Extra rental hours will be charged:</p>
                    <p class="small mb-1 ms-4">Sedan — ₱120/hr</p>
                    <p class="small mb-1 ms-4">Expanded (Gas) — ₱150/hr</p>
                    <p class="small mb-2 ms-4">Expanded (Diesel) — ₱200/hr</p>
                    <p class="small mb-2"><i class="fas fa-ban text-danger me-2"></i>No smoking and no garbage inside the car.</p>
                    <p class="small mb-2"><i class="fas fa-car-crash text-danger me-2"></i>If the vehicle is damaged, it cannot be returned until repaired. The customer must still pay for the repair time used.</p>
                    <p class="small mb-2"><i class="fas fa-wallet text-danger me-2"></i>Rental extensions can be paid through GCash or upon returning the vehicle.</p>
                    <p class="small mb-0"><i class="fas fa-hourglass-end text-danger me-2"></i>Request an extension before the 12-hour rental period ends.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pickupConditionModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4"><div class="modal-body p-4"><h5 class="fw-bold mb-3">Vehicle Pickup Condition Check</h5><p class="text-muted small">Your verification documents were already submitted during booking. You do not need to submit them again for pickup or return.</p><form id="pickupConditionForm" data-no-page-loader method="POST" action="{{ route('user.active-rental.condition', $reservation) }}" enctype="multipart/form-data">@csrf<div class="condition-box"><div class="condition-item"><input type="checkbox" name="checks[]" value="Fuel level checked and noted" required> Fuel level checked and noted</div><div class="condition-item"><input type="checkbox" name="checks[]" value="Exterior/body inspected" required> Exterior/body inspected</div><div class="condition-item"><input type="checkbox" name="checks[]" value="Interior checked and clean" required> Interior checked and clean</div><div class="condition-item"><input type="checkbox" name="checks[]" value="Accessories/tools verified" required> Accessories/tools verified</div><div class="condition-item"><input type="checkbox" name="checks[]" value="I confirm the vehicle condition" required> I confirm the vehicle condition</div></div><div class="alert alert-warning small mt-3">Any damage not noted here may be considered during your rental.</div><label class="label-text text-start mt-3">Damage / Condition Notes (if any)</label><input name="damage_part" class="form-control form-control-sm mb-2" placeholder="Part (e.g. Front Bumper)"><textarea name="damage_description" class="form-control form-control-sm mb-2" rows="2" placeholder="e.g. small scratch on rear bumper..."></textarea><label class="label-text text-start mt-2">Photo (optional)</label><input type="file" name="photo" accept="image/jpeg,image/png" class="form-control form-control-sm"><button type="submit" class="btn btn-danger w-100 rounded-pill mt-3">DONE - CONFIRM CONDITION</button></form></div></div></div></div>
<div class="modal fade" id="pickupConditionConfirmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow"><div class="modal-body p-4 text-center"><i class="fas fa-question-circle text-danger fa-2x mb-3"></i><h5 class="fw-bold">Confirm Vehicle Condition?</h5><p class="text-muted small mb-4">Are you sure the information you entered is complete and accurate?</p><div class="d-flex gap-2"><button type="button" class="btn btn-light border rounded-pill w-50 fw-bold" data-bs-dismiss="modal">No, Go Back</button><button type="button" id="confirmPickupConditionBtn" class="btn btn-danger rounded-pill w-50 fw-bold">Yes, Confirm</button></div></div></div></div></div>
<div class="modal fade" id="extendModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4"><div class="modal-body p-4"><h5 class="fw-bold mb-3"><i class="fas fa-clock text-danger me-2"></i>Request Extension</h5><p class="small text-muted">Submit your new return date. The admin will record the extension payment in Payments &amp; Billing.</p><form method="POST" action="{{ route('user.active-rental.extension', $reservation) }}">@csrf<label class="label-text">New Return Date</label><input type="date" id="extensionReturnDate" name="requested_return_date" min="{{ $reservation->return_date->copy()->addDay()->toDateString() }}" class="form-control rounded-pill mb-3" required><div class="bg-light border rounded-3 p-3 mb-3"><div class="d-flex justify-content-between small"><span>Extension rate per day</span><strong>₱{{ number_format($vehicleDailyPrice + ($reservation->driver_option === 'with_driver' ? 1500 : 0), 2) }}</strong></div><div class="d-flex justify-content-between mt-2"><span class="fw-bold">Estimated extension total</span><strong class="text-danger" id="extensionPrice">₱0.00</strong></div></div><button class="btn btn-dark w-100 rounded-pill fw-bold">SUBMIT EXTENSION</button></form></div></div></div></div>
<div class="modal fade" id="ratingModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4"><div class="modal-body p-4"><h5 class="fw-bold mb-2"><i class="fas fa-star text-warning me-2"></i>Rate Your Rental</h5><p class="small text-muted">How was your experience with {{ $reservation->vehicle }}?</p><div id="ratingAlert" class="alert d-none small"></div><form id="ratingForm" data-no-page-loader>@csrf<div class="mb-3"><label class="label-text">Rating</label><select name="rating" class="form-select rounded-pill" required><option value="">Choose a rating</option><option value="5">★★★★★ Excellent</option><option value="4">★★★★ Very Good</option><option value="3">★★★ Good</option><option value="2">★★ Fair</option><option value="1">★ Poor</option></select></div><label class="label-text">Comment</label><textarea name="comment" class="form-control mb-3" rows="3" maxlength="2000" placeholder="Tell us about your rental experience." required></textarea><button class="btn btn-warning w-100 rounded-pill fw-bold" type="submit">SUBMIT RATING</button></form></div></div></div></div>
@endif
@if($activeReservations->count() > 1)
<div class="status-card mt-4"><div class="p-3"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="fw-bold mb-0"><i class="fas fa-car text-danger me-2"></i>My Active Rentals</h6><span class="badge bg-danger">{{ $activeReservations->count() }}</span></div><div class="row g-2">@foreach($activeReservations as $activeItem)<div class="col-md-6"><a href="{{ route('user.active-rental', ['reservation_id' => $activeItem->id]) }}" class="d-flex justify-content-between align-items-center border rounded-3 p-2 text-decoration-none {{ $reservation?->id === $activeItem->id ? 'border-danger bg-light' : 'bg-white' }}"><div><strong class="d-block text-dark">{{ $activeItem->vehicle }}</strong><small class="text-muted">{{ $activeItem->control_number }} · {{ $activeItem->pickup_date->format('M d, Y') }}</small></div><span class="badge {{ $activeItem->status === 'released' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($activeItem->status) }}</span></a></div>@endforeach</div></div></div>
@endif
</div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@if($reservation)
<script>
    (function () {
        const extensionDate = document.getElementById('extensionReturnDate');
        const extensionPrice = document.getElementById('extensionPrice');
        const originalReturnDate = new Date('{{ $reservation->return_date->toDateString() }}T00:00:00');
        const dailyRate = {{ $vehicleDailyPrice + ($reservation->driver_option === 'with_driver' ? 1500 : 0) }};
        function updateExtensionPrice() {
            if (!extensionDate.value) {
                extensionPrice.textContent = '₱0.00';
                return;
            }
            const selectedDate = new Date(extensionDate.value + 'T00:00:00');
            const days = Math.max(0, Math.round((selectedDate - originalReturnDate) / 86400000));
            extensionPrice.textContent = '₱' + (days * dailyRate).toLocaleString('en-PH', {minimumFractionDigits: 2});
        }
        extensionDate.addEventListener('change', updateExtensionPrice);
    }());

    (function () {
        const form = document.getElementById('pickupConditionForm');
        const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('pickupConditionConfirmModal'));
        const confirmButton = document.getElementById('confirmPickupConditionBtn');
        if (!form || !confirmButton) return;

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            confirmModal.show();
        });

        confirmButton.addEventListener('click', function () {
            confirmButton.disabled = true;
            confirmButton.textContent = 'Submitting...';
            confirmModal.hide();
            const loader = document.getElementById('userPageLoader');
            loader?.classList.add('is-visible');
            loader?.setAttribute('aria-hidden', 'false');
            form.submit();
        });
    }());

    (function () {
        const releasedAt = @json($reservation->released_at?->toIso8601String());
        const pickupDate = new Date('{{ $reservation->pickup_date->format('Y-m-d') }}T00:00:00');
        const returnDate = new Date('{{ $reservation->return_date->format('Y-m-d') }}T00:00:00');
        const output = document.getElementById('timeRemainingText');
        if (!output) return;
        if (!releasedAt) {
            output.textContent = 'Waiting for release';
            return;
        }
        const rentalDays = Math.max(1, Math.ceil((returnDate - pickupDate) / 86400000));
        const rentalEnd = new Date(new Date(releasedAt).getTime() + rentalDays * 86400000);
        function updateRemaining() {
            const remaining = Math.max(0, rentalEnd.getTime() - Date.now());
            const days = Math.floor(remaining / 86400000);
            const hours = Math.floor((remaining % 86400000) / 3600000);
            const minutes = Math.floor((remaining % 3600000) / 60000);
            output.textContent = String(days).padStart(2, '0') + 'd : ' +
                String(hours).padStart(2, '0') + 'h : ' +
                String(minutes).padStart(2, '0') + 'm';
        }
        updateRemaining();
        window.setInterval(updateRemaining, 60000);
    }());

    document.getElementById('ratingForm')?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = event.currentTarget;
        const alertBox = document.getElementById('ratingAlert');
        const response = await fetch('{{ $activeVehicle ? route('user.vehicles.feedback', $activeVehicle) : '#' }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: new FormData(form)
        });
        if (!response.ok) {
            alertBox.className = 'alert alert-danger small';
            alertBox.textContent = 'Unable to submit your rating. Please try again.';
            return;
        }
        alertBox.className = 'alert alert-success small';
        alertBox.textContent = 'Thank you for rating your rental.';
        form.reset();
        setTimeout(function () {
            bootstrap.Modal.getInstance(document.getElementById('ratingModal'))?.hide();
            alertBox.className = 'alert d-none small';
        }, 1200);
    });
</script>
@endif
</body>
</html>
