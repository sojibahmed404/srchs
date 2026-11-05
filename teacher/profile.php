<?php
/**
 * Teacher Profile & Account Settings
 * Sirajganj Road Chowrasta High School
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTeacher();

$db = Database::getInstance();
$currentTeacher = getCurrentTeacher();
$teacherId = (int)$currentTeacher['id'];
$userId = (int)$_SESSION['user_id'];

// Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security token invalid.');
        redirect('profile.php');
    }

    $phone = cleanInput($_POST['phone'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $qualification = cleanInput($_POST['qualification'] ?? '');
    $specialization = cleanInput($_POST['specialization'] ?? '');
    $bloodGroup = cleanInput($_POST['blood_group'] ?? '');

    $photoPath = $currentTeacher['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['photo'], 'teacher', ['jpg', 'jpeg', 'png', 'webp']);
        if ($uploadResult['success']) {
            if (!empty($photoPath)) {
                deleteUploadedFile($photoPath);
            }
            $photoPath = $uploadResult['filename'];
        } else {
            setFlash('danger', 'Photo upload failed: ' . $uploadResult['error']);
            redirect('profile.php');
        }
    }

    try {
        $db->query(
            "UPDATE teachers 
             SET phone = ?, address = ?, qualification = ?, specialization = ?, blood_group = ?, photo = ?, updated_at = NOW() 
             WHERE id = ?",
            [$phone, $address, $qualification, $specialization, $bloodGroup, $photoPath, $teacherId]
        );

        logActivity($userId, 'update_profile', "Teacher {$currentTeacher['name']} updated profile information.");
        setFlash('success', 'Profile updated successfully.');
    } catch (Exception $e) {
        setFlash('danger', 'Database error: ' . $e->getMessage());
    }

    redirect('profile.php');
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security token invalid.');
        redirect('profile.php#security');
    }

    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        setFlash('danger', 'All password fields are required.');
        redirect('profile.php#security');
    }

    if ($newPass !== $confirmPass) {
        setFlash('danger', 'New password and confirmation do not match.');
        redirect('profile.php#security');
    }

    if (strlen($newPass) < 8) {
        setFlash('danger', 'New password must be at least 8 characters long.');
        redirect('profile.php#security');
    }

    $result = changePassword($userId, $currentPass, $newPass);
    if ($result['success']) {
        setFlash('success', 'Password changed successfully! Please use your new password for next logins.');
    } else {
        setFlash('danger', $result['error']);
    }

    redirect('profile.php#security');
}

// Refresh teacher data
$teacher = $db->fetchOne("SELECT t.*, u.username, u.email, u.last_login FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?", [$teacherId]);

$pageTitle = "Teacher Profile";
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-user-gear text-primary me-2"></i>My Profile & Account</h4>
        <p class="text-muted small mb-0">Manage your faculty credentials and personal security settings</p>
    </div>
</div>

<?php displayFlash(); ?>

<div class="row g-4">
    <!-- Left Column: Profile Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4 mb-4">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <img src="<?= e(resolveUploadUrl($teacher['photo'], 'teacher')) ?>" 
                     alt="<?= e($teacher['name']) ?>" 
                     class="rounded-circle border border-3 border-primary shadow-sm" 
                     style="width: 130px; height: 130px; object-fit: cover;">
                <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success p-2">
                    <i class="fa-solid fa-check"></i>
                </span>
            </div>

            <h5 class="fw-bold mb-1 text-dark"><?= e($teacher['name']) ?></h5>
            <div class="text-muted small mb-1"><?= e($teacher['designation']) ?></div>
            <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-3">
                <?= e($teacher['department']) ?>
            </div>

            <div class="border-top pt-3 text-start small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Teacher ID:</span>
                    <span class="fw-bold"><?= e($teacher['teacher_code']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Username:</span>
                    <span class="fw-bold"><?= e($teacher['username']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Joined Date:</span>
                    <span class="fw-bold"><?= $teacher['joining_date'] ? date('d M, Y', strtotime($teacher['joining_date'])) : '—' ?></span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Blood Group:</span>
                    <span class="fw-bold text-danger"><?= e($teacher['blood_group'] ?: '—') ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Last Login:</span>
                    <span class="text-muted"><?= $teacher['last_login'] ? date('d M, Y h:i A', strtotime($teacher['last_login'])) : 'First login' ?></span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4 bg-light">
            <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-shield-halved text-success me-2"></i>Account Security</h6>
            <p class="text-muted small mb-0">Always use a strong password with a mix of letters, numbers, and symbols. Never share your credentials with students.</p>
        </div>
    </div>

    <!-- Right Column: Tabs (Details / Password) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white pt-3 px-4">
                <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-semibold" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab">
                            <i class="fa-solid fa-user-pen me-2"></i>Edit Profile
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                            <i class="fa-solid fa-key me-2"></i>Change Password
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="profileTabsContent">
                    <!-- Edit Profile Tab -->
                    <div class="tab-pane fade show active" id="edit" role="tabpanel">
                        <form method="POST" action="profile.php" enctype="multipart/form-data">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Full Name (English)</label>
                                    <input type="text" class="form-control" value="<?= e($teacher['name']) ?>" disabled>
                                    <div class="form-text">Official name changes require administrative assistance.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Official Email Address</label>
                                    <input type="email" class="form-control" value="<?= e($teacher['email']) ?>" disabled>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Contact Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($teacher['phone']) ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Blood Group</label>
                                    <select name="blood_group" class="form-select">
                                        <option value="">Select Blood Group</option>
                                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                                            <option value="<?= $bg ?>" <?= ($teacher['blood_group'] === $bg) ? 'selected' : '' ?>><?= $bg ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Educational Qualifications</label>
                                    <input type="text" name="qualification" class="form-control" value="<?= e($teacher['qualification']) ?>" placeholder="e.g. M.Sc. in Mathematics (Rajshahi University), B.Ed.">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Subject Specialization / Research Area</label>
                                    <input type="text" name="specialization" class="form-control" value="<?= e($teacher['specialization']) ?>" placeholder="e.g. Higher Mathematics, Geometry, Physics">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Residential Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?= e($teacher['address']) ?></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Update Profile Picture</label>
                                    <input type="file" name="photo" class="form-control" accept="image/*">
                                    <div class="form-text">Allowed formats: JPG, PNG, WEBP (Max 2MB). Recommended square aspect ratio.</div>
                                </div>

                                <div class="col-12 text-end pt-3">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <form method="POST" action="profile.php">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="row g-3" style="max-width: 600px;">
                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="current_password" id="curPass" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('curPass')">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="newPass" class="form-control" minlength="8" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('newPass')">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Must contain at least 8 characters.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold small">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" id="confPass" class="form-control" minlength="8" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confPass')">
                                            <i class="fa-regular fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-12 pt-3">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fa-solid fa-key me-1"></i> Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}

// Retain active tab on hash
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#security') {
        const securityTab = new bootstrap.Tab(document.getElementById('security-tab'));
        securityTab.show();
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
