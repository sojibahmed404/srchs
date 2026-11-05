<?php
/**
 * Sirajganj Road Chowrasta High School - Class Routine Management
 */

$pageTitle = 'Class Routine Management';
require_once __DIR__ . '/../includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$selectedClassId = getInt('class_id') ?: ($classes[0]['id'] ?? 1);

$availSections = Database::fetchAll("SELECT * FROM sections WHERE class_id = ? AND status = 'active' ORDER BY section_name ASC", [$selectedClassId]);
$selectedSectionId = getInt('section_id') ?: ($availSections[0]['id'] ?? 0);

$daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
$periods = Database::fetchAll("SELECT * FROM periods ORDER BY order_num ASC");
$subjects = Database::fetchAll("SELECT * FROM subjects WHERE status = 'active' ORDER BY subject_name ASC");
$teachers = Database::fetchAll("SELECT t.*, u.full_name FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY t.order_priority ASC");

// 1. Save or Update Routine Slot
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $postClassId = getInt('class_id', 'POST');
        $postSectionId = getInt('section_id', 'POST');
        $dayOfWeek = getString('day_of_week', 'POST');
        $periodId = getInt('period_id', 'POST');
        $subjectId = getInt('subject_id', 'POST');
        $teacherId = getInt('teacher_id', 'POST');
        $roomNo = getString('room_no', 'POST');

        if ($periodId <= 0 || !in_array($dayOfWeek, $daysOfWeek)) {
            setFlash('danger', 'Valid period and weekday are required.');
        } else {
            try {
                // Upsert routine slot
                Database::insert(
                    "INSERT INTO routines (session_id, class_id, section_id, period_id, subject_id, teacher_id, day_of_week, room_no) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE subject_id = VALUES(subject_id), teacher_id = VALUES(teacher_id), room_no = VALUES(room_no)",
                    [$sessionId, $postClassId, $postSectionId, $periodId, $subjectId ?: null, $teacherId ?: null, $dayOfWeek, $roomNo ?: null]
                );
                logActivity('routine_slot_saved', "Saved routine slot for Class {$postClassId}, Sec {$postSectionId}, {$dayOfWeek}, Period {$periodId}");
                setFlash('success', 'Class routine slot updated successfully.');
            } catch (Exception $e) {
                setFlash('danger', 'Error updating routine slot: ' . $e->getMessage());
            }
        }
    }
    redirect(url("admin/routines/index.php?class_id={$selectedClassId}&section_id={$selectedSectionId}"));
}

// 2. Clear Routine Slot
if (getString('action') === 'delete_slot') {
    $slotId = getInt('id');
    if ($slotId > 0 && verifyCSRFToken()) {
        Database::execute("DELETE FROM routines WHERE id = ?", [$slotId]);
        setFlash('success', 'Routine period slot cleared.');
    }
    redirect(url("admin/routines/index.php?class_id={$selectedClassId}&section_id={$selectedSectionId}"));
}

// 3. Fetch Existing Routine Grid for Selected Class & Section
$existingRoutines = [];
if ($selectedClassId > 0 && $selectedSectionId > 0) {
    $rows = Database::fetchAll(
        "SELECT r.*, s.subject_name, s.subject_code, u.full_name as teacher_name 
         FROM routines r 
         LEFT JOIN subjects s ON r.subject_id = s.id 
         LEFT JOIN teachers t ON r.teacher_id = t.id 
         LEFT JOIN users u ON t.user_id = u.id 
         WHERE r.session_id = ? AND r.class_id = ? AND r.section_id = ?",
        [$sessionId, $selectedClassId, $selectedSectionId]
    );
    foreach ($rows as $r) {
        $existingRoutines[$r['day_of_week']][$r['period_id']] = $r;
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Class Routine Management</h4>
        <p class="text-muted small mb-0">Construct weekly timetable, allocate periods, rooms, and instructor slots.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-print me-1"></i> Print Routine
        </button>
    </div>
</div>

<!-- Class / Section Selector Filter -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form action="<?= url('admin/routines/index.php') ?>" method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Class</label>
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $selectedClassId == $c['id'] ? 'selected' : '' ?>><?= e($c['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Section</label>
            <select name="section_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($availSections as $sec): ?>
                    <option value="<?= $sec['id'] ?>" <?= $selectedSectionId == $sec['id'] ? 'selected' : '' ?>><?= e($sec['section_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100 rounded-3">Load Routine</button>
        </div>
    </form>
</div>

<!-- Timetable Routine Matrix -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="fw-bold text-dark mb-0">
            <i class="fas fa-calendar-week text-success me-2"></i> Weekly Schedule Matrix
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle mb-0">
            <thead style="background-color: #123B2A; color: white;">
                <tr>
                    <th style="width: 120px;">Day / Time</th>
                    <?php foreach ($periods as $p): ?>
                        <th>
                            <div class="fw-bold"><?= e($p['period_name']) ?></div>
                            <small class="text-white-50"><?= formatTime($p['start_time']) ?>-<?= formatTime($p['end_time']) ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $day): ?>
                    <tr>
                        <td class="fw-bold bg-light text-dark"><?= $day ?></td>
                        <?php foreach ($periods as $p): ?>
                            <?php if ($p['is_break']): ?>
                                <td class="bg-warning-subtle text-muted small fw-semibold">Recess</td>
                            <?php else: ?>
                                <?php $slot = $existingRoutines[$day][$p['id']] ?? null; ?>
                                <td class="p-2" style="min-width: 140px; height: 95px;">
                                    <?php if ($slot && !empty($slot['subject_name'])): ?>
                                        <div class="p-2 rounded bg-light border position-relative h-100 d-flex flex-column justify-content-between">
                                            <div class="fw-bold text-success small"><?= e($slot['subject_name']) ?></div>
                                            <div class="small text-muted text-truncate"><?= e($slot['teacher_name'] ?: 'No Teacher') ?></div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <small class="badge bg-secondary">Room <?= e($slot['room_no'] ?: '101') ?></small>
                                                <a href="<?= url("admin/routines/index.php?action=delete_slot&id={$slot['id']}&class_id={$selectedClassId}&section_id={$selectedSectionId}&csrf_token=" . generateCSRFToken()) ?>" 
                                                   class="text-danger small btn-confirm-delete" data-confirm="Remove this period class?">
                                                    <i class="fas fa-times-circle"></i>
                                                </a>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-light text-muted border-dashed w-100 h-100" 
                                                data-bs-toggle="modal" data-bs-target="#slotModal" 
                                                data-day="<?= $day ?>" data-period="<?= $p['id'] ?>" data-period-name="<?= e($p['period_name']) ?>">
                                            <i class="fas fa-plus-circle text-muted"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ==================== SLOT ASSIGNMENT MODAL ==================== -->
<div class="modal fade" id="slotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-clock text-warning me-2"></i> Assign Period Slot</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/routines/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="class_id" value="<?= $selectedClassId ?>">
                <input type="hidden" name="section_id" value="<?= $selectedSectionId ?>">
                <input type="hidden" name="day_of_week" id="modalDay">
                <input type="hidden" name="period_id" id="modalPeriod">

                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Configuring: <strong id="modalInfoLabel" class="text-dark"></strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['subject_code'] . ' - ' . $s['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Teacher</label>
                        <select name="teacher_id" class="form-select">
                            <option value="">-- Choose Teacher --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= e($t['full_name'] . ' (' . $t['designation'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Room Number</label>
                        <input type="text" name="room_no" class="form-control" value="Room 102" placeholder="e.g. Room 201">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Save Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const slotModal = document.getElementById('slotModal');
    if (slotModal) {
        slotModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const day = button.getAttribute('data-day');
            const periodId = button.getAttribute('data-period');
            const periodName = button.getAttribute('data-period-name');

            document.getElementById('modalDay').value = day;
            document.getElementById('modalPeriod').value = periodId;
            document.getElementById('modalInfoLabel').textContent = day + ' — ' + periodName;
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
