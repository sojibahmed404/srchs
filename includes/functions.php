<?php
/**
 * Helper Functions
 * Sirajganj Road Chowrasta High School Management System
 */

require_once __DIR__ . '/db.php';

/**
 * Escape output for HTML (XSS protection)
 */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get a setting value from the database
 */
function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (empty($cache)) {
        $rows = Database::fetchAll("SELECT setting_key, setting_value FROM settings");
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

/**
 * Get the current academic session
 */
function getCurrentSession(): ?array {
    return Database::fetchOne("SELECT * FROM academic_sessions WHERE is_current = 1 AND status = 'active' LIMIT 1");
}

/**
 * Get the current session ID
 */
function getCurrentSessionId(): int {
    $session = getCurrentSession();
    return $session ? (int)$session['id'] : 1;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF field
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(generateCSRFToken()) . '">';
}

/**
 * Clean and sanitize user input
 */
function cleanInput($value): string {
    if (is_array($value)) return '';
    return trim(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
}

/**
 * Verify CSRF token (supports direct parameter, POST, or GET)
 */
function verifyCSRFToken(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Resolve upload URL with fallback
 */
function resolveUploadUrl(?string $path, string $default = 'assets/images/default-avatar.png'): string {
    if (!empty($path)) {
        $fullPath = ROOT_PATH . '/' . ltrim($path, '/');
        if (file_exists($fullPath)) {
            return asset($path);
        }
    }
    return asset($default);
}

/**
 * Get current teacher profile record with joined user information
 */
function getCurrentTeacher(?int $userId = null): ?array {
    $uid = $userId ?? ($_SESSION['user_id'] ?? 0);
    if (!$uid) return null;
    return Database::fetchOne(
        "SELECT t.*, u.full_name, u.email, u.phone, u.avatar, u.username, u.status as user_status
         FROM teachers t
         JOIN users u ON t.user_id = u.id
         WHERE t.user_id = ?",
        [$uid]
    );
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Set a flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message as Bootstrap alert
 */
function displayFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    
    $type = $flash['type'];
    $icon = match($type) {
        'success' => 'fa-check-circle',
        'danger', 'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle',
        default => 'fa-info-circle'
    };
    if ($type === 'error') $type = 'danger';
    
    return '<div class="alert alert-' . e($type) . ' alert-dismissible fade show" role="alert">
        <i class="fas ' . $icon . ' me-2"></i>' . e($flash['message']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

/**
 * Format date
 */
function formatDate(?string $date, string $format = 'd M Y'): string {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Format date and time
 */
function formatDateTime(?string $datetime, string $format = 'd M Y, h:i A'): string {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Format time
 */
function formatTime(?string $time, string $format = 'h:i A'): string {
    if (empty($time)) return '';
    return date($format, strtotime($time));
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Generate a unique filename for uploads
 */
function generateFileName(string $originalName): string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid('file_', true) . '.' . $ext;
}

/**
 * Upload a file
 */
function uploadFile(array $file, string $destination, array $allowedTypes = []): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds the limit (5MB).'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type.'];
    }

    $fileName = generateFileName($file['name']);
    $destDir = UPLOADS_PATH . $destination;

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $filePath = $destDir . DIRECTORY_SEPARATOR . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return [
            'success' => true,
            'filename' => $fileName,
            'path' => 'uploads/' . $destination . '/' . $fileName
        ];
    }

    return ['success' => false, 'message' => 'Failed to save file.'];
}

/**
 * Delete a file
 */
function deleteFile(string $path): bool {
    $fullPath = ROOT_PATH . $path;
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

/**
 * Log user activity
 */
function logActivity(string $action, string $description, ?int $userId = null): void {
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    try {
        Database::insert(
            "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [$userId, $action, $description, $ip, $ua]
        );
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

/**
 * Paginate results
 */
function paginate(string $baseQuery, array $params, int $page, int $perPage = RECORDS_PER_PAGE): array {
    // Count total
    $countQuery = "SELECT COUNT(*) FROM (" . $baseQuery . ") AS total_count";
    $total = (int)Database::fetchColumn($countQuery, $params);
    
    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    $dataQuery = $baseQuery . " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
    $data = Database::fetchAll($dataQuery, $params);
    
    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Render pagination HTML
 */
function renderPagination(array $pagination, string $baseUrl): string {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous
    $prevClass = $pagination['has_prev'] ? '' : ' disabled';
    $prevPage = $pagination['page'] - 1;
    $html .= '<li class="page-item' . $prevClass . '"><a class="page-link" href="' . e($baseUrl) . '&page=' . $prevPage . '"><i class="fas fa-chevron-left"></i></a></li>';
    
    // Page numbers
    $start = max(1, $pagination['page'] - 2);
    $end = min($pagination['total_pages'], $pagination['page'] + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($baseUrl) . '&page=1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = $i === $pagination['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $activeClass . '"><a class="page-link" href="' . e($baseUrl) . '&page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . e($baseUrl) . '&page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
    }
    
    // Next
    $nextClass = $pagination['has_next'] ? '' : ' disabled';
    $nextPage = $pagination['page'] + 1;
    $html .= '<li class="page-item' . $nextClass . '"><a class="page-link" href="' . e($baseUrl) . '&page=' . $nextPage . '"><i class="fas fa-chevron-right"></i></a></li>';
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Calculate grade and grade point from marks (Bangladesh Board system)
 */
function calculateGrade(float $marks): array {
    $scale = Database::fetchOne(
        "SELECT grade_name, grade_point, remarks FROM grading_scales WHERE ? BETWEEN min_mark AND max_mark LIMIT 1",
        [$marks]
    );
    
    if ($scale) {
        return [
            'grade' => $scale['grade_name'],
            'point' => (float)$scale['grade_point'],
            'remarks' => $scale['remarks']
        ];
    }
    
    return ['grade' => 'F', 'point' => 0.00, 'remarks' => 'Fail'];
}

/**
 * Calculate percentage from marks
 */
function calculatePercentage(float $obtained, float $total): float {
    if ($total <= 0) return 0;
    return round(($obtained / $total) * 100, 2);
}

/**
 * Get a safe integer from request
 */
function getInt(string $key, string $method = 'GET'): int {
    $source = $method === 'POST' ? $_POST : $_GET;
    return (int)($source[$key] ?? 0);
}

/**
 * Get a safe string from request
 */
function getString(string $key, string $method = 'GET'): string {
    $source = $method === 'POST' ? $_POST : $_GET;
    return trim((string)($source[$key] ?? ''));
}

/**
 * Check if request is AJAX
 */
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Get the asset URL
 */
function asset(string $path): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Get base URL for linking
 */
function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Check if current page matches
 */
function isActivePage(string $page): string {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    
    if ($page === $currentPage || $page === $currentDir) {
        return 'active';
    }
    return '';
}
