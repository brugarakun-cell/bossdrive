<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - About Us</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --boss-red: #b71c1c;
            --boss-dark: #121212;
            --boss-grey: #f4f4f4;
        }

        body {
            background-color: var(--boss-grey);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
            padding: 0 15%;
        }

        .welcome-section h2 {
            font-weight: 800;
            color: var(--boss-dark);
            margin-bottom: 20px;
        }

        .welcome-section p {
            color: #555;
            line-height: 1.8;
            font-size: 1rem;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            height: 100%;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
        }

        .icon-box {
            font-size: 2.5rem;
            color: var(--boss-red);
            margin-bottom: 20px;
        }

        .info-card h5 {
            font-weight: 800;
            color: var(--boss-dark);
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .info-card p {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.6;
        }

        .text-black-bold {
            color: #000000;
            font-weight: 800;
        }

        .text-boss-red {
            color: var(--boss-red);
            font-weight: 800;
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

            .container-fluid.p-5 {
                padding: 1rem !important;
            }

            .welcome-section {
                margin-bottom: 1.5rem;
                padding: 0 .25rem;
            }

            .welcome-section h2 {
                font-size: 1.45rem;
                margin-bottom: .75rem;
            }

            .welcome-section p {
                font-size: .92rem;
                line-height: 1.6;
            }

            .info-card {
                padding: 1.35rem;
                border-radius: 16px;
            }

            .icon-box {
                font-size: 2rem;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    @auth
        @include('user.partials.navigation', ['title' => 'ABOUT US'])
    @else
        @include('partials.sidebar')
        <div class="main-content">
        <header class="top-nav shadow-sm d-flex justify-content-between align-items-center p-3">
            <h4 class="mb-0 text-black-bold">ABOUT US <span class="text-boss-red"></span></h4>

            <div class="d-flex align-items-center gap-3">
                <div class="auth-buttons d-flex gap-2">
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-dark px-3">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-sm btn-danger px-3" style="background-color: #b71c1c; border: none;">Register</a>
                </div>
            </div>
        </header>
    @endauth

        <div class="container-fluid p-5">
            <div class="welcome-section">
                <h2>Welcome to Big Boss Car Rental</h2>
                <p>
                    Your trusted partner for reliable and affordable car rental services in GMA, Cavite.
                    We are dedicated to providing our customers with high-quality vehicles and exceptional
                    service to make every journey comfortable and stress-free.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="icon-box"><i class="fas fa-history"></i></div>
                        <h5>History</h5>
                        <p>Established to serve the growing transportation needs in Cavite, Big Boss Car Rental started with a commitment to excellence and reliability. Over the years, we have built a reputation for trust and quality service.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="icon-box"><i class="fas fa-shield-alt"></i></div>
                        <h5>Safety 24/7</h5>
                        <p>Your safety is our top priority. Our vehicles undergo regular maintenance and strict safety checks. With our 24/7 roadside assistance, you can travel with peace of mind knowing we are always here to help.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="icon-box"><i class="fas fa-user-tie"></i></div>
                        <h5>Professional Service</h5>
                        <p>We take pride in our professional approach. From easy booking to transparent pricing and friendly support, we ensure a seamless experience that treats every customer like a Boss.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
