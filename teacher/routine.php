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

$db = Database::getInstance();
$activeSessionId = (int)getSetting('active_session_id', 1);
$currentTeacher = getCurrentTeacher();
$teacherId = (int)$currentTeacher['id'];

// Days of the week (Bangladeshi school calendar: Sunday to Thursday)
$daysOfWeek = [
    'sunday'    => 'Sunday',
    'monday'    => 'Monday',
    'tuesday'   => 'Tuesday',
    'wednesday' => 'Wednesday',
    'thursday'  => 'Thursday'
];

// Fetch all standard periods
$periods = $db->fetchAll("SELECT * FROM periods ORDER BY period_no ASC");

// Fetch all routine entries for this teacher across all classes and days
$routineEntries = $db->fetchAll(
    "SELECT r.*, 
            c.name AS class_name, c.numeric_name,
            s.name AS section_name,
            sub.name AS subject_name, sub.code AS subject_code
     FROM routines r
     JOIN classes c ON r.class_id = c.id
     JOIN sections s ON r.section_id = s.id
     JOIN subjects sub ON r.subject_id = sub.id
     WHERE r.teacher_id = ? AND r.session_id = ?
     ORDER BY r.day_of_week, r.period_id",
    [$teacherId, $activeSessionId]
);

// Map routine entries by [day][period_id]
$routineGrid = [];
$totalWeeklyPeriods = 0;
$dayPeriodsCount = ['sunday' => 0, 'monday' => 0, 'tuesday' => 0, 'wednesday' => 0, 'thursday' => 0];

foreach ($routineEntries as $entry) {
    $day = strtolower($entry['day_of_week']);
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-calendar-days text-primary me-2"></i>Weekly Teaching Routine</h4>
        <p class="text-muted small mb-0">Personalized timetable for Academic Session <?= e(getSetting('current_session', '2026')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" onclick="window.print();">
            <i class="fa-solid fa-print me-1"></i> Print Timetable
        </button>
    </div>
</div>

<?php displayFlash(); ?>

<!-- Routine Quick Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-muted fw-semibold">Total Weekly Periods</div>
                    <div class="fs-4 fw-bold text-dark"><?= $totalWeeklyPeriods ?></div>
                </div>
                <div class="fs-2 text-primary opacity-50">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-success border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-muted fw-semibold">Teaching Days</div>
                    <div class="fs-4 fw-bold text-dark">5 Days / Week</div>
                </div>
                <div class="fs-2 text-success opacity-50">
                    <i class="fa-solid fa-calendar-week"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-warning border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-muted fw-semibold">Avg. Load Per Day</div>
                    <div class="fs-4 fw-bold text-dark"><?= round($totalWeeklyPeriods / 5, 1) ?> Periods</div>
                </div>
                <div class="fs-2 text-warning opacity-50">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 bg-white border-start border-info border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-muted fw-semibold">Daily Assembly</div>
                    <div class="fs-4 fw-bold text-dark">09:30 AM</div>
                </div>
                <div class="fs-2 text-info opacity-50">
                    <i class="fa-solid fa-bell"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Routine Matrix Card -->
<div class="card border-0 shadow-sm mb-4 print-area">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-bold text-dark">
                <i class="fa-solid fa-table text-primary me-2"></i>
                Master Timetable Matrix — <?= e($currentTeacher['name']) ?> (<?= e($currentTeacher['designation']) ?>)
            </h6>
        </div>
        <span class="badge bg-light text-dark border">
            Official Schedule
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle text-center mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 120px;" class="text-uppercase small fw-bold">Day</th>
                    <?php foreach ($periods as $p): ?>
                        <th style="min-width: 140px;">
                            <div class="fw-bold"><?= e($p['name']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <?= date('h:i A', strtotime($p['start_time'])) ?> - <?= date('h:i A', strtotime($p['end_time'])) ?>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $dayKey => $dayLabel): 
                    $isToday = (strtolower(date('l')) === $dayKey);
                ?>
                    <tr class="<?= $isToday ? 'table-warning-subtle' : '' ?>">
                        <td class="fw-bold bg-light text-start ps-3">
                            <div><?= $dayLabel ?></div>
                            <?php if ($isToday): ?>
                                <span class="badge bg-warning text-dark" style="font-size: 0.65rem;">TODAY</span>
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
                                <td class="bg-secondary-subtle text-muted py-3" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-mug-hot d-block mb-1"></i>
                                    <span class="fw-bold">TIFFIN BREAK</span>
                                </td>
                            <?php elseif (isset($routineGrid[$dayKey][$pId])): 
                                $slot = $routineGrid[$dayKey][$pId];
                            ?>
                                <td class="p-2 bg-success-subtle border border-success-subtle">
                                    <div class="fw-bold text-success-emphasis">
                                        <?= e($slot['subject_name']) ?>
                                    </div>
                                    <div class="small text-dark fw-semibold">
                                        <?= e($slot['class_name']) ?> (<?= e($slot['section_name']) ?>)
                                    </div>
                                    <?php if (!empty($slot['room_no'])): ?>
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            <i class="fa-solid fa-door-open me-1"></i>Room <?= e($slot['room_no']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <td class="text-muted py-3" style="font-size: 0.8rem; background-color: #fafafa;">
                                    <span class="opacity-50">— Free —</span>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    .sidebar, .topbar, footer, .btn, .d-flex.gap-2 {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .print-area {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
