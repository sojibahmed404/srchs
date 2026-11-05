<?php
/**
 * Sirajganj Road Chowrasta High School - System Activity Logs
 */

$pageTitle = 'Activity Logs';
require_once __DIR__ . '/../includes/header.php';

$page = max(1, getInt('page'));
$search = getString('search');

$where = "WHERE 1=1";
$params = [];
if (!empty($search)) {
    $where .= " AND (l.action LIKE ? OR l.description LIKE ? OR u.username LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$baseQuery = "SELECT l.*, u.username, u.full_name, r.display_name as role_name 
              FROM activity_logs l 
              LEFT JOIN users u ON l.user_id = u.id 
              LEFT JOIN roles r ON u.role_id = r.id 
              {$where} 
              ORDER BY l.id DESC";

$pagination = paginate($baseQuery, $params, $page, 20);
$logs = $pagination['data'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Security Audit Trail & Activity Logs</h4>
        <p class="text-muted small mb-0">Complete historical logging of user logins, updates, admissions, and marks entries.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/settings/index.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
            &larr; Portal Settings
        </a>
    </div>
</div>

<!-- Search Filter -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
    <form action="<?= url('admin/settings/logs.php') ?>" method="GET" class="row g-3 align-items-center">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search action, user, or log text..." value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-success rounded-pill px-4 flex-grow-1">Search Logs</button>
            <?php if ($search): ?>
                <a href="<?= url('admin/settings/logs.php') ?>" class="btn btn-outline-secondary rounded-pill">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 170px;">Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="small text-muted"><?= formatDate($log['created_at'], 'd M Y, h:i A') ?></td>
                            <td>
                                <?php if ($log['username']): ?>
                                    <div class="fw-bold text-dark"><?= e($log['full_name']) ?></div>
                                    <small class="badge bg-light text-muted border">@<?= e($log['username']) ?> (<?= e($log['role_name']) ?>)</small>
                                <?php else: ?>
                                    <span class="text-muted small">System / Public</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace small">
                                    <?= e($log['action']) ?>
                                </span>
                            </td>
                            <td class="small text-secondary"><?= e($log['description']) ?></td>
                            <td><code class="small text-muted"><?= e($log['ip_address'] ?: '127.0.0.1') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">No activity logs recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <?php
            $baseUrl = url("admin/settings/logs.php?search=" . urlencode($search));
            echo renderPagination($pagination, $baseUrl);
            ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
