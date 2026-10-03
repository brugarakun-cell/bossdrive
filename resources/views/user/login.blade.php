<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - User Login</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #b71c1c; --boss-dark: #121212; }
        body { background: #f4f4f4; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: #fff; width: 100%; max-width: 400px; padding: 40px; border-radius: 25px; box-shadow: 0 15px 35px rgba(0,0,0,.2); text-align: center; }
        .back-btn { color: #aaa; text-decoration: none; display: block; text-align: left; }
        .boss-logo { max-height: 120px; max-width: 220px; display: block; margin: 0 auto 20px; }
        .form-label { font-weight: 700; font-size: .75rem; color: #444; text-transform: uppercase; display: block; text-align: left; }
        .form-control { border-radius: 12px; padding: 12px 15px; border: 1.5px solid #eee; margin-bottom: 16px; background: #f9f9f9; }
        .btn-login { background: var(--boss-red); color: #fff; width: 100%; padding: 14px; border-radius: 12px; font-weight: 800; border: none; text-transform: uppercase; }
        a { color: var(--boss-red); font-weight: 700; text-decoration: none; }
        @media(max-width:575.98px) {
            body { padding:12px; }
            .login-card { padding:24px 20px; border-radius:18px; }
            .boss-logo { max-height:95px; max-width:190px; margin-bottom:14px; }
            .form-control { font-size:16px; }
            .btn-login { padding:12px; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <a href="{{ url('/reservations') }}" class="back-btn mb-3"><i class="fas fa-arrow-left"></i></a>
        <img src="{{ asset('image/1.png') }}" alt="Big Boss Logo" class="boss-logo">
        <h4 class="fw-bold"> <span class="text-danger">LOGIN</span></h4>
        <p class="text-muted small mb-4">Drive like a Boss. Log in to your account.</p>

        @if (session('success'))
            <div class="alert alert-success text-start small">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger text-start small">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('user.login.submit') }}">
            @csrf
            <label class="form-label" for="email">Email Address</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" placeholder="Enter your email" required autofocus>
            <label class="form-label" for="password">Password</label>
            <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required>
            <div class="form-check text-start small mb-3">
                <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn-login shadow-sm">Sign In</button>
            <div class="mt-3 small"><a href="{{ route('password.request') }}">Forgot Password?</a></div>
            <div class="mt-4 small">Don't have an account? <a href="{{ url('/user/register') }}">Register here</a></div>
        </form>
    </div>
    @include('partials.password-toggle')
</body>
</html>
