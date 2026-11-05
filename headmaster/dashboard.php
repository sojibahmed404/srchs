<?php
/**
 * Head Sir Monitoring Dashboard
 * Sirajganj Road Chowrasta High School
 */
$pageTitle = 'School Monitoring Dashboard';
require_once __DIR__ . '/includes/header.php';

$filterDate = getString('date') ?: date('Y-m-d');
$dayName = date('l', strtotime($filterDate));

// 1. Overall School Metrics
$totalStudents = (int)Database::fetchColumn("SELECT COUNT(*) FROM students WHERE status = 'active'");
$totalTeachers = (int)Database::fetchColumn("SELECT COUNT(*) FROM teachers");

// Attendance Metrics for Filter Date
$presentToday = (int)Database::fetchColumn(
    "SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'present' AND session_id = ?",
    [$filterDate, $sessionId]
);
$absentToday = (int)Database::fetchColumn(
    "SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'absent' AND session_id = ?",
    [$filterDate, $sessionId]
);
$lateToday = (int)Database::fetchColumn(
    "SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'late' AND session_id = ?",
    [$filterDate, $sessionId]
);
$leaveToday = (int)Database::fetchColumn(
    "SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'excused' AND session_id = ?",
    [$filterDate, $sessionId]
);

$totalMarkedToday = $presentToday + $absentToday + $lateToday + $leaveToday;
$overallRate = $totalStudents > 0 && $totalMarkedToday > 0 ? round(($presentToday / $totalStudents) * 100, 1) : 0;

// 2. Class-wise Attendance Statistics (Classes 6 to 10 and Sections)
$allClasses = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$allSections = Database::fetchAll(
    "SELECT sec.*, c.class_name, c.numeric_value 
     FROM sections sec 
     JOIN classes c ON sec.class_id = c.id 
     WHERE sec.status = 'active' AND c.status = 'active' 
     ORDER BY c.numeric_value ASC, sec.section_name ASC"
);

// Get 1st Period ID
$firstPeriodId = (int)Database::fetchColumn("SELECT id FROM periods WHERE order_num = 1 LIMIT 1");

$classSectionStats = [];
$totalSubmittedSections = 0;

foreach ($allSections as $sec) {
    $cid = (int)$sec['class_id'];
    $sid = (int)$sec['id'];

    // Enrolled student count for this section
    $enrolledCount = (int)Database::fetchColumn(
        "SELECT COUNT(*) FROM enrollments e 
         JOIN students s ON e.student_id = s.id 
         WHERE e.class_id = ? AND e.section_id = ? AND e.session_id = ? AND s.status = 'active'",
        [$cid, $sid, $sessionId]
    );

    // Attendance breakdown for filter date
    $pres = (int)Database::fetchColumn(
        "SELECT COUNT(*) FROM attendance 
         WHERE class_id = ? AND section_id = ? AND attendance_date = ? AND session_id = ? AND status = 'present'",
        [$cid, $sid, $filterDate, $sessionId]
    );
    $abs = (int)Database::fetchColumn(
        "SELECT COUNT(*) FROM attendance 
         WHERE class_id = ? AND section_id = ? AND attendance_date = ? AND session_id = ? AND status = 'absent'",
        [$cid, $sid, $filterDate, $sessionId]
    );
    $lat = (int)Database::fetchColumn(
        "SELECT COUNT(*) FROM attendance 
         WHERE class_id = ? AND section_id = ? AND attendance_date = ? AND session_id = ? AND status = 'late'",
        [$cid, $sid, $filterDate, $sessionId]
    );
    $exc = (int)Database::fetchColumn(
        "SELECT COUNT(*) FROM attendance 
         WHERE class_id = ? AND section_id = ? AND attendance_date = ? AND session_id = ? AND status = 'excused'",
        [$cid, $sid, $filterDate, $sessionId]
    );

    // Submission record
    $subInfo = Database::fetchOne(
        "SELECT sub.*, u.full_name as submitter_name 
         FROM attendance_submissions sub 
         JOIN users u ON sub.submitted_by = u.id 
         WHERE sub.class_id = ? AND sub.section_id = ? AND sub.attendance_date = ? AND sub.session_id = ?",
        [$cid, $sid, $filterDate, $sessionId]
    );

    // Routine 1st period teacher for today
    $routineTeacher = Database::fetchOne(
        "SELECT u.full_name as teacher_name, sub.subject_name 
         FROM routines r 
         JOIN teachers t ON r.teacher_id = t.id 
         JOIN users u ON t.user_id = u.id 
         LEFT JOIN subjects sub ON r.subject_id = sub.id 
         WHERE r.class_id = ? AND r.section_id = ? AND r.session_id = ? AND r.day_of_week = ? AND r.period_id = ?",
        [$cid, $sid, $sessionId, $dayName, $firstPeriodId]
    );

    $isSubmitted = !empty($subInfo) || ($pres + $abs + $lat + $exc > 0);
    if ($isSubmitted) $totalSubmittedSections++;

    $percentage = $enrolledCount > 0 ? round(($pres / $enrolledCount) * 100, 2) : 0;

    $classSectionStats[] = [
        'class_id' => $cid,
        'section_id' => $sid,
        'class_name' => $sec['class_name'],
        'section_name' => $sec['section_name'],
        'total' => $enrolledCount,
        'present' => $pres,
        'absent' => $abs,
        'late' => $lat,
        'leave' => $exc,
        'percentage' => $percentage,
        'is_submitted' => $isSubmitted,
        'submitter' => $subInfo['submitter_name'] ?? null,
        'submitted_at' => $subInfo['submitted_at'] ?? null,
        'first_class_teacher' => $routineTeacher['teacher_name'] ?? 'Not Assigned',
        'first_class_subject' => $routineTeacher['subject_name'] ?? '-'
    ];
}

// 3. Past 7-Day Attendance Trend
$chartLabels = [];
$chartPresent = [];
$chartAbsent = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days", strtotime($filterDate)));
    $p = (int)Database::fetchColumn("SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'present' AND session_id = ?", [$d, $sessionId]);
    $a = (int)Database::fetchColumn("SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'absent' AND session_id = ?", [$d, $sessionId]);
    $chartLabels[] = ($i === 0) ? 'Selected (' . date('d M', strtotime($d)) . ')' : date('D d', strtotime($d));
    $chartPresent[] = $p;
    $chartAbsent[] = $a;
}
?>

<!-- Welcome Banner -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-school text-success me-2"></i> Principal / Head Sir Monitoring Console
        </h4>
        <p class="text-muted small mb-0">Real-time institution attendance metrics, classroom tracking, and faculty compliance.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <!-- Date Selector Form -->
        <form action="<?= url('headmaster/dashboard.php') ?>" method="GET" class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-semibold">Inspect Date:</span>
            <input type="date" name="date" class="form-control form-control-sm rounded-pill" value="<?= e($filterDate) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
        </form>
        <a href="<?= url('headmaster/reports.php?date=' . $filterDate) ?>" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
            <i class="fas fa-print me-1"></i> Print Daily Report
        </a>
    </div>
</div>

<?= displayFlash() ?>

<!-- Section Submission Alert -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
                 style="width: 46px; height: 46px; background: <?= $totalSubmittedSections === count($allSections) ? '#198754' : '#ffc107' ?>; color: #123B2A;">
                <i class="fas <?= $totalSubmittedSections === count($allSections) ? 'fa-check' : 'fa-clock' ?> fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    Daily Attendance Progress: <?= $totalSubmittedSections ?> / <?= count($allSections) ?> Sections Completed
                </h6>
                <small class="text-muted">
                    <?= count($allSections) - $totalSubmittedSections ?> sections still pending attendance submission for <?= date('d F Y (l)', strtotime($filterDate)) ?>.
                </small>
            </div>
        </div>
        <a href="<?= url('headmaster/teachers.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            <i class="fas fa-tasks me-1"></i> View Teacher Submission Log
        </a>
    </div>
</div>

<!-- A. Daily Overview Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Total Students -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card" style="border-top: 4px solid #123B2A;">
            <div class="text-muted small fw-semibold text-uppercase">Total Students</div>
            <h3 class="fw-bold text-dark mt-2 mb-0"><?= number_format($totalStudents) ?></h3>
            <small class="text-muted">Enrolled in school</small>
        </div>
    </div>

    <!-- Total Teachers -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card" style="border-top: 4px solid #0d6efd;">
            <div class="text-muted small fw-semibold text-uppercase">Total Teachers</div>
            <h3 class="fw-bold text-primary mt-2 mb-0"><?= number_format($totalTeachers) ?></h3>
            <small class="text-muted">Active faculty</small>
        </div>
    </div>

    <!-- Present Today -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card" style="border-top: 4px solid #198754;">
            <div class="text-success small fw-semibold text-uppercase">Present Today</div>
            <h3 class="fw-bold text-success mt-2 mb-0"><?= number_format($presentToday) ?></h3>
            <small class="text-muted">Attended classes</small>
        </div>
    </div>

    <!-- Absent Today -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card" style="border-top: 4px solid #dc3545;">
            <div class="text-danger small fw-semibold text-uppercase">Absent Today</div>
            <h3 class="fw-bold text-danger mt-2 mb-0"><?= number_format($absentToday) ?></h3>
            <small class="text-danger fw-semibold">Requires follow-up</small>
        </div>
    </div>

    <!-- On Leave Today -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card" style="border-top: 4px solid #0dcaf0;">
            <div class="text-info small fw-semibold text-uppercase">On Leave</div>
            <h3 class="fw-bold text-info mt-2 mb-0"><?= number_format($leaveToday) ?></h3>
            <small class="text-muted">Official leave</small>
        </div>
    </div>

    <!-- Overall Attendance % -->
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card" style="border-top: 4px solid #D9AD55;">
            <div class="text-warning small fw-semibold text-uppercase">Attendance Rate</div>
            <h3 class="fw-bold mt-2 mb-0" style="color: #b88f3a;"><?= $overallRate ?>%</h3>
            <small class="text-muted">Based on total students</small>
        </div>
    </div>
</div>

<!-- Quick Student Lookup Bar -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h6 class="fw-bold text-dark mb-1">
                    <i class="fas fa-search text-success me-2"></i> Quick Student Search
                </h6>
                <p class="text-muted small mb-lg-0">Search instantly by Student Name, Admission ID, Roll, or Guardian Mobile Number.</p>
            </div>
            <div class="col-lg-6">
                <form action="<?= url('headmaster/students.php') ?>" method="GET" class="input-group">
                    <input type="text" name="q" class="form-control rounded-start-pill ps-3" placeholder="Enter student name, ID or mobile...">
                    <button type="submit" class="btn btn-success rounded-end-pill px-4">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- B. Class-wise Attendance Statistics Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold text-dark mb-0">
                <i class="fas fa-layer-group text-primary me-2"></i> Class-wise Attendance Roster
            </h6>
            <small class="text-muted">Official live data for Classes 6 to 10 on <?= date('d M Y (l)', strtotime($filterDate)) ?></small>
        </div>
        <span class="badge bg-light text-dark border">
            Total Sections: <?= count($allSections) ?>
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Class</th>
                    <th>Section</th>
                    <th class="text-center">Total Students</th>
                    <th class="text-center">Present</th>
                    <th class="text-center">Absent</th>
                    <th class="text-center">Late / Leave</th>
                    <th style="width: 170px;">Attendance Rate</th>
                    <th>Status</th>
                    <th>1st Class Teacher (Routine)</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classSectionStats as $row): ?>
                    <tr>
                        <td class="fw-bold text-dark"><?= e($row['class_name']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1"><?= e($row['section_name']) ?></span>
                        </td>
                        <td class="text-center fw-semibold"><?= $row['total'] ?></td>
                        <td class="text-center text-success fw-bold"><?= $row['present'] ?></td>
                        <td class="text-center text-danger fw-bold"><?= $row['absent'] ?></td>
                        <td class="text-center text-muted small"><?= $row['late'] ?> / <?= $row['leave'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar <?= $row['percentage'] >= 80 ? 'bg-success' : ($row['percentage'] >= 60 ? 'bg-warning' : 'bg-danger') ?>" 
                                         role="progressbar" style="width: <?= $row['percentage'] ?>%"></div>
                                </div>
                                <span class="small fw-bold"><?= $row['percentage'] ?>%</span>
                            </div>
                        </td>
                        <td>
                            <?php if ($row['is_submitted']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fas fa-check-circle me-1"></i> Submitted
                                </span>
                                <?php if ($row['submitter']): ?>
                                    <div class="text-muted" style="font-size: 0.7rem;">
                                        By <?= e($row['submitter']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <i class="fas fa-exclamation-circle me-1"></i> Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark"><?= e($row['first_class_teacher']) ?></div>
                            <small class="text-muted"><?= e($row['first_class_subject']) ?></small>
                        </td>
                        <td class="text-end">
                            <a href="<?= url("teacher/attendance.php?class_id={$row['class_id']}&section_id={$row['section_id']}&date={$filterDate}") ?>" 
                               class="btn btn-sm btn-outline-success rounded-pill px-3" title="Inspect or Update Records">
                                <i class="fas fa-eye me-1"></i> Inspect
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 7-Day Attendance Trend Chart -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-chart-line text-success me-2"></i> School-Wide 7-Day Attendance Trend
        </h6>
        <span class="badge bg-light text-muted border">Daily Student Counts</span>
    </div>
    <div style="height: 250px;">
        <canvas id="headmasterTrendChart"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('headmasterTrendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Present Students',
                        data: <?= json_encode($chartPresent) ?>,
                        backgroundColor: '#198754',
                        borderRadius: 6
                    },
                    {
                        label: 'Absent Students',
                        data: <?= json_encode($chartAbsent) ?>,
                        backgroundColor: '#dc3545',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

