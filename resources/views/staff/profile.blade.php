<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Staff Profile</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #dc3545; --boss-dark: #212529; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar { width: 250px; height: 100vh; background: #212529; position: fixed; border-right: 5px solid #dc3545; z-index: 1000; }
        .sidebar .nav-link { color: white; padding: 15px 20px; margin: 5px 15px; border-radius: 8px; font-size: .9rem; font-weight: 600; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #dc3545; color: white; }
        .main-content { margin-left: 250px; min-height: 100vh; }
        .top-nav { background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,.05); display: flex; justify-content: space-between; align-items: center; }
        .content-container { padding: 30px; }
        .profile-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .profile-header { background: #212529; height: 120px; }
        .profile-avatar-container { position: relative; margin: -60px 0 0 30px; display: inline-block; }
        .profile-avatar { width: 120px; height: 120px; border: 5px solid white; background: #b71c1c; object-fit: cover; border-radius: 50%; }
        .edit-avatar-btn { position: absolute; bottom: 5px; right: 5px; background: white; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #212529; cursor: pointer; }
        .form-label-custom { font-size: .75rem; font-weight: 700; color: #6c757d; text-transform: uppercase; }
        .input-custom { background: #f8f9fa !important; border: 1px solid #eee !important; padding: 12px 15px !important; border-radius: 10px !important; }
        .section-title { color: #dc3545; font-weight: 800; font-size: .9rem; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        @media (max-width: 992px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
        @media (max-width: 575.98px) {
            .content-container { padding:12px 9px; }
            .profile-header { height:90px; }
            .profile-avatar-container { margin:-45px 0 0 16px; }
            .profile-avatar { width:90px; height:90px; border-width:4px; }
            .profile-card > .p-4 { padding:1rem !important; }
            .profile-card .d-flex.justify-content-between { align-items:flex-start !important; gap:10px; flex-wrap:wrap; }
            .profile-card .d-flex.justify-content-between h4 { font-size:1rem; overflow-wrap:anywhere; }
            .section-title { font-size:.78rem; letter-spacing:.6px; margin-bottom:14px; }
            .input-custom { padding:9px 11px !important; font-size:16px; }
            .profile-card .text-end .btn { width:100%; }
        }
    </style>
</head>
<body>
    @php
        $profileInitials = collect(preg_split('/\s+/', trim($staffUser->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    @endphp
    @include('staff.partials.navigation', ['staffPageTitle' => 'My', 'staffPageAccent' => 'Profile'])

    <main class="main-content">

        <div class="content-container">
            <div class="row"><div class="col-lg-8 mx-auto">
                @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
                @if($errors->any()) <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
                <div class="profile-card">
                    <div class="profile-header"></div>
                    <div class="profile-avatar-container">
                        @if($staffUser->profile_photo_path)
                            <img id="previewImg" class="profile-avatar" src="{{ asset('storage/'.$staffUser->profile_photo_path) }}" alt="Profile photo">
                        @else
                            <div id="previewInitials" class="profile-avatar d-flex align-items-center justify-content-center text-white fw-bold" style="font-size:3rem;">{{ $profileInitials }}</div>
                            <img id="previewImg" class="profile-avatar d-none" alt="Profile photo">
                        @endif
                        <label for="imgUpload" class="edit-avatar-btn"><i class="fas fa-camera"></i></label>
                    </div>
                    <div class="p-4 pt-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><h4 class="fw-bold mb-1">{{ $staffUser->name }}</h4><span class="badge bg-danger">{{ ucfirst($staffUser->role) }}</span></div>
                            <form method="POST" action="{{ route('staff.logout') }}">@csrf<button class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="fas fa-sign-out-alt me-1"></i> Logout</button></form>
                        </div>
                        <form method="POST" action="{{ route('staff.profile.update') }}" enctype="multipart/form-data" class="mt-4">
                            @csrf @method('PUT')
                            <input id="imgUpload" name="profile_photo" type="file" hidden accept="image/*" onchange="previewFile(event)">
                            <div class="section-title"><i class="fas fa-user me-2"></i>Personal Information</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6"><label class="form-label-custom">Full Name</label><input name="name" class="form-control input-custom" value="{{ old('name', $staffUser->name) }}" required></div>
                                <div class="col-md-6"><label class="form-label-custom">Email Address</label><input name="email" type="email" class="form-control input-custom" value="{{ old('email', $staffUser->email) }}" required></div>
                                <div class="col-md-6"><label class="form-label-custom">Phone Number</label><input name="contact_number" class="form-control input-custom" value="{{ old('contact_number', $staffUser->contact_number) }}" required></div>
                                <div class="col-md-3"><label class="form-label-custom">Age</label><input name="age" type="number" min="18" max="100" class="form-control input-custom" value="{{ old('age', $staffUser->age) }}" required></div>
                                <div class="col-md-3"><label class="form-label-custom">Gender</label><select name="gender" class="form-select input-custom" required><option value="">Select</option>@foreach(['Male','Female','Other'] as $gender)<option value="{{ $gender }}" @selected(old('gender', $staffUser->gender) === $gender)>{{ $gender }}</option>@endforeach</select></div>
                            </div>
                            <div class="section-title"><i class="fas fa-lock me-2"></i>Security Settings</div>
                            <div class="row g-3 mb-4">
                                <div class="col-12"><label class="form-label-custom">Current Password</label><input name="current_password" type="password" class="form-control input-custom" placeholder="Required only when changing password"></div>
                                <div class="col-md-6"><label class="form-label-custom">New Password</label><input name="password" type="password" class="form-control input-custom" minlength="8" placeholder="Leave blank to keep current"></div>
                                <div class="col-md-6"><label class="form-label-custom">Confirm New Password</label><input name="password_confirmation" type="password" class="form-control input-custom" minlength="8"></div>
                            </div>
                            <p class="small text-muted">Password must be at least 8 characters and include uppercase, lowercase, number, and special character.</p>
                            <div class="text-end border-top pt-3"><button class="btn btn-danger px-5 fw-bold rounded-pill">SAVE CHANGES</button></div>
                        </form>
                    </div>
                </div>
            </div></div>
        </div>
    </main>
    <script>
        function previewFile(event) {
            const file = event.target.files[0];
            if (file) {
                const preview = document.getElementById('previewImg');
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
                document.getElementById('previewInitials')?.classList.add('d-none');
            }
        }
    </script>
@include('partials.password-toggle')
</body>
</html>
