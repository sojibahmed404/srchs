<?php
/**
 * Sirajganj Road Chowrasta High School - Result Publication & Tabulation Sheet
 */

$pageTitle = 'Result Publication & Tabulation';
require_once __DIR__ . '/../includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$exams = Database::fetchAll("SELECT * FROM exams WHERE session_id = ? ORDER BY start_date DESC", [$sessionId]);
$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");

$selectedExamId = getInt('exam_id') ?: ($exams[0]['id'] ?? 0);
$selectedClassId = getInt('class_id') ?: ($classes[0]['id'] ?? 1);

$action = getString('action');

// 1. Calculate and Assign Class Ranks
if ($action === 'calculate_ranks') {
    if (verifyCSRFToken() && $selectedExamId > 0 && $selectedClassId > 0) {
        try {
            // Fetch passed students ordered by GPA desc, then total marks desc
            $resultsToRank = Database::fetchAll(
                "SELECT id FROM results 
                 WHERE exam_id = ? AND class_id = ? AND result_status = 'passed' 
                 ORDER BY gpa DESC, total_marks DESC",
                [$selectedExamId, $selectedClassId]
            );

            $rank = 1;
            foreach ($resultsToRank as $row) {
                Database::execute("UPDATE results SET class_rank = ? WHERE id = ?", [$rank, $row['id']]);
                $rank++;
            }

            // Unset rank for failed students
            Database::execute("UPDATE results SET class_rank = NULL WHERE exam_id = ? AND class_id = ? AND result_status != 'passed'", [$selectedExamId, $selectedClassId]);

            logActivity('ranks_calculated', "Computed class ranks for Exam {$selectedExamId}, Class {$selectedClassId}");
            setFlash('success', 'Class ranks successfully computed and assigned based on GPA and Total Marks.');
        } catch (Exception $e) {
            setFlash('danger', 'Error computing ranks: ' . $e->getMessage());
        }
    }
    redirect(url("admin/results/index.php?exam_id={$selectedExamId}&class_id={$selectedClassId}"));
}

// 2. Publish / Unpublish Class Results
if ($action === 'publish_class' || $action === 'unpublish_class') {
    if (verifyCSRFToken() && $selectedExamId > 0 && $selectedClassId > 0) {
        $status = $action === 'publish_class' ? 1 : 0;
        Database::execute("UPDATE results SET is_published = ? WHERE exam_id = ? AND class_id = ?", [$status, $selectedExamId, $selectedClassId]);
        // Also update main exam table
        if ($status === 1) {
            Database::execute("UPDATE exams SET is_published = 1 WHERE id = ?", [$selectedExamId]);
        }
        setFlash('success', "Results for this class are now " . ($status ? "PUBLISHED" : "UNPUBLISHED") . ".");
    }
    redirect(url("admin/results/index.php?exam_id={$selectedExamId}&class_id={$selectedClassId}"));
}

// 3. Fetch Tabulation Results
$resultsList = [];
if ($selectedExamId > 0 && $selectedClassId > 0) {
    $resultsList = Database::fetchAll(
        "SELECT r.*, s.admission_no, s.first_name, s.last_name, e.roll_no, sec.section_name 
         FROM results r 
         JOIN students s ON r.student_id = s.id 
         JOIN enrollments e ON (e.student_id = s.id AND e.session_id = r.session_id) 
         JOIN sections sec ON r.section_id = sec.id 
         WHERE r.exam_id = ? AND r.class_id = ? 
         ORDER BY e.roll_no ASC",
        [$selectedExamId, $selectedClassId]
    );
}

// Quick stats
$passCount = 0;
$failCount = 0;
$aPlusCount = 0;
foreach ($resultsList as $r) {
    if ($r['result_status'] === 'passed') $passCount++;
    else $failCount++;
    if ($r['final_grade'] === 'A+') $aPlusCount++;
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Result Publication & Tabulation Sheet</h4>
        <p class="text-muted small mb-0">Review class grade distribution, publish official results, and print tabulation master sheets.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url("admin/results/index.php?action=calculate_ranks&exam_id={$selectedExamId}&class_id={$selectedClassId}&csrf_token=" . generateCSRFToken()) ?>" 
           class="btn btn-outline-warning text-dark rounded-pill px-3">
            <i class="fas fa-medal me-1"></i> Compute Class Ranks
        </a>
        <a href="<?= url("admin/results/index.php?action=publish_class&exam_id={$selectedExamId}&class_id={$selectedClassId}&csrf_token=" . generateCSRFToken()) ?>" 
           class="btn btn-success rounded-pill px-4">
            <i class="fas fa-check-double me-1"></i> Publish All Results
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-print me-1"></i> Print Tabulation Sheet
        </button>
    </div>
</div>

<!-- Exam & Class Selection Bar -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form action="<?= url('admin/results/index.php') ?>" method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Examination</label>
            <select name="exam_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($exams as $ex): ?>
                    <option value="<?= $ex['id'] ?>" <?= $selectedExamId == $ex['id'] ? 'selected' : '' ?>><?= e($ex['exam_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Class</label>
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100 rounded-3">Load Sheet</button>
        </div>
    </form>
</div>

<!-- Performance Summary Strip -->
<?php if (!empty($resultsList)): ?>
    <div class="row g-3 mb-4">
        <div class="col-sm-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <small class="text-muted fw-bold">STUDENTS EVALUATED</small>
                <h4 class="fw-bold text-dark mb-0"><?= count($resultsList) ?></h4>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <small class="text-success fw-bold">PASSED</small>
                <h4 class="fw-bold text-success mb-0"><?= $passCount ?></h4>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <small class="text-danger fw-bold">FAILED</small>
                <h4 class="fw-bold text-danger mb-0"><?= $failCount ?></h4>
            </div>
        </div>
        <div class="col-sm-3 col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center">
                <small class="text-warning fw-bold">GPA 5.00 (A+)</small>
                <h4 class="fw-bold text-warning mb-0"><?= $aPlusCount ?></h4>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tabulation Sheet Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-list-ol text-success me-2"></i> Official Tabulation Register
        </h6>
        <span class="badge bg-light text-muted border">Class Rank & GPA Computed</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-center">
            <thead class="table-light">
                <tr>
                    <th>Roll</th>
                    <th class="text-start">Student Name</th>
                    <th>Admission No</th>
                    <th>Section</th>
                    <th>Total Marks</th>
                    <th>GPA</th>
                    <th>Grade</th>
                    <th>Rank</th>
                    <th>Status</th>
                    <th>Public Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($resultsList)): ?>
                    <?php foreach ($resultsList as $r): ?>
                        <tr>
                            <td class="fw-bold"><span class="badge bg-dark rounded-pill"><?= e((string)$r['roll_no']) ?></span></td>
                            <td class="text-start fw-semibold text-dark"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                            <td><span class="badge bg-light text-secondary border"><?= e($r['admission_no']) ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?= e($r['section_name']) ?></span></td>
                            <td class="fw-bold"><?= (float)$r['total_marks'] ?></td>
                            <td class="fw-bold text-success fs-6"><?= number_format((float)$r['gpa'], 2) ?></td>
                            <td>
                                <span class="badge <?= $r['final_grade'] === 'A+' ? 'bg-success' : ($r['final_grade'] === 'F' ? 'bg-danger' : 'bg-primary') ?> px-2 py-1">
                                    <?= e($r['final_grade']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['class_rank']): ?>
                                    <span class="badge bg-warning text-dark rounded-pill px-3">#<?= (int)$r['class_rank'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $r['result_status'] === 'passed' ? 'bg-success' : 'bg-danger' ?> text-uppercase">
                                    <?= e($r['result_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['is_published']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Published</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><i class="fas fa-eye-slash"></i> Hidden</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            No evaluated results found. Please enter marks in the "Marks Entry" section first.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
