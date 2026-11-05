<?php
/**
 * Teacher Smart Daily Attendance Module
 * Sirajganj Road Chowrasta High School
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTeacher();

$currentSession = getCurrentSession();
$activeSessionId = $currentSession ? (int)$currentSession['id'] : 1;
$currentTeacher = getCurrentTeacher();
$teacherId = $currentTeacher ? (int)$currentTeacher['id'] : 0;
$userId = (int)($_SESSION['user_id'] ?? 0);

// Get day of week for selected date
$selectedDate = getString('date') ?: date('Y-m-d');
$dayOfWeek = date('l', strtotime($selectedDate));

// All classes & sections available for dropdown
$availableClasses = Database::fetchAll(
    "SELECT c.id AS class_id, c.class_name, c.numeric_value,
            sec.id AS section_id, sec.section_name
     FROM sections sec
     JOIN classes c ON sec.class_id = c.id
     WHERE sec.status = 'active' AND c.status = 'active'
     ORDER BY c.numeric_value ASC, sec.section_name ASC"
);

// Find which sections this teacher takes 1st period for today
$firstPeriodId = (int)Database::fetchColumn("SELECT id FROM periods WHERE order_num = 1 LIMIT 1");
$firstPeriodAssignments = Database::fetchAll(
    "SELECT class_id, section_id FROM routines 
     WHERE teacher_id = ? AND session_id = ? AND day_of_week = ? AND period_id = ?",
    [$teacherId, $activeSessionId, $dayOfWeek, $firstPeriodId]
);
$firstPeriodKeyMap = [];
foreach ($firstPeriodAssignments as $fpa) {
    $firstPeriodKeyMap[$fpa['class_id'] . '_' . $fpa['section_id']] = true;
}

// Find teacher's assigned classes from teacher_assignments
$myAssignedClasses = Database::fetchAll(
    "SELECT DISTINCT ta.class_id, ta.section_id, ta.is_class_teacher
     FROM teacher_assignments ta
     WHERE ta.teacher_id = ? AND ta.session_id = ?",
    [$teacherId, $activeSessionId]
);
$myAssignedKeyMap = [];
foreach ($myAssignedClasses as $mac) {
    $myAssignedKeyMap[$mac['class_id'] . '_' . $mac['section_id']] = (bool)$mac['is_class_teacher'];
}

// Select default class and section (prefer teacher's 1st period class today, or first assigned class)
$defaultClassId = 0;
$defaultSectionId = 0;
if (!empty($firstPeriodAssignments)) {
    $defaultClassId = (int)$firstPeriodAssignments[0]['class_id'];
    $defaultSectionId = (int)$firstPeriodAssignments[0]['section_id'];
} elseif (!empty($myAssignedClasses)) {
    $defaultClassId = (int)$myAssignedClasses[0]['class_id'];
    $defaultSectionId = (int)$myAssignedClasses[0]['section_id'];
} elseif (!empty($availableClasses)) {
    $defaultClassId = (int)$availableClasses[0]['class_id'];
    $defaultSectionId = (int)$availableClasses[0]['section_id'];
}

$selectedClassId = getInt('class_id') ?: $defaultClassId;
$selectedSectionId = getInt('section_id') ?: $defaultSectionId;

// Determine teacher's authority for selected class:
// 1. First period teacher today?
$isFirstPeriodTeacherToday = isset($firstPeriodKeyMap[$selectedClassId . '_' . $selectedSectionId]);
// 2. Class teacher?
$isClassTeacher = isset($myAssignedKeyMap[$selectedClassId . '_' . $selectedSectionId]) && $myAssignedKeyMap[$selectedClassId . '_' . $selectedSectionId] === true;
// 3. Subject teacher?
$isSubjectTeacher = isset($myAssignedKeyMap[$selectedClassId . '_' . $selectedSectionId]);
// 4. Head Sir / Admin?
$isSuper = isHeadmaster() || isAdmin();

// Authorized if 1st class teacher, class teacher, assigned subject teacher, or Head Sir/Admin
$isAuthorized = $isFirstPeriodTeacherToday || $isClassTeacher || $isSubjectTeacher || $isSuper;

// Check existing submission for this class, section, date
$existingSubmission = Database::fetchOne(
    "SELECT sub.*, u.full_name as submitter_name, r.display_name as submitter_role
     FROM attendance_submissions sub
     JOIN users u ON sub.submitted_by = u.id
     JOIN roles r ON u.role_id = r.id
     WHERE sub.session_id = ? AND sub.class_id = ? AND sub.section_id = ? AND sub.attendance_date = ?",
    [$activeSessionId, $selectedClassId, $selectedSectionId, $selectedDate]
);

// Handle Attendance Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'Security validation token expired. Please try again.');
        redirect(url("teacher/attendance.php?class_id={$selectedClassId}&section_id={$selectedSectionId}&date={$selectedDate}"));
    }

    if (!$isAuthorized) {
        setFlash('danger', 'Unauthorized: You are not assigned to take attendance for this section today.');
        redirect(url("teacher/attendance.php?class_id={$selectedClassId}&section_id={$selectedSectionId}&date={$selectedDate}"));
    }

    $attendanceDate = getString('attendance_date', 'POST') ?: $selectedDate;
    $postClassId = getInt('class_id', 'POST') ?: $selectedClassId;
    $postSectionId = getInt('section_id', 'POST') ?: $selectedSectionId;
    $attendanceData = $_POST['attendance'] ?? [];
    $remarksData = $_POST['remarks'] ?? [];

    if (empty($attendanceData)) {
        setFlash('warning', 'No student attendance records received.');
        redirect(url("teacher/attendance.php?class_id={$postClassId}&section_id={$postSectionId}&date={$attendanceDate}"));
    }

    $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];

    try {
        Database::beginTransaction();

        foreach ($attendanceData as $studentId => $status) {
            $studentId = (int)$studentId;
            $status = in_array($status, ['present', 'absent', 'late', 'excused']) ? $status : 'present';
            $remark = cleanInput($remarksData[$studentId] ?? '');
            $counts[$status]++;

            // Unique key on (student_id, attendance_date) handles safe upsert
            Database::query(
                "INSERT INTO attendance (session_id, class_id, section_id, student_id, attendance_date, status, remarks, marked_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                    class_id = VALUES(class_id), 
                    section_id = VALUES(section_id), 
                    status = VALUES(status), 
                    remarks = VALUES(remarks), 
                    marked_by = VALUES(marked_by), 
                    updated_at = NOW()",
                [$activeSessionId, $postClassId, $postSectionId, $studentId, $attendanceDate, $status, $remark ?: null, $userId]
            );
        }

        $totalStudents = count($attendanceData);

        // Record in attendance_submissions table
        Database::query(
            "INSERT INTO attendance_submissions (session_id, class_id, section_id, attendance_date, submitted_by, total_students, present_count, absent_count, late_count, excused_count, submitted_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE 
                submitted_by = VALUES(submitted_by),
                total_students = VALUES(total_students),
                present_count = VALUES(present_count),
                absent_count = VALUES(absent_count),
                late_count = VALUES(late_count),
                excused_count = VALUES(excused_count),
                updated_at = NOW()",
            [$activeSessionId, $postClassId, $postSectionId, $attendanceDate, $userId, $totalStudents, $counts['present'], $counts['absent'], $counts['late'], $counts['excused']]
        );

        Database::commit();

        $teacherName = $currentTeacher['full_name'] ?? 'Teacher';
        logActivity('attendance_submitted', "Teacher {$teacherName} recorded attendance for {$totalStudents} students (Class ID: {$postClassId}, Sec ID: {$postSectionId}, Date: {$attendanceDate})", $userId);

        setFlash('success', "Daily attendance successfully recorded! Present: {$counts['present']}, Absent: {$counts['absent']}, Late: {$counts['late']}, Leave: {$counts['excused']}.");
    } catch (Exception $e) {
        Database::rollback();
        setFlash('danger', 'Failed to save attendance: ' . $e->getMessage());
    }

    redirect(url("teacher/attendance.php?class_id={$postClassId}&section_id={$postSectionId}&date={$attendanceDate}"));
}

// Fetch enrolled students and their attendance status for that day
$students = [];
$attendanceStats = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'unmarked' => 0];

if ($selectedClassId && $selectedSectionId) {
    $students = Database::fetchAll(
        "SELECT s.id as student_id, s.admission_no, s.first_name, s.last_name, s.gender, s.photo,
                e.roll_no,
                att.status AS attendance_status, att.remarks AS attendance_remarks
         FROM enrollments e
         JOIN students s ON e.student_id = s.id
         LEFT JOIN attendance att ON (att.student_id = s.id AND att.attendance_date = ?)
         WHERE e.session_id = ? AND e.class_id = ? AND e.section_id = ? AND s.status = 'active'
         ORDER BY e.roll_no ASC",
        [$selectedDate, $activeSessionId, $selectedClassId, $selectedSectionId]
    );

    $attendanceStats['total'] = count($students);
    foreach ($students as $st) {
        $stt = $st['attendance_status'];
        if ($stt && isset($attendanceStats[$stt])) {
            $attendanceStats[$stt]++;
        } else {
            $attendanceStats['unmarked']++;
        }
    }
}

// Selected class and section names
$currentClassInfo = Database::fetchOne(
    "SELECT c.class_name, sec.section_name 
     FROM sections sec 
     JOIN classes c ON sec.class_id = c.id 
     WHERE c.id = ? AND sec.id = ?",
    [$selectedClassId, $selectedSectionId]
);

$pageTitle = "Smart Daily Attendance";
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-clipboard-check text-success me-2"></i> Smart Daily Attendance
        </h4>
        <p class="text-muted small mb-0">Record and verify daily classroom attendance by section and routine period.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('teacher/routine.php') ?>" class="btn btn-outline-primary rounded-pill px-3">
            <i class="fas fa-calendar-alt me-1"></i> View My Routine
        </a>
    </div>
</div>

<?= displayFlash() ?>

<!-- Filter & Routine Status Bar -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-4">
        <form action="<?= url('teacher/attendance.php') ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-dark">Class & Section</label>
                <select name="class_and_section" class="form-select" onchange="const [c, s] = this.value.split('_'); window.location.href='<?= url('teacher/attendance.php') ?>?class_id='+c+'&section_id='+s+'&date=<?= e($selectedDate) ?>';">
                    <?php foreach ($availableClasses as $ac): 
                        $val = $ac['class_id'] . '_' . $ac['section_id'];
                        $isSel = ($ac['class_id'] == $selectedClassId && $ac['section_id'] == $selectedSectionId);
                        $is1st = isset($firstPeriodKeyMap[$val]);
                    ?>
                        <option value="<?= $val ?>" <?= $isSel ? 'selected' : '' ?>>
                            <?= e($ac['class_name']) ?> - <?= e($ac['section_name']) ?> <?= $is1st ? '★ (Your 1st Class Today)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
                <input type="hidden" name="section_id" value="<?= $selectedSectionId ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark">Attendance Date</label>
                <input type="date" name="date" class="form-control" value="<?= e($selectedDate) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100 rounded-3">
                    <i class="fas fa-filter me-1"></i> Load Section
                </button>
            </div>

            <div class="col-md-3">
                <div class="p-2 rounded-3 text-center border <?= $existingSubmission ? 'bg-success-subtle border-success' : 'bg-warning-subtle border-warning' ?>">
                    <small class="d-block fw-bold <?= $existingSubmission ? 'text-success' : 'text-dark' ?>">
                        <?= $existingSubmission ? '<i class="fas fa-check-circle me-1"></i> Submitted' : '<i class="fas fa-clock me-1"></i> Pending Submission' ?>
                    </small>
                    <small class="text-muted" style="font-size: 0.72rem;">
                        <?= $existingSubmission ? 'By ' . e($existingSubmission['submitter_name']) . ' at ' . date('h:i A', strtotime($existingSubmission['submitted_at'])) : 'No submission recorded for today' ?>
                    </small>
                </div>
            </div>
        </form>

        <!-- Smart Routine Notification -->
        <div class="mt-3 pt-3 border-top">
            <?php if ($isFirstPeriodTeacherToday): ?>
                <div class="alert alert-success d-flex align-items-center mb-0 py-2">
                    <i class="fas fa-check-double fs-4 me-3 text-success"></i>
                    <div>
                        <strong>Routine Match:</strong> You are scheduled to take the <strong>1st Period</strong> class for <?= e($currentClassInfo['class_name'] ?? 'this class') ?> (<?= e($currentClassInfo['section_name'] ?? 'Section') ?>) today (<?= $dayOfWeek ?>). You are the designated attendance teacher.
                    </div>
                </div>
            <?php elseif ($isClassTeacher): ?>
                <div class="alert alert-info d-flex align-items-center mb-0 py-2">
                    <i class="fas fa-user-shield fs-4 me-3 text-info"></i>
                    <div>
                        <strong>Class Teacher Match:</strong> You are the designated <strong>Class Teacher</strong> for this section with full attendance authority.
                    </div>
                </div>
            <?php elseif ($isAuthorized): ?>
                <div class="alert alert-light border d-flex align-items-center mb-0 py-2 text-muted small">
                    <i class="fas fa-info-circle fs-5 me-2 text-primary"></i>
                    <span>Authorized faculty attendance access for <?= e($currentClassInfo['class_name'] ?? '') ?> - <?= e($currentClassInfo['section_name'] ?? '') ?>.</span>
                </div>
            <?php else: ?>
                <div class="alert alert-warning d-flex align-items-center mb-0 py-2">
                    <i class="fas fa-exclamation-triangle fs-4 me-3 text-warning"></i>
                    <div>
                        <strong>Notice:</strong> You are not currently listed on the routine for 1st period of this section. (Head Sir or 1st period teacher will mark attendance).
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Attendance Statistics Bar -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white">
            <span class="text-muted small fw-semibold">Total Students</span>
            <h4 class="fw-bold mb-0 text-dark" id="stat-total"><?= $attendanceStats['total'] ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white border-start border-4 border-success">
            <span class="text-success small fw-semibold">Present</span>
            <h4 class="fw-bold mb-0 text-success" id="stat-present"><?= $attendanceStats['present'] ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white border-start border-4 border-danger">
            <span class="text-danger small fw-semibold">Absent</span>
            <h4 class="fw-bold mb-0 text-danger" id="stat-absent"><?= $attendanceStats['absent'] ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white border-start border-4 border-warning">
            <span class="text-warning small fw-semibold">Late</span>
            <h4 class="fw-bold mb-0 text-warning" id="stat-late"><?= $attendanceStats['late'] ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white border-start border-4 border-info">
            <span class="text-info small fw-semibold">Leave / Excused</span>
            <h4 class="fw-bold mb-0 text-info" id="stat-excused"><?= $attendanceStats['excused'] ?></h4>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white border-start border-4 border-secondary">
            <span class="text-muted small fw-semibold">Unmarked</span>
            <h4 class="fw-bold mb-0 text-secondary" id="stat-unmarked"><?= $attendanceStats['unmarked'] ?></h4>
        </div>
    </div>
</div>

<!-- Attendance Marking Table Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <?= e($currentClassInfo['class_name'] ?? 'Class') ?> (<?= e($currentClassInfo['section_name'] ?? 'Section') ?>) &mdash; Student Roster
            </h6>
            <small class="text-muted">Date: <?= date('d M Y (l)', strtotime($selectedDate)) ?></small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="markAll('present')">
                <i class="fas fa-check-double me-1"></i> Mark All Present
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="markAll('absent')">
                <i class="fas fa-times me-1"></i> Mark All Absent
            </button>
        </div>
    </div>

    <?php if (empty($students)): ?>
        <div class="p-5 text-center text-muted">
            <i class="fas fa-user-graduate fs-1 text-muted mb-3 opacity-50"></i>
            <h5>No Enrolled Students Found</h5>
            <p class="small mb-0">No active students are currently enrolled in <?= e($currentClassInfo['class_name'] ?? '') ?> - <?= e($currentClassInfo['section_name'] ?? '') ?> for Session <?= e($currentSession['session_name'] ?? '2026') ?>.</p>
        </div>
    <?php else: ?>
        <form action="<?= url('teacher/attendance.php') ?>" method="POST" id="attendanceForm">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_attendance">
            <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
            <input type="hidden" name="section_id" value="<?= $selectedSectionId ?>">
            <input type="hidden" name="attendance_date" value="<?= e($selectedDate) ?>">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;" class="text-center">Roll</th>
                            <th>Student Information</th>
                            <th style="width: 320px;" class="text-center">Daily Status</th>
                            <th>Remarks (Optional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): 
                            $sid = (int)$st['student_id'];
                            $currentStatus = $st['attendance_status'] ?: 'present';
                        ?>
                            <tr>
                                <td class="text-center fw-bold text-success fs-6">
                                    <?= str_pad((string)$st['roll_no'], 2, '0', STR_PAD_LEFT) ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= resolveUploadUrl($st['photo'] ?? '', 'assets/images/default-student.png') ?>" 
                                             class="rounded-circle me-2 border" style="width: 42px; height: 42px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold text-dark">
                                                <?= e($st['first_name'] . ' ' . $st['last_name']) ?>
                                            </div>
                                            <div class="small text-muted">
                                                ID: <code><?= e($st['admission_no']) ?></code> • <?= e($st['gender']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group w-100" role="group">
                                        <!-- Present -->
                                        <input type="radio" class="btn-check status-radio" name="attendance[<?= $sid ?>]" id="pres_<?= $sid ?>" value="present" <?= $currentStatus === 'present' ? 'checked' : '' ?> onchange="updateCounters()">
                                        <label class="btn btn-outline-success btn-sm py-1" for="pres_<?= $sid ?>">
                                            <i class="fas fa-check"></i> Present
                                        </label>

                                        <!-- Absent -->
                                        <input type="radio" class="btn-check status-radio" name="attendance[<?= $sid ?>]" id="abs_<?= $sid ?>" value="absent" <?= $currentStatus === 'absent' ? 'checked' : '' ?> onchange="updateCounters()">
                                        <label class="btn btn-outline-danger btn-sm py-1" for="abs_<?= $sid ?>">
                                            <i class="fas fa-times"></i> Absent
                                        </label>

                                        <!-- Late -->
                                        <input type="radio" class="btn-check status-radio" name="attendance[<?= $sid ?>]" id="late_<?= $sid ?>" value="late" <?= $currentStatus === 'late' ? 'checked' : '' ?> onchange="updateCounters()">
                                        <label class="btn btn-outline-warning btn-sm py-1" for="late_<?= $sid ?>">
                                            <i class="fas fa-clock"></i> Late
                                        </label>

                                        <!-- Excused -->
                                        <input type="radio" class="btn-check status-radio" name="attendance[<?= $sid ?>]" id="exc_<?= $sid ?>" value="excused" <?= $currentStatus === 'excused' ? 'checked' : '' ?> onchange="updateCounters()">
                                        <label class="btn btn-outline-info btn-sm py-1" for="exc_<?= $sid ?>">
                                            <i class="fas fa-bed"></i> Leave
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?= $sid ?>]" class="form-control form-control-sm" placeholder="e.g. Fever, Sick leave" value="<?= e($st['attendance_remarks'] ?? '') ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white p-4 border-top d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <i class="fas fa-shield-alt text-success me-1"></i> Recorded against official NCTB Academic Session <?= e($currentSession['session_name'] ?? '2026') ?>
                </span>
                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold shadow">
                    <i class="fas fa-save me-1"></i> <?= $existingSubmission ? 'Update Attendance Records' : 'Save & Submit Attendance' ?>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function markAll(status) {
    document.querySelectorAll(`input[type="radio"][value="${status}"]`).forEach(radio => {
        radio.checked = true;
    });
    updateCounters();
}

function updateCounters() {
    let p = 0, a = 0, l = 0, e = 0;
    const radios = document.querySelectorAll('.status-radio:checked');
    radios.forEach(r => {
        if (r.value === 'present') p++;
        else if (r.value === 'absent') a++;
        else if (r.value === 'late') l++;
        else if (r.value === 'excused') e++;
    });
    const total = document.querySelectorAll('tbody tr').length;
    const unmarked = total - (p + a + l + e);

    const elP = document.getElementById('stat-present');
    const elA = document.getElementById('stat-absent');
    const elL = document.getElementById('stat-late');
    const elE = document.getElementById('stat-excused');
    const elU = document.getElementById('stat-unmarked');

    if (elP) elP.innerText = p;
    if (elA) elA.innerText = a;
    if (elL) elL.innerText = l;
    if (elE) elE.innerText = e;
    if (elU) elU.innerText = Math.max(0, unmarked);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
