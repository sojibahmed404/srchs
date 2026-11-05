<?php
/**
 * Sirajganj Road Chowrasta High School - Assignments & Study Materials Overview
 */

$pageTitle = 'Assignments & Study Materials';
require_once __DIR__ . '/../includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$action = getString('action');

// Delete assignment
if ($action === 'delete') {
    $delId = getInt('id');
    if ($delId > 0 && verifyCSRFToken()) {
        $asg = Database::fetchOne("SELECT * FROM assignments WHERE id = ?", [$delId]);
        if ($asg) {
            if (!empty($asg['attachment'])) {
                deleteFile($asg['attachment']);
            }
            Database::execute("DELETE FROM assignments WHERE id = ?", [$delId]);
            setFlash('success', 'Assignment / Material deleted.');
        }
    }
    redirect(url('admin/teachers/materials.php'));
}

$assignmentsList = Database::fetchAll(
    "SELECT a.*, c.class_name, sec.section_name, s.subject_name, u.full_name as teacher_name 
     FROM assignments a 
     JOIN classes c ON a.class_id = c.id 
     JOIN sections sec ON a.section_id = sec.id 
     JOIN subjects s ON a.subject_id = s.id 
     JOIN teachers t ON a.teacher_id = t.id 
     JOIN users u ON t.user_id = u.id 
     WHERE a.session_id = ? 
     ORDER BY a.id DESC",
    [$sessionId]
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Assignments & Study Materials</h4>
        <p class="text-muted small mb-0">Overview of instructional homework and digital lecture notes uploaded by teachers.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Type</th>
                    <th>Title & Description</th>
                    <th>Class / Section</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Due Date</th>
                    <th>Attachment</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($assignmentsList)): ?>
                    <?php foreach ($assignmentsList as $asg): ?>
                        <tr>
                            <td>
                                <span class="badge <?= $asg['assignment_type'] === 'assignment' ? 'bg-primary' : 'bg-success' ?> text-capitalize">
                                    <?= str_replace('_', ' ', $asg['assignment_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($asg['title']) ?></div>
                                <small class="text-muted"><?= e(truncate($asg['description'], 70)) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($asg['class_name']) ?></span>
                                <span class="badge bg-light text-secondary border"><?= e($asg['section_name']) ?></span>
                            </td>
                            <td class="fw-semibold text-primary"><?= e($asg['subject_name']) ?></td>
                            <td><small class="text-dark"><?= e($asg['teacher_name']) ?></small></td>
                            <td class="small text-muted"><?= $asg['due_date'] ? formatDate($asg['due_date']) : 'N/A' ?></td>
                            <td>
                                <?php if (!empty($asg['attachment'])): ?>
                                    <a href="<?= asset($asg['attachment']) ?>" target="_blank" class="badge bg-danger-subtle text-danger border border-danger-subtle text-decoration-none">
                                        <i class="fas fa-file-download me-1"></i> File
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('admin/teachers/materials.php?action=delete&id=' . $asg['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-circle btn-confirm-delete" 
                                   data-confirm="Delete this assignment/study material?" 
                                   title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No assignments or study materials uploaded yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
