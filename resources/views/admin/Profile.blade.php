<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Admin Profile</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --boss-red: #dc3545; 
            --boss-dark: #212529; 
            --boss-grey: #f8f9fa; 
        }

        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar Styles */
        .sidebar { 
            width: 250px; height: 100vh; background-color: #212529; position: fixed; 
            border-right: 5px solid #dc3545; z-index: 1000; 
        }
        .sidebar .nav-link { 
            color: white; padding: 15px 20px; margin: 5px 15px; border-radius: 8px;
            font-size: 0.9rem; transition: 0.3s; text-decoration: none; display: block; font-weight: 600;
        }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link.active { background-color: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); color: white; }

        .main-content { margin-left: 250px; }
        .top-nav { 
            background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }

        .content-container { padding: 30px; }
        
        /* Profile Specific Styles */
        .profile-card {
            background: white; border-radius: 15px; border: none; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .profile-header {
            background: var(--boss-dark);
            height: 120px;
            position: relative;
        }
        .profile-avatar-container {
            position: relative;
            margin-top: -60px;
            margin-left: 30px;
            display: inline-block;
        }
        .profile-avatar {
            width: 120px; height: 120px;
            border: 5px solid white;
            background-color: var(--boss-red);
            color: white;
            font-size: 3rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            object-fit: cover;
        }
        .edit-avatar-btn {
            position: absolute; bottom: 5px; right: 5px;
            background: white; border-radius: 50%; width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            cursor: pointer; color: var(--boss-dark);
        }

        .form-label-custom { font-size: 0.75rem; font-weight: 700; color: #6c757d; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .input-custom { background-color: #f8f9fa !important; border: 1px solid #eee !important; padding: 12px 15px !important; border-radius: 10px !important; font-size: 0.9rem; }
        .input-custom:focus { border-color: var(--boss-red) !important; box-shadow: none !important; background-color: white !important; }
        
        .section-title {
            color: var(--boss-red); font-weight: 800; font-size: 0.9rem;
            text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;
        }

        /* Custom Confirmation Modal Styling */
        .custom-confirm-modal .modal-content {
            border-radius: 20px;
            border: none;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .question-icon-box {
            width: 70px;
            height: 70px;
            background-color: #fff5f5;
            color: #dc3545;
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 20px auto;
        }

        /* Toast Container */
        .bd-toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .bd-toast { min-width: 260px; max-width: 340px; padding: 14px 18px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.18); font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; color: #fff; opacity: 0; transform: scale(0.9); transition: 0.25s ease; }
        .bd-toast.show { opacity: 1; transform: scale(1); }
        .bd-toast.success { background: #198754; }
        .bd-toast.cancel { background: #6c757d; }
        .bd-toast.error { background: #dc3545; }
        #bdConfirmModal { z-index: 1090 !important; }
    </style>
</head>
<body>

    @include('admin.partials.navigation', ['adminPageTitle' => 'My', 'adminPageAccent' => 'Profile', 'adminAccount' => $adminUser])
    <div class="main-content">

        <div class="content-container">
            @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
            @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="profile-card">
                        <div class="profile-header"></div>
                        <div class="profile-avatar-container">
                            <img src="{{ $adminUser?->profile_photo_path ? asset('storage/'.$adminUser->profile_photo_path) : asset('image/1.png') }}" alt="Admin profile" class="profile-avatar" id="previewImg">
                            <label for="imgUpload" class="edit-avatar-btn"><i class="fas fa-camera"></i></label>
                            <input type="file" name="profile_photo" id="imgUpload" hidden accept="image/*" form="adminProfileForm" onchange="previewFile()">
                        </div>
                        
                        <div class="p-4 pt-2">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h4 class="fw-bold mb-0">{{ $adminUser?->name ?? session('admin_name', 'Admin Patrick') }}</h4>
                                    <span class="badge bg-danger">SUPER ADMIN</span>
                                </div>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill fw-bold px-3" onclick="confirmLogout()">
                                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                                </button>
                            </div>

                            <form id="adminProfileForm" method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                                @csrf @method('PUT')
                                <div class="section-title"><i class="fas fa-user me-2"></i> Personal Information</div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Full Name</label>
                                        <input type="text" name="name" class="form-control input-custom" value="{{ old('name', $adminUser?->name ?? session('admin_name', 'Admin Patrick')) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Email Address</label>
                                        <input type="email" name="email" class="form-control input-custom" value="{{ old('email', $adminUser?->email ?? '') }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Phone Number</label>
                                        <input type="text" name="contact_number" class="form-control input-custom" value="{{ old('contact_number', $adminUser?->contact_number ?? '') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-custom">Age</label>
                                        <input type="number" name="age" min="18" max="120" class="form-control input-custom" value="{{ old('age', $adminUser?->age) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label-custom">Gender</label>
                                        <select name="gender" class="form-select input-custom">
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ old('gender', $adminUser?->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ old('gender', $adminUser?->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ old('gender', $adminUser?->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="section-title"><i class="fas fa-lock me-2"></i> Security Settings</div>
                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label class="form-label-custom">Current Password</label>
                                        <input type="password" class="form-control input-custom" placeholder="Enter current password to make changes">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">New Password</label>
                                        <input type="password" class="form-control input-custom" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-custom">Confirm New Password</label>
                                        <input type="password" class="form-control input-custom" placeholder="Re-type new password">
                                    </div>
                                </div>

                                <div class="text-end pt-3 border-top">
                                    <button type="reset" class="btn btn-light px-4 fw-bold me-2 rounded-pill" onclick="showToast('Changes cleared.', 'cancel')">CANCEL</button>
                                    <button type="submit" class="btn btn-danger px-5 fw-bold rounded-pill shadow-sm">
                                        SAVE CHANGES
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CUSTOM CONFIRM MODAL WITH 3S COUNTDOWN -->
    <div class="modal fade custom-confirm-modal" id="bdConfirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content text-center">
                <div class="question-icon-box">
                    <i class="fas fa-question"></i>
                </div>
                <h5 class="fw-bold mb-4 text-dark px-3" id="bdConfirmMessage" style="font-size: 1.15rem; line-height: 1.5;">
                    Are you sure?
                </h5>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-bold border" id="bdConfirmCancelBtn" style="width: 130px;">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 py-2 fw-bold" id="bdConfirmOkBtn" style="background-color: #dc3545; width: 130px;" disabled>Confirm (3s)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="bd-toast-container" id="bdToastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <form id="adminLogoutForm" method="POST" action="{{ route('admin.logout') }}" class="d-none">
        @csrf
    </form>
    <script>
        // PROFILE PICTURE PREVIEW
        function previewFile() {
            const preview = document.getElementById('previewImg');
            const file = document.getElementById('imgUpload').files[0];
            const reader = new FileReader();
            reader.onloadend = function () {
                preview.src = reader.result;
                showToast('Profile picture updated successfully!', 'success');
            }
            if (file) {
                reader.readAsDataURL(file);
            }
        }

        // TOAST NOTIFICATIONS
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
            }, 2800);
        }

        // CUSTOM CONFIRM MODAL WITH 3-SECOND COUNTDOWN LOGIC
        let bdConfirmModalInstance = null;
        let bdConfirmResolve = null;
        let countdownTimer = null;

        function showConfirm(message) {
            return new Promise(function(resolve) {
                document.getElementById('bdConfirmMessage').innerText = message;
                bdConfirmResolve = resolve;

                const okBtn = document.getElementById('bdConfirmOkBtn');
                okBtn.disabled = true;
                let timeLeft = 3;
                okBtn.innerText = `Confirm (${timeLeft}s)`;

                if (!bdConfirmModalInstance) {
                    bdConfirmModalInstance = new bootstrap.Modal(document.getElementById('bdConfirmModal'));
                }
                bdConfirmModalInstance.show();

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

        // FORM SUBMISSION & LOGOUT HANDLERS
        async function handleSaveProfile(event) {
            event.preventDefault();
            if (!(await showConfirm('Are you sure you want to save these profile changes?'))) {
                showToast('Changes cancelled.', 'cancel');
                return;
            }
            showToast('Profile updated successfully!', 'success');
        }

        async function confirmLogout() {
            if (!(await showConfirm('Are you sure you want to log out of your account?'))) {
                showToast('Logout cancelled.', 'cancel');
                return;
            }
            showToast('Logging out...', 'success');
            setTimeout(function() {
                document.getElementById('adminLogoutForm').submit();
            }, 500);
        }
    </script>
@include('partials.password-toggle')
</body>
</html>