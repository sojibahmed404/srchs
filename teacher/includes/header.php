<?php
/**
 * Teacher Panel Header & Layout Component
 * Sirajganj Road Chowrasta High School
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
requireTeacher();

$currentUser = getCurrentUser();
$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');
$currentSession = getCurrentSession();
$sessionId = $currentSession ? (int)$currentSession['id'] : 1;

// Fetch teacher profile
$teacherRecord = Database::fetchOne("SELECT * FROM teachers WHERE user_id = ?", [$currentUser['id']]);
$teacherId = $teacherRecord ? (int)$teacherRecord['id'] : 0;
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Teacher Portal - <?= e($schoolName) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        .teacher-sidebar {
            width: var(--sidebar-width);
            background-color: #123B2A;
            color: #ffffff;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1040;
            overflow-y: auto;
            transition: all 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .teacher-sidebar .sidebar-brand {
            padding: 20px;
            background-color: #0b251a;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .teacher-sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 11px 20px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .teacher-sidebar .nav-link:hover,
        .teacher-sidebar .nav-link.active {
            color: #ffffff;
            background-color: rgba(255,255,255,0.08);
            border-left-color: #D9AD55;
        }
        .teacher-sidebar .nav-link i {
            width: 24px;
            margin-right: 10px;
            font-size: 1rem;
            color: #D9AD55;
        }
        .teacher-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        .teacher-topbar {
            height: 70px;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1030;
        }
        @media (max-width: 991.98px) {
            .teacher-sidebar {
                transform: translateX(-100%);
            }
            .teacher-sidebar.active {
                transform: translateX(0);
            }
            .teacher-main {
                margin-left: 0;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ==================== TEACHER SIDEBAR ==================== -->
<aside class="teacher-sidebar" id="dashboardSidebar">
    <div class="sidebar-brand d-flex align-items-center">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white me-2 flex-shrink-0" style="width: 38px; height: 38px; background-color: #198754;">
            <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="overflow-hidden">
            <h6 class="mb-0 fw-bold text-white text-truncate" style="font-family: 'Hind Siliguri', sans-serif;">SRCHS Faculty</h6>
            <small class="text-warning small"><?= e($teacherRecord['teacher_code'] ?? 'Teacher') ?></small>
        </div>
    </div>

    <nav class="py-2">
        <a href="<?= url('teacher/dashboard.php') ?>" class="nav-link <?= isActivePage('dashboard') ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a href="<?= url('teacher/my-classes.php') ?>" class="nav-link <?= isActivePage('my-classes') ?>">
            <i class="fas fa-layer-group"></i> My Assigned Classes
        </a>
        <a href="<?= url('teacher/students.php') ?>" class="nav-link <?= isActivePage('students') ?>">
            <i class="fas fa-user-graduate"></i> My Students
        </a>
        <a href="<?= url('teacher/attendance.php') ?>" class="nav-link <?= isActivePage('attendance') ?>">
            <i class="fas fa-clipboard-check"></i> Daily Attendance
        </a>
        <a href="<?= url('teacher/marks-entry.php') ?>" class="nav-link <?= isActivePage('marks-entry') ?>">
            <i class="fas fa-calculator"></i> Exam Marks Entry
        </a>
        <a href="<?= url('teacher/assignments.php') ?>" class="nav-link <?= isActivePage('assignments') ?>">
            <i class="fas fa-tasks"></i> Upload Assignments
        </a>
        <a href="<?= url('teacher/routine.php') ?>" class="nav-link <?= isActivePage('routine') ?>">
            <i class="fas fa-calendar-alt"></i> Class Routine
        </a>
        <a href="<?= url('teacher/profile.php') ?>" class="nav-link <?= isActivePage('profile') ?>">
            <i class="fas fa-user-cog"></i> My Profile
        </a>
        <?php if (isHeadmaster()): ?>
            <a href="<?= url('headmaster/dashboard.php') ?>" class="nav-link text-warning">
                <i class="fas fa-crown text-warning"></i> Head Sir Monitoring
            </a>
        <?php endif; ?>

        <div class="px-3 pt-3 mt-4 border-top border-secondary">
            <a href="<?= url('index.php') ?>" target="_blank" class="btn btn-outline-light btn-sm w-100 rounded-pill mb-2">
                <i class="fas fa-globe me-1"></i> Public Website
            </a>
            <a href="<?= url('auth/logout.php') ?>" class="btn btn-danger btn-sm w-100 rounded-pill">
                <i class="fas fa-sign-out-alt me-1"></i> Sign Out
            </a>
        </div>
    </nav>
</aside>

<!-- ==================== MAIN WRAPPER ==================== -->
<div class="teacher-main">
    <!-- Topbar -->
    <header class="teacher-topbar d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-light d-lg-none" id="sidebarToggleBtn">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0 fw-bold text-dark d-none d-sm-block">
                <?= isset($pageTitle) ? e($pageTitle) : 'Teacher Dashboard' ?>
            </h5>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?= asset($currentUser['avatar'] ?: 'assets/images/default-avatar.png') ?>" 
                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($currentUser['full_name']) ?>&background=123B2A&color=fff&size=100';" 
                         alt="Avatar" class="rounded-circle me-2 border" style="width: 36px; height: 36px; object-fit: cover;">
                    <span class="fw-semibold small d-none d-md-inline"><?= e($currentUser['full_name']) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                    <li class="px-3 py-2 border-bottom">
                        <small class="text-muted d-block">Signed in as Teacher</small>
                        <strong class="text-dark"><?= e($teacherRecord['designation'] ?? 'Teacher') ?></strong>
                    </li>
                    <li><a class="dropdown-item py-2" href="<?= url('teacher/profile.php') ?>"><i class="fas fa-user-circle me-2 text-muted"></i> My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= url('auth/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i> Log Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content Container -->
    <main class="flex-grow-1 p-4">
        <?= displayFlash() ?>
