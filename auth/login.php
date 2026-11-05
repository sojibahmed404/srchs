<?php
/**
 * Sirajganj Road Chowrasta High School - Authentication Portal
 * Multi-Role Gateway: Admin, Teacher, Head Sir (Headmaster)
 */

require_once dirname(__DIR__) . '/includes/auth.php';

// If already authenticated, redirect to appropriate role panel
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(url('admin/dashboard.php'));
    } elseif (isHeadmaster()) {
        redirect(url('headmaster/dashboard.php'));
    } elseif (isTeacher()) {
        redirect(url('teacher/dashboard.php'));
    }
}

$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');
$activeRole = getString('role', 'GET') ?: 'admin';
if (!in_array($activeRole, ['admin', 'teacher', 'headmaster'])) {
    $activeRole = 'admin';
}

// Process Login Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'Security validation token expired. Please try again.');
    } else {
        $username = getString('username', 'POST');
        $password = $_POST['password'] ?? '';
        $selectedRole = getString('login_role', 'POST') ?: $activeRole;

        if (empty($username) || empty($password)) {
            setFlash('danger', 'Please enter your username/email and password.');
        } else {
            $authResult = loginUser($username, $password);

            if ($authResult['success']) {
                $actualRole = $authResult['role'];

                // Optional role consistency check
                if ($selectedRole === 'admin' && $actualRole !== 'admin') {
                    setFlash('warning', 'Notice: You have been redirected to your authorized ' . ucfirst($actualRole) . ' dashboard.');
                } elseif ($selectedRole === 'headmaster' && $actualRole !== 'headmaster') {
                    setFlash('warning', 'Notice: You have been redirected to your authorized ' . ucfirst($actualRole) . ' dashboard.');
                }

                if ($actualRole === 'admin') {
                    setFlash('success', 'Welcome back, System Administrator!');
                    redirect(url('admin/dashboard.php'));
                } elseif ($actualRole === 'headmaster') {
                    setFlash('success', 'Welcome, Respected Head Sir! School monitoring dashboard ready.');
                    redirect(url('headmaster/dashboard.php'));
                } elseif ($actualRole === 'teacher') {
                    setFlash('success', 'Welcome to Teacher Dashboard!');
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
        :root {
            --primary: #123B2A;
            --secondary: #198754;
            --accent: #D9AD55;
            --accent-hover: #b88f3a;
        }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #123B2A 0%, #175239 50%, #198754 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            font-family: 'Inter', sans-serif;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 20% 20%, rgba(217, 173, 85, 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 80% 80%, rgba(25, 135, 84, 0.25) 0%, transparent 50%);
            pointer-events: none;
        }
        .login-wrapper {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 1;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.3);
            overflow: hidden;
            border-top: 6px solid var(--accent);
        }
        .role-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            background: #f1f5f3;
            padding: 8px;
            border-radius: 14px;
            margin-bottom: 22px;
        }
        .role-btn {
            border: none;
            background: transparent;
            padding: 10px 6px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #495057;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            transition: all 0.25s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .role-btn i {
            font-size: 1.25rem;
            transition: transform 0.2s ease;
        }
        .role-btn:hover {
            color: var(--primary);
            background: rgba(255,255,255,0.7);
        }
        .role-btn:hover i {
            transform: translateY(-2px);
        }
        .role-btn.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid rgba(217, 173, 85, 0.4);
        }
        .role-btn.active i {
            color: var(--accent);
        }
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e8f5e9;
            color: var(--primary);
            border: 1px solid #c8e6c9;
            margin-bottom: 12px;
        }
        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.2);
        }
        .btn-portal {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            letter-spacing: 0.3px;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-portal:hover {
            background: linear-gradient(135deg, #0d2a1e 0%, #146c43 100%);
            color: #ffffff;
            box-shadow: 0 6px 16px rgba(18, 59, 42, 0.3);
            transform: translateY(-1px);
        }
        .quick-hint {
            font-size: 0.76rem;
            color: #6c757d;
            background: #f8f9fa;
            border-left: 3px solid var(--accent);
            padding: 6px 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <!-- Header -->
        <div class="p-4 text-center bg-white border-bottom">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-2" 
                 style="width: 65px; height: 65px; background: linear-gradient(135deg, #123B2A, #198754); box-shadow: 0 6px 14px rgba(18,59,42,0.25);">
                <i class="fas fa-graduation-cap fs-2 text-warning"></i>
            </div>
            <h5 class="fw-bold mb-1" style="font-family: 'Hind Siliguri', sans-serif; color: #123B2A;">
                <?= e($schoolName) ?>
            </h5>
            <p class="text-muted small mb-0">Hatikumrul, Ullapara, Sirajganj | EIIN: 128456</p>
        </div>

        <!-- Body -->
        <div class="p-4 p-sm-5 pt-4">
            <!-- 3 SEPARATE LOGIN OPTIONS -->
            <label class="form-label small fw-bold text-muted text-uppercase mb-2" style="letter-spacing: 0.5px;">Select Portal Role</label>
            <div class="role-selector">
                <a href="?role=admin" class="role-btn <?= $activeRole === 'admin' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin</span>
                </a>
                <a href="?role=teacher" class="role-btn <?= $activeRole === 'teacher' ? 'active' : '' ?>">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Teacher</span>
                </a>
                <a href="?role=headmaster" class="role-btn <?= $activeRole === 'headmaster' ? 'active' : '' ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Head Sir</span>
                </a>
            </div>

            <!-- Role Context Badge -->
            <div class="text-center">
                <?php if ($activeRole === 'admin'): ?>
                    <span class="role-badge">
                        <i class="fas fa-shield-alt text-primary"></i> Administrative Control Panel
                    </span>
                <?php elseif ($activeRole === 'headmaster'): ?>
                    <span class="role-badge" style="background:#fff8e1; color:#856404; border-color:#ffeeba;">
                        <i class="fas fa-crown text-warning"></i> Head Sir School Monitoring Portal
                    </span>
                <?php else: ?>
                    <span class="role-badge" style="background:#e3f2fd; color:#0d47a1; border-color:#bbdefb;">
                        <i class="fas fa-book-reader text-info"></i> Teacher Routine & Attendance Portal
                    </span>
                <?php endif; ?>
            </div>

            <?= displayFlash() ?>

            <!-- Login Form -->
            <form action="<?= url('auth/login.php?role=' . $activeRole) ?>" method="POST" class="needs-validation" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="login_role" value="<?= e($activeRole) ?>">

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">
                        <?php if ($activeRole === 'headmaster'): ?>
                            Head Sir Username / Email
                        <?php elseif ($activeRole === 'teacher'): ?>
                            Teacher Username / Employee ID
                        <?php else: ?>
                            Admin Username or Email
                        <?php endif; ?>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="username" class="form-control border-start-0" 
                               value="<?= $activeRole === 'headmaster' ? 'headmaster' : ($activeRole === 'admin' ? 'admin' : '') ?>"
                               placeholder="<?= $activeRole === 'headmaster' ? 'headmaster' : ($activeRole === 'teacher' ? 'e.g. aminul.haque' : 'admin or email') ?>" 
                               required autofocus>
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
                            <i class="fas fa-eye text-muted"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                    <label class="form-check-label small text-muted" for="rememberMe">Remember my session</label>
                </div>

                <button type="submit" class="btn btn-portal w-100 shadow-sm mb-3">
                    <i class="fas fa-sign-in-alt me-1"></i> Sign In to <?= $activeRole === 'headmaster' ? 'Head Sir' : ucfirst($activeRole) ?> Portal
                </button>
            </form>

            <div class="text-center pt-3 border-top">
                <a href="<?= url('index.php') ?>" class="text-muted small text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Return to Public Website
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Password visibility toggle
    document.querySelectorAll('.toggle-password-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
</script>
</body>
</html>
