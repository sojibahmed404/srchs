<?php
/**
 * Sirajganj Road Chowrasta High School - Academic Information
 */

$pageTitle = 'Academic Information - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';

try {
    $periods = Database::fetchAll("SELECT * FROM periods ORDER BY order_num ASC");
    $gradingScales = Database::fetchAll("SELECT * FROM grading_scales ORDER BY min_mark DESC");
} catch (Exception $e) {
    $periods = [];
    $gradingScales = [];
}
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Academic Information</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Academics</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Academic Structure -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-lg-6">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-graduation-cap me-1"></i> Academic Framework</span>
                <h2 class="display-6 fw-bold text-dark mt-1 mb-4" style="color: #123B2A;">
                    NCTB Curriculum & Rajshahi Board Standards
                </h2>
                <p class="text-muted leading-relaxed mb-3">
                    Sirajganj Road Chowrasta High School delivers the standardized national secondary school curriculum designed by the National Curriculum and Textbook Board (NCTB) under the Ministry of Education, Government of Bangladesh.
                </p>
                <p class="text-muted leading-relaxed mb-4">
                    Instruction is conducted in Bengali with intensive English language reinforcement, computer science literacy, and mathematics foundations. For Class 9 and 10, students select specialized tracks in Science and Humanities.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= url('classes.php') ?>" class="btn btn-success rounded-pill px-4">
                        <i class="fas fa-layer-group me-1"></i> Explore Class 6 to 10
                    </a>
                    <a href="<?= url('admission.php') ?>" class="btn btn-outline-success rounded-pill px-4">
                        <i class="fas fa-user-plus me-1"></i> Admission Process
                    </a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5" style="background-color: #F5F7F5;">
                    <h5 class="fw-bold mb-4" style="color: #123B2A;"><i class="fas fa-clock text-warning me-2"></i> Daily Class Bell Schedule</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle mb-0">
                            <thead>
                                <tr class="border-bottom text-muted small">
                                    <th>Period</th>
                                    <th>Timing</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($periods)): ?>
                                    <?php foreach ($periods as $p): ?>
                                        <tr class="<?= $p['is_break'] ? 'bg-warning-subtle fw-bold' : '' ?>">
                                            <td class="py-2">
                                                <i class="fas <?= $p['is_break'] ? 'fa-coffee text-warning' : 'fa-bell text-success' ?> me-2"></i>
                                                <?= e($p['period_name']) ?>
                                            </td>
                                            <td class="py-2 text-muted">
                                                <?= formatTime($p['start_time']) ?> - <?= formatTime($p['end_time']) ?>
                                            </td>
                                            <td class="py-2">
                                                <span class="badge <?= $p['is_break'] ? 'bg-warning text-dark' : 'bg-light text-secondary border' ?>">
                                                    <?= $p['is_break'] ? 'Recess' : 'Instructional' ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-muted">Bell schedule available in office.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading System Section -->
        <div class="row justify-content-center mt-5">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white border-top border-4 border-success">
                    <div class="text-center mb-4">
                        <span class="text-success fw-bold text-uppercase small"><i class="fas fa-calculator me-1"></i> Evaluation Policy</span>
                        <h3 class="fw-bold text-dark mt-1" style="color: #123B2A;">Standard Secondary Grading Scale (GPA 5.00)</h3>
                        <p class="text-muted small">Official letter grading scale adopted in accordance with the Secondary Education Board standards.</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle mb-0">
                            <thead style="background-color: #123B2A; color: white;">
                                <tr>
                                    <th>Marks Range (%)</th>
                                    <th>Letter Grade</th>
                                    <th>Grade Point (GP)</th>
                                    <th>Performance Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($gradingScales)): ?>
                                    <?php foreach ($gradingScales as $scale): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= (int)$scale['min_mark'] ?>% - <?= (int)$scale['max_mark'] ?>%</td>
                                            <td>
                                                <span class="badge <?= $scale['grade_name'] === 'A+' ? 'bg-success' : ($scale['grade_name'] === 'F' ? 'bg-danger' : 'bg-primary') ?> px-3 py-1">
                                                    <?= e($scale['grade_name']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold"><?= number_format($scale['grade_point'], 2) ?></td>
                                            <td class="text-muted"><?= e($scale['remarks']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
