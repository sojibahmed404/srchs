<?php
/**
 * Sirajganj Road Chowrasta High School - Photo Gallery Management
 */

$pageTitle = 'Photo Gallery Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');
$categories = ['Campus', 'Sports', 'Cultural', 'Classroom', 'Events', 'Laboratory'];

// 1. Delete Photo
if ($action === 'delete') {
    $delId = getInt('id');
    if ($delId > 0 && verifyCSRFToken()) {
        $photo = Database::fetchOne("SELECT * FROM galleries WHERE id = ?", [$delId]);
        if ($photo) {
            deleteFile($photo['image_path']);
            Database::execute("DELETE FROM galleries WHERE id = ?", [$delId]);
            logActivity('gallery_delete', "Deleted gallery image: {$photo['title']}");
            setFlash('success', 'Gallery photo deleted.');
        }
    }
    redirect(url('admin/gallery/index.php'));
}

// 2. Upload / Add Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $title = getString('title', 'POST');
        $category = getString('category', 'POST') ?: 'Campus';
        $caption = getString('caption', 'POST');
        $orderNum = getInt('order_num', 'POST');

        if (empty($title)) {
            setFlash('danger', 'Photo title is required.');
        } else {
            $imagePath = null;
            if (!empty($_FILES['image']['name'])) {
                $upload = uploadFile($_FILES['image'], 'gallery', ALLOWED_IMAGE_TYPES);
                if ($upload['success']) {
                    $imagePath = $upload['path'];
                } else {
                    setFlash('danger', 'Photo upload error: ' . $upload['message']);
                }
            } else {
                // If using URL or fallback
                $imagePath = 'assets/images/gallery/campus-main.jpg';
            }

            if ($imagePath) {
                try {
                    Database::insert(
                        "INSERT INTO galleries (title, category, image_path, caption, order_num) VALUES (?, ?, ?, ?, ?)",
                        [$title, $category, $imagePath, $caption ?: null, $orderNum]
                    );
                    logActivity('gallery_upload', "Uploaded gallery image: {$title}");
                    setFlash('success', 'Photo added to school gallery.');
                } catch (Exception $e) {
                    setFlash('danger', 'Error adding gallery item: ' . $e->getMessage());
                }
            }
        }
    }
    redirect(url('admin/gallery/index.php'));
}

$galleryList = Database::fetchAll("SELECT * FROM galleries ORDER BY order_num ASC, id DESC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Photo Gallery Management</h4>
        <p class="text-muted small mb-0">Upload campus photography, laboratory exhibits, sports meets, and national celebrations.</p>
    </div>
    <div>
        <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadPhotoModal">
            <i class="fas fa-upload me-1"></i> Upload New Photo
        </button>
    </div>
</div>

<div class="row g-4">
    <?php if (!empty($galleryList)): ?>
        <?php foreach ($galleryList as $g): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100">
                    <div style="height: 200px; background: #123B2A;">
                        <img src="<?= asset($g['image_path']) ?>" 
                             onerror="this.onerror=null;this.src='https://picsum.photos/seed/<?= urlencode($g['title']) ?>/600/400';" 
                             alt="<?= e($g['title']) ?>" 
                             class="w-100 h-100 object-fit-cover">
                    </div>
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-light text-success border"><?= e($g['category']) ?></span>
                            <small class="text-muted">Order: <?= (int)$g['order_num'] ?></small>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 text-truncate"><?= e($g['title']) ?></h6>
                        <small class="text-muted mb-3"><?= e(truncate($g['caption'] ?: 'Campus photograph', 70)) ?></small>

                        <div class="mt-auto text-end pt-2 border-top">
                            <a href="<?= url('admin/gallery/index.php?action=delete&id=' . $g['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                               class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-confirm-delete" 
                               data-confirm="Delete this photo from the public gallery?">
                                <i class="fas fa-trash me-1"></i> Delete
                            </a>
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

<!-- ==================== UPLOAD MODAL ==================== -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-camera text-warning me-2"></i> Add Photo to Gallery</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/gallery/index.php') ?>" method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Photo Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Science Laboratory Experiment" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Photo Image File (JPG/PNG, max 5MB)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Caption / Description</label>
                        <textarea name="caption" class="form-control" rows="2" placeholder="Brief context about this photo"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Display Priority (Order)</label>
                        <input type="number" name="order_num" class="form-control" value="1" min="0">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Upload to Gallery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
