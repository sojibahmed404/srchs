<?php
/**
 * Sirajganj Road Chowrasta High School - Academic Session Management
 */

$pageTitle = 'Academic Session Management';
require_once __DIR__ . '/../includes/header.php';

$action = getString('action');

// Set as current session
if ($action === 'set_current') {
    $targetId = getInt('id');
    if ($targetId > 0 && verifyCSRFToken()) {
        try {
            Database::beginTransaction();
            Database::execute("UPDATE academic_sessions SET is_current = 0");
            Database::execute("UPDATE academic_sessions SET is_current = 1, status = 'active' WHERE id = ?", [$targetId]);
            Database::execute("UPDATE settings SET setting_value = ? WHERE setting_key = 'active_session_id'", [$targetId]);
            Database::commit();

            $sess = Database::fetchOne("SELECT session_name FROM academic_sessions WHERE id = ?", [$targetId]);
            logActivity('session_current', "Set active academic session to: " . ($sess['session_name'] ?? $targetId));
            setFlash('success', "Active academic session updated to {$sess['session_name']}.");
        } catch (Exception $e) {
            Database::rollback();
            setFlash('danger', 'Error updating active session: ' . $e->getMessage());
        }
    }
    redirect(url('admin/classes/sessions.php'));
}

// Create new session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $sessionName = getString('session_name', 'POST');
        $startDate = getString('start_date', 'POST');
        $endDate = getString('end_date', 'POST');
        $isCurrent = isset($_POST['is_current']) ? 1 : 0;

        if (empty($sessionName) || empty($startDate) || empty($endDate)) {
            setFlash('danger', 'Please provide session name and date ranges.');
        } else {
            try {
                Database::beginTransaction();
                if ($isCurrent) {
                    Database::execute("UPDATE academic_sessions SET is_current = 0");
                }

                $newId = Database::insert(
                    "INSERT INTO academic_sessions (session_name, start_date, end_date, is_current, status) VALUES (?, ?, ?, ?, 'active')",
                    [$sessionName, $startDate, $endDate, $isCurrent]
                );

                if ($isCurrent) {
                    Database::execute("UPDATE settings SET setting_value = ? WHERE setting_key = 'active_session_id'", [$newId]);
                }

                Database::commit();
                logActivity('session_create', "Created academic session: {$sessionName}");
                setFlash('success', "Academic session {$sessionName} created successfully.");
            } catch (Exception $e) {
                Database::rollback();
                setFlash('danger', 'Error creating session: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/classes/sessions.php'));
}

$sessionsList = Database::fetchAll("SELECT * FROM academic_sessions ORDER BY start_date DESC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Academic Session Management</h4>
        <p class="text-muted small mb-0">Manage school enrollment years and switch the system active calendar.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/classes/index.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
            &larr; Classes & Sections
        </a>
        <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSessionModal">
            <i class="fas fa-plus me-1"></i> Add Academic Session
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Session Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Enrolled Students</th>
                    <th>Status</th>
                    <th>Active Session</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sessionsList)): ?>
                    <?php foreach ($sessionsList as $s): ?>
                        <?php $sessionStudentCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM enrollments WHERE session_id = ?", [$s['id']]); ?>
                        <tr>
                            <td class="fw-bold text-dark fs-6"><?= e($s['session_name']) ?></td>
                            <td><?= formatDate($s['start_date']) ?></td>
                            <td><?= formatDate($s['end_date']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $sessionStudentCount ?> Students</span></td>
                            <td><span class="badge <?= $s['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> text-capitalize"><?= e($s['status']) ?></span></td>
                            <td>
                                <?php if ($s['is_current']): ?>
                                    <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i> CURRENT ACTIVE</span>
                                <?php else: ?>
                                    <span class="text-muted small">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if (!$s['is_current']): ?>
                                    <a href="<?= url('admin/classes/sessions.php?action=set_current&id=' . $s['id'] . '&csrf_token=' . generateCSRFToken()) ?>" 
                                       class="btn btn-sm btn-outline-success rounded-pill px-3" 
                                       title="Activate this Session">
                                        Make Active
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-light disabled rounded-pill px-3">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== ADD SESSION MODAL ==================== -->
<div class="modal fade" id="addSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-calendar-plus text-warning me-2"></i> Add Academic Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/classes/sessions.php') ?>" method="POST">
                <?= csrfField() ?>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Session Name <span class="text-danger">*</span></label>
                        <input type="text" name="session_name" class="form-control" placeholder="e.g. 2027 or 2026-2027" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y') ?>-01-01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="<?= date('Y') ?>-12-31" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_current" id="isCurrentCheck">
                        <label class="form-check-label small" for="isCurrentCheck">Set as Current Active Session immediately</label>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Save Session</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
