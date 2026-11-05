<?php
/**
 * Teacher Weekly Class Routine Module
 * Sirajganj Road Chowrasta High School
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireTeacher();

$currentSession = getCurrentSession();
$activeSessionId = $currentSession ? (int)$currentSession['id'] : 1;
$currentTeacher = getCurrentTeacher();
$teacherId = $currentTeacher ? (int)$currentTeacher['id'] : 0;

// Days of the week (Bangladeshi school calendar: Sunday to Thursday)
$daysOfWeek = [
    'Sunday'    => 'Sunday',
    'Monday'    => 'Monday',
    'Tuesday'   => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday'  => 'Thursday'
];

// Fetch all standard periods
$periods = Database::fetchAll("SELECT * FROM periods ORDER BY order_num ASC");

// Fetch all routine entries for this teacher across all classes and days
$routineEntries = Database::fetchAll(
    "SELECT r.*, 
            c.class_name, c.numeric_value,
            sec.section_name,
            sub.subject_name, sub.subject_code
     FROM routines r
     JOIN classes c ON r.class_id = c.id
     JOIN sections sec ON r.section_id = sec.id
     LEFT JOIN subjects sub ON r.subject_id = sub.id
     WHERE r.teacher_id = ? AND r.session_id = ?
     ORDER BY r.day_of_week, r.period_id",
    [$teacherId, $activeSessionId]
);

// Map routine entries by [day][period_id]
$routineGrid = [];
$totalWeeklyPeriods = 0;
$dayPeriodsCount = ['Sunday' => 0, 'Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 'Thursday' => 0];

foreach ($routineEntries as $entry) {
    $day = $entry['day_of_week'];
    $pId = (int)$entry['period_id'];
    $routineGrid[$day][$pId] = $entry;
    $totalWeeklyPeriods++;
    if (isset($dayPeriodsCount[$day])) {
        $dayPeriodsCount[$day]++;
    }
}

$pageTitle = "My Class Routine";
include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="fas fa-calendar-alt text-success me-2"></i> My Teaching Schedule & Routine
        </h4>
        <p class="text-muted small mb-0">Weekly timetable schedule for Academic Session <?= e($currentSession['session_name'] ?? '2026') ?>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('teacher/attendance.php') ?>" class="btn btn-success rounded-pill px-3 shadow-sm">
            <i class="fas fa-clipboard-check me-1"></i> Take Today's Attendance
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-print me-1"></i> Print Timetable
        </button>
    </div>
</div>

<!-- Teacher Schedule Info Banner -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <img src="<?= resolveUploadUrl($currentTeacher['avatar'] ?? '', 'assets/images/default-avatar.png') ?>" 
                         class="rounded-circle me-3 border" style="width: 58px; height: 58px; object-fit: cover;">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark"><?= e($currentTeacher['full_name'] ?? 'Faculty Member') ?></h5>
                        <div class="text-muted small">
                            <span class="badge bg-light text-dark border me-2"><?= e($currentTeacher['designation'] ?? 'Teacher') ?></span>
                            <span>Code: <strong><?= e($currentTeacher['teacher_code'] ?? 'TCH-000') ?></strong></span>
                            <span class="mx-2">•</span>
                            <span>Specialization: <strong><?= e($currentTeacher['specialization'] ?: 'General Education') ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="p-2 bg-light rounded-3 d-inline-block text-center px-4">
                    <div class="fs-4 fw-bold text-success"><?= $totalWeeklyPeriods ?></div>
                    <div class="small text-muted text-uppercase" style="font-size: 0.75rem;">Total Periods / Week</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Routine Matrix Card -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 print-area bg-white">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="fas fa-table text-primary me-2"></i> Master Timetable Matrix
        </h6>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
            Session: <?= e($currentSession['session_name'] ?? '2026') ?>
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle text-center mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 130px;" class="text-uppercase small fw-bold">Day</th>
                    <?php foreach ($periods as $p): ?>
                        <th style="min-width: 140px;">
                            <div class="fw-bold text-dark"><?= e($p['period_name']) ?></div>
                            <div class="text-muted small" style="font-size: 0.72rem;">
                                <?= date('h:i A', strtotime($p['start_time'])) ?> - <?= date('h:i A', strtotime($p['end_time'])) ?>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $dayKey => $dayLabel): 
                    $isToday = (date('l') === $dayKey);
                ?>
                    <tr class="<?= $isToday ? 'table-warning' : '' ?>">
                        <td class="fw-bold bg-light text-start ps-3">
                            <div class="text-dark"><?= $dayLabel ?></div>
                            <?php if ($isToday): ?>
                                <span class="badge bg-warning text-dark px-2 py-0" style="font-size: 0.65rem;">TODAY</span>
                            <?php endif; ?>
                            <div class="text-muted small" style="font-size: 0.7rem;">
                                <?= $dayPeriodsCount[$dayKey] ?> classes
                            </div>
                        </td>

                        <?php foreach ($periods as $p): 
                            $pId = (int)$p['id'];
                            $isBreak = (int)$p['is_break'] === 1;
                        ?>
                            <?php if ($isBreak): ?>
                                <td class="bg-light text-muted py-3" style="font-size: 0.8rem; background-color: #f8f9fa;">
                                    <i class="fas fa-coffee d-block mb-1 text-secondary"></i>
                                    <span class="fw-bold small text-secondary">TIFFIN BREAK</span>
                                </td>
                            <?php elseif (isset($routineGrid[$dayKey][$pId])): 
                                $cell = $routineGrid[$dayKey][$pId];
                                $isFirstPeriod = ((int)$p['order_num'] === 1);
                            ?>
                                <td class="p-2" style="background-color: #f1f8f4;">
                                    <div class="p-2 rounded-3 border border-success bg-white shadow-sm position-relative">
                                        <?php if ($isFirstPeriod): ?>
                                            <span class="badge bg-danger position-absolute top-0 end-0 translate-middle-y me-1" 
                                                  style="font-size: 0.6rem;" title="Takes Daily Attendance">
                                                1st Class (Attendance)
                                            </span>
                                        <?php endif; ?>
                                        <div class="fw-bold text-success small mb-1">
                                            <?= e($cell['subject_name'] ?? 'Class Subject') ?>
                                        </div>
                                        <div class="small fw-semibold text-dark">
                                            <?= e($cell['class_name']) ?> (<?= e($cell['section_name']) ?>)
                                        </div>
                                        <?php if (!empty($cell['room_no'])): ?>
                                            <div class="text-muted" style="font-size: 0.7rem;">
                                                <i class="fas fa-door-open me-1"></i><?= e($cell['room_no']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php else: ?>
                                <td class="text-muted p-2" style="font-size: 0.8rem;">
                                    <span class="text-muted opacity-50">&mdash;</span>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
