<?php
/**
 * Sirajganj Road Chowrasta High School - Teachers & Staff Directory
 */

$pageTitle = 'Teachers & Staff - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';

try {
    $teachers = Database::fetchAll(
        "SELECT t.*, u.full_name, u.email, u.phone, u.avatar 
         FROM teachers t 
         JOIN users u ON t.user_id = u.id 
         WHERE u.status = 'active' 
         ORDER BY t.order_priority ASC, t.id ASC"
    );
} catch (Exception $e) {
    $teachers = [];
}
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Teachers & Staff</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Faculty & Staff</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row justify-content-between align-items-center mb-5">
            <div class="col-md-7">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-chalkboard-teacher me-1"></i> Academic Leadership</span>
                <h2 class="display-6 fw-bold text-dark mt-1" style="color: #123B2A;">Our Esteemed Teachers & Mentors</h2>
                <p class="text-muted mb-0">Qualified, passionate educators committed to character building and academic excellence.</p>
            </div>
            <div class="col-md-4 mt-3 mt-md-0">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 table-search-input" data-table="teachersGrid" placeholder="Search teacher by name or subject...">
                </div>
            </div>
        </div>

        <div class="row g-4" id="teachersGrid">
            <?php if (!empty($teachers)): ?>
                <?php foreach ($teachers as $t): ?>
                    <div class="col-lg-3 col-md-6 teacher-card-item">
                        <div class="card h-100 border-0 shadow-sm rounded-4 text-center overflow-hidden bg-white hover-shadow transition">
                            <div class="p-4 pb-2" style="background-color: #F5F7F5;">
                                <div class="rounded-circle mx-auto overflow-hidden border border-4 border-white shadow-sm mb-2" style="width: 120px; height: 120px; background: #e2e8f0;">
                                    <img src="<?= asset($t['avatar'] ?: 'assets/images/default-avatar.png') ?>" 
                                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($t['full_name']) ?>&background=123B2A&color=fff&size=160';" 
                                         alt="<?= e($t['full_name']) ?>" 
                                         class="w-100 h-100 object-fit-cover">
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 small">
                                    <?= e($t['teacher_code']) ?>
                                </span>
                            </div>

                            <div class="card-body p-4 d-flex flex-column">
                                <h5 class="fw-bold text-dark mb-1"><?= e($t['full_name']) ?></h5>
                                <p class="text-success fw-semibold small mb-2"><?= e($t['designation']) ?></p>
                                <p class="text-muted small mb-2"><i class="fas fa-graduation-cap me-1"></i> <?= e($t['qualification']) ?></p>

                                <?php if (!empty($t['specialization'])): ?>
                                    <p class="small text-secondary mb-3">
                                        <i class="fas fa-book-open me-1 text-warning"></i> <strong>Subject:</strong> <?= e($t['specialization']) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($t['bio'])): ?>
                                    <p class="small text-muted mb-3 fst-italic">
                                        "<?= e(truncate($t['bio'], 90)) ?>"
                                    </p>
                                <?php endif; ?>

                                <div class="mt-auto pt-3 border-top small text-muted text-start">
                                    <?php if (!empty($t['email'])): ?>
                                        <p class="mb-1 text-truncate">
                                            <i class="fas fa-envelope text-success me-2"></i> <?= e($t['email']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($t['joining_date'])): ?>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar-alt text-success me-2"></i> Serving Since: <?= formatDate($t['joining_date'], 'Y') ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted fs-4 mb-2"><i class="fas fa-users-slash"></i></div>
                    <p class="text-muted">No faculty members found in the directory.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
