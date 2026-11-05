<?php
/**
 * Sirajganj Road Chowrasta High School - Public Result Search & Marksheet
 */

require_once __DIR__ . '/includes/auth.php';

try {
    $publishedExams = Database::fetchAll("SELECT * FROM exams WHERE is_published = 1 ORDER BY start_date DESC");
    $classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
} catch (Exception $e) {
    $publishedExams = [];
    $classes = [];
}

$searched = false;
$foundStudent = null;
$foundResult = null;
$subjectMarks = [];
$examDetails = null;

$selectedExamId = getInt('exam_id');
$selectedClassId = getInt('class_id');
$rollNumber = getInt('roll_no');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $selectedExamId > 0 && $selectedClassId > 0 && $rollNumber > 0) {
    $searched = true;

    // 1. Fetch Exam
    $examDetails = Database::fetchOne("SELECT * FROM exams WHERE id = ? AND is_published = 1", [$selectedExamId]);

    if ($examDetails) {
        $sessionId = (int)$examDetails['session_id'];

        // 2. Fetch Enrollment
        $foundStudent = Database::fetchOne(
            "SELECT e.*, s.first_name, s.last_name, s.admission_no, s.photo, s.gender,
                    c.class_name, sec.section_name, g.father_name, g.mother_name
             FROM enrollments e 
             JOIN students s ON e.student_id = s.id 
             JOIN classes c ON e.class_id = c.id 
             JOIN sections sec ON e.section_id = sec.id 
             LEFT JOIN guardians g ON s.guardian_id = g.id
             WHERE e.session_id = ? AND e.class_id = ? AND e.roll_no = ? 
             LIMIT 1",
            [$sessionId, $selectedClassId, $rollNumber]
        );

        if ($foundStudent) {
            $studentId = (int)$foundStudent['student_id'];

            // 3. Fetch Summary Result
            $foundResult = Database::fetchOne(
                "SELECT * FROM results WHERE exam_id = ? AND student_id = ? AND is_published = 1",
                [$selectedExamId, $studentId]
            );

            // 4. Fetch Detailed Subject Marks
            $subjectMarks = Database::fetchAll(
                "SELECT m.*, sub.subject_name, sub.subject_code, sub.subject_type, es.full_marks, es.pass_marks
                 FROM marks m 
                 JOIN subjects sub ON m.subject_id = sub.id 
                 LEFT JOIN exam_subjects es ON (es.exam_id = m.exam_id AND es.subject_id = m.subject_id AND es.class_id = m.class_id)
                 WHERE m.exam_id = ? AND m.student_id = ? 
                 ORDER BY sub.subject_code ASC",
                [$selectedExamId, $studentId]
            );
        }
    }
}

$pageTitle = 'Public Result Search - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Public Result Search</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Result Search</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">

        <!-- Search Form Card -->
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 mb-5 mx-auto" style="max-width: 800px; background-color: #F5F7F5; border-top: 5px solid #123B2A !important;">
            <div class="text-center mb-4">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-search me-1"></i> Examination Gateway</span>
                <h3 class="fw-bold text-dark mt-1" style="color: #123B2A;">Individual Student Result Search</h3>
                <p class="text-muted small">Select your examination, class, and enter the active class roll number.</p>
            </div>

            <form action="<?= url('results.php') ?>" method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Select Examination <span class="text-danger">*</span></label>
                    <select name="exam_id" class="form-select" required>
                        <option value="">-- Choose Examination --</option>
                        <?php foreach ($publishedExams as $ex): ?>
                            <option value="<?= $ex['id'] ?>" <?= $selectedExamId === (int)$ex['id'] ? 'selected' : '' ?>>
                                <?= e($ex['exam_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Class <span class="text-danger">*</span></label>
                    <select name="class_id" class="form-select" required>
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $selectedClassId === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= e($c['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Class Roll No <span class="text-danger">*</span></label>
                    <input type="number" name="roll_no" class="form-control" placeholder="e.g. 1" min="1" value="<?= $rollNumber > 0 ? e((string)$rollNumber) : '' ?>" required>
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-search me-2"></i> View Academic Marksheet
                    </button>
                    <?php if ($searched): ?>
                        <a href="<?= url('results.php') ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill ms-2">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Result Display Section -->
        <?php if ($searched): ?>
            <?php if ($foundStudent && $foundResult): ?>
                <!-- Official Marksheet Card -->
                <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 mx-auto bg-white mb-4" id="marksheetCard" style="max-width: 900px; border: 2px solid #123B2A !important;">
                    
                    <!-- Marksheet Header -->
                    <div class="text-center pb-3 border-bottom border-2 border-dark mb-4">
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 55px; height: 55px; background-color: #123B2A;">
                                <i class="fas fa-graduation-cap fs-3"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0 text-dark" style="font-family: 'Hind Siliguri', sans-serif;">সিরাজগঞ্জ রোড চৌরাস্তা উচ্চ বিদ্যালয়</h3>
                                <h5 class="fw-bold mb-0 text-secondary">Sirajganj Road Chowrasta High School</h5>
                                <small class="text-muted">Hatikumrul, Ullapara, Sirajganj | EIIN: <?= e(getSetting('school_eiin', '128456')) ?></small>
                            </div>
                        </div>
                        <span class="badge bg-success px-4 py-2 rounded-pill fs-6 mt-2 text-uppercase">
                            Academic Progress Report & Marksheet
                        </span>
                        <div class="fw-semibold text-dark mt-2 fs-5"><?= e($examDetails['exam_name']) ?></div>
                    </div>

                    <!-- Student Metadata Box -->
                    <div class="row g-3 mb-4 p-3 rounded-3" style="background-color: #F5F7F5;">
                        <div class="col-sm-6">
                            <p class="mb-1"><strong>Student Name:</strong> <span class="text-success fw-bold"><?= e($foundStudent['first_name'] . ' ' . $foundStudent['last_name']) ?></span></p>
                            <p class="mb-1"><strong>Admission ID:</strong> <?= e($foundStudent['admission_no']) ?></p>
                            <p class="mb-0"><strong>Father's Name:</strong> <?= e($foundStudent['father_name'] ?: 'N/A') ?></p>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <p class="mb-1"><strong>Class:</strong> <span class="fw-bold"><?= e($foundStudent['class_name']) ?></span> | <strong>Section:</strong> <?= e($foundStudent['section_name']) ?></p>
                            <p class="mb-1"><strong>Class Roll No:</strong> <span class="badge bg-dark fs-6"><?= e((string)$foundStudent['roll_no']) ?></span></p>
                            <p class="mb-0"><strong>Mother's Name:</strong> <?= e($foundStudent['mother_name'] ?: 'N/A') ?></p>
                        </div>
                    </div>

                    <!-- Subject Marks Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped text-center align-middle mb-0">
                            <thead style="background-color: #123B2A; color: white;">
                                <tr>
                                    <th>Code</th>
                                    <th class="text-start">Subject Title</th>
                                    <th>Full Marks</th>
                                    <th>Obtained</th>
                                    <th>Letter Grade</th>
                                    <th>Grade Point</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($subjectMarks)): ?>
                                    <?php foreach ($subjectMarks as $m): ?>
                                        <tr>
                                            <td class="fw-bold"><?= e($m['subject_code']) ?></td>
                                            <td class="text-start"><?= e($m['subject_name']) ?></td>
                                            <td><?= $m['full_marks'] ? (int)$m['full_marks'] : 100 ?></td>
                                            <td class="fw-bold <?= $m['is_absent'] ? 'text-danger' : '' ?>">
                                                <?= $m['is_absent'] ? 'Absent' : (float)$m['marks_obtained'] ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $m['grade_letter'] === 'F' ? 'bg-danger' : 'bg-success' ?> px-2 py-1">
                                                    <?= e($m['grade_letter']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-semibold"><?= number_format((float)$m['grade_point'], 2) ?></td>
                                            <td class="text-muted small"><?= e($m['remarks'] ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-muted">No individual subject marks entered.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Result Summary Box -->
                    <div class="row g-3 p-4 rounded-4 mb-4 text-center text-md-start align-items-center" style="background-color: #ecfdf5; border: 2px solid #10b981;">
                        <div class="col-md-3">
                            <small class="text-muted text-uppercase fw-semibold">Total Marks</small>
                            <h4 class="fw-bold text-dark mb-0"><?= (float)$foundResult['total_marks'] ?></h4>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted text-uppercase fw-semibold">Grade Point Average (GPA)</small>
                            <h3 class="fw-bold text-success mb-0"><?= number_format((float)$foundResult['gpa'], 2) ?></h3>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted text-uppercase fw-semibold">Final Grade</small>
                            <h3 class="fw-bold text-dark mb-0"><?= e($foundResult['final_grade']) ?></h3>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <span class="badge <?= $foundResult['result_status'] === 'passed' ? 'bg-success' : 'bg-danger' ?> px-4 py-2 fs-6 rounded-pill text-uppercase">
                                <?= e($foundResult['result_status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Footer Signatures -->
                    <div class="row pt-5 mt-4 text-center">
                        <div class="col-4">
                            <div class="border-top pt-2 small text-muted">Class Teacher</div>
                        </div>
                        <div class="col-4">
                            <div class="border-top pt-2 small text-muted">Controller of Exams</div>
                        </div>
                        <div class="col-4">
                            <div class="border-top pt-2 small text-muted">Headmaster Signature</div>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-5">
                    <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4 py-2 me-2">
                        <i class="fas fa-print me-1"></i> Print Marksheet
                    </button>
                    <a href="<?= url('results.php') ?>" class="btn btn-success rounded-pill px-4 py-2">
                        Search Another Result
                    </a>
                </div>

            <?php else: ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white mx-auto" style="max-width: 700px;">
                    <div class="text-danger fs-1 mb-3"><i class="fas fa-exclamation-circle"></i></div>
                    <h4 class="fw-bold text-dark mb-2">No Published Result Found</h4>
                    <p class="text-muted mb-4">
                        We could not find an approved result for the provided Examination, Class, and Roll Number combination. Please verify the credentials or contact the school examination controller.
                    </p>
                    <a href="<?= url('results.php') ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                        Try Another Search
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
