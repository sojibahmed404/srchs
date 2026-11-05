<?php
/**
 * Sirajganj Road Chowrasta High School - Class & Section Management
 */

$pageTitle = 'Class & Section Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');
$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

// 1. Handle Class Creation / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_class'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $classId = getInt('class_id', 'POST');
        $className = getString('class_name', 'POST');
        $numericValue = getInt('numeric_value', 'POST');
        $description = getString('description', 'POST');

        if (empty($className) || $numericValue <= 0) {
            setFlash('danger', 'Class name and numeric value (e.g. 6) are required.');
        } else {
            try {
                if ($classId > 0) {
                    Database::execute(
                        "UPDATE classes SET class_name = ?, numeric_value = ?, description = ? WHERE id = ?",
                        [$className, $numericValue, $description, $classId]
                    );
                    logActivity('class_update', "Updated class: {$className}");
                    setFlash('success', 'Class updated successfully.');
                } else {
                    Database::insert(
                        "INSERT INTO classes (class_name, numeric_value, description, status) VALUES (?, ?, ?, 'active')",
                        [$className, $numericValue, $description]
                    );
                    logActivity('class_create', "Created new class: {$className}");
                    setFlash('success', 'New class created successfully.');
                }
            } catch (Exception $e) {
                setFlash('danger', 'Error saving class: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/classes/index.php'));
}

// 2. Handle Section Creation / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_section'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF validation failed.');
    } else {
        $sectionId = getInt('section_id', 'POST');
        $classId = getInt('class_id', 'POST');
        $sectionName = getString('section_name', 'POST');
        $capacity = getInt('capacity', 'POST');

        if ($classId <= 0 || empty($sectionName)) {
            setFlash('danger', 'Target class and section name are required.');
        } else {
            try {
                if ($sectionId > 0) {
                    Database::execute(
                        "UPDATE sections SET class_id = ?, section_name = ?, capacity = ? WHERE id = ?",
                        [$classId, $sectionName, $capacity ?: 60, $sectionId]
                    );
                    logActivity('section_update', "Updated section {$sectionName}");
                    setFlash('success', 'Section updated successfully.');
                } else {
                    Database::insert(
                        "INSERT INTO sections (class_id, section_name, capacity, status) VALUES (?, ?, ?, 'active')",
                        [$classId, $sectionName, $capacity ?: 60]
                    );
                    logActivity('section_create', "Added new section {$sectionName}");
                    setFlash('success', 'Section added successfully.');
                }
            } catch (Exception $e) {
                setFlash('danger', 'Error saving section: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/classes/index.php'));
}

// 3. Delete Section
if ($action === 'delete_section') {
    $secId = getInt('id');
    if ($secId > 0 && verifyCSRFToken()) {
        try {
            Database::execute("DELETE FROM sections WHERE id = ?", [$secId]);
            setFlash('success', 'Section removed.');
        } catch (Exception $e) {
            setFlash('danger', 'Cannot delete section with existing students or routines.');
        }
    }
    redirect(url('admin/classes/index.php'));
}

$classesList = Database::fetchAll("SELECT * FROM classes ORDER BY numeric_value ASC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Class & Section Management</h4>
        <p class="text-muted small mb-0">Configure classroom levels, sections, and seating quotas.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/classes/sessions.php') ?>" class="btn btn-outline-primary rounded-pill px-3">
            <i class="fas fa-calendar-alt me-1"></i> Academic Sessions
        </a>
        <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addClassModal">
            <i class="fas fa-plus me-1"></i> Add Class
        </button>
        <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="fas fa-plus me-1"></i> Add Section
        </button>
    </div>
</div>

<!-- Class Cards -->
<div class="row g-4">
    <?php if (!empty($classesList)): ?>
        <?php foreach ($classesList as $c): ?>
            <?php
            $sections = Database::fetchAll("SELECT * FROM sections WHERE class_id = ? ORDER BY section_name ASC", [$c['id']]);
            $studentCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM enrollments WHERE class_id = ? AND session_id = ?", [$c['id'], $sessionId]);
            $subjectCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM class_subjects WHERE class_id = ?", [$c['id']]);
            ?>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-top: 5px solid #123B2A !important;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="fw-bold text-dark mb-0"><?= e($c['class_name']) ?></h4>
                            <small class="text-success fw-semibold">Grade <?= e((string)$c['numeric_value']) ?></small>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                <?= $studentCount ?> Students
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                                <?= $subjectCount ?> Subjects
                            </span>
                        </div>
                    </div>

                    <p class="text-muted small mb-3"><?= e($c['description'] ?: 'Secondary education grade') ?></p>

                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-door-open text-warning me-2"></i> Sections in <?= e($c['class_name']) ?>:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Section Name</th>
                                    <th>Capacity</th>
                                    <th>Enrolled (<?= date('Y') ?>)</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sections)): ?>
                                    <?php foreach ($sections as $s): ?>
                                        <?php $secEnrolled = (int)Database::fetchColumn("SELECT COUNT(*) FROM enrollments WHERE section_id = ? AND session_id = ?", [$s['id'], $sessionId]); ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= e($s['section_name']) ?></td>
                                            <td><?= (int)$s['capacity'] ?> Students</td>
                                            <td>
                                                <div class="progress" style="height: 14px;">
                                                    <?php $pct = $s['capacity'] > 0 ? min(100, round(($secEnrolled / $s['capacity']) * 100)) : 0; ?>
                                                    <div class="progress-bar bg-success" style="width: <?= $pct ?>%; font-size: 10px;"><?= $secEnrolled ?></div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= url('admin/classes/index.php?action=delete_section&id=' . $s['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                                   class="text-danger btn-confirm-delete" 
                                                   data-confirm="Delete section <?= e($s['section_name']) ?>?" 
                                                   title="Delete Section">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-muted text-center">No sections created yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ==================== ADD CLASS MODAL ==================== -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-warning me-2"></i> Add New Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/classes/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="save_class" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Class Name <span class="text-danger">*</span></label>
                        <input type="text" name="class_name" class="form-control" placeholder="e.g. Class 6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Numeric Value <span class="text-danger">*</span></label>
                        <input type="number" name="numeric_value" class="form-control" placeholder="e.g. 6" min="1" max="12" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Curriculum details"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Create Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== ADD SECTION MODAL ==================== -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-warning me-2"></i> Add New Section</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/classes/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="save_section" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assign to Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classesList as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Section Name <span class="text-danger">*</span></label>
                        <input type="text" name="section_name" class="form-control" placeholder="e.g. Section A or Padma" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Student Capacity</label>
                        <input type="number" name="capacity" class="form-control" value="60" min="10" max="120">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Add Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
