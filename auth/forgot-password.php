<?php
/**
 * Sirajganj Road Chowrasta High School - Password Reset Request
 */

require_once dirname(__DIR__) . '/includes/auth.php';

$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'Security validation expired.');
    } else {
        $email = getString('email', 'POST');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Please enter a valid account email address.');
        } else {
            $user = Database::fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
            if ($user) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                Database::insert(
                    "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)",
                    [$email, $token, $expires]
                );

                logActivity('password_reset_request', "Reset token requested for {$email}", $user['id']);

                setFlash('success', 'A password reset link has been prepared. For security reasons, please contact the System Administrator to confirm authorization.');
            } else {
                // Prevent email enumeration
                setFlash('info', 'If an active account corresponds to that email, instructions have been generated.');
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
    <title>Forgot Password - <?= e($schoolName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
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
        .reset-card {
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

<div class="reset-card p-4 p-sm-5">
    <div class="text-center mb-4">
        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 60px; height: 60px; background-color: #123B2A;">
            <i class="fas fa-key fs-3"></i>
        </div>
        <h4 class="fw-bold mb-1" style="color: #123B2A;">Password Recovery</h4>
        <p class="text-muted small">Enter your account email to initiate reset</p>
    </div>

    <?= displayFlash() ?>

    <form action="<?= url('auth/forgot-password.php') ?>" method="POST" class="needs-validation" novalidate>
        <?= csrfField() ?>
        <div class="mb-4">
            <label class="form-label small fw-semibold">Account Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control" placeholder="user@srchs.edu.bd" required autofocus>
            </div>
        </div>

        <button type="submit" class="btn btn-success w-100 py-2 rounded-pill fw-bold mb-3">
            Send Reset Instructions
        </button>
    </form>

    <div class="text-center pt-3 border-top">
        <a href="<?= url('auth/login.php') ?>" class="text-muted small text-decoration-none">
            <i class="fas fa-arrow-left me-1"></i> Back to Portal Login
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
