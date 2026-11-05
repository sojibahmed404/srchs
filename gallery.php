<?php
/**
 * Sirajganj Road Chowrasta High School - Photo Gallery
 */

require_once __DIR__ . '/includes/auth.php';

try {
    $galleryItems = Database::fetchAll("SELECT * FROM galleries ORDER BY order_num ASC, id DESC");
} catch (Exception $e) {
    $galleryItems = [];
}

$categories = ['Campus', 'Sports', 'Cultural', 'Classroom', 'Events', 'Laboratory'];

$pageTitle = 'Photo Gallery - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Photo Gallery</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Campus Gallery</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">

        <!-- Category Filter Buttons -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
            <button type="button" class="btn btn-primary rounded-pill px-4 gallery-filter-btn active" data-filter="all">
                All Photos
            </button>
            <?php foreach ($categories as $cat): ?>
                <button type="button" class="btn btn-outline-primary rounded-pill px-3 gallery-filter-btn" data-filter="<?= e($cat) ?>">
                    <?= e($cat) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Gallery Grid -->
        <div class="row g-4" id="galleryContainer">
            <?php if (!empty($galleryItems)): ?>
                <?php foreach ($galleryItems as $idx => $g): ?>
                    <div class="col-lg-4 col-md-6 gallery-item" data-category="<?= e($g['category']) ?>">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative group-hover" style="height: 260px;">
                            <img src="<?= asset($g['image_path']) ?>" 
                                 onerror="this.onerror=null;this.src='https://picsum.photos/seed/<?= urlencode($g['title']) ?>/800/600';" 
                                 alt="<?= e($g['title']) ?>" 
                                 class="w-100 h-100 object-fit-cover transition transform-scale">
                            
                            <!-- Overlay Caption -->
                            <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-dark bg-opacity-75 text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-bold text-truncate"><?= e($g['title']) ?></h6>
                                        <span class="badge bg-warning text-dark small"><?= e($g['category']) ?></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light rounded-circle" data-bs-toggle="modal" data-bs-target="#imgModal<?= $idx ?>">
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Lightbox Modal -->
                        <div class="modal fade" id="imgModal<?= $idx ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content border-0 rounded-4 overflow-hidden">
                                    <div class="modal-header border-0 bg-dark text-white">
                                        <h6 class="modal-title fw-bold"><?= e($g['title']) ?> (<?= e($g['category']) ?>)</h6>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-0 text-center bg-black">
                                        <img src="<?= asset($g['image_path']) ?>" 
                                             onerror="this.onerror=null;this.src='https://picsum.photos/seed/<?= urlencode($g['title']) ?>/800/600';" 
                                             alt="<?= e($g['title']) ?>" 
                                             class="img-fluid" style="max-height: 75vh;">
                                    </div>
                                    <?php if (!empty($g['caption'])): ?>
                                        <div class="modal-footer border-0 bg-dark text-white justify-content-start">
                                            <p class="mb-0 small text-light"><?= e($g['caption']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No gallery photos added yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
