<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>BossDrive - Reset Password</title>@include('partials.favicon')<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>:root{--boss-red:#b71c1c}body{background:#f4f4f4;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',sans-serif}.reset-card{background:#fff;width:100%;max-width:400px;padding:40px;border-radius:25px;box-shadow:0 15px 35px rgba(0,0,0,.2)}.form-label{font-weight:700;font-size:.75rem;color:#444;text-transform:uppercase}.form-control{border-radius:12px;padding:12px 15px;margin-bottom:16px}.btn-reset{background:var(--boss-red);color:#fff;width:100%;padding:14px;border-radius:12px;font-weight:800;border:none;text-transform:uppercase}</style></head>
<body>
    <div class="reset-card">
        <img src="{{ asset('image/1.png') }}" class="img-fluid d-block mx-auto mb-3" style="max-height:90px" alt="Big Boss Logo">
        <h4 class="fw-bold text-center mb-2">SET NEW <span class="text-danger">PASSWORD</span></h4>
        <p class="text-center text-muted small mb-4">Enter the 6-digit code sent to your email, then choose a new password.</p>

        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <label class="form-label" for="email">Registered Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" class="form-control" required>
            <label class="form-label" for="code">6-Digit Reset Code</label>
            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" class="form-control" required>
            <label class="form-label" for="password">New Password</label>
            <input id="password" name="password" type="password" class="form-control" required>
            <div class="small text-muted mb-3">At least 8 characters with uppercase, lowercase, and number.</div>
            <label class="form-label" for="password_confirmation">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
            <button type="submit" class="btn-reset">CHANGE PASSWORD</button>
        </form>
    </div>
@include('partials.password-toggle')
</body>
</html>
