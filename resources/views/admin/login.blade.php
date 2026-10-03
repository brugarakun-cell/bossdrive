<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Admin Login</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--boss-red:#b71c1c;--boss-dark:#121212} body{background:linear-gradient(rgba(0,0,0,.8),rgba(0,0,0,.8)),url('{{ asset('image/banner-car.jpg') }}');background-size:cover;background-position:center;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',sans-serif}.login-card{background:#fff;width:100%;max-width:400px;padding:40px;border-radius:25px;box-shadow:0 15px 35px rgba(0,0,0,.4);text-align:center;position:relative}.back-btn{position:absolute;top:25px;left:25px;color:#ccc;font-size:1.1rem;text-decoration:none}.boss-logo{max-height:120px;display:block;margin:0 auto 20px}.form-label{font-weight:700;font-size:.75rem;color:#444;text-transform:uppercase;display:block;text-align:left;margin-bottom:5px}.form-control{border-radius:12px;padding:12px 15px;border:1.5px solid #eee;margin-bottom:20px;background:#f9f9f9}.btn-admin{background:var(--boss-dark);color:#fff;width:100%;padding:14px;border-radius:12px;font-weight:800;border:none;text-transform:uppercase;letter-spacing:1px}
    </style>
</head>
<body>
<div class="login-card">
    <a href="{{ url('/about') }}" class="back-btn"><i class="fas fa-arrow-left"></i></a>
    <img src="{{ asset('image/1.png') }}" alt="Big Boss Logo" class="boss-logo">
    <h4 class="fw-bold mb-1">LOGIN <span class="text-danger">PANEL</span></h4>
    @if(session('error'))<div class="alert alert-danger text-start small">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger text-start small">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <label class="form-label" for="email">Email / Username</label>
        <input id="email" name="email" type="text" class="form-control" placeholder="Admin" required autofocus>
        <label class="form-label" for="password">Password</label>
        <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required>
        <button type="submit" class="btn-admin shadow-sm mt-2">Login as Admin</button>
    </form>
</div>
@include('partials.password-toggle')
</body>
</html>
