<?php
/**
 * Head Sir Portal - Student Search & Individual Dossier
 * Sirajganj Road Chowrasta High School
 */
$pageTitle = 'Student Search & Profiles';
require_once __DIR__ . '/includes/header.php';

$searchQuery = getString('q');
$filterClassId = getInt('class_id');
$filterSectionId = getInt('section_id');
$filterRoll = getInt('roll_no');
$selectedStudentId = getInt('student_id');

$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$sections = Database::fetchAll("SELECT * FROM sections WHERE status = 'active' ORDER BY section_name ASC");

// Build Student Search Query
$sql = "SELECT s.*, 
               e.roll_no, e.class_id, e.section_id,
               c.class_name, sec.section_name,
               g.guardian_name, g.guardian_phone, g.guardian_relation, 
               g.father_name, g.father_phone, g.mother_name,
               g.present_address, g.permanent_address
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        JOIN classes c ON e.class_id = c.id
        JOIN sections sec ON e.section_id = sec.id
        LEFT JOIN guardians g ON s.guardian_id = g.id
        WHERE e.session_id = ? AND s.status = 'active'";
$params = [$sessionId];

if (!empty($searchQuery)) {
    $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ? OR g.guardian_phone LIKE ? OR g.father_phone LIKE ? OR g.guardian_name LIKE ?)";
    $like = "%{$searchQuery}%";
    array_push($params, $like, $like, $like, $like, $like, $like);
}

if ($filterClassId > 0) {
    $sql .= " AND e.class_id = ?";
    $params[] = $filterClassId;
}

if ($filterSectionId > 0) {
    $sql .= " AND e.section_id = ?";
    $params[] = $filterSectionId;
}

if ($filterRoll > 0) {
    $sql .= " AND e.roll_no = ?";
    $params[] = $filterRoll;
}

$sql .= " ORDER BY c.numeric_value ASC, sec.section_name ASC, e.roll_no ASC LIMIT 100";
$students = Database::fetchAll($sql, $params);

// If a specific student is selected, fetch their detailed records
$selectedStudent = null;
$studentAttendance = [];
$studentResults = [];
$attStats = ['total' => 0, 'present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'rate' => 0];

if ($selectedStudentId > 0) {
    $selectedStudent = Database::fetchOne(
        "SELECT s.*, 
                e.roll_no, e.class_id, e.section_id,
                c.class_name, sec.section_name,
                g.guardian_name, g.guardian_phone, g.guardian_relation, 
                g.father_name, g.father_phone, g.mother_name, g.mother_phone,
                g.present_address, g.permanent_address
         FROM enrollments e
         JOIN students s ON e.student_id = s.id
         JOIN classes c ON e.class_id = c.id
         JOIN sections sec ON e.section_id = sec.id
         LEFT JOIN guardians g ON s.guardian_id = g.id
         WHERE s.id = ? AND e.session_id = ?
         LIMIT 1",
        [$selectedStudentId, $sessionId]
    );

    if ($selectedStudent) {
        // Individual Attendance History
        $studentAttendance = Database::fetchAll(
            "SELECT att.*, u.full_name as marked_by_name 
             FROM attendance att 
             LEFT JOIN users u ON att.marked_by = u.id 
             WHERE att.student_id = ? AND att.session_id = ? 
             ORDER BY att.attendance_date DESC",
            [$selectedStudentId, $sessionId]
        );

        $attStats['total'] = count($studentAttendance);
        foreach ($studentAttendance as $rec) {
            if (isset($attStats[$rec['status']])) {
                $attStats[$rec['status']]++;
            }
        }
        $attStats['rate'] = $attStats['total'] > 0 ? round(($attStats['present'] / $attStats['total']) * 100, 1) : 0;

        // Individual Exam Results
        $studentResults = Database::fetchAll(
            "SELECT r.*, ex.exam_name, ex.start_date 
             FROM results r 
             JOIN exams ex ON r.exam_id = ex.id 
             WHERE r.student_id = ? AND r.session_id = ? 
             ORDER BY ex.start_date DESC",
            [$selectedStudentId, $sessionId]
        );
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-search-plus text-success me-2"></i> Student Search & Full Academic Dossier
        </h4>
        <p class="text-muted small mb-0">Search any student in the school to inspect contact details, daily attendance logs, and exam marks.</p>
    </div>
    <?php if ($selectedStudent): ?>
        <a href="<?= url('headmaster/students.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
            &larr; Back to Search List
        </a>
    <?php endif; ?>
</div>

<!-- ==================== IF STUDENT SELECTED: SHOW FULL DOSSIER ==================== -->
<?php if ($selectedStudent): ?>
    <div class="row g-4 mb-5">
        <!-- Profile Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center h-100">
                <img src="<?= resolveUploadUrl($selectedStudent['photo'] ?? '', 'assets/images/default-student.png') ?>" 
                     class="rounded-circle mx-auto mb-3 border border-4 border-success shadow-sm" 
                     style="width: 120px; height: 120px; object-fit: cover;">
                <h5 class="fw-bold text-dark mb-1">
                    <?= e($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']) ?>
                </h5>
                <div class="text-muted small mb-2">
                    Admission ID: <code><?= e($selectedStudent['admission_no']) ?></code>
                </div>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill">
                        <?= e($selectedStudent['class_name']) ?> (<?= e($selectedStudent['section_name']) ?>)
                    </span>
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill">
                        Roll: <?= $selectedStudent['roll_no'] ?>
                    </span>
                </div>

                <hr class="my-3">

                <!-- Contact & Guardian Info -->
                <div class="text-start small">
                    <div class="mb-2">
                        <strong class="text-dark">Guardian Name:</strong>
                        <div class="text-muted"><?= e($selectedStudent['guardian_name'] ?: ($selectedStudent['father_name'] ?: 'Not Provided')) ?> (<?= e($selectedStudent['guardian_relation'] ?: 'Guardian') ?>)</div>
                    </div>
                    <div class="mb-2">
                        <strong class="text-dark">Guardian Phone:</strong>
                        <div>
                            <a href="tel:<?= e($selectedStudent['guardian_phone'] ?: $selectedStudent['father_phone']) ?>" class="text-success text-decoration-none fw-bold">
                                <i class="fas fa-phone-alt me-1"></i> <?= e($selectedStudent['guardian_phone'] ?: ($selectedStudent['father_phone'] ?: 'N/A')) ?>
                            </a>
                        </div>
                    </div>
                    <div class="mb-2">
                        <strong class="text-dark">Father:</strong>
                        <div class="text-muted"><?= e($selectedStudent['father_name'] ?: '-') ?></div>
                    </div>
                    <div class="mb-2">
                        <strong class="text-dark">Mother:</strong>
                        <div class="text-muted"><?= e($selectedStudent['mother_name'] ?: '-') ?></div>
                    </div>
                    <div class="mb-2">
                        <strong class="text-dark">Date of Birth:</strong>
                        <div class="text-muted"><?= formatDate($selectedStudent['dob']) ?> (<?= e($selectedStudent['gender']) ?>)</div>
                    </div>
                    <div class="mb-2">
                        <strong class="text-dark">Present Address:</strong>
                        <div class="text-muted"><?= e($selectedStudent['present_address'] ?: 'Hatikumrul, Sirajganj') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance & Academic Tabs -->
        <div class="col-lg-8">
            <!-- Attendance Summary Counters -->
            <div class="row g-2 mb-4">
                <div class="col-3">
                    <div class="p-3 bg-white rounded-4 shadow-sm text-center border-bottom border-4 border-success">
                        <small class="text-muted fw-bold">Present</small>
                        <h4 class="fw-bold text-success mb-0"><?= $attStats['present'] ?></h4>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-3 bg-white rounded-4 shadow-sm text-center border-bottom border-4 border-danger">
                        <small class="text-muted fw-bold">Absent</small>
                        <h4 class="fw-bold text-danger mb-0"><?= $attStats['absent'] ?></h4>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-3 bg-white rounded-4 shadow-sm text-center border-bottom border-4 border-warning">
                        <small class="text-muted fw-bold">Late / Leave</small>
                        <h4 class="fw-bold text-warning mb-0"><?= $attStats['late'] + $attStats['excused'] ?></h4>
                    </div>
                </div>
                <div class="col-3">
                    <div class="p-3 bg-white rounded-4 shadow-sm text-center border-bottom border-4 border-primary">
                        <small class="text-muted fw-bold">Attendance %</small>
                        <h4 class="fw-bold text-primary mb-0"><?= $attStats['rate'] ?>%</h4>
                    </div>
                </div>
            </div>

            <!-- D. Individual Attendance History Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-history text-primary me-2"></i> Complete Daily Attendance History
                    </h6>
                    <span class="badge bg-light text-muted border"><?= count($studentAttendance) ?> Recorded Days</span>
                </div>
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Marked By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($studentAttendance)): ?>
                                <?php foreach ($studentAttendance as $rec): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= formatDate($rec['attendance_date']) ?></td>
                                        <td class="text-muted small"><?= date('l', strtotime($rec['attendance_date'])) ?></td>
                                        <td>
                                            <?php if ($rec['status'] === 'present'): ?>
                                                <span class="badge bg-success-subtle text-success px-2 py-1"><i class="fas fa-check me-1"></i> Present</span>
                                            <?php elseif ($rec['status'] === 'absent'): ?>
                                                <span class="badge bg-danger-subtle text-danger px-2 py-1"><i class="fas fa-times me-1"></i> Absent</span>
                                            <?php elseif ($rec['status'] === 'late'): ?>
                                                <span class="badge bg-warning-subtle text-warning px-2 py-1"><i class="fas fa-clock me-1"></i> Late</span>
                                            <?php else: ?>
                                                <span class="badge bg-info-subtle text-info px-2 py-1"><i class="fas fa-bed me-1"></i> Leave</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= e($rec['remarks'] ?: '-') ?></td>
                                        <td class="small text-muted"><?= e($rec['marked_by_name'] ?: 'Teacher') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No attendance entries recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Examination Results -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-award text-warning me-2"></i> Examination Tabulation & GPA
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Exam Name</th>
                                <th class="text-center">Total Marks</th>
                                <th class="text-center">GPA</th>
                                <th class="text-center">Grade</th>
                                <th class="text-center">Class Rank</th>
                                <th class="text-center">Result Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($studentResults)): ?>
                                <?php foreach ($studentResults as $res): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= e($res['exam_name']) ?></td>
                                        <td class="text-center"><?= $res['total_marks'] ?></td>
                                        <td class="text-center fw-bold text-success"><?= number_format($res['gpa'], 2) ?></td>
                                        <td class="text-center"><span class="badge bg-dark"><?= e($res['final_grade']) ?></span></td>
                                        <td class="text-center fw-bold text-primary">#<?= $res['class_rank'] ?: '-' ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $res['result_status'] === 'Passed' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= e($res['result_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No examination results published yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ==================== SEARCH BAR & FILTER CARD ==================== -->
<div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
    <form action="<?= url('headmaster/students.php') ?>" method="GET" class="row g-3">
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-dark">Keyword (Name / Admission ID / Mobile)</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="e.g. Tanvir, SRCHS-2026, 017..." value="<?= e($searchQuery) ?>">
            </div>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-dark">Class</label>
            <select name="class_id" class="form-select">
                <option value="">All Classes (6 to 10)</option>
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

        <div class="col-md-1">
            <label class="form-label small fw-semibold text-dark">Roll</label>
            <input type="number" name="roll_no" class="form-control" placeholder="No." value="<?= $filterRoll ?: '' ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-success w-100 rounded-3">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <a href="<?= url('headmaster/students.php') ?>" class="btn btn-outline-secondary rounded-3" title="Clear">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<!-- ==================== SEARCH RESULTS TABLE ==================== -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-users text-primary me-2"></i> Matching Students Roster
        </h6>
        <span class="badge bg-light text-dark border"><?= count($students) ?> Students Found</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;" class="text-center">Roll</th>
                    <th>Student Name & ID</th>
                    <th>Class & Section</th>
                    <th>Guardian Details</th>
                    <th>Guardian Phone</th>
                    <th>Address</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $st): ?>
                        <tr class="<?= $selectedStudentId == $st['id'] ? 'table-warning' : '' ?>">
                            <td class="text-center fw-bold text-success fs-6">
                                <?= str_pad((string)$st['roll_no'], 2, '0', STR_PAD_LEFT) ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= resolveUploadUrl($st['photo'] ?? '', 'assets/images/default-student.png') ?>" 
                                         class="rounded-circle me-3 border" style="width: 44px; height: 44px; object-fit: cover;">
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
                                <span class="badge bg-success-subtle text-success px-2 py-1">
                                    <?= e($st['class_name']) ?> - <?= e($st['section_name']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= e($st['guardian_name'] ?: ($st['father_name'] ?: 'Not Provided')) ?></div>
                                <small class="text-muted"><?= e($st['guardian_relation'] ?: 'Guardian') ?></small>
                            </td>
                            <td>
                                <a href="tel:<?= e($st['guardian_phone'] ?: $st['father_phone']) ?>" class="small text-decoration-none fw-bold text-success">
                                    <i class="fas fa-phone-alt me-1"></i> <?= e($st['guardian_phone'] ?: ($st['father_phone'] ?: 'N/A')) ?>
                                </a>
                            </td>
                            <td class="small text-muted">
                                <?= truncate(e($st['present_address'] ?: 'Sirajganj'), 35) ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('headmaster/students.php?student_id=' . $st['id'] . '&q=' . urlencode($searchQuery) . '&class_id=' . $filterClassId . '&section_id=' . $filterSectionId) ?>" 
                                   class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                    <i class="fas fa-id-card me-1"></i> Full Dossier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-user-slash fs-2 d-block mb-2 opacity-50"></i>
                            No students match your search criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

