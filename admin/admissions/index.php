<?php
/**
 * Sirajganj Road Chowrasta High School - Online Admission Management
 */

$pageTitle = 'Admission Applications Management';
require_once __DIR__ . '/../includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$action = getString('action');
$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");

// 1. Approve Application & Enroll Student
if ($action === 'approve') {
    $admId = getInt('id');
    if ($admId > 0 && verifyCSRFToken()) {
        $adm = Database::fetchOne("SELECT * FROM admissions WHERE id = ?", [$admId]);
        if ($adm) {
            Database::beginTransaction();
            try {
                // Update admission record
                Database::execute(
                    "UPDATE admissions SET status = 'approved', reviewed_by = ? WHERE id = ?",
                    [$_SESSION['user_id'], $admId]
                );

                // Auto enroll into students table if not yet enrolled
                $existingStudent = Database::fetchOne("SELECT id FROM students WHERE admission_no = ?", [$adm['application_no']]);
                if (!$existingStudent) {
                    // Create Guardian
                    $guardianId = (int)Database::insert(
                        "INSERT INTO guardians (father_name, father_phone, mother_name, guardian_name, guardian_relation, guardian_phone, present_address, permanent_address) 
                         VALUES (?, ?, ?, ?, 'Parent', ?, ?, ?)",
                        [$adm['father_name'], $adm['father_phone'], $adm['mother_name'], $adm['father_name'], $adm['guardian_phone'], $adm['present_address'], $adm['permanent_address']]
                    );

                    // Name split
                    $parts = explode(' ', trim($adm['student_name']));
                    $lastName = count($parts) > 1 ? array_pop($parts) : 'Student';
                    $firstName = implode(' ', $parts) ?: $adm['student_name'];

                    // Create Student
                    $newStudentId = (int)Database::insert(
                        "INSERT INTO students (guardian_id, admission_no, first_name, last_name, dob, gender, blood_group, religion, photo, admission_date, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'active')",
                        [$guardianId, $adm['application_no'], $firstName, $lastName, $adm['dob'], $adm['gender'], $adm['blood_group'], $adm['religion'], $adm['photo'] ?: 'assets/images/default-student.png']
                    );

                    // Find first available section for this class
                    $sec = Database::fetchOne("SELECT id FROM sections WHERE class_id = ? AND status = 'active' ORDER BY section_name ASC LIMIT 1", [$adm['desired_class_id']]);
                    $secId = $sec ? (int)$sec['id'] : 1;

                    // Next available roll number
                    $maxRoll = (int)Database::fetchColumn("SELECT MAX(roll_no) FROM enrollments WHERE session_id = ? AND class_id = ? AND section_id = ?", [$sessionId, $adm['desired_class_id'], $secId]);
                    $nextRoll = $maxRoll + 1;

                    // Create Enrollment
                    Database::insert(
                        "INSERT INTO enrollments (student_id, session_id, class_id, section_id, roll_no, status) 
                         VALUES (?, ?, ?, ?, ?, 'active')",
                        [$newStudentId, $sessionId, $adm['desired_class_id'], $secId, $nextRoll]
                    );
                }

                Database::commit();
                logActivity('admission_approved', "Approved and enrolled applicant: {$adm['student_name']} (App: {$adm['application_no']})");
                setFlash('success', "Application {$adm['application_no']} APPROVED and student successfully enrolled in active session!");
            } catch (Exception $e) {
                Database::rollback();
                setFlash('danger', 'Error approving application: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/admissions/index.php'));
}

// 2. Reject Application
if ($action === 'reject') {
    $admId = getInt('id');
    if ($admId > 0 && verifyCSRFToken()) {
        Database::execute("UPDATE admissions SET status = 'rejected', reviewed_by = ? WHERE id = ?", [$_SESSION['user_id'], $admId]);
        setFlash('warning', 'Application marked as rejected.');
    }
    redirect(url('admin/admissions/index.php'));
}

// 3. Delete Application
if ($action === 'delete') {
    $admId = getInt('id');
    if ($admId > 0 && verifyCSRFToken()) {
        Database::execute("DELETE FROM admissions WHERE id = ?", [$admId]);
        setFlash('success', 'Application record removed.');
    }
    redirect(url('admin/admissions/index.php'));
}

// Filter & Listings
$filterStatus = getString('status');
$filterClass = getInt('class_id');
$search = getString('search');
$page = max(1, getInt('page'));

$where = "WHERE 1=1";
$params = [];

if ($filterStatus && in_array($filterStatus, ['pending', 'approved', 'rejected'])) {
    $where .= " AND a.status = ?";
    $params[] = $filterStatus;
}
if ($filterClass > 0) {
    $where .= " AND a.desired_class_id = ?";
    $params[] = $filterClass;
}
if (!empty($search)) {
    $where .= " AND (a.student_name LIKE ? OR a.application_no LIKE ? OR a.guardian_phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$baseQuery = "SELECT a.*, c.class_name, u.full_name as reviewer_name 
              FROM admissions a 
              JOIN classes c ON a.desired_class_id = c.id 
              LEFT JOIN users u ON a.reviewed_by = u.id 
              {$where} 
              ORDER BY a.id DESC";

$pagination = paginate($baseQuery, $params, $page, 15);
$admissionsList = $pagination['data'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Online Admission Applications</h4>
        <p class="text-muted small mb-0">Review online submissions, approve candidates, and convert to enrolled students.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-print me-1"></i> Print Applications Report
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form action="<?= url('admin/admissions/index.php') ?>" method="GET" class="row g-3 align-items-center">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search applicant name, phone, app no..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">-- All Statuses --</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved & Enrolled</option>
                <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="class_id" class="form-select">
                <option value="">-- All Target Classes --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClass === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-success rounded-pill px-4 flex-grow-1">Filter</button>
            <?php if ($filterStatus || $filterClass || $search): ?>
                <a href="<?= url('admin/admissions/index.php') ?>" class="btn btn-outline-secondary rounded-pill">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Applications Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>App No</th>
                    <th>Applicant Name</th>
                    <th>Desired Class</th>
                    <th>Guardian Details</th>
                    <th>Applied Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($admissionsList)): ?>
                    <?php foreach ($admissionsList as $adm): ?>
                        <tr>
                            <td class="fw-bold text-success"><?= e($adm['application_no']) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($adm['student_name']) ?></div>
                                <small class="text-muted">DOB: <?= formatDate($adm['dob']) ?> | <?= e($adm['gender']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($adm['class_name']) ?></span></td>
                            <td>
                                <div class="small fw-semibold"><?= e($adm['father_name']) ?></div>
                                <small class="text-muted"><i class="fas fa-phone-alt me-1"></i> <?= e($adm['guardian_phone']) ?></small>
                            </td>
                            <td class="small text-muted"><?= formatDate($adm['created_at'], 'd M Y') ?></td>
                            <td>
                                <span class="badge <?= $adm['status'] === 'approved' ? 'bg-success' : ($adm['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-danger') ?> rounded-pill px-3 py-1">
                                    <?= e($adm['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($adm['status'] === 'pending'): ?>
                                    <a href="<?= url("admin/admissions/index.php?action=approve&id={$adm['id']}&csrf_token=" . generateCSRFToken()) ?>" 
                                       class="btn btn-sm btn-success rounded-pill px-3 me-1" 
                                       title="Approve & Enroll">
                                        <i class="fas fa-check me-1"></i> Approve
                                    </a>
                                    <a href="<?= url("admin/admissions/index.php?action=reject&id={$adm['id']}&csrf_token=" . generateCSRFToken()) ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-pill px-3 me-1" 
                                       title="Reject">
                                        Reject
                                    </a>
                                <?php endif; ?>
                                <a href="<?= url("admin/admissions/index.php?action=delete&id={$adm['id']}&csrf_token=" . generateCSRFToken()) ?>" 
                                   class="btn btn-sm btn-outline-secondary rounded-circle btn-confirm-delete" 
                                   data-confirm="Delete application record for <?= e($adm['student_name']) ?>?" 
                                   title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No admission applications found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <?php
            $baseUrl = url("admin/admissions/index.php?status={$filterStatus}&class_id={$filterClass}&search=" . urlencode($search));
            echo renderPagination($pagination, $baseUrl);
            ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
