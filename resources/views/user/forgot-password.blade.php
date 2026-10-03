<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>BossDrive - Forgot Password</title>@include('partials.favicon')<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>:root{--boss-red:#b71c1c}body{background:#f4f4f4;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',sans-serif}.reset-card{background:#fff;width:100%;max-width:400px;padding:40px;border-radius:25px;box-shadow:0 15px 35px rgba(0,0,0,.4);text-align:center}.boss-logo{max-height:100px;margin-bottom:20px}.form-label{font-weight:700;font-size:.75rem;color:#444;text-transform:uppercase;display:block;text-align:left}.form-control{border-radius:12px;padding:12px 15px;border:1.5px solid #eee;margin-bottom:20px;background:#f9f9f9}.btn-reset{background:var(--boss-red);color:#fff;width:100%;padding:14px;border-radius:12px;font-weight:800;border:none;text-transform:uppercase}a{color:var(--boss-red);font-weight:700;text-decoration:none}</style></head>
<body>
    <div class="reset-card">
        <a href="{{ route('login') }}" class="d-block text-start text-secondary mb-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <img src="{{ asset('image/1.png') }}" class="boss-logo" alt="Big Boss Logo">
        <h4 class="fw-bold mb-2">FORGOT <span class="text-danger">PASSWORD?</span></h4>
        <p class="text-muted small mb-4">
            Enter the email address linked to your registered user account. We'll email you a 6-digit code to set a new password.
        </p>

        @if (session('success'))
            <div class="alert alert-success text-start small">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger text-start small">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label class="form-label" for="email">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" placeholder="yourname@example.com" required>
            <button type="submit" class="btn-reset shadow-sm">Send 6-Digit Code</button>
        </form>

        <div class="mt-4 small">Remembered it? <a href="{{ route('login') }}">Log In</a></div>
    </div>
</body>
</html>
