<?php
/**
 * Teacher Assignment & Study Material Management Module
 * Sirajganj Road Chowrasta High School
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTeacher();

$db = Database::getInstance();
$activeSessionId = (int)getSetting('active_session_id', 1);
$currentTeacher = getCurrentTeacher();
$teacherId = (int)$currentTeacher['id'];

// Fetch teacher's assigned classes, sections, subjects
$assignedCourses = $db->fetchAll(
    "SELECT ta.id AS assignment_id,
            c.id AS class_id, c.name AS class_name,
            s.id AS section_id, s.name AS section_name,
            sub.id AS subject_id, sub.name AS subject_name, sub.code AS subject_code
     FROM teacher_assignments ta
     JOIN classes c ON ta.class_id = c.id
     JOIN sections s ON ta.section_id = s.id
     JOIN subjects sub ON ta.subject_id = sub.id
     WHERE ta.teacher_id = ? AND ta.session_id = ?
     ORDER BY c.numeric_name ASC, s.name ASC, sub.name ASC",
    [$teacherId, $activeSessionId]
);

// Handle New Assignment / Study Material Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_assignment') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed (CSRF).');
        redirect('assignments.php');
    }

    $courseSelection = cleanInput($_POST['course_selection'] ?? '');
    $parts = explode(':', $courseSelection);
    if (count($parts) !== 3) {
        setFlash('danger', 'Please select an assigned class, section, and subject.');
        redirect('assignments.php');
    }

    $classId = (int)$parts[0];
    $sectionId = (int)$parts[1];
    $subjectId = (int)$parts[2];

    // Verify teacher owns this assignment
    $validCourse = false;
    foreach ($assignedCourses as $ac) {
        if ((int)$ac['class_id'] === $classId && (int)$ac['section_id'] === $sectionId && (int)$ac['subject_id'] === $subjectId) {
            $validCourse = true;
            break;
        }
    }

    if (!$validCourse) {
        setFlash('danger', 'You are not assigned to teach this class and subject.');
        redirect('assignments.php');
    }

    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $dueDate = cleanInput($_POST['due_date'] ?? '');
    $assignedDate = date('Y-m-d');

    if (empty($title)) {
        setFlash('danger', 'Assignment title is required.');
        redirect('assignments.php');
    }

    $filePath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['attachment'], 'assignments', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip']);
        if ($uploadResult['success']) {
            $filePath = $uploadResult['filename'];
        } else {
            setFlash('danger', 'File upload error: ' . $uploadResult['error']);
            redirect('assignments.php');
        }
    }

    try {
        $db->query(
            "INSERT INTO assignments (session_id, class_id, section_id, subject_id, teacher_id, title, description, file_path, assigned_date, due_date, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [$activeSessionId, $classId, $sectionId, $subjectId, $teacherId, $title, $description, $filePath, $assignedDate, !empty($dueDate) ? $dueDate : null]
        );

        logActivity($_SESSION['user_id'], 'upload_assignment', "Teacher {$currentTeacher['name']} uploaded assignment '{$title}'");
        setFlash('success', 'Assignment / Study Material created successfully!');
    } catch (Exception $e) {
        setFlash('danger', 'Database error: ' . $e->getMessage());
    }

    redirect('assignments.php');
}

// Handle Delete Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_assignment') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Security validation failed.');
        redirect('assignments.php');
    }

    $assignmentId = (int)($_POST['assignment_id'] ?? 0);
    $assignment = $db->fetchOne(
        "SELECT * FROM assignments WHERE id = ? AND teacher_id = ?",
        [$assignmentId, $teacherId]
    );

    if ($assignment) {
        if (!empty($assignment['file_path'])) {
            deleteUploadedFile($assignment['file_path']);
        }
        $db->query("DELETE FROM assignments WHERE id = ?", [$assignmentId]);
        logActivity($_SESSION['user_id'], 'delete_assignment', "Teacher {$currentTeacher['name']} deleted assignment '{$assignment['title']}'");
        setFlash('success', 'Assignment deleted successfully.');
    } else {
        setFlash('danger', 'Assignment not found or permission denied.');
    }

    redirect('assignments.php');
}

// Fetch teacher's assignments
$myAssignments = $db->fetchAll(
    "SELECT a.*, c.name AS class_name, s.name AS section_name, sub.name AS subject_name, sub.code AS subject_code,
            (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) AS submission_count
     FROM assignments a
     JOIN classes c ON a.class_id = c.id
     JOIN sections s ON a.section_id = s.id
     JOIN subjects sub ON a.subject_id = sub.id
     WHERE a.teacher_id = ? AND a.session_id = ?
     ORDER BY a.created_at DESC",
    [$teacherId, $activeSessionId]
);

$pageTitle = "Assignments & Study Materials";
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-book-open-reader text-primary me-2"></i>Assignments & Study Materials</h4>
        <p class="text-muted small mb-0">Upload lesson materials, homework, and track class submissions</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload New
        </button>
    </div>
</div>

<?php displayFlash(); ?>

<!-- Assignment List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="fa-solid fa-folder-open text-primary me-2"></i>
            Uploaded Materials (<?= count($myAssignments) ?>)
        </h6>
        <div class="small text-muted">
            Session <?= e(getSetting('current_session', '2026')) ?>
        </div>
    </div>

    <?php if (empty($myAssignments)): ?>
        <div class="card-body p-5 text-center">
            <div class="mb-3 text-muted">
                <i class="fa-solid fa-file-circle-question fa-3x"></i>
            </div>
            <h5 class="fw-bold">No Materials Uploaded Yet</h5>
            <p class="text-muted">You haven't uploaded any homework or study documents yet for your assigned classes.</p>
            <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload First Assignment
            </button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title & Details</th>
                        <th>Class & Section</th>
                        <th>Subject</th>
                        <th>Assigned Date</th>
                        <th>Due Date</th>
                        <th>File Attachment</th>
                        <th class="text-center">Submissions</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myAssignments as $asg): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($asg['title']) ?></div>
                                <?php if (!empty($asg['description'])): ?>
                                    <div class="text-muted small text-truncate" style="max-width: 250px;">
                                        <?= e($asg['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= e($asg['class_name']) ?> (<?= e($asg['section_name']) ?>)
                                </span>
                            </td>
                            <td>
                                <span class="fw-semibold text-secondary">
                                    <?= e($asg['subject_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="small text-muted"><?= date('d M, Y', strtotime($asg['assigned_date'])) ?></span>
                            </td>
                            <td>
                                <?php if ($asg['due_date']): ?>
                                    <?php $isOverdue = strtotime($asg['due_date']) < time(); ?>
                                    <span class="badge <?= $isOverdue ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis' ?>">
                                        <?= date('d M, Y', strtotime($asg['due_date'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">No deadline</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($asg['file_path'])): ?>
                                    <a href="<?= e(resolveUploadUrl($asg['file_path'], 'assignments')) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2">
                                        <i class="fa-solid fa-paperclip me-1"></i> Download
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill">
                                    <?= $asg['submission_count'] ?> submissions
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="assignments.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_assignment">
                                    <input type="hidden" name="assignment_id" value="<?= $asg['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Delete">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- New Assignment Modal -->
<div class="modal fade" id="newAssignmentModal" tabindex="-1" aria-labelledby="newAssignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="assignments.php" enctype="multipart/form-data" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create_assignment">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="newAssignmentModalLabel">
                    <i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>Upload Assignment or Study Material
                </h5>
                <button type="button" class="btn-close" data-bs-target="#newAssignmentModal" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Target Class, Section & Subject <span class="text-danger">*</span></label>
                        <select name="course_selection" class="form-select" required>
                            <option value="">-- Choose Assigned Course --</option>
                            <?php foreach ($assignedCourses as $ac): ?>
                                <option value="<?= $ac['class_id'] . ':' . $ac['section_id'] . ':' . $ac['subject_id'] ?>">
                                    <?= e($ac['class_name']) ?> — Section <?= e($ac['section_name']) ?>: <?= e($ac['subject_name']) ?> (<?= e($ac['subject_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Assignment / Material Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 4 Geometry Worksheet & Practice Problems" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Submission Due Date</label>
                        <input type="date" name="due_date" class="form-control" min="<?= date('Y-m-d') ?>">
                        <div class="form-text">Leave blank for reading materials or study notes.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Attachment File</label>
                        <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                        <div class="form-text">Allowed formats: PDF, Word, Images, Zip (Max 10MB)</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Instructions or Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Enter instructions for students..."></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fa-solid fa-check me-1"></i> Publish Assignment
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
