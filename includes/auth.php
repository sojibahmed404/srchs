<?php
/**
 * Authentication & Session Management
 * Sirajganj Road Chowrasta High School Management System
 */

require_once __DIR__ . '/functions.php';

// Start secure session
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_name(SESSION_NAME);
        session_start();
    }

    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // every 5 min
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Initialize session on include
initSession();

/**
 * Authenticate user login
 */
function loginUser(string $username, string $password): array {
    // Rate limiting: check login attempts
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $recentAttempts = Database::fetchColumn(
        "SELECT COUNT(*) FROM activity_logs WHERE ip_address = ? AND action = 'login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
        [$ip]
    );

    if ($recentAttempts >= 5) {
        return ['success' => false, 'message' => 'Too many login attempts. Please try again after 15 minutes.'];
    }

    // Find user
    $user = Database::fetchOne(
        "SELECT u.*, r.role_name, r.display_name as role_display 
         FROM users u 
         JOIN roles r ON u.role_id = r.id 
         WHERE (u.username = ? OR u.email = ?) AND u.status = 'active' 
         LIMIT 1",
        [$username, $username]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        logActivity('login_failed', 'Failed login attempt for: ' . $username);
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    // Set session variables
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role_name'];
    $_SESSION['role_display'] = $user['role_display'];
    $_SESSION['avatar'] = $user['avatar'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // If teacher or headmaster, store teacher_id
    if ($user['role_name'] === 'teacher' || $user['role_name'] === 'headmaster') {
        $teacher = Database::fetchOne("SELECT id FROM teachers WHERE user_id = ?", [$user['id']]);
        if ($teacher) {
            $_SESSION['teacher_id'] = (int)$teacher['id'];
        }
    }

    // Update last login
    Database::execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

    // Log activity
    logActivity('login', 'User logged in successfully', $user['id']);

    // Regenerate session ID if headers not sent
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    return ['success' => true, 'role' => $user['role_name']];
}

/**
 * Logout user
 */
function logoutUser(): void {
    if (isLoggedIn()) {
        logActivity('logout', 'User logged out');
    }
    
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']);
}

/**
 * Check if user has a specific role
 */
function hasRole(string $role): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is admin
 */
function isAdmin(): bool {
    return hasRole('admin');
}

/**
 * Check if user is teacher
 */
function isTeacher(): bool {
    return hasRole('teacher');
}

/**
 * Check if user is Head Sir / Headmaster
 */
function isHeadmaster(): bool {
    return hasRole('headmaster');
}

/**
 * Check if user is Head Sir or Admin
 */
function isHeadmasterOrAdmin(): bool {
    return isAdmin() || isHeadmaster();
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please login to continue.');
        redirect(url('auth/login.php'));
    }
    
    // Session timeout
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
        logoutUser();
        initSession();
        setFlash('warning', 'Your session has expired. Please login again.');
        redirect(url('auth/login.php'));
    }
}

/**
 * Require admin role
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setFlash('danger', 'Access denied. Administrator privileges required.');
        redirect(url('auth/login.php'));
    }
}

/**
 * Require teacher role
 */
function requireTeacher(): void {
    requireLogin();
    if (!isTeacher() && !isHeadmaster()) {
        setFlash('danger', 'Access denied. Teacher privileges required.');
        redirect(url('auth/login.php'));
    }
}

/**
 * Require Head Sir role
 */
function requireHeadmaster(): void {
    requireLogin();
    if (!isHeadmaster()) {
        setFlash('danger', 'Access denied. Head Sir monitoring privileges required.');
        redirect(url('auth/login.php'));
    }
}

/**
 * Require Head Sir or Admin role
 */
function requireHeadmasterOrAdmin(): void {
    requireLogin();
    if (!isHeadmaster() && !isAdmin()) {
        setFlash('danger', 'Access denied. Authorized monitoring privileges required.');
        redirect(url('auth/login.php'));
    }
}

/**
 * Get current user data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return Database::fetchOne("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$_SESSION['user_id']]);
}

/**
 * Change user password
 */
function changePassword(int $userId, string $currentPassword, string $newPassword): array {
    $user = Database::fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }
    
    if (strlen($newPassword) < 8) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters.'];
    }
    
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    Database::execute("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
    
    logActivity('password_change', 'User changed their password', $userId);
    
    return ['success' => true, 'message' => 'Password changed successfully.'];
}
