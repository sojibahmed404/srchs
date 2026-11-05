<?php
/**
 * Sirajganj Road Chowrasta High School - Student Management (CRUD)
 */

$pageTitle = 'Student Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');
$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");

// ---------------------------------------------------------------------
// 1. DELETE ACTION
// ---------------------------------------------------------------------
if ($action === 'delete') {
    $deleteId = getInt('id');
    if ($deleteId > 0 && verifyCSRFToken()) {
        try {
            $student = Database::fetchOne("SELECT * FROM students WHERE id = ?", [$deleteId]);
            if ($student) {
                Database::execute("DELETE FROM students WHERE id = ?", [$deleteId]);
                logActivity('student_delete', "Deleted student: {$student['first_name']} {$student['last_name']} (Adm: {$student['admission_no']})");
                setFlash('success', 'Student record deleted successfully.');
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting student record: ' . $e->getMessage());
        }
    }
    redirect(url('admin/students/index.php'));
}

// ---------------------------------------------------------------------
// 2. CREATE / UPDATE ACTION (POST)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF validation token failed.');
    } else {
        $studentId = getInt('student_id', 'POST');
        $firstName = getString('first_name', 'POST');
        $lastName = getString('last_name', 'POST');
        $dob = getString('dob', 'POST');
        $gender = getString('gender', 'POST');
        $bloodGroup = getString('blood_group', 'POST');
        $religion = getString('religion', 'POST');
        $birthCertNo = getString('birth_certificate_no', 'POST');
        $phone = getString('phone', 'POST');
        $email = getString('email', 'POST');

        // Enrollment data
        $classId = getInt('class_id', 'POST');
        $sectionId = getInt('section_id', 'POST');
        $rollNo = getInt('roll_no', 'POST');

        // Guardian data
        $fatherName = getString('father_name', 'POST');
        $fatherPhone = getString('father_phone', 'POST');
        $motherName = getString('mother_name', 'POST');
        $guardianPhone = getString('guardian_phone', 'POST');
        $presentAddress = getString('present_address', 'POST');

        $errors = [];
        if (empty($firstName) || empty($lastName)) $errors[] = 'Student full name is required.';
        if (empty($dob)) $errors[] = 'Date of birth is required.';
        if ($classId <= 0 || $sectionId <= 0) $errors[] = 'Class and section selection is mandatory.';
        if ($rollNo <= 0) $errors[] = 'A valid positive roll number is required.';

        // Check roll uniqueness within class & section for the current session
        $rollConflict = Database::fetchOne(
            "SELECT e.id FROM enrollments e 
             WHERE e.session_id = ? AND e.class_id = ? AND e.section_id = ? AND e.roll_no = ? AND e.student_id != ?",
            [$sessionId, $classId, $sectionId, $rollNo, $studentId]
        );
        if ($rollConflict) {
            $errors[] = "Roll number {$rollNo} is already assigned in this class and section for this session.";
        }

        if (empty($errors)) {
            Database::beginTransaction();
            try {
                // Guardian handling
                $guardianId = null;
                if (!empty($fatherName) || !empty($guardianPhone)) {
                    $guardianId = (int)Database::insert(
                        "INSERT INTO guardians (father_name, father_phone, mother_name, guardian_name, guardian_relation, guardian_phone, present_address, permanent_address) 
                         VALUES (?, ?, ?, ?, 'Father', ?, ?, ?)",
                        [$fatherName ?: 'N/A', $fatherPhone, $motherName ?: 'N/A', $fatherName ?: 'Guardian', $guardianPhone ?: $fatherPhone, $presentAddress ?: 'Sirajganj', $presentAddress ?: 'Sirajganj']
                    );
                }

                if ($studentId > 0) {
                    // Update Student
                    Database::execute(
                        "UPDATE students SET first_name = ?, last_name = ?, dob = ?, gender = ?, blood_group = ?, religion = ?, birth_certificate_no = ?, phone = ?, email = ? WHERE id = ?",
                        [$firstName, $lastName, $dob, $gender, $bloodGroup, $religion, $birthCertNo, $phone, $email, $studentId]
                    );

                    // Update Enrollment
                    Database::execute(
                        "UPDATE enrollments SET class_id = ?, section_id = ?, roll_no = ? WHERE student_id = ? AND session_id = ?",
                        [$classId, $sectionId, $rollNo, $studentId, $sessionId]
                    );

                    logActivity('student_update', "Updated student: {$firstName} {$lastName} (Roll: {$rollNo})");
                    setFlash('success', 'Student record updated successfully.');
                } else {
                    // Create Student
                    $randomId = str_pad((string)mt_rand(10, 9999), 4, '0', STR_PAD_LEFT);
                    $admNo = 'SRCHS-' . date('Y') . '-' . $randomId;

                    $newStudentId = (int)Database::insert(
                        "INSERT INTO students (guardian_id, admission_no, first_name, last_name, dob, gender, blood_group, religion, birth_certificate_no, phone, email, admission_date, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                        [$guardianId ?: null, $admNo, $firstName, $lastName, $dob, $gender, $bloodGroup, $religion, $birthCertNo, $phone, $email, date('Y-m-d')]
                    );

                    // Create Enrollment
                    Database::insert(
                        "INSERT INTO enrollments (student_id, session_id, class_id, section_id, roll_no, status) 
                         VALUES (?, ?, ?, ?, ?, 'active')",
                        [$newStudentId, $sessionId, $classId, $sectionId, $rollNo]
                    );

                    logActivity('student_create', "Enrolled new student: {$firstName} {$lastName} (Adm: {$admNo}, Roll: {$rollNo})");
                    setFlash('success', "New student enrolled successfully! Admission No: {$admNo}");
                }

                Database::commit();
                redirect(url('admin/students/index.php'));
            } catch (Exception $ex) {
                Database::rollback();
                setFlash('danger', 'Error saving student: ' . $ex->getMessage());
            }
        } else {
            setFlash('danger', implode('<br>', $errors));
        }
    }
}

// ---------------------------------------------------------------------
// 3. EDIT RECORD PREPARATION
// ---------------------------------------------------------------------
$editStudent = null;
if ($action === 'edit') {
    $editId = getInt('id');
    $editStudent = Database::fetchOne(
        "SELECT s.*, e.class_id, e.section_id, e.roll_no, g.father_name, g.father_phone, g.mother_name, g.guardian_phone, g.present_address 
         FROM students s 
         LEFT JOIN enrollments e ON (e.student_id = s.id AND e.session_id = {$sessionId}) 
         LEFT JOIN guardians g ON s.guardian_id = g.id 
         WHERE s.id = ?",
        [$editId]
    );
}

// ---------------------------------------------------------------------
// 4. LISTING WITH SEARCH & FILTERS
// ---------------------------------------------------------------------
$filterClass = getInt('class_id');
$filterSection = getInt('section_id');
$search = getString('search');
$page = max(1, getInt('page'));

$where = "WHERE s.status = 'active' AND e.session_id = {$sessionId}";
$params = [];

if ($filterClass > 0) {
    $where .= " AND e.class_id = ?";
    $params[] = $filterClass;
}
if ($filterSection > 0) {
    $where .= " AND e.section_id = ?";
    $params[] = $filterSection;
}
if (!empty($search)) {
    $where .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ? OR e.roll_no = ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = (int)$search;
}

$baseQuery = "SELECT s.*, e.roll_no, c.class_name, sec.section_name, g.father_name, g.guardian_phone 
              FROM students s 
              JOIN enrollments e ON (e.student_id = s.id AND e.session_id = {$sessionId}) 
              JOIN classes c ON e.class_id = c.id 
              JOIN sections sec ON e.section_id = sec.id 
              LEFT JOIN guardians g ON s.guardian_id = g.id 
              {$where} 
              ORDER BY c.numeric_value ASC, sec.section_name ASC, e.roll_no ASC";

$pagination = paginate($baseQuery, $params, $page, 15);
$students = $pagination['data'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Student Management</h4>
        <p class="text-muted small mb-0">Active Academic Session: <strong><?= e($activeSession['session_name'] ?? '2026') ?></strong></p>
    </div>
    <div>
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
            <a href="<?= url('admin/students/index.php?action=create') ?>" class="btn btn-success rounded-pill px-4">
                <i class="fas fa-user-plus me-1"></i> Enroll New Student
            </a>
        <?php else: ?>
            <a href="<?= url('admin/students/index.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                &larr; Back to Student List
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ==================== FORM: CREATE OR EDIT ==================== -->
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">
            <i class="fas <?= $action === 'edit' ? 'fa-user-edit text-warning' : 'fa-user-plus text-success' ?> me-2"></i>
            <?= $action === 'edit' ? 'Edit Student Profile & Enrollment' : 'Enroll New Student' ?>
        </h5>

        <form action="<?= url('admin/students/index.php') ?>" method="POST" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="student_id" value="<?= $editStudent ? (int)$editStudent['id'] : 0 ?>">

            <!-- 1. Student Personal Information -->
            <h6 class="fw-bold text-success mb-3">1. Student Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?= e($editStudent['first_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?= e($editStudent['last_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="dob" class="form-control" value="<?= e($editStudent['dob'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select" required>
                        <option value="Male" <?= ($editStudent['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($editStudent['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($editStudent['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Unknown</option>
                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($editStudent['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Religion</label>
                    <select name="religion" class="form-select">
                        <option value="Islam" <?= ($editStudent['religion'] ?? '') === 'Islam' ? 'selected' : '' ?>>Islam</option>
                        <option value="Hinduism" <?= ($editStudent['religion'] ?? '') === 'Hinduism' ? 'selected' : '' ?>>Hinduism</option>
                        <option value="Christianity" <?= ($editStudent['religion'] ?? '') === 'Christianity' ? 'selected' : '' ?>>Christianity</option>
                        <option value="Buddhism" <?= ($editStudent['religion'] ?? '') === 'Buddhism' ? 'selected' : '' ?>>Buddhism</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Birth Certificate No</label>
                    <input type="text" name="birth_certificate_no" class="form-control" value="<?= e($editStudent['birth_certificate_no'] ?? '') ?>">
                </div>
            </div>

            <!-- 2. Academic Enrollment -->
            <h6 class="fw-bold text-success mb-3">2. Academic Enrollment (Session <?= e($activeSession['session_name'] ?? '2026') ?>)</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Class <span class="text-danger">*</span></label>
                    <select name="class_id" id="classSelect" class="form-select" required>
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($editStudent['class_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Section <span class="text-danger">*</span></label>
                    <select name="section_id" id="sectionSelect" class="form-select" required>
                        <option value="">-- Choose Section --</option>
                        <?php
                        // Fetch all sections
                        $allSections = Database::fetchAll("SELECT * FROM sections WHERE status = 'active' ORDER BY section_name ASC");
                        foreach ($allSections as $s): ?>
                            <option value="<?= $s['id'] ?>" data-class="<?= $s['class_id'] ?>" <?= ($editStudent['section_id'] ?? 0) == $s['id'] ? 'selected' : '' ?>>
                                <?= e($s['section_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Class Roll Number <span class="text-danger">*</span></label>
                    <input type="number" name="roll_no" class="form-control" min="1" value="<?= e($editStudent['roll_no'] ?? '') ?>" required>
                </div>
            </div>

            <!-- 3. Guardian Information -->
            <h6 class="fw-bold text-success mb-3">3. Guardian Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Father's Name</label>
                    <input type="text" name="father_name" class="form-control" value="<?= e($editStudent['father_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Father's Phone</label>
                    <input type="text" name="father_phone" class="form-control" value="<?= e($editStudent['father_phone'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Mother's Name</label>
                    <input type="text" name="mother_name" class="form-control" value="<?= e($editStudent['mother_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Emergency Guardian Phone</label>
                    <input type="text" name="guardian_phone" class="form-control" value="<?= e($editStudent['guardian_phone'] ?? '') ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Present Residential Address</label>
                    <input type="text" name="present_address" class="form-control" value="<?= e($editStudent['present_address'] ?? '') ?>">
                </div>
            </div>

            <div class="text-end border-top pt-3">
                <a href="<?= url('admin/students/index.php') ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill me-2">Cancel</a>
                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold">
                    <i class="fas fa-save me-1"></i> <?= $action === 'edit' ? 'Update Student Record' : 'Save Student' ?>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- ==================== FILTER BAR & DATA TABLE ==================== -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form action="<?= url('admin/students/index.php') ?>" method="GET" class="row g-3 align-items-center">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, roll, or admission ID..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3">
            <select name="class_id" class="form-select">
                <option value="">-- All Classes --</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClass === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['class_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-success rounded-pill px-4 flex-grow-1">Filter</button>
            <?php if ($filterClass || $filterSection || $search): ?>
                <a href="<?= url('admin/students/index.php') ?>" class="btn btn-outline-secondary rounded-pill">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Admission No</th>
                    <th>Roll</th>
                    <th>Student Name</th>
                    <th>Class / Section</th>
                    <th>Gender</th>
                    <th>Guardian Phone</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td class="fw-bold text-success"><?= e($stu['admission_no']) ?></td>
                            <td><span class="badge bg-dark rounded-pill px-3 py-1 fs-6"><?= e((string)$stu['roll_no']) ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($stu['first_name'] . ' ' . $stu['last_name']) ?></div>
                                <small class="text-muted">DOB: <?= formatDate($stu['dob']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($stu['class_name']) ?></span>
                                <span class="badge bg-light text-secondary border"><?= e($stu['section_name']) ?></span>
                            </td>
                            <td><?= e($stu['gender']) ?></td>
                            <td><?= e($stu['guardian_phone'] ?: '-') ?></td>
                            <td><span class="badge bg-success rounded-pill">Active</span></td>
                            <td class="text-end">
                                <a href="<?= url('admin/students/index.php?action=edit&id=' . $stu['id']) ?>" class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= url('admin/students/index.php?action=delete&id=' . $stu['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-circle btn-confirm-delete" 
                                   data-confirm="Are you sure you want to delete student <?= e($stu['first_name']) ?>? All attendance and marks records for this student will also be removed." 
                                   title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-user-slash fs-2 d-block mb-2"></i> No student records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <?php
            $baseUrl = url('admin/students/index.php?class_id=' . $filterClass . '&section_id=' . $filterSection . '&search=' . urlencode($search));
            echo renderPagination($pagination, $baseUrl);
            ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
