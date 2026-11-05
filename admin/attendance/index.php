<?php
/**
 * Sirajganj Road Chowrasta High School - Attendance Management
 */

$pageTitle = 'Attendance Management';
require_once __DIR__ . '/../includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$sections = Database::fetchAll("SELECT * FROM sections WHERE status = 'active' ORDER BY section_name ASC");

$selectedClassId = getInt('class_id') ?: ($classes[0]['id'] ?? 1);
$selectedSectionId = getInt('section_id');
if ($selectedSectionId <= 0) {
    $firstSec = Database::fetchOne("SELECT id FROM sections WHERE class_id = ? AND status = 'active' LIMIT 1", [$selectedClassId]);
    $selectedSectionId = $firstSec ? (int)$firstSec['id'] : 0;
}
$selectedDate = getString('attendance_date') ?: date('Y-m-d');

// 1. Handle Batch Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF verification expired.');
    } else {
        $postDate = getString('attendance_date', 'POST');
        $postClassId = getInt('class_id', 'POST');
        $postSectionId = getInt('section_id', 'POST');
        $attendanceData = $_POST['attendance'] ?? []; // student_id => status
        $remarksData = $_POST['remarks'] ?? []; // student_id => remarks

        if (empty($attendanceData)) {
            setFlash('danger', 'No student attendance records to record.');
        } else {
            Database::beginTransaction();
            try {
                $savedCount = 0;
                $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
                foreach ($attendanceData as $stuId => $status) {
                    $stuId = (int)$stuId;
                    $status = in_array($status, ['present', 'absent', 'late', 'excused']) ? $status : 'present';
                    $remark = trim($remarksData[$stuId] ?? '');
                    $counts[$status]++;

                    // Prevent duplicates with unique key on (student_id, attendance_date)
                    Database::insert(
                        "INSERT INTO attendance (session_id, class_id, section_id, student_id, attendance_date, status, remarks, marked_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)",
                        [$sessionId, $postClassId, $postSectionId, $stuId, $postDate, $status, $remark ?: null, $_SESSION['user_id']]
                    );
                    $savedCount++;
                }

                // Sync attendance_submissions table
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
                    [$sessionId, $postClassId, $postSectionId, $postDate, $_SESSION['user_id'], $savedCount, $counts['present'], $counts['absent'], $counts['late'], $counts['excused']]
                );

                Database::commit();
                logActivity('attendance_marked', "Recorded attendance for Class ID {$postClassId}, Sec ID {$postSectionId} on {$postDate} ({$savedCount} students)");
                setFlash('success', "Attendance successfully recorded for {$savedCount} students on {$postDate}.");
            } catch (Exception $e) {
                Database::rollback();
                setFlash('danger', 'Error recording attendance: ' . $e->getMessage());
            }
        }
    }
    redirect(url("admin/attendance/index.php?class_id={$selectedClassId}&section_id={$selectedSectionId}&attendance_date={$selectedDate}"));
}

// 2. Fetch Students and their current attendance for that day
$studentsList = [];
if ($selectedClassId > 0 && $selectedSectionId > 0) {
    $studentsList = Database::fetchAll(
        "SELECT s.id as student_id, s.admission_no, s.first_name, s.last_name, s.gender,
                e.roll_no,
                att.status as attendance_status, att.remarks as attendance_remarks
         FROM enrollments e 
         JOIN students s ON e.student_id = s.id 
         LEFT JOIN attendance att ON (att.student_id = s.id AND att.attendance_date = ?)
         WHERE e.session_id = ? AND e.class_id = ? AND e.section_id = ? AND s.status = 'active'
         ORDER BY e.roll_no ASC",
        [$selectedDate, $sessionId, $selectedClassId, $selectedSectionId]
    );
}

// Attendance summary counters
$presentCount = 0;
$absentCount = 0;
$lateCount = 0;
$excusedCount = 0;
foreach ($studentsList as $stu) {
    if ($stu['attendance_status'] === 'present') $presentCount++;
    elseif ($stu['attendance_status'] === 'absent') $absentCount++;
    elseif ($stu['attendance_status'] === 'late') $lateCount++;
    elseif ($stu['attendance_status'] === 'excused') $excusedCount++;
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Attendance Management</h4>
        <p class="text-muted small mb-0">Record daily classroom attendance, track absences, and view history.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-print me-1"></i> Print Attendance Sheet
        </button>
    </div>
</div>

<!-- Class / Section / Date Selector Filter -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form action="<?= url('admin/attendance/index.php') ?>" method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Class</label>
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Section</label>
            <select name="section_id" class="form-select" onchange="this.form.submit()">
                <?php
                $availSections = Database::fetchAll("SELECT * FROM sections WHERE class_id = ? AND status = 'active'", [$selectedClassId]);
                foreach ($availSections as $sec): ?>
                    <option value="<?= $sec['id'] ?>" <?= $selectedSectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['section_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Date</label>
            <input type="date" name="attendance_date" class="form-control" value="<?= e($selectedDate) ?>" onchange="this.form.submit()">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-success w-100 rounded-3"><i class="fas fa-sync-alt"></i></button>
        </div>
    </form>
</div>

<!-- Summary Cards for Selected Date -->
<?php if (!empty($studentsList)): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <small class="text-muted fw-bold">TOTAL ENROLLED</small>
                <h4 class="fw-bold text-dark mb-0"><?= count($studentsList) ?></h4>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <small class="text-success fw-bold">PRESENT</small>
                <h4 class="fw-bold text-success mb-0"><?= $presentCount ?></h4>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <small class="text-danger fw-bold">ABSENT</small>
                <h4 class="fw-bold text-danger mb-0"><?= $absentCount ?></h4>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <small class="text-warning fw-bold">LATE / EXCUSED</small>
                <h4 class="fw-bold text-warning mb-0"><?= $lateCount + $excusedCount ?></h4>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Attendance Sheet Form -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-clipboard-list text-success me-2"></i> Attendance Sheet: <?= formatDate($selectedDate, 'l, d F Y') ?>
        </h6>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-success rounded-pill" onclick="setAllAttendance('present')">Mark All Present</button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="setAllAttendance('absent')">Mark All Absent</button>
        </div>
    </div>

    <form action="<?= url('admin/attendance/index.php') ?>" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="save_attendance" value="1">
        <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
        <input type="hidden" name="section_id" value="<?= $selectedSectionId ?>">
        <input type="hidden" name="attendance_date" value="<?= e($selectedDate) ?>">

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Roll</th>
                        <th>Student Name</th>
                        <th>Admission ID</th>
                        <th class="text-center" style="width: 320px;">Status</th>
                        <th>Remarks (Optional)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($studentsList)): ?>
                        <?php foreach ($studentsList as $stu): ?>
                            <?php $currStatus = $stu['attendance_status'] ?: 'present'; ?>
                            <tr>
                                <td class="fw-bold"><span class="badge bg-dark rounded-pill"><?= e((string)$stu['roll_no']) ?></span></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($stu['first_name'] . ' ' . $stu['last_name']) ?></div>
                                    <small class="text-muted"><?= e($stu['gender']) ?></small>
                                </td>
                                <td><span class="badge bg-light text-secondary border"><?= e($stu['admission_no']) ?></span></td>
                                <td>
                                    <div class="btn-group w-100 attendance-btn-group" role="group">
                                        <input type="radio" class="btn-check att-radio" name="attendance[<?= $stu['student_id'] ?>]" id="pres_<?= $stu['student_id'] ?>" value="present" <?= $currStatus === 'present' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-success btn-sm" for="pres_<?= $stu['student_id'] ?>">Present</label>

                                        <input type="radio" class="btn-check att-radio" name="attendance[<?= $stu['student_id'] ?>]" id="abs_<?= $stu['student_id'] ?>" value="absent" <?= $currStatus === 'absent' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-danger btn-sm" for="abs_<?= $stu['student_id'] ?>">Absent</label>

                                        <input type="radio" class="btn-check att-radio" name="attendance[<?= $stu['student_id'] ?>]" id="late_<?= $stu['student_id'] ?>" value="late" <?= $currStatus === 'late' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-warning btn-sm" for="late_<?= $stu['student_id'] ?>">Late</label>

                                        <input type="radio" class="btn-check att-radio" name="attendance[<?= $stu['student_id'] ?>]" id="exc_<?= $stu['student_id'] ?>" value="excused" <?= $currStatus === 'excused' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-info btn-sm" for="exc_<?= $stu['student_id'] ?>">Excused</label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?= $stu['student_id'] ?>]" class="form-control form-control-sm" placeholder="e.g. Fever / Leave" value="<?= e($stu['attendance_remarks'] ?? '') ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                No enrolled students found for the selected Class and Section.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($studentsList)): ?>
            <div class="card-footer bg-white border-0 py-3 text-end">
                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> Save Daily Attendance
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
function setAllAttendance(val) {
    document.querySelectorAll('.att-radio[value="' + val + '"]').forEach(r => {
        r.checked = true;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
