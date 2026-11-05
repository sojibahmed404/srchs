<?php
/**
 * Admin Panel Header & Layout Component
 * Sirajganj Road Chowrasta High School
 */

require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
requireAdmin();

$currentUser = getCurrentUser();
$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');
$currentSession = getCurrentSession();
$pendingAdmissions = (int)Database::fetchColumn("SELECT COUNT(*) FROM admissions WHERE status = 'pending'");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Admin Portal - <?= e($schoolName) ?></title>

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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --sidebar-width: 270px;
        }
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        .admin-sidebar {
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
        .admin-sidebar .sidebar-brand {
            padding: 20px;
            background-color: #0b251a;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: #ffffff;
            background-color: rgba(255,255,255,0.08);
            border-left-color: #D9AD55;
        }
        .admin-sidebar .nav-link i {
            width: 24px;
            margin-right: 10px;
            font-size: 1rem;
            color: #D9AD55;
        }
        .admin-sidebar .sidebar-heading {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px 20px 5px;
            color: rgba(255,255,255,0.4);
            font-weight: 700;
        }
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        .admin-topbar {
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
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.active {
                transform: translateX(0);
            }
            .admin-main {
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

<!-- ==================== SIDEBAR ==================== -->
<aside class="admin-sidebar" id="dashboardSidebar">
    <div class="sidebar-brand d-flex align-items-center">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white me-2 flex-shrink-0" style="width: 38px; height: 38px; background-color: #198754;">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="overflow-hidden">
            <h6 class="mb-0 fw-bold text-white text-truncate" style="font-family: 'Hind Siliguri', sans-serif;">SRCHS Admin</h6>
            <small class="text-warning small">Session <?= e($currentSession['session_name'] ?? '2026') ?></small>
        </div>
    </div>

    <nav class="py-2">
        <a href="<?= url('admin/dashboard.php') ?>" class="nav-link <?= isActivePage('dashboard') ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="sidebar-heading">Academic Management</div>
        <a href="<?= url('admin/students/index.php') ?>" class="nav-link <?= isActivePage('students') ?>">
            <i class="fas fa-user-graduate"></i> Students
        </a>
        <a href="<?= url('admin/teachers/index.php') ?>" class="nav-link <?= isActivePage('teachers') ?>">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>
        <a href="<?= url('admin/classes/index.php') ?>" class="nav-link <?= isActivePage('classes') ?>">
            <i class="fas fa-layer-group"></i> Classes & Sections
        </a>
        <a href="<?= url('admin/subjects/index.php') ?>" class="nav-link <?= isActivePage('subjects') ?>">
            <i class="fas fa-book"></i> Subjects
        </a>
        <a href="<?= url('admin/teachers/assignments.php') ?>" class="nav-link">
            <i class="fas fa-tasks"></i> Teacher Assignments
        </a>
        <a href="<?= url('admin/routines/index.php') ?>" class="nav-link <?= isActivePage('routines') ?>">
            <i class="fas fa-calendar-alt"></i> Class Routine
        </a>
        <a href="<?= url('admin/attendance/index.php') ?>" class="nav-link <?= isActivePage('attendance') ?>">
            <i class="fas fa-clipboard-check"></i> Attendance
        </a>

        <div class="sidebar-heading">Exams & Results</div>
        <a href="<?= url('admin/exams/index.php') ?>" class="nav-link <?= isActivePage('exams') ?>">
            <i class="fas fa-edit"></i> Examinations
        </a>
        <a href="<?= url('admin/results/marks.php') ?>" class="nav-link">
            <i class="fas fa-calculator"></i> Marks Entry
        </a>
        <a href="<?= url('admin/results/index.php') ?>" class="nav-link <?= isActivePage('results') ?>">
            <i class="fas fa-poll"></i> Result Publication
        </a>

        <div class="sidebar-heading">Admissions & Accounts</div>
        <a href="<?= url('admin/admissions/index.php') ?>" class="nav-link d-flex justify-content-between align-items-center <?= isActivePage('admissions') ?>">
            <span><i class="fas fa-file-signature"></i> Admissions</span>
            <?php if ($pendingAdmissions > 0): ?>
                <span class="badge bg-warning text-dark rounded-pill"><?= $pendingAdmissions ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= url('admin/fees/index.php') ?>" class="nav-link <?= isActivePage('fees') ?>">
            <i class="fas fa-file-invoice-dollar"></i> Fees & Payments
        </a>
        <a href="<?= url('admin/reports/index.php') ?>" class="nav-link <?= isActivePage('reports') ?>">
            <i class="fas fa-chart-pie"></i> Reports & Export
        </a>

        <div class="sidebar-heading">Portal & Communication</div>
        <a href="<?= url('admin/notices/index.php') ?>" class="nav-link <?= isActivePage('notices') ?>">
            <i class="fas fa-bullhorn"></i> Notices
        </a>
        <a href="<?= url('admin/gallery/index.php') ?>" class="nav-link <?= isActivePage('gallery') ?>">
            <i class="fas fa-images"></i> Photo Gallery
        </a>

        <div class="sidebar-heading">System Administration</div>
        <a href="<?= url('admin/users/index.php') ?>" class="nav-link <?= isActivePage('users') ?>">
            <i class="fas fa-users-cog"></i> User Accounts
        </a>
        <a href="<?= url('admin/settings/index.php') ?>" class="nav-link <?= isActivePage('settings') ?>">
            <i class="fas fa-cog"></i> Portal Settings
        </a>
        <a href="<?= url('admin/settings/logs.php') ?>" class="nav-link">
            <i class="fas fa-shield-alt"></i> Activity Logs
        </a>

        <div class="px-3 pt-3 mt-3 border-top border-secondary">
            <a href="<?= url('index.php') ?>" target="_blank" class="btn btn-outline-light btn-sm w-100 rounded-pill mb-2">
                <i class="fas fa-external-link-alt me-1"></i> View Website
            </a>
            <a href="<?= url('auth/logout.php') ?>" class="btn btn-danger btn-sm w-100 rounded-pill">
                <i class="fas fa-sign-out-alt me-1"></i> Sign Out
            </a>
        </div>
    </nav>
</aside>

<!-- ==================== MAIN CONTENT WRAPPER ==================== -->
<div class="admin-main">
    <!-- Topbar -->
    <header class="admin-topbar d-flex align-items-center justify-content-between px-4">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-light d-lg-none" id="sidebarToggleBtn">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0 fw-bold text-dark d-none d-sm-block">
                <?= isset($pageTitle) ? e($pageTitle) : 'Administration Panel' ?>
            </h5>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?= asset($currentUser['avatar'] ?: 'assets/images/default-avatar.png') ?>" 
                         onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Admin&background=123B2A&color=fff&size=100';" 
                         alt="Avatar" class="rounded-circle me-2 border" style="width: 36px; height: 36px; object-fit: cover;">
                    <span class="fw-semibold small d-none d-md-inline"><?= e($currentUser['full_name'] ?? 'Admin') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2">
                    <li class="px-3 py-2 border-bottom">
                        <small class="text-muted d-block">Signed in as</small>
                        <strong class="text-dark"><?= e($currentUser['username'] ?? 'admin') ?></strong>
                    </li>
                    <li><a class="dropdown-item py-2" href="<?= url('admin/settings/index.php') ?>"><i class="fas fa-cog me-2 text-muted"></i> Settings</a></li>
                    <li><a class="dropdown-item py-2" href="<?= url('index.php') ?>" target="_blank"><i class="fas fa-globe me-2 text-muted"></i> Public Website</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= url('auth/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i> Log Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content Container -->
    <main class="flex-grow-1 p-4">
        <?= displayFlash() ?>
