<?php
/**
 * Sirajganj Road Chowrasta High School - Notice Board Management
 */

$pageTitle = 'Notice Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');

// 1. Delete Notice
if ($action === 'delete') {
    $delId = getInt('id');
    if ($delId > 0 && verifyCSRFToken()) {
        try {
            $notice = Database::fetchOne("SELECT * FROM notices WHERE id = ?", [$delId]);
            if ($notice) {
                if (!empty($notice['attachment'])) {
                    deleteFile($notice['attachment']);
                }
                Database::execute("DELETE FROM notices WHERE id = ?", [$delId]);
                logActivity('notice_delete', "Deleted notice: {$notice['title']}");
                setFlash('success', 'Notice deleted successfully.');
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting notice: ' . $e->getMessage());
        }
    }
    redirect(url('admin/notices/index.php'));
}

// 2. Toggle Pin
if ($action === 'toggle_pin') {
    $pinId = getInt('id');
    if ($pinId > 0 && verifyCSRFToken()) {
        $n = Database::fetchOne("SELECT is_pinned FROM notices WHERE id = ?", [$pinId]);
        if ($n) {
            $newPin = $n['is_pinned'] ? 0 : 1;
            Database::execute("UPDATE notices SET is_pinned = ? WHERE id = ?", [$newPin, $pinId]);
            setFlash('success', 'Notice pinned status updated.');
        }
    }
    redirect(url('admin/notices/index.php'));
}

// 3. Create / Update Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $noticeId = getInt('notice_id', 'POST');
        $title = getString('title', 'POST');
        $content = getString('content', 'POST');
        $category = getString('category', 'POST') ?: 'general';
        $target = getString('target_audience', 'POST') ?: 'all';
        $pubDate = getString('published_date', 'POST') ?: date('Y-m-d');
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        $status = getString('status', 'POST') ?: 'published';

        if (empty($title) || empty($content)) {
            setFlash('danger', 'Notice title and message content are required.');
        } else {
            // Attachment Handling
            $attachmentPath = null;
            if (!empty($_FILES['attachment']['name'])) {
                $upload = uploadFile($_FILES['attachment'], 'notices', array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES));
                if ($upload['success']) {
                    $attachmentPath = $upload['path'];
                }
            }

            try {
                if ($noticeId > 0) {
                    $sql = "UPDATE notices SET title = ?, content = ?, category = ?, target_audience = ?, published_date = ?, is_pinned = ?, status = ?";
                    $params = [$title, $content, $category, $target, $pubDate, $isPinned, $status];
                    if ($attachmentPath) {
                        $sql .= ", attachment = ?";
                        $params[] = $attachmentPath;
                    }
                    $sql .= " WHERE id = ?";
                    $params[] = $noticeId;

                    Database::execute($sql, $params);
                    logActivity('notice_update', "Updated notice: {$title}");
                    setFlash('success', 'Notice updated successfully.');
                } else {
                    Database::insert(
                        "INSERT INTO notices (title, content, category, target_audience, attachment, published_date, is_pinned, status, author_id) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [$title, $content, $category, $target, $attachmentPath, $pubDate, $isPinned, $status, $_SESSION['user_id']]
                    );
                    logActivity('notice_create', "Published new notice: {$title}");
                    setFlash('success', 'Notice published successfully.');
                }
                redirect(url('admin/notices/index.php'));
            } catch (Exception $e) {
                setFlash('danger', 'Error saving notice: ' . $e->getMessage());
            }
        }
    }
}

// Edit Notice Prep
$editNotice = null;
if ($action === 'edit') {
    $editNotice = Database::fetchOne("SELECT * FROM notices WHERE id = ?", [getInt('id')]);
}

$noticesList = Database::fetchAll(
    "SELECT n.*, u.full_name as author_name 
     FROM notices n 
     JOIN users u ON n.author_id = u.id 
     ORDER BY n.is_pinned DESC, n.published_date DESC, n.id DESC"
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Notice Board Management</h4>
        <p class="text-muted small mb-0">Publish circulars, exam schedules, and official notifications for students and guardians.</p>
    </div>
    <div>
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
            <a href="<?= url('admin/notices/index.php?action=create') ?>" class="btn btn-success rounded-pill px-4">
                <i class="fas fa-plus me-1"></i> Compose Notice
            </a>
        <?php else: ?>
            <a href="<?= url('admin/notices/index.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
                &larr; Back to Notice List
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- ==================== FORM: CREATE OR EDIT ==================== -->
<?php if ($action === 'create' || $action === 'edit'): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-5">
        <h5 class="fw-bold text-dark mb-4 border-bottom pb-3">
            <i class="fas <?= $action === 'edit' ? 'fa-edit text-warning' : 'fa-bullhorn text-success' ?> me-2"></i>
            <?= $action === 'edit' ? 'Edit Circular Notice' : 'Publish New Circular Notice' ?>
        </h5>

        <form action="<?= url('admin/notices/index.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="notice_id" value="<?= $editNotice ? (int)$editNotice['id'] : 0 ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Notice Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Schedule of Annual Assessment 2026" value="<?= e($editNotice['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select" required>
                        <option value="general" <?= ($editNotice['category'] ?? '') === 'general' ? 'selected' : '' ?>>General Notice</option>
                        <option value="academic" <?= ($editNotice['category'] ?? '') === 'academic' ? 'selected' : '' ?>>Academic</option>
                        <option value="exam" <?= ($editNotice['category'] ?? '') === 'exam' ? 'selected' : '' ?>>Examination & Routine</option>
                        <option value="admission" <?= ($editNotice['category'] ?? '') === 'admission' ? 'selected' : '' ?>>Admission</option>
                        <option value="event" <?= ($editNotice['category'] ?? '') === 'event' ? 'selected' : '' ?>>Observance & Events</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Notice Content & Body <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control" rows="6" placeholder="Write the full circular announcement text..." required><?= e($editNotice['content'] ?? '') ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Target Audience</label>
                    <select name="target_audience" class="form-select">
                        <option value="all" <?= ($editNotice['target_audience'] ?? '') === 'all' ? 'selected' : '' ?>>Public & All</option>
                        <option value="students" <?= ($editNotice['target_audience'] ?? '') === 'students' ? 'selected' : '' ?>>Students Only</option>
                        <option value="teachers" <?= ($editNotice['target_audience'] ?? '') === 'teachers' ? 'selected' : '' ?>>Teachers Only</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Publication Date</label>
                    <input type="date" name="published_date" class="form-control" value="<?= e($editNotice['published_date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Publication Status</label>
                    <select name="status" class="form-select">
                        <option value="published" <?= ($editNotice['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                        <option value="draft" <?= ($editNotice['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label small fw-semibold">PDF Attachment / Circular Image (Optional)</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png">
                    <?php if (!empty($editNotice['attachment'])): ?>
                        <small class="text-muted d-block mt-1">Existing Attachment: <a href="<?= asset($editNotice['attachment']) ?>" target="_blank">View File</a></small>
                    <?php endif; ?>
                </div>

                <div class="col-md-4 d-flex align-items-center pt-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinnedCheck" <?= !empty($editNotice['is_pinned']) ? 'checked' : '' ?>>
                        <label class="form-check-label small fw-semibold" for="isPinnedCheck">Pin this notice to top of public notice board</label>
                    </div>
                </div>
            </div>

            <div class="text-end border-top pt-3">
                <a href="<?= url('admin/notices/index.php') ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill me-2">Cancel</a>
                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold">
                    <i class="fas fa-save me-1"></i> <?= $action === 'edit' ? 'Update Notice' : 'Publish Notice' ?>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Notice List Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">Pin</th>
                    <th>Notice Title</th>
                    <th>Category</th>
                    <th>Audience</th>
                    <th>Published</th>
                    <th>Author</th>
                    <th>Attachment</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($noticesList)): ?>
                    <?php foreach ($noticesList as $n): ?>
                        <tr>
                            <td>
                                <a href="<?= url('admin/notices/index.php?action=toggle_pin&id=' . $n['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   title="Toggle Pin" class="<?= $n['is_pinned'] ? 'text-danger' : 'text-muted' ?>">
                                    <i class="fas fa-thumbtack fs-5"></i>
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($n['title']) ?></div>
                                <small class="text-muted"><?= e(truncate($n['content'], 70)) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border text-uppercase small"><?= e($n['category']) ?></span></td>
                            <td><span class="badge bg-light text-secondary border text-capitalize"><?= e($n['target_audience']) ?></span></td>
                            <td class="small text-muted"><?= formatDate($n['published_date'], 'd M Y') ?></td>
                            <td class="small text-muted"><?= e($n['author_name']) ?></td>
                            <td>
                                <?php if (!empty($n['attachment'])): ?>
                                    <a href="<?= asset($n['attachment']) ?>" target="_blank" class="badge bg-danger text-decoration-none">
                                        <i class="fas fa-file-pdf me-1"></i> File
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('admin/notices/index.php?action=edit&id=' . $n['id']) ?>" class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= url('admin/notices/index.php?action=delete&id=' . $n['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-circle btn-confirm-delete" 
                                   data-confirm="Delete this notice circular?" 
                                   title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">No notices recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
