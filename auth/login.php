<?php
/**
 * Sirajganj Road Chowrasta High School - Authentication Portal
 */

require_once dirname(__DIR__) . '/includes/auth.php';

// If already authenticated, redirect to appropriate role panel
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(url('admin/dashboard.php'));
    } elseif (isTeacher()) {
        redirect(url('teacher/dashboard.php'));
    }
}

$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');

// Process Login Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'Security validation token expired. Please try again.');
    } else {
        $username = getString('username', 'POST');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            setFlash('danger', 'Please enter your username/email and password.');
        } else {
            $authResult = loginUser($username, $password);

            if ($authResult['success']) {
                if ($authResult['role'] === 'admin') {
                    setFlash('success', 'Welcome back, Administrator!');
                    redirect(url('admin/dashboard.php'));
                } elseif ($authResult['role'] === 'teacher') {
                    setFlash('success', 'Welcome to Teacher Portal!');
                    redirect(url('teacher/dashboard.php'));
                } else {
                    redirect(url('index.php'));
                }
            } else {
                setFlash('danger', $authResult['message']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - <?= e($schoolName) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #123B2A 0%, #198754 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 15px;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.25);
            overflow: hidden;
            border-top: 6px solid #D9AD55;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="p-4 p-sm-5 text-center bg-white border-bottom">
        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 70px; height: 70px; background-color: #123B2A;">
            <i class="fas fa-school fs-2"></i>
        </div>
        <h4 class="fw-bold mb-1" style="font-family: 'Hind Siliguri', sans-serif; color: #123B2A;">
            <?= e($schoolName) ?>
        </h4>
        <p class="text-muted small mb-0">Management Portal Login (Admin & Teachers)</p>
    </div>

    <div class="p-4 p-sm-5 pt-4">
        <?= displayFlash() ?>

        <form action="<?= url('auth/login.php') ?>" method="POST" class="needs-validation" novalidate>
            <?= csrfField() ?>

            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" placeholder="admin or email address" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label small fw-semibold text-dark mb-0">Password</label>
                    <a href="<?= url('auth/forgot-password.php') ?>" class="small text-muted text-decoration-none">Forgot?</a>
                </div>
                <div class="input-group mt-1">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="passwordField" class="form-control border-start-0 border-end-0" placeholder="••••••••" required>
                    <button type="button" class="input-group-text bg-light border-start-0 toggle-password-btn" data-target="passwordField">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                <label class="form-check-label small text-muted" for="rememberMe">Remember my session</label>
            </div>

            <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-bold shadow-sm mb-3">
                <i class="fas fa-sign-in-alt me-1"></i> Sign In to Portal
            </button>
        </form>

        <div class="text-center pt-3 border-top">
            <a href="<?= url('index.php') ?>" class="text-muted small text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Back to Public Website
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('assets/js/main.js') ?>"></script>
</body>
</html>
