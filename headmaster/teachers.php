<?php
/**
 * Head Sir Portal - Teacher Monitoring & Routine Submission Tracker
 * Sirajganj Road Chowrasta High School
 */
$pageTitle = 'Teacher & Routine Monitoring';
require_once __DIR__ . '/includes/header.php';

$filterDate = getString('date') ?: date('Y-m-d');
$dayName = date('l', strtotime($filterDate));

// 1. All Teachers with their profiles and assigned courses
$teachers = Database::fetchAll(
    "SELECT t.*, u.full_name, u.email, u.phone, u.avatar, u.username, u.status as user_status
     FROM teachers t
     JOIN users u ON t.user_id = u.id
     ORDER BY t.order_priority ASC, t.id ASC"
);

// Map assignments by teacher_id
$assignments = Database::fetchAll(
    "SELECT ta.*, c.class_name, sec.section_name, sub.subject_name, sub.subject_code
     FROM teacher_assignments ta
     JOIN classes c ON ta.class_id = c.id
     JOIN sections sec ON ta.section_id = sec.id
     JOIN subjects sub ON ta.subject_id = sub.id
     WHERE ta.session_id = ?
     ORDER BY c.numeric_value ASC, sec.section_name ASC",
    [$sessionId]
);
$teacherAssignmentsMap = [];
foreach ($assignments as $as) {
    $teacherAssignmentsMap[$as['teacher_id']][] = $as;
}

// 2. Sections and their attendance status for filter date
$allSections = Database::fetchAll(
    "SELECT sec.*, c.class_name, c.numeric_value 
     FROM sections sec 
     JOIN classes c ON sec.class_id = c.id 
     WHERE sec.status = 'active' AND c.status = 'active' 
     ORDER BY c.numeric_value ASC, sec.section_name ASC"
);

$firstPeriodId = (int)Database::fetchColumn("SELECT id FROM periods WHERE order_num = 1 LIMIT 1");

$sectionSubmissionRoster = [];
$totalSubmitted = 0;
$totalPending = 0;

foreach ($allSections as $sec) {
    $cid = (int)$sec['class_id'];
    $sid = (int)$sec['id'];

    // Who was scheduled for 1st period?
    $routineInfo = Database::fetchOne(
        "SELECT t.id as teacher_id, u.full_name as teacher_name, sub.subject_name, r.room_no
         FROM routines r
         JOIN teachers t ON r.teacher_id = t.id
         JOIN users u ON t.user_id = u.id
         LEFT JOIN subjects sub ON r.subject_id = sub.id
         WHERE r.session_id = ? AND r.class_id = ? AND r.section_id = ? AND r.day_of_week = ? AND r.period_id = ?",
        [$sessionId, $cid, $sid, $dayName, $firstPeriodId]
    );

    // Who submitted attendance?
    $subInfo = Database::fetchOne(
        "SELECT sub.*, u.full_name as submitter_name, r.display_name as submitter_role
         FROM attendance_submissions sub
         JOIN users u ON sub.submitted_by = u.id
         JOIN roles r ON u.role_id = r.id
         WHERE sub.session_id = ? AND sub.class_id = ? AND sub.section_id = ? AND sub.attendance_date = ?",
        [$sessionId, $cid, $sid, $filterDate]
    );

    $isSubmitted = !empty($subInfo);
    if ($isSubmitted) $totalSubmitted++; else $totalPending++;

    $sectionSubmissionRoster[] = [
        'class_id' => $cid,
        'section_id' => $sid,
        'class_name' => $sec['class_name'],
        'section_name' => $sec['section_name'],
        'scheduled_teacher' => $routineInfo['teacher_name'] ?? 'Not Assigned',
        'scheduled_subject' => $routineInfo['subject_name'] ?? '-',
        'scheduled_room' => $routineInfo['room_no'] ?? '-',
        'is_submitted' => $isSubmitted,
        'submitter_name' => $subInfo['submitter_name'] ?? null,
        'submitted_at' => $subInfo['submitted_at'] ?? null,
        'present_count' => $subInfo['present_count'] ?? 0,
        'absent_count' => $subInfo['absent_count'] ?? 0,
        'total_students' => $subInfo['total_students'] ?? 0
    ];
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-chalkboard-teacher text-success me-2"></i> Faculty & Routine Submission Compliance
        </h4>
        <p class="text-muted small mb-0">Track which teachers submitted morning attendance and inspect classroom routines.</p>
    </div>
    <form action="<?= url('headmaster/teachers.php') ?>" method="GET" class="d-flex align-items-center gap-2">
        <span class="text-muted small fw-semibold">Inspect Date:</span>
        <input type="date" name="date" class="form-control form-control-sm rounded-pill" value="<?= e($filterDate) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
    </form>
</div>

<!-- Submission Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card" style="border-top: 4px solid #123B2A;">
            <div class="text-muted small fw-semibold text-uppercase">Total Faculty</div>
            <h3 class="fw-bold text-dark mt-2 mb-0"><?= count($teachers) ?></h3>
            <small class="text-muted">Registered Teachers</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card" style="border-top: 4px solid #0d6efd;">
            <div class="text-muted small fw-semibold text-uppercase">Total Sections</div>
            <h3 class="fw-bold text-primary mt-2 mb-0"><?= count($allSections) ?></h3>
            <small class="text-muted">Class 6 to 10</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card" style="border-top: 4px solid #198754;">
            <div class="text-success small fw-semibold text-uppercase">Submitted Today</div>
            <h3 class="fw-bold text-success mt-2 mb-0"><?= $totalSubmitted ?></h3>
            <small class="text-success fw-semibold">Attendance Completed</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card" style="border-top: 4px solid #dc3545;">
            <div class="text-danger small fw-semibold text-uppercase">Pending Sections</div>
            <h3 class="fw-bold text-danger mt-2 mb-0"><?= $totalPending ?></h3>
            <small class="text-danger fw-semibold">Awaiting 1st Period Submission</small>
        </div>
    </div>
</div>

<!-- Table 1: Daily Attendance Submission Compliance -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-clipboard-check text-primary me-2"></i> Section Attendance Submission Status
            </h6>
            <small class="text-muted">For <?= date('d M Y (l)', strtotime($filterDate)) ?> &mdash; Rule: 1st Period Routine Teacher records attendance</small>
        </div>
        <span class="badge bg-light text-dark border">
            Compliance: <?= count($allSections) > 0 ? round(($totalSubmitted / count($allSections)) * 100) : 0 ?>%
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Class & Section</th>
                    <th>Designated 1st Period Teacher</th>
                    <th>Scheduled Subject / Room</th>
                    <th class="text-center">Submission Status</th>
                    <th>Actual Submitter</th>
                    <th>Submission Timestamp</th>
                    <th class="text-center">Present / Total</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sectionSubmissionRoster as $ssr): ?>
                    <tr class="<?= !$ssr['is_submitted'] ? 'table-warning-subtle' : '' ?>">
                        <td class="fw-bold text-dark">
                            <?= e($ssr['class_name']) ?> &mdash; <?= e($ssr['section_name']) ?>
                        </td>
                        <td>
                            <i class="fas fa-user-tie text-muted me-1"></i>
                            <strong><?= e($ssr['scheduled_teacher']) ?></strong>
                        </td>
                        <td>
                            <span class="small text-muted"><?= e($ssr['scheduled_subject']) ?> (<?= e($ssr['scheduled_room']) ?>)</span>
                        </td>
                        <td class="text-center">
                            <?php if ($ssr['is_submitted']): ?>
                                <span class="badge bg-success px-3 py-1 rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i> SUBMITTED
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger px-3 py-1 rounded-pill">
                                    <i class="fas fa-clock me-1"></i> PENDING
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $ssr['submitter_name'] ? e($ssr['submitter_name']) : '<span class="text-muted opacity-50">&mdash;</span>' ?>
                        </td>
                        <td class="small text-muted">
                            <?= $ssr['submitted_at'] ? date('h:i:s A', strtotime($ssr['submitted_at'])) : '<span class="text-muted opacity-50">&mdash;</span>' ?>
                        </td>
                        <td class="text-center">
                            <?php if ($ssr['is_submitted']): ?>
                                <span class="fw-bold text-success"><?= $ssr['present_count'] ?></span> / <?= $ssr['total_students'] ?>
                            <?php else: ?>
                                <span class="text-muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url("teacher/attendance.php?class_id={$ssr['class_id']}&section_id={$ssr['section_id']}&date={$filterDate}") ?>" 
                               class="btn btn-sm <?= $ssr['is_submitted'] ? 'btn-outline-success' : 'btn-danger' ?> rounded-pill px-3">
                                <i class="fas <?= $ssr['is_submitted'] ? 'fa-edit' : 'fa-clipboard-check' ?> me-1"></i> 
                                <?= $ssr['is_submitted'] ? 'View / Edit' : 'Take Attendance' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Table 2: Complete Teacher Faculty Directory & Assigned Classes -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-id-badge text-success me-2"></i> Teacher Faculty Directory & Teaching Assignments
        </h6>
        <span class="badge bg-light text-dark border"><?= count($teachers) ?> Faculty Members</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Teacher Name & Code</th>
                    <th>Official Designation</th>
                    <th>Specialization & Qualification</th>
                    <th>Assigned Classes & Subjects</th>
                    <th>Contact Information</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teachers as $t): 
                    $myAss = $teacherAssignmentsMap[$t['id']] ?? [];
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="<?= resolveUploadUrl($t['avatar'] ?? '', 'assets/images/default-avatar.png') ?>" 
                                     class="rounded-circle me-3 border" style="width: 44px; height: 44px; object-fit: cover;">
                                <div>
                                    <div class="fw-bold text-dark"><?= e($t['full_name']) ?></div>
                                    <small class="text-muted">Code: <code><?= e($t['teacher_code']) ?></code> • @<?= e($t['username']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= e($t['designation']) ?></span>
                        </td>
                        <td class="small">
                            <div class="fw-semibold text-primary"><?= e($t['specialization'] ?: 'General Education') ?></div>
                            <div class="text-muted"><?= e($t['qualification']) ?></div>
                        </td>
                        <td>
                            <?php if (!empty($myAss)): ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($myAss as $a): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= e($a['class_name']) ?> (<?= e($a['section_name']) ?>): <?= e($a['subject_name']) ?>
                                            <?php if ($a['is_class_teacher']): ?>
                                                <i class="fas fa-crown text-warning ms-1" title="Class Teacher"></i>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">No class assignments</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <div><i class="fas fa-envelope text-muted me-1"></i> <?= e($t['email']) ?></div>
                            <div><i class="fas fa-phone text-muted me-1"></i> <?= e($t['phone'] ?: '-') ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

