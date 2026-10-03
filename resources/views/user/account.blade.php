<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - My Account</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--boss-red:#b71c1c;--boss-dark:#121212;--boss-grey:#e0e0e0}
        body{background:var(--boss-grey);font-family:'Segoe UI',sans-serif}.sidebar{width:250px;height:100vh;background:var(--boss-dark);position:fixed;padding:20px;border-right:4px solid var(--boss-red);z-index:1000;display:flex;flex-direction:column;overflow-y:auto}.nav-link{color:#fff;margin-bottom:10px;border-radius:10px;padding:12px 15px;font-weight:600;text-decoration:none;display:block}.nav-link:hover,.nav-link.active{background:var(--boss-red);color:#fff!important}.sidebar-footer{margin-top:auto;width:100%;border-top:1px solid #333;padding-top:15px}.sidebar-footer a{color:#888;text-decoration:none;font-size:.85rem}.main-content{margin-left:250px;min-height:100vh}.top-nav{background:#fff;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #ccc;position:sticky;top:0;z-index:999}.content-container{padding:30px}.text-black-bold{font-weight:800}.text-boss-red{color:var(--boss-red);font-weight:800}.account-card{background:#fff;border-radius:20px;box-shadow:0 10px 25px rgba(0,0,0,.05);padding:30px}.profile-img-container{position:relative;width:120px;height:120px;margin:0 auto 20px}.profile-img{width:120px;height:120px;object-fit:cover;border:4px solid var(--boss-red)}.edit-img-btn{position:absolute;bottom:0;right:0;background:var(--boss-red);color:#fff;border-radius:50%;width:35px;height:35px;border:3px solid #fff;display:flex;align-items:center;justify-content:center;cursor:pointer}.form-label{font-weight:700;font-size:.85rem;color:#555}.form-control{border-radius:10px;padding:10px 15px;border:1px solid #ddd}.form-control:focus{border-color:var(--boss-red);box-shadow:none}.birth-date-field{min-width:180px}@media(max-width:768px){.sidebar{width:210px}.main-content{margin-left:0}.content-container{padding:15px}.birth-date-field{min-width:0}.sidebar-footer{position:static;width:100%}}@media(max-width:575.98px){.account-card{padding:1rem}.account-card .border-end{border-right:0!important;border-bottom:1px solid #eee;padding-bottom:1.25rem;margin-bottom:1rem}.account-card .ps-md-5{padding-left:calc(var(--bs-gutter-x)*.5)!important}.account-card .mt-5{margin-top:1.25rem!important}.account-card .d-flex.gap-2{flex-wrap:wrap}.account-card .d-flex.gap-2 .btn{flex:1 1 auto}.form-control,.form-select{font-size:16px}.modal-dialog{margin:.5rem}.modal-content{padding:1rem!important}}
    </style>
</head>
<body>
@php $profileImage=$user->profile_photo_path?asset('storage/'.$user->profile_photo_path):'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=b71c1c&color=fff&size=128'; @endphp
@include('user.partials.navigation', ['title' => 'MY', 'accent' => 'ACCOUNT'])
<div class="content-container">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="row justify-content-center"><div class="col-lg-11"><div class="account-card"><div class="row"><div class="col-md-4 text-center border-end"><div class="profile-img-container"><img src="{{ $profileImage }}" class="rounded-circle profile-img" id="previewImg"><label for="imgUpload" class="edit-img-btn"><i class="fas fa-camera"></i></label></div><h5 class="fw-bold mb-1">{{ $user->name }}</h5><p class="text-muted small mb-1">Verified Member</p><hr><div class="text-start px-3"><h6 class="fw-bold small mb-3">ACCOUNT SECURITY</h6><button type="button" class="btn btn-outline-dark btn-sm w-100 mb-2 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#changePassModal">Change Password</button><form method="POST" action="{{ route('user.logout') }}">@csrf<button type="submit" class="btn btn-danger btn-sm w-100 rounded-pill fw-bold">Logout Account</button></form></div></div>
<div class="col-md-8 ps-md-5 mt-4 mt-md-0"><h6 class="fw-bold mb-4 text-boss-red"><i class="fas fa-info-circle me-2"></i>PERSONAL INFORMATION</h6><form method="POST" action="{{ route('user.account.update') }}" enctype="multipart/form-data">@csrf @method('PUT')<input type="file" name="profile_photo" id="imgUpload" hidden accept="image/*" onchange="previewFile()"><div class="row g-3"><div class="col-md-6"><label class="form-label">Full Name</label><input name="name" class="form-control" value="{{ old('name',$user->name) }}" required></div><div class="col-md-6"><label class="form-label">Email Address</label><input name="email" type="email" class="form-control" value="{{ old('email',$user->email) }}" required></div><div class="col-md-5"><label class="form-label">Phone Number</label><input name="contact_number" class="form-control" value="{{ old('contact_number',$user->contact_number) }}" required></div><div class="col-md-3"><label class="form-label">Age</label><input name="age" type="number" min="18" max="120" class="form-control" value="{{ old('age',$user->age) }}"></div><div class="col-md-4 birth-date-field"><label class="form-label">Birthday</label><input name="birth_date" type="date" class="form-control" value="{{ old('birth_date',$user->birth_date?->format('Y-m-d')) }}"></div><div class="col-md-6"><label class="form-label">Gender</label><select name="gender" class="form-select"><option value="">Select Gender</option><option value="Male" {{ old('gender',$user->gender) === 'Male' ? 'selected' : '' }}>Male</option><option value="Female" {{ old('gender',$user->gender) === 'Female' ? 'selected' : '' }}>Female</option><option value="Other" {{ old('gender',$user->gender) === 'Other' ? 'selected' : '' }}>Other</option></select></div><div class="col-md-6"><label class="form-label" for="province">Province</label><select id="province" name="province" class="form-select" required><option value="">Loading provinces...</option></select></div><div class="col-md-6"><label class="form-label" for="city">City / Municipality</label><select id="city" name="city" class="form-select" required disabled><option value="">Select province first</option></select></div><div class="col-md-6"><label class="form-label" for="barangay">Barangay</label><select id="barangay" name="barangay" class="form-select" required disabled><option value="">Select city / municipality first</option></select></div><div class="col-12"><label class="form-label">Block &amp; Lot / Street</label><textarea name="address" class="form-control" rows="2" required>{{ old('address',$user->address) }}</textarea></div></div><div class="mt-5 d-flex gap-2"><button type="submit" class="btn btn-danger px-4 rounded-pill fw-bold shadow">SAVE CHANGES</button><a href="{{ route('user.account') }}" class="btn btn-outline-secondary px-4 rounded-pill fw-bold">CANCEL</a></div></form></div></div></div></div></div></div>
<div class="modal fade" id="changePassModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content rounded-4 p-4 border-0 shadow"><div class="modal-header border-0 p-0 mb-3"><h5 class="fw-bold mb-0">Change Password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="{{ route('user.account.password') }}">@csrf @method('PUT')<div class="mb-3"><label class="form-label small">Current Password</label><input name="current_password" type="password" class="form-control rounded-pill" required></div><div class="mb-3"><label class="form-label small">New Password</label><input name="password" type="password" class="form-control rounded-pill" minlength="8" required></div><div class="mb-4"><label class="form-label small">Confirm New Password</label><input name="password_confirmation" type="password" class="form-control rounded-pill" minlength="8" required></div><button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold py-2 shadow">UPDATE PASSWORD</button></form></div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script>
function previewFile(){const f=document.getElementById('imgUpload').files[0];if(!f)return;const r=new FileReader();r.onloadend=()=>document.getElementById('previewImg').src=r.result;r.readAsDataURL(f);}
const provinceSelect=document.getElementById('province');
const citySelect=document.getElementById('city');
const barangaySelect=document.getElementById('barangay');
if(provinceSelect&&citySelect&&barangaySelect){
    const oldProvince=@json(old('province',$user->province));
    const oldCity=@json(old('city',$user->city));
    const oldBarangay=@json(old('barangay',$user->barangay));
    const psgcBaseUrl='https://psgc.gitlab.io/api';
    function resetSelect(select,label){select.innerHTML='<option value="">'+label+'</option>';select.disabled=true;}
    function addOptions(select,items,selectedValue){
        items.forEach(function(item){
            const option=new Option(item.name,item.name);
            option.dataset.code=item.code;
            option.selected=item.name.toLocaleLowerCase()===String(selectedValue||'').toLocaleLowerCase();
            select.add(option);
        });
        select.disabled=items.length===0;
    }
    async function loadProvinces(){
        try{
            const response=await fetch(psgcBaseUrl+'/provinces/');
            if(!response.ok)throw new Error('Unable to load provinces.');
            const items=await response.json();
            provinceSelect.innerHTML='<option value="">Select Province</option>';
            addOptions(provinceSelect,items.sort((a,b)=>a.name.localeCompare(b.name)),oldProvince);
            if(oldProvince)provinceSelect.dispatchEvent(new Event('change'));
        }catch(error){
            provinceSelect.innerHTML='<option value="">Unable to load provinces</option>';
            provinceSelect.disabled=true;
        }
    }
    provinceSelect.addEventListener('change',async function(){
        resetSelect(citySelect,'Loading cities / municipalities...');
        resetSelect(barangaySelect,'Select city / municipality first');
        const provinceCode=provinceSelect.selectedOptions[0]?.dataset.code;
        if(!provinceCode)return;
        try{
            const response=await fetch(psgcBaseUrl+'/provinces/'+provinceCode+'/cities-municipalities/');
            if(!response.ok)throw new Error('Unable to load cities.');
            const items=await response.json();
            citySelect.innerHTML='<option value="">Select City / Municipality</option>';
            addOptions(citySelect,items.sort((a,b)=>a.name.localeCompare(b.name)),oldCity);
            if(oldCity)citySelect.dispatchEvent(new Event('change'));
        }catch(error){
            resetSelect(citySelect,'Unable to load cities / municipalities');
        }
    });
    citySelect.addEventListener('change',async function(){
        resetSelect(barangaySelect,'Loading barangays...');
        const cityCode=citySelect.selectedOptions[0]?.dataset.code;
        if(!cityCode)return;
        try{
            const response=await fetch(psgcBaseUrl+'/cities-municipalities/'+cityCode+'/barangays/');
            if(!response.ok)throw new Error('Unable to load barangays.');
            const items=await response.json();
            barangaySelect.innerHTML='<option value="">Select Barangay</option>';
            addOptions(barangaySelect,items.sort((a,b)=>a.name.localeCompare(b.name)),oldBarangay);
        }catch(error){
            resetSelect(barangaySelect,'Unable to load barangays');
        }
    });
    loadProvinces();
}
</script>
@include('partials.password-toggle')
</body>
</html>
