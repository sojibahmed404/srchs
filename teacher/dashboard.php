<?php
/**
 * Sirajganj Road Chowrasta High School - Teacher Dashboard
 */

$pageTitle = 'Teacher Workspace';
require_once __DIR__ . '/includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

try {
    // 1. Assigned classes & subjects
    $assignments = Database::fetchAll(
        "SELECT ta.*, c.class_name, sec.section_name, s.subject_name, s.subject_code 
         FROM teacher_assignments ta 
         JOIN classes c ON ta.class_id = c.id 
         JOIN sections sec ON ta.section_id = sec.id 
         JOIN subjects s ON ta.subject_id = s.id 
         WHERE ta.teacher_id = ? AND ta.session_id = ?",
        [$teacherId, $sessionId]
    );

    // 2. Class teacher designation
    $classTeacherRole = Database::fetchOne(
        "SELECT c.class_name, sec.section_name 
         FROM teacher_assignments ta 
         JOIN classes c ON ta.class_id = c.id 
         JOIN sections sec ON ta.section_id = sec.id 
         WHERE ta.teacher_id = ? AND ta.session_id = ? AND ta.is_class_teacher = 1 
         LIMIT 1",
        [$teacherId, $sessionId]
    );

    // 3. Today's Routine Periods
    $currentDay = date('l'); // e.g. Sunday
    $todayRoutine = Database::fetchAll(
        "SELECT r.*, c.class_name, sec.section_name, s.subject_name, p.period_name, p.start_time, p.end_time 
         FROM routines r 
         JOIN classes c ON r.class_id = c.id 
         JOIN sections sec ON r.section_id = sec.id 
         JOIN subjects s ON r.subject_id = s.id 
         JOIN periods p ON r.period_id = p.id 
         WHERE r.teacher_id = ? AND r.session_id = ? AND r.day_of_week = ? 
         ORDER BY p.order_num ASC",
        [$teacherId, $sessionId, $currentDay]
    );

    // 4. Assignments Count
    $materialsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM assignments WHERE teacher_id = ? AND session_id = ?", [$teacherId, $sessionId]);

    // 5. General published notices
    $recentNotices = Database::fetchAll("SELECT * FROM notices WHERE status = 'published' ORDER BY is_pinned DESC, published_date DESC LIMIT 3");

} catch (Exception $e) {
    $assignments = $todayRoutine = $recentNotices = [];
    $classTeacherRole = null;
    $materialsCount = 0;
}
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm rounded-4 p-4 text-white mb-4" style="background: linear-gradient(135deg, #123B2A 0%, #198754 100%);">
    <div class="row align-items-center">
        <div class="col-md-8">
            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-semibold mb-2">Faculty Portal</span>
            <h3 class="fw-bold mb-1" style="font-family: 'Hind Siliguri', sans-serif;">
                Welcome, <?= e($currentUser['full_name']) ?>!
            </h3>
            <p class="text-white-50 mb-0 small">
                <?= e($teacherRecord['designation'] ?? 'Instructor') ?> | <?= e($teacherRecord['specialization'] ?: 'General Subject') ?> | Code: <?= e($teacherRecord['teacher_code'] ?? 'TCH') ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <span class="badge bg-white text-dark px-3 py-2 rounded-pill shadow-sm">
                <i class="far fa-calendar-alt text-success me-1"></i> <?= date('l, d F Y') ?>
            </span>
        </div>
    </div>
</div>

<!-- Quick Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-left: 5px solid #123B2A !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-semibold text-uppercase">Assigned Classes</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= count($assignments) ?></h3>
                    <small class="text-success">Active Timetable Slots</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background-color: #123B2A;">
                    <i class="fas fa-layer-group fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-left: 5px solid #198754 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-semibold text-uppercase">Class Teacher Role</small>
                    <h6 class="fw-bold text-dark mt-2 mb-0">
                        <?= $classTeacherRole ? e($classTeacherRole['class_name'] . ' (' . $classTeacherRole['section_name'] . ')') : 'Subject Teacher' ?>
                    </h6>
                    <small class="text-muted"><?= $classTeacherRole ? 'Assigned' : 'None' ?></small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background-color: #198754;">
                    <i class="fas fa-crown fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-left: 5px solid #D9AD55 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-semibold text-uppercase">Today's Lectures</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= count($todayRoutine) ?></h3>
                    <small class="text-warning text-dark"><?= date('l') ?> Schedule</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background-color: #D9AD55;">
                    <i class="fas fa-clock fs-5 text-dark"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100" style="border-left: 5px solid #0d6efd !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-semibold text-uppercase">Study Materials</small>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= $materialsCount ?></h3>
                    <small class="text-primary">Uploaded Files</small>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background-color: #0d6efd;">
                    <i class="fas fa-tasks fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Shortcuts -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <div class="fw-bold text-dark">
            <i class="fas fa-bolt text-warning me-2"></i> Teacher Actions:
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('teacher/attendance.php') ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                <i class="fas fa-clipboard-check me-1"></i> Take Attendance
            </a>
            <a href="<?= url('teacher/marks-entry.php') ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="fas fa-calculator me-1"></i> Enter Exam Marks
            </a>
            <a href="<?= url('teacher/assignments.php') ?>" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3">
                <i class="fas fa-upload me-1"></i> Upload Study Material
            </a>
            <a href="<?= url('teacher/routine.php') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="fas fa-calendar-week me-1"></i> View Full Routine
            </a>
        </div>
    </div>
</div>

<!-- Main Row: Today's Schedule & My Classes -->
<div class="row g-4 mb-4">
    <!-- Today's Routine Periods -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h6 class="fw-bold text-dark mb-3">
                <i class="fas fa-calendar-day text-success me-2"></i> Today's Class Routine (<?= date('l') ?>)
            </h6>

            <?php if (!empty($todayRoutine)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th>Timing</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayRoutine as $tr): ?>
                                <tr>
                                    <td class="fw-bold text-success"><?= e($tr['period_name']) ?></td>
                                    <td><?= formatTime($tr['start_time']) ?>-<?= formatTime($tr['end_time']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($tr['class_name'] . ' - ' . $tr['section_name']) ?></span></td>
                                    <td class="fw-semibold"><?= e($tr['subject_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= e($tr['room_no'] ?: '101') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-coffee fs-3 d-block mb-2 text-warning"></i>
                    No classes scheduled for today on the timetable.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Assigned Classes -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-book-reader text-primary me-2"></i> My Assigned Courses
                </h6>
                <a href="<?= url('teacher/my-classes.php') ?>" class="small text-success fw-semibold text-decoration-none">Manage &rarr;</a>
            </div>

            <?php if (!empty($assignments)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Class & Section</th>
                                <th>Assigned Subject</th>
                                <th>Role</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $as): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= e($as['class_name'] . ' (' . $as['section_name'] . ')') ?></td>
                                    <td>
                                        <span class="text-primary fw-semibold"><?= e($as['subject_name']) ?></span>
                                        <small class="text-muted">(<?= e($as['subject_code']) ?>)</small>
                                    </td>
                                    <td>
                                        <?php if ($as['is_class_teacher']): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-crown me-1"></i> Class Teacher</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Subject Teacher</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= url("teacher/attendance.php?class_id={$as['class_id']}&section_id={$as['section_id']}") ?>" class="btn btn-sm btn-outline-success rounded-pill px-2" title="Attendance">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="<?= url("teacher/marks-entry.php?class_id={$as['class_id']}&section_id={$as['section_id']}&subject_id={$as['subject_id']}") ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2" title="Marks">
                                            <i class="fas fa-calculator"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4 text-muted">
                    No classes assigned yet by the administrator.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Recent Notices & Announcements -->
    <?php if (!empty($recentNotices)): ?>
    <div class="col-12 mt-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h6 class="fw-bold text-dark mb-3">
                <i class="fas fa-bullhorn text-warning me-2"></i> Recent School Notices & Circulars
            </h6>
            <div class="row g-3">
                <?php foreach ($recentNotices as $n): ?>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-light border-start border-4 border-success">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-success-subtle text-success"><?= e($n['category']) ?></span>
                                <small class="text-muted"><?= formatDate($n['published_date'], 'd M Y') ?></small>
                            </div>
                            <div class="fw-bold text-dark mb-1"><?= e($n['title']) ?></div>
                            <p class="text-muted small mb-0"><?= truncate(e($n['content']), 110) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
