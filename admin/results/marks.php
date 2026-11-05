<?php
/**
 * Sirajganj Road Chowrasta High School - Marks Entry & Evaluation
 */

$pageTitle = 'Examination Marks Entry';
require_once __DIR__ . '/../includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$exams = Database::fetchAll("SELECT * FROM exams WHERE session_id = ? ORDER BY start_date DESC", [$sessionId]);
$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");

$selectedExamId = getInt('exam_id') ?: ($exams[0]['id'] ?? 0);
$selectedClassId = getInt('class_id') ?: ($classes[0]['id'] ?? 1);

$availSections = Database::fetchAll("SELECT * FROM sections WHERE class_id = ? AND status = 'active' ORDER BY section_name ASC", [$selectedClassId]);
$selectedSectionId = getInt('section_id') ?: ($availSections[0]['id'] ?? 0);

// Prescribed subjects for this class
$classSubjects = Database::fetchAll(
    "SELECT cs.*, s.subject_name, s.subject_code 
     FROM class_subjects cs 
     JOIN subjects s ON cs.subject_id = s.id 
     WHERE cs.class_id = ? 
     ORDER BY s.subject_code ASC",
    [$selectedClassId]
);
$selectedSubjectId = getInt('subject_id') ?: ($classSubjects[0]['subject_id'] ?? 0);

// Grading scale helper function
function getGradeLetterAndPoint(float $obtained, float $fullMarks): array {
    $percentage = $fullMarks > 0 ? ($obtained / $fullMarks) * 100 : 0;
    $scale = Database::fetchOne(
        "SELECT * FROM grading_scales WHERE ? >= min_mark AND ? <= max_mark LIMIT 1",
        [$percentage, $percentage]
    );
    if ($scale) {
        return [
            'letter' => $scale['grade_name'],
            'point'  => (float)$scale['grade_point'],
            'remarks'=> $scale['remarks']
        ];
    }
    return ['letter' => 'F', 'point' => 0.00, 'remarks' => 'Fail'];
}

// 1. Process Batch Marks Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $postExamId = getInt('exam_id', 'POST');
        $postClassId = getInt('class_id', 'POST');
        $postSectionId = getInt('section_id', 'POST');
        $postSubjectId = getInt('subject_id', 'POST');
        $marksInput = $_POST['marks_obtained'] ?? []; // student_id => marks
        $absentInput = $_POST['is_absent'] ?? []; // student_id => 1
        $remarksInput = $_POST['remarks'] ?? [];

        // Find subject full marks
        $csInfo = Database::fetchOne("SELECT full_marks FROM class_subjects WHERE class_id = ? AND subject_id = ?", [$postClassId, $postSubjectId]);
        $fullMarks = $csInfo ? (float)$csInfo['full_marks'] : 100.00;

        Database::beginTransaction();
        try {
            $savedCount = 0;
            foreach ($marksInput as $stuId => $val) {
                $stuId = (int)$stuId;
                $isAbsent = isset($absentInput[$stuId]) ? 1 : 0;
                $obtained = $isAbsent ? 0.00 : (float)$val;
                $remark = trim($remarksInput[$stuId] ?? '');

                $evaluation = $isAbsent ? ['letter' => 'F', 'point' => 0.00, 'remarks' => 'Absent'] : getGradeLetterAndPoint($obtained, $fullMarks);

                // Upsert to marks table (Unique key: exam_id, student_id, subject_id)
                Database::insert(
                    "INSERT INTO marks (exam_id, student_id, subject_id, class_id, section_id, marks_obtained, grade_point, grade_letter, is_absent, remarks, entered_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), grade_point = VALUES(grade_point), grade_letter = VALUES(grade_letter), is_absent = VALUES(is_absent), remarks = VALUES(remarks), entered_by = VALUES(entered_by)",
                    [$postExamId, $stuId, $postSubjectId, $postClassId, $postSectionId, $obtained, $evaluation['point'], $evaluation['letter'], $isAbsent, $remark ?: null, $_SESSION['user_id']]
                );

                // Auto-recalculate cumulative student results for this exam
                $allStudentMarks = Database::fetchAll("SELECT * FROM marks WHERE exam_id = ? AND student_id = ?", [$postExamId, $stuId]);
                if (!empty($allStudentMarks)) {
                    $totalSum = 0;
                    $pointsSum = 0;
                    $hasFail = false;
                    foreach ($allStudentMarks as $m) {
                        $totalSum += (float)$m['marks_obtained'];
                        $pointsSum += (float)$m['grade_point'];
                        if ($m['grade_letter'] === 'F') $hasFail = true;
                    }
                    $subjectCount = count($allStudentMarks);
                    $calculatedGpa = $hasFail ? 0.00 : ($subjectCount > 0 ? round($pointsSum / $subjectCount, 2) : 0.00);

                    // Final Grade from GPA
                    $finalGrade = 'F';
                    if (!$hasFail) {
                        if ($calculatedGpa >= 5.00) $finalGrade = 'A+';
                        elseif ($calculatedGpa >= 4.00) $finalGrade = 'A';
                        elseif ($calculatedGpa >= 3.50) $finalGrade = 'A-';
                        elseif ($calculatedGpa >= 3.00) $finalGrade = 'B';
                        elseif ($calculatedGpa >= 2.00) $finalGrade = 'C';
                        elseif ($calculatedGpa >= 1.00) $finalGrade = 'D';
                    }

                    $resultStatus = $hasFail ? 'failed' : 'passed';

                    Database::insert(
                        "INSERT INTO results (exam_id, student_id, session_id, class_id, section_id, total_marks, gpa, final_grade, result_status, is_published, calculated_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
                         ON DUPLICATE KEY UPDATE total_marks = VALUES(total_marks), gpa = VALUES(gpa), final_grade = VALUES(final_grade), result_status = VALUES(result_status), calculated_at = NOW()",
                        [$postExamId, $stuId, $sessionId, $postClassId, $postSectionId, $totalSum, $calculatedGpa, $finalGrade, $resultStatus]
                    );
                }

                $savedCount++;
            }

            Database::commit();
            logActivity('marks_entered', "Saved marks for {$savedCount} students: Exam {$postExamId}, Class {$postClassId}, Subject {$postSubjectId}");
            setFlash('success', "Marks and automatic GPA updated successfully for {$savedCount} students.");
        } catch (Exception $e) {
            Database::rollback();
            setFlash('danger', 'Error saving marks: ' . $e->getMessage());
        }
    }
    redirect(url("admin/results/marks.php?exam_id={$selectedExamId}&class_id={$selectedClassId}&section_id={$selectedSectionId}&subject_id={$selectedSubjectId}"));
}

// 2. Fetch Students and their entered marks for this combination
$studentsList = [];
if ($selectedExamId > 0 && $selectedClassId > 0 && $selectedSectionId > 0 && $selectedSubjectId > 0) {
    $studentsList = Database::fetchAll(
        "SELECT s.id as student_id, s.admission_no, s.first_name, s.last_name, e.roll_no,
                m.marks_obtained, m.grade_letter, m.grade_point, m.is_absent, m.remarks
         FROM enrollments e 
         JOIN students s ON e.student_id = s.id 
         LEFT JOIN marks m ON (m.exam_id = ? AND m.subject_id = ? AND m.student_id = s.id)
         WHERE e.session_id = ? AND e.class_id = ? AND e.section_id = ? AND s.status = 'active'
         ORDER BY e.roll_no ASC",
        [$selectedExamId, $selectedSubjectId, $sessionId, $selectedClassId, $selectedSectionId]
    );
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Marks Management & GPA Calculation</h4>
        <p class="text-muted small mb-0">Record examination marks with real-time automatic NCTB GPA, grade, and status calculation.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/results/index.php?exam_id=' . $selectedExamId . '&class_id=' . $selectedClassId) ?>" class="btn btn-outline-primary rounded-pill px-3">
            <i class="fas fa-poll me-1"></i> Class Tabulation Sheet
        </a>
    </div>
</div>

<!-- Exam / Class / Section / Subject Filter Bar -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form action="<?= url('admin/results/marks.php') ?>" method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Examination</label>
            <select name="exam_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($exams as $ex): ?>
                    <option value="<?= $ex['id'] ?>" <?= $selectedExamId == $ex['id'] ? 'selected' : '' ?>><?= e($ex['exam_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Class</label>
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Section</label>
            <select name="section_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($availSections as $sec): ?>
                    <option value="<?= $sec['id'] ?>" <?= $selectedSectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['section_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Subject</label>
            <select name="subject_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($classSubjects as $cs): ?>
                    <option value="<?= $cs['subject_id'] ?>" <?= $selectedSubjectId == $cs['subject_id'] ? 'selected' : '' ?>>
                        <?= e($cs['subject_code'] . ' - ' . $cs['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<!-- Marks Input Matrix -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-pencil-alt text-success me-2"></i> Student Marks Entry Sheet
        </h6>
    </div>

    <form action="<?= url('admin/results/marks.php') ?>" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="save_marks" value="1">
        <input type="hidden" name="exam_id" value="<?= $selectedExamId ?>">
        <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
        <input type="hidden" name="section_id" value="<?= $selectedSectionId ?>">
        <input type="hidden" name="subject_id" value="<?= $selectedSubjectId ?>">

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Roll</th>
                        <th>Student Name</th>
                        <th>Admission No</th>
                        <th style="width: 140px;">Marks Obtained</th>
                        <th style="width: 100px;">Absent?</th>
                        <th style="width: 120px;">Current Grade</th>
                        <th>Remarks (Optional)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($studentsList)): ?>
                        <?php foreach ($studentsList as $stu): ?>
                            <tr>
                                <td class="fw-bold"><span class="badge bg-dark rounded-pill"><?= e((string)$stu['roll_no']) ?></span></td>
                                <td class="fw-semibold text-dark"><?= e($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                                <td><span class="badge bg-light text-secondary border"><?= e($stu['admission_no']) ?></span></td>
                                <td>
                                    <input type="number" step="0.5" min="0" max="100" 
                                           name="marks_obtained[<?= $stu['student_id'] ?>]" 
                                           class="form-control form-control-sm text-center fw-bold" 
                                           value="<?= $stu['marks_obtained'] !== null ? (float)$stu['marks_obtained'] : '' ?>" 
                                           placeholder="0-100">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="is_absent[<?= $stu['student_id'] ?>]" value="1" <?= $stu['is_absent'] ? 'checked' : '' ?> class="form-check-input">
                                </td>
                                <td>
                                    <?php if ($stu['grade_letter']): ?>
                                        <span class="badge <?= $stu['grade_letter'] === 'F' ? 'bg-danger' : 'bg-success' ?> px-2 py-1">
                                            <?= e($stu['grade_letter']) ?> (<?= number_format((float)$stu['grade_point'], 2) ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Not entered</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?= $stu['student_id'] ?>]" class="form-control form-control-sm" placeholder="Optional notes" value="<?= e($stu['remarks'] ?? '') ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No students found for this class and section.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($studentsList)): ?>
            <div class="card-footer bg-white border-0 py-3 text-end">
                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> Save Marks & Calculate GPA
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
