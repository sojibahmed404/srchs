<?php
/**
 * Sirajganj Road Chowrasta High School - Reports & Printable Export
 */

$pageTitle = 'Reports & Data Export';
require_once __DIR__ . '/../includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$exams = Database::fetchAll("SELECT * FROM exams WHERE session_id = ? ORDER BY start_date DESC", [$sessionId]);

$reportType = getString('report_type') ?: 'students';
$selectedClassId = getInt('class_id') ?: ($classes[0]['id'] ?? 1);
$selectedExamId = getInt('exam_id') ?: ($exams[0]['id'] ?? 0);

// Data for reports
$reportData = [];

if ($reportType === 'students') {
    $reportData = Database::fetchAll(
        "SELECT s.*, e.roll_no, c.class_name, sec.section_name, g.father_name, g.guardian_phone 
         FROM enrollments e 
         JOIN students s ON e.student_id = s.id 
         JOIN classes c ON e.class_id = c.id 
         JOIN sections sec ON e.section_id = sec.id 
         LEFT JOIN guardians g ON s.guardian_id = g.id 
         WHERE e.session_id = ? AND e.class_id = ? AND s.status = 'active' 
         ORDER BY sec.section_name ASC, e.roll_no ASC",
        [$sessionId, $selectedClassId]
    );
} elseif ($reportType === 'teachers') {
    $reportData = Database::fetchAll(
        "SELECT t.*, u.full_name, u.email, u.phone 
         FROM teachers t 
         JOIN users u ON t.user_id = u.id 
         ORDER BY t.order_priority ASC"
    );
} elseif ($reportType === 'fees') {
    $reportData = Database::fetchAll(
        "SELECT sf.*, s.first_name, s.last_name, s.admission_no, e.roll_no, c.class_name, ft.fee_name 
         FROM student_fees sf 
         JOIN students s ON sf.student_id = s.id 
         JOIN enrollments e ON (e.student_id = s.id AND e.session_id = sf.session_id) 
         JOIN classes c ON e.class_id = c.id 
         JOIN fee_types ft ON sf.fee_type_id = ft.id 
         WHERE sf.session_id = ? AND e.class_id = ? 
         ORDER BY e.roll_no ASC",
        [$sessionId, $selectedClassId]
    );
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Reports & Printable Documents</h4>
        <p class="text-muted small mb-0">Generate official student rosters, faculty lists, and institutional transcripts.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-success rounded-pill px-4 shadow-sm">
            <i class="fas fa-print me-1"></i> Print / Save as PDF
        </button>
    </div>
</div>

<!-- Report Selection Filter -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form action="<?= url('admin/reports/index.php') ?>" method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Report Type</label>
            <select name="report_type" class="form-select" onchange="this.form.submit()">
                <option value="students" <?= $reportType === 'students' ? 'selected' : '' ?>>Student Class Roster</option>
                <option value="teachers" <?= $reportType === 'teachers' ? 'selected' : '' ?>>Faculty & Staff Register</option>
                <option value="fees" <?= $reportType === 'fees' ? 'selected' : '' ?>>Class Fee Dues Report</option>
            </select>
        </div>

        <?php if ($reportType === 'students' || $reportType === 'fees'): ?>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Select Class</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-success w-100 rounded-3">Generate</button>
        </div>
    </form>
</div>

<!-- Printable Report Card -->
<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4" id="printableReportSection">
    <!-- School Header for Print -->
    <div class="text-center pb-3 border-bottom border-2 border-dark mb-4">
        <h3 class="fw-bold mb-0 text-dark" style="font-family: 'Hind Siliguri', sans-serif;">সিরাজগঞ্জ রোড চৌরাস্তা উচ্চ বিদ্যালয়</h3>
        <h5 class="fw-bold text-secondary mb-0">Sirajganj Road Chowrasta High School</h5>
        <small class="text-muted">Hatikumrul, Ullapara, Sirajganj | Established 1974 | EIIN: <?= e(getSetting('school_eiin', '128456')) ?></small>
        <div class="badge bg-success text-uppercase mt-2 px-3 py-1 fs-6">
            Official Report: <?= ucfirst($reportType) ?> (Session <?= e($activeSession['session_name'] ?? '2026') ?>)
        </div>
    </div>

    <!-- Student Roster Report -->
    <?php if ($reportType === 'students'): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle small mb-0">
                <thead style="background-color: #123B2A; color: white;">
                    <tr>
                        <th style="width: 60px;">Roll</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Section</th>
                        <th>Gender</th>
                        <th>Guardian Name</th>
                        <th>Contact Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reportData)): ?>
                        <?php foreach ($reportData as $stu): ?>
                            <tr>
                                <td class="fw-bold text-center"><?= e((string)$stu['roll_no']) ?></td>
                                <td class="fw-semibold text-secondary"><?= e($stu['admission_no']) ?></td>
                                <td class="fw-bold text-dark"><?= e($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                                <td><?= e($stu['section_name']) ?></td>
                                <td><?= e($stu['gender']) ?></td>
                                <td><?= e($stu['father_name'] ?: '-') ?></td>
                                <td><?= e($stu['guardian_phone'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No students enrolled in this class.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <!-- Faculty Register Report -->
    <?php elseif ($reportType === 'teachers'): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle small mb-0">
                <thead style="background-color: #123B2A; color: white;">
                    <tr>
                        <th>Code</th>
                        <th>Teacher Name</th>
                        <th>Designation</th>
                        <th>Subject Specialization</th>
                        <th>Qualification</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Joining Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $t): ?>
                        <tr>
                            <td class="fw-bold text-success"><?= e($t['teacher_code']) ?></td>
                            <td class="fw-bold text-dark"><?= e($t['full_name']) ?></td>
                            <td><?= e($t['designation']) ?></td>
                            <td><?= e($t['specialization'] ?: 'General') ?></td>
                            <td><?= e($t['qualification']) ?></td>
                            <td><?= e($t['email']) ?></td>
                            <td><?= e($t['phone'] ?: '-') ?></td>
                            <td><?= formatDate($t['joining_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <!-- Fee Report -->
    <?php elseif ($reportType === 'fees'): ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle small mb-0">
                <thead style="background-color: #123B2A; color: white;">
                    <tr>
                        <th>Roll</th>
                        <th>Student Name</th>
                        <th>Admission No</th>
                        <th>Fee Particulars</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reportData)): ?>
                        <?php foreach ($reportData as $f): ?>
                            <tr>
                                <td class="fw-bold text-center"><?= e((string)$f['roll_no']) ?></td>
                                <td class="fw-semibold text-dark"><?= e($f['first_name'] . ' ' . $f['last_name']) ?></td>
                                <td><?= e($f['admission_no']) ?></td>
                                <td><?= e($f['fee_name']) ?> (<?= e($f['month'] ?: 'Yearly') ?>)</td>
                                <td class="fw-bold"><?= formatCurrency($f['amount']) ?></td>
                                <td><?= formatDate($f['due_date']) ?></td>
                                <td><span class="badge <?= $f['status'] === 'paid' ? 'bg-success' : 'bg-danger' ?> text-uppercase"><?= e($f['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No fee records found for this class.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Signature lines for official print -->
    <div class="row pt-5 mt-5 text-center">
        <div class="col-4">
            <div class="border-top pt-2 small text-muted">Prepared By</div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 small text-muted">Verified by Office Staff</div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 small text-muted">Headmaster Signature & Stamp</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
