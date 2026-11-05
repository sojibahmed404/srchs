<?php
/**
 * Sirajganj Road Chowrasta High School - Assigned Students Directory
 */

$pageTitle = 'Assigned Students';
require_once __DIR__ . '/includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

// Fetch teacher's assigned classes
$assignedClasses = Database::fetchAll(
    "SELECT DISTINCT ta.class_id, ta.section_id, c.class_name, sec.section_name 
     FROM teacher_assignments ta 
     JOIN classes c ON ta.class_id = c.id 
     JOIN sections sec ON ta.section_id = sec.id 
     WHERE ta.teacher_id = ? AND ta.session_id = ? 
     ORDER BY c.numeric_value ASC",
    [$teacherId, $sessionId]
);

$selectedClassId = getInt('class_id') ?: ($assignedClasses[0]['class_id'] ?? 0);
$selectedSectionId = getInt('section_id') ?: ($assignedClasses[0]['section_id'] ?? 0);

$students = [];
if ($selectedClassId > 0 && $selectedSectionId > 0) {
    // Check teacher authorization for this class/section
    $isAuthorized = Database::fetchOne(
        "SELECT id FROM teacher_assignments WHERE teacher_id = ? AND class_id = ? AND section_id = ? AND session_id = ?",
        [$teacherId, $selectedClassId, $selectedSectionId, $sessionId]
    );

    if ($isAuthorized) {
        $students = Database::fetchAll(
            "SELECT s.*, e.roll_no, c.class_name, sec.section_name, g.father_name, g.guardian_phone 
             FROM enrollments e 
             JOIN students s ON e.student_id = s.id 
             JOIN classes c ON e.class_id = c.id 
             JOIN sections sec ON e.section_id = sec.id 
             LEFT JOIN guardians g ON s.guardian_id = g.id 
             WHERE e.session_id = ? AND e.class_id = ? AND e.section_id = ? AND s.status = 'active' 
             ORDER BY e.roll_no ASC",
            [$sessionId, $selectedClassId, $selectedSectionId]
        );
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">My Students</h4>
        <p class="text-muted small mb-0">Roster of students enrolled in your assigned sections.</p>
    </div>
</div>

<!-- Class/Section Filter -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="small fw-bold text-muted me-2"><i class="fas fa-filter text-success me-1"></i> Switch Class:</span>
        <?php foreach ($assignedClasses as $ac): ?>
            <a href="<?= url("teacher/students.php?class_id={$ac['class_id']}&section_id={$ac['section_id']}") ?>" 
               class="btn btn-sm rounded-pill px-3 <?= ($selectedClassId == $ac['class_id'] && $selectedSectionId == $ac['section_id']) ? 'btn-success text-white' : 'btn-outline-secondary' ?>">
                <?= e($ac['class_name'] . ' (' . $ac['section_name'] . ')') ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">Roll</th>
                    <th>Student Name</th>
                    <th>Admission No</th>
                    <th>Gender</th>
                    <th>Guardian Name</th>
                    <th>Guardian Contact</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td class="fw-bold"><span class="badge bg-dark rounded-pill px-3 py-1"><?= e((string)$stu['roll_no']) ?></span></td>
                            <td class="fw-bold text-dark"><?= e($stu['first_name'] . ' ' . $stu['last_name']) ?></td>
                            <td><span class="badge bg-light text-secondary border"><?= e($stu['admission_no']) ?></span></td>
                            <td><?= e($stu['gender']) ?></td>
                            <td><?= e($stu['father_name'] ?: '-') ?></td>
                            <td><i class="fas fa-phone text-muted me-1 small"></i> <?= e($stu['guardian_phone'] ?: '-') ?></td>
                            <td><span class="badge bg-success rounded-pill">Active</span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No students found or no class selected.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
