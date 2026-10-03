<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BossDrive - Staff Reports</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red:#dc3545; --boss-dark:#212529; --boss-grey:#f8f9fa; }
        body { background:var(--boss-grey); font-family:'Segoe UI',sans-serif; }
        .sidebar { width:250px; height:100vh; background:#212529; position:fixed; border-right:5px solid #dc3545; z-index:1000; }
        .sidebar .nav-link { color:#fff; padding:15px 20px; margin:5px 15px; border-radius:8px; font-size:.9rem; font-weight:600; }
        .sidebar .nav-link:hover { background:rgba(255,255,255,.1); }
        .sidebar .nav-link.active { background:#dc3545; box-shadow:0 4px 10px rgba(220,53,69,.3); }
        .main-content { margin-left:250px; min-height:100vh; }
        .top-nav { background:#fff; padding:15px 30px; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        .table-container { background:#fff; border-radius:15px; padding:25px; box-shadow:0 5px 20px rgba(0,0,0,.05); }
        .report-tab-btn { background:#fff; border:0; border-radius:15px; padding:18px 15px; width:100%; text-align:left; box-shadow:0 3px 12px rgba(0,0,0,.05); border-left:5px solid transparent; transition:.25s; }
        .report-tab-btn:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,.1); }
        .report-tab-btn.active { border-left-color:var(--boss-red); background:#fff5f5; }
        .tab-icon { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:rgba(220,53,69,.1); color:var(--boss-red); }
        .tab-title { font-size:.8rem; font-weight:700; text-transform:uppercase; color:var(--boss-dark); }
        .tab-count { font-size:1.3rem; font-weight:800; color:var(--boss-dark); }
        .report-panel { display:none; }
        .report-panel.active { display:block; animation:fadeIn .25s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .msg-bubble { background:#f0f2f5; padding:10px 15px; border-radius:15px; font-size:.85rem; border-left:4px solid #dc3545; }
        .pickup-photo-thumb { width:42px; height:42px; object-fit:cover; border-radius:8px; cursor:pointer; border:1px solid rgba(0,0,0,.08); }
        .pickup-photo-empty { display:inline-flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:8px; background:#f1f3f5; color:#adb5bd; }
        .bd-toast-container { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:2000; }
        .bd-toast { min-width:260px; padding:14px 18px; border-radius:12px; color:#fff; font-weight:700; opacity:0; transform:scale(.9); transition:.25s; }
        .bd-toast.show { opacity:1; transform:scale(1); }
        .bd-toast.success { background:#198754; } .bd-toast.error { background:#dc3545; } .bd-toast.cancel { background:#6c757d; }
        @media (max-width:575.98px) {
            .report-tab-btn { min-height:74px; padding:10px 9px; gap:8px !important; border-left-width:3px; }
            .tab-icon { width:32px; height:32px; flex:0 0 32px; font-size:.8rem; }
            .tab-title { font-size:.63rem; line-height:1.2; }
            .tab-count { font-size:1rem; }
            .table-container { padding:12px; }
            .table-container > .d-flex { gap:8px; align-items:flex-start !important; }
            .msg-bubble { min-width:180px; padding:8px 10px; font-size:.78rem; }
        }
    </style>
</head>
<body>
@include('staff.partials.navigation', ['staffPageTitle' => 'Management', 'staffPageAccent' => 'Reports & Logs'])
<div class="main-content">
    <div class="container-fluid p-4">
        <div class="row g-3 mb-4 row-cols-2 row-cols-lg-5">
            <div class="col"><button class="report-tab-btn active d-flex align-items-center gap-3" onclick="showReportPanel('inquiries', this)"><div class="tab-icon"><i class="fas fa-envelope-open-text"></i></div><div><span class="tab-title d-block">Customer Inquiries</span><span class="tab-count" id="inquiriesCountBtn">{{ $inquiries->count() }}</span></div></button></div>
            <div class="col"><button class="report-tab-btn d-flex align-items-center gap-3" onclick="showReportPanel('pickup', this)"><div class="tab-icon"><i class="fas fa-clipboard-check"></i></div><div><span class="tab-title d-block">Pickup Condition Reports</span><span class="tab-count" id="pickupCountBtn">{{ $pickupReports->count() }}</span></div></button></div>
        </div>

        <div class="report-panel active" id="panel-inquiries">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-4"><h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-envelope-open-text me-2 text-danger"></i> Customer Inquiries Inbox</h6><span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2">{{ $inquiries->count() }} Records</span></div>
                <div class="table-responsive">
                    <table class="table align-middle" id="inquiriesTable">
                        <thead class="bg-light small text-muted text-uppercase"><tr><th>Sender Details</th><th>Message Preview</th><th class="text-center">Status</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        @forelse($inquiries as $inquiry)
                            <tr>
                                <td><div class="fw-bold">{{ $inquiry->name }}</div><small class="text-muted">{{ $inquiry->email ?: 'No email' }} · {{ $inquiry->created_at->format('M d, Y | g:i A') }}</small></td>
                                <td><div class="msg-bubble text-dark"><strong>{{ $inquiry->subject }}</strong><br>{{ \Illuminate\Support\Str::limit($inquiry->message, 180) }}</div></td>
                                <td class="text-center"><span class="badge rounded-pill {{ $inquiry->status === 'new' ? 'bg-danger' : 'bg-secondary bg-opacity-25 text-dark' }} px-3">{{ ucfirst($inquiry->status) }}</span></td>
                                <td class="text-end"><button class="btn btn-outline-danger btn-sm border-0" onclick="deleteReport(this, 'Inquiry', '{{ route('staff.reports.inquiries.destroy', $inquiry) }}')"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No customer inquiries found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="report-panel" id="panel-pickup">
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-4"><div><h6 class="fw-bold mb-0 text-uppercase text-muted small"><i class="fas fa-clipboard-check me-2 text-danger"></i> Vehicle Pickup Condition Reports</h6><small class="text-muted">Submitted by users before viewing their Active Rental</small></div><span class="badge bg-secondary bg-opacity-10 text-secondary fw-bold px-3 py-2">{{ $pickupReports->count() }} Records</span></div>
                <div class="table-responsive">
                    <table class="table align-middle" id="pickupTable">
                        <thead class="bg-light small text-muted text-uppercase"><tr><th>Customer</th><th>Vehicle</th><th>Date Submitted</th><th class="text-center">Checklist</th><th>Damage / Condition Notes</th><th class="text-center">Photo</th><th class="text-end">Action</th></tr></thead>
                        <tbody>
                        @forelse($pickupReports as $report)
                            @php
                                $checks = is_array($report->checks) ? $report->checks : [];
                                $checked = collect($checks)->filter(fn ($value) => (bool) $value)->count();
                                $vehicle = $report->reservation?->vehicle ?: 'Unknown vehicle';
                                $photoUrl = $report->photo_path ? asset('storage/'.ltrim($report->photo_path, '/')) : null;
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $report->user?->name ?: 'Unknown customer' }}</td>
                                <td class="text-muted small">{{ $vehicle }}</td>
                                <td class="text-muted small">{{ $report->created_at->format('M d, Y | g:i A') }}</td>
                                <td class="text-center"><span class="badge rounded-pill {{ $checked === count($checks) && count($checks) > 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' }} fw-bold">{{ $checked }}/{{ count($checks) }} Checked</span></td>
                                <td class="small">{!! $report->notes ? nl2br(e($report->notes)) : '<span class="text-muted fst-italic">No damage notes submitted</span>' !!}</td>
                                <td class="text-center">
                                    @if($photoUrl)<img src="{{ $photoUrl }}" class="pickup-photo-thumb" alt="Pickup condition photo" onclick="viewPickupPhoto(@js($photoUrl))">@else<span class="pickup-photo-empty" title="No photo uploaded"><i class="fas fa-image"></i></span>@endif
                                </td>
                                <td class="text-end"><button class="btn btn-outline-danger btn-sm border-0" onclick="deleteReport(this, 'Pickup Condition Report', '{{ route('staff.reports.pickup.destroy', $report) }}')"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No pickup condition reports found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="bd-toast-container" id="bdToastContainer"></div>
<div class="modal fade" id="pickupPhotoModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 border-0 shadow"><div class="modal-body p-2 text-center"><img id="pickupPhotoModalImg" src="" class="img-fluid rounded-3" style="max-height:70vh;"></div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showReportPanel(key, button) {
    document.querySelectorAll('.report-panel').forEach(function(panel) { panel.classList.remove('active'); });
    document.querySelectorAll('.report-tab-btn').forEach(function(tab) { tab.classList.remove('active'); });
    document.getElementById('panel-' + key).classList.add('active');
    button.classList.add('active');
}
function showToast(message, type) {
    var toast = document.createElement('div');
    toast.className = 'bd-toast ' + (type || 'success');
    toast.innerText = message;
    document.getElementById('bdToastContainer').appendChild(toast);
    requestAnimationFrame(function() { toast.classList.add('show'); });
    setTimeout(function() { toast.classList.remove('show'); setTimeout(function() { toast.remove(); }, 250); }, 2500);
}
async function deleteReport(button, label, url) {
    if (!window.confirm('Delete this ' + label + ' permanently?')) return;
    var response = await fetch(url, { method:'DELETE', headers:{'Accept':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content} });
    if (!response.ok) { showToast('Unable to delete ' + label + '.', 'error'); return; }
    button.closest('tr').remove();
    showToast(label + ' deleted successfully.', 'success');
}
function viewPickupPhoto(src) {
    document.getElementById('pickupPhotoModalImg').src = src;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('pickupPhotoModal')).show();
}
</script>
</body>
</html>
