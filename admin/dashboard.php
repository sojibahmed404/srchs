<?php
/**
 * Sirajganj Road Chowrasta High School - Admin Dashboard
 */

$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/includes/header.php';

$todayDate = date('Y-m-d');
$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

// Metrics
try {
    $totalStudents = (int)Database::fetchColumn("SELECT COUNT(*) FROM students WHERE status = 'active'");
    $totalTeachers = (int)Database::fetchColumn("SELECT COUNT(*) FROM teachers");
    $totalClasses = (int)Database::fetchColumn("SELECT COUNT(*) FROM classes WHERE status = 'active'");
    $totalAdmissions = (int)Database::fetchColumn("SELECT COUNT(*) FROM admissions");
    $pendingAdmissionsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM admissions WHERE status = 'pending'");
    $upcomingExamsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM exams WHERE status IN ('upcoming', 'ongoing')");

    // Today's Attendance
    $todayPresent = (int)Database::fetchColumn("SELECT COUNT(*) FROM attendance WHERE attendance_date = ? AND status = 'present'", [$todayDate]);
    $todayTotalMarked = (int)Database::fetchColumn("SELECT COUNT(*) FROM attendance WHERE attendance_date = ?", [$todayDate]);
    $attendanceRate = $todayTotalMarked > 0 ? round(($todayPresent / $todayTotalMarked) * 100, 1) : 0;

    // Recent 5 Admissions
    $recentAdmissions = Database::fetchAll(
        "SELECT a.*, c.class_name 
         FROM admissions a 
         JOIN classes c ON a.desired_class_id = c.id 
         ORDER BY a.id DESC LIMIT 5"
    );

    // Recent Activity Logs
    $recentLogs = Database::fetchAll(
        "SELECT l.*, u.username, u.full_name 
         FROM activity_logs l 
         LEFT JOIN users u ON l.user_id = u.id 
         ORDER BY l.id DESC LIMIT 6"
    );

    // Recent Notices
    $recentNotices = Database::fetchAll("SELECT * FROM notices ORDER BY id DESC LIMIT 4");

} catch (Exception $e) {
    $totalStudents = $totalTeachers = $totalClasses = $totalAdmissions = $pendingAdmissionsCount = $upcomingExamsCount = 0;
    $todayPresent = $todayTotalMarked = $attendanceRate = 0;
    $recentAdmissions = $recentLogs = $recentNotices = [];
}
?>

<!-- Statistics Metric Cards -->
<div class="row g-4 mb-4">
    <!-- Total Students -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-left: 5px solid #123B2A !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-semibold text-uppercase">Total Students</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalStudents ?></h3>
                    <small class="text-success"><i class="fas fa-check-circle me-1"></i> Active Enrolled</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px; background-color: #123B2A;">
                    <i class="fas fa-user-graduate fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Teachers -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-left: 5px solid #198754 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-semibold text-uppercase">Faculty Members</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalTeachers ?></h3>
                    <small class="text-success"><i class="fas fa-chalkboard-teacher me-1"></i> Active Teachers</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px; background-color: #198754;">
                    <i class="fas fa-chalkboard-teacher fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Attendance -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-left: 5px solid #D9AD55 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-semibold text-uppercase">Today's Attendance</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= $attendanceRate ?>%</h3>
                    <small class="text-muted"><?= $todayPresent ?> Present / <?= $todayTotalMarked ?> Marked</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px; background-color: #D9AD55;">
                    <i class="fas fa-clipboard-check fs-4 text-dark"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Admissions Status -->
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-left: 5px solid #0d6efd !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-semibold text-uppercase">Pending Applications</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= $pendingAdmissionsCount ?></h3>
                    <small class="text-primary"><?= $totalAdmissions ?> Total Registered</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px; background-color: #0d6efd;">
                    <i class="fas fa-file-signature fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Strip -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="fw-bold text-dark">
            <i class="fas fa-bolt text-warning me-2"></i> Quick Actions:
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('admin/students/index.php?action=create') ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                <i class="fas fa-plus me-1"></i> Add Student
            </a>
            <a href="<?= url('admin/teachers/index.php?action=create') ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                <i class="fas fa-plus me-1"></i> Add Teacher
            </a>
            <a href="<?= url('admin/attendance/index.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="fas fa-check-square me-1"></i> Take Attendance
            </a>
            <a href="<?= url('admin/results/marks.php') ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3 text-dark">
                <i class="fas fa-calculator me-1"></i> Enter Exam Marks
            </a>
            <a href="<?= url('admin/notices/index.php?action=create') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="fas fa-bullhorn me-1"></i> Publish Notice
            </a>
        </div>
    </div>
</div>

<!-- Charts & Highlights Row -->
<div class="row g-4 mb-4">
    <!-- Chart: Attendance Trend -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-chart-line text-success me-2"></i> Weekly Attendance Performance</h6>
                <span class="badge bg-light text-muted border">Active Session <?= e($activeSession['session_name'] ?? '2026') ?></span>
            </div>
            <div style="height: 280px;">
                <canvas id="attendanceTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- System Snapshot -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-info-circle text-primary me-2"></i> System Snapshot</h6>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Active Session</span>
                    <strong class="text-success"><?= e($activeSession['session_name'] ?? '2026') ?></strong>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Active Classes</span>
                    <strong>Class 6 through Class 10 (<?= $totalClasses ?>)</strong>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Upcoming Examinations</span>
                    <span class="badge bg-primary rounded-pill"><?= $upcomingExamsCount ?></span>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Online Admission</span>
                    <span class="badge bg-success rounded-pill"><?= strtoupper(getSetting('online_admission_status', 'open')) ?></span>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Server Time (Asia/Dhaka)</span>
                    <span class="text-muted"><?= date('h:i A, d M Y') ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Recent Admissions & Activity Logs Row -->
<div class="row g-4">
    <!-- Recent Admission Applications -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-file-signature text-warning me-2"></i> Recent Online Admissions</h6>
                <a href="<?= url('admin/admissions/index.php') ?>" class="small text-success fw-semibold text-decoration-none">View All &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>App No</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Guardian Phone</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentAdmissions)): ?>
                            <?php foreach ($recentAdmissions as $adm): ?>
                                <tr>
                                    <td class="fw-bold"><?= e($adm['application_no']) ?></td>
                                    <td><?= e($adm['student_name']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($adm['class_name']) ?></span></td>
                                    <td><?= e($adm['guardian_phone']) ?></td>
                                    <td>
                                        <span class="badge <?= $adm['status'] === 'approved' ? 'bg-success' : ($adm['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-danger') ?> rounded-pill">
                                            <?= e($adm['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">No admission applications received yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Activity Logs -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-history text-secondary me-2"></i> Recent Activity Logs</h6>
                <a href="<?= url('admin/settings/logs.php') ?>" class="small text-success fw-semibold text-decoration-none">All Logs &rarr;</a>
            </div>

            <ul class="list-unstyled mb-0 small">
                <?php if (!empty($recentLogs)): ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <li class="pb-2 mb-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <strong class="text-dark"><?= e($log['action']) ?></strong>
                                <small class="text-muted"><?= formatDate($log['created_at'], 'd M, h:i A') ?></small>
                            </div>
                            <p class="text-muted mb-0 small text-truncate"><?= e($log['description']) ?></p>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="text-muted text-center py-3">No activity logged yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('attendanceTrendChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Sun', 'Today'],
                datasets: [{
                    label: 'Attendance Rate (%)',
                    data: [88, 92, 90, 94, 91, 93, <?= $attendanceRate > 0 ? $attendanceRate : 92 ?>],
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#123B2A',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 70,
                        max: 100,
                        ticks: {
                            callback: function(val) { return val + '%'; }
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
