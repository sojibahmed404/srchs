<?php
/**
 * Sirajganj Road Chowrasta High School - Public Notice Board
 */

require_once __DIR__ . '/includes/auth.php';

$page = max(1, getInt('page'));
$category = getString('category');
$search = getString('search');
$singleId = getInt('id');

$singleNotice = null;
if ($singleId > 0) {
    $singleNotice = Database::fetchOne("SELECT n.*, u.full_name as author_name FROM notices n JOIN users u ON n.author_id = u.id WHERE n.id = ? AND n.status = 'published'", [$singleId]);
}

$where = "WHERE status = 'published'";
$params = [];

if ($category && in_array($category, ['academic', 'exam', 'admission', 'event', 'general'])) {
    $where .= " AND category = ?";
    $params[] = $category;
}

if ($search) {
    $where .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$baseQuery = "SELECT * FROM notices {$where} ORDER BY is_pinned DESC, published_date DESC, id DESC";
$pagination = paginate($baseQuery, $params, $page, 8);
$notices = $pagination['data'];

$pageTitle = 'Notice Board - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Notice Board</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Notices & Circulars</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">

        <!-- Single Notice Modal/View if requested -->
        <?php if ($singleNotice): ?>
            <div class="card border-0 shadow rounded-4 p-4 p-md-5 mb-5" style="background-color: #F5F7F5; border-left: 6px solid #123B2A !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-success px-3 py-2 rounded-pill text-uppercase">
                        <?= e($singleNotice['category']) ?> Notice
                    </span>
                    <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> Published: <?= formatDate($singleNotice['published_date'], 'F d, Y') ?></small>
                </div>
                <h3 class="fw-bold text-dark mb-3"><?= e($singleNotice['title']) ?></h3>
                <div class="text-secondary leading-relaxed mb-4" style="white-space: pre-line;">
                    <?= nl2br(e($singleNotice['content'])) ?>
                </div>

                <?php if (!empty($singleNotice['attachment'])): ?>
                    <div class="mb-4">
                        <a href="<?= asset($singleNotice['attachment']) ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                            <i class="fas fa-file-pdf me-1"></i> Download Official Circular Attachment
                        </a>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <small class="text-muted"><i class="fas fa-user-edit me-1"></i> Published by: <?= e($singleNotice['author_name']) ?></small>
                    <a href="<?= url('notices.php') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        &larr; Back to Notice List
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-light">
            <form action="<?= url('notices.php') ?>" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search notices by keyword..." value="<?= e($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">-- All Categories --</option>
                        <option value="academic" <?= $category === 'academic' ? 'selected' : '' ?>>Academic Notices</option>
                        <option value="exam" <?= $category === 'exam' ? 'selected' : '' ?>>Examination & Routines</option>
                        <option value="admission" <?= $category === 'admission' ? 'selected' : '' ?>>Admission Notices</option>
                        <option value="event" <?= $category === 'event' ? 'selected' : '' ?>>Events & Observances</option>
                        <option value="general" <?= $category === 'general' ? 'selected' : '' ?>>General Circulars</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success rounded-pill px-4 flex-grow-1">
                        Filter
                    </button>
                    <?php if ($category || $search): ?>
                        <a href="<?= url('notices.php') ?>" class="btn btn-outline-secondary rounded-pill">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Notices Grid -->
        <div class="row g-4 mb-5">
            <?php if (!empty($notices)): ?>
                <?php foreach ($notices as $n): ?>
                    <div class="col-lg-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white hover-shadow transition" style="border-left: 5px solid <?= $n['category'] === 'exam' ? '#0d6efd' : ($n['category'] === 'admission' ? '#198754' : '#D9AD55') ?> !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-light text-dark text-uppercase small border px-2 py-1">
                                    <?= e($n['category']) ?>
                                </span>
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt me-1 text-success"></i> <?= formatDate($n['published_date'], 'M d, Y') ?>
                                </small>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">
                                <?php if ($n['is_pinned']): ?>
                                    <span class="badge bg-danger me-1 small">PINNED</span>
                                <?php endif; ?>
                                <?= e($n['title']) ?>
                            </h5>
                            <p class="text-muted small mb-3">
                                <?= e(truncate($n['content'], 180)) ?>
                            </p>
                            <div class="mt-auto d-flex justify-content-between align-items-center pt-2 border-top">
                                <a href="<?= url('notices.php?id=' . $n['id']) ?>" class="btn btn-sm btn-link p-0 text-success fw-bold text-decoration-none">
                                    View Full Circular <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                                <?php if (!empty($n['attachment'])): ?>
                                    <a href="<?= asset($n['attachment']) ?>" target="_blank" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none small">
                                        <i class="fas fa-file-pdf me-1"></i> PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted fs-3 mb-2"><i class="fas fa-inbox"></i></div>
                    <p class="text-muted">No notices matched your selected filter.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php
        $baseUrl = url('notices.php?category=' . urlencode($category) . '&search=' . urlencode($search));
        echo renderPagination($pagination, $baseUrl);
        ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
