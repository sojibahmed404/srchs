<?php
/**
 * Sirajganj Road Chowrasta High School - Website & Portal Settings
 */

$pageTitle = 'Portal Settings';
require_once __DIR__ . '/../includes/header.php';

// Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $postedSettings = $_POST['settings'] ?? [];
        try {
            foreach ($postedSettings as $key => $val) {
                $val = trim($val);
                Database::execute(
                    "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                    [$key, $val]
                );
            }
            logActivity('settings_update', 'Updated institutional website settings');
            setFlash('success', 'Portal and school settings updated successfully.');
        } catch (Exception $e) {
            setFlash('danger', 'Error updating settings: ' . $e->getMessage());
        }
    }
    redirect(url('admin/settings/index.php'));
}

// Fetch all settings
$allSettings = [];
$rows = Database::fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($rows as $r) {
    $allSettings[$r['setting_key']] = $r['setting_value'];
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Website & Institutional Settings</h4>
        <p class="text-muted small mb-0">Configure institution credentials, contact details, leadership statements, and admission windows.</p>
    </div>
    <div>
        <a href="<?= url('admin/settings/logs.php') ?>" class="btn btn-outline-secondary rounded-pill px-3">
            <i class="fas fa-shield-alt me-1"></i> View Activity Logs
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white mb-4">
    <form action="<?= url('admin/settings/index.php') ?>" method="POST">
        <?= csrfField() ?>

        <!-- 1. General Institutional Info -->
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
            <i class="fas fa-landmark text-success me-2"></i> 1. General School Information
        </h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">School Name (Official)</label>
                <input type="text" name="settings[school_name]" class="form-control" value="<?= e($allSettings['school_name'] ?? 'Sirajganj Road Chowrasta High School') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Short Name / Acronym</label>
                <input type="text" name="settings[school_short_name]" class="form-control" value="<?= e($allSettings['school_short_name'] ?? 'SRCHS') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">EIIN Number</label>
                <input type="text" name="settings[school_eiin]" class="form-control" value="<?= e($allSettings['school_eiin'] ?? '128456') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Year Established</label>
                <input type="text" name="settings[school_estd]" class="form-control" value="<?= e($allSettings['school_estd'] ?? '1974') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Affiliated Education Board</label>
                <input type="text" name="settings[school_board]" class="form-control" value="<?= e($allSettings['school_board'] ?? 'Rajshahi Education Board') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Currency Symbol</label>
                <input type="text" name="settings[currency_symbol]" class="form-control" value="<?= e($allSettings['currency_symbol'] ?? '৳') ?>">
            </div>
        </div>

        <!-- 2. Contact Details -->
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
            <i class="fas fa-phone-alt text-success me-2"></i> 2. Public Contact Details
        </h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Primary Office Phone</label>
                <input type="text" name="settings[school_phone]" class="form-control" value="<?= e($allSettings['school_phone'] ?? '+880 1712-345678') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Official Email Address</label>
                <input type="email" name="settings[school_email]" class="form-control" value="<?= e($allSettings['school_email'] ?? 'info@srchs.edu.bd') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Online Admission Status</label>
                <select name="settings[online_admission_status]" class="form-select">
                    <option value="open" <?= ($allSettings['online_admission_status'] ?? '') === 'open' ? 'selected' : '' ?>>Open (Accepting Submissions)</option>
                    <option value="closed" <?= ($allSettings['online_admission_status'] ?? '') === 'closed' ? 'selected' : '' ?>>Closed (Window Ended)</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Full Campus Address</label>
                <input type="text" name="settings[school_address]" class="form-control" value="<?= e($allSettings['school_address'] ?? 'Hatikumrul, Ullapara, Sirajganj, Bangladesh') ?>">
            </div>
        </div>

        <!-- 3. Leadership & Quotes -->
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
            <i class="fas fa-user-tie text-success me-2"></i> 3. Leadership & Homepage Content
        </h5>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Headmaster Full Name</label>
                <input type="text" name="settings[headmaster_name]" class="form-control" value="<?= e($allSettings['headmaster_name'] ?? 'Md. Rafiqul Islam') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Headmaster Title / Designation</label>
                <input type="text" name="settings[headmaster_designation]" class="form-control" value="<?= e($allSettings['headmaster_designation'] ?? 'Headmaster, Sirajganj Road Chowrasta High School') ?>">
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Headmaster Welcome Quote</label>
                <textarea name="settings[headmaster_quote]" class="form-control" rows="2"><?= e($allSettings['headmaster_quote'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- 4. SEO & Meta Details -->
        <h5 class="fw-bold text-dark mb-3 border-bottom pb-2">
            <i class="fas fa-search text-success me-2"></i> 4. SEO & Meta Information
        </h5>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label small fw-semibold">Website Title Bar</label>
                <input type="text" name="settings[website_meta_title]" class="form-control" value="<?= e($allSettings['website_meta_title'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label class="form-label small fw-semibold">Meta Description</label>
                <textarea name="settings[website_meta_description]" class="form-control" rows="2"><?= e($allSettings['website_meta_description'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="text-end pt-3 border-top">
            <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold shadow-sm">
                <i class="fas fa-save me-1"></i> Save System Settings
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
