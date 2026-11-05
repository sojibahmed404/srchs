<?php
/**
 * Teacher Examination Marks Entry Module
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

// Fetch active exams
$exams = $db->fetchAll(
    "SELECT id, name, exam_type, start_date, end_date, is_published 
     FROM exams 
     WHERE session_id = ? 
     ORDER BY start_date DESC",
    [$activeSessionId]
);

// Fetch teacher's assigned class, section, subject combinations
$assignedCourses = $db->fetchAll(
    "SELECT ta.id AS assignment_id,
            c.id AS class_id, c.name AS class_name, c.numeric_name,
            s.id AS section_id, s.name AS section_name,
            sub.id AS subject_id, sub.name AS subject_name, sub.code AS subject_code
     FROM teacher_assignments ta
     JOIN classes c ON ta.class_id = c.id
     JOIN sections s ON ta.section_id = s.id
     JOIN subjects sub ON ta.subject_id = sub.id
     WHERE ta.teacher_id = ? AND ta.session_id = ?
     ORDER BY c.numeric_name ASC, s.name ASC, sub.name ASC",
    [$teacherId, $activeSessionId]
);

// Selected parameters
$selectedExamId = (int)($_GET['exam_id'] ?? ($exams[0]['id'] ?? 0));
$selectedAssignmentId = (int)($_GET['assignment_id'] ?? ($assignedCourses[0]['assignment_id'] ?? 0));

$currentAssignment = null;
foreach ($assignedCourses as $ac) {
    if ((int)$ac['assignment_id'] === $selectedAssignmentId) {
        $currentAssignment = $ac;
        break;
    }
}

// Fallback to first if not found
if (!$currentAssignment && !empty($assignedCourses)) {
    $currentAssignment = $assignedCourses[0];
    $selectedAssignmentId = (int)$currentAssignment['assignment_id'];
}

$classId = $currentAssignment ? (int)$currentAssignment['class_id'] : 0;
$sectionId = $currentAssignment ? (int)$currentAssignment['section_id'] : 0;
$subjectId = $currentAssignment ? (int)$currentAssignment['subject_id'] : 0;

// Subject configurations (full marks, pass marks)
$subjectConfig = null;
if ($classId && $subjectId) {
    $subjectConfig = $db->fetchOne(
        "SELECT full_marks, pass_marks, is_optional 
         FROM class_subjects 
         WHERE class_id = ? AND subject_id = ?",
        [$classId, $subjectId]
    );
}
$fullMarks = $subjectConfig ? (float)$subjectConfig['full_marks'] : 100.0;
$passMarks = $subjectConfig ? (float)$subjectConfig['pass_marks'] : 33.0;

// Fetch grading scales
$gradingScales = $db->fetchAll("SELECT * FROM grading_scales ORDER BY mark_from DESC");

// Helper function to calculate grade and GP for marks
function computeGradePoint($obtainedMarks, $fullMarks, $passMarks, $scales) {
    $percentage = ($fullMarks > 0) ? ($obtainedMarks / $fullMarks) * 100 : 0;
    if ($obtainedMarks < $passMarks) {
        return ['grade' => 'F', 'gpa' => 0.00];
    }
    foreach ($scales as $scale) {
        if ($percentage >= (float)$scale['mark_from'] && $percentage <= (float)$scale['mark_to']) {
            return ['grade' => $scale['grade'], 'gpa' => (float)$scale['point']];
        }
    }
    return ['grade' => 'F', 'gpa' => 0.00];
}

// Handle Marks Entry Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_marks') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (CSRF). Please retry.');
        redirect("marks-entry.php?exam_id={$selectedExamId}&assignment_id={$selectedAssignmentId}");
    }

    if (!$currentAssignment) {
        setFlash('danger', 'Unauthorized or invalid course selection.');
        redirect('marks-entry.php');
    }

    $marksData = $_POST['marks'] ?? [];
    $remarksData = $_POST['remarks'] ?? [];
    $isAbsentData = $_POST['is_absent'] ?? [];

    $savedCount = 0;
    try {
        $db->beginTransaction();

        foreach ($marksData as $studentId => $markVal) {
            $studentId = (int)$studentId;
            $isAbsent = isset($isAbsentData[$studentId]) ? 1 : 0;
            $remark = cleanInput($remarksData[$studentId] ?? '');

            if ($isAbsent) {
                $obtainedMarks = 0.00;
                $grade = 'F';
                $gradePoint = 0.00;
            } else {
                $obtainedMarks = (float)$markVal;
                if ($obtainedMarks < 0) $obtainedMarks = 0.00;
                if ($obtainedMarks > $fullMarks) $obtainedMarks = $fullMarks;

                $computed = computeGradePoint($obtainedMarks, $fullMarks, $passMarks, $gradingScales);
                $grade = $computed['grade'];
                $gradePoint = $computed['gpa'];
            }

            // Upsert into marks table
            $db->query(
                "INSERT INTO marks (session_id, exam_id, class_id, section_id, subject_id, student_id, full_marks, pass_marks, obtained_marks, grade_point, letter_grade, is_absent, remarks, entered_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                    full_marks = VALUES(full_marks),
                    pass_marks = VALUES(pass_marks),
                    obtained_marks = VALUES(obtained_marks),
                    grade_point = VALUES(grade_point),
                    letter_grade = VALUES(letter_grade),
                    is_absent = VALUES(is_absent),
                    remarks = VALUES(remarks),
                    entered_by = VALUES(entered_by),
                    updated_at = NOW()",
                [
                    $activeSessionId, $selectedExamId, $classId, $sectionId, $subjectId, $studentId,
                    $fullMarks, $passMarks, $obtainedMarks, $gradePoint, $grade, $isAbsent, $remark, $_SESSION['user_id']
                ]
            );

            // Trigger overall student GPA recalculation for this exam
            recalculateStudentExamResult($activeSessionId, $selectedExamId, $studentId, $classId, $sectionId, $gradingScales);

            $savedCount++;
        }

        $db->commit();
        logActivity($_SESSION['user_id'], 'record_marks', "Teacher {$currentTeacher['name']} entered marks for {$savedCount} students ({$currentAssignment['class_name']} {$currentAssignment['section_name']} - {$currentAssignment['subject_name']}, Exam ID: {$selectedExamId})");
        setFlash('success', "Marks successfully recorded for {$savedCount} students!");
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('danger', 'Failed to save marks: ' . $e->getMessage());
    }

    redirect("marks-entry.php?exam_id={$selectedExamId}&assignment_id={$selectedAssignmentId}");
}

// Helper to recalculate overall result record in `results` table
function recalculateStudentExamResult($sessionId, $examId, $studentId, $classId, $sectionId, $gradingScales) {
    $db = Database::getInstance();
    $studentMarks = $db->fetchAll(
        "SELECT obtained_marks, full_marks, pass_marks, grade_point, letter_grade, is_absent 
         FROM marks 
         WHERE session_id = ? AND exam_id = ? AND student_id = ?",
        [$sessionId, $examId, $studentId]
    );

    if (empty($studentMarks)) return;

    $totalMarks = 0;
    $totalGP = 0;
    $count = count($studentMarks);
    $hasFailed = false;

    foreach ($studentMarks as $m) {
        $totalMarks += (float)$m['obtained_marks'];
        $totalGP += (float)$m['grade_point'];
        if ($m['letter_grade'] === 'F' || (int)$m['is_absent'] === 1) {
            $hasFailed = true;
        }
    }

    $rawGpa = ($count > 0) ? round($totalGP / $count, 2) : 0.00;
    if ($rawGpa > 5.00) $rawGpa = 5.00;

    if ($hasFailed) {
        $finalGpa = 0.00;
        $finalGrade = 'F';
        $status = 'failed';
    } else {
        $finalGpa = $rawGpa;
        $finalGrade = 'F';
        foreach ($gradingScales as $scale) {
            if ($finalGpa >= (float)$scale['point']) {
                $finalGrade = $scale['grade'];
                break;
            }
        }
        $status = 'passed';
    }

    // Upsert into results
    $db->query(
        "INSERT INTO results (session_id, exam_id, class_id, section_id, student_id, total_marks, gpa, grade, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE 
            total_marks = VALUES(total_marks),
            gpa = VALUES(gpa),
            grade = VALUES(grade),
            status = VALUES(status),
            updated_at = NOW()",
        [$sessionId, $examId, $classId, $sectionId, $studentId, $totalMarks, $finalGpa, $finalGrade, $status]
    );
}

// Fetch enrolled students and their existing marks
$students = [];
if ($classId && $sectionId && $subjectId && $selectedExamId) {
    $students = $db->fetchAll(
        "SELECT s.id, s.admission_no, s.name_en, s.name_bn, s.photo,
                e.roll_no,
                m.obtained_marks, m.grade_point, m.letter_grade, m.is_absent, m.remarks
         FROM enrollments e
         JOIN students s ON e.student_id = s.id
         LEFT JOIN marks m ON m.student_id = s.id 
              AND m.session_id = ? 
              AND m.exam_id = ? 
              AND m.subject_id = ?
         WHERE e.session_id = ? 
           AND e.class_id = ? 
           AND e.section_id = ? 
           AND e.status = 'active'
         ORDER BY e.roll_no ASC",
        [$activeSessionId, $selectedExamId, $subjectId, $activeSessionId, $classId, $sectionId]
    );
}

$pageTitle = "Examination Marks Entry";
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-pen-nib text-primary me-2"></i>Marks Entry</h4>
        <p class="text-muted small mb-0">Record and update exam marks for your assigned classes and subjects</p>
    </div>
    <div>
        <a href="my-classes.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-chalkboard-user me-1"></i> My Assigned Courses
        </a>
    </div>
</div>

<?php displayFlash(); ?>

<?php if (empty($assignedCourses)): ?>
    <div class="alert alert-warning py-4 text-center">
        <i class="fa-solid fa-triangle-exclamation fa-2x mb-2 d-block"></i>
        <h5>No Assigned Teaching Courses</h5>
        <p class="text-muted mb-0">You have not been assigned to any subjects or classes for Academic Session <?= e(getSetting('current_session', '2026')) ?>.</p>
    </div>
<?php elseif (empty($exams)): ?>
    <div class="alert alert-info py-4 text-center">
        <i class="fa-solid fa-calendar-xmark fa-2x mb-2 d-block"></i>
        <h5>No Scheduled Examinations</h5>
        <p class="text-muted mb-0">No active examinations found for Academic Session <?= e(getSetting('current_session', '2026')) ?>. Please check back when examinations are scheduled.</p>
    </div>
<?php else: ?>

    <!-- Filter Bar Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" action="marks-entry.php" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Examination <span class="text-danger">*</span></label>
                    <select name="exam_id" class="form-select" required>
                        <?php foreach ($exams as $ex): ?>
                            <option value="<?= $ex['id'] ?>" <?= $ex['id'] == $selectedExamId ? 'selected' : '' ?>>
                                <?= e($ex['name']) ?> (<?= e(ucfirst($ex['exam_type'])) ?>)
                                <?= $ex['is_published'] ? '— [Published]' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-semibold small">Assigned Class, Section & Subject <span class="text-danger">*</span></label>
                    <select name="assignment_id" class="form-select" required>
                        <?php foreach ($assignedCourses as $ac): ?>
                            <option value="<?= $ac['assignment_id'] ?>" <?= $ac['assignment_id'] == $selectedAssignmentId ? 'selected' : '' ?>>
                                <?= e($ac['class_name']) ?> — Section <?= e($ac['section_name']) ?>: <?= e($ac['subject_name']) ?> (<?= e($ac['subject_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i> Load Sheet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Course Info Card -->
    <?php if ($currentAssignment): ?>
        <div class="card border-0 shadow-sm mb-4 bg-light">
            <div class="card-body p-3">
                <div class="row align-items-center g-3 text-center text-md-start">
                    <div class="col-md-4">
                        <span class="text-muted small">Course:</span>
                        <div class="fw-bold text-dark fs-6"><?= e($currentAssignment['subject_name']) ?> (<?= e($currentAssignment['subject_code']) ?>)</div>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small">Class & Section:</span>
                        <div class="fw-bold text-dark fs-6"><?= e($currentAssignment['class_name']) ?> — Section <?= e($currentAssignment['section_name']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small">Marks Criteria:</span>
                        <div>
                            <span class="badge bg-primary">Full: <?= $fullMarks ?></span>
                            <span class="badge bg-warning text-dark">Pass: <?= $passMarks ?></span>
                        </div>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <span class="badge bg-success-subtle text-success border border-success px-3 py-2">
                            <?= count($students) ?> Students
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($students)): ?>
        <div class="alert alert-info py-4 text-center">
            <i class="fa-solid fa-info-circle fa-2x mb-2 d-block"></i>
            <h5>No Enrolled Students</h5>
            <p class="text-muted mb-0">No active students found in this class and section for Academic Session <?= e(getSetting('current_session', '2026')) ?>.</p>
        </div>
    <?php else: ?>

        <!-- Marks Entry Form -->
        <form method="POST" action="marks-entry.php?exam_id=<?= $selectedExamId ?>&assignment_id=<?= $selectedAssignmentId ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_marks">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-list-check text-primary me-2"></i>
                        Student Marks Entry Sheet
                    </h6>
                    <span class="text-muted small">
                        Grade Point & Letter Grades calculate automatically upon save.
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;" class="text-center">Roll</th>
                                <th>Student Name</th>
                                <th>Admission No</th>
                                <th style="width: 140px;">Marks (Max <?= $fullMarks ?>)</th>
                                <th style="width: 110px;" class="text-center">Absent?</th>
                                <th style="width: 90px;" class="text-center">Current Grade</th>
                                <th style="min-width: 180px;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $st): 
                                $isAbsent = (int)($st['is_absent'] ?? 0);
                                $hasMark = isset($st['obtained_marks']);
                            ?>
                                <tr>
                                    <td class="text-center fw-bold fs-6 text-primary">
                                        <?= str_pad((string)$st['roll_no'], 2, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= e(resolveUploadUrl($st['photo'], 'student')) ?>" 
                                                 alt="<?= e($st['name_en']) ?>" 
                                                 class="rounded-circle border" 
                                                 style="width: 36px; height: 36px; object-fit: cover;">
                                            <div>
                                                <div class="fw-semibold text-dark"><?= e($st['name_en']) ?></div>
                                                <div class="text-muted small"><?= e($st['name_bn']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($st['admission_no']) ?></span>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               step="0.5" 
                                               min="0" 
                                               max="<?= $fullMarks ?>" 
                                               name="marks[<?= $st['id'] ?>]" 
                                               id="mark_<?= $st['id'] ?>"
                                               class="form-control form-control-sm mark-input fw-bold text-center" 
                                               value="<?= $hasMark ? e($st['obtained_marks']) : '' ?>" 
                                               <?= $isAbsent ? 'disabled' : '' ?>
                                               placeholder="0 - <?= $fullMarks ?>">
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input absent-toggle" 
                                                   type="checkbox" 
                                                   name="is_absent[<?= $st['id'] ?>]" 
                                                   id="absent_<?= $st['id'] ?>" 
                                                   value="1" 
                                                   <?= $isAbsent ? 'checked' : '' ?>
                                                   onchange="toggleAbsent(<?= $st['id'] ?>)">
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($hasMark): ?>
                                            <?php if ($isAbsent): ?>
                                                <span class="badge bg-secondary">ABS</span>
                                            <?php else: ?>
                                                <span class="badge <?= $st['letter_grade'] === 'F' ? 'bg-danger' : 'bg-success' ?>">
                                                    <?= e($st['letter_grade']) ?> (<?= number_format((float)$st['grade_point'], 2) ?>)
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks[<?= $st['id'] ?>]" class="form-control form-control-sm" placeholder="Optional notes" value="<?= e($st['remarks'] ?? '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white p-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        <i class="fa-solid fa-calculator me-1"></i> Grades & GPAs update automatically across the school management portal.
                    </span>
                    <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save All Marks
                    </button>
                </div>
            </div>
        </form>

    <?php endif; ?>
<?php endif; ?>

<script>
function toggleAbsent(studentId) {
    const absentBox = document.getElementById('absent_' + studentId);
    const markInput = document.getElementById('mark_' + studentId);
    if (absentBox.checked) {
        markInput.value = 0;
        markInput.disabled = true;
    } else {
        markInput.disabled = false;
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
