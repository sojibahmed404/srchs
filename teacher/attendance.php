<?php
/**
 * Teacher Daily Attendance Module
 * Sirajganj Road Chowrasta High School
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTeacher();

$db = Database::getInstance();
$activeSessionId = (int)getSetting('active_session_id', 1);
$currentTeacher = getCurrentTeacher();
$teacherId = (int)$currentTeacher['id'];

// Get distinct classes/sections assigned to this teacher
$assignedClasses = $db->fetchAll(
    "SELECT DISTINCT c.id AS class_id, c.name AS class_name, c.numeric_name,
            s.id AS section_id, s.name AS section_name, ta.is_class_teacher
     FROM teacher_assignments ta
     JOIN classes c ON ta.class_id = c.id
     JOIN sections s ON ta.section_id = s.id
     WHERE ta.teacher_id = ? AND ta.session_id = ?
     ORDER BY c.numeric_name ASC, s.name ASC",
    [$teacherId, $activeSessionId]
);

$selectedClassId = (int)($_GET['class_id'] ?? ($assignedClasses[0]['class_id'] ?? 0));
$selectedSectionId = (int)($_GET['section_id'] ?? ($assignedClasses[0]['section_id'] ?? 0));
$selectedDate = cleanInput($_GET['date'] ?? date('Y-m-d'));

// Authorization check: Verify teacher is assigned to selected class and section
$isAuthorized = false;
foreach ($assignedClasses as $ac) {
    if ((int)$ac['class_id'] === $selectedClassId && (int)$ac['section_id'] === $selectedSectionId) {
        $isAuthorized = true;
        break;
    }
}

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (CSRF). Please retry.');
        redirect('attendance.php?class_id=' . $selectedClassId . '&section_id=' . $selectedSectionId . '&date=' . $selectedDate);
    }

    if (!$isAuthorized) {
        setFlash('danger', 'You are not assigned to take attendance for this class and section.');
        redirect('attendance.php');
    }

    $attendanceDate = cleanInput($_POST['attendance_date'] ?? $selectedDate);
    $attendanceData = $_POST['attendance'] ?? [];
    $remarksData = $_POST['remarks'] ?? [];

    if (empty($attendanceData)) {
        setFlash('warning', 'No student attendance records received.');
        redirect('attendance.php?class_id=' . $selectedClassId . '&section_id=' . $selectedSectionId . '&date=' . $attendanceDate);
    }

    $savedCount = 0;
    try {
        $db->beginTransaction();
        foreach ($attendanceData as $studentId => $status) {
            $studentId = (int)$studentId;
            $status = in_array($status, ['present', 'absent', 'late', 'excused']) ? $status : 'present';
            $remark = cleanInput($remarksData[$studentId] ?? '');

            $db->query(
                "INSERT INTO attendance (session_id, class_id, section_id, student_id, attendance_date, status, remarks, recorded_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), recorded_by = VALUES(recorded_by), updated_at = NOW()",
                [$activeSessionId, $selectedClassId, $selectedSectionId, $studentId, $attendanceDate, $status, $remark, $_SESSION['user_id']]
            );
            $savedCount++;
        }
        $db->commit();

        logActivity($_SESSION['user_id'], 'record_attendance', "Teacher {$currentTeacher['name']} recorded attendance for {$savedCount} students (Class ID: {$selectedClassId}, Section ID: {$selectedSectionId}, Date: {$attendanceDate})");
        setFlash('success', "Daily attendance successfully recorded for {$savedCount} students on {$attendanceDate}.");
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Failed to save attendance: ' . $e->getMessage());
    }

    redirect('attendance.php?class_id=' . $selectedClassId . '&section_id=' . $selectedSectionId . '&date=' . $attendanceDate);
}

// Fetch enrolled students and their attendance for selected class & section
$students = [];
$attendanceStats = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'unmarked' => 0];

if ($isAuthorized && $selectedClassId && $selectedSectionId) {
    $students = $db->fetchAll(
        "SELECT s.id, s.admission_no, s.name_en, s.name_bn, s.gender, s.photo,
                e.roll_no,
                att.status AS attendance_status, att.remarks AS attendance_remarks
         FROM enrollments e
         JOIN students s ON e.student_id = s.id
         LEFT JOIN attendance att ON att.student_id = s.id 
              AND att.session_id = ? 
              AND att.class_id = ? 
              AND att.section_id = ? 
              AND att.attendance_date = ?
         WHERE e.session_id = ? 
           AND e.class_id = ? 
           AND e.section_id = ? 
           AND e.status = 'active'
         ORDER BY e.roll_no ASC",
        [$activeSessionId, $selectedClassId, $selectedSectionId, $selectedDate, $activeSessionId, $selectedClassId, $selectedSectionId]
    );

    foreach ($students as $st) {
        if ($st['attendance_status']) {
            $attendanceStats[$st['attendance_status']]++;
        } else {
            $attendanceStats['unmarked']++;
        }
    }
}

$pageTitle = "Take Attendance";
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-clipboard-user text-primary me-2"></i>Daily Attendance</h4>
        <p class="text-muted small mb-0">Record or update daily student attendance for your assigned classes</p>
    </div>
    <div class="d-flex gap-2">
        <a href="students.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-users me-1"></i> My Students
        </a>
    </div>
</div>

<?php displayFlash(); ?>

<?php if (empty($assignedClasses)): ?>
    <div class="alert alert-warning py-4 text-center">
        <i class="fa-solid fa-triangle-exclamation fa-2x mb-2 d-block"></i>
        <h5>No Assigned Classes Found</h5>
        <p class="text-muted mb-0">You are currently not assigned to any classes for Academic Session <?= e(getSetting('current_session', '2026')) ?>. Please contact the administrator.</p>
    </div>
<?php else: ?>

    <!-- Filter & Selector Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="attendance.php" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Select Assigned Class & Section <span class="text-danger">*</span></label>
                    <select name="class_section" class="form-select" onchange="const parts = this.value.split(':'); document.getElementById('filterClass').value = parts[0]; document.getElementById('filterSection').value = parts[1];">
                        <?php foreach ($assignedClasses as $ac): 
                            $val = $ac['class_id'] . ':' . $ac['section_id'];
                            $isSelected = ($ac['class_id'] == $selectedClassId && $ac['section_id'] == $selectedSectionId);
                        ?>
                            <option value="<?= $val ?>" <?= $isSelected ? 'selected' : '' ?>>
                                <?= e($ac['class_name']) ?> — Section <?= e($ac['section_name']) ?> <?= $ac['is_class_teacher'] ? '(Class Teacher)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="class_id" id="filterClass" value="<?= $selectedClassId ?>">
                    <input type="hidden" name="section_id" id="filterSection" value="<?= $selectedSectionId ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Attendance Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="<?= e($selectedDate) ?>" max="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i> Load Students
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$isAuthorized): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-lock me-2"></i> Access restricted. You are not assigned to this class and section.
        </div>
    <?php elseif (empty($students)): ?>
        <div class="alert alert-info py-4 text-center">
            <i class="fa-solid fa-info-circle fa-2x mb-2 d-block"></i>
            <h5>No Active Students Found</h5>
            <p class="text-muted mb-0">No active student enrollments found for this class and section in Session <?= e(getSetting('current_session', '2026')) ?>.</p>
        </div>
    <?php else: ?>

        <!-- Attendance Summary Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center py-2 bg-light">
                    <div class="small text-muted">Total Enrolled</div>
                    <div class="fs-4 fw-bold text-dark"><?= count($students) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center py-2 bg-success-subtle border-start border-success border-4">
                    <div class="small text-success fw-semibold">Present</div>
                    <div class="fs-4 fw-bold text-success"><?= $attendanceStats['present'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center py-2 bg-danger-subtle border-start border-danger border-4">
                    <div class="small text-danger fw-semibold">Absent</div>
                    <div class="fs-4 fw-bold text-danger"><?= $attendanceStats['absent'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center py-2 bg-warning-subtle border-start border-warning border-4">
                    <div class="small text-warning-emphasis fw-semibold">Late</div>
                    <div class="fs-4 fw-bold text-warning-emphasis"><?= $attendanceStats['late'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center py-2 bg-info-subtle border-start border-info border-4">
                    <div class="small text-info-emphasis fw-semibold">Excused</div>
                    <div class="fs-4 fw-bold text-info-emphasis"><?= $attendanceStats['excused'] ?></div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="card border-0 shadow-sm text-center py-2 bg-secondary-subtle">
                    <div class="small text-muted">Unmarked</div>
                    <div class="fs-4 fw-bold text-secondary"><?= $attendanceStats['unmarked'] ?></div>
                </div>
            </div>
        </div>

        <!-- Attendance Form -->
        <form method="POST" action="attendance.php?class_id=<?= $selectedClassId ?>&section_id=<?= $selectedSectionId ?>&date=<?= e($selectedDate) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_attendance">
            <input type="hidden" name="attendance_date" value="<?= e($selectedDate) ?>">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fa-regular fa-calendar-check text-primary me-2"></i>
                            Attendance Sheet: <?= date('d F, Y (l)', strtotime($selectedDate)) ?>
                        </h6>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="markAllStatus('present')">
                            <i class="fa-solid fa-check-double me-1"></i> Mark All Present
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="markAllStatus('absent')">
                            <i class="fa-solid fa-xmark me-1"></i> Mark All Absent
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;" class="text-center">Roll</th>
                                <th style="width: 60px;">Photo</th>
                                <th>Student Name</th>
                                <th>Admission No</th>
                                <th style="min-width: 320px;" class="text-center">Attendance Status</th>
                                <th style="min-width: 200px;">Remarks (Optional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $st): 
                                $currentStatus = $st['attendance_status'] ?? 'present';
                            ?>
                                <tr>
                                    <td class="text-center fw-bold fs-6 text-primary">
                                        <?= str_pad((string)$st['roll_no'], 2, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td>
                                        <img src="<?= e(resolveUploadUrl($st['photo'], 'student')) ?>" 
                                             alt="<?= e($st['name_en']) ?>" 
                                             class="rounded-circle border" 
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($st['name_en']) ?></div>
                                        <div class="text-muted small"><?= e($st['name_bn']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($st['admission_no']) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <input type="radio" class="btn-check att-status" name="attendance[<?= $st['id'] ?>]" id="pres_<?= $st['id'] ?>" value="present" <?= $currentStatus === 'present' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-success px-3" for="pres_<?= $st['id'] ?>">
                                                <i class="fa-solid fa-check me-1"></i> Present
                                            </label>

                                            <input type="radio" class="btn-check att-status" name="attendance[<?= $st['id'] ?>]" id="abs_<?= $st['id'] ?>" value="absent" <?= $currentStatus === 'absent' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-danger px-3" for="abs_<?= $st['id'] ?>">
                                                <i class="fa-solid fa-xmark me-1"></i> Absent
                                            </label>

                                            <input type="radio" class="btn-check att-status" name="attendance[<?= $st['id'] ?>]" id="late_<?= $st['id'] ?>" value="late" <?= $currentStatus === 'late' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-warning px-3" for="late_<?= $st['id'] ?>">
                                                <i class="fa-regular fa-clock me-1"></i> Late
                                            </label>

                                            <input type="radio" class="btn-check att-status" name="attendance[<?= $st['id'] ?>]" id="exc_<?= $st['id'] ?>" value="excused" <?= $currentStatus === 'excused' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-info px-3" for="exc_<?= $st['id'] ?>">
                                                <i class="fa-regular fa-circle-question me-1"></i> Excused
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[<?= $st['id'] ?>]" class="form-control form-control-sm" placeholder="e.g. sick leave, informed" value="<?= e($st['attendance_remarks'] ?? '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        <i class="fa-solid fa-shield-halved me-1"></i> Duplicate records will be updated automatically.
                    </span>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Attendance Records
                    </button>
                </div>
            </div>
        </form>

    <?php endif; ?>
<?php endif; ?>

<script>
function markAllStatus(status) {
    document.querySelectorAll(`.att-status[value="${status}"]`).forEach(radio => {
        radio.checked = true;
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
