<?php
/**
 * Public Website Header Template
 * Sirajganj Road Chowrasta High School Management System
 */

require_once __DIR__ . '/auth.php';

// Get settings
$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');
$schoolPhone = getSetting('school_phone', '+880 1712-345678');
$schoolEmail = getSetting('school_email', 'info@srchs.edu.bd');
$schoolAddress = getSetting('school_address', 'Hatikumrul, Ullapara, Sirajganj, Bangladesh');
$schoolFacebook = getSetting('facebook_url', '#');
$schoolYoutube = getSetting('youtube_url', '#');
$metaTitle = $pageTitle ?? $schoolName;
$metaDesc = getSetting('website_meta_description', '');
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($metaDesc) ?>">
    <title><?= e($metaTitle) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

    <!-- ========== Top Contact Bar ========== -->
    <div class="top-bar" style="background-color: #123B2A;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-2">
                <!-- Left: Contact Info -->
                <div class="d-flex align-items-center gap-4">
                    <a href="tel:<?= e($schoolPhone) ?>" class="text-white text-decoration-none small">
                        <i class="fas fa-phone-alt me-1"></i> <?= e($schoolPhone) ?>
                    </a>
                    <a href="mailto:<?= e($schoolEmail) ?>" class="text-white text-decoration-none small">
                        <i class="fas fa-envelope me-1"></i> <?= e($schoolEmail) ?>
                    </a>
                </div>
                <!-- Right: Social Media Links -->
                <div class="d-flex align-items-center gap-3">
                    <a href="<?= e($schoolFacebook) ?>" target="_blank" rel="noopener noreferrer" class="text-white" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="<?= e($schoolYoutube) ?>" target="_blank" rel="noopener noreferrer" class="text-white" title="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== Main Navigation ========== -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <!-- School Logo & Name -->
            <a class="navbar-brand d-flex align-items-center" href="<?= url('index.php') ?>">
                <svg width="45" height="45" viewBox="0 0 45 45" class="me-2">
                    <circle cx="22.5" cy="22.5" r="22.5" fill="#123B2A"/>
                    <text x="22.5" y="26" text-anchor="middle" fill="#D9AD55" font-size="12" font-weight="700" font-family="Inter, sans-serif">SRCHS</text>
                </svg>
                <span class="fw-bold" style="color: #123B2A; font-family: 'Hind Siliguri', sans-serif; font-size: 1.15rem; line-height: 1.2;">
                    <?= e($schoolName) ?>
                </span>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Links -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

                    <!-- Home -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActivePage('index') ?>" href="<?= url('index.php') ?>">Home</a>
                    </li>

                    <!-- About Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= isActivePage('about') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">About</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= url('about.php') ?>">About Us</a></li>
                            <li><a class="dropdown-item" href="<?= url('history.php') ?>">School History</a></li>
                            <li><a class="dropdown-item" href="<?= url('headmaster-message.php') ?>">Headmaster's Message</a></li>
                            <li><a class="dropdown-item" href="<?= url('facilities.php') ?>">Facilities</a></li>
                        </ul>
                    </li>

                    <!-- Academic Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= isActivePage('academic') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Academic</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= url('classes.php') ?>">Class Information</a></li>
                            <li><a class="dropdown-item" href="<?= url('subjects.php') ?>">Subjects</a></li>
                            <li><a class="dropdown-item" href="<?= url('routine.php') ?>">Class Routine</a></li>
                        </ul>
                    </li>

                    <!-- Teachers & Staff -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActivePage('teachers') ?>" href="<?= url('teachers.php') ?>">Teachers &amp; Staff</a>
                    </li>

                    <!-- Admission Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= isActivePage('admission') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Admission</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= url('admission.php') ?>">Admission Info</a></li>
                            <li><a class="dropdown-item" href="<?= url('admission-apply.php') ?>">Apply Online</a></li>
                        </ul>
                    </li>

                    <!-- Notice Board -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActivePage('notices') ?>" href="<?= url('notices.php') ?>">Notice Board</a>
                    </li>

                    <!-- Results -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActivePage('results') ?>" href="<?= url('results.php') ?>">Results</a>
                    </li>

                    <!-- Gallery -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActivePage('gallery') ?>" href="<?= url('gallery.php') ?>">Gallery</a>
                    </li>

                    <!-- Contact -->
                    <li class="nav-item">
                        <a class="nav-link <?= isActivePage('contact') ?>" href="<?= url('contact.php') ?>">Contact</a>
                    </li>

                    <!-- Login / Dashboard Button -->
                    <li class="nav-item ms-lg-2">
                        <?php if (isLoggedIn()): ?>
                            <a class="btn btn-sm px-3 py-2" href="<?= url('admin/index.php') ?>" style="background-color: #198754; color: #fff;">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        <?php else: ?>
                            <a class="btn btn-sm px-3 py-2" href="<?= url('auth/login.php') ?>" style="background-color: #123B2A; color: #fff;">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <!-- ========== Flash Messages ========== -->
    <?php $flashHtml = displayFlash(); ?>
    <?php if ($flashHtml): ?>
    <div class="container mt-3">
        <?= $flashHtml ?>
    </div>
    <?php endif; ?>
