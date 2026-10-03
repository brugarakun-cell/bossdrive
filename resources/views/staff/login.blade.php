<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Staff Login</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red: #b71c1c; --boss-dark: #121212; }
        body {
            background: linear-gradient(rgba(0,0,0,.8), rgba(0,0,0,.8)), url('{{ asset('image/banner-car.jpg') }}');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff;
            width: 100%;
            max-width: 420px;
            padding: 40px 32px 28px;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,.4);
            text-align: center;
            position: relative;
        }
        .back-btn {
            position: absolute;
            top: 22px;
            left: 22px;
            color: #777;
            font-size: 1.1rem;
            text-decoration: none;
        }
        .boss-logo { max-height: 120px; display: block; margin: 0 auto 20px; }
        .form-label {
            font-weight: 700;
            font-size: .75rem;
            color: #444;
            text-transform: uppercase;
            display: block;
            text-align: left;
            margin-bottom: 5px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1.5px solid #eee;
            margin-bottom: 20px;
            background: #f9f9f9;
        }
        .btn-staff {
            background: var(--boss-dark);
            color: #fff;
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-weight: 800;
            border: none;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .helper-link {
            font-size: .82rem;
            color: #666;
            text-decoration: none;
            margin-top: 12px;
            display: inline-block;
        }
        @media (max-width: 575.98px) {
            body { padding:12px; }
            .login-card { padding:28px 20px 22px; border-radius:18px; }
            .boss-logo { max-height:95px; max-width:100%; }
            .form-control { font-size:16px; }
            .btn-staff { padding:12px; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <a href="{{ url('/about') }}" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <img src="{{ asset('image/1.png') }}" alt="Big Boss Logo" class="boss-logo">
        <h4 class="fw-bold mb-1">LOGIN <span class="text-danger">STAFF</span></h4>
        @if(session('error'))
            <div class="alert alert-danger text-start small mb-3">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger text-start small mb-3">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('staff.login.submit') }}">
            @csrf
            <label class="form-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="form-control" placeholder="staff@example.com" required autofocus>
            <label class="form-label" for="password">Password</label>
            <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required>
            <button type="submit" class="btn-staff shadow-sm mt-2">Login as Staff</button>
        </form>
        <div class="mt-3">
            <a class="helper-link" href="{{ route('admin.login') }}">Admin login</a>
        </div>
    </div>
@include('partials.password-toggle')
</body>
</html>
