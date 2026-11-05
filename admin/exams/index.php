<?php
/**
 * Sirajganj Road Chowrasta High School - Examination Management
 */

$pageTitle = 'Examination Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');
$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

// 1. Toggle Publish
if ($action === 'toggle_publish') {
    $examId = getInt('id');
    if ($examId > 0 && verifyCSRFToken()) {
        try {
            $exam = Database::fetchOne("SELECT * FROM exams WHERE id = ?", [$examId]);
            if ($exam) {
                $newStatus = $exam['is_published'] ? 0 : 1;
                Database::execute("UPDATE exams SET is_published = ? WHERE id = ?", [$newStatus, $examId]);
                // Also update corresponding results
                Database::execute("UPDATE results SET is_published = ? WHERE exam_id = ?", [$newStatus, $examId]);

                logActivity('exam_publish_toggle', "Set published status of exam '{$exam['exam_name']}' to {$newStatus}");
                setFlash('success', "Exam results are now " . ($newStatus ? "PUBLISHED to the public search!" : "UNPUBLISHED (Hidden from public search)."));
            }
        } catch (Exception $e) {
            setFlash('danger', 'Error updating publish status: ' . $e->getMessage());
        }
    }
    redirect(url('admin/exams/index.php'));
}

// 2. Delete Exam
if ($action === 'delete') {
    $examId = getInt('id');
    if ($examId > 0 && verifyCSRFToken()) {
        try {
            Database::execute("DELETE FROM exams WHERE id = ?", [$examId]);
            logActivity('exam_delete', "Deleted examination ID {$examId}");
            setFlash('success', 'Examination deleted.');
        } catch (Exception $e) {
            setFlash('danger', 'Error deleting exam: ' . $e->getMessage());
        }
    }
    redirect(url('admin/exams/index.php'));
}

// 3. Create / Update Exam
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $examId = getInt('exam_id', 'POST');
        $examName = getString('exam_name', 'POST');
        $startDate = getString('start_date', 'POST');
        $endDate = getString('end_date', 'POST');
        $description = getString('description', 'POST');
        $status = getString('status', 'POST') ?: 'upcoming';

        if (empty($examName) || empty($startDate) || empty($endDate)) {
            setFlash('danger', 'Exam title and scheduled dates are required.');
        } else {
            try {
                if ($examId > 0) {
                    Database::execute(
                        "UPDATE exams SET exam_name = ?, start_date = ?, end_date = ?, description = ?, status = ? WHERE id = ?",
                        [$examName, $startDate, $endDate, $description, $status, $examId]
                    );
                    logActivity('exam_update', "Updated exam: {$examName}");
                    setFlash('success', 'Exam updated successfully.');
                } else {
                    Database::insert(
                        "INSERT INTO exams (session_id, exam_name, start_date, end_date, description, status, is_published) 
                         VALUES (?, ?, ?, ?, ?, ?, 0)",
                        [$sessionId, $examName, $startDate, $endDate, $description, $status]
                    );
                    logActivity('exam_create', "Created exam: {$examName}");
                    setFlash('success', 'New examination scheduled successfully.');
                }
            } catch (Exception $e) {
                setFlash('danger', 'Error saving exam: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/exams/index.php'));
}

$examsList = Database::fetchAll("SELECT * FROM exams WHERE session_id = ? ORDER BY start_date DESC", [$sessionId]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Examination Management</h4>
        <p class="text-muted small mb-0">Active Academic Session: <strong><?= e($activeSession['session_name'] ?? '2026') ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/results/marks.php') ?>" class="btn btn-outline-warning text-dark rounded-pill px-3">
            <i class="fas fa-calculator me-1"></i> Marks Entry
        </a>
        <a href="<?= url('admin/results/index.php') ?>" class="btn btn-outline-primary rounded-pill px-3">
            <i class="fas fa-poll me-1"></i> Result Publication
        </a>
        <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newExamModal">
            <i class="fas fa-plus me-1"></i> Schedule Exam
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Exam Title</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Public Results</th>
                    <th>Entered Marks</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($examsList)): ?>
                    <?php foreach ($examsList as $ex): ?>
                        <?php
                        $marksCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM marks WHERE exam_id = ?", [$ex['id']]);
                        $resultsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM results WHERE exam_id = ?", [$ex['id']]);
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($ex['exam_name']) ?></div>
                                <small class="text-muted"><?= e(truncate($ex['description'] ?: 'Official term exam', 60)) ?></small>
                            </td>
                            <td><?= formatDate($ex['start_date']) ?></td>
                            <td><?= formatDate($ex['end_date']) ?></td>
                            <td>
                                <span class="badge <?= $ex['status'] === 'completed' ? 'bg-secondary' : ($ex['status'] === 'ongoing' ? 'bg-primary' : 'bg-warning text-dark') ?> text-capitalize">
                                    <?= e($ex['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($ex['is_published']): ?>
                                    <span class="badge bg-success px-3 py-1 rounded-pill"><i class="fas fa-check-circle me-1"></i> PUBLISHED</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border px-3 py-1 rounded-pill"><i class="fas fa-eye-slash me-1"></i> Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= $marksCount ?> Marks | <?= $resultsCount ?> GPA calculated</small>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('admin/exams/index.php?action=toggle_publish&id=' . $ex['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   class="btn btn-sm <?= $ex['is_published'] ? 'btn-outline-warning text-dark' : 'btn-outline-success' ?> rounded-pill px-3 me-1" 
                                   title="Toggle Publication">
                                    <?= $ex['is_published'] ? 'Unpublish' : 'Publish Results' ?>
                                </a>
                                <a href="<?= url('admin/results/marks.php?exam_id=' . $ex['id']) ?>" 
                                   class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Enter Marks">
                                    <i class="fas fa-calculator"></i>
                                </a>
                                <a href="<?= url('admin/exams/index.php?action=delete&id=' . $ex['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-circle btn-confirm-delete" 
                                   data-confirm="Delete this exam and all associated student marks?" 
                                   title="Delete Exam">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No examinations scheduled for this session.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== NEW EXAM MODAL ==================== -->
<div class="modal fade" id="newExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus text-warning me-2"></i> Schedule New Examination</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/exams/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Examination Name <span class="text-danger">*</span></label>
                        <input type="text" name="exam_name" class="form-control" placeholder="e.g. Annual Examination 2026" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description / Circular Notes</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Term evaluation instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Create Examination</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
