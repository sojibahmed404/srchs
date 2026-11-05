<?php
/**
 * Head Sir Portal - Absence Monitoring & Chronic Absentee Tracker
 * Sirajganj Road Chowrasta High School
 */
$pageTitle = 'Absence Monitoring';
require_once __DIR__ . '/includes/header.php';

$preset = getString('preset') ?: 'today';
$filterClassId = getInt('class_id');
$filterSectionId = getInt('section_id');
$startDate = getString('start_date');
$endDate = getString('end_date');

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Determine date range based on preset
if ($preset === 'today') {
    $startDate = $endDate = $today;
} elseif ($preset === 'yesterday') {
    $startDate = $endDate = $yesterday;
} elseif ($preset === 'last7') {
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = $today;
} elseif ($preset === 'month') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
} elseif ($preset === 'custom') {
    if (empty($startDate)) $startDate = date('Y-m-01');
    if (empty($endDate)) $endDate = $today;
} else {
    $startDate = $endDate = $today;
}

$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$sections = Database::fetchAll("SELECT * FROM sections WHERE status = 'active' ORDER BY section_name ASC");

// 1. Fetch Students who were marked absent in this date range
$sql = "SELECT s.id, s.admission_no, s.first_name, s.last_name, s.photo,
               c.class_name, sec.section_name, e.roll_no,
               g.guardian_name, g.guardian_phone, g.father_phone,
               COUNT(att.id) AS total_absences,
               MAX(att.attendance_date) AS last_absence_date,
               GROUP_CONCAT(DATE_FORMAT(att.attendance_date, '%d %b') ORDER BY att.attendance_date DESC SEPARATOR ', ') AS absent_dates
        FROM attendance att
        JOIN students s ON att.student_id = s.id
        JOIN enrollments e ON s.id = e.student_id AND att.session_id = e.session_id
        JOIN classes c ON att.class_id = c.id
        JOIN sections sec ON att.section_id = sec.id
        LEFT JOIN guardians g ON s.guardian_id = g.id
        WHERE att.status = 'absent' 
          AND att.session_id = ?
          AND att.attendance_date BETWEEN ? AND ?";
$params = [$sessionId, $startDate, $endDate];

if ($filterClassId > 0) {
    $sql .= " AND att.class_id = ?";
    $params[] = $filterClassId;
}
if ($filterSectionId > 0) {
    $sql .= " AND att.section_id = ?";
    $params[] = $filterSectionId;
}

$sql .= " GROUP BY s.id ORDER BY total_absences DESC, c.numeric_value ASC, e.roll_no ASC";
$absentStudents = Database::fetchAll($sql, $params);

// 2. Identify Consecutive Absentees (Absent Today AND Yesterday)
$consecutiveAbsentees = Database::fetchAll(
    "SELECT s.id, s.admission_no, s.first_name, s.last_name, 
            c.class_name, sec.section_name, e.roll_no,
            g.guardian_name, g.guardian_phone, g.father_phone
     FROM students s
     JOIN enrollments e ON s.id = e.student_id AND e.session_id = ?
     JOIN classes c ON e.class_id = c.id
     JOIN sections sec ON e.section_id = sec.id
     LEFT JOIN guardians g ON s.guardian_id = g.id
     WHERE s.id IN (
         SELECT student_id FROM attendance WHERE attendance_date = ? AND status = 'absent'
     ) AND s.id IN (
         SELECT student_id FROM attendance WHERE attendance_date = ? AND status = 'absent'
     ) AND s.status = 'active'",
    [$sessionId, $today, $yesterday]
);

// 3. Class-wise Breakdown
$classAbsenceBreakdown = Database::fetchAll(
    "SELECT c.class_name, COUNT(att.id) as total_absences
     FROM attendance att
     JOIN classes c ON att.class_id = c.id
     WHERE att.status = 'absent' AND att.session_id = ? AND att.attendance_date BETWEEN ? AND ?
     GROUP BY c.id
     ORDER BY c.numeric_value ASC",
    [$sessionId, $startDate, $endDate]
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-user-clock text-danger me-2"></i> Absence Monitoring & Tracking Console
        </h4>
        <p class="text-muted small mb-0">Identify unexcused absences, track consecutive absentees, and facilitate parental follow-up.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url("headmaster/reports.php?report=absent&start_date={$startDate}&end_date={$endDate}") ?>" class="btn btn-outline-danger rounded-pill px-3">
            <i class="fas fa-print me-1"></i> Print Absentee List
        </a>
    </div>
</div>

<!-- Consecutive Absentee Warning Callout -->
<?php if (!empty($consecutiveAbsentees)): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex align-items-center gap-3 mb-2">
            <i class="fas fa-exclamation-triangle fs-3 text-danger"></i>
            <div>
                <h6 class="fw-bold text-danger mb-0">
                    High Priority Alert: <?= count($consecutiveAbsentees) ?> Student(s) Absent for Consecutive Days (Today & Yesterday)
                </h6>
                <small class="text-muted">Direct phone contact with parents is recommended under school safety and attendance guidelines.</small>
            </div>
        </div>
        <div class="table-responsive bg-white rounded-3 mt-3 border">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student Name & ID</th>
                        <th>Class & Section</th>
                        <th>Roll</th>
                        <th>Guardian</th>
                        <th>Phone Number</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consecutiveAbsentees as $ca): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= e($ca['first_name'] . ' ' . $ca['last_name']) ?> <small class="text-muted">(<?= e($ca['admission_no']) ?>)</small></td>
                            <td><span class="badge bg-danger-subtle text-danger"><?= e($ca['class_name']) ?> - <?= e($ca['section_name']) ?></span></td>
                            <td class="fw-bold"><?= $ca['roll_no'] ?></td>
                            <td><?= e($ca['guardian_name'] ?: 'Guardian') ?></td>
                            <td>
                                <a href="tel:<?= e($ca['guardian_phone'] ?: $ca['father_phone']) ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">
                                    <i class="fas fa-phone-alt me-1"></i> Call <?= e($ca['guardian_phone'] ?: ($ca['father_phone'] ?: 'N/A')) ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('headmaster/students.php?student_id=' . $ca['id']) ?>" class="btn btn-sm btn-light border rounded-pill">
                                    Dossier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Filter Presets & Date Range -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="<?= url('headmaster/absences.php?preset=today') ?>" class="btn btn-sm <?= $preset === 'today' ? 'btn-danger' : 'btn-outline-secondary' ?> rounded-pill px-3">
            Today (<?= date('d M') ?>)
        </a>
        <a href="<?= url('headmaster/absences.php?preset=yesterday') ?>" class="btn btn-sm <?= $preset === 'yesterday' ? 'btn-danger' : 'btn-outline-secondary' ?> rounded-pill px-3">
            Yesterday (<?= date('d M', strtotime('-1 day')) ?>)
        </a>
        <a href="<?= url('headmaster/absences.php?preset=last7') ?>" class="btn btn-sm <?= $preset === 'last7' ? 'btn-danger' : 'btn-outline-secondary' ?> rounded-pill px-3">
            Last 7 Days
        </a>
        <a href="<?= url('headmaster/absences.php?preset=month') ?>" class="btn btn-sm <?= $preset === 'month' ? 'btn-danger' : 'btn-outline-secondary' ?> rounded-pill px-3">
            Current Month (<?= date('F Y') ?>)
        </a>
    </div>

    <form action="<?= url('headmaster/absences.php') ?>" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="preset" value="custom">

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-dark">From Date</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-dark">To Date</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>" max="<?= date('Y-m-d') ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold text-dark">Class</label>
            <select name="class_id" class="form-select">
                <option value="">All Classes</option>
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClassId == $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['class_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label small fw-semibold text-dark">Section</label>
            <select name="section_id" class="form-select">
                <option value="">All Sections</option>
                <?php foreach ($sections as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filterSectionId == $s['id'] ? 'selected' : '' ?>>
                        <?= e($s['section_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-danger w-100 rounded-3">
                <i class="fas fa-filter me-1"></i> Apply Filter
            </button>
        </div>
    </form>
</div>

<!-- Absence Roster Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-list-ul text-danger me-2"></i> Absent Students Register
            </h6>
            <small class="text-muted">Period: <?= formatDate($startDate) ?> to <?= formatDate($endDate) ?></small>
        </div>
        <span class="badge bg-danger px-3 py-1 rounded-pill">
            <?= count($absentStudents) ?> Absent Students
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;" class="text-center">Roll</th>
                    <th>Student Name & ID</th>
                    <th>Class & Section</th>
                    <th class="text-center">Total Absences</th>
                    <th>Absent Dates</th>
                    <th>Guardian Contact</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($absentStudents)): ?>
                    <?php foreach ($absentStudents as $st): ?>
                        <tr>
                            <td class="text-center fw-bold text-danger fs-6">
                                <?= str_pad((string)$st['roll_no'], 2, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">
                                    <?= e($st['first_name'] . ' ' . $st['last_name']) ?>
                                </div>
                                <div class="small text-muted">
                                    ID: <code><?= e($st['admission_no']) ?></code>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= e($st['class_name']) ?> - <?= e($st['section_name']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $st['total_absences'] >= 3 ? 'bg-danger' : 'bg-warning text-dark' ?> fs-6 px-2 py-1">
                                    <?= $st['total_absences'] ?> Days
                                </span>
                            </td>
                            <td class="small text-muted" style="max-width: 250px;">
                                <?= e($st['absent_dates']) ?>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?= e($st['guardian_name'] ?: 'Guardian') ?></div>
                                <a href="tel:<?= e($st['guardian_phone'] ?: $st['father_phone']) ?>" class="small text-success text-decoration-none fw-bold">
                                    <i class="fas fa-phone-alt me-1"></i> <?= e($st['guardian_phone'] ?: ($st['father_phone'] ?: 'N/A')) ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('headmaster/students.php?student_id=' . $st['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    Dossier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fs-2 text-success d-block mb-2"></i>
                            Excellent! No absences recorded for the selected filter period.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

