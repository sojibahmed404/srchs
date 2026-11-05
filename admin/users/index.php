<?php
/**
 * Sirajganj Road Chowrasta High School - User Management
 */

$pageTitle = 'User Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');

// 1. Toggle Status (Active / Suspended)
if ($action === 'toggle_status') {
    $uId = getInt('id');
    if ($uId > 0 && verifyCSRFToken()) {
        if ($uId === (int)$_SESSION['user_id']) {
            setFlash('danger', 'You cannot suspend your own active administrator account.');
        } else {
            $user = Database::fetchOne("SELECT status, username FROM users WHERE id = ?", [$uId]);
            if ($user) {
                $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
                Database::execute("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $uId]);
                logActivity('user_status_toggle', "Changed status of {$user['username']} to {$newStatus}");
                setFlash('success', "User account {$user['username']} is now {$newStatus}.");
            }
        }
    }
    redirect(url('admin/users/index.php'));
}

// 2. Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $uId = getInt('user_id', 'POST');
        $newPass = $_POST['new_password'] ?? '';

        if (strlen($newPass) < 8) {
            setFlash('danger', 'New password must contain at least 8 characters.');
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            Database::execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $uId]);
            logActivity('admin_password_reset', "Admin reset password for user ID {$uId}");
            setFlash('success', 'Password updated successfully.');
        }
    }
    redirect(url('admin/users/index.php'));
}

// 3. Create Admin User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $username = getString('username', 'POST');
        $email = getString('email', 'POST');
        $fullName = getString('full_name', 'POST');
        $phone = getString('phone', 'POST');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
            setFlash('danger', 'All fields are required.');
        } else {
            $dup = Database::fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            if ($dup) {
                setFlash('danger', 'Username or email already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                Database::insert(
                    "INSERT INTO users (role_id, username, email, password, full_name, phone, status) 
                     VALUES (1, ?, ?, ?, ?, ?, 'active')",
                    [$username, $email, $hash, $fullName, $phone]
                );
                logActivity('admin_user_create', "Created administrator user: {$username}");
                setFlash('success', "Administrator account {$username} created.");
            }
        }
    }
    redirect(url('admin/users/index.php'));
}

$usersList = Database::fetchAll(
    "SELECT u.*, r.display_name as role_display 
     FROM users u 
     JOIN roles r ON u.role_id = r.id 
     ORDER BY u.role_id ASC, u.id ASC"
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">User Accounts & Access Control</h4>
        <p class="text-muted small mb-0">Manage system credentials, administrator profiles, and security statuses.</p>
    </div>
    <div>
        <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newAdminModal">
            <i class="fas fa-user-shield me-1"></i> Create Admin Account
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Contact Info</th>
                    <th>Last Login</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($usersList)): ?>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white me-2" style="width: 36px; height: 36px; background-color: <?= $u['role_id'] == 1 ? '#123B2A' : '#198754' ?>;">
                                        <i class="fas <?= $u['role_id'] == 1 ? 'fa-user-shield' : 'fa-chalkboard-teacher' ?> small"></i>
                                    </div>
                                    <div class="fw-bold text-dark"><?= e($u['full_name']) ?></div>
                                </div>
                            </td>
                            <td><code>@<?= e($u['username']) ?></code></td>
                            <td>
                                <span class="badge <?= $u['role_id'] == 1 ? 'bg-danger' : 'bg-success' ?> rounded-pill">
                                    <?= e($u['role_display']) ?>
                                </span>
                            </td>
                            <td class="small">
                                <div><i class="fas fa-envelope text-muted me-1"></i> <?= e($u['email']) ?></div>
                                <div><i class="fas fa-phone text-muted me-1"></i> <?= e($u['phone'] ?: '-') ?></div>
                            </td>
                            <td class="small text-muted"><?= $u['last_login'] ? formatDate($u['last_login'], 'd M, h:i A') : 'Never' ?></td>
                            <td>
                                <span class="badge <?= $u['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> text-capitalize">
                                    <?= e($u['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-circle me-1" 
                                        data-bs-toggle="modal" data-bs-target="#changePassModal" 
                                        data-id="<?= $u['id'] ?>" data-name="<?= e($u['username']) ?>" title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <a href="<?= url('admin/users/index.php?action=toggle_status&id=' . $u['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                       class="btn btn-sm btn-outline-secondary rounded-pill px-3" 
                                       title="Toggle Status">
                                        <?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== NEW ADMIN MODAL ==================== -->
<div class="modal fade" id="newAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-shield text-warning me-2"></i> Create Administrator Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/users/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="create_admin" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Administrator Md. Aminul" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. admin2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="admin2@srchs.edu.bd" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Mobile Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="017xxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== RESET PASSWORD MODAL ==================== -->
<div class="modal fade" id="changePassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-key text-warning me-2"></i> Reset User Password</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/users/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="reset_password" value="1">
                <input type="hidden" name="user_id" id="modalResetUserId">
                <div class="modal-body p-4">
                    <p class="text-muted small">Setting a new password for account: <strong class="text-dark" id="modalResetUsername"></strong></p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Password (Min 8 characters) <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passModal = document.getElementById('changePassModal');
    if (passModal) {
        passModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('modalResetUserId').value = button.getAttribute('data-id');
            document.getElementById('modalResetUsername').textContent = '@' + button.getAttribute('data-name');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
