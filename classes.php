<?php
/**
 * Sirajganj Road Chowrasta High School - Class Information (6-10)
 */

$pageTitle = 'Class Information (6–10) - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';

try {
    $classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
} catch (Exception $e) {
    $classes = [];
}
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Class Information (6–10)</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('academics.php') ?>" class="text-warning text-decoration-none">Academics</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Classes</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center max-w-xl mx-auto mb-5">
            <span class="text-success fw-bold text-uppercase small"><i class="fas fa-layer-group me-1"></i> Grade Structure</span>
            <h2 class="display-6 fw-bold text-dark mt-1" style="color: #123B2A;">Classroom Levels & Subjects</h2>
            <p class="text-muted">Detailed curriculum distribution, section quotas, and subject offerings from Class 6 to Class 10.</p>
        </div>

        <div class="row g-4">
            <?php if (!empty($classes)): ?>
                <?php foreach ($classes as $c): ?>
                    <?php
                    // Fetch sections for this class
                    $sections = Database::fetchAll("SELECT * FROM sections WHERE class_id = ? AND status = 'active'", [$c['id']]);
                    // Fetch subjects for this class
                    $subjects = Database::fetchAll(
                        "SELECT cs.*, s.subject_name, s.subject_code, s.subject_type 
                         FROM class_subjects cs 
                         JOIN subjects s ON cs.subject_id = s.id 
                         WHERE cs.class_id = ? 
                         ORDER BY s.subject_code ASC",
                        [$c['id']]
                    );
                    ?>
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white" style="border-top: 5px solid #123B2A !important;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h3 class="fw-bold mb-0 text-dark"><?= e($c['class_name']) ?></h3>
                                    <small class="text-success fw-semibold">Grade <?= e($c['numeric_value']) ?> Secondary Level</small>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fs-6">
                                    <?= count($sections) ?> Sections
                                </span>
                            </div>

                            <p class="text-muted small mb-3"><?= e($c['description']) ?></p>

                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-users-class text-warning me-2"></i> Active Sections:</h6>
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <?php foreach ($sections as $sec): ?>
                                    <span class="badge bg-light text-dark border p-2 px-3 rounded-pill">
                                        <i class="fas fa-door-open text-success me-1"></i> <?= e($sec['section_name']) ?> 
                                        <span class="text-muted">(Cap: <?= (int)$sec['capacity'] ?>)</span>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-books text-success me-2"></i> Prescribed Subjects:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle small mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Subject</th>
                                            <th>Type</th>
                                            <th>Full Marks</th>
                                            <th>Pass</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($subjects)): ?>
                                            <?php foreach ($subjects as $sub): ?>
                                                <tr>
                                                    <td class="fw-bold text-secondary"><?= e($sub['subject_code']) ?></td>
                                                    <td><?= e($sub['subject_name']) ?></td>
                                                    <td class="text-capitalize"><?= e($sub['subject_type']) ?></td>
                                                    <td class="fw-semibold"><?= (int)$sub['full_marks'] ?></td>
                                                    <td class="text-muted"><?= (int)$sub['pass_marks'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-muted text-center">Subject mapping available upon enrollment.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-auto pt-2 text-end">
                                <a href="<?= url('admission.php') ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                    Apply for <?= e($c['class_name']) ?> &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Class information is currently being updated.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
