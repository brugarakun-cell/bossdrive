<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Detailed Reports</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #dc3545; --boss-dark: #212529; --boss-grey: #f8f9fa; }
        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }

        /* Consistent Sidebar */
        .sidebar { width: 250px; height: 100vh; background-color: #212529; position: fixed; border-right: 5px solid #dc3545; z-index: 1000; }
        .sidebar .nav-link { color: white; padding: 15px 20px; margin: 5px 15px; border-radius: 8px; transition: 0.3s; font-size: 0.9rem; text-decoration: none; display: block; font-weight: 600; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link.active { background-color: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); color: white; }

        .main-content { margin-left: 250px; min-height: 100vh; }
        .top-nav { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

        /* Report Cards */
        .report-card { border: none; border-radius: 15px; transition: 0.3s; }

        /* Table Styling */
        .table-container { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); max-height: 65vh; overflow-y: auto; }
        .msg-bubble { background: #f0f2f5; padding: 10px 15px; border-radius: 15px; font-size: 0.85rem; border-left: 4px solid #dc3545; }

        .badge-extension { font-size: 0.7rem; padding: 5px 10px; border-radius: 5px; }

        /* Report Section Tab Buttons */
        .report-tab-btn {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 18px 15px;
            width: 100%;
            text-align: left;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
            transition: 0.25s;
            border-left: 5px solid transparent;
            position: relative;
        }
        .report-tab-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .report-tab-btn.active {
            border-left: 5px solid var(--boss-red);
            background: #fff5f5;
        }
        .report-tab-btn .tab-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(220,53,69,0.1);
            color: var(--boss-red);
            font-size: 1rem;
            flex-shrink: 0;
        }
        .report-tab-btn .tab-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--boss-dark);
        }
        .report-tab-btn .tab-count {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--boss-dark);
        }

        .report-panel { display: none; }
        .report-panel.active { display: block; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px);} to { opacity: 1; transform: translateY(0);} }

        /* Toast and Confirm Modal Custom Styles */
        .bd-toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .bd-toast { min-width: 260px; max-width: 340px; padding: 14px 18px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.18); font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; color: #fff; opacity: 0; transform: scale(0.9); transition: 0.25s ease; }
        .bd-toast.show { opacity: 1; transform: scale(1); }
        .bd-toast.success { background: #198754; }
        .bd-toast.cancel { background: #6c757d; }
        .bd-toast.error { background: #dc3545; }
        #bdConfirmModal { z-index: 1090 !important; }

        /* Pickup photo cell states */
        .pickup-photo-thumb {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid rgba(0,0,0,0.08);
        }
        .pickup-photo-empty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: #f1f3f5;
            color: #adb5bd;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

    @include('admin.partials.navigation', ['adminPageTitle' => 'Management', 'adminPageAccent' => 'Reports & Logs'])
    <div class="main-content">

        <div class="container-fluid p-4">

            <!-- Report Category Buttons -->
            <div class="row g-3 mb-4 row-cols-2 row-cols-lg-5">
                <div class="col">
                    <button class="report-tab-btn active d-flex align-items-center gap-3" onclick="showReportPanel('cancelled', this)">
                        <div class="tab-icon"><i class="fas fa-ban"></i></div>
                        <div>
                            <span class="tab-title d-block">Cancelled Bookings</span>
                            <span class="tab-count" id="cancelledCountBtn">{{ $cancelledCount }}</span>
                        </div>
                    </button>
                </div>
                <div class="col">
                    <button class="report-tab-btn d-flex align-items-center gap-3" onclick="showReportPanel('voided', this)">
                        <div class="tab-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <span class="tab-title d-block">Voided Bookings</span>
                            <span class="tab-count" id="voidedCountBtn">{{ $voidedCount ?? 0 }}</span>
                        </div>
                    </button>
                </div>
                <div class="col">
                    <button class="report-tab-btn d-flex align-items-center gap-3" onclick="showReportPanel('deleted', this)">
                        <div class="tab-icon"><i class="fas fa-car-side"></i></div>
                        <div>
                            <span class="tab-title d-block">Audit Logs</span>
                            <span class="tab-count" id="deletedCountBtn">{{ $auditCount }}</span>
                        </div>
                    </button>
                </div>
                <div class="col">
                    <button class="report-tab-btn d-flex align-items-center gap-3" onclick="showReportPanel('inquiries', this)">
                        <div class="tab-icon"><i class="fas fa-comment-dots"></i></div>
                        <div>
                            <span class="tab-title d-block">Unresolved Inquiries</span>
                            <span class="tab-count" id="inquiriesCountBtn">{{ $inquiriesCount ?? 0 }}</span>
                        </div>
                    </button>
                </div>
                <div class="col">
                    <button class="report-tab-btn d-flex align-items-center gap-3" onclick="showReportPanel('pickup', this)">
                        <div class="tab-icon"><i class="fas fa-clipboard-check"></i></div>
                        <div>
                            <span class="tab-title d-block">Pickup Condition Reports</span>
                            <span class="tab-count" id="pickupCountBtn">{{ $pickupCount }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Panel: Cancelled Bookings -->
            <div class="report-panel active" id="panel-cancelled">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-ban me-2 text-danger"></i> Cancelled Bookings</h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2">{{ $cancelledCount }} Records</span>
                            <button class="btn btn-danger btn-sm rounded-pill fw-bold" onclick="deleteAllReports('cancelled', 'Cancelled Bookings', '{{ route('admin.reports.delete-all', 'cancelled') }}', 'cancelledTable')"><i class="fas fa-trash me-1"></i>Delete All</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="cancelledTable">
                            <thead class="bg-light small text-muted text-uppercase">
                                <tr>
                                    <th class="border-0">Customer</th>
                                    <th class="border-0">Vehicle</th>
                                    <th class="border-0">Date Cancelled</th>
                                    <th class="border-0 text-center">Reason</th>
                                    <th class="border-0 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reservations->where('status', 'cancelled') as $reservation)
                                <tr>
                                    <td class="fw-bold">{{ $reservation->customer_name ?: ($reservation->user?->name ?? 'Walk-in Customer') }}</td>
                                    <td class="text-muted small">{{ $reservation->vehicle }}</td>
                                    <td class="text-muted small">{{ $reservation->updated_at->format('M d, Y') }}</td>
                                    <td class="text-center"><span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary fw-bold">{{ ucfirst($reservation->payment_status ?? 'Customer Request') }}</span></td>
                                    <td class="text-end"><button class="btn btn-outline-danger btn-sm border-0" onclick="deleteRow(this, 'Cancelled Booking', '{{ route('admin.reservations.destroy', $reservation) }}')"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No cancelled bookings in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Panel: Voided Bookings -->
            <div class="report-panel" id="panel-voided">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-clock me-2 text-danger"></i> Voided Bookings</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Walk-in reservations auto-voided after 3 hours with no payment confirmation</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2">{{ $voidedCount ?? 0 }} Records</span>
                            <button class="btn btn-danger btn-sm rounded-pill fw-bold" onclick="deleteAllReports('voided', 'Voided Bookings', '{{ route('admin.reports.delete-all', 'voided') }}', 'voidedTable')"><i class="fas fa-trash me-1"></i>Delete All</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="voidedTable">
                            <thead class="bg-light small text-muted text-uppercase">
                                <tr>
                                    <th class="border-0">Customer</th>
                                    <th class="border-0">Vehicle</th>
                                    <th class="border-0">Walk-in Time</th>
                                    <th class="border-0 text-center">Voided After</th>
                                    <th class="border-0 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($voidedReservations ?? collect() as $reservation)
                                @php
                                    $walkInTime = $reservation->created_at;
                                    $voidedAfter = $walkInTime->diffForHumans($reservation->updated_at, true);
                                @endphp
                                <tr>
                                    <td class="fw-bold">{{ $reservation->customer_name ?: ($reservation->user?->name ?? 'Walk-in Customer') }}</td>
                                    <td class="text-muted small">{{ $reservation->vehicle }}</td>
                                    <td class="text-muted small">{{ $walkInTime->format('M d, Y | g:i A') }}</td>
                                    <td class="text-center"><span class="badge rounded-pill bg-danger bg-opacity-10 text-danger fw-bold">{{ $voidedAfter }}</span></td>
                                    <td class="text-end"><button class="btn btn-outline-danger btn-sm border-0" onclick="deleteRow(this, 'Voided Booking', '{{ route('admin.reservations.destroy', $reservation) }}')"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No voided bookings in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Panel: Recently Deleted Vehicles -->
            <div class="report-panel" id="panel-deleted">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-list me-2 text-danger"></i> Audit Logs</h6>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2" id="deletedCount">{{ $auditCount }} Records</span>
                            <button class="btn btn-danger btn-sm rounded-pill fw-bold" onclick="deleteAllReports('audit', 'Audit Logs', '{{ route('admin.reports.delete-all', 'audit') }}', 'deletedVehiclesTable')"><i class="fas fa-trash me-1"></i>Delete All</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="deletedVehiclesTable">
                            <thead class="bg-light small text-muted text-uppercase">
                                <tr>
                                    <th class="border-0">Action</th>
                                    <th class="border-0">Entity</th>
                                    <th class="border-0">Date</th>
                                    <th class="border-0">Details</th>
                                    <th class="border-0 text-end">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($auditLogs as $log)
                                @php
                                    $auditMetadata = $log->metadata ?? [];
                                    $auditDetailText = collect($auditMetadata)
                                        ->map(fn ($value, $key) => $key.': '.(is_array($value) ? json_encode($value) : (string) $value))
                                        ->implode(' | ');

                                    if (str_contains($log->action, 'deleted')) {
                                        $entityName = class_basename($log->entity_type);
                                        $auditDetailText = filled($auditDetailText)
                                            ? 'Deleted '.$entityName.' — '.$auditDetailText
                                            : 'Deleted '.$entityName;
                                    } elseif (filled($auditDetailText)) {
                                        $auditDetailText = 'Updated: '.$auditDetailText;
                                    } else {
                                        $auditDetailText = 'No additional details';
                                    }
                                @endphp
                                <tr>
                                    <td class="fw-bold">{{ $log->action }}</td>
                                    <td class="text-muted small">{{ class_basename($log->entity_type) }} #{{ $log->entity_id ?? '—' }}</td>
                                    <td class="text-muted small">{{ $log->created_at->format('M d, Y | g:i A') }}</td>
                                    <td class="small text-dark"><span class="badge bg-light text-dark text-wrap" style="white-space: normal; max-width: 340px;">{{ e($auditDetailText) }}</span></td>
                                    <td class="text-end"><button class="btn btn-outline-danger btn-sm border-0" onclick="deleteRow(this, 'Audit Log', '{{ url('/admin/audit-logs') }}/{{ $log->id }}')" title="Delete audit log"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No audit logs in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <p class="text-muted small mb-0 fst-italic" id="deletedEmptyMsg" style="display:none;">No audit logs.</p>
                    </div>
                </div>
            </div>

            <!-- Panel: Unresolved Inquiries -->
            <div class="report-panel" id="panel-inquiries">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-envelope-open-text me-2 text-danger"></i> Customer Inquiries Inbox</h6>
                        <div class="d-flex align-items-center gap-2">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">Filter: Newest</button>
                                <ul class="dropdown-menu shadow border-0">
                                    <li><a class="dropdown-item small fw-bold" href="#">Newest First</a></li>
                                    <li><a class="dropdown-item small fw-bold" href="#">Unread Only</a></li>
                                </ul>
                            </div>
                            <button class="btn btn-danger btn-sm rounded-pill fw-bold" onclick="deleteAllReports('inquiries', 'Customer Inquiries', '{{ route('admin.reports.delete-all', 'inquiries') }}', 'inquiriesTable')"><i class="fas fa-trash me-1"></i>Delete All</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle" id="inquiriesTable">
                            <thead class="bg-light small text-muted text-uppercase">
                                <tr>
                                    <th class="border-0">Sender Details</th>
                                    <th class="border-0">Message Preview</th>
                                    <th class="border-0 text-center">Status</th>
                                    <th class="border-0 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inquiries as $inquiry)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $inquiry->name }}</div>
                                            <small class="text-muted">{{ $inquiry->email }}</small>
                                        </td>
                                        <td>
                                            <div class="small text-dark">
                                                <strong>{{ $inquiry->subject }}</strong><br>
                                                <span class="text-muted">{{ Str::limit($inquiry->message, 120) }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning fw-bold">{{ ucfirst($inquiry->status) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-outline-danger btn-sm border-0" onclick="deleteRow(this, 'Inquiry', '{{ url('/admin/inquiries') }}/{{ $inquiry->id }}')"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No unresolved inquiries in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Panel: Vehicle Pickup Condition Reports -->
            <div class="report-panel" id="panel-pickup">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-clipboard-check me-2 text-danger"></i> Vehicle Pickup Condition Reports</h6>
                            <small class="text-muted" style="font-size: 0.75rem;">Submitted by users before viewing their Active Rental</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2" id="pickupCount">{{ $pickupCount }} Records</span>
                            <button class="btn btn-danger btn-sm rounded-pill fw-bold" onclick="deleteAllReports('pickup', 'Pickup Condition Reports', '{{ route('admin.reports.delete-all', 'pickup') }}', 'pickupTable')"><i class="fas fa-trash me-1"></i>Delete All</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle" id="pickupTable">
                            <thead class="bg-light small text-muted text-uppercase">
                                <tr>
                                    <th class="border-0">Customer</th>
                                    <th class="border-0">Vehicle</th>
                                    <th class="border-0">Date Submitted</th>
                                    <th class="border-0 text-center">Checklist</th>
                                    <th class="border-0">Damage / Condition Notes</th>
                                    <th class="border-0 text-center">Photo</th>
                                    <th class="border-0 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pickupReports as $report)
                                @php $checked = collect($report->checks ?? [])->filter()->count(); $totalChecks = count($report->checks ?? []); @endphp
                                <tr data-photo-url="{{ $report->photo_path ? asset('storage/'.$report->photo_path) : '' }}">
                                    <td class="fw-bold">{{ $report->reservation?->customer_name ?: ($report->user?->name ?? 'Walk-in Customer') }}</td>
                                    <td class="text-muted small">{{ $report->reservation?->vehicle ?? 'Unknown vehicle' }}</td>
                                    <td class="text-muted small">{{ $report->created_at->format('M d, Y | g:i A') }}</td>
                                    <td class="text-center"><span class="badge rounded-pill bg-{{ $checked === $totalChecks ? 'success' : 'warning' }} bg-opacity-10 text-{{ $checked === $totalChecks ? 'success' : 'warning' }} fw-bold">{{ $checked }}/{{ $totalChecks }} Checked</span></td>
                                    <td class="small">{!! $report->notes ? '<div class="msg-bubble text-dark">'.e($report->notes).'</div>' : '<span class="text-muted fst-italic">No damage notes submitted</span>' !!}</td>
                                    <td class="text-center">
                                        @if ($report->photo_path)
                                        <img src="{{ asset('storage/'.$report->photo_path) }}" class="pickup-photo-thumb" alt="Pickup condition photo" onclick="viewPickupPhoto(this.src)">
                                        @else
                                        <span class="pickup-photo-empty" title="No photo uploaded"><i class="fas fa-image"></i></span>
                                        @endif
                                    </td>
                                    <td class="text-end"><button class="btn btn-outline-danger btn-sm border-0" onclick="deleteRow(this, 'Pickup Condition Report', '{{ route('admin.reports.pickup.destroy', $report) }}')"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No pickup condition reports in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        <p class="text-muted small mb-0 fst-italic" id="pickupEmptyMsg" style="display:none;">No pickup condition reports yet.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- CUSTOM CONFIRM MODAL WITH 3S COUNTDOWN -->
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
                        <button type="button" class="btn btn-danger fw-bold rounded-pill px-4" id="bdConfirmOkBtn" disabled>Confirm (3s)</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PICKUP CONDITION PHOTO VIEWER -->
    <div class="modal fade" id="pickupPhotoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-body p-2 text-center">
                    <img id="pickupPhotoModalImg" src="" class="img-fluid rounded-3" style="max-height: 70vh;">
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="bd-toast-container" id="bdToastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showReportPanel(key, btn) {
            document.querySelectorAll('.report-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.report-tab-btn').forEach(b => b.classList.remove('active'));

            document.getElementById('panel-' + key).classList.add('active');
            btn.classList.add('active');
        }

        // ================= SUCCESS / CANCEL TOAST NOTIFICATIONS =================
        function showToast(message, type, duration) {
            type = type || 'success';
            duration = duration || 2800;
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
            }, duration);
        }

        // ================= CUSTOM CONFIRM MODAL WITH 3-SECOND COUNTDOWN =================
        let bdConfirmModalInstance = null;
        let bdConfirmResolve = null;
        let countdownTimer = null;

        function showConfirm(message, countdownSeconds) {
            return new Promise(function(resolve) {
                document.getElementById('bdConfirmMessage').innerText = message;
                bdConfirmResolve = resolve;

                const okBtn = document.getElementById('bdConfirmOkBtn');
                okBtn.disabled = true;
                let timeLeft = countdownSeconds || 3;
                okBtn.innerText = `Confirm (${timeLeft}s)`;

                if (!bdConfirmModalInstance) {
                    bdConfirmModalInstance = new bootstrap.Modal(document.getElementById('bdConfirmModal'));
                }
                bdConfirmModalInstance.show();

                // Start 3-second countdown
                clearInterval(countdownTimer);
                countdownTimer = setInterval(function() {
                    timeLeft--;
                    if (timeLeft > 0) {
                        okBtn.innerText = `Confirm (${timeLeft}s)`;
                    } else {
                        clearInterval(countdownTimer);
                        okBtn.disabled = false;
                        okBtn.innerText = 'Confirm';
                    }
                }, 1000);

                setTimeout(function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    const ownBackdrop = backdrops[backdrops.length - 1];
                    if (ownBackdrop) ownBackdrop.style.zIndex = 1085;
                }, 0);
            });
        }

        document.getElementById('bdConfirmOkBtn').addEventListener('click', function() {
            if (this.disabled) return;
            clearInterval(countdownTimer);
            bdConfirmModalInstance.hide();
            if (bdConfirmResolve) { bdConfirmResolve(true); bdConfirmResolve = null; }
        });

        document.getElementById('bdConfirmCancelBtn').addEventListener('click', function() {
            clearInterval(countdownTimer);
            bdConfirmModalInstance.hide();
            if (bdConfirmResolve) { bdConfirmResolve(false); bdConfirmResolve = null; }
        });

        async function deleteAllReports(type, label, url, tableId) {
            if (!(await showConfirm('Delete all ' + label + '? This cannot be undone.', 5))) {
                showToast('Delete all cancelled.', 'cancel', 5000);
                return;
            }

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Delete all request failed.');
                }

                const table = document.getElementById(tableId);
                if (table) {
                    const tbody = table.querySelector('tbody');
                    tbody.innerHTML = '<tr><td colspan="' + table.querySelectorAll('thead th').length + '" class="text-center text-muted py-4">No records found.</td></tr>';
                }

                const counterIds = {
                    cancelledTable: 'cancelledCountBtn',
                    voidedTable: 'voidedCountBtn',
                    deletedVehiclesTable: 'deletedCountBtn',
                    inquiriesTable: 'inquiriesCountBtn',
                    pickupTable: 'pickupCountBtn'
                };
                const counter = document.getElementById(counterIds[tableId]);
                if (counter) counter.innerText = '0';
                const recordsBadge = table ? table.closest('.table-container').querySelector('.badge.bg-secondary') : null;
                if (recordsBadge) recordsBadge.innerText = '0 Records';

                showToast('All ' + label + ' deleted successfully.', 'success', 5000);
            } catch (error) {
                showToast('Unable to delete all ' + label + '.', 'error', 5000);
            }
        }

        // Delete Row with 3s Countdown Confirmation Modal & Toast
        async function deleteRow(btn, recordName, url) {
            if (!(await showConfirm('Are you sure you want to delete this ' + recordName + '?'))) {
                showToast('Cancelled.', 'cancel');
                return;
            }

            const row = btn.closest('tr');
            const table = row.closest('table');

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
                });

                if (!response.ok) {
                    throw new Error('Delete request failed');
                }
            } catch (error) {
                showToast('Unable to delete ' + recordName + '.', 'error');
                return;
            }

            row.remove();
            updateBadgeCount(table);
            showToast(recordName + ' deleted successfully!', 'success');
        }

        function updateBadgeCount(table) {
            const container = table.closest('.table-container');
            const badge = container.querySelector('.badge.bg-secondary.bg-opacity-10');
            const count = table.querySelectorAll('tbody tr').length;
            if (badge) {
                badge.textContent = count + (count === 1 ? ' Record' : ' Records');
            }

            // sync the matching top button counter
            const idMap = {
                cancelledTable: 'cancelledCountBtn',
                voidedTable: 'voidedCountBtn',
                deletedVehiclesTable: 'deletedCountBtn',
                inquiriesTable: 'inquiriesCountBtn',
                pickupTable: 'pickupCountBtn'
            };
            const btnCountId = idMap[table.id];
            if (btnCountId) {
                document.getElementById(btnCountId).textContent = count;
            }

            // Pickup Condition Reports has its own empty-state message, same pattern as Deleted Vehicles
            if (table.id === 'pickupTable') {
                const emptyMsg = document.getElementById('pickupEmptyMsg');
                if (count === 0) {
                    table.style.display = 'none';
                    emptyMsg.style.display = 'block';
                }
            }
        }

        // Open the pickup condition photo in a larger preview
        function viewPickupPhoto(src) {
            if (!src) return;
            document.getElementById('pickupPhotoModalImg').src = src;
            new bootstrap.Modal(document.getElementById('pickupPhotoModal')).show();
        }

        async function restoreVehicle(btn) {
            const row = btn.closest('tr');
            const vehicleName = row.querySelector('td').textContent.trim();

            if (!(await showConfirm('Restore ' + vehicleName + ' back to Vehicle Management?'))) {
                showToast('Cancelled.', 'cancel');
                return;
            }

            row.remove();

            const table = document.getElementById('deletedVehiclesTable');
            const emptyMsg = document.getElementById('deletedEmptyMsg');
            const countBadge = document.getElementById('deletedCount');
            const remaining = table.querySelectorAll('tbody tr').length;

            countBadge.textContent = remaining + (remaining === 1 ? ' Record' : ' Records');
            document.getElementById('deletedCountBtn').textContent = remaining;
            if (remaining === 0) {
                table.style.display = 'none';
                emptyMsg.style.display = 'block';
            }

            showToast(vehicleName + ' has been restored successfully!', 'success');
        }
    </script>
</body>
</html>