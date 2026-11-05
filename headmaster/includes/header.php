<?php
/**
 * Head Sir Portal - Shared Header
 * Sirajganj Road Chowrasta High School
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireHeadmaster();

$currentUser = getCurrentUser();
$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');
$currentSession = getCurrentSession();
$sessionId = $currentSession ? (int)$currentSession['id'] : 1;

// Get Headmaster record
$headmasterTeacher = getCurrentTeacher();

// Calculate quick pending attendance count for today
$todayDate = date('Y-m-d');
$totalSectionsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM sections WHERE status = 'active'");
$submittedCountToday = (int)Database::fetchColumn("SELECT COUNT(*) FROM attendance_submissions WHERE attendance_date = ? AND session_id = ?", [$todayDate, $sessionId]);
$pendingCountToday = max(0, $totalSectionsCount - $submittedCountToday);

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>Head Sir Portal - <?= e($schoolName) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        :root {
            --primary: #123B2A;
            --primary-dark: #0c271b;
            --secondary: #198754;
            --accent: #D9AD55;
            --accent-hover: #b88f3a;
            --sidebar-width: 270px;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f8;
            color: #2b2f33;
            overflow-x: hidden;
        }
        .head-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(180deg, #123B2A 0%, #0d291d 100%);
            color: #ffffff;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .head-sidebar .sidebar-brand {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.15);
        }
        .head-sidebar .nav-link {
            color: rgba(255,255,255,0.78);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.92rem;
            font-weight: 500;
            border-left: 4px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .head-sidebar .nav-link i {
            width: 20px;
            font-size: 1.05rem;
            text-align: center;
            color: var(--accent);
        }
        .head-sidebar .nav-link:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.07);
            border-left-color: rgba(217, 173, 85, 0.5);
        }
        .head-sidebar .nav-link.active {
            color: #ffffff;
            background: rgba(217, 173, 85, 0.15);
            border-left-color: var(--accent);
            font-weight: 600;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }
        .head-topbar {
            height: 68px;
            background: #ffffff;
            border-bottom: 1px solid #e5e9ec;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .content-body {
            padding: 28px;
            flex: 1;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 22px;
            border: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        @media (max-width: 991.98px) {
            .head-sidebar {
                transform: translateX(-100%);
            }
            .head-sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
        @media print {
            .head-sidebar, .head-topbar, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .content-body {
                padding: 0 !important;
            }
        }
    </style>
</head>
<body>

<!-- Head Sir Sidebar -->
<aside class="head-sidebar" id="headSidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
                 style="width: 44px; height: 44px; background: var(--accent); color: var(--primary);">
                <i class="fas fa-crown fs-5"></i>
            </div>
            <div class="overflow-hidden">
                <div class="fw-bold text-white text-truncate" style="font-size: 0.95rem; font-family: 'Hind Siliguri', sans-serif;">
                    <?= e($schoolName) ?>
                </div>
                <small class="text-warning fw-semibold text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                    Head Sir Monitoring Portal
                </small>
            </div>
        </div>
    </div>

    <!-- User Profile Summary in Sidebar -->
    <div class="p-3 border-bottom border-light border-opacity-10 bg-black bg-opacity-20">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= resolveUploadUrl($currentUser['avatar'] ?? '', 'assets/images/default-avatar.png') ?>" 
                 class="rounded-circle border border-warning" style="width: 38px; height: 38px; object-fit: cover;">
            <div class="overflow-hidden">
                <div class="fw-bold text-white small text-truncate"><?= e($currentUser['full_name']) ?></div>
                <div class="text-white-50" style="font-size: 0.72rem;">Principal / Headmaster</div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-grow-1 overflow-auto py-3">
        <nav class="nav flex-column">
            <div class="px-3 pb-2 text-uppercase text-white-50 fw-bold" style="font-size: 0.68rem; letter-spacing: 0.8px;">
                Monitoring & Oversight
            </div>
            <a href="<?= url('headmaster/dashboard.php') ?>" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>School Overview</span>
            </a>
            <a href="<?= url('headmaster/students.php') ?>" class="nav-link <?= $currentPage === 'students' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i>
                <span>Student Directory & Search</span>
            </a>
            <a href="<?= url('headmaster/absences.php') ?>" class="nav-link <?= $currentPage === 'absences' ? 'active' : '' ?>">
                <i class="fas fa-user-clock"></i>
                <span>Absence Monitoring</span>
            </a>
            <a href="<?= url('headmaster/teachers.php') ?>" class="nav-link <?= $currentPage === 'teachers' ? 'active' : '' ?>">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teacher & Routine Tracking</span>
                <?php if ($pendingCountToday > 0): ?>
                    <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.7rem;"><?= $pendingCountToday ?> Pending</span>
                <?php endif; ?>
            </a>
            <a href="<?= url('headmaster/reports.php') ?>" class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Printable Official Reports</span>
            </a>

            <div class="px-3 pt-4 pb-2 text-uppercase text-white-50 fw-bold" style="font-size: 0.68rem; letter-spacing: 0.8px;">
                Direct Operations
            </div>
            <a href="<?= url('teacher/attendance.php') ?>" class="nav-link">
                <i class="fas fa-clipboard-check"></i>
                <span>Classroom Attendance Taker</span>
            </a>
            <a href="<?= url('index.php') ?>" class="nav-link" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>Public Website</span>
            </a>
            <a href="<?= url('auth/logout.php') ?>" class="nav-link text-danger mt-3">
                <i class="fas fa-sign-out-alt text-danger"></i>
                <span>Sign Out</span>
            </a>
        </nav>
    </div>

    <!-- Session Badge Footer -->
    <div class="p-3 border-top border-light border-opacity-10 text-center" style="font-size: 0.75rem;">
        <span class="text-white-50">Academic Session:</span>
        <span class="badge bg-warning text-dark ms-1"><?= e($currentSession['session_name'] ?? '2026') ?></span>
    </div>
</aside>

<!-- Main Wrapper -->
<div class="main-content">
    <!-- Topbar -->
    <header class="head-topbar no-print">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary btn-sm d-lg-none" id="toggleSidebarBtn">
                <i class="fas fa-bars"></i>
            </button>
            <div class="d-none d-md-block">
                <span class="text-muted small">Date:</span>
                <span class="fw-bold text-dark small"><?= date('l, d F Y') ?></span>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <a href="<?= url('headmaster/reports.php') ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
                <i class="fas fa-print me-1"></i> Quick Reports
            </a>
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle rounded-pill d-flex align-items-center gap-2 px-3 py-1 border" data-bs-toggle="dropdown">
                    <img src="<?= resolveUploadUrl($currentUser['avatar'] ?? '', 'assets/images/default-avatar.png') ?>" 
                         class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                    <span class="small fw-semibold text-dark"><?= e($currentUser['full_name']) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                    <li><h6 class="dropdown-header small">Head Sir / Principal</h6></li>
                    <li><a class="dropdown-item small" href="<?= url('headmaster/dashboard.php') ?>"><i class="fas fa-tachometer-alt me-2 text-muted"></i>Dashboard</a></li>
                    <li><a class="dropdown-item small" href="<?= url('teacher/profile.php') ?>"><i class="fas fa-user me-2 text-muted"></i>My Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item small text-danger" href="<?= url('auth/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Content Body -->
    <main class="content-body">

