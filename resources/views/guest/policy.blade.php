<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Rental Policy</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --boss-red: #b71c1c;
            --boss-grey: #e0e0e0;
        }

        body {
            background-color: var(--boss-grey);
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

        .content-container {
            padding: 30px;
        }

        .policy-card {
            background: #ffffff;
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 20px;
        }

        .policy-section-title {
            color: var(--boss-red);
            font-weight: 800;
            border-bottom: 2px solid #eeeeee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .policy-list {
            list-style: none;
            padding-left: 0;
        }

        .policy-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.95rem;
        }

        .policy-list li i {
            color: var(--boss-red);
            margin-top: 4px;
        }

        .important-note {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px;
            border-radius: 10px;
            font-size: 0.9rem;
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

            .content-container {
                padding: 14px 10px 24px;
            }

            .policy-card {
                padding: 1rem;
                margin-bottom: 14px;
                border-radius: 16px;
            }

            .policy-section-title {
                gap: .5rem;
                align-items: flex-start;
                font-size: .95rem;
                line-height: 1.35;
                margin-bottom: 12px;
            }

            .policy-section-title i {
                margin-right: .25rem !important;
                margin-top: .1rem;
            }

            .policy-list li {
                gap: 8px;
                padding: 9px 0;
                font-size: .86rem;
            }

            .important-note {
                padding: 11px;
                font-size: .84rem;
            }
        }
    </style>
</head>
<body>
    @auth
        @include('user.partials.navigation', ['title' => 'POLICY'])
    @else
        @include('partials.sidebar')
        <div class="main-content">
        <header class="top-nav shadow-sm">
            <h4 class="mb-0 text-black-bold">POLICY</h4>

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

        <main class="content-container">
            <div class="policy-card">
                <h5 class="policy-section-title"><i class="fas fa-id-card me-3"></i>RENTAL REQUIREMENTS</h5>
                <ul class="policy-list">
                    <li><i class="fas fa-check-circle"></i> <span><strong>Valid Driver's License:</strong> Original and clear copy of a non-expired professional or non-professional license.</span></li>
                    <li><i class="fas fa-check-circle"></i> <span><strong>Government-Issued ID:</strong> At least one additional valid ID (Passport, UMID, SSS, or PRC).</span></li>
                    <li><i class="fas fa-check-circle"></i> <span><strong>Proof of Billing:</strong> Recent utility bill (Electric or Water) under the renter's name or immediate family.</span></li>
                    <li><i class="fas fa-check-circle"></i> <span><strong>Security Deposit:</strong> A refundable security deposit of ₱1,000 is required upon booking/pickup.</span></li>
                </ul>
            </div>

            <div class="policy-card">
                <h5 class="policy-section-title"><i class="fas fa-gavel me-3"></i>VEHICLE RULES &amp; GUIDELINES</h5>
                <ul class="policy-list">
                    <li><i class="fas fa-ban"></i> <span><strong>Strictly No Smoking:</strong> A cleaning fee of ₱2,000 will be charged if the vehicle smells like smoke upon return.</span></li>
                    <li><i class="fas fa-gas-pump"></i> <span><strong>Fuel Policy:</strong> Return the vehicle with the same fuel level as when it was picked up.</span></li>
                    <li><i class="fas fa-broom"></i> <span><strong>Cleanliness:</strong> The vehicle must be returned in reasonably clean condition. Extreme dirt or stains will incur a ₱500 cleaning fee.</span></li>
                    <li><i class="fas fa-map-marker-alt"></i> <span><strong>Geographic Limits:</strong> Vehicles are not allowed to be taken outside of Luzon without prior written consent.</span></li>
                </ul>
            </div>

            <div class="policy-card">
                <h5 class="policy-section-title"><i class="fas fa-clock me-3"></i>LATE RETURNS &amp; EXTENSIONS</h5>
                <ul class="policy-list">
                    <li><i class="fas fa-exclamation-triangle"></i> <span><strong>Late Fee:</strong> ₱300 per hour will be charged for unannounced late returns.</span></li>
                    <li><i class="fas fa-calendar-plus"></i> <span><strong>Extensions:</strong> Must be requested at least 12 hours before the original return time, subject to availability.</span></li>
                </ul>
                <div class="important-note mt-3">
                    <i class="fas fa-info-circle me-2"></i> <strong>Note:</strong> Over 5 hours of late return without notice will automatically be considered as an additional 1-day rental.
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
