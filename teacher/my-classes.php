<?php
/**
 * Sirajganj Road Chowrasta High School - Teacher Assigned Classes
 */

$pageTitle = 'My Assigned Classes';
require_once __DIR__ . '/includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$assignments = Database::fetchAll(
    "SELECT ta.*, c.class_name, sec.section_name, s.subject_name, s.subject_code, s.subject_type 
     FROM teacher_assignments ta 
     JOIN classes c ON ta.class_id = c.id 
     JOIN sections sec ON ta.section_id = sec.id 
     JOIN subjects s ON ta.subject_id = s.id 
     WHERE ta.teacher_id = ? AND ta.session_id = ? 
     ORDER BY c.numeric_value ASC, sec.section_name ASC",
    [$teacherId, $sessionId]
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">My Assigned Classes & Subjects</h4>
        <p class="text-muted small mb-0">Active Academic Session: <strong><?= e($activeSession['session_name'] ?? '2026') ?></strong></p>
    </div>
</div>

<div class="row g-4">
    <?php if (!empty($assignments)): ?>
        <?php foreach ($assignments as $a): ?>
            <?php
            $enrolledCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM enrollments WHERE class_id = ? AND section_id = ? AND session_id = ?", [$a['class_id'], $a['section_id'], $sessionId]);
            ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-top: 5px solid #123B2A !important;">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="fw-bold text-dark mb-0"><?= e($a['class_name']) ?> — <?= e($a['section_name']) ?></h4>
                            <p class="text-primary fw-semibold mb-0"><?= e($a['subject_name']) ?> (Code: <?= e($a['subject_code']) ?>)</p>
                        </div>
                        <?php if ($a['is_class_teacher']): ?>
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fas fa-crown me-1"></i> Class Teacher</span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">Subject Teacher</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-3 mb-4 text-muted small">
                        <div><i class="fas fa-users text-success me-1"></i> <?= $enrolledCount ?> Enrolled Students</div>
                        <div><i class="fas fa-tag text-info me-1"></i> <?= ucfirst($a['subject_type']) ?></div>
                    </div>

                    <div class="mt-auto d-flex flex-wrap gap-2 pt-3 border-top">
                        <a href="<?= url("teacher/attendance.php?class_id={$a['class_id']}&section_id={$a['section_id']}") ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                            <i class="fas fa-clipboard-check me-1"></i> Take Attendance
                        </a>
                        <a href="<?= url("teacher/marks-entry.php?class_id={$a['class_id']}&section_id={$a['section_id']}&subject_id={$a['subject_id']}") ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="fas fa-calculator me-1"></i> Enter Marks
                        </a>
                        <a href="<?= url("teacher/students.php?class_id={$a['class_id']}&section_id={$a['section_id']}") ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="fas fa-user-graduate me-1"></i> Student List
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <p class="text-muted">No teaching courses currently assigned to your account.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
