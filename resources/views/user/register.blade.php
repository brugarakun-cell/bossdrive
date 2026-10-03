<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - Register</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --boss-red:#b71c1c; }
        body { background:#f4f4f4; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; font-family:'Segoe UI',sans-serif; }
        .signup-card { background:#fff; width:100%; max-width:650px; padding:40px; border-radius:25px; box-shadow:0 15px 35px rgba(0,0,0,.2); }
        .boss-logo { max-height:90px; max-width:220px; display:block; margin:0 auto 15px; }
        .form-label { font-weight:700; font-size:.75rem; color:#444; text-transform:uppercase; }
        .form-control,.form-select { border-radius:10px; padding:11px 15px; border:1.5px solid #eee; margin-bottom:14px; background:#f9f9f9; }
        .section-title { font-weight:800; font-size:.95rem; color:var(--boss-red); margin-bottom:18px; border-bottom:2px solid #f0f0f0; padding-bottom:8px; }
        .btn-signup { background:var(--boss-red); color:#fff; width:100%; padding:14px; border-radius:12px; font-weight:800; border:none; text-transform:uppercase; margin-top:15px; }
        a { color:var(--boss-red); font-weight:700; text-decoration:none; }
        .step-dot { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:50%; background:#eee; color:#888; font-weight:800; margin:0 5px; }
        .step-dot.active { background:var(--boss-red); color:white; }
        .password-rules { list-style:none; padding:0; margin:-4px 0 14px; font-size:.78rem; text-align:left; }
        .password-rules li { color:#dc3545; margin:3px 0; }
        .password-rules li.valid { color:#198754; }
        .password-rules i { width:18px; }
        .field-error { color:#dc3545; font-size:.75rem; font-weight:600; margin-top:-9px; margin-bottom:10px; display:block; }
        .form-control.is-invalid,.form-select.is-invalid { border-color:#dc3545; background-image:none; }
        @media(max-width:575.98px) {
            body { align-items:flex-start; padding:12px; }
            .signup-card { padding:22px 16px; border-radius:18px; }
            .boss-logo { max-height:75px; max-width:190px; }
            .step-dot { width:27px; height:27px; margin:0 3px; }
            .form-control,.form-select { font-size:16px; }
            .btn-signup { padding:12px; }
        }
    </style>
</head>
<body>
<div class="signup-card">
    <a href="{{ url('/reservations') }}" class="text-secondary"><i class="fas fa-arrow-left"></i></a>
    <img src="{{ asset('image/1.png') }}" alt="Big Boss Logo" class="boss-logo">
    <h3 class="text-center fw-bold mb-3">REGISTER <span class="text-danger">ACCOUNT</span></h3>
    <div class="text-center mb-4">
        <span class="step-dot {{ $step === 1 ? 'active' : '' }}">1</span>
        <span class="step-dot {{ $step === 3 ? 'active' : '' }}">2</span>
    </div>
    @if(session('success')) <div class="alert alert-success small">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="alert alert-danger small"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

    @if($step === 1)
        <div class="section-title"><i class="fas fa-user me-2"></i>Personal Information</div>
        <form method="POST" action="{{ route('user.register.personal') }}">
            @csrf
            <label class="form-label" for="name">Full Name</label>
            <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', session('registration.name')) }}" required>
            @error('name')<span class="field-error">{{ $message }}</span>@enderror
            <label class="form-label" for="email">Email Account</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', session('registration.email')) }}" required>
            @error('email')<span class="field-error">{{ $message }}</span>@enderror
            <label class="form-label" for="contact_number">Contact Number</label>
            <input id="contact_number" name="contact_number" class="form-control @error('contact_number') is-invalid @enderror" value="{{ old('contact_number', session('registration.contact_number')) }}" required>
            @error('contact_number')<span class="field-error">{{ $message }}</span>@enderror
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label" for="birth_date">Birthday</label>
                    <input id="birth_date" name="birth_date" type="date" max="{{ now()->subYears(18)->toDateString() }}" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date', session('registration.birth_date')) }}" required>
                    <small class="text-muted d-block mb-2">Must be 18 years old or above.</small>
                    @error('birth_date')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="age">Age</label>
                    <input id="age" name="age" type="number" min="18" max="120" class="form-control @error('age') is-invalid @enderror" value="{{ old('age', session('registration.age')) }}" placeholder="Automatic" readonly required>
                    @error('age')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <label class="form-label" for="gender">Gender</label>
            <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                <option value="">Select Gender</option>
                <option value="Male" {{ old('gender', session('registration.gender')) === 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ old('gender', session('registration.gender')) === 'Female' ? 'selected' : '' }}>Female</option>
                <option value="Other" {{ old('gender', session('registration.gender')) === 'Other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('gender')<span class="field-error">{{ $message }}</span>@enderror
            <label class="form-label" for="password">Password</label>
            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" aria-describedby="password-rules" required>
            @error('password')<span class="field-error">{{ $message }}</span>@enderror
            <ul id="password-rules" class="password-rules">
                <li data-rule="length"><i class="fas fa-circle-xmark"></i> At least 8 characters</li>
                <li data-rule="uppercase"><i class="fas fa-circle-xmark"></i> At least 1 uppercase letter</li>
                <li data-rule="lowercase"><i class="fas fa-circle-xmark"></i> At least 1 lowercase letter</li>
                <li data-rule="number"><i class="fas fa-circle-xmark"></i> At least 1 number</li>
                <li data-rule="special"><i class="fas fa-circle-xmark"></i> At least 1 special character</li>
            </ul>
            <label class="form-label" for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" aria-describedby="password-match" required>
            <div id="password-match" class="small text-danger mb-2">Passwords must match.</div>
            <button type="submit" id="nextPersonalButton" class="btn-signup shadow-sm" disabled>NEXT: ADDRESS DETAILS</button>
        </form>
    @else
        <div class="section-title"><i class="fas fa-map-marker-alt me-2"></i>Address Details</div>
        <form method="POST" action="{{ route('user.register.address') }}">
            @csrf
            <label class="form-label" for="province">Province</label>
            <select id="province" name="province" class="form-select @error('province') is-invalid @enderror" required><option value="">Loading provinces...</option></select>
            @error('province')<span class="field-error">{{ $message }}</span>@enderror
            <label class="form-label" for="city">City / Municipality</label>
            <select id="city" name="city" class="form-select @error('city') is-invalid @enderror" required disabled><option value="">Select province first</option></select>
            @error('city')<span class="field-error">{{ $message }}</span>@enderror
            <label class="form-label" for="barangay">Barangay</label>
            <select id="barangay" name="barangay" class="form-select @error('barangay') is-invalid @enderror" required disabled><option value="">Select city / municipality first</option></select>
            @error('barangay')<span class="field-error">{{ $message }}</span>@enderror
            <label class="form-label" for="address">Block &amp; Lot / Street</label>
            <input id="address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}" required>
            @error('address')<span class="field-error">{{ $message }}</span>@enderror
            <button type="submit" class="btn-signup shadow-sm">FINISH REGISTRATION</button>
        </form>
        <form method="POST" action="{{ route('user.register.back') }}" class="mt-2">@csrf<button class="btn btn-outline-secondary w-100 rounded-pill">PREVIOUS</button></form>
    @endif
    <div class="text-center mt-3 small">Already have an account? <a href="{{ route('login') }}">Login here</a></div>
</div>
<script>
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password_confirmation');
    const age = document.getElementById('age');
    const birthDate = document.getElementById('birth_date');
    const ruleTests = {
        length: value => value.length >= 8,
        uppercase: value => /[A-Z]/.test(value),
        lowercase: value => /[a-z]/.test(value),
        number: value => /[0-9]/.test(value),
        special: value => /[^A-Za-z0-9]/.test(value)
    };
    function updatePasswordRules() {
        if (!password || !confirmation) return;
        const value = password.value;
        Object.entries(ruleTests).forEach(([name, test]) => {
            const item = document.querySelector('[data-rule="' + name + '"]');
            const valid = test(value);
            item.classList.toggle('valid', valid);
            item.querySelector('i').className = valid ? 'fas fa-circle-check' : 'fas fa-circle-xmark';
        });
        const match = confirmation.value !== '' && value === confirmation.value;
        const matchText = document.getElementById('password-match');
        matchText.className = 'small mb-2 ' + (match ? 'text-success' : 'text-danger');
        matchText.textContent = match ? 'Passwords match.' : 'Passwords must match.';
        document.getElementById('nextPersonalButton').disabled =
            !Object.values(ruleTests).every(test => test(value)) || !match;
    }
    if (password && confirmation) {
        password.addEventListener('input', updatePasswordRules);
        confirmation.addEventListener('input', updatePasswordRules);
    }
    function updateBirthdayError() {
        if (!age || !birthDate || !birthDate.value || !age.value) return;
        const birthday = new Date(birthDate.value + 'T00:00:00');
        const today = new Date();
        let calculated = today.getFullYear() - birthday.getFullYear();
        const beforeBirthday = today.getMonth() < birthday.getMonth()
            || (today.getMonth() === birthday.getMonth() && today.getDate() < birthday.getDate());
        if (beforeBirthday) calculated--;
        age.setCustomValidity(calculated === Number(age.value) ? '' : 'Age does not match the selected birthday.');
    }
    if (age && birthDate) {
        function updateCalculatedAge() {
            if (!birthDate.value) {
                age.value = '';
                age.setCustomValidity('');
                return;
            }
            const birthday = new Date(birthDate.value + 'T00:00:00');
            const today = new Date();
            let calculated = today.getFullYear() - birthday.getFullYear();
            if (today.getMonth() < birthday.getMonth()
                || (today.getMonth() === birthday.getMonth() && today.getDate() < birthday.getDate())) {
                calculated--;
            }
            age.value = calculated >= 0 ? calculated : '';
            if (calculated < 18) {
                birthDate.setCustomValidity('Registrants must be 18 years old or above.');
                age.setCustomValidity('Registrants must be 18 years old or above.');
            } else {
                birthDate.setCustomValidity('');
                updateBirthdayError();
            }
        }
        birthDate.addEventListener('change', updateCalculatedAge);
        updateCalculatedAge();
    }

    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');
    if (provinceSelect && citySelect && barangaySelect) {
        const oldProvince = @json(old('province'));
        const oldCity = @json(old('city'));
        const oldBarangay = @json(old('barangay'));
        const psgcBaseUrl = 'https://psgc.gitlab.io/api';

        function resetSelect(select, label) {
            select.innerHTML = '<option value="">' + label + '</option>';
            select.disabled = true;
        }

        function addOptions(select, items, selectedValue) {
            items.forEach(function (item) {
                const option = new Option(item.name, item.name);
                option.dataset.code = item.code;
                option.selected = item.name === selectedValue;
                select.add(option);
            });
            select.disabled = items.length === 0;
        }

        async function loadProvinces() {
            try {
                const response = await fetch(psgcBaseUrl + '/provinces/');
                if (!response.ok) throw new Error('Unable to load provinces.');
                const items = await response.json();
                provinceSelect.innerHTML = '<option value="">Select Province</option>';
                addOptions(provinceSelect, items.sort((a, b) => a.name.localeCompare(b.name)), oldProvince);
                if (oldProvince) provinceSelect.dispatchEvent(new Event('change'));
            } catch (error) {
                provinceSelect.innerHTML = '<option value="">Unable to load provinces</option>';
                provinceSelect.disabled = true;
            }
        }

        provinceSelect.addEventListener('change', async function () {
            resetSelect(citySelect, 'Loading cities / municipalities...');
            resetSelect(barangaySelect, 'Select city / municipality first');
            const provinceCode = provinceSelect.selectedOptions[0]?.dataset.code;
            if (!provinceCode) return;
            try {
                const response = await fetch(psgcBaseUrl + '/provinces/' + provinceCode + '/cities-municipalities/');
                if (!response.ok) throw new Error('Unable to load cities.');
                const items = await response.json();
                citySelect.innerHTML = '<option value="">Select City / Municipality</option>';
                addOptions(citySelect, items.sort((a, b) => a.name.localeCompare(b.name)), oldCity);
                if (oldCity) citySelect.dispatchEvent(new Event('change'));
            } catch (error) {
                resetSelect(citySelect, 'Unable to load cities / municipalities');
            }
        });

        citySelect.addEventListener('change', async function () {
            resetSelect(barangaySelect, 'Loading barangays...');
            const cityCode = citySelect.selectedOptions[0]?.dataset.code;
            if (!cityCode) return;
            try {
                const response = await fetch(psgcBaseUrl + '/cities-municipalities/' + cityCode + '/barangays/');
                if (!response.ok) throw new Error('Unable to load barangays.');
                const items = await response.json();
                barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
                addOptions(barangaySelect, items.sort((a, b) => a.name.localeCompare(b.name)), oldBarangay);
            } catch (error) {
                resetSelect(barangaySelect, 'Unable to load barangays');
            }
        });

        loadProvinces();
    }
</script>
@include('partials.password-toggle')
</body>
</html>
