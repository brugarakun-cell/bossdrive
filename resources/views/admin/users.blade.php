<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BossDrive - User Accounts</title>
    @include('partials.favicon')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --boss-red: #dc3545; 
            --boss-dark: #212529; 
            --boss-grey: #f8f9fa; 
        }

        body { background-color: var(--boss-grey); font-family: 'Segoe UI', sans-serif; }
        
        .sidebar { 
            width: 250px; height: 100vh; background-color: #212529; position: fixed; 
            border-right: 5px solid #dc3545; z-index: 1000; 
        }
        .sidebar .nav-link { 
            color: white; padding: 15px 20px; margin: 5px 15px; border-radius: 8px;
            font-size: 0.9rem; transition: 0.3s; text-decoration: none; display: block; font-weight: 600;
        }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .sidebar .nav-link.active { background-color: #dc3545; box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); color: white; }

        .main-content { margin-left: 250px; }
        .top-nav { 
            background: white; padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }

        .content-container { padding: 30px; }
        .dashboard-card {
            background: white; border-radius: 15px; border: none; padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .search-input { border-radius: 20px; padding-left: 40px; background-color: #f8f9fa; border: 1px solid #eee; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }

        /* Modal Custom Styles */
        .form-label-custom { font-size: 0.75rem; font-weight: 700; color: #6c757d; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .input-custom { background-color: #f8f9fa !important; border: none !important; padding: 12px 15px !important; border-radius: 10px !important; font-size: 0.9rem; }
        .section-title-custom { color: var(--boss-red); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 15px; }
        .password-rules { list-style: none; padding: 0; margin: 8px 0 0; font-size: 0.72rem; }
        .password-rules li { color: #dc3545; margin: 3px 0; }
        .password-rules li.valid { color: #198754; }
        .password-rules i { width: 16px; }

        /* Toast and Confirm Modal Custom Styles */
        .bd-toast-container { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 2000; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .bd-toast { min-width: 260px; max-width: 340px; padding: 14px 18px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.18); font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 10px; color: #fff; opacity: 0; transform: scale(0.9); transition: 0.25s ease; }
        .bd-toast.show { opacity: 1; transform: scale(1); }
        .bd-toast.success { background: #198754; }
        .bd-toast.cancel { background: #6c757d; }
        .bd-toast.error { background: #dc3545; }
        #bdConfirmModal { z-index: 1090 !important; }
    </style>
</head>
<body>

    @include('admin.partials.navigation', ['adminPageTitle' => 'User', 'adminPageAccent' => 'Accounts'])
    <div class="main-content">

        <div class="content-container">
            <div class="dashboard-card">
                <div class="row mb-4 align-items-center">
                    <div class="col-md-4">
                        <h6 class="fw-bold m-0 text-uppercase small">Account List</h6>
                    </div>
                    <div class="col-md-5 position-relative">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="userSearch" class="form-control search-input" placeholder="Search accounts..." onkeyup="filterUsers()">
                    </div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-danger btn-sm rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                            <i class="fas fa-plus me-2"></i> NEW USER
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-hover" id="userTable">
                        <thead class="small text-muted text-uppercase fw-bold">
                            <tr>
                                <th>Name & Profile</th>
                                <th>Email Address</th>
                                <th>Role</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="user-1">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">JW</div>
                                        <div>
                                            <span class="fw-bold d-block">John Wick</span>
                                            <small class="text-muted">35 yrs old | Male</small>
                                        </div>
                                    </div>
                                </td>
                                <td>john@email.com</td>
                                <td><span class="badge rounded-pill px-3 py-2 bg-dark fw-normal">User</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" onclick="viewDetails('John Wick', 'john@email.com', '09123456789', 'User', '35', 'Male', 'Manila', 'Makati', 'Poblacion', 'Blk 1')"><i class="fas fa-eye text-success"></i></button>
                                    <button class="btn btn-sm btn-light" onclick="editUser('John Wick', 'john@email.com', '09123456789', 'User', '35', 'Male', 'Manila', 'Makati', 'Poblacion', 'Blk 1')"><i class="fas fa-edit text-primary"></i></button>
                                    <button class="btn btn-sm btn-light text-danger" onclick="deleteUser('user-1', 'John Wick')"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr id="user-2">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success text-white rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">MM</div>
                                        <div>
                                            <span class="fw-bold d-block">Maria Makiling</span>
                                            <small class="text-muted">28 yrs old | Female</small>
                                        </div>
                                    </div>
                                </td>
                                <td>maria@email.com</td>
                                <td><span class="badge rounded-pill px-3 py-2 bg-dark fw-normal">User</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" onclick="viewDetails('Maria Makiling', 'maria@email.com', '09987654321', 'User', '28', 'Female', 'Laguna', 'Calamba', 'Bagong Silang', 'Phase 1')"><i class="fas fa-eye text-success"></i></button>
                                    <button class="btn btn-sm btn-light" onclick="editUser('Maria Makiling', 'maria@email.com', '09987654321', 'User', '28', 'Female', 'Laguna', 'Calamba', 'Bagong Silang', 'Phase 1')"><i class="fas fa-edit text-primary"></i></button>
                                    <button class="btn btn-sm btn-light text-danger" onclick="deleteUser('user-2', 'Maria Makiling')"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr id="user-3">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-danger text-white rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">PA</div>
                                        <div>
                                            <span class="fw-bold d-block">Patrick Admin</span>
                                            <small class="text-muted">20 yrs old | Male</small>
                                        </div>
                                    </div>
                                </td>
                                <td>admin@bossdrive.com</td>
                                <td><span class="badge rounded-pill px-3 py-2 bg-danger fw-normal">Admin</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light" onclick="viewDetails('Patrick Admin', 'admin@bossdrive.com', '09112233445', 'Admin', '20', 'Male', 'Cavite', 'GMA', 'San Gabriel', 'Area 1')"><i class="fas fa-eye text-success"></i></button>
                                    <button class="btn btn-sm btn-light" onclick="editUser('Patrick Admin', 'admin@bossdrive.com', '09112233445', 'Admin', '20', 'Male', 'Cavite', 'GMA', 'San Gabriel', 'Area 1')"><i class="fas fa-edit text-primary"></i></button>
                                    <button class="btn btn-sm btn-light text-danger" onclick="deleteUser('user-3', 'Patrick Admin')"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal (Add / Edit) -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pt-4 pb-0 justify-content-center text-center">
                    <div>
                        <img src="{{ asset('image/1.png') }}" alt="Big Boss Car Rental" style="max-height: 60px;">
                        <h4 class="fw-bold mt-2 text-uppercase" id="modalTitle">Register <span class="text-danger">Account</span></h4>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <form id="userAccountForm">
                        <div class="row g-4">
                            <div class="col-md-6 border-end">
                                <p class="section-title-custom"><i class="fas fa-user-circle me-2"></i> Personal Information</p>
                                <div class="mb-3">
                                    <label class="form-label-custom">Full Name</label>
                                    <input type="text" id="m_name" class="form-control input-custom" placeholder="Juan Dela Cruz" required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label-custom">Age</label>
                                        <input type="number" id="m_age" class="form-control input-custom" placeholder="20">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label-custom">Gender</label>
                                        <select id="m_gender" class="form-select input-custom">
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-custom">Email Address</label>
                                    <input type="email" id="m_email" class="form-control input-custom" placeholder="juan@example.com" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-custom">Contact Number</label>
                                    <input type="text" id="m_phone" class="form-control input-custom" placeholder="09123456789">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <p class="section-title-custom"><i class="fas fa-shield-alt me-2"></i> Security & Address</p>
                                <div class="mb-3">
                                    <label class="form-label-custom" id="passLabel">Password</label>
                                    <input type="password" id="m_pass" class="form-control input-custom" placeholder="********">
                                    <input type="password" id="m_pass_confirmation" class="form-control input-custom mt-2" placeholder="Confirm password">
                                    <small class="text-muted d-block" id="passHelp" style="font-size: 11px;">Password requirements:</small>
                                    <ul id="passwordRules" class="password-rules">
                                        <li data-rule="length"><i class="fas fa-circle-xmark"></i> At least 8 characters</li>
                                        <li data-rule="uppercase"><i class="fas fa-circle-xmark"></i> At least 1 uppercase letter</li>
                                        <li data-rule="number"><i class="fas fa-circle-xmark"></i> At least 1 number</li>
                                        <li data-rule="special"><i class="fas fa-circle-xmark"></i> At least 1 special character</li>
                                        <li data-rule="match"><i class="fas fa-circle-xmark"></i> Passwords must match</li>
                                    </ul>
                                    <small class="text-danger d-block" id="passError" style="font-size: 11px;"></small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-custom">Province / City / Brgy</label>
                                    <input type="text" id="m_prov" class="form-control input-custom mb-2" placeholder="Province">
                                    <input type="text" id="m_city" class="form-control input-custom mb-2" placeholder="City">
                                    <input type="text" id="m_brgy" class="form-control input-custom" placeholder="Barangay">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label-custom">Account Role</label>
                                    <select id="m_role" class="form-select input-custom">
                                        <option value="User">User</option>
                                        <option value="Admin">Admin</option>
                                    </select>
                                </div>
                                <button type="submit" id="btnSubmit" class="btn btn-danger w-100 rounded-pill fw-bold py-3 mt-2 text-uppercase">Create Account</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-dark text-white py-3 rounded-top-4">
                    <h6 class="modal-title fw-bold text-uppercase mb-0 small">User Profile Details</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold shadow-sm mb-2" style="width: 70px; height: 70px; font-size: 26px;" id="d_initials"></div>
                    <h5 id="d_name" class="fw-bold mb-0"></h5>
                    <div id="d_meta" class="small text-muted mb-3"></div>
                    <span id="d_role_badge" class="badge rounded-pill px-3 py-2 mb-4"></span>
                    
                    <div class="row text-start g-3 border-top pt-3">
                        <div class="col-6">
                            <label class="form-label-custom">Email</label>
                            <p id="d_email" class="fw-bold small mb-0"></p>
                        </div>
                        <div class="col-6">
                            <label class="form-label-custom">Phone</label>
                            <p id="d_phone" class="fw-bold small mb-0"></p>
                        </div>
                        <div class="col-12">
                            <label class="form-label-custom text-danger">Complete Address</label>
                            <p id="d_address" class="fw-bold small mb-0"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CUSTOM CONFIRM MODAL WITH 3S COUNTDOWN -->
    <div class="modal fade" id="bdConfirmModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
            <div class="modal-content rounded-4 shadow border-0">
                <div class="modal-body p-4 text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:56px; height:56px; border-radius:50%; background:#fff5f5;">
                        <i class="fas fa-question text-danger" style="font-size:1.4rem;"></i>
                    </div>
                    <p class="fw-bold mb-4" id="bdConfirmMessage" style="font-size:0.95rem;">Are you sure?</p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light border fw-bold rounded-pill px-4" id="bdConfirmCancelBtn">Cancel</button>
                        <button type="button" class="btn btn-danger fw-bold rounded-pill px-4" id="bdConfirmOkBtn" disabled>Confirm (3s)</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="bd-toast-container" id="bdToastContainer"></div>

    <script>
        function updatePasswordRules() {
            const password = document.getElementById('m_pass').value;
            const confirmation = document.getElementById('m_pass_confirmation').value;
            const rules = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password),
                match: confirmation !== '' && password === confirmation
            };
            Object.entries(rules).forEach(function ([name, valid]) {
                const item = document.querySelector('#passwordRules [data-rule="' + name + '"]');
                item.classList.toggle('valid', valid);
                item.querySelector('i').className = valid
                    ? 'fas fa-circle-check'
                    : 'fas fa-circle-xmark';
            });
        }

        document.getElementById('m_pass').addEventListener('input', updatePasswordRules);
        document.getElementById('m_pass_confirmation').addEventListener('input', updatePasswordRules);

        function filterUsers() {
            let input = document.getElementById('userSearch').value.toLowerCase();
            let rows = document.getElementById('userTable').getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = rows[i].innerText.toLowerCase().includes(input) ? "" : "none";
            }
        }

        // ================= SUCCESS / CANCEL TOAST NOTIFICATIONS =================
        function showToast(message, type) {
            type = type || 'success';
            const container = document.getElementById('bdToastContainer');
            if (!container) return;

            const icon = type === 'success' ? 'fa-check-circle' : (type === 'cancel' ? 'fa-times-circle' : 'fa-exclamation-circle');
            const toast = document.createElement('div');
            toast.className = 'bd-toast ' + type;
            toast.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span>';
            container.appendChild(toast);

            requestAnimationFrame(function() { toast.classList.add('show'); });

            setTimeout(function() {
                toast.classList.remove('show');
                setTimeout(function() { toast.remove(); }, 250);
            }, 2800);
        }

        // ================= CUSTOM CONFIRM MODAL WITH 3-SECOND COUNTDOWN =================
        let bdConfirmModalInstance = null;
        let bdConfirmResolve = null;
        let countdownTimer = null;

        function showConfirm(message) {
            return new Promise(function(resolve) {
                if (typeof bootstrap === 'undefined') {
                    resolve(window.confirm(message));
                    return;
                }
                document.getElementById('bdConfirmMessage').innerText = message;
                bdConfirmResolve = resolve;

                const okBtn = document.getElementById('bdConfirmOkBtn');
                okBtn.disabled = true;
                let timeLeft = 3;
                okBtn.innerText = `Confirm (${timeLeft}s)`;

                try {
                    if (!bdConfirmModalInstance) {
                        bdConfirmModalInstance = new bootstrap.Modal(document.getElementById('bdConfirmModal'));
                    }
                    bdConfirmModalInstance.show();
                } catch (error) {
                    resolve(window.confirm(message));
                    return;
                }

                // Start 3-second countdown
                clearInterval(countdownTimer);
                countdownTimer = setInterval(function() {
                    timeLeft--;
                    if (timeLeft > 0) {
                        okBtn.innerText = `Confirm (${timeLeft}s)`;
                    } else {
                        clearInterval(countdownTimer);
                        okBtn.disabled = false;
                        okBtn.innerText = 'Confirm';
                    }
                }, 1000);

                setTimeout(function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    const ownBackdrop = backdrops[backdrops.length - 1];
                    if (ownBackdrop) ownBackdrop.style.zIndex = 1085;
                }, 0);
            });
        }

        document.getElementById('bdConfirmOkBtn').addEventListener('click', function() {
            if (this.disabled) return;
            clearInterval(countdownTimer);
            bdConfirmModalInstance.hide();
            if (bdConfirmResolve) { bdConfirmResolve(true); bdConfirmResolve = null; }
        });

        document.getElementById('bdConfirmCancelBtn').addEventListener('click', function() {
            clearInterval(countdownTimer);
            bdConfirmModalInstance.hide();
            if (bdConfirmResolve) { bdConfirmResolve(false); bdConfirmResolve = null; }
        });

        const serverUsers = @json($users->getCollection()->values());

        function renderServerUsers() {
            const tbody = document.querySelector('#userTable tbody');
            if (!tbody) return;
            tbody.innerHTML = serverUsers.map(function (user) {
                const initials = user.name.split(' ').map(function (part) { return part[0]; }).join('').slice(0, 2).toUpperCase();
                const role = (user.role || 'user').toLowerCase();
                return '<tr id="user-' + user.id + '">' +
                    '<td><div class="d-flex align-items-center"><div class="bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">' + initials + '</div><div><span class="fw-bold d-block">' + escapeHtml(user.name) + '</span><small class="text-muted">' + escapeHtml(user.email) + '</small></div></div></td>' +
                    '<td>' + escapeHtml(user.email) + '</td>' +
                    '<td><span class="badge rounded-pill px-3 py-2 ' + (role === 'admin' ? 'bg-danger' : 'bg-dark') + ' fw-normal">' + (role === 'admin' ? 'Admin' : 'User') + '</span></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-light" data-action="view" data-user-id="' + user.id + '"><i class="fas fa-eye text-success"></i></button> <button type="button" class="btn btn-sm btn-light" data-action="edit" data-user-id="' + user.id + '"><i class="fas fa-edit text-primary"></i></button> <button type="button" class="btn btn-sm btn-light text-danger" data-action="delete" data-user-id="' + user.id + '"><i class="fas fa-trash"></i></button></td>' +
                    '</tr>';
            }).join('');
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (character) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
            });
        }

        renderServerUsers();

        document.querySelector('#userTable tbody').addEventListener('click', function (event) {
            const button = event.target.closest('[data-action][data-user-id]');
            if (!button) return;
            const user = serverUsers.find(function (item) {
                return String(item.id) === button.dataset.userId;
            });
            if (!user) return;
            const role = (user.role || 'user').toLowerCase() === 'admin' ? 'Admin' : 'User';
            const details = [
                user.name,
                user.email,
                user.contact_number || '',
                role,
                '',
                '',
                user.province || '',
                user.city || '',
                user.barangay || '',
                user.address || ''
            ];
            if (button.dataset.action === 'view') {
                viewDetails.apply(null, details);
            } else if (button.dataset.action === 'edit') {
                editUser.apply(null, details.concat(user.id));
            } else if (button.dataset.action === 'delete') {
                deleteUser('user-' + user.id, user.name, user.id);
            }
        });

        // Delete User with 3s Count-down on Confirm Modal
        async function deleteUser(rowId, name, userId) {
            if (!(await showConfirm('Confirm delete for ' + name + '?'))) {
                showToast('Cancelled.', 'cancel');
                return;
            }
            const response = await fetch('{{ url('/admin/users') }}/' + userId, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({_method: 'DELETE'})
            });
            if (!response.ok) {
                const error = await response.json().catch(function () { return {}; });
                showToast(error.message || 'User could not be deleted.', 'error');
                return;
            }
            showToast('User deleted successfully!', 'success');
            setTimeout(function () { window.location.reload(); }, 700);
        }

        function resetForm() {
            document.getElementById('modalTitle').innerHTML = 'Register <span class="text-danger">Account</span>';
            document.getElementById('btnSubmit').innerText = 'CREATE ACCOUNT';
            document.getElementById('passLabel').innerText = 'Password';
            document.getElementById('passHelp').innerText = '*Required for new accounts';
            document.getElementById('userAccountForm').reset();
            window.isEditing = false;
            updatePasswordRules();
        }

        function editUser(name, email, phone, role, age, gender, prov, city, brgy, street, userId = null) {
            document.getElementById('modalTitle').innerHTML = 'Edit <span class="text-danger">Account</span>';
            document.getElementById('btnSubmit').innerText = 'UPDATE ACCOUNT';
            document.getElementById('passLabel').innerText = 'Change Password';
            document.getElementById('passHelp').innerText = '*Leave blank to keep current password';
            
            document.getElementById('m_name').value = name;
            document.getElementById('m_email').value = email;
            document.getElementById('m_phone').value = phone;
            document.getElementById('m_role').value = role;
            document.getElementById('m_age').value = age;
            document.getElementById('m_gender').value = gender;
            document.getElementById('m_prov').value = prov;
            document.getElementById('m_city').value = city;
            document.getElementById('m_brgy').value = brgy;
            document.getElementById('m_pass').value = ""; 
            document.getElementById('m_pass_confirmation').value = "";
            updatePasswordRules();

            window.isEditing = true;
            window.editingUserId = userId;
            openAdminModal('userModal');
        }

        // Form Submit Handler for Add / Edit
        document.getElementById('userAccountForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const actionText = window.isEditing ? 'update this account' : 'register this new account';
            
            if (!(await showConfirm('Are you sure you want to ' + actionText + '?'))) {
                showToast('Cancelled.', 'cancel');
                return;
            }

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('name', document.getElementById('m_name').value);
            formData.append('email', document.getElementById('m_email').value);
            formData.append('contact_number', document.getElementById('m_phone').value);
            formData.append('role', document.getElementById('m_role').value.toLowerCase());
            const password = document.getElementById('m_pass').value;
            const passwordConfirmation = document.getElementById('m_pass_confirmation').value;
            const passwordIsValid = password.length >= 8
                && /[A-Z]/.test(password)
                && /[0-9]/.test(password)
                && /[^A-Za-z0-9]/.test(password);
            const passwordRequired = !window.isEditing || password !== '';
            const passError = document.getElementById('passError');
            passError.textContent = '';
            if (passwordRequired && (!passwordIsValid || password !== passwordConfirmation)) {
                passError.textContent = password !== passwordConfirmation
                    ? 'Passwords must match.'
                    : 'Password needs 8 characters, an uppercase letter, a number, and a special character.';
                showToast(passError.textContent, 'error');
                return;
            }
            if (password) {
                formData.append('password', password);
                formData.append('password_confirmation', passwordConfirmation);
            }
            if (window.isEditing) formData.append('_method', 'PATCH');
            const endpoint = window.isEditing ? '{{ url('/admin/users') }}/' + window.editingUserId : '{{ route('admin.users.store') }}';
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                const error = await response.json().catch(function () { return {}; });
                showToast(error.message || 'Account could not be saved. Check the form fields and try again.', 'error');
                return;
            }

            const userModalEl = document.getElementById('userModal');
            const userModalInstance = bootstrap.Modal.getInstance(userModalEl);
            if (userModalInstance) userModalInstance.hide();

            const successMsg = window.isEditing ? 'Account updated successfully!' : 'Account registered successfully!';
            showToast(successMsg, 'success');
            setTimeout(function () { window.location.reload(); }, 900);
        });

        function viewDetails(name, email, phone, role, age, gender, prov, city, brgy, street) {
            document.getElementById('d_name').innerText = name;
            document.getElementById('d_meta').innerText = `${age} yrs old | ${gender}`;
            document.getElementById('d_email').innerText = email;
            document.getElementById('d_phone').innerText = phone;
            document.getElementById('d_address').innerText = `${street}, ${brgy}, ${city}, ${prov}`;
            document.getElementById('d_initials').innerText = name.split(' ').map(n => n[0]).join('').toUpperCase();
            
            let badge = document.getElementById('d_role_badge');
            badge.innerText = role;
            badge.className = role === 'Admin' ? 'badge rounded-pill bg-danger' : 'badge rounded-pill bg-dark';
            
            openAdminModal('detailsModal');
        }

        function openAdminModal(id) {
            const modal = document.getElementById(id);
            if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal === 'function') {
                try {
                    new bootstrap.Modal(modal).show();
                    return;
                } catch (error) {
                    // Use the simple fallback below when Bootstrap cannot initialize.
                }
            }
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.removeAttribute('aria-hidden');
            modal.setAttribute('role', 'dialog');
        }

        window.deleteUser = deleteUser;
        window.editUser = editUser;
        window.viewDetails = viewDetails;
        window.resetForm = resetForm;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.password-toggle')
</body>
</html>