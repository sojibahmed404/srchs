<?php
/**
 * Sirajganj Road Chowrasta High School - Subject Management & Class Mapping
 */

$pageTitle = 'Subject Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');
$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");

// 1. Create Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subject'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $subjectCode = getString('subject_code', 'POST');
        $subjectName = getString('subject_name', 'POST');
        $subjectType = getString('subject_type', 'POST') ?: 'theory';

        if (empty($subjectCode) || empty($subjectName)) {
            setFlash('danger', 'Subject code and name are required.');
        } else {
            try {
                Database::insert(
                    "INSERT INTO subjects (subject_code, subject_name, subject_type, status) VALUES (?, ?, ?, 'active')",
                    [$subjectCode, $subjectName, $subjectType]
                );
                logActivity('subject_create', "Created subject: {$subjectName} ({$subjectCode})");
                setFlash('success', "Subject {$subjectName} created successfully.");
            } catch (Exception $e) {
                setFlash('danger', 'Subject code might already exist or DB error: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/subjects/index.php'));
}

// 2. Map Subject to Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['map_subject'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $classId = getInt('class_id', 'POST');
        $subjectId = getInt('subject_id', 'POST');
        $fullMarks = (float)($_POST['full_marks'] ?? 100.00);
        $passMarks = (float)($_POST['pass_marks'] ?? 33.00);
        $isOptional = isset($_POST['is_optional']) ? 1 : 0;

        if ($classId <= 0 || $subjectId <= 0) {
            setFlash('danger', 'Class and Subject selections are required.');
        } else {
            try {
                Database::insert(
                    "INSERT INTO class_subjects (class_id, subject_id, full_marks, pass_marks, is_optional) 
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE full_marks = VALUES(full_marks), pass_marks = VALUES(pass_marks), is_optional = VALUES(is_optional)",
                    [$classId, $subjectId, $fullMarks, $passMarks, $isOptional]
                );
                logActivity('subject_mapped', "Mapped subject ID {$subjectId} to class ID {$classId}");
                setFlash('success', 'Class subject mapped successfully.');
            } catch (Exception $e) {
                setFlash('danger', 'Error mapping subject: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/subjects/index.php'));
}

// 3. Remove Mapping
if ($action === 'unmap') {
    $mapId = getInt('id');
    if ($mapId > 0 && verifyCSRFToken()) {
        Database::execute("DELETE FROM class_subjects WHERE id = ?", [$mapId]);
        setFlash('success', 'Subject unmapped from class.');
    }
    redirect(url('admin/subjects/index.php'));
}

$allSubjects = Database::fetchAll("SELECT * FROM subjects WHERE status = 'active' ORDER BY subject_code ASC");

$selectedClassId = getInt('class_id') ?: ($classes[0]['id'] ?? 1);
$mappedSubjects = Database::fetchAll(
    "SELECT cs.*, s.subject_name, s.subject_code, s.subject_type, c.class_name 
     FROM class_subjects cs 
     JOIN subjects s ON cs.subject_id = s.id 
     JOIN classes c ON cs.class_id = c.id 
     WHERE cs.class_id = ? 
     ORDER BY s.subject_code ASC",
    [$selectedClassId]
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Subject & Curriculum Management</h4>
        <p class="text-muted small mb-0">Define academic subjects and configure class-specific marks criteria.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#newSubjectModal">
            <i class="fas fa-plus me-1"></i> Add Master Subject
        </button>
        <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#mapSubjectModal">
            <i class="fas fa-link me-1"></i> Assign Subject to Class
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Class Filter Navigation -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="fw-bold small text-muted me-2"><i class="fas fa-filter me-1"></i> Class Syllabus:</span>
                <?php foreach ($classes as $c): ?>
                    <a href="<?= url('admin/subjects/index.php?class_id=' . $c['id']) ?>" 
                       class="btn btn-sm rounded-pill px-3 <?= $selectedClassId == $c['id'] ? 'btn-success text-white' : 'btn-outline-secondary' ?>">
                        <?= e($c['class_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Mapped Subjects for Selected Class -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-5">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-book-reader text-success me-2"></i> Prescribed Subjects for Selected Class (<?= count($mappedSubjects) ?>)
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Type</th>
                    <th>Full Marks</th>
                    <th>Pass Marks</th>
                    <th>Optional</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($mappedSubjects)): ?>
                    <?php foreach ($mappedSubjects as $ms): ?>
                        <tr>
                            <td class="fw-bold text-dark"><?= e($ms['subject_code']) ?></td>
                            <td><?= e($ms['subject_name']) ?></td>
                            <td><span class="badge bg-light text-secondary border text-capitalize"><?= e($ms['subject_type']) ?></span></td>
                            <td class="fw-bold text-success"><?= (int)$ms['full_marks'] ?></td>
                            <td><?= (int)$ms['pass_marks'] ?></td>
                            <td>
                                <?php if ($ms['is_optional']): ?>
                                    <span class="badge bg-warning text-dark">Optional Subject</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Compulsory</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('admin/subjects/index.php?action=unmap&id=' . $ms['id'] . '&class_id=' . $selectedClassId . '&csrf_token=' . generateCSRFToken()) ?>" 
                                   class="btn btn-sm btn-outline-danger rounded-circle btn-confirm-delete" 
                                   data-confirm="Unmap subject <?= e($ms['subject_name']) ?> from this class?" 
                                   title="Remove from Class">
                                    <i class="fas fa-times"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No subjects assigned to this class yet. Use "Assign Subject to Class" button above.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== NEW SUBJECT MODAL ==================== -->
<div class="modal fade" id="newSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold">Add Master Subject</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/subjects/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="save_subject" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" class="form-control" placeholder="e.g. 109" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject Title <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g. Higher Mathematics" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject Type</label>
                        <select name="subject_type" class="form-select">
                            <option value="theory">Theory Only</option>
                            <option value="both">Theory + Practical</option>
                            <option value="practical">Practical Only</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Create Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MAP SUBJECT MODAL ==================== -->
<div class="modal fade" id="mapSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold">Map Subject to Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/subjects/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="map_subject" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Master Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($allSubjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= e($sub['subject_code'] . ' - ' . $sub['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Full Marks</label>
                            <input type="number" name="full_marks" class="form-control" value="100" min="10" max="200" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Pass Marks</label>
                            <input type="number" name="pass_marks" class="form-control" value="33" min="1" max="100" required>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_optional" id="isOptCheck">
                        <label class="form-check-label small" for="isOptCheck">Mark as Optional Subject (4th Subject)</label>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Mapping</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
