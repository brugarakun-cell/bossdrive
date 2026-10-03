<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Contact Us</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --boss-red: #b71c1c;
            --boss-dark: #121212;
            --boss-bg-light: #f8f9fa;
            --boss-card-light: #ffffff;
        }

        body {
            background-color: var(--boss-bg-light);
            color: #000000;
            font-family: 'Segoe UI', sans-serif;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }

        .top-nav {
            background-color: #ffffff;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .text-black-bold {
            color: #000000;
            font-weight: 800;
        }

        .bg-light-card {
            background-color: var(--boss-card-light) !important;
            border: 1px solid #dee2e6 !important;
        }

        .bg-light-input {
            background-color: #ffffff !important;
            color: #000000 !important;
            border: 1px solid #ced4da !important;
        }

        .bg-light-input:focus {
            border-color: var(--boss-red);
            box-shadow: none;
        }

        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 575.98px) {
            .top-nav {
                gap: 8px;
                padding: 12px 12px 12px 58px !important;
            }

            .top-nav h4 {
                font-size: 1rem;
            }

            .auth-buttons {
                flex-shrink: 0;
                gap: 5px !important;
            }

            .auth-buttons .btn {
                padding: .35rem .55rem !important;
                font-size: .72rem;
            }

            main.bg-light {
                padding: 1rem !important;
            }

            main .container-fluid {
                padding: 0;
            }

            main .row.g-5 {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1.5rem;
            }

            main h1 {
                font-size: 1.65rem;
            }

            main .mb-5 {
                margin-bottom: 1.5rem !important;
            }

            main .bg-light-card {
                padding: 1rem !important;
            }

            main .btn[type="submit"] {
                padding: .5rem 1.25rem !important;
                font-size: .85rem;
            }

            main iframe {
                height: 280px;
            }
        }
    </style>
</head>
<body>
    @auth
        @include('user.partials.navigation', ['title' => 'CONTACT'])
    @else
        @include('partials.sidebar')
        <div class="main-content">
        <header class="top-nav shadow-sm">
            <h4 class="mb-0 text-black-bold">CONTACT</h4>

            <div class="auth-buttons d-flex gap-2">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-dark px-3">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-danger px-3" style="background-color: #b71c1c; border: none;">Register</a>
                @else
                    <a href="{{ route('user.dashboard') }}" class="btn btn-sm btn-danger px-3" style="background-color: #b71c1c; border: none;">Dashboard</a>
                @endguest
            </div>
        </header>
    @endauth

        <main class="bg-light p-4 p-md-5" style="min-height: calc(100vh - 85px);">
            <div class="container-fluid">
                <div class="row g-5">
                    <div class="col-lg-5">
                        <h1 class="fw-bold text-dark mb-2">Get in Touch</h1>
                        <p class="text-muted mb-5">"Got a question or a special request? Feel free to reach out. The BossDrive team is ready to help."</p>

                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle border border-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: white;">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                            </div>
                            <div class="ms-3">
                                <p class="mb-0 fw-bold text-dark text-uppercase small">Garage Location</p>
                                <small class="text-muted">Area G, Poblacion 5, GMA, Cavite</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle border border-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: white;">
                                <i class="fas fa-phone-alt text-danger"></i>
                            </div>
                            <div class="ms-3">
                                <p class="mb-0 fw-bold text-dark text-uppercase small">Phone Number</p>
                                <small class="text-muted">09xxxxxxxxx</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle border border-danger d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: white;">
                                <i class="fas fa-envelope text-danger"></i>
                            </div>
                            <div class="ms-3">
                                <p class="mb-0 fw-bold text-dark text-uppercase small">Email Address</p>
                                <small class="text-muted">bossdrive.carrental@gmail.com</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="bg-light-card p-4 rounded-4 border shadow">
                            <form method="POST" action="{{ route('contact.submit') }}">
                                @csrf

                                @if (session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="text-dark small fw-bold text-uppercase mb-1">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light-input border-end-0 text-danger"><i class="fas fa-user"></i></span>
                                            <input id="name" name="name" type="text" value="{{ old('name') }}" class="form-control bg-light-input border-start-0 shadow-none" placeholder="Enter name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="text-dark small fw-bold text-uppercase mb-1">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light-input border-end-0 text-danger"><i class="fas fa-envelope"></i></span>
                                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control bg-light-input border-start-0 shadow-none" placeholder="your@email.com" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="text-dark small fw-bold text-uppercase mb-1">Subject</label>
                                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="form-control bg-light-input shadow-none" placeholder="Subject" required>
                                </div>
                                <div class="mb-4">
                                    <label for="message" class="text-dark small fw-bold text-uppercase mb-1">Message</label>
                                    <textarea id="message" name="message" class="form-control bg-light-input shadow-none" rows="5" placeholder="Message" required>{{ old('message') }}</textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-danger px-5 py-2 fw-bold text-uppercase shadow rounded-3">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row mt-5 pt-3">
                    <div class="col-12">
                        <div class="rounded-4 overflow-hidden border shadow">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d399.15508174820576!2d120.99894671222316!3d14.28369870178908!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397d604b4868f9b%3A0x79c385e3312f3542!2sPetron!5e1!3m2!1sen!2sph!4v1777122319185!5m2!1sen!2sph" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
