<?php
/**
 * Head Sir Portal - Official Printable School Reports
 * Sirajganj Road Chowrasta High School
 */
$pageTitle = 'Printable Official Reports';
require_once __DIR__ . '/includes/header.php';

$reportType = getString('report') ?: 'daily';
$selectedDate = getString('date') ?: date('Y-m-d');
$selectedClassId = getInt('class_id') ?: 1;
$selectedSectionId = getInt('section_id') ?: 1;
$selectedMonth = getString('month') ?: date('Y-m');

$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$sections = Database::fetchAll("SELECT * FROM sections WHERE status = 'active' ORDER BY section_name ASC");

// Fetch Report Data based on reportType:
// 1. Daily Report
$dailyClasses = [];
$dailyTotalStudents = 0;
$dailyTotalPresent = 0;
$dailyTotalAbsent = 0;
$dailyTotalLate = 0;
$dailyTotalLeave = 0;

if ($reportType === 'daily' || $reportType === 'classwise') {
    $secList = Database::fetchAll(
        "SELECT sec.id as section_id, sec.section_name, c.id as class_id, c.class_name 
         FROM sections sec 
         JOIN classes c ON sec.class_id = c.id 
         WHERE sec.status = 'active' AND c.status = 'active'
         ORDER BY c.numeric_value ASC, sec.section_name ASC"
    );

    foreach ($secList as $sc) {
        $cid = (int)$sc['class_id'];
        $sid = (int)$sc['section_id'];

        $enrolled = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM enrollments e JOIN students s ON e.student_id = s.id 
             WHERE e.class_id = ? AND e.section_id = ? AND e.session_id = ? AND s.status = 'active'",
            [$cid, $sid, $sessionId]
        );

        $pres = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM attendance WHERE class_id = ? AND section_id = ? AND attendance_date = ? AND session_id = ? AND status = 'present'",
            [$cid, $sid, $selectedDate, $sessionId]
        );
        $abs = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM attendance WHERE class_id = ? AND section_id = ? AND attendance_date = ? AND session_id = ? AND status = 'absent'",
            [$cid, $sid, $selectedDate, $sessionId]
        );
        $lat = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM attendance WHERE class_id = ? AND section_id = ? AND attendance_date = ? AND session_id = ? AND status = 'late'",
            [$cid, $sid, $selectedDate, $sessionId]
        );
        $exc = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM attendance WHERE class_id = ? AND section_id = ? AND attendance_date = ? AND session_id = ? AND status = 'excused'",
            [$cid, $sid, $selectedDate, $sessionId]
        );

        $dailyTotalStudents += $enrolled;
        $dailyTotalPresent += $pres;
        $dailyTotalAbsent += $abs;
        $dailyTotalLate += $lat;
        $dailyTotalLeave += $exc;

        $rate = $enrolled > 0 ? round(($pres / $enrolled) * 100, 1) : 0;

        $dailyClasses[] = [
            'class_name' => $sc['class_name'],
            'section_name' => $sc['section_name'],
            'enrolled' => $enrolled,
            'present' => $pres,
            'absent' => $abs,
            'late' => $lat,
            'leave' => $exc,
            'rate' => $rate
        ];
    }
}

// 2. Classwise Detailed Roster Report
$classwiseStudents = [];
$currentClassInfo = null;
if ($reportType === 'classwise') {
    $currentClassInfo = Database::fetchOne(
        "SELECT c.class_name, sec.section_name 
         FROM sections sec JOIN classes c ON sec.class_id = c.id 
         WHERE c.id = ? AND sec.id = ?",
        [$selectedClassId, $selectedSectionId]
    );

    $classwiseStudents = Database::fetchAll(
        "SELECT s.admission_no, s.first_name, s.last_name, s.gender, e.roll_no,
                att.status, att.remarks, u.full_name as marked_by_name
         FROM enrollments e
         JOIN students s ON e.student_id = s.id
         LEFT JOIN attendance att ON (att.student_id = s.id AND att.attendance_date = ?)
         LEFT JOIN users u ON att.marked_by = u.id
         WHERE e.class_id = ? AND e.section_id = ? AND e.session_id = ? AND s.status = 'active'
         ORDER BY e.roll_no ASC",
        [$selectedDate, $selectedClassId, $selectedSectionId, $sessionId]
    );
}

// 3. Absent Students List
$absentReportList = [];
if ($reportType === 'absent') {
    $absentReportList = Database::fetchAll(
        "SELECT s.admission_no, s.first_name, s.last_name, 
                c.class_name, sec.section_name, e.roll_no,
                g.guardian_name, g.guardian_phone, g.present_address,
                att.remarks
         FROM attendance att
         JOIN students s ON att.student_id = s.id
         JOIN enrollments e ON s.id = e.student_id AND att.session_id = e.session_id
         JOIN classes c ON att.class_id = c.id
         JOIN sections sec ON att.section_id = sec.id
         LEFT JOIN guardians g ON s.guardian_id = g.id
         WHERE att.attendance_date = ? AND att.status = 'absent' AND att.session_id = ?
         ORDER BY c.numeric_value ASC, sec.section_name ASC, e.roll_no ASC",
        [$selectedDate, $sessionId]
    );
}

// 4. Teacher Attendance Submission Compliance
$submissionsList = [];
if ($reportType === 'submissions') {
    $secList = Database::fetchAll(
        "SELECT sec.id as section_id, sec.section_name, c.id as class_id, c.class_name 
         FROM sections sec 
         JOIN classes c ON sec.class_id = c.id 
         WHERE sec.status = 'active' AND c.status = 'active'
         ORDER BY c.numeric_value ASC, sec.section_name ASC"
    );

    foreach ($secList as $sc) {
        $sub = Database::fetchOne(
            "SELECT sub.*, u.full_name as submitter_name 
             FROM attendance_submissions sub 
             JOIN users u ON sub.submitted_by = u.id 
             WHERE sub.class_id = ? AND sub.section_id = ? AND sub.attendance_date = ? AND sub.session_id = ?",
            [$sc['class_id'], $sc['section_id'], $selectedDate, $sessionId]
        );

        $submissionsList[] = [
            'class_name' => $sc['class_name'],
            'section_name' => $sc['section_name'],
            'is_submitted' => !empty($sub),
            'submitter_name' => $sub['submitter_name'] ?? 'Pending',
            'submitted_at' => $sub['submitted_at'] ?? null,
            'present_count' => $sub['present_count'] ?? 0,
            'absent_count' => $sub['absent_count'] ?? 0,
            'total_students' => $sub['total_students'] ?? 0
        ];
    }
}
?>

<!-- Filter & Type Selector Card (Hidden on Print) -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4 no-print">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fas fa-print text-success me-2"></i> Report Generator & Printable Sheets
        </h5>
        <button onclick="window.print()" class="btn btn-success rounded-pill px-4 shadow-sm">
            <i class="fas fa-print me-1"></i> Print / Save as PDF
        </button>
    </div>

    <!-- Report Type Tabs -->
    <div class="d-flex flex-wrap gap-2 mb-4 border-bottom pb-3">
        <a href="?report=daily&date=<?= e($selectedDate) ?>" class="btn btn-sm <?= $reportType === 'daily' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
            <i class="fas fa-school me-1"></i> 1. Daily School Overview
        </a>
        <a href="?report=classwise&date=<?= e($selectedDate) ?>&class_id=<?= $selectedClassId ?>&section_id=<?= $selectedSectionId ?>" class="btn btn-sm <?= $reportType === 'classwise' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
            <i class="fas fa-users me-1"></i> 2. Class Section Roster
        </a>
        <a href="?report=absent&date=<?= e($selectedDate) ?>" class="btn btn-sm <?= $reportType === 'absent' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
            <i class="fas fa-user-times me-1"></i> 3. Absent Students List
        </a>
        <a href="?report=submissions&date=<?= e($selectedDate) ?>" class="btn btn-sm <?= $reportType === 'submissions' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
            <i class="fas fa-clipboard-check me-1"></i> 4. Teacher Submission Compliance
        </a>
    </div>

    <!-- Parameter Filters -->
    <form action="<?= url('headmaster/reports.php') ?>" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="report" value="<?= e($reportType) ?>">

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-dark">Date</label>
            <input type="date" name="date" class="form-control" value="<?= e($selectedDate) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
        </div>

        <?php if ($reportType === 'classwise'): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark">Class</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['class_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark">Section</label>
                <select name="section_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($sections as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selectedSectionId == $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['section_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100 rounded-3">
                <i class="fas fa-sync me-1"></i> Generate
            </button>
        </div>
    </form>
</div>

<!-- ==================== OFFICIAL PRINTABLE REPORT CONTAINER ==================== -->
<div class="card border-0 shadow rounded-4 bg-white p-4 p-md-5 mb-5 print-sheet">
    <!-- School Letterhead Header -->
    <div class="text-center pb-3 mb-4 border-bottom border-dark border-2">
        <div class="d-flex align-items-center justify-content-center gap-3 mb-1">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
                 style="width: 55px; height: 55px; background: #123B2A;">
                <i class="fas fa-school fs-3 text-warning"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-0 text-dark" style="font-family: 'Hind Siliguri', sans-serif;">
                    সিরাজগঞ্জ রোড চৌরাস্তা উচ্চ বিদ্যালয়
                </h3>
                <h5 class="fw-bold text-dark mb-0">
                    Sirajganj Road Chowrasta High School
                </h5>
            </div>
        </div>
        <p class="text-muted small mb-1">
            Hatikumrul, Ullapara, Sirajganj, Bangladesh | EIIN: 128456 | Estd: 1974 | Board: Rajshahi
        </p>
        <div class="badge bg-dark px-3 py-1 fs-6 text-uppercase" style="letter-spacing: 0.5px;">
            <?php if ($reportType === 'daily'): ?>
                Daily School Attendance Report
            <?php elseif ($reportType === 'classwise'): ?>
                Class Attendance Register: <?= e($currentClassInfo['class_name'] ?? '') ?> (<?= e($currentClassInfo['section_name'] ?? '') ?>)
            <?php elseif ($reportType === 'absent'): ?>
                Official Student Absence Roster
            <?php elseif ($reportType === 'submissions'): ?>
                Teacher Morning Attendance Submission Compliance
            <?php endif; ?>
        </div>
        <div class="small text-muted mt-2">
            <strong>Date:</strong> <?= date('d F Y (l)', strtotime($selectedDate)) ?> &bull; 
            <strong>Academic Session:</strong> <?= e($currentSession['session_name'] ?? '2026') ?>
        </div>
    </div>

    <!-- ==================== REPORT CONTENT: 1. DAILY ==================== -->
    <?php if ($reportType === 'daily'): ?>
        <!-- Summary Numbers -->
        <div class="row g-2 mb-4 text-center">
            <div class="col-3">
                <div class="p-2 border rounded-3 bg-light">
                    <small class="text-muted fw-bold">Total Enrolled</small>
                    <div class="fs-5 fw-bold text-dark"><?= $dailyTotalStudents ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="p-2 border rounded-3 bg-light">
                    <small class="text-success fw-bold">Total Present</small>
                    <div class="fs-5 fw-bold text-success"><?= $dailyTotalPresent ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="p-2 border rounded-3 bg-light">
                    <small class="text-danger fw-bold">Total Absent</small>
                    <div class="fs-5 fw-bold text-danger"><?= $dailyTotalAbsent ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="p-2 border rounded-3 bg-light">
                    <small class="text-primary fw-bold">Overall Attendance</small>
                    <div class="fs-5 fw-bold text-primary">
                        <?= $dailyTotalStudents > 0 ? round(($dailyTotalPresent / $dailyTotalStudents) * 100, 1) : 0 ?>%
                    </div>
                </div>
            </div>
        </div>

        <table class="table table-bordered align-middle text-center mb-4">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">SL</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Enrolled</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                    <th>Leave</th>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
                <?php $sl = 1; foreach ($dailyClasses as $dc): ?>
                    <tr>
                        <td><?= $sl++ ?></td>
                        <td class="fw-bold text-start ps-3"><?= e($dc['class_name']) ?></td>
                        <td><?= e($dc['section_name']) ?></td>
                        <td class="fw-semibold"><?= $dc['enrolled'] ?></td>
                        <td class="text-success fw-bold"><?= $dc['present'] ?></td>
                        <td class="text-danger fw-bold"><?= $dc['absent'] ?></td>
                        <td><?= $dc['late'] ?></td>
                        <td><?= $dc['leave'] ?></td>
                        <td class="fw-bold"><?= $dc['rate'] ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="3" class="text-start ps-3">Institution Totals</th>
                    <th><?= $dailyTotalStudents ?></th>
                    <th><?= $dailyTotalPresent ?></th>
                    <th><?= $dailyTotalAbsent ?></th>
                    <th><?= $dailyTotalLate ?></th>
                    <th><?= $dailyTotalLeave ?></th>
                    <th><?= $dailyTotalStudents > 0 ? round(($dailyTotalPresent / $dailyTotalStudents) * 100, 1) : 0 ?>%</th>
                </tr>
            </tfoot>
        </table>

    <!-- ==================== REPORT CONTENT: 2. CLASSWISE ==================== -->
    <?php elseif ($reportType === 'classwise'): ?>
        <table class="table table-bordered align-middle text-center mb-4">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">Roll</th>
                    <th>Admission No</th>
                    <th>Student Full Name</th>
                    <th>Gender</th>
                    <th>Attendance Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($classwiseStudents)): ?>
                    <?php foreach ($classwiseStudents as $cs): ?>
                        <tr>
                            <td class="fw-bold fs-6"><?= str_pad((string)$cs['roll_no'], 2, '0', STR_PAD_LEFT) ?></td>
                            <td><code><?= e($cs['admission_no']) ?></code></td>
                            <td class="text-start ps-3 fw-bold"><?= e($cs['first_name'] . ' ' . $cs['last_name']) ?></td>
                            <td><?= e($cs['gender']) ?></td>
                            <td>
                                <?php if ($cs['status'] === 'present'): ?>
                                    <span class="badge bg-success">Present</span>
                                <?php elseif ($cs['status'] === 'absent'): ?>
                                    <span class="badge bg-danger">Absent</span>
                                <?php elseif ($cs['status'] === 'late'): ?>
                                    <span class="badge bg-warning text-dark">Late</span>
                                <?php elseif ($cs['status'] === 'excused'): ?>
                                    <span class="badge bg-info text-dark">Leave</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unmarked</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted text-start ps-3"><?= e($cs['remarks'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No students enrolled in this section.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <!-- ==================== REPORT CONTENT: 3. ABSENT ==================== -->
    <?php elseif ($reportType === 'absent'): ?>
        <table class="table table-bordered align-middle mb-4">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 50px;">SL</th>
                    <th>Admission ID</th>
                    <th>Student Name</th>
                    <th>Class & Section</th>
                    <th>Roll</th>
                    <th>Guardian Name</th>
                    <th>Guardian Phone</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($absentReportList)): ?>
                    <?php $sl = 1; foreach ($absentReportList as $ar): ?>
                        <tr>
                            <td class="text-center"><?= $sl++ ?></td>
                            <td><code><?= e($ar['admission_no']) ?></code></td>
                            <td class="fw-bold"><?= e($ar['first_name'] . ' ' . $ar['last_name']) ?></td>
                            <td><?= e($ar['class_name']) ?> - <?= e($ar['section_name']) ?></td>
                            <td class="text-center fw-bold text-danger"><?= $ar['roll_no'] ?></td>
                            <td><?= e($ar['guardian_name'] ?: 'Guardian') ?></td>
                            <td class="fw-bold"><?= e($ar['guardian_phone'] ?: 'N/A') ?></td>
                            <td class="small text-muted"><?= e($ar['present_address'] ?: 'Sirajganj') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No absent students recorded on this date.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <!-- ==================== REPORT CONTENT: 4. SUBMISSIONS ==================== -->
    <?php elseif ($reportType === 'submissions'): ?>
        <table class="table table-bordered align-middle text-center mb-4">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">SL</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Compliance Status</th>
                    <th>Recorded By Teacher</th>
                    <th>Submission Time</th>
                    <th>Present / Total Students</th>
                </tr>
            </thead>
            <tbody>
                <?php $sl = 1; foreach ($submissionsList as $sub): ?>
                    <tr>
                        <td><?= $sl++ ?></td>
                        <td class="fw-bold text-start ps-3"><?= e($sub['class_name']) ?></td>
                        <td><?= e($sub['section_name']) ?></td>
                        <td>
                            <?php if ($sub['is_submitted']): ?>
                                <span class="badge bg-success">SUBMITTED</span>
                            <?php else: ?>
                                <span class="badge bg-danger">PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-start ps-3"><?= e($sub['submitter_name']) ?></td>
                        <td><?= $sub['submitted_at'] ? date('h:i A', strtotime($sub['submitted_at'])) : '-' ?></td>
                        <td>
                            <?php if ($sub['is_submitted']): ?>
                                <span class="fw-bold text-success"><?= $sub['present_count'] ?></span> / <?= $sub['total_students'] ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Signature Footer (Visible on Print) -->
    <div class="row pt-5 mt-5">
        <div class="col-4 text-center">
            <div style="border-top: 1px dotted #000; width: 180px; margin: 0 auto; padding-top: 6px;">
                <small class="fw-bold text-dark d-block">Class Teacher</small>
                <small class="text-muted" style="font-size: 0.75rem;">Verified Sign</small>
            </div>
        </div>
        <div class="col-4 text-center">
            <div style="border-top: 1px dotted #000; width: 180px; margin: 0 auto; padding-top: 6px;">
                <small class="fw-bold text-dark d-block">Assistant Headmaster</small>
                <small class="text-muted" style="font-size: 0.75rem;">Checked & Verified</small>
            </div>
        </div>
        <div class="col-4 text-center">
            <div style="border-top: 1px dotted #000; width: 180px; margin: 0 auto; padding-top: 6px;">
                <small class="fw-bold text-dark d-block">Headmaster / Principal</small>
                <small class="text-muted" style="font-size: 0.75rem;">Sirajganj Road Chowrasta High School</small>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

