<?php
/**
 * Sirajganj Road Chowrasta High School - Teacher Management (CRUD)
 */

$pageTitle = 'Teacher Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');

// ---------------------------------------------------------------------
// 1. DELETE ACTION
// ---------------------------------------------------------------------
if ($action === 'delete') {
    $deleteId = getInt('id');
    if ($deleteId > 0 && verifyCSRFToken()) {
        try {
            $teacher = Database::fetchOne("SELECT t.*, u.full_name, u.id as uid FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.id = ?", [$deleteId]);
            if ($teacher) {
                // Delete user (cascade removes teacher record)
                Database::execute("DELETE FROM users WHERE id = ?", [$teacher['uid']]);
                logActivity('teacher_delete', "Deleted teacher account: {$teacher['full_name']} (Code: {$teacher['teacher_code']})");
                setFlash('success', 'Teacher account deleted successfully.');
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting teacher: ' . $e->getMessage());
        }
    }
    redirect(url('admin/teachers/index.php'));
}

// ---------------------------------------------------------------------
// 2. CREATE / UPDATE (POST)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF verification expired.');
    } else {
        $teacherId = getInt('teacher_id', 'POST');
        $fullName = getString('full_name', 'POST');
        $username = getString('username', 'POST');
        $email = getString('email', 'POST');
        $phone = getString('phone', 'POST');
        $password = $_POST['password'] ?? '';
        $designation = getString('designation', 'POST');
        $qualification = getString('qualification', 'POST');
        $specialization = getString('specialization', 'POST');
        $gender = getString('gender', 'POST');
        $bloodGroup = getString('blood_group', 'POST');
        $joiningDate = getString('joining_date', 'POST');
        $address = getString('address', 'POST');
        $bio = getString('bio', 'POST');
        $orderPriority = getInt('order_priority', 'POST');

        $errors = [];
        if (empty($fullName)) $errors[] = 'Teacher full name is required.';
        if (empty($username)) $errors[] = 'Username is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if (empty($designation)) $errors[] = 'Official designation is required.';
        if (empty($qualification)) $errors[] = 'Academic qualification is required.';
        if (empty($joiningDate)) $errors[] = 'Joining date is required.';

        // Check username/email uniqueness
        $userId = 0;
        if ($teacherId > 0) {
            $existing = Database::fetchOne("SELECT user_id FROM teachers WHERE id = ?", [$teacherId]);
            $userId = $existing ? (int)$existing['user_id'] : 0;
        }

        $dup = Database::fetchOne("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?", [$username, $email, $userId]);
        if ($dup) {
            $errors[] = 'The username or email is already in use by another account.';
        }

        if ($teacherId == 0 && empty($password)) {
            $errors[] = 'Initial login password is required for new teacher account.';
        }

        if (empty($errors)) {
            Database::beginTransaction();
            try {
                if ($teacherId > 0) {
                    // Update User
                    Database::execute(
                        "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ? WHERE id = ?",
                        [$fullName, $username, $email, $phone, $userId]
                    );

                    // If new password provided
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        Database::execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
                    }

                    // Update Teacher profile
                    Database::execute(
                        "UPDATE teachers SET designation = ?, qualification = ?, specialization = ?, gender = ?, blood_group = ?, joining_date = ?, address = ?, bio = ?, order_priority = ? WHERE id = ?",
                        [$designation, $qualification, $specialization, $gender, $bloodGroup, $joiningDate, $address, $bio, $orderPriority, $teacherId]
                    );

                    logActivity('teacher_update', "Updated teacher profile: {$fullName}");
                    setFlash('success', 'Teacher profile updated successfully.');
                } else {
                    // Create User (Role ID 2 = Teacher)
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $newUserId = (int)Database::insert(
                        "INSERT INTO users (role_id, username, email, password, full_name, phone, status) 
                         VALUES (2, ?, ?, ?, ?, ?, 'active')",
                        [$username, $email, $hash, $fullName, $phone]
                    );

                    // Generate Teacher Code: TCH-0XX
                    $teacherCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM teachers");
                    $teacherCode = 'TCH-' . str_pad((string)($teacherCount + 1), 3, '0', STR_PAD_LEFT);

                    Database::insert(
                        "INSERT INTO teachers (user_id, teacher_code, designation, qualification, specialization, gender, blood_group, joining_date, address, bio, order_priority) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$newUserId, $teacherCode, $designation, $qualification, $specialization, $gender, $bloodGroup, $joiningDate, $address, $bio, $orderPriority]
                    );

                    logActivity('teacher_create', "Registered new teacher: {$fullName} (Code: {$teacherCode})");
                    setFlash('success', "Teacher account registered successfully! Teacher Code: {$teacherCode}");
                }

                Database::commit();
                redirect(url('admin/teachers/index.php'));
            } catch (Exception $ex) {
                Database::rollback();
                setFlash('danger', 'Error saving teacher: ' . $ex->getMessage());
            }
        } else {
            setFlash('danger', implode('<br>', $errors));
        }
    }
}

// ---------------------------------------------------------------------
// 3. EDIT PREPARATION
// ---------------------------------------------------------------------
$editTeacher = null;
if ($action === 'edit') {
    $editId = getInt('id');
    $editTeacher = Database::fetchOne(
        "SELECT t.*, u.full_name, u.username, u.email, u.phone 
         FROM teachers t 
         JOIN users u ON t.user_id = u.id 
         WHERE t.id = ?",
        [$editId]
    );
}

// ---------------------------------------------------------------------
// 4. LISTING
// ---------------------------------------------------------------------
$teachersList = Database::fetchAll(
    "SELECT t.*, u.full_name, u.username, u.email, u.phone, u.status 
     FROM teachers t 
     JOIN users u ON t.user_id = u.id 
     ORDER BY t.order_priority ASC, t.id ASC"
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Teacher & Faculty Management</h4>
        <p class="text-muted small mb-0">Create credentials, manage academic profiles, and monitor teaching assignments.</p>
    </div>
    <div>
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
            <a href="<?= url('admin/teachers/index.php?action=create') ?>" class="btn btn-success rounded-pill px-4 me-2">
                <i class="fas fa-plus me-1"></i> Register New Teacher
            </a>
            <a href="<?= url('admin/teachers/assignments.php') ?>" class="btn btn-outline-primary rounded-pill px-3">
                <i class="fas fa-tasks me-1"></i> Class Assignments
            </a>
        <?php else: ?>
            <a href="<?= url('admin/teachers/index.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                &larr; Back to Teacher List
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ==================== FORM ==================== -->
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">
            <i class="fas <?= $action === 'edit' ? 'fa-edit text-warning' : 'fa-user-plus text-success' ?> me-2"></i>
            <?= $action === 'edit' ? 'Edit Faculty Account & Details' : 'Register New Faculty Member' ?>
        </h5>

        <form action="<?= url('admin/teachers/index.php') ?>" method="POST" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="teacher_id" value="<?= $editTeacher ? (int)$editTeacher['id'] : 0 ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($editTeacher['full_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Designation <span class="text-danger">*</span></label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Assistant Teacher" value="<?= e($editTeacher['designation'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">System Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?= e($editTeacher['username'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Official Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= e($editTeacher['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Mobile Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($editTeacher['phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Account Password <?= $action === 'edit' ? '(Leave blank to retain)' : '<span class="text-danger">*</span>' ?></label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" <?= $action === 'create' ? 'required' : '' ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Academic Qualification <span class="text-danger">*</span></label>
                    <input type="text" name="qualification" class="form-control" placeholder="e.g. M.Sc. in Mathematics, B.Ed." value="<?= e($editTeacher['qualification'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Primary Specialization</label>
                    <input type="text" name="specialization" class="form-control" placeholder="e.g. Mathematics / Physics" value="<?= e($editTeacher['specialization'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male" <?= ($editTeacher['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($editTeacher['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($editTeacher['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Unknown</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($editTeacher['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Joining Date <span class="text-danger">*</span></label>
                    <input type="date" name="joining_date" class="form-control" value="<?= e($editTeacher['joining_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Display Priority (Order)</label>
                    <input type="number" name="order_priority" class="form-control" min="0" value="<?= (int)($editTeacher['order_priority'] ?? 10) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Residential Address</label>
                    <input type="text" name="address" class="form-control" value="<?= e($editTeacher['address'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Short Biography / Message</label>
                    <input type="text" name="bio" class="form-control" placeholder="Brief teaching philosophy" value="<?= e($editTeacher['bio'] ?? '') ?>">
                </div>
            </div>

            <div class="text-end border-top pt-3">
                <a href="<?= url('admin/teachers/index.php') ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill me-2">Cancel</a>
                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold">
                    <i class="fas fa-save me-1"></i> <?= $action === 'edit' ? 'Update Faculty Details' : 'Create Teacher Account' ?>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- ==================== TEACHER LIST TABLE ==================== -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Teacher Name</th>
                    <th>Designation</th>
                    <th>Subject Specialty</th>
                    <th>Qualification</th>
                    <th>Email / Phone</th>
                    <th>Serving Since</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($teachersList)): ?>
                    <?php foreach ($teachersList as $t): ?>
                        <tr>
                            <td class="fw-bold text-success"><?= e($t['teacher_code']) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($t['full_name']) ?></div>
                                <small class="text-muted">@<?= e($t['username']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($t['designation']) ?></span></td>
                            <td><span class="text-primary fw-semibold"><?= e($t['specialization'] ?: 'General') ?></span></td>
                            <td class="small text-muted"><?= e($t['qualification']) ?></td>
                            <td class="small">
                                <div><i class="fas fa-envelope text-muted me-1"></i> <?= e($t['email']) ?></div>
                                <div><i class="fas fa-phone text-muted me-1"></i> <?= e($t['phone'] ?: '-') ?></div>
                            </td>
                            <td class="small text-muted"><?= formatDate($t['joining_date'], 'M Y') ?></td>
                            <td class="text-end">
                                <a href="<?= url('admin/teachers/index.php?action=edit&id=' . $t['id']) ?>" class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= url('admin/teachers/index.php?action=delete&id=' . $t['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-circle btn-confirm-delete" 
                                   data-confirm="Are you sure you want to delete <?= e($t['full_name']) ?>? Their login account and assignments will also be deleted." 
                                   title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No teachers registered.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
