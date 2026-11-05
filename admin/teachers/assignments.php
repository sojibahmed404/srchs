<?php
/**
 * Sirajganj Road Chowrasta High School - Teacher Class/Subject Assignments
 */

$pageTitle = 'Teacher Assignments';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');
$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

// 1. Save Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $teacherId = getInt('teacher_id', 'POST');
        $classId = getInt('class_id', 'POST');
        $sectionId = getInt('section_id', 'POST');
        $subjectId = getInt('subject_id', 'POST');
        $isClassTeacher = isset($_POST['is_class_teacher']) ? 1 : 0;

        if ($teacherId <= 0 || $classId <= 0 || $sectionId <= 0 || $subjectId <= 0) {
            setFlash('danger', 'Please select Teacher, Class, Section, and Subject.');
        } else {
            try {
                // If designating as class teacher, unset any existing class teacher for this class & section
                if ($isClassTeacher) {
                    Database::execute(
                        "UPDATE teacher_assignments SET is_class_teacher = 0 WHERE session_id = ? AND class_id = ? AND section_id = ?",
                        [$sessionId, $classId, $sectionId]
                    );
                }

                Database::insert(
                    "INSERT INTO teacher_assignments (teacher_id, session_id, class_id, section_id, subject_id, is_class_teacher) 
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id), is_class_teacher = VALUES(is_class_teacher)",
                    [$teacherId, $sessionId, $classId, $sectionId, $subjectId, $isClassTeacher]
                );

                logActivity('teacher_assigned', "Assigned teacher ID {$teacherId} to class {$classId}, sec {$sectionId}, subject {$subjectId}");
                setFlash('success', 'Teacher assignment saved successfully.');
            } catch (Exception $e) {
                setFlash('danger', 'Error assigning teacher: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/teachers/assignments.php'));
}

// 2. Delete Assignment
if ($action === 'delete') {
    $assignId = getInt('id');
    if ($assignId > 0 && verifyCSRFToken()) {
        Database::execute("DELETE FROM teacher_assignments WHERE id = ?", [$assignId]);
        setFlash('success', 'Assignment removed.');
    }
    redirect(url('admin/teachers/assignments.php'));
}

// Fetch resources
$teachers = Database::fetchAll("SELECT t.*, u.full_name FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY t.order_priority ASC");
$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$sections = Database::fetchAll("SELECT * FROM sections WHERE status = 'active' ORDER BY section_name ASC");
$subjects = Database::fetchAll("SELECT * FROM subjects WHERE status = 'active' ORDER BY subject_name ASC");

// Fetch active assignments
$assignments = Database::fetchAll(
    "SELECT ta.*, u.full_name, t.teacher_code, t.designation,
            c.class_name, sec.section_name, s.subject_name, s.subject_code 
     FROM teacher_assignments ta 
     JOIN teachers t ON ta.teacher_id = t.id 
     JOIN users u ON t.user_id = u.id 
     JOIN classes c ON ta.class_id = c.id 
     JOIN sections sec ON ta.section_id = sec.id 
     JOIN subjects s ON ta.subject_id = s.id 
     WHERE ta.session_id = ? 
     ORDER BY c.numeric_value ASC, sec.section_name ASC, s.subject_name ASC",
    [$sessionId]
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Teacher-Class-Subject Assignments</h4>
        <p class="text-muted small mb-0">Active Academic Session: <strong><?= e($activeSession['session_name'] ?? '2026') ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/teachers/index.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
            &larr; Teacher Directory
        </a>
        <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#assignModal">
            <i class="fas fa-plus me-1"></i> New Assignment
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Teacher Name</th>
                    <th>Code / Role</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Assigned Subject</th>
                    <th>Class Teacher?</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($assignments)): ?>
                    <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($a['full_name']) ?></div>
                                <small class="text-muted"><?= e($a['designation']) ?></small>
                            </td>
                            <td><span class="badge bg-light text-success border"><?= e($a['teacher_code']) ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?= e($a['class_name']) ?></span></td>
                            <td><span class="badge bg-light text-dark border"><?= e($a['section_name']) ?></span></td>
                            <td>
                                <span class="fw-semibold text-primary"><?= e($a['subject_name']) ?></span>
                                <small class="text-muted">(Code: <?= e($a['subject_code']) ?>)</small>
                            </td>
                            <td>
                                <?php if ($a['is_class_teacher']): ?>
                                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill"><i class="fas fa-crown me-1"></i> Class Teacher</span>
                                <?php else: ?>
                                    <span class="text-muted small">Subject Teacher</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('admin/teachers/assignments.php?action=delete&id=' . $a['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-circle btn-confirm-delete" 
                                   data-confirm="Remove this teaching assignment?" 
                                   title="Remove Assignment">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No teaching assignments recorded for this session.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== ASSIGN MODAL ==================== -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-tasks text-warning me-2"></i> Assign Teacher to Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/teachers/assignments.php') ?>" method="POST">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Teacher <span class="text-danger">*</span></label>
                        <select name="teacher_id" class="form-select" required>
                            <option value="">-- Choose Teacher --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= e($t['full_name'] . ' (' . $t['teacher_code'] . ' - ' . $t['designation'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Section <span class="text-danger">*</span></label>
                        <select name="section_id" class="form-select" required>
                            <option value="">-- Choose Section --</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= $sec['id'] ?>"><?= e($sec['section_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= e($sub['subject_code'] . ' - ' . $sub['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_class_teacher" id="isClassTeacherCheck">
                        <label class="form-check-label small" for="isClassTeacherCheck">Designate as Official Class Teacher for this section</label>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Save Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
